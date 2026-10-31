<?php

declare(strict_types=1);

require_once __DIR__ . '/app/bootstrap.php';
require_once __DIR__ . '/app/layout.php';

$connection = database();
$user = require_auth($connection);
$role = $user['role'];
$userId = (int) $user['id'];
$now = date('Y-m-d H:i:s');

$count = static function (string $sql, array $parameters = []) use ($connection): int {
    $statement = $connection->prepare($sql);
    $statement->execute($parameters);

    return (int) $statement->fetchColumn();
};
$rupiah = static fn (int $amount): string => 'Rp ' . number_format($amount, 0, ',', '.');
$shortDate = static fn (string $value): string => indonesian_date(strtotime($value), false, true) . ', ' . date('H:i', strtotime($value));

$hour = (int) date('G');
$firstName = explode(' ', trim($user['name']))[0];
$greeting = match (true) {
    $hour < 11 => 'Selamat pagi',
    $hour < 15 => 'Selamat siang',
    $hour < 18 => 'Selamat sore',
    default => 'Selamat malam',
} . ', ' . $firstName . '!';

// Kartu kursus memakai variasi warna yang sama untuk semua role.
$courseCovers = [['mint', 'book'], ['blue', 'message'], ['gold', 'people']];
$decorateCourses = static function (array $courses) use ($courseCovers): array {
    foreach ($courses as $index => &$course) {
        [$course['cover'], $course['symbol']] = $courseCovers[$index % count($courseCovers)];
    }

    return $courses;
};
$progressPercent = static fn (int $done, int $total): int => $total > 0 ? (int) round($done * 100 / $total) : 0;

$scheduleScope = match ($role) {
    'guru' => ['JOIN courses c ON c.id = s.course_id AND c.teacher_id = :user_id', ['user_id' => $userId]],
    'murid' => [
        "JOIN courses c ON c.id = s.course_id AND c.status = 'published'
         JOIN enrollments e ON e.course_id = c.id AND e.student_id = :user_id",
        ['user_id' => $userId],
    ],
    default => ['JOIN courses c ON c.id = s.course_id', []],
};
$scheduleStatement = $connection->prepare(
    "SELECT s.starts_at, s.title, s.duration_minutes, s.location, c.title AS course_title
     FROM class_schedules s {$scheduleScope[0]}
     WHERE s.starts_at >= :now ORDER BY s.starts_at LIMIT 3"
);
$scheduleStatement->execute($scheduleScope[1] + ['now' => $now]);
$schedule = $scheduleStatement->fetchAll();
$upcomingCount = $count(
    "SELECT COUNT(*) FROM class_schedules s {$scheduleScope[0]} WHERE s.starts_at >= :now",
    $scheduleScope[1] + ['now' => $now]
);

if ($role === 'murid') {
    $summaryStatement = $connection->prepare(
        "SELECT COUNT(DISTINCT e.id) AS enrollment_count, COUNT(DISTINCT l.id) AS lesson_count, COUNT(DISTINCT lp.id) AS completed_count
         FROM enrollments e
         JOIN courses c ON c.id = e.course_id AND c.status = 'published'
         LEFT JOIN lessons l ON l.course_id = c.id
         LEFT JOIN lesson_progress lp ON lp.enrollment_id = e.id AND lp.lesson_id = l.id
         WHERE e.student_id = :student_id"
    );
    $summaryStatement->execute(['student_id' => $userId]);
    $summary = $summaryStatement->fetch();
    $pendingPayments = $count(
        "SELECT COUNT(*) FROM finance_transactions WHERE user_id = :user_id AND kind = 'income' AND status = 'pending'",
        ['user_id' => $userId]
    );
    $stats = [
        ['Kursus terdaftar', (string) $summary['enrollment_count'], 'Kursus yang kamu ikuti', 'book'],
        ['Materi selesai', (string) $summary['completed_count'], 'Tersimpan di progres', 'check'],
        ['Progres materi', $progressPercent((int) $summary['completed_count'], (int) $summary['lesson_count']) . '%', 'Dari semua kursusmu', 'chart'],
        ['Pembayaran tertunda', (string) $pendingPayments, $pendingPayments > 0 ? 'Menunggu verifikasi' : 'Tidak ada tagihan', 'wallet'],
    ];

    $coursesStatement = $connection->prepare(
        "SELECT c.id, c.title, c.level, u.name AS teacher_name,
            COUNT(DISTINCT l.id) AS lesson_count, COUNT(DISTINCT lp.id) AS completed_count
         FROM enrollments e
         JOIN courses c ON c.id = e.course_id AND c.status = 'published'
         LEFT JOIN users u ON u.id = c.teacher_id
         LEFT JOIN lessons l ON l.course_id = c.id
         LEFT JOIN lesson_progress lp ON lp.enrollment_id = e.id AND lp.lesson_id = l.id
         WHERE e.student_id = :student_id
         GROUP BY c.id ORDER BY e.enrolled_at DESC LIMIT 6"
    );
    $coursesStatement->execute(['student_id' => $userId]);
    $courses = $decorateCourses(array_map(static fn (array $course): array => [
        'id' => (int) $course['id'],
        'name' => $course['title'],
        'by' => 'Dengan ' . ($course['teacher_name'] ?? 'Guru Qreative'),
        'level' => $course['level'],
        'progress' => $progressPercent((int) $course['completed_count'], (int) $course['lesson_count']),
        'lesson_count' => (int) $course['lesson_count'],
        'completed_count' => (int) $course['completed_count'],
    ], $coursesStatement->fetchAll()));

    if ($courses !== []) {
        $next = $courses[0];
        $featured = [
            'tag' => 'Lanjutkan belajar',
            'title' => $next['name'],
            'description' => 'Kursus level ' . $next['level'] . ' · ' . $next['by'],
            'meta' => [$next['completed_count'] . ' dari ' . $next['lesson_count'] . ' materi selesai'],
            'progress' => $next['progress'],
            'action' => 'Lanjut belajar',
            'href' => '/course.php?id=' . $next['id'],
            'note' => $next['progress'] . '% selesai',
        ];
    } else {
        $publishedCount = $count("SELECT COUNT(*) FROM courses WHERE status = 'published'");
        $featured = [
            'tag' => 'Mulai belajar',
            'title' => 'Temukan kursus pertamamu',
            'description' => 'Jelajahi katalog dan daftar ke kursus bahasa Inggris.',
            'meta' => [$publishedCount . ' kursus tersedia'],
            'progress' => null,
            'action' => 'Jelajahi kursus',
            'href' => '/courses.php',
            'note' => 'Ayo mulai',
        ];
    }

    $activityStatement = $connection->prepare(
        "SELECT l.title, c.id AS course_id, c.title AS course_title, lp.completed_at
         FROM lesson_progress lp
         JOIN enrollments e ON e.id = lp.enrollment_id AND e.student_id = :student_id
         JOIN lessons l ON l.id = lp.lesson_id
         JOIN courses c ON c.id = l.course_id
         ORDER BY lp.completed_at DESC LIMIT 3"
    );
    $activityStatement->execute(['student_id' => $userId]);
    $activities = array_map(static fn (array $row): array => [
        'icon' => 'check',
        'title' => $row['title'],
        'meta' => $row['course_title'] . ' · ' . $shortDate($row['completed_at']),
        'badge' => 'Selesai',
        'href' => '/course.php?id=' . $row['course_id'],
    ], $activityStatement->fetchAll());

    $subtitle = 'Sedikit demi sedikit, kemampuan bahasa Inggrismu makin berkembang.';
    $courseTitle = 'Lanjutkan belajarmu';
    $activityTitle = 'Materi terakhir diselesaikan';
    $activityLink = ['/courses.php', 'Lihat kursus'];
    $activityEmpty = 'Belum ada materi yang diselesaikan.';
    $courseEmpty = 'Kamu belum terdaftar di kursus. Buka menu Kursus saya untuk mendaftar.';
} elseif ($role === 'guru') {
    $courseCount = $count('SELECT COUNT(*) FROM courses WHERE teacher_id = :id', ['id' => $userId]);
    $studentCount = $count(
        'SELECT COUNT(DISTINCT e.student_id) FROM enrollments e JOIN courses c ON c.id = e.course_id WHERE c.teacher_id = :id',
        ['id' => $userId]
    );
    $lessonCount = $count(
        'SELECT COUNT(*) FROM lessons l JOIN courses c ON c.id = l.course_id WHERE c.teacher_id = :id',
        ['id' => $userId]
    );
    $stats = [
        ['Kursus diajar', (string) $courseCount, 'Kursus milikmu', 'book'],
        ['Total murid', (string) $studentCount, 'Murid terdaftar', 'people'],
        ['Total materi', (string) $lessonCount, 'Materi tersimpan', 'check'],
        ['Jadwal mendatang', (string) $upcomingCount, 'Sesi kelas berikutnya', 'calendar'],
    ];

    $coursesStatement = $connection->prepare(
        'SELECT c.id, c.title, c.level, COUNT(DISTINCT l.id) AS lesson_count,
            COUNT(DISTINCT e.id) AS student_count, COUNT(DISTINCT lp.id) AS completed_count
         FROM courses c
         LEFT JOIN lessons l ON l.course_id = c.id
         LEFT JOIN enrollments e ON e.course_id = c.id
         LEFT JOIN lesson_progress lp ON lp.enrollment_id = e.id AND lp.lesson_id = l.id
         WHERE c.teacher_id = :teacher_id GROUP BY c.id ORDER BY c.updated_at DESC LIMIT 6'
    );
    $coursesStatement->execute(['teacher_id' => $userId]);
    $courses = $decorateCourses(array_map(static fn (array $course): array => [
        'id' => (int) $course['id'],
        'name' => $course['title'],
        'by' => $course['student_count'] . ' murid · ' . $course['lesson_count'] . ' materi',
        'level' => $course['level'],
        'progress' => $progressPercent((int) $course['completed_count'], (int) $course['lesson_count'] * (int) $course['student_count']),
    ], $coursesStatement->fetchAll()));

    if ($schedule !== []) {
        $nextClass = $schedule[0];
        $featured = [
            'tag' => 'Kelas berikutnya',
            'title' => $nextClass['title'],
            'description' => $nextClass['course_title'] . ($nextClass['location'] !== '' ? ' · ' . $nextClass['location'] : ''),
            'meta' => [indonesian_date(strtotime($nextClass['starts_at']), true) . ' · ' . date('H:i', strtotime($nextClass['starts_at'])) . ' WIB', $nextClass['duration_minutes'] . ' menit'],
            'progress' => null,
            'action' => 'Lihat jadwal',
            'href' => '/schedule.php',
            'note' => 'Kelas siap',
        ];
    } elseif ($courses !== []) {
        $featured = [
            'tag' => 'Kelola kelas',
            'title' => $courses[0]['name'],
            'description' => 'Kursus level ' . $courses[0]['level'] . ' · ' . $courses[0]['by'],
            'meta' => ['Belum ada jadwal kelas mendatang'],
            'progress' => null,
            'action' => 'Buka kursus',
            'href' => '/course.php?id=' . $courses[0]['id'],
            'note' => 'Kelas siap',
        ];
    } else {
        $featured = [
            'tag' => 'Mulai mengajar',
            'title' => 'Buat kursus pertamamu',
            'description' => 'Susun materi dan jadwal kelas untuk murid-muridmu.',
            'meta' => ['Belum ada kursus'],
            'progress' => null,
            'action' => 'Buat kursus',
            'href' => '/courses.php',
            'note' => 'Ayo mulai',
        ];
    }

    $activityStatement = $connection->prepare(
        'SELECT u.name, c.id AS course_id, c.title AS course_title, e.enrolled_at
         FROM enrollments e
         JOIN courses c ON c.id = e.course_id AND c.teacher_id = :teacher_id
         JOIN users u ON u.id = e.student_id
         ORDER BY e.enrolled_at DESC LIMIT 3'
    );
    $activityStatement->execute(['teacher_id' => $userId]);
    $activities = array_map(static fn (array $row): array => [
        'icon' => 'people',
        'title' => $row['name'] . ' mendaftar',
        'meta' => $row['course_title'] . ' · ' . $shortDate($row['enrolled_at']),
        'badge' => 'Murid baru',
        'href' => '/course.php?id=' . $row['course_id'],
    ], $activityStatement->fetchAll());

    $subtitle = 'Siap menginspirasi murid-muridmu belajar bahasa Inggris hari ini?';
    $courseTitle = 'Kelas yang kamu ajar';
    $activityTitle = 'Pendaftaran terbaru';
    $activityLink = ['/courses.php', 'Lihat kelas'];
    $activityEmpty = 'Belum ada murid yang mendaftar di kelasmu.';
    $courseEmpty = 'Belum ada kursus. Buka menu Kelas saya untuk membuat kursus.';
} else {
    $activeUsers = $count("SELECT COUNT(*) FROM users WHERE status = 'active'");
    $activeStudents = $count("SELECT COUNT(*) FROM users WHERE status = 'active' AND role = 'murid'");
    $activeTeachers = $count("SELECT COUNT(*) FROM users WHERE status = 'active' AND role = 'guru'");
    $publishedCount = $count("SELECT COUNT(*) FROM courses WHERE status = 'published'");
    $totalCourses = $count('SELECT COUNT(*) FROM courses');
    $enrollmentCount = $count('SELECT COUNT(*) FROM enrollments');
    $pendingCount = $count("SELECT COUNT(*) FROM finance_transactions WHERE status = 'pending'");
    $monthIncome = $count(
        "SELECT COALESCE(SUM(amount_idr), 0) FROM finance_transactions WHERE kind = 'income' AND status = 'paid' AND substr(paid_at, 1, 7) = :period",
        ['period' => date('Y-m')]
    );
    $stats = [
        ['Pengguna aktif', (string) $activeUsers, $activeStudents . ' murid · ' . $activeTeachers . ' guru', 'people'],
        ['Kursus terbit', (string) $publishedCount, 'Dari ' . $totalCourses . ' kursus', 'book'],
        ['Jadwal mendatang', (string) $upcomingCount, 'Sesi kelas berikutnya', 'calendar'],
        ['Pemasukan bulan ini', $rupiah($monthIncome), $pendingCount . ' menunggu verifikasi', 'wallet'],
    ];

    $coursesStatement = $connection->query(
        "SELECT c.id, c.title, c.level, u.name AS teacher_name,
            COUNT(DISTINCT e.id) AS student_count, COUNT(DISTINCT l.id) AS lesson_count, COUNT(DISTINCT lp.id) AS completed_count
         FROM courses c
         LEFT JOIN users u ON u.id = c.teacher_id
         LEFT JOIN enrollments e ON e.course_id = c.id
         LEFT JOIN lessons l ON l.course_id = c.id
         LEFT JOIN lesson_progress lp ON lp.enrollment_id = e.id AND lp.lesson_id = l.id
         WHERE c.status = 'published'
         GROUP BY c.id ORDER BY student_count DESC, c.title LIMIT 6"
    );
    $courses = $decorateCourses(array_map(static fn (array $course): array => [
        'id' => (int) $course['id'],
        'name' => $course['title'],
        'by' => ($course['teacher_name'] ?? 'Guru Qreative') . ' · ' . $course['student_count'] . ' murid',
        'level' => $course['level'],
        'progress' => $progressPercent((int) $course['completed_count'], (int) $course['lesson_count'] * (int) $course['student_count']),
    ], $coursesStatement->fetchAll()));

    $featured = [
        'tag' => 'Ringkasan platform',
        'title' => 'Pantau perkembangan belajar di satu tempat.',
        'description' => $activeUsers . ' pengguna aktif · ' . $publishedCount . ' kursus terbit',
        'meta' => [$enrollmentCount . ' pendaftaran kursus', $pendingCount . ' pembayaran menunggu'],
        'progress' => null,
        'action' => 'Lihat laporan',
        'href' => '/reports.php',
        'note' => 'Platform aktif',
    ];

    $activities = array_map(static fn (array $row): array => [
        'icon' => 'wallet',
        'title' => ($row['user_name'] ?? 'Pengguna') . ' · ' . $rupiah((int) $row['amount_idr']),
        'meta' => ($row['course_title'] ?? 'Tanpa kursus') . ' · ' . $shortDate($row['created_at']),
        'badge' => 'Tinjau',
        'href' => '/finance.php',
    ], $connection->query(
        "SELECT t.amount_idr, t.created_at, u.name AS user_name, c.title AS course_title
         FROM finance_transactions t
         LEFT JOIN users u ON u.id = t.user_id
         LEFT JOIN courses c ON c.id = t.course_id
         WHERE t.status = 'pending' ORDER BY t.created_at DESC LIMIT 3"
    )->fetchAll());

    $subtitle = 'Pantau aktivitas belajar dan kelola platform ' . (app_settings($connection)['platform_name'] ?? 'Qreative LMS') . '.';
    $courseTitle = 'Kursus populer';
    $activityTitle = 'Pembayaran menunggu verifikasi';
    $activityLink = ['/finance.php', 'Buka keuangan'];
    $activityEmpty = 'Tidak ada pembayaran yang menunggu verifikasi.';
    $courseEmpty = 'Belum ada kursus terbit. Buka menu Kursus untuk mulai mengelola materi.';
}

$messagesStatement = $connection->prepare(
    'SELECT m.conversation_id, m.body, m.created_at, m.read_at, u.name AS sender_name
     FROM messages m JOIN users u ON u.id = m.sender_id
     WHERE m.recipient_id = :user_id
     ORDER BY m.created_at DESC LIMIT 3'
);
$messagesStatement->execute(['user_id' => $userId]);
$recentMessages = $messagesStatement->fetchAll();

$scheduleTitle = match ($role) {
    'murid' => 'Jadwal kelas saya',
    'guru' => 'Jadwal mengajar',
    default => 'Jadwal kelas mendatang',
};
$pageTitle = 'Dashboard';
$csrfToken = csrf_token();

require __DIR__ . '/views/dashboard.php';
