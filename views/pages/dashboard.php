<?php
/**
 * @var array $summary
 * @var array $devices
 * @var array $activeAlarms
 * @var int $dashboardActiveCount
 * @var string|null $dashboardAlarmType 
 * @var string|null $dashboardRole
 * @var string|null $dashboardTheme
 * @var string|null $dashboardApiUrl
 * @var string $dashboardTitle
 * @var string $dashboardSubtitle 
 */

$activeAlarmCount = (int) $dashboardActiveCount;
$isAdminDashboard = ($dashboardRole ?? 'admin') === 'admin';
$deviceSlideCount = count($devices);
$firstDevice = $devices[0] ?? null;
$accentLabel = $dashboardAlarmType ?? 'Semua';
$metricAccentClass = $dashboardRole === 'codered' ? 'text-danger' : ($dashboardRole === 'codeblue' ? 'text-info' : 'text-warning');
$metricAccentBg = $dashboardRole === 'codered' ? 'bg-danger' : ($dashboardRole === 'codeblue' ? 'bg-info' : 'bg-warning text-dark');
$lampClass = $dashboardRole === 'codered' ? 'lamp-red' : 'lamp-blue';
$buttonClass = $dashboardRole === 'codered' ? 'danger' : 'info';
$buttonLabel = $dashboardRole === 'codered' ? 'Codered' : 'Codeblue';
$alarmCountLabel = $dashboardAlarmType ? $dashboardAlarmType . ' aktif' : 'Alarm aktif';
$activeFilterLabel = $dashboardAlarmType ? 'Hanya menampilkan alarm ' . strtolower($dashboardAlarmType) : 'Menampilkan alarm codered dan codeblue';
?>
<div class="d-flex flex-column flex-xl-row gap-3 align-items-xl-center justify-content-between mb-4">
    <div>
        <!-- <div class="dashboard-kicker <?= e($metricAccentBg) ?>">Dashboard <?= e($accentLabel) ?></div> -->
        <h1 class="display-6 fw-bold mb-1"><?= e($dashboardTitle) ?></h1>
        <!-- <p class="text-muted mb-0"><?= e($dashboardSubtitle) ?></p> -->
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <button type="button" class="btn btn-warning alarm-audio-btn" id="enableAlarmSound">
            <i class="bi bi-bell-fill me-2"></i>Suara Alarm Aktif
        </button>
        <button type="button" class="btn btn-outline-light fullscreen-btn" id="toggleFullscreen">
            <i class="bi bi-arrows-fullscreen me-2"></i>Fullscreen
        </button>
        <!-- <span class="badge bg-dark-subtle text-dark px-3 py-2" id="alarmStatusBadge"><?= e($alarmCountLabel) ?>: <?= $activeAlarmCount ?></span> -->
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-6 col-xl-3">
        <div class="metric-card metric-card-strong">
            <div class="metric-label">Total Device</div>
            <div class="metric-value"><?= (int) $summary['devices'] ?></div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="metric-card metric-card-strong">
            <div class="metric-label">Connected</div>
            <div class="metric-value text-success"><?= (int) $summary['connected'] ?></div>
        </div>
    </div>
    <?php if ($isAdminDashboard) : ?>
        <div class="col-6 col-xl-3">
            <div class="metric-card metric-card-strong">
                <div class="metric-label">Codered Aktif</div>
                <div id="activeRed" class="metric-value text-danger"><?= (int) $summary['active_red'] ?></div>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="metric-card metric-card-strong">
                <div class="metric-label">Codeblue Aktif</div>
                <div id="activeBlue" class="metric-value text-info"><?= (int) $summary['active_blue'] ?></div>
            </div>
        </div>
    <?php else : ?>
        <div class="col-12 col-xl-3">
            <div class="metric-card metric-card-strong metric-card-accent <?= e($buttonClass) ?>">
                <div class="metric-label"><?= e($dashboardAlarmType) ?> Aktif</div>
                <div id="activeAlarm" class="metric-value <?= e($metricAccentClass) ?>"><?= $activeAlarmCount ?></div>
            </div>
        </div>
    <?php endif; ?>
</div>

<div class="panel-card mb-4">
    <div class="d-flex align-items-center justify-content-between gap-3 mb-3 flex-wrap">
        <div>
            <h2 class="h5 mb-1">Status Alarm Switch (Satelit)</h2>
            <!-- <div class="text-muted small"><?= e($activeFilterLabel) ?></div> -->
        </div>
        <!-- <span class="badge bg-dark-subtle text-dark">Scroll berkelanjutan, update tiap loop</span> -->
    </div>
    <div class="device-carousel" id="deviceCarousel" data-device-count="<?= (int) $deviceSlideCount ?>">
        <div class="device-carousel-viewport">
            <div class="device-carousel-track" id="deviceCarouselTrack">
                <?php if (empty($devices)) : ?>
                    <div class="device-slide device-slide-empty">
                        <div class="device-card device-card-empty">
                            <div class="display-6 mb-2"><i class="bi bi-broadcast"></i></div>
                            <h3 class="h4 mb-2">Belum ada button terdaftar</h3>
                            <p class="text-muted mb-0">Admin dapat menambahkan satelit baru dari menu Manage Button.</p>
                        </div>
                    </div>
                <?php else : ?>
                    <?php foreach ($devices as $device) : ?>
                        <?php
                        $redActive = (int) $device['red_active'] === 1;
                        $blueActive = (int) $device['blue_active'] === 1;
                        $isConnected = ($device['status'] ?? '') === 'Connected';
                        $roleActive = $dashboardRole === 'codered' ? $redActive : ($dashboardRole === 'codeblue' ? $blueActive : ($redActive || $blueActive));
                        ?>
                        <div class="device-slide">
                            <div class="device-card <?= $roleActive ? 'device-alert' : '' ?>" data-btn-code="<?= e($device['btn_code']) ?>">
                                <div class="d-flex justify-content-between align-items-start gap-2 mb-3">
                                    <div>
                                        <div class="text-uppercase small text-muted"><i class="bi bi-geo-alt-fill"></i> Lokasi</div>
                                        <div class="h5 mb-0"><?= e($device['location']) ?></div>
                                    </div>
                                    <span class="badge <?= $isConnected ? 'bg-success' : 'bg-secondary' ?>"><?= e($device['status']) ?></span>
                                </div>
                                <div class="d-flex align-items-center gap-3 mb-3">
                                    <div class="lamp-stack">
                                        <?php if ($isAdminDashboard) : ?>
                                            <span class="lamp lamp-red <?= $redActive ? 'is-active blink' : '' ?>"></span>
                                            <span class="lamp lamp-blue <?= $blueActive ? 'is-active blink' : '' ?>"></span>
                                        <?php else : ?>
                                            <span class="lamp <?= e($lampClass) ?> <?= $roleActive ? 'is-active blink' : '' ?>"></span>
                                        <?php endif; ?>
                                    </div>
                                    <div>
                                        <div class="fw-semibold">Kode: <?= e($device['btn_code']) ?></div>
                                        <div class="text-muted small">IP: <?= e($device['ip']) ?></div>
                                        <div class="text-muted small">Last ping: <?= e($device['last_ping']) ?></div>
                                    </div>
                                </div>
                                <div class="d-flex gap-2 flex-wrap">
                                    <?php if ($isAdminDashboard) : ?>
                                        <span class="btn btn-sm btn-outline-danger disabled <?= $redActive ? 'active-state' : '' ?>">Codered <?= $redActive ? 'ON' : 'OFF' ?></span>
                                        <span class="btn btn-sm btn-outline-info disabled <?= $blueActive ? 'active-state' : '' ?>">Codeblue <?= $blueActive ? 'ON' : 'OFF' ?></span>
                                    <?php else : ?>
                                        <span class="btn btn-sm btn-outline-<?= e($buttonClass) ?> disabled <?= $roleActive ? 'active-state' : '' ?>"><?= e($buttonLabel) ?> <?= $roleActive ? 'ON' : 'OFF' ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
        <!-- <div class="device-carousel-footer">
            <div class="device-carousel-counter" id="carouselCounter"><?= $deviceSlideCount > 0 ? (int) $deviceSlideCount . ' kartu' : '0 kartu' ?></div>
            <div class="device-carousel-location" id="carouselLocationLabel"><?= e($firstDevice['location'] ?? 'Belum ada lokasi') ?></div>
        </div> -->
    </div>
</div>

<div class="panel-card">
    <div class="d-flex align-items-center justify-content-between mb-3">
        <div>
            <h2 class="h5 mb-1">Alarm Aktif</h2>
            <!-- <div class="text-muted small">Data di-refresh otomatis</div> -->
        </div>
        <?php if (!$isAdminDashboard) : ?>
            <span class="badge <?= e($metricAccentBg) ?>"><?= e($dashboardAlarmType) ?></span>
        <?php endif; ?>
    </div>
    <div class="table-responsive">
        <table class="table table-dark table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th>Lokasi</th>
                    <th>Kode</th>
                    <th>Tipe</th>
                    <th>Status</th>
                    <th>Waktu</th>
                </tr>
            </thead>
            <tbody id="activeAlarmTable">
                <?php if ($activeAlarmCount === 0) : ?>
                    <tr><td colspan="5" class="text-center text-muted py-4">Tidak ada alarm aktif</td></tr>
                <?php else : ?>
                    <?php foreach ($activeAlarms as $alarm) : ?>
                        <tr>
                            <td class="fw-bold"><?= e($alarm['location']) ?></td>
                            <td><?= e($alarm['btn_code']) ?></td>
                            <td><span class="badge <?= strtolower((string) $alarm['btn_type']) === 'red' ? 'bg-danger' : 'bg-info' ?>"><?= e($alarm['btn_type']) ?></span></td>
                            <td><span class="badge bg-warning text-dark">ON</span></td>
                            <td><?= e($alarm['last_ping']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
