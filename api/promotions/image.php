<?php
require_once __DIR__ . '/../../config.php';

$user = require_auth();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(405, ['error' => 'Method not allowed']);
}

$id = (int) ($_GET['id'] ?? 0);
if (!$id) {
    json_response(400, ['error' => 'Missing promotion id']);
}

// Check ownership
$check = db()->prepare('SELECT p.id, b.user_id FROM promotions p JOIN businesses b ON p.business_id = b.id WHERE p.id = ?');
$check->execute([$id]);
$promo = $check->fetch();
if (!$promo) {
    json_response(404, ['error' => 'Promotion not found']);
}
if ($user['role'] !== 'admin' && (int)$promo['user_id'] !== (int)$user['user_id']) {
    json_response(403, ['error' => 'Forbidden']);
}

if (empty($_FILES['image'])) {
    json_response(400, ['error' => 'No file uploaded']);
}

$file = $_FILES['image'];
if ($file['error'] !== UPLOAD_ERR_OK) {
    json_response(400, ['error' => 'Upload failed: code ' . $file['error']]);
}

$allowedTypes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mime = finfo_file($finfo, $file['tmp_name']);
finfo_close($finfo);

if (!in_array($mime, $allowedTypes, true)) {
    json_response(400, ['error' => 'Invalid file type. Allowed: JPG, PNG, WebP, GIF']);
}

if ($file['size'] > 2 * 1024 * 1024) {
    json_response(400, ['error' => 'File too large. Max 2MB']);
}

$uploadDir = __DIR__ . '/../../uploads/promotions/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

$ext = match ($mime) {
    'image/jpeg' => 'jpg',
    'image/png' => 'png',
    'image/webp' => 'webp',
    'image/gif' => 'gif',
    default => 'jpg',
};

$filename = 'promo_' . $id . '_' . time() . '.' . $ext;
$destPath = $uploadDir . $filename;

if (!move_uploaded_file($file['tmp_name'], $destPath)) {
    json_response(500, ['error' => 'Failed to save file']);
}

$imageUrl = '/uploads/promotions/' . $filename;

$stmt = db()->prepare('UPDATE promotions SET image_url = ? WHERE id = ?');
$stmt->execute([$imageUrl, $id]);

json_response(200, ['message' => 'Image uploaded', 'image_url' => $imageUrl]);
