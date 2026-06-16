<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Database;

class UserRepository
{
    public function count(): int
    {
        $stmt = Database::connection()->query('SELECT COUNT(*) FROM users');
        return (int) $stmt->fetchColumn();
    }

    public function findById(int $id): ?array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM users WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function findByEmail(string $email): ?array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM users WHERE email = :email');
        $stmt->execute(['email' => strtolower(trim($email))]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /** @return array<int, array> */
    public function all(): array
    {
        $stmt = Database::connection()->query('SELECT * FROM users ORDER BY created_at DESC');
        return $stmt->fetchAll();
    }

    public function countByRole(string $role): int
    {
        $stmt = Database::connection()->prepare('SELECT COUNT(*) FROM users WHERE role = :role AND is_active = 1');
        $stmt->execute(['role' => $role]);
        return (int) $stmt->fetchColumn();
    }

    public function create(array $data): array
    {
        $now = nowIso();
        $stmt = Database::connection()->prepare(
            'INSERT INTO users (email, password_hash, name, role, is_active, created_at, updated_at)
             VALUES (:email, :password_hash, :name, :role, :is_active, :created_at, :updated_at)'
        );
        $stmt->execute([
            'email' => strtolower(trim($data['email'])),
            'password_hash' => password_hash($data['password'], PASSWORD_DEFAULT),
            'name' => trim($data['name']),
            'role' => $data['role'],
            'is_active' => !empty($data['is_active']) ? 1 : 0,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return $this->findById((int) Database::connection()->lastInsertId());
    }

    public function update(int $id, array $data): ?array
    {
        $user = $this->findById($id);
        if ($user === null) {
            return null;
        }

        $fields = [];
        $params = ['id' => $id];

        if (isset($data['email'])) {
            $fields[] = 'email = :email';
            $params['email'] = strtolower(trim($data['email']));
        }
        if (isset($data['name'])) {
            $fields[] = 'name = :name';
            $params['name'] = trim($data['name']);
        }
        if (isset($data['role'])) {
            $fields[] = 'role = :role';
            $params['role'] = $data['role'];
        }
        if (array_key_exists('is_active', $data)) {
            $fields[] = 'is_active = :is_active';
            $params['is_active'] = !empty($data['is_active']) ? 1 : 0;
        }
        if (!empty($data['password'])) {
            $fields[] = 'password_hash = :password_hash';
            $params['password_hash'] = password_hash($data['password'], PASSWORD_DEFAULT);
        }

        if ($fields === []) {
            return $user;
        }

        $fields[] = 'updated_at = :updated_at';
        $params['updated_at'] = nowIso();

        $sql = 'UPDATE users SET ' . implode(', ', $fields) . ' WHERE id = :id';
        Database::connection()->prepare($sql)->execute($params);

        return $this->findById($id);
    }
}
