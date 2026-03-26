<?php

declare(strict_types=1);

namespace App\Router;

final class Router
{
    public const ROUTE_HOME = 'home';
    public const ROUTE_PAGE_OR_SECTION = 'page_or_section';
    public const ROUTE_POST = 'post';
    public const ROUTE_NOT_FOUND = 'not_found';

    public function __construct(private readonly string $homepageSlug)
    {
    }

    public function resolve(string $requestUri): array
    {
        $path = parse_url($requestUri, PHP_URL_PATH) ?? '/';
        $path = trim($path, '/');

        if ($path === '' || $path === 'index.php') {
            return [
                'type' => self::ROUTE_HOME,
                'slug' => $this->homepageSlug,
            ];
        }

        $segments = array_values(array_filter(explode('/', $path), static fn (string $segment): bool => $segment !== ''));

        if (count($segments) === 1) {
            return [
                'type' => self::ROUTE_PAGE_OR_SECTION,
                'slug' => $segments[0],
            ];
        }

        if (count($segments) === 2) {
            return [
                'type' => self::ROUTE_POST,
                'section_slug' => $segments[0],
                'post_slug' => $segments[1],
            ];
        }

        return ['type' => self::ROUTE_NOT_FOUND];
    }
}