<?php
/**
 * Image Upload Handler - SECURE VERSION
 * Handles secure image uploads with MIME validation, re-encoding, and thumbnail generation
 */

require_once __DIR__ . '/../includes/Database.php';
require_once __DIR__ . '/../includes/Auth.php';

header('Content-Type: application/json');

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

// Check authentication
Auth::requireLogin();

// Verify CSRF token
if (!Auth::verifyCsrfToken($_POST['csrf_token'] ?? '')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Invalid security token']);
    exit;
}

$db = Database::getInstance();

// Configuration
$allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
$maxSize = 5 * 1024 * 1024; // 5MB
$uploadDir = __DIR__ . '/../uploads/';

// Get target subdirectory (sanitize input)
$targetSubDir = isset($_POST['dir']) ? preg_replace('/[^a-zA-Z0-9_-]/', '', $_POST['dir']) : 'general';
$fullUploadDir = $uploadDir . $targetSubDir . '/';

// Ensure directory exists and is writable
if (!is_dir($fullUploadDir)) {
    if (!mkdir($fullUploadDir, 0755, true)) {
        echo json_encode(['success' => false, 'error' => 'Failed to create upload directory']);
        exit;
    }
}

// Check if file exists in request
if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
    $errorCodes = [
        UPLOAD_ERR_INI_SIZE => 'File exceeds server limit',
        UPLOAD_ERR_FORM_SIZE => 'File exceeds form limit',
        UPLOAD_ERR_PARTIAL => 'File only partially uploaded',
        UPLOAD_ERR_NO_FILE => 'No file uploaded',
        UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
        UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
        UPLOAD_ERR_EXTENSION => 'File upload stopped by extension'
    ];
    $code = isset($_FILES['image']) ? $_FILES['image']['error'] : UPLOAD_ERR_NO_FILE;
    echo json_encode(['success' => false, 'error' => $errorCodes[$code] ?? 'Unknown upload error']);
    exit;
}

$file = $_FILES['image'];

// FIX 1: Validate File Size First
if ($file['size'] > $maxSize) {
    echo json_encode(['success' => false, 'error' => 'File size exceeds 5MB limit']);
    exit;
}

// FIX 2: Validate MIME Type using finfo (not client-provided type)
$finfo = new finfo(FILEINFO_MIME_TYPE);
$mimeType = $finfo->file($file['tmp_name']);

if (!in_array($mimeType, $allowedTypes)) {
    echo json_encode(['success' => false, 'error' => 'Invalid file type. Only JPG, PNG, GIF, WEBP allowed.']);
    exit;
}

// FIX 3: Validate Image Integrity by attempting to load it
$sourceImage = null;
switch ($mimeType) {
    case 'image/jpeg': $sourceImage = imagecreatefromjpeg($file['tmp_name']); break;
    case 'image/png': $sourceImage = imagecreatefrompng($file['tmp_name']); break;
    case 'image/gif': $sourceImage = imagecreatefromgif($file['tmp_name']); break;
    case 'image/webp': $sourceImage = imagecreatefromwebp($file['tmp_name']); break;
}

if (!$sourceImage) {
    echo json_encode(['success' => false, 'error' => 'Corrupted image file']);
    exit;
}

// FIX 4: Generate Secure Filename (random hex, no user input)
$extensionMap = [
    'image/jpeg' => '.jpg',
    'image/png' => '.png',
    'image/gif' => '.gif',
    'image/webp' => '.webp'
];
$extension = $extensionMap[$mimeType];
$filename = bin2hex(random_bytes(16)) . '_' . time() . $extension;
$destinationPath = $fullUploadDir . $filename;
$thumbnailPath = $fullUploadDir . 'thumb_' . $filename;

// FIX 5: Re-encode Image (Strips Metadata, EXIF, Malicious Scripts)
$quality = 85;
$origWidth = imagesx($sourceImage);
$origHeight = imagesy($sourceImage);

// Save Original (Re-encoded cleanly)
if ($mimeType == 'image/jpeg') {
    imagejpeg($sourceImage, $destinationPath, $quality);
} elseif ($mimeType == 'image/png') {
    imagepng($sourceImage, $destinationPath, 6);
} elseif ($mimeType == 'image/gif') {
    imagegif($sourceImage, $destinationPath);
} elseif ($mimeType == 'image/webp') {
    imagewebp($sourceImage, $destinationPath, $quality);
}

// FIX 6: Generate Thumbnail (Max 300x300)
$thumbWidth = 300;
$thumbHeight = 300;
$ratio = min($thumbWidth / $origWidth, $thumbHeight / $origHeight);
$newWidth = (int)($origWidth * $ratio);
$newHeight = (int)($origHeight * $ratio);

$thumbImage = imagecreatetruecolor($newWidth, $newHeight);

// Preserve transparency for PNG/GIF/WEBP
if (in_array($mimeType, ['image/png', 'image/gif', 'image/webp'])) {
    imagealphablending($thumbImage, false);
    imagesavealpha($thumbImage, true);
    $transparent = imagecolorallocatealpha($thumbImage, 255, 255, 255, 127);
    imagefilledrectangle($thumbImage, 0, 0, $newWidth, $newHeight, $transparent);
}

imagecopyresampled($thumbImage, $sourceImage, 0, 0, 0, 0, $newWidth, $newHeight, $origWidth, $origHeight);

if ($mimeType == 'image/jpeg') {
    imagejpeg($thumbImage, $thumbnailPath, $quality);
} elseif ($mimeType == 'image/png') {
    imagepng($thumbImage, $thumbnailPath, 6);
} elseif ($mimeType == 'image/gif') {
    imagegif($thumbImage, $thumbnailPath);
} elseif ($mimeType == 'image/webp') {
    imagewebp($thumbImage, $thumbnailPath, $quality);
}

// Cleanup memory
imagedestroy($sourceImage);
imagedestroy($thumbImage);

// Verify files were created
if (!file_exists($destinationPath) || !file_exists($thumbnailPath)) {
    echo json_encode(['success' => false, 'error' => 'Failed to save image files']);
    exit;
}

// Set correct permissions
chmod($destinationPath, 0644);
chmod($thumbnailPath, 0644);

// Return relative paths for database storage
$relativePath = 'uploads/' . $targetSubDir . '/' . $filename;
$relativeThumb = 'uploads/' . $targetSubDir . '/thumb_' . $filename;

echo json_encode([
    'success' => true,
    'file' => $relativePath,
    'thumbnail' => $relativeThumb,
    'mime' => $mimeType,
    'size' => filesize($destinationPath)
]);
