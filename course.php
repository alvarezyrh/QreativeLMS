<?php

declare(strict_types=1);

require_once __DIR__ . '/app/bootstrap.php';
require_once __DIR__ . '/app/lms.php';

$user = require_auth(database());
$connection = database();
$courseId = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
if ($courseId === false || $courseId === null) {
    http_response_code(400);
    exit('ID kursus tidak valid.');
}

$courseStatement = $connection->prepare(
    'SELECT c.*, u.name AS teacher_name FROM courses c LEFT JOIN users u ON u.id = c.teacher_id WHERE c.id = :id'
);
$courseStatement->execute(['id' => $courseId]);
$course = $courseStatement->fetch();
if ($course === false) {
    http_response_code(404);
    exit('Kursus tidak ditemukan.');
}

$isManager = can_manage_course($user, $course);
$enrollmentId = null;
if ($user['role'] === 'murid') {
    $enrollment = $connection->prepare('SELECT id FROM enrollments WHERE course_id = :course_id AND student_id = :student_id');
    $enrollment->execute(['course_id' => $courseId, 'student_id' => $user['id']]);
    $enrollmentId = $enrollment->fetchColumn();
    if ($enrollmentId === false || $course['status'] !== 'published') {
        redirect('/courses.php');
    }
}
if (!$isManager && $user['role'] !== 'murid') {
    http_response_code(403);
    exit('Akses materi kursus ditolak.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_post_csrf();

    try {
        $action = post_value('action', 32);
        if (in_array($action, ['create_lesson', 'update_lesson', 'delete_lesson'], true) && !$isManager) {
            http_response_code(403);
            exit('Hanya guru pengampu atau admin yang dapat mengelola materi.');
        }
        if ($action === 'complete_lesson' && $user['role'] !== 'murid') {
            http_response_code(403);
            exit('Akses ditolak.');
        }

        if ($action === 'create_lesson') {
            $title = post_value('title', 120);
            $content = post_value('content', 10000);
            if ($title === '') {
                throw new InvalidArgumentException('Judul materi wajib diisi.');
            }
            $position = $connection->prepare('SELECT COALESCE(MAX(position), 0) + 1 FROM lessons WHERE course_id = :course_id');
            $position->execute(['course_id' => $courseId]);
            $insert = $connection->prepare('INSERT INTO lessons (course_id, title, content, position) VALUES (:course_id, :title, :content, :position)');
            $insert->execute(['course_id' => $courseId, 'title' => $title, 'content' => $content, 'position' => $position->fetchColumn()]);
            set_flash('Materi berhasil ditambahkan.');
        } elseif ($action === 'update_lesson') {
            $lessonId = post_id('lesson_id');
            $title = post_value('title', 120);
            $content = post_value('content', 10000);
            if ($title === '') {
                throw new InvalidArgumentException('Judul materi wajib diisi.');
            }
            $update = $connection->prepare('UPDATE lessons SET title = :title, content = :content, updated_at = CURRENT_TIMESTAMP WHERE id = :id AND course_id = :course_id');
            $update->execute(['title' => $title, 'content' => $content, 'id' => $lessonId, 'course_id' => $courseId]);
            if ($update->rowCount() === 0) {
                $exists = $connection->prepare('SELECT 1 FROM lessons WHERE id = :id AND course_id = :course_id');
                $exists->execute(['id' => $lessonId, 'course_id' => $courseId]);
                if ($exists->fetchColumn() === false) {
                    throw new InvalidArgumentException('Materi tidak ditemukan pada kursus ini.');
                }
            }
            set_flash('Perubahan materi berhasil disimpan.');
        } elseif ($action === 'delete_lesson') {
            $lessonId = post_id('lesson_id');
            $delete = $connection->prepare('DELETE FROM lessons WHERE id = :id AND course_id = :course_id');
            $delete->execute(['id' => $lessonId, 'course_id' => $courseId]);
            if ($delete->rowCount() === 0) {
                throw new InvalidArgumentException('Materi tidak ditemukan pada kursus ini.');
            }
            set_flash('Materi dan progres terkait berhasil dihapus.');
        } elseif ($action === 'complete_lesson') {
            $lessonId = post_id('lesson_id');
            $saveProgress = $connection->prepare(
                'INSERT OR IGNORE INTO lesson_progress (enrollment_id, lesson_id)
                 SELECT :enrollment_id, l.id FROM lessons l
                 WHERE l.id = :lesson_id AND l.course_id = :course_id'
            );
            $saveProgress->execute([
                'enrollment_id' => $enrollmentId,
                'lesson_id' => $lessonId,
                'course_id' => $courseId,
            ]);
            if ($saveProgress->rowCount() === 0) {
                throw new InvalidArgumentException('Materi sudah selesai atau tidak ditemukan.');
            }
            set_flash('Progres belajar tersimpan. Bagus, terus lanjutkan!');
        } else {
            http_response_code(400);
            exit('Aksi tidak dikenal.');
        }

        redirect('/course.php?id=' . $courseId);
    } catch (InvalidArgumentException $exception) {
        set_flash($exception->getMessage(), 'error');
        redirect('/course.php?id=' . $courseId);
    }
}

$lessonStatement = $connection->prepare(
    'SELECT l.*, CASE WHEN lp.id IS NULL THEN 0 ELSE 1 END AS is_completed
     FROM lessons l
     LEFT JOIN lesson_progress lp ON lp.lesson_id = l.id AND lp.enrollment_id = :enrollment_id
     WHERE l.course_id = :course_id
     ORDER BY l.position'
);
$lessonStatement->execute(['enrollment_id' => $enrollmentId ?: 0, 'course_id' => $courseId]);
$lessons = $lessonStatement->fetchAll();
$completedLessons = count(array_filter($lessons, static fn (array $lesson): bool => (int) $lesson['is_completed'] === 1));
$progress = count($lessons) > 0 ? (int) round($completedLessons * 100 / count($lessons)) : 0;
$flash = take_flash();
$csrfToken = csrf_token();
$pageTitle = $course['title'];
$pageDescription = $course['description'];

require __DIR__ . '/views/course.php';
