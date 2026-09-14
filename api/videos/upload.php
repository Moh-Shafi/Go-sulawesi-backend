<?php
// POST /api/videos/upload  (multipart: video, optional thumbnail)
// Stores the file and returns its public URL. The caller then POSTs /api/videos
// with that URL to create the feed entry.
require_once __DIR__ . '/../../config.php';

$user = require_auth();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(405, ['error' => 'Method not allowed']);
}

if (empty($_FILES['video'])) {
    // An empty $_FILES with a non-empty POST usually means the file exceeded
    // post_max_size, which PHP silently discards.
    $limit = ini_get('post_max_size');
    $uploadLimit = ini_get('upload_max_filesize');
    $contentLen = $_SERVER['CONTENT_LENGTH'] ?? 'unknown';
    $postKeys = array_keys($_POST);
    $fileKeys = array_keys($_FILES);
    error_log("[videos/upload] Empty \$_FILES. post_max_size=$limit upload_max_filesize=$uploadLimit content_length=$contentLen POST keys=[" . implode(',', $postKeys) . "] FILES keys=[" . implode(',', $fileKeys) . "]");
    json_response(400, ['error' => "No video received. PHP limits: upload=$uploadLimit, post=$limit. Content-Length=$contentLen. If the video is larger than post_max_size, PHP silently discards it."]);
}

$file = $_FILES['video'];
if ($file['error'] !== UPLOAD_ERR_OK) {
    $messages = [
        UPLOAD_ERR_INI_SIZE => 'File larger than upload_max_filesize (' . ini_get('upload_max_filesize') . ')',
        UPLOAD_ERR_FORM_SIZE => 'File larger than the form limit',
        UPLOAD_ERR_PARTIAL => 'Upload was interrupted, please retry',
        UPLOAD_ERR_NO_FILE => 'No file was uploaded',
        UPLOAD_ERR_NO_TMP_DIR => 'Server is missing a temp directory',
        UPLOAD_ERR_CANT_WRITE => 'Server could not write the file to disk',
    ];
    error_log("[videos/upload] Upload error code: " . $file['error'] . " size=" . ($file['size'] ?? 'unknown'));
    json_response(400, ['error' => $messages[$file['error']] ?? 'Upload failed: code ' . $file['error']]);
}

$allowed = [
    'video/mp4' => 'mp4',
    'video/quicktime' => 'mov',
    'video/webm' => 'webm',
];

$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mime = finfo_file($finfo, $file['tmp_name']);
finfo_close($finfo);

if (!isset($allowed[$mime])) {
    json_response(400, ['error' => 'Invalid file type. Allowed: MP4, MOV, WebM']);
}

$maxBytes = 25 * 1024 * 1024;
if ($file['size'] > $maxBytes) {
    json_response(400, ['error' => 'Video too large. Max 25MB']);
}

$uploadDir = __DIR__ . '/../../uploads/videos/';
if (!is_dir($uploadDir) && !mkdir($uploadDir, 0777, true)) {
    json_response(500, ['error' => 'Could not create upload directory']);
}

$base = 'vid_' . (int) $user['user_id'] . '_' . time() . '_' . bin2hex(random_bytes(4));
$filename = $base . '.' . $allowed[$mime];

if (!move_uploaded_file($file['tmp_name'], $uploadDir . $filename)) {
    json_response(500, ['error' => 'Failed to save video']);
}

$response = ['message' => 'Video uploaded', 'video_url' => '/uploads/videos/' . $filename];

// Optional client-generated poster frame (a JPEG/PNG from a <canvas>).
if (!empty($_FILES['thumbnail']) && $_FILES['thumbnail']['error'] === UPLOAD_ERR_OK) {
    $thumbAllowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $thumbMime = finfo_file($finfo, $_FILES['thumbnail']['tmp_name']);
    finfo_close($finfo);

    if (isset($thumbAllowed[$thumbMime]) && $_FILES['thumbnail']['size'] <= 2 * 1024 * 1024) {
        $thumbName = $base . '.' . $thumbAllowed[$thumbMime];
        if (move_uploaded_file($_FILES['thumbnail']['tmp_name'], $uploadDir . $thumbName)) {
            $response['thumbnail_url'] = '/uploads/videos/' . $thumbName;
        }
    }
}

json_response(200, $response);
