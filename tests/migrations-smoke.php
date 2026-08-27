<?php

declare(strict_types=1);

use App\Database\Migrator;

define('BASE_PATH', dirname(__DIR__));
require BASE_PATH . '/src/Support/autoload.php';

$migrationsPath = BASE_PATH . '/storage/migrations';

$fresh = database();
Migrator::migrate($fresh, $migrationsPath);
assertSchema($fresh);
assertMigrationCount($fresh, 6);
Migrator::migrate($fresh, $migrationsPath);
assertMigrationCount($fresh, 6);

$old = database();
$old->exec(file_get_contents($migrationsPath . '/001_init.sql'));
$old->exec(
    "INSERT INTO users (login, password_hash, name, is_active, created_at, updated_at)
     VALUES ('existing', 'hash', 'Existing user', 1, 'before', 'before')"
);
Migrator::migrate($old, $migrationsPath);
assertSchema($old);
assertMigrationCount($old, 6);
if ($old->query("SELECT name FROM users WHERE login = 'existing'")->fetchColumn() !== 'Existing user') {
    throw new RuntimeException('Existing database data was not preserved.');
}

$current = database();
$current->exec(file_get_contents($migrationsPath . '/001_init.sql'));
$current->exec(file_get_contents($migrationsPath . '/002_add_users_email.sql'));
$current->exec(file_get_contents($migrationsPath . '/003_add_users_email_index.sql'));
$current->exec(file_get_contents($migrationsPath . '/004_add_users_role.sql'));
$current->exec(file_get_contents($migrationsPath . '/005_normalize_users_role.sql'));
$current->exec(file_get_contents($migrationsPath . '/006_create_settings.sql'));
Migrator::migrate($current, $migrationsPath);
assertSchema($current);
assertMigrationCount($current, 6);

$partial = database();
$partial->exec(file_get_contents($migrationsPath . '/001_init.sql'));
$partial->exec(file_get_contents($migrationsPath . '/002_add_users_email.sql'));
Migrator::migrate($partial, $migrationsPath);
assertSchema($partial);
assertMigrationCount($partial, 6);

echo "PASS fresh, existing, partial and current database migrations\n";

function database(): PDO
{
    $pdo = new PDO('sqlite::memory:');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $pdo->exec('PRAGMA foreign_keys = ON');

    return $pdo;
}

function assertSchema(PDO $pdo): void
{
    $columns = $pdo->query('PRAGMA table_info(users)')->fetchAll(PDO::FETCH_COLUMN, 1);
    foreach (['email', 'role'] as $column) {
        if (! in_array($column, $columns, true)) {
            throw new RuntimeException('Missing users column: ' . $column);
        }
    }

    $settings = $pdo->query("SELECT 1 FROM sqlite_master WHERE type = 'table' AND name = 'settings'")->fetchColumn();
    if (! $settings) {
        throw new RuntimeException('Missing settings table.');
    }
}

function assertMigrationCount(PDO $pdo, int $expected): void
{
    $actual = (int) $pdo->query('SELECT COUNT(*) FROM schema_migrations')->fetchColumn();
    if ($actual !== $expected) {
        throw new RuntimeException("Expected {$expected} migrations, found {$actual}.");
    }
}
