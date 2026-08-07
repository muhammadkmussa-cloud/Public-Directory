<?php
/**
 * Owner dashboard page — unified PHP entry point
 * Replaces dashboard.html with server-rendered meta tags via base.php
 */
declare(strict_types=1);

require_once __DIR__ . '/includes/TemplateHelpers.php';

$pageTitle = 'Dashboard — Ummah Directory';
$pageDescription = 'Manage your listings, claims, review responses and stats.';
$pageUrl = getCurrentPageUrl();
$pageImage = 'https://yourdomain.com/assets/img/logo.png';
$pageType = 'website';
$pageScript = 'dashboard.js';
[$lang, $dir] = getLangAndDir();
$rawMain = true;

$mainContent = '
    <div class="container" style="max-width:960px;">
      <div class="page-head">
        <h1>Owner dashboard</h1>
        <p>Claim listings, edit your business, respond to reviews</p>
      </div>
      <div id="dashWrap"><!-- filled by dashboard.js --></div>
    </div>
';

require_once __DIR__ . '/templates/base.php';
