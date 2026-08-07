<?php
/**
 * 404 page — uses base template for consistent layout
 */
declare(strict_types=1);

require_once __DIR__ . '/includes/TemplateHelpers.php';

$pageTitle = 'Page not found — Ummah Directory';
$pageDescription = 'The page you\'re looking for doesn\'t exist or has been moved.';
$pageUrl = getCurrentPageUrl();
$pageImage = 'https://yourdomain.com/assets/img/logo.png';
$pageType = 'website';
$pageScript = '';
[$lang, $dir] = getLangAndDir();

$mainContent = '
  <div class="panel center" style="max-width:400px;margin:4rem auto;padding:3rem 2rem;">
    <div style="font-size:6rem;line-height:1;margin-bottom:1rem;">404</div>
    <h1 style="font-size:1.8rem;margin-bottom:.8rem;">Page not found</h1>
    <p class="muted" style="margin-bottom:2rem;">The page you\'re looking for doesn\'t exist or has been moved.</p>
    <a class="btn btn-primary" href="/">← Back to homepage</a>
  </div>
';

require_once __DIR__ . '/templates/base.php';