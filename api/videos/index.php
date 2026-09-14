<?php
// GET  /api/videos            -> feed (active videos, newest first)
// GET  /api/videos?mine=1     -> only the current user's videos
// GET  /api/videos?saved=1    -> only videos the current user saved
// POST /api/videos            -> create a video row (video_url comes from /api/videos/upload)
require_once __DIR__ . '/../../config.php';

$user = require_auth();
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $uid = (int) $user['user_id'];

    // Placeholders must be bound in the order they appear in the final SQL:
    // 1) liked  2) is_saved  3) saved-join (optional)  4) mine-filter (optional)
    $params = [$uid, $uid];

    $join = '';
    if (!empty($_GET['saved'])) {
        $join = 'JOIN video_saves vs ON vs.video_id = v.id AND vs.user_id = ?';
        $params[] = $uid;
    }

    $where = ["v.status = 'active'"];
    if (!empty($_GET['mine'])) {
        $where[] = 'v.user_id = ?';
        $params[] = $uid;
    }
    if (!empty($_GET['following'])) {
        $join .= ' JOIN user_follows uf ON uf.following_id = v.user_id AND uf.follower_id = ?';
        $params[] = $uid;
    }
    if (!empty($_GET['sound_id'])) {
        $where[] = 'v.sound_id = ?';
        $params[] = (int) $_GET['sound_id'];
    }

    $sql = "
        SELECT
            v.id, v.video_url, v.thumbnail_url, v.caption, v.city,
            v.business_id, v.destination_id, v.duration_sec, v.views, v.shares, v.created_at,
            u.id AS user_id, u.name AS user_name, u.avatar AS user_avatar, u.role AS user_role,
            b.business_name, b.business_type,
            d.name AS destination_name,
            s.title AS sound_title, s.artist AS sound_artist, s.audio_url AS sound_url,
            (SELECT COUNT(*) FROM video_likes WHERE video_id = v.id) AS likes,
            (SELECT COUNT(*) FROM video_comments WHERE video_id = v.id) AS comments,
            EXISTS(SELECT 1 FROM video_likes WHERE video_id = v.id AND user_id = ?) AS liked,
            EXISTS(SELECT 1 FROM video_saves WHERE video_id = v.id AND user_id = ?) AS is_saved
        FROM videos v
        JOIN users u ON v.user_id = u.id
        $join
        LEFT JOIN businesses b ON v.business_id = b.id
        LEFT JOIN destinations d ON v.destination_id = d.id
        LEFT JOIN video_sounds s ON v.sound_id = s.id
        WHERE " . implode(' AND ', $where) . "
        ORDER BY v.created_at DESC
        LIMIT 60
    ";

    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    $videos = $stmt->fetchAll();

    foreach ($videos as &$v) {
        $v['likes'] = (int) $v['likes'];
        $v['comments'] = (int) $v['comments'];
        $v['views'] = (int) $v['views'];
        $v['liked'] = (bool) $v['liked'];
        $v['is_saved'] = (bool) $v['is_saved'];
        $v['shares'] = (int) ($v['shares'] ?? 0);
    }

    json_response(200, ['videos' => $videos]);
}

if ($method === 'POST') {
    $body = get_json_body();

    $videoUrl = trim($body['video_url'] ?? '');
    if ($videoUrl === '') {
        json_response(400, ['error' => 'video_url is required']);
    }

    $caption = mb_substr(trim($body['caption'] ?? ''), 0, 500);
    $soundId = !empty($body['sound_id']) ? (int) $body['sound_id'] : null;
    $businessId = !empty($body['business_id']) ? (int) $body['business_id'] : null;
    $destinationId = !empty($body['destination_id']) ? (int) $body['destination_id'] : null;
    $city = !empty($body['city']) ? mb_substr(trim($body['city']), 0, 80) : null;
    $thumb = !empty($body['thumbnail_url']) ? trim($body['thumbnail_url']) : null;
    $duration = (int) ($body['duration_sec'] ?? 0);

    if ($businessId) {
        $check = db()->prepare('SELECT city FROM businesses WHERE id = ?');
        $check->execute([$businessId]);
        $biz = $check->fetch();
        if (!$biz) json_response(400, ['error' => 'Tagged business not found']);
        if (!$city) $city = $biz['city'];
    }

    if ($destinationId) {
        $check = db()->prepare('SELECT city FROM destinations WHERE id = ?');
        $check->execute([$destinationId]);
        $dest = $check->fetch();
        if (!$dest) json_response(400, ['error' => 'Tagged destination not found']);
        if (!$city) $city = $dest['city'];
    }

    $stmt = db()->prepare('
        INSERT INTO videos (user_id, video_url, thumbnail_url, caption, business_id, destination_id, city, duration_sec, sound_id)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ');
    $stmt->execute([
        $user['user_id'], $videoUrl, $thumb, $caption ?: null,
        $businessId, $destinationId, $city, $duration, $soundId,
    ]);

    // Increment sound usage count
    if ($soundId) {
        db()->prepare('UPDATE video_sounds SET usage_count = usage_count + 1 WHERE id = ?')->execute([$soundId]);
    }

    $id = (int) db()->lastInsertId();
    $stmt = db()->prepare('
        SELECT v.*, u.name AS user_name, u.avatar AS user_avatar, b.business_name
        FROM videos v
        JOIN users u ON v.user_id = u.id
        LEFT JOIN businesses b ON v.business_id = b.id
        WHERE v.id = ?
    ');
    $stmt->execute([$id]);
    $video = $stmt->fetch();
    $video['likes'] = 0;
    $video['comments'] = 0;
    $video['liked'] = false;
    $video['is_saved'] = false;

    json_response(201, ['video' => $video]);
}

json_response(405, ['error' => 'Method not allowed']);
