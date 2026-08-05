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
});
