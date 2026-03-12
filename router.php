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

if ($path === '/api/health' || $path === '/api/health/') {
    require __DIR__ . '/src/bootstrap.php';
    header('Content-Type: application/json; charset=utf-8');
    try {
        ccms_hit_rate_limit('public_health', ccms_client_ip(), 60, 60, 'Too many health checks.');
    } catch (Throwable $e) {
        http_response_code(429);
        echo json_encode([
            'ok' => false,
            'error' => 'rate_limited',
            'message' => $e->getMessage(),
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        return true;
    }
    $payload = [
        'ok' => true,
        'installed' => ccms_is_installed(),
        'php_version' => PHP_VERSION,
        'storage' => ccms_storage_runtime_info()['driver'] ?? 'json',
        'timestamp' => ccms_now_iso(),
    ];
    if ($payload['installed']) {
        $data = ccms_load_data();
        $payload['site_title'] = (string) ($data['site']['title'] ?? 'LinuxCMS');
        $payload['pages'] = is_array($data['pages'] ?? null) ? count($data['pages']) : 0;
    }
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    return true;
}

if ($path === '/install' || $path === '/install.php') {
    require __DIR__ . '/install.php';
    return true;
}

$_GET['path'] = trim((string) $path, '/');
require __DIR__ . '/index.php';
