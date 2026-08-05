<?php
/**
 * Umma Directory — API bootstrap
 * Every endpoint starts with:  require __DIR__ . '/_bootstrap.php';
 */

require_once __DIR__ . '/../config/config.php';

// ---- Secure session -------------------------------------------------------
$secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || ($_SERVER['SERVER_PORT'] ?? '') === '443';
if (session_status() === PHP_SESSION_NONE) {
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

// ---- Error handling -------------------------------------------------------
set_exception_handler(function ($e) {
    error_log('API error: ' . $e->getMessage());
    json_err('Something went wrong on the server', 500);
});
