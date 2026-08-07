<?php
/**
 * Email verification page — unified PHP entry point
 * Replaces verify.html with server-rendered meta tags via base.php
 */
declare(strict_types=1);

require_once __DIR__ . '/includes/TemplateHelpers.php';

$pageTitle = 'Confirm your email — Ummah Directory';
$pageDescription = 'Confirm your email address to activate your account.';
$pageUrl = getCurrentPageUrl();
$pageImage = 'https://yourdomain.com/assets/img/logo.png';
$pageType = 'website';
$pageScript = 'auth.js';
[$lang, $dir] = getLangAndDir();
$rawMain = true;

$mainContent = '
    <div class="container auth-wrap">
      <div class="auth-card">
        <h1>Confirm your email</h1>
        <p class="sub">One more step — verify your email address to fully activate your account.</p>
        <div id="alertBox"></div>
        <div id="verifyStatus" class="verify-status" hidden>
          <div class="verify-check">✓</div>
          <h2>Email verified!</h2>
          <p class="muted">Your account is now fully active. You can write reviews, save listings and check in.</p>
          <a class="btn btn-primary btn-block" href="profile">Go to my profile</a>
        </div>
        <form id="verifyForm">
          <div class="form-group">
            <label for="verifyEmail">Email address</label>
            <input type="email" id="verifyEmail" name="email" required autocomplete="email" placeholder="you@example.com">
          </div>
          <button class="btn btn-primary btn-block" type="submit" id="verifyBtn">Resend confirmation link</button>
        </form>
        <div class="auth-alt"><a href="login">Back to login</a></div>
      </div>
    </div>
';

require_once __DIR__ . '/templates/base.php';
