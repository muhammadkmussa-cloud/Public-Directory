<?php
/**
 * Umma Directory — Donations API
 *
 *   POST api/donations.php
 *     {charity_id, campaign_id?, amount, donor_name?, donor_email?, donor_phone?,
 *      is_anonymous?, payment_method: 'mpesa'|'paypal'|'bank'}
 *
 *   - M-Pesa: initiates an STK push (sandbox mode simulates success).
 *   - PayPal: returns a payment URL (sandbox or live) to complete off-site.
 *   - Bank: records the pledge as pending.
 */
require __DIR__ . '/_bootstrap.php';
require_once __DIR__ . '/../includes/Mpesa.php';

require_method('POST');
rate_limit('donation', 20, 300);
require_csrf();

$db = Database::getInstance();
$body = json_body();

$charityId    = (int)($body['charity_id'] ?? 0);
$campaignId   = (int)($body['campaign_id'] ?? 0);
$amount       = round((float)($body['amount'] ?? 0), 2);
$method       = $body['payment_method'] ?? 'mpesa';
$donorName    = trim($body['donor_name'] ?? '');
$donorEmail   = strtolower(trim($body['donor_email'] ?? ''));
$donorPhone   = trim($body['donor_phone'] ?? '');
$isAnonymous  = !empty($body['is_anonymous']);
$message      = trim($body['message'] ?? '');

if ($charityId < 1 || !$db->fetchOne('SELECT id FROM charities WHERE id = ?', [$charityId])) {
    json_err('Charity not found', 404);
}
if ($amount < 10) {
    json_err('Minimum donation is KSh 10', 422);
}
if ($amount > 500000) {
    json_err('Maximum single donation is KSh 500,000', 422);
}
if (!in_array($method, ['mpesa', 'paypal', 'bank'], true)) {
    json_err('Invalid payment method', 422);
}
if ($campaignId) {
    $campaign = $db->fetchOne(
        "SELECT id, charity_id, title FROM campaigns WHERE id = ? AND status = 'active'",
        [$campaignId]
    );
    if (!$campaign) json_err('Campaign not found', 404);
    if ((int)$campaign['charity_id'] !== $charityId) json_err('Campaign does not belong to this charity', 422);
} else {
    $campaign = null;
}
if ($method === 'mpesa' && $donorPhone === '') {
    json_err('M-Pesa number is required for mobile money', 422);
}

$status = 'pending';
$paymentInfo = null;
$paymentUrl = null;

if ($method === 'mpesa') {
    $ref = $campaign ? 'Campaign #' . $campaign['id'] : 'Charity #' . $charityId;
    $stk = Mpesa::stkPush($donorPhone, $amount, $ref, 'Donation');
    if (!$stk['ok']) {
        json_err($stk['error'] ?? 'M-Pesa payment failed', 502);
    }
    // sandbox/simulation completes immediately; live waits for the callback
    if (!empty($stk['simulated'])) {
        $status = 'completed';
    }
    $paymentInfo = ['provider' => 'mpesa', 'phone' => $stk['phone'] ?? $donorPhone, 'simulated' => !empty($stk['simulated'])];
} elseif ($method === 'paypal') {
    $base = PAYPAL_MODE === 'live'
        ? 'https://www.paypal.com/cgi-bin/webscr'
        : 'https://www.sandbox.paypal.com/cgi-bin/webscr';
    $item = ($campaign ? $campaign['title'] : 'Donation') . ' — ' . ($db->fetchValue('SELECT name FROM charities WHERE id = ?', [$charityId]));
    $paymentUrl = $base . '?' . http_build_query([
        'cmd' => '_donations',
        'business' => PAYPAL_BUSINESS_EMAIL,
        'item_name' => $item,
        'amount' => $amount,
        'currency_code' => 'USD',
        'no_shipping' => '1',
    ]);
    $paymentInfo = ['provider' => 'paypal', 'url' => $paymentUrl];
} else {
    $paymentInfo = ['provider' => 'bank', 'message' => 'Please complete the bank transfer to finalize your donation.'];
}

$donationId = $db->insert(
    "INSERT INTO donations (campaign_id, charity_id, user_id, amount, currency, payment_method,
                            donor_name, donor_email, donor_phone, is_anonymous, message, status)
     VALUES (?, ?, ?, ?, 'KES', ?, ?, ?, ?, ?, ?, ?)",
    [
        $campaignId ?: null,
        $charityId,
        Auth::isLoggedIn() ? Auth::user()['id'] : null,
        $amount,
        $method,
        $isAnonymous ? null : ($donorName ?: null),
        $donorEmail ?: null,
        $method === 'mpesa' ? $donorPhone : null,
        $isAnonymous ? 1 : 0,
        $message ?: null,
        $status,
    ]
);

// notify the charity's admin
$charityAdmin = $db->fetchValue('SELECT user_id FROM charities WHERE id = ?', [$charityId]);
if ($charityAdmin) {
    $charityName = $db->fetchValue('SELECT name FROM charities WHERE id = ?', [$charityId]);
    notify($db, $charityAdmin, 'donation',
        'New donation: KSh ' . number_format($amount) . ($campaign ? ' to ' . $campaign['title'] : ''),
        'You received a donation for ' . $charityName . ($isAnonymous ? ' (anonymous)' : ' from ' . ($donorName ?: 'a supporter')),
        'dashboard.html');
}

// refresh campaign totals (charity totals are always computed from donations)
if ($status === 'completed' && $campaignId) {
    $agg = $db->fetchOne(
        "SELECT COALESCE(SUM(amount),0) AS raised, COUNT(*) AS donors
           FROM donations WHERE campaign_id = ? AND status IN ('completed','pending')",
        [$campaignId]
    );
    $db->execute('UPDATE campaigns SET raised_amount = ?, donor_count = ? WHERE id = ?',
        [round($agg['raised'], 2), (int)$agg['donors'], $campaignId]);
}

json_ok([
    'donation_id'   => $donationId,
    'status'        => $status,
    'amount'        => $amount,
    'payment'       => $paymentInfo,
    'payment_url'   => $paymentUrl,
    'message'       => $status === 'completed'
        ? 'Thank you for your donation! 🙏'
        : 'Donation recorded — please complete the payment to finalize it.',
], $status === 'completed' ? 201 : 200);
