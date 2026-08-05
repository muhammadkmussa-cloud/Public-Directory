/**
 * Notifications page
 */
'use strict';

window.appReady.then(async () => {
  const list = document.getElementById('notifList');

  if (!session.user) {
    list.innerHTML = emptyState('Please login first', 'You need an account to view notifications.',
      '<a class="btn btn-primary btn-sm" href="login.html">Login</a>');
    return;
  }

  try {
    const d = await api('api/notifications.php');
    const items = d.items || [];
    if (!items.length) {
      list.innerHTML = '<div class="panel"><p class="muted center" style="padding:1.2rem 0;">No notifications yet.</p></div>';
      return;
    }
    list.innerHTML = `
      <div class="panel">
        <div class="notif-head" style="margin-bottom:.6rem;">
          <b>${items.length} notification${items.length === 1 ? '' : 's'}</b>
          <button class="btn btn-ghost btn-xs" id="readAllBtn">Mark all read</button>
        </div>
        ${items.map(n => `
          <a class="notif-item ${n.is_read ? '' : 'unread'}" href="${esc(n.link || 'profile.html')}" data-notif-id="${n.id}">
            <b>${esc(n.title)}</b>
            <span class="muted small">${esc(n.message)}</span>
            <span class="muted small">${timeAgo(n.created_at)}</span>
          </a>`).join('')}
      </div>`;

    const readAllBtn = document.getElementById('readAllBtn');
    if (readAllBtn) readAllBtn.addEventListener('click', async () => {
      await api('api/notifications.php', { method: 'POST', body: { action: 'read_all' } });
      list.querySelectorAll('.notif-item').forEach(i => i.classList.remove('unread'));
      readAllBtn.remove();
      refreshNotifBadge();
    });
    list.querySelectorAll('.notif-item').forEach(item => {
      item.addEventListener('click', () => {
        api('api/notifications.php', { method: 'POST', body: { action: 'read', id: item.dataset.notifId } }).then(refreshNotifBadge).catch(() => {});
      });
    });
  } catch (e) {
    list.innerHTML = '<div class="panel"><p class="muted center" style="padding:1.2rem 0;">Could not load notifications.</p></div>';
  }
});
