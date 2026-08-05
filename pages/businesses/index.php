<?php
/**
 * Businesses Listing Page
 * Search and filter businesses Yelp-style
 */

$db = Database::getInstance();

// Get search and filter parameters
$search = trim($_GET['q'] ?? '');
$location = trim($_GET['location'] ?? '');
$category = trim($_GET['category'] ?? '');
$priceRange = $_GET['price'] ?? '';
$minRating = $_GET['rating'] ?? 0;
$isOpenNow = isset($_GET['open_now']);
$page = max(1, intval($_GET['page'] ?? 1));
$perPage = 12;
$offset = ($page - 1) * $perPage;

// Build query
$where = ["b.status = 'active'"];
$params = [];

if ($search) {
    $where[] = "(b.name LIKE ? OR b.description LIKE ? OR b.category LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($location) {
    $where[] = "(b.city LIKE ? OR b.address LIKE ?)";
    $params[] = "%$location%";
    $params[] = "%$location%";
}

if ($category) {
    $where[] = "b.category = ?";
    $params[] = $category;
}

if ($priceRange) {
    $where[] = "b.price_range = ?";
    $params[] = $priceRange;
}

if ($minRating > 0) {
    $where[] = "avg_rating >= ?";
    $params[] = $minRating;
}

$whereClause = implode(' AND ', $where);

// Get total count
$countSql = "SELECT COUNT(*) FROM businesses b 
             LEFT JOIN reviews r ON b.id = r.business_id 
             WHERE $whereClause";
$totalCount = $db->query($countSql, $params)->fetchColumn();
$totalPages = ceil($totalCount / $perPage);

// Get businesses
$sql = "SELECT b.*, AVG(r.rating) as avg_rating, COUNT(r.id) as review_count,
               (SELECT photo_url FROM business_photos WHERE business_id = b.id ORDER BY is_primary DESC LIMIT 1) as primary_photo
        FROM businesses b
        LEFT JOIN reviews r ON b.id = r.business_id
        WHERE $whereClause
        GROUP BY b.id
        ORDER BY avg_rating DESC, review_count DESC
        LIMIT $perPage OFFSET $offset";

$businesses = $db->query($sql, $params)->fetchAll();

// Get categories for filter
$categories = $db->query("SELECT DISTINCT category FROM businesses WHERE status = 'active' ORDER BY category")->fetchAll(PDO::FETCH_COLUMN);

$pageTitle = 'Browse Businesses';
include BASE_PATH . '/pages/layouts/header.php';
?>

<div class="listing-page">
    <div class="container">
        <!-- Page Header -->
        <div class="page-header">
            <h1>Businesses</h1>
            <p><?= number_format($totalCount) ?> businesses found</p>
        </div>
        
        <!-- Filters -->
        <div class="filters-section">
            <form method="GET" action="/businesses" class="filters-form">
                <div class="filters-grid">
                    <div class="filter-group">
                        <label for="search">Search</label>
                        <input type="text" id="search" name="q" value="<?= htmlspecialchars($search) ?>" placeholder="What are you looking for?">
                    </div>
                    
                    <div class="filter-group">
                        <label for="location">Location</label>
                        <input type="text" id="location" name="location" value="<?= htmlspecialchars($location) ?>" placeholder="City or area">
                    </div>
                    
                    <div class="filter-group">
                        <label for="category">Category</label>
                        <select id="category" name="category">
                            <option value="">All Categories</option>
                            <?php foreach ($categories as $cat): ?>
                            <option value="<?= htmlspecialchars($cat) ?>" <?= $category === $cat ? 'selected' : '' ?>><?= htmlspecialchars($cat) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label for="price">Price Range</label>
                        <select id="price" name="price">
                            <option value="">Any Price</option>
                            <option value="$" <?= $priceRange === '$' ? 'selected' : '' ?>>$ - Inexpensive</option>
                            <option value="$$" <?= $priceRange === '$$' ? 'selected' : '' ?>>$$ - Moderate</option>
                            <option value="$$$" <?= $priceRange === '$$$' ? 'selected' : '' ?>>$$$ - Expensive</option>
                            <option value="$$$$" <?= $priceRange === '$$$$' ? 'selected' : '' ?>>$$$$ - Very Expensive</option>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label for="rating">Minimum Rating</label>
                        <select id="rating" name="rating">
                            <option value="0">Any Rating</option>
                            <option value="4" <?= $minRating == 4 ? 'selected' : '' ?>>4+ Stars</option>
                            <option value="3" <?= $minRating == 3 ? 'selected' : '' ?>>3+ Stars</option>
                            <option value="2" <?= $minRating == 2 ? 'selected' : '' ?>>2+ Stars</option>
                        </select>
                    </div>
                    
                    <div class="filter-group checkbox-group">
                        <label class="checkbox-label">
                            <input type="checkbox" name="open_now" value="1" <?= $isOpenNow ? 'checked' : '' ?>>
                            <span>Open Now</span>
                        </label>
                    </div>
                </div>
                
                <div class="filters-actions">
                    <button type="submit" class="btn btn-primary">Apply Filters</button>
                    <a href="/businesses" class="btn btn-outline">Clear All</a>
                </div>
            </form>
        </div>
        
        <!-- Results Grid -->
        <?php if (empty($businesses)): ?>
        <div class="empty-state">
            <h3>No businesses found</h3>
            <p>Try adjusting your filters or search terms</p>
            <a href="/businesses" class="btn btn-primary">View All Businesses</a>
        </div>
        <?php else: ?>
        <div class="results-grid">
            <?php foreach ($businesses as $business): ?>
            <div class="business-card-large">
                <div class="business-card-image">
                    <?php if ($business['primary_photo']): ?>
                        <img src="<?= htmlspecialchars($business['primary_photo']) ?>" alt="<?= htmlspecialchars($business['name']) ?>">
                    <?php else: ?>
                        <div class="placeholder-image">No Photo</div>
                    <?php endif; ?>
                    <?php if ($business['is_verified']): ?>
                        <span class="verified-badge" title="Verified">✓</span>
                    <?php endif; ?>
                </div>
                <div class="business-card-content">
                    <h3><a href="/business/<?= $business['id'] ?>"><?= htmlspecialchars($business['name']) ?></a></h3>
                    <div class="rating-display">
                        <?= str_repeat('★', floor($business['avg_rating'] ?? 0)) ?>
                        <?= str_repeat('☆', 5 - floor($business['avg_rating'] ?? 0)) ?>
                        <span class="rating-text"><?= number_format($business['avg_rating'] ?? 0, 1) ?> (<?= $business['review_count'] ?> reviews)</span>
                    </div>
                    <p class="business-category"><?= htmlspecialchars($business['category']) ?> • <?= str_repeat('$', $business['price_range'] ?? 1) ?></p>
                    <p class="business-address">📍 <?= htmlspecialchars($business['address']) ?>, <?= htmlspecialchars($business['city']) ?></p>
                    <?php if ($business['phone']): ?>
                    <p class="business-phone">📞 <?= htmlspecialchars($business['phone']) ?></p>
                    <?php endif; ?>
                    <div class="business-features">
                        <?php if ($business['features']): ?>
                        <?php $features = json_decode($business['features'], true); ?>
                        <?php if (is_array($features)): ?>
                        <?php foreach (array_slice($features, 0, 3) as $feature): ?>
                        <span class="feature-tag"><?= htmlspecialchars($feature) ?></span>
                        <?php endforeach; ?>
                        <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        
        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
        <div class="pagination">
            <?php if ($page > 1): ?>
            <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>" class="btn btn-outline">Previous</a>
            <?php endif; ?>
            
            <span class="page-info">Page <?= $page ?> of <?= $totalPages ?></span>
            
            <?php if ($page < $totalPages): ?>
            <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>" class="btn btn-outline">Next</a>
            <?php endif; ?>
        </div>
        <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<style>
.listing-page {
    padding: 2rem 0 4rem;
}

.page-header {
    margin-bottom: 2rem;
}

.filters-section {
    background: var(--bg-secondary);
    padding: 2rem;
    border-radius: var(--radius-lg);
    margin-bottom: 2rem;
}

.filters-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1.5rem;
    margin-bottom: 1.5rem;
}

.filter-group label {
    display: block;
    font-weight: 600;
    margin-bottom: 0.5rem;
    color: var(--text-secondary);
}

.filter-group input,
.filter-group select {
    width: 100%;
    padding: 0.75rem;
    border: 2px solid var(--border-color);
    border-radius: var(--radius-md);
    font-size: 1rem;
}

.checkbox-group {
    display: flex;
    align-items: center;
    padding-top: 2rem;
}

.filters-actions {
    display: flex;
    gap: 1rem;
}

.results-grid {
    display: flex;
    flex-direction: column;
    gap: 2rem;
}

.business-card-large {
    display: grid;
    grid-template-columns: 300px 1fr;
    gap: 1.5rem;
    background: var(--bg-primary);
    border-radius: var(--radius-lg);
    overflow: hidden;
    box-shadow: var(--shadow-md);
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.business-card-large:hover {
    transform: translateY(-4px);
    box-shadow: var(--shadow-lg);
}

.business-card-image {
    position: relative;
    height: 220px;
    background: var(--bg-tertiary);
}

.business-card-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.business-card-content {
    padding: 1.5rem;
}

.business-card-content h3 {
    font-size: 1.5rem;
    margin-bottom: 0.5rem;
}

.business-card-content h3 a {
    color: var(--text-primary);
}

.business-card-content h3 a:hover {
    color: var(--primary-color);
}

.business-features {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
    margin-top: 1rem;
}

.feature-tag {
    background: var(--bg-tertiary);
    padding: 0.25rem 0.75rem;
    border-radius: var(--radius-sm);
    font-size: 0.875rem;
    color: var(--text-secondary);
}

.pagination {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 1rem;
    margin-top: 3rem;
}

.page-info {
    color: var(--text-secondary);
    font-weight: 600;
}

@media (max-width: 768px) {
    .business-card-large {
        grid-template-columns: 1fr;
    }
    
    .business-card-image {
        height: 200px;
    }
    
    .filters-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<?php include BASE_PATH . '/pages/layouts/footer.php'; ?>
