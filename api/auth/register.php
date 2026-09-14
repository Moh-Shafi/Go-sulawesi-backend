<?php
require_once __DIR__ . '/../../config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(405, ['error' => 'Method not allowed']);
}

$body = get_json_body();
$name = trim($body['name'] ?? '');
$email = trim($body['email'] ?? '');
$password = $body['password'] ?? '';
$role = $body['role'] ?? 'tourist'; // tourist | local

if (!$name || !$email || !$password) {
    json_response(400, ['error' => 'Name, email and password required']);
}

if (!in_array($role, ['tourist', 'local'])) {
    json_response(400, ['error' => 'Invalid role']);
}

// Check if email exists
$stmt = db()->prepare('SELECT id FROM users WHERE email = ?');
$stmt->execute([$email]);
if ($stmt->fetch()) {
    json_response(409, ['error' => 'Email already registered']);
}

// Create user
$hashed = password_hash($password, PASSWORD_BCRYPT);
$stmt = db()->prepare('INSERT INTO users (name, email, password, role, phone) VALUES (?, ?, ?, ?, ?)');
$stmt->execute([$name, $email, $hashed, $role, $body['phone'] ?? null]);
$user_id = (int) db()->lastInsertId();

// If local, create business record
if ($role === 'local') {
    $biz = $body['business'] ?? [];
    $bizName = trim($biz['businessName'] ?? '');
    $bizType = trim($biz['businessType'] ?? '');
    $city = trim($biz['city'] ?? '');
    $phone = trim($biz['phone'] ?? '');

    if (!$bizName || !$bizType || !$city || !$phone) {
        json_response(400, ['error' => 'Business details required for local role']);
    }

    $stmt = db()->prepare('INSERT INTO businesses (user_id, business_name, business_type, city, phone, description, nib) VALUES (?, ?, ?, ?, ?, ?, ?)');
    $stmt->execute([
        $user_id,
        $bizName,
        $bizType,
        $city,
        $phone,
        $biz['description'] ?? null,
        $biz['nib'] ?? null,
    ]);
}

$token = generate_token($user_id, $role);
set_auth_cookie($token);

// Fetch created user
$stmt = db()->prepare('SELECT id, name, email, role, avatar, phone FROM users WHERE id = ?');
$stmt->execute([$user_id]);
$user = $stmt->fetch();

json_response(201, [
    'message' => 'Registration successful',
    'token' => $token,
    'user' => $user,
]);
