<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/src/bootstrap.php';

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
    exit;
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
