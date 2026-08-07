/**
 * Charities listing page
 */
'use strict';

window.appReady.then(async () => {
  const form = document.getElementById('filtersForm');
  const resultsEl = document.getElementById('charityResults');
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
    const data = await api('api/charities.php?' + new URLSearchParams(formData()).toString());
    countEl.textContent = data.total.toLocaleString() + ' charities & causes';
    resultsEl.innerHTML = data.items.length
      ? data.items.map(charityCard).join('')
      : emptyState('No charities found', 'Try a different search.', '<a class="btn btn-primary btn-sm" href="charities">View all</a>');

    if (data.categories && document.getElementById('f-category').options.length <= 1) {
      const sel = document.getElementById('f-category');
      data.categories.forEach(c => {
        const o = document.createElement('option');
        o.value = c.category; o.textContent = c.category;
        sel.appendChild(o);
      });
      if (params.get('category')) sel.value = params.get('category');
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

  load().catch(err => resultsEl.innerHTML = emptyState('Could not load charities', err.message));
});
