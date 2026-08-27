<?php

use App\Template\Template;

function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function get_current_page(): int
{
    $page = isset($_GET['page']) ? (int) $_GET['page'] : 1;

    return $page > 0 ? $page : 1;
}

function normalize_base_url(string $url): string
{
    return rtrim($url, '/');
}

function build_url(string $baseUrl, string $path = ''): string
{
    $baseUrl = normalize_base_url($baseUrl);
    $path = trim($path, '/');

    if ($path === '') {
        return $baseUrl . '/';
    }

    return $baseUrl . '/' . $path;
}

function site_page_url(array $config, string $path = '', array $query = []): string
{
    $url = build_url($config['site']['url'], $path);

    if ($query === []) {
        return $url;
    }

    return $url . '?' . http_build_query($query);
}

function get_default_meta(array $config, string $type): string
{
    return (string) ($config['site'][$type] ?? '');
}

function pagination_window(int $currentPage, int $totalPages): array
{
    if ($totalPages < 1) {
        return [];
    }

    return range(1, $totalPages);
}

function current_path(): string
{
    $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';

    return trim($path, '/') === 'public/index.php' ? '/' : $path;
}

function format_admin_datetime(?string $value): string
{
    $value = trim((string) $value);

    if ($value === '') {
        return '-';
    }

    $timestamp = strtotime($value);

    if ($timestamp === false) {
        return $value;
    }

    return date('Y-m-d H:i', $timestamp);
}

function respond_not_found(Template $template, array $config): void
{
    http_response_code(404);

    echo $template->render([
        'title' => '404',
        'keywords' => get_default_meta($config, 'default_keywords'),
        'description' => get_default_meta($config, 'default_description'),
        'content' => '<div class="site-container"><section class="demo-content-section"><h1>404</h1><p>Page not found.</p></section></div>',
    ]);
}

function default_post_image_url(array $config, string $title = 'JenCMS'): string
{
    $svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1200 800" role="img" aria-label="'
        . e($title)
        . '">'
        . '<rect width="1200" height="800" fill="#e7ecf3"/>'
        . '<rect x="70" y="70" width="1060" height="660" rx="36" fill="#ffffff" stroke="#cfd7e3" stroke-width="8"/>'
        . '<circle cx="250" cy="250" r="82" fill="#d5deeb"/>'
        . '<path d="M150 610 430 360l170 160 130-110 220 200H150Z" fill="#b8c7da"/>'
        . '<text x="120" y="170" font-family="Arial, sans-serif" font-size="56" fill="#2c405a">JenCMS</text>'
        . '<text x="120" y="245" font-family="Arial, sans-serif" font-size="28" fill="#5d728e">Replace this placeholder with your project image</text>'
        . '</svg>';

    return 'data:image/svg+xml;charset=UTF-8,' . rawurlencode($svg);
}

function request_method(): string
{
    return strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
}

function is_post_request(): bool
{
    return request_method() === 'POST';
}

function redirect(string $url, int $statusCode = 302): never
{
    header('Location: ' . $url, true, $statusCode);
    exit;
}

function admin_path(array $config, string $path = ''): string
{
    $prefix = trim((string) ($config['routes']['admin_prefix'] ?? 'admin'), '/');
    $path = trim($path, '/');
    $fullPath = $prefix;

    if ($path !== '') {
        $fullPath .= '/' . $path;
    }

    return site_page_url($config, $fullPath);
}

function ensure_session_started(?array $config = null): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        $secure = request_is_https($config);
        ini_set('session.use_strict_mode', '1');
        ini_set('session.use_only_cookies', '1');
        session_name('JENCMSSESSID');
        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'secure' => $secure,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        session_start();
    }

    if ($config !== null) {
        $_SESSION['_admin_idle_timeout'] = max(300, (int) ($config['security']['admin_session_idle_timeout'] ?? 1800));
    }
}

function request_is_https(?array $config = null): bool
{
    $https = strtolower((string) ($_SERVER['HTTPS'] ?? ''));
    if ($https !== '' && $https !== 'off') {
        return true;
    }

    if ((int) ($_SERVER['SERVER_PORT'] ?? 0) === 443) {
        return true;
    }

    return $config !== null && parse_url((string) ($config['site']['url'] ?? ''), PHP_URL_SCHEME) === 'https';
}

function is_admin_authenticated(): bool
{
    ensure_session_started();

    if (! isset($_SESSION['admin_user']) || ! is_array($_SESSION['admin_user'])) {
        return false;
    }

    $now = time();
    $lastActivity = (int) ($_SESSION['_admin_last_activity'] ?? $now);
    $timeout = max(300, (int) ($_SESSION['_admin_idle_timeout'] ?? 1800));

    if ($now - $lastActivity > $timeout) {
        unset($_SESSION['admin_user'], $_SESSION['_admin_last_activity']);

        return false;
    }

    $_SESSION['_admin_last_activity'] = $now;

    return true;
}

function require_admin_auth(array $config): void
{
    if (! is_admin_authenticated()) {
        redirect(admin_path($config, 'login'));
    }
}

function admin_user(): ?array
{
    ensure_session_started();

    $user = $_SESSION['admin_user'] ?? null;

    return is_array($user) ? $user : null;
}

function admin_role(): string
{
    $user = admin_user();

    return (string) ($user['role'] ?? 'administrator');
}

function admin_has_role(string $role): bool
{
    return admin_role() === $role;
}

function admin_has_any_role(array $roles): bool
{
    return in_array(admin_role(), $roles, true);
}

function require_admin_role(array $config, array $roles): void
{
    require_admin_auth($config);

    if (! admin_has_any_role($roles)) {
        flash('error', 'You do not have permission to access that area.');
        redirect(admin_path($config));
    }
}

function set_admin_user(array $user): void
{
    ensure_session_started();
    session_regenerate_id(true);
    $_SESSION['admin_user'] = [
        'id' => $user['id'] ?? null,
        'login' => $user['login'] ?? '',
        'name' => $user['name'] ?? '',
        'email' => $user['email'] ?? '',
        'role' => $user['role'] ?? 'administrator',
    ];
    $_SESSION['_admin_last_activity'] = time();
    unset($_SESSION['_csrf_token']);
}

function clear_admin_user(): void
{
    ensure_session_started();
    unset($_SESSION['admin_user'], $_SESSION['_admin_last_activity'], $_SESSION['_csrf_token']);
    session_regenerate_id(true);
}

function flash(string $key, ?string $message = null): ?string
{
    ensure_session_started();

    if ($message !== null) {
        $_SESSION['_flash'][$key] = $message;

        return null;
    }

    $value = $_SESSION['_flash'][$key] ?? null;
    unset($_SESSION['_flash'][$key]);

    return is_string($value) ? $value : null;
}

function csrf_token(): string
{
    ensure_session_started();

    if (! isset($_SESSION['_csrf_token']) || ! is_string($_SESSION['_csrf_token'])) {
        $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['_csrf_token'];
}

function verify_csrf_token(?string $token): bool
{
    ensure_session_started();

    return is_string($token)
        && isset($_SESSION['_csrf_token'])
        && is_string($_SESSION['_csrf_token'])
        && hash_equals($_SESSION['_csrf_token'], $token);
}

function render_admin_view(array $config, string $view, array $data = []): void
{
    $viewPath = BASE_PATH . '/views/admin/' . $view . '.php';

    if (! is_file($viewPath)) {
        throw new \RuntimeException('Admin view not found: ' . $view);
    }

    $data['config'] = $config;
    $data['adminUser'] = admin_user();
    $data['flashSuccess'] = flash('success');
    $data['flashError'] = flash('error');
    $data['csrfToken'] = csrf_token();
    $data['adminTitle'] = $data['title'] ?? 'Admin';
    $data['viewPath'] = $viewPath;

    extract($data, EXTR_SKIP);

    require BASE_PATH . '/views/admin/layout.php';
}
