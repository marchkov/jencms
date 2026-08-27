<?php

declare(strict_types=1);

// Existing files under the public document root are served directly. All other
// requests are forwarded to the JenCMS front controller.
$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$decodedPath = rawurldecode($path);
$publicRoot = __DIR__;
$relativePath = ltrim(str_replace('/', DIRECTORY_SEPARATOR, $decodedPath), DIRECTORY_SEPARATOR);
$candidate = realpath($publicRoot . DIRECTORY_SEPARATOR . $relativePath);

if ($candidate !== false
    && str_starts_with($candidate, $publicRoot . DIRECTORY_SEPARATOR)
    && is_file($candidate)
) {
    return false;
}

require $publicRoot . '/index.php';
