<?php

declare(strict_types=1);

require_once __DIR__ . '/app/bootstrap.php';
require_once __DIR__ . '/app/modules.php';

$user = require_auth(database());
if (!in_array($user['role'], MODULE_ROLES, true)) {
    http_response_code(403);
    exit;
}

$id = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$statement = database()->prepare('SELECT file_name, original_name, mime_type FROM module_items WHERE id = :id');
$statement->execute(['id' => $id ?: 0]);
$item = $statement->fetch();
if ($item === false || !is_module_filename($item['file_name']) || !is_file(module_file_path($item['file_name']))) {
    http_response_code(404);
    exit('File tidak ditemukan.');
}

$path = module_file_path($item['file_name']);
// Hanya PDF dan gambar yang boleh dibuka langsung di browser; lainnya selalu diunduh.
$inlineTypes = ['application/pdf', 'image/png', 'image/jpeg', 'image/webp'];
$inline = isset($_GET['view']) && in_array($item['mime_type'], $inlineTypes, true);
$downloadName = $item['original_name'] !== '' ? $item['original_name'] : $item['file_name'];
$asciiName = preg_replace('/[^A-Za-z0-9._ -]+/', '_', $downloadName) ?: 'modul';

header('Content-Type: ' . ($inline ? $item['mime_type'] : 'application/octet-stream'));
header('Content-Length: ' . (string) filesize($path));
header(sprintf(
    'Content-Disposition: %s; filename="%s"; filename*=UTF-8\'\'%s',
    $inline ? 'inline' : 'attachment',
    $asciiName,
    rawurlencode($downloadName)
));
header('Cache-Control: private, no-store');
header('X-Content-Type-Options: nosniff');
readfile($path);
