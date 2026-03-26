<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;
use Throwable;

final class SettingsRepository
{
    private const EDITABLE_KEYS = [
        'site.name',
        'site.homepage_slug',
        'site.default_keywords',
        'site.default_description',
        'content.posts_per_page',
    ];

    public function __construct(private readonly PDO $pdo)
    {
    }

    public function loadOverrides(): array
    {
        $statement = $this->pdo->query('SELECT key_name, value FROM settings');
        $rows = $statement->fetchAll(PDO::FETCH_ASSOC);
        $overrides = [];

        foreach ($rows as $row) {
            $key = (string) $row['key_name'];
            if (! in_array($key, self::EDITABLE_KEYS, true)) {
                continue;
            }

            $segments = explode('.', $key);
            $target = &$overrides;
            foreach ($segments as $index => $segment) {
                if ($index === count($segments) - 1) {
                    $target[$segment] = $this->decodeValue($key, (string) $row['value']);
                    continue;
                }

                if (! isset($target[$segment]) || ! is_array($target[$segment])) {
                    $target[$segment] = [];
                }
                $target = &$target[$segment];
            }
            unset($target);
        }

        return $overrides;
    }

    public function saveEditableSettings(array $values): void
    {
        $this->pdo->beginTransaction();

        try {
            $delete = $this->pdo->prepare('DELETE FROM settings WHERE key_name = :key_name');
            $insert = $this->pdo->prepare('INSERT INTO settings (key_name, value, updated_at) VALUES (:key_name, :value, :updated_at)');
            $timestamp = date('c');

            foreach (self::EDITABLE_KEYS as $key) {
                $delete->execute(['key_name' => $key]);
                if (! array_key_exists($key, $values)) {
                    continue;
                }

                $insert->execute([
                    'key_name' => $key,
                    'value' => $this->encodeValue($key, $values[$key]),
                    'updated_at' => $timestamp,
                ]);
            }

            $this->pdo->commit();
        } catch (Throwable $exception) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            throw $exception;
        }
    }

    private function decodeValue(string $key, string $value): mixed
    {
        if ($key === 'content.posts_per_page') {
            return (int) $value;
        }

        return $value;
    }

    private function encodeValue(string $key, mixed $value): string
    {
        return (string) $value;
    }
}
