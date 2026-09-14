<?php
// Seed script: adds demo bookings and reviews for the local business
// Usage: docker exec gosulawesi-php php /var/www/html/database/seed_business_data.php

require_once __DIR__ . '/../config.php';

// ── 1. Get local business ──
$stmt = db()->prepare("SELECT id FROM users WHERE email = 'local@gosulawesi.id'");
$stmt->execute();
$localUser = $stmt->fetch();
if (!$localUser) {
    echo "Local user not found\n";
    exit;
}
$localId = $localUser['id'];

$stmt = db()->prepare('SELECT id FROM businesses WHERE user_id = ?');
$stmt->execute([$localId]);
$business = $stmt->fetch();
if (!$business) {
    echo "Business not found for local user\n";
    exit;
}
$businessId = $business['id'];

// ── 2. Get tourist users ──
$stmt = db()->prepare("SELECT id FROM users WHERE role = 'tourist' LIMIT 6");
$stmt->execute();
$tourists = $stmt->fetchAll();
if (count($tourists) < 3) {
    echo "Need at least 3 tourist users\n";
    exit;
}

// ── 3. Get some destinations for context ──
$stmt = db()->query('SELECT id FROM destinations ORDER BY RAND() LIMIT 10');
$destinations = $stmt->fetchAll();

// ── 4. Create sample bookings for this business ──
$bookingData = [
    [0, '+2 days', 'confirmed', 2500000, 'Dive tour for 2 people'],
    [1, '+5 days', 'pending', 1800000, 'Snorkeling trip'],
    [2, '-3 days', 'completed', 3200000, 'Full day diving package'],
    [3, '-10 days', 'completed', 1500000, 'Sunset snorkeling'],
    [4, '+12 days', 'confirmed', 4200000, 'Advanced diver course'],
];

$bookings = [];
foreach ($bookingData as $i => $b) {
    $t = $tourists[$i % count($tourists)]['id'] ?? null;
    $d = $destinations[$i % count($destinations)]['id'] ?? null;
    if ($t) {
        $bookings[] = [$t, $d, $businessId, date('Y-m-d', strtotime($b[1])), $b[2], $b[3], $b[4]];
    }
}

$stmt = db()->prepare('SELECT COUNT(*) FROM bookings WHERE business_id = ?');
$stmt->execute([$businessId]);
$existing = (int) $stmt->fetchColumn();

if ($existing === 0) {
    foreach ($bookings as $b) {
        $stmt = db()->prepare('INSERT INTO bookings (user_id, destination_id, business_id, booking_date, status, total_price, notes) VALUES (?, ?, ?, ?, ?, ?, ?)');
        $stmt->execute($b);
    }
    echo "Created " . count($bookings) . " bookings for business\n";
} else {
    echo "Bookings already exist for business ($existing)\n";
}

// ── 5. Create sample reviews for this business ──
$reviewData = [
    [5, 'Amazing diving experience! The instructors were professional and the coral reefs were stunning.'],
    [5, 'Best snorkeling trip in Bunaken. Highly recommended for families.'],
    [4, 'Great equipment and friendly staff. The boat was a bit crowded though.'],
    [5, 'Professional PADI instructors. Felt safe throughout the entire dive.'],
    [4, 'Beautiful marine life. Would book again!'],
    [5, 'Unforgettable underwater adventure in Sulawesi.'],
];

$reviews = [];
foreach ($reviewData as $i => $r) {
    $t = $tourists[$i % count($tourists)]['id'] ?? null;
    if ($t) {
        $reviews[] = [$t, $businessId, $r[0], $r[1]];
    }
}

$stmt = db()->prepare('SELECT COUNT(*) FROM reviews WHERE business_id = ?');
$stmt->execute([$businessId]);
$existingReviews = (int) $stmt->fetchColumn();

if ($existingReviews === 0) {
    foreach ($reviews as $r) {
        $stmt = db()->prepare('INSERT INTO reviews (user_id, business_id, rating, comment) VALUES (?, ?, ?, ?)');
        $stmt->execute($r);
    }
    echo "Created " . count($reviews) . " reviews for business\n";
} else {
    echo "Reviews already exist for business ($existingReviews)\n";
}

// ── 6. Add more businesses for the local user ──
$moreBusinesses = [
    ['Makassar Cultural Tours', 'Cultural Experience', 'Makassar', '+628123456790', 'Authentic Bugis and Makassar cultural tours and ceremonies', 'approved', 4.6],
    ['Bantaeng Village Homestay', 'Village Stay', 'Bantaeng', '+628123456791', 'Traditional village homestay with local cooking classes', 'approved', 4.7],
    ['Tana Toraja Guide', 'Tour Guide', 'Rantepao', '+628123456792', 'Experienced local guide for Toraja funeral ceremonies and highland tours', 'pending', 4.9],
];

$created = 0;
foreach ($moreBusinesses as $biz) {
    $stmt = db()->prepare('SELECT id FROM businesses WHERE business_name = ?');
    $stmt->execute([$biz[0]]);
    if (!$stmt->fetch()) {
        $stmt = db()->prepare('INSERT INTO businesses (user_id, business_name, business_type, city, phone, description, status, rating) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
        $stmt->execute([$localId, $biz[0], $biz[1], $biz[2], $biz[3], $biz[4], $biz[5], $biz[6]]);
        $created++;
    }
}
if ($created > 0) {
    echo "Created $created additional businesses for local user\n";
} else {
    echo "Additional businesses already exist\n";
}

echo "\nBusiness seed completed!\n";
