<?php
/**
 * Check-in API Handler
 * Allows users to check in at businesses/mosques
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/Database.php';
require_once __DIR__ . '/../includes/Auth.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$db = Database::getInstance();
$auth = new Auth();
$user = $auth->getCurrentUser();

if (!$user) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Please login to check in']);
    exit;
}

try {
    $listingId = filter_input(INPUT_POST, 'listing_id', FILTER_VALIDATE_INT);
    $type = $_POST['type'] ?? 'business';
    $message = trim($_POST['message'] ?? '');

    if (!$listingId) {
        throw new Exception('Invalid listing');
    }

    // Verify listing exists
    $table = $type === 'business' ? 'businesses' : ($type === 'mosque' ? 'mosques' : 'fundis');
    $listing = $db->queryOne("SELECT id, name FROM $table WHERE id = :id", [':id' => $listingId]);

    if (!$listing) {
        throw new Exception('Listing not found');
    }

    // Record check-in
    $db->query("INSERT INTO check_ins (user_id, listing_id, listing_type, message, created_at) 
                VALUES (:uid, :lid, :type, :msg, NOW())", [
        ':uid' => $user['id'],
        ':lid' => $listingId,
        ':type' => $type,
        ':msg' => $message
    ]);

    // Update user check-in count for badges
    $db->query("UPDATE users SET check_in_count = check_in_count + 1 WHERE id = :uid", [':uid' => $user['id']]);

    echo json_encode([
        'success' => true, 
        'message' => "Checked in at {$listing['name']}!",
        'check_in_count' => ($user['check_in_count'] ?? 0) + 1
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
