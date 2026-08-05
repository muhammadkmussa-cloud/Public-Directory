<?php
/**
 * Fundi Detail Page
 * Displays full fundi profile, portfolio, skills, reviews, and contact info
 */

require_once __DIR__ . '/../includes/Database.php';
require_once __DIR__ . '/../includes/Auth.php';
require_once __DIR__ . '/../includes/helpers.php';

$db = Database::getInstance();
$slug = $_GET['slug'] ?? '';

if (empty($slug)) {
    header('Location: /fundis');
    exit;
}

// Fetch fundi details
$stmt = $db->prepare("
    SELECT f.*, u.username, u.email, u.phone, u.avatar,
           (SELECT AVG(rating) FROM reviews WHERE entity_id = f.id AND entity_type = 'fundi') as avg_rating,
           (SELECT COUNT(*) FROM reviews WHERE entity_id = f.id AND entity_type = 'fundi') as review_count
    FROM fundis f
    JOIN users u ON f.user_id = u.id
    WHERE f.slug = ? AND f.status = 'active'
");
$stmt->execute([$slug]);
$fundi = $stmt->fetch();

if (!$fundi) {
    header('HTTP/1.0 404 Not Found');
    include __DIR__ . '/layout/header.php';
    echo '<div class="container"><h1>Fundi Not Found</h1></div>';
    include __DIR__ . '/layout/footer.php';
    exit;
}

// Parse skills
$skills = !empty($fundi['skills']) ? json_decode($fundi['skills'], true) : [];

// Fetch portfolio photos
$photosStmt = $db->prepare("SELECT * FROM photos WHERE entity_id = ? AND entity_type = 'fundi' ORDER BY created_at DESC LIMIT 20");
$photosStmt->execute([$fundi['id']]);
$photos = $photosStmt->fetchAll();

// Fetch reviews
$reviewsStmt = $db->prepare("
    SELECT r.*, u.username, u.avatar, u.contributor_level,
           (SELECT COUNT(*) FROM review_helpful WHERE review_id = r.id) as helpful_count
    FROM reviews r
    JOIN users u ON r.user_id = u.id
    WHERE r.entity_id = ? AND r.entity_type = 'fundi' AND r.status = 'approved'
    ORDER BY r.created_at DESC
    LIMIT 10
");
$reviewsStmt->execute([$fundi['id']]);
$reviews = $reviewsStmt->fetchAll();

include __DIR__ . '/layout/header.php';
?>

<div class="container py-4">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="/">Home</a></li>
            <li class="breadcrumb-item"><a href="/fundis">Fundis</a></li>
            <li class="breadcrumb-item active"><?= htmlspecialchars($fundi['name'] ?: $fundi['username']) ?></li>
        </ol>
    </nav>

    <div class="row">
        <!-- Main Content -->
        <div class="col-lg-8">
            <!-- Fundi Profile Header -->
            <div class="card mb-4">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-md-3 text-center">
                            <img src="<?= htmlspecialchars($fundi['avatar'] ?: '/assets/img/default-avatar.png') ?>" 
                                 class="rounded-circle mb-3" width="150" height="150" alt="<?= htmlspecialchars($fundi['name']) ?>">
                        </div>
                        <div class="col-md-9">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <h1 class="h2 mb-2"><?= htmlspecialchars($fundi['name'] ?: $fundi['username']) ?></h1>
                                    <p class="text-muted mb-2">
                                        <i class="fas fa-tools"></i> <?= htmlspecialchars($fundi['category']) ?>
                                        <?php if (!empty($skills)): ?>
                                        <span class="mx-2">•</span>
                                        <?php foreach (array_slice($skills, 0, 3) as $skill): ?>
                                        <span class="badge badge-secondary"><?= htmlspecialchars($skill) ?></span>
                                        <?php endforeach; ?>
                                        <?php endif; ?>
                                    </p>
                                    <div class="mb-2">
                                        <?= renderStars($fundi['avg_rating'] ?? 0) ?>
                                        <span class="text-muted">(<?= $fundi['review_count'] ?> reviews)</span>
                                    </div>
                                </div>
                                
                                <?php if ($fundi['is_verified']): ?>
                                <span class="badge badge-success"><i class="fas fa-check-circle"></i> Verified</span>
                                <?php endif; ?>
                            </div>

                            <?php if ($fundi['bio']): ?>
                            <p class="mt-3"><?= nl2br(htmlspecialchars($fundi['bio'])) ?></p>
                            <?php endif; ?>

                            <!-- Action Buttons -->
                            <div class="mt-4 d-flex gap-2 flex-wrap">
                                <?php if ($fundi['phone']): ?>
                                <a href="tel:<?= htmlspecialchars($fundi['phone']) ?>" class="btn btn-primary">
                                    <i class="fas fa-phone"></i> Call Now
                                </a>
                                <?php endif; ?>
                                
                                <?php if ($fundi['whatsapp']): ?>
                                <a href="https://wa.me/<?= htmlspecialchars($fundi['whatsapp']) ?>" target="_blank" class="btn btn-success">
                                    <i class="fab fa-whatsapp"></i> WhatsApp
                                </a>
                                <?php endif; ?>
                                
                                <button class="btn btn-outline-primary" onclick="requestQuote(<?= $fundi['id'] ?>)">
                                    <i class="fas fa-file-invoice"></i> Request Quote
                                </button>
                                
                                <button class="btn btn-outline-secondary" onclick="writeReview(<?= $fundi['id'] ?>, 'fundi')">
                                    <i class="fas fa-star"></i> Write a Review
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Skills & Services -->
            <?php if (!empty($skills) || $fundi['services']): ?>
            <div class="card mb-4">
                <div class="card-header">
                    <h5><i class="fas fa-list"></i> Skills & Services</h5>
                </div>
                <div class="card-body">
                    <?php if (!empty($skills)): ?>
                    <h6>Skills</h6>
                    <div class="mb-3">
                        <?php foreach ($skills as $skill): ?>
                        <span class="badge badge-info mr-2 mb-2"><?= htmlspecialchars($skill) ?></span>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($fundi['services']): ?>
                    <h6>Services Offered</h6>
                    <p><?= nl2br(htmlspecialchars($fundi['services'])) ?></p>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Portfolio Gallery -->
            <?php if (!empty($photos)): ?>
            <div class="card mb-4">
                <div class="card-header">
                    <h5><i class="fas fa-briefcase"></i> Portfolio (<?= count($photos) ?> projects)</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <?php foreach ($photos as $photo): ?>
                        <div class="col-md-4 col-sm-6 mb-3">
                            <img src="/uploads/<?= htmlspecialchars($photo['filename']) ?>" 
                                 class="img-fluid rounded cursor-pointer" 
                                 alt="Portfolio item"
                                 onclick="viewPhoto(this.src)">
                            <?php if ($photo['description']): ?>
                            <small class="d-block mt-1 text-muted"><?= htmlspecialchars($photo['description']) ?></small>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Reviews Section -->
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-comments"></i> Client Reviews</h5>
                    <button class="btn btn-sm btn-primary" onclick="writeReview(<?= $fundi['id'] ?>, 'fundi')">
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
            <!-- Contact Info -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-address-card"></i> Contact Information</h5>
                </div>
                <div class="card-body">
                    <?php if ($fundi['location']): ?>
                    <p><strong><i class="fas fa-map-marker-alt"></i> Location:</strong><br>
                    <?= htmlspecialchars($fundi['location']) ?></p>
                    <?php endif; ?>
                    
                    <?php if ($fundi['phone']): ?>
                    <p><strong><i class="fas fa-phone"></i> Phone:</strong><br>
                    <a href="tel:<?= htmlspecialchars($fundi['phone']) ?>"><?= htmlspecialchars($fundi['phone']) ?></a></p>
                    <?php endif; ?>
                    
                    <?php if ($fundi['whatsapp']): ?>
                    <p><strong><i class="fab fa-whatsapp"></i> WhatsApp:</strong><br>
                    <a href="https://wa.me/<?= htmlspecialchars($fundi['whatsapp']) ?>" target="_blank">Chat on WhatsApp</a></p>
                    <?php endif; ?>
                    
                    <?php if ($fundi['email']): ?>
                    <p><strong><i class="fas fa-envelope"></i> Email:</strong><br>
                    <a href="mailto:<?= htmlspecialchars($fundi['email']) ?>"><?= htmlspecialchars($fundi['email']) ?></a></p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Work Hours -->
            <?php if ($fundi['working_hours']): ?>
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-clock"></i> Working Hours</h5>
                </div>
                <div class="card-body">
                    <p class="mb-0"><?= nl2br(htmlspecialchars($fundi['working_hours'])) ?></p>
                </div>
            </div>
            <?php endif; ?>

            <!-- Price Range -->
            <?php if ($fundi['price_range']): ?>
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-tag"></i> Price Range</h5>
                </div>
                <div class="card-body">
                    <p class="mb-0"><?= str_repeat('$', $fundi['price_range']) ?> <?= ['Budget', 'Moderate', 'Premium', 'Luxury'][$fundi['price_range'] - 1] ?? '' ?></p>
                </div>
            </div>
            <?php endif; ?>

            <!-- Badges -->
            <?php if ($fundi['badges']): ?>
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-award"></i> Badges</h5>
                </div>
                <div class="card-body">
                    <?php 
                    $badges = json_decode($fundi['badges'], true);
                    if (is_array($badges)):
                        foreach ($badges as $badge):
                    ?>
                    <span class="badge badge-warning mr-2 mb-2"><?= htmlspecialchars($badge) ?></span>
                    <?php 
                        endforeach;
                    endif;
                    ?>
                </div>
            </div>
            <?php endif; ?>
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

<!-- Quote Request Modal -->
<div class="modal fade" id="quoteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Request Quote</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <form id="quoteForm">
                    <input type="hidden" name="fundi_id" value="<?= $fundi['id'] ?>">
                    <div class="form-group">
                        <label>Your Name</label>
                        <input type="text" class="form-control" name="name" required>
                    </div>
                    <div class="form-group">
                        <label>Phone Number</label>
                        <input type="tel" class="form-control" name="phone" required>
                    </div>
                    <div class="form-group">
                        <label>Service Needed</label>
                        <textarea class="form-control" name="description" rows="4" required placeholder="Describe the work you need done..."></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary btn-block">Send Request</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
const fundiId = <?= $fundi['id'] ?>;

function viewPhoto(src) {
    document.getElementById('photoModalImage').src = src;
    $('#photoModal').modal('show');
}

function writeReview(entityId, entityType) {
    window.location.href = '/reviews/write?entity=' + entityId + '&type=' + entityType;
}

function requestQuote(fundiId) {
    $('#quoteModal').modal('show');
}

document.getElementById('quoteForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    fetch('/api/quotes.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: new URLSearchParams(new FormData(this))
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('✓ Quote request sent! The fundi will contact you soon.');
            $('#quoteModal').modal('hide');
        } else {
            alert('Error: ' + data.error);
        }
    });
});
</script>

<?php include __DIR__ . '/layout/footer.php'; ?>
