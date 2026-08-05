/**
 * Ad manager — admin only
 * Lists ads with stats, creates new ads, toggles status, deletes.
 */
'use strict';

const PLACEMENTS = [
  [1, 'Homepage header', 'homepage_header'],
  [2, 'Homepage sidebar', 'homepage_sidebar'],
  [3, 'Search results', 'search_results'],
  [4, 'Listing page', 'listing_page'],
  [5, 'Detail page', 'detail_page'],
];

window.appReady.then(async () => {
  const wrap = document.getElementById('adminWrap');

  if (!session.user || session.user.user_type !== 'admin') {
    wrap.innerHTML = emptyState('Admins only', 'You need an admin account to manage ads.',
      '<a class="btn btn-primary btn-sm" href="login.html">Login</a>');
    return;
  }

  wrap.innerHTML = `
    <div class="panel">
      <h2>Create ad</h2>
      <form id="adForm" class="filters" style="grid-template-columns:repeat(auto-fit,minmax(170px,1fr));">
        <div class="filter">
          <label>Title</label>
          <input name="title" required placeholder="Al-Barakah Restaurant">
        </div>
        <div class="filter">
          <label>Link URL</label>
          <input name="link_url" required placeholder="business.html?id=1">
        </div>
        <div class="filter">
          <label>Placement</label>
          <select name="placement_id">
            ${PLACEMENTS.map(([id, name]) => `<option value="${id}">${name}</option>`).join('')}
          </select>
        </div>
        <div class="filter">
          <label>Image path (optional)</label>
          <input name="image_path" placeholder="assets/img/sample/restaurant-1.svg">
        </div>
        <div class="filter">
          <label>Tagline</label>
          <input name="html_content" placeholder="Short one-line pitch">
        </div>
        <div class="filter">
          <label>Priority</label>
          <input name="priority" type="number" value="0">
        </div>
        <div class="filter">
          <button class="btn btn-primary btn-block" type="submit">Create ad</button>
        </div>
      </form>
      <div id="formAlert"></div>
    </div>

    <div class="panel">
      <h2>All ads</h2>
      <div id="adList"><p class="muted">Loading…</p></div>
    </div>
  `;

  const alertBox = document.getElementById('formAlert');
  function formAlert(msg, type) {
    alertBox.innerHTML = msg ? `<div class="alert alert-${type}">${esc(msg)}</div>` : '';
  }

  /* ---- load list ---- */
  async function loadList() {
    const listEl = document.getElementById('adList');
    try {
      const ads = await api('api/ads.php?action=list');
      if (!ads.length) {
        listEl.innerHTML = '<p class="muted center" style="padding:1rem 0;">No ads yet — create your first one above.</p>';
        return;
      }
      listEl.innerHTML = ads.map(a => `
        <div class="ad-admin-row">
          ${a.image_path
            ? `<img src="${esc(a.image_path)}" alt="">`
            : `<div class="ad-admin-ph" style="background:${adColor(a.id)}">${esc((a.title || 'A').charAt(0).toUpperCase())}</div>`}
          <div class="ad-admin-info">
            <b>${esc(a.title)}</b>
            <span class="muted small">${esc(a.placement_name)} · ${esc(a.link_url)}</span>
            <span class="muted small">${esc(a.html_content || '')}</span>
          </div>
          <div class="ad-admin-stats">
            <span title="Impressions">${icon('search', 13)} ${Number(a.impressions || 0).toLocaleString()}</span>
            <span title="Clicks">${icon('nav', 13)} ${Number(a.clicks || 0).toLocaleString()}</span>
          </div>
          <span class="chip ${a.status === 'active' ? 'chip-on' : 'chip-off'}">${a.status}</span>
          <div class="ad-admin-actions">
            <button class="btn btn-outline btn-xs" data-toggle-ad="${a.id}" data-status="${a.status === 'active' ? 'paused' : 'active'}">${a.status === 'active' ? 'Pause' : 'Activate'}</button>
            <button class="btn btn-danger btn-xs" data-delete-ad="${a.id}">Delete</button>
          </div>
        </div>`).join('');
    } catch (e) {
      listEl.innerHTML = '<p class="muted">Could not load ads: ' + esc(e.message) + '</p>';
    }
  }

  /* ---- create ---- */
  document.getElementById('adForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    formAlert('', 'success');
    const fd = new FormData(e.target);
    const body = {};
    for (const [k, v] of fd.entries()) if (String(v).trim() !== '') body[k] = v;
    body.placement_id = Number(body.placement_id || 0);
    body.priority = Number(body.priority || 0);
    try {
      await api('api/ads.php?action=create', { method: 'POST', body });
      formAlert('Ad created ✓', 'success');
      e.target.reset();
      loadList();
    } catch (err) {
      formAlert(err.message || 'Could not create ad', 'error');
    }
  });

  /* ---- toggle / delete ---- */
  document.addEventListener('click', async (ev) => {
    const toggle = ev.target.closest('[data-toggle-ad]');
    if (toggle) {
      try {
        await api('api/ads.php?action=toggle', { method: 'POST', body: { ad_id: toggle.dataset.toggleAd, status: toggle.dataset.status } });
        loadList();
      } catch (err) { formAlert(err.message, 'error'); }
      return;
    }
    const del = ev.target.closest('[data-delete-ad]');
    if (del) {
      if (!confirm('Delete this ad?')) return;
      try {
        await api('api/ads.php?action=delete', { method: 'POST', body: { ad_id: del.dataset.deleteAd } });
        loadList();
      } catch (err) { formAlert(err.message, 'error'); }
    }
  });

  loadList();
});
