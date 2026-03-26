<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

final class AdminCategoryRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function all(array $filters = []): array
    {
        $sql = 'SELECT categories.*, sections.title AS section_title
             FROM categories
             LEFT JOIN sections ON sections.id = categories.section_id';
        $params = [];
        $conditions = [];

        $query = trim((string) ($filters['q'] ?? ''));
        if ($query !== '') {
            $conditions[] = '(categories.slug LIKE :query OR categories.title LIKE :query OR sections.title LIKE :query)';
            $params['query'] = '%' . $query . '%';
        }

        $sectionId = (int) ($filters['section_id'] ?? 0);
        if ($sectionId > 0) {
            $conditions[] = 'categories.section_id = :section_id';
            $params['section_id'] = $sectionId;
        }

        if ($conditions !== []) {
            $sql .= ' WHERE ' . implode(' AND ', $conditions);
        }

        $sql .= ' ORDER BY sections.title ASC, categories.sort_order ASC, categories.id ASC';

        $statement = $this->pdo->prepare($sql);
        $statement->execute($params);

        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findById(int $id): ?array
    {
        $statement = $this->pdo->prepare('SELECT * FROM categories WHERE id = :id LIMIT 1');
        $statement->execute(['id' => $id]);
        $category = $statement->fetch(PDO::FETCH_ASSOC);

        return $category ?: null;
    }

    public function slugExistsInSection(int $sectionId, string $slug, ?int $exceptId = null): bool
    {
        $sql = 'SELECT COUNT(*) FROM categories WHERE section_id = :section_id AND slug = :slug';
        $params = ['section_id' => $sectionId, 'slug' => $slug];

        if ($exceptId !== null) {
            $sql .= ' AND id != :id';
            $params['id'] = $exceptId;
        }

        $statement = $this->pdo->prepare($sql);
        $statement->execute($params);

        return (int) $statement->fetchColumn() > 0;
    }

    public function create(array $category): int
    {
        $statement = $this->pdo->prepare(
            'INSERT INTO categories (section_id, slug, title, description, sort_order, created_at, updated_at)
             VALUES (:section_id, :slug, :title, :description, :sort_order, :created_at, :updated_at)'
        );
        $statement->execute($category);

        return (int) $this->pdo->lastInsertId();
    }

    public function update(int $id, array $category): void
    {
        $category['id'] = $id;
        $statement = $this->pdo->prepare(
            'UPDATE categories
                SET section_id = :section_id,
                    slug = :slug,
                    title = :title,
                    description = :description,
                    sort_order = :sort_order,
                    updated_at = :updated_at
              WHERE id = :id'
        );
        $statement->execute($category);
    }

    public function delete(int $id): void
    {
        $statement = $this->pdo->prepare('DELETE FROM categories WHERE id = :id');
        $statement->execute(['id' => $id]);
    }

    public function options(): array
    {
        $statement = $this->pdo->query(
            'SELECT categories.id, categories.section_id, categories.slug, categories.title, sections.title AS section_title
             FROM categories
             LEFT JOIN sections ON sections.id = categories.section_id
             ORDER BY sections.title ASC, categories.id ASC'
        );

        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }
}
