<?php
/**
 * Mosque Directory Page
 * Lists mosques with search, filters, and prayer times
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/Database.php';
require_once __DIR__ . '/../includes/Auth.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/PrayerTimes.php';

$db = Database::getInstance();
$auth = new Auth();
$user = $auth->getCurrentUser();

// Search & Filter Parameters
$search = trim($_GET['q'] ?? '');
$city = $_GET['city'] ?? '';
$hasJummah = isset($_GET['jummah']) ? 1 : 0;
$sortBy = $_GET['sort'] ?? 'rating'; // rating, distance, name

$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 12;
$offset = ($page - 1) * $perPage;

// Build Query
$where = ['status' => 'active'];
$params = [];

if ($search) {
    $where[] = "(name LIKE :search OR address LIKE :search)";
    $params[':search'] = "%{$search}%";
}

if ($city) {
    $where[] = "city = :city";
    $params[':city'] = $city;
}

if ($hasJummah) {
    $where[] = "jummah_prayer = 1";
}

$whereClause = implode(' AND ', $where);

// Sorting
$orderMap = [
    'rating' => 'rating DESC',
    'distance' => 'distance ASC', // Requires lat/lng params
    'name' => 'name ASC',
    'reviews' => 'review_count DESC'
];
$orderBy = $orderMap[$sortBy] ?? 'rating DESC';

// Get User Location for Distance (if provided)
$userLat = $_GET['lat'] ?? null;
$userLng = $_GET['lng'] ?? null;
$distanceSql = '';
if ($userLat && $userLng) {
    $distanceSql = ", (6371 * acos(cos(radians(:lat)) * cos(radians(latitude)) 
                * cos(radians(longitude) - radians(:lng)) 
                + sin(radians(:lat)) * sin(radians(latitude)))) AS distance";
    $params[':lat'] = $userLat;
    $params[':lng'] = $userLng;
    if ($sortBy === 'distance') {
        $orderBy = 'distance ASC';
    }
}

// Fetch Mosques
$totalQuery = "SELECT COUNT(*) as count FROM mosques WHERE {$whereClause}";
$totalResult = $db->queryOne($totalQuery, str_replace(array_keys($params), array_values($params), $totalQuery) ? $params : []);

// Rebuild params for total count without distance placeholders
$countParams = [];
foreach ($params as $key => $val) {
    if (!in_array($key, [':lat', ':lng'])) {
        $countParams[$key] = $val;
    }
}
$totalCount = (int)$db->queryOne("SELECT COUNT(*) as count FROM mosques WHERE {$whereClause}", $countParams)['count'];

$query = "SELECT *{$distanceSql} FROM mosques 
          WHERE {$whereClause} 
          ORDER BY {$orderBy} 
          LIMIT :limit OFFSET :offset";

$stmt = $db->prepare($query);
foreach ($params as $key => $val) {
    $stmt->bindValue($key, $val);
}
$stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$mosques = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate prayer times for each mosque (if location available)
foreach ($mosques as &$mosque) {
    if ($mosque['latitude'] && $mosque['longitude']) {
        $prayerCalc = new PrayerTimes($mosque['latitude'], $mosque['longitude']);
        $mosque['prayer_times'] = $prayerCalc->getTimes();
        $mosque['next_prayer'] = $prayerCalc->getNextPrayer();
    }
    
    // Format distance
    if (isset($mosque['distance'])) {
        $mosque['distance_formatted'] = round($mosque['distance'], 1) . ' km';
    }
}

// Get cities for filter dropdown
$cities = $db->query("SELECT DISTINCT city FROM mosques WHERE city IS NOT NULL ORDER BY city");

include __DIR__ . '/../views/layout/header.php';
?>

<div class="container py-5">
    <div class="row mb-4">
        <div class="col-12">
            <h1 class="display-6 fw-bold">
                <i class="fas fa-mosque text-success"></i> Mosques Directory
            </h1>
            <p class="text-muted">Find mosques near you with prayer times and community reviews</p>
        </div>
    </div>

    <!-- Filters -->
    <div class="card mb-4 shadow-sm">
        <div class="card-body">
            <form method="GET" action="/mosques" class="row g-3">
                <div class="col-md-4">
                    <input type="text" name="q" class="form-control" placeholder="Search mosques..." value="<?= e($search) ?>">
                </div>
                <div class="col-md-3">
                    <select name="city" class="form-select">
                        <option value="">All Cities</option>
                        <?php foreach ($cities as $c): ?>
                            <option value="<?= e($c['city']) ?>" <?= $city === $c['city'] ? 'selected' : '' ?>>
                                <?= e($c['city']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <div class="form-check mt-2">
                        <input class="form-check-input" type="checkbox" name="jummah" id="jummah" <?= $hasJummah ? 'checked' : '' ?>>
                        <label class="form-check-label" for="jummah">Jummah Prayer</label>
                    </div>
                </div>
                <div class="col-md-2">
                    <select name="sort" class="form-select" onchange="this.form.submit()">
                        <option value="rating" <?= $sortBy === 'rating' ? 'selected' : '' ?>>Top Rated</option>
                        <option value="reviews" <?= $sortBy === 'reviews' ? 'selected' : '' ?>>Most Reviewed</option>
                        <option value="name" <?= $sortBy === 'name' ? 'selected' : '' ?>>Name (A-Z)</option>
                        <?php if ($userLat): ?>
                        <option value="distance" <?= $sortBy === 'distance' ? 'selected' : '' ?>>Nearest</option>
                        <?php endif; ?>
                    </select>
                </div>
                <div class="col-md-1">
                    <button type="submit" class="btn btn-primary w-100"><i class="fas fa-search"></i></button>
                </div>
            </form>
        </div>
    </div>

    <!-- Results -->
    <?php if (empty($mosques)): ?>
        <div class="alert alert-info">No mosques found. Try adjusting your filters.</div>
    <?php else: ?>
        <div class="row g-4">
            <?php foreach ($mosques as $mosque): ?>
                <div class="col-md-6 col-lg-4">
                    <div class="card h-100 shadow-sm hover-lift">
                        <?php if ($mosque['cover_photo']): ?>
                            <img src="/uploads/<?= e($mosque['cover_photo']) ?>" class="card-img-top" alt="<?= e($mosque['name']) ?>" style="height: 200px; object-fit: cover;">
                        <?php else: ?>
                            <div class="bg-light d-flex align-items-center justify-content-center" style="height: 200px;">
                                <i class="fas fa-mosque fa-4x text-muted"></i>
                            </div>
                        <?php endif; ?>
                        
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <h5 class="card-title mb-0"><?= e($mosque['name']) ?></h5>
                                <?php if ($mosque['verified']): ?>
                                    <span class="badge bg-success"><i class="fas fa-check-circle"></i></span>
                                <?php endif; ?>
                            </div>
                            
                            <?= renderStars($mosque['rating']) ?>
                            <small class="text-muted">(<?= $mosque['review_count'] ?> reviews)</small>
                            
                            <p class="card-text mt-2 text-muted small">
                                <i class="fas fa-map-marker-alt"></i> <?= e($mosque['address']) ?>
                                <?php if (isset($mosque['distance_formatted'])): ?>
                                    <br><i class="fas fa-location-arrow"></i> <?= e($mosque['distance_formatted']) ?>
                                <?php endif; ?>
                            </p>
                            
                            <?php if (isset($mosque['prayer_times'])): ?>
                                <div class="bg-light rounded p-2 mb-2">
                                    <small class="fw-bold text-success">Next: <?= $mosque['next_prayer']['name'] ?> at <?= $mosque['next_prayer']['time'] ?></small>
                                </div>
                            <?php endif; ?>
                            
                            <div class="d-flex gap-2 flex-wrap">
                                <?php if ($mosque['jummah_prayer']): ?>
                                    <span class="badge bg-info">Jummah</span>
                                <?php endif; ?>
                                <?php if ($mosque['wudu_facilities']): ?>
                                    <span class="badge bg-secondary">Wudu</span>
                                <?php endif; ?>
                                <?php if ($mosque['parking']): ?>
                                    <span class="badge bg-secondary">Parking</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="card-footer bg-white border-0 pb-3">
                            <a href="/listing/mosque/<?= $mosque['id'] ?>" class="btn btn-outline-primary btn-sm">View Details</a>
                            <a href="tel:<?= e($mosque['phone']) ?>" class="btn btn-outline-success btn-sm"><i class="fas fa-phone"></i></a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Pagination -->
        <?php if ($totalCount > $perPage): ?>
            <nav class="mt-4">
                <ul class="pagination justify-content-center">
                    <?php 
                    $totalPages = ceil($totalCount / $perPage);
                    for ($i = 1; $i <= $totalPages; $i++): 
                    ?>
                        <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                            <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>"><?= $i ?></a>
                        </li>
                    <?php endfor; ?>
                </ul>
            </nav>
        <?php endif; ?>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../views/layout/footer.php'; ?>
