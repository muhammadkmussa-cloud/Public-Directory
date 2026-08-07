<?php
/**
 * Ummah Directory — Business Visual Restaurant Menu API
 *
 * GET  api/business_menu.php?business_id=N     → fetch menu items grouped by category (public)
 * POST api/business_menu.php?action=create     → create menu item (owner/admin, CSRF)
 * POST api/business_menu.php?action=update     → update menu item (owner/admin, CSRF)
 * POST api/business_menu.php?action=delete     → delete menu item (owner/admin, CSRF)
 */
require __DIR__ . '/_bootstrap.php';

$db = Database::getInstance();
$action = $_GET['action'] ?? '';
$method = $_SERVER['REQUEST_METHOD'];

/**
 * Check if the current logged-in user can edit menu for a given business_id.
 */
function verify_business_owner(Database $db, int $businessId, array $user): array {
    $biz = $db->fetchOne('SELECT id, user_id FROM businesses WHERE id = ?', [$businessId]);
    if (!$biz) {
        json_err('Business not found', 404);
    }
    if ($user['user_type'] !== 'admin' && (int)$biz['user_id'] !== (int)$user['id']) {
        json_err('Not authorized to manage menu for this business', 403);
    }
    return $biz;
}

/**
 * Validate and sanitize menu item input.
 */
function validate_menu_item_input(array $b): array {
    $name = trim((string)($b['name'] ?? ''));
    $category = trim((string)($b['category'] ?? ''));
    if ($category === '') {
        $category = 'Main';
    }
    $description = trim((string)($b['description'] ?? ''));
    $price = (float)($b['price'] ?? 0.00);
    $isPopular = !empty($b['is_popular']) ? 1 : 0;
    $isHalal = isset($b['is_halal_certified']) ? (!empty($b['is_halal_certified']) ? 1 : 0) : 1;
    $photoUrl = trim((string)($b['photo_url'] ?? ''));
    $displayOrder = (int)($b['display_order'] ?? 0);

    if ($name === '') {
        json_err('Item name is required', 422);
    }
    if (mb_strlen($name) > 150) {
        json_err('Item name too long', 422);
    }
    if (mb_strlen($category) > 100) {
        json_err('Category name too long', 422);
    }
    if (mb_strlen($photoUrl) > 255) {
        json_err('Photo URL too long', 422);
    }
    if ($price < 0) {
        json_err('Price cannot be negative', 422);
    }

    return [$category, $name, $description ?: null, $price, $isPopular, $isHalal, $photoUrl ?: null, $displayOrder];
}

/* ================= GET: Fetch menu items ================= */
if ($method === 'GET' && $action === '') {
    $businessId = (int)($_GET['business_id'] ?? 0);
    if ($businessId < 1) {
        json_err('Missing business_id', 422);
    }

    $items = $db->fetchAll(
        'SELECT id, business_id, category, name, description, price, is_popular, is_halal_certified, photo_url, display_order, created_at
           FROM business_menu_items
          WHERE business_id = ?
          ORDER BY category ASC, display_order ASC, id ASC',
        [$businessId]
    );

    $grouped = [];
    foreach ($items as $item) {
        $cat = ($item['category'] !== null && $item['category'] !== '') ? $item['category'] : 'Main';
        if (!isset($grouped[$cat])) {
            $grouped[$cat] = [];
        }
        $grouped[$cat][] = [
            'id'                 => (int)$item['id'],
            'business_id'        => (int)$item['business_id'],
            'category'           => $cat,
            'name'               => $item['name'],
            'description'        => $item['description'],
            'price'              => (float)$item['price'],
            'is_popular'         => (bool)$item['is_popular'],
            'is_halal_certified' => (bool)$item['is_halal_certified'],
            'photo_url'          => $item['photo_url'],
            'display_order'      => (int)$item['display_order'],
        ];
    }

    $categoryList = [];
    foreach ($grouped as $catName => $catItems) {
        $categoryList[] = [
            'name'  => $catName,
            'items' => $catItems,
        ];
    }

    json_ok([
        'business_id' => $businessId,
        'categories'  => $categoryList,
        'total_items' => count($items),
    ]);
}

/* ================= POST: Create menu item ================= */
if ($action === 'create') {
    require_method('POST');
    $user = require_login();
    require_csrf();

    $b = json_body();
    $businessId = (int)($b['business_id'] ?? 0);
    if ($businessId < 1) {
        json_err('Invalid business_id', 422);
    }
    verify_business_owner($db, $businessId, $user);

    list($category, $name, $description, $price, $isPopular, $isHalal, $photoUrl, $displayOrder) = validate_menu_item_input($b);

    $itemId = $db->insert(
        'INSERT INTO business_menu_items (business_id, category, name, description, price, is_popular, is_halal_certified, photo_url, display_order)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)',
        [$businessId, $category, $name, $description, $price, $isPopular, $isHalal, $photoUrl, $displayOrder]
    );

    json_ok(['item_id' => $itemId, 'message' => 'Menu item created'], 201);
}

/* ================= POST: Update menu item ================= */
if ($action === 'update') {
    require_method('POST');
    $user = require_login();
    require_csrf();

    $b = json_body();
    $itemId = (int)($b['id'] ?? 0);
    if ($itemId < 1) {
        json_err('Invalid item id', 422);
    }

    $existing = $db->fetchOne('SELECT id, business_id FROM business_menu_items WHERE id = ?', [$itemId]);
    if (!$existing) {
        json_err('Menu item not found', 404);
    }
    verify_business_owner($db, (int)$existing['business_id'], $user);

    list($category, $name, $description, $price, $isPopular, $isHalal, $photoUrl, $displayOrder) = validate_menu_item_input($b);

    $db->execute(
        'UPDATE business_menu_items
            SET category = ?, name = ?, description = ?, price = ?, is_popular = ?, is_halal_certified = ?, photo_url = ?, display_order = ?
          WHERE id = ?',
        [$category, $name, $description, $price, $isPopular, $isHalal, $photoUrl, $displayOrder, $itemId]
    );

    json_ok(['message' => 'Menu item updated']);
}

/* ================= POST: Delete menu item ================= */
if ($action === 'delete') {
    require_method('POST');
    $user = require_login();
    require_csrf();

    $b = json_body();
    $itemId = (int)($b['id'] ?? 0);
    if ($itemId < 1) {
        json_err('Invalid item id', 422);
    }

    $existing = $db->fetchOne('SELECT id, business_id FROM business_menu_items WHERE id = ?', [$itemId]);
    if (!$existing) {
        json_err('Menu item not found', 404);
    }
    verify_business_owner($db, (int)$existing['business_id'], $user);

    $db->execute('DELETE FROM business_menu_items WHERE id = ?', [$itemId]);
    json_ok(['deleted' => true]);
}

json_err('Unknown action', 404);
