<?php
if (!isset($pageTitle)) $pageTitle = '';
if (!isset($user)) {
    require_once __DIR__ . '/../../includes/Auth.php';
    $auth = new Auth();
    $user = $auth->getCurrentUser();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($pageTitle) ? htmlspecialchars($pageTitle) . ' | ' : '' ?>Umma Directory - Discover Muslim-Friendly Businesses</title>
    <meta name="description" content="Find trusted businesses, mosques, fundis, and charities in your community. Read reviews, view photos, and connect with local Muslim services.">
    
    <!-- Stylesheets -->
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="/assets/images/favicon.ico">
    
    <!-- PWA Support -->
    <meta name="theme-color" content="#10b981">
    <link rel="manifest" href="/manifest.json">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar">
        <div class="container">
            <div class="navbar-brand">
                <a href="/" class="logo">
                    <span class="logo-icon">🕌</span>
                    <span class="logo-text">Umma Directory</span>
                </a>
            </div>
            
            <button class="mobile-menu-toggle" aria-label="Toggle menu">
                <span></span>
                <span></span>
                <span></span>
            </button>
            
            <div class="navbar-menu">
                <ul class="nav-links">
                    <li><a href="/businesses">Businesses</a></li>
                    <li><a href="/mosques">Mosques</a></li>
                    <li><a href="/fundis">Fundis</a></li>
                    <li><a href="/charities">Charities</a></li>
                </ul>
                
                <div class="nav-actions">
                    <?php if ($user): ?>
                        <div class="user-menu">
                            <a href="/profile" class="btn btn-outline">
                                <?= htmlspecialchars($user['name']) ?>
                            </a>
                            <a href="/logout" class="btn btn-text">Logout</a>
                        </div>
                    <?php else: ?>
                        <a href="/login" class="btn btn-outline">Login</a>
                        <a href="/register" class="btn btn-primary">Sign Up</a>
                    <?php endif; ?>
                    
                    <a href="/businesses" class="btn btn-primary btn-write-review">
                        Write a Review
                    </a>
                </div>
            </div>
        </div>
    </nav>
    
    <!-- Main Content -->
    <main>
