<?php
/**
 * User profile page — unified PHP entry point
 * Replaces profile.html with server-rendered meta tags via base.php
 */
declare(strict_types=1);

require_once __DIR__ . '/includes/TemplateHelpers.php';

$pageTitle = 'My profile — Ummah Directory';
$pageDescription = 'Your Ummah Directory profile and reviews.';
$pageUrl = getCurrentPageUrl();
$pageImage = 'https://yourdomain.com/assets/img/logo.png';
$pageType = 'website';
$pageScript = 'profile.js';
[$lang, $dir] = getLangAndDir();
$rawMain = true;

$mainContent = '
    <div class="container" id="profileWrap" style="max-width:820px;">
      <!-- filled by profile.js -->
    </div>
';

require_once __DIR__ . '/templates/base.php';
