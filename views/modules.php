<?php require __DIR__ . '/manage-header.php'; ?>
<?php
$formatModuleDate = static function (?string $value): string {
    if ($value === null || $value === '') {
        return '—';
    }
    $timestamp = (int) strtotime($value . ' UTC');

    return indonesian_date($timestamp, false, true) . ' ' . date('Y', $timestamp);
};
$itemsTitle = $sort === 'old' ? 'Terlama diunggah' : 'Terbaru diunggah';
?>

<?php if ($currentFolder === null): ?>
  <nav class="level-tabs" aria-label="Kategori level">
    <a class="level-tab<?= $selectedLevel === '' ? ' active' : '' ?>" href="<?= escape($moduleUrl(['level' => ''])) ?>"<?= $selectedLevel === '' ? ' aria-current="page"' : '' ?>>
      Semua <span><?= $totalItems ?></span>
    </a>
    <?php foreach ($levels as $level => $levelName): ?>
      <a class="level-tab<?= $selectedLevel === $level ? ' active' : '' ?>" href="<?= escape($moduleUrl(['level' => $level])) ?>" title="<?= escape($levelName) ?>"<?= $selectedLevel === $level ? ' aria-current="page"' : '' ?>>
        <?= escape(module_level_label($level)) ?> <span><?= (int) $levelCounts[$level] ?></span>
      </a>
    <?php endforeach; ?>
  </nav>

  <section class="manage-panel library-panel">
    <div class="section-head">
      <div>
        <h2>Rak modul</h2>
        <p class="panel-subtitle"><?= count($folders) ?> folder · <?= $totalItems ?> file dan tautan</p>
      </div>
    </div>
    <details class="new-folder">
      <summary class="primary-button"><span aria-hidden="true">+</span> Folder baru</summary>
      <form class="manage-form new-folder-form" method="post" action="/modules.php">
        <input type="hidden" name="csrf_token" value="<?= escape($csrfToken) ?>">
        <input type="hidden" name="action" value="create_folder">
        <div class="form-row">
          <div>
            <label for="folder_name">Nama folder</label>
            <input id="folder_name" name="name" required maxlength="120" placeholder="Contoh: Speaking A2 — Daily routines">
          </div>
          <div>
            <label for="folder_level">Kategori level</label>
            <select id="folder_level" name="level" required>
              <?php foreach ($levels as $level => $levelName): ?>
                <option value="<?= escape($level) ?>"<?= $selectedLevel === $level ? ' selected' : '' ?>><?= escape(module_level_label($level) . ' · ' . $levelName) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
        <label for="folder_description">Deskripsi <span class="form-hint">(opsional)</span></label>
        <input id="folder_description" name="description" maxlength="500" placeholder="Topik atau tujuan pembelajaran folder ini.">
        <button class="primary-button" type="submit">Buat folder <span aria-hidden="true">→</span></button>
      </form>
    </details>

    <?php if ($foldersByLevel === []): ?>
      <div class="empty-manage">Belum ada folder untuk level ini. Buat dengan tombol "Folder baru".</div>
    <?php else: ?>
      <div class="shelf-grid">
        <?php foreach ($foldersByLevel as $level => $levelFolders): ?>
          <div class="shelf">
            <div class="shelf-head">
              <span class="level-badge"><?= escape(module_level_label($level)) ?></span>
              <strong><?= escape($levels[$level]) ?></strong>
              <small><?= (int) $levelCounts[$level] ?> item</small>
            </div>
            <div class="shelf-folders">
              <?php foreach ($levelFolders as $folder): ?>
                <a class="folder-row" href="/modules.php?folder=<?= (int) $folder['id'] ?>" title="<?= escape($folder['description']) ?>">
                  <span class="folder-icon"><?= icon('folder', 17) ?></span>
                  <span class="folder-row-body">
                    <strong><?= escape($folder['name']) ?></strong>
                    <small><?= (int) $folder['modul_count'] ?> modul · <?= (int) $folder['bahan_count'] ?> bahan ajar<?= $folder['last_item_at'] !== null ? ' · ' . escape($formatModuleDate($folder['last_item_at'])) : '' ?></small>
                  </span>
                  <span class="folder-row-arrow" aria-hidden="true">›</span>
                </a>
              <?php endforeach; ?>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </section>

  <section class="manage-panel module-items-panel">
    <div class="section-head">
      <div>
        <h2><?= escape($itemsTitle) ?></h2>
        <p class="panel-subtitle"><?= count($items) ?> hasil<?= $selectedLevel !== '' ? ' · level ' . escape(module_level_label($selectedLevel)) : '' ?><?= count($items) === 200 ? ' · menampilkan 200 pertama' : '' ?></p>
      </div>
    </div>
    <?php require __DIR__ . '/module-filter.php'; ?>
    <?php $showFolder = true; require __DIR__ . '/module-items.php'; ?>
  </section>

<?php else: ?>
  <a class="text-button back-link" href="<?= escape('/modules.php?level=' . rawurlencode($currentFolder['level'])) ?>"><span aria-hidden="true">←</span> Semua folder <?= escape(module_level_label($currentFolder['level'])) ?></a>

  <div class="manage-grid module-layout">
    <div class="module-side">
      <section class="manage-panel">
        <?php if ($editingItem !== null): ?>
          <div class="section-head">
            <div><h2>Ubah modul</h2><p class="panel-subtitle">File tidak berubah. Unggah ulang sebagai modul baru jika perlu revisi file.</p></div>
            <a class="text-button" href="/modules.php?folder=<?= (int) $currentFolder['id'] ?>">Batal</a>
          </div>
          <form class="manage-form" method="post" action="/modules.php">
            <input type="hidden" name="csrf_token" value="<?= escape($csrfToken) ?>">
            <input type="hidden" name="action" value="update_item">
            <input type="hidden" name="item_id" value="<?= (int) $editingItem['id'] ?>">
            <input type="hidden" name="return_folder" value="<?= (int) $currentFolder['id'] ?>">
            <label for="item_title">Judul</label>
            <input id="item_title" name="title" required maxlength="160" value="<?= escape($editingItem['title']) ?>">
            <div class="form-row">
              <div>
                <label for="item_kind">Jenis</label>
                <select id="item_kind" name="kind" required>
                  <?php foreach ($kinds as $kind => $kindLabel): ?>
                    <option value="<?= escape($kind) ?>"<?= $editingItem['kind'] === $kind ? ' selected' : '' ?>><?= escape($kindLabel) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div>
                <label for="item_folder">Folder</label>
                <select id="item_folder" name="folder_id" required>
                  <?php foreach ($folders as $folder): ?>
                    <option value="<?= (int) $folder['id'] ?>"<?= (int) $folder['id'] === (int) $editingItem['folder_id'] ? ' selected' : '' ?>><?= escape(module_level_label($folder['level']) . ' · ' . $folder['name']) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
            </div>
            <label for="item_description">Deskripsi</label>
            <textarea id="item_description" name="description" rows="4" maxlength="1000"><?= escape($editingItem['description']) ?></textarea>
            <button class="primary-button" type="submit">Simpan perubahan <span aria-hidden="true">→</span></button>
          </form>
        <?php else: ?>
          <div class="section-head">
            <div><h2>Tambah ke folder</h2><p class="panel-subtitle">Unggah file atau simpan tautan materi.</p></div>
          </div>
          <form class="manage-form" method="post" action="/modules.php" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?= escape($csrfToken) ?>">
            <input type="hidden" name="action" value="create_item">
            <input type="hidden" name="folder_id" value="<?= (int) $currentFolder['id'] ?>">
            <input type="hidden" name="return_folder" value="<?= (int) $currentFolder['id'] ?>">
            <label for="item_title">Judul</label>
            <input id="item_title" name="title" required maxlength="160" placeholder="Contoh: Unit 3 — Daily routines">
            <span class="field-label" id="item_kind_label">Jenis</span>
            <div class="segmented" role="radiogroup" aria-labelledby="item_kind_label">
              <?php foreach ($kinds as $kind => $kindLabel): ?>
                <label><input type="radio" name="kind" value="<?= escape($kind) ?>"<?= $kind === 'modul' ? ' checked' : '' ?>><span><?= escape($kindLabel) ?></span></label>
              <?php endforeach; ?>
            </div>
            <label for="item_file">File</label>
            <div class="file-drop">
              <input id="item_file" name="file" type="file" data-file-input accept=".pdf,.doc,.docx,.ppt,.pptx,.xls,.xlsx,.txt,.jpg,.jpeg,.png,.webp,.mp3,.mp4,.zip">
              <label class="secondary-button" for="item_file">Pilih file</label>
              <span data-file-name>PDF, Office, gambar, audio/video, ZIP · maks. <?= escape(format_bytes($uploadLimit)) ?></span>
            </div>
            <label for="item_link">atau tautan <span class="form-hint">(Google Drive, YouTube, dll.)</span></label>
            <input id="item_link" name="link_url" type="url" maxlength="500" placeholder="https://">
            <label for="item_description">Deskripsi <span class="form-hint">(opsional)</span></label>
            <textarea id="item_description" name="description" rows="3" maxlength="1000" placeholder="Tujuan, durasi, atau catatan penggunaan."></textarea>
            <button class="primary-button" type="submit">Simpan ke folder <span aria-hidden="true">→</span></button>
          </form>
        <?php endif; ?>
      </section>

      <?php if ($canEditFolder): ?>
        <section class="manage-panel">
          <details class="folder-settings">
            <summary><span><strong>Pengaturan folder</strong><small>Ubah nama, level, atau hapus folder.</small></span><span class="secondary-button">Atur</span></summary>
            <form class="manage-form" method="post" action="/modules.php">
              <input type="hidden" name="csrf_token" value="<?= escape($csrfToken) ?>">
              <input type="hidden" name="action" value="update_folder">
              <input type="hidden" name="folder_id" value="<?= (int) $currentFolder['id'] ?>">
              <input type="hidden" name="return_folder" value="<?= (int) $currentFolder['id'] ?>">
              <label for="edit_folder_name">Nama folder</label>
              <input id="edit_folder_name" name="name" required maxlength="120" value="<?= escape($currentFolder['name']) ?>">
              <label for="edit_folder_level">Kategori level</label>
              <select id="edit_folder_level" name="level" required>
                <?php foreach ($levels as $level => $levelName): ?>
                  <option value="<?= escape($level) ?>"<?= $currentFolder['level'] === $level ? ' selected' : '' ?>><?= escape(module_level_label($level) . ' · ' . $levelName) ?></option>
                <?php endforeach; ?>
              </select>
              <label for="edit_folder_description">Deskripsi</label>
              <textarea id="edit_folder_description" name="description" rows="3" maxlength="500"><?= escape($currentFolder['description']) ?></textarea>
              <button class="primary-button" type="submit">Simpan folder</button>
            </form>
            <?php if ($folderItemCount === 0): ?>
              <form class="action-form folder-delete" method="post" action="/modules.php" onsubmit="return confirm('Hapus folder kosong ini?')">
                <input type="hidden" name="csrf_token" value="<?= escape($csrfToken) ?>">
                <input type="hidden" name="action" value="delete_folder">
                <input type="hidden" name="folder_id" value="<?= (int) $currentFolder['id'] ?>">
                <button class="secondary-button danger-button" type="submit">Hapus folder</button>
              </form>
            <?php else: ?>
              <p class="form-hint folder-delete">Folder hanya bisa dihapus jika sudah kosong.</p>
            <?php endif; ?>
          </details>
        </section>
      <?php endif; ?>
    </div>

    <section class="manage-panel module-items-panel">
      <div class="section-head folder-head">
        <span class="folder-icon"><?= icon('folder', 22) ?></span>
        <div>
          <h2>Isi folder</h2>
          <p class="panel-subtitle">
            <span class="level-badge"><?= escape(module_level_label($currentFolder['level'])) ?></span>
            <?= escape($levels[$currentFolder['level']]) ?> · <?= $folderItemCount ?> item · dibuat oleh <?= escape($currentFolder['creator_name'] ?? 'Sistem') ?>
          </p>
        </div>
      </div>
      <?php require __DIR__ . '/module-filter.php'; ?>
      <?php $showFolder = false; require __DIR__ . '/module-items.php'; ?>
    </section>
  </div>
<?php endif; ?>
<?php require __DIR__ . '/layout-end.php'; ?>
