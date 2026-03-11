<?php
declare(strict_types=1);

require_once __DIR__ . '/helpers.php';

function ccms_data_dir(): string
{
    return ccms_root_path('data');
}

function ccms_uploads_dir(): string
{
    return ccms_root_path('uploads');
}

function ccms_data_file(): string
{
    return ccms_root_path('data/app.json');
}

function ccms_storage_config_file(): string
{
    return ccms_root_path('data/storage.json');
}

function ccms_sqlite_file(): string
{
    return ccms_root_path('data/app.sqlite');
}

function ccms_ensure_runtime_dirs(): void
{
    foreach ([ccms_data_dir(), ccms_uploads_dir()] as $dir) {
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }
    }
}

function ccms_storage_supports_sqlite(): bool
{
    return class_exists(PDO::class) && extension_loaded('pdo_sqlite');
}

function ccms_storage_driver(): string
{
    $envDriver = strtolower(trim((string) getenv('CCMS_STORAGE_DRIVER')));
    if (in_array($envDriver, ['json', 'sqlite'], true)) {
        return $envDriver;
    }

    $configPath = ccms_storage_config_file();
    if (is_file($configPath)) {
        $decoded = json_decode((string) file_get_contents($configPath), true);
        $configured = strtolower(trim((string) ($decoded['driver'] ?? '')));
        if (in_array($configured, ['json', 'sqlite'], true)) {
            return $configured;
        }
    }

    if (is_file(ccms_sqlite_file())) {
        return 'sqlite';
    }

    return 'json';
}

function ccms_set_storage_driver(string $driver): void
{
    $driver = strtolower(trim($driver));
    if (!in_array($driver, ['json', 'sqlite'], true)) {
        throw new RuntimeException('Storage driver not supported.');
    }
    if ($driver === 'sqlite' && !ccms_storage_supports_sqlite()) {
        throw new RuntimeException('SQLite is not available in this PHP runtime.');
    }
    ccms_ensure_runtime_dirs();
    file_put_contents(
        ccms_storage_config_file(),
        json_encode(['driver' => $driver], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
    );
}

function ccms_default_data(): array
{
    return [
        'installed_at' => null,
        'site' => [
            'title' => 'LinuxCMS',
            'tagline' => 'A local website studio with capsules, builder and CMS in one place.',
            'footer_text' => 'Powered by LinuxCMS',
            'contact_email' => '',
            'theme_preset' => 'warm',
            'custom_css' => '',
            'enabled_plugins' => [],
            'colors' => [
                'bg' => '#f7f4ee',
                'surface' => '#ffffff',
                'text' => '#2f241f',
                'muted' => '#6b5b53',
                'primary' => '#c86f5c',
                'secondary' => '#d9c4b3',
            ],
        ],
        'local_ai' => [
            'endpoint' => 'http://127.0.0.1:1234/v1',
            'model' => '',
            'temperature' => 0.2,
            'max_tokens' => 2800,
            'timeout' => 20,
        ],
        'admin' => [
            'id' => null,
            'username' => '',
            'email' => '',
            'password_hash' => '',
            'created_at' => null,
        ],
        'users' => [],
        'pages' => [],
        'media' => [],
        'audit_logs' => [],
        'password_reset_tokens' => [],
    ];
}

function ccms_normalize_password_reset_tokens(array $data): array
{
    $data['password_reset_tokens'] = array_values(array_filter(array_map(static function ($entry) {
        if (!is_array($entry) || trim((string) ($entry['token'] ?? '')) === '') {
            return null;
        }
        return [
            'id' => (string) ($entry['id'] ?? ccms_next_id('reset')),
            'token' => trim((string) ($entry['token'] ?? '')),
            'user_id' => trim((string) ($entry['user_id'] ?? '')),
            'created_at' => (string) ($entry['created_at'] ?? ccms_now_iso()),
            'expires_at' => (string) ($entry['expires_at'] ?? ccms_now_iso()),
            'used_at' => ($entry['used_at'] ?? null) ? (string) $entry['used_at'] : null,
            'created_by' => [
                'id' => trim((string) ($entry['created_by']['id'] ?? '')),
                'username' => trim((string) ($entry['created_by']['username'] ?? '')),
            ],
        ];
    }, is_array($data['password_reset_tokens'] ?? null) ? $data['password_reset_tokens'] : []), static function ($entry): bool {
        return is_array($entry) && $entry['token'] !== '';
    }));

    return $data;
}

function ccms_normalize_users(array $data): array
{
    $data['users'] = array_values(array_filter(array_map(static function ($user) {
        if (!is_array($user)) {
            return null;
        }
        return [
            'id' => (string) ($user['id'] ?? ccms_next_id('user')),
            'username' => trim((string) ($user['username'] ?? '')),
            'email' => trim((string) ($user['email'] ?? '')),
            'password_hash' => (string) ($user['password_hash'] ?? ''),
            'role' => in_array(($user['role'] ?? 'editor'), ['owner', 'editor', 'viewer'], true) ? (string) $user['role'] : 'editor',
            'must_change_password' => !empty($user['must_change_password']),
            'last_login_at' => ($user['last_login_at'] ?? null) ? (string) $user['last_login_at'] : null,
            'totp_secret' => trim((string) ($user['totp_secret'] ?? '')),
            'totp_enabled' => !empty($user['totp_enabled']),
            'created_at' => (string) ($user['created_at'] ?? ccms_now_iso()),
            'updated_at' => (string) ($user['updated_at'] ?? ccms_now_iso()),
        ];
    }, is_array($data['users'] ?? null) ? $data['users'] : []), static function ($user): bool {
        return is_array($user) && $user['username'] !== '' && $user['password_hash'] !== '';
    }));

    if (empty($data['users']) && is_array($data['admin'] ?? null) && (string) ($data['admin']['username'] ?? '') !== '') {
        $data['users'][] = [
            'id' => (string) ($data['admin']['id'] ?? ccms_next_id('user')),
            'username' => trim((string) ($data['admin']['username'] ?? '')),
            'email' => trim((string) ($data['admin']['email'] ?? '')),
            'password_hash' => (string) ($data['admin']['password_hash'] ?? ''),
            'role' => 'owner',
            'must_change_password' => false,
            'last_login_at' => null,
            'totp_secret' => '',
            'totp_enabled' => false,
            'created_at' => (string) ($data['admin']['created_at'] ?? ccms_now_iso()),
            'updated_at' => (string) ($data['admin']['created_at'] ?? ccms_now_iso()),
        ];
    }

    if (!empty($data['users'])) {
        $ownerExists = false;
        foreach ($data['users'] as $index => $user) {
            if (($user['role'] ?? '') === 'owner') {
                $ownerExists = true;
                break;
            }
        }
        if (!$ownerExists) {
            $data['users'][0]['role'] = 'owner';
        }
        $data['admin'] = [
            'id' => $data['users'][0]['id'],
            'username' => $data['users'][0]['username'],
            'email' => $data['users'][0]['email'],
            'password_hash' => $data['users'][0]['password_hash'],
            'created_at' => $data['users'][0]['created_at'],
        ];
    }

    return ccms_normalize_password_reset_tokens($data);
}

function ccms_audit_log_entry(string $action, string $label, ?array $user = null, array $meta = []): array
{
    return [
        'id' => ccms_next_id('audit'),
        'action' => $action,
        'label' => $label,
        'created_at' => ccms_now_iso(),
        'user' => [
            'id' => (string) ($user['id'] ?? ''),
            'username' => (string) ($user['username'] ?? ''),
            'role' => (string) ($user['role'] ?? ''),
        ],
        'meta' => $meta,
    ];
}

function ccms_push_audit_log(array &$data, string $action, string $label, ?array $user = null, array $meta = [], int $max = 250): void
{
    $data['audit_logs'] ??= [];
    array_unshift($data['audit_logs'], ccms_audit_log_entry($action, $label, $user, $meta));
    if (count($data['audit_logs']) > $max) {
        $data['audit_logs'] = array_slice($data['audit_logs'], 0, $max);
    }
}

function ccms_page_snapshot(array $page, string $label = 'Manual save'): array
{
    $copy = $page;
    unset($copy['revisions']);
    return [
        'id' => ccms_next_id('rev'),
        'label' => $label,
        'saved_at' => ccms_now_iso(),
        'page' => $copy,
    ];
}

function ccms_push_page_revision(array &$page, string $label = 'Manual save', int $max = 20): void
{
    $page['revisions'] ??= [];
    array_unshift($page['revisions'], ccms_page_snapshot($page, $label));
    if (count($page['revisions']) > $max) {
        $page['revisions'] = array_slice($page['revisions'], 0, $max);
    }
}

function ccms_sqlite_pdo(): PDO
{
    if (!ccms_storage_supports_sqlite()) {
        throw new RuntimeException('SQLite driver is not available.');
    }
    $pdo = new PDO('sqlite:' . ccms_sqlite_file());
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    return $pdo;
}

function ccms_sqlite_ensure_schema(PDO $pdo): void
{
    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS app_state (
            storage_key TEXT PRIMARY KEY,
            payload TEXT NOT NULL,
            updated_at TEXT NOT NULL
        )'
    );
}

function ccms_sqlite_has_install(): bool
{
    if (!ccms_storage_supports_sqlite() || !is_file(ccms_sqlite_file())) {
        return false;
    }
    $pdo = ccms_sqlite_pdo();
    ccms_sqlite_ensure_schema($pdo);
    $statement = $pdo->prepare('SELECT COUNT(*) FROM app_state WHERE storage_key = :key');
    $statement->execute([':key' => 'main']);
    return (int) $statement->fetchColumn() > 0;
}

function ccms_load_json_data_file(): array
{
    if (!is_file(ccms_data_file())) {
        return ccms_default_data();
    }
    $json = file_get_contents(ccms_data_file());
    if ($json === false || trim($json) === '') {
        return ccms_default_data();
    }
    $decoded = json_decode($json, true);
    if (!is_array($decoded)) {
        return ccms_default_data();
    }
    return ccms_normalize_users(array_replace_recursive(ccms_default_data(), $decoded));
}

function ccms_load_sqlite_data(): array
{
    $pdo = ccms_sqlite_pdo();
    ccms_sqlite_ensure_schema($pdo);
    $statement = $pdo->prepare('SELECT payload FROM app_state WHERE storage_key = :key LIMIT 1');
    $statement->execute([':key' => 'main']);
    $payload = $statement->fetchColumn();
    if (!is_string($payload) || trim($payload) === '') {
        return ccms_default_data();
    }
    $decoded = json_decode($payload, true);
    if (!is_array($decoded)) {
        return ccms_default_data();
    }
    return ccms_normalize_users(array_replace_recursive(ccms_default_data(), $decoded));
}

function ccms_save_json_data(array $data): void
{
    $path = ccms_data_file();
    $handle = fopen($path, 'c+');
    if ($handle === false) {
        throw new RuntimeException('Could not open data file for writing.');
    }
    if (!flock($handle, LOCK_EX)) {
        fclose($handle);
        throw new RuntimeException('Could not lock data file.');
    }
    ftruncate($handle, 0);
    rewind($handle);
    fwrite($handle, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    fflush($handle);
    flock($handle, LOCK_UN);
    fclose($handle);
}

function ccms_save_sqlite_data(array $data): void
{
    $pdo = ccms_sqlite_pdo();
    ccms_sqlite_ensure_schema($pdo);
    $payload = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    $statement = $pdo->prepare(
        'INSERT INTO app_state (storage_key, payload, updated_at)
         VALUES (:key, :payload, :updated_at)
         ON CONFLICT(storage_key) DO UPDATE SET payload = excluded.payload, updated_at = excluded.updated_at'
    );
    $statement->execute([
        ':key' => 'main',
        ':payload' => $payload,
        ':updated_at' => ccms_now_iso(),
    ]);
}

function ccms_is_installed(): bool
{
    ccms_ensure_runtime_dirs();
    $driver = ccms_storage_driver();
    if ($driver === 'sqlite') {
        return ccms_sqlite_has_install() || is_file(ccms_data_file());
    }
    return is_file(ccms_data_file());
}

function ccms_load_data(): array
{
    ccms_ensure_runtime_dirs();
    $driver = ccms_storage_driver();
    if ($driver === 'sqlite') {
        if (ccms_sqlite_has_install()) {
            return ccms_normalize_users(ccms_load_sqlite_data());
        }
        if (is_file(ccms_data_file())) {
            $data = ccms_normalize_users(ccms_load_json_data_file());
            ccms_save_sqlite_data($data);
            return $data;
        }
        return ccms_normalize_users(ccms_default_data());
    }
    return ccms_normalize_users(ccms_load_json_data_file());
}

function ccms_save_data(array $data): void
{
    ccms_ensure_runtime_dirs();
    $driver = ccms_storage_driver();
    if ($driver === 'sqlite') {
        ccms_save_sqlite_data($data);
        return;
    }
    ccms_save_json_data($data);
}

function ccms_storage_runtime_info(): array
{
    return [
        'driver' => ccms_storage_driver(),
        'sqlite_available' => ccms_storage_supports_sqlite(),
        'json_file' => ccms_data_file(),
        'sqlite_file' => ccms_sqlite_file(),
    ];
}

function ccms_export_backup_payload(array $data): array
{
    $files = [];
    $uploadsDir = ccms_uploads_dir();
    if (is_dir($uploadsDir)) {
        foreach (scandir($uploadsDir) ?: [] as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }
            $path = $uploadsDir . DIRECTORY_SEPARATOR . $entry;
            if (!is_file($path)) {
                continue;
            }
            $content = file_get_contents($path);
            if ($content === false) {
                continue;
            }
            $files[] = [
                'filename' => $entry,
                'content_base64' => base64_encode($content),
            ];
        }
    }

    return [
        'format' => 'linuxcms-backup',
        'version' => 1,
        'exported_at' => ccms_now_iso(),
        'storage_driver' => ccms_storage_driver(),
        'data' => $data,
        'uploads' => $files,
    ];
}

function ccms_import_backup_payload(array $payload): array
{
    if (($payload['format'] ?? '') !== 'linuxcms-backup') {
        throw new RuntimeException('Backup format not supported.');
    }
    if (!is_array($payload['data'] ?? null)) {
        throw new RuntimeException('Backup payload is missing site data.');
    }

    $data = ccms_normalize_users(array_replace_recursive(ccms_default_data(), $payload['data']));

    foreach (scandir(ccms_uploads_dir()) ?: [] as $entry) {
        if ($entry === '.' || $entry === '..') {
            continue;
        }
        $path = ccms_uploads_dir() . DIRECTORY_SEPARATOR . $entry;
        if (is_file($path)) {
            @unlink($path);
        }
    }

    foreach (($payload['uploads'] ?? []) as $file) {
        if (!is_array($file)) {
            continue;
        }
        $filename = basename((string) ($file['filename'] ?? ''));
        $encoded = (string) ($file['content_base64'] ?? '');
        if ($filename === '' || $encoded === '') {
            continue;
        }
        $decoded = base64_decode($encoded, true);
        if ($decoded === false) {
            continue;
        }
        file_put_contents(ccms_uploads_dir() . DIRECTORY_SEPARATOR . $filename, $decoded);
    }

    return $data;
}

function ccms_next_id(string $prefix): string
{
    return $prefix . '_' . bin2hex(random_bytes(6));
}

function ccms_find_page(array $data, string $id): ?array
{
    foreach ($data['pages'] as $page) {
        if (($page['id'] ?? '') === $id) {
            return $page;
        }
    }
    return null;
}

function ccms_find_page_index(array $data, string $id): ?int
{
    foreach ($data['pages'] as $index => $page) {
        if (($page['id'] ?? '') === $id) {
            return $index;
        }
    }
    return null;
}

function ccms_homepage(array $data): ?array
{
    foreach ($data['pages'] as $page) {
        if (!empty($page['is_homepage']) && ($page['status'] ?? 'draft') === 'published') {
            return $page;
        }
    }
    return $data['pages'][0] ?? null;
}

function ccms_page_by_slug(array $data, string $slug): ?array
{
    foreach ($data['pages'] as $page) {
        if (($page['slug'] ?? '') === $slug && ($page['status'] ?? 'draft') === 'published') {
            return $page;
        }
    }
    return null;
}

function ccms_menu_pages(array $data): array
{
    $pages = array_values(array_filter($data['pages'], static function (array $page): bool {
        return ($page['status'] ?? 'draft') === 'published' && !empty($page['show_in_menu']);
    }));
    usort($pages, static function (array $a, array $b): int {
        return strcmp((string) ($a['title'] ?? ''), (string) ($b['title'] ?? ''));
    });
    return $pages;
}
