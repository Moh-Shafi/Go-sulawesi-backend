<?php
require_once __DIR__ . '/../config.php';

$user = require_auth();

// Only return revenue to admins; tourists/locals see public counts only
$businesses = (int) db()->query("SELECT COUNT(*) FROM businesses WHERE status='approved'")->fetchColumn();
$tourists   = (int) db()->query("SELECT COUNT(*) FROM users WHERE role='tourist'")->fetchColumn();
$bookings   = (int) db()->query("SELECT COUNT(*) FROM bookings")->fetchColumn();
$dests      = (int) db()->query("SELECT COUNT(*) FROM destinations")->fetchColumn();

$response = [
    'local_businesses'  => $businesses,
    'tourists_connected' => $tourists,
    'bookings_completed' => $bookings,
    'destinations'       => $dests,
];

// Revenue is sensitive — only expose to admins
if ($user['role'] === 'admin') {
    $response['revenue_generated'] = (float) db()->query("SELECT COALESCE(SUM(total_price),0) FROM bookings WHERE status IN ('confirmed','completed')")->fetchColumn();
}

json_response(200, $response);
