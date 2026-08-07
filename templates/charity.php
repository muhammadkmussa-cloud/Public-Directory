<?php
/**
 * Charity detail SSR template
 * Uses shared helpers from includes/TemplateHelpers.php
 */
declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/Database.php';
require_once __DIR__ . '/../includes/TemplateHelpers.php';

// Fetch charity data
$id = (int)($_GET['id'] ?? 0);
if (!$id) {
    http_response_code(404);
    include __DIR__ . '/../404.php';
    exit;
}

$db = Database::getInstance();

$charity = $db->fetchOne('SELECT * FROM charities WHERE id = ?', [$id]);
if (!$charity) {
    http_response_code(404);
    include __DIR__ . '/../404.php';
    exit;
}

// Total raised and donors (only completed donations)
$totals = $db->fetchOne(
    "SELECT COALESCE(SUM(CASE WHEN status = 'completed' THEN amount END), 0) AS raised,
            COALESCE(SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END), 0) AS donors
       FROM donations WHERE charity_id = ?",
    [$id]
);
$raised = (float)$totals['raised'];
$donors = (int)$totals['donors'];

// Active campaigns
$campaigns = $db->fetchAll(
    "SELECT c.*,
            COALESCE(SUM(CASE WHEN d.status = 'completed' THEN d.amount END), 0) AS raised,
            COALESCE(SUM(CASE WHEN d.status = 'completed' THEN 1 ELSE 0 END), 0) AS donors
       FROM campaigns c
       LEFT JOIN donations d ON d.campaign_id = c.id
      WHERE c.charity_id = ? AND c.status = 'active'
      GROUP BY c.id
      ORDER BY c.end_date IS NULL, c.end_date ASC",
    [$id]
);
foreach ($campaigns as &$c) {
    $c['raised'] = (float)$c['raised'];
    $c['progress'] = $c['goal_amount'] > 0 ? min(100, round($c['raised'] / $c['goal_amount'] * 100)) : 0;
}

require_once __DIR__ . '/../includes/PreRenderer.php';

// Page variables
$logoOrCover = $charity['logo_path'] ?? $charity['cover_photo'] ?? '';
$pageUrl = getCurrentPageUrl();
$jsonLd = buildCharityJsonLd($charity, $logoOrCover, $pageUrl);
$extraHead = PreRenderer::renderHeaderTags('charity', $id, $charity);

$pageTitle = $charity['name'] . ' — Ummah Directory';
$pageDescription = ($charity['description'] ?? $charity['category'] ?? '') . ($charity['city'] ? ' — ' . $charity['city'] : '');
[$pageScript, $lang, $dir] = ['charity.js', ...getLangAndDir()];

// Build campaigns HTML
$campaignsHtml = '';
if ($campaigns) {
    foreach ($campaigns as $cam) {
        $hasGoal = $cam['goal_amount'] > 0;
        $campaignsHtml .= '
          <div class="campaign">
            <div class="campaign-head">
              <h3>' . e($cam['title']) . '</h3>
              <button class="btn btn-primary btn-sm" data-donate-campaign="' . $cam['id'] . '" data-campaign-title="' . e($cam['title']) . '">Donate</button>
            </div>
            <p class="muted small">' . e($cam['description'] ?? '') . '</p>
            ' . ($hasGoal ? '<div class="progress"><i style="width:' . (int)$cam['progress'] . '%"></i></div>' : '') . '
            <div class="campaign-stats">
              <span><b>' . fmtMoney($cam['raised']) . '</b> raised</span>
              ' . ($hasGoal ? '<span>of ' . fmtMoney($cam['goal_amount']) . ' goal</span>' : '') . '
              <span>' . (int)$cam['donors'] . ' donors</span>
              ' . ($cam['end_date'] ? '<span>ends ' . fmtDate($cam['end_date']) . '</span>' : '') . '
            </div>
          </div>';
    }
} else {
    $campaignsHtml = '<p class="muted center" style="padding:1.2rem 0;">No active campaigns right now.</p>';
}

// Logo or initial
$logoHtml = $charity['logo_path']
    ? '<img src="' . e($charity['logo_path']) . '" alt="" style="width:72px;height:72px;border-radius:12px;object-fit:cover;">'
    : '<span class="avatar" style="width:72px;height:72px;font-size:1.8rem;">' . e($charity['name'][0]) . '</span>';

$mainContent = '
  <div class="detail-top">
    <div style="display:flex;align-items:center;gap:1rem;flex-wrap:wrap;">
      ' . $logoHtml . '
      <div>
        <h1 class="detail-title">' . e($charity['name']) . '</h1>
        <p class="muted">' . e(str_replace('_', ' ', $charity['category'])) . ' · ' . e($charity['city'] ?? '') . ' ' . buildVerifiedChip($charity['is_verified']) . '</p>
      </div>
    </div>
    <p class="about-text" style="margin-top:1rem;">' . e($charity['description'] ?? '') . '</p>
    <div class="detail-actions">
      ' . ($charity['phone'] ? '<a class="btn btn-primary" href="tel:' . e($charity['phone']) . '">📞 Call</a>' : '') . '
      ' . ($charity['whatsapp'] ? '<a class="btn btn-outline" href="https://wa.me/' . e($charity['whatsapp']) . '" target="_blank" rel="noopener">💬 WhatsApp</a>' : '') . '
      ' . ($charity['website'] ? '<a class="btn btn-outline" href="' . e($charity['website']) . '" target="_blank" rel="noopener">🌐 Website</a>' : '') . '
      <button class="btn btn-ghost share-btn" data-share-title="' . e($charity['name']) . '" data-share-url="/charity/' . $charity['id'] . '" data-share-text="' . e($charity['description'] ?? $charity['name']) . '">🔗 Share</button>
      <button class="btn btn-primary" id="donateTopBtn">❤️ Donate</button>
    </div>
  </div>

  <div class="detail-grid">
    <div>
      <section class="panel">
        <h2>Active campaigns (' . count($campaigns) . ')</h2>
        ' . $campaignsHtml . '
      </section>
    </div>

    <aside>
      <div class="side-card center" style="padding:1.4rem;">
        <div style="font-size:2rem;">' . fmtMoney($raised) . '</div>
        <div class="muted small">raised in total</div>
        <div style="font-size:1.2rem;margin-top:.6rem;"><b>' . $donors . '</b> <span class="muted small">donors</span></div>
        <button class="btn btn-primary btn-block" style="margin-top:1rem;" id="donateSideBtn">Make a donation</button>
      </div>
      ' . ($charity['paybill_number'] ? '
      <div class="side-card">
        <h3>M-Pesa Paybill</h3>
        <p style="font-size:1.4rem;font-weight:800;color:var(--brand);">' . e($charity['paybill_number']) . '</p>
        <p class="muted small">Paybill (' . e($charity['paybill_number'] ?? '') . ') — use your phone to send directly.</p>
      </div>' : '') . '
      ' . ($charity['email'] ? '<div class="side-card"><h3>Contact</h3><div class="side-row"><span class="lbl">Email</span><a href="mailto:' . e($charity['email']) . '">' . e($charity['email']) . '</a></div></div>' : '') . '
    </aside>
  </div>
';

// Include base template
include __DIR__ . '/base.php';