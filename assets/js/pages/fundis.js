/**
 * Fundis listing page — list + map toggle + distance ("Near me") search
 */
'use strict';

window.appReady.then(async () => {
  const form = document.getElementById('filtersForm');
  const resultsEl = document.getElementById('fundiResults');
  const countEl = document.getElementById('resultCount');
  const pagEl = document.getElementById('pagination');
  const nearMeBtn = document.getElementById('nearMeBtn');

  let lastData = null;
  let userLoc = null;

  const params = new URLSearchParams(window.location.search);
  for (const [key, selId] of [['q', 'f-q'], ['skill', 'f-skill'], ['city', 'f-city'], ['lat', 'f-lat'], ['lng', 'f-lng'], ['radius', 'f-radius'], ['sort', 'f-sort']]) {
    const el = document.getElementById(selId);
    if (el && params.get(key)) el.value = params.get(key);
  }

  function formData() {
    const fd = new FormData(form);
    const out = {};
    for (const [k, v] of fd.entries()) {
      if (String(v).trim() !== '' && v !== 'All cities' && v !== 'All skills') out[k] = v;
    }
    if (out.sort === 'rating' && !out.lat) delete out.sort;
    return out;
  }

  function setNearMeState(active) {
    if (!nearMeBtn) return;
    nearMeBtn.classList.toggle('active', !!active);
    nearMeBtn.textContent = active ? '📍 Near me · active' : '📍 Near me';
  }
  setNearMeState(params.get('lat') && params.get('lng'));

  nearMeBtn.addEventListener('click', async () => {
    try {
      const pos = await getCurrentPosition();
      userLoc = { lat: pos.lat, lng: pos.lng };
      document.getElementById('f-lat').value = pos.lat.toFixed(6);
      document.getElementById('f-lng').value = pos.lng.toFixed(6);
      document.getElementById('f-radius').value = document.getElementById('f-radius').value || '50';
      document.getElementById('f-sort').value = 'distance';
      setNearMeState(true);
      history.replaceState(null, '', '?' + new URLSearchParams(formData()).toString());
      await load();
      if (mapToggle.isMapShown()) mapToggle.showMap();
    } catch (e) {
      toast(e.message || 'Could not get your location', 'error');
    }
  });

  async function load() {
    const qs = new URLSearchParams(formData()).toString();
    const data = await api('api/fundis.php?' + qs);
    lastData = data;
    countEl.textContent = data.total.toLocaleString() + ' fundis found';
    resultsEl.innerHTML = data.items.length
      ? data.items.map(fundiCard).join('')
      : emptyState('No fundis found', 'Try a different search, or move your location marker.', '<a class="btn btn-primary btn-sm" href="fundis">View all</a>');

    if (data.skills && document.getElementById('f-skill').options.length <= 1) {
      const sel = document.getElementById('f-skill');
      data.skills.forEach(s => {
        const o = document.createElement('option');
        o.value = s; o.textContent = s;
        sel.appendChild(o);
      });
      if (params.get('skill')) sel.value = params.get('skill');
    }
    if (data.cities && document.getElementById('f-city').options.length <= 1) {
      const sel = document.getElementById('f-city');
      data.cities.forEach(c => {
        const o = document.createElement('option');
        o.value = c.city; o.textContent = c.city;
        sel.appendChild(o);
      });
      if (params.get('city')) sel.value = params.get('city');
    }

    pagEl.innerHTML = paginationHtml(data.page, data.pages, qs);

    if (mapToggle.isMapShown()) mapToggle.showMap();
  }

  const mapToggle = wireResultsMapToggle({
    toggleId: 'viewToggle',
    containerId: 'mapContainer',
    resultsEl,
    pagEl,
    getItems: () => (lastData.items || []).map(i => ({ ...i, type: 'fundi', url: 'fundi?id=' + i.id })),
    onMapShown: (map) => {
      if (userLoc) addUserMarker(map, userLoc.lat, userLoc.lng);
    },
  });

  form.addEventListener('submit', e => {
    e.preventDefault();
    history.replaceState(null, '', '?' + new URLSearchParams(formData()).toString());
    load();
  });
  document.addEventListener('click', e => {
    const a = e.target.closest('#pagination a');
    if (!a) return;
    e.preventDefault();
    history.replaceState(null, '', a.getAttribute('href'));
    load();
  });

  load().catch(err => resultsEl.innerHTML = emptyState('Could not load fundis', err.message));
});
