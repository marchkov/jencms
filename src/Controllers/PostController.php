<?php

namespace App\Controllers;

use App\Repositories\PostRepository;
use App\Repositories\SectionRepository;
use App\Template\Template;

final class PostController
{
    public function __construct(
        private readonly SectionRepository $sections,
        private readonly PostRepository $posts,
        private readonly Template $template,
        private readonly array $config
    ) {
    }

    public function showSection(string $sectionSlug, int $page): void
    {
        $section = $this->sections->findPublishedBySlug($sectionSlug);

        if ($section === null) {
            respond_not_found($this->template, $this->config);
            return;
        }

        $configuredPerPage = (int) ($section['posts_per_page'] ?: $this->config['content']['posts_per_page']);
        $perPage = $this->normalizePostsPerPage($configuredPerPage);
        $totalPosts = $this->posts->countPublishedBySectionId((int) $section['id']);
        $totalPages = max(1, (int) ceil($totalPosts / max(1, $perPage)));
        $currentPage = min(max(1, $page), $totalPages);
        $offset = ($currentPage - 1) * $perPage;
        $posts = $this->posts->findPublishedBySectionId((int) $section['id'], $perPage, $offset);

        echo $this->template->render([
            'title' => $section['title'] . ' :: ' . $this->config['site']['name'],
            'keywords' => get_default_meta($this->config, 'default_keywords'),
            'description' => $section['description'] ?: get_default_meta($this->config, 'default_description'),
            'content' => $this->renderSectionContent($section, $posts, $currentPage, $totalPages),
        ]);
    }

    public function showPost(string $sectionSlug, string $postSlug): void
    {
        $section = $this->sections->findPublishedBySlug($sectionSlug);

        if ($section === null) {
            respond_not_found($this->template, $this->config);
            return;
        }

        $post = $this->posts->findPublishedBySectionAndSlug((int) $section['id'], $postSlug);

        if ($post === null) {
            respond_not_found($this->template, $this->config);
            return;
        }

        echo $this->template->render([
            'title' => $post['title'] . ' :: ' . $this->config['site']['name'],
            'keywords' => $post['keywords'] ?: get_default_meta($this->config, 'default_keywords'),
            'description' => $post['description'] ?: get_default_meta($this->config, 'default_description'),
            'content' => $post['content'],
        ]);
    }

    private function renderSectionContent(array $section, array $posts, int $currentPage, int $totalPages): string
    {
        $html = '<div class="site-container"><section class="news-cards-section">';

        if ($posts === []) {
            $html .= '<p>No posts yet.</p>';
        } else {
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
                $html .= '<h2 class="news-card__title"><a href="' . e($url) . '">' . e($post['title']) . '</a></h2>';
                if ($summary !== '') {
                    $html .= '<p class="news-card__text">' . e($summary) . '</p>';
                }
                $html .= '<a class="news-card__link" href="' . e($url) . '">Read more <span>&rarr;</span></a>';
                $html .= '</div></article>';
            }
            $html .= '</div>';
        }

        if ($totalPages > 1) {
            $html .= '<nav class="pagination" aria-label="Pagination"><ul>';
            foreach (pagination_window($currentPage, $totalPages) as $pageNumber) {
                $url = $pageNumber === 1 ? site_page_url($this->config, $section['slug']) : site_page_url($this->config, $section['slug'], ['page' => $pageNumber]);
                $label = $pageNumber === $currentPage ? '<strong>' . $pageNumber . '</strong>' : (string) $pageNumber;
                $html .= '<li><a href="' . e($url) . '">' . $label . '</a></li>';
            }
            $html .= '</ul></nav>';
        }

        $html .= '</section></div>';

        return $html;
    }

    private function normalizePostsPerPage(int $value): int
    {
        $value = max(1, $value);

        return (int) (ceil($value / 6) * 6);
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
