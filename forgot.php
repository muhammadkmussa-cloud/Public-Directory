<?php
/**
 * Forgot password page — unified PHP entry point
 * Replaces forgot.html with server-rendered meta tags via base.php
 */
declare(strict_types=1);

require_once __DIR__ . '/includes/TemplateHelpers.php';

$pageTitle = 'Forgot password — Ummah Directory';
$pageDescription = 'Reset your Ummah Directory password.';
$pageUrl = getCurrentPageUrl();
$pageImage = 'https://yourdomain.com/assets/img/logo.png';
$pageType = 'website';
$pageScript = 'auth.js';
[$lang, $dir] = getLangAndDir();
$rawMain = true;

$mainContent = '
    <div class="container auth-wrap">
      <div class="auth-card">
        <h1>Reset your password</h1>
        <p class="sub">Enter your email and we\'ll send you a reset link.</p>
        <div id="alertBox"></div>
        <form id="forgotForm">
          <div class="form-group">
            <label for="email">Email</label>
            <input type="email" id="email" name="email" required autocomplete="email" placeholder="you@example.com">
          </div>
          <button class="btn btn-primary btn-block" type="submit" id="forgotBtn">Send reset link</button>
        </form>
        <div class="auth-alt">Remembered it? <a href="login">Login</a></div>
      </div>
    </div>
';

require_once __DIR__ . '/templates/base.php';
