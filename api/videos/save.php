<?php
// POST /api/videos/{id}/save -> toggles the bookmark for the current user
require_once __DIR__ . '/../../config.php';

$user = require_auth();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(405, ['error' => 'Method not allowed']);
}

$id = (int) ($_GET['id'] ?? 0);
if (!$id) {
    json_response(400, ['error' => 'Missing video id']);
}

$check = db()->prepare('SELECT id FROM videos WHERE id = ?');
$check->execute([$id]);
if (!$check->fetch()) {
    json_response(404, ['error' => 'Video not found']);
}

$uid = (int) $user['user_id'];

$existing = db()->prepare('SELECT id FROM video_saves WHERE video_id = ? AND user_id = ?');
$existing->execute([$id, $uid]);

if ($existing->fetch()) {
    $del = db()->prepare('DELETE FROM video_saves WHERE video_id = ? AND user_id = ?');
    $del->execute([$id, $uid]);
    $isSaved = false;
} else {
    $ins = db()->prepare('INSERT INTO video_saves (video_id, user_id) VALUES (?, ?)');
    $ins->execute([$id, $uid]);
    $isSaved = true;
}

json_response(200, ['is_saved' => $isSaved]);
