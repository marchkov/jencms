<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Repositories\AdminPageRepository;

final class PageController
{
    public function __construct(
        private readonly AdminPageRepository $pages,
        private readonly array $config
    ) {
    }

    public function index(): void
    {
        require_admin_role($this->config, ['administrator', 'editor']);

        $filters = $this->listFilters();

        render_admin_view($this->config, 'pages/index', [
            'title' => 'Pages',
            'pages' => $this->pages->all($filters),
            'filters' => $filters,
        ]);
    }

    public function create(): void
    {
        require_admin_role($this->config, ['administrator', 'editor']);

        render_admin_view($this->config, 'pages/form', [
            'title' => 'New Page',
            'formAction' => admin_path($this->config, 'pages/create'),
            'page' => $this->emptyPage(),
            'errors' => [],
            'submitLabel' => 'Create page',
            'mediaLibraryUrl' => admin_path($this->config, 'media'),
        ]);
    }

    public function store(): void
    {
        require_admin_role($this->config, ['administrator', 'editor']);

        [$page, $errors] = $this->validateForm();

        if ($errors !== []) {
            render_admin_view($this->config, 'pages/form', [
                'title' => 'New Page',
                'formAction' => admin_path($this->config, 'pages/create'),
                'page' => $page,
                'errors' => $errors,
                'submitLabel' => 'Create page',
                'mediaLibraryUrl' => admin_path($this->config, 'media'),
            ]);
            return;
        }

        $now = date('c');
        $this->pages->create([
            'slug' => $page['slug'],
            'title' => $page['title'],
            'content' => $page['content'],
            'keywords' => $page['keywords'],
            'description' => $page['description'],
            'is_published' => $page['is_published'],
            'sort_order' => $page['sort_order'],
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        flash('success', 'Page created.');
        redirect(admin_path($this->config, 'pages'));
    }

    public function edit(int $id): void
    {
        require_admin_role($this->config, ['administrator', 'editor']);

        $page = $this->pages->findById($id);
        if ($page === null) {
            flash('error', 'Page not found.');
            redirect(admin_path($this->config, 'pages'));
        }

        render_admin_view($this->config, 'pages/form', [
            'title' => 'Edit Page',
            'formAction' => admin_path($this->config, 'pages/' . $id . '/edit'),
            'page' => $page,
            'errors' => [],
            'submitLabel' => 'Save changes',
            'mediaLibraryUrl' => admin_path($this->config, 'media'),
        ]);
    }

    public function update(int $id): void
    {
        require_admin_role($this->config, ['administrator', 'editor']);

        if ($this->pages->findById($id) === null) {
            flash('error', 'Page not found.');
            redirect(admin_path($this->config, 'pages'));
        }

        [$page, $errors] = $this->validateForm($id);

        if ($errors !== []) {
            $page['id'] = $id;
            render_admin_view($this->config, 'pages/form', [
                'title' => 'Edit Page',
                'formAction' => admin_path($this->config, 'pages/' . $id . '/edit'),
                'page' => $page,
                'errors' => $errors,
                'submitLabel' => 'Save changes',
                'mediaLibraryUrl' => admin_path($this->config, 'media'),
            ]);
            return;
        }

        $this->pages->update($id, [
            'slug' => $page['slug'],
            'title' => $page['title'],
            'content' => $page['content'],
            'keywords' => $page['keywords'],
            'description' => $page['description'],
            'is_published' => $page['is_published'],
            'sort_order' => $page['sort_order'],
            'updated_at' => date('c'),
        ]);

        flash('success', 'Changes saved.');
        redirect(admin_path($this->config, 'pages'));
    }

    public function delete(int $id): void
    {
        require_admin_role($this->config, ['administrator', 'editor']);

        if (! verify_csrf_token($_POST['_csrf'] ?? null)) {
            flash('error', 'Unable to confirm deletion.');
            redirect(admin_path($this->config, 'pages'));
        }

        $this->pages->delete($id);
        flash('success', 'Page deleted.');
        redirect(admin_path($this->config, 'pages'));
    }

    private function validateForm(?int $pageId = null): array
    {
        $page = [
            'id' => $pageId,
            'slug' => trim((string) ($_POST['slug'] ?? '')),
            'title' => trim((string) ($_POST['title'] ?? '')),
            'content' => trim((string) ($_POST['content'] ?? '')),
            'keywords' => trim((string) ($_POST['keywords'] ?? '')),
            'description' => trim((string) ($_POST['description'] ?? '')),
            'is_published' => isset($_POST['is_published']) ? 1 : 0,
            'sort_order' => (int) ($_POST['sort_order'] ?? 0),
        ];
        $errors = [];

        if (! verify_csrf_token($_POST['_csrf'] ?? null)) {
            $errors[] = 'Your session has expired. Refresh the page and try again.';
        }

        if ($page['slug'] === '') {
            $errors[] = 'Slug is required.';
        } elseif (! preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $page['slug'])) {
            $errors[] = 'Slug may only contain lowercase Latin letters, numbers, and hyphens.';
        } elseif ($this->pages->slugExists($page['slug'], $pageId)) {
            $errors[] = 'That slug is already used by a page or a section.';
        }

        if ($page['title'] === '') {
            $errors[] = 'Title is required.';
        }

        if ($page['content'] === '') {
            $errors[] = 'Content is required.';
        }

        return [$page, $errors];
    }

    private function emptyPage(): array
    {
        return [
            'slug' => '',
            'title' => '',
            'content' => '',
            'keywords' => '',
            'description' => '',
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
}
