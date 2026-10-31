<?php require __DIR__ . '/manage-header.php'; ?>

  <?php if ($canManage): ?>
    <div class="manage-grid schedule-layout">
      <section class="manage-panel">
        <div class="section-head">
          <div>
            <h2><?= $editingSchedule ? 'Ubah jadwal sesi' : 'Tambah sesi kelas' ?></h2>
            <p class="panel-subtitle">Jadwalkan pertemuan satu kali untuk kursus terbit.</p>
          </div>
          <?php if ($editingSchedule): ?><a class="text-button" href="/schedule.php">Batal</a><?php endif; ?>
        </div>
        <?php if ($courses === []): ?>
          <div class="empty-manage">Belum ada kursus terbit yang dapat dijadwalkan.</div>
        <?php else: ?>
          <form class="manage-form" method="post" action="/schedule.php<?= $editingSchedule ? '?edit=' . (int) $editingSchedule['id'] : '' ?>">
            <input type="hidden" name="csrf_token" value="<?= escape($csrfToken) ?>">
            <input type="hidden" name="action" value="<?= $editingSchedule ? 'update_schedule' : 'create_schedule' ?>">
            <?php if ($editingSchedule): ?><input type="hidden" name="schedule_id" value="<?= (int) $editingSchedule['id'] ?>"><?php endif; ?>
            <label for="schedule_course">Kursus</label>
            <select id="schedule_course" name="course_id" required>
              <option value="">Pilih kursus</option>
              <?php foreach ($courses as $course): ?>
                <option value="<?= (int) $course['id'] ?>" <?= (int) ($editingSchedule['course_id'] ?? 0) === (int) $course['id'] ? 'selected' : '' ?>><?= escape($course['title']) ?></option>
              <?php endforeach; ?>
            </select>
            <label for="schedule_title">Judul sesi</label>
            <input id="schedule_title" name="title" required maxlength="120" value="<?= escape($editingSchedule['title'] ?? '') ?>" placeholder="Contoh: Speaking practice — introductions">
            <label for="starts_at">Tanggal dan waktu (WIB)</label>
            <input id="starts_at" name="starts_at" type="datetime-local" required value="<?= $editingSchedule ? escape(date('Y-m-d\TH:i', strtotime($editingSchedule['starts_at']))) : '' ?>">
            <label for="duration_minutes">Durasi</label>
            <select id="duration_minutes" name="duration_minutes" required>
              <?php foreach ([30, 45, 60, 90, 120, 180, 240, 480] as $duration): ?>
                <option value="<?= $duration ?>" <?= (int) ($editingSchedule['duration_minutes'] ?? 60) === $duration ? 'selected' : '' ?>><?= $duration >= 60 ? (int) ($duration / 60) . ' jam' . ($duration % 60 > 0 ? ' ' . ($duration % 60) . ' menit' : '') : $duration . ' menit' ?></option>
              <?php endforeach; ?>
            </select>
            <label for="location">Lokasi / ruang kelas</label>
            <input id="location" name="location" maxlength="160" value="<?= escape($editingSchedule['location'] ?? '') ?>" placeholder="Contoh: Ruang 02 atau Online">
            <label for="meeting_url">Tautan kelas (opsional)</label>
            <input id="meeting_url" name="meeting_url" type="url" maxlength="500" value="<?= escape($editingSchedule['meeting_url'] ?? '') ?>" placeholder="https://">
            <label for="schedule_notes">Catatan untuk peserta</label>
            <textarea id="schedule_notes" name="notes" rows="3" maxlength="2000" placeholder="Persiapan atau materi yang perlu dibawa."><?= escape($editingSchedule['notes'] ?? '') ?></textarea>
            <button class="primary-button" type="submit"><?= $editingSchedule ? 'Simpan perubahan' : 'Buat jadwal' ?> <span aria-hidden="true">→</span></button>
          </form>
        <?php endif; ?>
      </section>

      <section class="manage-panel schedule-list-panel">
        <div class="section-head">
          <div><h2>Agenda kelas</h2><p class="panel-subtitle"><?= count($schedules) ?> sesi<?= count($schedules) === 200 ? ' · menampilkan 200 jadwal terbaru' : '' ?></p></div>
        </div>
        <?php if ($schedules === []): ?>
          <div class="empty-manage">Belum ada jadwal sesi. Tambahkan agenda pertemuan menggunakan formulir ini.</div>
        <?php else: ?>
          <div class="schedule-cards">
            <?php foreach ($schedules as $schedule): ?>
              <?php $isPast = strtotime($schedule['starts_at']) < time(); ?>
              <article class="schedule-card <?= $isPast ? 'is-past' : '' ?>">
                <div class="schedule-date-badge">
                  <strong><?= escape(date('d', strtotime($schedule['starts_at']))) ?></strong>
                  <span><?= escape(date('M', strtotime($schedule['starts_at']))) ?></span>
                </div>
                <div class="schedule-card-info">
                  <div class="schedule-card-meta">
                    <span class="course-level"><?= escape(date('H:i', strtotime($schedule['starts_at']))) ?> WIB · <?= (int) $schedule['duration_minutes'] ?> menit</span>
                    <span class="table-pill <?= $isPast ? '' : 'active-pill' ?>"><?= $isPast ? 'Selesai' : 'Akan datang' ?></span>
                  </div>
                  <h3><?= escape($schedule['title']) ?></h3>
                  <p class="schedule-course-name"><?= escape($schedule['course_title']) ?> · <?= escape($schedule['level']) ?><?= $schedule['teacher_name'] !== null ? ' · ' . escape($schedule['teacher_name']) : '' ?></p>
                  <?php if ($schedule['location'] !== ''): ?><p class="schedule-detail"><?= escape($schedule['location']) ?></p><?php endif; ?>
                  <?php if ($schedule['notes'] !== ''): ?><p class="schedule-detail"><?= nl2br(escape($schedule['notes'])) ?></p><?php endif; ?>
                  <?php if ($schedule['meeting_url'] !== ''): ?><a class="text-button schedule-meeting-link" href="<?= escape($schedule['meeting_url']) ?>" target="_blank" rel="noopener noreferrer">Buka tautan kelas ↗</a><?php endif; ?>
                </div>
                <?php if ($canManage): ?>
                  <div class="schedule-card-actions">
                    <a class="secondary-button" href="/schedule.php?edit=<?= (int) $schedule['id'] ?>">Ubah</a>
                    <form method="post" action="/schedule.php" onsubmit="return confirm('Hapus jadwal sesi ini?')">
                      <input type="hidden" name="csrf_token" value="<?= escape($csrfToken) ?>">
                      <input type="hidden" name="action" value="delete_schedule">
                      <input type="hidden" name="schedule_id" value="<?= (int) $schedule['id'] ?>">
                      <button class="link-button danger-link" type="submit">Hapus</button>
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
    <section class="manage-panel student-schedule-panel">
      <div class="section-head"><div><h2>Jadwal kursus yang diikuti</h2><p class="panel-subtitle">Sesi kelas hanya terlihat untuk kursus yang sudah kamu ikuti.</p></div></div>
      <?php if ($schedules === []): ?>
        <div class="empty-manage">Belum ada jadwal untuk kursusmu. Jadwal baru akan muncul di sini setelah guru atau admin menambahkannya.</div>
      <?php else: ?>
        <div class="schedule-cards">
          <?php foreach ($schedules as $schedule): ?>
            <?php $isPast = strtotime($schedule['starts_at']) < time(); ?>
            <article class="schedule-card <?= $isPast ? 'is-past' : '' ?>">
              <div class="schedule-date-badge">
                <strong><?= escape(date('d', strtotime($schedule['starts_at']))) ?></strong>
                <span><?= escape(date('M', strtotime($schedule['starts_at']))) ?></span>
              </div>
              <div class="schedule-card-info">
                <div class="schedule-card-meta">
                  <span class="course-level"><?= escape(date('l, H:i', strtotime($schedule['starts_at']))) ?> WIB · <?= (int) $schedule['duration_minutes'] ?> menit</span>
                  <span class="table-pill <?= $isPast ? '' : 'active-pill' ?>"><?= $isPast ? 'Selesai' : 'Akan datang' ?></span>
                </div>
                <h3><?= escape($schedule['title']) ?></h3>
                <p class="schedule-course-name"><?= escape($schedule['course_title']) ?> · <?= escape($schedule['level']) ?><?= $schedule['teacher_name'] !== null ? ' · ' . escape($schedule['teacher_name']) : '' ?></p>
                <?php if ($schedule['location'] !== ''): ?><p class="schedule-detail"><?= escape($schedule['location']) ?></p><?php endif; ?>
                <?php if ($schedule['notes'] !== ''): ?><p class="schedule-detail"><?= nl2br(escape($schedule['notes'])) ?></p><?php endif; ?>
                <?php if (!$isPast && $schedule['meeting_url'] !== ''): ?><a class="primary-button schedule-join-link" href="<?= escape($schedule['meeting_url']) ?>" target="_blank" rel="noopener noreferrer">Gabung kelas <span aria-hidden="true">↗</span></a><?php endif; ?>
              </div>
            </article>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </section>
  <?php endif; ?>
<?php require __DIR__ . '/layout-end.php'; ?>
