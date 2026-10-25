<?php

declare(strict_types=1);

require_once __DIR__ . '/app/bootstrap.php';
require_once __DIR__ . '/app/lms.php';

$user = require_auth(database());
$connection = database();
$canManage = in_array($user['role'], ['admin', 'guru'], true);

$loadManageableCourse = static function (int $courseId) use ($connection, $user): array {
    $sql = "SELECT id, title FROM courses WHERE id = :id AND status = 'published'";
    $parameters = ['id' => $courseId];
    if ($user['role'] === 'guru') {
        $sql .= ' AND teacher_id = :teacher_id';
        $parameters['teacher_id'] = $user['id'];
    }
    $statement = $connection->prepare($sql);
    $statement->execute($parameters);
    $course = $statement->fetch();
    if ($course === false) {
        throw new InvalidArgumentException('Pilih kursus terbit yang dapat kamu kelola.');
    }

    return $course;
};

$loadManagedSchedule = static function (int $scheduleId) use ($connection, $user): array {
    $sql = 'SELECT s.* FROM class_schedules s JOIN courses c ON c.id = s.course_id WHERE s.id = :id';
    $parameters = ['id' => $scheduleId];
    if ($user['role'] === 'guru') {
        $sql .= ' AND c.teacher_id = :teacher_id';
        $parameters['teacher_id'] = $user['id'];
    }
    $statement = $connection->prepare($sql);
    $statement->execute($parameters);
    $schedule = $statement->fetch();
    if ($schedule === false) {
        http_response_code(404);
        exit('Jadwal tidak ditemukan.');
    }

    return $schedule;
};

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_post_csrf();
    if (!$canManage) {
        http_response_code(403);
        exit('Murid hanya dapat melihat jadwal kelas yang diikuti.');
    }

    try {
        $action = post_value('action', 24);
        if ($action === 'delete_schedule') {
            $scheduleId = post_id('schedule_id');
            $schedule = $loadManagedSchedule($scheduleId);
            $delete = $connection->prepare('DELETE FROM class_schedules WHERE id = :id');
            $delete->execute(['id' => $schedule['id']]);
            set_flash('Jadwal kelas berhasil dihapus.');
            redirect('/schedule.php');
        }

        if (!in_array($action, ['create_schedule', 'update_schedule'], true)) {
            http_response_code(400);
            exit('Aksi jadwal tidak dikenal.');
        }

        $scheduleId = $action === 'update_schedule' ? post_id('schedule_id') : null;
        $existingSchedule = $scheduleId === null ? null : $loadManagedSchedule($scheduleId);
        $courseId = post_id('course_id');
        $loadManageableCourse($courseId);
        $title = post_value('title', 120);
        $startsAtInput = post_value('starts_at', 16);
        $durationInput = $_POST['duration_minutes'] ?? null;
        $duration = is_string($durationInput) ? filter_var($durationInput, FILTER_VALIDATE_INT) : false;
        $location = post_value('location', 160);
        $meetingUrl = post_value('meeting_url', 500);
        $notes = post_value('notes', 2000);

        $startsAt = DateTimeImmutable::createFromFormat('!Y-m-d\TH:i', $startsAtInput);
        $dateErrors = DateTimeImmutable::getLastErrors();
        if (
            $title === ''
            || $startsAt === false
            || ($dateErrors !== false && ($dateErrors['warning_count'] > 0 || $dateErrors['error_count'] > 0))
            || $startsAt->format('Y-m-d\TH:i') !== $startsAtInput
        ) {
            throw new InvalidArgumentException('Judul dan tanggal/jam jadwal harus diisi dengan benar.');
        }
        if ($duration === false || $duration < 10 || $duration > 480) {
            throw new InvalidArgumentException('Durasi sesi harus antara 10 menit hingga 8 jam.');
        }
        if ($meetingUrl !== '' && (
            filter_var($meetingUrl, FILTER_VALIDATE_URL) === false
            || !in_array(strtolower((string) parse_url($meetingUrl, PHP_URL_SCHEME)), ['http', 'https'], true)
        )) {
            throw new InvalidArgumentException('Tautan kelas harus berupa URL HTTP atau HTTPS yang valid.');
        }

        if ($action === 'create_schedule') {
            $statement = $connection->prepare(
                'INSERT INTO class_schedules
                    (course_id, title, starts_at, duration_minutes, location, meeting_url, notes, created_by)
                 VALUES (:course_id, :title, :starts_at, :duration, :location, :meeting_url, :notes, :created_by)'
            );
            $statement->execute([
                'course_id' => $courseId,
                'title' => $title,
                'starts_at' => $startsAt->format('Y-m-d H:i:s'),
                'duration' => $duration,
                'location' => $location,
                'meeting_url' => $meetingUrl,
                'notes' => $notes,
                'created_by' => $user['id'],
            ]);
            set_flash('Jadwal kelas berhasil ditambahkan.');
        } else {
            $statement = $connection->prepare(
                'UPDATE class_schedules
                 SET course_id = :course_id, title = :title, starts_at = :starts_at,
                     duration_minutes = :duration, location = :location, meeting_url = :meeting_url,
                     notes = :notes, updated_at = CURRENT_TIMESTAMP
                 WHERE id = :id'
            );
            $statement->execute([
                'course_id' => $courseId,
                'title' => $title,
                'starts_at' => $startsAt->format('Y-m-d H:i:s'),
                'duration' => $duration,
                'location' => $location,
                'meeting_url' => $meetingUrl,
                'notes' => $notes,
                'id' => $existingSchedule['id'],
            ]);
            set_flash('Perubahan jadwal berhasil disimpan.');
        }
        redirect('/schedule.php');
    } catch (InvalidArgumentException $exception) {
        set_flash($exception->getMessage(), 'error');
        redirect('/schedule.php');
    }
}

$editingSchedule = null;
if ($canManage && isset($_GET['edit'])) {
    $editId = filter_var($_GET['edit'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    if ($editId === false) {
        http_response_code(400);
        exit('ID jadwal tidak valid.');
    }
    $editingSchedule = $loadManagedSchedule($editId);
}

if ($user['role'] === 'guru') {
    $courseStatement = $connection->prepare(
        "SELECT id, title FROM courses WHERE teacher_id = :teacher_id AND status = 'published' ORDER BY title"
    );
    $courseStatement->execute(['teacher_id' => $user['id']]);
    $courses = $courseStatement->fetchAll();
} elseif ($user['role'] === 'admin') {
    $courses = $connection->query("SELECT id, title FROM courses WHERE status = 'published' ORDER BY title")->fetchAll();
} else {
    $courses = [];
}

$query = "SELECT s.*, c.title AS course_title, c.level, u.name AS teacher_name
          FROM class_schedules s
          JOIN courses c ON c.id = s.course_id AND c.status = 'published'
          LEFT JOIN users u ON u.id = c.teacher_id";
$parameters = ['now' => date('Y-m-d H:i:s')];
if ($user['role'] === 'guru') {
    $query .= ' WHERE c.teacher_id = :user_id';
    $parameters['user_id'] = $user['id'];
} elseif ($user['role'] === 'murid') {
    $query .= ' JOIN enrollments e ON e.course_id = c.id AND e.student_id = :user_id';
    $parameters['user_id'] = $user['id'];
}
$query .= " ORDER BY (s.starts_at < :now), CASE WHEN s.starts_at >= :now THEN s.starts_at END ASC, s.starts_at DESC LIMIT 200";
$statement = $connection->prepare($query);
$statement->execute($parameters);
$schedules = $statement->fetchAll();

$csrfToken = csrf_token();
$flash = take_flash();
$pageTitle = match ($user['role']) {
    'guru' => 'Jadwal mengajar',
    'murid' => 'Jadwal kelas saya',
    default => 'Jadwal kelas',
};
$pageDescription = match ($user['role']) {
    'guru' => 'Atur sesi untuk kursus yang kamu ajar dan pantau agenda kelas.',
    'murid' => 'Lihat waktu, kursus, dan informasi pertemuan dari kelas yang kamu ikuti.',
    default => 'Atur dan pantau seluruh sesi kelas pada platform.',
};

require __DIR__ . '/views/schedule.php';
