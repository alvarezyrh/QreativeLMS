<?php require __DIR__ . '/manage-header.php'; ?>

  <section class="manage-panel settings-panel">
    <div class="section-head">
      <div><h2>Identitas platform</h2><p class="panel-subtitle">Informasi yang digunakan pada area aplikasi.</p></div>
    </div>
    <form class="manage-form settings-form" method="post" action="/settings.php" enctype="multipart/form-data">
      <input type="hidden" name="csrf_token" value="<?= escape($csrfToken) ?>">
      <label for="platform_name">Nama platform</label>
      <input id="platform_name" name="platform_name" required maxlength="120" value="<?= escape($settings['platform_name'] ?? 'Qreative LMS') ?>">
      <label for="support_email">Email bantuan</label>
      <input id="support_email" name="support_email" type="email" maxlength="254" value="<?= escape($settings['support_email'] ?? '') ?>" placeholder="support@domainanda.id">

      <div class="settings-divider"></div>
      <div class="section-head">
        <div><h2>Pembayaran QRIS BCA</h2><p class="panel-subtitle">Pencatatan pembayaran dilakukan secara manual oleh admin. Tidak ada transaksi otomatis ke bank.</p></div>
      </div>
      <label for="qris_instructions">Instruksi pembayaran untuk murid</label>
      <textarea id="qris_instructions" name="qris_instructions" rows="4" maxlength="2000"><?= escape($settings['qris_instructions'] ?? 'Pindai QRIS BCA, lalu kirim nomor referensi pembayaran atau nama pengirim. Admin akan memverifikasi pembayaran secara manual.') ?></textarea>
      <label for="qris_image">Gambar QRIS BCA <span class="form-hint">(PNG, JPG, WebP · maksimal 2 MB · 4000 × 4000 px)</span></label>
      <input id="qris_image" name="qris_image" type="file" accept="image/png,image/jpeg,image/webp">
      <?php if (!empty($settings['qris_image'])): ?>
        <div class="qris-preview">
          <img src="/payment-qr.php" alt="QRIS pembayaran BCA">
          <span>QRIS tersimpan. Kosongkan pilihan file untuk mempertahankan gambar ini.</span>
        </div>
      <?php else: ?>
        <p class="form-hint">Belum ada gambar. Tambahkan QRIS resmi milik organisasi Anda. Jangan gunakan QR contoh.</p>
      <?php endif; ?>
      <button class="primary-button" type="submit">Simpan pengaturan <span aria-hidden="true">→</span></button>
    </form>
  </section>
</main>
</body>
</html>
