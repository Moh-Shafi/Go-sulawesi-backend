<?php
require_once __DIR__ . '/../../config.php';

$user = require_auth();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(405, ['error' => 'Method not allowed']);
}

$id = (int) ($_GET['id'] ?? 0);
if (!$id) {
    json_response(400, ['error' => 'Missing user id']);
}

// Only admin or the user themselves can upload
if ($user['role'] !== 'admin' && (int)$user['user_id'] !== $id) {
    json_response(403, ['error' => 'Forbidden']);
}

if (empty($_FILES['avatar'])) {
    json_response(400, ['error' => 'No file uploaded']);
}

$file = $_FILES['avatar'];
if ($file['error'] !== UPLOAD_ERR_OK) {
    json_response(400, ['error' => 'Upload failed: code ' . $file['error']]);
}

// Validate type
$allowedTypes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mime = finfo_file($finfo, $file['tmp_name']);
finfo_close($finfo);

if (!in_array($mime, $allowedTypes, true)) {
    json_response(400, ['error' => 'Invalid file type. Allowed: JPG, PNG, WebP, GIF']);
}

// Max 2MB
if ($file['size'] > 2 * 1024 * 1024) {
    json_response(400, ['error' => 'File too large. Max 2MB']);
}

// Save to uploads directory
$uploadDir = __DIR__ . '/../../uploads/avatars/';
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

$filename = 'user_' . $id . '_' . time() . '.' . $ext;
$destPath = $uploadDir . $filename;

if (!move_uploaded_file($file['tmp_name'], $destPath)) {
    json_response(500, ['error' => 'Failed to save file']);
}

$avatarUrl = '/uploads/avatars/' . $filename;

// Update user's avatar in DB
$stmt = db()->prepare('UPDATE users SET avatar = ? WHERE id = ?');
$stmt->execute([$avatarUrl, $id]);

error_log("[users/avatar] Uploaded avatar for user $id: $avatarUrl");
json_response(200, ['message' => 'Avatar uploaded', 'avatar' => $avatarUrl]);
