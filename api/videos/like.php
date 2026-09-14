<?php
// POST /api/videos/{id}/like -> toggles the like for the current user
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

$existing = db()->prepare('SELECT id FROM video_likes WHERE video_id = ? AND user_id = ?');
$existing->execute([$id, $uid]);

if ($existing->fetch()) {
    $del = db()->prepare('DELETE FROM video_likes WHERE video_id = ? AND user_id = ?');
    $del->execute([$id, $uid]);
    $liked = false;
} else {
    $ins = db()->prepare('INSERT INTO video_likes (video_id, user_id) VALUES (?, ?)');
    $ins->execute([$id, $uid]);
    $liked = true;

    // Track daily stat
    $today = date('Y-m-d');
    db()->prepare('INSERT INTO video_daily_stats (video_id, stat_date, likes) VALUES (?, ?, 1) ON DUPLICATE KEY UPDATE likes = likes + 1')->execute([$id, $today]);
}

$count = db()->prepare('SELECT COUNT(*) FROM video_likes WHERE video_id = ?');
$count->execute([$id]);

json_response(200, ['liked' => $liked, 'likes' => (int) $count->fetchColumn()]);
