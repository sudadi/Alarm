<?php

declare(strict_types=1);

final class UserRepository
{
    public function all(): array
    {
        return Database::pdo()->query('SELECT id, username, full_name, role, is_active, created_at FROM users ORDER BY created_at DESC, id DESC')->fetchAll();
    }

    public function create(array $data): void
    {
        $statement = Database::pdo()->prepare(
            'INSERT INTO users (username, password, full_name, role, is_active) VALUES (:username, :password, :full_name, :role, :is_active)'
        );

        $statement->execute([
            'username' => $data['username'],
            'password' => password_hash($data['password'], PASSWORD_DEFAULT),
            'full_name' => $data['full_name'],
            'role' => $data['role'],
            'is_active' => (int) ($data['is_active'] ?? 1),
        ]);
    }

    public function toggleActive(int $id, bool $active): void
    {
        $statement = Database::pdo()->prepare('UPDATE users SET is_active = :active WHERE id = :id');
        $statement->execute([
            'active' => (int) $active,
            'id' => $id,
        ]);
    }

    public function delete(int $id): void
    {
        $statement = Database::pdo()->prepare('DELETE FROM users WHERE id = :id');
        $statement->execute(['id' => $id]);
    }
}
