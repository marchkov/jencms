<?php

declare(strict_types=1);

use App\Repositories\AdminPageRepository;

define('BASE_PATH', dirname(__DIR__));
require BASE_PATH . '/src/Support/autoload.php';

$pdo = new PDO('sqlite::memory:');
$schema = file_get_contents(BASE_PATH . '/storage/migrations/001_init.sql');
if ($schema === false) {
    throw new RuntimeException('Unable to load the test schema.');
}
$pdo->exec($schema);

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
