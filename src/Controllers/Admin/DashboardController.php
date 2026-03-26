<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

final class DashboardController
{
    public function __construct(private readonly array $config)
    {
    }

    public function index(): void
    {
        require_admin_auth($this->config);

        render_admin_view($this->config, 'dashboard', [
            'title' => 'Admin Dashboard',
        ]);
    }
}