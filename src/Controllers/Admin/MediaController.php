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

        if (! verify_csrf_token($_POST['_csrf'] ?? null)) {
            flash('error', 'Your session has expired. Refresh the page and try again.');
            redirect(admin_path($this->config, 'media'));
        }

        try {
            $path = $this->media->upload($_FILES['media_file'] ?? []);
            flash('success', 'File uploaded: ' . $path);
        } catch (RuntimeException $exception) {
            flash('error', $exception->getMessage());
        }

        redirect(admin_path($this->config, 'media'));
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
