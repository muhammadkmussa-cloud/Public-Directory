/**
 * Mosques listing page
 */
'use strict';

window.appReady.then(async () => {
  const form = document.getElementById('filtersForm');
  const resultsEl = document.getElementById('mosqueResults');
  const countEl = document.getElementById('resultCount');
  const pagEl = document.getElementById('pagination');

  const params = new URLSearchParams(window.location.search);
  if (params.get('q')) document.getElementById('f-q').value = params.get('q');

  function formData() {
    const fd = new FormData(form);
    const out = {};
    for (const [k, v] of fd.entries()) if (String(v).trim() !== '') out[k] = v;
    return out;
  }

  async function load() {
    const data = await api('api/mosques.php?' + new URLSearchParams(formData()).toString());
    countEl.textContent = data.total.toLocaleString() + ' mosques found';
    resultsEl.innerHTML = data.items.length
      ? data.items.map(mosqueCard).join('')
      : emptyState('No mosques found', 'Try a different search.', '<a class="btn btn-primary btn-sm" href="mosques.html">View all</a>');

    if (data.cities && document.getElementById('f-city').options.length <= 1) {
      const sel = document.getElementById('f-city');
      data.cities.forEach(c => {
        const o = document.createElement('option');
        o.value = c.city; o.textContent = c.city;
        sel.appendChild(o);
      });
      if (params.get('city')) sel.value = params.get('city');
    }

    pagEl.innerHTML = paginationHtml(data.page, data.pages, new URLSearchParams(formData()).toString());
  }

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

  load().catch(err => resultsEl.innerHTML = emptyState('Could not load mosques', err.message));
});
