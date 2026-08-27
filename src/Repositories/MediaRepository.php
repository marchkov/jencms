<?php

declare(strict_types=1);

namespace App\Repositories;

use DirectoryIterator;
use RuntimeException;

final class MediaRepository
{
    private const MAX_FILE_SIZE = 10485760;

    private const ALLOWED_MIME_TYPES = [
        'jpg' => ['image/jpeg'],
        'jpeg' => ['image/jpeg'],
        'png' => ['image/png'],
        'gif' => ['image/gif'],
        'webp' => ['image/webp'],
        'pdf' => ['application/pdf'],
        'doc' => ['application/msword', 'application/CDFV2'],
        'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/zip'],
        'xls' => ['application/vnd.ms-excel', 'application/CDFV2'],
        'xlsx' => ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/zip'],
        'ppt' => ['application/vnd.ms-powerpoint', 'application/CDFV2'],
        'pptx' => ['application/vnd.openxmlformats-officedocument.presentationml.presentation', 'application/zip'],
        'zip' => ['application/zip', 'application/x-zip-compressed'],
        'txt' => ['text/plain'],
        'mp4' => ['video/mp4'],
        'webm' => ['video/webm'],
        'mp3' => ['audio/mpeg'],
        'ogg' => ['audio/ogg', 'video/ogg', 'application/ogg'],
    ];

    public function __construct(
        private readonly string $uploadDir,
        private readonly string $publicPrefix = '/uploads'
    ) {
    }

    public function all(): array
    {
        $this->ensureUploadDir();

        $items = [];
        foreach (new DirectoryIterator($this->uploadDir) as $fileInfo) {
            if (! $fileInfo->isFile() || $fileInfo->isDot()) {
                continue;
            }

            $filename = $fileInfo->getFilename();
            $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            $items[] = [
                'name' => $filename,
                'path' => $this->publicPath($filename),
                'size' => $fileInfo->getSize(),
                'modified_at' => date('Y-m-d H:i', $fileInfo->getMTime()),
                'is_image' => in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'], true),
            ];
        }

        usort($items, static fn (array $a, array $b): int => strcmp($b['modified_at'], $a['modified_at']));

        return $items;
    }

    public function upload(array $file): string
    {
        $this->ensureUploadDir();

        $error = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);
        if ($error === UPLOAD_ERR_NO_FILE) {
            throw new RuntimeException('No file was uploaded.');
        }

        if ($error !== UPLOAD_ERR_OK) {
            throw new RuntimeException('The uploaded file could not be saved.');
        }

        $size = (int) ($file['size'] ?? 0);
        if ($size < 1 || $size > self::MAX_FILE_SIZE) {
            throw new RuntimeException('Files must be between 1 byte and 10 MB.');
        }

        $originalName = (string) ($file['name'] ?? '');
        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        if (! isset(self::ALLOWED_MIME_TYPES[$extension])) {
            throw new RuntimeException('That file type is not allowed.');
        }

        $baseName = pathinfo($originalName, PATHINFO_FILENAME);
        $safeBaseName = $this->sanitizeBaseName($baseName);
        $targetName = $safeBaseName . '-' . date('YmdHis') . '-' . substr(bin2hex(random_bytes(4)), 0, 8) . '.' . $extension;
        $targetPath = $this->uploadDir . DIRECTORY_SEPARATOR . $targetName;

        $tmpName = (string) ($file['tmp_name'] ?? '');
        if ($tmpName === '' || ! is_uploaded_file($tmpName)) {
            throw new RuntimeException('Invalid uploaded file.');
        }

        if (! class_exists(\finfo::class)) {
            throw new RuntimeException('The PHP fileinfo extension is required for secure uploads.');
        }

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->file($tmpName);
        if (! is_string($mimeType) || ! in_array($mimeType, self::ALLOWED_MIME_TYPES[$extension], true)) {
            throw new RuntimeException('The file content does not match its extension.');
        }

        if (! move_uploaded_file($tmpName, $targetPath)) {
            throw new RuntimeException('Unable to move the uploaded file.');
        }

        @chmod($targetPath, 0644);

        return $this->publicPath($targetName);
    }

    public function delete(string $publicPath): void
    {
        $fullPath = $this->resolvePublicPath($publicPath);

        if ($fullPath === null || ! is_file($fullPath)) {
            return;
        }

        unlink($fullPath);
    }

    private function ensureUploadDir(): void
    {
        if (! is_dir($this->uploadDir) && ! mkdir($concurrentDirectory = $this->uploadDir, 0755, true) && ! is_dir($concurrentDirectory)) {
            throw new RuntimeException('Unable to create the upload directory.');
        }
    }

    private function publicPath(string $filename): string
    {
        return rtrim($this->publicPrefix, '/') . '/' . ltrim($filename, '/');
    }

    private function resolvePublicPath(string $publicPath): ?string
    {
        $normalizedPrefix = rtrim($this->publicPrefix, '/');
        if (! str_starts_with($publicPath, $normalizedPrefix . '/')) {
            return null;
        }

        $filename = basename($publicPath);
        $fullPath = realpath($this->uploadDir . DIRECTORY_SEPARATOR . $filename);
        $uploadRoot = realpath($this->uploadDir);

        if ($fullPath === false || $uploadRoot === false || ! str_starts_with($fullPath, $uploadRoot)) {
            return null;
        }

        return $fullPath;
    }

    private function sanitizeBaseName(string $name): string
    {
        $name = strtolower(trim($name));
        $name = preg_replace('/[^a-z0-9]+/', '-', $name) ?: 'file';
        $name = trim($name, '-');

        return $name !== '' ? $name : 'file';
    }
}
