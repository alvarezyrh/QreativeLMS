<form class="module-filter" method="get" action="/modules.php" role="search">
  <?php if ($currentFolder !== null): ?>
    <input type="hidden" name="folder" value="<?= (int) $currentFolder['id'] ?>">
  <?php elseif ($selectedLevel !== ''): ?>
    <input type="hidden" name="level" value="<?= escape($selectedLevel) ?>">
  <?php endif; ?>
  <label class="module-search">
    <span class="visually-hidden">Cari modul</span>
    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="10.8" cy="10.8" r="6.8" stroke="currentColor" stroke-width="1.7"/><path d="m16 16 4 4" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/></svg>
    <input name="q" type="search" maxlength="80" value="<?= escape($search) ?>" placeholder="Cari modul…">
  </label>
  <label>
    <span class="visually-hidden">Jenis</span>
    <select name="kind" data-autosubmit>
      <option value="">Semua jenis</option>
      <?php foreach ($kinds as $kind => $kindLabel): ?>
        <option value="<?= escape($kind) ?>"<?= $selectedKind === $kind ? ' selected' : '' ?>><?= escape($kindLabel) ?></option>
      <?php endforeach; ?>
    </select>
  </label>
  <label>
    <span class="visually-hidden">Urutkan</span>
    <select name="sort" data-autosubmit>
      <option value="new"<?= $sort === 'new' ? ' selected' : '' ?>>Terbaru dulu</option>
      <option value="old"<?= $sort === 'old' ? ' selected' : '' ?>>Terlama dulu</option>
    </select>
  </label>
  <button class="secondary-button" type="submit">Cari</button>
</form>
