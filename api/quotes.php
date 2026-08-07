<?php
/**
 * Ummah Directory — Fundi quote requests (delivered via WhatsApp)
 *
 *   POST api/quotes.php  {fundi_id, name, phone, description}
 *     → stores the request and returns a wa.me link pre-filled with the
 *       customer's details so they can message the fundi directly on WhatsApp.
 *
 * Anonymous users allowed (a quote request only needs a name + phone).
 */
require __DIR__ . '/_bootstrap.php';

require_method('POST');
rate_limit('quote', 10, 900);
require_csrf();

$db = Database::getInstance();
$body = json_body();

$fundiId     = (int)($body['fundi_id'] ?? 0);
$name        = sanitize_line($body['name'] ?? '', 100);
$phone       = trim($body['phone'] ?? '');
$description = sanitize_text($body['description'] ?? '', 2000);

if ($fundiId < 1) {
    json_err('Invalid fundi', 422);
}
if ($name === '' || mb_strlen($name) > 100) {
    json_err('Please provide your name', 422);
}
if ($phone === '' || !preg_match('/^[+0-9 ()-]{7,20}$/', $phone)) {
    json_err('Please provide a valid phone number', 422);
}
if ($description === '' || mb_strlen($description) > 2000) {
    json_err('Please describe the work you need done', 422);
}

$fundi = $db->fetchOne(
    'SELECT f.id, f.whatsapp, f.phone, f.profession, u.full_name
       FROM fundis f JOIN users u ON u.id = f.user_id
      WHERE f.id = ? AND f.is_available = 1',
    [$fundiId]
);
if (!$fundi) {
    json_err('Fundi not found', 404);
}

// resolve the WhatsApp number (prefer whatsapp, fall back to phone)
$waRaw = $fundi['whatsapp'] ?: $fundi['phone'];
$waNumber = preg_replace('/[^0-9]/', '', $waRaw);
if (strlen($waNumber) === 9) {
    $waNumber = '254' . $waNumber;
} elseif (strlen($waNumber) === 10 && $waNumber[0] === '0') {
    $waNumber = '254' . substr($waNumber, 1);
}

// store the request (for the fundi to track)
$quoteId = $db->insert(
    'INSERT INTO quote_requests (fundi_id, user_id, customer_name, customer_phone, description, status)
     VALUES (?, ?, ?, ?, ?, \'pending\')',
    [
        $fundiId,
        Auth::isLoggedIn() ? Auth::user()['id'] : null,
        $name,
        $phone,
        $description,
    ]
);

// pre-filled WhatsApp message
$msg = "Salaam! I'm {$name}. I'd like a quote for the following:\n\n{$description}\n\n"
     . "You can reach me on {$phone}.";
$waLink = 'https://wa.me/' . $waNumber . '?text=' . rawurlencode($msg);

json_ok([
    'quote_id'      => $quoteId,
    'whatsapp_link' => $waLink,
    'fundi_name'    => $fundi['full_name'],
    'fundi_wa'      => $waNumber,
    'message'       => 'Request saved — send it to ' . $fundi['full_name'] . ' on WhatsApp',
], 201);
