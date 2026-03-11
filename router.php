<?php
declare(strict_types=1);

$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$fullPath = __DIR__ . ($path === '/' ? '/index.php' : $path);

if ($path !== '/' && file_exists($fullPath) && !is_dir($fullPath)) {
    return false;
}

if ($path === '/r-admin' || $path === '/r-admin/' || $path === '/r_admin' || $path === '/r_admin/') {
    require __DIR__ . '/r-admin/index.php';
    return true;
}

if ($path === '/r-admin/logout' || $path === '/r-admin/logout/' || $path === '/r_admin/logout' || $path === '/r_admin/logout/') {
    require __DIR__ . '/r-admin/logout.php';
    return true;
}

if ($path === '/install' || $path === '/install.php') {
    require __DIR__ . '/install.php';
    return true;
}

$_GET['path'] = trim((string) $path, '/');
require __DIR__ . '/index.php';
