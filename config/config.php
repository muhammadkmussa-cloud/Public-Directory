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

// Base URL used to build absolute links (password reset, payment callbacks).
// Set APP_URL explicitly (e.g. 'https://yourdomain.com'), or leave empty to
// auto-detect from the request.
if (!defined('APP_URL')) {
    $appBase = trim((string)(getenv('APP_URL') ?: ''));
    if ($appBase === '') {
        $appHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || ($_SERVER['SERVER_PORT'] ?? '') === '443';
        $appHost  = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $appBase  = ($appHttps ? 'https' : 'http') . '://' . $appHost;
    }
    define('APP_URL', rtrim($appBase, '/'));
}
define('ITEMS_PER_PAGE', 12);

// ---- Security -------------------------------------------------------------
define('HASH_COST', 10);                    // bcrypt cost
define('SESSION_LIFETIME', 60 * 60 * 24);   // 24 hours (seconds)
define('CSRF_TOKEN_NAME', 'csrf_token');

// ---- Mail (used for the password-reset "From:" header) --------------------
define('SMTP_FROM_NAME', 'Umma Directory');          // sender display name
define('SMTP_FROM_EMAIL', 'no-reply@yourdomain.com'); // sender address

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

// PayPal currency + conversion. Donations are entered/stored in KES, but PayPal
// charges in its own supported currency, so the amount sent to PayPal is
// converted:  PayPalAmount = KES amount / PAYPAL_EXCHANGE_RATE.
// Set the live rate (1 USD = ~N KES) in production; 1.0 keeps the sandbox demo
// amounts numerically equal to the entered KES value.
define('PAYPAL_CURRENCY', 'USD');
define('PAYPAL_EXCHANGE_RATE', 1.0);

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
