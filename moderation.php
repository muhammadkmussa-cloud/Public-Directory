<?php
/**
 * Moderation queue page — unified PHP entry point
 * Replaces moderation.html with server-rendered meta tags via base.php
 */
declare(strict_types=1);

require_once __DIR__ . '/includes/TemplateHelpers.php';

$pageTitle = 'Moderation — Ummah Directory';
$pageDescription = 'Review reported content.';
$pageUrl = getCurrentPageUrl();
$pageImage = 'https://yourdomain.com/assets/img/logo.png';
$pageType = 'website';
$pageScript = 'moderation.js';
[$lang, $dir] = getLangAndDir();
$rawMain = true;

$mainContent = '
    <div class="container" style="max-width:860px;">
      <div class="page-head">
        <h1>Moderation queue</h1>
        <p>Reports from the community (admin only)</p>
      </div>
      <div id="modWrap"><!-- filled by moderation.js --></div>
    </div>
';

require_once __DIR__ . '/templates/base.php';
