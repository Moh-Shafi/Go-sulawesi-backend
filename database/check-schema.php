<?php
require_once __DIR__ . '/../config.php';
$rows = db()->query('DESCRIBE bookings')->fetchAll(PDO::FETCH_ASSOC);
foreach ($rows as $row) {
    echo $row['Field'] . ' | ' . $row['Type'] . PHP_EOL;
}
echo "---\n";
$rows2 = db()->query('SHOW TABLES LIKE "cancellation_%"')->fetchAll(PDO::FETCH_NUM);
foreach ($rows2 as $row) {
    echo $row[0] . PHP_EOL;
}
