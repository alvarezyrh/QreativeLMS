<?php require __DIR__ . '/manage-header.php'; ?>

  <?php if ($isAdmin): ?>
    <section class="finance-stats">
      <article class="finance-stat"><span>Pendapatan lunas bulan ini</span><strong>Rp <?= number_format((int) $summary['monthly_income'], 0, ',', '.') ?></strong></article>
      <article class="finance-stat"><span>Total pendapatan lunas</span><strong>Rp <?= number_format((int) $summary['total_income'], 0, ',', '.') ?></strong></article>
      <article class="finance-stat"><span>Pengeluaran lunas</span><strong>Rp <?= number_format((int) $summary['total_expense'], 0, ',', '.') ?></strong></article>
      <article class="finance-stat"><span>Menunggu verifikasi</span><strong><?= (int) $summary['pending_count'] ?> transaksi</strong></article>
    </section>

    <div class="manage-grid finance-layout">
      <section class="manage-panel">
        <div class="section-head"><div><h2>Catat transaksi</h2><p class="panel-subtitle">Catat pemasukan/pengeluaran manual dalam rupiah.</p></div></div>
        <form class="manage-form" method="post" action="/finance.php">
          <input type="hidden" name="csrf_token" value="<?= escape($csrfToken) ?>">
          <input type="hidden" name="action" value="record_transaction">
          <label for="kind">Jenis transaksi</label>
          <select id="kind" name="kind"><option value="income">Pemasukan</option><option value="expense">Pengeluaran</option></select>
          <div data-income-fields>
            <label for="finance_student">Murid</label>
            <select id="finance_student" name="user_id">
              <?php foreach ($students as $student): ?>
                <option value="<?= (int) $student['id'] ?>"><?= escape($student['name']) ?> · <?= escape($student['email']) ?></option>
              <?php endforeach; ?>
            </select>
            <label for="finance_course">Kursus (opsional)</label>
            <select id="finance_course" name="course_id">
              <option value="">Tanpa kursus tertentu</option>
              <?php foreach ($courses as $course): ?>
                <option value="<?= (int) $course['id'] ?>"><?= escape($course['title']) ?> · Rp <?= number_format((int) $course['price_idr'], 0, ',', '.') ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <label for="amount_idr">Nominal (Rp)</label>
          <input id="amount_idr" name="amount_idr" type="number" min="1" max="100000000000" step="1" required>
          <label for="method">Metode</label>
          <select id="method" name="method">
            <option value="qris_bca">QRIS BCA</option>
            <option value="bank_transfer">Transfer bank</option>
            <option value="cash">Tunai</option>
            <option value="other">Lainnya</option>
          </select>
          <label for="finance_status">Status awal</label>
          <select id="finance_status" name="status"><option value="paid">Sudah dibayar</option><option value="pending">Menunggu</option></select>
          <label for="created_at">Tanggal</label>
          <input id="created_at" name="created_at" type="date" value="<?= escape(date('Y-m-d')) ?>" required>
          <label for="notes">Catatan</label>
          <textarea id="notes" name="notes" rows="3" maxlength="1000" placeholder="Keperluan atau catatan internal"></textarea>
          <button class="primary-button" type="submit">Simpan transaksi</button>
        </form>
      </section>

      <section class="manage-panel finance-transactions-panel">
        <div class="section-head"><div><h2>Transaksi</h2><p class="panel-subtitle"><?= count($transactions) ?> transaksi terbaru (maksimal 200)</p></div><a class="text-button" href="/reports.php">Lihat laporan →</a></div>
        <?php if ($transactions === []): ?>
          <div class="empty-manage">Belum ada transaksi.</div>
        <?php else: ?>
          <div class="transaction-list">
            <?php foreach ($transactions as $transaction): ?>
              <article class="transaction-card">
                <div class="transaction-type <?= $transaction['kind'] === 'expense' ? 'expense-type' : '' ?>"><?= $transaction['kind'] === 'income' ? '↙' : '↗' ?></div>
                <div class="transaction-info">
                  <strong><?= $transaction['kind'] === 'income' ? 'Pemasukan' : 'Pengeluaran' ?> · <?= escape($transaction['student_name'] ?? ($transaction['notes'] ?: 'Operasional platform')) ?></strong>
                  <span><?= escape($transaction['course_title'] ?? 'Tanpa kursus') ?> · <?= escape(date('d M Y', strtotime($transaction['created_at']))) ?> · <?= escape(strtoupper(str_replace('_', ' ', $transaction['method']))) ?></span>
                  <?php if ($transaction['reference'] !== ''): ?><small>Referensi: <?= escape($transaction['reference']) ?></small><?php endif; ?>
                  <?php if ($transaction['notes'] !== ''): ?><small><?= escape($transaction['notes']) ?></small><?php endif; ?>
                </div>
                <div class="transaction-amount">
                  <strong class="<?= $transaction['kind'] === 'expense' ? 'amount-expense' : '' ?>"><?= $transaction['kind'] === 'income' ? '+' : '−' ?>Rp <?= number_format((int) $transaction['amount_idr'], 0, ',', '.') ?></strong>
                  <span class="table-pill <?= $transaction['status'] === 'paid' ? 'active-pill' : ($transaction['status'] === 'rejected' ? 'inactive-pill' : '') ?>"><?= $transaction['status'] === 'paid' ? 'Lunas' : ($transaction['status'] === 'rejected' ? 'Ditolak' : 'Pending') ?></span>
                </div>
                <?php if ($transaction['status'] === 'pending' && $transaction['kind'] === 'income'): ?>
                  <div class="transaction-review">
                    <form method="post" action="/finance.php">
                      <input type="hidden" name="csrf_token" value="<?= escape($csrfToken) ?>">
                      <input type="hidden" name="action" value="review_payment">
                      <input type="hidden" name="transaction_id" value="<?= (int) $transaction['id'] ?>">
                      <input type="hidden" name="decision" value="paid">
                      <button class="link-button" type="submit">Setujui</button>
                    </form>
                    <form method="post" action="/finance.php">
                      <input type="hidden" name="csrf_token" value="<?= escape($csrfToken) ?>">
                      <input type="hidden" name="action" value="review_payment">
                      <input type="hidden" name="transaction_id" value="<?= (int) $transaction['id'] ?>">
                      <input type="hidden" name="decision" value="rejected">
                      <button class="link-button danger-link" type="submit">Tolak</button>
                    </form>
                  </div>
                <?php endif; ?>
              </article>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </section>
    </div>
  <?php else: ?>
    <div class="manage-grid student-finance-layout">
      <section class="manage-panel">
        <div class="section-head"><div><h2><?= !empty($settings['qris_image']) ? 'Pembayaran QRIS BCA' : 'Instruksi pembayaran' ?></h2><p class="panel-subtitle">Pembayaran diperiksa secara manual oleh admin.</p></div></div>
        <?php if (!empty($settings['qris_image'])): ?>
          <div class="student-qris"><img src="/payment-qr.php" alt="QRIS BCA resmi Qreative LMS"></div>
        <?php else: ?>
          <div class="empty-manage">QRIS belum tersedia. Silakan hubungi admin sebelum membayar.</div>
        <?php endif; ?>
        <p class="payment-instructions"><?= nl2br(escape($settings['qris_instructions'] ?? 'Hubungi admin untuk instruksi pembayaran.')) ?></p>
        <?php if (!empty($settings['support_email'])): ?><a class="text-button" href="mailto:<?= escape($settings['support_email']) ?>">Hubungi <?= escape($settings['support_email']) ?></a><?php endif; ?>
      </section>
      <section class="manage-panel">
        <div class="section-head"><div><h2>Riwayat pembayaran</h2><p class="panel-subtitle"><?= count($transactions) ?> transaksi tersimpan</p></div></div>
        <?php if ($transactions === []): ?>
          <div class="empty-manage">Belum ada pembayaran. Pilih kursus dari katalog untuk memulai.</div>
        <?php else: ?>
          <div class="transaction-list">
            <?php foreach ($transactions as $transaction): ?>
              <article class="transaction-card student-transaction">
                <div class="transaction-info">
                  <strong><?= escape($transaction['course_title'] ?? 'Pembayaran') ?></strong>
                  <span><?= escape(date('d M Y', strtotime($transaction['created_at']))) ?> · <?= escape(strtoupper(str_replace('_', ' ', $transaction['method']))) ?></span>
                  <small class="table-pill <?= $transaction['status'] === 'paid' ? 'active-pill' : ($transaction['status'] === 'rejected' ? 'inactive-pill' : '') ?>"><?= $transaction['status'] === 'paid' ? 'Lunas' : ($transaction['status'] === 'rejected' ? 'Ditolak' : 'Menunggu verifikasi') ?></small>
                </div>
                <div class="transaction-amount"><strong>Rp <?= number_format((int) $transaction['amount_idr'], 0, ',', '.') ?></strong></div>
                <?php if ($transaction['status'] === 'pending'): ?>
                  <form class="reference-form" method="post" action="/finance.php">
                    <input type="hidden" name="csrf_token" value="<?= escape($csrfToken) ?>">
                    <input type="hidden" name="action" value="submit_reference">
                    <input type="hidden" name="transaction_id" value="<?= (int) $transaction['id'] ?>">
                    <label for="reference_<?= (int) $transaction['id'] ?>">Nomor referensi / nama pengirim</label>
                    <div class="reference-row">
                      <input id="reference_<?= (int) $transaction['id'] ?>" name="reference" maxlength="160" required value="<?= escape($transaction['reference']) ?>" placeholder="Contoh: transfer 25 Okt 14:20">
                      <button class="secondary-button" type="submit">Kirim</button>
                    </div>
                  </form>
                <?php elseif ($transaction['status'] === 'rejected'): ?>
                  <a class="text-button" href="/courses.php">Daftar ulang →</a>
                <?php endif; ?>
              </article>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </section>
    </div>
  <?php endif; ?>
</main>
</body>
</html>
