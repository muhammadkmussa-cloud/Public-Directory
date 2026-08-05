/**
 * Homepage logic
 */
'use strict';

window.appReady.then(async () => {

  /* ---- categories ---- */
  const chipsEl = document.getElementById('categoryChips');
  try {
    const cats = await api('api/categories.php?type=business');
    chipsEl.innerHTML = cats.map(c => `
      <a class="category-chip" href="businesses.html?category=${encodeURIComponent(c.slug)}">
        <span class="cat-icon">${esc(c.icon || '🏷️')}</span>
        <span>${esc(c.name)}</span>
      </a>`).join('');
  } catch (e) {
    chipsEl.innerHTML = emptyState('Could not load categories', e.message);
  }

  /* ---- featured businesses ---- */
  const bizEl = document.getElementById('featuredBusinesses');
  try {
    const featured = await api('api/businesses.php?featured=1');
    bizEl.innerHTML = featured.length
      ? featured.map(businessCard).join('')
      : emptyState('No featured businesses yet', 'Be the first to add one!', '<a class="btn btn-primary btn-sm" href="register.html">Add a business</a>');
  } catch (e) {
    bizEl.innerHTML = emptyState('Could not load businesses', e.message);
  }

  /* ---- top mosques ---- */
  const mosqueEl = document.getElementById('topMosques');
  try {
    const mosques = await api('api/mosques.php?top=1');
    mosqueEl.innerHTML = mosques.length
      ? mosques.map(mosqueCard).join('')
      : emptyState('No mosques listed yet', 'Check back soon.');
  } catch (e) {
    mosqueEl.innerHTML = emptyState('Could not load mosques', e.message);
  }

  /* ---- top fundis ---- */
  const fundiEl = document.getElementById('topFundis');
  try {
    const fundis = await api('api/fundis.php?top=1');
    fundiEl.innerHTML = fundis.length
      ? fundis.map(fundiCard).join('')
      : emptyState('No fundis listed yet', 'Check back soon.');
  } catch (e) {
    fundiEl.innerHTML = emptyState('Could not load fundis', e.message);
  }

  /* ---- stats (best-effort from list endpoints) ---- */
  try {
    const [biz, mosques, fundis] = await Promise.all([
      api('api/businesses.php'),
      api('api/mosques.php'),
      api('api/fundis.php'),
    ]);
    const set = (id, v) => { const el = document.getElementById(id); if (el) el.textContent = v; };
    set('statBiz', biz.total.toLocaleString());
    set('statMosque', mosques.total.toLocaleString());
    set('statFundi', fundis.total.toLocaleString());
    set('statReviews', (biz.items.reduce((s, x) => s + (x.review_count || 0), 0)).toLocaleString());
  } catch (e) { /* stats stay as placeholders */ }
});
