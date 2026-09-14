<?php
require_once __DIR__ . '/../config.php';

$sql = file_get_contents(__DIR__ . '/cancellation-v1.sql');
$statements = array_filter(array_map('trim', explode(';', $sql)));
$ok = 0;
foreach ($statements as $stmt) {
    if (empty($stmt)) continue;
    try {
        db()->exec($stmt);
        $ok++;
    } catch (PDOException $e) {
        echo "SKIP: {$e->getMessage()}\n";
    }
}
echo "Migration complete ($ok statements executed).\n";
