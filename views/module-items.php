<?php if ($items === []): ?>
  <div class="empty-manage">
    <?= $search !== '' || $selectedKind !== '' ? 'Tidak ada modul yang cocok dengan filter ini.' : ($currentFolder !== null ? 'Folder ini masih kosong. Tambahkan modul atau bahan ajar pertama menggunakan formulir di samping.' : 'Belum ada modul. Buka salah satu folder untuk mulai mengunggah.') ?>
  </div>
<?php else: ?>
  <div class="module-list">
    <?php foreach ($items as $item): ?>
      <?php
        $badge = module_badge($item);
        $badgeType = match (true) {
            $badge === 'URL' => 'link',
            $badge === 'PDF' => 'pdf',
            in_array($badge, ['DOC', 'DOCX', 'TXT'], true) => 'doc',
            in_array($badge, ['PPT', 'PPTX'], true) => 'ppt',
            in_array($badge, ['XLS', 'XLSX'], true) => 'xls',
            in_array($badge, ['JPG', 'PNG', 'WEBP'], true) => 'img',
            in_array($badge, ['MP3', 'MP4'], true) => 'media',
            default => 'zip',
        };
        $canView = $item['file_name'] !== '' && in_array($item['mime_type'], ['application/pdf', 'image/png', 'image/jpeg', 'image/webp'], true);
        $canEdit = can_edit_module_entry($user, $item['uploaded_by']);
      ?>
      <article class="module-item">
        <span class="file-badge file-<?= $badgeType ?>"><?= escape($badge) ?></span>
        <div class="module-item-info">
          <div class="module-item-title">
            <h3><?= escape($item['title']) ?></h3>
            <span class="kind-pill kind-<?= escape($item['kind']) ?>"><?= escape($kinds[$item['kind']]) ?></span>
          </div>
          <?php if ($item['description'] !== ''): ?><p><?= escape($item['description']) ?></p><?php endif; ?>
          <div class="manage-meta">
            <?php if ($showFolder): ?>
              <a href="/modules.php?folder=<?= (int) $item['folder_id'] ?>"><?= escape(module_level_label($item['folder_level']) . ' · ' . $item['folder_name']) ?></a>
            <?php endif; ?>
            <span><?= escape($item['uploader_name'] ?? 'Pengguna dihapus') ?></span>
            <span><?= escape($formatModuleDate($item['created_at'])) ?></span>
            <?php if ($item['file_name'] !== ''): ?>
              <span title="<?= escape($item['original_name']) ?>"><?= escape(format_bytes((int) $item['size_bytes'])) ?></span>
            <?php else: ?>
              <span class="module-link-host"><?= escape((string) parse_url($item['link_url'], PHP_URL_HOST)) ?></span>
            <?php endif; ?>
          </div>
        </div>
        <div class="module-item-actions">
          <?php if ($item['file_name'] === ''): ?>
            <a class="secondary-button" href="<?= escape($item['link_url']) ?>" target="_blank" rel="noopener noreferrer">Buka <span aria-hidden="true">↗</span></a>
          <?php else: ?>
            <?php if ($canView): ?>
              <a class="secondary-button" href="/module-file.php?id=<?= (int) $item['id'] ?>&amp;view=1" target="_blank" rel="noopener">Buka <span aria-hidden="true">↗</span></a>
            <?php endif; ?>
            <a class="secondary-button" href="/module-file.php?id=<?= (int) $item['id'] ?>" download>Unduh</a>
          <?php endif; ?>
          <?php if ($canEdit): ?>
            <a class="secondary-button" href="/modules.php?folder=<?= (int) $item['folder_id'] ?>&amp;edit=<?= (int) $item['id'] ?>">Ubah</a>
            <form class="action-form" method="post" action="/modules.php" onsubmit="return confirm('Hapus modul ini? File yang diunggah juga akan dihapus.')">
              <input type="hidden" name="csrf_token" value="<?= escape($csrfToken) ?>">
              <input type="hidden" name="action" value="delete_item">
              <input type="hidden" name="item_id" value="<?= (int) $item['id'] ?>">
              <?php if ($currentFolder !== null): ?><input type="hidden" name="return_folder" value="<?= (int) $currentFolder['id'] ?>"><?php endif; ?>
              <button class="secondary-button danger-button" type="submit">Hapus</button>
            </form>
          <?php endif; ?>
        </div>
      </article>
    <?php endforeach; ?>
  </div>
<?php endif; ?>
