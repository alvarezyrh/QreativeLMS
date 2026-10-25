<?php

declare(strict_types=1);

require_once __DIR__ . '/app/bootstrap.php';
require_once __DIR__ . '/app/lms.php';

$user = require_auth(database());
$connection = database();
$canManageCourses = in_array($user['role'], ['admin', 'guru'], true);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_post_csrf();

    try {
        $action = post_value('action', 32);
        switch ($action) {
            case 'create_course':
                if (!$canManageCourses) {
                    http_response_code(403);
                    exit('Hanya guru dan admin yang dapat membuat kursus.');
                }
                [$title, $description, $level, $status, $price] = validate_course_fields();
                $teacherId = (int) $user['id'];
                if ($user['role'] === 'admin') {
                    $teacherId = post_id('teacher_id');
                    $teacher = $connection->prepare("SELECT id FROM users WHERE id = :id AND role = 'guru' AND status = 'active'");
                    $teacher->execute(['id' => $teacherId]);
                    if ($teacher->fetchColumn() === false) {
                        throw new InvalidArgumentException('Pilih guru aktif yang valid untuk kursus ini.');
                    }
                }
                $statement = $connection->prepare(
                    'INSERT INTO courses (teacher_id, title, description, level, price_idr, status) VALUES (:teacher_id, :title, :description, :level, :price_idr, :status)'
                );
                $statement->execute([
                    'teacher_id' => $teacherId,
                    'title' => $title,
                    'description' => $description,
                    'level' => $level,
                    'price_idr' => $price,
                    'status' => $status,
                ]);
                set_flash('Kursus berhasil dibuat. Tambahkan materi pertamanya dari daftar kursus.');
                break;

            case 'update_course':
                if (!$canManageCourses) {
                    http_response_code(403);
                    exit('Akses ditolak.');
                }
                $courseId = post_id('course_id');
                $course = course_for_editor($connection, $courseId, $user);
                [$title, $description, $level, $status, $price] = validate_course_fields();
                $teacherId = (int) $course['teacher_id'];
                if ($user['role'] === 'admin') {
                    $teacherId = post_id('teacher_id');
                    $teacher = $connection->prepare("SELECT id FROM users WHERE id = :id AND role = 'guru' AND status = 'active'");
                    $teacher->execute(['id' => $teacherId]);
                    if ($teacher->fetchColumn() === false) {
                        throw new InvalidArgumentException('Pilih guru aktif yang valid untuk kursus ini.');
                    }
                }
                $statement = $connection->prepare(
                    'UPDATE courses SET teacher_id = :teacher_id, title = :title, description = :description, level = :level, price_idr = :price_idr, status = :status, updated_at = CURRENT_TIMESTAMP WHERE id = :id'
                );
                $statement->execute([
                    'teacher_id' => $teacherId,
                    'title' => $title,
                    'description' => $description,
                    'level' => $level,
                    'price_idr' => $price,
                    'status' => $status,
                    'id' => $courseId,
                ]);
                set_flash('Perubahan kursus berhasil disimpan.');
                break;

            case 'delete_course':
                if (!$canManageCourses) {
                    http_response_code(403);
                    exit('Akses ditolak.');
                }
                $courseId = post_id('course_id');
                course_for_editor($connection, $courseId, $user);
                $statement = $connection->prepare('DELETE FROM courses WHERE id = :id');
                $statement->execute(['id' => $courseId]);
                set_flash('Kursus dan materi terkait berhasil dihapus.');
                break;

            case 'enroll':
                if ($user['role'] !== 'murid') {
                    http_response_code(403);
                    exit('Pendaftaran kursus hanya tersedia untuk murid.');
                }
                $courseId = post_id('course_id');
                $courseStatement = $connection->prepare("SELECT id, price_idr FROM courses WHERE id = :id AND status = 'published'");
                $courseStatement->execute(['id' => $courseId]);
                $courseToEnroll = $courseStatement->fetch();
                if ($courseToEnroll === false) {
                    throw new InvalidArgumentException('Kursus tidak tersedia.');
                }
                $alreadyEnrolled = $connection->prepare('SELECT 1 FROM enrollments WHERE course_id = :course_id AND student_id = :student_id');
                $alreadyEnrolled->execute(['course_id' => $courseId, 'student_id' => $user['id']]);
                if ($alreadyEnrolled->fetchColumn() !== false) {
                    throw new InvalidArgumentException('Kamu sudah terdaftar di kursus ini.');
                }
                if ((int) $courseToEnroll['price_idr'] > 0) {
                    require_once __DIR__ . '/app/settings.php';
                    $settings = app_settings($connection);
                    if (($settings['qris_image'] ?? '') === '') {
                        throw new InvalidArgumentException('Pembayaran belum tersedia. Admin perlu mengunggah QRIS resmi terlebih dahulu.');
                    }
                    $existingPayment = $connection->prepare(
                        "SELECT status FROM finance_transactions WHERE user_id = :user_id AND course_id = :course_id AND kind = 'income' AND status IN ('pending', 'paid') ORDER BY CASE status WHEN 'paid' THEN 0 ELSE 1 END LIMIT 1"
                    );
                    $existingPayment->execute(['user_id' => $user['id'], 'course_id' => $courseId]);
                    $existingPaymentStatus = $existingPayment->fetchColumn();
                    if ($existingPaymentStatus === 'paid') {
                        $statement = $connection->prepare('INSERT OR IGNORE INTO enrollments (course_id, student_id) VALUES (:course_id, :student_id)');
                        $statement->execute(['course_id' => $courseId, 'student_id' => $user['id']]);
                        set_flash('Pembayaran kursus ini sudah lunas. Pendaftaran belajar berhasil dipulihkan.');
                        redirect('/courses.php');
                    }
                    if ($existingPaymentStatus === 'pending') {
                        throw new InvalidArgumentException('Kamu sudah memiliki pembayaran untuk kursus ini. Periksa halaman keuangan.');
                    }
                    $transaction = $connection->prepare(
                        "INSERT INTO finance_transactions (kind, user_id, course_id, amount_idr, method, status, created_by)
                         VALUES ('income', :user_id, :course_id, :amount_idr, :method, 'pending', :created_by)"
                    );
                    $transaction->execute([
                        'user_id' => $user['id'],
                        'course_id' => $courseId,
                        'amount_idr' => $courseToEnroll['price_idr'],
                        'method' => 'qris_bca',
                        'created_by' => $user['id'],
                    ]);
                    set_flash('Permintaan pembayaran dibuat. Selesaikan pembayaran dan kirim referensi transaksi di halaman Keuangan.');
                    redirect('/finance.php');
                }
                $statement = $connection->prepare("INSERT OR IGNORE INTO enrollments (course_id, student_id) SELECT id, :student_id FROM courses WHERE id = :course_id AND status = 'published'");
                $statement->execute(['student_id' => $user['id'], 'course_id' => $courseId]);
                if ($statement->rowCount() === 0) {
                    throw new InvalidArgumentException('Pendaftaran gagal. Muat ulang halaman dan coba lagi.');
                }
                set_flash('Pendaftaran berhasil. Kamu sekarang dapat membuka kursus dan mengerjakan materinya.');
                break;

            case 'withdraw':
                if ($user['role'] !== 'murid') {
                    http_response_code(403);
                    exit('Akses ditolak.');
                }
                $courseId = post_id('course_id');
                $statement = $connection->prepare('DELETE FROM enrollments WHERE course_id = :course_id AND student_id = :student_id');
                $statement->execute(['course_id' => $courseId, 'student_id' => $user['id']]);
                set_flash($statement->rowCount() > 0 ? 'Pendaftaran kursus dibatalkan.' : 'Kamu belum terdaftar di kursus tersebut.');
                break;

            default:
                http_response_code(400);
                exit('Aksi tidak dikenal.');
        }

        redirect('/courses.php');
    } catch (InvalidArgumentException $exception) {
        set_flash($exception->getMessage(), 'error');
        redirect('/courses.php');
    }
}

if ($user['role'] === 'murid') {
    $statement = $connection->prepare(
        "SELECT c.*, u.name AS teacher_name, e.id AS enrollment_id,
            COUNT(DISTINCT l.id) AS lesson_count,
            COUNT(DISTINCT lp.id) AS completed_count
         FROM courses c
         LEFT JOIN users u ON u.id = c.teacher_id
         LEFT JOIN enrollments e ON e.course_id = c.id AND e.student_id = :student_id
         LEFT JOIN lessons l ON l.course_id = c.id
         LEFT JOIN lesson_progress lp ON lp.enrollment_id = e.id AND lp.lesson_id = l.id
         WHERE c.status = 'published'
         GROUP BY c.id
         ORDER BY CASE WHEN e.id IS NULL THEN 1 ELSE 0 END, c.title"
    );
    $statement->execute(['student_id' => $user['id']]);
} elseif ($user['role'] === 'guru') {
    $statement = $connection->prepare(
        'SELECT c.*, u.name AS teacher_name, NULL AS enrollment_id,
            COUNT(DISTINCT l.id) AS lesson_count,
            COUNT(DISTINCT e.id) AS student_count
         FROM courses c
         LEFT JOIN users u ON u.id = c.teacher_id
         LEFT JOIN lessons l ON l.course_id = c.id
         LEFT JOIN enrollments e ON e.course_id = c.id
         WHERE c.teacher_id = :teacher_id
         GROUP BY c.id ORDER BY c.updated_at DESC'
    );
    $statement->execute(['teacher_id' => $user['id']]);
} else {
    $statement = $connection->query(
        'SELECT c.*, u.name AS teacher_name, NULL AS enrollment_id,
            COUNT(DISTINCT l.id) AS lesson_count,
            COUNT(DISTINCT e.id) AS student_count
         FROM courses c
         LEFT JOIN users u ON u.id = c.teacher_id
         LEFT JOIN lessons l ON l.course_id = c.id
         LEFT JOIN enrollments e ON e.course_id = c.id
         GROUP BY c.id ORDER BY c.updated_at DESC'
    );
}
$courses = $statement->fetchAll();
$teachers = $user['role'] === 'admin'
    ? $connection->query("SELECT id, name FROM users WHERE role = 'guru' AND status = 'active' ORDER BY name")->fetchAll()
    : [];
$editingCourse = null;
if ($canManageCourses && isset($_GET['edit']) && ctype_digit((string) $_GET['edit'])) {
    $editingCourse = course_for_editor($connection, (int) $_GET['edit'], $user);
}
$flash = take_flash();
$csrfToken = csrf_token();
$pageTitle = 'Kursus';
$pageDescription = $user['role'] === 'murid'
    ? 'Jelajahi kursus, daftar, dan lanjutkan belajarmu.'
    : 'Kelola kursus dan materi belajar bahasa Inggris.';

require __DIR__ . '/views/courses.php';
