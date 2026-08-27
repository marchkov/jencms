<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Repositories\AdminCategoryRepository;
use App\Repositories\AdminPostRepository;
use App\Repositories\AdminSectionRepository;
use App\Repositories\MediaRepository;
use RuntimeException;

final class PostController
{
    public function __construct(
        private readonly AdminPostRepository $posts,
        private readonly AdminSectionRepository $sections,
        private readonly AdminCategoryRepository $categories,
        private readonly MediaRepository $media,
        private readonly array $config
    ) {
    }

    public function index(): void
    {
        require_admin_role($this->config, ['administrator', 'editor']);

        $filters = $this->listFilters();

        render_admin_view($this->config, 'posts/index', [
            'title' => 'Posts',
            'posts' => $this->posts->all($filters),
            'filters' => $filters,
            'sections' => $this->sections->options(),
        ]);
    }

    public function create(): void
    {
        require_admin_role($this->config, ['administrator', 'editor']);

        render_admin_view($this->config, 'posts/form', [
            'title' => 'New Post',
            'formAction' => admin_path($this->config, 'posts/create'),
            'post' => $this->emptyPost(),
            'errors' => [],
            'submitLabel' => 'Create post',
            'sections' => $this->sections->options(),
            'categories' => $this->categories->options(),
            'mediaUploadUrl' => admin_path($this->config, 'media/upload'),
            'mediaImages' => $this->mediaImages(),
        ]);
    }

    public function store(): void
    {
        require_admin_role($this->config, ['administrator', 'editor']);

        [$post, $errors] = $this->validateForm();

        if ($errors !== []) {
            render_admin_view($this->config, 'posts/form', [
                'title' => 'New Post',
                'formAction' => admin_path($this->config, 'posts/create'),
                'post' => $post,
                'errors' => $errors,
                'submitLabel' => 'Create post',
                'sections' => $this->sections->options(),
                'categories' => $this->categories->options(),
                'mediaUploadUrl' => admin_path($this->config, 'media/upload'),
                'mediaImages' => $this->mediaImages(),
            ]);
            return;
        }

        $now = date('c');
        $this->posts->create([
            'section_id' => $post['section_id'],
            'category_id' => $post['category_id'],
            'slug' => $post['slug'],
            'title' => $post['title'],
            'excerpt' => $post['excerpt'],
            'content' => $post['content'],
            'keywords' => $post['keywords'],
            'description' => $post['description'],
            'image' => $post['image'],
            'is_published' => $post['is_published'],
            'published_at' => $post['published_at'],
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        flash('success', 'Post created.');
        redirect(admin_path($this->config, 'posts'));
    }

    public function edit(int $id): void
    {
        require_admin_role($this->config, ['administrator', 'editor']);

        $post = $this->posts->findById($id);
        if ($post === null) {
            flash('error', 'Post not found.');
            redirect(admin_path($this->config, 'posts'));
        }

        $post['published_at'] = $this->formatPublishedAtForInput((string) ($post['published_at'] ?? ''));

        render_admin_view($this->config, 'posts/form', [
            'title' => 'Edit Post',
            'formAction' => admin_path($this->config, 'posts/' . $id . '/edit'),
            'post' => $post,
            'errors' => [],
            'submitLabel' => 'Save changes',
            'sections' => $this->sections->options(),
            'categories' => $this->categories->options(),
            'mediaUploadUrl' => admin_path($this->config, 'media/upload'),
            'mediaImages' => $this->mediaImages(),
        ]);
    }

    public function update(int $id): void
    {
        require_admin_role($this->config, ['administrator', 'editor']);

        if ($this->posts->findById($id) === null) {
            flash('error', 'Post not found.');
            redirect(admin_path($this->config, 'posts'));
        }

        [$post, $errors] = $this->validateForm($id);

        if ($errors !== []) {
            $post['id'] = $id;
            render_admin_view($this->config, 'posts/form', [
                'title' => 'Edit Post',
                'formAction' => admin_path($this->config, 'posts/' . $id . '/edit'),
                'post' => $post,
                'errors' => $errors,
                'submitLabel' => 'Save changes',
                'sections' => $this->sections->options(),
                'categories' => $this->categories->options(),
                'mediaUploadUrl' => admin_path($this->config, 'media/upload'),
                'mediaImages' => $this->mediaImages(),
            ]);
            return;
        }

        $this->posts->update($id, [
            'section_id' => $post['section_id'],
            'category_id' => $post['category_id'],
            'slug' => $post['slug'],
            'title' => $post['title'],
            'excerpt' => $post['excerpt'],
            'content' => $post['content'],
            'keywords' => $post['keywords'],
            'description' => $post['description'],
            'image' => $post['image'],
            'is_published' => $post['is_published'],
            'published_at' => $post['published_at'],
            'updated_at' => date('c'),
        ]);

        flash('success', 'Changes saved.');
        redirect(admin_path($this->config, 'posts'));
    }

    public function delete(int $id): void
    {
        require_admin_role($this->config, ['administrator', 'editor']);

        if (! verify_csrf_token($_POST['_csrf'] ?? null)) {
            flash('error', 'Unable to confirm deletion.');
            redirect(admin_path($this->config, 'posts'));
        }

        $this->posts->delete($id);
        flash('success', 'Post deleted.');
        redirect(admin_path($this->config, 'posts'));
    }

    private function validateForm(?int $postId = null): array
    {
        $publishedAtInput = trim((string) ($_POST['published_at'] ?? ''));
        $post = [
            'id' => $postId,
            'section_id' => (int) ($_POST['section_id'] ?? 0),
            'category_id' => ($_POST['category_id'] ?? '') === '' ? null : (int) $_POST['category_id'],
            'slug' => trim((string) ($_POST['slug'] ?? '')),
            'title' => trim((string) ($_POST['title'] ?? '')),
            'excerpt' => trim((string) ($_POST['excerpt'] ?? '')),
            'content' => trim((string) ($_POST['content'] ?? '')),
            'keywords' => trim((string) ($_POST['keywords'] ?? '')),
            'description' => trim((string) ($_POST['description'] ?? '')),
            'image' => trim((string) ($_POST['image'] ?? '')),
            'is_published' => isset($_POST['is_published']) ? 1 : 0,
            'published_at' => $publishedAtInput,
        ];
        $errors = [];

        if (! verify_csrf_token($_POST['_csrf'] ?? null)) {
            $errors[] = 'Your session has expired. Refresh the page and try again.';
        }

        try {
            $uploadedImage = $this->handleImageUpload();
            if ($uploadedImage !== null) {
                $post['image'] = $uploadedImage;
            }
        } catch (RuntimeException $exception) {
            $errors[] = $exception->getMessage();
        }

        if ($post['section_id'] < 1) {
            $errors[] = 'Choose a section.';
        }

        if ($post['slug'] === '') {
            $errors[] = 'Slug is required.';
        } elseif (! preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $post['slug'])) {
            $errors[] = 'Slug may only contain lowercase Latin letters, numbers, and hyphens.';
        } elseif ($post['section_id'] > 0 && $this->posts->slugExistsInSection($post['section_id'], $post['slug'], $postId)) {
            $errors[] = 'That slug is already used inside the selected section.';
        }

        if ($post['title'] === '') {
            $errors[] = 'Title is required.';
        }

        if ($post['content'] === '') {
            $errors[] = 'Content is required.';
        }

        if ($post['image'] === '') {
            $errors[] = 'Post image is required.';
        }

        if ($publishedAtInput !== '') {
            $normalizedPublishedAt = $this->normalizePublishedAt($publishedAtInput);
            if ($normalizedPublishedAt === null) {
                $errors[] = 'Published at must be a valid date or datetime.';
            } else {
                $post['published_at'] = $normalizedPublishedAt;
            }
        }

        $availableCategories = [];
        foreach ($this->categories->options() as $category) {
            $availableCategories[(int) $category['id']] = (int) $category['section_id'];
        }
        if ($post['category_id'] !== null) {
            if (! isset($availableCategories[$post['category_id']])) {
                $errors[] = 'Selected category does not exist.';
            } elseif ($availableCategories[$post['category_id']] !== $post['section_id']) {
                $errors[] = 'Selected category does not belong to the chosen section.';
            }
        }

        return [$post, $errors];
    }

    private function emptyPost(): array
    {
        return [
            'section_id' => 0,
            'category_id' => null,
            'slug' => '',
            'title' => '',
            'excerpt' => '',
            'content' => '',
            'keywords' => '',
            'description' => '',
            'image' => '',
            'is_published' => 1,
            'published_at' => '',
        ];
    }

    private function handleImageUpload(): ?string
    {
        $upload = $_FILES['image_upload'] ?? null;
        if (! is_array($upload)) {
            return null;
        }

        $error = (int) ($upload['error'] ?? UPLOAD_ERR_NO_FILE);
        if ($error === UPLOAD_ERR_NO_FILE) {
            return null;
        }

        return $this->media->upload($upload);
    }

    private function mediaImages(): array
    {
        return array_values(array_filter(
            $this->media->all(),
            static fn (array $file): bool => (bool) ($file['is_image'] ?? false)
        ));
    }

    private function normalizePublishedAt(string $value): ?string
    {
        $timestamp = strtotime($value);

        if ($timestamp === false) {
            return null;
        }

        return date('c', $timestamp);
    }

    private function formatPublishedAtForInput(string $value): string
    {
        if ($value === '') {
            return '';
        }

        $timestamp = strtotime($value);

        if ($timestamp === false) {
            return $value;
        }

        return date('Y-m-d\\TH:i', $timestamp);
    }

    private function listFilters(): array
    {
        return [
            'q' => trim((string) ($_GET['q'] ?? '')),
            'status' => in_array((string) ($_GET['status'] ?? ''), ['published', 'hidden'], true) ? (string) $_GET['status'] : '',
            'section_id' => max(0, (int) ($_GET['section_id'] ?? 0)),
        ];
    }
}
