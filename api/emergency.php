<?php
/**
 * Emergency Numbers API
 * GET api/emergency.php → list active emergency numbers (optionally filter by city)
 */
require __DIR__ . '/_bootstrap.php';
require_method('GET');

$db = Database::getInstance();

$city = is_string($_GET['city'] ?? null) ? trim($_GET['city']) : '';
$category = is_string($_GET['category'] ?? null) ? trim($_GET['category']) : '';

$where = ['is_active = 1'];
$params = [];

if ($city !== '') {
    $where[] = 'city = ?';
    $params[] = $city;
}
if ($category !== '') {
    $where[] = 'category = ?';
    $params[] = $category;
}

$whereSql = implode(' AND ', $where);

$rows = $db->fetchAll(
    "SELECT id, name, category, phone, whatsapp, description, address, city, is_24_7
       FROM emergency_numbers
      WHERE $whereSql
      ORDER BY display_order ASC, name ASC",
    $params
);

json_ok($rows);