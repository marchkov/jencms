<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

final class AdminSectionRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function all(array $filters = []): array
    {
        $sql = 'SELECT * FROM sections';
        $params = [];
        $conditions = [];

        $query = trim((string) ($filters['q'] ?? ''));
        if ($query !== '') {
            $conditions[] = '(slug LIKE :query OR title LIKE :query)';
            $params['query'] = '%' . $query . '%';
        }

        $status = (string) ($filters['status'] ?? '');
        if ($status === 'published') {
            $conditions[] = 'is_published = 1';
        } elseif ($status === 'hidden') {
            $conditions[] = 'is_published = 0';
        }

        if ($conditions !== []) {
            $sql .= ' WHERE ' . implode(' AND ', $conditions);
        }

        $sql .= ' ORDER BY sort_order ASC, id ASC';

        $statement = $this->pdo->prepare($sql);
        $statement->execute($params);

        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findById(int $id): ?array
    {
        $statement = $this->pdo->prepare('SELECT * FROM sections WHERE id = :id LIMIT 1');
        $statement->execute(['id' => $id]);
        $section = $statement->fetch(PDO::FETCH_ASSOC);

        return $section ?: null;
    }

    public function slugExists(string $slug, ?int $exceptId = null): bool
    {
        $sectionSql = 'SELECT COUNT(*) FROM sections WHERE slug = :slug';
        $params = ['slug' => $slug];

        if ($exceptId !== null) {
            $sectionSql .= ' AND id != :id';
            $params['id'] = $exceptId;
        }

        $statement = $this->pdo->prepare($sectionSql);
        $statement->execute($params);
        if ((int) $statement->fetchColumn() > 0) {
            return true;
        }

        $pageStatement = $this->pdo->prepare('SELECT COUNT(*) FROM pages WHERE slug = :slug');
        $pageStatement->execute(['slug' => $slug]);

        return (int) $pageStatement->fetchColumn() > 0;
    }

    public function create(array $section): int
    {
        $statement = $this->pdo->prepare(
            'INSERT INTO sections (slug, title, description, posts_per_page, is_published, sort_order, created_at, updated_at)
             VALUES (:slug, :title, :description, :posts_per_page, :is_published, :sort_order, :created_at, :updated_at)'
        );
        $statement->execute($section);

        return (int) $this->pdo->lastInsertId();
    }

    public function update(int $id, array $section): void
    {
        $section['id'] = $id;
        $statement = $this->pdo->prepare(
            'UPDATE sections
                SET slug = :slug,
                    title = :title,
                    description = :description,
                    posts_per_page = :posts_per_page,
                    is_published = :is_published,
                    sort_order = :sort_order,
                    updated_at = :updated_at
              WHERE id = :id'
        );
        $statement->execute($section);
    }

    public function delete(int $id): void
    {
        $statement = $this->pdo->prepare('DELETE FROM sections WHERE id = :id');
        $statement->execute(['id' => $id]);
    }

    public function options(): array
    {
        $statement = $this->pdo->query('SELECT id, slug, title FROM sections ORDER BY sort_order ASC, id ASC');

        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }
}
