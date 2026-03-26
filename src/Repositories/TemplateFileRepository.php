<?php

declare(strict_types=1);

namespace App\Repositories;

use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;

final class TemplateFileRepository
{
    private const ALLOWED_EXTENSIONS = ['tpl', 'css', 'js'];

    public function __construct(
        private readonly string $themePath,
        private readonly string $backupRootPath
    ) {
    }

    public function all(): array
    {
        $files = [];
        $basePath = $this->basePath();
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($basePath, FilesystemIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if (! $file->isFile()) {
                continue;
            }

            $extension = strtolower((string) $file->getExtension());
            if (! in_array($extension, self::ALLOWED_EXTENSIONS, true)) {
                continue;
            }

            $fullPath = $file->getRealPath();
            if ($fullPath === false) {
                continue;
            }

            $relativePath = str_replace('\\', '/', substr($fullPath, strlen($basePath) + 1));
            $files[] = [
                'path' => $relativePath,
                'name' => basename($relativePath),
                'extension' => $extension,
            ];
        }

        usort($files, static fn (array $left, array $right): int => strcmp($left['path'], $right['path']));

        return $files;
    }

    public function read(string $relativePath): array
    {
        $relativePath = $this->normalizeRelativePath($relativePath);
        $fullPath = $this->resolvePath($relativePath);
        $contents = file_get_contents($fullPath);

        if ($contents === false) {
            throw new RuntimeException('Unable to read template file.');
        }

        return [
            'path' => $relativePath,
            'name' => basename($relativePath),
            'content' => $contents,
            'extension' => strtolower((string) pathinfo($relativePath, PATHINFO_EXTENSION)),
        ];
    }

    public function write(string $relativePath, string $content): void
    {
        $relativePath = $this->normalizeRelativePath($relativePath);
        $fullPath = $this->resolvePath($relativePath);
        $currentContents = file_get_contents($fullPath);

        if ($currentContents === false) {
            throw new RuntimeException('Unable to read current template before saving.');
        }

        $this->createBackup($relativePath, $currentContents);

        $written = file_put_contents($fullPath, $content, LOCK_EX);
        if ($written === false) {
            throw new RuntimeException('Unable to save template file.');
        }
    }

    public function backups(string $relativePath, int $limit = 10): array
    {
        $relativePath = $this->normalizeRelativePath($relativePath);
        $backupDirectory = $this->backupDirectoryFor($relativePath);

        if (! is_dir($backupDirectory)) {
            return [];
        }

        $pattern = $backupDirectory . DIRECTORY_SEPARATOR . basename($relativePath) . '.*.bak';
        $matches = glob($pattern) ?: [];
        rsort($matches, SORT_STRING);

        $backups = [];
        foreach (array_slice($matches, 0, $limit) as $backupPath) {
            $backups[] = [
                'name' => basename($backupPath),
                'timestamp' => date('Y-m-d H:i:s', (int) filemtime($backupPath)),
                'size' => (int) filesize($backupPath),
            ];
        }

        return $backups;
    }

    public function restoreBackup(string $relativePath, string $backupName): void
    {
        $relativePath = $this->normalizeRelativePath($relativePath);
        $backupPath = $this->resolveBackupPath($relativePath, $backupName);
        $contents = file_get_contents($backupPath);

        if ($contents === false) {
            throw new RuntimeException('Unable to read backup file.');
        }

        $this->write($relativePath, $contents);
    }

    private function createBackup(string $relativePath, string $contents): void
    {
        $backupDirectory = $this->backupDirectoryFor($relativePath);
        if (! is_dir($backupDirectory) && ! mkdir($backupDirectory, 0777, true) && ! is_dir($backupDirectory)) {
            throw new RuntimeException('Unable to create backup directory.');
        }

        $timestamp = date('Ymd_His');
        $backupFilename = basename($relativePath) . '.' . $timestamp . '.bak';
        $backupPath = $backupDirectory . DIRECTORY_SEPARATOR . $backupFilename;

        $written = file_put_contents($backupPath, $contents, LOCK_EX);
        if ($written === false) {
            throw new RuntimeException('Unable to create backup file.');
        }
    }

    private function resolveBackupPath(string $relativePath, string $backupName): string
    {
        $backupName = basename(str_replace('\\', '/', $backupName));
        if ($backupName === '' || ! str_ends_with($backupName, '.bak')) {
            throw new RuntimeException('Invalid backup file name.');
        }

        $backupPath = $this->backupDirectoryFor($relativePath) . DIRECTORY_SEPARATOR . $backupName;
        if (! is_file($backupPath)) {
            throw new RuntimeException('Backup file not found.');
        }

        return $backupPath;
    }

    private function normalizeRelativePath(string $relativePath): string
    {
        $relativePath = trim(str_replace('\\', '/', $relativePath), '/');
        if ($relativePath === '' || str_contains($relativePath, '..')) {
            throw new RuntimeException('Invalid template path.');
        }

        $extension = strtolower(pathinfo($relativePath, PATHINFO_EXTENSION));
        if (! in_array($extension, self::ALLOWED_EXTENSIONS, true)) {
            throw new RuntimeException('This file type is not editable from the admin panel.');
        }

        return $relativePath;
    }

    private function resolvePath(string $relativePath): string
    {
        $basePath = $this->basePath();
        $fullPath = $basePath . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativePath);
        $resolvedDirectory = realpath(dirname($fullPath));

        if ($resolvedDirectory === false || ! str_starts_with($resolvedDirectory, $basePath)) {
            throw new RuntimeException('Template path is outside the active theme.');
        }

        if (! is_file($fullPath)) {
            throw new RuntimeException('Template file not found.');
        }

        return $fullPath;
    }

    private function backupDirectoryFor(string $relativePath): string
    {
        $directory = dirname($relativePath);
        $directory = $directory === '.' ? '' : str_replace('/', DIRECTORY_SEPARATOR, $directory);

        return $this->backupBasePath() . ($directory !== '' ? DIRECTORY_SEPARATOR . $directory : '');
    }

    private function basePath(): string
    {
        $basePath = realpath($this->themePath);

        if ($basePath === false || ! is_dir($basePath)) {
            throw new RuntimeException('Active theme path was not found.');
        }

        return $basePath;
    }

    private function backupBasePath(): string
    {
        $basePath = $this->backupRootPath . DIRECTORY_SEPARATOR . basename($this->basePath());

        if (! is_dir($basePath) && ! mkdir($basePath, 0777, true) && ! is_dir($basePath)) {
            throw new RuntimeException('Unable to initialize backup storage.');
        }

        return $basePath;
    }
}