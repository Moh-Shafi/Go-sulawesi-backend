<?php
require_once __DIR__ . '/../../config.php';

$user = require_auth();

// Admin: list all users, others: list own
if ($user['role'] === 'admin') {
    $stmt = db()->query('SELECT id, name, email, role, avatar, phone, created_at FROM users ORDER BY created_at DESC');
    $users = $stmt->fetchAll();
} else {
    $stmt = db()->prepare('SELECT id, name, email, role, avatar, phone, created_at FROM users WHERE id = ?');
    $stmt->execute([$user['user_id']]);
    $users = $stmt->fetchAll();
}

json_response(200, ['users' => $users]);
