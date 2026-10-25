<?php

declare(strict_types=1);

$path = rawurldecode(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/');
foreach (['/app/', '/database/', '/storage/', '/views/'] as $protectedPath) {
    if (str_starts_with($path, $protectedPath)) {
        http_response_code(404);
        exit;
    }
}
if ($path === '/') {
    return false;
}

if (preg_match('/(?:^|\/)\.[^\/]+/', $path) === 1) {
    http_response_code(404);
    exit;
}

$requestedFile = realpath(dirname(__DIR__) . $path);
$projectRoot = realpath(dirname(__DIR__));
if (
    $requestedFile !== false
    && $projectRoot !== false
    && str_starts_with($requestedFile, $projectRoot . DIRECTORY_SEPARATOR)
    && is_file($requestedFile)
) {
    return false;
}

http_response_code(404);
echo 'Tidak ditemukan.';
