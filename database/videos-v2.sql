-- GoSulawesi Premium Video Feed Schema v2
-- Run via: php backend/database/migrate-videos-v2.php

-- Sound library for background music
CREATE TABLE IF NOT EXISTS video_sounds (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(120) NOT NULL,
    artist VARCHAR(120) NOT NULL,
    audio_url VARCHAR(255) NOT NULL,
    duration_sec INT DEFAULT 15,
    category VARCHAR(40) DEFAULT 'general',
    usage_count INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_sounds_category (category)
);

-- Video daily stats (one row per video per day)
CREATE TABLE IF NOT EXISTS video_daily_stats (
    id INT AUTO_INCREMENT PRIMARY KEY,
    video_id INT NOT NULL,
    stat_date DATE NOT NULL,
    views INT DEFAULT 0,
    likes INT DEFAULT 0,
    comments INT DEFAULT 0,
    saves INT DEFAULT 0,
    shares INT DEFAULT 0,
    FOREIGN KEY (video_id) REFERENCES videos(id) ON DELETE CASCADE,
    UNIQUE KEY unique_video_day (video_id, stat_date),
    INDEX idx_stats_date (stat_date)
);

-- Add sound_id to videos table
ALTER TABLE videos ADD COLUMN sound_id INT DEFAULT NULL;
ALTER TABLE videos ADD COLUMN shares INT DEFAULT 0;
ALTER TABLE videos ADD FOREIGN KEY (sound_id) REFERENCES video_sounds(id) ON DELETE SET NULL;

-- Seed default sounds
INSERT INTO video_sounds (title, artist, audio_url, duration_sec, category) VALUES
('Sulawesi Sunset', 'GoSulawesi Sounds', '/sounds/sulawesi-sunset.mp3', 30, 'chill'),
('Tropical Breeze', 'GoSulawesi Sounds', '/sounds/tropical-breeze.mp3', 25, 'chill'),
('Makassar Beat', 'GoSulawesi Sounds', '/sounds/makassar-beat.mp3', 20, 'upbeat'),
('Ocean Waves', 'GoSulawesi Sounds', '/sounds/ocean-waves.mp3', 30, 'nature'),
('Forest Morning', 'GoSulawesi Sounds', '/sounds/forest-morning.mp3', 25, 'nature'),
('Cultural Drums', 'GoSulawesi Sounds', '/sounds/cultural-drums.mp3', 20, 'traditional'),
('Adventure Pulse', 'GoSulawesi Sounds', '/sounds/adventure-pulse.mp3', 25, 'upbeat'),
('Serene Beach', 'GoSulawesi Sounds', '/sounds/serene-beach.mp3', 30, 'nature')
ON DUPLICATE KEY UPDATE title = title;
