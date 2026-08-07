<?php
/**
 * Ummah Directory — Donations API
 *
 *   POST api/donations.php
 *     {charity_id, campaign_id?, amount, donor_name?, donor_email?, donor_phone?,
 *      is_anonymous?, payment_method: 'mpesa'|'paypal'|'bank'}
 *
 *   - M-Pesa: initiates an STK push (sandbox mode simulates success). In live
 *     mode the payment is confirmed via the M-Pesa callback:
 *       POST api/donations.php?action=mpesa_callback   (server-to-server, no CSRF)
 *   - PayPal: returns a payment URL (sandbox or live) to complete off-site.
 *     Completion is confirmed via the PayPal IPN:
 *       POST api/donations.php?action=paypal_ipn       (server-to-server, no CSRF)
 *   - Bank: records the pledge as pending.
 */
require __DIR__ . '/_bootstrap.php';
require_once __DIR__ . '/../includes/Mpesa.php';

$db = Database::getInstance();
$action = $_GET['action'] ?? ($_POST['action'] ?? '');

/* ============================================================
 * Refresh the raised/donor totals for a donation's campaign.
 * Called after any donation is created or confirmed.
 * ============================================================ */
function refresh_donation_totals($db, $donation)
{
    if (empty($donation['campaign_id'])) {
        return;
    }
    $agg = $db->fetchOne(
        "SELECT COALESCE(SUM(amount),0) AS raised, COUNT(*) AS donors
           FROM donations WHERE campaign_id = ? AND status IN ('completed','pending')",
        [$donation['campaign_id']]
    );
    $db->execute('UPDATE campaigns SET raised_amount = ?, donor_count = ? WHERE id = ?',
        [round((float)$agg['raised'], 2), (int)$agg['donors'], $donation['campaign_id']]);
}

/* ============================================================
 * M-Pesa callback (Safaricom → us). This is a server-to-server
 * request with no session/CSRF, so it is handled before those checks.
 * ============================================================ */
if ($action === 'mpesa_callback') {
    $raw = file_get_contents('php://input');
    $payload = json_decode($raw ?: '', true);
    $body = $payload['Body']['stkCallback'] ?? null;

    if (!$body || empty($body['CheckoutRequestID'])) {
        http_response_code(400);
        header('Content-Type: application/json');
        echo json_encode(['ResultCode' => 1, 'ResultDesc' => 'Invalid callback payload']);
        exit;
    }

    $checkoutId = $body['CheckoutRequestID'];
    $resultCode = (int)($body['ResultCode'] ?? 1);

    // Extract metadata from callback (amount, receipt, etc.)
    $transAmount = null;
    $receipt = null;
    foreach ($body['CallbackMetadata']['Item'] ?? [] as $item) {
        $name = $item['Name'] ?? '';
        if ($name === 'Amount' || $name === 'TransAmount') {
            $transAmount = (float)($item['Value'] ?? 0);
        }
        if ($name === 'MpesaReceiptNumber' && !empty($item['Value'])) {
            $receipt = $item['Value'];
        }
    }

    $donation = $db->fetchOne(
        "SELECT id, campaign_id, status, amount FROM donations
          WHERE transaction_id = ? AND payment_method = 'mpesa' AND status = 'pending'",
        [$checkoutId]
    );

    // Log callback for audit (IP, user-agent, checkoutId, resultCode, amount)
    $callbackIp = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $callbackUa = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
    error_log("M-Pesa callback: checkoutId=$checkoutId, resultCode=$resultCode, transAmount=$transAmount, ip=$callbackIp, ua=$callbackUa");

    if ($donation && $resultCode === 0) {
        // Validate amount matches the pending donation
        $donationAmount = (float)$donation['amount'];
        if ($transAmount !== null && abs($transAmount - $donationAmount) > 0.01) {
            error_log("M-Pesa amount mismatch: callback=$transAmount, stored=$donationAmount, checkoutId=$checkoutId");
            // Mark as failed due to amount mismatch
            $db->execute("UPDATE donations SET status = 'failed', updated_at = NOW() WHERE id = ?", [$donation['id']]);
            http_response_code(200);
            header('Content-Type: application/json');
            echo json_encode(['ResultCode' => 1, 'ResultDesc' => 'Amount mismatch']);
            exit;
        }

        $db->execute(
            'UPDATE donations SET status = ?, transaction_id = COALESCE(?, transaction_id), updated_at = NOW() WHERE id = ?',
            ['completed', $receipt, $donation['id']]
        );
        $donation['status'] = 'completed';
        $donation['transaction_id'] = $receipt ?: $checkoutId;
        refresh_donation_totals($db, $donation);

        // Notify the charity admin that the payment cleared.
        $charityId = $db->fetchValue('SELECT charity_id FROM donations WHERE id = ?', [$donation['id']]);
        $admin = $db->fetchValue('SELECT user_id FROM charities WHERE id = ?', [$charityId]);
        if ($admin) {
            $amount = (float)$db->fetchValue('SELECT amount FROM donations WHERE id = ?', [$donation['id']]);
            notify($db, $admin, 'donation', 'Payment confirmed: KSh ' . number_format($amount),
                'An M-Pesa donation has been completed.', 'dashboard');
        }
    } elseif ($donation && $resultCode !== 0) {
        $db->execute("UPDATE donations SET status = 'failed', updated_at = NOW() WHERE id = ?", [$donation['id']]);
    }

    // Safaricom expects this exact acknowledgement.
    http_response_code(200);
    header('Content-Type: application/json');
    echo json_encode(['ResultCode' => 0, 'ResultDesc' => 'Success']);
    exit;
}

/* ============================================================
 * PayPal IPN (PayPal → us). Server-to-server, no CSRF.
 * ============================================================ */
if ($action === 'paypal_ipn') {
    // Re-post the raw POST back to PayPal for verification.
    $verifyUrl = PAYPAL_MODE === 'live'
        ? 'https://ipnpb.paypal.com/cgi-bin/webscr'
        : 'https://ipnpb.sandbox.paypal.com/cgi-bin/webscr';
    $verified = false;

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $req = 'cmd=_notify-validate&' . file_get_contents('php://input');
        $ch = curl_init($verifyUrl);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $req,
            CURLOPT_HTTPHEADER     => ['Connection: Close'],
            CURLOPT_TIMEOUT        => 20,
        ]);
        $resp = trim(curl_exec($ch) ?: '');
        curl_close($ch);
        $verified = ($resp === 'VERIFIED');
    }

    // Log verification result for audit
    $ipnIp = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    if ($verified) {
        error_log("PayPal IPN: VERIFIED, ip=$ipnIp");
    } else {
        error_log("PayPal IPN: verification FAILED, ip=$ipnIp, resp=$resp");
        // Do NOT auto-accept in sandbox — require genuine verification
        http_response_code(200);
        exit;
    }

    $paymentStatus = strtolower($_POST['payment_status'] ?? '');
    $custom = $_POST['custom'] ?? '';
    $donationId = 0;
    if (preg_match('/^donation_(\d+)$/', $custom, $m)) {
        $donationId = (int)$m[1];
    }

    if ($verified && $paymentStatus === 'completed' && $donationId) {
        $donation = $db->fetchOne(
            "SELECT id, campaign_id, status FROM donations WHERE id = ? AND payment_method = 'paypal' AND status = 'pending'",
            [$donationId]
        );
        if ($donation) {
            $txnId = !empty($_POST['txn_id']) ? substr($_POST['txn_id'], 0, 100) : null;
            $db->execute(
                'UPDATE donations SET status = ?, transaction_id = COALESCE(?, transaction_id), updated_at = NOW() WHERE id = ?',
                ['completed', $txnId, $donation['id']]
            );
            $donation['status'] = 'completed';
            refresh_donation_totals($db, $donation);

            $charityId = $db->fetchValue('SELECT charity_id FROM donations WHERE id = ?', [$donation['id']]);
            $admin = $db->fetchValue('SELECT user_id FROM charities WHERE id = ?', [$charityId]);
            if ($admin) {
                $amount = (float)$db->fetchValue('SELECT amount FROM donations WHERE id = ?', [$donation['id']]);
                notify($db, $admin, 'donation', 'Payment confirmed: KSh ' . number_format($amount),
                    'A PayPal donation has been completed.', 'dashboard');
            }
        }
    }

    http_response_code(200);
    exit;
}

/* ============================================================
 * Donation status (GET) — lets the donor/frontend poll whether a
 * pending M-Pesa/PayPal payment has cleared.
 *   GET api/donations.php?action=status&donation_id=X
 * ============================================================ */
if ($action === 'status') {
    require_method('GET');
    $uid = require_login()['id'];           // 401 when not logged in
    $donationId = (int)($_GET['donation_id'] ?? 0);
    $d = $db->fetchOne(
        'SELECT id, user_id, status, amount, payment_method, created_at FROM donations WHERE id = ?',
        [$donationId]
    );
    if (!$d) json_err('Donation not found', 404);
    // Only the donor who created the donation while logged in may poll its
    // status. Anonymous donations (user_id NULL) are never readable via this
    // endpoint — those donors get confirmation through their payment channel.
    if (!$d['user_id'] || (int)$d['user_id'] !== (int)$uid) {
        json_err('Not your donation', 403);
    }
    json_ok([
        'donation_id'   => (int)$d['id'],
        'status'        => $d['status'],
        'amount'        => (float)$d['amount'],
        'payment_method'=> $d['payment_method'],
        'created_at'    => $d['created_at'],
    ]);
}

/* ============================================================
 * Normal donation creation (browser → us)
 * ============================================================ */
require_method('POST');
rate_limit('donation', 20, 300);
require_csrf();

$body = json_body();

$charityId    = (int)($body['charity_id'] ?? 0);
$campaignId   = (int)($body['campaign_id'] ?? 0);
$amount       = round((float)($body['amount'] ?? 0), 2);
$method       = $body['payment_method'] ?? 'mpesa';
$donorName    = sanitize_line($body['donor_name'] ?? '', 100);
    $donorEmail   = strtolower(trim($body['donor_email'] ?? ''));
    $donorPhone   = trim($body['donor_phone'] ?? '');
    $isAnonymous  = !empty($body['is_anonymous']);
    $message      = sanitize_text($body['message'] ?? '', 1000);

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
$txnRef = null;

if ($method === 'mpesa') {
    $ref = $campaign ? 'Campaign #' . $campaign['id'] : 'Charity #' . $charityId;
    $stk = Mpesa::stkPush($donorPhone, $amount, $ref, 'Donation');
    if (!$stk['ok']) {
        json_err($stk['error'] ?? 'M-Pesa payment failed', 502);
    }
    // sandbox/simulation completes immediately; live waits for the callback
    if (!empty($stk['simulated'])) {
        $status = 'completed';
    } else {
        $txnRef = $stk['checkout_request_id'] ?? null;
    }
    $paymentInfo = ['provider' => 'mpesa', 'phone' => $stk['phone'] ?? $donorPhone, 'simulated' => !empty($stk['simulated'])];
} elseif ($method === 'paypal') {
    $paymentInfo = ['provider' => 'paypal'];
} else {
    $paymentInfo = ['provider' => 'bank', 'message' => 'Please complete the bank transfer to finalize your donation.'];
}

$donationId = $db->insert(
    "INSERT INTO donations (campaign_id, charity_id, user_id, amount, currency, payment_method,
                            transaction_id, donor_name, donor_email, donor_phone, is_anonymous, message, status)
     VALUES (?, ?, ?, ?, 'KES', ?, ?, ?, ?, ?, ?, ?, ?)",
    [
        $campaignId ?: null,
        $charityId,
        Auth::isLoggedIn() ? Auth::user()['id'] : null,
        $amount,
        $method,
        $txnRef,
        $isAnonymous ? null : ($donorName ?: null),
        $donorEmail ?: null,
        $method === 'mpesa' ? $donorPhone : null,
        $isAnonymous ? 1 : 0,
        $message ?: null,
        $status,
    ]
);

// Build the PayPal payment URL (needs the donation id for the IPN 'custom' field).
if ($method === 'paypal') {
    $base = PAYPAL_MODE === 'live'
        ? 'https://www.paypal.com/cgi-bin/webscr'
        : 'https://www.sandbox.paypal.com/cgi-bin/webscr';
    $item = ($campaign ? $campaign['title'] : 'Donation') . ' — ' . ($db->fetchValue('SELECT name FROM charities WHERE id = ?', [$charityId]));
    // Convert the KES amount into the PayPal currency at the configured rate.
    $paypalAmount = PAYPAL_EXCHANGE_RATE > 0 ? round($amount / PAYPAL_EXCHANGE_RATE, 2) : $amount;
    $paymentUrl = $base . '?' . http_build_query([
        'cmd' => '_donations',
        'business' => PAYPAL_BUSINESS_EMAIL,
        'item_name' => $item,
        'amount' => $paypalAmount,
        'currency_code' => PAYPAL_CURRENCY,
        'no_shipping' => '1',
        'custom' => 'donation_' . $donationId,
        'notify_url' => APP_URL . '/api/donations.php?action=paypal_ipn',
    ]);
    $paymentInfo['url'] = $paymentUrl;
}

// notify the charity's admin
$charityAdmin = $db->fetchValue('SELECT user_id FROM charities WHERE id = ?', [$charityId]);
if ($charityAdmin) {
    $charityName = $db->fetchValue('SELECT name FROM charities WHERE id = ?', [$charityId]);
    notify($db, $charityAdmin, 'donation',
        'New donation: KSh ' . number_format($amount) . ($campaign ? ' to ' . $campaign['title'] : ''),
        'You received a donation for ' . $charityName . ($isAnonymous ? ' (anonymous)' : ' from ' . ($donorName ?: 'a supporter')),
        'dashboard');
}

// refresh campaign totals (charity totals are always computed from donations)
if ($status === 'completed' && $campaignId) {
    refresh_donation_totals($db, ['campaign_id' => $campaignId]);
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
