<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Repositories\AdminPageRepository;
use App\Repositories\SettingsRepository;
use App\Support\SystemCheck;

final class SettingsController
{
    public function __construct(
        private readonly SettingsRepository $settings,
        private readonly AdminPageRepository $pages,
        private readonly SystemCheck $systemCheck,
        private readonly array $config
    ) {
    }

    public function edit(): void
    {
        require_admin_role($this->config, ['administrator']);

        render_admin_view($this->config, 'settings/form', [
            'title' => 'Settings',
            'formAction' => admin_path($this->config, 'settings'),
            'settingsForm' => $this->buildFormData(),
            'pages' => $this->pages->options(),
            'systemChecks' => $this->systemCheck->run(),
            'errors' => [],
        ]);
    }

    public function update(): void
    {
        require_admin_role($this->config, ['administrator']);

        [$form, $errors] = $this->validateForm();

        if ($errors !== []) {
            render_admin_view($this->config, 'settings/form', [
                'title' => 'Settings',
                'formAction' => admin_path($this->config, 'settings'),
                'settingsForm' => $form,
                'pages' => $this->pages->options(),
                'systemChecks' => $this->systemCheck->run(),
                'errors' => $errors,
            ]);
            return;
        }

        $this->settings->saveEditableSettings([
            'site.name' => $form['site_name'],
            'site.homepage_slug' => $form['homepage_slug'],
            'site.default_keywords' => $form['default_keywords'],
            'site.default_description' => $form['default_description'],
            'content.posts_per_page' => $form['posts_per_page'],
        ]);

        flash('success', 'Settings saved.');
        redirect(admin_path($this->config, 'settings'));
    }

    private function buildFormData(): array
    {
        return [
            'site_name' => (string) $this->config['site']['name'],
            'homepage_slug' => (string) $this->config['site']['homepage_slug'],
            'posts_per_page' => $this->normalizePostsPerPage((int) $this->config['content']['posts_per_page']),
            'default_keywords' => (string) ($this->config['site']['default_keywords'] ?? ''),
            'default_description' => (string) ($this->config['site']['default_description'] ?? ''),
        ];
    }

    private function validateForm(): array
    {
        $form = [
            'site_name' => trim((string) ($_POST['site_name'] ?? '')),
            'homepage_slug' => trim((string) ($_POST['homepage_slug'] ?? '')),
            'posts_per_page' => $this->normalizePostsPerPage((int) ($_POST['posts_per_page'] ?? 6)),
            'default_keywords' => trim((string) ($_POST['default_keywords'] ?? '')),
            'default_description' => trim((string) ($_POST['default_description'] ?? '')),
        ];
        $errors = [];

        if (! verify_csrf_token($_POST['_csrf'] ?? null)) {
            $errors[] = 'Your session has expired. Refresh the page and try again.';
        }

        if ($form['site_name'] === '') {
            $errors[] = 'Site name is required.';
        }

        if ($form['homepage_slug'] === '') {
            $errors[] = 'Choose a homepage.';
        }

        $availableSlugs = [];
        foreach ($this->pages->options() as $page) {
            $availableSlugs[] = (string) $page['slug'];
        }
        if ($form['homepage_slug'] !== '' && ! in_array($form['homepage_slug'], $availableSlugs, true)) {
            $errors[] = 'Selected homepage does not exist.';
        }

        return [$form, $errors];
    }

    private function normalizePostsPerPage(int $value): int
    {
        $value = max(1, $value);

        return (int) (ceil($value / 6) * 6);
    }
}
