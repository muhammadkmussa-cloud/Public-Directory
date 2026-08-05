<?php
/**
 * Homepage - Umma Directory
 * Yelp-style homepage with search, categories, and trending businesses
 */

$db = Database::getInstance();
$auth = new Auth($db);

// Get featured businesses
$featuredBusinesses = $db->query("
    SELECT b.*, AVG(r.rating) as avg_rating, COUNT(r.id) as review_count,
           (SELECT photo_url FROM business_photos WHERE business_id = b.id ORDER BY is_primary DESC LIMIT 1) as primary_photo
    FROM businesses b
    LEFT JOIN reviews r ON b.id = r.business_id
    WHERE b.is_verified = 1 AND b.status = 'active'
    GROUP BY b.id
    ORDER BY avg_rating DESC, review_count DESC
    LIMIT 6
")->fetchAll();

// Get trending mosques
$trendingMosques = $db->query("
    SELECT m.*, AVG(r.rating) as avg_rating, COUNT(r.id) as review_count
    FROM mosques m
    LEFT JOIN reviews r ON m.id = r.mosque_id
    GROUP BY m.id
    ORDER BY review_count DESC
    LIMIT 4
")->fetchAll();

// Get top fundis
$topFundis = $db->query("
    SELECT f.*, AVG(r.rating) as avg_rating, COUNT(r.id) as review_count
    FROM fundis f
    LEFT JOIN reviews r ON f.id = r.fundi_id
    WHERE f.is_verified = 1
    GROUP BY f.id
    ORDER BY avg_rating DESC, review_count DESC
    LIMIT 4
")->fetchAll();

// Get active charities
$activeCharities = $db->query("
    SELECT c.*, SUM(d.amount) as total_raised
    FROM charities c
    LEFT JOIN donations d ON c.id = d.charity_id
    WHERE c.status = 'active'
    GROUP BY c.id
    ORDER BY total_raised DESC
    LIMIT 3
")->fetchAll();

// Categories for businesses
$categories = [
    ['name' => 'Restaurants', 'icon' => '🍽️', 'slug' => 'restaurants'],
    ['name' => 'Shopping', 'icon' => '🛍️', 'slug' => 'shopping'],
    ['name' => 'Services', 'icon' => '🔧', 'slug' => 'services'],
    ['name' => 'Health', 'icon' => '🏥', 'slug' => 'health'],
    ['name' => 'Education', 'icon' => '📚', 'slug' => 'education'],
    ['name' => 'Entertainment', 'icon' => '🎉', 'slug' => 'entertainment'],
];

include BASE_PATH . '/pages/layouts/header.php';
?>

<!-- Hero Section -->
<section class="hero">
    <div class="container">
        <h1>Discover the Best in Your Community</h1>
        <p class="hero-subtitle">Find businesses, mosques, fundis, and charities trusted by Muslims worldwide</p>
        
        <!-- Search Form -->
        <form class="search-form" action="/businesses" method="GET">
            <div class="search-inputs">
                <div class="input-group">
                    <label for="search-what">What</label>
                    <input type="text" id="search-what" name="q" placeholder="business, mosque, fundi..." autocomplete="off">
                </div>
                <div class="input-group">
                    <label for="search-where">Where</label>
                    <input type="text" id="search-where" name="location" placeholder="city, area, or zip" autocomplete="off">
                </div>
            </div>
            <button type="submit" class="btn btn-primary btn-search">
                <span>Search</span>
            </button>
        </form>
    </div>
</section>

<!-- Categories Section -->
<section class="categories-section">
    <div class="container">
        <h2>Explore by Category</h2>
        <div class="categories-grid">
            <?php foreach ($categories as $category): ?>
            <a href="/businesses?category=<?= htmlspecialchars($category['slug']) ?>" class="category-card">
                <div class="category-icon"><?= $category['icon'] ?></div>
                <h3><?= htmlspecialchars($category['name']) ?></h3>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Featured Businesses -->
<section class="featured-section">
    <div class="container">
        <div class="section-header">
            <h2>Featured Businesses</h2>
            <a href="/businesses" class="btn btn-outline">View All</a>
        </div>
        
        <?php if (empty($featuredBusinesses)): ?>
        <div class="empty-state">
            <p>No featured businesses yet. Be the first to add one!</p>
            <a href="/businesses/write-review" class="btn btn-primary">Add Business</a>
        </div>
        <?php else: ?>
        <div class="business-grid">
            <?php foreach ($featuredBusinesses as $business): ?>
            <div class="business-card">
                <div class="business-image">
                    <?php if ($business['primary_photo']): ?>
                        <img src="<?= htmlspecialchars($business['primary_photo']) ?>" alt="<?= htmlspecialchars($business['name']) ?>">
                    <?php else: ?>
                        <div class="placeholder-image">No Photo</div>
                    <?php endif; ?>
                    <?php if ($business['is_verified']): ?>
                        <span class="verified-badge" title="Verified">✓</span>
                    <?php endif; ?>
                </div>
                <div class="business-info">
                    <h3><a href="/business/<?= $business['id'] ?>"><?= htmlspecialchars($business['name']) ?></a></h3>
                    <div class="rating-display">
                        <?= str_repeat('★', floor($business['avg_rating'] ?? 0)) ?>
                        <?= str_repeat('☆', 5 - floor($business['avg_rating'] ?? 0)) ?>
                        <span class="rating-text"><?= number_format($business['avg_rating'] ?? 0, 1) ?> (<?= $business['review_count'] ?>)</span>
                    </div>
                    <p class="business-category"><?= htmlspecialchars($business['category']) ?></p>
                    <p class="business-location">📍 <?= htmlspecialchars($business['city']) ?></p>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</section>

<!-- Mosques Section -->
<section class="mosques-section">
    <div class="container">
        <div class="section-header">
            <h2>Nearby Mosques</h2>
            <a href="/mosques" class="btn btn-outline">View All</a>
        </div>
        
        <?php if (empty($trendingMosques)): ?>
        <div class="empty-state">
            <p>No mosques listed yet.</p>
            <a href="/mosques" class="btn btn-primary">Explore Mosques</a>
        </div>
        <?php else: ?>
        <div class="mosque-grid">
            <?php foreach ($trendingMosques as $mosque): ?>
            <div class="mosque-card">
                <div class="mosque-info">
                    <h3><a href="/mosque/<?= $mosque['id'] ?>"><?= htmlspecialchars($mosque['name']) ?></a></h3>
                    <div class="rating-display">
                        <?= str_repeat('★', floor($mosque['avg_rating'] ?? 0)) ?>
                        <?= str_repeat('☆', 5 - floor($mosque['avg_rating'] ?? 0)) ?>
                        <span class="rating-text"><?= number_format($mosque['avg_rating'] ?? 0, 1) ?> (<?= $mosque['review_count'] ?>)</span>
                    </div>
                    <p class="mosque-location">📍 <?= htmlspecialchars($mosque['city']) ?></p>
                    <?php if ($mosque['next_prayer']): ?>
                    <p class="next-prayer">Next: <?= htmlspecialchars($mosque['next_prayer']) ?></p>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</section>

<!-- Fundis Section -->
<section class="fundis-section">
    <div class="container">
        <div class="section-header">
            <h2>Top Rated Fundis</h2>
            <a href="/fundis" class="btn btn-outline">View All</a>
        </div>
        
        <?php if (empty($topFundis)): ?>
        <div class="empty-state">
            <p>No fundis listed yet.</p>
            <a href="/fundis" class="btn btn-primary">Find a Fundi</a>
        </div>
        <?php else: ?>
        <div class="fundi-grid">
            <?php foreach ($topFundis as $fundi): ?>
            <div class="fundi-card">
                <div class="fundi-info">
                    <h3><a href="/fundi/<?= $fundi['id'] ?>"><?= htmlspecialchars($fundi['name']) ?></a></h3>
                    <p class="fundi-skill"><?= htmlspecialchars($fundi['skills']) ?></p>
                    <div class="rating-display">
                        <?= str_repeat('★', floor($fundi['avg_rating'] ?? 0)) ?>
                        <?= str_repeat('☆', 5 - floor($fundi['avg_rating'] ?? 0)) ?>
                        <span class="rating-text"><?= number_format($fundi['avg_rating'] ?? 0, 1) ?> (<?= $fundi['review_count'] ?>)</span>
                    </div>
                    <p class="fundi-location">📍 <?= htmlspecialchars($fundi['city']) ?></p>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</section>

<!-- Call to Action -->
<section class="cta-section">
    <div class="container">
        <h2>Own a Business?</h2>
        <p>Claim your listing, respond to reviews, and attract more customers</p>
        <a href="/businesses" class="btn btn-primary btn-large">Add Your Business</a>
    </div>
</section>

<?php include BASE_PATH . '/pages/layouts/footer.php'; ?>
