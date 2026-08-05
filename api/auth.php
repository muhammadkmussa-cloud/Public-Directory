<?php
/**
 * Auth API
 *   GET  api/auth.php?action=me        → current user (or null)
 *   POST api/auth.php?action=login     → {identifier, password}
 *   POST api/auth.php?action=register  → {username, email, password, full_name, phone}
 *   POST api/auth.php?action=logout
 */
require __DIR__ . '/_bootstrap.php';

$action = $_GET['action'] ?? '';

/* ---- current user ---- */
if ($action === 'me') {
    require_method('GET');
    json_ok(Auth::currentUser()); // null when logged out
}

/* ---- login ---- */
if ($action === 'login') {
    require_method('POST');
    rate_limit('login', 8, 300);          // 8 attempts / 5 min per IP
    require_csrf();
    $body = json_body();
    $result = Auth::login($body['identifier'] ?? '', $body['password'] ?? '');
    if (!$result['ok']) {
        json_err($result['error'], 401);
    }
    json_ok($result['user']);
}

/* ---- register ---- */
if ($action === 'register') {
    require_method('POST');
    rate_limit('register', 5, 900);       // 5 registrations / 15 min per IP
    require_csrf();
    $body = json_body();
    $result = Auth::register(
        $body['username'] ?? '',
        $body['email'] ?? '',
        $body['password'] ?? '',
        $body['full_name'] ?? '',
        $body['phone'] ?? ''
    );
    if (!$result['ok']) {
        json_err($result['error'], 422);
    }
    json_ok($result['user'], 201);
}

/* ---- forgot password ---- */
if ($action === 'forgot') {
    require_method('POST');
    rate_limit('forgot', 5, 900);         // prevent email bombing
    require_csrf();
    $body = json_body();
    $result = Auth::requestPasswordReset($body['email'] ?? '');
    if (!$result['ok']) {
        json_err($result['error'], 422);
    }
    // in development mode the token is returned so the flow is testable without SMTP
    json_ok($result);
}

/* ---- reset password ---- */
if ($action === 'reset') {
    require_method('POST');
    rate_limit('reset', 5, 900);
    require_csrf();
    $body = json_body();
    $result = Auth::resetPassword($body['token'] ?? '', $body['password'] ?? '');
    if (!$result['ok']) {
        json_err($result['error'], 422);
    }
    json_ok($result);
}

/* ---- logout ---- */
if ($action === 'logout') {
    require_method('POST');
    require_csrf();
    Auth::logout();
    json_ok(['logged_out' => true]);
}

json_err('Unknown action', 404);
