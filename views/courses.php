<?php require __DIR__ . '/manage-header.php'; ?>

  <div class="manage-grid <?= $canManageCourses ? '' : 'student-catalog' ?>">
    <?php if ($canManageCourses): ?>
      <section class="manage-panel">
        <div class="section-head">
          <div>
            <h2><?= $editingCourse ? 'Ubah kursus' : 'Buat kursus baru' ?></h2>
            <p class="panel-subtitle">Simpan kursus dan kelola materinya.</p>
          </div>
          <?php if ($editingCourse): ?>
            <a class="text-button" href="/courses.php">Batal</a>
          <?php endif; ?>
        </div>
        <form class="manage-form" method="post" action="/courses.php">
          <input type="hidden" name="csrf_token" value="<?= escape($csrfToken) ?>">
          <input type="hidden" name="action" value="<?= $editingCourse ? 'update_course' : 'create_course' ?>">
          <?php if ($editingCourse): ?>
            <input type="hidden" name="course_id" value="<?= (int) $editingCourse['id'] ?>">
          <?php endif; ?>
          <label for="title">Nama kursus</label>
          <input id="title" name="title" required maxlength="120" value="<?= escape($editingCourse['title'] ?? '') ?>" placeholder="Contoh: Everyday English">
          <label for="description">Deskripsi</label>
          <textarea id="description" name="description" rows="4" required maxlength="2000" placeholder="Jelaskan materi dan tujuan kursus."><?= escape($editingCourse['description'] ?? '') ?></textarea>
          <label for="level">Level CEFR</label>
          <select id="level" name="level" required>
            <?php foreach (['A1', 'A2', 'B1', 'B2', 'C1', 'C2'] as $level): ?>
              <option value="<?= $level ?>" <?= ($editingCourse['level'] ?? 'A1') === $level ? 'selected' : '' ?>><?= $level ?></option>
            <?php endforeach; ?>
          </select>
          <label for="price_idr">Harga (Rp; 0 untuk gratis)</label>
          <input id="price_idr" name="price_idr" type="number" min="0" max="1000000000" step="1" required value="<?= (int) ($editingCourse['price_idr'] ?? 0) ?>">
          <label for="status">Status</label>
          <select id="status" name="status" required>
            <option value="draft" <?= ($editingCourse['status'] ?? '') === 'draft' ? 'selected' : '' ?>>Draft</option>
            <option value="published" <?= ($editingCourse['status'] ?? 'published') === 'published' ? 'selected' : '' ?>>Terbit</option>
          </select>
          <?php if ($user['role'] === 'admin'): ?>
            <label for="teacher_id">Guru pengampu</label>
            <select id="teacher_id" name="teacher_id" required>
              <option value="">Pilih guru</option>
              <?php foreach ($teachers as $teacher): ?>
                <option value="<?= (int) $teacher['id'] ?>" <?= (int) ($editingCourse['teacher_id'] ?? 0) === (int) $teacher['id'] ? 'selected' : '' ?>><?= escape($teacher['name']) ?></option>
              <?php endforeach; ?>
            </select>
          <?php endif; ?>
          <button class="primary-button" type="submit"><?= $editingCourse ? 'Simpan perubahan' : 'Buat kursus' ?> <span aria-hidden="true">→</span></button>
        </form>
      </section>
    <?php endif; ?>

    <section class="manage-panel course-list-panel">
      <div class="section-head">
        <div>
          <h2><?= $user['role'] === 'murid' ? 'Katalog kursus' : 'Kursus yang dikelola' ?></h2>
          <p class="panel-subtitle"><?= count($courses) ?> kursus tersedia</p>
        </div>
      </div>
      <?php if ($courses === []): ?>
        <div class="empty-manage">Belum ada kursus di sini. <?= $user['role'] === 'murid' ? 'Kembali lagi nanti untuk menemukan kursus baru.' : 'Buat kursus pertamamu menggunakan formulir ini.' ?></div>
      <?php else: ?>
        <div class="manage-course-list">
          <?php foreach ($courses as $course): ?>
            <?php
              $isEnrolled = $course['enrollment_id'] !== null;
              $courseProgress = (int) ($course['lesson_count'] ?? 0) > 0
                  ? (int) round((int) ($course['completed_count'] ?? 0) * 100 / (int) $course['lesson_count'])
                  : 0;
            ?>
            <article class="manage-course">
              <div class="manage-course-info">
                <span class="course-level<?= $course['status'] === 'draft' ? ' is-draft' : '' ?>"><?= escape($course['level']) ?> · <?= $course['status'] === 'draft' ? 'Draft' : 'Terbit' ?></span>
                <h3><?= escape($course['title']) ?></h3>
                <p><?= escape($course['description']) ?></p>
                <div class="manage-meta">
                  <span><?= escape($course['teacher_name'] ?? 'Guru belum ditentukan') ?></span>
                  <span><?= (int) $course['price_idr'] === 0 ? 'Gratis' : 'Rp ' . number_format((int) $course['price_idr'], 0, ',', '.') ?></span>
                  <span><?= (int) $course['lesson_count'] ?> materi</span>
                  <?php if ($user['role'] !== 'murid'): ?>
                    <span><?= (int) ($course['student_count'] ?? 0) ?> murid terdaftar</span>
                  <?php endif; ?>
                </div>
              </div>
              <div class="manage-course-actions">
                <?php if ($user['role'] === 'murid' && $isEnrolled): ?>
                  <span class="enrolled-label"><?= $courseProgress ?>% selesai</span>
                  <a class="primary-button" href="/course.php?id=<?= (int) $course['id'] ?>">Buka kursus <span aria-hidden="true">→</span></a>
                  <form method="post" action="/courses.php">
                    <input type="hidden" name="csrf_token" value="<?= escape($csrfToken) ?>">
                    <input type="hidden" name="action" value="withdraw">
                    <input type="hidden" name="course_id" value="<?= (int) $course['id'] ?>">
                    <button class="link-button" type="submit">Batalkan pendaftaran</button>
                  </form>
                <?php elseif ($user['role'] === 'murid'): ?>
                  <form method="post" action="/courses.php">
                    <input type="hidden" name="csrf_token" value="<?= escape($csrfToken) ?>">
                    <input type="hidden" name="action" value="enroll">
                    <input type="hidden" name="course_id" value="<?= (int) $course['id'] ?>">
                    <button class="primary-button" type="submit">Daftar kursus <span aria-hidden="true">→</span></button>
                  </form>
                <?php else: ?>
                  <a class="secondary-button" href="/course.php?id=<?= (int) $course['id'] ?>">Materi</a>
                  <a class="secondary-button" href="/courses.php?edit=<?= (int) $course['id'] ?>">Ubah</a>
                  <form class="action-form" method="post" action="/courses.php" onsubmit="return confirm('Hapus kursus ini beserta materi, pendaftaran, dan progres terkait?')">
                    <input type="hidden" name="csrf_token" value="<?= escape($csrfToken) ?>">
                    <input type="hidden" name="action" value="delete_course">
                    <input type="hidden" name="course_id" value="<?= (int) $course['id'] ?>">
                    <button class="secondary-button danger-button" type="submit">Hapus</button>
                  </form>
                <?php endif; ?>
              </div>
            </article>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </section>
  </div>
<?php require __DIR__ . '/layout-end.php'; ?>
