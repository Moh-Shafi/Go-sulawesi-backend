<?php
// Migration: video reports + following feed support
require_once __DIR__ . '/../config.php';

// 1. Video reports table
db()->exec("CREATE TABLE IF NOT EXISTS video_reports (
    id INT AUTO_INCREMENT PRIMARY KEY,
    video_id INT NOT NULL,
    reporter_id INT NOT NULL,
    reason VARCHAR(255) NOT NULL,
    status ENUM('pending','reviewed','dismissed') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_video (video_id),
    INDEX idx_reporter (reporter_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// 2. User follows table (tourist follows business/local)
db()->exec("CREATE TABLE IF NOT EXISTS user_follows (
    id INT AUTO_INCREMENT PRIMARY KEY,
    follower_id INT NOT NULL,
    following_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_follow (follower_id, following_id),
    INDEX idx_follower (follower_id),
    INDEX idx_following (following_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

echo "Migration complete: video_reports + user_follows tables created.\n";
