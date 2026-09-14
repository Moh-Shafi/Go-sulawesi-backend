<?php
require_once __DIR__ . '/../../config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
    json_response(405, ['error' => 'Method not allowed']);
}

$user = require_auth();
$id = (int)($_GET['id'] ?? 0);
if (!$id) json_response(400, ['error' => 'Missing id']);

$body = get_json_body();
$action = $body['action'] ?? ''; // 'approve' or 'reject'
$handlerNotes = trim($body['notes'] ?? '');

// Get request
$stmt = db()->prepare('
    SELECT cr.*, b.business_id, b.user_id AS booking_user_id, b.total_price, b.booking_date
    FROM cancellation_requests cr
    LEFT JOIN bookings b ON cr.booking_id = b.id
    WHERE cr.id = ?
');
$stmt->execute([$id]);
$req = $stmt->fetch();
if (!$req) json_response(404, ['error' => 'Request not found']);

if ($req['status'] !== 'pending') {
    json_response(400, ['error' => 'Request already handled']);
}

// Permission: admin or business owner
if ($user['role'] !== 'admin') {
    if ($user['role'] !== 'local') {
        json_response(403, ['error' => 'Only businesses and admins can handle requests']);
    }
    $stmt = db()->prepare('SELECT id FROM businesses WHERE id = ? AND user_id = ?');
    $stmt->execute([$req['business_id'], $user['user_id']]);
    if (!$stmt->fetch()) json_response(403, ['error' => 'Not your business']);
}

if ($action === 'approve') {
    // Recalculate refund based on current time
    $refundPercent = (int)$req['refund_percent'];
    $refundAmount = (float)$req['refund_amount'];

    // Re-check policy at approval time
    $stmt = db()->prepare('SELECT * FROM cancellation_policies WHERE business_id = ?');
    $stmt->execute([$req['business_id']]);
    $policy = $stmt->fetch();
    if ($policy) {
        $bookingDate = strtotime($req['booking_date']);
        $hoursUntil = max(0, ($bookingDate - time()) / 3600);
        if ($hoursUntil >= $policy['deadline_hours']) {
            $refundPercent = (int)$policy['refund_before_deadline'];
        } else {
            $refundPercent = (int)$policy['refund_after_deadline'];
        }
        $refundAmount = round((float)$req['total_price'] * $refundPercent / 100, 2);
    }

    $stmt = db()->prepare('
        UPDATE cancellation_requests
        SET status = "approved", handled_by = ?, handler_notes = ?, handled_at = NOW(),
            refund_percent = ?, refund_amount = ?
        WHERE id = ?
    ');
    $stmt->execute([$user['user_id'], $handlerNotes ?: null, $refundPercent, $refundAmount, $id]);

    // Update booking
    $stmt = db()->prepare("UPDATE bookings SET status = 'cancelled', refund_amount = ?, cancelled_at = NOW() WHERE id = ?");
    $stmt->execute([$refundAmount, $req['booking_id']]);

    json_response(200, ['message' => 'Cancellation approved', 'refund_percent' => $refundPercent, 'refund_amount' => $refundAmount]);

} elseif ($action === 'reject') {
    $stmt = db()->prepare('
        UPDATE cancellation_requests
        SET status = "rejected", handled_by = ?, handler_notes = ?, handled_at = NOW()
        WHERE id = ?
    ');
    $stmt->execute([$user['user_id'], $handlerNotes ?: null, $id]);

    json_response(200, ['message' => 'Cancellation rejected']);

} else {
    json_response(400, ['error' => 'Invalid action, use "approve" or "reject"']);
}
