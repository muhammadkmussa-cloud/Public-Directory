<?php
/**
 * Fundis API
 *   GET api/fundis.php             → list (q, skill, city, page)
 *   GET api/fundis.php?id=2        → detail + portfolio + reviews
 *   GET api/fundis.php?top=1       → top-rated set for the homepage
 */
require __DIR__ . '/_bootstrap.php';

require_method('GET');

$db = Database::getInstance();

function fundi_json($f)
{
    foreach (['skills', 'certifications', 'languages', 'working_hours'] as $col) {
        if (!empty($f[$col])) {
            $f[$col] = json_decode($f[$col], true);
        }
    }
    return $f;
}

/* ================= detail ================= */
if (isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $fundi = $db->fetchOne(
        'SELECT f.*, u.full_name, u.profile_photo, u.username
           FROM fundis f
           JOIN users u ON u.id = f.user_id
          WHERE f.id = ? AND f.is_available = 1',
        [$id]
    );
    if (!$fundi) {
        json_err('Fundi not found', 404);
    }
    $fundi = fundi_json($fundi);

    $portfolio = $db->fetchAll(
        'SELECT id, photo_path, thumbnail_path, caption
           FROM fundi_photos WHERE fundi_id = ? ORDER BY id DESC LIMIT 12',
        [$id]
    );

    $reviews = $db->fetchAll(
        'SELECT r.id, r.rating, r.title, r.content, r.helpful_count, r.created_at,
                (SELECT COUNT(*) FROM review_helpful rh WHERE rh.review_id = r.id AND rh.reaction_type = \'useful\') AS useful_count,
                (SELECT COUNT(*) FROM review_helpful rh WHERE rh.review_id = r.id AND rh.reaction_type = \'funny\')  AS funny_count,
                (SELECT COUNT(*) FROM review_helpful rh WHERE rh.review_id = r.id AND rh.reaction_type = \'cool\')   AS cool_count,
                u.full_name, u.profile_photo, u.contributor_level, u.verification_badge
           FROM reviews r
           JOIN users u ON u.id = r.user_id
          WHERE r.reviewable_id = ? AND r.reviewable_type = \'fundi\' AND r.is_approved = 1 AND r.is_hidden = 0
          ORDER BY r.created_at DESC
          LIMIT 20',
        [$id]
    );
    foreach ($reviews as &$r) {
        $r['user'] = ['full_name' => $r['full_name'], 'profile_photo' => $r['profile_photo']];
        unset($r['full_name'], $r['profile_photo']);
    }
    attachReviewPhotos($db, $reviews);

    json_ok(['fundi' => $fundi, 'portfolio' => $portfolio, 'reviews' => $reviews]);
}

/* ================= top (homepage) ================= */
if (isset($_GET['top'])) {
    $rows = $db->fetchAll(
        'SELECT f.id, f.profession, f.city, f.is_verified, f.rating_average, f.review_count,
                f.hourly_rate_min, f.skills, u.full_name, u.profile_photo, u.contributor_level, u.verification_badge
           FROM fundis f
           JOIN users u ON u.id = f.user_id
          WHERE f.is_available = 1 AND f.is_verified = 1
          ORDER BY f.rating_average DESC, f.review_count DESC
          LIMIT 4'
    );
    foreach ($rows as &$r) {
        $r = fundi_json($r);
    }
    json_ok($rows);
}

/* ================= list ================= */
$q      = trim($_GET['q'] ?? '');
$skill  = trim($_GET['skill'] ?? '');
$city   = trim($_GET['city'] ?? '');
$sort   = $_GET['sort'] ?? 'rating';
$page   = max(1, (int)($_GET['page'] ?? 1));
$perPage = ITEMS_PER_PAGE;
$offset = ($page - 1) * $perPage;

$where  = ['f.is_available = 1'];
$params = [];
if ($q !== '') {
    $like = '%' . $db->escapeLike($q) . '%';
    $where[] = '(f.profession LIKE ? OR f.bio LIKE ? OR u.full_name LIKE ?)';
    array_push($params, $like, $like, $like);
}
if ($skill !== '') {
    $where[] = 'JSON_CONTAINS(f.skills, JSON_QUOTE(?))';
    $params[] = $skill;
}
if ($city !== '') {
    $where[] = 'f.city = ?';
    $params[] = $city;
}

// distance-based search (Near me)
$dist = distance_clause('f');
if ($dist['where'] !== '') {
    $where[] = $dist['where'];
}

$whereSql = implode(' AND ', $where);

$total = (int)$db->fetchValue(
    "SELECT COUNT(*) FROM fundis f JOIN users u ON u.id = f.user_id WHERE $whereSql",
    $params
);
$pages = max(1, (int)ceil($total / $perPage));

$orderMap = [
    'rating' => 'f.rating_average DESC, f.review_count DESC',
    'reviews' => 'f.review_count DESC',
    'name' => 'f.profession ASC',
];
if ($dist['has']) {
    $orderMap['distance'] = 'distance_km ASC';
}
$orderBy = $orderMap[$sort] ?? $orderMap['rating'];

$items = $db->fetchAll(
    "SELECT f.id, f.profession, f.profession_other, f.years_experience, f.city, f.region,
            f.is_verified, f.rating_average, f.review_count, f.hourly_rate_min,
            f.hourly_rate_max, f.skills, f.phone, f.whatsapp,
            f.latitude, f.longitude" . ($dist['select'] ? ',' . $dist['select'] : '') . ",
            u.full_name, u.profile_photo, u.contributor_level, u.verification_badge
       FROM fundis f
       JOIN users u ON u.id = f.user_id
      WHERE $whereSql
      ORDER BY $orderBy
      LIMIT $perPage OFFSET $offset",
    $params
);
foreach ($items as &$r) {
    $r = fundi_json($r);
}

$skills = $db->fetchAll(
    "SELECT DISTINCT JSON_UNQUOTE(JSON_EXTRACT(f.skills, '$[0]')) AS skill FROM fundis f WHERE f.skills IS NOT NULL"
);
$skillList = [];
foreach ($skills as $s) {
    if (!empty($s['skill'])) {
        $skillList[] = $s['skill'];
    }
}

$cities = $db->fetchAll(
    'SELECT city, COUNT(*) AS total FROM fundis WHERE city IS NOT NULL
     GROUP BY city ORDER BY total DESC LIMIT 15'
);

json_ok([
    'items'  => $items,
    'total'  => $total,
    'page'   => $page,
    'pages'  => $pages,
    'skills' => $skillList,
    'cities' => $cities,
]);
