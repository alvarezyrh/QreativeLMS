<?php

declare(strict_types=1);

function require_post_csrf(): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        header('Allow: POST');
        exit;
    }

    if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
        http_response_code(419);
        exit('Formulir kedaluwarsa. Muat ulang halaman dan coba lagi.');
    }
}

function post_value(string $key, int $maxLength): string
{
    $value = $_POST[$key] ?? '';
    if (!is_string($value)) {
        throw new InvalidArgumentException('Data formulir tidak valid.');
    }

    $value = trim($value);
    if (strlen($value) > $maxLength * 4) {
        throw new InvalidArgumentException('Ada isian yang melebihi batas karakter.');
    }

    return $value;
}

function post_id(string $key): int
{
    $value = $_POST[$key] ?? null;
    if (!is_string($value) && !is_int($value)) {
        throw new InvalidArgumentException('Data ID tidak valid.');
    }

    $id = filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    if ($id === false) {
        throw new InvalidArgumentException('Data ID tidak valid.');
    }

    return $id;
}

function set_flash(string $message, string $type = 'success'): void
{
    $_SESSION['flash'] = ['message' => $message, 'type' => $type];
}

function take_flash(): ?array
{
    $flash = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);

    return is_array($flash) ? $flash : null;
}

function course_for_editor(PDO $connection, int $courseId, array $user): array
{
    $statement = $connection->prepare('SELECT * FROM courses WHERE id = :id');
    $statement->execute(['id' => $courseId]);
    $course = $statement->fetch();
    if ($course === false || ($user['role'] === 'guru' && (int) $course['teacher_id'] !== (int) $user['id'])) {
        http_response_code(404);
        exit('Kursus tidak ditemukan.');
    }

    return $course;
}

function can_manage_course(array $user, array $course): bool
{
    return $user['role'] === 'admin'
        || ($user['role'] === 'guru' && (int) $course['teacher_id'] === (int) $user['id']);
}

function validate_course_fields(): array
{
    $title = post_value('title', 120);
    $description = post_value('description', 2000);
    $level = post_value('level', 2);
    $status = post_value('status', 16);
    $priceInput = $_POST['price_idr'] ?? '';
    $price = is_string($priceInput) ? filter_var($priceInput, FILTER_VALIDATE_INT) : false;

    if ($title === '' || $description === '') {
        throw new InvalidArgumentException('Nama dan deskripsi kursus wajib diisi.');
    }
    if ($price === false || $price < 0 || $price > 1000000000) {
        throw new InvalidArgumentException('Harga kursus harus berupa nominal rupiah antara 0 hingga 1 miliar.');
    }
    if (!in_array($level, ['A1', 'A2', 'B1', 'B2', 'C1', 'C2'], true)) {
        throw new InvalidArgumentException('Level kursus tidak valid.');
    }
    if (!in_array($status, ['draft', 'published'], true)) {
        throw new InvalidArgumentException('Status kursus tidak valid.');
    }

    return [$title, $description, $level, $status, $price];
}
