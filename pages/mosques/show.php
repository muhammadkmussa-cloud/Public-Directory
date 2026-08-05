<?php
/**
 * Mosque Detail Page
 * Displays full mosque information, prayer times, reviews, and photos
 */

require_once __DIR__ . '/../includes/Database.php';
require_once __DIR__ . '/../includes/Auth.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/PrayerTimes.php';

$db = Database::getInstance();
$slug = $_GET['slug'] ?? '';

if (empty($slug)) {
    header('Location: /mosques');
    exit;
}

// Fetch mosque details
$stmt = $db->prepare("
    SELECT m.*, 
           (SELECT AVG(rating) FROM reviews WHERE entity_id = m.id AND entity_type = 'mosque') as avg_rating,
           (SELECT COUNT(*) FROM reviews WHERE entity_id = m.id AND entity_type = 'mosque') as review_count
    FROM mosques m
    WHERE m.slug = ? AND m.status = 'active'
");
$stmt->execute([$slug]);
$mosque = $stmt->fetch();

if (!$mosque) {
    header('HTTP/1.0 404 Not Found');
    include __DIR__ . '/layout/header.php';
    echo '<div class="container"><h1>Mosque Not Found</h1></div>';
    include __DIR__ . '/layout/footer.php';
    exit;
}

// Calculate today's prayer times
$prayerTimes = new PrayerTimes();
$todayPrayers = $prayerTimes->getDayPrayers(
    $mosque['latitude'],
    $mosque['longitude'],
    date('Y-m-d')
);

// Fetch photos
$photosStmt = $db->prepare("SELECT * FROM photos WHERE entity_id = ? AND entity_type = 'mosque' ORDER BY created_at DESC LIMIT 20");
$photosStmt->execute([$mosque['id']]);
$photos = $photosStmt->fetchAll();

// Fetch reviews
$reviewsStmt = $db->prepare("
    SELECT r.*, u.username, u.avatar, u.contributor_level,
           (SELECT COUNT(*) FROM review_helpful WHERE review_id = r.id) as helpful_count
    FROM reviews r
    JOIN users u ON r.user_id = u.id
    WHERE r.entity_id = ? AND r.entity_type = 'mosque' AND r.status = 'approved'
    ORDER BY r.created_at DESC
    LIMIT 10
");
$reviewsStmt->execute([$mosque['id']]);
$reviews = $reviewsStmt->fetchAll();

// Upcoming prayer time
$nextPrayer = $prayerTimes->getNextPrayer($todayPrayers);

include __DIR__ . '/layout/header.php';
?>

<div class="container py-4">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="/">Home</a></li>
            <li class="breadcrumb-item"><a href="/mosques">Mosques</a></li>
            <li class="breadcrumb-item active"><?= htmlspecialchars($mosque['name']) ?></li>
        </ol>
    </nav>

    <div class="row">
        <!-- Main Content -->
        <div class="col-lg-8">
            <!-- Mosque Header -->
            <div class="card mb-4">
                <?php if (!empty($photos)): ?>
                <img src="/uploads/<?= htmlspecialchars($photos[0]['filename']) ?>" class="card-img-top" alt="<?= htmlspecialchars($mosque['name']) ?>" style="height: 400px; object-fit: cover;">
                <?php endif; ?>
                
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <h1 class="h2 mb-2"><?= htmlspecialchars($mosque['name']) ?></h1>
                            <div class="mb-2">
                                <?= renderStars($mosque['avg_rating'] ?? 0) ?>
                                <span class="text-muted">(<?= $mosque['review_count'] ?> reviews)</span>
                            </div>
                            <p class="text-muted mb-2">
                                <i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($mosque['city']) ?>, <?= htmlspecialchars($mosque['county']) ?>
                            </p>
                        </div>
                        
                        <?php if ($mosque['is_verified']): ?>
                        <span class="badge badge-success"><i class="fas fa-check-circle"></i> Verified</span>
                        <?php endif; ?>
                    </div>

                    <?php if ($mosque['description']): ?>
                    <p class="mt-3"><?= nl2br(htmlspecialchars($mosque['description'])) ?></p>
                    <?php endif; ?>

                    <!-- Prayer Times Today -->
                    <div class="card bg-light mt-4">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="fas fa-clock"></i> Prayer Times - <?= date('l, F j, Y') ?></h5>
                        </div>
                        <div class="card-body">
                            <div class="row text-center">
                                <?php foreach ($todayPrayers as $name => $time): ?>
                                <div class="col-4 col-md-2 mb-3">
                                    <div class="prayer-time-box p-2 rounded <?= $name === $nextPrayer['name'] ? 'bg-primary text-white' : 'bg-white' ?>">
                                        <small><?= ucfirst($name) ?></small><br>
                                        <strong><?= date('g:i A', strtotime($time)) ?></strong>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <?php if ($nextPrayer['name']): ?>
                            <p class="mt-3 text-center">
                                <strong>Next Prayer:</strong> <?= ucfirst($nextPrayer['name']) ?> in <?= $nextPrayer['remaining'] ?>
                            </p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Mosque Info -->
                    <div class="row mt-4">
                        <div class="col-md-6">
                            <h5><i class="fas fa-map-marker-alt"></i> Location</h5>
                            <p><?= htmlspecialchars($mosque['address']) ?><br>
                            <?= htmlspecialchars($mosque['city']) ?>, <?= htmlspecialchars($mosque['county']) ?></p>
                            
                            <?php if ($mosque['phone']): ?>
                            <h5><i class="fas fa-phone"></i> Contact</h5>
                            <p><a href="tel:<?= htmlspecialchars($mosque['phone']) ?>"><?= htmlspecialchars($mosque['phone']) ?></a></p>
                            <?php endif; ?>
                        </div>
                        
                        <div class="col-md-6">
                            <h5><i class="fas fa-info-circle"></i> Information</h5>
                            <ul class="list-unstyled">
                                <?php if ($mosque['has_jummah']): ?>
                                <li><i class="fas fa-check text-success"></i> Jummah Prayers</li>
                                <?php endif; ?>
                                <?php if ($mosque['has_wudu']): ?>
                                <li><i class="fas fa-check text-success"></i> Wudu Facilities</li>
                                <?php endif; ?>
                                <?php if ($mosque['has_parking']): ?>
                                <li><i class="fas fa-check text-success"></i> Parking Available</li>
                                <?php endif; ?>
                                <?php if ($mosque['wheelchair_accessible']): ?>
                                <li><i class="fas fa-check text-success"></i> Wheelchair Accessible</li>
                                <?php endif; ?>
                                <?php if ($mosque['women_section']): ?>
                                <li><i class="fas fa-check text-success"></i> Women's Section</li>
                                <?php endif; ?>
                            </ul>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="mt-4 d-flex gap-2">
                        <button class="btn btn-primary" onclick="writeReview(<?= $mosque['id'] ?>, 'mosque')">
                            <i class="fas fa-star"></i> Write a Review
                        </button>
                        <button class="btn btn-outline-secondary" onclick="checkIn(<?= $mosque['id'] ?>, 'mosque')">
                            <i class="fas fa-map-marker-check"></i> Check In
                        </button>
                    </div>
                </div>
            </div>

            <!-- Photos Gallery -->
            <?php if (!empty($photos)): ?>
            <div class="card mb-4">
                <div class="card-header">
                    <h5><i class="fas fa-images"></i> Photos (<?= count($photos) ?>)</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <?php foreach ($photos as $photo): ?>
                        <div class="col-md-4 col-sm-6 mb-3">
                            <img src="/uploads/<?= htmlspecialchars($photo['filename']) ?>" 
                                 class="img-fluid rounded cursor-pointer" 
                                 alt="Photo"
                                 onclick="viewPhoto(this.src)">
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Reviews Section -->
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-comments"></i> Reviews</h5>
                    <button class="btn btn-sm btn-primary" onclick="writeReview(<?= $mosque['id'] ?>, 'mosque')">
                        Write a Review
                    </button>
                </div>
                <div class="card-body">
                    <?php if (empty($reviews)): ?>
                    <p class="text-muted text-center">No reviews yet. Be the first to review!</p>
                    <?php else: ?>
                    <?php foreach ($reviews as $review): ?>
                    <div class="review-item mb-4 pb-4 border-bottom">
                        <div class="d-flex align-items-start mb-2">
                            <img src="<?= htmlspecialchars($review['avatar'] ?: '/assets/img/default-avatar.png') ?>" 
                                 class="rounded-circle mr-3" width="50" height="50" alt="<?= htmlspecialchars($review['username']) ?>">
                            <div class="flex-grow-1">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h6 class="mb-0"><?= htmlspecialchars($review['username']) ?></h6>
                                        <small class="text-muted"><?= formatDate($review['created_at']) ?></small>
                                    </div>
                                    <?= renderStars($review['rating']) ?>
                                </div>
                                
                                <?php if ($review['text']): ?>
                                <p class="mt-2"><?= nl2br(htmlspecialchars($review['text'])) ?></p>
                                <?php endif; ?>
                                
                                <div class="mt-2">
                                    <button class="btn btn-sm btn-outline-secondary helpful-btn" data-review-id="<?= $review['id'] ?>">
                                        <i class="fas fa-thumbs-up"></i> Helpful (<?= $review['helpful_count'] ?>)
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="col-lg-4">
            <!-- Map -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-map"></i> Location</h5>
                </div>
                <div class="card-body p-0">
                    <div id="map" style="height: 300px;"></div>
                </div>
            </div>

            <!-- Weekly Prayer Schedule -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-calendar"></i> This Week</h5>
                </div>
                <div class="card-body p-0">
                    <table class="table table-sm mb-0">
                        <thead>
                            <tr>
                                <th>Day</th>
                                <th>Fajr</th>
                                <th>Dhuhr</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php for ($i = 0; $i < 7; $i++): 
                                $date = date('Y-m-d', strtotime("+{$i} days"));
                                $dayPrayers = $prayerTimes->getDayPrayers($mosque['latitude'], $mosque['longitude'], $date);
                            ?>
                            <tr>
                                <td><?= date('D', strtotime($date)) ?></td>
                                <td><?= date('g:i A', strtotime($dayPrayers['Fajr'])) ?></td>
                                <td><?= date('g:i A', strtotime($dayPrayers['Dhuhr'])) ?></td>
                            </tr>
                            <?php endfor; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Photo Modal -->
<div class="modal fade" id="photoModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-body p-0">
                <button type="button" class="close position-absolute" style="right: 10px; top: 10px; z-index: 1000;" data-dismiss="modal">
                    <span>&times;</span>
                </button>
                <img id="photoModalImage" src="" class="img-fluid w-100" alt="">
            </div>
        </div>
    </div>
</div>

<script>
const mosqueId = <?= $mosque['id'] ?>;
const mosqueLat = <?= $mosque['latitude'] ?>;
const mosqueLng = <?= $mosque['longitude'] ?>;

document.addEventListener('DOMContentLoaded', function() {
    if (typeof L !== 'undefined') {
        const map = L.map('map').setView([mosqueLat, mosqueLng], 15);
        
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap contributors'
        }).addTo(map);
        
        L.marker([mosqueLat, mosqueLng]).addTo(map)
            .bindPopup('<?= addslashes($mosque['name']) ?>')
            .openPopup();
    }
});

function viewPhoto(src) {
    document.getElementById('photoModalImage').src = src;
    $('#photoModal').modal('show');
}

function writeReview(entityId, entityType) {
    window.location.href = '/reviews/write?entity=' + entityId + '&type=' + entityType;
}

function checkIn(entityId, entityType) {
    fetch('/api/checkin.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: new URLSearchParams({
            entity_id: entityId,
            entity_type: entityType,
            csrf_token: getCsrfToken()
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('✓ Checked in successfully!');
        } else {
            alert('Error: ' + data.error);
        }
    });
}
</script>

<?php include __DIR__ . '/layout/footer.php'; ?>
