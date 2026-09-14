<?php
require_once __DIR__ . '/../../config.php';

$method = $_SERVER['REQUEST_METHOD'];
$user = require_auth();

// GET /api/cancellations/policy?business_id=N  — get policy for a business
// POST /api/cancellations/policy               — create/update policy (local/admin)
// GET /api/cancellations/requests              — list requests (tourist: own, local: business, admin: all)
// POST /api/cancellations/requests             — tourist creates cancellation request
// PUT /api/cancellations/requests/:id          — business/admin approves/rejects

$path = $_GET['path'] ?? '';
$action = $_GET['action'] ?? '';

// Route based on query params
if (isset($_GET['policy'])) {
    // Policy CRUD
    if ($method === 'GET') {
        $businessId = (int)($_GET['business_id'] ?? 0);
        if (!$businessId) json_response(400, ['error' => 'Missing business_id']);

        $stmt = db()->prepare('SELECT * FROM cancellation_policies WHERE business_id = ?');
        $stmt->execute([$businessId]);
        $policy = $stmt->fetch();

        if (!$policy) {
            // Return default policy
            json_response(200, ['policy' => [
                'business_id' => $businessId,
                'deadline_hours' => 72,
                'refund_before_deadline' => 100,
                'refund_after_deadline' => 0,
                'requires_approval' => 1,
                'notes' => null,
                'is_default' => true,
            ]]);
        }
        json_response(200, ['policy' => $policy]);
    }

    if ($method === 'POST') {
        if (!in_array($user['role'], ['local', 'admin'])) {
            json_response(403, ['error' => 'Only businesses and admins can set policies']);
        }
        $body = get_json_body();
        $businessId = (int)($body['business_id'] ?? 0);
        if (!$businessId) json_response(400, ['error' => 'Missing business_id']);

        // Verify ownership for local role
        if ($user['role'] === 'local') {
            $stmt = db()->prepare('SELECT id FROM businesses WHERE id = ? AND user_id = ?');
            $stmt->execute([$businessId, $user['user_id']]);
            if (!$stmt->fetch()) json_response(403, ['error' => 'Not your business']);
        }

        $deadlineHours = max(1, (int)($body['deadline_hours'] ?? 72));
        $refundBefore = max(0, min(100, (int)($body['refund_before_deadline'] ?? 100)));
        $refundAfter = max(0, min(100, (int)($body['refund_after_deadline'] ?? 0)));
        $requiresApproval = (int)($body['requires_approval'] ?? 1) ? 1 : 0;
        $notes = trim($body['notes'] ?? '') ?: null;

        // Upsert
        $stmt = db()->prepare('
            INSERT INTO cancellation_policies (business_id, deadline_hours, refund_before_deadline, refund_after_deadline, requires_approval, notes)
            VALUES (?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
                deadline_hours = VALUES(deadline_hours),
                refund_before_deadline = VALUES(refund_before_deadline),
                refund_after_deadline = VALUES(refund_after_deadline),
                requires_approval = VALUES(requires_approval),
                notes = VALUES(notes)
        ');
        $stmt->execute([$businessId, $deadlineHours, $refundBefore, $refundAfter, $requiresApproval, $notes]);

        $stmt = db()->prepare('SELECT * FROM cancellation_policies WHERE business_id = ?');
        $stmt->execute([$businessId]);
        json_response(200, ['policy' => $stmt->fetch()]);
    }
    json_response(405, ['error' => 'Method not allowed']);
}

// Requests
if ($method === 'GET') {
    if ($user['role'] === 'admin') {
        $stmt = db()->query('
            SELECT cr.*, b.booking_date, b.total_price, b.status AS booking_status,
                   u.name AS user_name, biz.business_name, d.name AS destination_name
            FROM cancellation_requests cr
            LEFT JOIN bookings b ON cr.booking_id = b.id
            LEFT JOIN users u ON cr.user_id = u.id
            LEFT JOIN businesses biz ON b.business_id = biz.id
            LEFT JOIN destinations d ON b.destination_id = d.id
            ORDER BY cr.created_at DESC
        ');
    } elseif ($user['role'] === 'local') {
        $stmt = db()->prepare('
            SELECT cr.*, b.booking_date, b.total_price, b.status AS booking_status,
                   u.name AS user_name, biz.business_name, d.name AS destination_name
            FROM cancellation_requests cr
            LEFT JOIN bookings b ON cr.booking_id = b.id
            LEFT JOIN users u ON cr.user_id = u.id
            LEFT JOIN businesses biz ON b.business_id = biz.id
            LEFT JOIN destinations d ON b.destination_id = d.id
            WHERE b.business_id IN (SELECT id FROM businesses WHERE user_id = ?)
            ORDER BY cr.created_at DESC
        ');
        $stmt->execute([$user['user_id']]);
    } else {
        $stmt = db()->prepare('
            SELECT cr.*, b.booking_date, b.total_price, b.status AS booking_status,
                   biz.business_name, d.name AS destination_name
            FROM cancellation_requests cr
            LEFT JOIN bookings b ON cr.booking_id = b.id
            LEFT JOIN businesses biz ON b.business_id = biz.id
            LEFT JOIN destinations d ON b.destination_id = d.id
            WHERE cr.user_id = ?
            ORDER BY cr.created_at DESC
        ');
        $stmt->execute([$user['user_id']]);
    }
    json_response(200, ['requests' => $stmt->fetchAll()]);
}

if ($method === 'POST') {
    // Tourist creates a cancellation request
    $body = get_json_body();
    $bookingId = (int)($body['booking_id'] ?? 0);
    $reason = trim($body['reason'] ?? '');

    if (!$bookingId) json_response(400, ['error' => 'Missing booking_id']);

    // Get booking
    $stmt = db()->prepare('SELECT * FROM bookings WHERE id = ?');
    $stmt->execute([$bookingId]);
    $booking = $stmt->fetch();
    if (!$booking) json_response(404, ['error' => 'Booking not found']);

    // Only booking owner can request cancellation
    if ($user['role'] !== 'admin' && (int)$booking['user_id'] !== $user['user_id']) {
        json_response(403, ['error' => 'Not your booking']);
    }

    // Check if already cancelled or has pending request
    if ($booking['status'] === 'cancelled') {
        json_response(400, ['error' => 'Booking already cancelled']);
    }
    $stmt = db()->prepare('SELECT id FROM cancellation_requests WHERE booking_id = ? AND status = "pending"');
    $stmt->execute([$bookingId]);
    if ($stmt->fetch()) {
        json_response(400, ['error' => 'A pending cancellation request already exists']);
    }

    // Get policy
    $businessId = (int)$booking['business_id'];
    $refundPercent = 0;
    $status = 'pending';

    if ($businessId) {
        $stmt = db()->prepare('SELECT * FROM cancellation_policies WHERE business_id = ?');
        $stmt->execute([$businessId]);
        $policy = $stmt->fetch();

        if ($policy) {
            // Calculate hours until booking date
            $bookingDate = strtotime($booking['booking_date']);
            $now = time();
            $hoursUntil = max(0, ($bookingDate - $now) / 3600);

            if ($hoursUntil >= $policy['deadline_hours']) {
                $refundPercent = (int)$policy['refund_before_deadline'];
            } else {
                $refundPercent = (int)$policy['refund_after_deadline'];
            }

            // Auto-approve if no approval required
            if (!(int)$policy['requires_approval']) {
                $status = 'auto';
            }
        }
    }

    $refundAmount = round((float)$booking['total_price'] * $refundPercent / 100, 2);

    // Create request
    $stmt = db()->prepare('
        INSERT INTO cancellation_requests (booking_id, user_id, reason, status, refund_percent, refund_amount)
        VALUES (?, ?, ?, ?, ?, ?)
    ');
    $stmt->execute([$bookingId, $user['user_id'], $reason ?: null, $status, $refundPercent, $refundAmount]);
    $reqId = (int) db()->lastInsertId();

    // If auto-approved, update booking immediately
    if ($status === 'auto') {
        $stmt = db()->prepare("UPDATE bookings SET status = 'cancelled', refund_amount = ?, cancelled_at = NOW() WHERE id = ?");
        $stmt->execute([$refundAmount, $bookingId]);
    }

    $stmt = db()->prepare('SELECT * FROM cancellation_requests WHERE id = ?');
    $stmt->execute([$reqId]);
    json_response(201, ['request' => $stmt->fetch()]);
}

json_response(405, ['error' => 'Method not allowed']);
