<?php

namespace App\Repositories;

use PDO;

final class PageRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function findPublishedBySlug(string $slug): ?array
    {
        $statement = $this->pdo->prepare('SELECT * FROM pages WHERE slug = :slug AND is_published = 1 LIMIT 1');
        $statement->execute(['slug' => $slug]);
        $page = $statement->fetch(PDO::FETCH_ASSOC);

        return $page ?: null;
    }
}
