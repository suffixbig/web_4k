<?php
declare(strict_types=1);

$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
if (str_starts_with($path, '/api/')) {
    $_SERVER['SCRIPT_NAME'] = '/api/index.php';
    require dirname(__DIR__) . '/api/index.php';
    return true;
}

if ($path === '/admin' || $path === '/admin/') {
    $_SERVER['SCRIPT_NAME'] = '/admin.php';
    require dirname(__DIR__) . '/admin.php';
    return true;
}

if ($path === '/advertising' || $path === '/advertising/') {
    $_SERVER['SCRIPT_NAME'] = '/advertising.php';
    require dirname(__DIR__) . '/advertising.php';
    return true;
}

return false;
