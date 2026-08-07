<?php
/**
 * Business claim / For Business page — unified PHP entry point
 * Replaces business-claim.html with server-rendered meta tags via base.php
 */
declare(strict_types=1);

require_once __DIR__ . '/includes/TemplateHelpers.php';

$pageTitle = 'For Business — Ummah Directory';
$pageDescription = 'Claim your business listing on Ummah Directory, respond to reviews and grow your reach.';
$pageUrl = getCurrentPageUrl();
$pageImage = 'https://yourdomain.com/assets/img/logo.png';
$pageType = 'website';
$pageScript = 'business-claim.js';
[$lang, $dir] = getLangAndDir();
$rawMain = true;

$mainContent = '
    <div class="container" style="max-width:920px;">
      <div class="page-head">
        <h1>For Business</h1>
        <p>Claim your listing, respond to reviews and grow your presence on Ummah Directory.</p>
      </div>

      <div class="feature-grid">
        <div class="feature-card">
          <div class="feature-icon">🏪</div>
          <h3>Claim your listing</h3>
          <p>Take control of your business page so customers see accurate details you manage.</p>
        </div>
        <div class="feature-card">
          <div class="feature-icon">💬</div>
          <h3>Respond to reviews</h3>
          <p>Reply to feedback and show the community you care about their experience.</p>
        </div>
        <div class="feature-card">
          <div class="feature-icon">📈</div>
          <h3>Reach more customers</h3>
          <p>Be discovered by people searching for halal businesses, services and more near them.</p>
        </div>
      </div>

      <div class="panel" id="claimPanel">
        <h2>Claim your business</h2>
        <p class="muted small" style="margin-bottom:.8rem;">Select a listing and tell us how you\'re connected. Claims are reviewed by an admin before you get owner controls.</p>
        <div id="claimBody"><p class="muted">Loading…</p></div>
      </div>

      <div class="panel" id="ownerPanel" style="display:none;">
        <h2>Manage your listings</h2>
        <p class="muted small" style="margin-bottom:.8rem;">You already own listings on Ummah Directory. Open your dashboard to edit details and respond to reviews.</p>
        <a class="btn btn-primary" href="dashboard">Open dashboard</a>
      </div>
    </div>
';

require_once __DIR__ . '/templates/base.php';
