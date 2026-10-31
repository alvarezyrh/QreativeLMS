<?php require __DIR__ . '/manage-header.php'; ?>

  <section class="report-toolbar manage-panel">
    <form method="get" action="/reports.php" class="report-filter">
      <label for="period">Periode laporan</label>
      <input id="period" name="period" type="month" value="<?= escape($period) ?>" required>
      <button class="secondary-button" type="submit">Tampilkan</button>
      <a class="primary-button" href="/reports.php?period=<?= escape($period) ?>&amp;export=csv" download>Ekspor CSV</a>
    </form>
  </section>

  <section class="finance-stats report-stats">
    <article class="finance-stat"><span>Pemasukan terbayar</span><strong>Rp <?= number_format((int) $summary['income'], 0, ',', '.') ?></strong></article>
    <article class="finance-stat"><span>Pengeluaran terbayar</span><strong>Rp <?= number_format((int) $summary['expense'], 0, ',', '.') ?></strong></article>
    <article class="finance-stat"><span>Saldo bersih</span><strong>Rp <?= number_format((int) $summary['net'], 0, ',', '.') ?></strong></article>
    <article class="finance-stat"><span>Menunggu verifikasi</span><strong><?= (int) $summary['pending'] ?> transaksi</strong></article>
  </section>

  <section class="manage-panel report-daily-panel">
    <div class="section-head"><div><h2>Aktivitas transaksi harian</h2><p class="panel-subtitle">Pemasukan/pengeluaran memakai tanggal pembayaran; transaksi pending memakai tanggal pencatatan.</p></div></div>
    <?php if ($dailyReports === []): ?>
      <div class="empty-manage">Belum ada transaksi pada periode ini.</div>
    <?php else: ?>
      <div class="user-table-wrap">
        <table class="user-table report-table">
          <thead><tr><th>Tanggal</th><th>Pemasukan lunas</th><th>Pengeluaran lunas</th><th>Pending dibuat</th><th>Saldo harian</th></tr></thead>
          <tbody>
            <?php foreach ($dailyReports as $row): ?>
              <tr>
                <td><?= escape(date('d/m/Y', strtotime($row['date']))) ?></td>
                <td>Rp <?= number_format((int) $row['income'], 0, ',', '.') ?></td>
                <td>Rp <?= number_format((int) $row['expense'], 0, ',', '.') ?></td>
                <td><?= (int) $row['pending'] ?></td>
                <td>Rp <?= number_format((int) $row['income'] - (int) $row['expense'], 0, ',', '.') ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </section>

  <section class="manage-panel report-course-panel">
    <div class="section-head"><div><h2>Performa kursus</h2><p class="panel-subtitle">Pendaftaran, penyelesaian materi, dan pendapatan terbayar pada periode terpilih.</p></div></div>
    <?php if ($courseReports === []): ?>
      <div class="empty-manage">Belum ada kursus.</div>
    <?php else: ?>
      <div class="user-table-wrap">
        <table class="user-table report-table">
          <thead><tr><th>Kursus</th><th>Level</th><th>Murid</th><th>Materi selesai</th><th>Pendapatan periode</th></tr></thead>
          <tbody>
            <?php foreach ($courseReports as $course): ?>
              <tr>
                <td><strong><?= escape($course['title']) ?></strong></td>
                <td><?= escape($course['level']) ?></td>
                <td><?= (int) $course['students'] ?></td>
                <td><?= (int) $course['completed_lessons'] ?> / <?= (int) $course['lessons'] ?></td>
                <td>Rp <?= number_format((int) $course['income'], 0, ',', '.') ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </section>
<?php require __DIR__ . '/layout-end.php'; ?>
