<?php
// Migration runner for premium video feed features (sounds + analytics).
// Usage: docker exec gosulawesi-php php /var/www/html/database/migrate-videos-v2.php

require_once __DIR__ . '/../config.php';

$sql = file_get_contents(__DIR__ . '/videos-v2.sql');
if ($sql === false) {
    fwrite(STDERR, "Could not read videos-v2.sql\n");
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
    // Extract a label for logging
    if (preg_match('/CREATE TABLE IF NOT EXISTS\s+(\w+)/i', $statement, $m)) {
        $label = $m[1];
    } elseif (preg_match('/ALTER TABLE\s+(\w+)/i', $statement, $m)) {
        $label = 'ALTER ' . $m[1];
    } elseif (preg_match('/INSERT INTO\s+(\w+)/i', $statement, $m)) {
        $label = 'INSERT ' . $m[1];
    } else {
        $label = 'statement';
    }
    try {
        db()->exec($statement);
        echo "OK   $label\n";
    } catch (PDOException $e) {
        // Ignore "Duplicate column" errors for idempotent ALTERs
        if (strpos($e->getMessage(), 'Duplicate column') !== false) {
            echo "SKIP $label (already exists)\n";
        } else {
            echo "FAIL $label -> " . $e->getMessage() . "\n";
        }
    }
}

// Ensure the sounds directory exists
$soundsDir = __DIR__ . '/../sounds/';
if (!is_dir($soundsDir)) {
    mkdir($soundsDir, 0775, true);
    echo "OK   created sounds/\n";
}
chmod($soundsDir, 0775);

echo "Premium video migration completed.\n";
