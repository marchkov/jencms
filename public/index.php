<?php

use App\Controllers\Admin\AuthController as AdminAuthController;
use App\Controllers\Admin\CategoryController as AdminCategoryController;
use App\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Controllers\Admin\MediaController as AdminMediaController;
use App\Controllers\Admin\PageController as AdminPageController;
use App\Controllers\Admin\PostController as AdminPostController;
use App\Controllers\Admin\SectionController as AdminSectionController;
use App\Controllers\Admin\SettingsController as AdminSettingsController;
use App\Controllers\Admin\TemplateController as AdminTemplateController;
use App\Controllers\Admin\UserController as AdminUserController;
use App\Controllers\PageController;
use App\Controllers\PostController;
use App\Database\Connection;
use App\Database\Initializer;
use App\Repositories\AdminCategoryRepository;
use App\Repositories\AdminPageRepository;
use App\Repositories\AdminPostRepository;
use App\Repositories\AdminSectionRepository;
use App\Repositories\MediaRepository;
use App\Repositories\PageRepository;
use App\Repositories\PostRepository;
use App\Repositories\SectionRepository;
use App\Repositories\SettingsRepository;
use App\Repositories\TemplateFileRepository;
use App\Repositories\UserRepository;
use App\Router\Router;
use App\Template\Template;

define('BASE_PATH', dirname(__DIR__));

$config = require BASE_PATH . '/settings.php';

require BASE_PATH . '/src/Support/helpers.php';
require BASE_PATH . '/src/Support/autoload.php';

ensure_session_started();

if ($config['debug']['enabled']) {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
}

try {
    $pdo = Connection::create($config['database']);
    Initializer::bootstrap($pdo, BASE_PATH . '/storage/migrations/001_init.sql');

    $settingsRepository = new SettingsRepository($pdo);
    $config = array_replace_recursive($config, $settingsRepository->loadOverrides());

    if (is_admin_request($config, $_SERVER['REQUEST_URI'] ?? '/')) {
        dispatch_admin_request($config, $pdo, $settingsRepository);
        return;
    }

    $template = new Template(
        BASE_PATH . '/public/themes/' . $config['site']['theme'] . '/main.tpl',
        $config
    );

    $pageRepository = new PageRepository($pdo);
    $sectionRepository = new SectionRepository($pdo);
    $postRepository = new PostRepository($pdo);

    $pageController = new PageController($pageRepository, $sectionRepository, $postRepository, $template, $config);
    $postController = new PostController($sectionRepository, $postRepository, $template, $config);

    $router = new Router($config['site']['homepage_slug']);
    $route = $router->resolve($_SERVER['REQUEST_URI'] ?? '/');

    switch ($route['type']) {
        case Router::ROUTE_HOME:
            $pageController->showHome();
            break;
        case Router::ROUTE_PAGE_OR_SECTION:
            $slug = $route['slug'];
            $page = $pageRepository->findPublishedBySlug($slug);
            if ($page !== null) {
                $pageController->show($slug);
                break;
            }

            $postController->showSection($slug, get_current_page());
            break;
        case Router::ROUTE_POST:
            $postController->showPost($route['section_slug'], $route['post_slug']);
            break;
        default:
            respond_not_found($template, $config);
    }
} catch (Throwable $exception) {
    http_response_code(500);

    if ($config['debug']['enabled']) {
        echo '<pre>' . e($exception->getMessage()) . '</pre>';
    }
}

function is_admin_request(array $config, string $requestUri): bool
{
    $path = trim(parse_url($requestUri, PHP_URL_PATH) ?? '/', '/');
    $prefix = trim((string) ($config['routes']['admin_prefix'] ?? 'admin'), '/');

    return $path === $prefix || str_starts_with($path, $prefix . '/');
}

function dispatch_admin_request(array $config, PDO $pdo, SettingsRepository $settingsRepository): void
{
    $path = trim(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/', '/');
    $segments = array_values(array_filter(explode('/', $path), static fn (string $segment): bool => $segment !== ''));
    array_shift($segments);

    $userRepository = new UserRepository($pdo);
    $authController = new AdminAuthController($userRepository, $config);
    $dashboardController = new AdminDashboardController($config);
    $mediaRepository = new MediaRepository(BASE_PATH . '/public/uploads');
    $pageRepository = new AdminPageRepository($pdo);
    $pageController = new AdminPageController($pageRepository, $mediaRepository, $config);
    $sectionRepository = new AdminSectionRepository($pdo);
    $categoryRepository = new AdminCategoryRepository($pdo);
    $mediaController = new AdminMediaController($mediaRepository, $config);
    $sectionController = new AdminSectionController($sectionRepository, $config);
    $categoryController = new AdminCategoryController($categoryRepository, $sectionRepository, $config);
    $postController = new AdminPostController(
        new AdminPostRepository($pdo),
        $sectionRepository,
        $categoryRepository,
        $mediaRepository,
        $config
    );
    $userController = new AdminUserController($userRepository, $config);
    $settingsController = new AdminSettingsController($settingsRepository, $pageRepository, $config);
    $templateController = new AdminTemplateController(
        new TemplateFileRepository(
            BASE_PATH . '/public/themes/' . $config['site']['theme'],
            BASE_PATH . '/storage/template-backups'
        ),
        $config
    );

    if ($segments === []) {
        $dashboardController->index();
        return;
    }

    if ($segments[0] === 'login') {
        if (is_post_request()) {
            $authController->login();
            return;
        }

        $authController->showLogin();
        return;
    }

    if ($segments[0] === 'logout' && is_post_request()) {
        $authController->logout();
        return;
    }

    if ($segments[0] === 'media') {
        if (count($segments) === 1) {
            $mediaController->index();
            return;
        }

        if (count($segments) === 2 && $segments[1] === 'upload' && is_post_request()) {
            $mediaController->store();
            return;
        }

        if (count($segments) === 2 && $segments[1] === 'delete' && is_post_request()) {
            $mediaController->delete();
            return;
        }
    }

    if ($segments[0] === 'settings') {
        if (is_post_request()) {
            $settingsController->update();
            return;
        }

        $settingsController->edit();
        return;
    }

    if ($segments[0] === 'templates') {
        if (count($segments) === 1) {
            $templateController->index();
            return;
        }

        if (count($segments) === 2 && $segments[1] === 'edit') {
            $file = (string) ($_GET['file'] ?? '');

            if (is_post_request()) {
                $templateController->update($file);
                return;
            }

            $templateController->edit($file);
            return;
        }

        if (count($segments) === 2 && $segments[1] === 'restore' && is_post_request()) {
            $templateController->restore((string) ($_GET['file'] ?? ''));
            return;
        }
    }

    if ($segments[0] === 'pages') {
        if (count($segments) === 1) {
            $pageController->index();
            return;
        }

        if (count($segments) === 2 && $segments[1] === 'create') {
            if (is_post_request()) {
                $pageController->store();
                return;
            }

            $pageController->create();
            return;
        }

        if (count($segments) === 3 && ctype_digit($segments[1]) && $segments[2] === 'edit') {
            $pageId = (int) $segments[1];

            if (is_post_request()) {
                $pageController->update($pageId);
                return;
            }

            $pageController->edit($pageId);
            return;
        }

        if (count($segments) === 3 && ctype_digit($segments[1]) && $segments[2] === 'delete' && is_post_request()) {
            $pageController->delete((int) $segments[1]);
            return;
        }
    }

    if ($segments[0] === 'sections') {
        if (count($segments) === 1) {
            $sectionController->index();
            return;
        }

        if (count($segments) === 2 && $segments[1] === 'create') {
            if (is_post_request()) {
                $sectionController->store();
                return;
            }

            $sectionController->create();
            return;
        }

        if (count($segments) === 3 && ctype_digit($segments[1]) && $segments[2] === 'edit') {
            $sectionId = (int) $segments[1];

            if (is_post_request()) {
                $sectionController->update($sectionId);
                return;
            }

            $sectionController->edit($sectionId);
            return;
        }

        if (count($segments) === 3 && ctype_digit($segments[1]) && $segments[2] === 'delete' && is_post_request()) {
            $sectionController->delete((int) $segments[1]);
            return;
        }
    }

    if ($segments[0] === 'categories') {
        if (count($segments) === 1) {
            $categoryController->index();
            return;
        }

        if (count($segments) === 2 && $segments[1] === 'create') {
            if (is_post_request()) {
                $categoryController->store();
                return;
            }

            $categoryController->create();
            return;
        }

        if (count($segments) === 3 && ctype_digit($segments[1]) && $segments[2] === 'edit') {
            $categoryId = (int) $segments[1];

            if (is_post_request()) {
                $categoryController->update($categoryId);
                return;
            }

            $categoryController->edit($categoryId);
            return;
        }

        if (count($segments) === 3 && ctype_digit($segments[1]) && $segments[2] === 'delete' && is_post_request()) {
            $categoryController->delete((int) $segments[1]);
            return;
        }
    }

    if ($segments[0] === 'posts') {
        if (count($segments) === 1) {
            $postController->index();
            return;
        }

        if (count($segments) === 2 && $segments[1] === 'create') {
            if (is_post_request()) {
                $postController->store();
                return;
            }

            $postController->create();
            return;
        }

        if (count($segments) === 3 && ctype_digit($segments[1]) && $segments[2] === 'edit') {
            $postId = (int) $segments[1];

            if (is_post_request()) {
                $postController->update($postId);
                return;
            }

            $postController->edit($postId);
            return;
        }

        if (count($segments) === 3 && ctype_digit($segments[1]) && $segments[2] === 'delete' && is_post_request()) {
            $postController->delete((int) $segments[1]);
            return;
        }
    }

    if ($segments[0] === 'users') {
        if (count($segments) === 1) {
            $userController->index();
            return;
        }

        if (count($segments) === 2 && $segments[1] === 'create') {
            if (is_post_request()) {
                $userController->store();
                return;
            }

            $userController->create();
            return;
        }

        if (count($segments) === 3 && ctype_digit($segments[1]) && $segments[2] === 'edit') {
            $userId = (int) $segments[1];

            if (is_post_request()) {
                $userController->update($userId);
                return;
            }

            $userController->edit($userId);
            return;
        }

        if (count($segments) === 3 && ctype_digit($segments[1]) && $segments[2] === 'reset-password') {
            $userId = (int) $segments[1];

            if (is_post_request()) {
                $userController->updatePassword($userId);
                return;
            }

            $userController->resetPassword($userId);
            return;
        }

        if (count($segments) === 3 && ctype_digit($segments[1]) && $segments[2] === 'delete' && is_post_request()) {
            $userController->delete((int) $segments[1]);
            return;
        }
    }

    http_response_code(404);
    echo 'Admin page not found.';
}
