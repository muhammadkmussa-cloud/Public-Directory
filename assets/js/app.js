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
 * SVG icon system (Yelp-style line icons)
 * ============================================================ */
const ICONS = {
  spark: '<path fill="currentColor" stroke="none" d="M12 1.8c1 6 4.2 9.2 10.2 10.2-6 1-9.2 4.2-10.2 10.2C11 16.2 7.8 13 1.8 12 7.8 11 11 7.8 12 1.8z"/>',
  search: '<circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>',
  phone: '<path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6A19.79 19.79 0 0 1 2.08 4.18 2 2 0 0 1 4.06 2h3a2 2 0 0 1 2 1.72c.13.96.36 1.9.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.91.34 1.85.57 2.81.7A2 2 0 0 1 22 16.92z"/>',
  pin: '<path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/>',
  nav: '<polygon points="3 11 22 2 13 21 11 13 3 11"/>',
  globe: '<circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/>',
  wa: '<path d="M21 11.5a8.5 8.5 0 0 1-8.5 8.5c-1.4 0-2.7-.3-3.9-.9L3 21l1.9-5.1A8.5 8.5 0 1 1 21 11.5z"/><path d="M9 9.5c.3 2 1.5 3.2 3.5 3.5l1-1 1.5 1v1.5c0 .6-.5 1-1.1 1A7 7 0 0 1 8 10.1c0-.6.4-1.1 1-1.1h1.5l1 1z"/>',
  clock: '<circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>',
  pen: '<path d="M17 3a2.83 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5L17 3z"/>',
  thumb: '<path d="M14 9V5a3 3 0 0 0-3-3l-4 9v11h11.28a2 2 0 0 0 2-1.61l1.38-7a2 2 0 0 0-2-2.39H14z"/><path d="M7 21H4a1 1 0 0 1-1-1v-8a1 1 0 0 1 1-1h3"/>',
  smile: '<circle cx="12" cy="12" r="9.5"/><path d="M8 14.5s1.5 2.3 4 2.3 4-2.3 4-2.3"/><line x1="9" y1="9.5" x2="9.01" y2="9.5"/><line x1="15" y1="9.5" x2="15.01" y2="9.5"/>',
  cool: '<circle cx="12" cy="12" r="9.5"/><path d="M8 14.5s1.5 2 4 2 4-2 4-2"/><rect x="6.8" y="9" width="3.2" height="1.6" rx="0.4"/><rect x="14" y="9" width="3.2" height="1.6" rx="0.4"/><line x1="10" y1="9.8" x2="14" y2="9.8"/>',
  mosque: '<path d="M3 21h18"/><path d="M5 21v-5a7 7 0 0 1 14 0v5"/><path d="M12 9.5V7.5"/><path d="M9.6 7.5h4.8L12 3.8l-2.4 3.7z"/>',
  wrench: '<path d="M14.7 6.3a4.5 4.5 0 0 0-5.9 5.9L3 18l3 3 5.8-5.8a4.5 4.5 0 0 0 5.9-5.9L14 13.5 10.5 10l4.2-3.7z"/>',
  bag: '<path d="M6 8h12l-1.2 12.2a1 1 0 0 1-1 .8H8.2a1 1 0 0 1-1-.8L6 8z"/><path d="M9 8V6a3 3 0 0 1 6 0v2"/>',
  restaurant: '<path d="M6 2v6a2 2 0 0 0 4 0V2"/><path d="M8 8v14"/><path d="M17 2c-1.5 2.2-2 5-2 8 0 2.2 1 4 2 4.5V22"/>',
  book: '<path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/>',
  heart: '<path d="M20.8 4.6a5.5 5.5 0 0 0-7.8 0L12 5.6l-1-1a5.5 5.5 0 0 0-7.8 7.8l1 1L12 21.2l7.8-7.8 1-1a5.5 5.5 0 0 0 0-7.8z"/>',
  briefcase: '<rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/>',
  car: '<path d="M3 13l1.5-5A2 2 0 0 1 6.4 6.5h11.2a2 2 0 0 1 1.9 1.5L21 13"/><path d="M3 13h18v5a1 1 0 0 1-1 1h-1a1 1 0 0 1-1-1v-1H6v1a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1v-5z"/><circle cx="7.5" cy="16.5" r="1.2"/><circle cx="16.5" cy="16.5" r="1.2"/>',
  grid: '<circle cx="5" cy="5" r="1.6"/><circle cx="12" cy="5" r="1.6"/><circle cx="19" cy="5" r="1.6"/><circle cx="5" cy="12" r="1.6"/><circle cx="12" cy="12" r="1.6"/><circle cx="19" cy="12" r="1.6"/><circle cx="5" cy="19" r="1.6"/><circle cx="12" cy="19" r="1.6"/><circle cx="19" cy="19" r="1.6"/>',
};

const CATEGORY_ICONS = {
  restaurant: 'restaurant', 'shopping-bag': 'bag', services: 'briefcase',
  briefcase: 'briefcase', heart: 'heart', book: 'book', car: 'car', home: 'grid',
  wrench: 'wrench', mosque: 'mosque', kaaba: 'grid', child: 'grid', hospital: 'grid',
  'life-ring': 'grid', wheelchair: 'grid', wifi: 'grid', parking: 'grid',
};

function icon(name, size = 16) {
  const key = CATEGORY_ICONS[name] || name || 'grid';
  const p = ICONS[key] || ICONS.grid;
  return `<svg class="ic" width="${size}" height="${size}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">${p}</svg>`;
}

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
function wordmark() {
  return `
  <a href="index.html" class="brand" aria-label="Umma Directory — home">
    <svg class="brand-burst" width="26" height="26" viewBox="0 0 24 24" aria-hidden="true">${ICONS.spark}</svg>
    <span class="brand-text">umma<em>!</em></span>
  </a>`;
}

const HEADER_HTML = `
<nav class="navbar">
  <div class="container nav-inner">
    ${wordmark()}
    <button class="nav-toggle" id="navToggle" aria-label="Toggle menu" aria-expanded="false">
      <span></span><span></span><span></span>
    </button>
    <div class="nav-menu" id="navMenu">
      <a class="nav-link" href="businesses.html">Businesses</a>
      <a class="nav-link" href="mosques.html">Mosques</a>
      <a class="nav-link" href="fundis.html">Fundis</a>
      <a class="btn btn-primary btn-write" href="businesses.html">${icon('pen', 14)} Write a Review</a>
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
      <a href="index.html" class="brand" aria-label="Umma Directory — home">
        <svg class="brand-burst" width="24" height="24" viewBox="0 0 24 24" aria-hidden="true">${ICONS.spark}</svg>
        <span class="brand-text">umma<em>!</em></span>
      </a>
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
        : `<div class="media-placeholder">${icon('bag', 34)}</div>`}
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
        ${b.phone ? `<a class="btn btn-outline btn-xs" href="tel:${esc(b.phone)}">${icon('phone', 13)} Call</a>` : ''}
        ${b.latitude ? `<a class="btn btn-outline btn-xs" href="https://www.google.com/maps?q=${b.latitude},${b.longitude}" target="_blank" rel="noopener">${icon('nav', 13)} Directions</a>` : ''}
        <span class="result-loc">${icon('pin', 12)} ${esc(b.city || '')}</span>
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
        : `<div class="media-placeholder">${icon('mosque', 34)}</div>`}
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
      ${m.next_prayer ? `<span class="prayer-now"><span>${icon('clock', 13)} Next: <b>${esc(m.next_prayer.name)}</b> at ${esc(m.next_prayer.time)}</span></span>` : ''}
      <p class="result-snippet">${esc(m.address || '')}</p>
      <div class="result-actions">
        ${m.phone ? `<a class="btn btn-outline btn-xs" href="tel:${esc(m.phone)}">${icon('phone', 13)} Call</a>` : ''}
        ${m.latitude ? `<a class="btn btn-outline btn-xs" href="https://www.google.com/maps?q=${m.latitude},${m.longitude}" target="_blank" rel="noopener">${icon('nav', 13)} Directions</a>` : ''}
        <span class="result-loc">${icon('pin', 12)} ${esc(m.city || '')}</span>
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
        : `<div class="media-placeholder">${icon('wrench', 34)}</div>`}
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
        ${f.phone ? `<a class="btn btn-outline btn-xs" href="tel:${esc(f.phone)}">${icon('phone', 13)} Call</a>` : ''}
        ${f.whatsapp ? `<a class="btn btn-outline btn-xs" href="https://wa.me/${esc(f.whatsapp)}" target="_blank" rel="noopener">${icon('wa', 13)} WhatsApp</a>` : ''}
        <span class="result-loc">${icon('pin', 12)} ${esc(f.city || '')}${f.hourly_rate_min ? ' · ' + fmtMoney(f.hourly_rate_min) + '/hr' : ''}</span>
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
        : `<div class="media-placeholder">${icon('bag', 34)}</div>`}
      ${b.is_verified ? '<span class="badge badge-verified">✓ Verified</span>' : ''}
    </a>
    <div class="tile-body">
      <h3 class="tile-title"><a href="business.html?id=${b.id}">${esc(b.name)}</a></h3>
      <div class="rating-row">
        ${starsHtml(b.rating_average)}
        <span class="rating-num">${Number(b.rating_average || 0).toFixed(1)}</span>
        <span class="muted">(${b.review_count || 0})</span>
      </div>
      <div class="tile-loc">${icon('pin', 12)} ${esc(b.city || '')}${b.category_name ? ' · ' + esc(b.category_name) : ''}</div>
    </div>
  </article>`;
}

function mosqueTile(m) {
  return `
  <article class="card tile-card">
    <a class="tile-media" href="mosque.html?id=${m.id}">
      ${m.primary_photo
        ? `<img src="${esc(m.primary_photo)}" alt="${esc(m.name)}" loading="lazy">`
        : `<div class="media-placeholder">${icon('mosque', 34)}</div>`}
    </a>
    <div class="tile-body">
      <h3 class="tile-title"><a href="mosque.html?id=${m.id}">${esc(m.name)}</a></h3>
      <div class="rating-row">
        ${starsHtml(m.rating_average)}
        <span class="rating-num">${Number(m.rating_average || 0).toFixed(1)}</span>
        <span class="muted">(${m.review_count || 0})</span>
      </div>
      ${m.next_prayer ? `<span class="prayer-now">${icon('clock', 12)} Next: <b>${esc(m.next_prayer.name)}</b> ${esc(m.next_prayer.time)}</span>` : ''}
      <div class="tile-loc">${icon('pin', 12)} ${esc(m.city || '')}</div>
    </div>
  </article>`;
}

function fundiTile(f) {
  return `
  <article class="card tile-card">
    <a class="tile-media" href="fundi.html?id=${f.id}">
      ${f.profile_photo
        ? `<img src="${esc(f.profile_photo)}" alt="${esc(f.full_name)}" loading="lazy">`
        : `<div class="media-placeholder">${icon('wrench', 34)}</div>`}
    </a>
    <div class="tile-body">
      <h3 class="tile-title"><a href="fundi.html?id=${f.id}">${esc(f.full_name)}</a></h3>
      <div class="rating-row">
        ${starsHtml(f.rating_average)}
        <span class="rating-num">${Number(f.rating_average || 0).toFixed(1)}</span>
        <span class="muted">(${f.review_count || 0})</span>
      </div>
      <div class="tile-loc">${esc(f.profession)} · ${icon('pin', 12)} ${esc(f.city || '')}</div>
    </div>
  </article>`;
}

const REACTIONS = [
  ['useful', 'thumb', 'Useful'],
  ['funny', 'smile', 'Funny'],
  ['cool', 'cool', 'Cool'],
];

function reviewCard(r) {
  const u = r.user || {};
  const initial = esc((u.full_name || 'U').charAt(0).toUpperCase());
  const reactions = REACTIONS.map(([type, ic, label]) => {
    const count = r[type + '_count'] ?? (type === 'useful' ? (r.helpful_count || 0) : 0);
    return `<button class="reaction-btn react-btn" data-review-id="${r.id}" data-react="${type}">${icon(ic, 14)} <span class="react-label">${label}</span> <span class="react-count">${count}</span></button>`;
  }).join('');
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
    <div class="review-actions">${reactions}</div>
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
  return `<div class="empty-state"><div class="empty-icon">${icon('search', 36)}</div><h3>${esc(title)}</h3><p>${esc(sub)}</p>${cta || ''}</div>`;
}

/* ============================================================
 * Event delegation: review reactions (Useful / Funny / Cool)
 * ============================================================ */
document.addEventListener('click', async (e) => {
  const btn = e.target.closest('.react-btn');
  if (!btn) return;
  const reviewId = btn.dataset.reviewId;
  const reaction = btn.dataset.react;
  if (!session.user) { toast('Please login to vote', 'error'); return; }
  try {
    const data = await api('api/reviews.php', { method: 'POST', body: { action: 'react', review_id: reviewId, reaction } });
    const card = btn.closest('.review');
    if (card) {
      card.querySelectorAll('.react-btn').forEach(b => {
        const t = b.dataset.react;
        const count = b.querySelector('.react-count');
        if (count && data.counts[t] !== undefined) count.textContent = data.counts[t];
        b.classList.toggle('voted', (data.user_reactions || []).includes(t));
      });
    } else {
      const count = btn.querySelector('.react-count');
      if (count && data.counts[reaction] !== undefined) count.textContent = data.counts[reaction];
      btn.classList.toggle('voted', (data.user_reactions || []).includes(reaction));
    }
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
