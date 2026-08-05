<?php
/**
 * Check-in API
 *   POST api/checkin.php  {checkinable_id, checkinable_type: 'business'|'mosque', note}
 * Requires login + CSRF.
 */
require __DIR__ . '/_bootstrap.php';

require_method('POST');
$user = require_login();
require_csrf();

$db = Database::getInstance();
$body = json_body();

$checkinableId   = (int)($body['checkinable_id'] ?? 0);
$checkinableType = $body['checkinable_type'] ?? '';
$note            = trim($body['note'] ?? '');

if (!in_array($checkinableType, ['business', 'mosque'], true) || $checkinableId < 1) {
    json_err('Invalid check-in target', 422);
}

$table = $checkinableType === 'business' ? 'businesses' : 'mosques';
if (!$db->fetchOne("SELECT id FROM $table WHERE id = ?", [$checkinableId])) {
    json_err('Listing not found', 404);
}

// cooldown: one check-in per user per listing per day
$today = date('Y-m-d');
$recent = $db->fetchOne(
    'SELECT id FROM checkins
      WHERE user_id = ? AND checkinable_id = ? AND checkinable_type = ? AND DATE(created_at) = ?',
    [$user['id'], $checkinableId, $checkinableType, $today]
);
if ($recent) {
    json_err('You already checked in here today', 409);
}

$db->begin();
try {
    $db->insert(
        'INSERT INTO checkins (user_id, checkinable_id, checkinable_type, note) VALUES (?, ?, ?, ?)',
        [$user['id'], $checkinableId, $checkinableType, $note ?: null]
    );
    // businesses carry a checkin_count column; mosques don't (count rows instead)
    if ($checkinableType === 'business') {
        $db->execute('UPDATE businesses SET checkin_count = checkin_count + 1 WHERE id = ?', [$checkinableId]);
    }
    $db->execute('UPDATE users SET total_checkins = total_checkins + 1 WHERE id = ?', [$user['id']]);
    $db->commit();
} catch (Throwable $e) {
    $db->rollback();
    error_log('Check-in failed: ' . $e->getMessage());
    json_err('Could not save check-in', 500);
}

if ($checkinableType === 'business') {
    $count = (int)$db->fetchValue('SELECT checkin_count FROM businesses WHERE id = ?', [$checkinableId]);
} else {
    $count = (int)$db->fetchValue(
        'SELECT COUNT(*) FROM checkins WHERE checkinable_id = ? AND checkinable_type = ?',
        [$checkinableId, $checkinableType]
    );
}
json_ok(['checkin_count' => $count], 201);
