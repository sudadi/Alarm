<?php

declare(strict_types=1);

session_start();

$appConfig = require __DIR__ . '/../config/app.php';
date_default_timezone_set((string) ($appConfig['timezone'] ?? 'Asia/Jakarta'));

require __DIR__ . '/../src/Database.php';
require __DIR__ . '/../src/helpers.php';
require __DIR__ . '/../src/Auth.php';
require __DIR__ . '/../src/AlarmRepository.php';
require __DIR__ . '/../src/DeviceMessageHandler.php';
require __DIR__ . '/../src/UserRepository.php';

function dashboard_page_for_role(string $role): string
{
    return match ($role) {
        'codered' => 'dashboard-red',
        'codeblue' => 'dashboard-blue',
        default => 'dashboard-admin',
    };
}

function dashboard_role_for_page(string $page): ?string
{
    return match ($page) {
        'dashboard-red' => 'codered',
        'dashboard-blue' => 'codeblue',
        default => null,
    };
}

function dashboard_theme_for_page(string $page): string
{
    return match ($page) {
        'dashboard-red' => 'theme-red',
        'dashboard-blue' => 'theme-blue',
        default => 'theme-admin',
    };
}

function filter_alarms_by_role(array $alarms, ?string $role): array
{
    if ($role === null) {
        return $alarms;
    }

    return array_values(array_filter($alarms, static fn (array $alarm): bool => strtolower((string) ($alarm['btn_type'] ?? '')) === substr($role, 4)));
}

function summarize_alarms_by_role(array $alarms, ?string $role): int
{
    if ($role === null) {
        return count($alarms);
    }

    return count(filter_alarms_by_role($alarms, $role));
}

function role_from_dashboard_request(?string $requestedRole, ?string $currentRole): ?string
{
    $normalizedRole = match ($requestedRole) {
        'red', 'codered' => 'codered',
        'blue', 'codeblue' => 'codeblue',
        default => null,
    };

    if ($normalizedRole === null) {
        return $currentRole;
    }

    if ($currentRole === 'admin' || $currentRole === $normalizedRole) {
        return $normalizedRole;
    }

    return $currentRole;
}

function json_response(array $payload, int $statusCode = 200): never
{
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function json_request_payload(): array
{
    $rawPayload = file_get_contents('php://input');
    $payload = json_decode($rawPayload === false ? '' : $rawPayload, true);

    if (!is_array($payload)) {
        json_response([
            'ok' => false,
            'error' => 'Payload JSON tidak valid.',
        ], 400);
    }

    return $payload;
}

function load_env_file(string $path): void
{
    if (!is_file($path)) {
        return;
    }

    foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);

        if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
            continue;
        }

        [$key, $value] = explode('=', $line, 2);
        $key = trim($key);
        $value = trim($value, " \t\n\r\0\x0B\"'");

        if ($key !== '' && getenv($key) === false) {
            putenv($key . '=' . $value);
        }
    }
}

function require_api_key(): void
{
    load_env_file(__DIR__ . '/../.env');

    $expectedApiKey = (string) getenv('API_KEY');
    $providedApiKey = trim((string) ($_SERVER['HTTP_X_API_KEY'] ?? ''));

    if ($expectedApiKey === '') {
        json_response([
            'ok' => false,
            'error' => 'API key server belum dikonfigurasi.',
        ], 500);
    }

    if ($providedApiKey === '' || !hash_equals($expectedApiKey, $providedApiKey)) {
        json_response([
            'ok' => false,
            'error' => 'API key tidak valid.',
        ], 401);
    }
}

$alarmRepository = new AlarmRepository();
$deviceMessageHandler = new DeviceMessageHandler($alarmRepository);
$userRepository = new UserRepository();
$currentUser = current_user();
$page = $_GET['page'] ?? ($currentUser ? dashboard_page_for_role((string) $currentUser['role']) : 'login');

if ($page === 'dashboard' && $currentUser) {
    redirect('/?page=' . dashboard_page_for_role((string) $currentUser['role']));
}

if ($page === 'login' && is_logged_in()) {
    redirect('/?page=' . dashboard_page_for_role((string) current_user()['role']));
}

if ($page === 'logout') {
    Auth::logout();
    flash('success', 'Anda berhasil logout.');
    redirect('/?page=login');
}

if ($page === 'api/dashboard') {
    require_login();
    $requestedRole = role_from_dashboard_request($_GET['role'] ?? null, (string) (current_user()['role'] ?? 'admin'));
    $activeAlarms = $alarmRepository->activeAlarms();
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'summary' => $alarmRepository->counts(),
        'devices' => $alarmRepository->devices(),
        'active_alarms' => filter_alarms_by_role($activeAlarms, $requestedRole === 'admin' ? null : $requestedRole),
        'active_alarm_count' => summarize_alarms_by_role($activeAlarms, $requestedRole === 'admin' ? null : $requestedRole),
        'dashboard_role' => $requestedRole,
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

if ($page === 'api/device') {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        json_response([
            'ok' => false,
            'error' => 'Method tidak diizinkan. Gunakan POST.',
        ], 405);
    }

    require_api_key();

    $payload = json_request_payload();

    try {
        $result = $deviceMessageHandler->handle($payload);

        json_response([
            'ok' => true,
            'message' => $result['message'],
            'data' => $result['data'],
        ]);
    } catch (InvalidArgumentException $exception) {
        json_response([
            'ok' => false,
            'error' => $exception->getMessage(),
        ], 422);
    } catch (Throwable $throwable) {
        json_response([
            'ok' => false,
            'error' => 'Gagal memproses payload device.',
        ], 500);
    }
}

if ($page === 'login' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim((string) ($_POST['username'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');

    if (Auth::login($username, $password)) {
        flash('success', 'Selamat datang kembali.');
        redirect('/?page=' . dashboard_page_for_role((string) current_user()['role']));
    }

    flash('error', 'Username atau password tidak valid.');
    redirect('/?page=login');
}

if (in_array($page, ['dashboard', 'dashboard-red', 'dashboard-blue', 'dashboard-admin', 'history', 'users', 'buttons', 'api/dashboard'], true)) {
    require_login();
}

$dashboardRole = dashboard_role_for_page($page);
$dashboardMode = $dashboardRole ?? 'admin';
$dashboardTheme = dashboard_theme_for_page($page);
$dashboardTitle = match ($dashboardMode) {
    'codered' => 'Dashboard CodeRed',
    'codeblue' => 'Dashboard CodeBlue',
    default => 'Dashboard Monitoring',
};
$dashboardSubtitle = match ($dashboardMode) {
    'codered' => 'Pantau alarm dan device untuk jalur codered.',
    'codeblue' => 'Pantau alarm dan device untuk jalur codeblue.',
    default => 'Pantau status codered dan codeblue secara real-time.',
};
$dashboardAlarmType = $dashboardRole === null ? null : ucfirst(substr($dashboardRole, 4));
$dashboardApiUrl = $dashboardRole === null
    ? '/?page=api/dashboard'
    : '/?page=api/dashboard&role=' . strtolower((string) $dashboardAlarmType);

if ($page === 'users') {
    require_role(['admin']);
}

if ($page === 'buttons') {
    require_role(['admin']);
}

if ($page === 'dashboard-red') {
    require_role(['codered', 'admin']);
}

if ($page === 'dashboard-blue') {
    require_role(['codeblue', 'admin']);
}

if ($page === 'dashboard-admin') {
    require_role(['admin']);
}

if ($page === 'users' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = (string) ($_POST['action'] ?? '');

    if ($action === 'create') {
        $userRepository->create([
            'username' => trim((string) ($_POST['username'] ?? '')),
            'password' => (string) ($_POST['password'] ?? ''),
            'full_name' => trim((string) ($_POST['full_name'] ?? '')),
            'role' => (string) ($_POST['role'] ?? 'codered'),
            'is_active' => isset($_POST['is_active']) ? 1 : 0,
        ]);
        flash('success', 'User berhasil ditambahkan.');
    }

    if ($action === 'toggle') {
        $userRepository->toggleActive((int) ($_POST['id'] ?? 0), (bool) ((int) ($_POST['is_active'] ?? 0)));
        flash('success', 'Status user berhasil diperbarui.');
    }

    if ($action === 'delete') {
        $userRepository->delete((int) ($_POST['id'] ?? 0));
        flash('success', 'User berhasil dihapus.');
    }

    redirect('/?page=users');
}

if ($page === 'buttons' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = (string) ($_POST['action'] ?? '');

    if ($action === 'create') {
        try {
            $alarmRepository->createDevice([
                'btn_code' => trim((string) ($_POST['btn_code'] ?? '')),
                'location' => trim((string) ($_POST['location'] ?? '')),
                'ip' => trim((string) ($_POST['ip'] ?? '')),
                'status' => (string) ($_POST['status'] ?? 'Disconnect'),
            ]);
            flash('success', 'Button / satelit berhasil ditambahkan.');
        } catch (Throwable $throwable) {
            flash('error', 'Gagal menambah button. Pastikan kode dan IP belum dipakai.');
        }
    }

    redirect('/?page=buttons');
}

$viewData = [
    'page' => $page,
    'user' => current_user(),
    'summary' => $alarmRepository->counts(),
    'devices' => $alarmRepository->devices(),
    'activeAlarms' => filter_alarms_by_role($alarmRepository->activeAlarms(), $dashboardRole),
    'dashboardRole' => $dashboardMode,
    'dashboardTheme' => $dashboardTheme,
    'dashboardTitle' => $dashboardTitle,
    'dashboardSubtitle' => $dashboardSubtitle,
    'dashboardAlarmType' => $dashboardAlarmType,
    'dashboardApiUrl' => $dashboardApiUrl,
    'dashboardActiveCount' => summarize_alarms_by_role($alarmRepository->activeAlarms(), $dashboardRole),
    'logs' => $alarmRepository->logs(150),
    'users' => $userRepository->all(),
    'devicesList' => $alarmRepository->devices(),
    'flashSuccess' => flash('success'),
    'flashError' => flash('error'),
];

function render(string $view, array $data = []): void
{
    extract($data, EXTR_SKIP);
    ob_start();
    require __DIR__ . '/../views/pages/' . $view . '.php';
    $content = ob_get_clean();
    require __DIR__ . '/../views/layouts/main.php';
}

switch ($page) {
    case 'dashboard-red':
    case 'dashboard-blue':
    case 'dashboard-admin':
        render('dashboard', $viewData);
        break;
    case 'history':
        render('history', $viewData);
        break;
    case 'users':
        render('users', $viewData);
        break;
    case 'buttons':
        render('buttons', $viewData);
        break;
    case 'dashboard':
        redirect('/?page=' . dashboard_page_for_role((string) current_user()['role']));
        break;
    case 'login':
    default:
        $viewData['page'] = 'login';
        render('login', $viewData);
        break;
}
