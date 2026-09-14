<?php
require_once __DIR__ . '/../../config.php';

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $user = require_auth();

    if ($user['role'] === 'admin') {
        $stmt = db()->query('
            SELECT b.*, u.name AS user_name, d.name AS destination_name, d.image_url AS destination_image, biz.business_name, biz.image_url AS business_image
            FROM bookings b
            LEFT JOIN users u ON b.user_id = u.id
            LEFT JOIN destinations d ON b.destination_id = d.id
            LEFT JOIN businesses biz ON b.business_id = biz.id
            ORDER BY b.created_at DESC
        ');
    } elseif ($user['role'] === 'local') {
        $stmt = db()->prepare('
            SELECT b.*, d.name AS destination_name, d.image_url AS destination_image, biz.business_name, biz.image_url AS business_image
            FROM bookings b
            LEFT JOIN destinations d ON b.destination_id = d.id
            LEFT JOIN businesses biz ON b.business_id = biz.id
            WHERE b.business_id IN (SELECT id FROM businesses WHERE user_id = ?)
            ORDER BY b.created_at DESC
        ');
        $stmt->execute([$user['user_id']]);
    } else {
        $stmt = db()->prepare('
            SELECT b.*, d.name AS destination_name, d.image_url AS destination_image, biz.business_name, biz.image_url AS business_image
            FROM bookings b
            LEFT JOIN destinations d ON b.destination_id = d.id
            LEFT JOIN businesses biz ON b.business_id = biz.id
            WHERE b.user_id = ?
            ORDER BY b.created_at DESC
        ');
        $stmt->execute([$user['user_id']]);
    }
    json_response(200, ['bookings' => $stmt->fetchAll()]);
}

if ($method === 'POST') {
    $user = require_auth();
    $body = get_json_body();

    $stmt = db()->prepare('INSERT INTO bookings (user_id, destination_id, business_id, booking_date, status, total_price, notes) VALUES (?, ?, ?, ?, ?, ?, ?)');
    $stmt->execute([
        $user['user_id'],
        $body['destination_id'] ?? null,
        $body['business_id'] ?? null,
        $body['booking_date'] ?? date('Y-m-d'),
        'pending', // always starts as pending — status is managed by business/admin
        $body['total_price'] ?? 0.00,
        $body['notes'] ?? null,
    ]);
    $id = (int) db()->lastInsertId();

    $stmt = db()->prepare('SELECT * FROM bookings WHERE id = ?');
    $stmt->execute([$id]);
    json_response(201, ['booking' => $stmt->fetch()]);
}

json_response(405, ['error' => 'Method not allowed']);
