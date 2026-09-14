<?php
require_once __DIR__ . '/../../config.php';

$current = require_auth();

if ($current['role'] !== 'admin') {
    json_response(403, ['error' => 'Forbidden']);
}

if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
    json_response(405, ['error' => 'Method not allowed']);
}

$id = (int) ($_GET['id'] ?? 0);

error_log("[users/delete] id=$id current_user=" . ($current['user_id'] ?? '-') . " role=" . ($current['role'] ?? '-'));

if (!$id) {
    error_log("[users/delete] Bad request: missing id");
    json_response(400, ['error' => 'Missing id']);
}

$stmt = db()->prepare('DELETE FROM users WHERE id = ?');
$stmt->execute([$id]);

if ($stmt->rowCount() === 0) {
    json_response(404, ['error' => 'User not found']);
}

json_response(200, ['message' => 'User deleted']);
