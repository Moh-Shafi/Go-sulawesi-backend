<?php
require_once __DIR__ . '/../../config.php';

$user = require_auth();

$stmt = db()->prepare('SELECT id, name, email, role, avatar, phone, created_at FROM users WHERE id = ?');
$stmt->execute([$user['user_id']]);
$u = $stmt->fetch();

if (!$u) {
    json_response(404, ['error' => 'User not found']);
}

json_response(200, ['user' => $u]);
