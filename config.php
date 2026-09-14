<?php
// Load local/production environment overrides if available
// Create this file from .env.example.php on your server
if (file_exists(__DIR__ . '/.env.php')) {
    require_once __DIR__ . '/.env.php';
}

// Database configuration
// Priority: 1) environment variables, 2) .env.php constants, 3) local Docker defaults
define('DB_HOST', getenv('DB_HOST') ?: (defined('ENV_DB_HOST') ? ENV_DB_HOST : 'db'));
define('DB_NAME', getenv('DB_NAME') ?: (defined('ENV_DB_NAME') ? ENV_DB_NAME : 'gosulawesi'));
define('DB_USER', getenv('DB_USER') ?: (defined('ENV_DB_USER') ? ENV_DB_USER : 'gosulawesi_user'));
define('DB_PASS', getenv('DB_PASS') ?: (defined('ENV_DB_PASS') ? ENV_DB_PASS : 'gosulawesi_pass'));

// JWT-like simple token secret
// Priority: 1) environment variable, 2) .env.php constant, 3) stable dev fallback
if (getenv('TOKEN_SECRET')) {
    define('TOKEN_SECRET', getenv('TOKEN_SECRET'));
} elseif (defined('ENV_TOKEN_SECRET')) {
    define('TOKEN_SECRET', ENV_TOKEN_SECRET);
} else {
    // Dev-only fallback — stable across requests within the same container.
    // Production MUST set TOKEN_SECRET via environment variable or .env.php
    $devSecretFile = __DIR__ . '/.dev_token_secret';
    if (file_exists($devSecretFile)) {
        define('TOKEN_SECRET', trim(file_get_contents($devSecretFile)));
    } else {
        $secret = bin2hex(random_bytes(32));
        file_put_contents($devSecretFile, $secret);
        define('TOKEN_SECRET', $secret);
    }
}

// CORS headers — restrict to allowed origins in production
$allowedOrigins = [
    'http://localhost:5173',
    'http://127.0.0.1:5173',
];
$envOrigin = getenv('CORS_ALLOWED_ORIGIN');
if ($envOrigin) {
    $allowedOrigins[] = $envOrigin;
}
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if (in_array($origin, $allowedOrigins, true)) {
    header('Access-Control-Allow-Origin: ' . $origin);
    header('Access-Control-Allow-Credentials: true');
} elseif (defined('ENV_CORS_ALLOWED_ORIGIN') && ENV_CORS_ALLOWED_ORIGIN === $origin) {
    header('Access-Control-Allow-Origin: ' . $origin);
    header('Access-Control-Allow-Credentials: true');
}
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json; charset=utf-8');

// Handle preflight
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Database connection
function db() {
    static $pdo = null;
    if ($pdo === null) {
        try {
            $pdo = new PDO(
                "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
                DB_USER,
                DB_PASS,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]
            );
        } catch (PDOException $e) {
            error_log('[DB] Connection failed: ' . $e->getMessage());
            json_response(500, ['error' => 'Database connection failed']);
        }
    }
    return $pdo;
}

// JSON response helper
function json_response($code, $data) {
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

// Get JSON body
function get_json_body() {
    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true);
    return $data ?: [];
}

// Simple token generate
function generate_token($user_id, $role) {
    $payload = base64_encode(json_encode([
        'user_id' => $user_id,
        'role' => $role,
        'exp' => time() + (60 * 60 * 24 * 7), // 7 days
    ]));
    $signature = hash_hmac('sha256', $payload, TOKEN_SECRET);
    return $payload . '.' . $signature;
}

// Set the auth token as an httpOnly cookie (XSS-resistant)
function set_auth_cookie($token) {
    $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
    setcookie('gosulawesi_token', $token, [
        'expires'  => time() + (60 * 60 * 24 * 7),
        'path'     => '/',
        'httponly' => true,
        'secure'   => $secure,
        'samesite' => 'Lax',
    ]);
}

// Clear the auth cookie
function clear_auth_cookie() {
    setcookie('gosulawesi_token', '', [
        'expires'  => time() - 3600,
        'path'     => '/',
        'httponly' => true,
    ]);
}

// Verify token — reads from httpOnly cookie first, falls back to Authorization header
function verify_token() {
    // 1. Try httpOnly cookie (preferred — XSS-resistant)
    $token = $_COOKIE['gosulawesi_token'] ?? '';

    // 2. Fall back to Authorization header (for API clients / backward compat)
    if (!$token) {
        $headers = getallheaders();
        $auth = $headers['Authorization'] ?? $headers['authorization'] ?? $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '';
        if ($auth && str_starts_with($auth, 'Bearer ')) {
            $token = substr($auth, 7);
        }
    }

    if (!$token) return null;
    $parts = explode('.', $token);
    if (count($parts) !== 2) return null;
    [$payload, $signature] = $parts;
    $expected = hash_hmac('sha256', $payload, TOKEN_SECRET);
    if (!hash_equals($expected, $signature)) return null;
    $data = json_decode(base64_decode($payload), true);
    if (!$data || ($data['exp'] ?? 0) < time()) return null;
    return $data;
}

// Require auth
function require_auth() {
    $user = verify_token();
    if (!$user) {
        json_response(401, ['error' => 'Unauthorized']);
    }
    return $user;
}

// Require specific role
function require_role($role) {
    $user = require_auth();
    if ($user['role'] !== $role && $user['role'] !== 'admin') {
        json_response(403, ['error' => 'Forbidden']);
    }
    return $user;
}
