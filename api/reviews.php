<?php
/**
 * Reviews API
 *   GET  api/reviews.php?action=mine   → the logged-in user's reviews
 *   POST api/reviews.php  {action: 'create', reviewable_id, reviewable_type, rating, title, content}
 *   POST api/reviews.php  {action: 'helpful', review_id}
 *   POST api/reviews.php  {action: 'delete', review_id}      (author or admin)
 * Create/helpful/delete require login + CSRF.
 */
require __DIR__ . '/_bootstrap.php';

$db = Database::getInstance();

/* ================= my reviews ================= */
if ($_SERVER['REQUEST_METHOD'] === 'GET' && ($_GET['action'] ?? '') === 'mine') {
    $user = require_login();
    $rows = $db->fetchAll(
        'SELECT r.id, r.reviewable_id, r.reviewable_type, r.rating, r.title, r.content,
                r.helpful_count, r.created_at
           FROM reviews r
          WHERE r.user_id = ?
          ORDER BY r.created_at DESC
          LIMIT 50',
        [$user['id']]
    );
    // resolve listing names
    $names = [];
    foreach ($rows as &$row) {
        $key = $row['reviewable_type'] . ':' . $row['reviewable_id'];
        if (!isset($names[$key])) {
            $table = $row['reviewable_type'] === 'business' ? 'businesses' : ($row['reviewable_type'] === 'mosque' ? 'mosques' : 'fundis');
            if ($row['reviewable_type'] === 'fundi') {
                $name = $db->fetchValue(
                    "SELECT u.full_name FROM fundis f JOIN users u ON u.id = f.user_id WHERE f.id = ?",
                    [$row['reviewable_id']]
                );
            } else {
                $name = $db->fetchValue("SELECT name FROM $table WHERE id = ?", [$row['reviewable_id']]);
            }
            $names[$key] = $name ?: 'Deleted listing';
        }
        $row['listing_name'] = $names[$key];
        $row['listing_url'] = $row['reviewable_type'] . '.html?id=' . $row['reviewable_id'];
    }
    json_ok($rows);
}

require_method('POST');
$user = require_login();
require_csrf();

$db = Database::getInstance();
$body = json_body();
$action = $body['action'] ?? '';

/* ================= create ================= */
if ($action === 'create') {
    $reviewableId   = (int)($body['reviewable_id'] ?? 0);
    $reviewableType = $body['reviewable_type'] ?? '';
    $rating         = (int)($body['rating'] ?? 0);
    $title          = trim($body['title'] ?? '');
    $content        = trim($body['content'] ?? '');

    if (!in_array($reviewableType, ['business', 'mosque', 'fundi'], true)) {
        json_err('Invalid review target', 422);
    }
    if ($reviewableId < 1) {
        json_err('Missing review target', 422);
    }
    if ($rating < 1 || $rating > 5) {
        json_err('Rating must be between 1 and 5', 422);
    }
    if ($title === '' || mb_strlen($title) > 200) {
        json_err('Please add a short title (max 200 chars)', 422);
    }
    if ($content === '' || mb_strlen($content) > 2000) {
        json_err('Review text is required (max 2000 chars)', 422);
    }

    // target must exist
    $table = $reviewableType === 'business' ? 'businesses' : ($reviewableType === 'mosque' ? 'mosques' : 'fundis');
    if (!$db->fetchOne("SELECT id FROM $table WHERE id = ?", [$reviewableId])) {
        json_err('That listing does not exist', 404);
    }

    // one review per user per listing
    $existing = $db->fetchOne(
        'SELECT id FROM reviews WHERE user_id = ? AND reviewable_id = ? AND reviewable_type = ?',
        [$user['id'], $reviewableId, $reviewableType]
    );
    if ($existing) {
        json_err('You already reviewed this listing', 409);
    }

    $db->begin();
    try {
        $reviewId = $db->insert(
            'INSERT INTO reviews (user_id, reviewable_id, reviewable_type, rating, title, content, is_approved)
             VALUES (?, ?, ?, ?, ?, ?, 1)',
            [$user['id'], $reviewableId, $reviewableType, $rating, $title, $content]
        );

        // refresh cached aggregates on the listing
        $agg = $db->fetchOne(
            'SELECT AVG(rating) AS avg, COUNT(*) AS cnt FROM reviews
              WHERE reviewable_id = ? AND reviewable_type = ? AND is_approved = 1 AND is_hidden = 0',
            [$reviewableId, $reviewableType]
        );
        $avg = round((float)$agg['avg'], 2);
        $cnt = (int)$agg['cnt'];
        $db->execute(
            "UPDATE $table SET rating_average = ?, rating_count = ?, review_count = ? WHERE id = ?",
            [$avg, $cnt, $cnt, $reviewableId]
        );

        // contributor counters
        $db->execute('UPDATE users SET total_reviews = total_reviews + 1 WHERE id = ?', [$user['id']]);

        $db->commit();
    } catch (Throwable $e) {
        $db->rollback();
        error_log('Review create failed: ' . $e->getMessage());
        json_err('Could not save your review', 500);
    }

    json_ok(['review_id' => $reviewId], 201);
}

/* ================= helpful ================= */
if ($action === 'helpful') {
    $reviewId = (int)($body['review_id'] ?? 0);
    $review = $db->fetchOne('SELECT id FROM reviews WHERE id = ?', [$reviewId]);
    if (!$review) {
        json_err('Review not found', 404);
    }

    // prevent duplicates (PK review_id + user_id)
    $db->begin();
    try {
        $db->insert(
            'INSERT INTO review_helpful (review_id, user_id, is_helpful) VALUES (?, ?, 1)',
            [$reviewId, $user['id']]
        );
        $db->execute('UPDATE reviews SET helpful_count = helpful_count + 1 WHERE id = ?', [$reviewId]);
        $db->execute('UPDATE users SET helpful_votes = helpful_votes + 1 WHERE id = ?', [$user['id']]);
        $db->commit();
    } catch (Throwable $e) {
        $db->rollback();
        json_err('You already marked this review as helpful', 409);
    }

    $count = (int)$db->fetchValue('SELECT helpful_count FROM reviews WHERE id = ?', [$reviewId]);
    json_ok(['helpful_count' => $count]);
}

/* ================= delete ================= */
if ($action === 'delete') {
    $reviewId = (int)($body['review_id'] ?? 0);
    $review = $db->fetchOne('SELECT * FROM reviews WHERE id = ?', [$reviewId]);
    if (!$review) {
        json_err('Review not found', 404);
    }
    if ($review['user_id'] !== $user['id'] && $user['user_type'] !== 'admin') {
        json_err('You can only delete your own reviews', 403);
    }

    $db->execute('DELETE FROM reviews WHERE id = ?', [$reviewId]);

    // recalc aggregates
    $table = $review['reviewable_type'] === 'business' ? 'businesses' : ($review['reviewable_type'] === 'mosque' ? 'mosques' : 'fundis');
    $agg = $db->fetchOne(
        'SELECT AVG(rating) AS avg, COUNT(*) AS cnt FROM reviews
          WHERE reviewable_id = ? AND reviewable_type = ? AND is_approved = 1 AND is_hidden = 0',
        [$review['reviewable_id'], $review['reviewable_type']]
    );
    $db->execute(
        "UPDATE $table SET rating_average = ?, rating_count = ?, review_count = ? WHERE id = ?",
        [round((float)$agg['avg'], 2), (int)$agg['cnt'], (int)$agg['cnt'], $review['reviewable_id']]
    );

    json_ok(['deleted' => true]);
}

json_err('Unknown action', 404);
