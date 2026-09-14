<?php
require_once __DIR__ . '/../../config.php';

$current = require_auth();

if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
    json_response(405, ['error' => 'Method not allowed']);
}

$id = (int) ($_GET['id'] ?? 0);
$input = json_decode(file_get_contents('php://input'), true);

error_log("[users/update] id=$id current_user=" . ($current['user_id'] ?? '-') . " role=" . ($current['role'] ?? '-') . " keys=" . implode(',', array_keys($input ?: [])));

if (!$id || empty($input)) {
    error_log("[users/update] Bad request: id=$id empty=" . (empty($input) ? 'yes' : 'no'));
    json_response(400, ['error' => 'Missing id or body']);
}

$isAdmin = $current['role'] === 'admin';
$isSelf = (int) $current['user_id'] === $id;

if (!$isAdmin && !$isSelf) {
    json_response(403, ['error' => 'Forbidden']);
}

$allowed = $isAdmin ? ['name', 'email', 'role', 'phone', 'avatar'] : ['name', 'email', 'phone', 'avatar'];
$fields = [];
$values = [];

foreach ($allowed as $key) {
    if (isset($input[$key])) {
        $fields[] = "$key = ?";
        $values[] = $input[$key];
    }
}

if (!empty($input['password'])) {
    if (!$isAdmin) {
        $stmt = db()->prepare('SELECT password FROM users WHERE id = ?');
        $stmt->execute([$id]);
        $user = $stmt->fetch();
        if (!$user || empty($input['current_password']) || !password_verify($input['current_password'], $user['password'])) {
            json_response(403, ['error' => 'Current password is incorrect']);
        }
    }
    $fields[] = "password = ?";
    $values[] = password_hash($input['password'], PASSWORD_DEFAULT);
}

if (empty($fields)) {
    json_response(400, ['error' => 'No fields to update']);
}

$values[] = $id;
$sql = 'UPDATE users SET ' . implode(', ', $fields) . ' WHERE id = ?';
error_log("[users/update] SQL=$sql values=" . implode(',', $values));
$stmt = db()->prepare($sql);
$stmt->execute($values);

if ($stmt->rowCount() === 0) {
    error_log("[users/update] User not found: id=$id");
    json_response(404, ['error' => 'User not found']);
}

error_log("[users/update] Success: id=$id updated");
json_response(200, ['message' => 'User updated']);
