<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Repositories\AdminCategoryRepository;
use App\Repositories\AdminSectionRepository;

final class CategoryController
{
    public function __construct(
        private readonly AdminCategoryRepository $categories,
        private readonly AdminSectionRepository $sections,
        private readonly array $config
    ) {
    }

    public function index(): void
    {
        require_admin_role($this->config, ['administrator', 'editor']);

        $filters = $this->listFilters();

        render_admin_view($this->config, 'categories/index', [
            'title' => 'Categories',
            'categories' => $this->categories->all($filters),
            'filters' => $filters,
            'sections' => $this->sections->options(),
        ]);
    }

    public function create(): void
    {
        require_admin_role($this->config, ['administrator', 'editor']);

        render_admin_view($this->config, 'categories/form', [
            'title' => 'New Category',
            'formAction' => admin_path($this->config, 'categories/create'),
            'category' => $this->emptyCategory(),
            'sections' => $this->sections->options(),
            'errors' => [],
            'submitLabel' => 'Create category',
        ]);
    }

    public function store(): void
    {
        require_admin_role($this->config, ['administrator', 'editor']);

        [$category, $errors] = $this->validateForm();

        if ($errors !== []) {
            render_admin_view($this->config, 'categories/form', [
                'title' => 'New Category',
                'formAction' => admin_path($this->config, 'categories/create'),
                'category' => $category,
                'sections' => $this->sections->options(),
                'errors' => $errors,
                'submitLabel' => 'Create category',
            ]);
            return;
        }

        $now = date('c');
        $this->categories->create([
            'section_id' => $category['section_id'],
            'slug' => $category['slug'],
            'title' => $category['title'],
            'description' => $category['description'],
            'sort_order' => $category['sort_order'],
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        flash('success', 'Category created.');
        redirect(admin_path($this->config, 'categories'));
    }

    public function edit(int $id): void
    {
        require_admin_role($this->config, ['administrator', 'editor']);

        $category = $this->categories->findById($id);
        if ($category === null) {
            flash('error', 'Category not found.');
            redirect(admin_path($this->config, 'categories'));
        }

        render_admin_view($this->config, 'categories/form', [
            'title' => 'Edit Category',
            'formAction' => admin_path($this->config, 'categories/' . $id . '/edit'),
            'category' => $category,
            'sections' => $this->sections->options(),
            'errors' => [],
            'submitLabel' => 'Save changes',
        ]);
    }

    public function update(int $id): void
    {
        require_admin_role($this->config, ['administrator', 'editor']);

        if ($this->categories->findById($id) === null) {
            flash('error', 'Category not found.');
            redirect(admin_path($this->config, 'categories'));
        }

        [$category, $errors] = $this->validateForm($id);

        if ($errors !== []) {
            $category['id'] = $id;
            render_admin_view($this->config, 'categories/form', [
                'title' => 'Edit Category',
                'formAction' => admin_path($this->config, 'categories/' . $id . '/edit'),
                'category' => $category,
                'sections' => $this->sections->options(),
                'errors' => $errors,
                'submitLabel' => 'Save changes',
            ]);
            return;
        }

        $this->categories->update($id, [
            'section_id' => $category['section_id'],
            'slug' => $category['slug'],
            'title' => $category['title'],
            'description' => $category['description'],
            'sort_order' => $category['sort_order'],
            'updated_at' => date('c'),
        ]);

        flash('success', 'Changes saved.');
        redirect(admin_path($this->config, 'categories'));
    }

    public function delete(int $id): void
    {
        require_admin_role($this->config, ['administrator', 'editor']);

        if (! verify_csrf_token($_POST['_csrf'] ?? null)) {
            flash('error', 'Unable to confirm deletion.');
            redirect(admin_path($this->config, 'categories'));
        }

        $this->categories->delete($id);
        flash('success', 'Category deleted.');
        redirect(admin_path($this->config, 'categories'));
    }

    private function validateForm(?int $categoryId = null): array
    {
        $category = [
            'id' => $categoryId,
            'section_id' => (int) ($_POST['section_id'] ?? 0),
            'slug' => trim((string) ($_POST['slug'] ?? '')),
            'title' => trim((string) ($_POST['title'] ?? '')),
            'description' => trim((string) ($_POST['description'] ?? '')),
            'sort_order' => (int) ($_POST['sort_order'] ?? 0),
        ];
        $errors = [];

        if (! verify_csrf_token($_POST['_csrf'] ?? null)) {
            $errors[] = 'Your session has expired. Refresh the page and try again.';
        }

        if ($category['section_id'] < 1) {
            $errors[] = 'Choose a section.';
        }

        if ($category['slug'] === '') {
            $errors[] = 'Slug is required.';
        } elseif (! preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $category['slug'])) {
            $errors[] = 'Slug may only contain lowercase Latin letters, numbers, and hyphens.';
        } elseif ($category['section_id'] > 0 && $this->categories->slugExistsInSection($category['section_id'], $category['slug'], $categoryId)) {
            $errors[] = 'That slug is already used inside the selected section.';
        }

        if ($category['title'] === '') {
            $errors[] = 'Title is required.';
        }

        return [$category, $errors];
    }

    private function emptyCategory(): array
    {
        return [
            'section_id' => 0,
            'slug' => '',
            'title' => '',
            'description' => '',
            'sort_order' => 0,
        ];
    }

    private function listFilters(): array
    {
        return [
            'q' => trim((string) ($_GET['q'] ?? '')),
            'section_id' => max(0, (int) ($_GET['section_id'] ?? 0)),
        ];
    }
}
