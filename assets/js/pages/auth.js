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
        toast('Account created — welcome to the community!');
        setTimeout(() => (window.location.href = 'profile.html'), 600);
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
