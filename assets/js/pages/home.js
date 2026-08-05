/**
 * Homepage logic
 */
'use strict';

window.appReady.then(async () => {

  /* ---- sponsored strip (homepage header placement) ---- */
  const strip = document.getElementById('sponsoredStrip');
  if (strip) {
    try {
      const res = await api('api/ads.php?placement=homepage_header');
      const ads = (res && res.ads) || [];
      if (ads.length) {
        strip.innerHTML = ads.map(ad => { recordAdImpression(ad.id); return sponsoredBanner(ad); }).join('');
      }
    } catch (e) { /* no ads — fine */ }
  }

  /* ---- categories ---- */
  const chipsEl = document.getElementById('categoryChips');
  try {
    const cats = await api('api/categories.php?type=business');
    chipsEl.innerHTML = cats.map(c => `
      <a class="category-chip" href="businesses.html?category=${encodeURIComponent(c.slug)}">
        <span class="cat-icon">${icon(c.icon || 'grid', 22)}</span>
        <span>${esc(lang === 'ar' && c.name_ar ? c.name_ar : lang === 'sw' && c.name_sw ? c.name_sw : c.name)}</span>
      </a>`).join('');
  } catch (e) {
    chipsEl.innerHTML = emptyState('Could not load categories', e.message);
  }

  /* ---- featured businesses ---- */
  const bizEl = document.getElementById('featuredBusinesses');
  try {
    const featured = await api('api/businesses.php?featured=1');
    bizEl.innerHTML = featured.length
      ? featured.map(businessTile).join('')
      : emptyState('No featured businesses yet', 'Be the first to add one!', '<a class="btn btn-primary btn-sm" href="register.html">Add a business</a>');
  } catch (e) {
    bizEl.innerHTML = emptyState('Could not load businesses', e.message);
  }

  /* ---- top mosques ---- */
  const mosqueEl = document.getElementById('topMosques');
  try {
    const mosques = await api('api/mosques.php?top=1');
    mosqueEl.innerHTML = mosques.length
      ? mosques.map(mosqueTile).join('')
      : emptyState('No mosques listed yet', 'Check back soon.');
  } catch (e) {
    mosqueEl.innerHTML = emptyState('Could not load mosques', e.message);
  }

  /* ---- top fundis ---- */
  const fundiEl = document.getElementById('topFundis');
  try {
    const fundis = await api('api/fundis.php?top=1');
    fundiEl.innerHTML = fundis.length
      ? fundis.map(fundiTile).join('')
      : emptyState('No fundis listed yet', 'Check back soon.');
  } catch (e) {
    fundiEl.innerHTML = emptyState('Could not load fundis', e.message);
  }

  /* ---- active causes ---- */
  const charityEl = document.getElementById('topCharities');
  if (charityEl) {
    try {
      const charities = await api('api/charities.php?top=1');
      charityEl.innerHTML = charities.length
        ? charities.map(charityTile).join('')
        : emptyState('No causes yet', 'Check back soon.');
    } catch (e) {
      charityEl.innerHTML = emptyState('Could not load causes', e.message);
    }
  }

  /* ---- recent activity feed ---- */
  const activityEl = document.getElementById('recentActivity');
  if (activityEl) {
    try {
      const feed = await api('api/activity.php');
      activityEl.innerHTML = feed.length
        ? feed.map(activityItem).join('')
        : '<p class="muted center" style="padding:1.2rem 0;">No activity yet — be the first to write a review!</p>';
    } catch (e) {
      activityEl.innerHTML = '<p class="muted">Could not load activity.</p>';
    }
  }

  /* ---- search autocomplete ---- */
  wireAutocomplete(document.getElementById('hero-q'), document.getElementById('heroSuggest'));

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
