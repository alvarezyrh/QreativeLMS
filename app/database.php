<?php

declare(strict_types=1);

function database(): PDO
{
    static $connection;

    if ($connection instanceof PDO) {
        return $connection;
    }

    $databasePath = getenv('QREATIVE_DB_PATH') ?: dirname(__DIR__) . '/storage/qreative.sqlite';
    $directory = dirname($databasePath);

    if (!is_dir($directory) && !mkdir($directory, 0700, true) && !is_dir($directory)) {
        throw new RuntimeException('Folder database tidak dapat dibuat.');
    }

    $connection = new PDO('sqlite:' . $databasePath, null, null, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
    $connection->exec('PRAGMA foreign_keys = ON');
    $connection->exec('PRAGMA busy_timeout = 5000');
    upgrade_schema($connection);

    return $connection;
}

/**
 * Tambahan kolom yang dibuat otomatis pada database lama, sehingga hosting
 * tanpa akses terminal tetap mendapat struktur terbaru tanpa menjalankan setup.
 */
function upgrade_schema(PDO $connection): void
{
    $userColumns = array_column($connection->query('PRAGMA table_info(users)')->fetchAll(), 'name');
    if ($userColumns === []) {
        return; // Database baru: tabel dibuat oleh app/setup.php.
    }

    $profileColumns = [
        'username' => 'TEXT COLLATE NOCASE',
        'avatar' => "TEXT NOT NULL DEFAULT ''",
        'nik' => "TEXT NOT NULL DEFAULT ''",
        'bpjs' => "TEXT NOT NULL DEFAULT ''",
        'address' => "TEXT NOT NULL DEFAULT ''",
        'marital_status' => "TEXT NOT NULL DEFAULT ''",
    ];
    foreach ($profileColumns as $column => $definition) {
        if (!in_array($column, $userColumns, true)) {
            $connection->exec('ALTER TABLE users ADD COLUMN ' . $column . ' ' . $definition);
        }
    }
    $connection->exec('CREATE UNIQUE INDEX IF NOT EXISTS idx_users_username ON users (username) WHERE username IS NOT NULL');

    upgrade_module_library($connection);
}

/** Tabel perpustakaan modul guru beserta folder awal per level CEFR. */
function upgrade_module_library(PDO $connection): void
{
    $connection->exec(
        "CREATE TABLE IF NOT EXISTS module_folders (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            level TEXT NOT NULL CHECK (level IN ('A1', 'A2', 'B1', 'B2', 'C1', 'C2', 'UMUM')),
            description TEXT NOT NULL DEFAULT '',
            created_by INTEGER REFERENCES users(id) ON DELETE SET NULL,
            created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
        );
        CREATE INDEX IF NOT EXISTS idx_module_folders_level ON module_folders (level, name);
        CREATE TABLE IF NOT EXISTS module_items (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            folder_id INTEGER NOT NULL REFERENCES module_folders(id) ON DELETE CASCADE,
            title TEXT NOT NULL,
            kind TEXT NOT NULL CHECK (kind IN ('modul', 'bahan_ajar')),
            description TEXT NOT NULL DEFAULT '',
            file_name TEXT NOT NULL DEFAULT '',
            original_name TEXT NOT NULL DEFAULT '',
            mime_type TEXT NOT NULL DEFAULT '',
            size_bytes INTEGER NOT NULL DEFAULT 0,
            link_url TEXT NOT NULL DEFAULT '',
            uploaded_by INTEGER REFERENCES users(id) ON DELETE SET NULL,
            created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
        );
        CREATE INDEX IF NOT EXISTS idx_module_items_folder_created ON module_items (folder_id, created_at);
        CREATE INDEX IF NOT EXISTS idx_module_items_created ON module_items (created_at);"
    );

    $seeded = $connection->query("SELECT 1 FROM app_settings WHERE setting_key = 'module_folders_seeded'")->fetchColumn();
    if ($seeded !== false) {
        return;
    }
    $insertFolder = $connection->prepare('INSERT INTO module_folders (name, level, description) VALUES (:name, :level, :description)');
    foreach ([
        ['A1', 'Modul A1 · Beginner', 'Perkenalan diri, angka, warna, dan kosakata dasar sehari-hari.'],
        ['A2', 'Modul A2 · Elementary', 'Kalimat sederhana, rutinitas, belanja, dan arah jalan.'],
        ['B1', 'Modul B1 · Intermediate', 'Bercerita pengalaman, opini sederhana, dan percakapan perjalanan.'],
        ['B2', 'Modul B2 · Upper Intermediate', 'Diskusi topik umum, menulis esai pendek, dan presentasi.'],
        ['C1', 'Modul C1 · Advanced', 'Bahasa akademik dan profesional, debat, serta laporan.'],
        ['C2', 'Modul C2 · Proficiency', 'Nuansa bahasa, sastra, dan komunikasi tingkat mahir.'],
        ['UMUM', 'Bahan ajar umum', 'Template RPP, lembar kerja, rubrik penilaian, dan referensi lintas level.'],
    ] as [$level, $name, $description]) {
        $insertFolder->execute(['name' => $name, 'level' => $level, 'description' => $description]);
    }
    $connection->exec("INSERT OR IGNORE INTO app_settings (setting_key, setting_value) VALUES ('module_folders_seeded', '1')");
}
