<?php
/**
 * Ummah Directory — Reports / moderation API
 *
 *   GET  api/reports.php?action=mine    → my reports (login)
 *   POST api/reports.php                → {action:'create', reportable_id,
 *                                           reportable_type, reason, description} (login+CSRF)
 *
 * reportable_type: business | mosque | fundi | review | photo | user
 * reason: spam | fake | inappropriate | scam | duplicate | closed | other
 */
require __DIR__ . '/_bootstrap.php';

$db = Database::getInstance();
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? ($_GET['action'] ?? '');

const REPORT_TYPES = ['business', 'mosque', 'fundi', 'review', 'photo', 'user'];
const REPORT_REASONS = ['spam', 'fake', 'inappropriate', 'scam', 'duplicate', 'closed', 'other'];

function report_target_exists($db, $type, $id)
{
    switch ($type) {
        case 'business': return (bool)$db->fetchOne('SELECT id FROM businesses WHERE id = ?', [$id]);
        case 'mosque':   return (bool)$db->fetchOne('SELECT id FROM mosques WHERE id = ?', [$id]);
        case 'fundi':    return (bool)$db->fetchOne('SELECT id FROM fundis WHERE id = ?', [$id]);
        case 'review':   return (bool)$db->fetchOne('SELECT id FROM reviews WHERE id = ?', [$id]);
        case 'photo':    return (bool)$db->fetchOne('SELECT id FROM business_photos WHERE id = ?', [$id])
                              || (bool)$db->fetchOne('SELECT id FROM mosque_photos WHERE id = ?', [$id])
                              || (bool)$db->fetchOne('SELECT id FROM fundi_photos WHERE id = ?', [$id]);
        case 'user':     return (bool)$db->fetchOne('SELECT id FROM users WHERE id = ?', [$id]);
    }
    return false;
}

/* ================= mine ================= */
if ($method === 'GET' && $action === 'mine') {
    $user = require_login();
    $rows = $db->fetchAll(
        'SELECT reportable_id, reportable_type, reason, description, status, created_at
           FROM reports WHERE reporter_id = ? ORDER BY created_at DESC LIMIT 50',
        [$user['id']]
    );
    json_ok($rows);
}

/* ================= create ================= */
if ($method === 'POST') {
    $user = require_login();
    require_csrf();
    $body = json_body();
    $action = $body['action'] ?? ($_GET['action'] ?? '');

    if ($action !== 'create') {
        json_err('Unknown action', 404);
    }

    $type = $body['reportable_type'] ?? '';
    $id = (int)($body['reportable_id'] ?? 0);
    $reason = $body['reason'] ?? '';
    $description = trim($body['description'] ?? '');

    if (!in_array($type, REPORT_TYPES, true) || $id < 1) {
        json_err('Invalid report target', 422);
    }
    if (!in_array($reason, REPORT_REASONS, true)) {
        json_err('Invalid report reason', 422);
    }
    if (mb_strlen($description) > 1000) {
        json_err('Description too long (max 1000 chars)', 422);
    }
    if (!report_target_exists($db, $type, $id)) {
        json_err('That item does not exist', 404);
    }

    // prevent duplicate pending reports from the same user on the same target
    $dup = $db->fetchOne(
        'SELECT id FROM reports
          WHERE reporter_id = ? AND reportable_id = ? AND reportable_type = ? AND status = \'pending\'',
        [$user['id'], $id, $type]
    );
    if ($dup) {
        json_err('You already reported this item — our team will review it', 409);
    }

    $db->insert(
        'INSERT INTO reports (reporter_id, reportable_id, reportable_type, reason, description, status)
         VALUES (?, ?, ?, ?, ?, \'pending\')',
        [$user['id'], $id, $type, $reason, $description ?: null]
    );

    json_ok(['reported' => true], 201);
}

/* ================= admin: list reports (queue) ================= */
if ($method === 'GET' && $action === 'queue') {
    $admin = require_login();
    if ($admin['user_type'] !== 'admin') {
        json_err('Admins only', 403);
    }
    $rows = $db->fetchAll(
        'SELECT r.id, r.reportable_id, r.reportable_type, r.reason, r.description,
                r.status, r.admin_notes, r.created_at,
                u.full_name AS reporter_name
           FROM reports r
           JOIN users u ON u.id = r.reporter_id
          ORDER BY (r.status = \'pending\') DESC, r.created_at ASC
          LIMIT 100'
    );
    json_ok($rows);
}

/* ================= admin: update report status ================= */
if ($method === 'POST' && ($body_action = (json_body()['action'] ?? '')) === 'admin_resolve') {
    $admin = require_login();
    if ($admin['user_type'] !== 'admin') {
        json_err('Admins only', 403);
    }
    require_csrf();
    $b = json_body();
    $reportId = (int)($b['report_id'] ?? 0);
    $status = in_array($b['status'] ?? '', ['resolved', 'rejected'], true) ? $b['status'] : 'resolved';
    $notes = trim($b['notes'] ?? '');

    $db->execute(
        'UPDATE reports SET status = ?, admin_notes = ?, resolved_by = ?, resolved_at = NOW() WHERE id = ?',
        [$status, $notes ?: null, $admin['id'], $reportId]
    );
    json_ok(['resolved' => true]);
}

json_err('Unknown action', 404);
