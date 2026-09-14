<?php
require_once __DIR__ . '/../../config.php';

$user = require_auth();
$id = (int) ($_GET['id'] ?? 0);
$method = $_SERVER['REQUEST_METHOD'];

// Verify conversation access
function getConversation($userId, $role, $convId) {
    if ($role === 'tourist') {
        $stmt = db()->prepare('SELECT c.*, b.user_id AS business_user_id, b.business_name, b.image_url AS business_image FROM chat_conversations c JOIN businesses b ON c.business_id = b.id WHERE c.id = ? AND c.tourist_id = ?');
        $stmt->execute([$convId, $userId]);
    } elseif ($role === 'local') {
        $stmt = db()->prepare('SELECT c.*, b.user_id AS business_user_id, b.business_name, b.image_url AS business_image, u.name AS tourist_name FROM chat_conversations c JOIN businesses b ON c.business_id = b.id JOIN users u ON c.tourist_id = u.id WHERE c.id = ? AND b.user_id = ?');
        $stmt->execute([$convId, $userId]);
    } else {
        $stmt = db()->prepare('SELECT c.*, b.user_id AS business_user_id, b.business_name, b.image_url AS business_image, u.name AS tourist_name FROM chat_conversations c JOIN businesses b ON c.business_id = b.id JOIN users u ON c.tourist_id = u.id WHERE c.id = ?');
        $stmt->execute([$convId]);
    }
    return $stmt->fetch();
}

if ($method === 'GET') {
    $conv = getConversation($user['user_id'], $user['role'], $id);
    if (!$conv) json_response(404, ['error' => 'Conversation not found']);

    // Mark messages from other party as read
    db()->prepare('UPDATE chat_messages SET is_read = 1 WHERE conversation_id = ? AND sender_id != ?')
        ->execute([$id, $user['user_id']]);

    $stmt = db()->prepare('SELECT * FROM chat_messages WHERE conversation_id = ? ORDER BY created_at ASC');
    $stmt->execute([$id]);
    json_response(200, ['conversation' => $conv, 'messages' => $stmt->fetchAll()]);
}

if ($method === 'POST') {
    $conv = getConversation($user['user_id'], $user['role'], $id);
    if (!$conv) json_response(404, ['error' => 'Conversation not found']);
    if ($conv['status'] === 'closed') {
        json_response(400, ['error' => 'Conversation is closed']);
    }

    $body = get_json_body();
    $text = trim($body['message_text'] ?? '');
    if ($text === '') {
        json_response(400, ['error' => 'Message cannot be empty']);
    }

    $stmt = db()->prepare('INSERT INTO chat_messages (conversation_id, sender_id, sender_role, message_text) VALUES (?, ?, ?, ?)');
    $stmt->execute([$id, $user['user_id'], $user['role'], $text]);

    $msgId = (int) db()->lastInsertId();
    $stmt = db()->prepare('SELECT * FROM chat_messages WHERE id = ?');
    $stmt->execute([$msgId]);

    // Update conversation timestamp
    db()->prepare('UPDATE chat_conversations SET updated_at = CURRENT_TIMESTAMP WHERE id = ?')->execute([$id]);

    json_response(201, ['message' => $stmt->fetch()]);
}

if ($method === 'DELETE') {
    $body = get_json_body();
    $msgId = (int)($body['message_id'] ?? 0);
    if (!$msgId) json_response(400, ['error' => 'Missing message_id']);

    // Verify message exists and belongs to this user
    $stmt = db()->prepare('SELECT * FROM chat_messages WHERE id = ?');
    $stmt->execute([$msgId]);
    $msg = $stmt->fetch();
    if (!$msg) json_response(404, ['error' => 'Message not found']);

    // Verify conversation access
    $conv = getConversation($user['user_id'], $user['role'], (int)$msg['conversation_id']);
    if (!$conv) json_response(404, ['error' => 'Conversation not found']);

    // Only the sender can delete their own message
    if ((int)$msg['sender_id'] !== (int)$user['user_id']) {
        json_response(403, ['error' => 'You can only delete your own messages']);
    }

    db()->prepare('DELETE FROM chat_messages WHERE id = ?')->execute([$msgId]);
    json_response(200, ['message' => 'Message deleted']);
}

if ($method === 'PUT') {
    $body = get_json_body();

    // Close conversation
    if (isset($body['status']) && $body['status'] === 'closed') {
        $conv = getConversation($user['user_id'], $user['role'], $id);
        if (!$conv) json_response(404, ['error' => 'Conversation not found']);
        db()->prepare('UPDATE chat_conversations SET status = ? WHERE id = ?')->execute(['closed', $id]);
        json_response(200, ['message' => 'Conversation closed']);
    }

    json_response(400, ['error' => 'Invalid update']);
}

json_response(405, ['error' => 'Method not allowed']);
