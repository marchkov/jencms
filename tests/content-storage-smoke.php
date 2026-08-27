<?php

declare(strict_types=1);

use App\Database\Migrator;
use App\Repositories\AdminPageRepository;

define('BASE_PATH', dirname(__DIR__));
require BASE_PATH . '/src/Support/autoload.php';

$pdo = new PDO('sqlite::memory:');
Migrator::migrate($pdo, BASE_PATH . '/storage/migrations');

$repository = new AdminPageRepository($pdo);
$rawHtml = '<section data-component="hero"><div class="slider"><custom-card data-id="7">Text</custom-card></div></section>';
$now = date('c');
$id = $repository->create([
    'slug' => 'raw-html-test',
    'title' => 'Raw HTML test',
    'content' => $rawHtml,
    'keywords' => '',
    'description' => '',
    'is_published' => 1,
    'sort_order' => 0,
    'created_at' => $now,
    'updated_at' => $now,
]);

$saved = $repository->findById($id);
if (($saved['content'] ?? null) !== $rawHtml) {
    throw new RuntimeException('Raw page HTML was normalized or changed during storage.');
}

echo "PASS raw page HTML preservation\n";
