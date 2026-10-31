<?php

declare(strict_types=1);

require_once __DIR__ . '/app/bootstrap.php';
require_once __DIR__ . '/app/lms.php';
require_once __DIR__ . '/app/layout.php';
require_once __DIR__ . '/app/settings.php';
require_once __DIR__ . '/app/profile.php';

$user = require_auth(database());
require_role($user, ...PROFILE_ROLES);
$connection = database();
$userId = (int) $user['id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_post_csrf();

    try {
        $action = post_value('action', 32);
        if ($action === 'update_profile') {
            $name = post_value('name', 120);
            $username = text_lower(post_value('username', 30));
            $nik = post_value('nik', 16);
            $bpjs = post_value('bpjs', 13);
            $address = post_value('address', 500);
            $maritalStatus = post_value('marital_status', 16);

            if ($name === '') {
                throw new InvalidArgumentException('Nama lengkap wajib diisi.');
            }
            if ($username !== '' && !is_valid_username($username)) {
                throw new InvalidArgumentException('Username harus 3–30 karakter: huruf kecil, angka, titik, atau garis bawah, dan diawali huruf/angka.');
            }
            if ($nik !== '' && preg_match('/\A\d{16}\z/', $nik) !== 1) {
                throw new InvalidArgumentException('NIK harus terdiri dari 16 digit angka.');
            }
            if ($bpjs !== '' && preg_match('/\A\d{11,13}\z/', $bpjs) !== 1) {
                throw new InvalidArgumentException('Nomor BPJS harus berupa 11–13 digit angka.');
            }
            if ($maritalStatus !== '' && !array_key_exists($maritalStatus, marital_status_options())) {
                throw new InvalidArgumentException('Status tidak valid.');
            }
            if ($username !== '') {
                $taken = $connection->prepare('SELECT 1 FROM users WHERE username = :username AND id <> :id');
                $taken->execute(['username' => $username, 'id' => $userId]);
                if ($taken->fetchColumn() !== false) {
                    throw new InvalidArgumentException('Username sudah dipakai pengguna lain.');
                }
            }

            $oldAvatar = (string) $user['avatar'];
            $avatar = $oldAvatar;
            $hasUpload = isset($_FILES['avatar']) && ($_FILES['avatar']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;
            if ($hasUpload) {
                $avatar = store_private_image($_FILES['avatar'], 'avatars', 'foto profil');
            } elseif (($_POST['remove_avatar'] ?? '') === '1') {
                $avatar = '';
            }

            try {
                $statement = $connection->prepare(
                    'UPDATE users SET name = :name, username = :username, nik = :nik, bpjs = :bpjs,
                     address = :address, marital_status = :marital_status, avatar = :avatar WHERE id = :id'
                );
                $statement->execute([
                    'name' => $name,
                    'username' => $username === '' ? null : $username,
                    'nik' => $nik,
                    'bpjs' => $bpjs,
                    'address' => $address,
                    'marital_status' => $maritalStatus,
                    'avatar' => $avatar,
                    'id' => $userId,
                ]);
            } catch (PDOException $exception) {
                if ($avatar !== $oldAvatar) {
                    delete_avatar_file($avatar);
                }
                throw $exception;
            }
            if ($avatar !== $oldAvatar) {
                delete_avatar_file($oldAvatar);
            }
            set_flash('Profil berhasil disimpan.');
        } elseif ($action === 'change_password') {
            $currentPassword = $_POST['current_password'] ?? '';
            $newPassword = $_POST['new_password'] ?? '';
            $confirmPassword = $_POST['confirm_password'] ?? '';
            if (!is_string($currentPassword) || !is_string($newPassword) || !is_string($confirmPassword)) {
                throw new InvalidArgumentException('Data formulir tidak valid.');
            }

            $hashStatement = $connection->prepare('SELECT password_hash FROM users WHERE id = :id');
            $hashStatement->execute(['id' => $userId]);
            if (strlen($currentPassword) > 4096 || !password_verify($currentPassword, (string) $hashStatement->fetchColumn())) {
                throw new InvalidArgumentException('Kata sandi saat ini tidak cocok.');
            }
            if (strlen($newPassword) < 10 || strlen($newPassword) > 256) {
                throw new InvalidArgumentException('Kata sandi baru harus 10–256 karakter.');
            }
            if (!hash_equals($newPassword, $confirmPassword)) {
                throw new InvalidArgumentException('Konfirmasi kata sandi baru tidak sama.');
            }

            $connection->prepare('UPDATE users SET password_hash = :hash WHERE id = :id')
                ->execute(['hash' => password_hash($newPassword, PASSWORD_DEFAULT), 'id' => $userId]);
            session_regenerate_id(true);
            set_flash('Kata sandi berhasil diganti.');
        } else {
            throw new InvalidArgumentException('Aksi tidak dikenal.');
        }
    } catch (InvalidArgumentException $exception) {
        set_flash($exception->getMessage(), 'error');
    }
    redirect('/profile.php');
}

$profileStatement = $connection->prepare(
    'SELECT id, name, username, email, role, avatar, nik, bpjs, address, marital_status, created_at FROM users WHERE id = :id'
);
$profileStatement->execute(['id' => $userId]);
$profile = $profileStatement->fetch();

$csrfToken = csrf_token();
$flash = take_flash();
$pageTitle = 'Profil saya';
$pageDescription = 'Kelola foto, akun, kata sandi, dan data diri Anda.';

require __DIR__ . '/views/profile.php';
