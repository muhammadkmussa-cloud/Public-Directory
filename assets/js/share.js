/**
 * Umma Directory — share helpers
 * Uses the native Web Share API where supported (mobile), otherwise falls
 * back to a modal with copy-link + WhatsApp / X / Facebook / Email links.
 * Loaded on every page (after app.js) so share buttons work on cards too.
 */
'use strict';

/* ---- tiny inline SVG icons for the share targets (match app.js style) ---- */
const SHARE_TARGETS = [
  { key: 'wa', label: 'WhatsApp', href: (t, u) => 'https://wa.me/?text=' + encodeURIComponent(t + ' — ' + u) },
  { key: 'spark', label: 'X (Twitter)', href: (t, u) => 'https://twitter.com/intent/tweet?text=' + encodeURIComponent(t) + '&url=' + encodeURIComponent(u) },
  { key: 'globe', label: 'Facebook', href: (t, u) => 'https://www.facebook.com/sharer/sharer.php?u=' + encodeURIComponent(u) },
  { key: 'pen', label: 'Email', href: (t, u) => 'mailto:?subject=' + encodeURIComponent(t) + '&body=' + encodeURIComponent(t + '\n\n' + u) },
];

/** Main entry: share a listing. */
function shareListing(opts = {}) {
  const title = opts.title || document.title || 'Umma Directory';
  const url = opts.url || window.location.href;
  const text = opts.text || title;
  const data = { title, text, url };

  if (navigator.share && navigator.canShare && navigator.canShare(data)) {
    navigator.share(data).catch(() => { /* user cancelled */ });
    return;
  }
  openShareModal(data);
}
window.shareListing = shareListing;

/** Update (or create) the social OG / Twitter meta tags — done client-side. */
function setOgMeta(overrides = {}) {
  const head = document.head;
  const meta = (prop, content) => {
    let el = head.querySelector(`meta[property="${prop}"]`);
    if (!el) { el = document.createElement('meta'); el.setAttribute('property', prop); head.appendChild(el); }
    if (content) el.setAttribute('content', String(content));
  };
  if (overrides.title) { meta('og:title', overrides.title); meta('twitter:title', overrides.title); document.title = overrides.title + ' — Umma Directory'; }
  if (overrides.description) { meta('og:description', overrides.description); meta('twitter:description', overrides.description); }
  if (overrides.image) { meta('og:image', overrides.image); meta('twitter:image', overrides.image); }
  meta('og:type', 'website');
  meta('og:url', window.location.href);
  meta('og:site_name', 'Umma Directory');
  meta('twitter:card', 'summary_large_image');
}
window.setOgMeta = setOgMeta;

/** Build + open the fallback share modal (also used by any .share-btn). */
function openShareModal({ title, text, url }) {
  let overlay = document.getElementById('shareModal');
  if (overlay) { overlay.classList.add('open'); return; }

  overlay = document.createElement('div');
  overlay.id = 'shareModal';
  overlay.className = 'modal-overlay';
  overlay.setAttribute('role', 'dialog');
  overlay.setAttribute('aria-modal', 'true');
  overlay.innerHTML = `
    <div class="modal">
      <button class="modal-close" data-close-share aria-label="Close">×</button>
      <h2>Share this listing</h2>
      <p class="muted small" style="margin-bottom:1rem;">${esc(title)}</p>
      <div class="share-targets">
        ${SHARE_TARGETS.map(s => `
          <a class="share-target" href="${s.href(text, url)}" target="_blank" rel="noopener">
            ${icon(s.key, 20)} <span>${s.label}</span>
          </a>`).join('')}
      </div>
      <div class="share-copy">
        <input type="text" id="shareLinkInput" readonly value="${esc(url)}" aria-label="Listing link">
        <button class="btn btn-primary btn-sm" id="shareCopyBtn">Copy link</button>
      </div>
    </div>`;
  document.body.appendChild(overlay);

  overlay.addEventListener('click', (e) => {
    if (e.target === overlay) overlay.classList.remove('open');
    if (e.target.closest('[data-close-share]')) overlay.classList.remove('open');
  });

  document.getElementById('shareCopyBtn').addEventListener('click', async () => {
    const input = document.getElementById('shareLinkInput');
    try {
      await navigator.clipboard.writeText(input.value);
      toast('Link copied to clipboard');
    } catch (e) {
      input.select();
      document.execCommand('copy');
      toast('Link copied to clipboard');
    }
  });
  overlay.classList.add('open');
}

/* Event delegation: any element with class .share-btn opens the share modal.
 * Buttons may carry data-share-title / data-share-url / data-share-text. */
document.addEventListener('click', (e) => {
  const btn = e.target.closest('.share-btn');
  if (!btn) return;
  e.preventDefault();
  e.stopPropagation();
  shareListing({
    title: btn.dataset.shareTitle,
    url: btn.dataset.shareUrl || window.location.href,
    text: btn.dataset.shareText,
  });
});
