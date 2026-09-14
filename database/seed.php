<?php
// Seed script: run once to create demo users with proper password hashes
// Usage: docker exec gosulawesi-php php /var/www/html/database/seed.php

require_once __DIR__ . '/../config.php';

$users = [
    ['Admin GoSulawesi', 'admin@gosulawesi.id', 'admin123', 'admin'],
    ['Tourist Demo', 'tourist@gosulawesi.id', 'tourist123', 'tourist'],
    ['Local Business', 'local@gosulawesi.id', 'local123', 'local'],
];

foreach ($users as [$name, $email, $pass, $role]) {
    $stmt = db()->prepare('SELECT id FROM users WHERE email = ?');
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        echo "User $email already exists, updating password...\n";
        $stmt = db()->prepare('UPDATE users SET password = ? WHERE email = ?');
        $stmt->execute([password_hash($pass, PASSWORD_BCRYPT), $email]);
    } else {
        $stmt = db()->prepare('INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)');
        $stmt->execute([$name, $email, password_hash($pass, PASSWORD_BCRYPT), $role]);
        echo "Created user: $email\n";
    }
}

// Create business for local user
$stmt = db()->prepare("SELECT id FROM users WHERE email = 'local@gosulawesi.id'");
$stmt->execute();
$localUser = $stmt->fetch();
if ($localUser) {
    $stmt = db()->prepare('SELECT id FROM businesses WHERE user_id = ?');
    $stmt->execute([$localUser['id']]);
    if (!$stmt->fetch()) {
        $stmt = db()->prepare('INSERT INTO businesses (user_id, business_name, business_type, city, phone, description, image_url, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
        $stmt->execute([
            $localUser['id'],
            'Sulawesi Dive Center',
            'Dive & Water Sports',
            'Manado',
            '+628123456789',
            'Professional diving tours in Bunaken Marine Park',
            '/img/Air Terjun Depa.jpeg',
            'approved',
        ]);
        echo "Created business for local user\n";
    }
}

echo "Seed completed!\n";
