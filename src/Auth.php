<?php

declare(strict_types=1);

final class Auth
{
    public static function login(string $username, string $password): bool
    {
        $pdo = Database::pdo();
        $statement = $pdo->prepare('SELECT id, username, password, full_name, role, is_active FROM users WHERE username = :username LIMIT 1');
        $statement->execute(['username' => $username]);
        $user = $statement->fetch();

        if (!$user || !(bool) $user['is_active']) {
            return false;
        }

        if (!password_verify($password, $user['password'])) {
            return false;
        }

        unset($user['password']);
        $_SESSION['user'] = $user;
        return true;
    }

    public static function logout(): void
    {
        unset($_SESSION['user']);
        session_regenerate_id(true);
    }
}
