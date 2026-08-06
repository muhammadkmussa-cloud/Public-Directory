<?php
/**
 * Businesses API
 *   GET api/businesses.php                    → list (q, location, category, price, min_rating, open_now, sort, page)
 *   GET api/businesses.php?id=5               → detail + photos + reviews + similar
 *   GET api/businesses.php?featured=1         → featured set for the homepage
 */
require __DIR__ . '/_bootstrap.php';

$db = Database::getInstance();

/* ================= owner dashboard actions (POST) ================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user = require_login();
    require_csrf();
    $body = json_body();
    $action = $body['action'] ?? ($_GET['action'] ?? '');

    /* ---- claim a listing ---- */
    if ($action === 'claim') {
        $id = (int)($body['business_id'] ?? 0);
        $biz = $db->fetchOne('SELECT id, user_id, is_claimed FROM businesses WHERE id = ?', [$id]);
        if (!$biz) json_err('Business not found', 404);
        if ($biz['is_claimed'] && $biz['user_id']) json_err('This listing is already claimed', 409);
        $db->execute(
            'UPDATE businesses SET user_id = ?, is_claimed = 1, claimed_at = NOW() WHERE id = ?',
            [$user['id'], $id]
        );
        if ($user['user_type'] === 'admin') {
            notify($db, $user['id'], 'claim_request', 'Listing claimed',
                'You claimed the listing. You now have owner controls.', 'dashboard.html');
        }
        json_ok(['claimed' => true]);
    }

    /* ---- respond to a review (owner or admin) ---- */
    if ($action === 'respond') {
        $reviewId = (int)($body['review_id'] ?? 0);
        $response = trim($body['response'] ?? '');
        if (mb_strlen($response) < 2 || mb_strlen($response) > 2000) {
            json_err('Response must be 2–2000 characters', 422);
        }
        $review = $db->fetchOne('SELECT * FROM reviews WHERE id = ?', [$reviewId]);
        if (!$review) json_err('Review not found', 404);
        if ($review['reviewable_type'] !== 'business') json_err('Only business reviews can be answered', 422);

        $biz = $db->fetchOne('SELECT user_id FROM businesses WHERE id = ?', [$review['reviewable_id']]);
        $isOwner = $biz && (int)$biz['user_id'] === (int)$user['id'];
        if (!$isOwner && $user['user_type'] !== 'admin') {
            json_err('You can only respond to reviews on your own listings', 403);
        }

        $db->execute(
            'UPDATE reviews SET owner_response = ?, owner_response_at = NOW() WHERE id = ?',
            [$response, $reviewId]
        );
        json_ok(['responded' => true]);
    }

    /* ---- edit a listing (owner or admin) ---- */
    if ($action === 'update') {
        $id = (int)($body['business_id'] ?? 0);
        $biz = $db->fetchOne('SELECT user_id FROM businesses WHERE id = ?', [$id]);
        if (!$biz) json_err('Business not found', 404);
        if ((int)$biz['user_id'] !== (int)$user['id'] && $user['user_type'] !== 'admin') {
            json_err('You can only edit your own listings', 403);
        }

        $fields = ['name', 'short_description', 'description', 'phone', 'whatsapp', 'email', 'website', 'price_range', 'city', 'address'];
        $sets = [];
        $params = [];
        foreach ($fields as $f) {
            if (array_key_exists($f, $body)) {
                $v = trim((string)$body[$f]);
                if ($f === 'name' && $v === '') json_err('Name cannot be empty', 422);
                if ($f === 'website' && $v !== '' && !preg_match('#^https?://#i', $v)) {
                    $v = 'https://' . $v;
                }
                $sets[] = "$f = ?";
                $params[] = $v === '' ? null : $v;
            }
        }
        if (!$sets) json_err('Nothing to update', 422);
        $params[] = $id;
        $db->execute('UPDATE businesses SET ' . implode(', ', $sets) . ' WHERE id = ?', $params);
        json_ok(['updated' => true]);
    }

    json_err('Unknown action', 404);
}

// From here on everything is read-only (GET) — enforce the method now.
require_method('GET');

/* ================= owner listings (GET) ================= */
if (isset($_GET['action']) && $_GET['action'] === 'mine') {
    $user = require_login();
    $where = $user['user_type'] === 'admin' ? '1=1' : 'b.user_id = ?';
    $params = $user['user_type'] === 'admin' ? [] : [$user['id']];
    $rows = $db->fetchAll(
        "SELECT b.id, b.name, b.slug, b.city, b.price_range, b.is_claimed, b.is_verified,
                b.rating_average, b.review_count, b.checkin_count, b.claimed_at,
                (SELECT COUNT(*) FROM reviews r
                  WHERE r.reviewable_id = b.id AND r.reviewable_type = 'business'
                    AND r.owner_response IS NULL AND r.is_approved = 1) AS pending_responses
           FROM businesses b
          WHERE $where
          ORDER BY b.is_claimed DESC, b.created_at DESC",
        $params
    );
    json_ok($rows);
}

/* ================= detail ================= */
if (isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $business = $db->fetchOne(
        'SELECT b.*,
                (SELECT c.name FROM categories c
                   JOIN business_categories bc ON bc.category_id = c.id AND bc.business_id = b.id
                  ORDER BY bc.is_primary DESC, c.display_order LIMIT 1) AS category_name,
                (SELECT c.icon FROM categories c
                   JOIN business_categories bc ON bc.category_id = c.id AND bc.business_id = b.id
                  ORDER BY bc.is_primary DESC, c.display_order LIMIT 1) AS category_icon
           FROM businesses b WHERE b.id = ?',
        [$id]
    );
    if (!$business) {
        json_err('Business not found', 404);
    }

    $photos = $db->fetchAll(
        'SELECT id, photo_path, thumbnail_path, caption, is_primary
           FROM business_photos WHERE business_id = ? ORDER BY is_primary DESC, id ASC',
        [$id]
    );

    $reviews = $db->fetchAll(
        'SELECT r.id, r.rating, r.title, r.content, r.helpful_count, r.is_verified_visit,
                r.owner_response, r.created_at,
                (SELECT COUNT(*) FROM review_helpful rh WHERE rh.review_id = r.id AND rh.reaction_type = \'useful\') AS useful_count,
                (SELECT COUNT(*) FROM review_helpful rh WHERE rh.review_id = r.id AND rh.reaction_type = \'funny\')  AS funny_count,
                (SELECT COUNT(*) FROM review_helpful rh WHERE rh.review_id = r.id AND rh.reaction_type = \'cool\')   AS cool_count,
                u.full_name, u.profile_photo, u.contributor_level, u.verification_badge
           FROM reviews r
           JOIN users u ON u.id = r.user_id
          WHERE r.reviewable_id = ? AND r.reviewable_type = \'business\' AND r.is_approved = 1 AND r.is_hidden = 0
          ORDER BY r.created_at DESC
          LIMIT 20',
        [$id]
    );
    foreach ($reviews as &$r) {
        $r['user'] = ['full_name' => $r['full_name'], 'profile_photo' => $r['profile_photo']];
        unset($r['full_name'], $r['profile_photo']);
    }
    attachReviewPhotos($db, $reviews);

    $breakdown = array_fill(1, 5, 0);
    foreach ($db->fetchAll(
        'SELECT rating, COUNT(*) AS c FROM reviews
          WHERE reviewable_id = ? AND reviewable_type = \'business\'
            AND is_approved = 1 AND is_hidden = 0
          GROUP BY rating',
        [$id]
    ) as $row) {
        $breakdown[(int)$row['rating']] = (int)$row['c'];
    }

    $similar = $db->fetchAll(
        'SELECT b.id, b.name, b.slug, b.city, b.price_range, b.is_verified,
                b.rating_average, b.review_count,
                (SELECT photo_path FROM business_photos WHERE business_id = b.id
                  ORDER BY is_primary DESC, id DESC LIMIT 1) AS primary_photo
           FROM businesses b
           JOIN business_categories bc ON bc.business_id = b.id
           JOIN categories c ON c.id = bc.category_id
           JOIN (SELECT category_id FROM business_categories WHERE business_id = ? LIMIT 1) mine
             ON mine.category_id = c.id
          WHERE b.id != ? AND b.is_open = 1
          GROUP BY b.id
          ORDER BY b.rating_average DESC, b.review_count DESC
          LIMIT 4',
        [$id, $id]
    );

    // decode JSON columns for the frontend
    foreach (['opening_hours', 'amenities', 'payment_methods', 'languages'] as $col) {
        if (!empty($business[$col])) {
            $business[$col] = json_decode($business[$col], true);
        }
    }

    json_ok([
        'business' => $business,
        'photos'   => $photos,
        'reviews'  => $reviews,
        'rating_breakdown' => $breakdown,
        'similar'  => $similar,
    ]);
}

/* ================= featured (homepage) ================= */
if (isset($_GET['featured'])) {
    $featured = $db->fetchAll(
        'SELECT b.id, b.name, b.slug, b.city, b.price_range, b.short_description,
                b.is_verified, b.rating_average, b.review_count,
                (SELECT c.name FROM categories c
                   JOIN business_categories bc ON bc.category_id = c.id AND bc.business_id = b.id
                  ORDER BY bc.is_primary DESC LIMIT 1) AS category_name,
                (SELECT photo_path FROM business_photos WHERE business_id = b.id
                  ORDER BY is_primary DESC, id DESC LIMIT 1) AS primary_photo
           FROM businesses b
          WHERE b.is_verified = 1 AND b.is_open = 1
          ORDER BY b.rating_average DESC, b.review_count DESC
          LIMIT 6'
    );
    json_ok($featured);
}

/* ================= list ================= */
$q          = trim($_GET['q'] ?? '');
$location   = trim($_GET['location'] ?? '');
$category   = trim($_GET['slug'] ?? ($_GET['category'] ?? ''));
$price      = trim($_GET['price'] ?? '');
$minRating  = (float)($_GET['min_rating'] ?? 0);
$openNow    = isset($_GET['open_now']) && $_GET['open_now'] !== '0';
$sort       = $_GET['sort'] ?? 'rating';
$page       = max(1, (int)($_GET['page'] ?? 1));
$perPage    = ITEMS_PER_PAGE;
$offset     = ($page - 1) * $perPage;

$where  = ['b.is_open = 1'];
$params = [];

if ($q !== '') {
    $like = '%' . $db->escapeLike($q) . '%';
    $where[] = '(b.name LIKE ? OR b.short_description LIKE ? OR b.description LIKE ?)';
    array_push($params, $like, $like, $like);
}
if ($location !== '') {
    $like = '%' . $db->escapeLike($location) . '%';
    $where[] = '(b.city LIKE ? OR b.address LIKE ? OR b.region LIKE ?)';
    array_push($params, $like, $like, $like);
}
if ($category !== '') {
    $where[] = 'EXISTS (SELECT 1 FROM business_categories bc
                        JOIN categories c ON c.id = bc.category_id
                       WHERE bc.business_id = b.id AND c.slug = ?)';
    $params[] = $category;
}
if ($price !== '') {
    $where[] = 'b.price_range = ?';
    $params[] = $price;
}
if ($minRating > 0) {
    $where[] = 'b.rating_average >= ?';
    $params[] = $minRating;
}
if ($openNow) {
    // compute from opening_hours JSON: compare current time against today's range
    $dayKey = strtolower(date('l'));               // monday..sunday
    $now = date('H:i');
    $hoursJson = "JSON_UNQUOTE(JSON_EXTRACT(b.opening_hours, '$." . $dayKey . "'))";
    $where[] = "$hoursJson <> 'Closed'"
        . " AND SUBSTRING_INDEX($hoursJson, ' - ', 1) <= ?"
        . " AND SUBSTRING_INDEX($hoursJson, ' - ', -1) >= ?";
    $params[] = $now;
    $params[] = $now;
}

// distance-based search (Near me): adds distance_km + radius filter
$dist = distance_clause('b');
if ($dist['where'] !== '') {
    $where[] = $dist['where'];
}

$whereSql = implode(' AND ', $where);

$total = (int)$db->fetchValue("SELECT COUNT(*) FROM businesses b WHERE $whereSql", $params);
$pages = max(1, (int)ceil($total / $perPage));

$orderMap = [
    'rating' => 'b.rating_average DESC, b.review_count DESC',
    'reviews' => 'b.review_count DESC',
    'name' => 'b.name ASC',
];
if ($dist['has']) {
    $orderMap['distance'] = 'distance_km ASC';
}
$orderBy = $orderMap[$sort] ?? $orderMap['rating'];

$items = $db->fetchAll(
    "SELECT b.id, b.name, b.slug, b.city, b.region, b.price_range, b.short_description,
            b.is_verified, b.is_open, b.rating_average, b.review_count, b.checkin_count,
            b.latitude, b.longitude" . ($dist['select'] ? ',' . $dist['select'] : '') . ",
            (SELECT c.name FROM categories c
               JOIN business_categories bc ON bc.category_id = c.id AND bc.business_id = b.id
              ORDER BY bc.is_primary DESC LIMIT 1) AS category_name,
            (SELECT photo_path FROM business_photos WHERE business_id = b.id
              ORDER BY is_primary DESC, id DESC LIMIT 1) AS primary_photo
       FROM businesses b
      WHERE $whereSql
      ORDER BY $orderBy
      LIMIT $perPage OFFSET $offset",
    $params
);

// filter facets for the sidebar
$categories = $db->fetchAll(
    'SELECT c.slug, c.name, c.icon, COUNT(bc.business_id) AS total
       FROM categories c
       LEFT JOIN business_categories bc ON bc.category_id = c.id
      WHERE c.type = \'business\' AND c.is_active = 1
      GROUP BY c.id
      ORDER BY c.display_order, c.name'
);
$cities = $db->fetchAll(
    'SELECT city, COUNT(*) AS total FROM businesses WHERE is_open = 1 AND city IS NOT NULL
     GROUP BY city ORDER BY total DESC LIMIT 15'
);

json_ok([
    'items'      => $items,
    'total'      => $total,
    'page'       => $page,
    'pages'      => $pages,
    'categories' => $categories,
    'cities'     => $cities,
]);
