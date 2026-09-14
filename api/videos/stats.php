<?php
// GET /api/videos/stats?video_id=N  -> daily stats for a video (owner only)
// GET /api/videos/stats?mine=1      -> aggregate stats for all of the user's videos
require_once __DIR__ . '/../../config.php';

$user = require_auth();

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    json_response(405, ['error' => 'Method not allowed']);
}

$uid = (int) $user['user_id'];

if (!empty($_GET['video_id'])) {
    $vid = (int) $_GET['video_id'];

    // Verify ownership
    $check = db()->prepare('SELECT id, views, likes, comments, shares FROM videos WHERE id = ? AND user_id = ?');
    $check->execute([$vid, $uid]);
    $video = $check->fetch();
    if (!$video) {
        json_response(404, ['error' => 'Video not found or not owned by you']);
    }

    // Get daily stats for last 30 days
    $stmt = db()->prepare('
        SELECT stat_date, views, likes, comments, saves, shares
        FROM video_daily_stats
        WHERE video_id = ?
        ORDER BY stat_date ASC
        LIMIT 30
    ');
    $stmt->execute([$vid]);
    $daily = $stmt->fetchAll();

    json_response(200, [
        'video_id' => $vid,
        'totals' => [
            'views' => (int) $video['views'],
            'likes' => (int) $video['likes'],
            'comments' => (int) $video['comments'],
            'shares' => (int) $video['shares'],
        ],
        'daily' => $daily,
    ]);
}

// Aggregate stats for all of the user's videos
if (isset($_GET['mine'])) {
    $stmt = db()->prepare('
        SELECT
            COUNT(*) AS total_videos,
            COALESCE(SUM(views), 0) AS total_views,
            COALESCE(SUM(likes), 0) AS total_likes,
            COALESCE(SUM(comments), 0) AS total_comments,
            COALESCE(SUM(shares), 0) AS total_shares
        FROM videos WHERE user_id = ? AND status = \'active\'
    ');
    $stmt->execute([$uid]);
    $agg = $stmt->fetch();

    // Top performing videos
    $stmt2 = db()->prepare('
        SELECT id, caption, thumbnail_url, views, likes, comments, shares, created_at
        FROM videos WHERE user_id = ? AND status = \'active\'
        ORDER BY views DESC LIMIT 5
    ');
    $stmt2->execute([$uid]);
    $top = $stmt2->fetchAll();

    // Daily aggregate for last 14 days
    $stmt3 = db()->prepare('
        SELECT ds.stat_date, SUM(ds.views) AS views, SUM(ds.likes) AS likes, SUM(ds.comments) AS comments, SUM(ds.shares) AS shares
        FROM video_daily_stats ds
        JOIN videos v ON ds.video_id = v.id
        WHERE v.user_id = ?
        GROUP BY ds.stat_date
        ORDER BY ds.stat_date ASC
        LIMIT 14
    ');
    $stmt3->execute([$uid]);
    $dailyAgg = $stmt3->fetchAll();

    json_response(200, [
        'aggregate' => [
            'total_videos' => (int) $agg['total_videos'],
            'total_views' => (int) $agg['total_views'],
            'total_likes' => (int) $agg['total_likes'],
            'total_comments' => (int) $agg['total_comments'],
            'total_shares' => (int) $agg['total_shares'],
        ],
        'top_videos' => $top,
        'daily' => $dailyAgg,
    ]);
}

json_response(400, ['error' => 'Provide video_id or mine parameter']);
