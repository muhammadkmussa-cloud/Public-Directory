/**
 * Ummah Directory — map helpers (Leaflet + OpenStreetMap, no API key)
 * Loaded on pages that include Leaflet. All functions no-op safely if
 * Leaflet isn't available (e.g. demo preview without network).
 */
'use strict';

function mapAvailable() {
  return typeof L !== 'undefined';
}

const OSM_TILE = 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png';
const OSM_ATTR = '&copy; <a href="https://www.openstreetmap.org/copyright" target="_blank" rel="noopener">OpenStreetMap</a> contributors';

/** Interactive single-marker map (detail pages) */
function initDetailMap(containerId, lat, lng, title, linkUrl) {
  if (!mapAvailable() || !lat || !lng) return;
  const el = document.getElementById(containerId);
  if (!el) return;
  const map = L.map(el, { scrollWheelZoom: false }).setView([lat, lng], 15);
  L.tileLayer(OSM_TILE, { attribution: OSM_ATTR }).addTo(map);
  const marker = L.marker([lat, lng]).addTo(map);
  if (title) {
    const popup = '<b>' + String(title).replace(/</g, '&lt;') + '</b>'
      + (linkUrl ? ' <br><a href="' + String(linkUrl).replace(/"/g, '&quot;') + '">View details</a>' : '');
    marker.bindPopup(popup).openPopup();
  }
  return map;
}

/** Marker map for a list of results (search results page) */
function renderResultsMap(containerId, items) {
  if (!mapAvailable()) return false;
  const el = document.getElementById(containerId);
  if (!el) return false;
  const has = (items || []).filter(i => i.latitude && i.longitude);
  if (!has.length) return false;

  const map = L.map(el, { scrollWheelZoom: false });
  L.tileLayer(OSM_TILE, { attribution: OSM_ATTR }).addTo(map);

  const markers = has.map(item => {
    const name = item.name || item.full_name || '';
    const rating = item.rating_average ? ' ★ ' + Number(item.rating_average).toFixed(1) + ' (' + (item.review_count || 0) + ')' : '';
    const url = item.url || (item.id ? (item.type || 'business') + '?id=' + item.id : '#');
    const m = L.marker([item.latitude, item.longitude]).addTo(map);
    m.bindPopup('<b>' + String(name).replace(/</g, '&lt;') + '</b>' + rating
      + '<br><a href="' + String(url).replace(/"/g, '&quot;') + '">View details →</a>');
    return m;
  });

  if (markers.length > 1) {
    map.fitBounds(L.featureGroup(markers).getBounds(), { padding: [24, 24] });
  } else {
    map.setView([has[0].latitude, has[0].longitude], 14);
  }
  return map;
}

/** Blue-dot marker for the user's location (Near me). Returns marker or null. */
function addUserMarker(map, lat, lng) {
  if (!mapAvailable() || !map || !lat || !lng) return null;
  const icon = L.divIcon({
    className: 'user-dot-wrap',
    html: '<span class="user-dot"></span>',
    iconSize: [18, 18],
    iconAnchor: [9, 9],
  });
  return L.marker([lat, lng], { icon, zIndexOffset: 1000 })
    .addTo(map)
    .bindPopup('<b>You are here</b>');
}

/**
 * Promise wrapper around the browser Geolocation API.
 * Rejects with a friendly message; caller decides how to degrade.
 */
function getCurrentPosition() {
  return new Promise((resolve, reject) => {
    if (!('geolocation' in navigator)) {
      reject(new Error('Geolocation is not supported by this browser'));
      return;
    }
    navigator.geolocation.getCurrentPosition(
      pos => resolve({ lat: pos.coords.latitude, lng: pos.coords.longitude, accuracy: pos.coords.accuracy || 0 }),
      err => {
        const msg = err && err.code === 1
          ? 'Location access was denied'
          : 'Could not get your location';
        reject(new Error(msg));
      },
      { enableHighAccuracy: false, timeout: 10000, maximumAge: 60000 }
    );
  });
}

/**
 * Shared List/Map toggle wiring for listing pages (businesses, mosques, fundis).
 * opts:
 *   toggleId     — id of the .view-toggle element (buttons data-view="list|map")
 *   containerId  — id of the map container div (hidden by default)
 *   resultsEl    — element with the result cards
 *   pagEl        — element with pagination
 *   getItems     — () => raw items with latitude/longitude (+ type + url fields)
 *   onMapShown   — optional callback after the map renders (e.g. center on user)
 */
function wireResultsMapToggle(opts) {
  const toggle = document.getElementById(opts.toggleId);
  const container = document.getElementById(opts.containerId);
  if (!toggle || !container) return;
  let mapShown = false;

  function showMap() {
    mapShown = true;
    if (opts.resultsEl) opts.resultsEl.hidden = true;
    if (opts.pagEl) opts.pagEl.hidden = true;
    container.hidden = false;
    const items = opts.getItems() || [];
    const ok = renderResultsMap(opts.containerId, items);
    if (!ok && container) {
      container.innerHTML = '<div class="empty-state"><p>No locations available to map.</p></div>';
    } else if (ok && opts.onMapShown) {
      opts.onMapShown(ok); // ok = Leaflet map instance
    }
  }

  function showList() {
    mapShown = false;
    if (opts.resultsEl) opts.resultsEl.hidden = false;
    if (opts.pagEl) opts.pagEl.hidden = false;
    container.hidden = true;
  }

  toggle.addEventListener('click', (e) => {
    const btn = e.target.closest('.view-btn');
    if (!btn) return;
    toggle.querySelectorAll('.view-btn').forEach(b => b.classList.toggle('active', b === btn));
    if (btn.dataset.view === 'map') showMap(); else showList();
  });

  return { showMap, showList, isMapShown: () => mapShown };
}
