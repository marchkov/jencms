<?php

return [
    'site' => [
        'name' => 'JenCMS',
        'url' => 'http://localhost:8000',
        'theme' => 'default',
        'language' => 'en',
        'homepage_slug' => 'home',
        'default_keywords' => 'jencms, php cms, sqlite cms',
        'default_description' => 'Default project configuration for JenCMS.',
    ],
    'database' => [
        'driver' => 'sqlite',
        'path' => __DIR__ . '/storage/database.sqlite',
    ],
    'content' => [
        'posts_per_page' => 6,
    ],
    'routes' => [
        'admin_prefix' => 'admin',
    ],
    'debug' => [
        'enabled' => false,
    ],
];
