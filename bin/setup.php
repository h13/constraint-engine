<?php

declare(strict_types=1);

chdir(dirname(__DIR__));
passthru('rm -rf ./var/tmp/*');

if (! is_dir('./var/db')) {
    mkdir('./var/db', 0755, true);
}

$dbPath = './var/db/constraint_engine.sqlite3';
$pdo = new PDO('sqlite:' . $dbPath);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$sql = file_get_contents('./var/sql/create_checkpoint.sql');
if ($sql !== false) {
    $pdo->exec($sql);
    echo "Database setup complete: {$dbPath}" . PHP_EOL;
}
