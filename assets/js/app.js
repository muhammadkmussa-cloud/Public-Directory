/**
 * Umma Directory — frontend core
 * Static HTML/CSS/JS frontend consuming the PHP JSON API.
 * Loaded on every page (before the page-specific script).
 */
'use strict';

/* ============================================================
 * Config
 * ============================================================ */
const USE_MOCK_FALLBACK = true; // show sample data when the PHP API is unreachable (preview/demo mode)
let mockMode = false;

/* ============================================================
 * Tiny utilities
 * ============================================================ */
function esc(s) {
  return String(s ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
}

function starsHtml(rating) {
  rating = Math.max(0, Math.min(5, Number(rating) || 0));
  const full = Math.floor(rating);
  const half = rating - full >= 0.5;
  let html = '<span class="stars" aria-label="' + rating.toFixed(1) + ' out of 5 stars">';
  for (let i = 0; i < full; i++) html += '<span class="star on">★</span>';
  if (half) html += '<span class="star half">★</span>';
  for (let i = full + (half ? 1 : 0); i < 5; i++) html += '<span class="star">★</span>';
  return html + '</span>';
}

function timeAgo(iso) {
  if (!iso) return '';
  const then = new Date(iso.replace(' ', 'T') + 'Z');
  if (isNaN(then)) return iso;
  const diff = (Date.now() - then.getTime()) / 1000;
  if (diff < 60) return 'Just now';
  if (diff < 3600) return Math.floor(diff / 60) + 'm ago';
  if (diff < 86400) return Math.floor(diff / 3600) + 'h ago';
  if (diff < 604800) return Math.floor(diff / 86400) + 'd ago';
  return fmtDate(iso);
}

function fmtDate(iso) {
  if (!iso) return '';
  const d = new Date(iso.replace(' ', 'T'));
  if (isNaN(d)) return iso;
  return d.toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' });
}

function fmtMoney(amount) {
  if (amount === null || amount === undefined) return '';
  return 'KSh ' + Number(amount).toLocaleString('en-KE');
}

let toastTimer = null;
function toast(msg, type = 'success') {
  let box = document.querySelector('.toast-box');
  if (!box) {
    box = document.createElement('div');
    box.className = 'toast-box';
    document.body.appendChild(box);
  }
  const t = document.createElement('div');
  t.className = 'toast toast-' + type;
  t.textContent = msg;
  box.appendChild(t);
  setTimeout(() => t.classList.add('show'), 10);
  setTimeout(() => { t.classList.remove('show'); setTimeout(() => t.remove(), 300); }, 3800);
}

/* ============================================================
 * API client (talks to the PHP backend)
 * ============================================================ */
let csrfToken = null;
const session = { user: null };

async function api(path, opts = {}) {
  const headers = { 'X-Requested-With': 'XMLHttpRequest' };
  if (opts.method && opts.method !== 'GET') {
    headers['X-CSRF-Token'] = csrfToken || (await ensureCsrf());
    if (opts.body && !(opts.body instanceof FormData)) {
      headers['Content-Type'] = 'application/json';
      opts.body = JSON.stringify(opts.body);
    }
  }
  let res;
  try {
    res = await fetch(path, { credentials: 'same-origin', ...opts, headers: { ...headers, ...(opts.headers || {}) } });
  } catch (e) {
    // network failure → maybe demo mode
    if (USE_MOCK_FALLBACK && window.mockApi) { mockMode = true; return window.mockApi(path, opts); }
    throw new Error('Network error — is the PHP backend running?');
  }
  const ct = res.headers.get('content-type') || '';
  let data = null;
  try { data = ct.includes('application/json') ? await res.json() : null; } catch (e) { data = null; }
  if (!res.ok) {
    if (USE_MOCK_FALLBACK && window.mockApi && (!data || !data.success)) {
      // PHP not present (404/500 on a static server) → demo mode
      mockMode = true;
      return window.mockApi(path, opts);
    }
    const err = new Error((data && data.error) || 'Request failed (' + res.status + ')');
    err.status = res.status;
    throw err;
  }
  return data && typeof data === 'object' && 'data' in data ? data.data : data;
}

async function ensureCsrf() {
  if (csrfToken) return csrfToken;
  const d = await api('api/csrf.php');
  csrfToken = d.csrf_token;
  return csrfToken;
}

/* ============================================================
 * Layout (header + footer injected so pages stay DRY)
 * ============================================================ */
const HEADER_HTML = `
<nav class="navbar">
  <div class="container nav-inner">
    <a href="index.html" class="brand">
      <span class="brand-icon">🕌</span>
      <span class="brand-text">Umma <b>Directory</b></span>
    </a>
    <button class="nav-toggle" id="navToggle" aria-label="Toggle menu" aria-expanded="false">
      <span></span><span></span><span></span>
    </button>
    <div class="nav-menu" id="navMenu">
      <a class="nav-link" href="businesses.html">Businesses</a>
      <a class="nav-link" href="mosques.html">Mosques</a>
      <a class="nav-link" href="fundis.html">Fundis</a>
      <a class="btn btn-primary btn-write" href="businesses.html">✏️ Write a Review</a>
      <div class="nav-auth" id="navAuth"></div>
    </div>
  </div>
</nav>
<div class="mock-banner" id="mockBanner" hidden>
  <span>⚠️ Preview mode — showing sample data because the PHP API isn't reachable. Set up the backend (see README) for live data.</span>
</div>`;

const FOOTER_HTML = `
<footer class="footer">
  <div class="container footer-grid">
    <div class="footer-col footer-about">
      <div class="brand"><span class="brand-icon">🕌</span><span class="brand-text">Umma <b>Directory</b></span></div>
      <p>Connecting Muslim communities with trusted businesses, mosques and skilled workers.</p>
    </div>
    <div class="footer-col">
      <h4>Discover</h4>
      <a href="businesses.html">Businesses</a>
      <a href="mosques.html">Mosques</a>
      <a href="fundis.html">Fundis</a>
    </div>
    <div class="footer-col">
      <h4>Account</h4>
      <a href="login.html">Login</a>
      <a href="register.html">Create account</a>
      <a href="profile.html">Profile</a>
    </div>
    <div class="footer-col">
      <h4>Project</h4>
      <a href="index.html">Home</a>
      <a href="404.html">Report an issue</a>
    </div>
  </div>
  <div class="container footer-bottom">
    <span>© <span id="year"></span> Umma Directory — made with ❤️ for the Ummah</span>
    <span class="stack-tag">HTML · CSS · JS frontend · PHP backend</span>
  </div>
</footer>`;

function renderLayout() {
  const headerEl = document.getElementById('site-header');
  const footerEl = document.getElementById('site-footer');
  if (headerEl) {
    headerEl.innerHTML = HEADER_HTML;
    const toggle = document.getElementById('navToggle');
    const menu = document.getElementById('navMenu');
    if (toggle && menu) {
      toggle.addEventListener('click', () => {
        const open = menu.classList.toggle('open');
        toggle.setAttribute('aria-expanded', open);
      });
    }
  }
  if (footerEl) {
    footerEl.innerHTML = FOOTER_HTML;
    const y = document.getElementById('year');
    if (y) y.textContent = new Date().getFullYear();
  }
}

function renderAuthNav() {
  const el = document.getElementById('navAuth');
  if (!el) return;
  const u = session.user;
  if (u) {
    const initial = esc((u.full_name || u.username || 'U').charAt(0).toUpperCase());
    el.innerHTML = `
      <a class="nav-link user-chip" href="profile.html">
        <span class="avatar">${initial}</span> ${esc(u.full_name || u.username)}
      </a>
      <button class="btn btn-ghost" id="logoutBtn">Logout</button>`;
    const lb = document.getElementById('logoutBtn');
    if (lb) lb.addEventListener('click', async () => {
      try { await api('api/auth.php?action=logout', { method: 'POST' }); } catch (e) {}
      session.user = null;
      renderAuthNav();
      toast('Logged out');
      setTimeout(() => (window.location.href = 'index.html'), 400);
    });
  } else {
    el.innerHTML = `
      <a class="btn btn-ghost" href="login.html">Login</a>
      <a class="btn btn-primary" href="register.html">Sign up</a>`;
  }
}

async function boot() {
  renderLayout();
  try {
    session.user = await api('api/auth.php?action=me');
  } catch (e) {
    session.user = null;
  }
  renderAuthNav();
  if (mockMode) {
    const banner = document.getElementById('mockBanner');
    if (banner) banner.hidden = false;
  }
}

/* ============================================================
 * Shared card renderers
 * ============================================================ */
/* ---- Yelp-style horizontal result cards (listing pages) ---- */
function businessCard(b) {
  return `
  <article class="card result-card">
    <a class="result-media" href="business.html?id=${b.id}">
      ${b.primary_photo
        ? `<img src="${esc(b.primary_photo)}" alt="${esc(b.name)}" loading="lazy">`
        : `<div class="media-placeholder">🛍️</div>`}
      ${b.is_verified ? '<span class="badge badge-verified">✓ Verified</span>' : ''}
    </a>
    <div class="result-body">
      <h3 class="result-title"><a href="business.html?id=${b.id}">${esc(b.name)}</a></h3>
      <div class="rating-row">
        ${starsHtml(b.rating_average)}
        <span class="rating-num">${Number(b.rating_average || 0).toFixed(1)}</span>
        <span class="muted">(${b.review_count || 0})</span>
        <span class="result-meta"> · ${esc(b.price_range || '$')}${b.category_name ? ' · ' + esc(b.category_name) : ''}</span>
      </div>
      ${b.is_open ? '<span class="open-now">Open</span>' : ''}
      <p class="result-snippet">${esc(b.short_description || '')}</p>
      <div class="result-actions">
        ${b.phone ? `<a class="btn btn-outline btn-xs" href="tel:${esc(b.phone)}">📞 Call</a>` : ''}
        ${b.latitude ? `<a class="btn btn-outline btn-xs" href="https://www.google.com/maps?q=${b.latitude},${b.longitude}" target="_blank" rel="noopener">🧭 Directions</a>` : ''}
        <span class="result-loc">📍 ${esc(b.city || '')}</span>
      </div>
    </div>
  </article>`;
}

function mosqueCard(m) {
  return `
  <article class="card result-card">
    <a class="result-media" href="mosque.html?id=${m.id}">
      ${m.primary_photo
        ? `<img src="${esc(m.primary_photo)}" alt="${esc(m.name)}" loading="lazy">`
        : `<div class="media-placeholder">🕌</div>`}
      ${m.is_verified ? '<span class="badge badge-verified">✓ Verified</span>' : ''}
    </a>
    <div class="result-body">
      <h3 class="result-title"><a href="mosque.html?id=${m.id}">${esc(m.name)}</a></h3>
      <div class="rating-row">
        ${starsHtml(m.rating_average)}
        <span class="rating-num">${Number(m.rating_average || 0).toFixed(1)}</span>
        <span class="muted">(${m.review_count || 0})</span>
        <span class="result-meta"> · Mosque</span>
      </div>
      ${m.next_prayer ? `<span class="prayer-now"><span>🕌 Next: <b>${esc(m.next_prayer.name)}</b> at ${esc(m.next_prayer.time)}</span></span>` : ''}
      <p class="result-snippet">${esc(m.address || '')}</p>
      <div class="result-actions">
        ${m.phone ? `<a class="btn btn-outline btn-xs" href="tel:${esc(m.phone)}">📞 Call</a>` : ''}
        ${m.latitude ? `<a class="btn btn-outline btn-xs" href="https://www.google.com/maps?q=${m.latitude},${m.longitude}" target="_blank" rel="noopener">🧭 Directions</a>` : ''}
        <span class="result-loc">📍 ${esc(m.city || '')}</span>
      </div>
    </div>
  </article>`;
}

function fundiCard(f) {
  const skills = (f.skills || []).slice(0, 3);
  return `
  <article class="card result-card">
    <a class="result-media" href="fundi.html?id=${f.id}">
      ${f.profile_photo
        ? `<img src="${esc(f.profile_photo)}" alt="${esc(f.full_name)}" loading="lazy">`
        : `<div class="media-placeholder">🧰</div>`}
      ${f.is_verified ? '<span class="badge badge-verified">✓ Verified</span>' : ''}
    </a>
    <div class="result-body">
      <h3 class="result-title"><a href="fundi.html?id=${f.id}">${esc(f.full_name)}</a></h3>
      <div class="rating-row">
        ${starsHtml(f.rating_average)}
        <span class="rating-num">${Number(f.rating_average || 0).toFixed(1)}</span>
        <span class="muted">(${f.review_count || 0})</span>
        <span class="result-meta"> · ${esc(f.profession)}${f.years_experience ? ' · ' + f.years_experience + ' yrs' : ''}</span>
      </div>
      <div class="chips">${skills.map(s => `<span class="chip">${esc(s)}</span>`).join('')}</div>
      <p class="result-snippet">${esc(f.bio || '')}</p>
      <div class="result-actions">
        ${f.phone ? `<a class="btn btn-outline btn-xs" href="tel:${esc(f.phone)}">📞 Call</a>` : ''}
        ${f.whatsapp ? `<a class="btn btn-outline btn-xs" href="https://wa.me/${esc(f.whatsapp)}" target="_blank" rel="noopener">💬 WhatsApp</a>` : ''}
        <span class="result-loc">📍 ${esc(f.city || '')}${f.hourly_rate_min ? ' · ' + fmtMoney(f.hourly_rate_min) + '/hr' : ''}</span>
      </div>
    </div>
  </article>`;
}

/* ---- compact tiles (homepage modules, like Yelp's "popular" rows) ---- */
function businessTile(b) {
  return `
  <article class="card tile-card">
    <a class="tile-media" href="business.html?id=${b.id}">
      ${b.primary_photo
        ? `<img src="${esc(b.primary_photo)}" alt="${esc(b.name)}" loading="lazy">`
        : `<div class="media-placeholder">🛍️</div>`}
      ${b.is_verified ? '<span class="badge badge-verified">✓ Verified</span>' : ''}
    </a>
    <div class="tile-body">
      <h3 class="tile-title"><a href="business.html?id=${b.id}">${esc(b.name)}</a></h3>
      <div class="rating-row">
        ${starsHtml(b.rating_average)}
        <span class="rating-num">${Number(b.rating_average || 0).toFixed(1)}</span>
        <span class="muted">(${b.review_count || 0})</span>
      </div>
      <div class="tile-loc">📍 ${esc(b.city || '')}${b.category_name ? ' · ' + esc(b.category_name) : ''}</div>
    </div>
  </article>`;
}

function mosqueTile(m) {
  return `
  <article class="card tile-card">
    <a class="tile-media" href="mosque.html?id=${m.id}">
      ${m.primary_photo
        ? `<img src="${esc(m.primary_photo)}" alt="${esc(m.name)}" loading="lazy">`
        : `<div class="media-placeholder">🕌</div>`}
    </a>
    <div class="tile-body">
      <h3 class="tile-title"><a href="mosque.html?id=${m.id}">${esc(m.name)}</a></h3>
      <div class="rating-row">
        ${starsHtml(m.rating_average)}
        <span class="rating-num">${Number(m.rating_average || 0).toFixed(1)}</span>
        <span class="muted">(${m.review_count || 0})</span>
      </div>
      ${m.next_prayer ? `<span class="prayer-now">Next: <b>${esc(m.next_prayer.name)}</b> ${esc(m.next_prayer.time)}</span>` : ''}
      <div class="tile-loc">📍 ${esc(m.city || '')}</div>
    </div>
  </article>`;
}

function fundiTile(f) {
  return `
  <article class="card tile-card">
    <a class="tile-media" href="fundi.html?id=${f.id}">
      ${f.profile_photo
        ? `<img src="${esc(f.profile_photo)}" alt="${esc(f.full_name)}" loading="lazy">`
        : `<div class="media-placeholder">🧰</div>`}
    </a>
    <div class="tile-body">
      <h3 class="tile-title"><a href="fundi.html?id=${f.id}">${esc(f.full_name)}</a></h3>
      <div class="rating-row">
        ${starsHtml(f.rating_average)}
        <span class="rating-num">${Number(f.rating_average || 0).toFixed(1)}</span>
        <span class="muted">(${f.review_count || 0})</span>
      </div>
      <div class="tile-loc">${esc(f.profession)} · 📍 ${esc(f.city || '')}</div>
    </div>
  </article>`;
}

function reviewCard(r) {
  const u = r.user || {};
  const initial = esc((u.full_name || 'U').charAt(0).toUpperCase());
  return `
  <article class="review" data-review-id="${r.id}">
    <div class="review-head">
      <div class="reviewer">
        ${u.profile_photo
          ? `<img class="avatar" src="${esc(u.profile_photo)}" alt="">`
          : `<span class="avatar">${initial}</span>`}
        <div>
          <div class="reviewer-name">${esc(u.full_name || 'User')}</div>
          <div class="muted small">${timeAgo(r.created_at)}</div>
        </div>
      </div>
      <div class="rating-row">${starsHtml(r.rating)}</div>
    </div>
    <h4 class="review-title">${esc(r.title || '')}</h4>
    <p class="review-text">${esc(r.content || '')}</p>
    ${r.owner_response ? `<div class="owner-response"><b>Owner response:</b> ${esc(r.owner_response)}</div>` : ''}
    <div class="review-actions">
      <button class="reaction-btn helpful-btn" data-review-id="${r.id}">
        👍 Useful <span class="helpful-count">${r.helpful_count || 0}</span>
      </button>
    </div>
  </article>`;
}

function skeletonCards(n = 6) {
  let out = '';
  for (let i = 0; i < n; i++) {
    out += '<div class="card listing-card skeleton"><div class="skeleton-media"></div><div class="card-body"><div class="sk-line w60"></div><div class="sk-line w40"></div><div class="sk-line w80"></div></div></div>';
  }
  return out;
}

function paginationHtml(page, pages, baseQuery) {
  if (pages <= 1) return '';
  let html = '<div class="pagination">';
  if (page > 1) html += `<a class="btn btn-outline" href="?${baseQuery}&page=${page - 1}">← Prev</a>`;
  html += `<span class="page-info">Page ${page} of ${pages}</span>`;
  if (page < pages) html += `<a class="btn btn-outline" href="?${baseQuery}&page=${page + 1}">Next →</a>`;
  return html + '</div>';
}

function emptyState(title, sub, cta) {
  return `<div class="empty-state"><div class="empty-icon">🔍</div><h3>${esc(title)}</h3><p>${esc(sub)}</p>${cta || ''}</div>`;
}

/* ============================================================
 * Event delegation: helpful buttons (any page)
 * ============================================================ */
document.addEventListener('click', async (e) => {
  const btn = e.target.closest('.helpful-btn');
  if (!btn) return;
  const reviewId = btn.dataset.reviewId;
  if (!session.user) { toast('Please login to vote', 'error'); return; }
  try {
    const data = await api('api/reviews.php', { method: 'POST', body: { action: 'helpful', review_id: reviewId } });
    const count = btn.querySelector('.helpful-count');
    if (count) count.textContent = data.helpful_count;
    btn.disabled = true;
    btn.classList.add('voted');
    toast('Thanks for your feedback!');
  } catch (err) {
    toast(err.message || 'Could not vote', 'error');
  }
});

/* ============================================================
 * Boot
 * ============================================================ */
const appReady = new Promise(resolve => {
  document.addEventListener('DOMContentLoaded', () => {
    boot().then(() => resolve());
  });
});
window.appReady = appReady;
