<?php
require_once __DIR__ . '/../config.php';
$updates = [
    'Sulawesi Sunset' => '/sounds/sulawesi-sunset.mp3',
    'Tropical Breeze' => '/sounds/tropical-breeze.mp3',
    'Makassar Beat' => '/sounds/makassar-beat.mp3',
    'Ocean Waves' => '/sounds/ocean-waves.mp3',
    'Forest Morning' => '/sounds/forest-morning.mp3',
    'Cultural Drums' => '/sounds/cultural-drums.mp3',
    'Adventure Pulse' => '/sounds/adventure-pulse.mp3',
    'Serene Beach' => '/sounds/serene-beach.mp3',
];
foreach ($updates as $title => $url) {
    $stmt = db()->prepare('UPDATE video_sounds SET audio_url = ? WHERE title = ?');
    $stmt->execute([$url, $title]);
    echo "OK $title -> $url\n";
}
echo "Done.\n";
