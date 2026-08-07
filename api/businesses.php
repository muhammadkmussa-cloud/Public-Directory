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
    rate_limit('biz_write', 30, 300);
    $body = json_body();
    $action = $body['action'] ?? ($_GET['action'] ?? '');

    /* ---- claim a listing (non-admins require admin approval) ---- */
    if ($action === 'claim') {
        $id = (int)($body['business_id'] ?? 0);
        $biz = $db->fetchOne('SELECT id, name, user_id, is_claimed, claim_status FROM businesses WHERE id = ?', [$id]);
        if (!$biz) json_err('Business not found', 404);
        if ($biz['is_claimed'] && $biz['user_id']) json_err('This listing is already claimed', 409);
        if ($biz['claim_status'] === 'approved') json_err('This listing is already claimed', 409);
        if ($biz['claim_status'] === 'pending') json_err('A claim for this listing is already pending review', 409);

        $proof = sanitize_text($body['proof'] ?? '', 500);
        if (mb_strlen($proof) > 500) json_err('Proof is too long (max 500 characters)', 422);

        // Admins are trusted — grant ownership immediately (original behaviour).
        if ($user['user_type'] === 'admin') {
            $db->begin();
            try {
                $db->execute(
                    'INSERT INTO claims (business_id, user_id, status, proof) VALUES (?, ?, ?, ?)',
                    [$id, $user['id'], 'approved', $proof !== '' ? $proof : null]
                );
                $db->execute(
                    'UPDATE businesses SET user_id = ?, is_claimed = 1, claimed_at = NOW(),
                            claim_status = \'approved\'
                      WHERE id = ?',
                    [$user['id'], $id]
                );
                $db->commit();
            } catch (Throwable $e) {
                $db->rollback();
                throw $e;
            }
            notify($db, $user['id'], 'claim_request', 'Listing claimed',
                'You claimed "' . $biz['name'] . '". You now have owner controls.', 'dashboard');
            json_ok(['claimed' => true, 'status' => 'approved']);
        }

        // Everyone else: record the request as pending; ownership is NOT granted here.
        $db->begin();
        try {
            $db->execute(
                'INSERT INTO claims (business_id, user_id, status, proof) VALUES (?, ?, ?, ?)',
                [$id, $user['id'], 'pending', $proof !== '' ? $proof : null]
            );
            $db->execute(
                'UPDATE businesses SET claim_status = ? WHERE id = ?',
                ['pending', $id]
            );
            $db->commit();
        } catch (Throwable $e) {
            $db->rollback();
            throw $e;
        }

        // Notify every admin so a real claim is never silently ignored.
        foreach ($db->fetchAll('SELECT id FROM users WHERE user_type = ?', ['admin']) as $admin) {
            notify($db, (int)$admin['id'], 'claim_request', 'New claim request',
                $user['full_name'] . ' requested to claim "' . $biz['name'] . '".',
                'admin');
        }
        notify($db, $user['id'], 'claim_request', 'Claim submitted',
            'Your request to claim "' . $biz['name'] . '" is pending approval.', 'dashboard');

        json_ok(['requested' => true, 'status' => 'pending']);
    }

    /* ---- respond to a review (owner or admin) ---- */
    if ($action === 'respond') {
        $reviewId = (int)($body['review_id'] ?? 0);
        $response = sanitize_text($body['response'] ?? '', 2000);
        if (mb_strlen($response) < 2 || mb_strlen($response) > 2000) {
            json_err('Response must be 2–2000 characters', 422);
        }
        $review = $db->fetchOne('SELECT * FROM reviews WHERE id = ?', [$reviewId]);
        if (!$review) json_err('Review not found', 404);
        if ($review['reviewable_type'] !== 'business') json_err('Only business reviews can be answered', 422);

        $biz = $db->fetchOne('SELECT user_id, claim_status FROM businesses WHERE id = ?', [$review['reviewable_id']]);
        $isOwner = $biz && (int)$biz['user_id'] === (int)$user['id'] && $biz['claim_status'] === 'approved';
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
        $biz = $db->fetchOne('SELECT user_id, claim_status FROM businesses WHERE id = ?', [$id]);
        if (!$biz) json_err('Business not found', 404);
        if (!((int)$biz['user_id'] === (int)$user['id'] && $biz['claim_status'] === 'approved') && $user['user_type'] !== 'admin') {
            json_err('You can only edit your own listings', 403);
        }

        $fields = ['name', 'short_description', 'description', 'phone', 'whatsapp', 'email', 'website', 'price_range', 'city', 'address'];
        $sets = [];
        $params = [];
        foreach ($fields as $f) {
            if (array_key_exists($f, $body)) {
                $v = sanitize_line($body[$f], $f === 'description' ? 2000 : 255);
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

    /* ---- approve or reject a claim (admin) ---- */
    if ($action === 'review_claim') {
        if ($user['user_type'] !== 'admin') json_err('Admins only', 403);
        $claimId = (int)($body['claim_id'] ?? 0);
        $decision = $body['decision'] ?? '';
        $notes = sanitize_text($body['admin_notes'] ?? '', 500);
        if (!in_array($decision, ['approve', 'reject'], true)) json_err('Invalid decision', 422);
        if (mb_strlen($notes) > 500) json_err('Note too long (max 500 characters)', 422);

        $claim = $db->fetchOne(
            'SELECT c.id, c.business_id, c.user_id, c.status, b.name AS business_name
               FROM claims c JOIN businesses b ON b.id = c.business_id
              WHERE c.id = ?',
            [$claimId]
        );
        if (!$claim) json_err('Claim not found', 404);
        if ($claim['status'] !== 'pending') json_err('Claim already reviewed', 409);

        if ($decision === 'approve') {
            $affected = $db->execute(
                'UPDATE businesses SET user_id = ?, is_claimed = 1, claimed_at = NOW(), claim_status = \'approved\'
                  WHERE id = ? AND claim_status = \'pending\'',
                [$claim['user_id'], $claim['business_id']]
            );
            if ($affected === 0) json_err('Listing state changed; please re-check', 409);
            notify($db, (int)$claim['user_id'], 'claim_request', 'Claim approved',
                'Your claim for "' . $claim['business_name'] . '" was approved. You now have owner controls.',
                'dashboard');
        } else {
            $affected = $db->execute(
                'UPDATE businesses SET claim_status = \'none\' WHERE id = ? AND claim_status = \'pending\'',
                [$claim['business_id']]
            );
            if ($affected === 0) json_err('Listing state changed; please re-check', 409);
            notify($db, (int)$claim['user_id'], 'claim_request', 'Claim not approved',
                'Your claim for "' . $claim['business_name'] . '" was not approved'
                . ($notes ? '. Reason: ' . $notes : '') . '.',
                'dashboard');
        }

        $affected = $db->execute(
            'UPDATE claims SET status = ?, reviewed_by = ?, reviewed_at = NOW(), admin_notes = ?
              WHERE id = ? AND status = \'pending\'',
            [$decision === 'approve' ? 'approved' : 'rejected', $user['id'], $notes !== '' ? $notes : null, $claimId]
        );
        if ($affected === 0) json_err('Claim already reviewed', 409);
        json_ok(['reviewed' => true]);
    }

    json_err('Unknown action', 404);
}

// From here on everything is read-only (GET) — enforce the method now.
require_method('GET');

/* ================= pending claims (admin) ================= */
if (isset($_GET['action']) && $_GET['action'] === 'claims') {
    $user = require_login();
    if ($user['user_type'] !== 'admin') json_err('Admins only', 403);
    $rows = $db->fetchAll(
        "SELECT c.id, c.business_id, c.proof, c.admin_notes, c.created_at,
                b.name AS business_name, b.city, b.is_claimed, b.claim_status,
                u.full_name AS claimant_name, u.email AS claimant_email
           FROM claims c
           JOIN businesses b ON b.id = c.business_id
           JOIN users u ON u.id = c.user_id
          WHERE c.status = 'pending'
          ORDER BY c.created_at ASC"
    );
    json_ok($rows);
}

/* ================= owner listings (GET) ================= */
if (isset($_GET['action']) && $_GET['action'] === 'mine') {
    $user = require_login();
    if ($user['user_type'] === 'admin') {
        $where = '1=1';
        $params = [];
    } else {
        $where = '(b.user_id = ?
                   OR EXISTS (SELECT 1 FROM claims c
                               WHERE c.business_id = b.id AND c.user_id = ? AND c.status = \'pending\'))';
        $params = [$user['id'], $user['id']];
    }
    $rows = $db->fetchAll(
        "SELECT b.id, b.name, b.slug, b.city, b.price_range, b.is_claimed, b.claim_status, b.is_verified,
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
                (SELECT c.slug FROM categories c
                   JOIN business_categories bc ON bc.category_id = c.id AND bc.business_id = b.id
                  ORDER BY bc.is_primary DESC, c.display_order LIMIT 1) AS category_slug,
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
        'SELECT r.id, r.rating, r.rating_service, r.rating_value, r.rating_ambience, r.rating_cleanliness,
                r.title, r.content, r.helpful_count, r.is_verified_visit,
                r.visit_date, r.owner_response, r.created_at,
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
                b.is_verified, b.rating_average, b.review_count, b.opening_hours,
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
$validHours = ['open'];
$hoursParam = $_GET['hours'] ?? '';
$openNow = (isset($_GET['open_now']) && $_GET['open_now'] !== '0')
        || (in_array($hoursParam, $validHours, true));
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
    // We'll filter in PHP after fetching (handles overnight & multi-range).
    // Just ensure opening_hours is selected.
} else {
    // no open_now filter
}

// distance-based search (Near me): adds distance_km + radius filter
$dist = distance_clause('b');
if ($dist['where'] !== '') {
    $where[] = $dist['where'];
}

$whereSql = implode(' AND ', $where);

$orderMap = [
    'rating' => 'b.rating_average DESC, b.review_count DESC',
    'reviews' => 'b.review_count DESC',
    'name' => 'b.name ASC',
];
if ($dist['has']) {
    $orderMap['distance'] = 'distance_km ASC';
}
$orderBy = $orderMap[$sort] ?? $orderMap['rating'];

$itemSelect = "b.id, b.name, b.slug, b.city, b.region, b.price_range, b.short_description,
        b.is_verified, b.is_open, b.is_claimed, b.claim_status, b.rating_average, b.review_count, b.checkin_count,
        b.latitude, b.longitude, b.opening_hours" . ($dist['select'] ? ',' . $dist['select'] : '') . ",
        (SELECT c.name FROM categories c
           JOIN business_categories bc ON bc.category_id = c.id AND bc.business_id = b.id
          ORDER BY bc.is_primary DESC LIMIT 1) AS category_name,
        (SELECT photo_path FROM business_photos WHERE business_id = b.id
          ORDER BY is_primary DESC, id DESC LIMIT 1) AS primary_photo";

if ($openNow) {
    // Filter by open status in PHP first (overnight & multi-interval aware),
    // then paginate so total/pages reflect only open businesses.
    $now = new DateTimeImmutable();
    $candidates = $db->fetchAll(
        "SELECT b.id, b.opening_hours" . ($dist['select'] ? ',' . $dist['select'] : '') . "
           FROM businesses b
          WHERE $whereSql
          ORDER BY $orderBy",
        $params
    );
    $openRows = array_values(array_filter($candidates, fn($b) => is_open_now($b['opening_hours'] ?? null, $now)));
    $total = count($openRows);
    $pages = max(1, (int)ceil($total / $perPage));
    $ids = array_column(array_slice($openRows, $offset, $perPage), 'id');
    if ($ids) {
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $items = $db->fetchAll(
            "SELECT $itemSelect
               FROM businesses b
              WHERE b.id IN ($placeholders)
              ORDER BY $orderBy",
            $ids
        );
    } else {
        $items = [];
    }
} else {
    $total = (int)$db->fetchValue("SELECT COUNT(*) FROM businesses b WHERE $whereSql", $params);
    $pages = max(1, (int)ceil($total / $perPage));
    $items = $db->fetchAll(
        "SELECT $itemSelect
           FROM businesses b
          WHERE $whereSql
          ORDER BY $orderBy
          LIMIT $perPage OFFSET $offset",
        $params
    );
}

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
