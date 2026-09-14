<?php
require_once __DIR__ . '/../../config.php';

$user = require_auth();
$id = (int) ($_GET['id'] ?? 0);
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $stmt = db()->prepare('
        SELECT b.*, u.name AS owner_name
        FROM businesses b JOIN users u ON b.user_id = u.id
        WHERE b.id = ?
    ');
    $stmt->execute([$id]);
    $biz = $stmt->fetch();
    if (!$biz) json_response(404, ['error' => 'Business not found']);
    // Only admin sees owner email
    if ($user['role'] !== 'admin') {
        unset($biz['owner_email']);
    }
    json_response(200, ['business' => $biz]);
}

if ($method === 'PUT') {
    $body = get_json_body();

    // Status-only update (approve/reject from admin panel) — admin only
    if (array_key_exists('status', $body) && count($body) === 1) {
        if ($user['role'] !== 'admin') {
            json_response(403, ['error' => 'Only admins can change business status']);
        }
        $validStatus = in_array($body['status'], ['pending', 'approved', 'rejected']) ? $body['status'] : 'pending';
        $stmt = db()->prepare('UPDATE businesses SET status = ? WHERE id = ?');
        $stmt->execute([$validStatus, $id]);
    }
    // Full business update (from edit form)
    elseif (array_key_exists('business_name', $body)) {
        if ($user['role'] === 'admin') {
            $stmt = db()->prepare('UPDATE businesses SET business_name=?, business_type=?, city=?, phone=?, description=?, price=?, business_hours=?, nib=?, status=? WHERE id = ?');
            $stmt->execute([
                trim($body['business_name'] ?? ''),
                trim($body['business_type'] ?? ''),
                trim($body['city'] ?? ''),
                trim($body['phone'] ?? ''),
                $body['description'] ?? null,
                (float)($body['price'] ?? 0),
                array_key_exists('business_hours', $body) ? json_encode($body['business_hours']) : null,
                $body['nib'] ?? null,
                $body['status'] ?? 'pending',
                $id,
            ]);
        } else {
            // Verify ownership for non-admin
            $check = db()->prepare('SELECT user_id FROM businesses WHERE id = ?');
            $check->execute([$id]);
            $biz = $check->fetch();
            if (!$biz || (int)$biz['user_id'] !== (int)$user['user_id']) {
                json_response(403, ['error' => 'Not authorized to edit this business']);
            }
            $stmt = db()->prepare('UPDATE businesses SET business_name=?, business_type=?, city=?, phone=?, description=?, price=?, business_hours=?, nib=? WHERE id=? AND user_id=?');
            $stmt->execute([
                trim($body['business_name'] ?? ''),
                trim($body['business_type'] ?? ''),
                trim($body['city'] ?? ''),
                trim($body['phone'] ?? ''),
                $body['description'] ?? null,
                (float)($body['price'] ?? 0),
                array_key_exists('business_hours', $body) ? json_encode($body['business_hours']) : null,
                $body['nib'] ?? null,
                $id,
                $user['user_id'],
            ]);
        }
    }
    // Status update with other fields — admin only for status
    elseif (array_key_exists('status', $body)) {
        if ($user['role'] !== 'admin') {
            json_response(403, ['error' => 'Only admins can change business status']);
        }
        $validStatus = in_array($body['status'], ['pending', 'approved', 'rejected']) ? $body['status'] : 'pending';
        $stmt = db()->prepare('UPDATE businesses SET status = ? WHERE id = ?');
        $stmt->execute([$validStatus, $id]);
    }
    $stmt = db()->prepare('SELECT * FROM businesses WHERE id = ?');
    $stmt->execute([$id]);
    json_response(200, ['business' => $stmt->fetch()]);
}

if ($method === 'DELETE') {
    $check = db()->prepare('SELECT id, user_id FROM businesses WHERE id = ?');
    $check->execute([$id]);
    $biz = $check->fetch();
    if (!$biz) {
        json_response(404, ['error' => 'Business not found']);
    }
    if ($user['role'] !== 'admin' && (int)$biz['user_id'] !== (int)$user['user_id']) {
        json_response(403, ['error' => 'Not authorized to delete this business']);
    }
    $stmt = db()->prepare('DELETE FROM businesses WHERE id = ?');
    $stmt->execute([$id]);
    json_response(200, ['message' => 'Deleted']);
}

json_response(405, ['error' => 'Method not allowed']);
