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
 * i18n (EN / SW) — lightweight dictionary
 * ============================================================ */
const I18N = {
  en: {
    'Write a Review': 'Write a Review',
    'View all': 'View all',
    'Open': 'Open',
    'Verified': 'Verified',
    'Sponsored': 'Sponsored',
    'Saved to your bookmarks': 'Saved to your bookmarks',
    'Removed from bookmarks': 'Removed from bookmarks',
  },
  sw: {
    'Write a Review': 'Andika Tathmini',
    'View all': 'Ona Zote',
    'Open': 'Funguliwa',
    'Verified': 'Imethibitishwa',
    'Sponsored': 'Inafadhiliwa',
    'Saved to your bookmarks': 'Imehifadhiwa',
    'Removed from bookmarks': 'Imeondolewa kwenye hifadhi',
  },
};
let lang = localStorage.getItem('umma_lang') || 'en';

function t(str) {
  const dict = I18N[lang] || I18N.en;
  return dict[str] || str;
}
function setLang(l) {
  lang = ['en', 'sw'].includes(l) ? l : 'en';
  localStorage.setItem('umma_lang', lang);
}
window.t = t;
window.setLang = setLang;

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
  bookmark: '<path d="M19 21l-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2v16z"/>',
  bookmarkFill: '<path fill="currentColor" stroke="currentColor" d="M19 21l-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2v16z"/>',
  flag: '<path d="M4 15s1-1 4-1 5 2 8 2 4-1 4-1V3s-1 1-4 1-5-2-8-2-4 1-4 1z"/><line x1="4" y1="22" x2="4" y2="15"/>',
  trophy: '<path d="M8 21h8"/><path d="M12 17v4"/><path d="M7 4h10v6a5 5 0 0 1-10 0V4z"/><path d="M7 6H4a2 2 0 0 0 0 4h3"/><path d="M17 6h3a2 2 0 0 1 0 4h-3"/>',
  starBadge: '<path d="M12 2l2.9 5.9 6.5.9-4.7 4.6 1.1 6.5L12 17.8 6.2 19.9l1.1-6.5L2.6 8.8l6.5-.9L12 2z"/>',
  bell: '<path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.7 21a2 2 0 0 1-3.4 0"/>',
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
      <a class="nav-link" href="charities.html">Charities</a>
      <a class="btn btn-primary btn-write" href="businesses.html">${icon('pen', 14)} Write a Review</a>
      <div class="nav-auth" id="navAuth"></div>
      <button class="btn btn-ghost nav-bell" id="notifBell" aria-label="Notifications" title="Notifications">
        ${icon('bell', 18)} <span class="notif-dot" id="notifDot" hidden></span>
      </button>
      <div class="lang-switch" id="langSwitch">
        <button class="lang-btn ${lang === 'en' ? 'active' : ''}" data-lang="en">EN</button>
        <button class="lang-btn ${lang === 'sw' ? 'active' : ''}" data-lang="sw">SW</button>
      </div>
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
      <a href="charities.html">Charities</a>
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
      <a href="admin.html">Advertise</a>
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
      <a class="nav-link" href="dashboard.html">Dashboard</a>
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
  // language switcher
  document.querySelectorAll('.lang-btn').forEach(btn => {
    btn.addEventListener('click', () => {
      setLang(btn.dataset.lang);
      document.querySelectorAll('.lang-btn').forEach(b => b.classList.toggle('active', b === btn));
      toast(lang === 'sw' ? 'Lugha imebadilishwa kuwa Kiswahili' : 'Language set to English');
      setTimeout(() => window.location.reload(), 400);
    });
  });
  try {
    session.user = await api('api/auth.php?action=me');
  } catch (e) {
    session.user = null;
  }
  renderAuthNav();
  refreshNotifBadge();
  if (mockMode) {
    const banner = document.getElementById('mockBanner');
    if (banner) banner.hidden = false;
  }
}

/* ============================================================
 * Notifications (bell + dropdown)
 * ============================================================ */
async function refreshNotifBadge() {
  const dot = document.getElementById('notifDot');
  if (!dot) return;
  if (!session.user) { dot.hidden = true; return; }
  try {
    const d = await api('api/notifications.php?unread=1');
    dot.hidden = !(d.unread_count > 0);
    dot.textContent = d.unread_count > 9 ? '9+' : d.unread_count;
  } catch (e) { dot.hidden = true; }
}

function renderNotifDropdown() {
  let panel = document.getElementById('notifPanel');
  if (panel) { panel.remove(); return; } // toggle close
  panel = document.createElement('div');
  panel.id = 'notifPanel';
  panel.className = 'notif-panel';
  panel.innerHTML = '<div class="notif-loading">Loading…</div>';
  document.body.appendChild(panel);

  api('api/notifications.php').then(d => {
    const items = (d && d.items) || [];
    panel.innerHTML = `
      <div class="notif-head">
        <b>Notifications</b>
        ${items.length ? '<button class="btn btn-ghost btn-xs" id="notifReadAll">Mark all read</button>' : ''}
      </div>
      ${items.length
        ? items.slice(0, 10).map(n => `
            <a class="notif-item ${n.is_read ? '' : 'unread'}" href="${esc(n.link || 'profile.html')}" data-notif-id="${n.id}">
              <b>${esc(n.title)}</b>
              <span class="muted small">${esc(n.message)}</span>
              <span class="muted small">${timeAgo(n.created_at)}</span>
            </a>`).join('')
        : '<p class="muted center" style="padding:1rem 0;">No notifications yet</p>'}
      <a class="notif-all" href="notifications.html">View all</a>`;

    const readAll = panel.querySelector('#notifReadAll');
    if (readAll) readAll.addEventListener('click', async () => {
      await api('api/notifications.php', { method: 'POST', body: { action: 'read_all' } });
      refreshNotifBadge();
      panel.querySelectorAll('.notif-item').forEach(i => i.classList.remove('unread'));
      readAll.remove();
    });
    panel.querySelectorAll('.notif-item').forEach(item => {
      item.addEventListener('click', () => {
        api('api/notifications.php', { method: 'POST', body: { action: 'read', id: item.dataset.notifId } }).then(refreshNotifBadge).catch(() => {});
      });
    });
  }).catch(() => {
    panel.innerHTML = '<p class="muted center" style="padding:1rem 0;">Could not load notifications</p>';
  });

  setTimeout(() => {
    document.addEventListener('click', (e) => {
      const p = document.getElementById('notifPanel');
      if (p && !p.contains(e.target) && !e.target.closest('#notifBell')) p.remove();
    }, 0);
  }, 0);
}

document.addEventListener('click', (e) => {
  if (e.target.closest('#notifBell')) {
    if (!session.user) { toast('Please login to see notifications', 'error'); return; }
    renderNotifDropdown();
  }
});

/* ============================================================
 * Review photos
 * ============================================================ */
/** Upload up to 5 files to api/upload.php → array of "uploads/..." paths */
async function uploadReviewPhotos(files) {
  const paths = [];
  const list = Array.from(files || []).slice(0, 5);
  for (const file of list) {
    const fd = new FormData();
    fd.append('image', file);
    fd.append('dir', 'reviews');
    const res = await api('api/upload.php', { method: 'POST', body: fd });
    if (res && res.file) paths.push(res.file);
  }
  return paths;
}

/** Wire #rvPhotos file input → thumbnail preview */
function wirePhotoInput() {
  const input = document.getElementById('rvPhotos');
  const preview = document.getElementById('rvPhotoPreview');
  if (!input || !preview) return;
  input.addEventListener('change', () => {
    preview.innerHTML = '';
    Array.from(input.files).slice(0, 5).forEach(file => {
      const img = document.createElement('img');
      img.src = URL.createObjectURL(file);
      img.alt = 'Photo preview';
      preview.appendChild(img);
    });
  });
}

/* ============================================================
 * Favorites (bookmarks)
 * ============================================================ */
function saveBtnHtml(type, id) {
  return `<button class="save-btn" data-save-type="${type}" data-save-id="${id}" aria-label="Save" title="Save">${icon('bookmark', 16)}</button>`;
}

function setSavedState(btn, saved) {
  btn.classList.toggle('saved', !!saved);
  btn.innerHTML = saved ? icon('bookmarkFill', 16) : icon('bookmark', 16);
  btn.title = saved ? 'Remove from saved' : 'Save';
  btn.setAttribute('aria-label', saved ? 'Remove from saved' : 'Save');
}

async function toggleFavorite(type, id) {
  if (!session.user) {
    toast('Please login to save', 'error');
    setTimeout(() => (window.location.href = 'login.html'), 700);
    return null;
  }
  try {
    const res = await api('api/favorites.php', {
      method: 'POST',
      body: { action: 'toggle', favoritable_type: type, favoritable_id: id },
    });
    toast(res.saved ? 'Saved to your bookmarks' : 'Removed from bookmarks');
    return res.saved;
  } catch (e) {
    toast(e.message || 'Could not save', 'error');
    return null;
  }
}

/** If logged in, sync every visible save button for a listing */
async function refreshSaveState(type, id) {
  if (!session.user) return;
  try {
    const res = await api('api/favorites.php?action=status&favoritable_type=' + encodeURIComponent(type) + '&favoritable_id=' + id);
    document.querySelectorAll(`.save-btn[data-save-type="${type}"][data-save-id="${id}"]`).forEach(b => setSavedState(b, res.saved));
  } catch (e) { /* ignore */ }
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
      ${saveBtnHtml('business', b.id)}
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
      ${saveBtnHtml('mosque', m.id)}
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
      ${saveBtnHtml('fundi', f.id)}
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
      ${saveBtnHtml('business', b.id)}
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
      ${saveBtnHtml('mosque', m.id)}
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

/* ---- charity cards ---- */
const CHARITY_CATEGORY_LABEL = c => String(c || '').replace(/_/g, ' ');

function charityCard(c) {
  return `
  <article class="card tile-card">
    <a class="tile-media" href="charity.html?id=${c.id}">
      ${c.cover_photo
        ? `<img src="${esc(c.cover_photo)}" alt="${esc(c.name)}" loading="lazy">`
        : `<div class="media-placeholder">${icon('heart', 34)}</div>`}
      ${c.is_verified ? '<span class="badge badge-verified">✓ Verified</span>' : ''}
    </a>
    <div class="tile-body">
      <h3 class="tile-title"><a href="charity.html?id=${c.id}">${esc(c.name)}</a></h3>
      <div class="tile-loc">${esc(CHARITY_CATEGORY_LABEL(c.category))} · ${esc(c.city || '')}</div>
      <div class="campaign-stats" style="margin-top:.3rem;">
        <span><b>${fmtMoney(c.raised)}</b> raised</span>
        <span>${c.donors} donors</span>
      </div>
    </div>
  </article>`;
}

function charityTile(c) {
  return charityCard(c);
}

function fundiTile(f) {
  return `
  <article class="card tile-card">
    <a class="tile-media" href="fundi.html?id=${f.id}">
      ${f.profile_photo
        ? `<img src="${esc(f.profile_photo)}" alt="${esc(f.full_name)}" loading="lazy">`
        : `<div class="media-placeholder">${icon('wrench', 34)}</div>`}
      ${saveBtnHtml('fundi', f.id)}
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

/** Contributor badge chip for a user (Yelp-style "elite"/level badges) */
function contributorBadge(u) {
  if (!u) return '';
  const badge = u.verification_badge && u.verification_badge !== 'none'
    ? `<span class="contrib-badge badge-${esc(u.verification_badge)}" title="${esc(u.verification_badge.replace('_', ' '))} contributor">${icon('trophy', 11)} ${esc(u.verification_badge.replace('_', ' '))}</span>`
    : '';
  const level = (u.contributor_level || 1) > 1
    ? `<span class="contrib-badge contrib-level" title="Contributor level ${u.contributor_level}">${icon('starBadge', 11)} Lv ${u.contributor_level}</span>`
    : '';
  return badge + level;
}

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
          <div class="contrib-row">${contributorBadge(u)}</div>
          <div class="muted small">${timeAgo(r.created_at)}</div>
        </div>
      </div>
      <div class="rating-row">${starsHtml(r.rating)}</div>
    </div>
    <h4 class="review-title">${esc(r.title || '')}</h4>
    <p class="review-text">${esc(r.content || '')}</p>
    ${(r.photos && r.photos.length) ? `<div class="review-photos">${r.photos.map(p => `<img src="${esc(p)}" alt="Review photo" loading="lazy">`).join('')}</div>` : ''}
    ${r.owner_response ? `<div class="owner-response"><b>Owner response:</b> ${esc(r.owner_response)}</div>` : ''}
    <div class="review-actions">
      ${reactions}
      <button class="reaction-btn report-btn" data-report-type="review" data-report-id="${r.id}">${icon('flag', 13)} Report</button>
    </div>
  </article>`;
}

/* ============================================================
 * Sponsored ads (Yelp-style)
 * ============================================================ */
const AD_COLORS = ['#c2410c', '#0369a1', '#7c3aed', '#0d9488', '#be185d', '#4d7c0f', '#b45309', '#1d4ed8'];
function adColor(id) { return AD_COLORS[Number(id) % AD_COLORS.length]; }

/** Horizontal sponsored result card (search results) */
function sponsoredCard(ad) {
  return `
  <article class="card result-card ad-card">
    <a class="result-media" href="${esc(ad.link_url)}" data-ad-click="${ad.id}">
      ${ad.image_path
        ? `<img src="${esc(ad.image_path)}" alt="${esc(ad.title)}" loading="lazy">`
        : `<div class="media-placeholder ad-placeholder" style="background:${adColor(ad.id)}">${esc((ad.title || 'A').charAt(0).toUpperCase())}</div>`}
    </a>
    <div class="result-body">
      <span class="sponsored-tag">Sponsored</span>
      <h3 class="result-title"><a href="${esc(ad.link_url)}" data-ad-click="${ad.id}">${esc(ad.title)}</a></h3>
      <p class="result-snippet">${esc(ad.html_content || '')}</p>
      <div class="result-actions">
        <span class="result-loc">${icon('spark', 12)} Ad · Learn more →</span>
      </div>
    </div>
  </article>`;
}

/** Wide banner (homepage header placement) */
function sponsoredBanner(ad) {
  return `
  <a class="ad-banner" href="${esc(ad.link_url)}" data-ad-click="${ad.id}">
    <span class="sponsored-tag">Sponsored</span>
    ${ad.image_path
      ? `<img src="${esc(ad.image_path)}" alt="" loading="lazy">`
      : `<div class="ad-banner-ph" style="background:${adColor(ad.id)}">${esc((ad.title || 'A').charAt(0).toUpperCase())}</div>`}
    <span class="ad-banner-text">
      <b>${esc(ad.title)}</b>
      <span>${esc(ad.html_content || '')}</span>
    </span>
  </a>`;
}

/** Compact sidebar card (detail pages) */
function sponsoredMini(ad) {
  return `
  <a class="ad-mini" href="${esc(ad.link_url)}" data-ad-click="${ad.id}">
    ${ad.image_path
      ? `<img src="${esc(ad.image_path)}" alt="" loading="lazy">`
      : `<div class="ad-mini-ph" style="background:${adColor(ad.id)}">${esc((ad.title || 'A').charAt(0).toUpperCase())}</div>`}
    <span>
      <b>${esc(ad.title)}</b>
      <span class="ad-mini-tag">Sponsored</span>
    </span>
  </a>`;
}

/** Fire-and-forget impression beacon */
function recordAdImpression(adId) {
  if (!adId) return;
  api('api/ads.php', { method: 'POST', body: { action: 'impression', ad_id: adId } }).catch(() => {});
}

/** Fill a #sponsoredSide container on detail pages */
async function loadSponsoredSide() {
  const el = document.getElementById('sponsoredSide');
  if (!el) return;
  try {
    const res = await api('api/ads.php?placement=detail_page');
    const ads = (res && res.ads) || [];
    if (!ads.length) { el.remove(); return; }
    el.innerHTML = '<h3>Sponsored</h3>' + ads.map(ad => {
      recordAdImpression(ad.id);
      return sponsoredMini(ad);
    }).join('');
  } catch (e) { /* ignore — side stays empty */ }
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
 * Report modal (shared)
 * ============================================================ */
const REPORT_REASONS = ['spam', 'fake', 'inappropriate', 'scam', 'duplicate', 'closed', 'other'];

function openReportModal(type, id) {
  if (!session.user) {
    toast('Please login to report', 'error');
    setTimeout(() => (window.location.href = 'login.html'), 700);
    return;
  }
  let overlay = document.getElementById('reportModal');
  if (!overlay) {
    overlay = document.createElement('div');
    overlay.id = 'reportModal';
    overlay.className = 'modal-overlay';
    overlay.innerHTML = `
      <div class="modal">
        <button class="modal-close" data-close-report aria-label="Close">×</button>
        <h2>Report this content</h2>
        <p class="muted small" style="margin-bottom:1rem;">Help us keep the community safe. Our moderators will review your report.</p>
        <form id="reportForm">
          <input type="hidden" id="repId">
          <input type="hidden" id="repType">
          <div class="form-group">
            <label for="repReason">Reason</label>
            <select id="repReason" style="width:100%;padding:.6rem .8rem;border:1.5px solid var(--border-2);border-radius:5px;font:inherit;">
              ${REPORT_REASONS.map(r => `<option value="${r}">${r.charAt(0).toUpperCase() + r.slice(1)}</option>`).join('')}
            </select>
          </div>
          <div class="form-group">
            <label for="repDesc">Details (optional)</label>
            <textarea id="repDesc" rows="3" maxlength="1000" placeholder="Tell us what's wrong…" style="width:100%;padding:.6rem .8rem;border:1.5px solid var(--border-2);border-radius:5px;font:inherit;outline:none;"></textarea>
          </div>
          <button class="btn btn-primary btn-block" type="submit" id="reportSubmit">Submit report</button>
        </form>
      </div>`;
    document.body.appendChild(overlay);
    overlay.addEventListener('click', e => {
      if (e.target === overlay) overlay.classList.remove('open');
      if (e.target.closest('[data-close-report]')) overlay.classList.remove('open');
    });
    overlay.querySelector('#reportForm').addEventListener('submit', async (e) => {
      e.preventDefault();
      const btn = document.getElementById('reportSubmit');
      btn.disabled = true;
      try {
        await api('api/reports.php', {
          method: 'POST',
          body: {
            action: 'create',
            reportable_id: document.getElementById('repId').value,
            reportable_type: document.getElementById('repType').value,
            reason: document.getElementById('repReason').value,
            description: document.getElementById('repDesc').value,
          },
        });
        toast('Report submitted — thank you!');
        overlay.classList.remove('open');
        btn.disabled = false;
      } catch (err) {
        toast(err.message || 'Could not submit report', 'error');
        btn.disabled = false;
      }
    });
  }
  document.getElementById('repId').value = id;
  document.getElementById('repType').value = type;
  document.getElementById('repDesc').value = '';
  overlay.classList.add('open');
}

document.addEventListener('click', (e) => {
  const btn = e.target.closest('.report-btn');
  if (!btn) return;
  e.preventDefault();
  openReportModal(btn.dataset.reportType, btn.dataset.reportId);
});

/* ============================================================
 * Search autocomplete
 * ============================================================ */
/** Wire an input + dropdown element to show suggestions under it. */
function wireAutocomplete(inputEl, dropdownEl) {
  if (!inputEl || !dropdownEl) return;
  let timer = null;
  const close = () => { dropdownEl.classList.remove('open'); dropdownEl.innerHTML = ''; };
  inputEl.addEventListener('input', () => {
    clearTimeout(timer);
    const q = inputEl.value.trim();
    if (q.length < 2) { close(); return; }
    timer = setTimeout(async () => {
      try {
        const items = await api('api/suggest.php?q=' + encodeURIComponent(q));
        if (!items.length) {
          dropdownEl.innerHTML = '<div class="suggest-empty">No matches</div>';
          dropdownEl.classList.add('open');
          return;
        }
        dropdownEl.innerHTML = items.map(it => `
          <a class="suggest-item" href="${esc(it.url)}">
            <span class="suggest-icon">${icon(it.type === 'business' ? 'bag' : it.type === 'mosque' ? 'mosque' : it.type === 'fundi' ? 'wrench' : 'grid', 15)}</span>
            <span class="suggest-text">
              <b>${esc(it.label)}</b>
              <span class="muted small">${esc(it.sub)}</span>
            </span>
          </a>`).join('');
        dropdownEl.classList.add('open');
      } catch (e) { /* ignore */ }
    }, 220);
  });
  inputEl.addEventListener('focus', () => { if (dropdownEl.innerHTML) dropdownEl.classList.add('open'); });
  document.addEventListener('click', (e) => {
    if (!dropdownEl.contains(e.target) && !inputEl.contains(e.target)) close();
  });
  return close;
}

/* ============================================================
 * Recent activity (homepage feed)
 * ============================================================ */
function activityItem(a) {
  const u = a.user_name || 'User';
  const initial = esc(u.charAt(0).toUpperCase());
  return `
  <div class="activity-item">
    <span class="avatar">${a.profile_photo ? `<img src="${esc(a.profile_photo)}" alt="">` : initial}</span>
    <div class="activity-body">
      <div>
        <b>${esc(u)}</b> reviewed <a href="${esc(a.listing_url)}"><b>${esc(a.listing_name)}</b></a>
        <span class="muted small">· ${timeAgo(a.created_at)}</span>
      </div>
      <div class="activity-review">
        ${starsHtml(a.rating)}
        <span class="activity-title">${esc(a.title || '')}</span>
      </div>
      <p class="activity-text">${esc(a.content || '')}</p>
    </div>
  </div>`;
}

/* ============================================================
 * Event delegation: save (bookmark) buttons
 * ============================================================ */
document.addEventListener('click', async (e) => {
  const btn = e.target.closest('.save-btn');
  if (!btn) return;
  e.preventDefault();
  e.stopPropagation();
  const type = btn.dataset.saveType;
  const id = btn.dataset.saveId;
  const saved = await toggleFavorite(type, id);
  if (saved !== null) {
    document.querySelectorAll(`.save-btn[data-save-type="${type}"][data-save-id="${id}"]`).forEach(b => setSavedState(b, saved));
  }
});

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
 * Event delegation: sponsored ad clicks (record, then navigate)
 * ============================================================ */
document.addEventListener('click', (e) => {
  const link = e.target.closest('[data-ad-click]');
  if (!link) return;
  api('api/ads.php', { method: 'POST', body: { action: 'click', ad_id: link.dataset.adClick } }).catch(() => {});
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
