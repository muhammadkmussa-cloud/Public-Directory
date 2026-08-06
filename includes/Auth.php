<?php
/**
 * Ummah Directory — Authentication (session based)
 * Static helpers used by the JSON API.
 */

require_once __DIR__ . '/Database.php';

class Auth
{
    private static $db;
    private static $currentUser = null;
    private static $currentUserLoaded = false;

    private static function db()
    {
        if (self::$db === null) {
            self::$db = Database::getInstance();
        }
        return self::$db;
    }

    /* ------------------------------------------------------------------
     * Registration
     * ------------------------------------------------------------------ */
    public static function register($username, $email, $password, $fullName = '', $phone = '')
    {
        $username = trim($username);
        $email    = strtolower(trim($email));
        $fullName = trim($fullName);
        $phone    = trim($phone);

        if (strlen($username) < 3 || strlen($username) > 50) {
            return ['ok' => false, 'error' => 'Username must be 3-50 characters'];
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['ok' => false, 'error' => 'Invalid email address'];
        }
        if (strlen($password) < 8) {
            return ['ok' => false, 'error' => 'Password must be at least 8 characters'];
        }
        if (self::isWeakPassword($password, $username, $email)) {
            return ['ok' => false, 'error' => 'Password is too weak — use a longer, less common password'];
        }

        $db = self::db();
        if ($db->fetchOne('SELECT id FROM users WHERE email = ?', [$email])) {
            return ['ok' => false, 'error' => 'That email is already registered'];
        }
        if ($db->fetchOne('SELECT id FROM users WHERE username = ?', [$username])) {
            return ['ok' => false, 'error' => 'That username is already taken'];
        }

        $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => HASH_COST]);

        $id = $db->insert(
            'INSERT INTO users (username, email, password_hash, full_name, phone, user_type, is_active)
             VALUES (?, ?, ?, ?, ?, \'regular\', 1)',
            [$username, $email, $hash, $fullName ?: null, $phone ?: null]
        );

        if (!$id) {
            return ['ok' => false, 'error' => 'Registration failed, please try again'];
        }

        $token = self::sendVerificationEmail($id, $email, $username); // emails a one-time link
        self::login($username, $password); // auto-login after register (soft-gated)
        $user = self::currentUser();
        $user['email_verified'] = false;
        if (APP_ENV === 'development') {
            $user['verification_link'] = (APP_URL ?: '') . '/verify.html?token=' . $token;
        }
        return ['ok' => true, 'user' => $user];
    }

    /* ------------------------------------------------------------------
     * Email verification
     * ------------------------------------------------------------------ */

    /** Generate a token, store it, and email the one-time confirmation link. */
    public static function sendVerificationEmail($userId, $email, $username)
    {
        $token = bin2hex(random_bytes(32));
        self::db()->execute(
            'INSERT INTO email_verifications (user_id, token, expires_at, used)
             VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 24 HOUR), 0)',
            [$userId, $token]
        );

        $link = (APP_URL ?: '') . '/verify.html?token=' . $token;
        $subject = 'Confirm your email — ' . APP_NAME;
        $message = "Assalamu alaikum" . ($username ? " $username" : '') . ",\n\n"
                 . "Welcome to " . APP_NAME . "! Please confirm your email address by clicking the link below:\n\n"
                 . "$link\n\n"
                 . "This link expires in 24 hours. If you didn't create this account, you can safely ignore this email.\n\n"
                 . '— ' . APP_NAME . ' Team';
        @mail($email, $subject, $message, 'From: ' . SMTP_FROM_NAME . ' <' . SMTP_FROM_EMAIL . '>');

        return $token;
    }

    /** Verify a user with a one-time token. Returns ['ok'=>true] or an error. */
    public static function verifyEmail($token)
    {
        if (!is_string($token) || strlen($token) < 32) {
            return ['ok' => false, 'error' => 'Invalid verification link'];
        }
        $row = self::db()->fetchOne(
            'SELECT * FROM email_verifications WHERE token = ? AND used = 0 AND expires_at > NOW()',
            [$token]
        );
        if (!$row) {
            return ['ok' => false, 'error' => 'This verification link is invalid or has expired. Request a new one.'];
        }
        self::db()->execute('UPDATE users SET is_verified = 1 WHERE id = ?', [$row['user_id']]);
        self::db()->execute('UPDATE email_verifications SET used = 1 WHERE id = ?', [$row['id']]);
        return ['ok' => true, 'user_id' => (int)$row['user_id']];
    }

    /**
     * Re-send a verification email to an unverified account (rate-limited at the API).
     * Never reveals whether the email exists.
     */
    public static function resendVerification($email)
    {
        $email = strtolower(trim($email));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['ok' => false, 'error' => 'Invalid email address'];
        }
        $user = self::db()->fetchOne(
            'SELECT id, username, email FROM users WHERE email = ? AND is_verified = 0 AND is_active = 1',
            [$email]
        );
        if (!$user) {
            return ['ok' => true, 'message' => 'If that account exists and is unverified, a new confirmation link has been sent.'];
        }
        $token = self::sendVerificationEmail($user['id'], $user['email'], $user['username']);
        if (APP_ENV === 'development') {
            return ['ok' => true, 'message' => 'Verification email sent', 'verification_link' => (APP_URL ?: '') . '/verify.html?token=' . $token];
        }
        return ['ok' => true, 'message' => 'If that account exists and is unverified, a new confirmation link has been sent.'];
    }

    /* ------------------------------------------------------------------
     * Login / logout
     * ------------------------------------------------------------------ */
    public static function login($identifier, $password)
    {
        $identifier = trim($identifier);
        if ($identifier === '' || $password === '') {
            return ['ok' => false, 'error' => 'Please enter your email/username and password'];
        }

        // per-account lockout: 10 failed attempts → 15 min block
        $failKey = 'login_fail_' . md5(strtolower($identifier));
        $failCount = (int)($_SESSION['lockout'][$failKey]['count'] ?? 0);
        $failUntil = (int)($_SESSION['lockout'][$failKey]['until'] ?? 0);
        if ($failCount >= 10 && time() < $failUntil) {
            return ['ok' => false, 'error' => 'Too many failed attempts — try again in a few minutes'];
        }

        $field = filter_var($identifier, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';
        $user = self::db()->fetchOne(
            "SELECT * FROM users WHERE ($field = ?) AND is_active = 1",
            [$identifier]
        );

        if (!$user || !password_verify($password, $user['password_hash'])) {
            // record the failure for lockout
            $_SESSION['lockout'][$failKey]['count'] = $failCount + 1;
            if ($failCount + 1 >= 10) {
                $_SESSION['lockout'][$failKey]['until'] = time() + 900;
            }
            return ['ok' => false, 'error' => 'Invalid credentials'];
        }

        // success → clear any lockout for this account
        unset($_SESSION['lockout'][$failKey]);

        self::startSession((int)$user['id']);
        return ['ok' => true, 'user' => self::currentUser()];
    }

    /**
     * Issue a session for a user id (used by password login and OAuth).
     * Records a revocable token in user_sessions, updates last_login.
     */
    public static function startSession($userId)
    {
        session_regenerate_id(true);

        // Issue a session token recorded in user_sessions so that we can revoke
        // a user's other sessions (e.g. on password reset).
        $token = bin2hex(random_bytes(32));
        try {
            self::db()->insert(
                'INSERT INTO user_sessions (user_id, session_token, ip_address, user_agent, expires_at)
                 VALUES (?, ?, ?, ?, DATE_ADD(NOW(), INTERVAL ' . (int)SESSION_LIFETIME . ' SECOND))',
                [$userId, $token, $_SERVER['REMOTE_ADDR'] ?? null, substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255)]
            );
        } catch (Throwable $e) {
            // user_sessions table may be missing on older installs — proceed
            // without token-based revocation rather than breaking login.
            $token = '';
            error_log('Auth::startSession user_sessions insert failed: ' . $e->getMessage());
        }

        $_SESSION['user_id']       = $userId;
        $_SESSION['logged_in']     = true;
        $_SESSION['umdir_auth_token'] = $token;

        self::db()->execute('UPDATE users SET last_login = NOW() WHERE id = ?', [$userId]);

        // Invalidate any cached user from earlier in this request.
        self::$currentUser = null;
        self::$currentUserLoaded = false;
    }

    /* ------------------------------------------------------------------
     * OAuth (Google sign-in)
     * ------------------------------------------------------------------ */

    /**
     * Find or create a user for a verified OAuth identity, then log them in.
     *
     * @param string $provider        'google'
     * @param string $providerUserId  Google's stable sub/ID
     * @param array  $profile         ['email' =>, 'name' =>, 'picture' =>]
     * @return array ['ok'=>true,'user'=>...] or ['ok'=>false,'error'=>...]
     */
    public static function findOrCreateOAuthUser($provider, $providerUserId, $profile)
    {
        $email = strtolower(trim($profile['email'] ?? ''));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['ok' => false, 'error' => 'Google did not return a valid email address'];
        }
        $db = self::db();

        // 1) existing link → log in
        $link = $db->fetchOne(
            'SELECT id, user_id FROM oauth_links WHERE provider = ? AND provider_user_id = ?',
            [$provider, $providerUserId]
        );
        if ($link) {
            $user = $db->fetchOne('SELECT id, is_active FROM users WHERE id = ?', [$link['user_id']]);
            if (!$user) {
                // linked user was deleted → drop the stale link, then re-link below
                $db->execute('DELETE FROM oauth_links WHERE id = ?', [$link['id']]);
            } elseif ((int)$user['is_active'] !== 1) {
                return ['ok' => false, 'error' => 'This account has been disabled'];
            } else {
                self::startSession((int)$user['id']);
                return ['ok' => true, 'user' => self::currentUser()];
            }
        }

        // 2) matching email on a local account → link + log in
        $existing = $db->fetchOne(
            'SELECT id, is_active, is_verified FROM users WHERE email = ?',
            [$email]
        );
        if ($existing) {
            if ((int)$existing['is_active'] !== 1) {
                return ['ok' => false, 'error' => 'This account has been disabled'];
            }
            $db->insert(
                'INSERT INTO oauth_links (user_id, provider, provider_user_id) VALUES (?, ?, ?)',
                [(int)$existing['id'], $provider, $providerUserId]
            );
            $db->execute('UPDATE users SET is_verified = 1 WHERE id = ?', [$existing['id']]);
            self::startSession((int)$existing['id']);
            return ['ok' => true, 'user' => self::currentUser()];
        }

        // 3) brand-new account — Google has already verified the email
        $name = trim($profile['name'] ?? '');
        $base = strtolower(preg_replace('/[^A-Za-z0-9_]/', '', explode('@', $email)[0]));
        if (strlen($base) < 3) {
            $base = 'user';
        }
        $username = $base;
        $suffix = 1;
        while ($db->fetchOne('SELECT id FROM users WHERE username = ?', [$username])) {
            $username = $base . ($suffix++);
        }

        $id = $db->insert(
            'INSERT INTO users (username, email, password_hash, full_name, profile_photo, user_type, is_active, is_verified)
             VALUES (?, ?, ?, ?, ?, \'regular\', 1, 1)',
            [
                $username,
                $email,
                // unguessable random hash — account is OAuth-only (password login never used)
                password_hash(bin2hex(random_bytes(24)), PASSWORD_BCRYPT, ['cost' => HASH_COST]),
                $name !== '' ? $name : null,
                $profile['picture'] ?? null,
            ]
        );
        if (!$id) {
            return ['ok' => false, 'error' => 'Could not create your account — please try again'];
        }
        $db->insert(
            'INSERT INTO oauth_links (user_id, provider, provider_user_id) VALUES (?, ?, ?)',
            [$id, $provider, $providerUserId]
        );
        self::startSession($id);
        return ['ok' => true, 'user' => self::currentUser()];
    }

    public static function logout()
    {
        // Remove the session's DB token so the session is truly revoked.
        if (!empty($_SESSION['user_id']) && !empty($_SESSION['umdir_auth_token'])) {
            try {
                self::db()->execute(
                    'DELETE FROM user_sessions WHERE user_id = ? AND session_token = ?',
                    [(int)$_SESSION['user_id'], $_SESSION['umdir_auth_token']]
                );
            } catch (Throwable $e) {
                // user_sessions may be missing on older installs — ignore.
            }
        }
        $_SESSION = [];
        self::$currentUser = null;
        self::$currentUserLoaded = false;
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
        }
        session_destroy();
    }

    /* ------------------------------------------------------------------
     * Session state
     * ------------------------------------------------------------------ */
    public static function isLoggedIn()
    {
        return !empty($_SESSION['logged_in']) && !empty($_SESSION['user_id']);
    }

    /** Full user row (without password hash) or null */
    public static function currentUser()
    {
        if (self::$currentUserLoaded) {
            return self::$currentUser;
        }
        self::$currentUserLoaded = true;

        if (!self::isLoggedIn()) {
            self::$currentUser = null;
            return null;
        }
        // Validate this session's token against user_sessions. If it has been
        // revoked (e.g. the password was reset elsewhere) or has expired, log out.
        $token = $_SESSION['umdir_auth_token'] ?? '';
        if ($token === '' || !self::sessionValid((int)$_SESSION['user_id'], $token)) {
            self::logout();
            self::$currentUser = null;
            return null;
        }
        self::$currentUser = self::db()->fetchOne(
            'SELECT id, username, email, full_name, phone, profile_photo, user_type,
                    is_verified, verification_badge, contributor_level, total_reviews,
                    total_checkins, total_photos, helpful_votes, created_at, last_login
             FROM users WHERE id = ?',
            [$_SESSION['user_id']]
        );
        if (self::$currentUser) {
            // friendly alias the frontend uses to show the "Confirm email" state
            self::$currentUser['email_verified'] = (int)self::$currentUser['is_verified'] === 1;
        }
        return self::$currentUser;
    }

    /**
     * Whether a session token is still valid for a user.
     * On old installs without a user_sessions table this returns true so the
     * site keeps working (token-based revocation simply isn't available).
     */
    private static function sessionValid($userId, $token)
    {
        try {
            return (bool)self::db()->fetchOne(
                'SELECT 1 FROM user_sessions
                  WHERE user_id = ? AND session_token = ? AND expires_at > NOW()',
                [$userId, $token]
            );
        } catch (Throwable $e) {
            error_log('Auth::sessionValid failed: ' . $e->getMessage());
            return true;
        }
    }

    /** Convenience aliases (match the names used by the old frontend) */
    public static function check() { return self::isLoggedIn(); }
    public static function user()   { return self::currentUser(); }

    public static function isAdmin()
    {
        $u = self::currentUser();
        return $u && $u['user_type'] === 'admin';
    }

    /** Reject common/weak passwords and passwords containing the username/email */
    private static function isWeakPassword($password, $username = '', $email = '')
    {
        $p = strtolower(trim($password));
        if (strlen($p) < 8) return true;
        if ($p === strtolower(trim($username))) return true;
        if ($email && strpos($p, strtolower(trim(explode('@', $email)[0]))) !== false) return true;

        $common = ['password', '12345678', '123456789', 'qwertyui', 'iloveyou', 'admin123',
                   'letmein', 'welcome1', 'monkey12', 'dragon12', 'abc12345', '11111111',
                   '12345678a', 'password1', 'changeme', 'default1', 'user1234', 'test1234'];
        if (in_array($p, $common, true)) return true;
        // repeated patterns like aaaaaaaa, 12121212
        if (preg_match('/^(.)\1{5,}$/', $p)) return true;
        return false;
    }

    /* ------------------------------------------------------------------
     * Password reset
     * ------------------------------------------------------------------ */
    /**
     * Create a reset token for an email.
     * In production the token is emailed; in development/demo mode the token
     * is returned in the response so the flow can be tested without SMTP.
     */
    public static function requestPasswordReset($email)
    {
        $email = strtolower(trim($email));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['ok' => false, 'error' => 'Invalid email address'];
        }

        $user = self::db()->fetchOne('SELECT id FROM users WHERE email = ?', [$email]);
        if (!$user) {
            // don't reveal whether the email exists
            return ['ok' => true, 'message' => 'If that email is registered, a reset link has been sent.'];
        }

        $token = bin2hex(random_bytes(32));
        self::db()->execute(
            'INSERT INTO password_resets (user_id, token, expires_at, used)
             VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 1 HOUR), 0)',
            [$user['id'], $token]
        );

        $resetLink = (APP_URL ?: '') . '/reset.html?token=' . $token;
        $subject = 'Reset your password — ' . APP_NAME;
        $message = "Hello,\n\nWe received a request to reset your password. Click the link below:\n\n$resetLink\n\n"
                 . "This link expires in 1 hour. If you didn't request this, ignore this email.\n\n— " . APP_NAME . ' Team';
        @mail($email, $subject, $message, 'From: ' . SMTP_FROM_NAME . ' <' . SMTP_FROM_EMAIL . '>');

        if (APP_ENV === 'development') {
            return ['ok' => true, 'message' => 'Reset link generated', 'reset_token' => $token, 'reset_link' => $resetLink];
        }
        return ['ok' => true, 'message' => 'If that email is registered, a reset link has been sent.'];
    }

    /** Reset a password with a valid token */
    public static function resetPassword($token, $newPassword)
    {
        if (!is_string($token) || strlen($token) < 32) {
            return ['ok' => false, 'error' => 'Invalid reset token'];
        }
        if (strlen($newPassword) < 8) {
            return ['ok' => false, 'error' => 'Password must be at least 8 characters'];
        }
        if (self::isWeakPassword($newPassword)) {
            return ['ok' => false, 'error' => 'Password is too weak — use a longer, less common password'];
        }

        $reset = self::db()->fetchOne(
            'SELECT * FROM password_resets WHERE token = ? AND used = 0 AND expires_at > NOW()',
            [$token]
        );
        if (!$reset) {
            return ['ok' => false, 'error' => 'This reset link is invalid or has expired'];
        }

        $hash = password_hash($newPassword, PASSWORD_BCRYPT, ['cost' => HASH_COST]);
        self::db()->execute('UPDATE users SET password_hash = ? WHERE id = ?', [$hash, $reset['user_id']]);
        self::db()->execute('UPDATE password_resets SET used = 1 WHERE id = ?', [$reset['id']]);
        // invalidate all existing sessions for this user (user_sessions is the
        // source of truth for token validity, so deleting rows revokes sessions)
        try {
            self::db()->execute('DELETE FROM user_sessions WHERE user_id = ?', [$reset['user_id']]);
        } catch (Throwable $e) {
            error_log('Auth::resetPassword user_sessions cleanup failed: ' . $e->getMessage());
        }

        return ['ok' => true, 'message' => 'Password reset successfully — you can now login'];
    }

    /* ------------------------------------------------------------------
     * CSRF
     * ------------------------------------------------------------------ */
    public static function csrfToken()
    {
        if (empty($_SESSION[CSRF_TOKEN_NAME])) {
            $_SESSION[CSRF_TOKEN_NAME] = bin2hex(random_bytes(32));
        }
        return $_SESSION[CSRF_TOKEN_NAME];
    }

    public static function verifyCsrf($token)
    {
        return !empty($_SESSION[CSRF_TOKEN_NAME])
            && is_string($token)
            && hash_equals($_SESSION[CSRF_TOKEN_NAME], $token);
    }
}
