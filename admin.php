<?php
/**
 * Admin console page — unified PHP entry point
 * Replaces admin.html with server-rendered meta tags via base.php
 */
declare(strict_types=1);

require_once __DIR__ . '/includes/TemplateHelpers.php';

$pageTitle = 'Admin console — Ummah Directory';
$pageDescription = 'Approve listing claims and manage sponsored listings.';
$pageUrl = getCurrentPageUrl();
$pageImage = 'https://yourdomain.com/assets/img/logo.png';
$pageType = 'website';
$pageScript = 'admin.js';
[$lang, $dir] = getLangAndDir();
$rawMain = true;
$extraScripts = ['chart.js'];

$mainContent = '
    <div class="container" style="max-width:920px;">
      <div class="page-head">
        <h1>Admin console</h1>
        <p>Approve listing claims and manage sponsored listings (admin only)</p>
      </div>

      <div class="panel" id="claimsPanel" style="display:none;">
        <h2>Claim requests</h2>
        <p class="muted small" style="margin-bottom:.8rem;">Approve or reject requests to claim a listing. Approving grants the requester owner controls.</p>
        <div id="claimsWrap"><p class="muted">Loading…</p></div>
      </div>

      <div id="adminWrap"><!-- filled by admin.js --></div>
    </div>
';

require_once __DIR__ . '/templates/base.php';
