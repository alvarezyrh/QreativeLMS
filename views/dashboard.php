<?php require __DIR__ . '/layout-start.php'; ?>

    <section class="heading-row">
      <div>
        <div class="eyebrow">Workspace · <?= escape($roleLabel) ?></div>
        <h1><?= escape($greeting) ?></h1>
        <p class="subtitle"><?= escape($subtitle) ?></p>
      </div>
      <div class="date-pill">
        <?= icon('calendar', 14) ?>
        <span><?= escape(indonesian_date(time(), true)) ?></span>
      </div>
    </section>

    <section class="stats" aria-label="Ringkasan">
      <?php foreach ($stats as $index => [$label, $value, $foot, $statIcon]): ?>
        <article class="stat-card">
          <div class="stat-top"><span><?= escape($label) ?></span><span class="stat-icon <?= ['', 'sky', 'peach', ''][$index] ?? '' ?>"><?= icon($statIcon, 15) ?></span></div>
          <div class="stat-value"><?= escape($value) ?></div>
          <div class="stat-foot"><?= escape($foot) ?></div>
        </article>
      <?php endforeach; ?>
    </section>

    <section class="dashboard-grid">
      <article class="panel continue-card">
        <div class="continue-copy">
          <span class="mini-tag"><?= icon('book', 11) ?> <?= escape($featured['tag']) ?></span>
          <h2><?= escape($featured['title']) ?></h2>
          <p><?= escape($featured['description']) ?></p>
          <div class="lesson-meta">
            <?php foreach ($featured['meta'] as $metaIndex => $metaText): ?>
              <span><?= icon($metaIndex === 0 ? 'book' : 'clock', 12) ?> <?= escape($metaText) ?></span>
            <?php endforeach; ?>
          </div>
          <?php if ($featured['progress'] !== null): ?>
            <div class="progress-row"><div class="progress-track"><span style="width:<?= (int) $featured['progress'] ?>%"></span></div><small><?= (int) $featured['progress'] ?>% selesai</small></div>
          <?php endif; ?>
          <a class="primary-button" href="<?= escape($featured['href']) ?>"><?= escape($featured['action']) ?> <span aria-hidden="true">→</span></a>
        </div>
        <div class="art-panel" aria-hidden="true"><div class="book-art"><?= icon('book', 56) ?></div><div class="floating-note">✦ <?= escape($featured['note']) ?></div></div>
      </article>

      <article class="panel section-panel">
        <div class="section-head"><h2><?= escape($scheduleTitle) ?></h2><a class="text-button" href="/schedule.php">Lihat semua <span aria-hidden="true">→</span></a></div>
        <?php if ($schedule === []): ?>
          <div class="empty-state compact">Belum ada jadwal kelas mendatang.</div>
        <?php else: ?>
          <div class="schedule-list">
            <?php foreach ($schedule as $index => $item): ?>
              <?php $startsAt = strtotime($item['starts_at']); ?>
              <div class="schedule-item">
                <div class="schedule-time"><b><?= date('H:i', $startsAt) ?></b><span><?= escape(indonesian_date($startsAt, false, true)) ?></span></div>
                <div class="schedule-mark <?= ['', 'blue', 'orange'][$index] ?? '' ?>"></div>
                <div class="schedule-info"><strong><?= escape($item['title']) ?></strong><span><?= escape($item['course_title'] . ($item['location'] !== '' ? ' · ' . $item['location'] : '')) ?></span></div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </article>
    </section>

    <section class="panel section-panel">
      <div class="section-head"><h2><?= escape($courseTitle) ?></h2><a class="text-button" href="/courses.php">Semua kursus <span aria-hidden="true">→</span></a></div>
      <?php if ($courses === []): ?>
        <div class="empty-state compact"><?= escape($courseEmpty) ?></div>
      <?php else: ?>
        <div class="course-grid" id="courseGrid">
          <?php foreach ($courses as $course): ?>
            <a class="course-card" href="/course.php?id=<?= (int) $course['id'] ?>" data-search="<?= escape(text_lower($course['name'] . ' ' . $course['by'] . ' ' . $course['level'])) ?>">
              <div class="course-cover <?= escape($course['cover']) ?>"><span class="course-level"><?= escape($course['level']) ?></span><span class="course-cover-icon"><?= icon($course['symbol'], 26) ?></span></div>
              <div class="course-body">
                <h3><?= escape($course['name']) ?></h3>
                <p><?= escape($course['by']) ?></p>
                <div class="course-progress"><div class="progress-track"><span style="width:<?= (int) $course['progress'] ?>%"></span></div><small><?= (int) $course['progress'] ?>%</small></div>
              </div>
            </a>
          <?php endforeach; ?>
          <div class="empty-state" id="courseSearchEmpty" hidden></div>
        </div>
      <?php endif; ?>
    </section>

    <section class="lower-grid">
      <article class="panel section-panel">
        <div class="section-head"><h2><?= escape($activityTitle) ?></h2><a class="text-button" href="<?= escape($activityLink[0]) ?>"><?= escape($activityLink[1]) ?> <span aria-hidden="true">→</span></a></div>
        <?php if ($activities === []): ?>
          <div class="empty-state compact"><?= escape($activityEmpty) ?></div>
        <?php else: ?>
          <div class="activity-list">
            <?php foreach ($activities as $index => $activity): ?>
              <a class="activity" href="<?= escape($activity['href']) ?>">
                <span class="activity-icon <?= ['', 'blue', 'gold'][$index] ?? '' ?>"><?= icon($activity['icon'], 15) ?></span>
                <div class="activity-text"><strong><?= escape($activity['title']) ?></strong><span><?= escape($activity['meta']) ?></span></div>
                <span class="activity-score"><?= escape($activity['badge']) ?></span>
              </a>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </article>

      <article class="panel section-panel">
        <div class="section-head"><h2>Pesan terbaru</h2><a class="text-button" href="/messages.php">Buka pesan <span aria-hidden="true">→</span></a></div>
        <?php if ($recentMessages === []): ?>
          <div class="empty-state compact">Belum ada pesan masuk.</div>
        <?php else: ?>
          <div class="activity-list">
            <?php foreach ($recentMessages as $message): ?>
              <a class="activity" href="/messages.php?conversation=<?= (int) $message['conversation_id'] ?>">
                <span class="activity-icon blue"><?= icon('message', 15) ?></span>
                <div class="activity-text"><strong><?= escape($message['sender_name']) ?></strong><span><?= escape(text_excerpt($message['body'], 70)) ?></span></div>
                <span class="activity-score<?= $message['read_at'] === null ? '' : ' muted' ?>"><?= $message['read_at'] === null ? 'Baru' : escape(indonesian_date(strtotime($message['created_at']), false, true)) ?></span>
              </a>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </article>
    </section>

<?php require __DIR__ . '/layout-end.php'; ?>
