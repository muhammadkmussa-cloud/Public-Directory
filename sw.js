/**
 * Ummah Directory — service worker (PWA)
 * App-shell cache for the static pages; API calls go to the network.
 */
const CACHE = 'ummah-v1';
const APP_SHELL = [
  './',
  'index.html',
  'businesses.html',
  'mosques.html',
  'fundis.html',
  'charities.html',
  'verify.html',
  'assets/css/style.css',
  'assets/js/app.js',
  'assets/js/mock.js',
  'assets/js/map.js',
  'assets/js/share.js',
];

self.addEventListener('install', (event) => {
  event.waitUntil(
    caches.open(CACHE)
      .then((cache) => cache.addAll(APP_SHELL))
      .then(() => self.skipWaiting())
  );
});

self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys()
      .then((keys) => Promise.all(keys.filter((k) => k !== CACHE).map((k) => caches.delete(k))))
      .then(() => self.clients.claim())
  );
});

// network-first for pages, cache-first for static assets, never cache API
self.addEventListener('fetch', (event) => {
  const url = new URL(event.request.url);
  if (event.request.method !== 'GET') return;
  if (url.pathname.includes('/api/')) return;

  if (url.pathname.endsWith('.html') || url.pathname.endsWith('/')) {
    event.respondWith(
      fetch(event.request)
        .then((res) => {
          const copy = res.clone();
          caches.open(CACHE).then((c) => c.put(event.request, copy));
          return res;
        })
        .catch(() => caches.match(event.request).then((m) => m || caches.match('index.html')))
    );
    return;
  }

  event.respondWith(
    caches.match(event.request).then(
      (hit) => hit || fetch(event.request).then((res) => {
        const copy = res.clone();
        caches.open(CACHE).then((c) => c.put(event.request, copy));
        return res;
      })
    )
  );
});
