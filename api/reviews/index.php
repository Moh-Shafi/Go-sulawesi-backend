<?php
require_once __DIR__ . '/../../config.php';

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $destination_id = $_GET['destination_id'] ?? null;
    $business_id = $_GET['business_id'] ?? null;

    $sql = 'SELECT r.*, u.name AS user_name, u.avatar AS user_avatar, d.name AS destination_name, d.image_url AS destination_image, biz.business_name, biz.image_url AS business_image FROM reviews r JOIN users u ON r.user_id = u.id LEFT JOIN destinations d ON r.destination_id = d.id LEFT JOIN businesses biz ON r.business_id = biz.id WHERE 1=1';
    $params = [];

    if ($destination_id) {
        $sql .= ' AND r.destination_id = ?';
        $params[] = $destination_id;
    }
    if ($business_id) {
        $sql .= ' AND r.business_id = ?';
        $params[] = $business_id;
    }
    $sql .= ' ORDER BY r.created_at DESC';

    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    json_response(200, ['reviews' => $stmt->fetchAll()]);
}

if ($method === 'POST') {
    $user = require_auth();
    $body = get_json_body();

    $rating = (int) ($body['rating'] ?? 0);
    if ($rating < 1 || $rating > 5) {
        json_response(400, ['error' => 'Rating must be 1-5']);
    }

    $destinationId = $body['destination_id'] ?? null;
    $businessId = $body['business_id'] ?? null;

    // Verify the user has a completed booking for this destination or business
    // before allowing them to post a review (prevents fake reviews)
    if ($businessId) {
        $bookingCheck = db()->prepare("SELECT id FROM bookings WHERE user_id = ? AND business_id = ? AND status = 'completed'");
        $bookingCheck->execute([$user['user_id'], $businessId]);
    } elseif ($destinationId) {
        $bookingCheck = db()->prepare("SELECT id FROM bookings WHERE user_id = ? AND destination_id = ? AND status = 'completed'");
        $bookingCheck->execute([$user['user_id'], $destinationId]);
    } else {
        json_response(400, ['error' => 'Must specify business_id or destination_id']);
    }

    if (!$bookingCheck->fetch()) {
        json_response(403, ['error' => 'You can only review places you have completed a booking with']);
    }

    $stmt = db()->prepare('INSERT INTO reviews (user_id, destination_id, business_id, rating, comment) VALUES (?, ?, ?, ?, ?)');
    $stmt->execute([
        $user['user_id'],
        $destinationId,
        $businessId,
        $rating,
        $body['comment'] ?? null,
    ]);
    $id = (int) db()->lastInsertId();

    $stmt = db()->prepare('SELECT r.*, u.name AS user_name FROM reviews r JOIN users u ON r.user_id = u.id WHERE r.id = ?');
    $stmt->execute([$id]);
    json_response(201, ['review' => $stmt->fetch()]);
}

json_response(405, ['error' => 'Method not allowed']);
