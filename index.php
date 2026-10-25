<?php

declare(strict_types=1);

require_once __DIR__ . '/app/bootstrap.php';
require_once __DIR__ . '/app/settings.php';
$currentUser = require_auth(database());
$platformName = app_settings(database())['platform_name'] ?? 'Qreative LMS';
$role = $currentUser['role'];

$dashboardRoles = require __DIR__ . '/app/data/dashboard.php';
$dashboardRole = $dashboardRoles[$role];
$dashboardRole['name'] = $currentUser['name'];
$nameInitials = [];
if (preg_match_all('/(?:^|\s)(\p{L})/u', $currentUser['name'], $nameMatches) !== false) {
    $nameInitials = array_slice($nameMatches[1], 0, 2);
}
$dashboardRole['initials'] = strtoupper(implode('', $nameInitials));
$dashboardRole['detail'] = $dashboardRole['label'] . ' · Akun aktif';
$dashboardRole['greeting'] = match ($role) {
    'admin' => 'Welcome back, ' . $currentUser['name'] . '! ☀️',
    'guru' => 'Good morning, ' . $currentUser['name'] . '! ☀️',
    default => 'Good morning, ' . $currentUser['name'] . '! ☀️',
};
$dashboardRole['nav'] = array_map(
    static function (array $item) use ($role): array {
        $item[2] ??= null;
        $item[3] = match ($item[0]) {
            'Dashboard' => '/',
            'Kursus saya', 'Kelas saya', 'Kursus' => '/courses.php',
            'Pengguna' => $role === 'admin' ? '/users.php' : '',
            'Murid' => $role === 'admin' ? '/users.php' : '',
            'Jadwal', 'Jadwal mengajar' => '/schedule.php',
            'Pesan' => '/messages.php',
            'Laporan' => $role === 'admin' ? '/reports.php' : '',
            'Keuangan' => in_array($role, ['admin', 'murid'], true) ? '/finance.php' : '',
            'Pengaturan' => $role === 'admin' ? '/settings.php' : '',
            default => '',
        };

        return array_values($item);
    },
    $dashboardRole['nav']
);
$unreadMessages = database()->prepare('SELECT COUNT(*) FROM messages WHERE recipient_id = :user_id AND read_at IS NULL');
$unreadMessages->execute(['user_id' => $currentUser['id']]);
$unreadMessageCount = (int) $unreadMessages->fetchColumn();
foreach ($dashboardRole['nav'] as &$navigationItem) {
    if ($navigationItem[0] === 'Pesan') {
        $navigationItem[2] = $unreadMessageCount > 0 ? (string) $unreadMessageCount : null;
    }
}
unset($navigationItem);

if ($role === 'murid') {
    $summary = database()->prepare(
        "SELECT COUNT(DISTINCT e.id) AS enrollment_count,
            COUNT(DISTINCT l.id) AS lesson_count,
            COUNT(DISTINCT lp.id) AS completed_count
         FROM enrollments e
         JOIN courses c ON c.id = e.course_id AND c.status = 'published'
         LEFT JOIN lessons l ON l.course_id = c.id
         LEFT JOIN lesson_progress lp ON lp.enrollment_id = e.id AND lp.lesson_id = l.id
         WHERE e.student_id = :student_id"
    );
    $summary->execute(['student_id' => $currentUser['id']]);
    $progressSummary = $summary->fetch();
    $dashboardRole['stats'][0][1] = (string) $progressSummary['enrollment_count'];
    $dashboardRole['stats'][1][1] = (string) $progressSummary['completed_count'];
    $dashboardRole['stats'][1][0] = 'Materi selesai';
    $dashboardRole['stats'][1][2] = 'Tersimpan di progres belajar';
    $dashboardRole['stats'][2][1] = $progressSummary['lesson_count'] > 0
        ? (string) (int) round($progressSummary['completed_count'] * 100 / $progressSummary['lesson_count']) . '%'
        : '0%';
    $dashboardRole['stats'][2][0] = 'Progres materi';
    $dashboardRole['stats'][2][2] = 'Dari kursus terdaftar';

    $studentCourses = database()->prepare(
        "SELECT c.id, c.title AS name, c.level, u.name AS teacher_name,
            COUNT(DISTINCT l.id) AS lesson_count,
            COUNT(DISTINCT lp.id) AS completed_count
         FROM enrollments e
         JOIN courses c ON c.id = e.course_id AND c.status = 'published'
         LEFT JOIN users u ON u.id = c.teacher_id
         LEFT JOIN lessons l ON l.course_id = c.id
         LEFT JOIN lesson_progress lp ON lp.enrollment_id = e.id AND lp.lesson_id = l.id
         WHERE e.student_id = :student_id
         GROUP BY c.id ORDER BY e.enrolled_at DESC LIMIT 6"
    );
    $studentCourses->execute(['student_id' => $currentUser['id']]);
    $dashboardRole['courses'] = array_map(
        static fn (array $course): array => [
            'id' => (int) $course['id'],
            'name' => $course['name'],
            'by' => 'Dengan ' . ($course['teacher_name'] ?? 'Guru Qreative'),
            'level' => $course['level'],
            'progress' => (int) $course['lesson_count'] > 0
                ? (int) round($course['completed_count'] * 100 / $course['lesson_count'])
                : 0,
            'cover' => 'mint',
            'symbol' => 'book',
            'lesson_count' => (int) $course['lesson_count'],
            'completed_count' => (int) $course['completed_count'],
        ],
        $studentCourses->fetchAll()
    );
    if ($dashboardRole['courses'] !== []) {
        $nextCourse = $dashboardRole['courses'][0];
        $dashboardRole['featured'] = [
            'course_id' => $nextCourse['id'],
            'tag' => 'LANJUTKAN BELAJAR',
            'title' => $nextCourse['name'],
            'description' => 'Kursus level ' . $nextCourse['level'] . ' · ' . $nextCourse['by'],
            'lesson' => $nextCourse['completed_count'] . ' dari ' . $nextCourse['lesson_count'] . ' materi selesai',
            'duration' => $nextCourse['lesson_count'] . ' materi',
            'progress' => $nextCourse['progress'] . '%',
            'action' => 'Lanjut belajar',
        ];
    } else {
        $dashboardRole['featured']['tag'] = 'MULAI BELAJAR';
        $dashboardRole['featured']['title'] = 'Temukan kursus pertamamu';
        $dashboardRole['featured']['description'] = 'Jelajahi katalog dan daftar ke kursus bahasa Inggris.';
        $dashboardRole['featured']['lesson'] = '3 kursus tersedia';
        $dashboardRole['featured']['duration'] = 'Belajar sesuai ritmemu';
        $dashboardRole['featured']['progress'] = '';
        $dashboardRole['featured']['action'] = 'Jelajahi kursus';
        $dashboardRole['featured']['course_id'] = null;
    }
} elseif ($role === 'guru') {
    $summary = database()->prepare(
        'SELECT COUNT(DISTINCT c.id) AS course_count, COUNT(DISTINCT e.student_id) AS student_count
         FROM courses c LEFT JOIN enrollments e ON e.course_id = c.id
         WHERE c.teacher_id = :teacher_id'
    );
    $summary->execute(['teacher_id' => $currentUser['id']]);
    $teacherSummary = $summary->fetch();
    $dashboardRole['stats'][0][1] = (string) $teacherSummary['course_count'];
    $dashboardRole['stats'][1][1] = (string) $teacherSummary['student_count'];
    $teacherCourses = database()->prepare(
        'SELECT c.id, c.title AS name, c.level, COUNT(DISTINCT l.id) AS lesson_count,
            COUNT(DISTINCT e.id) AS student_count, COUNT(DISTINCT lp.id) AS completed_count
         FROM courses c
         LEFT JOIN lessons l ON l.course_id = c.id
         LEFT JOIN enrollments e ON e.course_id = c.id
         LEFT JOIN lesson_progress lp ON lp.enrollment_id = e.id AND lp.lesson_id = l.id
         WHERE c.teacher_id = :teacher_id GROUP BY c.id ORDER BY c.updated_at DESC LIMIT 6'
    );
    $teacherCourses->execute(['teacher_id' => $currentUser['id']]);
    $dashboardRole['courses'] = array_map(
        static fn (array $course): array => [
            'id' => (int) $course['id'],
            'name' => $course['name'],
            'by' => $course['student_count'] . ' murid · ' . $course['lesson_count'] . ' materi',
            'level' => $course['level'],
            'progress' => (int) $course['lesson_count'] > 0 && (int) $course['student_count'] > 0
                ? (int) round($course['completed_count'] * 100 / ($course['lesson_count'] * $course['student_count']))
                : 0,
            'cover' => 'mint',
            'symbol' => 'book',
        ],
        $teacherCourses->fetchAll()
    );
} else {
    $dashboardRole['stats'][0][1] = (string) database()->query("SELECT COUNT(*) FROM users WHERE status = 'active'")->fetchColumn();
    $dashboardRole['stats'][0][2] = 'Akun aktif';
    $dashboardRole['stats'][1][1] = (string) database()->query("SELECT COUNT(*) FROM courses WHERE status = 'published'")->fetchColumn();
    $dashboardRole['stats'][1][0] = 'Kursus terbit';
    $dashboardRole['stats'][2][1] = (string) database()->query("SELECT COUNT(*) FROM courses")->fetchColumn();
    $dashboardRole['stats'][2][0] = 'Total kursus';
    $dashboardRole['stats'][2][2] = 'Kursus tersimpan';
    $financeSummaryQuery = database()->prepare(
        "SELECT
            COALESCE(SUM(CASE WHEN kind = 'income' AND status = 'paid' AND substr(paid_at, 1, 7) = :period THEN amount_idr ELSE 0 END), 0) AS income,
            SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) AS pending
         FROM finance_transactions"
    );
    $financeSummaryQuery->execute(['period' => date('Y-m')]);
    $financeSummary = $financeSummaryQuery->fetch();
    $dashboardRole['stats'][3][1] = 'Rp ' . number_format((int) $financeSummary['income'], 0, ',', '.');
    $dashboardRole['stats'][3][0] = 'Pemasukan bulan ini';
    $dashboardRole['stats'][3][2] = (int) $financeSummary['pending'] . ' menunggu verifikasi';
    $adminCourses = database()->query(
        "SELECT c.id, c.title AS name, c.level, u.name AS teacher_name,
            COUNT(DISTINCT e.id) AS student_count, COUNT(DISTINCT l.id) AS lesson_count,
            COUNT(DISTINCT lp.id) AS completed_count
         FROM courses c
         LEFT JOIN users u ON u.id = c.teacher_id
         LEFT JOIN enrollments e ON e.course_id = c.id
         LEFT JOIN lessons l ON l.course_id = c.id
         LEFT JOIN lesson_progress lp ON lp.enrollment_id = e.id AND lp.lesson_id = l.id
         WHERE c.status = 'published'
         GROUP BY c.id ORDER BY student_count DESC, c.title LIMIT 6"
    );
    $dashboardRole['courses'] = array_map(
        static fn (array $course): array => [
            'id' => (int) $course['id'],
            'name' => $course['name'],
            'by' => ($course['teacher_name'] ?? 'Guru Qreative') . ' · ' . $course['student_count'] . ' murid',
            'level' => $course['level'],
            'progress' => (int) $course['lesson_count'] > 0 && (int) $course['student_count'] > 0
                ? (int) round($course['completed_count'] * 100 / ($course['lesson_count'] * $course['student_count']))
                : 0,
            'cover' => 'mint',
            'symbol' => 'book',
        ],
        $adminCourses->fetchAll()
    );
}

$scheduleQuery = match ($role) {
    'guru' => database()->prepare(
        "SELECT s.starts_at, s.title, c.title AS course_title, s.location
         FROM class_schedules s JOIN courses c ON c.id = s.course_id
         WHERE c.teacher_id = :user_id AND s.starts_at >= :now
         ORDER BY s.starts_at LIMIT 3"
    ),
    'murid' => database()->prepare(
        "SELECT s.starts_at, s.title, c.title AS course_title, s.location
         FROM class_schedules s
         JOIN courses c ON c.id = s.course_id AND c.status = 'published'
         JOIN enrollments e ON e.course_id = c.id AND e.student_id = :user_id
         WHERE s.starts_at >= :now
         ORDER BY s.starts_at LIMIT 3"
    ),
    default => database()->prepare(
        "SELECT s.starts_at, s.title, c.title AS course_title, s.location
         FROM class_schedules s JOIN courses c ON c.id = s.course_id
         WHERE s.starts_at >= :now
         ORDER BY s.starts_at LIMIT 3"
    ),
};
$scheduleParameters = ['now' => date('Y-m-d H:i:s')];
if ($role !== 'admin') {
    $scheduleParameters['user_id'] = $currentUser['id'];
}
$scheduleQuery->execute($scheduleParameters);
$upcomingSchedules = $scheduleQuery->fetchAll();
$dashboardRole['schedule'] = array_map(
    static fn (array $schedule): array => [
        date('d/m H:i', strtotime($schedule['starts_at'])),
        $schedule['title'],
        $schedule['course_title'] . ($schedule['location'] !== '' ? ' · ' . $schedule['location'] : ''),
    ],
    $upcomingSchedules
);
$dashboardRole['scheduleTitle'] = match ($role) {
    'murid' => 'Jadwal kelas saya',
    'guru' => 'Jadwal mengajar',
    default => 'Jadwal kelas mendatang',
};
$csrfToken = csrf_token();

require __DIR__ . '/views/dashboard.php';
