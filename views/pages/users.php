<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h1 class="display-6 fw-bold mb-1">Manage User</h1>
        <p class="text-muted mb-0">Kelola akses role codered, codeblue, dan admin.</p>
    </div>
</div>

<div class="row g-4">
    <div class="col-12 col-xl-5">
        <div class="panel-card h-100">
            <h2 class="h5 mb-3">Tambah User</h2>
            <form method="post" action="/?page=users" class="vstack gap-3">
                <input type="hidden" name="action" value="create">
                <div>
                    <label class="form-label">Nama Lengkap</label>
                    <input type="text" name="full_name" class="form-control" required>
                </div>
                <div>
                    <label class="form-label">Username</label>
                    <input type="text" name="username" class="form-control" required>
                </div>
                <div>
                    <label class="form-label">Password</label>
                    <input type="password" name="password" class="form-control" required>
                </div>
                <div>
                    <label class="form-label">Role</label>
                    <select name="role" class="form-select" required>
                        <option value="codered">codered</option>
                        <option value="codeblue">codeblue</option>
                        <option value="admin">admin</option>
                    </select>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="is_active" id="isActive" checked>
                    <label class="form-check-label" for="isActive">Aktif</label>
                </div>
                <button class="btn btn-primary" type="submit">Simpan User</button>
            </form>
        </div>
    </div>
    <div class="col-12 col-xl-7">
        <div class="panel-card h-100">
            <h2 class="h5 mb-3">Daftar User</h2>
            <div class="table-responsive">
                <table class="table table-dark table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Nama</th>
                            <th>Username</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $item) : ?>
                            <tr>
                                <td><?= e($item['full_name']) ?></td>
                                <td><?= e($item['username']) ?></td>
                                <td><span class="badge bg-secondary text-uppercase"><?= e($item['role']) ?></span></td>
                                <td><?= (int) $item['is_active'] === 1 ? '<span class="badge bg-success">Active</span>' : '<span class="badge bg-secondary">Inactive</span>' ?></td>
                                <td>
                                    <div class="d-flex gap-2 flex-wrap">
                                        <form method="post" action="/?page=users" class="m-0">
                                            <input type="hidden" name="action" value="toggle">
                                            <input type="hidden" name="id" value="<?= (int) $item['id'] ?>">
                                            <input type="hidden" name="is_active" value="<?= (int) ($item['is_active'] ? 0 : 1) ?>">
                                            <button class="btn btn-sm btn-outline-warning" type="submit">Toggle</button>
                                        </form>
                                        <form method="post" action="/?page=users" class="m-0" onsubmit="return confirm('Hapus user ini?');">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?= (int) $item['id'] ?>">
                                            <button class="btn btn-sm btn-outline-danger" type="submit">Hapus</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
