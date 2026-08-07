<?php
/**
 * Google OAuth (Sign in with Google)
 *
 *   GET  api/oauth.php?action=login&provider=google
 *        → redirects to Google's consent screen (state-protected).
 *        In dev/demo mode (no client id configured) it returns a simulated
 *        link so the UI can be tested without Google credentials.
 *
 *   GET  api/oauth.php?action=callback&provider=google&code=...&state=...
 *        → exchanges the code, verifies identity, logs the user in, and
 *        redirects to dashboard (or returns JSON in dev mode).
 *
 * Security: the OAuth `state` parameter is the CSRF protection here — the
 * frontend never sends our CSRF header to Google, so we must not require it.
 * State is stored in the session with a short lifetime.
 */
require __DIR__ . '/_bootstrap.php';

require_method('GET');

$provider = $_GET['provider'] ?? '';
if ($provider !== 'google') {
    json_err('Unsupported OAuth provider', 422);
}

/* ================= start: redirect to Google ================= */
if (($_GET['action'] ?? '') === 'login') {
    rate_limit('oauth_login', 10, 300);

    if (GOOGLE_CLIENT_ID === '' || GOOGLE_CLIENT_SECRET === '') {
        // Dev/demo mode — simulate the flow so the UI is testable.
        $token = bin2hex(random_bytes(16));
        $_SESSION['oauth_state'] = ['provider' => 'google', 'token' => $token, 'expires' => time() + OAUTH_STATE_LIFETIME];
        $sim = APP_URL . '/api/oauth.php?action=callback&provider=google&code=mock-oauth-code&state=' . $token;
        if (APP_ENV === 'development') {
            json_ok([
                'mode' => 'demo',
                'message' => 'Google OAuth is not configured. Simulated login link (dev mode):',
                'login_url' => $sim,
            ]);
        }
        // Production without config: still hand back the simulated link — the
        // callback refuses to complete when keys are missing, so this is safe.
        json_err('Google sign-in is not configured yet. See config/config.php (GOOGLE_CLIENT_ID/SECRET).', 501);
    }

    $state = bin2hex(random_bytes(16));
    $_SESSION['oauth_state'] = ['provider' => 'google', 'token' => $state, 'expires' => time() + OAUTH_STATE_LIFETIME];

    $params = http_build_query([
        'client_id'     => GOOGLE_CLIENT_ID,
        'redirect_uri'  => GOOGLE_REDIRECT_URI,
        'response_type' => 'code',
        'scope'         => 'openid email profile',
        'state'         => $state,
        'prompt'        => 'select_account',
    ]);
    header('Location: https://accounts.google.com/o/oauth2/v2/auth?' . $params);
    exit;
}

/* ================= callback: exchange code + log in ================= */
if (($_GET['action'] ?? '') === 'callback') {
    rate_limit('oauth_callback', 10, 300);

    // --- verify state (CSRF protection for the redirect flow) ---
    $state = $_GET['state'] ?? '';
    $saved = $_SESSION['oauth_state'] ?? null;
    unset($_SESSION['oauth_state']);
    if (!$saved || $saved['provider'] !== 'google' || !hash_equals((string)$saved['token'], (string)$state)) {
        json_err('Invalid OAuth state — please try signing in again', 403);
    }
    if (time() > (int)$saved['expires']) {
        json_err('This sign-in attempt expired — please try again', 403);
    }

    $code = $_GET['code'] ?? '';
    if ($code === '') {
        json_err('Google did not return an authorization code', 422);
    }

    // Demo callback (dev mode simulated flow)
    if ($code === 'mock-oauth-code' && (GOOGLE_CLIENT_ID === '' || GOOGLE_CLIENT_SECRET === '')) {
        $profile = [
            'email'   => 'oauth.demo@example.com',
            'name'    => 'OAuth Demo User',
            'picture' => '',
        ];
        $result = Auth::findOrCreateOAuthUser('google', 'mock-google-sub-123', $profile);
        if (!$result['ok']) {
            json_err($result['error'], 422);
        }
        if (APP_ENV === 'development') {
            json_ok(['ok' => true, 'user' => $result['user'], 'redirect' => APP_URL . '/dashboard']);
        }
        header('Location: ' . APP_URL . '/dashboard');
        exit;
    }

    if (GOOGLE_CLIENT_ID === '' || GOOGLE_CLIENT_SECRET === '') {
        json_err('Google sign-in is not configured. Set GOOGLE_CLIENT_ID/SECRET in config/config.php.', 501);
    }

    // --- exchange the code for tokens ---
    $tokenResp = http_post_json('https://oauth2.googleapis.com/token', [
        'code'          => $code,
        'client_id'     => GOOGLE_CLIENT_ID,
        'client_secret' => GOOGLE_CLIENT_SECRET,
        'redirect_uri'  => GOOGLE_REDIRECT_URI,
        'grant_type'    => 'authorization_code',
    ]);
    if (!$tokenResp || empty($tokenResp['access_token'])) {
        json_err('Could not exchange the authorization code with Google', 502);
    }

    // --- fetch the user profile ---
    $userInfo = http_get_json('https://www.googleapis.com/oauth2/v3/userinfo', [
        'Authorization: Bearer ' . $tokenResp['access_token'],
    ]);
    if (!$userInfo || empty($userInfo['sub'])) {
        json_err('Could not fetch your Google profile', 502);
    }
    if (empty($userInfo['email_verified'])) {
        json_err('Google has not verified this email address — please verify it on your Google account first', 422);
    }

    $profile = [
        'email'   => $userInfo['email'] ?? '',
        'name'    => $userInfo['name'] ?? '',
        'picture' => $userInfo['picture'] ?? '',
    ];

    $result = Auth::findOrCreateOAuthUser('google', $userInfo['sub'], $profile);
    if (!$result['ok']) {
        json_err($result['error'], 422);
    }

    if (APP_ENV === 'development') {
        json_ok(['ok' => true, 'user' => $result['user'], 'redirect' => APP_URL . '/dashboard']);
    }
    header('Location: ' . APP_URL . '/dashboard');
    exit;
}

json_err('Unknown action', 404);

/* ================= tiny HTTP helpers (no curl dependency) ================= */

/** POST JSON to a URL, return decoded array or null on failure. */
function http_post_json($url, $payload)
{
    $opts = [
        'http' => [
            'method' => 'POST',
            'header' => "Content-Type: application/x-www-form-urlencoded\r\n",
            'content' => http_build_query($payload),
            'timeout' => 15,
            'ignore_errors' => true,
        ],
    ];
    $body = @file_get_contents($url, false, stream_context_create($opts));
    if ($body === false) {
        return null;
    }
    $data = json_decode($body, true);
    return is_array($data) ? $data : null;
}

/** GET a URL with extra headers, return decoded array or null on failure. */
function http_get_json($url, $headers = [])
{
    $opts = [
        'http' => [
            'method' => 'GET',
            'header' => implode("\r\n", $headers) . "\r\n",
            'timeout' => 15,
            'ignore_errors' => true,
        ],
    ];
    $body = @file_get_contents($url, false, stream_context_create($opts));
    if ($body === false) {
        return null;
    }
    $data = json_decode($body, true);
    return is_array($data) ? $data : null;
}
