<?php
/**
 * Fundi detail SSR template
 * Uses shared helpers from includes/TemplateHelpers.php
 */
declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/Database.php';
require_once __DIR__ . '/../includes/TemplateHelpers.php';

// Fetch fundi data
$id = (int)($_GET['id'] ?? 0);
if (!$id) {
    http_response_code(404);
    include __DIR__ . '/../404.php';
    exit;
}

$db = Database::getInstance();

$fundi = $db->fetchOne(
    'SELECT f.*, u.full_name, u.profile_photo, u.username
       FROM fundis f
       JOIN users u ON u.id = f.user_id
      WHERE f.id = ? AND f.is_available = 1',
    [$id]
);
if (!$fundi) {
    http_response_code(404);
    include __DIR__ . '/../404.php';
    exit;
}
$fundi = decodeJsonColumns($fundi, ['skills', 'certifications', 'languages', 'working_hours']);

$portfolio = $db->fetchAll(
    'SELECT id, photo_path, thumbnail_path, caption
       FROM fundi_photos WHERE fundi_id = ? ORDER BY id DESC LIMIT 12',
    [$id]
);

// Fetch reviews with reaction counts in single query (no N+1)
$reviews = fetchReviewsWithReactions($db, 'fundi', $id, 20);

require_once __DIR__ . '/../includes/PreRenderer.php';

// Page variables
$pageUrl = getCurrentPageUrl();
$jsonLd = buildFundiJsonLd($fundi, $pageUrl);
$extraHead = PreRenderer::renderHeaderTags('fundi', $id, $fundi);

$pageTitle = $fundi['full_name'] . ' — Ummah Directory';
$pageDescription = ($fundi['profession'] ?? 'Fundi') . ($fundi['city'] ? ' — ' . $fundi['city'] : '');
[$pageScript, $lang, $dir] = ['fundi.js', ...getLangAndDir()];

// Build skills HTML
$skillsHtml = '';
if (!empty($fundi['skills'])) {
    $skillsHtml = '<h3 style="margin-top:1.2rem;">Skills</h3><div class="chips">' .
        implode('', array_map(fn($s) => '<span class="chip">' . e($s) . '</span>', $fundi['skills'])) .
        '</div>';
}

// Build languages HTML
$languagesHtml = '';
if (!empty($fundi['languages'])) {
    $languagesHtml = '<h3 style="margin-top:1rem;">Languages</h3><div class="chips">' .
        implode('', array_map(fn($l) => '<span class="chip">' . e($l) . '</span>', $fundi['languages'])) .
        '</div>';
}

// Build portfolio HTML
$portfolioHtml = '';
if ($portfolio) {
    $portfolioHtml = '
      <section class="panel">
        <h2>Portfolio (' . count($portfolio) . ')</h2>
        <div class="grid" style="grid-template-columns:repeat(auto-fill,minmax(180px,1fr));">
          ' . implode('', array_map(fn($p) => '
            <figure style="margin:0;">
              <img src="' . e($p['photo_path']) . '" alt="' . e($p['caption'] ?? '') . '" style="border-radius:12px;aspect-ratio:1;object-fit:cover;width:100%;">
              ' . ($p['caption'] ? '<figcaption class="muted small" style="margin-top:.4rem;">' . e($p['caption']) . '</figcaption>' : '') . '
            </figure>', $portfolio)) . '
        </div>
      </section>';
}

// Build working hours table (with today highlighting)
$hours = $fundi['working_hours'] ?? [];
$days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
$today = strtolower(date('l'));
$hoursRows = '';
foreach ($days as $d) {
    $label = ucfirst($d);
    $value = $hours[$d] ?? 'Closed';
    $todayClass = $d === $today ? 'today' : '';
    $hoursRows .= '<tr class="' . $todayClass . '"><td>' . e($label) . '</td><td>' . e($value) . '</td></tr>';
}

// Reviews HTML
$reviewsHtml = '';
if ($reviews) {
    foreach ($reviews as $r) {
        $userInitial = strtoupper(($r['user']['full_name'] ?? 'U')[0]);
        $avatar = $r['user']['profile_photo']
            ? '<img src="' . e($r['user']['profile_photo']) . '" alt="" class="avatar-img">'
            : '<span class="avatar">' . $userInitial . '</span>';
        
        $reactionsHtml = reactionsHtml($r);

        $reviewPhotosHtml = '';
        if (!empty($r['photos'])) {
            $reviewPhotosHtml = '<div class="review-photos">' .
                implode('', array_map(fn($p) => '<img src="' . e($p['photo_path']) . '" alt="" loading="lazy">', $r['photos'])) .
                '</div>';
        }

        $reviewsHtml .= '
          <article class="review-card">
            <header>
              ' . $avatar . '
              <div>
                <strong>' . e($r['user']['full_name'] ?? 'Anonymous') . '</strong>
                <span class="muted"> · ' . date('M j, Y', strtotime($r['created_at'])) . '</span>
              </div>
            </header>
            <div class="review-rating">' . starsHtml($r['rating']) . '</div>
            <h4>' . e($r['title']) . '</h4>
            <p>' . e($r['content']) . '</p>
            ' . $reviewPhotosHtml . '
            ' . $reactionsHtml . '
          </article>';
    }
} else {
    $reviewsHtml = '<p class="muted center" style="padding:1.5rem 0;">No reviews yet — be the first!</p>';
}

// Profile image or initial
$profileImg = $fundi['profile_photo']
    ? '<img class="avatar" style="width:84px;height:84px;font-size:2rem;" src="' . e($fundi['profile_photo']) . '" alt="">'
    : '<span class="avatar" style="width:84px;height:84px;font-size:2rem;">' . strtoupper(($fundi['full_name'] ?? 'F')[0]) . '</span>';

// Rate display
$rateHtml = '';
if ($fundi['hourly_rate_min']) {
    $rateHtml = '<a class="btn btn-ghost">🕐 ' . fmtMoney($fundi['hourly_rate_min']) . ($fundi['hourly_rate_max'] ? '–' . fmtMoney($fundi['hourly_rate_max']) : '') . '/hr</a>';
}

$mainContent = '
  <div class="detail-top">
    <div style="display:flex;align-items:center;gap:1.2rem;flex-wrap:wrap;">
      ' . $profileImg . '
      <div>
        <div class="rating-row">
          ' . starsHtml($fundi['rating_average']) . '
          <span class="rating-num">' . number_format((float)($fundi['rating_average'] ?? 0), 1) . '</span>
          <span class="muted">(' . ($fundi['review_count'] ?? 0) . ' reviews)</span>
          ' . ($fundi['is_verified'] ? '<span class="chip">✓ Verified</span>' : '') . '
        </div>
        <h1 class="detail-title">' . e($fundi['full_name']) . '</h1>
        <p class="muted">' . e($fundi['profession']) . ($fundi['years_experience'] ? ' · ' . $fundi['years_experience'] . ' years experience' : '') . ' · 📍 ' . e($fundi['city'] ?? '') . ($fundi['region'] ? ', ' . e($fundi['region']) : '') . '</p>
      </div>
    </div>
    <div class="detail-actions">
      ' . ($fundi['phone'] ? '<a class="btn btn-primary" href="tel:' . e($fundi['phone']) . '">📞 Call</a>' : '') . '
      ' . ($fundi['whatsapp'] ? '<a class="btn btn-outline" href="https://wa.me/' . e($fundi['whatsapp']) . '" target="_blank" rel="noopener">💬 WhatsApp</a>' : '') . '
      <button class="btn btn-primary" id="quoteBtnTop">✏️ Request a quote</button>
      <button class="btn btn-ghost share-btn" data-share-title="' . e($fundi['full_name']) . '" data-share-url="/fundi/' . $fundi['id'] . '" data-share-text="' . e($fundi['profession'] ?? $fundi['full_name']) . '">🔗 Share</button>
      ' . $rateHtml . '
    </div>
  </div>

  <div class="detail-grid">
    <div>
      <section class="panel">
        <h2>About</h2>
        <p class="about-text">' . e($fundi['bio'] ?? '') . '</p>
        ' . $skillsHtml . '
        ' . $languagesHtml . '
      </section>

      ' . $portfolioHtml . '

      <section class="panel">
        <h2>Reviews</h2>
        <div style="margin-bottom:1rem;">
          <button class="btn btn-outline save-btn" data-save-type="fundi" data-save-id="' . $fundi['id'] . '">🔖 Save</button>
          <button class="btn btn-primary" id="writeReviewBtn">✏️ Write a review</button>
        </div>
        ' . $reviewsHtml . '
      </section>
    </div>

    <aside>
      <div class="side-card">
        <h3>Working hours</h3>
        <table class="hours-table">
          ' . $hoursRows . '
        </table>
      </div>
      ' . ($fundi['phone'] || $fundi['email'] ? '
      <div class="side-card">
        <h3>Contact</h3>
        ' . ($fundi['phone'] ? '<div class="side-row"><span class="lbl">Phone</span><a href="tel:' . e($fundi['phone']) . '">' . e($fundi['phone']) . '</a></div>' : '') . '
        ' . ($fundi['whatsapp'] ? '<div class="side-row"><span class="lbl">WhatsApp</span><a href="https://wa.me/' . e($fundi['whatsapp']) . '" target="_blank" rel="noopener">Chat</a></div>' : '') . '
        ' . ($fundi['email'] ? '<div class="side-row"><span class="lbl">Email</span><a href="mailto:' . e($fundi['email']) . '">' . e($fundi['email']) . '</a></div>' : '') . '
      </div>' : '') . '
      <div class="side-card">
        <h3>Location</h3>
        <div id="fundiMap" class="map-box" style="height:200px;margin-bottom:.6rem;" data-lat="' . e((string)$fundi['latitude']) . '" data-lng="' . e((string)$fundi['longitude']) . '" data-name="' . e($fundi['full_name']) . '"></div>
        <div class="side-row"><span class="lbl">Address</span><span>' . e($fundi['city'] ?? '') . ($fundi['region'] ? ', ' . e($fundi['region']) : '') . '</span></div>
      </div>
      <div class="side-card" id="sponsoredSide"></div>
    </aside>
  </div>
';

// Include base template
include __DIR__ . '/base.php';