<?php
// POST /api/videos/report -> report a video
require_once __DIR__ . '/../../config.php';

$user = require_auth();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(405, ['error' => 'Method not allowed']);
}

$body = get_json_body();
$videoId = (int) ($body['video_id'] ?? 0);
$reason = mb_substr(trim($body['reason'] ?? ''), 0, 255);

if (!$videoId) {
    json_response(400, ['error' => 'video_id is required']);
}
if ($reason === '') {
    json_response(400, ['error' => 'reason is required']);
}

// Check video exists
$check = db()->prepare('SELECT id FROM videos WHERE id = ? AND status = "active"');
$check->execute([$videoId]);
if (!$check->fetch()) {
    json_response(404, ['error' => 'Video not found']);
}

// Check if already reported by this user
$dup = db()->prepare('SELECT id FROM video_reports WHERE video_id = ? AND reporter_id = ? AND status = "pending"');
$dup->execute([$videoId, $user['user_id']]);
if ($dup->fetch()) {
    json_response(409, ['error' => 'You have already reported this video']);
}

$stmt = db()->prepare('INSERT INTO video_reports (video_id, reporter_id, reason) VALUES (?, ?, ?)');
$stmt->execute([$videoId, $user['user_id'], $reason]);

json_response(201, ['message' => 'Report submitted']);
