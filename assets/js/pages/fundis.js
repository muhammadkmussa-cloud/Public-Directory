/**
 * Fundis listing page
 */
'use strict';

window.appReady.then(async () => {
  const form = document.getElementById('filtersForm');
  const resultsEl = document.getElementById('fundiResults');
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
    const data = await api('api/fundis.php?' + new URLSearchParams(formData()).toString());
    countEl.textContent = data.total.toLocaleString() + ' fundis found';
    resultsEl.innerHTML = data.items.length
      ? data.items.map(fundiCard).join('')
      : emptyState('No fundis found', 'Try a different search.', '<a class="btn btn-primary btn-sm" href="fundis.html">View all</a>');

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

  load().catch(err => resultsEl.innerHTML = emptyState('Could not load fundis', err.message));
});
