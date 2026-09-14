<?php
// GET    /api/videos/{id}  -> single video with counters
// DELETE /api/videos/{id}  -> owner (or admin) removes their video
require_once __DIR__ . '/../../config.php';

$user = require_auth();
$method = $_SERVER['REQUEST_METHOD'];

$id = (int) ($_GET['id'] ?? 0);
if (!$id) {
    json_response(400, ['error' => 'Missing video id']);
}

if ($method === 'GET') {
    $uid = (int) $user['user_id'];
    $stmt = db()->prepare('
        SELECT
            v.*, u.name AS user_name, u.avatar AS user_avatar, u.role AS user_role,
            b.business_name, d.name AS destination_name,
            s.title AS sound_title, s.artist AS sound_artist, s.audio_url AS sound_url,
            (SELECT COUNT(*) FROM video_likes WHERE video_id = v.id) AS likes,
            (SELECT COUNT(*) FROM video_comments WHERE video_id = v.id) AS comments,
            EXISTS(SELECT 1 FROM video_likes WHERE video_id = v.id AND user_id = ?) AS liked,
            EXISTS(SELECT 1 FROM video_saves WHERE video_id = v.id AND user_id = ?) AS is_saved
        FROM videos v
        JOIN users u ON v.user_id = u.id
        LEFT JOIN businesses b ON v.business_id = b.id
        LEFT JOIN destinations d ON v.destination_id = d.id
        LEFT JOIN video_sounds s ON v.sound_id = s.id
        WHERE v.id = ?
    ');
    $stmt->execute([$uid, $uid, $id]);
    $video = $stmt->fetch();

    if (!$video) {
        json_response(404, ['error' => 'Video not found']);
    }

    $video['likes'] = (int) $video['likes'];
    $video['comments'] = (int) $video['comments'];
    $video['liked'] = (bool) $video['liked'];
    $video['is_saved'] = (bool) $video['is_saved'];

    json_response(200, ['video' => $video]);
}

if ($method === 'DELETE') {
    $stmt = db()->prepare('SELECT user_id, video_url, thumbnail_url FROM videos WHERE id = ?');
    $stmt->execute([$id]);
    $video = $stmt->fetch();

    if (!$video) {
        json_response(404, ['error' => 'Video not found']);
    }
    if ($user['role'] !== 'admin' && (int) $video['user_id'] !== (int) $user['user_id']) {
        json_response(403, ['error' => 'Forbidden']);
    }

    foreach ([$video['video_url'], $video['thumbnail_url']] as $url) {
        if (!$url || !str_starts_with($url, '/uploads/videos/')) continue;
        $path = __DIR__ . '/../..' . $url;
        if (is_file($path)) @unlink($path);
    }

    $del = db()->prepare('DELETE FROM videos WHERE id = ?');
    $del->execute([$id]);

    json_response(200, ['message' => 'Video deleted']);
}

// POST /api/videos/{id}/view is handled here too via the ?view=1 flag
if ($method === 'POST' && !empty($_GET['view'])) {
    $stmt = db()->prepare('UPDATE videos SET views = views + 1 WHERE id = ?');
    $stmt->execute([$id]);

    // Track daily stat
    $today = date('Y-m-d');
    db()->prepare('
        INSERT INTO video_daily_stats (video_id, stat_date, views) VALUES (?, ?, 1)
        ON DUPLICATE KEY UPDATE views = views + 1
    ')->execute([$id, $today]);

    json_response(200, ['message' => 'View counted']);
}

// POST /api/videos/{id}?share=1 -> increment share count
if ($method === 'POST' && !empty($_GET['share'])) {
    $stmt = db()->prepare('UPDATE videos SET shares = shares + 1 WHERE id = ?');
    $stmt->execute([$id]);

    $today = date('Y-m-d');
    db()->prepare('
        INSERT INTO video_daily_stats (video_id, stat_date, shares) VALUES (?, ?, 1)
        ON DUPLICATE KEY UPDATE shares = shares + 1
    ')->execute([$id, $today]);

    json_response(200, ['message' => 'Share counted']);
}

json_response(405, ['error' => 'Method not allowed']);
