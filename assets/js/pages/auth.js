/**
 * Login + register pages
 */
'use strict';

function showAlert(msg, type) {
  const box = document.getElementById('alertBox');
  if (!box) return;
  box.innerHTML = `<div class="alert alert-${type}">${esc(msg)}</div>`;
}

window.appReady.then(() => {

  /* ---- Google OAuth (login + register) ---- */
  const googleBtn = document.getElementById('googleBtn');
  if (googleBtn) {
    googleBtn.addEventListener('click', async () => {
      try {
        // Dev/demo mode (or static preview): the API answers with a simulated
        // link that we complete client-side. Production answers with a
        // Location: header to Google, which fetch can't follow cross-origin —
        // we fall through to a full-page redirect below.
        const res = await api('api/oauth.php?action=login&provider=google');
        if (res && res.login_url) {
          const done = await api(res.login_url);
          if (done && done.user) {
            session.user = done.user;
            renderAuthNav();
            toast('Signed in with Google');
            setTimeout(() => (window.location.href = done.redirect || 'index.html'), 500);
            return;
          }
        }
      } catch (e) {
        // API-level errors (e.g. Google sign-in not configured) have a status;
        // surface them instead of bouncing into a JSON page.
        if (e && e.status) {
          showAlert(e.message || 'Google sign-in is not available', 'error');
          return;
        }
        // network/CSP failure (or a redirect we couldn't follow) → real redirect
      }
      window.location.href = 'api/oauth.php?action=login&provider=google';
    });
  }

  /* ---- login ---- */
  const loginForm = document.getElementById('loginForm');
  if (loginForm) {
    if (session.user) { window.location.href = 'profile.html'; return; }
    loginForm.addEventListener('submit', async (e) => {
      e.preventDefault();
      const btn = document.getElementById('loginBtn');
      btn.disabled = true; btn.textContent = 'Logging in…';
      showAlert('', 'success');
      try {
        const user = await api('api/auth.php?action=login', {
          method: 'POST',
          body: { identifier: document.getElementById('identifier').value.trim(), password: document.getElementById('password').value },
        });
        session.user = user;
        renderAuthNav();
        if (user.email_verified === false) {
          showAlert('Your email is not verified yet. <a href="verify.html" style="color:inherit;text-decoration:underline;">Confirm your email</a> to unlock everything.', 'error');
          btn.disabled = false; btn.textContent = 'Login';
          return;
        }
        toast('Welcome back, ' + (user.full_name || user.username) + '!');
        setTimeout(() => (window.location.href = 'index.html'), 500);
      } catch (err) {
        showAlert(err.message || 'Login failed', 'error');
        btn.disabled = false; btn.textContent = 'Login';
      }
    });
  }

  /* ---- register ---- */
  const regForm = document.getElementById('registerForm');
  if (regForm) {
    regForm.addEventListener('submit', async (e) => {
      e.preventDefault();
      const btn = document.getElementById('registerBtn');
      btn.disabled = true; btn.textContent = 'Creating account…';
      showAlert('', 'success');
      const pass = document.getElementById('password').value;
      if (pass.length < 8) {
        showAlert('Password must be at least 8 characters', 'error');
        btn.disabled = false; btn.textContent = 'Create account';
        return;
      }
      try {
        const user = await api('api/auth.php?action=register', {
          method: 'POST',
          body: {
            username: document.getElementById('username').value.trim(),
            email: document.getElementById('email').value.trim(),
            password: pass,
            full_name: document.getElementById('full_name').value.trim(),
            phone: document.getElementById('phone').value.trim(),
          },
        });
        session.user = user;
        renderAuthNav();
        // demo/development mode returns a working link directly
        if (user.verification_link) {
          const safe = esc(user.verification_link);
          const box = document.getElementById('alertBox');
          if (box) box.innerHTML = '<div class="alert alert-success">Account created! Confirm your email (demo link): <a href="' + safe + '" style="color:inherit;text-decoration:underline;">' + safe + '</a></div>';
        } else {
          showAlert('Account created! Check your inbox to confirm your email before your account is fully active.', 'success');
        }
        btn.disabled = false; btn.textContent = 'Create account';
        document.getElementById('registerForm').reset();
      } catch (err) {
        showAlert(err.message || 'Registration failed', 'error');
        btn.disabled = false; btn.textContent = 'Create account';
      }
    });
  }

  /* ---- forgot password ---- */
  const forgotForm = document.getElementById('forgotForm');
  if (forgotForm) {
    forgotForm.addEventListener('submit', async (e) => {
      e.preventDefault();
      const btn = document.getElementById('forgotBtn');
      btn.disabled = true; btn.textContent = 'Sending…';
      showAlert('', 'success');
      try {
        const res = await api('api/auth.php?action=forgot', {
          method: 'POST',
          body: { email: document.getElementById('email').value.trim() },
        });
        // in demo/development mode the reset link is returned so it can be tested
        if (res.reset_link) {
          const safe = esc(res.reset_link);
          const box = document.getElementById('alertBox');
          if (box) box.innerHTML = '<div class="alert alert-success">Reset link generated (demo mode): <a href="' + safe + '">' + safe + '</a></div>';
        } else {
          showAlert(res.message || 'If that email is registered, a reset link has been sent.', 'success');
        }
        btn.disabled = false; btn.textContent = 'Send reset link';
      } catch (err) {
        showAlert(err.message || 'Something went wrong', 'error');
        btn.disabled = false; btn.textContent = 'Send reset link';
      }
    });
  }

  /* ---- verify email (verify.html) ---- */
  const verifyStatus = document.getElementById('verifyStatus');
  const verifyForm = document.getElementById('verifyForm');
  if (verifyForm) {
    const params = new URLSearchParams(window.location.search);
    const token = params.get('token');

    // Auto-verify when arriving with a token from the confirmation email
    if (token) {
      verifyForm.hidden = true;
      (async () => {
        try {
          await api('api/auth.php?action=verify', { method: 'POST', body: { token } });
          if (session.user) { session.user.email_verified = true; }
          if (verifyStatus) {
            verifyStatus.hidden = false;
            verifyForm.hidden = true;
            const link = verifyStatus.querySelector('a.btn');
            if (link) link.href = session.user ? 'profile.html' : 'login.html';
          }
        } catch (err) {
          verifyForm.hidden = false;
          showAlert(err.message || 'Could not verify your email', 'error');
        }
      })();
    }

    verifyForm.addEventListener('submit', async (e) => {
      e.preventDefault();
      const btn = document.getElementById('verifyBtn');
      btn.disabled = true; btn.textContent = 'Sending…';
      showAlert('', 'success');
      try {
        const res = await api('api/auth.php?action=resend_verification', {
          method: 'POST',
          body: { email: document.getElementById('verifyEmail').value.trim() },
        });
        if (res.verification_link) {
          const safe = esc(res.verification_link);
          const box = document.getElementById('alertBox');
          if (box) box.innerHTML = '<div class="alert alert-success">Confirmation link (demo mode): <a href="' + safe + '" style="color:inherit;text-decoration:underline;">' + safe + '</a></div>';
        } else {
          showAlert(res.message || 'If that account exists and is unverified, a new confirmation link has been sent.', 'success');
        }
        btn.disabled = false; btn.textContent = 'Resend confirmation link';
      } catch (err) {
        showAlert(err.message || 'Could not send the link', 'error');
        btn.disabled = false; btn.textContent = 'Resend confirmation link';
      }
    });
  }

  /* ---- reset password ---- */
  const resetForm = document.getElementById('resetForm');
  if (resetForm) {
    const token = new URLSearchParams(window.location.search).get('token');
    resetForm.addEventListener('submit', async (e) => {
      e.preventDefault();
      const pass = document.getElementById('password').value;
      const pass2 = document.getElementById('password2').value;
      if (pass.length < 8) { showAlert('Password must be at least 8 characters', 'error'); return; }
      if (pass !== pass2) { showAlert('Passwords do not match', 'error'); return; }
      const btn = document.getElementById('resetBtn');
      btn.disabled = true; btn.textContent = 'Resetting…';
      showAlert('', 'success');
      try {
        const res = await api('api/auth.php?action=reset', {
          method: 'POST',
          body: { token: token || '', password: pass },
        });
        showAlert(res.message || 'Password reset successfully', 'success');
        setTimeout(() => (window.location.href = 'login.html'), 1500);
      } catch (err) {
        showAlert(err.message || 'Could not reset password', 'error');
        btn.disabled = false; btn.textContent = 'Reset password';
      }
    });
  }
});
