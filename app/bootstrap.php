<?php

declare(strict_types=1);

date_default_timezone_set('Asia/Jakarta');

$isHttps = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
ini_set('session.use_strict_mode', '1');
ini_set('session.use_only_cookies', '1');
session_set_cookie_params([
    'httponly' => true,
    'secure' => $isHttps,
    'samesite' => 'Lax',
    'path' => '/',
]);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/security.php';

/**
 * URL file statis dengan penanda versi dari isi file, sehingga browser dan
 * cache hosting/CDN selalu mengambil CSS/JS terbaru setelah deploy.
 */
function asset_url(string $path): string
{
    static $versions = [];
    $path = '/' . ltrim($path, '/');
    if (!array_key_exists($path, $versions)) {
        $file = dirname(__DIR__) . $path;
        $versions[$path] = is_file($file) ? (hash_file('crc32b', $file) ?: '') : '';
    }

    return $versions[$path] === '' ? $path : $path . '?v=' . $versions[$path];
}
