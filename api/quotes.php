<?php
/**
 * Quote Request API
 * Handles quote requests for fundis
 */

require_once __DIR__ . '/../includes/Database.php';
require_once __DIR__ . '/../includes/Auth.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

$db = Database::getInstance();

// Get POST data
$fundiId = $_POST['fundi_id'] ?? null;
$name = $_POST['name'] ?? null;
$phone = $_POST['phone'] ?? null;
$description = $_POST['description'] ?? null;

// Validation
if (!$fundiId || !$name || !$phone || !$description) {
    echo json_encode(['success' => false, 'error' => 'All fields are required']);
    exit;
}

// Verify fundi exists
$stmt = $db->prepare("SELECT id, user_id FROM fundis WHERE id = ? AND status = 'active'");
$stmt->execute([$fundiId]);
$fundi = $stmt->fetch();

if (!$fundi) {
    echo json_encode(['success' => false, 'error' => 'Fundi not found']);
    exit;
}

// Insert quote request
try {
    $stmt = $db->prepare("
        INSERT INTO quote_requests (fundi_id, customer_name, customer_phone, description, status)
        VALUES (?, ?, ?, ?, 'pending')
    ");
    $stmt->execute([$fundiId, $name, $phone, $description]);
    
    // TODO: Send notification to fundi (email/SMS)
    
    echo json_encode([
        'success' => true,
        'message' => 'Quote request sent successfully'
    ]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Failed to send quote request']);
}
