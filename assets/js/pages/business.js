/**
 * Business detail page
 * Reads ?id= from the URL, loads the business from the API.
 */
'use strict';

const modal = document.getElementById('reviewModal');

function openModal(type, id) {
  if (!session.user) {
    toast('Please login to write a review', 'error');
    setTimeout(() => (window.location.href = 'login'), 700);
    return;
  }
  document.getElementById('rvType').value = type;
  document.getElementById('rvId').value = id;
  modal.classList.add('open');
}

function closeModal() { if (modal) modal.classList.remove('open'); }

window.appReady.then(async () => {
  const id = new URLSearchParams(window.location.search).get('id');
  if (!id) { window.location.href = 'businesses'; return; }

  const visitDateInput = document.getElementById('rvVisitDate');
  if (visitDateInput) visitDateInput.max = new Date().toISOString().split('T')[0];

  const el = document.getElementById('businessDetail');
  if (!el) {
    /* ---- SSR page: content is server-rendered; wire the interactive bits ---- */
    const mapBox = document.getElementById('bizMap');
    if (mapBox && mapBox.dataset.lat && typeof initDetailMap === 'function') {
      initDetailMap('bizMap', mapBox.dataset.lat, mapBox.dataset.lng, mapBox.dataset.name, '/business/' + id);
    }
    loadSponsoredSide({ category: mapBox && mapBox.dataset.category, city: mapBox && mapBox.dataset.city });
    const checkinBtn = document.getElementById('checkinBtn');
    if (checkinBtn) {
      checkinBtn.addEventListener('click', async () => {
        if (!session.user) { toast('Please login to check in', 'error'); return; }
        try {
          const data = await api('api/checkin.php', { method: 'POST', body: { checkinable_id: id, checkinable_type: 'business' } });
          toast('Checked in! (' + data.checkin_count + ' check-ins)');
          checkinBtn.disabled = true;
        } catch (e) { toast(e.message || 'Could not check in', 'error'); }
      });
    }
    const writeReviewBtn = document.getElementById('writeReviewBtn');
    if (writeReviewBtn) {
      writeReviewBtn.addEventListener('click', () => { window.location.href = 'business?id=' + id; });
    }
    loadBusinessMenu(id);
    return;
  }

  el.innerHTML = '<div class="panel">Loading business…</div>';

  try {
    const { business, photos, reviews, rating_breakdown, similar } = await api('api/businesses.php?id=' + id);
    if (window.setOgMeta) setOgMeta({
      title: business.name,
      description: (business.short_description || business.description || '') + ' — ' + business.city,
      image: photos && photos[0] ? photos[0].photo_path : '',
    });
    renderBusiness(business, photos, reviews, rating_breakdown);
    renderSimilar(similar);
    loadBusinessMenu(business.id, business.user_id);
    loadSponsoredSide({ category: business.category_slug, city: business.city });
    initDetailMap('bizMap', business.latitude, business.longitude, business.name, 'business?id=' + business.id);
    refreshSaveState('business', business.id);
    wirePhotoInput();
  /* ---- SEO: inject JSON-LD structured data ---- */
  (() => {
    try {
      const ld = {
        '@context': 'https://schema.org',
        '@type': 'LocalBusiness',
        'name': business.name,
        'image': (photos && photos[0] && photos[0].photo_path) || '',
        'telephone': business.phone || '',
        'address': {
          '@type': 'PostalAddress',
          'streetAddress': business.address || '',
          'addressLocality': business.city || '',
          'addressRegion': business.region || '',
          'addressCountry': business.country || 'KE',
        },
        'aggregateRating': business.rating_average ? {
          '@type': 'AggregateRating',
          'ratingValue': business.rating_average,
          'reviewCount': business.review_count || 0,
        } : undefined,
        'url': window.location.href,
        'priceRange': business.price_range || '$$',
      };
      if (!document.querySelector('script[type="application/ld+json"]')) {
        const script = document.createElement('script');
        script.type = 'application/ld+json';
        script.textContent = JSON.stringify(ld);
        document.head.appendChild(script);
      }
    } catch (e) { /* ignore */ }
  })();

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
        const photoPaths = await uploadReviewPhotos(document.getElementById('rvPhotos').files);
        await api('api/reviews.php', {
          method: 'POST',
          body: {
            action: 'create',
            reviewable_type: document.getElementById('rvType').value,
            reviewable_id: document.getElementById('rvId').value,
            rating: document.querySelector('input[name="rating"]:checked')?.value,
            rating_service: document.querySelector('select[name="rating_service"]')?.value || null,
            rating_value: document.querySelector('select[name="rating_value"]')?.value || null,
            rating_ambience: document.querySelector('select[name="rating_ambience"]')?.value || null,
            rating_cleanliness: document.querySelector('select[name="rating_cleanliness"]')?.value || null,
            title: document.getElementById('rvTitle').value,
            content: document.getElementById('rvContent').value,
            visit_date: document.getElementById('rvVisitDate').value || null,
            photo_paths: photoPaths,
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
    el.innerHTML = emptyState('Business not found', e.message, '<a class="btn btn-primary btn-sm" href="businesses">Browse businesses</a>');
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
          <button class="btn btn-ghost save-btn" data-save-type="business" data-save-id="${b.id}">${icon('bookmark', 14)} Save</button>
          <button class="btn btn-ghost" id="checkinBtn">${icon('pin', 14)} Check in</button>
          <button class="btn btn-ghost share-btn" data-share-title="${esc(b.name)}" data-share-url="business?id=${b.id}" data-share-text="${esc(b.short_description || b.name)}">${icon('share', 14)} Share</button>
          <button class="btn btn-ghost report-btn" data-report-type="business" data-report-id="${b.id}">${icon('flag', 14)} Report</button>
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

          ${b.checkin_count ? `<div class="side-card center"><b style="font-size:1.8rem;color:var(--brand);">${b.checkin_count}</b><br><span class="muted small">check-ins</span></div>` : ''}
          <div class="side-card">
            <h3>Location</h3>
            <div id="bizMap" class="map-box" style="height:200px;margin-bottom:.6rem;"></div>
            <div class="side-row"><span class="lbl">Address</span><span id="bizAddress">${esc(b.address || '')}${b.city ? ', ' + esc(b.city) : ''}</span></div>
          </div>
          <div class="side-card" id="sponsoredSide"></div>
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
      <div class="section-head"><div><h2>Similar businesses</h2></div><a class="btn btn-outline btn-sm" href="businesses">View all</a></div>
      <div class="grid">${list.map(businessCard).join('')}</div>`;
    el.appendChild(wrap);
  }
});

async function loadBusinessMenu(businessId, ownerUserId) {
  const section = document.getElementById('menuSection');
  const container = document.getElementById('menuContainer');
  const manageBtn = document.getElementById('manageMenuBtn');
  if (!section || !container) return;

  ownerUserId = ownerUserId || (section.dataset ? section.dataset.ownerId : null);
  try {
    const res = await api(`api/business_menu.php?business_id=${businessId}`);
    const isOwnerOrAdmin = session.user && (session.user.user_type === 'admin' || (ownerUserId && session.user.id == ownerUserId));

    if (manageBtn && isOwnerOrAdmin) {
      manageBtn.style.display = 'inline-block';
      manageBtn.onclick = () => {
        const name = prompt('Item Name:');
        if (!name) return;
        const cat = prompt('Category (e.g. Starters, Main, Drinks):', 'Main') || 'Main';
        const priceStr = prompt('Price (KSh):', '500');
        if (!priceStr) return;
        const price = parseFloat(priceStr) || 0;
        const desc = prompt('Short Description (optional):', '') || '';

        api('api/business_menu.php?action=create', {
          method: 'POST',
          body: { business_id: businessId, name, category: cat, price, description: desc }
        }).then(() => {
          toast('Menu item added!');
          loadBusinessMenu(businessId, ownerUserId);
        }).catch(err => toast(err.message || 'Could not add item', 'error'));
      };
    }

    if (!res || !res.categories || !res.categories.length) {
      if (isOwnerOrAdmin) {
        section.style.display = 'block';
        container.innerHTML = `
          <div class="muted center" style="padding:1.5rem 0;">
            No menu items added yet. Click <strong>Manage Menu</strong> to add your first dish.
          </div>`;
      } else {
        section.style.display = 'none';
      }
      return;
    }

    section.style.display = 'block';
    let html = '';

    res.categories.forEach(cat => {
      html += `
        <div class="menu-category" style="margin-bottom:1.5rem;">
          <h3 style="border-bottom:2px solid var(--brand);padding-bottom:0.4rem;margin-bottom:1rem;color:var(--text);font-size:1.1rem;">
            ${esc(cat.name)}
          </h3>
          <div class="menu-grid" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(260px,1fr));gap:1rem;">
            ${cat.items.map(item => `
              <div class="menu-card" style="background:#f9f9f9;border:1px solid #eee;border-radius:8px;padding:0.9rem;display:flex;flex-direction:column;justify-content:space-between;position:relative;">
                <div>
                  ${item.photo_url ? `<img src="${esc(item.photo_url)}" alt="${esc(item.name)}" style="width:100%;height:130px;object-fit:cover;border-radius:6px;margin-bottom:0.6rem;">` : ''}
                  <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:0.5rem;margin-bottom:0.3rem;">
                    <h4 style="margin:0;font-size:1rem;font-weight:700;">${esc(item.name)}</h4>
                    <span style="font-weight:700;color:var(--brand);font-size:1.05rem;white-space:nowrap;">KSh ${Number(item.price).toLocaleString()}</span>
                  </div>
                  ${item.description ? `<p class="muted small" style="margin:0.2rem 0 0.5rem;">${esc(item.description)}</p>` : ''}
                </div>
                <div style="display:flex;gap:0.4rem;flex-wrap:wrap;margin-top:0.4rem;">
                  ${item.is_popular ? '<span class="badge" style="background:#fff3cd;color:#856404;border:1px solid #ffeeba;font-size:0.75rem;">🔥 Popular</span>' : ''}
                  ${item.is_halal_certified ? '<span class="badge" style="background:#d4edda;color:#155724;border:1px solid #c3e6cb;font-size:0.75rem;">✓ Halal</span>' : ''}
                </div>
              </div>
            `).join('')}
          </div>
        </div>`;
    });

    container.innerHTML = html;
  } catch (err) {
    /* ignore menu load error */
  }
}

/* modal close handlers */
document.addEventListener('click', e => {
  if (e.target === modal) closeModal();
  if (e.target.closest('[data-close-modal]')) closeModal();
});
document.addEventListener('keydown', e => { if (e.key === 'Escape') closeModal(); });
