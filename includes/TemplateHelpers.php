<?php
/**
 * Shared template helpers — eliminates duplication across detail templates
 */
declare(strict_types=1);

/**
 * Escape for HTML attribute/text context
 */
function e(string $s): string {
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}

/**
 * Escape for JS string context (used in inline script data attributes)
 */
function je($v): string {
    return json_encode($v, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
}

/**
 * Generate star rating HTML
 */
function starsHtml($rating): string {
    $rating = max(0, min(5, (float)($rating ?? 0)));
    $full = (int)$rating;
    $half = $rating - $full >= 0.5;
    $html = '<span class="stars" aria-label="' . $rating . ' out of 5 stars">';
    for ($i = 0; $i < $full; $i++) $html .= '<span class="star on">★</span>';
    if ($half) $html .= '<span class="star half">★</span>';
    for ($i = $full + ($half ? 1 : 0); $i < 5; $i++) $html .= '<span class="star">★</span>';
    return $html . '</span>';
}

/**
 * Format money with thousands separator (Kenyan style)
 */
function fmtMoney($n): string {
    return number_format((float)$n, 0, '.', ',');
}

/**
 * Format date as M j, Y
 */
function fmtDate($iso): string {
    if (!$iso) return '';
    $d = new DateTime($iso);
    return $d->format('M j, Y');
}

/**
 * Attach review photos to review objects (single query)
 */
function attachReviewPhotos(Database $db, array &$reviews): void {
    if (!$reviews) return;
    $ids = array_column($reviews, 'id');
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $rows = $db->fetchAll(
        "SELECT rp.*, r.reviewable_type FROM review_photos rp JOIN reviews r ON r.id = rp.review_id WHERE rp.review_id IN ($placeholders)",
        $ids
    );
    $byReview = [];
    foreach ($rows as $row) {
        $byReview[$row['review_id']][] = $row;
    }
    foreach ($reviews as &$r) {
        $r['photos'] = $byReview[$r['id']] ?? [];
    }
}

/**
 * Fetch reviews with reaction counts in a single query (no N+1)
 * Returns array of review objects with useful_count, funny_count, cool_count
 */
function fetchReviewsWithReactions(Database $db, string $reviewableType, int $reviewableId, int $limit = 20): array {
    $reviews = $db->fetchAll(
        "SELECT r.id, r.rating, r.rating_service, r.rating_value, r.rating_ambience, r.rating_cleanliness,
                r.title, r.content, r.helpful_count, r.is_verified_visit,
                r.owner_response, r.created_at,
                COUNT(CASE WHEN rh.reaction_type = 'useful' THEN 1 END) AS useful_count,
                COUNT(CASE WHEN rh.reaction_type = 'funny' THEN 1 END) AS funny_count,
                COUNT(CASE WHEN rh.reaction_type = 'cool' THEN 1 END) AS cool_count,
                MAX(u.full_name) AS full_name,
                MAX(u.profile_photo) AS profile_photo,
                MAX(u.contributor_level) AS contributor_level,
                MAX(u.verification_badge) AS verification_badge
           FROM reviews r
           JOIN users u ON u.id = r.user_id
           LEFT JOIN review_helpful rh ON rh.review_id = r.id
          WHERE r.reviewable_id = ? AND r.reviewable_type = ? AND r.is_approved = 1 AND r.is_hidden = 0
          GROUP BY r.id
          ORDER BY r.created_at DESC
          LIMIT ?",
        [$reviewableId, $reviewableType, $limit]
    );

    foreach ($reviews as &$r) {
        $r['user'] = ['full_name' => $r['full_name'], 'profile_photo' => $r['profile_photo']];
        unset($r['full_name'], $r['profile_photo']);
    }

    attachReviewPhotos($db, $reviews);
    return $reviews;
}

/**
 * Build JSON-LD for LocalBusiness (businesses)
 */
function buildBusinessJsonLd(array $business, ?string $firstPhoto, string $pageUrl): string {
    return json_encode([
        '@context' => 'https://schema.org',
        '@type' => 'LocalBusiness',
        'name' => $business['name'],
        'image' => $firstPhoto,
        'telephone' => $business['phone'] ?? '',
        'address' => [
            '@type' => 'PostalAddress',
            'streetAddress' => $business['address'] ?? '',
            'addressLocality' => $business['city'] ?? '',
            'addressRegion' => $business['region'] ?? '',
            'addressCountry' => $business['country'] ?? 'KE',
        ],
        'aggregateRating' => $business['rating_average'] ? [
            '@type' => 'AggregateRating',
            'ratingValue' => (float)$business['rating_average'],
            'reviewCount' => (int)($business['review_count'] ?? 0),
        ] : null,
        'url' => $pageUrl,
        'priceRange' => $business['price_range'] ?? '$$',
    ], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
}

/**
 * Build JSON-LD for Place (mosques)
 */
function buildMosqueJsonLd(array $mosque, ?string $firstPhoto, string $pageUrl): string {
    return json_encode([
        '@context' => 'https://schema.org',
        '@type' => 'Place',
        'name' => $mosque['name'],
        'description' => $mosque['description'] ?? '',
        'image' => $firstPhoto,
        'address' => [
            '@type' => 'PostalAddress',
            'streetAddress' => $mosque['address'] ?? '',
            'addressLocality' => $mosque['city'] ?? '',
            'addressRegion' => $mosque['region'] ?? '',
            'addressCountry' => $mosque['country'] ?? 'KE',
        ],
        'url' => $pageUrl,
    ], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
}

/**
 * Build JSON-LD for Person (fundis)
 */
function buildFundiJsonLd(array $fundi, string $pageUrl): string {
    return json_encode([
        '@context' => 'https://schema.org',
        '@type' => 'Person',
        'name' => $fundi['full_name'],
        'jobTitle' => $fundi['profession'],
        'description' => $fundi['bio'] ?? '',
        'image' => $fundi['profile_photo'] ?? '',
        'url' => $pageUrl,
    ], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
}

/**
 * Build JSON-LD for Organization (charities)
 */
function buildCharityJsonLd(array $charity, ?string $logoOrCover, string $pageUrl): string {
    return json_encode([
        '@context' => 'https://schema.org',
        '@type' => 'Organization',
        'name' => $charity['name'],
        'description' => $charity['description'] ?? '',
        'url' => $charity['website'] ?? $pageUrl,
        'logo' => $logoOrCover,
        'address' => [
            '@type' => 'PostalAddress',
            'addressLocality' => $charity['city'] ?? '',
            'addressCountry' => 'KE',
        ],
        'telephone' => $charity['phone'] ?? '',
        'email' => $charity['email'] ?? '',
    ], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
}

/**
 * Get current page URL
 */
function getCurrentPageUrl(): string {
    return (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
}

/**
 * Get language/direction from cookie (with fallback)
 */
function getLangAndDir(): array {
    $lang = $_COOKIE['ummah_lang'] ?? 'en';
    return [$lang, $lang === 'ar' ? 'rtl' : 'ltr'];
}

/**
 * Build verified badge HTML
 */
function buildVerifiedChip(bool $isVerified): string {
    return $isVerified ? '<span class="chip">✓ Verified</span>' : '';
}

/**
 * Decode JSON columns in a row (for entities with JSON columns)
 */
function decodeJsonColumns(array $row, array $cols): array {
    foreach ($cols as $col) {
        if (!empty($row[$col])) {
            $decoded = json_decode($row[$col], true);
            $row[$col] = is_array($decoded) ? $decoded : [];
        }
    }
    return $row;
}

/**
 * Render review reaction buttons (Useful / Funny / Cool) — mirrors the
 * SPA reviewCard markup in app.js so the delegated .react-btn handler
 * in app.js works on SSR-rendered review cards.
 */
function reactionsHtml(array $r): string {
    $html = '<div class="review-actions">';
    foreach ([['useful', '👍', 'Useful'], ['funny', '😂', 'Funny'], ['cool', '😎', 'Cool']] as [$type, $emoji, $label]) {
        $count = $r[$type . '_count'] ?? 0;
        $html .= '<button type="button" class="reaction-btn react-btn" data-review-id="' . (int)$r['id'] . '" data-react="' . $type . '">'
            . $emoji . ' <span class="react-label">' . $label . '</span> <span class="react-count">' . (int)$count . '</span></button>';
    }
    return $html . '</div>';
}