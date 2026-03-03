<?php

declare(strict_types=1);

chdir(dirname(__DIR__));
passthru('rm -rf ./var/tmp/*');

$dsn = $_ENV['DB_DSN'] ?? getenv('DB_DSN') ?: 'pgsql:host=localhost;dbname=constraint_engine';
$user = $_ENV['DB_USER'] ?? getenv('DB_USER') ?: 'app';
$pass = $_ENV['DB_PASS'] ?? getenv('DB_PASS') ?: 'secret';

$pdo = new PDO($dsn, $user, $pass);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$sql = file_get_contents('./var/sql/create_checkpoint.sql');
if ($sql !== false) {
    $pdo->exec($sql);
    echo "Database setup complete: {$dsn}" . PHP_EOL;
}
