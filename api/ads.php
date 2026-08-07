<?php
/**
 * Ummah Directory — Ads API
 *
 *   GET  api/ads.php?placement=search_results        → active ads for a placement (public)
 *   GET  api/ads.php?action=list                     → all ads + stats (admin)
 *   POST api/ads.php?action=impression  {ad_id}      → record impression (CSRF)
 *   POST api/ads.php?action=click       {ad_id}      → record click (CSRF)
 *   POST api/ads.php?action=create      {...}        → create ad (admin, CSRF)
 *   POST api/ads.php?action=toggle      {ad_id,status} → activate/pause (admin, CSRF)
 *   POST api/ads.php?action=delete      {ad_id}      → delete ad (admin, CSRF)
 *
 * Placements (from schema seed): homepage_header(1), homepage_sidebar(2),
 * search_results(3), listing_page(4), detail_page(5).
 */
require __DIR__ . '/_bootstrap.php';

$db = Database::getInstance();
$action = $_GET['action'] ?? '';
$method = $_SERVER['REQUEST_METHOD'];

/* ================= serve ads for a placement ================= */
if ($method === 'GET' && $action === '') {
    $placement = $_GET['placement'] ?? 'search_results';
    $category  = is_string($_GET['category'] ?? null) ? trim($_GET['category']) : '';
    $city      = is_string($_GET['city'] ?? null) ? trim($_GET['city']) : '';

    $place = $db->fetchOne(
        'SELECT id, location, max_ads FROM ad_placements WHERE location = ? AND is_active = 1',
        [$placement]
    );
    if (!$place) {
        json_ok(['ads' => [], 'placement' => null]);
    }

    // Optional targeting: an ad matches when it has no target for a given
    // dimension, or its target equals the page context.
    $targetWhere  = '';
    $targetParams = [];
    if ($category !== '') {
        $targetWhere  .= ' AND (a.target_category IS NULL OR a.target_category = ?)';
        $targetParams[] = $category;
    }
    if ($city !== '') {
        $targetWhere  .= ' AND (a.target_city IS NULL OR a.target_city = ?)';
        $targetParams[] = $city;
    }

    $limit = (int)$place['max_ads'];
    $ads = $db->fetchAll(
        "SELECT a.id, a.title, a.link_url, a.image_path, a.html_content, a.priority
           FROM ads a
          WHERE a.placement_id = ? AND a.status = 'active'
            AND a.start_date <= CURDATE() AND a.end_date >= CURDATE()
            $targetWhere
          ORDER BY a.priority DESC, a.id ASC
          LIMIT $limit",
        array_merge([$place['id']], $targetParams)
    );

    json_ok([
        'ads' => $ads,
        'placement' => ['location' => $place['location'], 'max_ads' => $limit],
    ]);
}

/* ================= admin list ================= */
if ($action === 'list') {
    require_method('GET');
    $admin = require_login();
    if ($admin['user_type'] !== 'admin') {
        json_err('Admins only', 403);
    }
    $rows = $db->fetchAll(
        'SELECT a.*, p.name AS placement_name, p.location AS placement_location
           FROM ads a
           JOIN ad_placements p ON p.id = a.placement_id
          ORDER BY a.status, a.priority DESC, a.id DESC'
    );
    json_ok($rows);
}

/* ================= analytics (admin) ================= */
if ($action === 'analytics') {
    require_method('GET');
    $admin = require_login();
    if ($admin['user_type'] !== 'admin') {
        json_err('Admins only', 403);
    }

    $adId = (int)($_GET['ad_id'] ?? 0);
    $timeframe = $_GET['timeframe'] ?? '7d';
    $days = $timeframe === '30d' ? 30 : ($timeframe === '90d' ? 90 : 7);

    if ($adId > 0) {
        $ad = $db->fetchOne('SELECT id, title, impressions, clicks FROM ads WHERE id = ?', [$adId]);
        if (!$ad) json_err('Ad not found', 404);
    }

    $startDate = date('Y-m-d 00:00:00', strtotime("-{$days} days"));

    $whereImp = $adId > 0 ? 'WHERE created_at >= ? AND ad_id = ?' : 'WHERE created_at >= ?';
    $paramsImp = $adId > 0 ? [$startDate, $adId] : [$startDate];

    $impRows = $db->fetchAll(
        "SELECT DATE(created_at) AS date_key, COUNT(*) AS impressions
           FROM ad_impressions
          $whereImp
          GROUP BY DATE(created_at)",
        $paramsImp
    );

    $clickRows = $db->fetchAll(
        "SELECT DATE(created_at) AS date_key, COUNT(*) AS clicks
           FROM ad_clicks
          $whereImp
          GROUP BY DATE(created_at)",
        $paramsImp
    );

    $byDate = [];
    for ($i = $days - 1; $i >= 0; $i--) {
        $d = date('Y-m-d', strtotime("-{$i} days"));
        $byDate[$d] = ['date' => $d, 'impressions' => 0, 'clicks' => 0, 'ctr' => 0.0];
    }

    foreach ($impRows as $r) {
        if (isset($byDate[$r['date_key']])) {
            $byDate[$r['date_key']]['impressions'] = (int)$r['impressions'];
        }
    }

    foreach ($clickRows as $r) {
        if (isset($byDate[$r['date_key']])) {
            $byDate[$r['date_key']]['clicks'] = (int)$r['clicks'];
        }
    }

    foreach ($byDate as &$d) {
        if ($d['impressions'] > 0) {
            $d['ctr'] = round(($d['clicks'] / $d['impressions']) * 100, 2);
        }
    }

    json_ok([
        'ad_id'     => $adId ?: null,
        'timeframe' => $timeframe,
        'days'      => $days,
        'series'    => array_values($byDate),
    ]);
}

/* ================= impression ================= */
if ($action === 'impression') {
    require_method('POST');
    rate_limit('ad_imp', 300, 300);
    require_csrf();
    $body = json_body();
    $adId = (int)($body['ad_id'] ?? 0);
    if (!$db->fetchOne('SELECT id FROM ads WHERE id = ?', [$adId])) {
        json_err('Ad not found', 404);
    }
    $u = Auth::isLoggedIn() ? Auth::user() : null;
    $db->insert(
        'INSERT INTO ad_impressions (ad_id, user_id, ip_address, page_url) VALUES (?, ?, ?, ?)',
        [$adId, $u['id'] ?? null, $_SERVER['REMOTE_ADDR'] ?? null, $_SERVER['HTTP_REFERER'] ?? null]
    );
    $db->execute('UPDATE ads SET impressions = impressions + 1 WHERE id = ?', [$adId]);
    json_ok(['impressions' => (int)$db->fetchValue('SELECT impressions FROM ads WHERE id = ?', [$adId])]);
}

/* ================= click ================= */
if ($action === 'click') {
    require_method('POST');
    rate_limit('ad_click', 120, 300);
    require_csrf();
    $body = json_body();
    $adId = (int)($body['ad_id'] ?? 0);
    if (!$db->fetchOne('SELECT id FROM ads WHERE id = ?', [$adId])) {
        json_err('Ad not found', 404);
    }
    $u = Auth::isLoggedIn() ? Auth::user() : null;
    $db->insert(
        'INSERT INTO ad_clicks (ad_id, user_id, ip_address, page_url) VALUES (?, ?, ?, ?)',
        [$adId, $u['id'] ?? null, $_SERVER['REMOTE_ADDR'] ?? null, $_SERVER['HTTP_REFERER'] ?? null]
    );
    $db->execute('UPDATE ads SET clicks = clicks + 1 WHERE id = ?', [$adId]);
    json_ok(['clicks' => (int)$db->fetchValue('SELECT clicks FROM ads WHERE id = ?', [$adId])]);
}

/* ================= create (admin) ================= */
if ($action === 'create') {
    require_method('POST');
    $admin = require_login();
    if ($admin['user_type'] !== 'admin') {
        json_err('Admins only', 403);
    }
    require_csrf();

    $b = json_body();
    // Store raw text — every render path escapes with esc()/e(); encoding here
    // would double-encode (e.g. "&" → "&amp;").
    $title        = trim((string)($b['title'] ?? ''));
    $link         = trim((string)($b['link_url'] ?? ''));
    $placementId  = (int)($b['placement_id'] ?? 0);
    $priority     = (int)($b['priority'] ?? 0);
    $image        = trim((string)($b['image_path'] ?? ''));
    $tagline      = mb_substr(trim((string)($b['html_content'] ?? '')), 0, 500);
    $targetCat    = mb_substr(trim((string)($b['target_category'] ?? '')), 0, 100);
    $targetCity   = mb_substr(trim((string)($b['target_city'] ?? '')), 0, 100);

    if ($title === '' || $link === '' || $placementId < 1) {
        json_err('Title, link URL and placement are required', 422);
    }
    if (preg_match('#^(javascript:|data:)#i', $link)) {
        json_err('That link URL is not allowed', 422);
    }
    if (mb_strlen($title) > 200 || mb_strlen($link) > 500) {
        json_err('Title or link too long', 422);
    }
    if (!$db->fetchOne('SELECT id FROM ad_placements WHERE id = ?', [$placementId])) {
        json_err('Placement not found', 422);
    }

    // Targeting must reference a real category slug / city, otherwise the ad
    // silently never serves.
    if ($targetCat !== '') {
        $slugOk = $db->fetchValue(
            'SELECT id FROM categories WHERE slug = ? AND type = ? AND is_active = 1',
            [$targetCat, 'business']
        );
        if (!$slugOk) json_err('Unknown target category', 422);
    }
    if ($targetCity !== '') {
        $cityOk = $db->fetchValue(
            'SELECT 1 FROM businesses WHERE city = ? LIMIT 1',
            [$targetCity]
        );
        if (!$cityOk) json_err('Unknown target city', 422);
    }

    $adId = $db->insert(
        'INSERT INTO ads (placement_id, advertiser_id, title, image_path, link_url, html_content,
                          start_date, end_date, status, priority, target_category, target_city)
         VALUES (?, ?, ?, ?, ?, ?, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 30 DAY), \'active\', ?, ?, ?)',
        [$placementId, $admin['id'], $title, $image ?: null, $link, $tagline ?: null, $priority, $targetCat ?: null, $targetCity ?: null]
    );

    json_ok(['ad_id' => $adId], 201);
}

/* ================= toggle status (admin) ================= */
if ($action === 'toggle') {
    require_method('POST');
    $admin = require_login();
    if ($admin['user_type'] !== 'admin') {
        json_err('Admins only', 403);
    }
    require_csrf();

    $b = json_body();
    $adId = (int)($b['ad_id'] ?? 0);
    $status = ($b['status'] ?? '') === 'active' ? 'active' : 'paused';
    $db->execute('UPDATE ads SET status = ? WHERE id = ?', [$status, $adId]);
    json_ok(['status' => $status]);
}

/* ================= delete (admin) ================= */
if ($action === 'delete') {
    require_method('POST');
    $admin = require_login();
    if ($admin['user_type'] !== 'admin') {
        json_err('Admins only', 403);
    }
    require_csrf();

    $adId = (int)(json_body()['ad_id'] ?? 0);
    $db->execute('DELETE FROM ads WHERE id = ?', [$adId]);
    json_ok(['deleted' => true]);
}

json_err('Unknown action', 404);
