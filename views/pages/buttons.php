<div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-3">
    <div>
        <h1 class="display-6 fw-bold mb-1">Manage Button</h1>
        <p class="text-muted mb-0">Tambah button / satelit untuk lokasi monitoring.</p>
    </div>
    <span class="badge bg-dark-subtle text-dark px-3 py-2">Admin only</span>
</div>

<div class="row g-4">
    <div class="col-12 col-xl-4">
        <div class="panel-card h-100">
            <h2 class="h5 mb-3">Tambah Button</h2>
            <form method="post" action="/?page=buttons" class="vstack gap-3">
                <input type="hidden" name="action" value="create">
                <div>
                    <label class="form-label">Kode Button</label>
                    <input type="text" name="btn_code" class="form-control" maxlength="10" required placeholder="BTN001">
                </div>
                <div>
                    <label class="form-label">Lokasi</label>
                    <input type="text" name="location" class="form-control" maxlength="100" required placeholder="Ruang ICU">
                </div>
                <div>
                    <label class="form-label">IP</label>
                    <input type="text" name="ip" class="form-control" maxlength="16" required placeholder="192.168.1.10">
                </div>
                <div>
                    <label class="form-label">Status Awal</label>
                    <select name="status" class="form-select">
                        <option value="Disconnect">Disconnect</option>
                        <option value="Connected">Connected</option>
                    </select>
                </div>
                <button class="btn btn-primary" type="submit">Simpan Button</button>
            </form>
        </div>
    </div>
    <div class="col-12 col-xl-8">
        <div class="panel-card h-100">
            <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
                <h2 class="h5 mb-0">Daftar Button / Satelit</h2>
                <span class="text-muted small">State Red dan Blue dibuat otomatis dalam kondisi OFF</span>
            </div>
            <div class="table-responsive">
                <table class="table table-dark table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Kode</th>
                            <th>Lokasi</th>
                            <th>IP</th>
                            <th>Status</th>
                            <th>Last Ping</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($devicesList)) : ?>
                            <tr><td colspan="5" class="text-center text-muted py-4">Belum ada button terdaftar</td></tr>
                        <?php else : ?>
                            <?php foreach ($devicesList as $device) : ?>
                                <tr>
                                    <td><?= e($device['btn_code']) ?></td>
                                    <td><?= e($device['location']) ?></td>
                                    <td><?= e($device['ip']) ?></td>
                                    <td><?= status_badge((string) $device['status']) ?></td>
                                    <td><?= e($device['last_ping']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
