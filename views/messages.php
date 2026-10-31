<?php require __DIR__ . '/manage-header.php'; ?>

  <div class="manage-grid messages-layout">
    <section class="manage-panel messages-inbox">
      <div class="section-head">
        <div><h2>Percakapan</h2><p class="panel-subtitle"><?= count($conversations) ?> percakapan</p></div>
      </div>
      <?php if ($contacts !== []): ?>
        <form class="new-conversation-form" method="post" action="/messages.php">
          <input type="hidden" name="csrf_token" value="<?= escape($csrfToken) ?>">
          <input type="hidden" name="action" value="start_conversation">
          <label for="message_contact">Mulai pesan baru</label>
          <select id="message_contact" name="contact" required>
            <option value="">Pilih kontak</option>
            <?php foreach ($contacts as $contact): ?>
              <?php $contactKey = (int) $contact['contact_id'] . ':' . (int) $contact['course_id']; ?>
              <option value="<?= escape($contactKey) ?>">
                <?= escape($contact['contact_name']) ?> · <?= escape(ucfirst($contact['contact_role'])) ?><?= $contact['course_title'] !== '' ? ' · ' . escape($contact['course_title']) : '' ?>
              </option>
            <?php endforeach; ?>
          </select>
          <button class="secondary-button" type="submit">Buka percakapan</button>
        </form>
      <?php else: ?>
        <div class="empty-manage messages-contact-empty">
          <?php if ($user['role'] === 'murid'): ?>
            Ikuti kursus dengan guru aktif untuk mulai mengirim pesan.
          <?php elseif ($user['role'] === 'guru'): ?>
            Belum ada murid terdaftar di kursusmu atau akun admin aktif.
          <?php else: ?>
            Belum ada akun guru aktif yang dapat dihubungi.
          <?php endif; ?>
        </div>
      <?php endif; ?>

      <?php if ($conversations === []): ?>
        <div class="empty-manage messages-empty">Belum ada percakapan. Pilih kontak di atas untuk memulai.</div>
      <?php else: ?>
        <nav class="conversation-list" aria-label="Daftar percakapan">
          <?php foreach ($conversations as $conversation): ?>
            <a class="conversation-link <?= $selectedConversation !== null && (int) $selectedConversation['id'] === (int) $conversation['id'] ? 'is-selected' : '' ?>" href="/messages.php?conversation=<?= (int) $conversation['id'] ?>">
              <span class="conversation-avatar"><?= escape(strtoupper(substr($conversation['other_name'], 0, 1))) ?></span>
              <span class="conversation-preview">
                <strong><?= escape($conversation['other_name']) ?></strong>
                <small><?= escape(ucfirst($conversation['other_role'])) ?><?= $conversation['course_title'] !== null ? ' · ' . escape($conversation['course_title']) : '' ?></small>
                <span><?= escape($conversation['last_message'] ?? 'Percakapan baru') ?></span>
              </span>
              <span class="conversation-meta">
                <?php if ($conversation['last_message_at'] !== null): ?><time><?= escape(date('d/m', strtotime($conversation['last_message_at']))) ?></time><?php endif; ?>
                <?php if ((int) $conversation['unread_count'] > 0): ?><b class="unread-badge"><?= (int) $conversation['unread_count'] ?></b><?php endif; ?>
              </span>
            </a>
          <?php endforeach; ?>
        </nav>
      <?php endif; ?>
    </section>

    <section class="manage-panel message-thread">
      <?php if ($selectedConversation === null): ?>
        <div class="message-welcome">
          <span class="message-welcome-icon" aria-hidden="true">✉</span>
          <h2>Pilih percakapan</h2>
          <p>Percakapan privat menghubungkan murid dengan guru kursusnya, atau guru dengan admin.</p>
        </div>
      <?php else: ?>
        <header class="thread-header">
          <span class="conversation-avatar"><?= escape(strtoupper(substr($selectedConversation['other_name'], 0, 1))) ?></span>
          <div>
            <h2><?= escape($selectedConversation['other_name']) ?></h2>
            <p><?= escape(ucfirst($selectedConversation['other_role'])) ?><?= $selectedConversation['course_title'] !== null ? ' · ' . escape($selectedConversation['course_title']) : '' ?></p>
          </div>
        </header>
        <div class="message-list" aria-live="polite">
          <?php if ($messages === []): ?>
            <div class="empty-manage">Belum ada pesan di percakapan ini. Kirim pesan pertama untuk memulai.</div>
          <?php else: ?>
            <?php foreach ($messages as $message): ?>
              <?php $isMine = (int) $message['sender_id'] === (int) $user['id']; ?>
              <article class="message-bubble <?= $isMine ? 'is-mine' : 'is-theirs' ?>">
                <?php if (!$isMine): ?><strong><?= escape($message['sender_name']) ?></strong><?php endif; ?>
                <p><?= nl2br(escape($message['body'])) ?></p>
                <time datetime="<?= escape(date(DATE_ATOM, strtotime($message['created_at']))) ?>"><?= escape(date('d M Y · H:i', strtotime($message['created_at']))) ?></time>
              </article>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>
        <form class="message-compose" method="post" action="/messages.php?conversation=<?= (int) $selectedConversation['id'] ?>">
          <input type="hidden" name="csrf_token" value="<?= escape($csrfToken) ?>">
          <input type="hidden" name="action" value="send_message">
          <input type="hidden" name="conversation_id" value="<?= (int) $selectedConversation['id'] ?>">
          <label class="visually-hidden" for="message_body">Tulis pesan</label>
          <textarea id="message_body" name="body" rows="3" maxlength="4000" required placeholder="Tulis pesan..."></textarea>
          <button class="primary-button" type="submit">Kirim <span aria-hidden="true">→</span></button>
        </form>
      <?php endif; ?>
    </section>
  </div>
<?php require __DIR__ . '/layout-end.php'; ?>
