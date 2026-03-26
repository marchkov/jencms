<?php

declare(strict_types=1);

namespace App\Database;

use PDO;
use RuntimeException;

final class Initializer
{
    public static function bootstrap(PDO $pdo, string $schemaPath): void
    {
        if (! self::hasTable($pdo, 'pages')) {
            $schema = file_get_contents($schemaPath);

            if ($schema === false) {
                throw new RuntimeException('Unable to read database schema.');
            }

            $pdo->exec($schema);
        }

        self::ensureSettingsTable($pdo);

        if (! self::hasSeedData($pdo)) {
            self::seed($pdo, dirname($schemaPath) . '/../source_main_content.html');
        }

        self::ensureUsersEmailColumn($pdo);
        self::ensureUsersRoleColumn($pdo);
        self::ensureDefaultAdminUser($pdo);
    }

    private static function hasTable(PDO $pdo, string $table): bool
    {
        $statement = $pdo->prepare("SELECT name FROM sqlite_master WHERE type = 'table' AND name = :table");
        $statement->execute(['table' => $table]);

        return (bool) $statement->fetchColumn();
    }

    private static function hasColumn(PDO $pdo, string $table, string $column): bool
    {
        $statement = $pdo->query('PRAGMA table_info(' . $table . ')');
        $columns = $statement ? $statement->fetchAll(PDO::FETCH_ASSOC) : [];

        foreach ($columns as $info) {
            if (($info['name'] ?? '') === $column) {
                return true;
            }
        }

        return false;
    }

    private static function hasSeedData(PDO $pdo): bool
    {
        if (! self::hasTable($pdo, 'pages')) {
            return false;
        }

        $statement = $pdo->query('SELECT COUNT(*) FROM pages');

        return (int) $statement->fetchColumn() > 0;
    }

    private static function ensureSettingsTable(PDO $pdo): void
    {
        $pdo->exec(
            'CREATE TABLE IF NOT EXISTS settings (
                key_name TEXT PRIMARY KEY,
                value TEXT NOT NULL,
                updated_at TEXT NOT NULL
            )'
        );
    }

    private static function ensureUsersEmailColumn(PDO $pdo): void
    {
        if (! self::hasTable($pdo, 'users')) {
            return;
        }

        if (! self::hasColumn($pdo, 'users', 'email')) {
            $pdo->exec('ALTER TABLE users ADD COLUMN email TEXT DEFAULT NULL');
        }

        $pdo->exec('CREATE UNIQUE INDEX IF NOT EXISTS idx_users_email ON users(email)');
    }

    private static function ensureUsersRoleColumn(PDO $pdo): void
    {
        if (! self::hasTable($pdo, 'users')) {
            return;
        }

        if (! self::hasColumn($pdo, 'users', 'role')) {
            $pdo->exec("ALTER TABLE users ADD COLUMN role TEXT NOT NULL DEFAULT 'administrator'");
        }

        $pdo->exec("UPDATE users SET role = 'administrator' WHERE role IS NULL OR role = ''");
    }

    private static function ensureDefaultAdminUser(PDO $pdo): void
    {
        if (! self::hasTable($pdo, 'users')) {
            return;
        }

        $statement = $pdo->query('SELECT COUNT(*) FROM users');
        if ((int) $statement->fetchColumn() > 0) {
            return;
        }

        $now = date('c');
        $insert = $pdo->prepare(
            'INSERT INTO users (login, password_hash, name, email, role, is_active, created_at, updated_at)
             VALUES (:login, :password_hash, :name, :email, :role, :is_active, :created_at, :updated_at)'
        );
        $insert->execute([
            'login' => 'admin',
            'password_hash' => password_hash('admin123', PASSWORD_DEFAULT),
            'name' => 'Administrator',
            'email' => 'admin@example.test',
            'role' => 'administrator',
            'is_active' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    private static function seed(PDO $pdo, string $sourceContentPath): void
    {
        $sourceContent = file_get_contents($sourceContentPath);

        if ($sourceContent === false) {
            throw new RuntimeException('Unable to read seeded main page content.');
        }

        $now = date('c');
        $pdo->beginTransaction();

        self::insertPage($pdo, [
            'slug' => 'home',
            'title' => 'Home',
            'content' => $sourceContent,
            'keywords' => 'home, jencms, php',
            'description' => 'Default homepage for a new JenCMS project.',
            'is_published' => 1,
            'sort_order' => 0,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        self::insertSection($pdo, [
            'slug' => 'news',
            'title' => 'News',
            'description' => 'Latest posts.',
            'posts_per_page' => 6,
            'is_published' => 1,
            'sort_order' => 0,
            'created_at' => $now,
            'updated_at' => $now,
        ], $now);

        $pdo->commit();
    }

    private static function insertPage(PDO $pdo, array $page): void
    {
        $statement = $pdo->prepare(
            'INSERT INTO pages (slug, title, content, keywords, description, is_published, sort_order, created_at, updated_at)
             VALUES (:slug, :title, :content, :keywords, :description, :is_published, :sort_order, :created_at, :updated_at)'
        );
        $statement->execute($page);
    }

    private static function insertSection(PDO $pdo, array $section, string $now): void
    {
        $statement = $pdo->prepare(
            'INSERT INTO sections (slug, title, description, posts_per_page, is_published, sort_order, created_at, updated_at)
             VALUES (:slug, :title, :description, :posts_per_page, :is_published, :sort_order, :created_at, :updated_at)'
        );
        $statement->execute($section);
        $sectionId = (int) $pdo->lastInsertId();

        $categoryStatement = $pdo->prepare(
            'INSERT INTO categories (section_id, slug, title, description, sort_order, created_at, updated_at)
             VALUES (:section_id, :slug, :title, :description, :sort_order, :created_at, :updated_at)'
        );
        $categoryStatement->execute([
            'section_id' => $sectionId,
            'slug' => 'general',
            'title' => 'General',
            'description' => 'Default category.',
            'sort_order' => 0,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $categoryId = (int) $pdo->lastInsertId();

        $postStatement = $pdo->prepare(
            'INSERT INTO posts (section_id, category_id, slug, title, excerpt, content, keywords, description, image, is_published, published_at, created_at, updated_at)
             VALUES (:section_id, :category_id, :slug, :title, :excerpt, :content, :keywords, :description, :image, :is_published, :published_at, :created_at, :updated_at)'
        );

        for ($index = 1; $index <= 3; $index++) {
            $postStatement->execute([
                'section_id' => $sectionId,
                'category_id' => $categoryId,
                'slug' => 'sample-post-' . $index,
                'title' => 'Sample Post ' . $index,
                'excerpt' => 'This is a starter post that ships with a fresh JenCMS install.',
                'content' => '<div class="site-container"><section class="demo-content-section"><h1>Sample Post ' . $index . '</h1><p>Edit or delete this sample content from the admin panel.</p></section></div>',
                'keywords' => 'sample, jencms',
                'description' => 'Starter content for a fresh JenCMS installation.',
                'image' => '',
                'is_published' => 1,
                'published_at' => date('c', strtotime('-' . $index . ' days')),
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }
}
