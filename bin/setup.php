<?php

declare(strict_types=1);

chdir(dirname(__DIR__));
passthru('rm -rf ./var/tmp/*');

$dsn = $_ENV['DB_DSN'] ?? getenv('DB_DSN') ?: 'sqlite:var/db/constraint_engine.sqlite3';
$user = $_ENV['DB_USER'] ?? getenv('DB_USER') ?: '';
$pass = $_ENV['DB_PASS'] ?? getenv('DB_PASS') ?: '';

if (str_starts_with($dsn, 'sqlite:')) {
    // SQLite: ensure directory exists
    $dbFile = substr($dsn, 7);
    $dbDir = dirname($dbFile);
    if (! is_dir($dbDir)) {
        mkdir($dbDir, 0755, true);
    }

    $pdo = new PDO($dsn);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $sqlFile = './var/sql/sqlite/create_checkpoint.sql';
} elseif (str_starts_with($dsn, 'pgsql:')) {
    $pdo = new PDO($dsn, $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $sqlFile = './var/sql/pgsql/create_checkpoint.sql';
} else {
    echo "Unsupported DB_DSN: {$dsn}" . PHP_EOL;
    exit(1);
}

$sql = file_get_contents($sqlFile);
if ($sql !== false) {
    $pdo->exec($sql);
    echo "Database setup complete: {$dsn}" . PHP_EOL;
}
