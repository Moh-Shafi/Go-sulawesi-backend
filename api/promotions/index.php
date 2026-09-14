<?php
require_once __DIR__ . '/../../config.php';

$user = require_auth();
$method = $_SERVER['REQUEST_METHOD'];

// Auto-expire promotions past end_date
db()->exec("UPDATE promotions SET status = 'expired' WHERE status = 'approved' AND end_date < CURDATE()");

if ($method === 'GET') {
    if ($user['role'] === 'admin') {
        $stmt = db()->query('
            SELECT p.*, b.business_name, b.city, b.image_url AS business_image_url, b.business_type
            FROM promotions p
            JOIN businesses b ON p.business_id = b.id
            ORDER BY p.created_at DESC
        ');
    } elseif ($user['role'] === 'local') {
        $stmt = db()->prepare('
            SELECT p.*, b.business_name, b.city, b.image_url AS business_image_url, b.business_type
            FROM promotions p
            JOIN businesses b ON p.business_id = b.id
            WHERE b.user_id = ?
            ORDER BY p.created_at DESC
        ');
        $stmt->execute([$user['user_id']]);
    } else {
        // Tourist: only approved, not expired
        $stmt = db()->query('
            SELECT p.*, b.business_name, b.city, b.image_url AS business_image_url, b.business_type, b.rating, b.price
            FROM promotions p
            JOIN businesses b ON p.business_id = b.id
            WHERE p.status = "approved"
            ORDER BY p.created_at DESC
        ');
    }
    json_response(200, ['promotions' => $stmt->fetchAll()]);
}

if ($method === 'POST') {
    if ($user['role'] !== 'local' && $user['role'] !== 'admin') {
        json_response(403, ['error' => 'Only businesses can create promotions']);
    }

    $body = get_json_body();

    // Get business_id for this user
    $bizStmt = db()->prepare('SELECT id FROM businesses WHERE user_id = ?');
    $bizStmt->execute([$user['user_id']]);
    $biz = $bizStmt->fetch();

    if (!$biz && $user['role'] !== 'admin') {
        json_response(404, ['error' => 'No business found for this user']);
    }

    $businessId = $user['role'] === 'admin' ? (int)($body['business_id'] ?? 0) : (int)$biz['id'];

    $discountType = in_array($body['discount_type'] ?? '', ['percent', 'fixed']) ? $body['discount_type'] : 'percent';
    $discountValue = (float)($body['discount_value'] ?? 0);

    if ($discountType === 'percent' && ($discountValue < 1 || $discountValue > 100)) {
        json_response(400, ['error' => 'Percent discount must be between 1 and 100']);
    }

    if ($discountType === 'fixed' && $discountValue <= 0) {
        json_response(400, ['error' => 'Fixed discount must be greater than 0']);
    }

    $startDate = $body['start_date'] ?? date('Y-m-d');
    $endDate = $body['end_date'] ?? date('Y-m-d', strtotime('+7 days'));

    if (strtotime($endDate) < strtotime($startDate)) {
        json_response(400, ['error' => 'End date cannot be before start date']);
    }

    $stmt = db()->prepare('INSERT INTO promotions (business_id, title, description, discount_type, discount_value, original_price, start_date, end_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
    $stmt->execute([
        $businessId,
        trim($body['title'] ?? ''),
        $body['description'] ?? null,
        $discountType,
        $discountValue,
        isset($body['original_price']) ? (float)$body['original_price'] : null,
        $startDate,
        $endDate,
    ]);

    $id = (int) db()->lastInsertId();
    $stmt = db()->prepare('SELECT * FROM promotions WHERE id = ?');
    $stmt->execute([$id]);
    json_response(201, ['promotion' => $stmt->fetch()]);
}

json_response(405, ['error' => 'Method not allowed']);
