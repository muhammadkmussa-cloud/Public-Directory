<?php
/**
 * Ummah Directory — Notifications API
 *
 *   GET  api/notifications.php            → my notifications (login)
 *   GET  api/notifications.php?unread=1   → {unread_count} (login)
 *   POST api/notifications.php            → {action:'read', id} | {action:'read_all'} (login+CSRF)
 *
 * Notifications are created by other endpoints (reviews, claims, donations…).
 */
require __DIR__ . '/_bootstrap.php';

$db = Database::getInstance();
$method = $_SERVER['REQUEST_METHOD'];
$user = require_login();

/* ================= list ================= */
if ($method === 'GET' && !isset($_GET['unread'])) {
    $rows = $db->fetchAll(
        'SELECT id, type, title, message, link, is_read, created_at
           FROM notifications WHERE user_id = ?
          ORDER BY created_at DESC LIMIT 30',
        [$user['id']]
    );
    $unread = (int)$db->fetchValue(
        'SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0',
        [$user['id']]
    );
    json_ok(['items' => $rows, 'unread_count' => $unread]);
}

/* ================= unread count ================= */
if ($method === 'GET' && isset($_GET['unread'])) {
    $unread = (int)$db->fetchValue(
        'SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0',
        [$user['id']]
    );
    json_ok(['unread_count' => $unread]);
}

/* ================= mark read ================= */
if ($method === 'POST') {
    rate_limit('notif', 60, 300);
    require_csrf();
    $body = json_body();
    $action = $body['action'] ?? '';

    if ($action === 'read') {
        $id = (int)($body['id'] ?? 0);
        $db->execute(
            'UPDATE notifications SET is_read = 1, read_at = NOW() WHERE id = ? AND user_id = ?',
            [$id, $user['id']]
        );
        json_ok(['read' => true]);
    }

    if ($action === 'read_all') {
        $db->execute(
            'UPDATE notifications SET is_read = 1, read_at = NOW() WHERE user_id = ? AND is_read = 0',
            [$user['id']]
        );
        json_ok(['read_all' => true]);
    }

    json_err('Unknown action', 404);
}

json_err('Unknown action', 404);
