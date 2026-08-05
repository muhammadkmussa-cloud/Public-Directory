/**
 * Umma Directory — map helpers (Leaflet + OpenStreetMap, no API key)
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
    const url = item.url || (item.id ? (item.type || 'business') + '.html?id=' + item.id : '#');
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
  return true;
}
