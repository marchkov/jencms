<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Repositories\UserRepository;

final class UserController
{
    public function __construct(
        private readonly UserRepository $users,
        private readonly array $config
    ) {
    }

    public function index(): void
    {
        require_admin_role($this->config, ['administrator']);

        $filters = $this->listFilters();

        render_admin_view($this->config, 'users/index', [
            'title' => 'Users',
            'users' => $this->users->all($filters),
            'filters' => $filters,
        ]);
    }

    public function create(): void
    {
        require_admin_role($this->config, ['administrator']);

        render_admin_view($this->config, 'users/form', [
            'title' => 'New User',
            'formAction' => admin_path($this->config, 'users/create'),
            'userForm' => $this->emptyUser(),
            'errors' => [],
            'submitLabel' => 'Create user',
            'isEdit' => false,
        ]);
    }

    public function store(): void
    {
        require_admin_role($this->config, ['administrator']);

        [$user, $errors] = $this->validateForm();

        if ($errors !== []) {
            render_admin_view($this->config, 'users/form', [
                'title' => 'New User',
                'formAction' => admin_path($this->config, 'users/create'),
                'userForm' => $user,
                'errors' => $errors,
                'submitLabel' => 'Create user',
                'isEdit' => false,
            ]);
            return;
        }

        $now = date('c');
        $this->users->create([
            'login' => $user['login'],
            'password_hash' => password_hash($user['password'], PASSWORD_DEFAULT),
            'name' => $user['name'],
            'email' => $user['email'],
            'role' => $user['role'],
            'is_active' => $user['is_active'],
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        flash('success', 'User created.');
        redirect(admin_path($this->config, 'users'));
    }

    public function edit(int $id): void
    {
        require_admin_role($this->config, ['administrator']);

        $user = $this->users->findById($id);
        if ($user === null) {
            flash('error', 'User not found.');
            redirect(admin_path($this->config, 'users'));
        }

        render_admin_view($this->config, 'users/form', [
            'title' => 'Edit User',
            'formAction' => admin_path($this->config, 'users/' . $id . '/edit'),
            'userForm' => $this->normalizeUser($user),
            'errors' => [],
            'submitLabel' => 'Save changes',
            'isEdit' => true,
        ]);
    }

    public function update(int $id): void
    {
        require_admin_role($this->config, ['administrator']);

        $existingUser = $this->users->findById($id);
        if ($existingUser === null) {
            flash('error', 'User not found.');
            redirect(admin_path($this->config, 'users'));
        }

        [$user, $errors] = $this->validateForm($id);

        if ($errors !== []) {
            $user['id'] = $id;
            render_admin_view($this->config, 'users/form', [
                'title' => 'Edit User',
                'formAction' => admin_path($this->config, 'users/' . $id . '/edit'),
                'userForm' => $user,
                'errors' => $errors,
                'submitLabel' => 'Save changes',
                'isEdit' => true,
            ]);
            return;
        }

        $payload = [
            'login' => $user['login'],
            'name' => $user['name'],
            'email' => $user['email'],
            'role' => $user['role'],
            'is_active' => $user['is_active'],
            'updated_at' => date('c'),
        ];

        if ($user['password'] !== '') {
            $payload['password_hash'] = password_hash($user['password'], PASSWORD_DEFAULT);
        }

        $this->users->update($id, $payload);

        if ((int) ($existingUser['id'] ?? 0) === (int) (admin_user()['id'] ?? 0)) {
            set_admin_user([
                'id' => $id,
                'login' => $user['login'],
                'name' => $user['name'],
                'email' => $user['email'],
                'role' => $user['role'],
            ]);
        }

        flash('success', 'Changes saved.');
        redirect(admin_path($this->config, 'users'));
    }

    public function resetPassword(int $id): void
    {
        require_admin_role($this->config, ['administrator']);

        $user = $this->users->findById($id);
        if ($user === null) {
            flash('error', 'User not found.');
            redirect(admin_path($this->config, 'users'));
        }

        render_admin_view($this->config, 'users/reset-password', [
            'title' => 'Reset Password',
            'formAction' => admin_path($this->config, 'users/' . $id . '/reset-password'),
            'targetUser' => $user,
            'errors' => [],
        ]);
    }

    public function updatePassword(int $id): void
    {
        require_admin_role($this->config, ['administrator']);

        $user = $this->users->findById($id);
        if ($user === null) {
            flash('error', 'User not found.');
            redirect(admin_path($this->config, 'users'));
        }

        $password = trim((string) ($_POST['password'] ?? ''));
        $confirmPassword = trim((string) ($_POST['password_confirm'] ?? ''));
        $errors = [];

        if (! verify_csrf_token($_POST['_csrf'] ?? null)) {
            $errors[] = 'Your session has expired. Refresh the page and try again.';
        }

        if ($password === '') {
            $errors[] = 'Password is required.';
        } elseif (strlen($password) < 8) {
            $errors[] = 'Password must contain at least 8 characters.';
        }

        if ($password !== $confirmPassword) {
            $errors[] = 'Password confirmation does not match.';
        }

        if ($errors !== []) {
            render_admin_view($this->config, 'users/reset-password', [
                'title' => 'Reset Password',
                'formAction' => admin_path($this->config, 'users/' . $id . '/reset-password'),
                'targetUser' => $user,
                'errors' => $errors,
            ]);
            return;
        }

        $this->users->updatePassword($id, password_hash($password, PASSWORD_DEFAULT));
        flash('success', 'Password updated.');
        redirect(admin_path($this->config, 'users'));
    }

    public function delete(int $id): void
    {
        require_admin_role($this->config, ['administrator']);

        if (! verify_csrf_token($_POST['_csrf'] ?? null)) {
            flash('error', 'Unable to confirm deletion.');
            redirect(admin_path($this->config, 'users'));
        }

        $currentUser = admin_user();
        if ((int) ($currentUser['id'] ?? 0) === $id) {
            flash('error', 'You cannot delete the currently signed-in user.');
            redirect(admin_path($this->config, 'users'));
        }

        $user = $this->users->findById($id);
        if ($user === null) {
            flash('error', 'User not found.');
            redirect(admin_path($this->config, 'users'));
        }

        if (($user['role'] ?? 'editor') === 'administrator' && $this->users->countAdministrators($id) < 1) {
            flash('error', 'At least one administrator must remain.');
            redirect(admin_path($this->config, 'users'));
        }

        if ((int) $user['is_active'] === 1 && $this->users->countActiveUsers($id) < 1) {
            flash('error', 'At least one active user must remain.');
            redirect(admin_path($this->config, 'users'));
        }

        $this->users->delete($id);
        flash('success', 'User deleted.');
        redirect(admin_path($this->config, 'users'));
    }

    private function validateForm(?int $userId = null): array
    {
        $user = [
            'id' => $userId,
            'login' => trim((string) ($_POST['login'] ?? '')),
            'name' => trim((string) ($_POST['name'] ?? '')),
            'email' => trim((string) ($_POST['email'] ?? '')),
            'password' => trim((string) ($_POST['password'] ?? '')),
            'role' => in_array((string) ($_POST['role'] ?? 'editor'), ['administrator', 'editor'], true) ? (string) $_POST['role'] : 'editor',
            'is_active' => isset($_POST['is_active']) ? 1 : 0,
        ];
        $errors = [];

        if (! verify_csrf_token($_POST['_csrf'] ?? null)) {
            $errors[] = 'Your session has expired. Refresh the page and try again.';
        }

        if ($user['login'] === '') {
            $errors[] = 'Login is required.';
        } elseif (! preg_match('/^[A-Za-z0-9._-]+$/', $user['login'])) {
            $errors[] = 'Login may only contain letters, numbers, dots, underscores, and hyphens.';
        } elseif ($this->users->loginExists($user['login'], $userId)) {
            $errors[] = 'That login is already in use.';
        }

        if ($user['name'] === '') {
            $errors[] = 'Name is required.';
        }

        if ($user['email'] === '') {
            $errors[] = 'Email is required.';
        } elseif (! filter_var($user['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Enter a valid email address.';
        } elseif ($this->users->emailExists($user['email'], $userId)) {
            $errors[] = 'That email is already in use.';
        }

        if ($userId === null && $user['password'] === '') {
            $errors[] = 'Password is required for a new user.';
        }

        if ($user['password'] !== '' && strlen($user['password']) < 8) {
            $errors[] = 'Password must contain at least 8 characters.';
        }

        $currentUser = admin_user();
        if ($userId !== null && (int) ($currentUser['id'] ?? 0) === $userId && $user['is_active'] !== 1) {
            $errors[] = 'You cannot deactivate the currently signed-in user.';
        }

        if ($userId !== null && (int) ($currentUser['id'] ?? 0) === $userId && $user['role'] !== 'administrator') {
            $errors[] = 'You cannot remove administrator access from the currently signed-in user.';
        }

        if ($userId !== null && $user['is_active'] !== 1 && $this->users->countActiveUsers($userId) < 1) {
            $errors[] = 'At least one active user must remain.';
        }

        if ($userId !== null && $user['role'] !== 'administrator' && $this->users->countAdministrators($userId) < 1) {
            $errors[] = 'At least one administrator must remain.';
        }

        return [$user, $errors];
    }

    private function emptyUser(): array
    {
        return [
            'login' => '',
            'name' => '',
            'email' => '',
            'password' => '',
            'role' => 'editor',
            'is_active' => 1,
        ];
    }

    private function normalizeUser(array $user): array
    {
        return [
            'id' => (int) $user['id'],
            'login' => (string) ($user['login'] ?? ''),
            'name' => (string) ($user['name'] ?? ''),
            'email' => (string) ($user['email'] ?? ''),
            'password' => '',
            'role' => (string) ($user['role'] ?? 'editor'),
            'is_active' => (int) ($user['is_active'] ?? 1),
        ];
    }

    private function listFilters(): array
    {
        return [
            'q' => trim((string) ($_GET['q'] ?? '')),
            'status' => in_array((string) ($_GET['status'] ?? ''), ['active', 'inactive'], true) ? (string) $_GET['status'] : '',
        ];
    }
}
