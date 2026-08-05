<?php
/**
 * Umma Directory — Charities & campaigns API
 *
 *   GET api/charities.php             → list (q, category, page)
 *   GET api/charities.php?id=2        → detail + active campaigns
 *   GET api/charities.php?top=1       → top causes for the homepage
 */
require __DIR__ . '/_bootstrap.php';

require_method('GET');

$db = Database::getInstance();

function charity_totals($db, $charityId)
{
    $row = $db->fetchOne(
        "SELECT COALESCE(SUM(CASE WHEN status IN ('completed','pending') THEN amount END), 0) AS raised,
                COALESCE(SUM(CASE WHEN status IN ('completed','pending') THEN 1 ELSE 0 END), 0) AS donors
           FROM donations WHERE charity_id = ?",
        [$charityId]
    );
    return ['raised' => (float)$row['raised'], 'donors' => (int)$row['donors']];
}

/* ================= detail ================= */
if (isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $charity = $db->fetchOne(
        'SELECT * FROM charities WHERE id = ?',
        [$id]
    );
    if (!$charity) {
        json_err('Charity not found', 404);
    }
    $totals = charity_totals($db, $id);

    $campaigns = $db->fetchAll(
        "SELECT c.*,
                COALESCE(SUM(CASE WHEN d.status IN ('completed','pending') THEN d.amount END), 0) AS raised,
                COALESCE(SUM(CASE WHEN d.status IN ('completed','pending') THEN 1 ELSE 0 END), 0) AS donors
           FROM campaigns c
           LEFT JOIN donations d ON d.campaign_id = c.id
          WHERE c.charity_id = ? AND c.status = 'active'
          GROUP BY c.id
          ORDER BY c.end_date IS NULL, c.end_date ASC",
        [$id]
    );
    foreach ($campaigns as &$c) {
        $c['raised'] = (float)$c['raised'];
        $c['progress'] = $c['goal_amount'] > 0 ? min(100, round($c['raised'] / $c['goal_amount'] * 100)) : 0;
    }

    json_ok([
        'charity'   => $charity,
        'raised'    => $totals['raised'],
        'donors'    => $totals['donors'],
        'campaigns' => $campaigns,
    ]);
}

/* ================= top (homepage) ================= */
if (isset($_GET['top'])) {
    $rows = $db->fetchAll(
        "SELECT c.id, c.name, c.slug, c.category, c.logo_path, c.cover_photo, c.city,
                COALESCE(SUM(d.amount), 0) AS raised
           FROM charities c
           LEFT JOIN donations d ON d.charity_id = c.id AND d.status IN ('completed','pending')
          WHERE c.is_verified = 1
          GROUP BY c.id
          ORDER BY raised DESC
          LIMIT 3"
    );
    json_ok($rows);
}

/* ================= list ================= */
$q      = trim($_GET['q'] ?? '');
$cat    = trim($_GET['category'] ?? '');
$page   = max(1, (int)($_GET['page'] ?? 1));
$perPage = ITEMS_PER_PAGE;
$offset = ($page - 1) * $perPage;

$where  = ['1=1'];
$params = [];
if ($q !== '') {
    $like = '%' . $db->escapeLike($q) . '%';
    $where[] = '(c.name LIKE ? OR c.description LIKE ?)';
    array_push($params, $like, $like);
}
if ($cat !== '') {
    $where[] = 'c.category = ?';
    $params[] = $cat;
}
$whereSql = implode(' AND ', $where);

$total = (int)$db->fetchValue("SELECT COUNT(*) FROM charities c WHERE $whereSql", $params);
$pages = max(1, (int)ceil($total / $perPage));

$items = $db->fetchAll(
    "SELECT c.id, c.name, c.slug, c.category, c.logo_path, c.cover_photo, c.city, c.is_verified,
            c.registration_number,
            COALESCE(SUM(CASE WHEN d.status IN ('completed','pending') THEN d.amount END), 0) AS raised,
            COALESCE(SUM(CASE WHEN d.status IN ('completed','pending') THEN 1 ELSE 0 END), 0) AS donors
       FROM charities c
       LEFT JOIN donations d ON d.charity_id = c.id
      WHERE $whereSql
      GROUP BY c.id
      ORDER BY raised DESC
      LIMIT $perPage OFFSET $offset",
    $params
);
foreach ($items as &$it) {
    $it['raised'] = (float)$it['raised'];
}

$categories = $db->fetchAll(
    'SELECT category, COUNT(*) AS total FROM charities GROUP BY category ORDER BY total DESC'
);

json_ok([
    'items'      => $items,
    'total'      => $total,
    'page'       => $page,
    'pages'      => $pages,
    'categories' => $categories,
]);
