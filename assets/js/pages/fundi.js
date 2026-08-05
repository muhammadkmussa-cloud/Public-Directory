/**
 * Fundi detail page
 * Reads ?id= — profile, skills, portfolio, working hours, reviews.
 */
'use strict';

window.appReady.then(async () => {
  const id = new URLSearchParams(window.location.search).get('id');
  if (!id) { window.location.href = 'fundis.html'; return; }

  const el = document.getElementById('fundiDetail');
  el.innerHTML = '<div class="panel">Loading profile…</div>';

  try {
    const { fundi, portfolio, reviews } = await api('api/fundis.php?id=' + id);
    render(fundi, portfolio, reviews);
    loadSponsoredSide();

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
    el.innerHTML = emptyState('Fundi not found', e.message, '<a class="btn btn-primary btn-sm" href="fundis.html">Browse fundis</a>');
  }

  function render(f, portfolio, reviews) {
    const skills = (f.skills || []).map(s => `<span class="chip">${esc(s)}</span>`).join('');
    const hours = f.working_hours || {};
    const days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
    const initial = esc((f.full_name || 'F').charAt(0).toUpperCase());

    el.innerHTML = `
      <div class="detail-top">
        <div style="display:flex;align-items:center;gap:1.2rem;flex-wrap:wrap;">
          ${f.profile_photo
            ? `<img class="avatar" style="width:84px;height:84px;font-size:2rem;" src="${esc(f.profile_photo)}" alt="">`
            : `<span class="avatar" style="width:84px;height:84px;font-size:2rem;">${initial}</span>`}
          <div>
            <div class="rating-row">
              ${starsHtml(f.rating_average)}
              <span class="rating-num">${Number(f.rating_average || 0).toFixed(1)}</span>
              <span class="muted">(${f.review_count || 0} reviews)</span>
              ${f.is_verified ? '<span class="chip">✓ Verified</span>' : ''}
            </div>
            <h1 class="detail-title">${esc(f.full_name)}</h1>
            <p class="muted">${esc(f.profession)}${f.years_experience ? ' · ' + f.years_experience + ' years experience' : ''} · ${icon('pin', 12)} ${esc(f.city || '')}${f.region ? ', ' + esc(f.region) : ''}</p>
          </div>
        </div>
        <div class="detail-actions">
          ${f.phone ? `<a class="btn btn-primary" href="tel:${esc(f.phone)}">${icon('phone', 14)} Call</a>` : ''}
          ${f.whatsapp ? `<a class="btn btn-outline" href="https://wa.me/${esc(f.whatsapp)}" target="_blank" rel="noopener">${icon('wa', 14)} WhatsApp</a>` : ''}
          ${f.hourly_rate_min ? `<a class="btn btn-ghost">${icon('clock', 14)} ${fmtMoney(f.hourly_rate_min)}${f.hourly_rate_max ? '–' + fmtMoney(f.hourly_rate_max) : ''}/hr</a>` : ''}
        </div>
      </div>

      <div class="detail-grid">
        <div>
          <section class="panel">
            <h2>About</h2>
            <p class="about-text">${esc(f.bio || '')}</p>
            ${skills ? `<h3 style="margin-top:1.2rem;">Skills</h3><div class="chips">${skills}</div>` : ''}
            ${f.languages && f.languages.length ? `<h3 style="margin-top:1rem;">Languages</h3><div class="chips">${f.languages.map(l => `<span class="chip">${esc(l)}</span>`).join('')}</div>` : ''}
          </section>

          ${(portfolio || []).length ? `
          <section class="panel">
            <h2>Portfolio (${portfolio.length})</h2>
            <div class="grid" style="grid-template-columns:repeat(auto-fill,minmax(180px,1fr));">
              ${portfolio.map(p => `
                <figure style="margin:0;">
                  <img src="${esc(p.photo_path)}" alt="${esc(p.caption || '')}" style="border-radius:12px;aspect-ratio:1;object-fit:cover;width:100%;">
                  ${p.caption ? `<figcaption class="muted small" style="margin-top:.4rem;">${esc(p.caption)}</figcaption>` : ''}
                </figure>`).join('')}
            </div>
          </section>` : ''}

          <section class="panel">
            <h2>Reviews</h2>
            <div style="margin-bottom:1rem;">
              <button class="btn btn-primary" id="writeReviewBtn">${icon('pen', 14)} Write a review</button>
            </div>
            ${reviews.length ? reviews.map(reviewCard).join('') : '<p class="muted center" style="padding:1.5rem 0;">No reviews yet — be the first!</p>'}
          </section>
        </div>

        <aside>
          <div class="side-card">
            <h3>Working hours</h3>
            <table class="hours-table">
              ${days.map(d => `
                <tr>
                  <td>${esc(d.charAt(0).toUpperCase() + d.slice(1))}</td>
                  <td>${esc(hours[d] || 'Closed')}</td>
                </tr>`).join('')}
            </table>
          </div>
          ${f.phone || f.email ? `
          <div class="side-card">
            <h3>Contact</h3>
            ${f.phone ? `<div class="side-row"><span class="lbl">Phone</span><a href="tel:${esc(f.phone)}">${esc(f.phone)}</a></div>` : ''}
            ${f.whatsapp ? `<div class="side-row"><span class="lbl">WhatsApp</span><a href="https://wa.me/${esc(f.whatsapp)}" target="_blank" rel="noopener">Chat</a></div>` : ''}
            ${f.email ? `<div class="side-row"><span class="lbl">Email</span><a href="mailto:${esc(f.email)}">${esc(f.email)}</a></div>` : ''}
          </div>` : ''}
          <div class="side-card" id="sponsoredSide"></div>
        </aside>
      </div>
    `;

    document.getElementById('writeReviewBtn').addEventListener('click', () => openModal('fundi', f.id));
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
