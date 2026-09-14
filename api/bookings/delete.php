<?php
require_once __DIR__ . '/../../config.php';

$current = require_auth();

if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
    json_response(405, ['error' => 'Method not allowed']);
}

$id = (int) ($_GET['id'] ?? 0);

if (!$id) {
    json_response(400, ['error' => 'Missing id']);
}

$stmt = db()->prepare('SELECT * FROM bookings WHERE id = ?');
$stmt->execute([$id]);
$booking = $stmt->fetch();

if (!$booking) {
    json_response(404, ['error' => 'Booking not found']);
}

if ($current['role'] === 'admin') {
    // admin can delete any booking
} elseif ($current['role'] === 'local' && $current['user_id'] !== (int) $booking['user_id']) {
    // local business can delete bookings for their own business
    $bStmt = db()->prepare('SELECT id FROM businesses WHERE id = ? AND user_id = ?');
    $bStmt->execute([$booking['business_id'], $current['user_id']]);
    if (!$bStmt->fetch()) {
        json_response(403, ['error' => 'Forbidden']);
    }
} elseif ($current['user_id'] !== (int) $booking['user_id']) {
    json_response(403, ['error' => 'Forbidden']);
}

$stmt = db()->prepare('DELETE FROM bookings WHERE id = ?');
$stmt->execute([$id]);

json_response(200, ['message' => 'Booking deleted']);
