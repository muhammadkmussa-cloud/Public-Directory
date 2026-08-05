<?php
/**
 * Umma Directory - Database Configuration
 * Yelp-Style Muslim Community Directory
 * 
 * DirectAdmin Compatible - PHP/MySQL
 */

// Database credentials - UPDATE THESE FOR YOUR SERVER
define('DB_HOST', 'localhost');
define('DB_NAME', 'umma_directory');
define('DB_USER', 'your_database_user');
define('DB_PASS', 'your_database_password');
define('DB_CHARSET', 'utf8mb4');

// Application settings
define('APP_NAME', 'Umma Directory');
define('APP_URL', 'https://yourdomain.com');
define('APP_ENV', 'production'); // development, production

// Security settings
define('HASH_COST', 10); // Password hashing cost (higher = more secure but slower)
define('SESSION_LIFETIME', 86400); // 24 hours in seconds
define('CSRF_TOKEN_NAME', 'csrf_token');

// File upload settings
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/png', 'image/gif', 'image/webp']);
define('UPLOAD_PATH', __DIR__ . '/uploads/');

// Pagination settings
define('ITEMS_PER_PAGE', 20);
define('REVIEWS_PER_PAGE', 10);

// Map settings (for location features)
define('DEFAULT_LATITUDE', -1.2921); // Nairobi coordinates
define('DEFAULT_LONGITUDE', 36.8219);
define('SEARCH_RADIUS_KM', 50);

// Prayer times API (Aladhan API)
define('PRAYER_TIMES_API', 'https://api.aladhan.com/v1/timingsByCity');

// Email settings (for DirectAdmin server)
define('SMTP_HOST', 'localhost');
define('SMTP_PORT', 25);
define('SMTP_FROM_EMAIL', 'noreply@yourdomain.com');
define('SMTP_FROM_NAME', 'Umma Directory');

// Payment settings (M-Pesa, PayPal, etc.)
define('MPESA_CONSUMER_KEY', 'your_mpesa_consumer_key');
define('MPESA_CONSUMER_SECRET', 'your_mpesa_consumer_secret');
define('MPESA_PASSKEY', 'your_mpesa_passkey');
define('MPESA_SHORTCODE', '174379');
define('MPESA_ENVIRONMENT', 'sandbox'); // sandbox, production

define('PAYPAL_CLIENT_ID', 'your_paypal_client_id');
define('PAYPAL_SECRET', 'your_paypal_secret');
define('PAYPAL_MODE', 'sandbox'); // sandbox, live

// Admin settings
define('ADMIN_EMAIL', 'admin@yourdomain.com');

// Error reporting (disable in production)
if (APP_ENV === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// Timezone
date_default_timezone_set('Africa/Nairobi');

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
