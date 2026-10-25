<?php

declare(strict_types=1);

function app_settings(PDO $connection): array
{
    $settings = $connection->query('SELECT setting_key, setting_value FROM app_settings')->fetchAll();

    return array_column($settings, 'setting_value', 'setting_key');
}

function save_app_setting(PDO $connection, string $key, string $value): void
{
    $statement = $connection->prepare(
        'INSERT INTO app_settings (setting_key, setting_value, updated_at)
         VALUES (:key, :value, CURRENT_TIMESTAMP)
         ON CONFLICT(setting_key) DO UPDATE SET setting_value = excluded.setting_value, updated_at = CURRENT_TIMESTAMP'
    );
    $statement->execute(['key' => $key, 'value' => $value]);
}

function store_qris_upload(array $upload): string
{
    if (($upload['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        throw new InvalidArgumentException(match ($upload['error'] ?? UPLOAD_ERR_NO_FILE) {
            UPLOAD_ERR_NO_FILE => 'Pilih file QRIS terlebih dahulu.',
            UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'Ukuran gambar QRIS melebihi batas 2 MB.',
            default => 'Upload QRIS gagal. Silakan coba lagi.',
        });
    }

    if (!is_uploaded_file($upload['tmp_name']) || (int) $upload['size'] > 2 * 1024 * 1024) {
        throw new InvalidArgumentException('File QRIS tidak valid atau melebihi batas 2 MB.');
    }

    $image = @getimagesize($upload['tmp_name']);
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($upload['tmp_name']);
    $extensions = ['image/png' => 'png', 'image/jpeg' => 'jpg', 'image/webp' => 'webp'];
    if (
        $image === false
        || !isset($extensions[$mime])
        || $image['mime'] !== $mime
        || $image[0] > 4000
        || $image[1] > 4000
    ) {
        throw new InvalidArgumentException('File QRIS harus berupa gambar PNG, JPG, atau WebP yang valid (maksimal 4000 × 4000 px).');
    }

    $directory = dirname(__DIR__) . '/storage/payments';
    if (!is_dir($directory) && !mkdir($directory, 0700, true) && !is_dir($directory)) {
        throw new RuntimeException('Folder penyimpanan QRIS tidak dapat dibuat.');
    }

    $filename = bin2hex(random_bytes(24)) . '.' . $extensions[$mime];
    $destination = $directory . '/' . $filename;
    if (!move_uploaded_file($upload['tmp_name'], $destination)) {
        throw new RuntimeException('Gambar QRIS tidak dapat disimpan.');
    }
    if (!chmod($destination, 0600)) {
        unlink($destination);
        throw new RuntimeException('Izin akses gambar QRIS tidak dapat dibatasi.');
    }

    return $filename;
}
