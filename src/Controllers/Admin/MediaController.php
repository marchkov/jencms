<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Repositories\MediaRepository;
use RuntimeException;

final class MediaController
{
    public function __construct(
        private readonly MediaRepository $media,
        private readonly array $config
    ) {
    }

    public function index(): void
    {
        require_admin_role($this->config, ['administrator', 'editor']);

        render_admin_view($this->config, 'media/index', [
            'title' => 'Media',
            'files' => $this->media->all(),
            'uploadAction' => admin_path($this->config, 'media/upload'),
            'deleteAction' => admin_path($this->config, 'media/delete'),
        ]);
    }

    public function store(): void
    {
        require_admin_role($this->config, ['administrator', 'editor']);

        $wantsJson = str_contains((string) ($_SERVER['HTTP_ACCEPT'] ?? ''), 'application/json');

        if (! verify_csrf_token($_POST['_csrf'] ?? null)) {
            if ($wantsJson) {
                $this->respondJson(['ok' => false, 'error' => 'Your session has expired. Refresh the page and try again.'], 419);
            }
            flash('error', 'Your session has expired. Refresh the page and try again.');
            redirect(admin_path($this->config, 'media'));
        }

        try {
            $path = $this->media->upload($_FILES['media_file'] ?? []);
            if ($wantsJson) {
                $this->respondJson([
                    'ok' => true,
                    'file' => [
                        'name' => basename($path),
                        'path' => $path,
                        'url' => site_page_url($this->config, ltrim($path, '/')),
                    ],
                ], 201);
            }
            flash('success', 'File uploaded: ' . $path);
        } catch (RuntimeException $exception) {
            if ($wantsJson) {
                $this->respondJson(['ok' => false, 'error' => $exception->getMessage()], 422);
            }
            flash('error', $exception->getMessage());
        }

        redirect(admin_path($this->config, 'media'));
    }

    private function respondJson(array $payload, int $statusCode): never
    {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        exit;
    }

    public function delete(): void
    {
        require_admin_role($this->config, ['administrator', 'editor']);

        if (! verify_csrf_token($_POST['_csrf'] ?? null)) {
            flash('error', 'Unable to confirm deletion.');
            redirect(admin_path($this->config, 'media'));
        }

        $path = trim((string) ($_POST['path'] ?? ''));
        if ($path !== '') {
            $this->media->delete($path);
            flash('success', 'File deleted.');
        }

        redirect(admin_path($this->config, 'media'));
    }
}
