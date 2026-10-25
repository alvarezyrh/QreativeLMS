<?php

declare(strict_types=1);

require_once __DIR__ . '/app/bootstrap.php';
require_once __DIR__ . '/app/lms.php';

$user = require_auth(database());
if ($user['role'] !== 'admin') {
    http_response_code(403);
    exit('Hanya admin yang dapat mengelola pengguna.');
}

$connection = database();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_post_csrf();

    try {
        $action = post_value('action', 32);
        if ($action === 'create_user') {
            $name = post_value('name', 120);
            $email = post_value('email', 254);
            $password = post_value('password', 256);
            $role = post_value('role', 16);
            if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new InvalidArgumentException('Nama dan alamat email yang valid wajib diisi.');
            }
            if (strlen($password) < 10) {
                throw new InvalidArgumentException('Kata sandi pengguna harus memiliki minimal 10 karakter.');
            }
            if (!in_array($role, ['admin', 'guru', 'murid'], true)) {
                throw new InvalidArgumentException('Role pengguna tidak valid.');
            }
            $statement = $connection->prepare(
                'INSERT INTO users (name, email, password_hash, role) VALUES (:name, :email, :password_hash, :role)'
            );
            $statement->execute([
                'name' => $name,
                'email' => $email,
                'password_hash' => password_hash($password, PASSWORD_DEFAULT),
                'role' => $role,
            ]);
            set_flash('Pengguna berhasil dibuat.');
        } elseif ($action === 'update_user') {
            $userId = post_id('user_id');
            $name = post_value('name', 120);
            $email = post_value('email', 254);
            $role = post_value('role', 16);
            $status = post_value('status', 16);
            $newPassword = post_value('password', 256);
            if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new InvalidArgumentException('Nama dan alamat email yang valid wajib diisi.');
            }
            if (!in_array($role, ['admin', 'guru', 'murid'], true) || !in_array($status, ['active', 'inactive'], true)) {
                throw new InvalidArgumentException('Role atau status pengguna tidak valid.');
            }
            if ($newPassword !== '' && strlen($newPassword) < 10) {
                throw new InvalidArgumentException('Kata sandi baru harus memiliki minimal 10 karakter.');
            }
            if ($userId === (int) $user['id'] && $status !== 'active') {
                throw new InvalidArgumentException('Akun yang sedang digunakan tidak dapat dinonaktifkan.');
            }
            $currentStatement = $connection->prepare('SELECT role, status FROM users WHERE id = :id');
            $currentStatement->execute(['id' => $userId]);
            $targetUser = $currentStatement->fetch();
            if ($targetUser === false) {
                throw new InvalidArgumentException('Pengguna tidak ditemukan.');
            }
            if ($targetUser['role'] === 'admin' && $targetUser['status'] === 'active' && ($role !== 'admin' || $status !== 'active')) {
                $activeAdmins = (int) $connection->query("SELECT COUNT(*) FROM users WHERE role = 'admin' AND status = 'active'")->fetchColumn();
                if ($activeAdmins <= 1) {
                    throw new InvalidArgumentException('Minimal harus ada satu akun admin aktif.');
                }
            }
            if ($targetUser['role'] === 'guru' && ($role !== 'guru' || $status !== 'active')) {
                $ownedCourses = $connection->prepare('SELECT COUNT(*) FROM courses WHERE teacher_id = :teacher_id');
                $ownedCourses->execute(['teacher_id' => $userId]);
                if ((int) $ownedCourses->fetchColumn() > 0) {
                    throw new InvalidArgumentException('Pindahkan kursus guru ini ke guru lain sebelum mengubah role atau menonaktifkan akunnya.');
                }
            }

            $parameters = ['name' => $name, 'email' => $email, 'role' => $role, 'status' => $status, 'id' => $userId];
            $sql = 'UPDATE users SET name = :name, email = :email, role = :role, status = :status';
            if ($newPassword !== '') {
                $sql .= ', password_hash = :password_hash';
                $parameters['password_hash'] = password_hash($newPassword, PASSWORD_DEFAULT);
            }
            $sql .= ' WHERE id = :id';
            $connection->prepare($sql)->execute($parameters);
            set_flash('Perubahan pengguna berhasil disimpan.');
        } elseif ($action === 'delete_user') {
            $userId = post_id('user_id');
            if ($userId === (int) $user['id']) {
                throw new InvalidArgumentException('Akun yang sedang digunakan tidak dapat dinonaktifkan.');
            }
            $targetStatement = $connection->prepare('SELECT role, status FROM users WHERE id = :id');
            $targetStatement->execute(['id' => $userId]);
            $targetUser = $targetStatement->fetch();
            if ($targetUser === false) {
                throw new InvalidArgumentException('Pengguna tidak ditemukan.');
            }
            if ($targetUser['role'] === 'admin' && $targetUser['status'] === 'active') {
                $activeAdmins = (int) $connection->query("SELECT COUNT(*) FROM users WHERE role = 'admin' AND status = 'active'")->fetchColumn();
                if ($activeAdmins <= 1) {
                    throw new InvalidArgumentException('Minimal harus ada satu akun admin aktif.');
                }
            }
            if ($targetUser['role'] === 'guru' && $targetUser['status'] === 'active') {
                $ownedCourses = $connection->prepare('SELECT COUNT(*) FROM courses WHERE teacher_id = :teacher_id');
                $ownedCourses->execute(['teacher_id' => $userId]);
                if ((int) $ownedCourses->fetchColumn() > 0) {
                    throw new InvalidArgumentException('Pindahkan kursus guru ini ke guru lain sebelum menonaktifkan akunnya.');
                }
            }
            $disable = $connection->prepare("UPDATE users SET status = 'inactive' WHERE id = :id");
            $disable->execute(['id' => $userId]);
            set_flash('Akun pengguna dinonaktifkan. Data kursus, pendaftaran, dan progres tetap dipertahankan.');
        } else {
            http_response_code(400);
            exit('Aksi tidak dikenal.');
        }

        redirect('/users.php');
    } catch (InvalidArgumentException $exception) {
        set_flash($exception->getMessage(), 'error');
        redirect('/users.php');
    } catch (PDOException $exception) {
        if (str_contains($exception->getMessage(), 'UNIQUE constraint failed: users.email')) {
            set_flash('Alamat email tersebut sudah terdaftar.', 'error');
            redirect('/users.php');
        }
        throw $exception;
    }
}

$editingUser = null;
if (isset($_GET['edit']) && ctype_digit((string) $_GET['edit'])) {
    $statement = $connection->prepare('SELECT id, name, email, role, status FROM users WHERE id = :id');
    $statement->execute(['id' => (int) $_GET['edit']]);
    $editingUser = $statement->fetch();
    if ($editingUser === false) {
        http_response_code(404);
        exit('Pengguna tidak ditemukan.');
    }
}
$users = $connection->query(
    "SELECT u.id, u.name, u.email, u.role, u.status, u.created_at,
        COUNT(DISTINCT e.id) AS enrollment_count
     FROM users u
     LEFT JOIN enrollments e ON e.student_id = u.id
     GROUP BY u.id
     ORDER BY CASE u.role WHEN 'admin' THEN 1 WHEN 'guru' THEN 2 ELSE 3 END, u.name"
)->fetchAll();
$flash = take_flash();
$csrfToken = csrf_token();
$pageTitle = 'Kelola pengguna';
$pageDescription = 'Buat, ubah, dan nonaktifkan akun pengguna berdasarkan role.';

require __DIR__ . '/views/users.php';
