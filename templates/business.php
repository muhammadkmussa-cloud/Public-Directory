<?php
/**
 * Business detail SSR template
 * Uses shared helpers from includes/TemplateHelpers.php
 */
declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/Database.php';
require_once __DIR__ . '/../includes/TemplateHelpers.php';

// Fetch business data
$id = (int)($_GET['id'] ?? 0);
if (!$id) {
    http_response_code(404);
    include __DIR__ . '/../404.php';
    exit;
}

$db = Database::getInstance();

$business = $db->fetchOne(
    'SELECT b.*,
            (SELECT c.name FROM categories c
               JOIN business_categories bc ON bc.category_id = c.id AND bc.business_id = b.id
              ORDER BY bc.is_primary DESC, c.display_order LIMIT 1) AS category_name,
            (SELECT c.slug FROM categories c
               JOIN business_categories bc ON bc.category_id = c.id AND bc.business_id = b.id
              ORDER BY bc.is_primary DESC, c.display_order LIMIT 1) AS category_slug,
            (SELECT c.icon FROM categories c
               JOIN business_categories bc ON bc.category_id = c.id AND bc.business_id = b.id
              ORDER BY bc.is_primary DESC, c.display_order LIMIT 1) AS category_icon
       FROM businesses b WHERE b.id = ?',
    [$id]
);

if (!$business) {
    http_response_code(404);
    include __DIR__ . '/../404.php';
    exit;
}

$photos = $db->fetchAll(
    'SELECT id, photo_path, thumbnail_path, caption, is_primary
       FROM business_photos WHERE business_id = ? ORDER BY is_primary DESC, id ASC',
    [$id]
);

// Fetch reviews with reaction counts in single query (no N+1)
$reviews = fetchReviewsWithReactions($db, 'business', $id, 20);

// Rating breakdown
$breakdown = array_fill(1, 5, 0);
foreach ($db->fetchAll(
    'SELECT rating, COUNT(*) AS c FROM reviews
      WHERE reviewable_id = ? AND reviewable_type = \'business\'
        AND is_approved = 1 AND is_hidden = 0
      GROUP BY rating',
    [$id]
) as $row) {
    $breakdown[(int)$row['rating']] = (int)$row['c'];
}
$subAverages = $db->fetchOne(
    'SELECT AVG(rating_service) AS avg_service,
            AVG(rating_value) AS avg_value,
            AVG(rating_ambience) AS avg_ambience,
            AVG(rating_cleanliness) AS avg_cleanliness
       FROM reviews
      WHERE reviewable_id = ? AND reviewable_type = \'business\'
        AND is_approved = 1 AND is_hidden = 0',
    [$id]
);

// Similar businesses
$similar = $db->fetchAll(
    'SELECT b.id, b.name, b.slug, b.city, b.price_range, b.is_verified,
            b.rating_average, b.review_count,
            (SELECT photo_path FROM business_photos WHERE business_id = b.id
              ORDER BY is_primary DESC, id DESC LIMIT 1) AS primary_photo
       FROM businesses b
       JOIN business_categories bc ON bc.business_id = b.id
       JOIN categories c ON c.id = bc.category_id
       JOIN (SELECT category_id FROM business_categories WHERE business_id = ? LIMIT 1) mine
         ON mine.category_id = c.id
      WHERE b.id != ? AND b.is_open = 1
      GROUP BY b.id
      ORDER BY b.rating_average DESC, b.review_count DESC
      LIMIT 4',
    [$id, $id]
);

// Decode JSON columns
$business = decodeJsonColumns($business, ['opening_hours', 'amenities', 'payment_methods', 'languages']);

// Claim banner — shown on any listing the viewer does not own (approved).
$claimBanner = '';
$claimStatus = $business['claim_status'] ?? 'none';
if ($claimStatus === 'pending') {
    $claimBanner = '
      <div class="claim-banner pending">
        <strong>Claim pending review</strong>
        <span class="muted small">An admin is reviewing this listing’s claim request.</span>
      </div>';
} elseif ($claimStatus !== 'approved') {
    $claimBanner = '
      <div class="claim-banner">
        <div>
          <strong>Own this business?</strong>
          <span class="muted small">Claim your listing to respond to reviews and keep your details accurate.</span>
        </div>
        <a class="btn btn-primary btn-sm" href="/business-claim/' . (int)$business['id'] . '">Claim this business</a>
      </div>';
}

require_once __DIR__ . '/../includes/PreRenderer.php';

// Page variables
$firstPhoto = $photos[0]['photo_path'] ?? '';
$pageUrl = getCurrentPageUrl();
$jsonLd = buildBusinessJsonLd($business, $firstPhoto, $pageUrl);
$extraHead = PreRenderer::renderHeaderTags('business', $id, $business);

$pageTitle = $business['name'] . ' — Ummah Directory';
$pageDescription = ($business['short_description'] ?? $business['description'] ?? '') . ' — ' . ($business['city'] ?? '');
[$pageScript, $lang, $dir] = ['business.js', ...getLangAndDir()];

// Build hours table
$hours = $business['opening_hours'] ?? [];
$today = strtolower(date('l'));
$days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
$hoursRows = '';
foreach ($days as $d) {
    $label = ucfirst($d);
    $value = $hours[$d] ?? 'Closed';
    $todayClass = $d === $today ? 'today' : '';
    $hoursRows .= '<tr class="' . $todayClass . '"><td>' . e($label) . '</td><td>' . e($value) . '</td></tr>';
}

// Build amenities HTML
$amenitiesHtml = '';
if (!empty($business['amenities'])) {
    $amenitiesHtml = '<h3 style="margin-top:1.2rem;">Features & amenities</h3><div class="amenity-list">' .
        implode('', array_map(fn($a) => '<span class="amenity">✓ ' . e($a) . '</span>', $business['amenities'])) .
        '</div>';
}

// Build photos HTML
$photosHtml = '';
if ($photos) {
    $sidePhotos = array_slice($photos, 1, 2);
    $photosHtml = '
      <div class="gallery">
        <div class="gallery-main"><img src="' . e($photos[0]['photo_path']) . '" alt="' . e($business['name']) . '"></div>
        <div class="gallery-side">' .
        implode('', array_map(fn($p) => '<img src="' . e($p['photo_path']) . '" alt="' . e($business['name']) . ' - photo">', $sidePhotos)) .
        '</div>
      </div>';
}

// Build reviews HTML
$reviewsHtml = '';
$total = array_sum($breakdown);
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

        $verifiedVisit = $r['is_verified_visit'] ? '<span class="verified-visit">✓ Verified visit</span>' : '';
        $ownerResponseHtml = !empty($r['owner_response']) ? '<div class="owner-response"><strong>Owner response:</strong> ' . e($r['owner_response']) . '</div>' : '';

        $subList = [];
        if (!empty($r['rating_service'])) $subList[] = 'Service: ' . (int)$r['rating_service'] . '★';
        if (!empty($r['rating_value'])) $subList[] = 'Value: ' . (int)$r['rating_value'] . '★';
        if (!empty($r['rating_ambience'])) $subList[] = 'Ambience: ' . (int)$r['rating_ambience'] . '★';
        if (!empty($r['rating_cleanliness'])) $subList[] = 'Cleanliness: ' . (int)$r['rating_cleanliness'] . '★';
        $subRatingsChips = $subList ? '<div class="muted small" style="margin:0.2rem 0 0.5rem;display:flex;gap:0.6rem;flex-wrap:wrap;">' . implode(' · ', $subList) . '</div>' : '';

        $reviewsHtml .= '
          <article class="review-card">
            <header>
              ' . $avatar . '
              <div>
                <strong>' . e($r['user']['full_name'] ?? 'Anonymous') . '</strong>
                <span class="muted"> · ' . date('M j, Y', strtotime($r['created_at'])) . '</span>
                ' . $verifiedVisit . '
              </div>
            </header>
            <div class="review-rating">' . starsHtml($r['rating']) . '</div>
            ' . $subRatingsChips . '
            <h4>' . e($r['title']) . '</h4>
            <p>' . e($r['content']) . '</p>
            ' . $reviewPhotosHtml . '
            ' . $reactionsHtml . '
            ' . $ownerResponseHtml . '
          </article>';
    }
} else {
    $reviewsHtml = '<p class="muted center" style="padding:1.5rem 0;">No reviews yet — be the first!</p>';
}

// Rating breakdown HTML
$ratingBreakdownHtml = '';
foreach ([5, 4, 3, 2, 1] as $n) {
    $c = $breakdown[$n] ?? 0;
    $pct = $total ? round(($c / $total) * 100) : 0;
    $ratingBreakdownHtml .= '<div class="break-row"><span>' . $n . '★</span><div class="break-bar"><i style="width:' . $pct . '%"></i></div><span class="break-count">' . $c . '</span></div>';
}

if ($subAverages && (array_filter($subAverages))) {
    $subCategoryParts = [];
    if (!empty($subAverages['avg_service'])) $subCategoryParts[] = '<span>Service: <b>' . number_format((float)$subAverages['avg_service'], 1) . '★</b></span>';
    if (!empty($subAverages['avg_value'])) $subCategoryParts[] = '<span>Value: <b>' . number_format((float)$subAverages['avg_value'], 1) . '★</b></span>';
    if (!empty($subAverages['avg_ambience'])) $subCategoryParts[] = '<span>Ambience: <b>' . number_format((float)$subAverages['avg_ambience'], 1) . '★</b></span>';
    if (!empty($subAverages['avg_cleanliness'])) $subCategoryParts[] = '<span>Cleanliness: <b>' . number_format((float)$subAverages['avg_cleanliness'], 1) . '★</b></span>';
    
    if ($subCategoryParts) {
        $ratingBreakdownHtml .= '<div style="display:flex;gap:1rem;flex-wrap:wrap;margin-top:0.8rem;padding-top:0.8rem;border-top:1px solid #eee;font-size:0.85rem;" class="muted">' . implode('', $subCategoryParts) . '</div>';
    }
}

// Similar businesses HTML
$similarHtml = '';
if ($similar) {
    $similarCards = '';
    foreach ($similar as $b) {
        $similarCards .= '
          <a class="card" href="/business/' . (int)$b['id'] . '">
            <div class="card-img">
              <img src="' . e($b['primary_photo'] ?? 'assets/img/sample/restaurant-1.svg') . '" alt="' . e($b['name']) . '" loading="lazy">
              ' . ($b['is_verified'] ? '<span class="badge verified">✓ Verified</span>' : '') . '
            </div>
            <div class="card-body">
              <h3>' . e($b['name']) . '</h3>
              <div class="card-meta">
                ' . starsHtml($b['rating_average']) . '
                <span class="muted">(' . ($b['review_count'] ?? 0) . ')</span>
                <span class="price">' . e($b['price_range'] ?? '$') . '</span>
              </div>
              <p class="muted small">' . e($b['city']) . '</p>
            </div>
          </a>';
    }
    $similarHtml = '
      <section class="section">
        <div class="section-head"><div><h2>Similar businesses</h2></div><a class="btn btn-outline btn-sm" href="businesses">View all</a></div>
        <div class="grid">' . $similarCards . '</div>
      </section>';
}

$mainContent = '
  <div class="detail-top">
    <div class="rating-row">
      ' . starsHtml($business['rating_average']) . '
      <span class="rating-num">' . number_format((float)($business['rating_average'] ?? 0), 1) . '</span>
      <span class="muted">(' . ($business['review_count'] ?? 0) . ' reviews)</span>
      ' . ($business['is_verified'] ? '<span class="chip">✓ Verified</span>' : '') . '
      ' . ($business['category_name'] ? '<span class="chip">' . e($business['category_name']) . '</span>' : '') . '
      <span class="price">' . e($business['price_range'] ?? '$') . '</span>
    </div>
    <h1 class="detail-title">' . e($business['name']) . '</h1>
    <p class="muted">📍 ' . e($business['address'] ?? '') . ($business['city'] ? ', ' . e($business['city']) : '') . ($business['region'] ? ', ' . e($business['region']) : '') . '</p>
    <div class="detail-actions">
      ' . ($business['phone'] ? '<a class="btn btn-primary" href="tel:' . e($business['phone']) . '">📞 Call</a>' : '') . '
      ' . ($business['whatsapp'] ? '<a class="btn btn-outline" href="https://wa.me/' . e($business['whatsapp']) . '" target="_blank" rel="noopener">💬 WhatsApp</a>' : '') . '
      ' . ($business['website'] ? '<a class="btn btn-outline" href="' . e($business['website']) . '" target="_blank" rel="noopener">🌐 Website</a>' : '') . '
      ' . ($business['latitude'] ? '<a class="btn btn-outline" href="https://www.google.com/maps?q=' . $business['latitude'] . ',' . $business['longitude'] . '" target="_blank" rel="noopener">🧭 Directions</a>' : '') . '
      <button class="btn btn-ghost save-btn" data-save-type="business" data-save-id="' . $business['id'] . '">🔖 Save</button>
      <button class="btn btn-ghost" id="checkinBtn">📍 Check in</button>
      <button class="btn btn-ghost share-btn" data-share-title="' . e($business['name']) . '" data-share-url="/business/' . $business['id'] . '" data-share-text="' . e($business['short_description'] ?? $business['name']) . '">🔗 Share</button>
      <button class="btn btn-ghost report-btn" data-report-type="business" data-report-id="' . $business['id'] . '">🚩 Report</button>
    </div>
  </div>

  ' . $claimBanner . '

  ' . $photosHtml . '

  <div class="detail-grid">
    <div>
      <section class="panel">
        <h2>About</h2>
        <p class="about-text">' . e($business['description'] ?? 'No description yet.') . '</p>
        ' . $amenitiesHtml . '
      </section>

      <section class="panel" id="menuSection" data-owner-id="' . (int)($business['user_id'] ?? 0) . '" style="display:none;">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1rem;">
          <h2 style="margin:0;">🍽️ Menu & Popular Dishes</h2>
          <button class="btn btn-outline btn-sm" id="manageMenuBtn" style="display:none;">⚙️ Manage Menu</button>
        </div>
        <div id="menuContainer"></div>
      </section>

      <section class="panel">
        <h2>Reviews (' . count($reviews) . ')</h2>
        <div class="rating-breakdown">
          ' . $ratingBreakdownHtml . '
        </div>
        <div style="margin:1.2rem 0 0;">
          <button class="btn btn-primary" id="writeReviewBtn">✏️ Write a review</button>
        </div>
        <div id="reviewsList">
          ' . $reviewsHtml . '
        </div>
      </section>
    </div>

    <aside>
      <div class="side-card">
        <h3>Contact</h3>
        ' . ($business['phone'] ? '<div class="side-row"><span class="lbl">Phone</span><a href="tel:' . e($business['phone']) . '">' . e($business['phone']) . '</a></div>' : '') . '
        ' . ($business['whatsapp'] ? '<div class="side-row"><span class="lbl">WhatsApp</span><a href="https://wa.me/' . e($business['whatsapp']) . '" target="_blank" rel="noopener">Chat</a></div>' : '') . '
        ' . ($business['email'] ? '<div class="side-row"><span class="lbl">Email</span><a href="mailto:' . e($business['email']) . '">' . e($business['email']) . '</a></div>' : '') . '
        ' . ($business['website'] ? '<div class="side-row"><span class="lbl">Website</span><a href="' . e($business['website']) . '" target="_blank" rel="noopener">Visit</a></div>' : '') . '
      </div>

      <div class="side-card">
        <h3>Opening hours</h3>
        <table class="hours-table">
          ' . $hoursRows . '
        </table>
      </div>

      ' . ($business['checkin_count'] ? '<div class="side-card center"><b style="font-size:1.8rem;color:var(--brand);">' . (int)$business['checkin_count'] . '</b><br><span class="muted small">check-ins</span></div>' : '') . '

      <div class="side-card">
        <h3>Location</h3>
        <div id="bizMap" class="map-box" style="height:200px;margin-bottom:.6rem;" data-lat="' . e((string)$business['latitude']) . '" data-lng="' . e((string)$business['longitude']) . '" data-name="' . e($business['name']) . '" data-category="' . e($business['category_slug'] ?? '') . '" data-city="' . e($business['city'] ?? '') . '"></div>
        <div class="side-row"><span class="lbl">Address</span><span id="bizAddress">' . e($business['address'] ?? '') . ($business['city'] ? ', ' . e($business['city']) : '') . '</span></div>
      </div>
      <div class="side-card" id="sponsoredSide"></div>
    </aside>
  </div>

  ' . $similarHtml;

// Include base template
include __DIR__ . '/base.php';