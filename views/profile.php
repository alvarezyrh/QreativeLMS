<?php require __DIR__ . '/manage-header.php'; ?>

  <div class="manage-grid profile-layout">
    <div class="profile-side">
      <section class="manage-panel profile-summary">
        <?= user_avatar_html($profile, 'avatar profile-avatar') ?>
        <h2><?= escape($profile['name']) ?></h2>
        <p class="panel-subtitle"><?= $profile['username'] !== null ? '@' . escape($profile['username']) : 'Username belum diatur' ?></p>
        <dl class="profile-facts">
          <div><dt>Role</dt><dd><?= escape(role_label($profile['role'])) ?></dd></div>
          <div><dt>Email</dt><dd><?= escape($profile['email']) ?></dd></div>
          <div><dt>Bergabung</dt><dd><?= escape(indonesian_date((int) strtotime($profile['created_at']))) ?></dd></div>
        </dl>
      </section>

      <section class="manage-panel profile-password">
        <div class="section-head">
          <div><h2>Ganti kata sandi</h2><p class="panel-subtitle">Minimal 10 karakter.</p></div>
        </div>
        <form class="manage-form" method="post" action="/profile.php">
          <input type="hidden" name="csrf_token" value="<?= escape($csrfToken) ?>">
          <input type="hidden" name="action" value="change_password">
          <label for="current_password">Kata sandi saat ini</label>
          <input id="current_password" name="current_password" type="password" required maxlength="4096" autocomplete="current-password">
          <label for="new_password">Kata sandi baru</label>
          <input id="new_password" name="new_password" type="password" required minlength="10" maxlength="256" autocomplete="new-password">
          <label for="confirm_password">Ulangi kata sandi baru</label>
          <input id="confirm_password" name="confirm_password" type="password" required minlength="10" maxlength="256" autocomplete="new-password">
          <span class="form-spacer" aria-hidden="true"></span>
          <button class="primary-button" type="submit">Ganti kata sandi <span aria-hidden="true">→</span></button>
        </form>
      </section>
    </div>

    <section class="manage-panel">
      <form class="manage-form" method="post" action="/profile.php" enctype="multipart/form-data">
        <input type="hidden" name="csrf_token" value="<?= escape($csrfToken) ?>">
        <input type="hidden" name="action" value="update_profile">

        <div class="section-head">
          <div><h2>Akun</h2><p class="panel-subtitle">Foto, nama, dan username yang terlihat di aplikasi.</p></div>
        </div>
        <label for="avatar">Foto profil</label>
        <div class="photo-field">
          <?= user_avatar_html($profile, 'avatar') ?>
          <div class="photo-field-body">
            <div class="photo-field-actions">
              <input id="avatar" name="avatar" type="file" accept="image/png,image/jpeg,image/webp" data-photo-input>
              <label class="secondary-button" for="avatar"><?= $profile['avatar'] !== '' ? 'Ganti foto' : 'Pilih foto' ?></label>
              <?php if ($profile['avatar'] !== ''): ?>
                <label class="checkbox-label"><input type="checkbox" name="remove_avatar" value="1"> Hapus foto</label>
              <?php endif; ?>
            </div>
            <span class="photo-field-name" data-photo-name>PNG, JPG, atau WebP · maksimal 2 MB</span>
          </div>
        </div>
        <div class="form-row">
          <div>
            <label for="profile_name">Nama lengkap</label>
            <input id="profile_name" name="name" required maxlength="120" value="<?= escape($profile['name']) ?>" autocomplete="name">
          </div>
          <div>
            <label for="profile_username">Username <span class="form-hint">(untuk login)</span></label>
            <input id="profile_username" name="username" maxlength="30" pattern="[A-Za-z0-9][A-Za-z0-9._]{2,29}" value="<?= escape($profile['username'] ?? '') ?>" placeholder="contoh: nama.anda" autocomplete="username">
          </div>
        </div>

        <div class="settings-divider"></div>
        <div class="section-head">
          <div><h2>Data diri</h2><p class="panel-subtitle">Hanya terlihat oleh Anda.</p></div>
        </div>
        <div class="form-row">
          <div>
            <label for="profile_nik">NIK</label>
            <input id="profile_nik" name="nik" inputmode="numeric" pattern="\d{16}" maxlength="16" value="<?= escape($profile['nik']) ?>" placeholder="16 digit" autocomplete="off">
          </div>
          <div>
            <label for="profile_bpjs">No. BPJS</label>
            <input id="profile_bpjs" name="bpjs" inputmode="numeric" pattern="\d{11,13}" maxlength="13" value="<?= escape($profile['bpjs']) ?>" placeholder="11–13 digit" autocomplete="off">
          </div>
        </div>
        <label for="profile_address">Alamat</label>
        <textarea id="profile_address" name="address" rows="3" maxlength="500" autocomplete="street-address"><?= escape($profile['address']) ?></textarea>
        <label for="profile_status">Status</label>
        <select id="profile_status" name="marital_status">
          <option value="">— Pilih status —</option>
          <?php foreach (marital_status_options() as $value => $label): ?>
            <option value="<?= escape($value) ?>"<?= $profile['marital_status'] === $value ? ' selected' : '' ?>><?= escape($label) ?></option>
          <?php endforeach; ?>
        </select>
        <span class="form-spacer" aria-hidden="true"></span>
        <button class="primary-button" type="submit">Simpan profil <span aria-hidden="true">→</span></button>
      </form>
    </section>
  </div>
<?php require __DIR__ . '/layout-end.php'; ?>
