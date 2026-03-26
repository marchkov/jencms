<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

final class UserRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function findActiveByLogin(string $login): ?array
    {
        $statement = $this->pdo->prepare('SELECT * FROM users WHERE login = :login AND is_active = 1 LIMIT 1');
        $statement->execute(['login' => $login]);
        $user = $statement->fetch(PDO::FETCH_ASSOC);

        return $user ?: null;
    }

    public function all(array $filters = []): array
    {
        $sql = 'SELECT * FROM users';
        $params = [];
        $conditions = [];

        $query = trim((string) ($filters['q'] ?? ''));
        if ($query !== '') {
            $conditions[] = '(login LIKE :query OR name LIKE :query OR email LIKE :query)';
            $params['query'] = '%' . $query . '%';
        }

        $status = (string) ($filters['status'] ?? '');
        if ($status === 'active') {
            $conditions[] = 'is_active = 1';
        } elseif ($status === 'inactive') {
            $conditions[] = 'is_active = 0';
        }

        if ($conditions !== []) {
            $sql .= ' WHERE ' . implode(' AND ', $conditions);
        }

        $sql .= ' ORDER BY CASE role WHEN "administrator" THEN 0 ELSE 1 END ASC, is_active DESC, login ASC, id ASC';

        $statement = $this->pdo->prepare($sql);
        $statement->execute($params);

        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findById(int $id): ?array
    {
        $statement = $this->pdo->prepare('SELECT * FROM users WHERE id = :id LIMIT 1');
        $statement->execute(['id' => $id]);
        $user = $statement->fetch(PDO::FETCH_ASSOC);

        return $user ?: null;
    }

    public function loginExists(string $login, ?int $exceptId = null): bool
    {
        $sql = 'SELECT COUNT(*) FROM users WHERE login = :login';
        $params = ['login' => $login];

        if ($exceptId !== null) {
            $sql .= ' AND id != :id';
            $params['id'] = $exceptId;
        }

        $statement = $this->pdo->prepare($sql);
        $statement->execute($params);

        return (int) $statement->fetchColumn() > 0;
    }

    public function emailExists(string $email, ?int $exceptId = null): bool
    {
        $sql = 'SELECT COUNT(*) FROM users WHERE email = :email';
        $params = ['email' => $email];

        if ($exceptId !== null) {
            $sql .= ' AND id != :id';
            $params['id'] = $exceptId;
        }

        $statement = $this->pdo->prepare($sql);
        $statement->execute($params);

        return (int) $statement->fetchColumn() > 0;
    }

    public function create(array $user): int
    {
        $statement = $this->pdo->prepare(
            'INSERT INTO users (login, password_hash, name, email, role, is_active, created_at, updated_at)
             VALUES (:login, :password_hash, :name, :email, :role, :is_active, :created_at, :updated_at)'
        );
        $statement->execute($user);

        return (int) $this->pdo->lastInsertId();
    }

    public function update(int $id, array $user): void
    {
        $user['id'] = $id;
        $sql = 'UPDATE users
                   SET login = :login,
                       name = :name,
                       email = :email,
                       role = :role,
                       is_active = :is_active,
                       updated_at = :updated_at';

        if (isset($user['password_hash'])) {
            $sql .= ', password_hash = :password_hash';
        }

        $sql .= ' WHERE id = :id';

        $statement = $this->pdo->prepare($sql);
        $statement->execute($user);
    }

    public function updatePassword(int $id, string $passwordHash): void
    {
        $statement = $this->pdo->prepare(
            'UPDATE users
                SET password_hash = :password_hash,
                    updated_at = :updated_at
              WHERE id = :id'
        );
        $statement->execute([
            'id' => $id,
            'password_hash' => $passwordHash,
            'updated_at' => date('c'),
        ]);
    }

    public function delete(int $id): void
    {
        $statement = $this->pdo->prepare('DELETE FROM users WHERE id = :id');
        $statement->execute(['id' => $id]);
    }

    public function countActiveUsers(?int $exceptId = null): int
    {
        $sql = 'SELECT COUNT(*) FROM users WHERE is_active = 1';
        $params = [];

        if ($exceptId !== null) {
            $sql .= ' AND id != :id';
            $params['id'] = $exceptId;
        }

        $statement = $this->pdo->prepare($sql);
        $statement->execute($params);

        return (int) $statement->fetchColumn();
    }

    public function countAdministrators(?int $exceptId = null, ?int $onlyActive = null): int
    {
        $sql = 'SELECT COUNT(*) FROM users WHERE role = :role';
        $params = ['role' => 'administrator'];

        if ($exceptId !== null) {
            $sql .= ' AND id != :id';
            $params['id'] = $exceptId;
        }

        if ($onlyActive !== null) {
            $sql .= ' AND is_active = :is_active';
            $params['is_active'] = $onlyActive;
        }

        $statement = $this->pdo->prepare($sql);
        $statement->execute($params);

        return (int) $statement->fetchColumn();
    }
}
