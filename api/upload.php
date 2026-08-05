<?php
/**
 * Umma Directory — Image Upload API (secure)
 *
 *   POST api/upload.php  (multipart/form-data)
 *     image : file            required
 *     dir   : target folder   optional (sanitized to [a-z0-9_-])
 *
 * Requires login + CSRF. Returns { file, thumbnail, mime, size } with paths
 * relative to the site root (e.g. "uploads/reviews/abc123.jpg").
 *
 * Hardening: MIME sniffed with finfo, image re-encoded with GD (strips EXIF
 * and embedded payloads), random filenames, thumbnail generated, PHP execution
 * blocked in uploads/ via .htaccess.
 */
require __DIR__ . '/_bootstrap.php';

require_method('POST');
rate_limit('upload', 20, 3600);
require_login();
require_csrf();

$db = null; // not needed, but keep consistent with bootstrap

$allowedTypes = ALLOWED_IMAGE_TYPES;
$maxSize = MAX_FILE_SIZE;
$uploadDir = UPLOAD_PATH;

// Target subdirectory (sanitized)
$targetSubDir = isset($_POST['dir']) ? preg_replace('/[^a-zA-Z0-9_-]/', '', $_POST['dir']) : 'general';
if ($targetSubDir === '') {
    $targetSubDir = 'general';
}
$fullUploadDir = $uploadDir . $targetSubDir . '/';

if (!is_dir($fullUploadDir)) {
    if (!mkdir($fullUploadDir, 0755, true) && !is_dir($fullUploadDir)) {
        json_err('Could not create upload directory', 500);
    }
}

if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
    json_err('No file uploaded', 422);
}

$file = $_FILES['image'];

if ($file['size'] > $maxSize) {
    json_err('File exceeds the ' . round($maxSize / 1048576) . 'MB limit', 413);
}

// MIME check via finfo (not client-provided)
$finfo = new finfo(FILEINFO_MIME_TYPE);
$mimeType = $finfo->file($file['tmp_name']);
if (!in_array($mimeType, $allowedTypes, true)) {
    json_err('Invalid file type. Only JPG, PNG, GIF, WEBP allowed.', 415);
}

// Load image (validates it's a real image)
$sourceImage = null;
switch ($mimeType) {
    case 'image/jpeg': $sourceImage = @imagecreatefromjpeg($file['tmp_name']); break;
    case 'image/png':  $sourceImage = @imagecreatefrompng($file['tmp_name']);  break;
    case 'image/gif':  $sourceImage = @imagecreatefromgif($file['tmp_name']);  break;
    case 'image/webp': $sourceImage = @imagecreatefromwebp($file['tmp_name']); break;
}
if (!$sourceImage) {
    json_err('Corrupted or unsupported image', 422);
}

// Random secure filename
$extMap = ['image/jpeg' => '.jpg', 'image/png' => '.png', 'image/gif' => '.gif', 'image/webp' => '.webp'];
$filename = bin2hex(random_bytes(16)) . '_' . time() . $extMap[$mimeType];
$destinationPath = $fullUploadDir . $filename;
$thumbnailPath = $fullUploadDir . 'thumb_' . $filename;

// Re-encode (strips metadata / embedded payloads)
$origW = imagesx($sourceImage);
$origH = imagesy($sourceImage);
$quality = 85;

switch ($mimeType) {
    case 'image/jpeg': imagejpeg($sourceImage, $destinationPath, $quality); break;
    case 'image/png':  imagepng($sourceImage, $destinationPath, 6);         break;
    case 'image/gif':  imagegif($sourceImage, $destinationPath);            break;
    case 'image/webp': imagewebp($sourceImage, $destinationPath, $quality); break;
}

// Thumbnail (max 300x300)
$tw = 300; $th = 300;
$ratio = min($tw / $origW, $th / $origH);
$nw = max(1, (int)($origW * $ratio));
$nh = max(1, (int)($origH * $ratio));
$thumb = imagecreatetruecolor($nw, $nh);
if (in_array($mimeType, ['image/png', 'image/gif', 'image/webp'], true)) {
    imagealphablending($thumb, false);
    imagesavealpha($thumb, true);
    $trans = imagecolorallocatealpha($thumb, 255, 255, 255, 127);
    imagefilledrectangle($thumb, 0, 0, $nw, $nh, $trans);
}
imagecopyresampled($thumb, $sourceImage, 0, 0, 0, 0, $nw, $nh, $origW, $origH);

switch ($mimeType) {
    case 'image/jpeg': imagejpeg($thumb, $thumbnailPath, $quality); break;
    case 'image/png':  imagepng($thumb, $thumbnailPath, 6);         break;
    case 'image/gif':  imagegif($thumb, $thumbnailPath);            break;
    case 'image/webp': imagewebp($thumb, $thumbnailPath, $quality); break;
}

imagedestroy($sourceImage);
imagedestroy($thumb);

if (!file_exists($destinationPath) || !file_exists($thumbnailPath)) {
    json_err('Failed to save image', 500);
}
chmod($destinationPath, 0644);
chmod($thumbnailPath, 0644);

json_ok([
    'file'      => 'uploads/' . $targetSubDir . '/' . $filename,
    'thumbnail' => 'uploads/' . $targetSubDir . '/thumb_' . $filename,
    'mime'      => $mimeType,
    'size'      => filesize($destinationPath),
], 201);
