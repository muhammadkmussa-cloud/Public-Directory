/**
 * Business detail page
 * Reads ?id= from the URL, loads the business from the API.
 */
'use strict';

const modal = document.getElementById('reviewModal');

function openModal(type, id) {
  if (!session.user) {
    toast('Please login to write a review', 'error');
    setTimeout(() => (window.location.href = 'login.html'), 700);
    return;
  }
  document.getElementById('rvType').value = type;
  document.getElementById('rvId').value = id;
  modal.classList.add('open');
}

function closeModal() { modal.classList.remove('open'); }

window.appReady.then(async () => {
  const id = new URLSearchParams(window.location.search).get('id');
  if (!id) { window.location.href = 'businesses.html'; return; }

  const el = document.getElementById('businessDetail');
  el.innerHTML = '<div class="panel">Loading business…</div>';

  try {
    const { business, photos, reviews, rating_breakdown, similar } = await api('api/businesses.php?id=' + id);
    renderBusiness(business, photos, reviews, rating_breakdown);
    renderSimilar(similar);

    // check-in button
    const checkinBtn = document.getElementById('checkinBtn');
    if (checkinBtn) {
      checkinBtn.addEventListener('click', async () => {
        if (!session.user) { toast('Please login to check in', 'error'); return; }
        try {
          const data = await api('api/checkin.php', { method: 'POST', body: { checkinable_id: business.id, checkinable_type: 'business' } });
          toast('Checked in! (' + data.checkin_count + ' check-ins)');
          checkinBtn.disabled = true;
        } catch (e) { toast(e.message || 'Could not check in', 'error'); }
      });
    }

    // review form submit
    document.getElementById('reviewForm').addEventListener('submit', async (e) => {
      e.preventDefault();
      const btn = document.getElementById('reviewSubmit');
      btn.disabled = true;
      try {
        await api('api/reviews.php', {
          method: 'POST',
          body: {
            action: 'create',
            reviewable_type: document.getElementById('rvType').value,
            reviewable_id: document.getElementById('rvId').value,
            rating: document.querySelector('input[name="rating"]:checked')?.value,
            title: document.getElementById('rvTitle').value,
            content: document.getElementById('rvContent').value,
          },
        });
        toast('Review submitted! Thank you.');
        closeModal();
        setTimeout(() => window.location.reload(), 900);
      } catch (err) {
        toast(err.message || 'Could not submit review', 'error');
        btn.disabled = false;
      }
    });

  } catch (e) {
    el.innerHTML = emptyState('Business not found', e.message, '<a class="btn btn-primary btn-sm" href="businesses.html">Browse businesses</a>');
  }

  /* ---------- render helpers ---------- */
  function renderBusiness(b, photos, reviews, breakdown) {
    const hours = b.opening_hours || {};
    const today = new Date().toLocaleDateString('en-GB', { weekday: 'long' }).toLowerCase();
    const days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
    const total = Object.values(breakdown || {}).reduce((s, v) => s + v, 0);

    const amenities = (b.amenities || []).map(a => `<span class="amenity">✓ ${esc(a)}</span>`).join('');

    el.innerHTML = `
      <div class="detail-top">
        <div class="rating-row">
          ${starsHtml(b.rating_average)}
          <span class="rating-num">${Number(b.rating_average || 0).toFixed(1)}</span>
          <span class="muted">(${b.review_count || 0} reviews)</span>
          ${b.is_verified ? '<span class="chip">✓ Verified</span>' : ''}
          ${b.category_name ? `<span class="chip">${esc(b.category_name)}</span>` : ''}
          <span class="price">${esc(b.price_range || '$')}</span>
        </div>
        <h1 class="detail-title">${esc(b.name)}</h1>
        <p class="muted">${icon('pin', 13)} ${esc(b.address || '')}${b.city ? ', ' + esc(b.city) : ''}${b.region ? ', ' + esc(b.region) : ''}</p>
        <div class="detail-actions">
          ${b.phone ? `<a class="btn btn-primary" href="tel:${esc(b.phone)}">${icon('phone', 14)} Call</a>` : ''}
          ${b.whatsapp ? `<a class="btn btn-outline" href="https://wa.me/${esc(b.whatsapp)}" target="_blank" rel="noopener">${icon('wa', 14)} WhatsApp</a>` : ''}
          ${b.website ? `<a class="btn btn-outline" href="${esc(b.website)}" target="_blank" rel="noopener">${icon('globe', 14)} Website</a>` : ''}
          ${b.latitude ? `<a class="btn btn-outline" href="https://www.google.com/maps?q=${b.latitude},${b.longitude}" target="_blank" rel="noopener">${icon('nav', 14)} Directions</a>` : ''}
          <button class="btn btn-ghost" id="checkinBtn">${icon('pin', 14)} Check in</button>
        </div>
      </div>

      ${(photos || []).length ? `
        <div class="gallery">
          <div class="gallery-main"><img src="${esc(photos[0].photo_path)}" alt="${esc(b.name)}"></div>
          <div class="gallery-side">
            ${(photos.slice(1, 3)).map(p => `<img src="${esc(p.photo_path)}" alt="">`).join('')}
          </div>
        </div>` : ''}

      <div class="detail-grid">
        <div>
          <section class="panel">
            <h2>About</h2>
            <p class="about-text">${esc(b.description || 'No description yet.')}</p>
            ${amenities ? `<h3 style="margin-top:1.2rem;">Features & amenities</h3><div class="amenity-list">${amenities}</div>` : ''}
          </section>

          <section class="panel">
            <h2>Reviews (${reviews.length})</h2>
            <div class="rating-breakdown">
              ${[5, 4, 3, 2, 1].map(n => {
                const c = (breakdown || {})[n] || 0;
                const pct = total ? Math.round((c / total) * 100) : 0;
                return `<div class="break-row"><span>${n}★</span><div class="break-bar"><i style="width:${pct}%"></i></div><span class="break-count">${c}</span></div>`;
              }).join('')}
            </div>
            <div style="margin:1.2rem 0 0;">
              <button class="btn btn-primary" id="writeReviewBtn">${icon('pen', 14)} Write a review</button>
            </div>
            <div id="reviewsList">
              ${reviews.length ? reviews.map(reviewCard).join('') : '<p class="muted center" style="padding:1.5rem 0;">No reviews yet — be the first!</p>'}
            </div>
          </section>
        </div>

        <aside>
          <div class="side-card">
            <h3>Contact</h3>
            ${b.phone ? `<div class="side-row"><span class="lbl">Phone</span><a href="tel:${esc(b.phone)}">${esc(b.phone)}</a></div>` : ''}
            ${b.whatsapp ? `<div class="side-row"><span class="lbl">WhatsApp</span><a href="https://wa.me/${esc(b.whatsapp)}" target="_blank" rel="noopener">Chat</a></div>` : ''}
            ${b.email ? `<div class="side-row"><span class="lbl">Email</span><a href="mailto:${esc(b.email)}">${esc(b.email)}</a></div>` : ''}
            ${b.website ? `<div class="side-row"><span class="lbl">Website</span><a href="${esc(b.website)}" target="_blank" rel="noopener">Visit</a></div>` : ''}
          </div>

          <div class="side-card">
            <h3>Opening hours</h3>
            <table class="hours-table">
              ${days.map(d => `
                <tr class="${d === today ? 'today' : ''}">
                  <td>${esc(d.charAt(0).toUpperCase() + d.slice(1))}</td>
                  <td>${esc(hours[d] || 'Closed')}</td>
                </tr>`).join('')}
            </table>
          </div>

          ${b.checkin_count ? `<div class="side-card center"><b style="font-size:1.8rem;color:var(--primary-dark);">${b.checkin_count}</b><br><span class="muted small">check-ins</span></div>` : ''}
        </aside>
      </div>
    `;

    document.getElementById('writeReviewBtn').addEventListener('click', () => openModal('business', b.id));
  }

  function renderSimilar(list) {
    if (!list || !list.length) return;
    const wrap = document.createElement('section');
    wrap.className = 'section';
    wrap.innerHTML = `
      <div class="section-head"><div><h2>Similar businesses</h2></div><a class="btn btn-outline btn-sm" href="businesses.html">View all</a></div>
      <div class="grid">${list.map(businessCard).join('')}</div>`;
    el.appendChild(wrap);
  }
});

/* modal close handlers */
document.addEventListener('click', e => {
  if (e.target === modal) closeModal();
  if (e.target.closest('[data-close-modal]')) closeModal();
});
document.addEventListener('keydown', e => { if (e.key === 'Escape') closeModal(); });
