<?php
/**
 * Base template — shared layout for all SSR detail pages
 * Provides: head, header, footer, CSP nonce, common scripts
 */
declare(strict_types=1);

require_once __DIR__ . '/../includes/TemplateHelpers.php';

// Generate CSP nonce for inline scripts
$cspNonce = base64_encode(random_bytes(16));
$rawMain = $rawMain ?? false;

// Page-specific variables (with defaults)
$pageTitle = $pageTitle ?? 'Ummah Directory';
$pageDescription = $pageDescription ?? 'Find halal businesses, mosques with prayer times, and trusted fundis near you.';
$pageUrl = $pageUrl ?? 'https://yourdomain.com/';
$pageImage = $pageImage ?? 'https://yourdomain.com/assets/img/logo.png';
$pageType = $pageType ?? 'website';
$jsonLd = $jsonLd ?? '';
$extraHead = $extraHead ?? '';
$bodyClass = $bodyClass ?? '';
$mainContent = $mainContent ?? '';

// Include config for APP_URL
require_once __DIR__ . '/../config/config.php';
$appUrl = APP_URL ?? 'https://yourdomain.com';
?>
<!DOCTYPE html>
<html lang="<?= e($lang ?? 'en') ?>" dir="<?= e($dir ?? 'ltr') ?>">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
<?php if (!empty($extraHead)): ?>
  <?= $extraHead ?>

<?php else: ?>
  <title><?= e($pageTitle) ?></title>
  <meta name="description" content="<?= e($pageDescription) ?>">
  
  <!-- Open Graph -->
  <meta property="og:type" content="<?= e($pageType) ?>">
  <meta property="og:site_name" content="Ummah Directory">
  <meta property="og:title" content="<?= e($pageTitle) ?>">
  <meta property="og:description" content="<?= e($pageDescription) ?>">
  <meta property="og:url" content="<?= e($pageUrl) ?>">
  <meta property="og:image" content="<?= e($pageImage) ?>">
  
  <!-- Twitter -->
  <meta name="twitter:card" content="summary_large_image">
  <meta name="twitter:title" content="<?= e($pageTitle) ?>">
  <meta name="twitter:description" content="<?= e($pageDescription) ?>">
  <meta name="twitter:image" content="<?= e($pageImage) ?>">

  <!-- JSON-LD Structured Data -->
  <?= $jsonLd ?>
<?php endif; ?>

  <link rel="icon" href="<?= e($appUrl) ?>/assets/img/logo.png" type="image/png">
  <meta name="theme-color" content="#14F528">
  <link rel="manifest" href="<?= e($appUrl) ?>/manifest.json">
  <link rel="apple-touch-icon" href="<?= e($appUrl) ?>/assets/img/logo.png">
  
  <link rel="stylesheet" href="<?= e($appUrl) ?>/assets/css/style.css">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Archivo:wght@500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin="">
</head>
<body class="<?= e($bodyClass) ?>">
  <div id="site-header"></div>

<?php if ($rawMain): ?>
  <main>
    <?= $mainContent ?>
  </main>
<?php else: ?>
  <main>
    <div class="container">
      <?= $mainContent ?>
    </div>
  </main>
<?php endif; ?>

  <div id="site-footer"></div>

  <!-- Shared modals (injected by app.js on client side) -->
  <div id="shared-modals"></div>

  <!-- Core scripts -->
  <script nonce="<?= $cspNonce ?>" src="<?= e($appUrl) ?>/assets/js/app.js"></script>
  <script nonce="<?= $cspNonce ?>" src="<?= e($appUrl) ?>/assets/js/share.js"></script>
  <script nonce="<?= $cspNonce ?>" src="<?= e($appUrl) ?>/assets/js/mock.js"></script>
  <script nonce="<?= $cspNonce ?>" src="<?= e($appUrl) ?>/assets/js/map.js"></script>
  <script nonce="<?= $cspNonce ?>" src="<?= e($appUrl) ?>/assets/js/lightbox.js"></script>
  <script nonce="<?= $cspNonce ?>" src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>
  
  <!-- Extra scripts (set by child template for page-specific dependencies) -->
  <?php foreach (($extraScripts ?? []) as $extraSrc): ?>
    <script nonce="<?= $cspNonce ?>" src="<?= e($appUrl) ?>/assets/js/<?= e($extraSrc) ?>"></script>
  <?php endforeach; ?>

  <!-- Page-specific script (set in child template) -->
  <?php if (isset($pageScript)): ?>
    <script nonce="<?= $cspNonce ?>" src="<?= e($appUrl) ?>/assets/js/pages/<?= e($pageScript) ?>"></script>
  <?php endif; ?>
  
  <!-- Service worker registration (external, CSP-safe) -->
  <script nonce="<?= $cspNonce ?>" src="<?= e($appUrl) ?>/assets/js/sw-register.js"></script>
</body>
</html>