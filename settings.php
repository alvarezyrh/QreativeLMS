<?php

declare(strict_types=1);

require_once __DIR__ . '/app/bootstrap.php';
require_once __DIR__ . '/app/lms.php';
require_once __DIR__ . '/app/settings.php';

$user = require_auth(database());
if ($user['role'] !== 'admin') {
    http_response_code(403);
    exit('Hanya admin yang dapat mengubah pengaturan platform.');
}

$connection = database();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_post_csrf();

    try {
        $platformName = post_value('platform_name', 120);
        $supportEmail = post_value('support_email', 254);
        $instructions = post_value('qris_instructions', 2000);
        if ($platformName === '' || ($supportEmail !== '' && !filter_var($supportEmail, FILTER_VALIDATE_EMAIL))) {
            throw new InvalidArgumentException('Nama platform wajib diisi dan email bantuan harus valid.');
        }

        $settings = app_settings($connection);
        $newQrImage = '';
        if (isset($_FILES['qris_image']) && ($_FILES['qris_image']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
            $newQrImage = store_qris_upload($_FILES['qris_image']);
        }

        save_app_setting($connection, 'platform_name', $platformName);
        save_app_setting($connection, 'support_email', $supportEmail);
        save_app_setting($connection, 'qris_instructions', $instructions);
        if ($newQrImage !== '') {
            save_app_setting($connection, 'qris_image', $newQrImage);
            $oldQrImage = $settings['qris_image'] ?? '';
            if (preg_match('/\A[a-f0-9]{48}\.(?:png|jpg|webp)\z/', $oldQrImage) === 1) {
                $oldPath = __DIR__ . '/storage/payments/' . $oldQrImage;
                if (is_file($oldPath) && !unlink($oldPath)) {
                    throw new RuntimeException('QRIS baru tersimpan, tetapi gambar QRIS lama tidak dapat dihapus.');
                }
            }
        }

        set_flash('Pengaturan platform dan pembayaran berhasil disimpan.');
        redirect('/settings.php');
    } catch (InvalidArgumentException $exception) {
        set_flash($exception->getMessage(), 'error');
        redirect('/settings.php');
    }
}

$settings = app_settings($connection);
$csrfToken = csrf_token();
$flash = take_flash();
$pageTitle = 'Pengaturan platform';
$pageDescription = 'Atur identitas platform dan instruksi pembayaran manual QRIS BCA.';

require __DIR__ . '/views/settings.php';
