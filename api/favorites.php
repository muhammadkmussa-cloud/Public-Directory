<?php
/**
 * Umma Directory — Favorites (bookmarks) API
 *
 *   GET  api/favorites.php?action=mine                             → saved items (login)
 *   GET  api/favorites.php?action=status&favoritable_id=1&favoritable_type=business
 *                                                                  → {saved: bool} (login)
 *   POST api/favorites.php  {action:'toggle', favoritable_id, favoritable_type} (login+CSRF)
 *                                                                  → {saved: bool}
 */
require __DIR__ . '/_bootstrap.php';

$db = Database::getInstance();
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

const VALID_TYPES = ['business', 'mosque', 'fundi', 'charity'];

function table_for_type($type)
{
    return $type === 'business' ? 'businesses' : ($type === 'mosque' ? 'mosques' : ($type === 'fundi' ? 'fundis' : 'charities'));
}

/* ================= status ================= */
if ($method === 'GET' && $action === 'status') {
    $user = require_login();
    $id = (int)($_GET['favoritable_id'] ?? 0);
    $type = $_GET['favoritable_type'] ?? '';
    if (!in_array($type, VALID_TYPES, true) || $id < 1) {
        json_err('Invalid target', 422);
    }
    $saved = (bool)$db->fetchOne(
        'SELECT 1 FROM favorites WHERE user_id = ? AND favoritable_id = ? AND favoritable_type = ?',
        [$user['id'], $id, $type]
    );
    json_ok(['saved' => $saved]);
}

/* ================= mine ================= */
if ($method === 'GET' && $action === 'mine') {
    $user = require_login();
    $rows = $db->fetchAll(
        'SELECT favoritable_id, favoritable_type, created_at
           FROM favorites WHERE user_id = ?
          ORDER BY created_at DESC',
        [$user['id']]
    );

    $out = [];
    foreach ($rows as $f) {
        $type = $f['favoritable_type'];
        $id = (int)$f['favoritable_id'];
        $item = ['type' => $type, 'id' => $id, 'url' => $type . '.html?id=' . $id];

        if ($type === 'business') {
            $b = $db->fetchOne(
                'SELECT b.name, b.city, b.rating_average, b.review_count,
                        (SELECT photo_path FROM business_photos WHERE business_id = b.id
                          ORDER BY is_primary DESC, id DESC LIMIT 1) AS primary_photo
                   FROM businesses b WHERE b.id = ?',
                [$id]
            );
            if (!$b) continue;
            $item += ['name' => $b['name'], 'city' => $b['city'], 'rating_average' => $b['rating_average'],
                      'review_count' => $b['review_count'], 'primary_photo' => $b['primary_photo']];
        } elseif ($type === 'mosque') {
            $m = $db->fetchOne(
                'SELECT m.name, m.city, m.rating_average, m.review_count,
                        (SELECT photo_path FROM mosque_photos WHERE mosque_id = m.id
                          ORDER BY is_primary DESC, id DESC LIMIT 1) AS primary_photo
                   FROM mosques m WHERE m.id = ?',
                [$id]
            );
            if (!$m) continue;
            $item += ['name' => $m['name'], 'city' => $m['city'], 'rating_average' => $m['rating_average'],
                      'review_count' => $m['review_count'], 'primary_photo' => $m['primary_photo']];
        } elseif ($type === 'fundi') {
            $f2 = $db->fetchOne(
                'SELECT u.full_name AS name, f.city, f.rating_average, f.review_count, u.profile_photo AS primary_photo
                   FROM fundis f JOIN users u ON u.id = f.user_id WHERE f.id = ?',
                [$id]
            );
            if (!$f2) continue;
            $item += ['name' => $f2['name'], 'city' => $f2['city'], 'rating_average' => $f2['rating_average'],
                      'review_count' => $f2['review_count'], 'primary_photo' => $f2['primary_photo']];
        } else {
            $c = $db->fetchOne('SELECT name, city FROM charities WHERE id = ?', [$id]);
            if (!$c) continue;
            $item += ['name' => $c['name'], 'city' => $c['city'], 'rating_average' => null,
                      'review_count' => 0, 'primary_photo' => null];
        }
        $out[] = $item;
    }
    json_ok($out);
}

/* ================= toggle ================= */
if ($method === 'POST' && $action === 'toggle') {
    rate_limit('favorite', 120, 300);
    $user = require_login();
    require_csrf();
    $body = json_body();
    $id = (int)($body['favoritable_id'] ?? 0);
    $type = $body['favoritable_type'] ?? '';

    if (!in_array($type, VALID_TYPES, true) || $id < 1) {
        json_err('Invalid target', 422);
    }
    $table = table_for_type($type);
    if (!$db->fetchOne("SELECT id FROM $table WHERE id = ?", [$id])) {
        json_err('Listing not found', 404);
    }

    $existing = $db->fetchOne(
        'SELECT 1 FROM favorites WHERE user_id = ? AND favoritable_id = ? AND favoritable_type = ?',
        [$user['id'], $id, $type]
    );
    if ($existing) {
        $db->execute(
            'DELETE FROM favorites WHERE user_id = ? AND favoritable_id = ? AND favoritable_type = ?',
            [$user['id'], $id, $type]
        );
        $saved = false;
    } else {
        $db->insert(
            'INSERT INTO favorites (user_id, favoritable_id, favoritable_type) VALUES (?, ?, ?)',
            [$user['id'], $id, $type]
        );
        $saved = true;
    }
    json_ok(['saved' => $saved]);
}

json_err('Unknown action', 404);
