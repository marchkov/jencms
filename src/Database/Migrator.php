<?php

declare(strict_types=1);

namespace App\Database;

use PDO;
use RuntimeException;
use Throwable;

final class Migrator
{
    private const CORE_TABLES = ['users', 'pages', 'sections', 'categories', 'posts'];

    public static function migrate(PDO $pdo, string $directory): void
    {
        $migrations = self::migrationFiles($directory);

        $pdo->exec(
            'CREATE TABLE IF NOT EXISTS schema_migrations (
                migration TEXT PRIMARY KEY,
                applied_at TEXT NOT NULL
            )'
        );

        self::baselineExistingDatabase($pdo, array_keys($migrations));

        $applied = self::appliedMigrations($pdo);
        foreach ($migrations as $name => $path) {
            if (isset($applied[$name])) {
                continue;
            }

            self::apply($pdo, $name, $path);
        }
    }

    /** @return array<string, string> */
    private static function migrationFiles(string $directory): array
    {
        if (! is_dir($directory)) {
            throw new RuntimeException('Database migrations directory does not exist.');
        }

        $paths = glob(rtrim($directory, '/\\') . DIRECTORY_SEPARATOR . '[0-9][0-9][0-9]_*.sql');
        if ($paths === false || $paths === []) {
            throw new RuntimeException('No database migrations were found.');
        }

        sort($paths, SORT_STRING);
        $migrations = [];
        foreach ($paths as $path) {
            $name = basename($path);
            $migrations[$name] = $path;
        }

        return $migrations;
    }

    /** @param list<string> $available */
    private static function baselineExistingDatabase(PDO $pdo, array $available): void
    {
        if (self::appliedMigrations($pdo) !== []) {
            return;
        }

        $existingCoreTables = array_values(array_filter(
            self::CORE_TABLES,
            static fn (string $table): bool => self::hasTable($pdo, $table)
        ));

        if ($existingCoreTables === []) {
            return;
        }

        if (count($existingCoreTables) !== count(self::CORE_TABLES)) {
            throw new RuntimeException('The existing database schema is incomplete; migrations were not applied.');
        }

        $represented = ['001_init.sql'];

        if (self::hasColumn($pdo, 'users', 'email')) {
            $represented[] = '002_add_users_email.sql';
            if (self::hasIndex($pdo, 'idx_users_email')) {
                $represented[] = '003_add_users_email_index.sql';
            }
        }

        if (self::hasColumn($pdo, 'users', 'role')) {
            $represented[] = '004_add_users_role.sql';
            if (self::hasIndex($pdo, 'idx_users_role') && ! self::hasEmptyRole($pdo)) {
                $represented[] = '005_normalize_users_role.sql';
            }
        }

        if (self::hasTable($pdo, 'settings')) {
            $represented[] = '006_create_settings.sql';
        }

        try {
            $pdo->beginTransaction();
            foreach ($represented as $name) {
                if (in_array($name, $available, true)) {
                    self::record($pdo, $name);
                }
            }
            $pdo->commit();
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            throw new RuntimeException('Unable to register the existing database schema.', 0, $exception);
        }
    }

    /** @return array<string, true> */
    private static function appliedMigrations(PDO $pdo): array
    {
        $statement = $pdo->query('SELECT migration FROM schema_migrations');
        $names = $statement ? $statement->fetchAll(PDO::FETCH_COLUMN) : [];

        return array_fill_keys($names, true);
    }

    private static function apply(PDO $pdo, string $name, string $path): void
    {
        $sql = file_get_contents($path);
        if ($sql === false) {
            throw new RuntimeException('Unable to read database migration: ' . $name);
        }

        try {
            $pdo->beginTransaction();
            $pdo->exec($sql);
            self::record($pdo, $name);
            $pdo->commit();
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            throw new RuntimeException('Database migration failed: ' . $name, 0, $exception);
        }
    }

    private static function record(PDO $pdo, string $name): void
    {
        $statement = $pdo->prepare(
            'INSERT INTO schema_migrations (migration, applied_at) VALUES (:migration, :applied_at)'
        );
        $statement->execute(['migration' => $name, 'applied_at' => date('c')]);
    }

    private static function hasTable(PDO $pdo, string $table): bool
    {
        $statement = $pdo->prepare("SELECT 1 FROM sqlite_master WHERE type = 'table' AND name = :name");
        $statement->execute(['name' => $table]);

        return (bool) $statement->fetchColumn();
    }

    private static function hasColumn(PDO $pdo, string $table, string $column): bool
    {
        $statement = $pdo->query('PRAGMA table_info(' . $table . ')');
        foreach ($statement ? $statement->fetchAll(PDO::FETCH_ASSOC) : [] as $info) {
            if (($info['name'] ?? '') === $column) {
                return true;
            }
        }

        return false;
    }

    private static function hasIndex(PDO $pdo, string $index): bool
    {
        $statement = $pdo->prepare("SELECT 1 FROM sqlite_master WHERE type = 'index' AND name = :name");
        $statement->execute(['name' => $index]);

        return (bool) $statement->fetchColumn();
    }

    private static function hasEmptyRole(PDO $pdo): bool
    {
        $statement = $pdo->query("SELECT 1 FROM users WHERE role IS NULL OR role = '' LIMIT 1");

        return (bool) $statement->fetchColumn();
    }
}
