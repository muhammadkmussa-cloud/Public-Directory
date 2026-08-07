/**
 * For Business page — claim a listing.
 * Lets a logged-in user select an unclaimed business and submit a claim
 * request (pending admin approval for non-admins).
 */
'use strict';

window.appReady.then(async () => {
  const claimBody = document.getElementById('claimBody');
  const ownerPanel = document.getElementById('ownerPanel');
  const preselectId = Number(new URLSearchParams(location.search).get('id')) || null;

  if (!session.user) {
    const next = 'business-claim' + (preselectId ? '?id=' + preselectId : '');
    claimBody.innerHTML = emptyState('Login to claim a business',
      'You need an account to claim a listing.',
      '<a class="btn btn-primary btn-sm" href="login?next=' + encodeURIComponent(next) + '">Login</a>');
    return;
  }

  let claimable = [];
  let mineIds = new Set();

  try {
    const [all, mine] = await Promise.all([
      api('api/businesses.php'),
      api('api/businesses.php?action=mine'),
    ]);
    const mineList = Array.isArray(mine) ? mine : ((mine && mine.items) || []);
    mineIds = new Set(mineList.map(b => b.id));
    claimable = (all.items || []).filter(b =>
      !mineIds.has(b.id) && !b.is_claimed
      && b.claim_status !== 'pending' && b.claim_status !== 'approved');

    const owned = mineList.filter(b => b.claim_status === 'approved');
    if (owned.length) {
      ownerPanel.style.display = '';
      ownerPanel.querySelector('p').textContent =
        'You own ' + owned.length + (owned.length === 1 ? ' listing' : ' listings') + ' on Ummah Directory. Open your dashboard to edit details and respond to reviews.';
    }
  } catch (e) {
    claimBody.innerHTML = '<p class="muted">Could not load listings: ' + esc(e.message) + '</p>';
    return;
  }

  if (!claimable.length) {
    claimBody.innerHTML = '<p class="muted center" style="padding:1rem 0;">No unclaimed listings are available right now. Check back soon!</p>';
    return;
  }

  const options = claimable.map(b => {
    const selected = b.id === preselectId ? ' selected' : '';
    return `<option value="${b.id}"${selected}>${esc(b.name)} — ${esc(b.city || '')}</option>`;
  }).join('');

  claimBody.innerHTML = `
    <form id="claimForm" novalidate>
      <div class="filter">
        <label for="claimBiz">Your business</label>
        <select id="claimBiz" required>
          <option value="">Select a business…</option>
          ${options}
        </select>
      </div>
      <div class="filter">
        <label for="claimProof">How are you connected to this business?</label>
        <textarea id="claimProof" rows="3" maxlength="500"
          placeholder="e.g. I'm the owner / manager, staff ID … (optional)"></textarea>
        <span class="muted small">Optional — helps admins verify your claim.</span>
      </div>
      <button type="submit" class="btn btn-primary" id="claimSubmit">Submit claim request</button>
    </form>
    <div id="claimResult" aria-live="polite" style="margin-top:.8rem;"></div>`;

  document.getElementById('claimForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    const submit = document.getElementById('claimSubmit');
    const result = document.getElementById('claimResult');
    const bizId = Number(document.getElementById('claimBiz').value);
    const proof = document.getElementById('claimProof').value.trim();

    if (!bizId) {
      result.innerHTML = '<p class="error-text">Please select a business.</p>';
      return;
    }

    submit.disabled = true;
    submit.textContent = 'Submitting…';
    try {
      const res = await api('api/businesses.php', {
        method: 'POST',
        body: { action: 'claim', business_id: bizId, proof },
      });
      const pending = res.status === 'pending';
      result.innerHTML = pending
        ? '<p class="ok-text">Claim submitted! An admin will review it. You can track it in your dashboard.</p>'
        : '<p class="ok-text">Listing claimed! You now have owner controls in your dashboard.</p>';
      const btn = document.createElement('a');
      btn.className = 'btn btn-outline btn-sm';
      btn.href = 'dashboard';
      btn.textContent = 'Go to dashboard';
      result.appendChild(btn);
      document.getElementById('claimForm').remove();
    } catch (err) {
      result.innerHTML = '<p class="error-text">' + esc(err.message || 'Could not submit your claim.') + '</p>';
    } finally {
      submit.disabled = false;
      submit.textContent = 'Submit claim request';
    }
  });
});
