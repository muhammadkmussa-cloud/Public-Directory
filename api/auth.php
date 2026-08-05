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

/* ---- logout ---- */
if ($action === 'logout') {
    require_method('POST');
    Auth::logout();
    json_ok(['logged_out' => true]);
}

json_err('Unknown action', 404);
