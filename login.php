<?php

declare(strict_types=1);

require_once __DIR__ . '/app/bootstrap.php';
require_once __DIR__ . '/app/settings.php';

if (current_user(database()) !== null) {
    redirect('/');
}

$error = '';
$email = '';
$platformName = app_settings(database())['platform_name'] ?? 'Qreative LMS';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
        http_response_code(419);
        $error = 'Sesi formulir tidak valid atau sudah kedaluwarsa. Silakan muat ulang halaman.';
    } else {
        $emailInput = $_POST['email'] ?? '';
        $passwordInput = $_POST['password'] ?? '';
        $email = is_string($emailInput) ? trim($emailInput) : '';
        $password = is_string($passwordInput) ? $passwordInput : '';

        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || $password === '' || strlen($password) > 4096) {
            $error = 'Email atau kata sandi tidak valid.';
        } else {
            $statement = database()->prepare(
                'SELECT id, password_hash FROM users WHERE email = :email AND status = :status LIMIT 1'
            );
            $statement->execute(['email' => $email, 'status' => 'active']);
            $account = $statement->fetch();

            if ($account !== false && password_verify($password, $account['password_hash'])) {
                session_regenerate_id(true);
                $_SESSION['user_id'] = (int) $account['id'];
                unset($_SESSION['csrf_token']);
                redirect('/');
            }

            $error = 'Email atau kata sandi tidak cocok.';
        }
    }
}

$token = csrf_token();
require __DIR__ . '/views/login.php';
