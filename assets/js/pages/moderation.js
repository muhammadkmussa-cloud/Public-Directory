/**
 * Moderation console — admin only
 * Lists reports from the community; resolve or reject.
 */
'use strict';

window.appReady.then(async () => {
  const wrap = document.getElementById('modWrap');

  if (!session.user || session.user.user_type !== 'admin') {
    wrap.innerHTML = emptyState('Admins only', 'You need an admin account to moderate content.',
      '<a class="btn btn-primary btn-sm" href="login.html">Login</a>');
    return;
  }

  wrap.innerHTML = '<div class="panel"><h2>Reports</h2><div id="reportQueue"><p class="muted">Loading…</p></div></div>';

  async function load() {
    const queue = document.getElementById('reportQueue');
    try {
      const rows = await api('api/reports.php?action=queue');
      if (!rows.length) {
        queue.innerHTML = '<p class="muted center" style="padding:1.2rem 0;">No reports — the community is behaving! 🎉</p>';
        return;
      }
      queue.innerHTML = rows.map(r => `
        <div class="report-row">
          <div class="report-info">
            <span class="chip ${r.status === 'pending' ? 'chip-on' : 'chip-off'}">${esc(r.status)}</span>
            <span class="chip">${esc(r.reportable_type)} #${r.reportable_id}</span>
            <span class="chip">${esc(r.reason)}</span>
            <span class="muted small">by ${esc(r.reporter_name)} · ${timeAgo(r.created_at)}</span>
            ${r.description ? `<p class="report-desc">"${esc(r.description)}"</p>` : ''}
            ${r.admin_notes ? `<p class="muted small" style="margin-top:.3rem;">Notes: ${esc(r.admin_notes)}</p>` : ''}
          </div>
          ${r.status === 'pending' ? `
          <div class="report-actions">
            <button class="btn btn-outline btn-xs" data-resolve="${r.id}" data-status="resolved">Resolve</button>
            <button class="btn btn-danger btn-xs" data-resolve="${r.id}" data-status="rejected">Reject</button>
          </div>` : ''}
        </div>`).join('');
    } catch (e) {
      queue.innerHTML = '<p class="muted">Could not load reports: ' + esc(e.message) + '</p>';
    }
  }

  document.addEventListener('click', async (e) => {
    const btn = e.target.closest('[data-resolve]');
    if (!btn) return;
    try {
      await api('api/reports.php', { method: 'POST', body: { action: 'admin_resolve', report_id: btn.dataset.resolve, status: btn.dataset.status } });
      toast(btn.dataset.status === 'resolved' ? 'Report resolved ✓' : 'Report rejected');
      load();
    } catch (err) {
      toast(err.message || 'Could not update report', 'error');
    }
  });

  load();
});
