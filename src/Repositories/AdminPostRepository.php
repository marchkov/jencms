<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

final class AdminPostRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function all(array $filters = []): array
    {
        $sql = 'SELECT posts.*,
                    sections.title AS section_title,
                    categories.title AS category_title
             FROM posts
             LEFT JOIN sections ON sections.id = posts.section_id
             LEFT JOIN categories ON categories.id = posts.category_id';
        $params = [];
        $conditions = [];

        $query = trim((string) ($filters['q'] ?? ''));
        if ($query !== '') {
            $conditions[] = '(posts.slug LIKE :query OR posts.title LIKE :query OR sections.title LIKE :query)';
            $params['query'] = '%' . $query . '%';
        }

        $status = (string) ($filters['status'] ?? '');
        if ($status === 'published') {
            $conditions[] = 'posts.is_published = 1';
        } elseif ($status === 'hidden') {
            $conditions[] = 'posts.is_published = 0';
        }

        $sectionId = (int) ($filters['section_id'] ?? 0);
        if ($sectionId > 0) {
            $conditions[] = 'posts.section_id = :section_id';
            $params['section_id'] = $sectionId;
        }

        if ($conditions !== []) {
            $sql .= ' WHERE ' . implode(' AND ', $conditions);
        }

        $sql .= ' ORDER BY COALESCE(posts.published_at, posts.created_at) DESC, posts.id DESC';

        $statement = $this->pdo->prepare($sql);
        $statement->execute($params);

        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findById(int $id): ?array
    {
        $statement = $this->pdo->prepare('SELECT * FROM posts WHERE id = :id LIMIT 1');
        $statement->execute(['id' => $id]);
        $post = $statement->fetch(PDO::FETCH_ASSOC);

        return $post ?: null;
    }

    public function slugExistsInSection(int $sectionId, string $slug, ?int $exceptId = null): bool
    {
        $sql = 'SELECT COUNT(*) FROM posts WHERE section_id = :section_id AND slug = :slug';
        $params = ['section_id' => $sectionId, 'slug' => $slug];

        if ($exceptId !== null) {
            $sql .= ' AND id != :id';
            $params['id'] = $exceptId;
        }

        $statement = $this->pdo->prepare($sql);
        $statement->execute($params);

        return (int) $statement->fetchColumn() > 0;
    }

    public function create(array $post): int
    {
        $statement = $this->pdo->prepare(
            'INSERT INTO posts (section_id, category_id, slug, title, excerpt, content, keywords, description, image, is_published, published_at, created_at, updated_at)
             VALUES (:section_id, :category_id, :slug, :title, :excerpt, :content, :keywords, :description, :image, :is_published, :published_at, :created_at, :updated_at)'
        );
        $statement->execute($post);

        return (int) $this->pdo->lastInsertId();
    }

    public function update(int $id, array $post): void
    {
        $post['id'] = $id;
        $statement = $this->pdo->prepare(
            'UPDATE posts
                SET section_id = :section_id,
                    category_id = :category_id,
                    slug = :slug,
                    title = :title,
                    excerpt = :excerpt,
                    content = :content,
                    keywords = :keywords,
                    description = :description,
                    image = :image,
                    is_published = :is_published,
                    published_at = :published_at,
                    updated_at = :updated_at
              WHERE id = :id'
        );
        $statement->execute($post);
    }

    public function delete(int $id): void
    {
        $statement = $this->pdo->prepare('DELETE FROM posts WHERE id = :id');
        $statement->execute(['id' => $id]);
    }
}
