<?php

namespace App\Template;

use RuntimeException;

final class Template
{
    public function __construct(
        private readonly string $templatePath,
        private readonly array $config
    ) {
    }

    public function render(array $data): string
    {
        $template = file_get_contents($this->templatePath);

        if ($template === false) {
            throw new RuntimeException('Unable to read template file.');
        }

        $siteUrl = normalize_base_url($this->config['site']['url']);
        $themeUrl = site_page_url($this->config, 'themes/' . $this->config['site']['theme']);
        $currentPath = trim(current_path(), '/');
        $currentUrl = $currentPath === '' ? $siteUrl . '/' : site_page_url($this->config, $currentPath);
        $bodyClass = trim((string) ($data['body_class'] ?? 'page-default'));
        $header = $data['header'] ?? '<a class="site-brand" href="' . e($siteUrl . '/') . '">' . e($this->config['site']['name']) . '</a>';
        $footer = $data['footer'] ?? '<p>Powered by JenCMS.</p>';

        return strtr($template, [
            '[PAGE_TITLE]' => e($data['title'] ?? $this->config['site']['name']),
            '[META_KEYWORDS]' => e($data['keywords'] ?? get_default_meta($this->config, 'default_keywords')),
            '[META_DESCRIPTION]' => e($data['description'] ?? get_default_meta($this->config, 'default_description')),
            '[SITE_NAME]' => e($this->config['site']['name']),
            '[SITE_URL]' => e($siteUrl),
            '[THEME_URL]' => e($themeUrl),
            '[CURRENT_URL]' => e($currentUrl),
            '[HOME_URL]' => e($siteUrl . '/'),
            '[ADMIN_URL]' => e(admin_path($this->config)),
            '[BODY_CLASS]' => e($bodyClass !== '' ? $bodyClass : 'page-default'),
            '[CURRENT_YEAR]' => e(date('Y')),
            '[CONTENT]' => $data['content'] ?? '',
            '[HTML_LANG]' => e((string) ($this->config['site']['language'] ?? 'en')),
            '[HEADER]' => $header,
            '[FOOTER]' => $footer,
        ]);
    }
}
