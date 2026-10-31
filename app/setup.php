<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once __DIR__ . '/database.php';

$schema = file_get_contents(dirname(__DIR__) . '/database/schema.sql');
if ($schema === false) {
    throw new RuntimeException('File schema database tidak dapat dibaca.');
}

$connection = database();
$connection->exec($schema);
upgrade_schema($connection);

$courseColumns = array_column($connection->query('PRAGMA table_info(courses)')->fetchAll(), 'name');
if (!in_array('price_idr', $courseColumns, true)) {
    $connection->exec('ALTER TABLE courses ADD COLUMN price_idr INTEGER NOT NULL DEFAULT 0 CHECK (price_idr >= 0)');
}

$defaultSettings = [
    'platform_name' => 'Qreative LMS',
    'support_email' => '',
    'qris_instructions' => 'Pindai QRIS BCA, lalu kirim nomor referensi pembayaran atau nama pengirim. Admin akan memverifikasi pembayaran secara manual.',
    'qris_image' => '',
];
$saveSetting = $connection->prepare('INSERT OR IGNORE INTO app_settings (setting_key, setting_value) VALUES (:key, :value)');
foreach ($defaultSettings as $key => $value) {
    $saveSetting->execute(['key' => $key, 'value' => $value]);
}
$connection->prepare(
    "UPDATE app_settings SET setting_value = :value, updated_at = CURRENT_TIMESTAMP
     WHERE setting_key = 'qris_instructions'
       AND setting_value = 'Pindai QRIS BCA, lalu unggah bukti pembayaran. Admin akan memverifikasi pembayaran secara manual.'"
)->execute(['value' => $defaultSettings['qris_instructions']]);

$demoUsers = [
    ['Alex Morgan', 'admin@qreative.test', 'admin'],
    ['Emma Wilson', 'guru@qreative.test', 'guru'],
    ['Sarah Anderson', 'murid@qreative.test', 'murid'],
];

$insertUser = $connection->prepare(
    'INSERT OR IGNORE INTO users (name, email, password_hash, role) VALUES (:name, :email, :password_hash, :role)'
);

foreach ($demoUsers as [$name, $email, $role]) {
    $insertUser->execute([
        'name' => $name,
        'email' => $email,
        'password_hash' => password_hash('Qreative123!', PASSWORD_DEFAULT),
        'role' => $role,
    ]);
}

$seededCourses = (int) $connection->query('SELECT COUNT(*) FROM courses')->fetchColumn() === 0;
if ($seededCourses) {
    $teacherId = (int) $connection->query("SELECT id FROM users WHERE email = 'guru@qreative.test'")->fetchColumn();
    $sampleCourses = [
        ['Everyday English', 'Bangun kepercayaan diri menggunakan bahasa Inggris sehari-hari.', 'B1'],
        ['Grammar in Context', 'Pelajari tata bahasa melalui contoh dan latihan kontekstual.', 'A2'],
        ['Speak with Confidence', 'Latihan berbicara untuk berbagai situasi sehari-hari.', 'B1'],
    ];
    $insertCourse = $connection->prepare(
        'INSERT INTO courses (teacher_id, title, description, level, price_idr, status) VALUES (:teacher_id, :title, :description, :level, :price_idr, :status)'
    );
    $insertLesson = $connection->prepare(
        'INSERT INTO lessons (course_id, title, content, position) VALUES (:course_id, :title, :content, :position)'
    );

    foreach ($sampleCourses as [$title, $description, $level]) {
        $insertCourse->execute([
            'teacher_id' => $teacherId,
            'title' => $title,
            'description' => $description,
            'level' => $level,
            'price_idr' => match ($title) {
                'Everyday English' => 250000,
                'Grammar in Context' => 180000,
                default => 200000,
            },
            'status' => 'published',
        ]);
        $courseId = (int) $connection->lastInsertId();
        foreach (['Getting started', 'Practice and vocabulary', 'Review and speaking'] as $position => $lessonTitle) {
            $insertLesson->execute([
                'course_id' => $courseId,
                'title' => $lessonTitle,
                'content' => 'Latihan bahasa Inggris untuk ' . strtolower($lessonTitle) . ' pada kursus ' . $title . '.',
                'position' => $position + 1,
            ]);
        }
    }
}

$seedSamplePrices = $connection->prepare(
    'UPDATE courses SET price_idr = :price WHERE title = :title AND price_idr = 0 AND status = :status'
);
foreach ([
    'Everyday English' => 250000,
    'Grammar in Context' => 180000,
    'Speak with Confidence' => 200000,
] as $title => $price) {
    $seedSamplePrices->execute(['price' => $price, 'title' => $title, 'status' => 'published']);
}

$databasePath = getenv('QREATIVE_DB_PATH') ?: dirname(__DIR__) . '/storage/qreative.sqlite';
if (!chmod($databasePath, 0600)) {
    throw new RuntimeException('Izin akses file database tidak dapat dibatasi.');
}

fwrite(STDOUT, "Database siap. Akun demo dibuat jika email belum terdaftar.\n");
