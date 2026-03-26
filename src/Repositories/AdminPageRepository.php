<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

final class AdminPageRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function all(array $filters = []): array
    {
        $sql = 'SELECT * FROM pages';
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
        $statement = $this->pdo->prepare('SELECT * FROM pages WHERE id = :id LIMIT 1');
        $statement->execute(['id' => $id]);
        $page = $statement->fetch(PDO::FETCH_ASSOC);

        return $page ?: null;
    }

    public function slugExists(string $slug, ?int $exceptId = null): bool
    {
        $pageSql = 'SELECT COUNT(*) FROM pages WHERE slug = :slug';
        $pageParams = ['slug' => $slug];

        if ($exceptId !== null) {
            $pageSql .= ' AND id != :id';
            $pageParams['id'] = $exceptId;
        }

        $pageStatement = $this->pdo->prepare($pageSql);
        $pageStatement->execute($pageParams);
        if ((int) $pageStatement->fetchColumn() > 0) {
            return true;
        }

        $sectionStatement = $this->pdo->prepare('SELECT COUNT(*) FROM sections WHERE slug = :slug');
        $sectionStatement->execute(['slug' => $slug]);

        return (int) $sectionStatement->fetchColumn() > 0;
    }

    public function create(array $page): int
    {
        $statement = $this->pdo->prepare(
            'INSERT INTO pages (slug, title, content, keywords, description, is_published, sort_order, created_at, updated_at)
             VALUES (:slug, :title, :content, :keywords, :description, :is_published, :sort_order, :created_at, :updated_at)'
        );
        $statement->execute($page);

        return (int) $this->pdo->lastInsertId();
    }

    public function update(int $id, array $page): void
    {
        $page['id'] = $id;
        $statement = $this->pdo->prepare(
            'UPDATE pages
                SET slug = :slug,
                    title = :title,
                    content = :content,
                    keywords = :keywords,
                    description = :description,
                    is_published = :is_published,
                    sort_order = :sort_order,
                    updated_at = :updated_at
              WHERE id = :id'
        );
        $statement->execute($page);
    }

    public function delete(int $id): void
    {
        $statement = $this->pdo->prepare('DELETE FROM pages WHERE id = :id');
        $statement->execute(['id' => $id]);
    }

    public function options(): array
    {
        $statement = $this->pdo->query('SELECT id, slug, title FROM pages ORDER BY sort_order ASC, id ASC');

        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }
}
