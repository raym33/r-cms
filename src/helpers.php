<?php
declare(strict_types=1);

function ccms_root_path(string $relative = ''): string
{
    $root = trim((string) getenv('CCMS_ROOT'));
    if ($root === '') {
        $root = dirname(__DIR__);
    }
    if ($relative === '') {
        return $root;
    }
    return $root . DIRECTORY_SEPARATOR . ltrim($relative, DIRECTORY_SEPARATOR);
}

function ccms_h(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function ccms_slugify(string $value): string
{
    $value = trim(mb_strtolower($value, 'UTF-8'));
    $value = preg_replace('/[^a-z0-9]+/u', '-', $value) ?? '';
    $value = trim($value, '-');
    return $value !== '' ? $value : 'page';
}

function ccms_now_iso(): string
{
    return gmdate('c');
}

function ccms_redirect(string $url): never
{
    header('Location: ' . $url);
    exit;
}

function ccms_request_path(): string
{
    $path = $_GET['path'] ?? parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
    $path = is_string($path) ? $path : '/';
    return '/' . trim($path, '/');
}

function ccms_base_url(): string
{
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? '127.0.0.1:8088';
    return $scheme . '://' . $host;
}

function ccms_send_common_security_headers(): void
{
    if (headers_sent()) {
        return;
    }
    @header_remove('X-Powered-By');
    header('X-Frame-Options: SAMEORIGIN');
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: same-origin');
    header("Permissions-Policy: camera=(), microphone=(), geolocation=()");
    header("Content-Security-Policy: default-src 'self' https: data: blob: 'unsafe-inline'; img-src 'self' https: data: blob:; style-src 'self' 'unsafe-inline' https:; script-src 'self' 'unsafe-inline' 'unsafe-eval'; frame-src 'self'; connect-src 'self' http://127.0.0.1:1234 http://localhost:1234 https:;");
}

function ccms_send_admin_headers(): void
{
    ccms_send_common_security_headers();
    if (headers_sent()) {
        return;
    }
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Pragma: no-cache');
    header('Expires: 0');
}

function ccms_flash(string $type, string $message): void
{
    $_SESSION['ccms_flash'] = ['type' => $type, 'message' => $message];
}

function ccms_consume_flash(): ?array
{
    if (!isset($_SESSION['ccms_flash']) || !is_array($_SESSION['ccms_flash'])) {
        return null;
    }
    $flash = $_SESSION['ccms_flash'];
    unset($_SESSION['ccms_flash']);
    return $flash;
}

function ccms_public_upload_url(string $filename): string
{
    return '/uploads/' . rawurlencode($filename);
}
