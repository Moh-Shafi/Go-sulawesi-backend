<?php
require_once __DIR__ . '/../../config.php';

$id = (int) ($_GET['id'] ?? 0);
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $stmt = db()->prepare('SELECT * FROM destinations WHERE id = ?');
    $stmt->execute([$id]);
    $dest = $stmt->fetch();
    if (!$dest) json_response(404, ['error' => 'Destination not found']);

    // Get reviews
    $stmt = db()->prepare('
        SELECT r.*, u.name AS user_name, u.avatar AS user_avatar
        FROM reviews r JOIN users u ON r.user_id = u.id
        WHERE r.destination_id = ?
        ORDER BY r.created_at DESC
    ');
    $stmt->execute([$id]);
    $dest['reviews'] = $stmt->fetchAll();

    json_response(200, ['destination' => $dest]);
}

if ($method === 'PUT') {
    $user = require_role('admin');
    $body = get_json_body();

    $stmt = db()->prepare('UPDATE destinations SET name=?, city=?, category=?, description=?, image_url=?, rating=?, price=?, latitude=?, longitude=? WHERE id=?');
    $stmt->execute([
        trim($body['name'] ?? ''),
        trim($body['city'] ?? ''),
        trim($body['category'] ?? ''),
        $body['description'] ?? null,
        $body['image_url'] ?? null,
        $body['rating'] ?? 0.0,
        $body['price'] ?? 0.00,
        $body['latitude'] ?? null,
        $body['longitude'] ?? null,
        $id,
    ]);
    $stmt = db()->prepare('SELECT * FROM destinations WHERE id = ?');
    $stmt->execute([$id]);
    json_response(200, ['destination' => $stmt->fetch()]);
}

if ($method === 'DELETE') {
    $user = require_role('admin');
    $stmt = db()->prepare('DELETE FROM destinations WHERE id = ?');
    $stmt->execute([$id]);
    json_response(200, ['message' => 'Deleted']);
}

json_response(405, ['error' => 'Method not allowed']);
