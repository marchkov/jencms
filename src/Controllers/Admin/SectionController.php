<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Repositories\AdminSectionRepository;

final class SectionController
{
    public function __construct(
        private readonly AdminSectionRepository $sections,
        private readonly array $config
    ) {
    }

    public function index(): void
    {
        require_admin_role($this->config, ['administrator']);

        $filters = $this->listFilters();

        render_admin_view($this->config, 'sections/index', [
            'title' => 'Sections',
            'sections' => $this->sections->all($filters),
            'filters' => $filters,
        ]);
    }

    public function create(): void
    {
        require_admin_role($this->config, ['administrator']);

        render_admin_view($this->config, 'sections/form', [
            'title' => 'New Section',
            'formAction' => admin_path($this->config, 'sections/create'),
            'section' => $this->emptySection(),
            'errors' => [],
            'submitLabel' => 'Create section',
        ]);
    }

    public function store(): void
    {
        require_admin_role($this->config, ['administrator']);

        [$section, $errors] = $this->validateForm();

        if ($errors !== []) {
            render_admin_view($this->config, 'sections/form', [
                'title' => 'New Section',
                'formAction' => admin_path($this->config, 'sections/create'),
                'section' => $section,
                'errors' => $errors,
                'submitLabel' => 'Create section',
            ]);
            return;
        }

        $now = date('c');
        $this->sections->create([
            'slug' => $section['slug'],
            'title' => $section['title'],
            'description' => $section['description'],
            'posts_per_page' => $section['posts_per_page'],
            'is_published' => $section['is_published'],
            'sort_order' => $section['sort_order'],
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        flash('success', 'Section created.');
        redirect(admin_path($this->config, 'sections'));
    }

    public function edit(int $id): void
    {
        require_admin_role($this->config, ['administrator']);

        $section = $this->sections->findById($id);
        if ($section === null) {
            flash('error', 'Section not found.');
            redirect(admin_path($this->config, 'sections'));
        }

        render_admin_view($this->config, 'sections/form', [
            'title' => 'Edit Section',
            'formAction' => admin_path($this->config, 'sections/' . $id . '/edit'),
            'section' => $section,
            'errors' => [],
            'submitLabel' => 'Save changes',
        ]);
    }

    public function update(int $id): void
    {
        require_admin_role($this->config, ['administrator']);

        if ($this->sections->findById($id) === null) {
            flash('error', 'Section not found.');
            redirect(admin_path($this->config, 'sections'));
        }

        [$section, $errors] = $this->validateForm($id);

        if ($errors !== []) {
            $section['id'] = $id;
            render_admin_view($this->config, 'sections/form', [
                'title' => 'Edit Section',
                'formAction' => admin_path($this->config, 'sections/' . $id . '/edit'),
                'section' => $section,
                'errors' => $errors,
                'submitLabel' => 'Save changes',
            ]);
            return;
        }

        $this->sections->update($id, [
            'slug' => $section['slug'],
            'title' => $section['title'],
            'description' => $section['description'],
            'posts_per_page' => $section['posts_per_page'],
            'is_published' => $section['is_published'],
            'sort_order' => $section['sort_order'],
            'updated_at' => date('c'),
        ]);

        flash('success', 'Changes saved.');
        redirect(admin_path($this->config, 'sections'));
    }

    public function delete(int $id): void
    {
        require_admin_role($this->config, ['administrator']);

        if (! verify_csrf_token($_POST['_csrf'] ?? null)) {
            flash('error', 'Unable to confirm deletion.');
            redirect(admin_path($this->config, 'sections'));
        }

        $this->sections->delete($id);
        flash('success', 'Section deleted.');
        redirect(admin_path($this->config, 'sections'));
    }

    private function validateForm(?int $sectionId = null): array
    {
        $section = [
            'id' => $sectionId,
            'slug' => trim((string) ($_POST['slug'] ?? '')),
            'title' => trim((string) ($_POST['title'] ?? '')),
            'description' => trim((string) ($_POST['description'] ?? '')),
            'posts_per_page' => $this->normalizePostsPerPage((int) ($_POST['posts_per_page'] ?? 6)),
            'is_published' => isset($_POST['is_published']) ? 1 : 0,
            'sort_order' => (int) ($_POST['sort_order'] ?? 0),
        ];
        $errors = [];

        if (! verify_csrf_token($_POST['_csrf'] ?? null)) {
            $errors[] = 'Your session has expired. Refresh the page and try again.';
        }

        if ($section['slug'] === '') {
            $errors[] = 'Slug is required.';
        } elseif (! preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $section['slug'])) {
            $errors[] = 'Slug may only contain lowercase Latin letters, numbers, and hyphens.';
        } elseif ($this->sections->slugExists($section['slug'], $sectionId)) {
            $errors[] = 'That slug is already used by a section or a page.';
        }

        if ($section['title'] === '') {
            $errors[] = 'Title is required.';
        }

        return [$section, $errors];
    }

    private function emptySection(): array
    {
        return [
            'slug' => '',
            'title' => '',
            'description' => '',
            'posts_per_page' => $this->normalizePostsPerPage((int) $this->config['content']['posts_per_page']),
            'is_published' => 1,
            'sort_order' => 0,
        ];
    }

    private function listFilters(): array
    {
        return [
            'q' => trim((string) ($_GET['q'] ?? '')),
            'status' => in_array((string) ($_GET['status'] ?? ''), ['published', 'hidden'], true) ? (string) $_GET['status'] : '',
        ];
    }

    private function normalizePostsPerPage(int $value): int
    {
        $value = max(1, $value);

        return (int) (ceil($value / 6) * 6);
    }
}
