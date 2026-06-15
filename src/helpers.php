<?php

declare(strict_types=1);

function app_config(string $key, mixed $default = null): mixed
{
    static $config;

    if ($config === null) {
        $config = require __DIR__ . '/../config/app.php';
    }

    return $config[$key] ?? $default;
}

function app_name(): string
{
    return (string) app_config('app_name', 'Alarm Monitoring');
}

function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function redirect(string $path): never
{
    header('Location: ' . $path);
    exit;
}

function flash(string $key, ?string $message = null): ?string
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }

    if ($message !== null) {
        $_SESSION['_flash'][$key] = $message;
        return null;
    }

    if (!isset($_SESSION['_flash'][$key])) {
        return null;
    }

    $value = $_SESSION['_flash'][$key];
    unset($_SESSION['_flash'][$key]);
    return $value;
}

function current_user(): ?array
{
    return $_SESSION['user'] ?? null;
}

function is_logged_in(): bool
{
    return current_user() !== null;
}

function require_login(): void
{
    if (!is_logged_in()) {
        redirect('/?page=login');
    }
}

function require_role(array $roles): void
{
    $user = current_user();

    if ($user === null || !in_array($user['role'], $roles, true)) {
        http_response_code(403);
        echo 'Forbidden';
        exit;
    }
}

function base_path(string $path = ''): string
{
    $base = (string) app_config('base_url', '');
    return rtrim($base, '/') . $path;
}

function status_badge(string $status): string
{
    return $status === 'Connected'
        ? '<span class="badge bg-success">Connected</span>'
        : '<span class="badge bg-secondary">Disconnect</span>';
}
