/**
 * Charity detail page — campaigns, progress, donate flow
 */
'use strict';

const donateModal = document.getElementById('donateModal');

function openDonate(charityId, campaignId, title) {
  document.getElementById('dnCharityId').value = charityId;
  document.getElementById('dnCampaignId').value = campaignId || '';
  document.getElementById('donateModalTitle').textContent = title || 'Make a donation';
  document.getElementById('donateAlert').innerHTML = '';
  document.getElementById('dnAmount').value = '';
  document.getElementById('dnPhone').value = '';
  document.getElementById('dnName').value = '';
  document.getElementById('dnEmail').value = '';
  donateModal.classList.add('open');
}
function closeDonate() { donateModal.classList.remove('open'); }

window.appReady.then(async () => {
  const id = new URLSearchParams(window.location.search).get('id');
  if (!id) { window.location.href = 'charities.html'; return; }

  const el = document.getElementById('charityDetail');
  el.innerHTML = '<div class="panel">Loading…</div>';

  try {
    const { charity, raised, donors, campaigns } = await api('api/charities.php?id=' + id);
    if (window.setOgMeta) setOgMeta({
      title: charity.name,
      description: (charity.description || charity.category || '').replace(/<[^>]+>/g, ''),
      image: charity.logo_path || charity.cover_photo || '',
    });
    render(charity, raised, donors, campaigns);

    // amount quick buttons
    document.querySelectorAll('.amount-btn').forEach(btn => {
      btn.addEventListener('click', () => { document.getElementById('dnAmount').value = btn.dataset.amount; });
    });

    // method toggle (phone only for M-Pesa)
    const method = document.getElementById('dnMethod');
    method.addEventListener('change', () => {
      document.getElementById('dnPhoneGroup').style.display = method.value === 'mpesa' ? '' : 'none';
    });

    // donate submit
    document.getElementById('donateForm').addEventListener('submit', async (e) => {
      e.preventDefault();
      const btn = document.getElementById('donateBtn');
      const alertBox = document.getElementById('donateAlert');
      btn.disabled = true;
      const fd = new FormData(e.target);
      const body = {};
      for (const [k, v] of fd.entries()) if (String(v).trim() !== '') body[k] = v;
      body.charity_id = Number(document.getElementById('dnCharityId').value);
      body.campaign_id = Number(document.getElementById('dnCampaignId').value || 0);
      body.amount = Number(document.getElementById('dnAmount').value);

      try {
        const res = await api('api/donations.php', { method: 'POST', body });

        if (res.payment_url) {
          // PayPal: send the donor to complete the payment off-site.
          alertBox.innerHTML = `<div class="alert alert-success">${esc(res.message)} <a href="${esc(res.payment_url)}" target="_blank" rel="noopener">Complete payment on PayPal →</a></div>`;
          setTimeout(() => { closeDonate(); window.location.reload(); }, 3500);
        } else if (res.status === 'completed') {
          alertBox.innerHTML = `<div class="alert alert-success">${esc(res.message)}${res.payment && res.payment.simulated ? ' <small>(sandbox — no real charge)</small>' : ''}</div>`;
          setTimeout(() => { closeDonate(); window.location.reload(); }, 2200);
        } else {
          // Pending (live M-Pesa / bank): show a waiting state and poll the
          // donation status until it clears.
          alertBox.innerHTML = `<div class="alert alert-success">${esc(res.message)} <small>Waiting for payment confirmation…</small></div>`;
          const donationId = res.donation_id;
          let attempts = 0;
          const poll = async () => {
            attempts++;
            try {
              const s = await api('api/donations.php?action=status&donation_id=' + donationId);
              if (s.status === 'completed') {
                alertBox.innerHTML = `<div class="alert alert-success">Payment confirmed — thank you! 🙏</div>`;
                setTimeout(() => { closeDonate(); window.location.reload(); }, 1200);
                return;
              }
            } catch (err) { /* keep polling */ }
            if (attempts < 20) setTimeout(poll, 3000); // poll every 3s for up to 60s
          };
          poll();
        }
      } catch (err) {
        alertBox.innerHTML = `<div class="alert alert-error">${esc(err.message || 'Could not process donation')}</div>`;
        btn.disabled = false;
      }
    });

  } catch (e) {
    el.innerHTML = emptyState('Charity not found', e.message, '<a class="btn btn-primary btn-sm" href="charities.html">Browse charities</a>');
  }

  function render(c, raised, donors, campaigns) {
    el.innerHTML = `
      <div class="detail-top">
        <div style="display:flex;align-items:center;gap:1rem;flex-wrap:wrap;">
          ${c.logo_path ? `<img src="${esc(c.logo_path)}" alt="" style="width:72px;height:72px;border-radius:12px;object-fit:cover;">` : `<span class="avatar" style="width:72px;height:72px;font-size:1.8rem;">${esc(c.name.charAt(0))}</span>`}
          <div>
            <h1 class="detail-title">${esc(c.name)}</h1>
            <p class="muted">${esc(c.category.replace('_', ' '))} · ${esc(c.city || '')}${c.is_verified ? ' · <span class="chip">✓ Verified</span>' : ''}</p>
          </div>
        </div>
        <p class="about-text" style="margin-top:1rem;">${esc(c.description || '')}</p>
        <div class="detail-actions">
          ${c.phone ? `<a class="btn btn-primary" href="tel:${esc(c.phone)}">${icon('phone', 14)} Call</a>` : ''}
          ${c.whatsapp ? `<a class="btn btn-outline" href="https://wa.me/${esc(c.whatsapp)}" target="_blank" rel="noopener">${icon('wa', 14)} WhatsApp</a>` : ''}
          ${c.website ? `<a class="btn btn-outline" href="${esc(c.website)}" target="_blank" rel="noopener">${icon('globe', 14)} Website</a>` : ''}
          <button class="btn btn-ghost share-btn" data-share-title="${esc(c.name)}" data-share-url="charity.html?id=${c.id}" data-share-text="${esc(c.description || c.name)}">${icon('share', 14)} Share</button>
          <button class="btn btn-primary" id="donateTopBtn">${icon('heart', 14)} Donate</button>
        </div>
      </div>

      <div class="detail-grid">
        <div>
          <section class="panel">
            <h2>Active campaigns (${campaigns.length})</h2>
            ${campaigns.length ? campaigns.map(cam => `
              <div class="campaign">
                <div class="campaign-head">
                  <h3>${esc(cam.title)}</h3>
                  <button class="btn btn-primary btn-sm" data-donate-campaign="${cam.id}" data-campaign-title="${esc(cam.title)}">Donate</button>
                </div>
                <p class="muted small">${esc(cam.description || '')}</p>
                <div class="progress"><i style="width:${cam.progress}%"></i></div>
                <div class="campaign-stats">
                  <span><b>${fmtMoney(cam.raised)}</b> raised</span>
                  <span>of ${fmtMoney(cam.goal_amount)} goal</span>
                  <span>${cam.donors} donors</span>
                  ${cam.end_date ? `<span>ends ${fmtDate(cam.end_date)}</span>` : ''}
                </div>
              </div>`).join('') : '<p class="muted center" style="padding:1.2rem 0;">No active campaigns right now.</p>'}
          </section>
        </div>

        <aside>
          <div class="side-card center" style="padding:1.4rem;">
            <div style="font-size:2rem;">${fmtMoney(raised)}</div>
            <div class="muted small">raised in total</div>
            <div style="font-size:1.2rem;margin-top:.6rem;"><b>${donors}</b> <span class="muted small">donors</span></div>
            <button class="btn btn-primary btn-block" style="margin-top:1rem;" id="donateSideBtn">Make a donation</button>
          </div>
          ${c.paybill_number ? `
          <div class="side-card">
            <h3>M-Pesa Paybill</h3>
            <p style="font-size:1.4rem;font-weight:800;color:var(--brand);">${esc(c.paybill_number)}</p>
            <p class="muted small">Paybill (${esc(c.paybill_number || '')}) — use your phone to send directly.</p>
          </div>` : ''}
          ${c.email ? `<div class="side-card"><h3>Contact</h3><div class="side-row"><span class="lbl">Email</span><a href="mailto:${esc(c.email)}">${esc(c.email)}</a></div></div>` : ''}
        </aside>
      </div>
    `;

    document.getElementById('donateTopBtn').addEventListener('click', () => openDonate(c.id, null, 'Donate to ' + c.name));
    document.getElementById('donateSideBtn').addEventListener('click', () => openDonate(c.id, null, 'Donate to ' + c.name));
    el.querySelectorAll('[data-donate-campaign]').forEach(b => {
      b.addEventListener('click', () => openDonate(c.id, b.dataset.donateCampaign, b.dataset.campaignTitle));
    });
  }
});

/* modal close */
document.addEventListener('click', e => {
  if (e.target === donateModal) closeDonate();
  if (e.target.closest('[data-close-donate]')) closeDonate();
});
document.addEventListener('keydown', e => { if (e.key === 'Escape') closeDonate(); });
