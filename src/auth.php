<?php
declare(strict_types=1);

require_once __DIR__ . '/storage.php';

function ccms_start_session(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_name('ccms_session');
        session_start();
    }
}

function ccms_csrf_token(): string
{
    ccms_start_session();
    if (empty($_SESSION['ccms_csrf'])) {
        $_SESSION['ccms_csrf'] = bin2hex(random_bytes(24));
    }
    return (string) $_SESSION['ccms_csrf'];
}

function ccms_verify_csrf(): void
{
    ccms_start_session();
    $token = $_POST['csrf_token'] ?? '';
    if (!hash_equals((string) ($_SESSION['ccms_csrf'] ?? ''), (string) $token)) {
        throw new RuntimeException('CSRF validation failed.');
    }
}

function ccms_current_admin(): ?array
{
    ccms_start_session();
    $admin = $_SESSION['ccms_admin'] ?? null;
    if (!is_array($admin)) {
        return null;
    }
    $data = ccms_load_data();
    $current = null;
    foreach (($data['users'] ?? []) as $user) {
        if (($user['id'] ?? '') === ($admin['id'] ?? '') || ($user['username'] ?? '') === ($admin['username'] ?? '')) {
            $current = $user;
            break;
        }
    }
    if ($current) {
        $_SESSION['ccms_admin'] = [
            'id' => $current['id'] ?? '',
            'username' => $current['username'] ?? '',
            'email' => $current['email'] ?? '',
            'role' => $current['role'] ?? 'owner',
        ];
        return $_SESSION['ccms_admin'];
    }
    return $admin;
}

function ccms_current_user(): ?array
{
    return ccms_current_admin();
}

function ccms_require_admin(): array
{
    $admin = ccms_current_admin();
    if (!$admin) {
        ccms_redirect('/r-admin/');
    }
    return $admin;
}

function ccms_require_role(array $roles): array
{
    $user = ccms_require_admin();
    if (!in_array((string) ($user['role'] ?? ''), $roles, true)) {
        throw new RuntimeException('No tienes permisos para realizar esta acción.');
    }
    return $user;
}

function ccms_user_can(string $capability): bool
{
    $user = ccms_current_user();
    if (!$user) {
        return false;
    }
    $role = (string) ($user['role'] ?? 'viewer');
    $matrix = [
        'owner' => ['site_manage', 'users_manage', 'pages_manage', 'media_manage', 'import_capsules', 'ai_generate'],
        'editor' => ['pages_manage', 'media_manage', 'import_capsules', 'ai_generate'],
        'viewer' => [],
    ];
    return in_array($capability, $matrix[$role] ?? [], true);
}

function ccms_require_capability(string $capability): array
{
    $user = ccms_require_admin();
    if (!ccms_user_can($capability)) {
        throw new RuntimeException('No tienes permisos para realizar esta acción.');
    }
    return $user;
}

function ccms_find_user(array $data, string $id): ?array
{
    foreach (($data['users'] ?? []) as $user) {
        if (($user['id'] ?? '') === $id) {
            return $user;
        }
    }
    return null;
}

function ccms_find_user_index(array $data, string $id): ?int
{
    foreach (($data['users'] ?? []) as $index => $user) {
        if (($user['id'] ?? '') === $id) {
            return $index;
        }
    }
    return null;
}

function ccms_login(string $username, string $password): bool
{
    $data = ccms_load_data();
    $matchedUser = null;
    foreach (($data['users'] ?? []) as $user) {
        if (($user['username'] ?? '') === $username || ($user['email'] ?? '') === $username) {
            $matchedUser = $user;
            break;
        }
    }
    if (!$matchedUser) {
        $admin = $data['admin'] ?? [];
        if (($admin['username'] ?? '') === $username || ($admin['email'] ?? '') === $username) {
            $matchedUser = $admin + ['role' => 'owner'];
        }
    }
    if (!$matchedUser || !password_verify($password, (string) ($matchedUser['password_hash'] ?? ''))) {
        return false;
    }
    ccms_start_session();
    session_regenerate_id(true);
    $_SESSION['ccms_admin'] = [
        'id' => $matchedUser['id'] ?? '',
        'username' => $matchedUser['username'] ?? '',
        'email' => $matchedUser['email'] ?? '',
        'role' => $matchedUser['role'] ?? 'owner',
    ];
    ccms_csrf_token();
    return true;
}

function ccms_logout(): void
{
    ccms_start_session();
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 3600, $params['path'], $params['domain'], (bool) $params['secure'], (bool) $params['httponly']);
    }
    session_destroy();
}
