<?php
/**
 * Mosques API
 *   GET api/mosques.php             → list (q, city, page)
 *   GET api/mosques.php?id=3        → detail + photos + reviews + prayer times (today + week)
 *   GET api/mosques.php?top=1       → top-rated set for the homepage
 */
require __DIR__ . '/_bootstrap.php';
require_once __DIR__ . '/../includes/PrayerTimes.php';

require_method('GET');

$db = Database::getInstance();

/* ================= detail ================= */
if (isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $mosque = $db->fetchOne('SELECT * FROM mosques WHERE id = ?', [$id]);
    if (!$mosque) {
        json_err('Mosque not found', 404);
    }

    $photos = $db->fetchAll(
        'SELECT id, photo_path, thumbnail_path, caption, is_primary
           FROM mosque_photos WHERE mosque_id = ? ORDER BY is_primary DESC, id ASC',
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
          WHERE r.reviewable_id = ? AND r.reviewable_type = \'mosque\' AND r.is_approved = 1 AND r.is_hidden = 0
          ORDER BY r.created_at DESC
          LIMIT 20',
        [$id]
    );
    foreach ($reviews as &$r) {
        $r['user'] = ['full_name' => $r['full_name'], 'profile_photo' => $r['profile_photo']];
        unset($r['full_name'], $r['profile_photo']);
    }
    attachReviewPhotos($db, $reviews);

    if (!empty($mosque['facilities'])) {
        $mosque['facilities'] = json_decode($mosque['facilities'], true);
    }

    // Prayer times
    $prayer = null;
    if ($mosque['latitude'] && $mosque['longitude']) {
        $calc = new PrayerTimes($mosque['latitude'], $mosque['longitude']);
        $today = $calc->getTimes();
        $week = [];
        for ($i = 0; $i < 7; $i++) {
            $d = date('Y-m-d', strtotime("+$i days"));
            $week[] = ['date' => $d, 'day' => date('D', strtotime($d)), 'times' => $calc->getTimesForDate($d)];
        }
        $prayer = [
            'today' => $today,
            'next'  => $calc->getNextPrayer(),
            'week'  => $week,
        ];
    }

    json_ok([
        'mosque' => $mosque,
        'photos' => $photos,
        'reviews' => $reviews,
        'prayer' => $prayer,
    ]);
}

/* ================= top (homepage) ================= */
if (isset($_GET['top'])) {
    $rows = $db->fetchAll(
        'SELECT id, name, slug, city, address, rating_average, review_count, is_verified,
                latitude, longitude,
                (SELECT photo_path FROM mosque_photos WHERE mosque_id = m.id
                  ORDER BY is_primary DESC, id DESC LIMIT 1) AS primary_photo
           FROM mosques m
          ORDER BY review_count DESC, rating_average DESC
          LIMIT 4'
    );
    foreach ($rows as &$m) {
        if ($m['latitude'] && $m['longitude']) {
            $calc = new PrayerTimes($m['latitude'], $m['longitude']);
            $m['next_prayer'] = $calc->getNextPrayer();
        }
    }
    json_ok($rows);
}

/* ================= list ================= */
$q      = trim($_GET['q'] ?? '');
$city   = trim($_GET['city'] ?? '');
$sort   = $_GET['sort'] ?? 'rating';
$page   = max(1, (int)($_GET['page'] ?? 1));
$perPage = ITEMS_PER_PAGE;
$offset = ($page - 1) * $perPage;

$where  = ['1=1'];
$params = [];
if ($q !== '') {
    $like = '%' . $db->escapeLike($q) . '%';
    $where[] = '(m.name LIKE ? OR m.address LIKE ? OR m.city LIKE ?)';
    array_push($params, $like, $like, $like);
}
if ($city !== '') {
    $where[] = 'm.city = ?';
    $params[] = $city;
}

// distance-based search (Near me)
$dist = distance_clause('m');
if ($dist['where'] !== '') {
    $where[] = $dist['where'];
}

$whereSql = implode(' AND ', $where);

$total = (int)$db->fetchValue("SELECT COUNT(*) FROM mosques m WHERE $whereSql", $params);
$pages = max(1, (int)ceil($total / $perPage));

$orderMap = [
    'rating' => 'm.rating_average DESC, m.review_count DESC',
    'reviews' => 'm.review_count DESC',
    'name' => 'm.name ASC',
];
if ($dist['has']) {
    $orderMap['distance'] = 'distance_km ASC';
}
$orderBy = $orderMap[$sort] ?? $orderMap['rating'];

$items = $db->fetchAll(
    "SELECT m.id, m.name, m.slug, m.city, m.address, m.phone, m.is_verified,
            m.rating_average, m.review_count, m.latitude, m.longitude" . ($dist['select'] ? ',' . $dist['select'] : '') . ",
            (SELECT photo_path FROM mosque_photos WHERE mosque_id = m.id
              ORDER BY is_primary DESC, id DESC LIMIT 1) AS primary_photo
       FROM mosques m
      WHERE $whereSql
      ORDER BY $orderBy
      LIMIT $perPage OFFSET $offset",
    $params
);

foreach ($items as &$m) {
    if ($m['latitude'] && $m['longitude']) {
        $calc = new PrayerTimes($m['latitude'], $m['longitude']);
        $m['prayer_times'] = $calc->getTimes();
        $m['next_prayer'] = $calc->getNextPrayer();
    }
}

$cities = $db->fetchAll(
    'SELECT city, COUNT(*) AS total FROM mosques WHERE city IS NOT NULL
     GROUP BY city ORDER BY total DESC LIMIT 15'
);

json_ok(['items' => $items, 'total' => $total, 'page' => $page, 'pages' => $pages, 'cities' => $cities]);
