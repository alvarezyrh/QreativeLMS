<?php

declare(strict_types=1);

require_once __DIR__ . '/app/bootstrap.php';
require_once __DIR__ . '/app/security.php';

$user = require_auth(database());
if (!in_array($user['role'], ['admin', 'murid'], true)) {
    http_response_code(403);
    exit;
}

$filename = database()->query("SELECT setting_value FROM app_settings WHERE setting_key = 'qris_image'")->fetchColumn();
if (!is_string($filename) || preg_match('/\A[a-f0-9]{48}\.(png|jpg|webp)\z/', $filename) !== 1) {
    http_response_code(404);
    exit;
}

$path = __DIR__ . '/storage/payments/' . $filename;
if (!is_file($path)) {
    http_response_code(404);
    exit;
}

$mime = (new finfo(FILEINFO_MIME_TYPE))->file($path);
if (!in_array($mime, ['image/png', 'image/jpeg', 'image/webp'], true)) {
    http_response_code(404);
    exit;
}

header('Content-Type: ' . $mime);
header('Content-Length: ' . (string) filesize($path));
header('Cache-Control: private, no-store');
header('X-Content-Type-Options: nosniff');
readfile($path);
