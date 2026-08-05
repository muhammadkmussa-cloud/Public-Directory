<?php
/**
 * Categories API
 *   GET api/categories.php?type=business|mosque|fundi|charity
 */
require __DIR__ . '/_bootstrap.php';

require_method('GET');

$type = $_GET['type'] ?? 'business';

$db = Database::getInstance();
$rows = $db->fetchAll(
    "SELECT id, name, name_sw, name_ar, slug, icon, display_order
       FROM categories
      WHERE type = ? AND is_active = 1
      ORDER BY display_order, name",
    [$type]
);

json_ok($rows);
