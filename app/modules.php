<?php

declare(strict_types=1);

/** Role yang dapat membuka perpustakaan modul. */
const MODULE_ROLES = ['admin', 'guru'];

/** Kategori folder: level CEFR, ditambah "Umum" untuk bahan lintas level. */
function module_levels(): array
{
    return [
        'A1' => 'Beginner',
        'A2' => 'Elementary',
        'B1' => 'Intermediate',
        'B2' => 'Upper Intermediate',
        'C1' => 'Advanced',
        'C2' => 'Proficiency',
        'UMUM' => 'Lintas level',
    ];
}

function module_level_label(string $level): string
{
    return $level === 'UMUM' ? 'Umum' : $level;
}

function module_kinds(): array
{
    return ['modul' => 'Modul', 'bahan_ajar' => 'Bahan ajar'];
}

/**
 * Jenis file yang boleh diunggah: ekstensi => tipe MIME yang diterima.
 * File Office modern terdeteksi sebagai ZIP pada sebagian server.
 */
function module_file_types(): array
{
    $zip = ['application/zip', 'application/x-zip-compressed'];

    return [
        'pdf' => ['application/pdf'],
        'doc' => ['application/msword', 'application/vnd.ms-office', 'application/CDFV2'],
        'docx' => array_merge(['application/vnd.openxmlformats-officedocument.wordprocessingml.document'], $zip),
        'ppt' => ['application/vnd.ms-powerpoint', 'application/vnd.ms-office', 'application/CDFV2'],
        'pptx' => array_merge(['application/vnd.openxmlformats-officedocument.presentationml.presentation'], $zip),
        'xls' => ['application/vnd.ms-excel', 'application/vnd.ms-office', 'application/CDFV2'],
        'xlsx' => array_merge(['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'], $zip),
        'txt' => ['text/plain'],
        'jpg' => ['image/jpeg'],
        'jpeg' => ['image/jpeg'],
        'png' => ['image/png'],
        'webp' => ['image/webp'],
        'mp3' => ['audio/mpeg'],
        'mp4' => ['video/mp4'],
        'zip' => $zip,
    ];
}

/** Ukuran upload maksimal: 20 MB, atau lebih kecil jika dibatasi server. */
function module_upload_limit(): int
{
    $toBytes = static function (string $value): int {
        $value = trim($value);
        if ($value === '' || $value === '0') {
            return PHP_INT_MAX;
        }
        $number = (int) $value;

        return match (strtolower(substr($value, -1))) {
            'g' => $number * 1024 ** 3,
            'm' => $number * 1024 ** 2,
            'k' => $number * 1024,
            default => $number,
        };
    };

    return min(
        20 * 1024 * 1024,
        $toBytes((string) ini_get('upload_max_filesize')),
        $toBytes((string) ini_get('post_max_size'))
    );
}

function format_bytes(int $bytes): string
{
    if ($bytes >= 1024 * 1024) {
        return number_format($bytes / 1024 / 1024, 1, ',', '.') . ' MB';
    }

    return max(1, (int) round($bytes / 1024)) . ' KB';
}

function module_file_extension(string $filename): string
{
    return strtolower(pathinfo($filename, PATHINFO_EXTENSION));
}

function is_module_filename(string $filename): bool
{
    return preg_match('/\A[a-f0-9]{48}\.[a-z0-9]{2,5}\z/', $filename) === 1;
}

function module_file_path(string $filename): string
{
    return dirname(__DIR__) . '/storage/modules/' . $filename;
}

function delete_module_file(string $filename): void
{
    if (is_module_filename($filename) && is_file(module_file_path($filename))) {
        unlink(module_file_path($filename));
    }
}

/**
 * Validasi dan simpan file modul ke storage/modules dengan nama acak.
 *
 * @return array{file_name: string, original_name: string, mime_type: string, size_bytes: int}
 */
function store_module_file(array $upload): array
{
    $limit = module_upload_limit();
    $error = $upload['error'] ?? UPLOAD_ERR_NO_FILE;
    if ($error !== UPLOAD_ERR_OK) {
        throw new InvalidArgumentException(match ($error) {
            UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'Ukuran file melebihi batas ' . format_bytes($limit) . '.',
            UPLOAD_ERR_NO_FILE => 'Pilih file modul terlebih dahulu.',
            default => 'Upload file gagal. Silakan coba lagi.',
        });
    }
    if (!is_uploaded_file($upload['tmp_name']) || (int) $upload['size'] <= 0 || (int) $upload['size'] > $limit) {
        throw new InvalidArgumentException('File tidak valid atau melebihi batas ' . format_bytes($limit) . '.');
    }

    $originalName = trim(preg_replace('/[\x00-\x1F\x7F\/\\\\]+/u', ' ', (string) $upload['name']) ?? '');
    $extension = module_file_extension($originalName);
    $types = module_file_types();
    $mime = (string) (new finfo(FILEINFO_MIME_TYPE))->file($upload['tmp_name']);
    if (!isset($types[$extension]) || !in_array($mime, $types[$extension], true)) {
        throw new InvalidArgumentException('Jenis file tidak didukung. Gunakan PDF, Word, PowerPoint, Excel, TXT, gambar, MP3, MP4, atau ZIP.');
    }

    $directory = dirname(__DIR__) . '/storage/modules';
    if (!is_dir($directory) && !mkdir($directory, 0700, true) && !is_dir($directory)) {
        throw new RuntimeException('Folder penyimpanan modul tidak dapat dibuat.');
    }
    $fileName = bin2hex(random_bytes(24)) . '.' . ($extension === 'jpeg' ? 'jpg' : $extension);
    $destination = $directory . '/' . $fileName;
    if (!move_uploaded_file($upload['tmp_name'], $destination)) {
        throw new RuntimeException('File modul tidak dapat disimpan.');
    }
    chmod($destination, 0600);

    return [
        'file_name' => $fileName,
        'original_name' => text_excerpt($originalName, 180),
        'mime_type' => $mime,
        'size_bytes' => (int) $upload['size'],
    ];
}

/** Pembuat dan admin dapat mengubah atau menghapus folder/modul. */
function can_edit_module_entry(array $user, mixed $ownerId): bool
{
    return $user['role'] === 'admin' || ($ownerId !== null && (int) $ownerId === (int) $user['id']);
}

/** Label singkat jenis file untuk ikon daftar modul. */
function module_badge(array $item): string
{
    if ($item['file_name'] === '') {
        return 'URL';
    }

    return strtoupper(module_file_extension($item['file_name']));
}
