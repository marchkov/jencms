<?php

namespace App\Repositories;

use PDO;

final class PostRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function countPublishedBySectionId(int $sectionId): int
    {
        $statement = $this->pdo->prepare('SELECT COUNT(*) FROM posts WHERE section_id = :section_id AND is_published = 1');
        $statement->execute(['section_id' => $sectionId]);

        return (int) $statement->fetchColumn();
    }

    public function findPublishedBySectionId(int $sectionId, int $limit, int $offset): array
    {
        $statement = $this->pdo->prepare(
            'SELECT * FROM posts
             WHERE section_id = :section_id AND is_published = 1
             ORDER BY COALESCE(published_at, created_at) DESC, id DESC
             LIMIT :limit OFFSET :offset'
        );
        $statement->bindValue(':section_id', $sectionId, PDO::PARAM_INT);
        $statement->bindValue(':limit', $limit, PDO::PARAM_INT);
        $statement->bindValue(':offset', $offset, PDO::PARAM_INT);
        $statement->execute();

        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findPublishedBySectionAndSlug(int $sectionId, string $slug): ?array
    {
        $statement = $this->pdo->prepare('SELECT * FROM posts WHERE section_id = :section_id AND slug = :slug AND is_published = 1 LIMIT 1');
        $statement->execute(['section_id' => $sectionId, 'slug' => $slug]);
        $post = $statement->fetch(PDO::FETCH_ASSOC);

        return $post ?: null;
    }
}
