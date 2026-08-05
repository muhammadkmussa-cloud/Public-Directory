<?php
/**
 * Main Entry Point for Umma Directory
 * All requests are routed through this file
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Define base path
define('BASE_PATH', dirname(__DIR__));

// Load configuration
require_once BASE_PATH . '/config/database.php';
require_once BASE_PATH . '/includes/Database.php';
require_once BASE_PATH . '/includes/Auth.php';
require_once BASE_PATH . '/includes/helpers.php';
require_once BASE_PATH . '/includes/Router.php';

// Initialize router
$router = new Router();

// Define routes
$router->get('home', 'home.php');
$router->get('login', 'auth/login.php');
$router->get('register', 'auth/register.php');
$router->post('login', 'auth/login.php');
$router->post('register', 'auth/register.php');
$router->get('logout', function() {
    Auth::logout();
    header('Location: /');
    exit;
});

// Business routes
$router->get('businesses', 'businesses/index.php');
$router->get('business/{id}', 'businesses/show.php');
$router->get('business/{id}/reviews', 'businesses/reviews.php');
$router->get('business/{id}/write-review', 'businesses/write-review.php');

// Mosque routes
$router->get('mosques', 'mosques/index.php');
$router->get('mosque/{id}', 'mosques/show.php');

// Fundi routes
$router->get('fundis', 'fundis/index.php');
$router->get('fundi/{id}', 'fundis/show.php');

// Charity routes
$router->get('charities', 'charities/index.php');
$router->get('charity/{id}', 'charities/show.php');

// User routes
$router->get('profile', 'user/profile.php');
$router->get('dashboard', 'user/dashboard.php');

// API routes (for AJAX requests)
$router->post('api/reviews', 'api/reviews/create.php');
$router->post('api/checkin', 'api/checkin/create.php');

// Dispatch the request
$url = $_GET['url'] ?? '';
$method = $_SERVER['REQUEST_METHOD'];

$router->dispatch($url, $method);
