<?php
require_once __DIR__ . '/../config.php';
$r = db()->query('SHOW TABLES LIKE "cancellation_policies"')->fetchAll(PDO::FETCH_NUM);
echo count($r) > 0 ? "EXISTS\n" : "MISSING\n";
