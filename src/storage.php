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

function ccms_default_onboarding_state(): array
{
    return [
        'dismissed' => false,
        'completed' => false,
        'completed_at' => null,
        'exported_at' => null,
        'last_step' => '',
    ];
}

function ccms_business_hours_day_keys(): array
{
    return ['mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun'];
}

function ccms_live_data_slot_types(): array
{
    return ['menu_daily', 'hours_status', 'price_list'];
}

function ccms_default_business_hours_payload(): array
{
    $days = [];
    foreach (ccms_business_hours_day_keys() as $day) {
        $days[$day] = [
            'closed' => $day === 'sun',
            'slots' => [],
        ];
    }

    return [
        'timezone' => (string) date_default_timezone_get(),
        'closed_today' => false,
        'closure_label' => '',
        'reopens_on' => '',
        'days' => $days,
    ];
}

function ccms_live_data_default_payload(string $type): array
{
    return match ($type) {
        'menu_daily' => [
            'price' => '',
            'currency' => 'EUR',
            'includes' => '',
            'sections' => [],
        ],
        'hours_status' => ccms_default_business_hours_payload(),
        'price_list' => [
            'currency' => 'EUR',
            'note' => '',
            'items' => [],
        ],
        default => [],
    };
}

function ccms_normalize_live_data_payload(string $type, $payload): array
{
    $payload = is_array($payload) ? $payload : [];

    if ($type === 'menu_daily') {
        $sections = [];
        foreach ((array) ($payload['sections'] ?? []) as $section) {
            if (!is_array($section)) {
                continue;
            }
            $name = trim((string) ($section['name'] ?? ''));
            $items = array_values(array_filter(array_map(static function ($item): string {
                return trim((string) $item);
            }, is_array($section['items'] ?? null) ? $section['items'] : []), static function (string $item): bool {
                return $item !== '';
            }));
            if ($name === '' && $items === []) {
                continue;
            }
            $sections[] = [
                'name' => $name !== '' ? $name : 'Sección',
                'items' => $items,
            ];
        }

        return [
            'price' => trim((string) ($payload['price'] ?? '')),
            'currency' => strtoupper(substr(trim((string) ($payload['currency'] ?? 'EUR')) ?: 'EUR', 0, 3)),
            'includes' => trim((string) ($payload['includes'] ?? '')),
            'sections' => $sections,
        ];
    }

    if ($type === 'hours_status') {
        $days = [];
        foreach (ccms_business_hours_day_keys() as $day) {
            $rawDay = is_array($payload['days'][$day] ?? null) ? $payload['days'][$day] : [];
            $slots = [];
            foreach ((array) ($rawDay['slots'] ?? []) as $slot) {
                if (!is_array($slot)) {
                    continue;
                }
                $open = trim((string) ($slot['open'] ?? ''));
                $close = trim((string) ($slot['close'] ?? ''));
                if ($open === '' && $close === '') {
                    continue;
                }
                $slots[] = [
                    'open' => $open,
                    'close' => $close,
                ];
            }
            $days[$day] = [
                'closed' => !empty($rawDay['closed']) || $slots === [],
                'slots' => $slots,
            ];
        }

        return [
            'timezone' => trim((string) ($payload['timezone'] ?? date_default_timezone_get())) ?: (string) date_default_timezone_get(),
            'closed_today' => !empty($payload['closed_today']),
            'closure_label' => trim((string) ($payload['closure_label'] ?? '')),
            'reopens_on' => trim((string) ($payload['reopens_on'] ?? '')),
            'days' => $days,
        ];
    }

    if ($type === 'price_list') {
        $items = [];
        foreach ((array) ($payload['items'] ?? []) as $item) {
            if (!is_array($item)) {
                continue;
            }
            $name = trim((string) ($item['name'] ?? ''));
            $price = trim((string) ($item['price'] ?? ''));
            $detail = trim((string) ($item['detail'] ?? ''));
            if ($name === '' && $price === '' && $detail === '') {
                continue;
            }
            $items[] = [
                'name' => $name,
                'price' => $price,
                'detail' => $detail,
            ];
        }

        return [
            'currency' => strtoupper(substr(trim((string) ($payload['currency'] ?? 'EUR')) ?: 'EUR', 0, 3)),
            'note' => trim((string) ($payload['note'] ?? '')),
            'items' => $items,
        ];
    }

    return [];
}

function ccms_normalize_live_data_structure($value): array
{
    $payload = is_array($value) ? $value : [];
    $payload['slots'] = is_array($payload['slots'] ?? null) ? $payload['slots'] : [];

    $slots = [];
    foreach ($payload['slots'] as $slotKey => $slot) {
        if (!is_array($slot)) {
            continue;
        }
        $key = trim((string) $slotKey);
        if ($key === '') {
            $key = trim((string) ($slot['slot'] ?? ''));
        }
        if ($key === '') {
            continue;
        }
        $type = trim((string) ($slot['type'] ?? ''));
        if (!in_array($type, ccms_live_data_slot_types(), true)) {
            continue;
        }
        $slots[$key] = [
            'type' => $type,
            'updated_at' => ($slot['updated_at'] ?? null) ? (string) $slot['updated_at'] : null,
            'payload' => ccms_normalize_live_data_payload($type, $slot['payload'] ?? []),
        ];
    }

    return ['slots' => $slots];
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
            'white_label_enabled' => false,
            'admin_brand_name' => '',
            'admin_brand_tagline' => '',
            'admin_logo_url' => '',
            'analytics_provider' => '',
            'analytics_id' => '',
            'theme_preset' => 'warm',
            'font_pairing' => 'auto',
            'custom_css' => '',
            'onboarding' => ccms_default_onboarding_state(),
            'trusted_plugins_enabled' => false,
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
        'posts' => [],
        'media' => [],
        'live_data' => [
            'slots' => [],
        ],
        'submissions' => [],
        'audit_logs' => [],
        'password_reset_tokens' => [],
    ];
}

function ccms_normalize_submissions(array $data): array
{
    $data['submissions'] = array_values(array_filter(array_map(static function ($entry) {
        if (!is_array($entry)) {
            return null;
        }
        $fields = [];
        foreach ((array) ($entry['fields'] ?? []) as $key => $value) {
            $fieldKey = trim((string) $key);
            if ($fieldKey === '') {
                continue;
            }
            if (is_scalar($value) || $value === null) {
                $fields[$fieldKey] = trim((string) $value);
            }
        }
        return [
            'id' => (string) ($entry['id'] ?? ccms_next_id('sub')),
            'kind' => trim((string) ($entry['kind'] ?? 'lead_form')),
            'status' => in_array(($entry['status'] ?? 'new'), ['new', 'reviewed', 'contacted', 'archived'], true) ? (string) $entry['status'] : 'new',
            'created_at' => (string) ($entry['created_at'] ?? ccms_now_iso()),
            'updated_at' => (string) ($entry['updated_at'] ?? ($entry['created_at'] ?? ccms_now_iso())),
            'page_id' => trim((string) ($entry['page_id'] ?? '')),
            'page_slug' => trim((string) ($entry['page_slug'] ?? '')),
            'page_title' => trim((string) ($entry['page_title'] ?? '')),
            'block_id' => trim((string) ($entry['block_id'] ?? '')),
            'block_type' => trim((string) ($entry['block_type'] ?? '')),
            'source_url' => trim((string) ($entry['source_url'] ?? '')),
            'delivery' => [
                'attempted' => !empty($entry['delivery']['attempted']),
                'sent' => !empty($entry['delivery']['sent']),
                'channel' => trim((string) ($entry['delivery']['channel'] ?? 'mail')),
                'target' => trim((string) ($entry['delivery']['target'] ?? '')),
            ],
            'meta' => [
                'ip' => trim((string) ($entry['meta']['ip'] ?? '')),
                'user_agent' => trim((string) ($entry['meta']['user_agent'] ?? '')),
            ],
            'fields' => $fields,
        ];
    }, is_array($data['submissions'] ?? null) ? $data['submissions'] : []), static function ($entry): bool {
        return is_array($entry) && $entry['id'] !== '';
    }));

    return $data;
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

function ccms_parse_taxonomy_input($value): array
{
    $items = [];
    if (is_string($value)) {
        $value = preg_split('/[,\\n]+/', $value) ?: [];
    }
    foreach ((array) $value as $entry) {
        $label = trim((string) $entry);
        if ($label === '') {
            continue;
        }
        $items[] = $label;
    }
    $items = array_values(array_unique($items));
    sort($items, SORT_NATURAL | SORT_FLAG_CASE);
    return $items;
}

function ccms_record_publish_timestamp(array $record): ?int
{
    $publishedAt = trim((string) ($record['published_at'] ?? ''));
    if ($publishedAt === '') {
        return null;
    }
    $timestamp = strtotime($publishedAt);
    return $timestamp === false ? null : $timestamp;
}

function ccms_record_is_public(array $record, ?int $now = null): bool
{
    $status = (string) ($record['status'] ?? 'draft');
    if ($status === 'published') {
        return true;
    }
    if ($status !== 'scheduled') {
        return false;
    }
    $publishAt = ccms_record_publish_timestamp($record);
    if ($publishAt === null) {
        return false;
    }
    return $publishAt <= ($now ?? time());
}

function ccms_normalize_pages(array $data): array
{
    $data['pages'] = array_values(array_filter(array_map(static function ($page) {
        if (!is_array($page)) {
            return null;
        }
        $title = trim((string) ($page['title'] ?? ''));
        $slugSource = trim((string) ($page['slug'] ?? $title));
        if ($title === '' && $slugSource === '') {
            return null;
        }
        $status = (string) ($page['status'] ?? 'draft');
        if (!in_array($status, ['draft', 'published', 'scheduled'], true)) {
            $status = 'draft';
        }
        $publishedAt = trim((string) ($page['published_at'] ?? ''));
        if ($status === 'published' && $publishedAt === '') {
            $publishedAt = (string) ($page['updated_at'] ?? $page['created_at'] ?? ccms_now_iso());
        }
        return [
            'id' => (string) ($page['id'] ?? ccms_next_id('page')),
            'title' => $title !== '' ? $title : 'Untitled page',
            'slug' => ccms_slugify($slugSource !== '' ? $slugSource : $title),
            'status' => $status,
            'published_at' => $publishedAt,
            'is_homepage' => !empty($page['is_homepage']),
            'show_in_menu' => array_key_exists('show_in_menu', $page) ? !empty($page['show_in_menu']) : true,
            'menu_label' => trim((string) ($page['menu_label'] ?? $title)) ?: ($title !== '' ? $title : 'Untitled page'),
            'meta_title' => trim((string) ($page['meta_title'] ?? '')),
            'meta_description' => trim((string) ($page['meta_description'] ?? '')),
            'capsule_json' => (string) ($page['capsule_json'] ?? '{}'),
            'html_content' => ccms_sanitize_html((string) ($page['html_content'] ?? '')),
            'created_at' => (string) ($page['created_at'] ?? ccms_now_iso()),
            'updated_at' => (string) ($page['updated_at'] ?? ccms_now_iso()),
            'revisions' => array_values(is_array($page['revisions'] ?? null) ? $page['revisions'] : []),
        ];
    }, is_array($data['pages'] ?? null) ? $data['pages'] : []), static function ($page): bool {
        return is_array($page) && (string) ($page['slug'] ?? '') !== '';
    }));

    return $data;
}

function ccms_normalize_posts(array $data): array
{
    $data['posts'] = array_values(array_filter(array_map(static function ($post) {
        if (!is_array($post)) {
            return null;
        }
        $title = trim((string) ($post['title'] ?? ''));
        $slugSource = trim((string) ($post['slug'] ?? $title));
        if ($title === '' && $slugSource === '') {
            return null;
        }
        $status = (string) ($post['status'] ?? 'draft');
        if (!in_array($status, ['draft', 'published', 'scheduled'], true)) {
            $status = 'draft';
        }
        $publishedAt = trim((string) ($post['published_at'] ?? ''));
        if ($status === 'published' && $publishedAt === '') {
            $publishedAt = (string) ($post['updated_at'] ?? $post['created_at'] ?? ccms_now_iso());
        }
        return [
            'id' => (string) ($post['id'] ?? ccms_next_id('post')),
            'title' => $title !== '' ? $title : 'Untitled post',
            'slug' => ccms_slugify($slugSource !== '' ? $slugSource : $title),
            'status' => $status,
            'excerpt' => trim((string) ($post['excerpt'] ?? '')),
            'content_html' => ccms_sanitize_html((string) ($post['content_html'] ?? '')),
            'cover_image' => ccms_sanitize_url((string) ($post['cover_image'] ?? ''), true),
            'author_name' => trim((string) ($post['author_name'] ?? '')),
            'categories' => ccms_parse_taxonomy_input($post['categories'] ?? []),
            'tags' => ccms_parse_taxonomy_input($post['tags'] ?? []),
            'meta_title' => trim((string) ($post['meta_title'] ?? '')),
            'meta_description' => trim((string) ($post['meta_description'] ?? '')),
            'published_at' => $publishedAt,
            'created_at' => (string) ($post['created_at'] ?? ccms_now_iso()),
            'updated_at' => (string) ($post['updated_at'] ?? ccms_now_iso()),
        ];
    }, is_array($data['posts'] ?? null) ? $data['posts'] : []), static function ($post): bool {
        return is_array($post) && (string) ($post['slug'] ?? '') !== '';
    }));

    usort($data['posts'], static function (array $a, array $b): int {
        $aDate = (string) ($a['published_at'] ?? $a['updated_at'] ?? '');
        $bDate = (string) ($b['published_at'] ?? $b['updated_at'] ?? '');
        return strcmp($bDate, $aDate);
    });

    return $data;
}

function ccms_normalize_users(array $data): array
{
    $data = ccms_normalize_pages($data);
    $data['site'] ??= [];
    $data['site']['trusted_plugins_enabled'] = !empty($data['site']['trusted_plugins_enabled']);
    $data['site']['white_label_enabled'] = !empty($data['site']['white_label_enabled']);
    $data['site']['admin_brand_name'] = trim((string) ($data['site']['admin_brand_name'] ?? ''));
    $data['site']['admin_brand_tagline'] = trim((string) ($data['site']['admin_brand_tagline'] ?? ''));
    $data['site']['admin_logo_url'] = ccms_sanitize_url((string) ($data['site']['admin_logo_url'] ?? ''), true);
    $data['site']['analytics_provider'] = in_array(($data['site']['analytics_provider'] ?? ''), ['', 'ga4', 'plausible'], true)
        ? (string) ($data['site']['analytics_provider'] ?? '')
        : '';
    $data['site']['analytics_id'] = trim((string) ($data['site']['analytics_id'] ?? ''));
    $data['site']['theme_preset'] = ccms_normalize_theme_preset((string) ($data['site']['theme_preset'] ?? 'warm'));
    $data['site']['font_pairing'] = ccms_normalize_font_pairing((string) ($data['site']['font_pairing'] ?? 'auto'));
    $data['site']['onboarding'] = array_merge(
        ccms_default_onboarding_state(),
        is_array($data['site']['onboarding'] ?? null) ? $data['site']['onboarding'] : []
    );
    $data['site']['onboarding']['dismissed'] = !empty($data['site']['onboarding']['dismissed']);
    $data['site']['onboarding']['completed'] = !empty($data['site']['onboarding']['completed']);
    $data['site']['onboarding']['completed_at'] = ($data['site']['onboarding']['completed_at'] ?? null)
        ? (string) $data['site']['onboarding']['completed_at']
        : null;
    $data['site']['onboarding']['exported_at'] = ($data['site']['onboarding']['exported_at'] ?? null)
        ? (string) $data['site']['onboarding']['exported_at']
        : null;
    $data['site']['onboarding']['last_step'] = trim((string) ($data['site']['onboarding']['last_step'] ?? ''));
    $data['live_data'] = ccms_normalize_live_data_structure($data['live_data'] ?? []);
    $data['users'] = array_values(array_filter(array_map(static function ($user) {
        if (!is_array($user)) {
            return null;
        }
        return [
            'id' => (string) ($user['id'] ?? ccms_next_id('user')),
            'username' => trim((string) ($user['username'] ?? '')),
            'email' => trim((string) ($user['email'] ?? '')),
            'password_hash' => (string) ($user['password_hash'] ?? ''),
            'role' => ccms_normalize_user_role((string) ($user['role'] ?? 'editor')),
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

    return ccms_normalize_password_reset_tokens(ccms_normalize_posts($data));
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
            return ccms_normalize_submissions(ccms_normalize_users(ccms_load_sqlite_data()));
        }
        if (is_file(ccms_data_file())) {
            $data = ccms_normalize_submissions(ccms_normalize_users(ccms_load_json_data_file()));
            ccms_save_sqlite_data($data);
            return $data;
        }
        return ccms_normalize_submissions(ccms_normalize_users(ccms_default_data()));
    }
    return ccms_normalize_submissions(ccms_normalize_users(ccms_load_json_data_file()));
}

function ccms_save_data(array $data): void
{
    ccms_ensure_runtime_dirs();
    $data = ccms_normalize_submissions(ccms_normalize_users($data));
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

    $payloadData = is_array($payload['data']) ? $payload['data'] : [];
    $defaults = ccms_default_data();
    $data = $defaults;

    if (is_array($payloadData['site'] ?? null)) {
        $site = $payloadData['site'];
        $data['site']['title'] = trim((string) ($site['title'] ?? $data['site']['title'])) ?: $data['site']['title'];
        $data['site']['tagline'] = trim((string) ($site['tagline'] ?? $data['site']['tagline']));
        $data['site']['footer_text'] = trim((string) ($site['footer_text'] ?? $data['site']['footer_text']));
        $data['site']['contact_email'] = trim((string) ($site['contact_email'] ?? $data['site']['contact_email']));
        $data['site']['white_label_enabled'] = !empty($site['white_label_enabled']);
        $data['site']['admin_brand_name'] = trim((string) ($site['admin_brand_name'] ?? ''));
        $data['site']['admin_brand_tagline'] = trim((string) ($site['admin_brand_tagline'] ?? ''));
        $data['site']['admin_logo_url'] = ccms_sanitize_url((string) ($site['admin_logo_url'] ?? ''), true);
        $data['site']['analytics_provider'] = in_array(($site['analytics_provider'] ?? ''), ['', 'ga4', 'plausible'], true)
            ? (string) ($site['analytics_provider'] ?? '')
            : '';
        $data['site']['analytics_id'] = trim((string) ($site['analytics_id'] ?? ''));
        $data['site']['theme_preset'] = ccms_normalize_theme_preset((string) ($site['theme_preset'] ?? $data['site']['theme_preset']));
        $data['site']['font_pairing'] = ccms_normalize_font_pairing((string) ($site['font_pairing'] ?? $data['site']['font_pairing']));
        $data['site']['custom_css'] = ccms_sanitize_css((string) ($site['custom_css'] ?? ''));
        if (is_array($site['onboarding'] ?? null)) {
            $onboarding = $site['onboarding'];
            $data['site']['onboarding'] = array_merge(
                ccms_default_onboarding_state(),
                [
                    'dismissed' => !empty($onboarding['dismissed']),
                    'completed' => !empty($onboarding['completed']),
                    'completed_at' => ($onboarding['completed_at'] ?? null) ? (string) $onboarding['completed_at'] : null,
                    'exported_at' => ($onboarding['exported_at'] ?? null) ? (string) $onboarding['exported_at'] : null,
                    'last_step' => trim((string) ($onboarding['last_step'] ?? '')),
                ]
            );
        }
        $data['site']['trusted_plugins_enabled'] = !empty($site['trusted_plugins_enabled']);
        $requestedPlugins = array_values(array_filter(array_map('strval', is_array($site['enabled_plugins'] ?? null) ? $site['enabled_plugins'] : [])));
        $availablePlugins = ccms_discover_plugins();
        $data['site']['enabled_plugins'] = array_values(array_filter($requestedPlugins, static function (string $slug) use ($availablePlugins): bool {
            return isset($availablePlugins[$slug]);
        }));
        if (is_array($site['colors'] ?? null)) {
            $data['site']['colors'] = array_merge($data['site']['colors'], array_intersect_key($site['colors'], $data['site']['colors']));
        }
    }

    if (is_array($payloadData['local_ai'] ?? null)) {
        $localAi = $payloadData['local_ai'];
        $data['local_ai']['endpoint'] = trim((string) ($localAi['endpoint'] ?? $data['local_ai']['endpoint']));
        $data['local_ai']['model'] = trim((string) ($localAi['model'] ?? $data['local_ai']['model']));
        $data['local_ai']['temperature'] = is_numeric($localAi['temperature'] ?? null) ? (float) $localAi['temperature'] : $data['local_ai']['temperature'];
        $data['local_ai']['max_tokens'] = is_numeric($localAi['max_tokens'] ?? null) ? (int) $localAi['max_tokens'] : $data['local_ai']['max_tokens'];
        $data['local_ai']['timeout'] = is_numeric($localAi['timeout'] ?? null) ? (int) $localAi['timeout'] : $data['local_ai']['timeout'];
    }

    $data['live_data'] = ccms_normalize_live_data_structure($payloadData['live_data'] ?? []);

    if (is_array($payloadData['admin'] ?? null)) {
        $data['admin'] = array_merge($data['admin'], array_intersect_key($payloadData['admin'], $data['admin']));
    }
    if (is_array($payloadData['users'] ?? null)) {
        $data['users'] = $payloadData['users'];
    }
    if (is_array($payloadData['pages'] ?? null)) {
        $data['pages'] = $payloadData['pages'];
    }
    if (is_array($payloadData['posts'] ?? null)) {
        $data['posts'] = $payloadData['posts'];
    }
    if (is_array($payloadData['media'] ?? null)) {
        $data['media'] = $payloadData['media'];
    }
    if (is_array($payloadData['submissions'] ?? null)) {
        $data['submissions'] = $payloadData['submissions'];
    }
    if (is_array($payloadData['audit_logs'] ?? null)) {
        $data['audit_logs'] = $payloadData['audit_logs'];
    }
    if (is_array($payloadData['password_reset_tokens'] ?? null)) {
        $data['password_reset_tokens'] = $payloadData['password_reset_tokens'];
    }

    $data = ccms_normalize_submissions(ccms_normalize_users($data));

    $uploadsDir = ccms_uploads_dir();
    $backupDir = $uploadsDir . '_backup_' . gmdate('Ymd_His');
    $hasUploadFiles = false;
    foreach (scandir($uploadsDir) ?: [] as $entry) {
        if ($entry === '.' || $entry === '..') {
            continue;
        }
        $path = $uploadsDir . DIRECTORY_SEPARATOR . $entry;
        if (is_file($path)) {
            $hasUploadFiles = true;
            break;
        }
    }
    if ($hasUploadFiles) {
        @mkdir($backupDir, 0775, true);
        foreach (scandir($uploadsDir) ?: [] as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }
            $path = $uploadsDir . DIRECTORY_SEPARATOR . $entry;
            if (!is_file($path)) {
                continue;
            }
            @copy($path, $backupDir . DIRECTORY_SEPARATOR . $entry);
            @unlink($path);
        }
    }

    foreach (scandir($uploadsDir) ?: [] as $entry) {
        if ($entry === '.' || $entry === '..') {
            continue;
        }
        $path = $uploadsDir . DIRECTORY_SEPARATOR . $entry;
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
        file_put_contents($uploadsDir . DIRECTORY_SEPARATOR . $filename, $decoded);
    }

    return $data;
}

function ccms_load_page_by_slug(string $slug): ?array
{
    $data = ccms_load_data();
    foreach (($data['pages'] ?? []) as $page) {
        if (($page['slug'] ?? '') === $slug) {
            return $page;
        }
    }
    return null;
}

function ccms_load_post_by_slug(string $slug): ?array
{
    $data = ccms_load_data();
    foreach (($data['posts'] ?? []) as $post) {
        if (($post['slug'] ?? '') === $slug) {
            return $post;
        }
    }
    return null;
}

function ccms_load_site_config(): array
{
    $data = ccms_load_data();
    return is_array($data['site'] ?? null) ? $data['site'] : [];
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

function ccms_find_post(array $data, string $id): ?array
{
    foreach ($data['posts'] ?? [] as $post) {
        if (($post['id'] ?? '') === $id) {
            return $post;
        }
    }
    return null;
}

function ccms_find_post_index(array $data, string $id): ?int
{
    foreach ($data['posts'] ?? [] as $index => $post) {
        if (($post['id'] ?? '') === $id) {
            return $index;
        }
    }
    return null;
}

function ccms_homepage(array $data): ?array
{
    foreach ($data['pages'] as $page) {
        if (!empty($page['is_homepage']) && ccms_record_is_public($page)) {
            return $page;
        }
    }
    foreach ($data['pages'] as $page) {
        if (ccms_record_is_public($page)) {
            return $page;
        }
    }
    return null;
}

function ccms_page_by_slug(array $data, string $slug): ?array
{
    foreach ($data['pages'] as $page) {
        if (($page['slug'] ?? '') === $slug && ccms_record_is_public($page)) {
            return $page;
        }
    }
    return null;
}

function ccms_menu_pages(array $data): array
{
    $pages = array_values(array_filter($data['pages'], static function (array $page): bool {
        return ccms_record_is_public($page) && !empty($page['show_in_menu']);
    }));
    usort($pages, static function (array $a, array $b): int {
        return strcmp((string) ($a['title'] ?? ''), (string) ($b['title'] ?? ''));
    });
    return $pages;
}

function ccms_posts_published(array $data): array
{
    $posts = array_values(array_filter($data['posts'] ?? [], static function (array $post): bool {
        return ccms_record_is_public($post);
    }));
    usort($posts, static function (array $a, array $b): int {
        $aDate = (string) ($a['published_at'] ?? $a['updated_at'] ?? '');
        $bDate = (string) ($b['published_at'] ?? $b['updated_at'] ?? '');
        return strcmp($bDate, $aDate);
    });
    return $posts;
}

function ccms_post_by_slug(array $data, string $slug): ?array
{
    foreach (ccms_posts_published($data) as $post) {
        if (($post['slug'] ?? '') === $slug) {
            return $post;
        }
    }
    return null;
}

function ccms_taxonomy_slug(string $label): string
{
    return ccms_slugify($label);
}

function ccms_posts_for_category_slug(array $data, string $categorySlug): array
{
    return array_values(array_filter(ccms_posts_published($data), static function (array $post) use ($categorySlug): bool {
        foreach ((array) ($post['categories'] ?? []) as $category) {
            if (ccms_taxonomy_slug((string) $category) === $categorySlug) {
                return true;
            }
        }
        return false;
    }));
}

function ccms_posts_for_tag_slug(array $data, string $tagSlug): array
{
    return array_values(array_filter(ccms_posts_published($data), static function (array $post) use ($tagSlug): bool {
        foreach ((array) ($post['tags'] ?? []) as $tag) {
            if (ccms_taxonomy_slug((string) $tag) === $tagSlug) {
                return true;
            }
        }
        return false;
    }));
}

function ccms_blog_categories(array $data): array
{
    $labels = [];
    foreach (ccms_posts_published($data) as $post) {
        foreach ((array) ($post['categories'] ?? []) as $category) {
            $category = trim((string) $category);
            if ($category !== '') {
                $labels[$category] = $category;
            }
        }
    }
    ksort($labels, SORT_NATURAL | SORT_FLAG_CASE);
    return array_values($labels);
}

function ccms_blog_tags(array $data): array
{
    $labels = [];
    foreach (ccms_posts_published($data) as $post) {
        foreach ((array) ($post['tags'] ?? []) as $tag) {
            $tag = trim((string) $tag);
            if ($tag !== '') {
                $labels[$tag] = $tag;
            }
        }
    }
    ksort($labels, SORT_NATURAL | SORT_FLAG_CASE);
    return array_values($labels);
}
