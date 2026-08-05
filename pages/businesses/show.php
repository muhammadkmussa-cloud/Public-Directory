<?php
/**
 * Business Detail Page - Yelp Style
 * Shows complete business information, reviews, photos, and actions
 */

$db = Database::getInstance();

// Get business ID from URL
$businessId = intval($id ?? 0);

if (!$businessId) {
    header('Location: /businesses');
    exit;
}

// Get business details
$business = $db->query("SELECT * FROM businesses WHERE id = ? AND status = 'active'", [$businessId])->fetch();

if (!$business) {
    http_response_code(404);
    include BASE_PATH . '/pages/errors/404.php';
    exit;
}

// Get reviews
$reviews = $db->query("
    SELECT r.*, u.name as user_name, u.avatar_url
    FROM reviews r
    JOIN users u ON r.user_id = u.id
    WHERE r.business_id = ?
    ORDER BY r.created_at DESC
    LIMIT 10
", [$businessId])->fetchAll();

// Get photos
$photos = $db->query("SELECT * FROM business_photos WHERE business_id = ? ORDER BY is_primary DESC, created_at DESC", [$businessId])->fetchAll();

// Get average rating and count
$ratingStats = $db->query("
    SELECT AVG(rating) as avg_rating, COUNT(*) as review_count,
           SUM(CASE WHEN rating = 5 THEN 1 ELSE 0 END) as five_star,
           SUM(CASE WHEN rating = 4 THEN 1 ELSE 0 END) as four_star,
           SUM(CASE WHEN rating = 3 THEN 1 ELSE 0 END) as three_star,
           SUM(CASE WHEN rating = 2 THEN 1 ELSE 0 END) as two_star,
           SUM(CASE WHEN rating = 1 THEN 1 ELSE 0 END) as one_star
    FROM reviews WHERE business_id = ?
", [$businessId])->fetch();

// Get similar businesses
$similarBusinesses = $db->query("
    SELECT b.*, AVG(r.rating) as avg_rating, COUNT(r.id) as review_count
    FROM businesses b
    LEFT JOIN reviews r ON b.id = r.business_id
    WHERE b.category = ? AND b.id != ? AND b.status = 'active'
    GROUP BY b.id
    ORDER BY avg_rating DESC
    LIMIT 4
", [$business['category'], $businessId])->fetchAll();

$pageTitle = $business['name'];
include BASE_PATH . '/pages/layouts/header.php';
?>

<div class="business-detail-page">
    <div class="container">
        <!-- Business Header -->
        <div class="business-header">
            <div class="business-header-content">
                <h1><?= htmlspecialchars($business['name']) ?></h1>
                <div class="rating-summary">
                    <div class="rating-display large">
                        <?= str_repeat('★', floor($ratingStats['avg_rating'] ?? 0)) ?>
                        <?= str_repeat('☆', 5 - floor($ratingStats['avg_rating'] ?? 0)) ?>
                    </div>
                    <span class="rating-count"><?= number_format($ratingStats['review_count'] ?? 0) ?> reviews</span>
                    <?php if ($business['is_verified']): ?>
                    <span class="verified-badge-large" title="Verified Business">✓ Verified</span>
                    <?php endif; ?>
                </div>
                <p class="business-category-detail"><?= htmlspecialchars($business['category']) ?> • <?= str_repeat('$', $business['price_range'] ?? 1) ?></p>
            </div>
            
            <div class="business-actions">
                <a href="tel:<?= htmlspecialchars($business['phone']) ?>" class="btn btn-primary">📞 Call</a>
                <a href="<?= htmlspecialchars($business['website']) ?>" target="_blank" class="btn btn-outline">🌐 Website</a>
                <button class="btn btn-outline share-btn">📤 Share</button>
                <?php if (Auth::check()): ?>
                <button class="btn btn-outline save-btn" data-business-id="<?= $business['id'] ?>">💾 Save</button>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Photo Gallery -->
        <?php if (!empty($photos)): ?>
        <div class="photo-gallery">
            <div class="main-photo">
                <img src="<?= htmlspecialchars($photos[0]['photo_url']) ?>" alt="<?= htmlspecialchars($business['name']) ?>">
            </div>
            <div class="photo-thumbnails">
                <?php foreach (array_slice($photos, 1, 4) as $photo): ?>
                <img src="<?= htmlspecialchars($photo['photo_url']) ?>" alt="Photo" class="thumbnail" data-full="<?= htmlspecialchars($photo['photo_url']) ?>">
                <?php endforeach; ?>
                <?php if (count($photos) > 5): ?>
                <div class="view-all-photos">+<?= count($photos) - 5 ?> more</div>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Main Content Grid -->
        <div class="detail-grid">
            <!-- Left Column -->
            <div class="detail-main">
                <!-- About Section -->
                <section class="detail-section">
                    <h2>About</h2>
                    <p><?= nl2br(htmlspecialchars($business['description'])) ?></p>
                    
                    <?php if ($business['features']): ?>
                    <?php $features = json_decode($business['features'], true); ?>
                    <?php if (is_array($features)): ?>
                    <div class="features-list">
                        <h3>Features & Amenities</h3>
                        <ul class="features-grid">
                            <?php foreach ($features as $feature): ?>
                            <li>✓ <?= htmlspecialchars($feature) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    <?php endif; ?>
                    <?php endif; ?>
                </section>
                
                <!-- Reviews Section -->
                <section class="detail-section reviews-section">
                    <div class="section-header">
                        <h2>Reviews</h2>
                        <?php if (Auth::check()): ?>
                        <a href="/business/<?= $business['id'] ?>/write-review" class="btn btn-primary">Write a Review</a>
                        <?php else: ?>
                        <a href="/login?redirect=/business/<?= $business['id'] ?>/write-review" class="btn btn-primary">Login to Review</a>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Rating Breakdown -->
                    <div class="rating-breakdown">
                        <div class="rating-overall">
                            <div class="overall-number"><?= number_format($ratingStats['avg_rating'] ?? 0, 1) ?></div>
                            <div class="overall-stars">
                                <?= str_repeat('★', floor($ratingStats['avg_rating'] ?? 0)) ?>
                                <?= str_repeat('☆', 5 - floor($ratingStats['avg_rating'] ?? 0)) ?>
                            </div>
                            <div class="overall-count"><?= number_format($ratingStats['review_count'] ?? 0) ?> reviews</div>
                        </div>
                        
                        <div class="rating-bars">
                            <?php for ($i = 5; $i >= 1; $i--): ?>
                            <div class="rating-bar-row">
                                <span><?= $i ?> ★</span>
                                <div class="rating-bar">
                                    <div class="rating-bar-fill" style="width: <?= ($ratingStats["{$i}_star"] ?? 0) / max(1, $ratingStats['review_count']) * 100 ?>%"></div>
                                </div>
                                <span><?= $ratingStats["{$i}_star"] ?? 0 ?></span>
                            </div>
                            <?php endfor; ?>
                        </div>
                    </div>
                    
                    <!-- Reviews List -->
                    <?php if (empty($reviews)): ?>
                    <div class="empty-state">
                        <p>No reviews yet. Be the first to review this business!</p>
                        <?php if (Auth::check()): ?>
                        <a href="/business/<?= $business['id'] ?>/write-review" class="btn btn-primary">Write a Review</a>
                        <?php endif; ?>
                    </div>
                    <?php else: ?>
                    <div class="reviews-list">
                        <?php foreach ($reviews as $review): ?>
                        <div class="review-card">
                            <div class="review-header">
                                <div class="reviewer-info">
                                    <?php if ($review['avatar_url']): ?>
                                    <img src="<?= htmlspecialchars($review['avatar_url']) ?>" alt="<?= htmlspecialchars($review['user_name']) ?>" class="reviewer-avatar">
                                    <?php else: ?>
                                    <div class="reviewer-avatar-placeholder"><?= strtoupper(substr($review['user_name'], 0, 1)) ?></div>
                                    <?php endif; ?>
                                    <div>
                                        <div class="reviewer-name"><?= htmlspecialchars($review['user_name']) ?></div>
                                        <div class="review-date"><?= date('M j, Y', strtotime($review['created_at'])) ?></div>
                                    </div>
                                </div>
                                <div class="review-rating">
                                    <?= str_repeat('★', $review['rating']) ?>
                                    <?= str_repeat('☆', 5 - $review['rating']) ?>
                                </div>
                            </div>
                            
                            <?php if ($review['text']): ?>
                            <div class="review-text">
                                <?= nl2br(htmlspecialchars($review['text'])) ?>
                            </div>
                            <?php endif; ?>
                            
                            <?php if ($review['photos']): ?>
                            <?php $reviewPhotos = json_decode($review['photos'], true); ?>
                            <?php if (is_array($reviewPhotos)): ?>
                            <div class="review-photos">
                                <?php foreach ($reviewPhotos as $photo): ?>
                                <img src="<?= htmlspecialchars($photo) ?>" alt="Review photo" class="review-photo">
                                <?php endforeach; ?>
                            </div>
                            <?php endif; ?>
                            <?php endif; ?>
                            
                            <div class="review-actions">
                                <button class="helpful-btn" data-review-id="<?= $review['id'] ?>">
                                    👍 Helpful (<span class="count"><?= $review['helpful_count'] ?? 0 ?></span>)
                                </button>
                                <button class="btn-text">Reply</button>
                                <button class="btn-text">Report</button>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </section>
            </div>
            
            <!-- Right Sidebar -->
            <aside class="detail-sidebar">
                <!-- Contact Info Box -->
                <div class="info-box">
                    <h3>Contact Information</h3>
                    
                    <?php if ($business['phone']): ?>
                    <div class="info-item">
                        <strong>📞 Phone</strong>
                        <a href="tel:<?= htmlspecialchars($business['phone']) ?>"><?= htmlspecialchars($business['phone']) ?></a>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($business['email']): ?>
                    <div class="info-item">
                        <strong>📧 Email</strong>
                        <a href="mailto:<?= htmlspecialchars($business['email']) ?>"><?= htmlspecialchars($business['email']) ?></a>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($business['website']): ?>
                    <div class="info-item">
                        <strong>🌐 Website</strong>
                        <a href="<?= htmlspecialchars($business['website']) ?>" target="_blank">Visit Website</a>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($business['whatsapp']): ?>
                    <div class="info-item">
                        <strong>💬 WhatsApp</strong>
                        <a href="https://wa.me/<?= htmlspecialchars($business['whatsapp']) ?>" target="_blank">Chat on WhatsApp</a>
                    </div>
                    <?php endif; ?>
                </div>
                
                <!-- Location Box -->
                <div class="info-box">
                    <h3>📍 Location</h3>
                    <p><?= nl2br(htmlspecialchars($business['address'])) ?></p>
                    <p><?= htmlspecialchars($business['city']) ?>, <?= htmlspecialchars($business['country']) ?></p>
                    <?php if ($business['latitude'] && $business['longitude']): ?>
                    <a href="https://www.google.com/maps?q=<?= $business['latitude'] ?>,<?= $business['longitude'] ?>" target="_blank" class="btn btn-outline btn-block">Get Directions</a>
                    <?php endif; ?>
                </div>
                
                <!-- Hours Box -->
                <?php if ($business['opening_hours']): ?>
                <div class="info-box">
                    <h3>🕐 Opening Hours</h3>
                    <?php $hours = json_decode($business['opening_hours'], true); ?>
                    <?php if (is_array($hours)): ?>
                    <div class="hours-list">
                        <?php 
                        $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
                        $today = date('l');
                        foreach ($days as $index => $day): 
                            $isToday = ($day === $today);
                            $hoursForDay = $hours[$index] ?? 'Closed';
                        ?>
                        <div class="hour-row <?= $isToday ? 'today' : '' ?>">
                            <span><?= substr($day, 0, 3) ?></span>
                            <span><?= $hoursForDay ?></span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
                
                <!-- Business Owner Actions -->
                <?php if (Auth::check() && Auth::user()['id'] == $business['owner_id']): ?>
                <div class="info-box owner-box">
                    <h3>Owner Controls</h3>
                    <a href="/dashboard/business/<?= $business['id'] ?>/edit" class="btn btn-outline btn-block">Edit Business</a>
                    <a href="/dashboard/business/<?= $business['id'] ?>/analytics" class="btn btn-outline btn-block">View Analytics</a>
                    <a href="/dashboard/business/<?= $business['id'] ?>/respond" class="btn btn-outline btn-block">Respond to Reviews</a>
                </div>
                <?php elseif (Auth::check()): ?>
                <div class="info-box">
                    <h3>Own this business?</h3>
                    <a href="/business/<?= $business['id'] ?>/claim" class="btn btn-primary btn-block">Claim This Listing</a>
                </div>
                <?php endif; ?>
            </aside>
        </div>
        
        <!-- Similar Businesses -->
        <?php if (!empty($similarBusinesses)): ?>
        <section class="similar-section">
            <h2>Similar Businesses</h2>
            <div class="business-grid">
                <?php foreach ($similarBusinesses as $similar): ?>
                <div class="business-card">
                    <div class="business-image">
                        <?php 
                        $similarPhoto = $db->query("SELECT photo_url FROM business_photos WHERE business_id = ? ORDER BY is_primary DESC LIMIT 1", [$similar['id']])->fetchColumn();
                        ?>
                        <?php if ($similarPhoto): ?>
                            <img src="<?= htmlspecialchars($similarPhoto) ?>" alt="<?= htmlspecialchars($similar['name']) ?>">
                        <?php else: ?>
                            <div class="placeholder-image">No Photo</div>
                        <?php endif; ?>
                    </div>
                    <div class="business-info">
                        <h3><a href="/business/<?= $similar['id'] ?>"><?= htmlspecialchars($similar['name']) ?></a></h3>
                        <div class="rating-display">
                            <?= str_repeat('★', floor($similar['avg_rating'] ?? 0)) ?>
                            <?= str_repeat('☆', 5 - floor($similar['avg_rating'] ?? 0)) ?>
                            <span class="rating-text"><?= number_format($similar['avg_rating'] ?? 0, 1) ?> (<?= $similar['review_count'] ?>)</span>
                        </div>
                        <p class="business-location">📍 <?= htmlspecialchars($similar['city']) ?></p>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </section>
        <?php endif; ?>
    </div>
</div>

<style>
.business-detail-page {
    padding: 2rem 0 4rem;
}

.business-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 2rem;
    gap: 2rem;
}

.business-header-content h1 {
    font-size: 2.5rem;
    margin-bottom: 0.5rem;
}

.rating-summary {
    display: flex;
    align-items: center;
    gap: 1rem;
    margin-bottom: 0.5rem;
}

.rating-display.large {
    font-size: 1.5rem;
}

.verified-badge-large {
    background: var(--primary-color);
    color: white;
    padding: 0.25rem 0.75rem;
    border-radius: var(--radius-md);
    font-size: 0.875rem;
    font-weight: 600;
}

.business-category-detail {
    color: var(--text-secondary);
    font-size: 1.125rem;
}

.business-actions {
    display: flex;
    gap: 0.75rem;
    flex-wrap: wrap;
}

.photo-gallery {
    margin-bottom: 2rem;
}

.main-photo img {
    width: 100%;
    height: 400px;
    object-fit: cover;
    border-radius: var(--radius-lg);
}

.photo-thumbnails {
    display: flex;
    gap: 0.75rem;
    margin-top: 1rem;
}

.thumbnail {
    width: 100px;
    height: 100px;
    object-fit: cover;
    border-radius: var(--radius-md);
    cursor: pointer;
    transition: transform 0.2s ease;
}

.thumbnail:hover {
    transform: scale(1.05);
}

.view-all-photos {
    width: 100px;
    height: 100px;
    background: var(--bg-tertiary);
    border-radius: var(--radius-md);
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    color: var(--text-secondary);
    cursor: pointer;
}

.detail-grid {
    display: grid;
    grid-template-columns: 1fr 350px;
    gap: 3rem;
    margin-top: 2rem;
}

.detail-section {
    margin-bottom: 3rem;
}

.detail-section h2 {
    font-size: 1.75rem;
    margin-bottom: 1rem;
}

.features-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 0.75rem;
    list-style: none;
    padding: 0;
}

.features-grid li {
    padding: 0.5rem;
    background: var(--bg-secondary);
    border-radius: var(--radius-sm);
}

.rating-breakdown {
    display: grid;
    grid-template-columns: 200px 1fr;
    gap: 2rem;
    margin-bottom: 2rem;
    padding: 2rem;
    background: var(--bg-secondary);
    border-radius: var(--radius-lg);
}

.rating-overall {
    text-align: center;
}

.overall-number {
    font-size: 4rem;
    font-weight: 700;
    color: var(--text-primary);
}

.overall-stars {
    font-size: 1.5rem;
    color: var(--accent-color);
    margin: 0.5rem 0;
}

.rating-bar-row {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    margin-bottom: 0.5rem;
}

.rating-bar {
    flex: 1;
    height: 8px;
    background: var(--border-color);
    border-radius: 4px;
    overflow: hidden;
}

.rating-bar-fill {
    height: 100%;
    background: var(--accent-color);
    border-radius: 4px;
}

.review-card {
    padding: 1.5rem;
    border: 1px solid var(--border-color);
    border-radius: var(--radius-lg);
    margin-bottom: 1.5rem;
}

.review-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 1rem;
}

.reviewer-info {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.reviewer-avatar,
.reviewer-avatar-placeholder {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    object-fit: cover;
}

.reviewer-avatar-placeholder {
    background: var(--primary-color);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    font-size: 1.25rem;
}

.reviewer-name {
    font-weight: 600;
}

.review-date {
    color: var(--text-light);
    font-size: 0.875rem;
}

.review-text {
    line-height: 1.8;
    margin-bottom: 1rem;
}

.review-photos {
    display: flex;
    gap: 0.5rem;
    margin: 1rem 0;
    flex-wrap: wrap;
}

.review-photo {
    width: 100px;
    height: 100px;
    object-fit: cover;
    border-radius: var(--radius-md);
}

.review-actions {
    display: flex;
    gap: 1rem;
    margin-top: 1rem;
    padding-top: 1rem;
    border-top: 1px solid var(--border-color);
}

.info-box {
    background: var(--bg-secondary);
    padding: 1.5rem;
    border-radius: var(--radius-lg);
    margin-bottom: 1.5rem;
}

.info-box h3 {
    font-size: 1.25rem;
    margin-bottom: 1rem;
}

.info-item {
    margin-bottom: 1rem;
}

.info-item strong {
    display: block;
    margin-bottom: 0.25rem;
    color: var(--text-secondary);
    font-size: 0.875rem;
}

.hours-list {
    margin-top: 1rem;
}

.hour-row {
    display: flex;
    justify-content: space-between;
    padding: 0.5rem 0;
    border-bottom: 1px solid var(--border-color);
}

.hour-row:last-child {
    border-bottom: none;
}

.hour-row.today {
    font-weight: 600;
    color: var(--primary-color);
}

.owner-box {
    border: 2px solid var(--primary-color);
}

.similar-section {
    margin-top: 3rem;
}

.similar-section h2 {
    margin-bottom: 1.5rem;
}

@media (max-width: 992px) {
    .detail-grid {
        grid-template-columns: 1fr;
    }
    
    .detail-sidebar {
        order: -1;
    }
}

@media (max-width: 768px) {
    .business-header {
        flex-direction: column;
    }
    
    .business-header-content h1 {
        font-size: 2rem;
    }
    
    .photo-gallery .main-photo img {
        height: 250px;
    }
    
    .rating-breakdown {
        grid-template-columns: 1fr;
    }
}
</style>

<?php include BASE_PATH . '/pages/layouts/footer.php'; ?>
