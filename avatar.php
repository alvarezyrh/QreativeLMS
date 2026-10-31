<?php

declare(strict_types=1);

require_once __DIR__ . '/app/bootstrap.php';
require_once __DIR__ . '/app/profile.php';

require_auth(database());

$id = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
if ($id === false) {
    http_response_code(404);
    exit;
}

$statement = database()->prepare('SELECT avatar FROM users WHERE id = :id');
$statement->execute(['id' => $id]);
$filename = $statement->fetchColumn();
if (!is_string($filename) || !is_avatar_filename($filename) || !is_file(avatar_path($filename))) {
    http_response_code(404);
    exit;
}

$path = avatar_path($filename);
$mime = (new finfo(FILEINFO_MIME_TYPE))->file($path);
if (!in_array($mime, ['image/png', 'image/jpeg', 'image/webp'], true)) {
    http_response_code(404);
    exit;
}

// URL foto memuat penanda versi, jadi aman disimpan lama di cache browser.
header('Content-Type: ' . $mime);
header('Content-Length: ' . (string) filesize($path));
header('Cache-Control: private, max-age=2592000');
header('X-Content-Type-Options: nosniff');
readfile($path);
