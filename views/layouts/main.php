<?php
/** @var string $content */
/** @var array|null $user */
/** @var string|null $flashSuccess */
/** @var string|null $flashError */
/** @var string $page */
/** @var string|null $dashboardRole */
/** @var string|null $dashboardTheme */
/** @var string|null $dashboardApiUrl */
?>
<?php
$userRole = $user['role'] ?? null;
$navDashboardLinks = [];

if ($userRole === 'codered') {
    $navDashboardLinks[] = ['label' => 'Dashboard Codered', 'href' => '/?page=dashboard-red', 'active' => $page === 'dashboard-red'];
}

if ($userRole === 'codeblue') {
    $navDashboardLinks[] = ['label' => 'Dashboard Codeblue', 'href' => '/?page=dashboard-blue', 'active' => $page === 'dashboard-blue'];
}

if ($userRole === 'admin') {
    $navDashboardLinks[] = ['label' => 'Dashboard Admin', 'href' => '/?page=dashboard-admin', 'active' => $page === 'dashboard-admin'];
    $navDashboardLinks[] = ['label' => 'Dashboard Codered', 'href' => '/?page=dashboard-red', 'active' => $page === 'dashboard-red'];
    $navDashboardLinks[] = ['label' => 'Dashboard Codeblue', 'href' => '/?page=dashboard-blue', 'active' => $page === 'dashboard-blue'];
}

$bodyTheme = $dashboardTheme ?? 'theme-admin';
$dashboardRoleValue = $dashboardRole ?? '';
$dashboardApiValue = $dashboardApiUrl ?? '/?page=api/dashboard';
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e(app_name()) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="/assets/css/app.css" rel="stylesheet">
</head>
<body class="app-shell <?= e($bodyTheme) ?>" data-dashboard-role="<?= e($dashboardRoleValue) ?>" data-dashboard-api="<?= e($dashboardApiValue) ?>">
<div class="app-bg"></div>
<div class="container-fluid">
    <div class="row min-vh-100">
        <?php if (!empty($user)) : ?>
            <aside class="col-12 col-lg-3 col-xl-2 p-0 sidebar">
                <div class="sidebar-inner">
                    <div class="brand-block">
                        <div class="brand-mark">A</div>
                        <div>
                            <div class="brand-title"><?= e(app_name()) ?></div>
                            <div class="brand-subtitle">
                                <?php if ($userRole === 'codered') : ?>Monitoring Codered<?php elseif ($userRole === 'codeblue') : ?>Monitoring Codeblue<?php elseif ($userRole === 'admin') : ?>Monitoring Admin<?php else : ?>Monitoring codered & codeblue<?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <div class="user-chip">
                        <div class="fw-semibold"><?= e($user['full_name'] ?? $user['username']) ?></div>
                        <div class="text-muted small text-uppercase"><?= e($user['role']) ?></div>
                    </div>
                    <nav class="nav flex-column gap-2">
                        <?php foreach ($navDashboardLinks as $link) : ?>
                            <a class="nav-link <?= $link['active'] ? 'active' : '' ?>" href="<?= e($link['href']) ?>"><i class="bi bi-speedometer2 me-2"></i><?= e($link['label']) ?></a>
                        <?php endforeach; ?>
                        <a class="nav-link <?= $page === 'history' ? 'active' : '' ?>" href="/?page=history"><i class="bi bi-clock-history me-2"></i>Log History</a>
                        <?php if (($user['role'] ?? '') === 'admin') : ?>
                            <a class="nav-link <?= $page === 'users' ? 'active' : '' ?>" href="/?page=users"><i class="bi bi-people me-2"></i>Manage User</a>
                            <a class="nav-link <?= $page === 'buttons' ? 'active' : '' ?>" href="/?page=buttons"><i class="bi bi-broadcast me-2"></i>Manage Button</a>
                        <?php endif; ?>
                        <a class="nav-link text-danger" href="/?page=logout"><i class="bi bi-box-arrow-right me-2"></i>Logout</a>
                    </nav>
                </div>
            </aside>
        <?php endif; ?>
        <main id="dashboardFullscreenTarget" class="<?= e($bodyTheme) ?> <?= !empty($user) ? 'col-12 col-lg-9 col-xl-10' : '`col-12' ?> content-wrap">
            <div class="content-inner">
                <?php if ($flashSuccess) : ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?= e($flashSuccess) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                <?php if ($flashError) : ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?= e($flashError) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                <?= $content ?>
            </div>
        </main>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="/assets/js/app.js"></script>
</body>
</html>
