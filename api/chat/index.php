<?php
require_once __DIR__ . '/../../config.php';

$user = require_auth();
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    // List conversations based on role
    if ($user['role'] === 'tourist') {
        $stmt = db()->prepare('
            SELECT c.*, b.business_name, b.image_url AS business_image, b.city,
                   u.name AS tourist_name,
                   (SELECT COUNT(*) FROM chat_messages m WHERE m.conversation_id = c.id AND m.sender_id != ? AND m.is_read = 0) AS unread_count,
                   (SELECT message_text FROM chat_messages m WHERE m.conversation_id = c.id ORDER BY m.created_at DESC LIMIT 1) AS last_message,
                   (SELECT created_at FROM chat_messages m WHERE m.conversation_id = c.id ORDER BY m.created_at DESC LIMIT 1) AS last_message_time
            FROM chat_conversations c
            JOIN businesses b ON c.business_id = b.id
            JOIN users u ON c.tourist_id = u.id
            WHERE c.tourist_id = ?
            ORDER BY last_message_time DESC
        ');
        $stmt->execute([$user['user_id'], $user['user_id']]);
    } elseif ($user['role'] === 'local') {
        $stmt = db()->prepare('
            SELECT c.*, b.business_name, b.image_url AS business_image, b.city,
                   u.name AS tourist_name, u.id AS tourist_user_id,
                   (SELECT COUNT(*) FROM chat_messages m WHERE m.conversation_id = c.id AND m.sender_id != ? AND m.is_read = 0) AS unread_count,
                   (SELECT message_text FROM chat_messages m WHERE m.conversation_id = c.id ORDER BY m.created_at DESC LIMIT 1) AS last_message,
                   (SELECT created_at FROM chat_messages m WHERE m.conversation_id = c.id ORDER BY m.created_at DESC LIMIT 1) AS last_message_time
            FROM chat_conversations c
            JOIN businesses b ON c.business_id = b.id
            JOIN users u ON c.tourist_id = u.id
            WHERE b.user_id = ?
            ORDER BY last_message_time DESC
        ');
        $stmt->execute([$user['user_id'], $user['user_id']]);
    } else {
        $stmt = db()->query('
            SELECT c.*, b.business_name, b.image_url AS business_image, b.city,
                   u.name AS tourist_name,
                   (SELECT COUNT(*) FROM chat_messages m WHERE m.conversation_id = c.id AND m.is_read = 0) AS unread_count,
                   (SELECT message_text FROM chat_messages m WHERE m.conversation_id = c.id ORDER BY m.created_at DESC LIMIT 1) AS last_message,
                   (SELECT created_at FROM chat_messages m WHERE m.conversation_id = c.id ORDER BY m.created_at DESC LIMIT 1) AS last_message_time
            FROM chat_conversations c
            JOIN businesses b ON c.business_id = b.id
            JOIN users u ON c.tourist_id = u.id
            ORDER BY last_message_time DESC
        ');
    }
    json_response(200, ['conversations' => $stmt->fetchAll()]);
}

if ($method === 'POST') {
    $body = get_json_body();
    $businessId = (int)($body['business_id'] ?? 0);

    if (!$businessId) {
        json_response(400, ['error' => 'Missing business_id']);
    }

    if ($user['role'] !== 'tourist') {
        json_response(403, ['error' => 'Only tourists can start conversations']);
    }

    // Check if conversation already exists
    $check = db()->prepare('SELECT id FROM chat_conversations WHERE tourist_id = ? AND business_id = ?');
    $check->execute([$user['user_id'], $businessId]);
    $existing = $check->fetch();

    if ($existing) {
        json_response(200, ['conversation' => $existing]);
    }

    $stmt = db()->prepare('INSERT INTO chat_conversations (tourist_id, business_id) VALUES (?, ?)');
    $stmt->execute([$user['user_id'], $businessId]);

    $id = (int) db()->lastInsertId();
    $stmt = db()->prepare('SELECT * FROM chat_conversations WHERE id = ?');
    $stmt->execute([$id]);
    json_response(201, ['conversation' => $stmt->fetch()]);
}

json_response(405, ['error' => 'Method not allowed']);
