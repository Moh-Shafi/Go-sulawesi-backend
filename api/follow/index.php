<?php
// POST /api/follow   -> follow a user
// POST /api/follow?action=unfollow -> unfollow
// GET  /api/follow?following_id=N -> check if following
// GET  /api/follow?follower_id=N  -> list who a user follows
require_once __DIR__ . '/../../config.php';

$user = require_auth();
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'POST') {
    $body = get_json_body();
    $followingId = (int) ($body['following_id'] ?? 0);
    $action = $_GET['action'] ?? '';

    if (!$followingId) {
        json_response(400, ['error' => 'following_id is required']);
    }
    if ($followingId === (int) $user['user_id']) {
        json_response(400, ['error' => 'Cannot follow yourself']);
    }

    // Check target user exists
    $check = db()->prepare('SELECT id, role FROM users WHERE id = ?');
    $check->execute([$followingId]);
    if (!$check->fetch()) {
        json_response(404, ['error' => 'User not found']);
    }

    if ($action === 'unfollow') {
        $stmt = db()->prepare('DELETE FROM user_follows WHERE follower_id = ? AND following_id = ?');
        $stmt->execute([$user['user_id'], $followingId]);
        json_response(200, ['following' => false]);
    } else {
        // Follow (insert ignore to avoid duplicates)
        $stmt = db()->prepare('INSERT IGNORE INTO user_follows (follower_id, following_id) VALUES (?, ?)');
        $stmt->execute([$user['user_id'], $followingId]);
        json_response(201, ['following' => true]);
    }
}

if ($method === 'GET') {
    $followingId = (int) ($_GET['following_id'] ?? 0);
    $followerId = (int) ($_GET['follower_id'] ?? 0);

    if ($followingId) {
        // Check if current user follows this user
        $stmt = db()->prepare('SELECT 1 FROM user_follows WHERE follower_id = ? AND following_id = ?');
        $stmt->execute([$user['user_id'], $followingId]);
        json_response(200, ['following' => (bool) $stmt->fetch()]);
    }

    if ($followerId) {
        // Only the user themselves or admin can see who they follow
        if ($followerId !== (int) $user['user_id'] && $user['role'] !== 'admin') {
            json_response(403, ['error' => 'You can only view your own following list']);
        }
        // List who this user follows
        $stmt = db()->prepare('
            SELECT u.id, u.name, u.avatar, u.role, b.business_name
            FROM user_follows f
            JOIN users u ON f.following_id = u.id
            LEFT JOIN businesses b ON u.id = b.user_id
            WHERE f.follower_id = ?
            ORDER BY f.created_at DESC
        ');
        $stmt->execute([$followerId]);
        json_response(200, ['following' => $stmt->fetchAll()]);
    }

    json_response(400, ['error' => 'Provide following_id or follower_id']);
}

json_response(405, ['error' => 'Method not allowed']);
