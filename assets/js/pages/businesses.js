/**
 * Businesses listing page — list + map toggle + distance ("Near me") search
 */
'use strict';

window.appReady.then(async () => {
  const form = document.getElementById('filtersForm');
  const resultsEl = document.getElementById('businessResults');
  const countEl = document.getElementById('resultCount');
  const pagEl = document.getElementById('pagination');
  const nearMeBtn = document.getElementById('nearMeBtn');

  let lastData = null;      // raw API response (for map pins)
  let lastAds = [];
  let userLoc = null;       // { lat, lng } after "Near me" is used

  // Prefill from URL
  const params = new URLSearchParams(window.location.search);
  // Clear invalid hours param
  const hoursParam = params.get('hours');
  if (hoursParam && hoursParam !== 'open') {
    params.delete('hours');
    history.replaceState(null, '', '?' + params.toString());
  }
  for (const [key, selId] of [['q', 'f-q'], ['category', 'f-category'], ['location', 'f-city'], ['price', 'f-price'], ['min_rating', 'f-rating'], ['sort', 'f-sort'], ['hours', 'f-open'], ['lat', 'f-lat'], ['lng', 'f-lng'], ['radius', 'f-radius']]) {
    const el = document.getElementById(selId);
    if (el && params.get(key)) el.value = params.get(key);
  }
  const openEl = document.getElementById('f-open');
  if (openEl && (params.get('hours') === 'open' || params.get('open_now'))) openEl.checked = true;

  wireAutocomplete(document.getElementById('f-q'), document.getElementById('bizSuggest'));

  function formData() {
    const fd = new FormData(form);
    const out = {};
    for (const [k, v] of fd.entries()) {
      if (String(v).trim() !== '' && v !== '0' && v !== 'All categories' && v !== 'All cities' && v !== 'Any' && v !== 'rating') out[k] = v;
    }
    return out;
  }

  // toggle the "Near me" visual state
  function setNearMeState(active) {
    if (!nearMeBtn) return;
    nearMeBtn.classList.toggle('active', !!active);
    nearMeBtn.textContent = active ? '📍 Near me · active' : '📍 Near me';
  }
  setNearMeState(params.get('lat') && params.get('lng'));

  /** Render a Yelp-style "Home › City › Category" breadcrumb from the filters */
  function renderBreadcrumb(categories) {
    const el = document.getElementById('breadcrumb');
    if (!el) return;
    const fd = formData();
    const city = fd['location'] || '';
    const catSlug = fd['category'] || '';
    const catName = (categories || []).find(c => c.slug === catSlug)?.name || '';
    const crumbs = ['<a href="index">Home</a>'];
    if (city) crumbs.push(`<a href="?location=${encodeURIComponent(city)}">${esc(city)}</a>`);
    if (catSlug && catName) crumbs.push(`<a href="?category=${encodeURIComponent(catSlug)}">${esc(catName)}</a>`);
    if (crumbs.length === 1) {
      el.innerHTML = '';
      return;
    }
    el.innerHTML = crumbs.join('<span class="crumb-sep">›</span>');
  }

  /** Use the browser's geolocation, then reload with lat/lng/radius + distance sort */
  nearMeBtn.addEventListener('click', async () => {
    try {
      const pos = await getCurrentPosition();
      userLoc = { lat: pos.lat, lng: pos.lng };
      document.getElementById('f-lat').value = pos.lat.toFixed(6);
      document.getElementById('f-lng').value = pos.lng.toFixed(6);
      document.getElementById('f-radius').value = document.getElementById('f-radius').value || '50';
      const sortEl = document.getElementById('f-sort');
      if (sortEl) sortEl.value = 'distance';
      setNearMeState(true);
      history.replaceState(null, '', '?' + new URLSearchParams(formData()).toString());
      await load();
      if (window.wireResultsMapToggle && typeof mapToggle !== 'undefined' && mapToggle.isMapShown()) {
        mapToggle.showMap(); // re-render + center on user via onMapShown
      }
    } catch (e) {
      toast(e.message || 'Could not get your location', 'error');
    }
  });

  async function load() {
    const qs = new URLSearchParams(formData()).toString();
    const adQs = new URLSearchParams({ placement: 'search_results' });
    const cat = formData()['category'];
    const city = formData()['location'];
    if (cat) adQs.set('category', cat);
    if (city) adQs.set('city', city);
    const [data, adsRes] = await Promise.all([
      api('api/businesses.php?' + qs),
      api('api/ads.php?' + adQs.toString()).catch(() => null),
    ]);
    lastData = data;
    lastAds = (adsRes && adsRes.ads) || [];

    countEl.textContent = data.total.toLocaleString() + ' businesses found';
    renderBreadcrumb(data.categories);

    // interleave sponsored ads like Yelp: first on top, others sprinkled in
    const cards = data.items.map(businessCard);
    lastAds.forEach((ad, i) => {
      const pos = i === 0 ? 0 : Math.min(4 + i, cards.length);
      cards.splice(pos, 0, sponsoredCard(ad));
      recordAdImpression(ad.id);
    });

    resultsEl.innerHTML = cards.length
      ? cards.join('')
      : emptyState('No businesses found', 'Try adjusting your filters, or move your location marker.', '<a class="btn btn-primary btn-sm" href="businesses">View all businesses</a>');

    // populate filter options (once)
    if (data.categories && document.getElementById('f-category').options.length <= 1) {
      const catSel = document.getElementById('f-category');
      data.categories.forEach(c => {
        const o = document.createElement('option');
        o.value = c.slug; o.textContent = c.name + ' (' + c.total + ')';
        catSel.appendChild(o);
      });
      if (params.get('category')) catSel.value = params.get('category');
    }
    if (data.cities && document.getElementById('f-city').options.length <= 1) {
      const citySel = document.getElementById('f-city');
      data.cities.forEach(c => {
        const o = document.createElement('option');
        o.value = c.city; o.textContent = c.city;
        citySel.appendChild(o);
      });
      if (params.get('location')) citySel.value = params.get('location');
    }

    pagEl.innerHTML = paginationHtml(data.page, data.pages, qs);

    if (typeof mapToggle !== 'undefined' && mapToggle.isMapShown()) mapToggle.showMap();
  }

  // shared List/Map toggle; onMapShown centers on the user when "Near me" is active
  const mapToggle = wireResultsMapToggle({
    toggleId: 'viewToggle',
    containerId: 'mapContainer',
    resultsEl,
    pagEl,
    getItems: () => (lastData.items || []).map(i => ({ ...i, type: 'business', url: 'business?id=' + i.id })),
    onMapShown: (map) => {
      if (userLoc) addUserMarker(map, userLoc.lat, userLoc.lng);
    },
  });

  form.addEventListener('submit', e => {
    e.preventDefault();
    history.replaceState(null, '', '?' + new URLSearchParams(formData()).toString());
    load();
  });

  // page links
  document.addEventListener('click', e => {
    const a = e.target.closest('#pagination a');
    if (!a) return;
    e.preventDefault();
    history.replaceState(null, '', a.getAttribute('href'));
    load();
  });

  load().catch(err => {
    resultsEl.innerHTML = emptyState('Could not load businesses', err.message);
  });
});
