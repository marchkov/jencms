<?php

namespace App\Repositories;

use PDO;

final class SectionRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function findPublishedBySlug(string $slug): ?array
    {
        $statement = $this->pdo->prepare('SELECT * FROM sections WHERE slug = :slug AND is_published = 1 LIMIT 1');
        $statement->execute(['slug' => $slug]);
        $section = $statement->fetch(PDO::FETCH_ASSOC);

        return $section ?: null;
    }
}
