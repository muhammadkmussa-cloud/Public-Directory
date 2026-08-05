/**
 * Owner dashboard — claim listings, edit, respond to reviews
 * Requires login. Owners see their listings; admins see all.
 */
'use strict';

window.appReady.then(async () => {
  const wrap = document.getElementById('dashWrap');

  if (!session.user) {
    wrap.innerHTML = emptyState('Please login first', 'You need an account to manage listings.',
      '<a class="btn btn-primary btn-sm" href="login.html">Login</a>');
    return;
  }

  wrap.innerHTML = `
    <div class="panel">
      <h2>My listings</h2>
      <div id="myListings"><p class="muted">Loading…</p></div>
    </div>
    <div class="panel">
      <h2>Respond to reviews</h2>
      <p class="muted small" style="margin-bottom:.8rem;">Reviews on your listings that don't have a response yet.</p>
      <div id="respondList"><p class="muted">Loading…</p></div>
    </div>
    <div class="panel">
      <h2>Claim a listing</h2>
      <p class="muted small" style="margin-bottom:.8rem;">Own this business? Claim it to get owner controls.</p>
      <div id="claimList"><p class="muted">Loading…</p></div>
    </div>
  `;

  function alert(elId, msg, type) {
    const box = document.getElementById(elId);
    if (box) box.innerHTML = msg ? `<div class="alert alert-${type}">${esc(msg)}</div>` : '';
  }

  /* ================= my listings ================= */
  async function loadListings() {
    const el = document.getElementById('myListings');
    try {
      const rows = await api('api/businesses.php?action=mine');
      if (!rows.length) {
        el.innerHTML = '<p class="muted center" style="padding:1rem 0;">No listings yet — claim one below.</p>';
        return;
      }
      el.innerHTML = rows.map(b => `
        <div class="dash-listing">
          <div class="dash-listing-info">
            <b>${esc(b.name)}</b>
            <span class="muted small">${esc(b.city || '')} · ${esc(b.price_range || '$')}${b.is_claimed ? ' · Claimed' : ' · Not claimed'}</span>
            <span class="muted small">⭐ ${Number(b.rating_average || 0).toFixed(1)} (${b.review_count || 0} reviews) · 📍 ${b.checkin_count || 0} check-ins${b.pending_responses ? ' · <b class="red-text">' + b.pending_responses + ' responses pending</b>' : ''}</span>
          </div>
          <div class="dash-listing-actions">
            <a class="btn btn-outline btn-xs" href="business.html?id=${b.id}">View</a>
            <button class="btn btn-outline btn-xs" data-edit-biz="${b.id}">Edit</button>
            ${b.pending_responses ? `<button class="btn btn-primary btn-xs" data-respond-biz="${b.id}">Respond (${b.pending_responses})</button>` : ''}
          </div>
        </div>`).join('');
    } catch (e) {
      el.innerHTML = '<p class="muted">Could not load listings: ' + esc(e.message) + '</p>';
    }
  }

  /* ================= edit form ================= */
  function showEditForm(b) {
    const panel = document.createElement('div');
    panel.className = 'panel';
    panel.id = 'editPanel';
    panel.innerHTML = `
      <h2>Edit: ${esc(b.name)}</h2>
      <form id="editForm" class="filters" style="grid-template-columns:repeat(auto-fit,minmax(200px,1fr));">
        <div class="filter"><label>Name</label><input name="name" value="${esc(b.name)}" required></div>
        <div class="filter"><label>Phone</label><input name="phone" value="${esc(b.phone || '')}"></div>
        <div class="filter"><label>WhatsApp</label><input name="whatsapp" value="${esc(b.whatsapp || '')}"></div>
        <div class="filter"><label>Email</label><input name="email" value="${esc(b.email || '')}"></div>
        <div class="filter"><label>Website</label><input name="website" value="${esc(b.website || '')}" placeholder="https://…"></div>
        <div class="filter"><label>City</label><input name="city" value="${esc(b.city || '')}"></div>
        <div class="filter"><label>Price</label>
          <select name="price_range">
            ${['$', '$$', '$$$', '$$$$'].map(p => `<option value="${p}" ${b.price_range === p ? 'selected' : ''}>${p}</option>`).join('')}
          </select>
        </div>
        <div class="filter" style="grid-column:1/-1;"><label>Short description</label><input name="short_description" value="${esc(b.short_description || '')}"></div>
        <div class="filter" style="grid-column:1/-1;"><label>Full description</label><textarea name="description" rows="4" style="width:100%;padding:.6rem .8rem;border:1.5px solid var(--border-2);border-radius:5px;font:inherit;">${esc(b.description || '')}</textarea></div>
        <div class="filter" style="grid-column:1/-1;">
          <button class="btn btn-primary" type="submit">Save changes</button>
          <button class="btn btn-ghost" type="button" id="cancelEdit">Cancel</button>
        </div>
      </form>
      <div id="editAlert"></div>`;
    const myListingsPanel = document.getElementById('myListings').closest('.panel');
    myListingsPanel.after(panel);
    panel.scrollIntoView({ behavior: 'smooth', block: 'start' });

    panel.querySelector('#cancelEdit').addEventListener('click', () => panel.remove());
    panel.querySelector('#editForm').addEventListener('submit', async (e) => {
      e.preventDefault();
      const fd = new FormData(e.target);
      const body = { action: 'update', business_id: b.id };
      for (const [k, v] of fd.entries()) if (String(v).trim() !== '') body[k] = v;
      try {
        await api('api/businesses.php', { method: 'POST', body });
        alert('editAlert', 'Saved ✓', 'success');
        panel.remove();
        loadListings();
      } catch (err) {
        alert('editAlert', err.message || 'Could not save', 'error');
      }
    });
  }

  document.addEventListener('click', async (e) => {
    const editBtn = e.target.closest('[data-edit-biz]');
    if (editBtn) {
      const id = editBtn.dataset.editBiz;
      const b = await api('api/businesses.php?id=' + id).then(d => d.business).catch(() => null);
      if (b) showEditForm(b);
      return;
    }

    const respBtn = e.target.closest('[data-respond-biz]');
    if (respBtn) {
      loadResponses(Number(respBtn.dataset.respondBiz));
      return;
    }

    const claimBtn = e.target.closest('[data-claim-biz]');
    if (claimBtn) {
      const id = claimBtn.dataset.claimBiz;
      try {
        await api('api/businesses.php', { method: 'POST', body: { action: 'claim', business_id: id } });
        toast('Listing claimed! 🎉');
        loadListings(); loadClaimable();
      } catch (err) { toast(err.message || 'Could not claim', 'error'); }
      return;
    }

    const respondBtn = e.target.closest('[data-send-response]');
    if (respondBtn) {
      const reviewId = respondBtn.dataset.sendResponse;
      const text = document.getElementById('resp_' + reviewId).value.trim();
      if (text.length < 2) { toast('Response too short', 'error'); return; }
      try {
        await api('api/businesses.php', { method: 'POST', body: { action: 'respond', review_id: reviewId, response: text } });
        toast('Response published ✓');
        loadResponses();
      } catch (err) { toast(err.message || 'Could not respond', 'error'); }
      return;
    }
  });

  /* ================= respond to reviews ================= */
  async function loadResponses(forBizId) {
    const el = document.getElementById('respondList');
    try {
      const rows = await api('api/businesses.php?action=mine');
      let reviews = [];
      for (const b of rows) {
        if (forBizId && b.id !== forBizId) continue;
        const det = await api('api/businesses.php?id=' + b.id).catch(() => null);
        if (!det) continue;
        (det.reviews || []).forEach(r => {
          if (!r.owner_response) reviews.push({ ...r, biz_id: b.id, biz_name: b.name });
        });
      }
      if (!reviews.length) {
        el.innerHTML = '<p class="muted center" style="padding:1rem 0;">No unreviewed responses needed — you\'re all caught up! 🎉</p>';
        return;
      }
      el.innerHTML = reviews.map(r => `
        <div class="respond-row">
          <div>
            <b>${esc(r.biz_name)}</b> · <span class="stars">${starsHtml(r.rating)}</span>
            <span class="muted small"> by ${esc(r.user.full_name || 'User')} · ${timeAgo(r.created_at)}</span>
            <p class="muted small" style="margin-top:.2rem;">"${esc((r.content || '').slice(0, 120))}${(r.content || '').length > 120 ? '…' : ''}"</p>
          </div>
          <textarea id="resp_${r.id}" rows="2" placeholder="Write your response…" style="width:100%;padding:.5rem .7rem;border:1.5px solid var(--border-2);border-radius:5px;font:inherit;margin-top:.4rem;"></textarea>
          <button class="btn btn-primary btn-sm" data-send-response="${r.id}">Post response</button>
        </div>`).join('');
    } catch (e) {
      el.innerHTML = '<p class="muted">Could not load reviews: ' + esc(e.message) + '</p>';
    }
  }

  /* ================= claim a listing ================= */
  async function loadClaimable() {
    const el = document.getElementById('claimList');
    try {
      const all = await api('api/businesses.php');
      const mine = await api('api/businesses.php?action=mine');
      const mineIds = new Set(mine.map(b => b.id));
      const claimable = all.items.filter(b => !mineIds.has(b.id) && !b.is_claimed);
      if (!claimable.length) {
        el.innerHTML = '<p class="muted center" style="padding:1rem 0;">No unclaimed listings available.</p>';
        return;
      }
      el.innerHTML = claimable.slice(0, 10).map(b => `
        <div class="dash-listing">
          <div class="dash-listing-info">
            <b>${esc(b.name)}</b>
            <span class="muted small">${esc(b.city || '')} · ${esc(b.price_range || '$')} · ⭐ ${Number(b.rating_average || 0).toFixed(1)} (${b.review_count || 0})</span>
          </div>
          <button class="btn btn-primary btn-xs" data-claim-biz="${b.id}">Claim</button>
        </div>`).join('');
    } catch (e) {
      el.innerHTML = '<p class="muted">Could not load listings: ' + esc(e.message) + '</p>';
    }
  }

  loadListings();
  loadResponses();
  loadClaimable();
});
