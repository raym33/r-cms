<?php
declare(strict_types=1);

require_once __DIR__ . '/storage.php';

function ccms_client_ip(): string
{
    return trim((string) ($_SERVER['REMOTE_ADDR'] ?? 'cli')) ?: 'cli';
}

function ccms_login_attempts_file(): string
{
    return ccms_root_path('data/login_attempts.json');
}

function ccms_load_login_attempts(): array
{
    $path = ccms_login_attempts_file();
    if (!is_file($path)) {
        return [];
    }
    $decoded = json_decode((string) file_get_contents($path), true);
    return is_array($decoded) ? $decoded : [];
}

function ccms_save_login_attempts(array $payload): void
{
    file_put_contents(
        ccms_login_attempts_file(),
        json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
    );
}

function ccms_login_throttle_key(string $username, string $ip): string
{
    return sha1(strtolower(trim($username)) . '|' . trim($ip));
}

function ccms_cleanup_login_attempts(array $payload, int $windowSeconds = 900): array
{
    $now = time();
    foreach ($payload as $key => $row) {
        $last = (int) ($row['last_attempt_at'] ?? 0);
        if ($last > 0 && ($now - $last) > $windowSeconds && empty($row['locked_until'])) {
            unset($payload[$key]);
        }
        if (!empty($row['locked_until']) && (int) $row['locked_until'] <= $now && ($now - $last) > $windowSeconds) {
            unset($payload[$key]);
        }
    }
    return $payload;
}

function ccms_assert_login_allowed(string $username, string $ip): void
{
    $attempts = ccms_cleanup_login_attempts(ccms_load_login_attempts());
    $key = ccms_login_throttle_key($username, $ip);
    $row = $attempts[$key] ?? null;
    $lockedUntil = (int) ($row['locked_until'] ?? 0);
    if ($lockedUntil > time()) {
        $remaining = max(1, $lockedUntil - time());
        throw new RuntimeException('Demasiados intentos fallidos. Espera ' . $remaining . ' segundos antes de volver a intentarlo.');
    }
}

function ccms_register_login_failure(string $username, string $ip): void
{
    $attempts = ccms_cleanup_login_attempts(ccms_load_login_attempts());
    $key = ccms_login_throttle_key($username, $ip);
    $row = $attempts[$key] ?? ['count' => 0];
    $count = (int) ($row['count'] ?? 0) + 1;
    $lockedUntil = $count >= 5 ? time() + 900 : 0;
    $attempts[$key] = [
        'count' => $count,
        'last_attempt_at' => time(),
        'locked_until' => $lockedUntil,
    ];
    ccms_save_login_attempts($attempts);
}

function ccms_clear_login_attempts(string $username, string $ip): void
{
    $attempts = ccms_cleanup_login_attempts(ccms_load_login_attempts());
    $key = ccms_login_throttle_key($username, $ip);
    if (isset($attempts[$key])) {
        unset($attempts[$key]);
        ccms_save_login_attempts($attempts);
    }
}

function ccms_start_session(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
        if (!headers_sent()) {
            session_name('ccms_session');
            session_set_cookie_params([
                'lifetime' => 0,
                'path' => '/',
                'domain' => '',
                'secure' => $secure,
                'httponly' => true,
                'samesite' => 'Lax',
            ]);
        }
        session_start();
        $_SESSION['ccms_last_seen'] = time();
    } elseif (isset($_SESSION['ccms_last_seen']) && (time() - (int) $_SESSION['ccms_last_seen']) > 7200) {
        $_SESSION = [];
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
        if (!headers_sent()) {
            session_name('ccms_session');
            session_set_cookie_params([
                'lifetime' => 0,
                'path' => '/',
                'domain' => '',
                'secure' => false,
                'httponly' => true,
                'samesite' => 'Lax',
            ]);
        }
        session_start();
    } else {
        $_SESSION['ccms_last_seen'] = time();
    }
}

function ccms_verify_same_origin_request(): void
{
    $targetHost = strtolower(trim((string) ($_SERVER['HTTP_HOST'] ?? '')));
    if ($targetHost === '') {
        return;
    }

    foreach (['HTTP_ORIGIN', 'HTTP_REFERER'] as $header) {
        $value = trim((string) ($_SERVER[$header] ?? ''));
        if ($value === '') {
            continue;
        }
        $host = strtolower((string) parse_url($value, PHP_URL_HOST));
        $port = (string) parse_url($value, PHP_URL_PORT);
        if ($host === '') {
            continue;
        }
        $candidate = $host . ($port !== '' ? ':' . $port : '');
        if ($candidate !== $targetHost && $host !== preg_replace('/:\d+$/', '', $targetHost)) {
            throw new RuntimeException('Origen no permitido para esta acción.');
        }
        return;
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
            'must_change_password' => !empty($current['must_change_password']),
            'last_login_at' => $current['last_login_at'] ?? null,
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
    ccms_assert_login_allowed($username, ccms_client_ip());
    $data = ccms_load_data();
    $matchedIndex = null;
    $matchedUser = null;
    foreach (($data['users'] ?? []) as $index => $user) {
        if (($user['username'] ?? '') === $username || ($user['email'] ?? '') === $username) {
            $matchedUser = $user;
            $matchedIndex = $index;
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
        ccms_register_login_failure($username, ccms_client_ip());
        return false;
    }
    ccms_start_session();
    if (session_status() === PHP_SESSION_ACTIVE && !headers_sent()) {
        session_regenerate_id(true);
    }
    ccms_clear_login_attempts($username, ccms_client_ip());
    if ($matchedIndex !== null) {
        $data['users'][$matchedIndex]['last_login_at'] = ccms_now_iso();
        $data['users'][$matchedIndex]['updated_at'] = ccms_now_iso();
        ccms_save_data($data);
        $matchedUser = $data['users'][$matchedIndex];
    }
    $_SESSION['ccms_admin'] = [
        'id' => $matchedUser['id'] ?? '',
        'username' => $matchedUser['username'] ?? '',
        'email' => $matchedUser['email'] ?? '',
        'role' => $matchedUser['role'] ?? 'owner',
        'must_change_password' => !empty($matchedUser['must_change_password']),
        'last_login_at' => $matchedUser['last_login_at'] ?? null,
    ];
    ccms_csrf_token();
    return true;
}

function ccms_logout(): void
{
    ccms_start_session();
    $_SESSION = [];
    if (!headers_sent() && ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 3600, $params['path'], $params['domain'], (bool) $params['secure'], (bool) $params['httponly']);
    }
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_destroy();
    }
}
