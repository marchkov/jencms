<?php

declare(strict_types=1);

use App\Database\Migrator;
use App\Support\SystemCheck;

define('BASE_PATH', dirname(__DIR__));
require BASE_PATH . '/src/Support/autoload.php';

$pdo = new PDO('sqlite::memory:');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
Migrator::migrate($pdo, BASE_PATH . '/storage/migrations');

$now = date('c');
$statement = $pdo->prepare(
    'INSERT INTO users (login, password_hash, name, email, role, is_active, created_at, updated_at)
     VALUES (:login, :password_hash, :name, :email, :role, 1, :created_at, :updated_at)'
);
$statement->execute([
    'login' => 'admin',
    'password_hash' => password_hash('admin123', PASSWORD_DEFAULT),
    'name' => 'Administrator',
    'email' => 'admin@example.test',
    'role' => 'administrator',
    'created_at' => $now,
    'updated_at' => $now,
]);

$config = require BASE_PATH . '/settings.php';
$checks = (new SystemCheck($pdo, $config, BASE_PATH))->run();
$byName = array_column($checks, null, 'name');

if (($byName['PHP version']['status'] ?? null) !== 'ok') {
    throw new RuntimeException('Supported PHP version was not detected.');
}

if (($byName['Administrator password']['status'] ?? null) !== 'warning') {
    throw new RuntimeException('The default administrator password was not detected.');
}

if (($byName['Private files']['status'] ?? null) !== 'ok') {
    throw new RuntimeException('Private storage location was not detected correctly.');
}

echo "PASS system checks and default password warning\n";
