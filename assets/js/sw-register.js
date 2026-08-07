/**
 * Service worker registration.
 * Loaded as an external file on every page so the strict CSP
 * (script-src 'self', no 'unsafe-inline') keeps the PWA working.
 */
'use strict';

if ('serviceWorker' in navigator) {
  window.addEventListener('load', () => {
    navigator.serviceWorker.register('sw.js').catch(() => {});
  });
}
