<?php
/**
 * Umma Directory — Search suggestions (autocomplete)
 *
 *   GET api/suggest.php?q=plum
 *   → [{type:'business'|'mosque'|'fundi'|'category', label, sub, url}, ...] (max 10)
 */
require __DIR__ . '/_bootstrap.php';

require_method('GET');

$q = trim($_GET['q'] ?? '');
if (mb_strlen($q) < 2) {
    json_ok([]);
}

$db = Database::getInstance();
$like = '%' . $db->escapeLike($q) . '%';
$out = [];

// businesses (by name)
foreach ($db->fetchAll(
    "SELECT id, name, city FROM businesses
      WHERE name LIKE ? AND is_open = 1
      ORDER BY review_count DESC LIMIT 4",
    [$like]
) as $b) {
    $out[] = [
        'type' => 'business',
        'label' => $b['name'],
        'sub' => 'Business · ' . ($b['city'] ?? ''),
        'url' => 'business.html?id=' . (int)$b['id'],
    ];
}

// mosques (by name)
foreach ($db->fetchAll(
    "SELECT id, name, city FROM mosques
      WHERE name LIKE ?
      ORDER BY review_count DESC LIMIT 4",
    [$like]
) as $m) {
    $out[] = [
        'type' => 'mosque',
        'label' => $m['name'],
        'sub' => 'Mosque · ' . ($m['city'] ?? ''),
        'url' => 'mosque.html?id=' . (int)$m['id'],
    ];
}

// fundis (by profession or name)
foreach ($db->fetchAll(
    "SELECT f.id, f.profession, u.full_name, f.city
       FROM fundis f JOIN users u ON u.id = f.user_id
      WHERE f.profession LIKE ? OR u.full_name LIKE ?
      LIMIT 4",
    [$like, $like]
) as $f) {
    $out[] = [
        'type' => 'fundi',
        'label' => $f['full_name'],
        'sub' => ($f['profession'] ?? 'Fundi') . ' · ' . ($f['city'] ?? ''),
        'url' => 'fundi.html?id=' . (int)$f['id'],
    ];
}

// categories (by name)
foreach ($db->fetchAll(
    "SELECT slug, name, type FROM categories
      WHERE name LIKE ? AND is_active = 1 LIMIT 4",
    [$like]
) as $c) {
    $url = $c['type'] === 'business'
        ? 'businesses.html?category=' . rawurlencode($c['slug'])
        : ($c['type'] === 'mosque'
            ? 'mosques.html?q=' . rawurlencode($c['name'])
            : 'fundis.html?skill=' . rawurlencode($c['name']));
    $out[] = [
        'type' => 'category',
        'label' => $c['name'],
        'sub' => 'Category',
        'url' => $url,
    ];
}

json_ok(array_slice($out, 0, 10));
