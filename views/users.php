<?php require __DIR__ . '/manage-header.php'; ?>

  <div class="manage-grid user-management-grid">
    <section class="manage-panel">
      <div class="section-head">
        <div>
          <h2><?= $editingUser ? 'Ubah pengguna' : 'Tambah pengguna' ?></h2>
          <p class="panel-subtitle">Akun baru langsung bisa masuk menggunakan email dan kata sandi.</p>
        </div>
        <?php if ($editingUser): ?>
          <a class="text-button" href="/users.php">Batal</a>
        <?php endif; ?>
      </div>
      <form class="manage-form" method="post" action="/users.php">
        <input type="hidden" name="csrf_token" value="<?= escape($csrfToken) ?>">
        <input type="hidden" name="action" value="<?= $editingUser ? 'update_user' : 'create_user' ?>">
        <?php if ($editingUser): ?><input type="hidden" name="user_id" value="<?= (int) $editingUser['id'] ?>"><?php endif; ?>
        <label for="user_name">Nama lengkap</label>
        <input id="user_name" name="name" required maxlength="120" value="<?= escape($editingUser['name'] ?? '') ?>" placeholder="Nama pengguna">
        <label for="user_email">Email</label>
        <input id="user_email" name="email" type="email" required maxlength="254" value="<?= escape($editingUser['email'] ?? '') ?>" placeholder="nama@email.com">
        <label for="user_password"><?= $editingUser ? 'Kata sandi baru (opsional)' : 'Kata sandi' ?></label>
        <input id="user_password" name="password" type="password" <?= $editingUser ? '' : 'required' ?> minlength="10" maxlength="256" autocomplete="new-password" placeholder="<?= $editingUser ? 'Kosongkan jika tidak diubah' : 'Minimal 10 karakter' ?>">
        <label for="user_role">Role</label>
        <select id="user_role" name="role" required>
          <?php foreach (['admin' => 'Admin', 'guru' => 'Guru', 'murid' => 'Murid'] as $roleKey => $roleLabel): ?>
            <option value="<?= $roleKey ?>" <?= ($editingUser['role'] ?? 'murid') === $roleKey ? 'selected' : '' ?>><?= $roleLabel ?></option>
          <?php endforeach; ?>
        </select>
        <?php if ($editingUser): ?>
          <label for="user_status">Status akun</label>
          <select id="user_status" name="status" required>
            <option value="active" <?= $editingUser['status'] === 'active' ? 'selected' : '' ?>>Aktif</option>
            <option value="inactive" <?= $editingUser['status'] === 'inactive' ? 'selected' : '' ?>>Nonaktif</option>
          </select>
        <?php endif; ?>
        <button class="primary-button" type="submit"><?= $editingUser ? 'Simpan perubahan' : 'Buat pengguna' ?> <span aria-hidden="true">→</span></button>
      </form>
    </section>

    <section class="manage-panel user-list-panel">
      <div class="section-head">
        <div><h2>Daftar pengguna</h2><p class="panel-subtitle"><?= count($users) ?> akun tersimpan</p></div>
      </div>
      <div class="user-table-wrap">
        <table class="user-table">
          <thead><tr><th>Pengguna</th><th>Role</th><th>Status</th><th>Detail</th><th>Aksi</th></tr></thead>
          <tbody>
            <?php foreach ($users as $listedUser): ?>
              <tr>
                <td><strong><?= escape($listedUser['name']) ?></strong><small><?= escape($listedUser['email']) ?></small></td>
                <td><span class="table-pill"><?= escape(ucfirst($listedUser['role'])) ?></span></td>
                <td><span class="table-pill <?= $listedUser['status'] === 'active' ? 'active-pill' : 'inactive-pill' ?>"><?= $listedUser['status'] === 'active' ? 'Aktif' : 'Nonaktif' ?></span></td>
                <td><?= (int) $listedUser['enrollment_count'] ?> kursus</td>
                <td>
                  <div class="table-actions">
                    <a class="link-button" href="/users.php?edit=<?= (int) $listedUser['id'] ?>">Ubah</a>
                    <?php if ($listedUser['status'] === 'active' && (int) $listedUser['id'] !== (int) $user['id']): ?>
                      <form method="post" action="/users.php" onsubmit="return confirm('Nonaktifkan akun ini? Data terkait tetap tersimpan.')">
                        <input type="hidden" name="csrf_token" value="<?= escape($csrfToken) ?>">
                        <input type="hidden" name="action" value="delete_user">
                        <input type="hidden" name="user_id" value="<?= (int) $listedUser['id'] ?>">
                        <button class="link-button danger-link" type="submit">Nonaktifkan</button>
                      </form>
                    <?php endif; ?>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <p class="table-note">Pengguna dinonaktifkan secara aman agar riwayat kursus dan progres tidak terhapus.</p>
    </section>
  </div>
<?php require __DIR__ . '/layout-end.php'; ?>
