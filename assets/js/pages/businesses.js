/**
 * Businesses listing page — list + map toggle
 */
'use strict';

window.appReady.then(async () => {
  const form = document.getElementById('filtersForm');
  const resultsEl = document.getElementById('businessResults');
  const countEl = document.getElementById('resultCount');
  const pagEl = document.getElementById('pagination');
  const mapContainer = document.getElementById('mapContainer');
  const viewToggle = document.getElementById('viewToggle');

  let lastData = null;      // raw API response (for map pins)
  let lastAds = [];
  let mapShown = false;

  // Prefill from URL
  const params = new URLSearchParams(window.location.search);
  for (const [key, selId] of [['q', 'f-q'], ['category', 'f-category'], ['location', 'f-city'], ['price', 'f-price'], ['min_rating', 'f-rating'], ['sort', 'f-sort']]) {
    const el = document.getElementById(selId);
    if (el && params.get(key)) el.value = params.get(key);
  }
  const openEl = document.getElementById('f-open');
  if (openEl && params.get('open_now')) openEl.checked = true;

  wireAutocomplete(document.getElementById('f-q'), document.getElementById('bizSuggest'));

  function formData() {
    const fd = new FormData(form);
    const out = {};
    for (const [k, v] of fd.entries()) {
      if (String(v).trim() !== '' && v !== '0' && v !== 'All categories' && v !== 'All cities' && v !== 'Any' && v !== 'rating') out[k] = v;
    }
    return out;
  }

  async function load() {
    const qs = new URLSearchParams(formData()).toString();
    const [data, adsRes] = await Promise.all([
      api('api/businesses.php?' + qs),
      api('api/ads.php?placement=search_results').catch(() => null),
    ]);
    lastData = data;
    lastAds = (adsRes && adsRes.ads) || [];

    countEl.textContent = data.total.toLocaleString() + ' businesses found';

    // interleave sponsored ads like Yelp: first on top, others sprinkled in
    const cards = data.items.map(businessCard);
    lastAds.forEach((ad, i) => {
      const pos = i === 0 ? 0 : Math.min(4 + i, cards.length);
      cards.splice(pos, 0, sponsoredCard(ad));
      recordAdImpression(ad.id);
    });

    resultsEl.innerHTML = cards.length
      ? cards.join('')
      : emptyState('No businesses found', 'Try adjusting your filters.', '<a class="btn btn-primary btn-sm" href="businesses.html">View all businesses</a>');

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

    if (mapShown) showMap();
  }

  function showMap() {
    mapShown = true;
    resultsEl.hidden = true;
    pagEl.hidden = true;
    mapContainer.hidden = false;
    const items = (lastData.items || []).map(i => ({ ...i, type: 'business', url: 'business.html?id=' + i.id }));
    if (!renderResultsMap('mapContainer', items) && mapContainer) {
      mapContainer.innerHTML = '<div class="empty-state"><p>No locations available to map.</p></div>';
    }
  }

  function showList() {
    mapShown = false;
    resultsEl.hidden = false;
    pagEl.hidden = false;
    mapContainer.hidden = true;
  }

  viewToggle.addEventListener('click', (e) => {
    const btn = e.target.closest('.view-btn');
    if (!btn) return;
    viewToggle.querySelectorAll('.view-btn').forEach(b => b.classList.toggle('active', b === btn));
    if (btn.dataset.view === 'map') showMap(); else showList();
  });

  form.addEventListener('submit', e => {
    e.preventDefault();
    history.replaceState(null, '', '?' + new URLSearchParams(formData()).toString());
    showList();
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
