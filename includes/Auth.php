<?php
/**
 * Umma Directory — Authentication (session based)
 * Static helpers used by the JSON API.
 */

require_once __DIR__ . '/Database.php';

class Auth
{
    private static $db;

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

        self::login($username, $password); // auto-login after register
        return ['ok' => true, 'user' => self::currentUser()];
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

        $field = filter_var($identifier, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';
        $user = self::db()->fetchOne(
            "SELECT * FROM users WHERE ($field = ?) AND is_active = 1",
            [$identifier]
        );

        if (!$user || !password_verify($password, $user['password_hash'])) {
            return ['ok' => false, 'error' => 'Invalid credentials'];
        }

        session_regenerate_id(true);

        $_SESSION['user_id']  = (int)$user['id'];
        $_SESSION['logged_in'] = true;

        self::db()->execute('UPDATE users SET last_login = NOW() WHERE id = ?', [$user['id']]);

        return ['ok' => true, 'user' => self::currentUser()];
    }

    public static function logout()
    {
        $_SESSION = [];
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
        if (!self::isLoggedIn()) {
            return null;
        }
        $user = self::db()->fetchOne(
            'SELECT id, username, email, full_name, phone, profile_photo, user_type,
                    is_verified, verification_badge, contributor_level, total_reviews,
                    total_checkins, total_photos, helpful_votes, created_at, last_login
             FROM users WHERE id = ?',
            [$_SESSION['user_id']]
        );
        return $user ?: null;
    }

    /** Convenience aliases (match the names used by the old frontend) */
    public static function check() { return self::isLoggedIn(); }
    public static function user()   { return self::currentUser(); }

    public static function isAdmin()
    {
        $u = self::currentUser();
        return $u && $u['user_type'] === 'admin';
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
        // invalidate all existing sessions for this user
        self::db()->execute('DELETE FROM user_sessions WHERE user_id = ?', [$reset['user_id']]);

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
