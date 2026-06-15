<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h1 class="display-6 fw-bold mb-1">Log History</h1>
        <p class="text-muted mb-0">Riwayat alarm codered dan codeblue.</p>
    </div>
</div>

<div class="panel-card">
    <div class="table-responsive">
        <table class="table table-dark table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th>Waktu</th>
                    <th>Lokasi</th>
                    <th>Kode</th>
                    <th>Tipe</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($logs)) : ?>
                    <tr><td colspan="4" class="text-center text-muted py-4">Belum ada log alarm</td></tr>
                <?php else : ?>
                    <?php foreach ($logs as $log) : ?>
                        <tr>
                            <td><?= e($log['create_at']) ?></td>
                            <td><?= e($log['location'] ?? '-') ?></td>
                            <td><?= e($log['btn_code']) ?></td>
                            <td>
                                <span class="badge <?= strtolower((string) $log['btn_type']) === 'red' ? 'bg-danger' : 'bg-info' ?>">
                                    <?= e($log['btn_type']) ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
