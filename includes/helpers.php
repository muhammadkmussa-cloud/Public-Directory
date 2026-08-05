<?php
/**
 * Umma Directory - Main Bootstrap File
 * Include this in all pages to load configuration and helpers
 */

// Load configuration
require_once __DIR__ . '/../config/database.php';

// Load core classes
require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/Auth.php';

/**
 * Helper function to escape HTML output
 */
function e($string) {
    return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * Helper function to generate URL
 */
function url($path = '') {
    return APP_URL . '/' . ltrim($path, '/');
}

/**
 * Helper function to generate asset URL
 */
function asset($path = '') {
    return APP_URL . '/assets/' . ltrim($path, '/');
}

/**
 * Helper function to redirect
 */
function redirect($url) {
    header("Location: $url");
    exit;
}

/**
 * Helper function for JSON response
 */
function jsonResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

/**
 * Helper function to check AJAX request
 */
function isAjax() {
    return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
           strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
}

/**
 * Helper function to get POST data
 */
function post($key = null, $default = null) {
    if ($key === null) {
        return $_POST;
    }
    return $_POST[$key] ?? $default;
}

/**
 * Helper function to get GET data
 */
function get($key = null, $default = null) {
    if ($key === null) {
        return $_GET;
    }
    return $_GET[$key] ?? $default;
}

/**
 * Helper function to sanitize input
 */
function sanitize($input) {
    if (is_array($input)) {
        return array_map('sanitize', $input);
    }
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

/**
 * Helper function to format rating display
 */
function formatRating($rating) {
    return number_format($rating, 1);
}

/**
 * Helper function to generate star rating HTML
 */
function starRating($rating, $readonly = false) {
    $fullStars = floor($rating);
    $halfStar = $rating - $fullStars >= 0.5;
    $emptyStars = 5 - $fullStars - ($halfStar ? 1 : 0);
    
    $html = '<div class="star-rating">';
    for ($i = 0; $i < $fullStars; $i++) {
        $html .= '<span class="star full">★</span>';
    }
    if ($halfStar) {
        $html .= '<span class="star half">★</span>';
    }
    for ($i = 0; $i < $emptyStars; $i++) {
        $html .= '<span class="star empty">★</span>';
    }
    $html .= '</div>';
    
    return $html;
}

/**
 * Helper function to format distance
 */
function formatDistance($kilometers) {
    if ($kilometers < 1) {
        return round($kilometers * 1000) . ' m';
    }
    return round($kilometers, 1) . ' km';
}

/**
 * Helper function to format phone number
 */
function formatPhone($phone) {
    // Remove all non-numeric characters except +
    $cleaned = preg_replace('/[^\d+]/', '', $phone);
    
    // Format Kenyan numbers
    if (strpos($cleaned, '+254') === 0) {
        return substr($cleaned, 0, 4) . ' ' . substr($cleaned, 4, 3) . ' ' . substr($cleaned, 7, 3) . ' ' . substr($cleaned, 10);
    } elseif (strpos($cleaned, '254') === 0) {
        return '+' . substr($cleaned, 0, 3) . ' ' . substr($cleaned, 3, 3) . ' ' . substr($cleaned, 6, 3) . ' ' . substr($cleaned, 9);
    } elseif (strpos($cleaned, '0') === 0) {
        return substr($cleaned, 0, 4) . ' ' . substr($cleaned, 4, 3) . ' ' . substr($cleaned, 7, 3);
    }
    
    return $phone;
}

/**
 * Helper function to calculate distance between two coordinates (Haversine formula)
 */
function calculateDistance($lat1, $lon1, $lat2, $lon2) {
    $earthRadius = 6371; // kilometers
    
    $latDelta = deg2rad($lat2 - $lat1);
    $lonDelta = deg2rad($lon2 - $lon1);
    
    $a = sin($latDelta / 2) * sin($latDelta / 2) +
         cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
         sin($lonDelta / 2) * sin($lonDelta / 2);
    
    $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
    
    return $earthRadius * $c;
}

/**
 * Helper function to generate slug from string
 */
function createSlug($string) {
    // Convert to lowercase
    $string = strtolower($string);
    
    // Remove special characters
    $string = preg_replace('/[^a-z0-9- ]/', '', $string);
    
    // Replace spaces with hyphens
    $string = preg_replace('/[ ]+/', '-', $string);
    
    // Remove multiple hyphens
    $string = preg_replace('/-+/', '-', $string);
    
    // Trim hyphens from ends
    $string = trim($string, '-');
    
    return $string;
}

/**
 * Helper function to check if business is open now
 */
function isOpenNow($openingHours) {
    if (!$openingHours) {
        return true; // Assume open if no hours set
    }
    
    $hours = json_decode($openingHours, true);
    if (!$hours) {
        return true;
    }
    
    $dayIndex = date('N'); // 1 (Monday) to 7 (Sunday)
    $currentTime = date('H:i');
    
    $dayMap = ['1' => 'monday', '2' => 'tuesday', '3' => 'wednesday', 
               '4' => 'thursday', '5' => 'friday', '6' => 'saturday', '7' => 'sunday'];
    
    $currentDay = $dayMap[$dayIndex];
    
    if (!isset($hours[$currentDay]) || !$hours[$currentDay]['open']) {
        return false;
    }
    
    $openTime = $hours[$currentDay]['open_time'] ?? '00:00';
    $closeTime = $hours[$currentDay]['close_time'] ?? '23:59';
    
    return ($currentTime >= $openTime && $currentTime <= $closeTime);
}

/**
 * Helper function to get time until next prayer
 */
function getNextPrayerTime($prayerTimes) {
    if (!$prayerTimes) {
        return null;
    }
    
    $now = new DateTime();
    $prayers = [
        'Fajr' => $prayerTimes['fajr'],
        'Sunrise' => $prayerTimes['sunrise'],
        'Dhuhr' => $prayerTimes['dhuhr'],
        'Asr' => $prayerTimes['asr'],
        'Maghrib' => $prayerTimes['maghrib'],
        'Isha' => $prayerTimes['isha']
    ];
    
    foreach ($prayers as $name => $time) {
        $prayerDateTime = DateTime::createFromFormat('H:i:s', $time);
        if ($prayerDateTime && $prayerDateTime > $now) {
            $interval = $now->diff($prayerDateTime);
            return [
                'name' => $name,
                'time' => $time,
                'until' => $interval->format('%h:%i')
            ];
        }
    }
    
    // If all prayers passed, return Fajr for tomorrow
    return [
        'name' => 'Fajr',
        'time' => $prayers['Fajr'],
        'until' => 'Tomorrow'
    ];
}

/**
 * Helper function to truncate text
 */
function truncate($text, $length = 100, $suffix = '...') {
    if (strlen($text) <= $length) {
        return $text;
    }
    return substr($text, 0, $length) . $suffix;
}

/**
 * Helper function to format currency
 */
function formatCurrency($amount, $currency = 'KES') {
    $symbols = [
        'KES' => 'KSh ',
        'USD' => '$',
        'EUR' => '€',
        'GBP' => '£'
    ];
    
    $symbol = $symbols[$currency] ?? $currency . ' ';
    return $symbol . number_format($amount, 2);
}

/**
 * Helper function to format date
 */
function formatDate($date, $format = 'M d, Y') {
    if (empty($date)) {
        return '';
    }
    return date($format, strtotime($date));
}

/**
 * Helper function to format datetime
 */
function formatDatetime($datetime, $format = 'M d, Y h:i A') {
    if (empty($datetime)) {
        return '';
    }
    return date($format, strtotime($datetime));
}

/**
 * Helper function to get relative time ago
 */
function timeAgo($datetime) {
    $timestamp = strtotime($datetime);
    $diff = time() - $timestamp;
    
    if ($diff < 60) {
        return 'Just now';
    } elseif ($diff < 3600) {
        $minutes = floor($diff / 60);
        return $minutes . ' minute' . ($minutes > 1 ? 's' : '') . ' ago';
    } elseif ($diff < 86400) {
        $hours = floor($diff / 3600);
        return $hours . ' hour' . ($hours > 1 ? 's' : '') . ' ago';
    } elseif ($diff < 604800) {
        $days = floor($diff / 86400);
        return $days . ' day' . ($days > 1 ? 's' : '') . ' ago';
    } else {
        return formatDate($datetime, 'M d, Y');
    }
}

/**
 * Helper function to validate CSRF token
 */
function verifyCsrf($token = null) {
    $token = $token ?? post('csrf_token');
    return Auth::verifyCsrfToken($token);
}

/**
 * Helper function to get CSRF token HTML
 */
function csrfField() {
    $token = Auth::generateCsrfToken();
    return '<input type="hidden" name="csrf_token" value="' . $token . '">';
}
