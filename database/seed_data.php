<?php
// Seed script: adds demo users, destinations, bookings, and reviews
// Usage: docker exec gosulawesi-php php /var/www/html/database/seed_data.php

require_once __DIR__ . '/../config.php';

// ── 1. Users ──
$users = [
    ['Admin GoSulawesi', 'admin@gosulawesi.id', 'admin123', 'admin'],
    ['Tourist Demo', 'tourist@gosulawesi.id', 'tourist123', 'tourist'],
    ['Local Business', 'local@gosulawesi.id', 'local123', 'local'],
    ['Andi Kurniawan', 'andi@gosulawesi.id', 'guide123', 'tourist'],
    ['Dewi Rahayu', 'dewi@gosulawesi.id', 'guide123', 'tourist'],
    ['Budi Santoso', 'budi@gosulawesi.id', 'guide123', 'tourist'],
];

$userIdMap = [];
foreach ($users as [$name, $email, $pass, $role]) {
    $stmt = db()->prepare('SELECT id FROM users WHERE email = ?');
    $stmt->execute([$email]);
    $existing = $stmt->fetch();
    if ($existing) {
        $stmt = db()->prepare('UPDATE users SET password = ? WHERE email = ?');
        $stmt->execute([password_hash($pass, PASSWORD_BCRYPT), $email]);
        $userIdMap[$email] = $existing['id'];
        echo "Updated user: $email (id={$existing['id']})\n";
    } else {
        $stmt = db()->prepare('INSERT INTO users (name, email, password, role, phone, avatar) VALUES (?, ?, ?, ?, ?, ?)');
        $phone = '+62812345' . str_pad((string)rand(1000, 9999), 4, '0', STR_PAD_LEFT);
        $avatar = '/img-local/' . ['admin'=>'1.jpg','tourist@gosulawesi.id'=>'2.jpg','local@gosulawesi.id'=>'3.jpg','andi@gosulawesi.id'=>'1.jpg','dewi@gosulawesi.id'=>'2.jpg','budi@gosulawesi.id'=>'3.jpg'][$email] ?? null;
        $stmt->execute([$name, $email, password_hash($pass, PASSWORD_BCRYPT), $role, $phone, $avatar]);
        $userIdMap[$email] = (int) db()->lastInsertId();
        echo "Created user: $email (id={$userIdMap[$email]})\n";
    }
}

// ── 2. Business for local user ──
$localId = $userIdMap['local@gosulawesi.id'];
$stmt = db()->prepare('SELECT id FROM businesses WHERE user_id = ?');
$stmt->execute([$localId]);
if (!$stmt->fetch()) {
    $stmt = db()->prepare('INSERT INTO businesses (user_id, business_name, business_type, city, phone, description, image_url, status, rating) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
    $stmt->execute([
        $localId,
        'Sulawesi Dive Center',
        'Dive & Water Sports',
        'Manado',
        '+628123456789',
        'Professional diving tours in Bunaken Marine Park',
        '/img/Air Terjun Depa.jpeg',
        'approved',
        4.8,
    ]);
    echo "Created business for local user\n";
}

// ── 3. Destinations ──
$destinations = [
    ['Danau Tanralili', 'Gowa', 'Nature', 'A pristine mountain lake surrounded by lush forests, perfect for hiking and camping.', '/img/Danau Tanralili.jpg', 4.9, 850000, -5.2670, 119.5550],
    ['Mappaccing Ceremony', 'Makassar', 'Cultural', 'Traditional Bugis coming-of-age ceremony with vibrant costumes and rituals.', '/img/Mappaccing Ceremony.jpg', 4.8, 1200000, -5.1477, 119.4326],
    ['Desa Bonto Manai', 'Bantaeng', 'Village', 'Authentic South Sulawesi village experience with traditional weaving and local cuisine.', '/img/Desa_Bonto_Manai.jpg', 4.7, 600000, -5.3750, 119.9000],
    ['Bungung Salapang', 'Jeneponto', 'Sacred', 'Sacred spring water site with cultural significance to the local Makassar people.', '/img/Bungung Salapang.webp', 4.6, 500000, -5.6500, 119.7000],
    ['Air Terjun Depa', 'Maros', 'Nature', 'Stunning multi-tiered waterfall hidden in karst landscapes, ideal for adventure seekers.', '/img/Air Terjun Depa.jpeg', 4.7, 750000, -4.9500, 119.5800],
    ["Menre' ri Lontang", 'Bulukumba', 'Cultural', 'Traditional Phinisi boat-building heritage village with centuries of craftsmanship.', "/img/Menre' ri Lontang.jpg", 4.8, 950000, -5.4800, 120.7200],
    ['Bantimurung Park', 'Maros', 'Nature', 'Waterfalls, butterfly conservation and limestone caves in a stunning karst setting.', '/img/Desa_Bonto_Manai.jpg', 4.6, 75000, -4.9900, 119.5600],
    ['Tana Toraja', 'Rantepao', 'Cultural', 'Unique funeral rituals, traditional Tongkonan houses and breathtaking highland landscapes.', '/img/Mappaccing Ceremony.jpg', 4.9, 300000, -3.0300, 119.8800],
    ['Lake Tondano', 'Tondano', 'Nature', 'Beautiful volcanic lake surrounded by mountains and flower gardens.', '/img/Danau Tanralili.jpg', 4.5, 100000, 1.2900, 124.9000],
    ['Bunaken Marine Park', 'Manado', 'Diving', 'World-class diving and snorkeling with stunning coral walls and diverse marine life.', '/img/Air Terjun Depa.jpeg', 4.9, 350000, 1.6200, 124.7600],
    ['Wakatobi Islands', 'Kendari', 'Diving', 'Pristine islands with some of the best coral reefs in the world.', '/img/Bungung Salapang.webp', 4.9, 500000, -5.3200, 123.7600],
    ['Tomohon Market', 'Tomohon', 'Cultural', 'Traditional market famous for exotic local foods and flowers.', "/img/Menre' ri Lontang.jpg", 4.3, 50000, 1.3100, 124.8300],
];

$destIdMap = [];
foreach ($destinations as $d) {
    $stmt = db()->prepare('SELECT id FROM destinations WHERE name = ?');
    $stmt->execute([$d[0]]);
    $existing = $stmt->fetch();
    if ($existing) {
        $stmt = db()->prepare('UPDATE destinations SET city=?, category=?, description=?, image_url=?, rating=?, price=?, latitude=?, longitude=? WHERE name=?');
        $stmt->execute([$d[1], $d[2], $d[3], $d[4], $d[5], $d[6], $d[7], $d[8], $d[0]]);
        $destIdMap[$d[0]] = $existing['id'];
        echo "Updated destination: {$d[0]} (id={$existing['id']})\n";
    } else {
        $stmt = db()->prepare('INSERT INTO destinations (name, city, category, description, image_url, rating, price, latitude, longitude) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
        $stmt->execute($d);
        $destIdMap[$d[0]] = (int) db()->lastInsertId();
        echo "Created destination: {$d[0]} (id={$destIdMap[$d[0]]})\n";
    }
}

// ── 4. Bookings for tourist user ──
$touristId = $userIdMap['tourist@gosulawesi.id'];
$bookings = [
    [$touristId, $destIdMap['Danau Tanralili'], null, date('Y-m-d', strtotime('+6 days')), 'confirmed', 850000, 'Nature hiking trip'],
    [$touristId, $destIdMap['Mappaccing Ceremony'], null, date('Y-m-d', strtotime('+8 days')), 'confirmed', 1200000, 'Cultural ceremony experience'],
    [$touristId, $destIdMap['Air Terjun Depa'], null, date('Y-m-d', strtotime('+16 days')), 'pending', 750000, 'Waterfall adventure'],
    [$touristId, $destIdMap['Tana Toraja'], null, date('Y-m-d', strtotime('-10 days')), 'completed', 300000, 'Highland cultural tour'],
    [$touristId, $destIdMap['Bunaken Marine Park'], null, date('Y-m-d', strtotime('-30 days')), 'completed', 350000, 'Diving trip'],
    [$touristId, $destIdMap["Menre' ri Lontang"], null, date('Y-m-d', strtotime('-45 days')), 'completed', 950000, 'Phinisi boat heritage tour'],
];

$stmt = db()->prepare('SELECT COUNT(*) FROM bookings WHERE user_id = ?');
$stmt->execute([$touristId]);
$existingBookings = (int) $stmt->fetchColumn();
if ($existingBookings === 0) {
    foreach ($bookings as $b) {
        $stmt = db()->prepare('INSERT INTO bookings (user_id, destination_id, business_id, booking_date, status, total_price, notes) VALUES (?, ?, ?, ?, ?, ?, ?)');
        $stmt->execute($b);
    }
    echo "Created " . count($bookings) . " bookings for tourist user\n";
} else {
    echo "Bookings already exist for tourist user ($existingBookings)\n";
}

// ── 5. Reviews ──
$reviews = [
    [$touristId, $destIdMap['Danau Tanralili'], null, 5, 'Absolutely breathtaking! The lake is crystal clear and the hike was amazing.'],
    [$touristId, $destIdMap['Mappaccing Ceremony'], null, 5, 'A once-in-a-lifetime cultural experience. The costumes and rituals were incredible.'],
    [$touristId, $destIdMap['Tana Toraja'], null, 4, 'Fascinating culture and beautiful highland scenery. A must-visit in Sulawesi.'],
    [$touristId, $destIdMap['Bunaken Marine Park'], null, 5, 'Best diving experience ever! The coral walls are stunning and full of marine life.'],
    [$touristId, $destIdMap["Menre' ri Lontang"], null, 4, 'Amazing to see traditional boat building still alive. The craftsmen are very skilled.'],
    [$touristId, $destIdMap['Air Terjun Depa'], null, 4, 'Beautiful waterfall but the trek is challenging. Worth it for adventure lovers!'],
];

$stmt = db()->prepare('SELECT COUNT(*) FROM reviews WHERE user_id = ?');
$stmt->execute([$touristId]);
$existingReviews = (int) $stmt->fetchColumn();
if ($existingReviews === 0) {
    foreach ($reviews as $r) {
        $stmt = db()->prepare('INSERT INTO reviews (user_id, destination_id, business_id, rating, comment) VALUES (?, ?, ?, ?, ?)');
        $stmt->execute($r);
    }
    echo "Created " . count($reviews) . " reviews for tourist user\n";
} else {
    echo "Reviews already exist for tourist user ($existingReviews)\n";
}

echo "\nSeed data completed!\n";
echo "Login: tourist@gosulawesi.id / tourist123\n";
