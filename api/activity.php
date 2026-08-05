<?php
/**
 * Umma Directory — Recent Activity feed (homepage)
 *
 *   GET api/activity.php → latest approved reviews with user + listing info
 */
require __DIR__ . '/_bootstrap.php';

require_method('GET');

$db = Database::getInstance();

$rows = $db->fetchAll(
    "SELECT r.id, r.rating, r.title, r.content, r.created_at,
            r.reviewable_id, r.reviewable_type,
            u.full_name AS user_name, u.profile_photo,
            u.contributor_level, u.verification_badge
       FROM reviews r
       JOIN users u ON u.id = r.user_id
      WHERE r.is_approved = 1 AND r.is_hidden = 0
      ORDER BY r.created_at DESC
      LIMIT 10"
);

foreach ($rows as &$row) {
    $type = $row['reviewable_type'];
    $table = $type === 'business' ? 'businesses' : ($type === 'mosque' ? 'mosques' : 'fundis');
    if ($type === 'fundi') {
        $name = $db->fetchValue(
            'SELECT u2.full_name FROM fundis f JOIN users u2 ON u2.id = f.user_id WHERE f.id = ?',
            [$row['reviewable_id']]
        );
    } else {
        $name = $db->fetchValue("SELECT name FROM $table WHERE id = ?", [$row['reviewable_id']]);
    }
    $row['listing_name'] = $name ?: 'Deleted listing';
    $row['listing_url'] = $type . '.html?id=' . (int)$row['reviewable_id'];
    $row['listing_type'] = $type;
}

json_ok($rows);
