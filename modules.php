<?php

declare(strict_types=1);

require_once __DIR__ . '/app/bootstrap.php';
require_once __DIR__ . '/app/lms.php';
require_once __DIR__ . '/app/layout.php';
require_once __DIR__ . '/app/modules.php';

$user = require_auth(database());
require_role($user, ...MODULE_ROLES);
$connection = database();

$loadFolder = static function (int $folderId) use ($connection): array {
    $statement = $connection->prepare('SELECT * FROM module_folders WHERE id = :id');
    $statement->execute(['id' => $folderId]);
    $folder = $statement->fetch();
    if ($folder === false) {
        throw new InvalidArgumentException('Folder tidak ditemukan.');
    }

    return $folder;
};

$loadEditableItem = static function (int $itemId) use ($connection, $user): array {
    $statement = $connection->prepare('SELECT * FROM module_items WHERE id = :id');
    $statement->execute(['id' => $itemId]);
    $item = $statement->fetch();
    if ($item === false || !can_edit_module_entry($user, $item['uploaded_by'])) {
        throw new InvalidArgumentException('Modul tidak ditemukan atau bukan milik Anda.');
    }

    return $item;
};

$validateFolderFields = static function (): array {
    $name = post_value('name', 120);
    $level = post_value('level', 8);
    $description = post_value('description', 500);
    if ($name === '') {
        throw new InvalidArgumentException('Nama folder wajib diisi.');
    }
    if (!array_key_exists($level, module_levels())) {
        throw new InvalidArgumentException('Kategori level tidak valid.');
    }

    return [$name, $level, $description];
};

$validateItemFields = static function (): array {
    $title = post_value('title', 160);
    $kind = post_value('kind', 16);
    $description = post_value('description', 1000);
    if ($title === '') {
        throw new InvalidArgumentException('Judul modul wajib diisi.');
    }
    if (!array_key_exists($kind, module_kinds())) {
        throw new InvalidArgumentException('Jenis modul tidak valid.');
    }

    return [$title, $kind, $description];
};

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // File yang melebihi post_max_size membuat $_POST kosong, termasuk token CSRF.
    if ($_POST === [] && (int) ($_SERVER['CONTENT_LENGTH'] ?? 0) > 0) {
        set_flash('File terlalu besar. Batas upload saat ini ' . format_bytes(module_upload_limit()) . '.', 'error');
        redirect('/modules.php');
    }
    require_post_csrf();

    $returnFolder = filter_var($_POST['return_folder'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    $returnUrl = $returnFolder ? '/modules.php?folder=' . $returnFolder : '/modules.php';

    try {
        $action = post_value('action', 24);
        switch ($action) {
            case 'create_folder':
                [$name, $level, $description] = $validateFolderFields();
                $connection->prepare(
                    'INSERT INTO module_folders (name, level, description, created_by) VALUES (:name, :level, :description, :created_by)'
                )->execute(['name' => $name, 'level' => $level, 'description' => $description, 'created_by' => $user['id']]);
                $returnUrl = '/modules.php?folder=' . (int) $connection->lastInsertId();
                set_flash('Folder dibuat. Tambahkan modul atau bahan ajar pertamanya.');
                break;

            case 'update_folder':
                $folder = $loadFolder(post_id('folder_id'));
                if (!can_edit_module_entry($user, $folder['created_by'])) {
                    throw new InvalidArgumentException('Hanya pembuat folder atau admin yang dapat mengubahnya.');
                }
                [$name, $level, $description] = $validateFolderFields();
                $connection->prepare(
                    'UPDATE module_folders SET name = :name, level = :level, description = :description, updated_at = CURRENT_TIMESTAMP WHERE id = :id'
                )->execute(['name' => $name, 'level' => $level, 'description' => $description, 'id' => $folder['id']]);
                set_flash('Folder diperbarui.');
                break;

            case 'delete_folder':
                $folder = $loadFolder(post_id('folder_id'));
                if (!can_edit_module_entry($user, $folder['created_by'])) {
                    throw new InvalidArgumentException('Hanya pembuat folder atau admin yang dapat menghapusnya.');
                }
                $count = $connection->prepare('SELECT COUNT(*) FROM module_items WHERE folder_id = :id');
                $count->execute(['id' => $folder['id']]);
                if ((int) $count->fetchColumn() > 0) {
                    throw new InvalidArgumentException('Kosongkan folder terlebih dahulu: pindahkan atau hapus modul di dalamnya.');
                }
                $connection->prepare('DELETE FROM module_folders WHERE id = :id')->execute(['id' => $folder['id']]);
                $returnUrl = '/modules.php';
                set_flash('Folder dihapus.');
                break;

            case 'create_item':
                $folder = $loadFolder(post_id('folder_id'));
                [$title, $kind, $description] = $validateItemFields();
                $linkUrl = post_value('link_url', 500);
                $hasFile = isset($_FILES['file']) && ($_FILES['file']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;
                if (!$hasFile && $linkUrl === '') {
                    throw new InvalidArgumentException('Unggah file atau isi tautan materi.');
                }
                if ($linkUrl !== '' && (!filter_var($linkUrl, FILTER_VALIDATE_URL) || preg_match('#\Ahttps?://#i', $linkUrl) !== 1)) {
                    throw new InvalidArgumentException('Tautan harus diawali http:// atau https://.');
                }
                $file = $hasFile
                    ? store_module_file($_FILES['file'])
                    : ['file_name' => '', 'original_name' => '', 'mime_type' => '', 'size_bytes' => 0];
                try {
                    $connection->prepare(
                        'INSERT INTO module_items (folder_id, title, kind, description, file_name, original_name, mime_type, size_bytes, link_url, uploaded_by)
                         VALUES (:folder_id, :title, :kind, :description, :file_name, :original_name, :mime_type, :size_bytes, :link_url, :uploaded_by)'
                    )->execute([
                        'folder_id' => $folder['id'],
                        'title' => $title,
                        'kind' => $kind,
                        'description' => $description,
                        'link_url' => $hasFile ? '' : $linkUrl,
                        'uploaded_by' => $user['id'],
                    ] + $file);
                    $connection->prepare('UPDATE module_folders SET updated_at = CURRENT_TIMESTAMP WHERE id = :id')->execute(['id' => $folder['id']]);
                } catch (PDOException $exception) {
                    delete_module_file($file['file_name']);
                    throw $exception;
                }
                $returnUrl = '/modules.php?folder=' . (int) $folder['id'];
                set_flash(module_kinds()[$kind] . ' "' . $title . '" ditambahkan.');
                break;

            case 'update_item':
                $item = $loadEditableItem(post_id('item_id'));
                [$title, $kind, $description] = $validateItemFields();
                $folder = $loadFolder(post_id('folder_id'));
                $connection->prepare(
                    'UPDATE module_items SET title = :title, kind = :kind, description = :description, folder_id = :folder_id, updated_at = CURRENT_TIMESTAMP WHERE id = :id'
                )->execute(['title' => $title, 'kind' => $kind, 'description' => $description, 'folder_id' => $folder['id'], 'id' => $item['id']]);
                set_flash('Modul diperbarui.');
                break;

            case 'delete_item':
                $item = $loadEditableItem(post_id('item_id'));
                $connection->prepare('DELETE FROM module_items WHERE id = :id')->execute(['id' => $item['id']]);
                delete_module_file($item['file_name']);
                set_flash('Modul dihapus.');
                break;

            default:
                throw new InvalidArgumentException('Aksi tidak dikenal.');
        }
    } catch (InvalidArgumentException $exception) {
        set_flash($exception->getMessage(), 'error');
    }
    redirect($returnUrl);
}

// ---------- Tampilan ----------
$levels = module_levels();
$kinds = module_kinds();
$levelOrder = "CASE f.level WHEN 'A1' THEN 1 WHEN 'A2' THEN 2 WHEN 'B1' THEN 3 WHEN 'B2' THEN 4 WHEN 'C1' THEN 5 WHEN 'C2' THEN 6 ELSE 7 END";

$queryText = static function (string $key, int $max): string {
    $value = $_GET[$key] ?? '';

    return is_string($value) ? text_excerpt(trim($value), $max) : '';
};
$selectedLevel = $queryText('level', 8);
$selectedLevel = array_key_exists($selectedLevel, $levels) ? $selectedLevel : '';
$selectedKind = $queryText('kind', 16);
$selectedKind = array_key_exists($selectedKind, $kinds) ? $selectedKind : '';
$sort = $queryText('sort', 8) === 'old' ? 'old' : 'new';
$search = $queryText('q', 80);

$currentFolder = null;
$folderId = filter_var($_GET['folder'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
if ($folderId) {
    $statement = $connection->prepare(
        'SELECT f.*, u.name AS creator_name FROM module_folders f LEFT JOIN users u ON u.id = f.created_by WHERE f.id = :id'
    );
    $statement->execute(['id' => $folderId]);
    $currentFolder = $statement->fetch() ?: null;
    if ($currentFolder === null) {
        http_response_code(404);
        exit('Folder tidak ditemukan.');
    }
}

// Semua folder (untuk kartu perpustakaan dan pilihan "pindahkan ke folder").
$folders = $connection->query(
    "SELECT f.*, u.name AS creator_name,
            COUNT(i.id) AS item_count,
            COALESCE(SUM(i.kind = 'modul'), 0) AS modul_count,
            COALESCE(SUM(i.kind = 'bahan_ajar'), 0) AS bahan_count,
            MAX(i.created_at) AS last_item_at
     FROM module_folders f
     LEFT JOIN module_items i ON i.folder_id = f.id
     LEFT JOIN users u ON u.id = f.created_by
     GROUP BY f.id
     ORDER BY $levelOrder, f.name COLLATE NOCASE"
)->fetchAll();

$foldersByLevel = [];
$levelCounts = array_fill_keys(array_keys($levels), 0);
foreach ($folders as $folder) {
    $levelCounts[$folder['level']] += (int) $folder['item_count'];
    if ($selectedLevel === '' || $folder['level'] === $selectedLevel) {
        $foldersByLevel[$folder['level']][] = $folder;
    }
}

// Daftar modul: seluruh perpustakaan atau satu folder, dengan filter dan urutan.
$conditions = [];
$parameters = [];
if ($currentFolder !== null) {
    $conditions[] = 'i.folder_id = :folder_id';
    $parameters['folder_id'] = $currentFolder['id'];
} elseif ($selectedLevel !== '') {
    $conditions[] = 'f.level = :level';
    $parameters['level'] = $selectedLevel;
}
if ($selectedKind !== '') {
    $conditions[] = 'i.kind = :kind';
    $parameters['kind'] = $selectedKind;
}
if ($search !== '') {
    $conditions[] = "(i.title LIKE :search ESCAPE '\\' OR i.description LIKE :search ESCAPE '\\' OR i.original_name LIKE :search ESCAPE '\\')";
    $parameters['search'] = '%' . str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $search) . '%';
}
$direction = $sort === 'old' ? 'ASC' : 'DESC';
$itemStatement = $connection->prepare(
    'SELECT i.*, f.name AS folder_name, f.level AS folder_level, u.name AS uploader_name
     FROM module_items i
     JOIN module_folders f ON f.id = i.folder_id
     LEFT JOIN users u ON u.id = i.uploaded_by'
    . ($conditions === [] ? '' : ' WHERE ' . implode(' AND ', $conditions))
    . " ORDER BY i.created_at $direction, i.id $direction LIMIT 200"
);
$itemStatement->execute($parameters);
$items = $itemStatement->fetchAll();

$editingItem = null;
$editId = filter_var($_GET['edit'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
if ($currentFolder !== null && $editId) {
    $statement = $connection->prepare('SELECT * FROM module_items WHERE id = :id AND folder_id = :folder_id');
    $statement->execute(['id' => $editId, 'folder_id' => $currentFolder['id']]);
    $candidate = $statement->fetch();
    if ($candidate !== false && can_edit_module_entry($user, $candidate['uploaded_by'])) {
        $editingItem = $candidate;
    }
}
$canEditFolder = $currentFolder !== null && can_edit_module_entry($user, $currentFolder['created_by']);
$folderItemCount = $currentFolder === null ? 0 : (int) (array_values(array_filter(
    $folders,
    static fn (array $folder): bool => (int) $folder['id'] === (int) $currentFolder['id']
))[0]['item_count'] ?? 0);

$totalItems = (int) $connection->query('SELECT COUNT(*) FROM module_items')->fetchColumn();
$uploadLimit = module_upload_limit();
$csrfToken = csrf_token();
$flash = take_flash();
$pageTitle = $currentFolder !== null ? $currentFolder['name'] : 'Modul';
$pageDescription = $currentFolder !== null
    ? ($currentFolder['description'] !== '' ? $currentFolder['description'] : 'Kumpulan modul dan bahan ajar dalam folder ini.')
    : 'Perpustakaan modul dan bahan ajar, tersusun per level pembelajaran.';

/** URL halaman modul dengan filter saat ini, ditimpa oleh $changes. */
$moduleUrl = static function (array $changes = []) use ($currentFolder, $selectedLevel, $selectedKind, $sort, $search): string {
    $query = array_filter(array_merge([
        'folder' => $currentFolder['id'] ?? null,
        'level' => $currentFolder === null ? $selectedLevel : '',
        'kind' => $selectedKind,
        'sort' => $sort === 'old' ? 'old' : '',
        'q' => $search,
    ], $changes), static fn ($value) => $value !== null && $value !== '');

    return '/modules.php' . ($query === [] ? '' : '?' . http_build_query($query));
};

require __DIR__ . '/views/modules.php';
