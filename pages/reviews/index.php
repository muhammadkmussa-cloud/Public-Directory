<?php
/**
 * Review Display Page Component
 * Shows all reviews for a specific business/mosque/fundi
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/Database.php';
require_once __DIR__ . '/../includes/Auth.php';
require_once __DIR__ . '/../includes/helpers.php';

$db = Database::getInstance();
$auth = new Auth();
$user = $auth->getCurrentUser();

// Get entity details
$entityId = $_GET['id'] ?? 0;
$entityType = $_GET['type'] ?? 'business'; // business, mosque, fundi

if (!$entityId) {
    header('Location: /');
    exit;
}

// Fetch entity details
$table = $entityType === 'mosque' ? 'mosques' : ($entityType === 'fundi' ? 'fundis' : 'businesses');
$stmt = $db->query("SELECT * FROM $table WHERE id = ?", [$entityId]);
$entity = $stmt->fetch();

if (!$entity) {
    header('HTTP/1.0 404 Not Found');
    include __DIR__ . '/layout/404.php';
    exit;
}

// Fetch reviews
$reviewsStmt = $db->query(
    "SELECT r.*, u.name as user_name, u.avatar 
     FROM reviews r 
     JOIN users u ON r.user_id = u.id 
     WHERE r.entity_id = ? AND r.entity_type = ? 
     ORDER BY r.created_at DESC",
    [$entityId, $entityType]
);
$reviews = $reviewsStmt->fetchAll();

// Calculate average rating
$ratingStmt = $db->query(
    "SELECT AVG(rating) as avg_rating, COUNT(*) as total_reviews 
     FROM reviews 
     WHERE entity_id = ? AND entity_type = ?",
    [$entityId, $entityType]
);
$ratingData = $ratingStmt->fetch();
$avgRating = round($ratingData['avg_rating'] ?? 0, 1);
$totalReviews = $ratingData['total_reviews'] ?? 0;

$pageTitle = "Reviews - " . ($entity['name'] ?? 'Unknown');
include __DIR__ . '/layout/header.php';
?>

<div class="container py-5">
    <div class="row mb-4">
        <div class="col-12">
            <a href="/<?= $entityType ?>s/show?id=<?= $entityId ?>" class="btn btn-outline-primary btn-sm">
                ← Back to <?= ucfirst($entityType) ?>
            </a>
        </div>
    </div>

    <div class="row">
        <!-- Rating Summary -->
        <div class="col-lg-4 mb-4">
            <div class="card shadow-sm">
                <div class="card-body text-center">
                    <h3 class="card-title"><?= htmlspecialchars($entity['name']) ?></h3>
                    <div class="rating-large my-3">
                        <?= renderStars($avgRating) ?>
                    </div>
                    <h1 class="display-4 fw-bold"><?= $avgRating ?></h1>
                    <p class="text-muted"><?= $totalReviews ?> <?= $totalReviews === 1 ? 'review' : 'reviews' ?></p>
                    
                    <?php if ($user): ?>
                    <button class="btn btn-primary w-100 mt-3" data-bs-toggle="modal" data-bs-target="#reviewModal">
                        Write a Review
                    </button>
                    <?php else: ?>
                    <a href="/auth/login?redirect=/reviews?id=<?= $entityId ?>&type=<?= $entityType ?>" 
                       class="btn btn-primary w-100 mt-3">
                        Login to Review
                    </a>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Rating Breakdown -->
            <div class="card shadow-sm mt-3">
                <div class="card-body">
                    <h6 class="card-subtitle mb-3 text-muted">Rating Breakdown</h6>
                    <?php for ($i = 5; $i >= 1; $i--): 
                        $countStmt = $db->query("SELECT COUNT(*) as cnt FROM reviews WHERE entity_id = ? AND entity_type = ? AND rating = ?", [$entityId, $entityType, $i]);
                        $count = $countStmt->fetch()['cnt'] ?? 0;
                        $percent = $totalReviews > 0 ? ($count / $totalReviews) * 100 : 0;
                    ?>
                    <div class="d-flex align-items-center mb-2">
                        <span class="me-2"><?= $i ?> ★</span>
                        <div class="progress flex-grow-1" style="height: 8px;">
                            <div class="progress-bar bg-warning" style="width: <?= $percent ?>%"></div>
                        </div>
                        <span class="ms-2 text-muted small"><?= $count ?></span>
                    </div>
                    <?php endfor; ?>
                </div>
            </div>
        </div>

        <!-- Reviews List -->
        <div class="col-lg-8">
            <h4 class="mb-3">Customer Reviews</h4>
            
            <?php if (empty($reviews)): ?>
            <div class="alert alert-info">
                No reviews yet. Be the first to review this <?= $entityType ?>!
            </div>
            <?php else: ?>
            <div class="reviews-list">
                <?php foreach ($reviews as $review): ?>
                <div class="card mb-3 shadow-sm review-card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <div class="d-flex align-items-center">
                                <img src="<?= htmlspecialchars($review['avatar'] ?? '/assets/images/avatar.png') ?>" 
                                     alt="<?= htmlspecialchars($review['user_name']) ?>" 
                                     class="rounded-circle me-2" width="40" height="40">
                                <div>
                                    <h6 class="mb-0"><?= htmlspecialchars($review['user_name']) ?></h6>
                                    <small class="text-muted"><?= timeAgo($review['created_at']) ?></small>
                                </div>
                            </div>
                            <?= renderStars($review['rating'], 'sm') ?>
                        </div>
                        
                        <h5 class="card-title"><?= htmlspecialchars($review['title']) ?></h5>
                        <p class="card-text"><?= nl2br(htmlspecialchars($review['content'])) ?></p>
                        
                        <?php if ($review['photos']): ?>
                        <div class="review-photos mt-2">
                            <?php 
                            $photos = json_decode($review['photos'], true);
                            if (is_array($photos)):
                                foreach (array_slice($photos, 0, 4) as $photo): 
                            ?>
                            <img src="<?= htmlspecialchars($photo) ?>" alt="Review photo" class="img-thumbnail me-1" style="max-width: 80px; max-height: 80px;">
                            <?php 
                                endforeach;
                            endif; 
                            ?>
                        </div>
                        <?php endif; ?>
                        
                        <!-- Helpful & Report -->
                        <div class="mt-3 d-flex gap-3">
                            <button class="btn btn-sm btn-outline-secondary helpful-btn" 
                                    data-review-id="<?= $review['id'] ?>">
                                👍 Helpful (<span class="helpful-count"><?= $review['helpful_count'] ?? 0 ?></span>)
                            </button>
                            <?php if ($user && $user['id'] != $review['user_id']): ?>
                            <button class="btn btn-sm btn-outline-danger" onclick="reportReview(<?= $review['id'] ?>)">
                                Report
                            </button>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Owner Response -->
                        <?php if ($review['owner_response']): ?>
                        <div class="owner-response mt-3 p-3 bg-light rounded">
                            <strong>Response from owner:</strong>
                            <p class="mb-0"><?= nl2br(htmlspecialchars($review['owner_response'])) ?></p>
                            <small class="text-muted"><?= timeAgo($review['response_date']) ?></small>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Review Modal -->
<?php if ($user): ?>
<div class="modal fade" id="reviewModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form id="reviewForm" enctype="multipart/form-data">
                <input type="hidden" name="entity_id" value="<?= $entityId ?>">
                <input type="hidden" name="entity_type" value="<?= $entityType ?>">
                <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                
                <div class="modal-header">
                    <h5 class="modal-title">Write a Review</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Overall Rating</label>
                        <div class="star-rating-input">
                            <input type="radio" name="rating" value="5" id="star5" required>
                            <label for="star5">★</label>
                            <input type="radio" name="rating" value="4" id="star4">
                            <label for="star4">★</label>
                            <input type="radio" name="rating" value="3" id="star3">
                            <label for="star3">★</label>
                            <input type="radio" name="rating" value="2" id="star2">
                            <label for="star2">★</label>
                            <input type="radio" name="rating" value="1" id="star1">
                            <label for="star1">★</label>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Review Title</label>
                        <input type="text" name="title" class="form-control" required maxlength="100">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Your Review</label>
                        <textarea name="content" class="form-control" rows="5" required maxlength="2000"></textarea>
                        <small class="text-muted">Max 2000 characters</small>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Add Photos (optional)</label>
                        <input type="file" name="photos[]" class="form-control" multiple accept="image/*">
                        <small class="text-muted">Upload up to 5 photos</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Submit Review</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<script>
document.getElementById('reviewForm')?.addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    formData.append('action', 'create');
    
    fetch('/api/reviews.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            window.location.reload();
        } else {
            alert(data.message || 'Failed to submit review');
        }
    })
    .catch(err => alert('Error submitting review'));
});

function reportReview(reviewId) {
    if (confirm('Report this review as inappropriate?')) {
        const formData = new FormData();
        formData.append('action', 'report');
        formData.append('review_id', reviewId);
        formData.append('csrf_token', document.querySelector('meta[name="csrf-token"]')?.content || '');
        
        fetch('/api/reviews.php', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            alert(data.success ? 'Review reported successfully' : 'Failed to report review');
        });
    }
}

document.querySelectorAll('.helpful-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        const reviewId = this.dataset.reviewId;
        const formData = new FormData();
        formData.append('action', 'helpful');
        formData.append('review_id', reviewId);
        formData.append('csrf_token', document.querySelector('meta[name="csrf-token"]')?.content || '');
        
        fetch('/api/reviews.php', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                this.querySelector('.helpful-count').textContent = data.count;
                this.disabled = true;
            }
        });
    });
});
</script>

<?php include __DIR__ . '/layout/footer.php'; ?>
