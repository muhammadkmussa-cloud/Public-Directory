<?php
/**
 * PHP Built-in Web Server Router Script
 * Handles URL rewrites for local development (mimicking Apache mod_rewrite / .htaccess).
 */

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$uri = rawurldecode($uri);
$filePath = __DIR__ . $uri;

// 1. Serve static files directly if they exist
if ($uri !== '/' && file_exists($filePath) && !is_dir($filePath)) {
    return false; // let built-in server serve the static file directly
}

// 2. Entity detail routes: /business/123-slug -> business.php?id=123
if (preg_match('#^/business/([0-9]+)(?:-[^/]+|/[^/]+)?/?$#', $uri, $m)) {
    $_GET['id'] = $m[1];
    require __DIR__ . '/business.php';
    exit;
}
if (preg_match('#^/mosque/([0-9]+)(?:-[^/]+|/[^/]+)?/?$#', $uri, $m)) {
    $_GET['id'] = $m[1];
    require __DIR__ . '/mosque.php';
    exit;
}
if (preg_match('#^/fundi/([0-9]+)(?:-[^/]+|/[^/]+)?/?$#', $uri, $m)) {
    $_GET['id'] = $m[1];
    require __DIR__ . '/fundi.php';
    exit;
}
if (preg_match('#^/charity/([0-9]+)(?:-[^/]+|/[^/]+)?/?$#', $uri, $m)) {
    $_GET['id'] = $m[1];
    require __DIR__ . '/charity.php';
    exit;
}
if (preg_match('#^/business-claim/([0-9]+)(?:-[^/]+|/[^/]+)?/?$#', $uri, $m)) {
    $_GET['id'] = $m[1];
    require __DIR__ . '/business-claim.php';
    exit;
}

// 3. Extensionless page routes: /businesses -> businesses.php
$trimmedUri = trim($uri, '/');
if (!empty($trimmedUri) && file_exists(__DIR__ . '/' . $trimmedUri . '.php')) {
    require __DIR__ . '/' . $trimmedUri . '.php';
    exit;
}

// 4. Default to index.php or 404
if ($uri === '/' || $uri === '/index') {
    require __DIR__ . '/index.php';
    exit;
}

return false;
