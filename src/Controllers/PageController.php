<?php

namespace App\Controllers;

use App\Repositories\PageRepository;
use App\Repositories\PostRepository;
use App\Repositories\SectionRepository;
use App\Template\Template;

final class PageController
{
    public function __construct(
        private readonly PageRepository $pages,
        private readonly SectionRepository $sections,
        private readonly PostRepository $posts,
        private readonly Template $template,
        private readonly array $config
    ) {
    }

    public function showHome(): void
    {
        $this->show($this->config['site']['homepage_slug']);
    }

    public function show(string $slug): void
    {
        $page = $this->pages->findPublishedBySlug($slug);

        if ($page === null) {
            respond_not_found($this->template, $this->config);
            return;
        }

        $content = $page['content'];
        if ($slug === $this->config['site']['homepage_slug']) {
            $content = $this->injectHomeNewsSection($content);
        }

        echo $this->template->render([
            'title' => $page['title'] . ' :: ' . $this->config['site']['name'],
            'keywords' => $page['keywords'] ?: get_default_meta($this->config, 'default_keywords'),
            'description' => $page['description'] ?: get_default_meta($this->config, 'default_description'),
            'content' => $content,
        ]);
    }

    private function injectHomeNewsSection(string $content): string
    {
        $section = $this->sections->findPublishedBySlug('news');

        if ($section === null) {
            return $content;
        }

        $posts = $this->posts->findPublishedBySectionId((int) $section['id'], 3, 0);
        $replacement = $this->renderHomeNewsSection($section, $posts);

        return (string) preg_replace('/<section class="news-cards-section">.*?<\/section>/s', $replacement, $content, 1);
    }

    private function renderHomeNewsSection(array $section, array $posts): string
    {
        $html = '<section class="news-cards-section">';
        $html .= '<h2 class="section-title">' . e($section['title']) . '</h2>';
        $html .= '<div class="news-cards-grid">';

        foreach ($posts as $post) {
            $url = site_page_url($this->config, $section['slug'] . '/' . $post['slug']);
            $imageUrl = $this->resolveImageUrl((string) ($post['image'] ?? ''));
            $summary = $this->buildSummary($post);

            $html .= '<article class="news-card">';
            $html .= '<a href="' . e($url) . '" class="news-card__image-link">';
            $html .= '<img src="' . e($imageUrl) . '" alt="' . e($post['title']) . '" class="news-card__image">';
            $html .= '</a>';
            $html .= '<div class="news-card__body">';
            $html .= '<h3 class="news-card__title"><a href="' . e($url) . '">' . e($post['title']) . '</a></h3>';
            if ($summary !== '') {
                $html .= '<p class="news-card__text">' . e($summary) . '</p>';
            }
            $html .= '<a href="' . e($url) . '" class="news-card__link">Read more <span>&rarr;</span></a>';
            $html .= '</div></article>';
        }

        $html .= '</div>';
        $html .= '</section>';

        return $html;
    }

    private function resolveImageUrl(string $image): string
    {
        $image = trim($image);

        if ($image === '') {
            return default_post_image_url($this->config);
        }

        if (preg_match('#^https?://#i', $image)) {
            return $image;
        }

        if (str_starts_with($image, '/')) {
            return normalize_base_url($this->config['site']['url']) . $image;
        }

        return site_page_url($this->config, ltrim($image, '/'));
    }

    private function buildSummary(array $post): string
    {
        $excerpt = trim((string) ($post['excerpt'] ?? ''));
        if ($excerpt !== '') {
            return $excerpt;
        }

        $content = trim(strip_tags((string) ($post['content'] ?? '')));
        if ($content === '') {
            return '';
        }

        if (function_exists('mb_substr')) {
            return mb_strlen($content) > 180 ? rtrim(mb_substr($content, 0, 180)) . '...' : $content;
        }

        return strlen($content) > 180 ? rtrim(substr($content, 0, 180)) . '...' : $content;
    }
}
