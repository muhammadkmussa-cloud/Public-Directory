<?php
/**
 * Umma Directory — Application Configuration
 * Frontend: static HTML/CSS/JS  ·  Backend: PHP + MySQL JSON API
 *
 * UPDATE THE DATABASE CREDENTIALS BELOW FOR YOUR SERVER.
 */

// ---- Database -------------------------------------------------------------
define('DB_HOST', 'localhost');
define('DB_NAME', 'umma_directory');
define('DB_USER', 'your_database_user');
define('DB_PASS', 'your_database_password');
define('DB_CHARSET', 'utf8mb4');

// ---- Application ----------------------------------------------------------
define('APP_NAME', 'Umma Directory');
define('APP_ENV', 'production');            // 'development' | 'production'
define('APP_URL', '');                      // e.g. 'https://yourdomain.com' (empty = auto-detect)
define('ITEMS_PER_PAGE', 12);

// ---- Security -------------------------------------------------------------
define('HASH_COST', 10);                    // bcrypt cost
define('SESSION_LIFETIME', 60 * 60 * 24);   // 24 hours (seconds)
define('CSRF_TOKEN_NAME', 'csrf_token');

// ---- File uploads ---------------------------------------------------------
define('MAX_FILE_SIZE', 5 * 1024 * 1024);   // 5MB
define('UPLOAD_PATH', dirname(__DIR__) . '/uploads/');
define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/png', 'image/gif', 'image/webp']);

// ---- Payments (M-Pesa Daraja + PayPal) ------------------------------------
// Leave M-Pesa credentials empty to run in sandbox/simulation mode.
define('MPESA_CONSUMER_KEY', '');
define('MPESA_CONSUMER_SECRET', '');
define('MPESA_PASSKEY', '');
define('MPESA_SHORTCODE', '174379');
define('MPESA_ENV', 'sandbox');                     // 'sandbox' | 'production'
define('MPESA_CALLBACK_URL', APP_URL . '/api/donations.php?action=mpesa_callback');

define('PAYPAL_MODE', 'sandbox');                   // 'sandbox' | 'live'
define('PAYPAL_BUSINESS_EMAIL', 'your-paypal-business@example.com');

// ---- Error reporting ------------------------------------------------------
if (APP_ENV === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
    ini_set('display_startup_errors', '1');
} else {
    error_reporting(0);
    ini_set('display_errors', '0');
    ini_set('log_errors', '1');
}

date_default_timezone_set('Africa/Nairobi');
