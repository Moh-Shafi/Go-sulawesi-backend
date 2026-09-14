<?php
// GET  /api/videos/sounds  -> list available sounds
require_once __DIR__ . '/../../config.php';

$user = require_auth();

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    json_response(405, ['error' => 'Method not allowed']);
}

$stmt = db()->prepare('SELECT id, title, artist, audio_url, duration_sec, category, usage_count FROM video_sounds ORDER BY usage_count DESC, id ASC');
$stmt->execute();
$sounds = $stmt->fetchAll();

json_response(200, ['sounds' => $sounds]);
