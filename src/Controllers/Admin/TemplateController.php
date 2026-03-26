<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Repositories\TemplateFileRepository;
use RuntimeException;

final class TemplateController
{
    public function __construct(
        private readonly TemplateFileRepository $templates,
        private readonly array $config
    ) {
    }

    public function index(): void
    {
        require_admin_role($this->config, ['administrator']);

        render_admin_view($this->config, 'templates/index', [
            'title' => 'Template Editor',
            'files' => $this->templates->all(),
        ]);
    }

    public function edit(string $filePath): void
    {
        require_admin_role($this->config, ['administrator']);

        try {
            $file = $this->templates->read($filePath);
        } catch (RuntimeException $exception) {
            flash('error', $exception->getMessage());
            redirect(admin_path($this->config, 'templates'));
        }

        render_admin_view($this->config, 'templates/form', [
            'title' => 'Edit Template',
            'formAction' => admin_path($this->config, 'templates/edit') . '?file=' . rawurlencode($file['path']),
            'restoreAction' => admin_path($this->config, 'templates/restore') . '?file=' . rawurlencode($file['path']),
            'file' => $file,
            'errors' => [],
            'previewLinks' => $this->buildPreviewLinks($file['path'], $file['extension']),
            'backups' => $this->templates->backups($file['path']),
        ]);
    }

    public function update(string $filePath): void
    {
        require_admin_role($this->config, ['administrator']);

        $errors = [];
        $content = (string) ($_POST['content'] ?? '');

        if (! verify_csrf_token($_POST['_csrf'] ?? null)) {
            $errors[] = 'Your session has expired. Refresh the page and try again.';
        }

        try {
            $file = $this->templates->read($filePath);
        } catch (RuntimeException $exception) {
            flash('error', $exception->getMessage());
            redirect(admin_path($this->config, 'templates'));
        }

        if ($errors !== []) {
            $file['content'] = $content;
            render_admin_view($this->config, 'templates/form', [
                'title' => 'Edit Template',
                'formAction' => admin_path($this->config, 'templates/edit') . '?file=' . rawurlencode($file['path']),
                'restoreAction' => admin_path($this->config, 'templates/restore') . '?file=' . rawurlencode($file['path']),
                'file' => $file,
                'errors' => $errors,
                'previewLinks' => $this->buildPreviewLinks($file['path'], $file['extension']),
                'backups' => $this->templates->backups($file['path']),
            ]);
            return;
        }

        try {
            $this->templates->write($filePath, $content);
        } catch (RuntimeException $exception) {
            $file['content'] = $content;
            render_admin_view($this->config, 'templates/form', [
                'title' => 'Edit Template',
                'formAction' => admin_path($this->config, 'templates/edit') . '?file=' . rawurlencode($file['path']),
                'restoreAction' => admin_path($this->config, 'templates/restore') . '?file=' . rawurlencode($file['path']),
                'file' => $file,
                'errors' => [$exception->getMessage()],
                'previewLinks' => $this->buildPreviewLinks($file['path'], $file['extension']),
                'backups' => $this->templates->backups($file['path']),
            ]);
            return;
        }

        flash('success', 'Template file saved. A backup of the previous version was created automatically.');
        redirect(admin_path($this->config, 'templates/edit') . '?file=' . rawurlencode($filePath));
    }

    public function restore(string $filePath): void
    {
        require_admin_role($this->config, ['administrator']);

        if (! verify_csrf_token($_POST['_csrf'] ?? null)) {
            flash('error', 'Your session has expired. Refresh the page and try again.');
            redirect(admin_path($this->config, 'templates/edit') . '?file=' . rawurlencode($filePath));
        }

        $backup = (string) ($_POST['backup'] ?? '');

        try {
            $this->templates->restoreBackup($filePath, $backup);
        } catch (RuntimeException $exception) {
            flash('error', $exception->getMessage());
            redirect(admin_path($this->config, 'templates/edit') . '?file=' . rawurlencode($filePath));
        }

        flash('success', 'Backup restored. The previously active file version was backed up before restore.');
        redirect(admin_path($this->config, 'templates/edit') . '?file=' . rawurlencode($filePath));
    }

    private function buildPreviewLinks(string $relativePath, string $extension): array
    {
        $links = [];
        $sitePreview = site_page_url($this->config);

        if ($extension === 'tpl') {
            $links[] = ['label' => 'Open site preview', 'url' => $sitePreview];

            return $links;
        }

        $links[] = ['label' => 'Open site preview', 'url' => $sitePreview];
        $links[] = [
            'label' => 'Open raw asset',
            'url' => site_page_url($this->config, 'themes/' . $this->config['site']['theme'] . '/' . ltrim($relativePath, '/')),
        ];

        return $links;
    }
}
