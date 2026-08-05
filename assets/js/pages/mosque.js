/**
 * Mosque detail page
 * Reads ?id= — shows info, prayer times (today + week), facilities, reviews.
 */
'use strict';

window.appReady.then(async () => {
  const id = new URLSearchParams(window.location.search).get('id');
  if (!id) { window.location.href = 'mosques.html'; return; }

  const el = document.getElementById('mosqueDetail');
  el.innerHTML = '<div class="panel">Loading mosque…</div>';

  try {
    const { mosque, photos, reviews, prayer } = await api('api/mosques.php?id=' + id);
    render(mosque, photos, reviews, prayer);
    loadSponsoredSide();

    document.getElementById('checkinBtn')?.addEventListener('click', async () => {
      if (!session.user) { toast('Please login to check in', 'error'); return; }
      try {
        const data = await api('api/checkin.php', { method: 'POST', body: { checkinable_id: mosque.id, checkinable_type: 'mosque' } });
        toast('Checked in! (' + data.checkin_count + ' total)');
        document.getElementById('checkinBtn').disabled = true;
      } catch (e) { toast(e.message || 'Could not check in', 'error'); }
    });

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
    el.innerHTML = emptyState('Mosque not found', e.message, '<a class="btn btn-primary btn-sm" href="mosques.html">Browse mosques</a>');
  }

  function render(m, photos, reviews, prayer) {
    const fac = m.facilities || {};
    const facList = [
      ['women_section', "Women's prayer area"],
      ['wudu', 'Wudu facilities'],
      ['parking', 'Parking'],
      ['wheelchair', 'Wheelchair accessible'],
      ['quran_classes', 'Quran classes'],
      ['ramadan_iftar', 'Ramadan iftar'],
    ];

    const today = prayer && prayer.today;
    const next = prayer && prayer.next;
    const prayerBoxes = today
      ? ['Fajr', 'Sunrise', 'Dhuhr', 'Asr', 'Maghrib', 'Isha'].map(name => `
          <div class="prayer-box ${next && next.name === name ? 'active' : ''}">
            <span>${name}</span><b>${esc(today[name])}</b>
          </div>`).join('')
      : '<p class="muted">Prayer times unavailable for this location.</p>';

    const weekRows = (prayer && prayer.week || []).map(d => `
      <tr><td>${esc(d.day)}</td><td>${esc(d.times.Fajr)}</td><td>${esc(d.times.Dhuhr)}</td><td>${esc(d.times.Maghrib)}</td></tr>`).join('');

    el.innerHTML = `
      <div class="detail-top">
        <div class="rating-row">
          ${starsHtml(m.rating_average)}
          <span class="rating-num">${Number(m.rating_average || 0).toFixed(1)}</span>
          <span class="muted">(${m.review_count || 0} reviews)</span>
          ${m.is_verified ? '<span class="chip">✓ Verified</span>' : ''}
        </div>
        <h1 class="detail-title">${esc(m.name)}</h1>
        <p class="muted">${icon('pin', 13)} ${esc(m.address || '')}${m.city ? ', ' + esc(m.city) : ''}${m.region ? ', ' + esc(m.region) : ''}</p>
        <div class="detail-actions">
          ${m.phone ? `<a class="btn btn-primary" href="tel:${esc(m.phone)}">${icon('phone', 14)} Call</a>` : ''}
          ${m.latitude ? `<a class="btn btn-outline" href="https://www.google.com/maps?q=${m.latitude},${m.longitude}" target="_blank" rel="noopener">${icon('nav', 14)} Directions</a>` : ''}
          <button class="btn btn-ghost" id="checkinBtn">${icon('pin', 14)} Check in</button>
          <button class="btn btn-outline" id="writeReviewBtn">${icon('pen', 14)} Write a review</button>
        </div>
      </div>

      ${(photos || []).length ? `
        <div class="gallery">
          <div class="gallery-main"><img src="${esc(photos[0].photo_path)}" alt="${esc(m.name)}"></div>
          <div class="gallery-side">${photos.slice(1, 3).map(p => `<img src="${esc(p.photo_path)}" alt="">`).join('')}</div>
        </div>` : ''}

      <div class="detail-grid">
        <div>
          <section class="panel">
            <h2>About</h2>
            <p class="about-text">${esc(m.description || '')}</p>
            ${m.imam_name ? `<p style="margin-top:.8rem;"><b>Imam:</b> ${esc(m.imam_name)}</p>` : ''}
            ${m.capacity ? `<p><b>Capacity:</b> ${Number(m.capacity).toLocaleString()}</p>` : ''}
          </section>

          <section class="panel">
            <h2>Today's prayer times</h2>
            <div class="prayer-grid">${prayerBoxes}</div>
            ${next ? `<p class="muted small" style="margin-top:.8rem;">Next prayer: <b>${esc(next.name)}</b> at ${esc(next.time)} (${next.remaining_minutes} min)</p>` : ''}
          </section>

          <section class="panel">
            <h2>This week</h2>
            <table class="week-table">
              <thead><tr><th>Day</th><th>Fajr</th><th>Dhuhr</th><th>Maghrib</th></tr></thead>
              <tbody>${weekRows}</tbody>
            </table>
          </section>

          <section class="panel">
            <h2>Reviews</h2>
            <div id="reviewsList">
              ${reviews.length ? reviews.map(reviewCard).join('') : '<p class="muted center" style="padding:1.5rem 0;">No reviews yet — be the first!</p>'}
            </div>
          </section>
        </div>

        <aside>
          <div class="side-card">
            <h3>Facilities</h3>
            <ul class="facility-list">
              ${facList.map(([key, label]) => `<li class="${fac[key] ? 'on' : 'off'}">${esc(label)}</li>`).join('')}
            </ul>
          </div>
          ${m.phone || m.email ? `
          <div class="side-card">
            <h3>Contact</h3>
            ${m.phone ? `<div class="side-row"><span class="lbl">Phone</span><a href="tel:${esc(m.phone)}">${esc(m.phone)}</a></div>` : ''}
            ${m.email ? `<div class="side-row"><span class="lbl">Email</span><a href="mailto:${esc(m.email)}">${esc(m.email)}</a></div>` : ''}
          </div>` : ''}
          <div class="side-card" id="sponsoredSide"></div>
        </aside>
      </div>
    `;

    document.getElementById('writeReviewBtn').addEventListener('click', () => openModal('mosque', m.id));
  }

  function openModal(type, id) {
    if (!session.user) { toast('Please login to write a review', 'error'); return; }
    document.getElementById('rvType').value = type;
    document.getElementById('rvId').value = id;
    document.getElementById('reviewModal').classList.add('open');
  }
  function closeModal() { document.getElementById('reviewModal').classList.remove('open'); }

  const modal = document.getElementById('reviewModal');
  document.addEventListener('click', e => {
    if (e.target === modal) closeModal();
    if (e.target.closest('[data-close-modal]')) closeModal();
  });
  document.addEventListener('keydown', e => { if (e.key === 'Escape') closeModal(); });
});
