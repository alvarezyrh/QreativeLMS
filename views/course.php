<?php require __DIR__ . '/manage-header.php'; ?>

  <section class="manage-panel course-detail-panel">
    <div class="course-detail-top">
      <div>
        <a class="text-button back-link" href="/courses.php">← Kembali ke kursus</a>
        <span class="course-level<?= $course['status'] === 'draft' ? ' is-draft' : '' ?>"><?= escape($course['level']) ?> · <?= $course['status'] === 'draft' ? 'Draft' : 'Terbit' ?></span>
        <h2><?= escape($course['title']) ?></h2>
        <p><?= escape($course['description']) ?></p>
        <div class="manage-meta"><span>Guru: <?= escape($course['teacher_name'] ?? 'Belum ditentukan') ?></span><span><?= count($lessons) ?> materi</span></div>
      </div>
      <?php if ($user['role'] === 'murid'): ?>
        <div class="course-progress-summary">
          <strong><?= $progress ?>%</strong><span>progres kursus</span>
          <div class="progress-track"><span style="width:<?= $progress ?>%"></span></div>
          <small><?= $completedLessons ?> dari <?= count($lessons) ?> materi selesai</small>
        </div>
      <?php endif; ?>
    </div>

    <?php if ($isManager): ?>
      <div class="lesson-editor">
        <h3>Tambah materi</h3>
        <form class="manage-form" method="post" action="/course.php?id=<?= (int) $course['id'] ?>">
          <input type="hidden" name="csrf_token" value="<?= escape($csrfToken) ?>">
          <input type="hidden" name="action" value="create_lesson">
          <label for="new_lesson_title">Judul materi</label>
          <input id="new_lesson_title" name="title" required maxlength="120" placeholder="Contoh: Introductions and greetings">
          <label for="new_lesson_content">Konten materi</label>
          <textarea id="new_lesson_content" name="content" rows="4" maxlength="10000" placeholder="Tulis materi atau instruksi pembelajaran."></textarea>
          <button class="primary-button" type="submit">Tambah materi <span aria-hidden="true">+</span></button>
        </form>
      </div>
    <?php endif; ?>

    <div class="lesson-list">
      <div class="section-head"><div><h2>Materi kursus</h2><p class="panel-subtitle"><?= $user['role'] === 'murid' ? 'Tandai materi sebagai selesai untuk menyimpan progres.' : 'Urutan materi mengikuti waktu materi ditambahkan.' ?></p></div></div>
      <?php if ($lessons === []): ?>
        <div class="empty-manage">Belum ada materi di kursus ini.</div>
      <?php else: ?>
        <?php foreach ($lessons as $index => $lesson): ?>
          <article class="lesson-card">
            <div class="lesson-number"><?= sprintf('%02d', $index + 1) ?></div>
            <div class="lesson-content">
              <h3><?= escape($lesson['title']) ?><?php if ((int) $lesson['is_completed'] === 1): ?><span class="completed-pill">Selesai</span><?php endif; ?></h3>
              <p><?= nl2br(escape($lesson['content'])) ?></p>
            </div>
            <?php if ($isManager): ?>
              <details class="lesson-edit">
                <summary class="secondary-button">Ubah</summary>
                <form class="manage-form" method="post" action="/course.php?id=<?= (int) $course['id'] ?>">
                  <input type="hidden" name="csrf_token" value="<?= escape($csrfToken) ?>">
                  <input type="hidden" name="action" value="update_lesson">
                  <input type="hidden" name="lesson_id" value="<?= (int) $lesson['id'] ?>">
                  <label for="lesson_title_<?= (int) $lesson['id'] ?>">Judul materi</label>
                  <input id="lesson_title_<?= (int) $lesson['id'] ?>" name="title" required maxlength="120" value="<?= escape($lesson['title']) ?>">
                  <label for="lesson_content_<?= (int) $lesson['id'] ?>">Konten materi</label>
                  <textarea id="lesson_content_<?= (int) $lesson['id'] ?>" name="content" rows="4" maxlength="10000"><?= escape($lesson['content']) ?></textarea>
                  <button class="primary-button" type="submit">Simpan materi</button>
                </form>
                <form class="inline-delete" method="post" action="/course.php?id=<?= (int) $course['id'] ?>" onsubmit="return confirm('Hapus materi ini dan progres murid terkait?')">
                  <input type="hidden" name="csrf_token" value="<?= escape($csrfToken) ?>">
                  <input type="hidden" name="action" value="delete_lesson">
                  <input type="hidden" name="lesson_id" value="<?= (int) $lesson['id'] ?>">
                  <button class="secondary-button danger-button" type="submit">Hapus materi</button>
                </form>
              </details>
            <?php elseif ((int) $lesson['is_completed'] !== 1): ?>
              <form method="post" action="/course.php?id=<?= (int) $course['id'] ?>">
                <input type="hidden" name="csrf_token" value="<?= escape($csrfToken) ?>">
                <input type="hidden" name="action" value="complete_lesson">
                <input type="hidden" name="lesson_id" value="<?= (int) $lesson['id'] ?>">
                <button class="secondary-button" type="submit">Tandai selesai</button>
              </form>
            <?php endif; ?>
          </article>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </section>
<?php require __DIR__ . '/layout-end.php'; ?>
