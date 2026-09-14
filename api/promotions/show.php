<?php
require_once __DIR__ . '/../../config.php';

$user = require_auth();
$id = (int) ($_GET['id'] ?? 0);
$method = $_SERVER['REQUEST_METHOD'];

// Auto-expire
db()->exec("UPDATE promotions SET status = 'expired' WHERE status = 'approved' AND end_date < CURDATE()");

if ($method === 'GET') {
    $stmt = db()->prepare('
        SELECT p.*, b.business_name, b.city, b.image_url AS business_image_url, b.business_type, b.rating, b.price
        FROM promotions p
        JOIN businesses b ON p.business_id = b.id
        WHERE p.id = ?
    ');
    $stmt->execute([$id]);
    $promo = $stmt->fetch();
    if (!$promo) json_response(404, ['error' => 'Promotion not found']);
    json_response(200, ['promotion' => $promo]);
}

if ($method === 'PUT') {
    $body = get_json_body();

    // Fetch promotion to check ownership
    $check = db()->prepare('SELECT p.*, b.user_id FROM promotions p JOIN businesses b ON p.business_id = b.id WHERE p.id = ?');
    $check->execute([$id]);
    $promo = $check->fetch();
    if (!$promo) json_response(404, ['error' => 'Promotion not found']);

    $isOwner = (int)$promo['user_id'] === (int)$user['user_id'];
    $isAdmin = $user['role'] === 'admin';

    if (!$isAdmin && !$isOwner) {
        json_response(403, ['error' => 'Not authorized']);
    }

    // Status-only update (approve/reject from admin)
    if (array_key_exists('status', $body) && count($body) === 1) {
        if ($user['role'] !== 'admin') {
            json_response(403, ['error' => 'Only admin can change status']);
        }
        $validStatus = in_array($body['status'], ['pending', 'approved', 'rejected', 'expired']) ? $body['status'] : 'pending';
        $stmt = db()->prepare('UPDATE promotions SET status = ? WHERE id = ?');
        $stmt->execute([$validStatus, $id]);
    }
    // Full update
    elseif (array_key_exists('title', $body)) {
        $discountType = in_array($body['discount_type'] ?? '', ['percent', 'fixed']) ? $body['discount_type'] : 'percent';
        $discountValue = (float)($body['discount_value'] ?? 0);

        if ($discountType === 'percent' && ($discountValue < 1 || $discountValue > 100)) {
            json_response(400, ['error' => 'Percent discount must be between 1 and 100']);
        }

        $startDate = $body['start_date'] ?? date('Y-m-d');
        $endDate = $body['end_date'] ?? date('Y-m-d', strtotime('+7 days'));

        if (strtotime($endDate) < strtotime($startDate)) {
            json_response(400, ['error' => 'End date cannot be before start date']);
        }

        $stmt = db()->prepare('UPDATE promotions SET title=?, description=?, discount_type=?, discount_value=?, original_price=?, start_date=?, end_date=? WHERE id = ?');
        $stmt->execute([
            trim($body['title'] ?? ''),
            $body['description'] ?? null,
            $discountType,
            $discountValue,
            isset($body['original_price']) ? (float)$body['original_price'] : null,
            $startDate,
            $endDate,
            $id,
        ]);
    }

    $stmt = db()->prepare('SELECT * FROM promotions WHERE id = ?');
    $stmt->execute([$id]);
    json_response(200, ['promotion' => $stmt->fetch()]);
}

if ($method === 'DELETE') {
    $check = db()->prepare('SELECT p.id, b.user_id FROM promotions p JOIN businesses b ON p.business_id = b.id WHERE p.id = ?');
    $check->execute([$id]);
    $promo = $check->fetch();
    if (!$promo) json_response(404, ['error' => 'Promotion not found']);

    $isOwner = (int)$promo['user_id'] === (int)$user['user_id'];
    $isAdmin = $user['role'] === 'admin';

    if (!$isAdmin && !$isOwner) {
        json_response(403, ['error' => 'Not authorized']);
    }

    $stmt = db()->prepare('DELETE FROM promotions WHERE id = ?');
    $stmt->execute([$id]);
    json_response(200, ['message' => 'Deleted']);
}

json_response(405, ['error' => 'Method not allowed']);
