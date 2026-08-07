<?php
/**
 * Mosque detail SSR template
 * Uses shared helpers from includes/TemplateHelpers.php
 */
declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/Database.php';
require_once __DIR__ . '/../includes/PrayerTimes.php';
require_once __DIR__ . '/../includes/TemplateHelpers.php';

// Fetch mosque data
$id = (int)($_GET['id'] ?? 0);
if (!$id) {
    http_response_code(404);
    include __DIR__ . '/../404.php';
    exit;
}

$db = Database::getInstance();

$mosque = $db->fetchOne('SELECT * FROM mosques WHERE id = ?', [$id]);
if (!$mosque) {
    http_response_code(404);
    include __DIR__ . '/../404.php';
    exit;
}

$photos = $db->fetchAll(
    'SELECT id, photo_path, thumbnail_path, caption, is_primary
       FROM mosque_photos WHERE mosque_id = ? ORDER BY is_primary DESC, id ASC',
    [$id]
);

// Fetch reviews with reaction counts in single query (no N+1)
$reviews = fetchReviewsWithReactions($db, 'mosque', $id, 20);

// Decode facilities
$mosque = decodeJsonColumns($mosque, ['facilities']);

// Prayer times
$prayer = null;
if ($mosque['latitude'] && $mosque['longitude']) {
    $calc = new PrayerTimes($mosque['latitude'], $mosque['longitude']);
    $today = $calc->getTimes();
    $week = [];
    for ($i = 0; $i < 7; $i++) {
        $d = date('Y-m-d', strtotime("+$i days"));
        $week[] = ['date' => $d, 'day' => date('D', strtotime($d)), 'times' => $calc->getTimesForDate($d)];
    }
    $prayer = [
        'today' => $today,
        'next'  => $calc->getNextPrayer(),
        'week'  => $week,
    ];
}

require_once __DIR__ . '/../includes/PreRenderer.php';

// Page variables
$firstPhoto = $photos[0]['photo_path'] ?? '';
$pageUrl = getCurrentPageUrl();
$jsonLd = buildMosqueJsonLd($mosque, $firstPhoto, $pageUrl);
$extraHead = PreRenderer::renderHeaderTags('mosque', $id, $mosque);

$pageTitle = $mosque['name'] . ' — Ummah Directory';
$pageDescription = ($mosque['address'] ?? $mosque['name']) . ($mosque['city'] ? ', ' . $mosque['city'] : '');
[$pageScript, $lang, $dir] = ['mosque.js', ...getLangAndDir()];

// Build prayer times HTML
$prayerBoxes = '';
if ($prayer && $prayer['today']) {
    $today = $prayer['today'];
    $next = $prayer['next'] ?? null;
    foreach (['Fajr', 'Sunrise', 'Dhuhr', 'Asr', 'Maghrib', 'Isha'] as $name) {
        $active = $next && $next['name'] === $name ? ' active' : '';
        $prayerBoxes .= '<div class="prayer-box' . $active . '"><span>' . e($name) . '</span><b>' . e($today[$name] ?? '--:--') . '</b></div>';
    }
} else {
    $prayerBoxes = '<p class="muted">Prayer times unavailable for this location.</p>';
}

$nextPrayerHtml = '';
if ($prayer && $prayer['next']) {
    $next = $prayer['next'];
    $nextPrayerHtml = '<p class="muted small" style="margin-top:.8rem;">Next prayer: <b>' . e($next['name']) . '</b> at ' . e($next['time']) . ' (' . (int)$next['remaining_minutes'] . ' min)</p>';
}

$weekRows = '';
if ($prayer && $prayer['week']) {
    foreach ($prayer['week'] as $d) {
        $weekRows .= '<tr><td>' . e($d['day']) . '</td><td>' . e($d['times']['Fajr'] ?? '--:--') . '</td><td>' . e($d['times']['Dhuhr'] ?? '--:--') . '</td><td>' . e($d['times']['Maghrib'] ?? '--:--') . '</td></tr>';
    }
}

// Facilities HTML
$facList = [
    ['women_section', "Women's prayer area"],
    ['wudu', 'Wudu facilities'],
    ['parking', 'Parking'],
    ['wheelchair', 'Wheelchair accessible'],
    ['quran_classes', 'Quran classes'],
    ['ramadan_iftar', 'Ramadan iftar'],
];
$facHtml = '';
$fac = $mosque['facilities'] ?? [];
foreach ($facList as [$key, $label]) {
    $class = $fac[$key] ? 'on' : 'off';
    $facHtml .= '<li class="' . $class . '">' . e($label) . '</li>';
}

// Photos HTML
$photosHtml = '';
if ($photos) {
    $sidePhotos = array_slice($photos, 1, 2);
    $photosHtml = '
      <div class="gallery">
        <div class="gallery-main"><img src="' . e($photos[0]['photo_path']) . '" alt="' . e($mosque['name']) . '"></div>
        <div class="gallery-side">' . implode('', array_map(fn($p) => '<img src="' . e($p['photo_path']) . '" alt="' . e($mosque['name']) . ' - photo">', $sidePhotos)) . '</div>
      </div>';
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

$mainContent = '
  <div class="detail-top">
    <div class="rating-row">
      ' . starsHtml($mosque['rating_average']) . '
      <span class="rating-num">' . number_format((float)($mosque['rating_average'] ?? 0), 1) . '</span>
      <span class="muted">(' . ($mosque['review_count'] ?? 0) . ' reviews)</span>
      ' . ($mosque['is_verified'] ? '<span class="chip">✓ Verified</span>' : '') . '
    </div>
    <h1 class="detail-title">' . e($mosque['name']) . '</h1>
    <p class="muted">📍 ' . e($mosque['address'] ?? '') . ($mosque['city'] ? ', ' . e($mosque['city']) : '') . ($mosque['region'] ? ', ' . e($mosque['region']) : '') . '</p>
    <div class="detail-actions">
      ' . ($mosque['phone'] ? '<a class="btn btn-primary" href="tel:' . e($mosque['phone']) . '">📞 Call</a>' : '') . '
      ' . ($mosque['latitude'] ? '<a class="btn btn-outline" href="https://www.google.com/maps?q=' . $mosque['latitude'] . ',' . $mosque['longitude'] . '" target="_blank" rel="noopener">🧭 Directions</a>' : '') . '
      <button class="btn btn-ghost save-btn" data-save-type="mosque" data-save-id="' . $mosque['id'] . '">🔖 Save</button>
      <button class="btn btn-ghost" id="checkinBtn">📍 Check in</button>
      <button class="btn btn-ghost share-btn" data-share-title="' . e($mosque['name']) . '" data-share-url="/mosque/' . $mosque['id'] . '" data-share-text="' . e($mosque['address'] ?? $mosque['name']) . '">🔗 Share</button>
      <button class="btn btn-outline" id="writeReviewBtn">✏️ Write a review</button>
    </div>
  </div>

  ' . $photosHtml . '

  <div class="detail-grid">
    <div>
      <section class="panel">
        <h2>About</h2>
        <p class="about-text">' . e($mosque['description'] ?? '') . '</p>
        ' . ($mosque['imam_name'] ? '<p style="margin-top:.8rem;"><b>Imam:</b> ' . e($mosque['imam_name']) . '</p>' : '') . '
        ' . ($mosque['capacity'] ? '<p><b>Capacity:</b> ' . number_format((int)$mosque['capacity']) . '</p>' : '') . '
      </section>

      <section class="panel">
        <h2>Today\'s prayer times</h2>
        <div class="prayer-grid">' . $prayerBoxes . '</div>
        ' . $nextPrayerHtml . '
      </section>

      <section class="panel">
        <h2>This week</h2>
        <table class="week-table">
          <thead><tr><th>Day</th><th>Fajr</th><th>Dhuhr</th><th>Maghrib</th></tr></thead>
          <tbody>' . $weekRows . '</tbody>
        </table>
      </section>

      <section class="panel">
        <h2>Reviews</h2>
        <div id="reviewsList">
          ' . $reviewsHtml . '
        </div>
      </section>
    </div>

    <aside>
      <div class="side-card">
        <h3>Facilities</h3>
        <ul class="facility-list">
          ' . $facHtml . '
        </ul>
      </div>
      ' . ($mosque['phone'] || $mosque['email'] ? '
      <div class="side-card">
        <h3>Contact</h3>
        ' . ($mosque['phone'] ? '<div class="side-row"><span class="lbl">Phone</span><a href="tel:' . e($mosque['phone']) . '">' . e($mosque['phone']) . '</a></div>' : '') . '
        ' . ($mosque['email'] ? '<div class="side-row"><span class="lbl">Email</span><a href="mailto:' . e($mosque['email']) . '">' . e($mosque['email']) . '</a></div>' : '') . '
      </div>' : '') . '
      <div class="side-card">
        <h3>Location</h3>
        <div id="mosqueMap" class="map-box" style="height:200px;margin-bottom:.6rem;" data-lat="' . e((string)$mosque['latitude']) . '" data-lng="' . e((string)$mosque['longitude']) . '" data-name="' . e($mosque['name']) . '"></div>
        <div class="side-row"><span class="lbl">Address</span><span>' . e($mosque['address'] ?? '') . ($mosque['city'] ? ', ' . e($mosque['city']) : '') . '</span></div>
      </div>
      <div class="side-card" id="sponsoredSide"></div>
    </aside>
  </div>
';

// Include base template
include __DIR__ . '/base.php';