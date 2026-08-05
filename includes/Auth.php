<?php
/**
 * Umma Directory - Authentication Helper Functions
 * User registration, login, session management
 */

require_once __DIR__ . '/Database.php';

class Auth {
    private static $db;
    
    /**
     * Initialize database connection
     */
    private static function init() {
        if (!self::$db) {
            self::$db = Database::getInstance();
        }
    }
    
    /**
     * Register a new user
     */
    public static function register($username, $email, $password, $userType = 'regular', $extraData = []) {
        self::init();
        
        // Validate input
        if (empty($username) || empty($email) || empty($password)) {
            return ['success' => false, 'error' => 'All fields are required'];
        }
        
        // Validate email
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'error' => 'Invalid email address'];
        }
        
        // Validate username
        if (strlen($username) < 3 || strlen($username) > 50) {
            return ['success' => false, 'error' => 'Username must be 3-50 characters'];
        }
        
        // Validate password
        if (strlen($password) < 6) {
            return ['success' => false, 'error' => 'Password must be at least 6 characters'];
        }
        
        // Check if username exists
        $existing = self::$db->fetchOne("SELECT id FROM users WHERE username = ?", [$username]);
        if ($existing) {
            return ['success' => false, 'error' => 'Username already taken'];
        }
        
        // Check if email exists
        $existing = self::$db->fetchOne("SELECT id FROM users WHERE email = ?", [$email]);
        if ($existing) {
            return ['success' => false, 'error' => 'Email already registered'];
        }
        
        // Hash password
        $passwordHash = password_hash($password, PASSWORD_BCRYPT, ['cost' => HASH_COST]);
        
        // Insert user
        $sql = "INSERT INTO users (username, email, password_hash, user_type, full_name, phone) 
                VALUES (?, ?, ?, ?, ?, ?)";
        
        $userId = self::$db->insert($sql, [
            $username,
            $email,
            $passwordHash,
            $userType,
            $extraData['full_name'] ?? null,
            $extraData['phone'] ?? null
        ]);
        
        if ($userId) {
            // Create session
            self::createSession($userId);
            
            return ['success' => true, 'user_id' => $userId];
        }
        
        return ['success' => false, 'error' => 'Registration failed. Please try again.'];
    }
    
    /**
     * Login user
     */
    public static function login($identifier, $password) {
        self::init();
        
        if (empty($identifier) || empty($password)) {
            return ['success' => false, 'error' => 'Please provide email/username and password'];
        }
        
        // Determine if identifier is email or username
        $field = filter_var($identifier, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';
        
        // Get user
        $user = self::$db->fetchOne("SELECT * FROM users WHERE ($field = ? OR phone = ?) AND is_active = 1", [$identifier, $identifier]);
        
        if (!$user) {
            return ['success' => false, 'error' => 'Invalid credentials'];
        }
        
        // Verify password
        if (!password_verify($password, $user['password_hash'])) {
            return ['success' => false, 'error' => 'Invalid credentials'];
        }
        
        // Update last login
        self::$db->update("UPDATE users SET last_login = NOW() WHERE id = ?", [$user['id']]);
        
        // Create session
        self::createSession($user['id']);
        
        return ['success' => true, 'user' => $user];
    }
    
    /**
     * Logout user
     */
    public static function logout() {
        // Destroy session token in database
        if (isset($_SESSION['user_id'])) {
            self::init();
            self::$db->delete("DELETE FROM user_sessions WHERE user_id = ? AND session_token = ?", 
                [$_SESSION['user_id'], session_id()]);
        }
        
        // Destroy session
        session_destroy();
        $_SESSION = [];
        
        return true;
    }
    
    /**
     * Create user session
     */
    private static function createSession($userId) {
        self::init();
        
        $sessionToken = session_id();
        $expiresAt = date('Y-m-d H:i:s', time() + SESSION_LIFETIME);
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? null;
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;
        
        // Store session in database
        self::$db->query(
            "INSERT INTO user_sessions (user_id, session_token, ip_address, user_agent, expires_at) 
             VALUES (?, ?, ?, ?, ?)",
            [$userId, $sessionToken, $ipAddress, $userAgent, $expiresAt]
        );
        
        // Set session variables
        $_SESSION['user_id'] = $userId;
        $_SESSION['session_token'] = $sessionToken;
        $_SESSION['logged_in'] = true;
    }
    
    /**
     * Check if user is logged in
     */
    public static function isLoggedIn() {
        if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
            return false;
        }
        
        self::init();
        
        // Verify session in database
        $session = self::$db->fetchOne(
            "SELECT * FROM user_sessions 
             WHERE user_id = ? AND session_token = ? AND expires_at > NOW()",
            [$_SESSION['user_id'], session_id()]
        );
        
        if (!$session) {
            // Session expired or invalid
            self::logout();
            return false;
        }
        
        return true;
    }
    
    /**
     * Get current user
     */
    public static function getCurrentUser() {
        if (!self::isLoggedIn()) {
            return null;
        }
        
        self::init();
        return self::$db->fetchOne("SELECT * FROM users WHERE id = ?", [$_SESSION['user_id']]);
    }
    
    /**
     * Get current user ID
     */
    public static function getUserId() {
        return self::isLoggedIn() ? $_SESSION['user_id'] : null;
    }
    
    /**
     * Check if user has specific type
     */
    public static function isUserType($type) {
        $user = self::getCurrentUser();
        return $user && $user['user_type'] === $type;
    }
    
    /**
     * Check if user is admin
     */
    public static function isAdmin() {
        return self::isUserType('admin');
    }
    
    /**
     * Require login - redirect if not logged in
     */
    public static function requireLogin($redirectUrl = '/pages/auth/login.php') {
        if (!self::isLoggedIn()) {
            header("Location: $redirectUrl");
            exit;
        }
    }
    
    /**
     * Require specific user type - redirect if not authorized
     */
    public static function requireUserType($type, $redirectUrl = '/pages/unauthorized.php') {
        self::requireLogin();
        
        if (!self::isUserType($type) && !self::isAdmin()) {
            header("Location: $redirectUrl");
            exit;
        }
    }
    
    /**
     * Require admin - redirect if not admin
     */
    public static function requireAdmin($redirectUrl = '/pages/unauthorized.php') {
        self::requireLogin();
        
        if (!self::isAdmin()) {
            header("Location: $redirectUrl");
            exit;
        }
    }
    
    /**
     * Request password reset
     */
    public static function requestPasswordReset($email) {
        self::init();
        
        $user = self::$db->fetchOne("SELECT id FROM users WHERE email = ?", [$email]);
        
        if (!$user) {
            // Don't reveal if email exists
            return ['success' => true, 'message' => 'If the email exists, a reset link will be sent.'];
        }
        
        // Generate token
        $token = bin2hex(random_bytes(32));
        $expiresAt = date('Y-m-d H:i:s', time() + 3600); // 1 hour
        
        // Store token
        self::$db->query(
            "INSERT INTO password_resets (user_id, token, expires_at) VALUES (?, ?, ?)",
            [$user['id'], $token, $expiresAt]
        );
        
        // Send email (implement based on your server setup)
        $resetLink = APP_URL . "/pages/auth/reset_password.php?token=$token";
        self::sendPasswordResetEmail($email, $resetLink);
        
        return ['success' => true, 'message' => 'If the email exists, a reset link will be sent.'];
    }
    
    /**
     * Reset password with token
     */
    public static function resetPassword($token, $newPassword) {
        self::init();
        
        // Validate token
        $reset = self::$db->fetchOne(
            "SELECT * FROM password_resets 
             WHERE token = ? AND used = 0 AND expires_at > NOW()",
            [$token]
        );
        
        if (!$reset) {
            return ['success' => false, 'error' => 'Invalid or expired reset token'];
        }
        
        // Validate new password
        if (strlen($newPassword) < 6) {
            return ['success' => false, 'error' => 'Password must be at least 6 characters'];
        }
        
        // Hash new password
        $passwordHash = password_hash($newPassword, PASSWORD_BCRYPT, ['cost' => HASH_COST]);
        
        // Update password
        $updated = self::$db->update(
            "UPDATE users SET password_hash = ?, updated_at = NOW() WHERE id = ?",
            [$passwordHash, $reset['user_id']]
        );
        
        if ($updated) {
            // Mark token as used
            self::$db->update("UPDATE password_resets SET used = 1 WHERE id = ?", [$reset['id']]);
            
            // Invalidate all sessions for this user
            self::$db->delete("DELETE FROM user_sessions WHERE user_id = ?", [$reset['user_id']]);
            
            return ['success' => true, 'message' => 'Password reset successfully'];
        }
        
        return ['success' => false, 'error' => 'Password reset failed'];
    }
    
    /**
     * Send password reset email
     */
    private static function sendPasswordResetEmail($email, $resetLink) {
        $subject = "Password Reset - " . APP_NAME;
        $message = "Hello,\n\nYou requested a password reset. Click the link below to reset your password:\n\n$resetLink\n\nThis link will expire in 1 hour.\n\nIf you didn't request this, please ignore this email.\n\nRegards,\n" . APP_NAME . " Team";
        $headers = "From: " . SMTP_FROM_NAME . " <" . SMTP_FROM_EMAIL . ">\r\n";
        $headers .= "Reply-To: " . SMTP_FROM_EMAIL . "\r\n";
        $headers .= "X-Mailer: PHP/" . phpversion();
        
        mail($email, $subject, $message, $headers);
    }
    
    /**
     * Generate CSRF token
     */
    public static function generateCsrfToken() {
        if (empty($_SESSION[CSRF_TOKEN_NAME])) {
            $_SESSION[CSRF_TOKEN_NAME] = bin2hex(random_bytes(32));
        }
        return $_SESSION[CSRF_TOKEN_NAME];
    }
    
    /**
     * Verify CSRF token
     */
    public static function verifyCsrfToken($token) {
        return isset($_SESSION[CSRF_TOKEN_NAME]) && hash_equals($_SESSION[CSRF_TOKEN_NAME], $token);
    }
}
