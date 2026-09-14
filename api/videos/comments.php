<?php
// GET  /api/videos/{id}/comments -> list comments for a video
// POST /api/videos/{id}/comments -> add a comment
require_once __DIR__ . '/../../config.php';

$user = require_auth();
$method = $_SERVER['REQUEST_METHOD'];

$id = (int) ($_GET['id'] ?? 0);
if (!$id) {
    json_response(400, ['error' => 'Missing video id']);
}

$check = db()->prepare('SELECT id FROM videos WHERE id = ?');
$check->execute([$id]);
if (!$check->fetch()) {
    json_response(404, ['error' => 'Video not found']);
}

if ($method === 'GET') {
    $stmt = db()->prepare('
        SELECT c.id, c.comment_text, c.created_at, u.id AS user_id, u.name AS user_name, u.avatar AS user_avatar
        FROM video_comments c
        JOIN users u ON c.user_id = u.id
        WHERE c.video_id = ?
        ORDER BY c.created_at DESC
        LIMIT 100
    ');
    $stmt->execute([$id]);
    json_response(200, ['comments' => $stmt->fetchAll()]);
}

if ($method === 'POST') {
    $body = get_json_body();
    $text = mb_substr(trim($body['comment_text'] ?? ''), 0, 500);

    if ($text === '') {
        json_response(400, ['error' => 'Comment cannot be empty']);
    }

    $stmt = db()->prepare('INSERT INTO video_comments (video_id, user_id, comment_text) VALUES (?, ?, ?)');
    $stmt->execute([$id, $user['user_id'], $text]);

    // Track daily stat
    $today = date('Y-m-d');
    db()->prepare('INSERT INTO video_daily_stats (video_id, stat_date, comments) VALUES (?, ?, 1) ON DUPLICATE KEY UPDATE comments = comments + 1')->execute([$id, $today]);

    $newId = (int) db()->lastInsertId();
    $stmt = db()->prepare('
        SELECT c.id, c.comment_text, c.created_at, u.id AS user_id, u.name AS user_name, u.avatar AS user_avatar
        FROM video_comments c
        JOIN users u ON c.user_id = u.id
        WHERE c.id = ?
    ');
    $stmt->execute([$newId]);
    json_response(201, ['comment' => $stmt->fetch()]);
}

if ($method === 'DELETE') {
    $commentId = (int) ($_GET['comment_id'] ?? 0);
    if (!$commentId) {
        json_response(400, ['error' => 'Missing comment_id']);
    }

    $stmt = db()->prepare('SELECT user_id FROM video_comments WHERE id = ? AND video_id = ?');
    $stmt->execute([$commentId, $id]);
    $comment = $stmt->fetch();

    if (!$comment) {
        json_response(404, ['error' => 'Comment not found']);
    }
    if ($user['role'] !== 'admin' && (int) $comment['user_id'] !== (int) $user['user_id']) {
        json_response(403, ['error' => 'Forbidden']);
    }

    $del = db()->prepare('DELETE FROM video_comments WHERE id = ?');
    $del->execute([$commentId]);
    json_response(200, ['message' => 'Comment deleted']);
}

json_response(405, ['error' => 'Method not allowed']);
