/**
 * Admin console — admin only
 * Approves listing claims and manages ads (list, create, toggle, delete).
 */
'use strict';

const PLACEMENTS = [
  [1, 'Homepage header', 'homepage_header'],
  [2, 'Homepage sidebar', 'homepage_sidebar'],
  [3, 'Search results', 'search_results'],
  [4, 'Listing page', 'listing_page'],
  [5, 'Detail page', 'detail_page'],
];

window.appReady.then(async () => {
  const wrap = document.getElementById('adminWrap');
  const claimsWrap = document.getElementById('claimsWrap');
  const claimsPanel = document.getElementById('claimsPanel');

  if (!session.user || session.user.user_type !== 'admin') {
    if (claimsPanel) claimsPanel.style.display = 'none';
    wrap.innerHTML = emptyState('Admins only', 'You need an admin account to manage listings and ads.',
      '<a class="btn btn-primary btn-sm" href="login">Login</a>');
    return;
  }
  if (claimsPanel) claimsPanel.style.display = '';

  wrap.innerHTML = `
    <div class="panel">
      <h2>Create ad</h2>
      <form id="adForm" class="filters" style="grid-template-columns:repeat(auto-fit,minmax(170px,1fr));">
        <div class="filter">
          <label>Title</label>
          <input name="title" required placeholder="Al-Barakah Restaurant">
        </div>
        <div class="filter">
          <label>Link URL</label>
          <input name="link_url" required placeholder="business?id=1">
        </div>
        <div class="filter">
          <label>Placement</label>
          <select name="placement_id">
            ${PLACEMENTS.map(([id, name]) => `<option value="${id}">${name}</option>`).join('')}
          </select>
        </div>
        <div class="filter">
          <label>Image path (optional)</label>
          <input name="image_path" placeholder="assets/img/sample/restaurant-1.svg">
        </div>
        <div class="filter">
          <label>Tagline</label>
          <input name="html_content" placeholder="Short one-line pitch">
        </div>
        <div class="filter">
          <label>Priority</label>
          <input name="priority" type="number" value="0">
        </div>
        <div class="filter">
          <label>Target category <span class="muted small">(optional)</span></label>
          <select name="target_category" id="adTargetCat">
            <option value="">Everywhere</option>
          </select>
        </div>
        <div class="filter">
          <label>Target city <span class="muted small">(optional)</span></label>
          <select name="target_city" id="adTargetCity">
            <option value="">Everywhere</option>
          </select>
        </div>
        <div class="filter">
          <button class="btn btn-primary btn-block" type="submit">Create ad</button>
        </div>
      </form>
      <div id="formAlert"></div>
    </div>

    <div class="panel">
      <h2>Ad performance & analytics</h2>
      <div style="display:flex;gap:1rem;flex-wrap:wrap;margin-bottom:1rem;align-items:center;">
        <div style="flex:1;min-width:180px;">
          <label style="font-size:0.85rem;font-weight:600;display:block;margin-bottom:0.2rem;">Select Campaign</label>
          <select id="analyticsAdSel" style="width:100%;padding:0.4rem;border-radius:6px;border:1px solid #ccc;">
            <option value="0">All Campaigns Overview</option>
          </select>
        </div>
        <div style="flex:1;min-width:180px;">
          <label style="font-size:0.85rem;font-weight:600;display:block;margin-bottom:0.2rem;">Timeframe</label>
          <select id="analyticsTimeframeSel" style="width:100%;padding:0.4rem;border-radius:6px;border:1px solid #ccc;">
            <option value="7d">Last 7 Days</option>
            <option value="30d">Last 30 Days</option>
            <option value="90d">Last 90 Days</option>
          </select>
        </div>
      </div>
      <div id="analyticsChartWrap" style="min-height:240px;"><p class="muted">Loading analytics chart…</p></div>
    </div>

    <div class="panel">
      <h2>All ads</h2>
      <div id="adList"><p class="muted">Loading…</p></div>
    </div>
  `;

  const alertBox = document.getElementById('formAlert');
  function formAlert(msg, type) {
    alertBox.innerHTML = msg ? `<div class="alert alert-${type}">${esc(msg)}</div>` : '';
  }

  async function loadAnalytics() {
    const adSel = document.getElementById('analyticsAdSel');
    const tfSel = document.getElementById('analyticsTimeframeSel');
    const wrap = document.getElementById('analyticsChartWrap');
    if (!wrap) return;

    const adId = adSel ? parseInt(adSel.value, 10) || 0 : 0;
    const tf = tfSel ? tfSel.value : '7d';

    try {
      const res = await api(`api/ads.php?action=analytics&ad_id=${adId}&timeframe=${tf}`);
      if (res && res.series && typeof renderAnalyticsChart === 'function') {
        renderAnalyticsChart(wrap, res.series);
      }
    } catch (err) {
      wrap.innerHTML = `<div class="muted center" style="padding:2rem;">${esc(err.message || 'Could not load analytics chart')}</div>`;
    }
  }

  /* ---- targeting & analytics options ---- */
  async function loadTargetingOptions() {
    const catSel = document.getElementById('adTargetCat');
    const citySel = document.getElementById('adTargetCity');
    try {
      const cats = await api('api/categories.php?type=business');
      if (catSel && Array.isArray(cats) && cats.length) {
        cats.forEach(c => {
          const o = document.createElement('option');
          o.value = c.slug; o.textContent = c.name;
          catSel.appendChild(o);
        });
      }
      const cities = await api('api/businesses.php?cities=1');
      if (citySel && Array.isArray(cities) && cities.length) {
        cities.forEach(c => {
          const o = document.createElement('option');
          o.value = c.city; o.textContent = c.city + ' (' + c.total + ')';
          citySel.appendChild(o);
        });
      }
    } catch (err) { /* ignore optional */ }
  }

  async function loadList() {
    const el = document.getElementById('adList');
    const adSel = document.getElementById('analyticsAdSel');
    try {
      const rows = await api('api/ads.php?action=list');
      if (!rows || !rows.length) {
        el.innerHTML = emptyState('No ads created', 'Use the form above to add your first sponsored listing.');
        if (adSel) adSel.innerHTML = '<option value="0">All Campaigns Overview</option>';
        loadAnalytics();
        return;
      }

      if (adSel) {
        const curVal = adSel.value;
        adSel.innerHTML = '<option value="0">All Campaigns Overview</option>' +
          rows.map(a => `<option value="${a.id}">${esc(a.title)} (${esc(a.placement_name)})</option>`).join('');
        if (curVal && adSel.querySelector(`option[value="${curVal}"]`)) {
          adSel.value = curVal;
        }
      }

      el.innerHTML = `
        <div class="table-responsive">
          <table class="table">
            <thead>
              <tr>
                <th>Ad</th>
                <th>Placement</th>
                <th>Targeting</th>
                <th>Impressions</th>
                <th>Clicks</th>
                <th>CTR</th>
                <th>Status</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              ${rows.map(a => {
                const ctr = a.impressions > 0 ? (a.clicks / a.impressions * 100).toFixed(1) + '%' : '0%';
                const targeting = [];
                if (a.target_category) targeting.push('Category: ' + esc(a.target_category));
                if (a.target_city) targeting.push('City: ' + esc(a.target_city));
                const targetText = targeting.length ? targeting.join(', ') : 'Everywhere';
                return `
                  <tr>
                    <td>
                      <strong>${esc(a.title)}</strong><br>
                      <a href="${esc(a.link_url)}" target="_blank" rel="noopener" class="small muted">${esc(a.link_url)}</a>
                    </td>
                    <td><span class="badge">${esc(a.placement_name)}</span></td>
                    <td><span class="small muted">${targetText}</span></td>
                    <td>${(a.impressions || 0).toLocaleString()}</td>
                    <td>${(a.clicks || 0).toLocaleString()}</td>
                    <td><b>${ctr}</b></td>
                    <td><span class="badge ${a.status === 'active' ? 'badge-open' : ''}">${esc(a.status)}</span></td>
                    <td>
                      <button class="btn btn-sm ${a.status === 'active' ? 'btn-ghost' : 'btn-primary'}" data-toggle-ad="${a.id}" data-status="${a.status === 'active' ? 'paused' : 'active'}">
                        ${a.status === 'active' ? 'Pause' : 'Activate'}
                      </button>
                      <button class="btn btn-sm btn-danger" data-delete-ad="${a.id}">Delete</button>
                    </td>
                  </tr>`;
              }).join('')}
            </tbody>
          </table>
        </div>`;
      
      loadAnalytics();
    } catch (err) {
      el.innerHTML = `<div class="alert alert-error">${esc(err.message)}</div>`;
    }
  }

  // Listeners for analytics controls
  document.getElementById('analyticsAdSel')?.addEventListener('change', loadAnalytics);
  document.getElementById('analyticsTimeframeSel')?.addEventListener('change', loadAnalytics);

  /* ---- claim requests ---- */
  async function loadClaims() {
    const el = document.getElementById('claimsWrap');
    if (!el) return;
    try {
      const claims = await api('api/businesses.php?action=claims');
      if (!claims.length) {
        el.innerHTML = '<p class="muted center" style="padding:1rem 0;">No pending claim requests. 🎉</p>';
        return;
      }
      el.innerHTML = claims.map(c => `
        <div class="ad-admin-row">
          <div class="ad-admin-info">
            <b>${esc(c.business_name)}</b>
            <span class="muted small">${esc(c.city || '')} · requested by ${esc(c.claimant_name)} (${esc(c.claimant_email)})</span>
            ${c.proof ? `<span class="muted small">Proof: ${esc(c.proof)}</span>` : ''}
            <span class="muted small">${timeAgo(c.created_at)}</span>
          </div>
          <div class="ad-admin-actions">
            <button class="btn btn-primary btn-xs" data-approve-claim="${esc(c.id)}">Approve</button>
            <button class="btn btn-outline btn-xs" data-reject-claim="${esc(c.id)}">Reject</button>
          </div>
        </div>`).join('');
    } catch (e) {
      el.innerHTML = '<p class="muted">Could not load claims: ' + esc(e.message) + '</p>';
    }
  }

  async function reviewClaim(claimId, decision) {
    let notes = '';
    if (decision === 'reject') {
      const reason = window.prompt('Reason for the claimant (optional):');
      if (reason === null) return false;
      notes = reason.trim();
    }
    await api('api/businesses.php', { method: 'POST', body: { action: 'review_claim', claim_id: claimId, decision, admin_notes: notes } });
    return true;
  }

  /* ---- create ---- */
  document.getElementById('adForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    formAlert('', 'success');
    const fd = new FormData(e.target);
    const body = {};
    for (const [k, v] of fd.entries()) if (String(v).trim() !== '') body[k] = v;
    body.placement_id = Number(body.placement_id || 0);
    body.priority = Number(body.priority || 0);
    try {
      await api('api/ads.php?action=create', { method: 'POST', body });
      formAlert('Ad created ✓', 'success');
      e.target.reset();
      loadList();
    } catch (err) {
      formAlert(err.message || 'Could not create ad', 'error');
    }
  });

  /* ---- claim approve / reject ---- */
  document.addEventListener('click', async (ev) => {
    const approve = ev.target.closest('[data-approve-claim]');
    if (approve) {
      approve.disabled = true;
      try {
        if (await reviewClaim(approve.dataset.approveClaim, 'approve')) {
          toast('Claim approved — owner controls granted');
          loadClaims();
        }
      } catch (err) { toast(err.message || 'Could not approve claim', 'error'); loadClaims(); }
      return;
    }
    const reject = ev.target.closest('[data-reject-claim]');
    if (reject) {
      reject.disabled = true;
      try {
        if (await reviewClaim(reject.dataset.rejectClaim, 'reject')) {
          toast('Claim rejected');
          loadClaims();
        } else {
          reject.disabled = false;
        }
      } catch (err) { toast(err.message || 'Could not reject claim', 'error'); loadClaims(); }
      return;
    }
    const toggle = ev.target.closest('[data-toggle-ad]');
    if (toggle) {
      try {
        await api('api/ads.php?action=toggle', { method: 'POST', body: { ad_id: toggle.dataset.toggleAd, status: toggle.dataset.status } });
        loadList();
      } catch (err) { formAlert(err.message, 'error'); }
      return;
    }
    const del = ev.target.closest('[data-delete-ad]');
    if (del) {
      if (!confirm('Delete this ad?')) return;
      try {
        await api('api/ads.php?action=delete', { method: 'POST', body: { ad_id: del.dataset.deleteAd } });
        loadList();
      } catch (err) { formAlert(err.message, 'error'); }
    }
  });

  loadTargetingOptions();
  loadClaims();
  loadList();
  loadAnalytics();
});
