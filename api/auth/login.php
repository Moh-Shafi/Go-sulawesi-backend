<?php
require_once __DIR__ . '/../../config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(405, ['error' => 'Method not allowed']);
}

$body = get_json_body();
$email = trim($body['email'] ?? '');
$password = $body['password'] ?? '';

if (!$email || !$password) {
    json_response(400, ['error' => 'Email and password required']);
}

// Rate limiting: max 5 failed attempts per email per 15 minutes
$ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
$rateKey = 'login_fail_' . md5($email . $ip);
$rateFile = sys_get_temp_dir() . '/gosulawesi_rate_' . $rateKey;
if (file_exists($rateFile)) {
    $data = json_decode(file_get_contents($rateFile), true);
    if ($data && $data['expires'] > time() && $data['count'] >= 5) {
        $waitMin = ceil(($data['expires'] - time()) / 60);
        json_response(429, ['error' => "Too many failed attempts. Try again in {$waitMin} minutes."]);
    }
}

$stmt = db()->prepare('SELECT * FROM users WHERE email = ?');
$stmt->execute([$email]);
$user = $stmt->fetch();

if (!$user || !password_verify($password, $user['password'])) {
    // Record failed attempt
    $count = 1;
    $expires = time() + 900; // 15 minutes
    if (file_exists($rateFile)) {
        $data = json_decode(file_get_contents($rateFile), true);
        if ($data && $data['expires'] > time()) {
            $count = $data['count'] + 1;
            $expires = $data['expires'];
        }
    }
    file_put_contents($rateFile, json_encode(['count' => $count, 'expires' => $expires]));

    json_response(401, ['error' => 'Invalid email or password']);
}

// Clear rate limit on successful login
if (file_exists($rateFile)) @unlink($rateFile);

$token = generate_token($user['id'], $user['role']);
set_auth_cookie($token);

json_response(200, [
    'message' => 'Login successful',
    'token' => $token,
    'user' => [
        'id' => $user['id'],
        'name' => $user['name'],
        'email' => $user['email'],
        'role' => $user['role'],
        'avatar' => $user['avatar'],
    ],
]);
