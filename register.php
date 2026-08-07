<?php
/**
 * Registration page — unified PHP entry point
 * Replaces register.html with server-rendered meta tags via base.php
 */
declare(strict_types=1);

require_once __DIR__ . '/includes/TemplateHelpers.php';

$pageTitle = 'Create account — Ummah Directory';
$pageDescription = 'Join Ummah Directory for free.';
$pageUrl = getCurrentPageUrl();
$pageImage = 'https://yourdomain.com/assets/img/logo.png';
$pageType = 'website';
$pageScript = 'auth.js';
[$lang, $dir] = getLangAndDir();
$rawMain = true;

$mainContent = '
    <div class="container auth-wrap">
      <div class="auth-card">
        <h1>Join the community</h1>
        <p class="sub">Write reviews, check in, and help others find the best.</p>
        <div id="alertBox"></div>
        <button class="btn btn-google btn-block" type="button" id="googleBtn">
          <svg class="g-logo" viewBox="0 0 48 48" width="18" height="18" aria-hidden="true"><path fill="#FFC107" d="M43.6 20.1H42V20H24v8h11.3C33.7 32.7 29.2 36 24 36c-6.6 0-12-5.4-12-12s5.4-12 12-12c3.1 0 5.9 1.2 8 3l5.7-5.7C34.4 6.1 29.5 4 24 4 13 4 4 13 4 24s9 20 20 20 20-9 20-20c0-1.3-.1-2.6-.4-3.9z"/><path fill="#FF3D00" d="M6.3 14.7l6.6 4.8C14.7 15.1 19 12 24 12c3.1 0 5.9 1.2 8 3l5.7-5.7C34.4 6.1 29.5 4 24 4 16.3 4 9.7 8.3 6.3 14.7z"/><path fill="#4CAF50" d="M24 44c5.2 0 9.9-2 13.4-5.2l-6.2-5.2C29.2 35.1 26.7 36 24 36c-5.2 0-9.6-3.3-11.3-8l-6.5 5C9.5 39.6 16.2 44 24 44z"/><path fill="#1976D2" d="M43.6 20.1H42V20H24v8h11.3c-.8 2.3-2.3 4.3-4.1 5.7l6.2 5.2C37.5 39.6 44 35 44 24c0-1.3-.1-2.6-.4-3.9z"/></svg>
          Continue with Google
        </button>
        <div class="auth-divider"><span>or</span></div>
        <form id="registerForm">
          <div class="form-group">
            <label for="full_name">Full name</label>
            <input type="text" id="full_name" name="full_name" required autocomplete="name" placeholder="Amina Hassan">
          </div>
          <div class="form-group">
            <label for="username">Username</label>
            <input type="text" id="username" name="username" required minlength="3" maxlength="50" autocomplete="username" placeholder="amina_hassan">
            <div class="hint">3–50 characters</div>
          </div>
          <div class="form-group">
            <label for="email">Email</label>
            <input type="email" id="email" name="email" required autocomplete="email" placeholder="you@example.com">
          </div>
          <div class="form-row">
            <div class="form-group">
              <label for="password">Password</label>
              <input type="password" id="password" name="password" required minlength="8" autocomplete="new-password" placeholder="Min 8 characters">
            </div>
            <div class="form-group">
              <label for="phone">Phone (optional)</label>
              <input type="tel" id="phone" name="phone" autocomplete="tel" placeholder="+254 7XX XXX XXX">
            </div>
          </div>
          <button class="btn btn-primary btn-block" type="submit" id="registerBtn">Create account</button>
        </form>
        <div class="auth-alt">Already have an account? <a href="login">Login</a></div>
      </div>
    </div>
';

require_once __DIR__ . '/templates/base.php';
