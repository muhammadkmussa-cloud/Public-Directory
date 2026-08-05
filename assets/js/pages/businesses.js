/**
 * Businesses listing page
 */
'use strict';

window.appReady.then(async () => {
  const form = document.getElementById('filtersForm');
  const resultsEl = document.getElementById('businessResults');
  const countEl = document.getElementById('resultCount');
  const pagEl = document.getElementById('pagination');

  // Prefill from URL
  const params = new URLSearchParams(window.location.search);
  for (const [key, selId] of [['q', 'f-q'], ['category', 'f-category'], ['location', 'f-city'], ['price', 'f-price'], ['min_rating', 'f-rating'], ['sort', 'f-sort']]) {
    const el = document.getElementById(selId);
    if (el && params.get(key)) el.value = params.get(key);
  }

  async function load() {
    const data = await api('api/businesses.php?' + new URLSearchParams(form ? formData(form) : {}).toString());

    countEl.textContent = data.total.toLocaleString() + ' businesses found';
    resultsEl.innerHTML = data.items.length
      ? data.items.map(businessCard).join('')
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

    // pagination
    const base = formData(form);
    pagEl.innerHTML = paginationHtml(data.page, data.pages, new URLSearchParams(base).toString());
  }

  function formData(formEl) {
    const fd = new FormData(formEl);
    const out = {};
    for (const [k, v] of fd.entries()) if (String(v).trim() !== '' && v !== '0' && v !== 'All categories' && v !== 'All cities' && v !== 'Any' && v !== 'rating') out[k] = v;
    return out;
  }

  form.addEventListener('submit', e => {
    e.preventDefault();
    history.replaceState(null, '', '?' + new URLSearchParams(formData(form)).toString());
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
