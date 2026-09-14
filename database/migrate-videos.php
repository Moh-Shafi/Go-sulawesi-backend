<?php
// Migration runner for the social video feed tables.
// Usage (local docker): docker exec gosulawesi-php php /var/www/html/database/migrate-videos.php
// Usage (shared host):  php backend/database/migrate-videos.php

require_once __DIR__ . '/../config.php';

$sql = file_get_contents(__DIR__ . '/videos.sql');
if ($sql === false) {
    fwrite(STDERR, "Could not read videos.sql\n");
    exit(1);
}

// Split on semicolons that terminate a statement, ignoring comment-only lines.
$statements = [];
$buffer = '';
foreach (preg_split('/\R/', $sql) as $line) {
    $trimmed = trim($line);
    if ($trimmed === '' || str_starts_with($trimmed, '--')) continue;
    $buffer .= $line . "\n";
    if (str_ends_with($trimmed, ';')) {
        $statements[] = trim($buffer);
        $buffer = '';
    }
}
if (trim($buffer) !== '') $statements[] = trim($buffer);

foreach ($statements as $statement) {
    preg_match('/CREATE TABLE IF NOT EXISTS\s+(\w+)/i', $statement, $m);
    $table = $m[1] ?? 'statement';
    try {
        db()->exec($statement);
        echo "OK   $table\n";
    } catch (PDOException $e) {
        echo "FAIL $table -> " . $e->getMessage() . "\n";
    }
}

// Ensure the upload directory exists and is writable.
$uploadDir = __DIR__ . '/../uploads/videos/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0777, true);
    echo "OK   created uploads/videos/\n";
}
// Ensure the directory is writable by the web server.
chmod($uploadDir, 0775);
if (function_exists('posix_getpwuid') && function_exists('posix_geteuid')) {
    $owner = posix_getpwuid(posix_geteuid());
    echo "OK   uploads/videos/ owner={$owner['name']}\n";
}
echo "Video migration completed.\n";
