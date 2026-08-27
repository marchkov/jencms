<?php

declare(strict_types=1);

namespace App\Support;

use FilesystemIterator;
use PDO;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Throwable;

final class SystemCheck
{
    public function __construct(
        private readonly PDO $pdo,
        private readonly array $config,
        private readonly string $basePath
    ) {
    }

    /** @return list<array{status: string, name: string, result: string, recommendation: string}> */
    public function run(): array
    {
        $theme = (string) ($this->config['site']['theme'] ?? 'default');

        return [
            $this->phpVersion(),
            $this->extension('pdo_sqlite', true),
            $this->extension('fileinfo', true),
            $this->extension('mbstring', false),
            $this->database(),
            $this->writableDirectory('Storage', $this->basePath . '/storage'),
            $this->writableDirectory('Uploads', $this->basePath . '/public/uploads'),
            $this->writableDirectory('Template backups', $this->basePath . '/storage/template-backups'),
            $this->theme($this->basePath . '/public/themes/' . $theme),
            $this->uploadLimits(),
            $this->defaultAdminPassword(),
            $this->privateFilesLocation(),
        ];
    }

    private function phpVersion(): array
    {
        $supported = version_compare(PHP_VERSION, '8.1.0', '>=');

        return $this->check(
            $supported ? 'ok' : 'error',
            'PHP version',
            PHP_VERSION,
            $supported ? '' : 'Upgrade PHP to version 8.1 or newer.'
        );
    }

    private function extension(string $extension, bool $required): array
    {
        $loaded = extension_loaded($extension);
        $status = $loaded ? 'ok' : ($required ? 'error' : 'warning');
        $recommendation = '';

        if (! $loaded) {
            $recommendation = $required
                ? 'Enable the PHP ' . $extension . ' extension.'
                : 'Enable the PHP ' . $extension . ' extension for better Unicode text handling.';
        }

        return $this->check($status, 'PHP extension: ' . $extension, $loaded ? 'Loaded' : 'Not loaded', $recommendation);
    }

    private function database(): array
    {
        try {
            $this->pdo->exec('BEGIN IMMEDIATE');
            $this->pdo->exec('ROLLBACK');

            return $this->check('ok', 'SQLite database', 'Writable', '');
        } catch (Throwable) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            $path = (string) ($this->config['database']['path'] ?? 'database file');

            return $this->check(
                'error',
                'SQLite database',
                'Not writable',
                'Grant the web-server account write access to the database file and its directory: ' . $path
            );
        }
    }

    private function writableDirectory(string $name, string $path): array
    {
        if (! is_dir($path)) {
            return $this->check(
                'error',
                $name,
                'Directory not found',
                'Create the directory and grant the web-server account write access: ' . $path
            );
        }

        $probe = $path . DIRECTORY_SEPARATOR . '.jencms-write-check-' . bin2hex(random_bytes(6));
        $handle = @fopen($probe, 'x');
        if ($handle === false) {
            return $this->check(
                'error',
                $name,
                'Not writable',
                'Grant the web-server account write access to this directory: ' . $path
            );
        }

        fclose($handle);
        @unlink($probe);

        return $this->check('ok', $name, 'Writable', '');
    }

    private function theme(string $path): array
    {
        if (! is_dir($path)) {
            return $this->check('error', 'Active theme', 'Directory not found', 'Check the active theme path: ' . $path);
        }

        $notWritable = [];
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if (! $file->isFile() || ! in_array(strtolower($file->getExtension()), ['tpl', 'css', 'js'], true)) {
                continue;
            }

            if (! is_writable($file->getPathname())) {
                $notWritable[] = $file->getFilename();
            }
        }

        if ($notWritable !== []) {
            return $this->check(
                'warning',
                'Active theme',
                count($notWritable) . ' editable file(s) are read-only',
                'Grant write access only if templates will be edited in JenCMS: ' . implode(', ', array_slice($notWritable, 0, 5))
            );
        }

        return $this->check('ok', 'Active theme', 'Editable files are writable', '');
    }

    private function uploadLimits(): array
    {
        $upload = (string) ini_get('upload_max_filesize');
        $post = (string) ini_get('post_max_size');
        $effective = min($this->iniBytes($upload), $this->iniBytes($post));
        $enough = $effective >= 10 * 1024 * 1024;

        return $this->check(
            $enough ? 'ok' : 'warning',
            'PHP upload limits',
            'upload_max_filesize=' . $upload . ', post_max_size=' . $post,
            $enough ? '' : 'JenCMS accepts files up to 10 MB; increase both PHP limits if larger uploads are needed.'
        );
    }

    private function defaultAdminPassword(): array
    {
        $statement = $this->pdo->prepare(
            'SELECT password_hash FROM users WHERE login = :login AND is_active = 1 LIMIT 1'
        );
        $statement->execute(['login' => 'admin']);
        $hash = $statement->fetchColumn();
        $usesDefault = is_string($hash) && password_verify('admin123', $hash);

        return $this->check(
            $usesDefault ? 'warning' : 'ok',
            'Administrator password',
            $usesDefault ? 'Default admin password is active' : 'Default admin password is not active',
            $usesDefault ? 'Change the admin password before publishing the site.' : ''
        );
    }

    private function privateFilesLocation(): array
    {
        $public = realpath($this->basePath . '/public');
        $database = realpath((string) ($this->config['database']['path'] ?? ''));
        $storage = realpath($this->basePath . '/storage');
        $unsafe = $public !== false && (
            ($database !== false && $this->isInside($database, $public))
            || ($storage !== false && $this->isInside($storage, $public))
        );

        return $this->check(
            $unsafe ? 'error' : 'ok',
            'Private files',
            $unsafe ? 'Located under the public directory' : 'Located outside the public directory',
            $unsafe ? 'Move the SQLite database and storage directory outside public/.' : ''
        );
    }

    private function isInside(string $path, string $directory): bool
    {
        $path = rtrim(str_replace('\\', '/', $path), '/');
        $directory = rtrim(str_replace('\\', '/', $directory), '/');

        return $path === $directory || str_starts_with($path, $directory . '/');
    }

    private function iniBytes(string $value): int
    {
        $value = trim($value);
        if ($value === '') {
            return 0;
        }

        $number = (float) $value;
        return match (strtolower(substr($value, -1))) {
            'g' => (int) ($number * 1024 * 1024 * 1024),
            'm' => (int) ($number * 1024 * 1024),
            'k' => (int) ($number * 1024),
            default => (int) $number,
        };
    }

    private function check(string $status, string $name, string $result, string $recommendation): array
    {
        return compact('status', 'name', 'result', 'recommendation');
    }
}
