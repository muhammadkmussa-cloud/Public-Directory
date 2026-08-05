<?php
/**
 * Fundi Directory Page
 * Lists skilled workers with search, filters, and portfolios
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/Database.php';
require_once __DIR__ . '/../includes/Auth.php';
require_once __DIR__ . '/../includes/helpers.php';

$db = Database::getInstance();
$auth = new Auth();
$user = $auth->getCurrentUser();

// Search & Filter Parameters
$search = trim($_GET['q'] ?? '');
$skill = $_GET['skill'] ?? '';
$city = $_GET['city'] ?? '';
$verified = isset($_GET['verified']) ? 1 : 0;
$sortBy = $_GET['sort'] ?? 'rating';

$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 12;
$offset = ($page - 1) * $perPage;

// Build Query
$where = ['status' => 'active'];
$params = [];

if ($search) {
    $where[] = "(name LIKE :search OR bio LIKE :search)";
    $params[':search'] = "%{$search}%";
}

if ($skill) {
    $where[] = "FIND_IN_SET(:skill, skills)";
    $params[':skill'] = $skill;
}

if ($city) {
    $where[] = "city = :city";
    $params[':city'] = $city;
}

if ($verified) {
    $where[] = "verified = 1";
}

$whereClause = implode(' AND ', $where);

// Sorting
$orderMap = [
    'rating' => 'rating DESC',
    'reviews' => 'review_count DESC',
    'name' => 'name ASC'
];
$orderBy = $orderMap[$sortBy] ?? 'rating DESC';

// Get Total Count
$countParams = $params;
$totalCount = (int)$db->queryOne("SELECT COUNT(*) as count FROM fundis WHERE {$whereClause}", $countParams)['count'];

// Fetch Fundis
$query = "SELECT * FROM fundis 
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
$fundis = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Parse skills for each fundi
foreach ($fundis as &$fundi) {
    $fundi['skills_array'] = $fundi['skills'] ? explode(',', $fundi['skills']) : [];
    
    // Get portfolio photos
    $fundi['portfolio'] = $db->query("SELECT photo_path FROM fundi_portfolio WHERE fundi_id = :fid ORDER BY created_at DESC LIMIT 6", [
        ':fid' => $fundi['id']
    ]);
}

// Get available skills for filter
$allSkills = $db->query("SELECT DISTINCT skills FROM fundis WHERE skills IS NOT NULL");
$skillsList = [];
foreach ($allSkills as $row) {
    if ($row['skills']) {
        $skills = explode(',', $row['skills']);
        foreach ($skills as $s) {
            $trimmed = trim($s);
            if ($trimmed && !in_array($trimmed, $skillsList)) {
                $skillsList[] = $trimmed;
            }
        }
    }
}
sort($skillsList);

// Get cities
$cities = $db->query("SELECT DISTINCT city FROM fundis WHERE city IS NOT NULL ORDER BY city");

include __DIR__ . '/../views/layout/header.php';
?>

<div class="container py-5">
    <div class="row mb-4">
        <div class="col-12">
            <h1 class="display-6 fw-bold">
                <i class="fas fa-tools text-warning"></i> Skilled Workers (Fundi) Directory
            </h1>
            <p class="text-muted">Find trusted plumbers, electricians, builders, and more in your community</p>
        </div>
    </div>

    <!-- Filters -->
    <div class="card mb-4 shadow-sm">
        <div class="card-body">
            <form method="GET" action="/fundis" class="row g-3">
                <div class="col-md-3">
                    <input type="text" name="q" class="form-control" placeholder="Search by name or bio..." value="<?= e($search) ?>">
                </div>
                <div class="col-md-2">
                    <select name="skill" class="form-select">
                        <option value="">All Skills</option>
                        <?php foreach ($skillsList as $s): ?>
                            <option value="<?= e($s) ?>" <?= $skill === $s ? 'selected' : '' ?>><?= e($s) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="city" class="form-select">
                        <option value="">All Cities</option>
                        <?php foreach ($cities as $c): ?>
                            <option value="<?= e($c['city']) ?>" <?= $city === $c['city'] ? 'selected' : '' ?>><?= e($c['city']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <div class="form-check mt-2">
                        <input class="form-check-input" type="checkbox" name="verified" id="verified" <?= $verified ? 'checked' : '' ?>>
                        <label class="form-check-label" for="verified">Verified Only</label>
                    </div>
                </div>
                <div class="col-md-2">
                    <select name="sort" class="form-select" onchange="this.form.submit()">
                        <option value="rating" <?= $sortBy === 'rating' ? 'selected' : '' ?>>Top Rated</option>
                        <option value="reviews" <?= $sortBy === 'reviews' ? 'selected' : '' ?>>Most Reviewed</option>
                        <option value="name" <?= $sortBy === 'name' ? 'selected' : '' ?>>Name (A-Z)</option>
                    </select>
                </div>
                <div class="col-md-1">
                    <button type="submit" class="btn btn-primary w-100"><i class="fas fa-search"></i></button>
                </div>
            </form>
        </div>
    </div>

    <!-- Results -->
    <?php if (empty($fundis)): ?>
        <div class="alert alert-info">No skilled workers found. Try adjusting your filters.</div>
    <?php else: ?>
        <div class="row g-4">
            <?php foreach ($fundis as $fundi): ?>
                <div class="col-md-6 col-lg-4">
                    <div class="card h-100 shadow-sm hover-lift">
                        <div class="position-relative">
                            <?php if (!empty($fundi['portfolio'][0])): ?>
                                <img src="/uploads/<?= e($fundi['portfolio'][0]['photo_path']) ?>" class="card-img-top" alt="<?= e($fundi['name']) ?>" style="height: 200px; object-fit: cover;">
                            <?php else: ?>
                                <div class="bg-light d-flex align-items-center justify-content-center" style="height: 200px;">
                                    <i class="fas fa-user-wrench fa-4x text-muted"></i>
                                </div>
                            <?php endif; ?>
                            
                            <?php if ($fundi['verified']): ?>
                                <span class="badge bg-success position-absolute top-0 end-0 m-2">
                                    <i class="fas fa-check-circle"></i> Verified
                                </span>
                            <?php endif; ?>
                        </div>
                        
                        <div class="card-body">
                            <h5 class="card-title"><?= e($fundi['name']) ?></h5>
                            
                            <?= renderStars($fundi['rating']) ?>
                            <small class="text-muted">(<?= $fundi['review_count'] ?> reviews)</small>
                            
                            <p class="card-text mt-2 text-muted small">
                                <i class="fas fa-map-marker-alt"></i> <?= e($fundi['city']) ?>
                            </p>
                            
                            <p class="card-text small"><?= e(substr($fundi['bio'], 0, 100)) ?>...</p>
                            
                            <div class="d-flex gap-2 flex-wrap mb-3">
                                <?php foreach (array_slice($fundi['skills_array'], 0, 3) as $skillItem): ?>
                                    <span class="badge bg-warning text-dark"><?= e(trim($skillItem)) ?></span>
                                <?php endforeach; ?>
                                <?php if (count($fundi['skills_array']) > 3): ?>
                                    <span class="badge bg-secondary">+<?= count($fundi['skills_array']) - 3 ?></span>
                                <?php endif; ?>
                            </div>
                            
                            <?php if (!empty($fundi['portfolio'])): ?>
                                <div class="mb-2">
                                    <small class="text-muted"><i class="fas fa-images"></i> <?= count($fundi['portfolio']) ?> project photos</small>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="card-footer bg-white border-0 pb-3 d-flex gap-2">
                            <a href="/listing/fundi/<?= $fundi['id'] ?>" class="btn btn-outline-primary btn-sm flex-grow-1">View Profile</a>
                            <a href="tel:<?= e($fundi['phone']) ?>" class="btn btn-outline-success btn-sm"><i class="fas fa-phone"></i></a>
                            <a href="https://wa.me/<?= preg_replace('/[^0-9]/', '', $fundi['phone']) ?>" class="btn btn-outline-success btn-sm" target="_blank"><i class="fab fa-whatsapp"></i></a>
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
