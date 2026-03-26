<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Repositories\UserRepository;

final class AuthController
{
    public function __construct(
        private readonly UserRepository $users,
        private readonly array $config
    ) {
    }

    public function showLogin(array $data = []): void
    {
        if (is_admin_authenticated()) {
            redirect(admin_path($this->config));
        }

        render_admin_view($this->config, 'login', [
            'title' => 'Admin Login',
            'login' => $data['login'] ?? '',
            'errorMessage' => $data['errorMessage'] ?? null,
            'hideNavigation' => true,
        ]);
    }

    public function login(): void
    {
        if (! verify_csrf_token($_POST['_csrf'] ?? null)) {
            $this->showLogin(['login' => (string) ($_POST['login'] ?? ''), 'errorMessage' => 'Your session has expired. Refresh the page and try again.']);
            return;
        }

        $login = trim((string) ($_POST['login'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');

        if ($login === '' || $password === '') {
            $this->showLogin(['login' => $login, 'errorMessage' => 'Enter your login and password.']);
            return;
        }

        $user = $this->users->findActiveByLogin($login);

        if ($user === null || ! password_verify($password, (string) $user['password_hash'])) {
            $this->showLogin(['login' => $login, 'errorMessage' => 'Invalid login or password.']);
            return;
        }

        set_admin_user($user);
        flash('success', 'You are now signed in.');
        redirect(admin_path($this->config));
    }

    public function logout(): void
    {
        if (! verify_csrf_token($_POST['_csrf'] ?? null)) {
            redirect(admin_path($this->config));
        }

        clear_admin_user();
        flash('success', 'You have been signed out.');
        redirect(admin_path($this->config, 'login'));
    }
}