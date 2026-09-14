<?php
require_once __DIR__ . '/../../config.php';

$user = require_auth();
$id = (int) ($_GET['id'] ?? 0);

if ($user['role'] !== 'admin' && $user['user_id'] !== $id) {
    json_response(403, ['error' => 'Forbidden']);
}

$stmt = db()->prepare('SELECT id, name, email, role, avatar, phone, created_at FROM users WHERE id = ?');
$stmt->execute([$id]);
$u = $stmt->fetch();

if (!$u) {
    json_response(404, ['error' => 'User not found']);
}

json_response(200, ['user' => $u]);
