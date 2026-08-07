<?php
/**
 * Password reset page — unified PHP entry point
 * Replaces reset.html with server-rendered meta tags via base.php
 */
declare(strict_types=1);

require_once __DIR__ . '/includes/TemplateHelpers.php';

$pageTitle = 'Set a new password — Ummah Directory';
$pageDescription = 'Choose a new password.';
$pageUrl = getCurrentPageUrl();
$pageImage = 'https://yourdomain.com/assets/img/logo.png';
$pageType = 'website';
$pageScript = 'auth.js';
[$lang, $dir] = getLangAndDir();
$rawMain = true;

$mainContent = '
    <div class="container auth-wrap">
      <div class="auth-card">
        <h1>Set a new password</h1>
        <p class="sub">Choose a strong password to continue.</p>
        <div id="alertBox"></div>
        <form id="resetForm">
          <div class="form-group">
            <label for="password">New password</label>
            <input type="password" id="password" name="password" required minlength="8" autocomplete="new-password" placeholder="Min 8 characters">
          </div>
          <div class="form-group">
            <label for="password2">Confirm password</label>
            <input type="password" id="password2" required autocomplete="new-password" placeholder="Re-enter password">
          </div>
          <button class="btn btn-primary btn-block" type="submit" id="resetBtn">Reset password</button>
        </form>
        <div class="auth-alt"><a href="login">Back to login</a></div>
      </div>
    </div>
';

require_once __DIR__ . '/templates/base.php';
