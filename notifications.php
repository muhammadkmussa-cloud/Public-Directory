<?php
/**
 * Notifications page — unified PHP entry point
 * Replaces notifications.html with server-rendered meta tags via base.php
 */
declare(strict_types=1);

require_once __DIR__ . '/includes/TemplateHelpers.php';

$pageTitle = 'Notifications — Ummah Directory';
$pageDescription = 'Your notifications.';
$pageUrl = getCurrentPageUrl();
$pageImage = 'https://yourdomain.com/assets/img/logo.png';
$pageType = 'website';
$pageScript = 'notifications.js';
[$lang, $dir] = getLangAndDir();
$rawMain = true;

$mainContent = '
    <div class="container" style="max-width:720px;">
      <div class="page-head">
        <h1>Notifications</h1>
      </div>
      <div id="notifList"><p class="muted">Loading…</p></div>
    </div>
';

require_once __DIR__ . '/templates/base.php';
