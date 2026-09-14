<?php
require_once __DIR__ . '/../../config.php';

$current = require_auth();

if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
    json_response(405, ['error' => 'Method not allowed']);
}

$id = (int) ($_GET['id'] ?? 0);
$input = json_decode(file_get_contents('php://input'), true);

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
    // admin can update any booking
} elseif ($current['role'] === 'local' && $current['user_id'] !== (int) $booking['user_id']) {
    // local business can update bookings for their own business
    $bStmt = db()->prepare('SELECT id FROM businesses WHERE id = ? AND user_id = ?');
    $bStmt->execute([$booking['business_id'], $current['user_id']]);
    if (!$bStmt->fetch()) {
        json_response(403, ['error' => 'Forbidden']);
    }
} elseif ($current['user_id'] !== (int) $booking['user_id']) {
    json_response(403, ['error' => 'Forbidden']);
}

// Tourists can only update date/notes; status is managed by business/admin
if ($current['role'] === 'tourist') {
    $allowed = ['booking_date', 'notes', 'destination_id'];
} else {
    $allowed = ['booking_date', 'status', 'total_price', 'notes', 'destination_id', 'business_id'];
}
$fields = [];
$values = [];

foreach ($allowed as $key) {
    if (isset($input[$key])) {
        $fields[] = "$key = ?";
        $values[] = $input[$key];
    }
}

if (empty($fields)) {
    json_response(400, ['error' => 'No fields to update']);
}

$values[] = $id;
$stmt = db()->prepare('UPDATE bookings SET ' . implode(', ', $fields) . ' WHERE id = ?');
$stmt->execute($values);

json_response(200, ['message' => 'Booking updated']);
