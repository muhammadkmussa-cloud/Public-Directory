/**
 * Profile page — requires login
 */
'use strict';

window.appReady.then(async () => {
  const wrap = document.getElementById('profileWrap');

  if (!session.user) {
    wrap.innerHTML = emptyState('Please login first', 'You need an account to view this page.',
      '<a class="btn btn-primary btn-sm" href="login">Login</a> <a class="btn btn-outline btn-sm" href="register">Sign up</a>');
    return;
  }

  const u = session.user;
  const initial = esc((u.full_name || u.username || 'U').charAt(0).toUpperCase());

  wrap.innerHTML = `
    <div class="page-head">
      <div class="profile-head">
        <div class="avatar-upload" id="avatarUpload">
          ${u.profile_photo
            ? `<img class="avatar" style="width:72px;height:72px;font-size:1.8rem;" src="${esc(u.profile_photo)}" alt="">`
            : `<span class="avatar" style="width:72px;height:72px;font-size:1.8rem;">${initial}</span>`}
          <label class="avatar-upload-label" for="avatarInput" title="Change photo">
            <svg class="ic" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 3a2.83 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5L17 3z"/></svg>
          </label>
          <input type="file" id="avatarInput" accept="image/*" style="display:none;">
        </div>
        <div>
          <h1 style="font-size:1.6rem;">${esc(u.full_name || u.username)}</h1>
          <p class="muted">@${esc(u.username)} · ${esc(u.email)}${u.phone ? ' · ' + esc(u.phone) : ''}</p>
        </div>
      </div>
      <div class="stat-grid">
        <div class="stat"><b>${u.total_reviews || 0}</b><span>Reviews</span></div>
        <div class="stat"><b>${u.total_checkins || 0}</b><span>Check-ins</span></div>
        <div class="stat"><b>${u.helpful_votes || 0}</b><span>Helpful votes</span></div>
        <div class="stat"><b>${u.contributor_level || 1}</b><span>Contributor level</span></div>
      </div>
    </div>
    <div class="panel">
      <h2>Edit Profile</h2>
      <form id="profileForm" class="form-grid">
        <div class="form-group">
          <label for="pfFullName">Full name</label>
          <input type="text" id="pfFullName" name="full_name" maxlength="100" value="${esc(u.full_name || '')}" style="width:100%;padding:.7rem .9rem;border:1.5px solid var(--border);border-radius:10px;font:inherit;outline:none;">
        </div>
        <div class="form-group">
          <label for="pfPhone">Phone</label>
          <input type="tel" id="pfPhone" name="phone" maxlength="20" placeholder="07XX XXX XXX" value="${esc(u.phone || '')}" style="width:100%;padding:.7rem .9rem;border:1.5px solid var(--border);border-radius:10px;font:inherit;outline:none;">
        </div>
        <button class="btn btn-primary btn-block" type="submit">Save changes</button>
      </form>
      <div id="profileAlert"></div>
    </div>
    <div class="panel">
      <h2>My reviews</h2>
      <div id="myReviews"><p class="muted">Loading…</p></div>
    </div>
    <div class="panel">
      <h2>Saved</h2>
      <div id="savedList"><p class="muted">Loading…</p></div>
    </div>
  `;

  // Avatar upload handler
  const avatarInput = document.getElementById('avatarInput');
  const avatarUpload = document.getElementById('avatarUpload');
  if (avatarInput && avatarUpload) {
    avatarUpload.addEventListener('click', () => avatarInput.click());
    avatarInput.addEventListener('change', async (e) => {
      const file = e.target.files[0];
      if (!file) return;
      
      const formData = new FormData();
      formData.append('image', file);
      formData.append('dir', 'avatars');
      
      const csrfToken = await (window.ensureCsrf ? window.ensureCsrf() : (await fetch('api/csrf.php').then(r => r.json()).then(d => d.csrf_token)));
      
      try {
        const res = await fetch('api/upload.php', {
          method: 'POST',
          credentials: 'same-origin',
          headers: { 'X-CSRF-Token': csrfToken },
          body: formData,
        });
        const data = await res.json();
        if (data.success && data.data?.file) {
          // Update user profile with new avatar
          await api('api/auth.php?action=update', {
            method: 'POST',
            body: { profile_photo: data.data.file },
          });
          // Reload page to show new avatar
          window.location.reload();
        } else {
          toast(data.error || 'Upload failed', 'error');
        }
      } catch (err) {
        toast(err.message || 'Could not upload avatar', 'error');
      }
    });
  }

  // Profile form submit
  document.getElementById('profileForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    const btn = e.target.querySelector('button[type="submit"]');
    const alertBox = document.getElementById('profileAlert');
    btn.disabled = true;
    alertBox.innerHTML = '';
    try {
      await api('api/auth.php?action=update', {
        method: 'POST',
        body: {
          full_name: document.getElementById('pfFullName').value.trim(),
          phone: document.getElementById('pfPhone').value.trim(),
        },
      });
      alertBox.innerHTML = '<div class="alert alert-success">Profile updated!</div>';
      // Update session user
      const fresh = await api('api/auth.php?action=me');
      session.user = fresh;
      setTimeout(() => window.location.reload(), 800);
    } catch (err) {
      alertBox.innerHTML = `<div class="alert alert-error">${esc(err.message || 'Could not update profile')}</div>`;
      btn.disabled = false;
    }
  });

  try {
    const reviews = await api('api/reviews.php?action=mine');
    const el = document.getElementById('myReviews');
    if (!reviews.length) {
      el.innerHTML = '<p class="muted center" style="padding:1.2rem 0;">You haven\'t written any reviews yet.</p>';
      return;
    }
    el.innerHTML = reviews.map(r => `
      <div class="review" style="border-bottom:1px solid var(--border);padding:.9rem 0;">
        <div style="display:flex;justify-content:space-between;gap:1rem;align-items:flex-start;">
          <div>
            <a href="${esc(r.listing_url)}" style="font-weight:700;">${esc(r.listing_name)}</a>
            <div class="muted small">${fmtDate(r.created_at)}</div>
          </div>
          ${starsHtml(r.rating)}
        </div>
        <h4 style="margin:.5rem 0 .2rem;">${esc(r.title || '')}</h4>
        <p class="review-text">${esc(r.content || '')}</p>
        <div class="muted small" style="margin-top:.4rem;">${icon('thumb', 13)} ${r.helpful_count || 0} found this useful</div>
      </div>`).join('');
  } catch (e) {
    document.getElementById('myReviews').innerHTML = '<p class="muted">Could not load your reviews.</p>';
  }

  /* ---- saved (bookmarks) ---- */
  try {
    const saved = await api('api/favorites.php?action=mine');
    const el = document.getElementById('savedList');
    if (!saved.length) {
      el.innerHTML = '<p class="muted center" style="padding:1rem 0;">Nothing saved yet — tap the bookmark on any listing.</p>';
      return;
    }
    el.innerHTML = saved.map(item => `
      <div class="saved-row">
        ${item.primary_photo
          ? `<img src="${esc(item.primary_photo)}" alt="">`
          : `<div class="saved-ph">${icon(item.type === 'business' ? 'bag' : item.type === 'mosque' ? 'mosque' : 'wrench', 22)}</div>`}
        <a href="${esc(item.url)}" class="saved-name">
          <b>${esc(item.name)}</b>
          <span class="muted small">${esc(item.type)} · ${esc(item.city || '')} · ${starsHtml(item.rating_average)} (${item.review_count || 0})</span>
        </a>
        <button class="btn btn-outline btn-xs save-btn" data-save-type="${item.type}" data-save-id="${item.id}">${icon('bookmarkFill', 14)} Saved</button>
      </div>`).join('');
  } catch (e) {
    document.getElementById('savedList').innerHTML = '<p class="muted">Could not load your saved items.</p>';
  }
});