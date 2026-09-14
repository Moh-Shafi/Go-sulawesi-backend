<?php
require_once __DIR__ . '/../../config.php';

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $city = $_GET['city'] ?? null;
    $category = $_GET['category'] ?? null;
    $search = $_GET['search'] ?? null;

    $sql = 'SELECT * FROM destinations WHERE 1=1';
    $params = [];

    if ($city) {
        $sql .= ' AND city = ?';
        $params[] = $city;
    }
    if ($category) {
        $sql .= ' AND category = ?';
        $params[] = $category;
    }
    if ($search) {
        $sql .= ' AND (name LIKE ? OR description LIKE ?)';
        $params[] = "%$search%";
        $params[] = "%$search%";
    }

    $sql .= ' ORDER BY rating DESC';
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    json_response(200, ['destinations' => $stmt->fetchAll()]);
}

if ($method === 'POST') {
    $user = require_role('admin');
    $body = get_json_body();

    $stmt = db()->prepare('INSERT INTO destinations (name, city, category, description, image_url, rating, price, latitude, longitude) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
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
    ]);
    $id = (int) db()->lastInsertId();

    $stmt = db()->prepare('SELECT * FROM destinations WHERE id = ?');
    $stmt->execute([$id]);
    json_response(201, ['destination' => $stmt->fetch()]);
}

json_response(405, ['error' => 'Method not allowed']);
