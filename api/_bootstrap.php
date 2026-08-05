<?php
/**
 * Umma Directory — API bootstrap
 * Every endpoint starts with:  require __DIR__ . '/_bootstrap.php';
 */

require_once __DIR__ . '/../config/config.php';

// ---- Secure session -------------------------------------------------------
$secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || ($_SERVER['SERVER_PORT'] ?? '') === '443';
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.use_strict_mode', '1');
    ini_set('session.cookie_samesite', 'Lax');
    ini_set('session.cookie_httponly', '1');
    session_set_cookie_params([
        'lifetime' => SESSION_LIFETIME,
        'path'     => '/',
        'domain'   => '',
        'secure'   => $secure,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_name('umdir_session');
    session_start();
}

require_once __DIR__ . '/../includes/Database.php';
require_once __DIR__ . '/../includes/Auth.php';

// ---- Security headers (API) ----------------------------------------------
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('X-XSS-Protection: 1; mode=block');
if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
    header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
}
header('Cache-Control: no-store, private'); // API responses are never cached

// ---- Rate limiting (per-IP token bucket stored on disk) --------------------
// Counters live outside the session so clearing cookies can't bypass limits.
// Note: on shared hosting the throttling is still best-effort (files are fast,
// but not atomic under heavy concurrency); for hard guarantees use mod_evasive
// or a WAF (e.g. Cloudflare).
function rate_limit($key, $max = 60, $window = 60)
{
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $now = time();
    $dir = rtrim(sys_get_temp_dir(), '/') . '/umdir_rl';
    if (!is_dir($dir)) {
        @mkdir($dir, 0700, true);
    }
    $file = $dir . '/' . $key . '_' . md5($ip) . '.json';

    $data = ['count' => 0, 'reset' => $now + $window];
    if (is_file($file)) {
        $raw = @file_get_contents($file);
        $decoded = $raw ? json_decode($raw, true) : null;
        if (is_array($decoded) && isset($decoded['count'], $decoded['reset'])) {
            $data = $decoded;
        }
    }
    if ($data['reset'] <= $now) {
        $data = ['count' => 0, 'reset' => $now + $window];
    }
    $data['count']++;
    @file_put_contents($file, json_encode($data), LOCK_EX);

    // Occasionally prune expired buckets so the temp dir doesn't grow unbounded.
    if (mt_rand(1, 100) === 1) {
        foreach ((array)glob($dir . '/*.json') as $f) {
            $j = @file_get_contents($f);
            $d = $j ? json_decode($j, true) : null;
            if (!is_array($d) || !isset($d['reset']) || $d['reset'] <= $now) {
                @unlink($f);
            }
        }
    }

    if ($data['count'] > $max) {
        json_err('Too many requests — please slow down', 429);
    }
}

// ---- JSON response helpers ------------------------------------------------
function json_out($data, $code = 200)
{
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    header('X-Content-Type-Options: nosniff');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function json_ok($data = null, $code = 200)
{
    json_out(['success' => true, 'data' => $data], $code);
}

function json_err($message, $code = 400)
{
    json_out(['success' => false, 'error' => $message], $code);
}

function require_method($method)
{
    if ($_SERVER['REQUEST_METHOD'] !== $method) {
        header('Allow: ' . $method);
        json_err('Method not allowed', 405);
    }
}

/** Logged-in user row or 401 */
function require_login()
{
    $user = Auth::currentUser();
    if (!$user) {
        json_err('Please login to continue', 401);
    }
    return $user;
}

/**
 * Build a Haversine "distance from point" expression for near-me searches.
 *
 * Reads lat/lng/radius from the query string. Returns:
 *   has    — whether valid lat/lng was supplied
 *   select — expression AS distance_km ('' when absent)
 *   where  — expression <= radius ('' when absent)
 *   radius — the radius in km used (for the frontend)
 *
 * Values are validated numerically and embedded via sprintf('%.6F') —
 * a sanitized float cannot carry SQL, so no parameter binding is needed
 * and the same expression can appear in both SELECT and WHERE.
 */
function distance_clause($alias)
{
    if (!isset($_GET['lat'], $_GET['lng'])) {
        return ['has' => false, 'select' => '', 'where' => '', 'radius' => null];
    }
    if (!is_numeric($_GET['lat']) || !is_numeric($_GET['lng'])) {
        return ['has' => false, 'select' => '', 'where' => '', 'radius' => null];
    }
    $lat = (float)$_GET['lat'];
    $lng = (float)$_GET['lng'];
    if ($lat < -90 || $lat > 90 || $lng < -180 || $lng > 180) {
        return ['has' => false, 'select' => '', 'where' => '', 'radius' => null];
    }
    $latF = sprintf('%.6F', $lat);
    $lngF = sprintf('%.6F', $lng);
    $radius = isset($_GET['radius']) && is_numeric($_GET['radius'])
        ? max(1.0, min(200.0, (float)$_GET['radius']))
        : 50.0;

    $expr = '(6371 * ACOS(LEAST(1, COS(RADIANS(' . $latF . ')) * COS(RADIANS(' . $alias . '.latitude))'
        . ' * COS(RADIANS(' . $alias . '.longitude) - RADIANS(' . $lngF . '))'
        . ' + SIN(RADIANS(' . $latF . ')) * SIN(RADIANS(' . $alias . '.latitude)))))';

    return [
        'has' => true,
        'select' => $expr . ' AS distance_km',
        'where' => $expr . ' <= ' . sprintf('%.1F', $radius),
        'radius' => $radius,
    ];
}

/** Require a valid CSRF token (header X-CSRF-Token) for state-changing calls */
function require_csrf()
{
    $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (!Auth::verifyCsrf($token)) {
        json_err('Invalid security token. Refresh the page and try again.', 403);
    }
}

/** Read JSON request body as array */
function json_body()
{
    $raw = file_get_contents('php://input');
    $data = json_decode($raw ?: '', true);
    return is_array($data) ? $data : $_POST;
}

/** Attach each review's photo paths (from review_photos) to the review rows */
function attachReviewPhotos($db, &$reviews)
{
    $ids = array_column($reviews, 'id');
    if (!$ids) {
        return;
    }
    $in = implode(',', array_fill(0, count($ids), '?'));
    $byReview = [];
    foreach ($db->fetchAll(
        "SELECT review_id, photo_path FROM review_photos WHERE review_id IN ($in) ORDER BY id ASC",
        $ids
    ) as $p) {
        $byReview[$p['review_id']][] = $p['photo_path'];
    }
    foreach ($reviews as &$r) {
        $r['photos'] = $byReview[$r['id']] ?? [];
    }
}

/** Insert a notification for a user (fire-and-forget) */
function notify($db, $userId, $type, $title, $message, $link = null, $data = null)
{
    if (!$userId) return;
    try {
        $db->insert(
            'INSERT INTO notifications (user_id, type, title, message, link, data)
             VALUES (?, ?, ?, ?, ?, ?)',
            [$userId, $type, $title, $message, $link, $data ? json_encode($data) : null]
        );
    } catch (Throwable $e) {
        error_log('notify() failed: ' . $e->getMessage());
    }
}

// ---- Error handling -------------------------------------------------------
set_exception_handler(function ($e) {
    error_log('API error: ' . $e->getMessage());
    json_err('Something went wrong on the server', 500);
});
