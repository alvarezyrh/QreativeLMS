<?php

declare(strict_types=1);

/** Role yang boleh mengelola profil dan data diri sendiri. */
const PROFILE_ROLES = ['admin', 'guru'];

/** Pilihan status perkawinan mengikuti isian KTP. */
function marital_status_options(): array
{
    return [
        'belum_kawin' => 'Belum kawin',
        'kawin' => 'Kawin',
        'cerai_hidup' => 'Cerai hidup',
        'cerai_mati' => 'Cerai mati',
    ];
}

function is_valid_username(string $username): bool
{
    return preg_match('/\A[a-z0-9][a-z0-9._]{2,29}\z/', $username) === 1;
}

function is_avatar_filename(string $filename): bool
{
    return preg_match('/\A[a-f0-9]{48}\.(?:png|jpg|webp)\z/', $filename) === 1;
}

function avatar_path(string $filename): string
{
    return dirname(__DIR__) . '/storage/avatars/' . $filename;
}

function delete_avatar_file(string $filename): void
{
    if (is_avatar_filename($filename) && is_file(avatar_path($filename))) {
        unlink(avatar_path($filename));
    }
}
