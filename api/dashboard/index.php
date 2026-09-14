<?php
require_once __DIR__ . '/../../config.php';

$user = require_auth();
$role = $user['role'];

$data = ['role' => $role];

if ($role === 'admin') {
    $data['total_users'] = (int) db()->query('SELECT COUNT(*) FROM users')->fetchColumn();
    $data['total_tourists'] = (int) db()->query("SELECT COUNT(*) FROM users WHERE role='tourist'")->fetchColumn();
    $data['total_locals'] = (int) db()->query("SELECT COUNT(*) FROM users WHERE role='local'")->fetchColumn();
    $data['total_businesses'] = (int) db()->query('SELECT COUNT(*) FROM businesses')->fetchColumn();
    $data['pending_businesses'] = (int) db()->query("SELECT COUNT(*) FROM businesses WHERE status='pending'")->fetchColumn();
    $data['total_destinations'] = (int) db()->query('SELECT COUNT(*) FROM destinations')->fetchColumn();
    $data['total_bookings'] = (int) db()->query('SELECT COUNT(*) FROM bookings')->fetchColumn();
    $data['total_reviews'] = (int) db()->query('SELECT COUNT(*) FROM reviews')->fetchColumn();

    $data['recent_users'] = db()->query('SELECT id, name, email, role, created_at FROM users ORDER BY created_at DESC LIMIT 5')->fetchAll();
    $data['recent_bookings'] = db()->query('
        SELECT b.*, u.name AS user_name, d.name AS destination_name
        FROM bookings b LEFT JOIN users u ON b.user_id = u.id
        LEFT JOIN destinations d ON b.destination_id = d.id
        ORDER BY b.created_at DESC LIMIT 5
    ')->fetchAll();

} elseif ($role === 'tourist') {
    $stmt = db()->prepare('SELECT COUNT(*) FROM bookings WHERE user_id = ?');
    $stmt->execute([$user['user_id']]);
    $data['total_bookings'] = (int) $stmt->fetchColumn();

    $stmt = db()->prepare('SELECT COUNT(*) FROM reviews WHERE user_id = ?');
    $stmt->execute([$user['user_id']]);
    $data['total_reviews'] = (int) $stmt->fetchColumn();

    $data['destinations'] = db()->query('SELECT id, name, city, category, image_url, rating, price FROM destinations ORDER BY rating DESC LIMIT 6')->fetchAll();

    $stmt = db()->prepare('
        SELECT b.*, d.name AS destination_name, d.image_url
        FROM bookings b LEFT JOIN destinations d ON b.destination_id = d.id
        WHERE b.user_id = ? ORDER BY b.created_at DESC LIMIT 5
    ');
    $stmt->execute([$user['user_id']]);
    $data['recent_bookings'] = $stmt->fetchAll();

} elseif ($role === 'local') {
    $stmt = db()->prepare('SELECT * FROM businesses WHERE user_id = ?');
    $stmt->execute([$user['user_id']]);
    $data['businesses'] = $stmt->fetchAll();

    $stmt = db()->prepare('
        SELECT b.*, d.name AS destination_name, u.name AS tourist_name
        FROM bookings b
        LEFT JOIN destinations d ON b.destination_id = d.id
        LEFT JOIN users u ON b.user_id = u.id
        WHERE b.business_id IN (SELECT id FROM businesses WHERE user_id = ?)
        ORDER BY b.created_at DESC LIMIT 5
    ');
    $stmt->execute([$user['user_id']]);
    $data['recent_bookings'] = $stmt->fetchAll();
}

json_response(200, $data);
