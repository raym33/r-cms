<?php
declare(strict_types=1);

ob_start();

$GLOBALS['lc_assert_count'] = 0;

function lc_assert(bool $condition, string $label): void
{
    if (!$condition) {
        fwrite(STDERR, "FAILED: {$label}\n");
        exit(1);
    }
    $GLOBALS['lc_assert_count']++;
    echo '[' . str_pad((string) $GLOBALS['lc_assert_count'], 3, '0', STR_PAD_LEFT) . "] {$label}\n";
}

function lc_rrmdir(string $dir): void
{
    if (!is_dir($dir)) {
        return;
    }
    $items = scandir($dir);
    foreach ($items ?: [] as $item) {
        if ($item === '.' || $item === '..') {
            continue;
        }
        $path = $dir . DIRECTORY_SEPARATOR . $item;
        if (is_dir($path) && !is_link($path)) {
            lc_rrmdir($path);
        } else {
            @unlink($path);
        }
    }
    @rmdir($dir);
}

function lc_rcopy(string $src, string $dst): void
{
    @mkdir($dst, 0775, true);
    foreach (scandir($src) ?: [] as $item) {
        if ($item === '.' || $item === '..' || $item === '.git') {
            continue;
        }
        $from = $src . DIRECTORY_SEPARATOR . $item;
        $to = $dst . DIRECTORY_SEPARATOR . $item;
        if (is_dir($from) && !is_link($from)) {
            lc_rcopy($from, $to);
        } else {
            copy($from, $to);
        }
    }
}

function lc_capture_include(string $file): string
{
    ob_start();
    include $file;
    return (string) ob_get_clean();
}

$sourceRoot = realpath(__DIR__ . '/..');
$runtimeRoot = sys_get_temp_dir() . '/linuxcms_deep_test_runtime';
lc_rrmdir($runtimeRoot);
lc_rcopy((string) $sourceRoot, $runtimeRoot);
@unlink($runtimeRoot . '/data/app.json');
@unlink($runtimeRoot . '/data/app.sqlite');
@unlink($runtimeRoot . '/data/storage.json');
putenv('CCMS_ROOT=' . $runtimeRoot);

require $sourceRoot . '/src/bootstrap.php';

$checks = 0;

// Helpers and storage basics
lc_assert(is_dir(ccms_root_path()), 'runtime root exists');
lc_assert(str_contains(ccms_root_path('data'), 'linuxcms_deep_test_runtime'), 'root override works');
lc_assert(is_array(ccms_default_data()), 'default data returns array');
$defaults = ccms_default_data();
lc_assert(($defaults['site']['title'] ?? '') === 'LinuxCMS', 'default site title is LinuxCMS');
lc_assert(($defaults['site']['theme_preset'] ?? '') === 'warm', 'default site theme preset is warm');
lc_assert(($defaults['site']['font_pairing'] ?? '') === 'auto', 'default site font pairing is auto');
lc_assert(array_key_exists('custom_css', $defaults['site']), 'default site includes custom css');
lc_assert(is_array($defaults['site']['business_profile'] ?? null), 'default site includes business profile');
lc_assert(($defaults['site']['business_profile']['schema_enabled'] ?? null) === true, 'default business profile enables schema flag');
lc_assert(is_array($defaults['live_data'] ?? null), 'default data includes live_data');
lc_assert(is_array($defaults['live_data']['slots'] ?? null), 'default live_data includes slots');
lc_assert(array_key_exists('trusted_plugins_enabled', $defaults['site']), 'default site includes trusted plugins flag');
lc_assert(array_key_exists('white_label_enabled', $defaults['site']), 'default site includes white-label flag');
lc_assert(is_array($defaults['site']['enabled_plugins'] ?? null), 'default site includes enabled plugins array');
lc_assert(isset($defaults['local_ai']['endpoint']), 'default data includes local_ai settings');
lc_assert(ccms_storage_runtime_info()['driver'] === 'json', 'default storage driver is json');
lc_assert(is_bool(ccms_storage_runtime_info()['sqlite_available'] ?? null), 'storage runtime exposes sqlite availability flag');
lc_assert(ccms_slugify('Hello Premium World') === 'hello-premium-world', 'slugify normalizes titles');
lc_assert(str_contains(ccms_base_url(), '127.0.0.1'), 'base url helper uses current host');
lc_assert(str_contains(ccms_script_nonce_attr(), 'nonce="'), 'script nonce attribute is generated');

$sanitizedHtml = ccms_sanitize_html('<section><img src="x" onerror="alert(1)"><script>alert(1)</script><p style="color:red;background:url(https://evil.example.com/x)">Safe copy</p><a href="javascript:alert(1)" onclick="evil()">Bad</a><a href="/ok" onclick="evil()">Good</a></section>');
lc_assert(!str_contains($sanitizedHtml, 'onerror'), 'html sanitizer removes event handlers');
lc_assert(!str_contains($sanitizedHtml, '<script'), 'html sanitizer removes script tags');
lc_assert(!str_contains($sanitizedHtml, 'javascript:'), 'html sanitizer strips javascript links');
lc_assert(!str_contains($sanitizedHtml, 'url('), 'html sanitizer strips dangerous css urls');
lc_assert(str_contains($sanitizedHtml, 'href="/ok"'), 'html sanitizer preserves safe relative links');

$sanitizedCss = ccms_sanitize_css("body{background:url(https://evil.example.com/a)}\n.test{color:red}\n@import url(https://evil.example.com/x.css);");
lc_assert(!str_contains($sanitizedCss, 'url('), 'custom css sanitizer removes url');
lc_assert(!str_contains($sanitizedCss, '@import'), 'custom css sanitizer removes import');
lc_assert(str_contains($sanitizedCss, 'color:red'), 'custom css sanitizer preserves safe declarations');
$tmpPng = $runtimeRoot . '/tmp-test.png';
file_put_contents($tmpPng, base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAusB9sOtJkAAAAAASUVORK5CYII='));
$assetInfo = ccms_validate_uploaded_asset($tmpPng, 'tiny.png', filesize($tmpPng), [
    'png' => ['image/png'],
], 1024 * 1024);
lc_assert(($assetInfo['extension'] ?? '') === 'png', 'upload validator preserves png extension');
lc_assert(($assetInfo['mime'] ?? '') === 'image/png', 'upload validator detects png mime');
$uploadDemoPath = ccms_uploads_dir() . '/demo.png';
copy($tmpPng, $uploadDemoPath);
$generatedImageVariants = ccms_generate_image_variants('demo.png');
lc_assert(is_array($generatedImageVariants), 'image optimizer returns metadata array');
try {
    ccms_validate_uploaded_asset($tmpPng, 'vector.svg', filesize($tmpPng), [
        'png' => ['image/png'],
    ], 1024 * 1024);
    lc_assert(false, 'svg upload should be rejected');
} catch (Throwable $e) {
    lc_assert(str_contains($e->getMessage(), 'Formato no permitido'), 'svg upload is rejected by extension whitelist');
}
try {
    ccms_assert_payload_size(str_repeat('a', 32), 8, 'El backup');
    lc_assert(false, 'oversized payload should fail');
} catch (Throwable $e) {
    lc_assert(str_contains($e->getMessage(), 'supera el tamaño máximo'), 'payload size guard rejects oversized content');
}
$headFragment = ccms_sanitize_plugin_fragment('public_head_end', '<script>alert(1)</script><style>body{background:url(https://evil.example.com/x)} .ok{color:#123}</style><meta name="theme-color" content="#fff"><link rel="stylesheet" href="javascript:alert(1)">');
lc_assert(!str_contains($headFragment, '<script'), 'plugin head sanitizer removes script tags');
lc_assert(!str_contains($headFragment, 'url('), 'plugin head sanitizer strips dangerous css urls');
lc_assert(str_contains($headFragment, '<meta '), 'plugin head sanitizer preserves safe meta');
lc_assert(!str_contains($headFragment, 'javascript:'), 'plugin head sanitizer strips dangerous link href');
ccms_plugin_runtime_reset();
ccms_register_plugin_hook('evil_hook', static fn (): string => '<script>alert(1)</script>');
lc_assert(ccms_render_plugin_fragments('evil_hook', []) === '', 'invalid plugin hooks are ignored');

// Save and load JSON
$defaults['installed_at'] = ccms_now_iso();
$defaults['site']['title'] = 'Deep Test Site';
$defaults['admin'] = [
    'id' => ccms_next_id('admin'),
    'username' => 'owner',
    'email' => 'owner@example.com',
    'password_hash' => password_hash('PasswordDemo2026!', PASSWORD_DEFAULT),
    'created_at' => ccms_now_iso(),
];
$defaults['users'] = [[
    'id' => $defaults['admin']['id'],
    'username' => 'owner',
    'email' => 'owner@example.com',
    'password_hash' => $defaults['admin']['password_hash'],
    'role' => 'owner',
    'created_at' => $defaults['admin']['created_at'],
    'updated_at' => ccms_now_iso(),
]];
ccms_save_data($defaults);
$loaded = ccms_load_data();
lc_assert(($loaded['site']['title'] ?? '') === 'Deep Test Site', 'json save/load preserves site title');
lc_assert(count($loaded['users'] ?? []) === 1, 'json save/load preserves users');

// SQLite migration path
ccms_set_storage_driver('sqlite');
$sqliteLoaded = ccms_load_data();
lc_assert(($sqliteLoaded['site']['title'] ?? '') === 'Deep Test Site', 'sqlite migration preserves site title');
lc_assert(ccms_storage_runtime_info()['driver'] === 'sqlite', 'storage driver switches to sqlite');
ccms_set_storage_driver('json');
lc_assert(ccms_storage_runtime_info()['driver'] === 'json', 'storage driver can switch back to json');

// Auth and permissions
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
$_SERVER['HTTPS'] = '';
ccms_start_session();
lc_assert(session_status() === PHP_SESSION_ACTIVE, 'session starts correctly');
$csrf = ccms_csrf_token();
lc_assert($csrf !== '', 'csrf token generated');
$_POST['csrf_token'] = $csrf;
ccms_verify_csrf();
lc_assert(true, 'csrf verification passes for valid token');

$_SERVER['HTTP_HOST'] = '127.0.0.1:8088';
$_SERVER['HTTP_ORIGIN'] = 'http://127.0.0.1:8088';
ccms_verify_same_origin_request();
lc_assert(true, 'same-origin request passes');

$_SERVER['HTTP_ORIGIN'] = 'https://evil.example.com';
try {
    ccms_verify_same_origin_request();
    lc_assert(false, 'cross-origin request should fail');
} catch (Throwable $e) {
    lc_assert(str_contains($e->getMessage(), 'Origen no permitido'), 'cross-origin request is blocked');
}
$_SERVER['HTTP_ORIGIN'] = 'http://127.0.0.1:8088';
unset($_SERVER['HTTP_ORIGIN']);
$_SERVER['HTTP_REFERER'] = 'http://127.0.0.1:8088/r-admin/';
ccms_verify_same_origin_request();
lc_assert(true, 'same-origin referer also passes');
$_SERVER['HTTP_REFERER'] = 'https://evil.example.com/hack';
try {
    ccms_verify_same_origin_request();
    lc_assert(false, 'cross-origin referer should fail');
} catch (Throwable $e) {
    lc_assert(str_contains($e->getMessage(), 'Origen no permitido'), 'cross-origin referer is blocked');
}
$_SERVER['HTTP_ORIGIN'] = 'http://127.0.0.1:8088';
unset($_SERVER['HTTP_REFERER']);

$_POST['csrf_token'] = 'bad-token';
try {
    ccms_verify_csrf();
    lc_assert(false, 'csrf invalid token should fail');
} catch (Throwable $e) {
    lc_assert(str_contains($e->getMessage(), 'CSRF'), 'csrf invalid token throws');
}
$_POST['csrf_token'] = $csrf;

lc_assert(ccms_login('owner', 'PasswordDemo2026!') === true, 'owner login works');
lc_assert(ccms_current_admin()['username'] === 'owner', 'current admin resolves after login');
lc_assert(ccms_user_can('site_manage') === true, 'owner has site_manage');
lc_assert(ccms_user_can('users_manage') === true, 'owner has users_manage');
lc_assert(ccms_user_can('ai_generate') === true, 'owner has ai_generate');
ccms_logout();
lc_assert(ccms_current_admin() === null, 'logout clears current admin');

// TOTP flow
$data = ccms_load_data();
$ownerIndex = ccms_find_user_index($data, (string) $data['users'][0]['id']);
$ownerSecret = ccms_generate_totp_secret();
$data['users'][$ownerIndex]['totp_secret'] = $ownerSecret;
$data['users'][$ownerIndex]['totp_enabled'] = true;
ccms_save_data($data);
lc_assert(ccms_login('owner', 'PasswordDemo2026!') === true, 'owner login with 2fa first step works');
lc_assert(ccms_current_admin() === null, '2fa login does not create full session before code');
$pending = ccms_pending_2fa();
lc_assert(($pending['username'] ?? '') === 'owner', 'pending 2fa session is stored');
lc_assert(ccms_complete_pending_2fa('000000') === false, 'invalid 2fa code is rejected');
lc_assert(ccms_complete_pending_2fa(ccms_totp_code($ownerSecret)) === true, 'valid 2fa code completes login');
lc_assert(ccms_current_admin()['username'] === 'owner', '2fa completion restores admin session');
lc_assert(!empty(ccms_current_admin()['totp_enabled']), 'current admin reports totp enabled');
ccms_logout();
lc_assert(ccms_pending_2fa() === null, 'logout clears pending 2fa session');

$data = ccms_load_data();
$ownerIndex = ccms_find_user_index($data, (string) $data['users'][0]['id']);
$data['users'][$ownerIndex]['totp_secret'] = '';
$data['users'][$ownerIndex]['totp_enabled'] = false;
ccms_save_data($data);

// Login throttling
for ($i = 0; $i < 5; $i++) {
    $ok = ccms_login('owner', 'wrong-password');
    lc_assert($ok === false, 'invalid login attempt #' . ($i + 1) . ' fails');
}
try {
    ccms_login('owner', 'wrong-password');
    lc_assert(false, 'throttle should trigger on excessive attempts');
} catch (Throwable $e) {
    lc_assert(str_contains($e->getMessage(), 'Demasiados intentos'), 'throttle blocks repeated attempts');
}
ccms_clear_login_attempts('owner', ccms_client_ip());
lc_assert(ccms_login('owner@example.com', 'PasswordDemo2026!') === true, 'login by email works after clearing attempts');

// Generic request throttling
ccms_hit_rate_limit('deep_test_health', '127.0.0.1', 2, 60, 'limit reached');
ccms_hit_rate_limit('deep_test_health', '127.0.0.1', 2, 60, 'limit reached');
try {
    ccms_hit_rate_limit('deep_test_health', '127.0.0.1', 2, 60, 'limit reached');
    lc_assert(false, 'generic request throttle should trigger');
} catch (Throwable $e) {
    lc_assert(str_contains($e->getMessage(), 'limit reached'), 'generic request throttle blocks repeated hits');
}

// Add users and capability matrix
$data = ccms_load_data();
$data['users'][] = [
    'id' => ccms_next_id('user'),
    'username' => 'editor-user',
    'email' => 'editor@example.com',
    'password_hash' => password_hash('EditorPass2026!', PASSWORD_DEFAULT),
    'role' => 'editor',
    'must_change_password' => true,
    'created_at' => ccms_now_iso(),
    'updated_at' => ccms_now_iso(),
];
$data['users'][] = [
    'id' => ccms_next_id('user'),
    'username' => 'viewer-user',
    'email' => 'viewer@example.com',
    'password_hash' => password_hash('ViewerPass2026!', PASSWORD_DEFAULT),
    'role' => 'viewer',
    'created_at' => ccms_now_iso(),
    'updated_at' => ccms_now_iso(),
];
$data['users'][] = [
    'id' => ccms_next_id('user'),
    'username' => 'client-user',
    'email' => 'client@example.com',
    'password_hash' => password_hash('ClientPass2026!', PASSWORD_DEFAULT),
    'role' => 'client',
    'created_at' => ccms_now_iso(),
    'updated_at' => ccms_now_iso(),
];
ccms_save_data($data);
ccms_logout();
lc_assert(ccms_login('editor-user', 'EditorPass2026!') === true, 'editor login works');
lc_assert(!empty(ccms_current_admin()['must_change_password']), 'editor is flagged for forced password change');
lc_assert(ccms_user_can('pages_manage') === true, 'editor can manage pages');
lc_assert(ccms_user_can('media_manage') === true, 'editor can manage media');
lc_assert(ccms_user_can('users_manage') === false, 'editor cannot manage users');
lc_assert(ccms_user_can('ai_generate') === true, 'editor can generate with AI');
ccms_logout();
lc_assert(ccms_login('viewer-user', 'ViewerPass2026!') === true, 'viewer login works');
lc_assert(ccms_user_can('pages_manage') === false, 'viewer cannot manage pages');
lc_assert(ccms_user_can('media_manage') === false, 'viewer cannot manage media');
lc_assert(ccms_user_can('ai_generate') === false, 'viewer cannot generate with AI');
lc_assert(ccms_user_can('business_mode') === false, 'viewer cannot access business mode');
ccms_logout();
lc_assert(ccms_login('client-user', 'ClientPass2026!') === true, 'client login works');
lc_assert(ccms_user_can('pages_manage') === false, 'client cannot manage pages');
lc_assert(ccms_user_can('business_mode') === true, 'client can access business mode');
ccms_logout();
ccms_login('owner', 'PasswordDemo2026!');

// Password reset tokens
$data = ccms_load_data();
$editorUser = ccms_find_user($data, (string) $data['users'][1]['id']);
$resetToken = ccms_create_password_reset_token($data, $editorUser, ccms_current_admin());
lc_assert(strlen($resetToken) >= 20, 'password reset token generated');
lc_assert(ccms_find_valid_reset_token($data, $resetToken) !== null, 'valid password reset token can be found');
$consumedUser = ccms_consume_password_reset_token($data, $resetToken, 'EditorResetPass2026!');
lc_assert(($consumedUser['username'] ?? '') === 'editor-user', 'password reset token updates target user');
lc_assert(ccms_find_valid_reset_token($data, $resetToken) === null, 'used password reset token becomes invalid');
ccms_save_data($data);
ccms_logout();
lc_assert(ccms_login('editor-user', 'EditorResetPass2026!') === true, 'editor can login with reset password');
lc_assert(empty(ccms_current_admin()['must_change_password']), 'password reset clears forced password change');
ccms_logout();
ccms_login('owner', 'PasswordDemo2026!');

// AI generation helpers
$brief = [
    'business_name' => 'OTM Lawyers',
    'page_title' => 'Corporate Law for fast-moving businesses',
    'industry' => 'lawyer',
    'offer' => 'Corporate law, partner disputes and contract support for growing companies.',
    'audience' => 'Founders and SMEs that need a clear legal partner.',
    'goal' => 'Generate qualified leads and first calls.',
    'cta_text' => 'Book a consultation',
    'tone' => 'calm, premium and direct',
    'notes' => 'Include proof and contact sections.',
];
$fallback = ccms_ai_generate_payload($brief, ccms_ai_defaults());
lc_assert(($fallback['_meta']['mode'] ?? '') !== '', 'ai payload includes meta mode');
lc_assert(is_array($fallback['page']['capsule'] ?? null), 'ai payload includes capsule');
lc_assert(count($fallback['page']['capsule']['blocks'] ?? []) >= 6, 'ai fallback generates multiple blocks');
lc_assert(($fallback['site']['title'] ?? '') === 'OTM Lawyers', 'ai fallback uses business name');

$data = ccms_load_data();
$pageRecord = ccms_ai_page_record_from_payload($fallback, $data['pages'], true);
lc_assert(($pageRecord['title'] ?? '') === 'Corporate Law for fast-moving businesses', 'page record title generated');
lc_assert(($pageRecord['is_homepage'] ?? false) === true, 'page record can be homepage');
lc_assert(str_contains((string) $pageRecord['capsule_json'], 'OTM Lawyers'), 'page record capsule json contains business name');
lc_assert(str_contains((string) $pageRecord['html_content'], 'Corporate Law for fast-moving businesses'), 'page record html contains title');

// Create page set for rendering/tests
$pageRecord['revisions'] = [];
ccms_push_page_revision($pageRecord, 'Generated by deep test');
lc_assert(count($pageRecord['revisions']) === 1, 'revision created on generated page');
$data['pages'][] = $pageRecord;
$data['posts'][] = [
    'id' => ccms_next_id('post'),
    'title' => 'How founders should approach contract review',
    'slug' => 'how-founders-should-approach-contract-review',
    'status' => 'published',
    'excerpt' => 'A practical checklist for reviewing commercial contracts without slowing the business down.',
    'content_html' => '<p>Review risk, scope, payment terms, and termination clauses before signing.</p><p>Keep the decision process fast, but never skip the fundamentals.</p>',
    'cover_image' => 'https://images.unsplash.com/photo-1450101499163-c8848c66ca85?auto=format&fit=crop&w=1400&q=80',
    'author_name' => 'Ramon Legal Team',
    'categories' => ['Legal', 'Business'],
    'tags' => ['contracts', 'founders'],
    'meta_title' => 'Founder contract review checklist',
    'meta_description' => 'A practical checklist for reviewing contracts in a fast-moving business.',
    'published_at' => '2026-03-10T09:00:00Z',
    'created_at' => ccms_now_iso(),
    'updated_at' => ccms_now_iso(),
];
$futurePageSlug = 'future-scheduled-page';
$pastPageSlug = 'past-scheduled-page';
$futurePostSlug = 'future-scheduled-post';
$pastPostSlug = 'past-scheduled-post';
$data['pages'][] = [
    'id' => ccms_next_id('page'),
    'title' => 'Future Scheduled Page',
    'slug' => $futurePageSlug,
    'status' => 'scheduled',
    'published_at' => gmdate('c', time() + 86400),
    'is_homepage' => false,
    'show_in_menu' => true,
    'menu_label' => 'Future Scheduled Page',
    'meta_title' => 'Future Scheduled Page',
    'meta_description' => '',
    'capsule_json' => '{}',
    'html_content' => '<section><h1>Future Scheduled Page</h1></section>',
    'created_at' => ccms_now_iso(),
    'updated_at' => ccms_now_iso(),
    'revisions' => [],
];
$data['pages'][] = [
    'id' => ccms_next_id('page'),
    'title' => 'Past Scheduled Page',
    'slug' => $pastPageSlug,
    'status' => 'scheduled',
    'published_at' => gmdate('c', time() - 86400),
    'is_homepage' => false,
    'show_in_menu' => true,
    'menu_label' => 'Past Scheduled Page',
    'meta_title' => 'Past Scheduled Page',
    'meta_description' => '',
    'capsule_json' => '{}',
    'html_content' => '<section><h1>Past Scheduled Page</h1></section>',
    'created_at' => ccms_now_iso(),
    'updated_at' => ccms_now_iso(),
    'revisions' => [],
];
$data['posts'][] = [
    'id' => ccms_next_id('post'),
    'title' => 'Future Scheduled Post',
    'slug' => $futurePostSlug,
    'status' => 'scheduled',
    'excerpt' => 'Future excerpt',
    'content_html' => '<p>Future content</p>',
    'cover_image' => '',
    'author_name' => 'Ramon Legal Team',
    'categories' => ['Legal'],
    'tags' => ['future'],
    'meta_title' => 'Future Scheduled Post',
    'meta_description' => 'Future scheduled post.',
    'published_at' => gmdate('c', time() + 86400),
    'created_at' => ccms_now_iso(),
    'updated_at' => ccms_now_iso(),
];
$data['posts'][] = [
    'id' => ccms_next_id('post'),
    'title' => 'Past Scheduled Post',
    'slug' => $pastPostSlug,
    'status' => 'scheduled',
    'excerpt' => 'Past excerpt',
    'content_html' => '<p>Past content</p>',
    'cover_image' => '',
    'author_name' => 'Ramon Legal Team',
    'categories' => ['Legal'],
    'tags' => ['scheduled'],
    'meta_title' => 'Past Scheduled Post',
    'meta_description' => 'Past scheduled post.',
    'published_at' => gmdate('c', time() - 86400),
    'created_at' => ccms_now_iso(),
    'updated_at' => ccms_now_iso(),
];
foreach ($data['pages'] as $idx => $page) {
    $data['pages'][$idx]['is_homepage'] = (($page['id'] ?? '') === ($pageRecord['id'] ?? ''));
    if (($page['id'] ?? '') === ($pageRecord['id'] ?? '')) {
        $data['pages'][$idx]['status'] = 'published';
        $data['pages'][$idx]['show_in_menu'] = true;
    }
}
$businessModePage = [
    'id' => ccms_next_id('page'),
    'title' => 'Casa Maria',
    'slug' => 'casa-maria',
    'status' => 'published',
    'published_at' => ccms_now_iso(),
    'is_homepage' => false,
    'show_in_menu' => true,
    'menu_label' => 'Casa Maria',
    'meta_title' => 'Casa Maria',
    'meta_description' => 'Restaurant quick edit page.',
    'capsule_json' => json_encode([
        'meta' => ['business_name' => 'Casa Maria'],
        'style' => [],
        'blocks' => [
            [
                'id' => 'hero-business',
                'type' => 'hero_split',
                'props' => [
                    'badge' => 'Restaurante',
                    'title' => 'Casa Maria',
                    'subtitle' => 'Cocina casera desde 1987',
                    'image_url' => '/uploads/demo.png',
                    'cta_primary' => 'Reservar',
                    'cta_href' => '#menu',
                ],
                'quick_edit' => [
                    'enabled' => true,
                    'source' => 'capsule',
                    'category' => 'textos',
                    'label' => 'Texto principal',
                    'fields' => ['title', 'subtitle', 'image_url'],
                ],
            ],
            [
                'id' => 'menu-business',
                'type' => 'menu_daily',
                'props' => [
                    'badge' => 'Actualizado hoy',
                    'title' => 'Menu del dia',
                    'subtitle' => 'Disponible de lunes a viernes.',
                    'price' => '10.50',
                    'currency' => 'EUR',
                    'includes' => 'Pan y bebida',
                    'sections' => [
                        ['name' => 'Primeros', 'items' => ['Ensalada mixta']],
                    ],
                ],
                'quick_edit' => [
                    'enabled' => true,
                    'source' => 'live_data',
                    'category' => 'menu',
                    'label' => 'Menu del dia',
                    'slot' => 'business.menu.primary',
                    'frequency' => 'daily',
                ],
            ],
            [
                'id' => 'hours-business',
                'type' => 'hours_status',
                'props' => [
                    'badge' => 'Horario',
                    'title' => 'Abierto ahora',
                    'subtitle' => 'Consulta aperturas y cierres.',
                ],
                'quick_edit' => [
                    'enabled' => true,
                    'source' => 'live_data',
                    'category' => 'horario',
                    'label' => 'Horario',
                    'slot' => 'business.hours.primary',
                    'frequency' => 'daily',
                ],
            ],
            [
                'id' => 'prices-business',
                'type' => 'price_list',
                'props' => [
                    'badge' => 'Tarifas',
                    'title' => 'Servicios',
                    'subtitle' => 'Lista de precios rapida.',
                ],
                'quick_edit' => [
                    'enabled' => true,
                    'source' => 'live_data',
                    'category' => 'precios',
                    'label' => 'Lista de precios',
                    'slot' => 'business.prices.primary',
                    'frequency' => 'weekly',
                ],
            ],
        ],
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
    'html_content' => '',
    'created_at' => ccms_now_iso(),
    'updated_at' => ccms_now_iso(),
    'revisions' => [],
];
$data['pages'][] = $businessModePage;
$data['live_data'] = [
    'slots' => [
        'business.menu.primary' => [
            'type' => 'menu_daily',
            'updated_at' => ccms_now_iso(),
            'payload' => [
                'price' => '11.50',
                'currency' => 'EUR',
                'includes' => 'Pan y bebida',
                'sections' => [
                    ['name' => 'Primeros', 'items' => ['Ensalada mixta', 'Sopa del dia']],
                    ['name' => 'Segundos', 'items' => ['Pollo al horno', 'Merluza a la plancha']],
                    ['name' => 'Postres', 'items' => ['Flan casero']],
                ],
            ],
        ],
        'business.hours.primary' => [
            'type' => 'hours_status',
            'updated_at' => ccms_now_iso(),
            'payload' => [
                'timezone' => 'Europe/Madrid',
                'closed_today' => false,
                'closure_label' => '',
                'reopens_on' => '',
                'days' => [
                    'mon' => ['closed' => false, 'slots' => [['open' => '09:00', 'close' => '14:00'], ['open' => '17:00', 'close' => '20:00']]],
                    'tue' => ['closed' => false, 'slots' => [['open' => '09:00', 'close' => '14:00'], ['open' => '17:00', 'close' => '20:00']]],
                    'wed' => ['closed' => false, 'slots' => [['open' => '09:00', 'close' => '14:00'], ['open' => '17:00', 'close' => '20:00']]],
                    'thu' => ['closed' => false, 'slots' => [['open' => '09:00', 'close' => '14:00'], ['open' => '17:00', 'close' => '20:00']]],
                    'fri' => ['closed' => false, 'slots' => [['open' => '09:00', 'close' => '14:00'], ['open' => '17:00', 'close' => '20:00']]],
                    'sat' => ['closed' => false, 'slots' => [['open' => '10:00', 'close' => '14:00']]],
                    'sun' => ['closed' => true, 'slots' => []],
                ],
            ],
        ],
        'business.prices.primary' => [
            'type' => 'price_list',
            'updated_at' => ccms_now_iso(),
            'payload' => [
                'currency' => 'EUR',
                'note' => 'Reserva por telefono.',
                'items' => [
                    ['name' => 'Menu degustacion', 'price' => '32', 'detail' => 'Bajo reserva'],
                    ['name' => 'Arroz meloso', 'price' => '18', 'detail' => 'Minimo 2 personas'],
                ],
            ],
        ],
    ],
];
$data['site']['business_profile'] = [
    'type' => 'restaurant',
    'name' => 'Casa Maria',
    'description' => 'Restaurante de cocina casera en Valencia.',
    'phone' => '+34 963 123 456',
    'email' => 'hola@casamaria.com',
    'street_address' => 'Calle Mayor 15',
    'postal_code' => '46001',
    'city' => 'Valencia',
    'region' => 'Valencia',
    'country' => 'ES',
    'latitude' => '39.4699',
    'longitude' => '-0.3763',
    'price_range' => '€€',
    'currencies_accepted' => 'EUR',
    'serves_cuisine' => 'Mediterranea',
    'reservation_url' => 'https://casamaria.example.com/reservas',
    'menu_url' => 'https://casamaria.example.com/menu',
    'daily_menu_slot' => 'business.menu.primary',
    'hours_slot' => 'business.hours.primary',
    'price_list_slot' => 'business.prices.primary',
    'schema_enabled' => true,
    'ai_feed_enabled' => true,
];
ccms_save_data($data);
$data = ccms_load_data();
$homepage = ccms_homepage($data);
$data['site']['theme_preset'] = 'editorial';
$data['site']['custom_css'] = 'body[data-test-theme="1"]{outline:0}';
$data['site']['white_label_enabled'] = true;
$data['site']['admin_brand_name'] = 'Agency Console';
$data['site']['admin_brand_tagline'] = 'Private client portal';
$data['site']['admin_logo_url'] = '/uploads/agency-logo.png';
$plugins = ccms_discover_plugins();
lc_assert(isset($plugins['announcement-chip']), 'plugin discovery finds announcement chip');
$pluginMeta = $plugins['announcement-chip'];
lc_assert(!empty($pluginMeta['trusted']), 'plugin manifest marks trusted plugin');
lc_assert(!empty($pluginMeta['integrity_ok']), 'plugin integrity hash matches');
lc_assert(!empty($pluginMeta['path_trusted']), 'plugin path is inside trusted plugins root');
lc_assert(!empty($pluginMeta['entry_trusted']), 'plugin entry is inside trusted plugins root');
$invalidPluginDir = ccms_plugins_dir() . DIRECTORY_SEPARATOR . 'Bad Plugin!';
@mkdir($invalidPluginDir, 0775, true);
file_put_contents($invalidPluginDir . '/manifest.json', json_encode([
    'slug' => '../evil',
    'name' => 'Bad Plugin',
    'trusted' => true,
    'entry_sha256' => str_repeat('0', 64),
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
file_put_contents($invalidPluginDir . '/plugin.php', '<?php return [];');
$rediscoveredPlugins = ccms_discover_plugins();
lc_assert(!isset($rediscoveredPlugins['../evil']), 'plugin discovery ignores invalid manifest slugs');
lc_assert(!isset($rediscoveredPlugins['Bad Plugin!']), 'plugin discovery ignores invalid directory slugs');
$data['site']['enabled_plugins'] = ['announcement-chip'];
$data['site']['trusted_plugins_enabled'] = true;
$backupFixtureFile = ccms_uploads_dir() . '/deep-test-upload.txt';
file_put_contents($backupFixtureFile, 'deep test upload');
$data['media'][] = [
    'id' => ccms_next_id('media'),
    'filename' => 'deep-test-upload.txt',
    'original_name' => 'deep-test-upload.txt',
    'url' => ccms_public_upload_url('deep-test-upload.txt'),
    'uploaded_at' => ccms_now_iso(),
];
$homepage = $homepage ?? $pageRecord;
ccms_push_audit_log($data, 'test.event', 'Synthetic audit entry', ccms_current_admin(), ['source' => 'deep_test']);
ccms_save_data($data);
$data = ccms_load_data();
lc_assert(($homepage['slug'] ?? '') === ($pageRecord['slug'] ?? ''), 'homepage resolves generated page');
lc_assert(ccms_page_by_slug($data, (string) $pageRecord['slug']) !== null, 'page by slug works');
lc_assert(count(ccms_menu_pages($data)) >= 1, 'menu pages list contains published page');
lc_assert(ccms_page_by_slug($data, $futurePageSlug) === null, 'future scheduled page is not public yet');
lc_assert(ccms_page_by_slug($data, $pastPageSlug) !== null, 'past scheduled page is public');
lc_assert(!in_array($futurePageSlug, array_map(static fn(array $page): string => (string) ($page['slug'] ?? ''), ccms_menu_pages($data)), true), 'future scheduled page is not in menu');
lc_assert(in_array($pastPageSlug, array_map(static fn(array $page): string => (string) ($page['slug'] ?? ''), ccms_menu_pages($data)), true), 'past scheduled page is in menu');
lc_assert(count(ccms_posts_published($data)) === 2, 'published posts list includes published and past scheduled posts');
lc_assert((ccms_load_post_by_slug('how-founders-should-approach-contract-review')['title'] ?? '') === 'How founders should approach contract review', 'load_post_by_slug returns post fixture');
lc_assert((ccms_post_by_slug($data, 'how-founders-should-approach-contract-review')['id'] ?? '') !== '', 'published post lookup by slug works');
lc_assert(ccms_post_by_slug($data, $futurePostSlug) === null, 'future scheduled post is not public yet');
lc_assert(ccms_post_by_slug($data, $pastPostSlug) !== null, 'past scheduled post is public');
lc_assert(count(ccms_posts_for_category_slug($data, 'legal')) === 2, 'category archive lookup returns published and past scheduled posts');
lc_assert(count(ccms_posts_for_category_slug($data, 'legal')) === 2, 'category archive lookup returns published and past scheduled posts');
lc_assert(count(ccms_posts_for_tag_slug($data, 'contracts')) === 1, 'tag archive lookup returns matching post');
lc_assert(count(ccms_posts_for_tag_slug($data, 'scheduled')) === 1, 'tag archive lookup returns past scheduled post only');
lc_assert(in_array('Legal', ccms_blog_categories($data), true), 'blog categories list includes fixture category');
lc_assert(in_array('contracts', ccms_blog_tags($data), true), 'blog tags list includes fixture tag');
lc_assert(count($data['audit_logs'] ?? []) >= 1, 'audit log entries can be stored');
lc_assert(($data['audit_logs'][0]['action'] ?? '') === 'test.event', 'audit log preserves latest action');

// Block render coverage
$capsule = $fallback['page']['capsule'];
$supported = ccms_capsule_supported_blocks();
foreach (['sticky_header','hero_fullscreen','split_image_right','features','testimonial_cards','faq','lead_form','footer_multi'] as $type) {
    lc_assert(in_array($type, $supported, true), 'supported blocks include ' . $type);
}
$builderTemplates = ccms_admin_capsule_builder_templates();
$builderTemplateTypes = array_map(static fn (array $template): string => (string) ($template['type'] ?? ''), $builderTemplates);
foreach (['pricing_toggle', 'blog_featured', 'blog_carousel', 'menu_daily', 'hours_status', 'price_list'] as $type) {
    lc_assert(in_array($type, $builderTemplateTypes, true), 'builder templates include ' . $type);
}
lc_assert(ccms_capsule_can_render($capsule) === true, 'generated capsule is fully renderable');
$bodyHtml = ccms_render_capsule_body($capsule);
lc_assert(str_contains($bodyHtml, 'Corporate Law for fast-moving businesses'), 'render body contains generated title');
lc_assert(str_contains($bodyHtml, 'Book a consultation'), 'render body contains CTA');
lc_assert(str_contains($bodyHtml, 'Start the conversation'), 'render body contains form block');
lc_assert(str_contains($bodyHtml, '/api/forms/submit'), 'render body includes public form endpoint');
lc_assert(str_contains($bodyHtml, 'method="post"'), 'render body includes public form post method');
lc_assert(!str_contains($bodyHtml, 'IntersectionObserver'), 'capsule render skips motion observer when no blocks are animated');
$animatedCapsule = $capsule;
$animatedCapsule['style']['button_radius'] = '12px';
$animatedCapsule['style']['line_height_body'] = '1.9';
$animatedCapsule['blocks'][0]['style']['animation'] = 'fade-up';
$animatedBodyHtml = ccms_render_capsule_body($animatedCapsule, [
    'theme_preset' => 'corporate',
    'font_pairing' => 'humanist',
    'colors' => $data['site']['colors'] ?? [],
]);
lc_assert(str_contains($animatedBodyHtml, 'data-ccms-animate="fade-up"'), 'capsule render includes block animation attribute');
lc_assert(str_contains($animatedBodyHtml, 'IntersectionObserver'), 'capsule render injects motion observer when a block is animated');
lc_assert(str_contains($animatedBodyHtml, '--site-button-radius:12px'), 'capsule style override controls button radius');
lc_assert(str_contains($animatedBodyHtml, '--site-body-leading:1.9'), 'capsule style override controls body line height');
$layoutCapsule = $capsule;
foreach (($layoutCapsule['blocks'] ?? []) as $layoutIndex => $layoutBlock) {
    $layoutType = (string) ($layoutBlock['type'] ?? '');
    if ($layoutType === 'hero_fullscreen') {
        $layoutCapsule['blocks'][$layoutIndex]['layout'] = 'reversed';
        $layoutCapsule['blocks'][$layoutIndex]['props']['background_image'] = '/uploads/demo.png';
    }
    if ($layoutType === 'features') {
        $layoutCapsule['blocks'][$layoutIndex]['layout'] = '4-col';
    }
    if ($layoutType === 'testimonial_cards') {
        $layoutCapsule['blocks'][$layoutIndex]['layout'] = 'spotlight';
    }
}
$layoutBodyHtml = ccms_render_capsule_body($layoutCapsule, $data['site']);
lc_assert(str_contains($layoutBodyHtml, 'data-ccms-layout="reversed"'), 'hero layout variant is rendered on capsule output');
lc_assert(str_contains($layoutBodyHtml, 'data-ccms-layout="4-col"'), 'features layout variant is rendered on capsule output');
lc_assert(str_contains($layoutBodyHtml, 'grid-template-columns:repeat(4,minmax(0,1fr))'), 'features 4-col layout changes the grid structure');
lc_assert(str_contains($layoutBodyHtml, 'data-ccms-layout="spotlight"'), 'testimonial spotlight layout is rendered on capsule output');
lc_assert(str_contains($layoutBodyHtml, 'class="ccms-grid-2" style="align-items:stretch"'), 'testimonial spotlight layout uses a split spotlight composition');
$sprint3Capsule = [
    'style' => [],
    'blocks' => [
        [
            'id' => 'pricing-sprint3',
            'type' => 'pricing',
            'layout' => 'comparison',
            'style' => ['background_effect' => 'glass'],
            'props' => [
                'badge' => 'Pricing',
                'title' => 'Compare plans',
                'plans' => [
                    ['name' => 'Starter', 'price' => '$29/mo', 'features' => ['Audit', 'Support'], 'cta' => 'Start'],
                    ['name' => 'Growth', 'price' => '$79/mo', 'features' => ['Audit', 'Support', 'Strategy'], 'cta' => 'Scale', 'highlighted' => true],
                ],
            ],
        ],
        [
            'id' => 'gallery-sprint3',
            'type' => 'gallery',
            'layout' => 'masonry',
            'style' => ['background_effect' => 'grain'],
            'props' => [
                'badge' => 'Gallery',
                'title' => 'Selected visuals',
                'images' => [
                    ['url' => '', 'alt' => 'Gallery image 1'],
                    ['url' => '', 'alt' => 'Gallery image 2'],
                    ['url' => '', 'alt' => 'Gallery image 3'],
                    ['url' => '', 'alt' => 'Gallery image 4'],
                ],
            ],
        ],
        [
            'id' => 'blog-sprint3-featured',
            'type' => 'blog_grid',
            'layout' => 'featured-left',
            'props' => [
                'badge' => 'Insights',
                'title' => 'Latest ideas',
                'subtitle' => 'Fresh notes from the studio',
                'posts' => [
                    ['category' => 'Guide', 'title' => 'Featured story', 'excerpt' => 'A longer lead article.', 'author' => 'Editor', 'date' => 'March 2026', 'href' => '#featured', 'image' => ''],
                    ['category' => 'Guide', 'title' => 'Second story', 'excerpt' => 'A compact supporting post.', 'author' => 'Editor', 'date' => 'March 2026', 'href' => '#second', 'image' => ''],
                    ['category' => 'Guide', 'title' => 'Third story', 'excerpt' => 'Another supporting post.', 'author' => 'Editor', 'date' => 'March 2026', 'href' => '#third', 'image' => ''],
                ],
            ],
        ],
        [
            'id' => 'blog-sprint3-list',
            'type' => 'blog_grid',
            'layout' => 'list',
            'style' => ['background_effect' => 'dots'],
            'props' => [
                'badge' => 'Archive',
                'title' => 'More reads',
                'subtitle' => 'List presentation',
                'posts' => [
                    ['category' => 'Briefing', 'title' => 'List item one', 'excerpt' => 'Short summary one.', 'author' => 'Editor', 'date' => 'March 2026', 'href' => '#one', 'image' => ''],
                    ['category' => 'Briefing', 'title' => 'List item two', 'excerpt' => 'Short summary two.', 'author' => 'Editor', 'date' => 'March 2026', 'href' => '#two', 'image' => ''],
                ],
            ],
        ],
    ],
];
$sprint3Html = ccms_render_capsule_body($sprint3Capsule, $data['site']);
lc_assert(str_contains($sprint3Html, 'data-ccms-layout="comparison"'), 'pricing comparison layout is rendered on capsule output');
lc_assert(str_contains($sprint3Html, '<th>Starter</th><th>Growth</th>'), 'pricing comparison layout renders plan headers');
lc_assert(str_contains($sprint3Html, 'data-ccms-bg="glass"'), 'pricing block renders glass background effect');
lc_assert(str_contains($sprint3Html, 'data-ccms-layout="masonry"'), 'gallery masonry layout is rendered on capsule output');
lc_assert(str_contains($sprint3Html, 'class="ccms-gallery-masonry"'), 'gallery masonry layout uses masonry wrapper');
lc_assert(str_contains($sprint3Html, 'data-ccms-bg="grain"'), 'gallery block renders grain background effect');
lc_assert(str_contains($sprint3Html, 'data-ccms-layout="featured-left"'), 'blog featured-left layout is rendered on capsule output');
lc_assert(str_contains($sprint3Html, 'data-ccms-layout="list"'), 'blog list layout is rendered on capsule output');
lc_assert(str_contains($sprint3Html, 'ccms-blog-list-card'), 'blog list layout uses list card composition');
$sprint4Capsule = [
    'style' => [],
    'blocks' => [
        [
            'id' => 'pricing-toggle-sprint4',
            'type' => 'pricing_toggle',
            'layout' => 'stacked',
            'props' => [
                'badge' => 'Plans',
                'title' => 'Annual pricing',
                'subtitle' => 'Choose your pace.',
                'annual_label' => 'Save 25% yearly',
                'plans' => [
                    ['name' => 'Core', 'price' => '$39/mo', 'features' => ['Support', 'Reporting'], 'cta' => 'Choose core'],
                    ['name' => 'Scale', 'price' => '$99/mo', 'features' => ['Support', 'Reporting', 'Advisory'], 'cta' => 'Choose scale', 'highlighted' => true],
                ],
            ],
        ],
        [
            'id' => 'blog-featured-sprint4',
            'type' => 'blog_featured',
            'layout' => 'reversed',
            'props' => [
                'badge' => 'Magazine',
                'title' => 'Editorial feature',
                'subtitle' => 'Lead story and side reads.',
                'posts' => [
                    ['category' => 'Feature', 'title' => 'Lead feature', 'excerpt' => 'Main story.', 'author' => 'Editor', 'date' => 'March 2026', 'href' => '#lead', 'image' => ''],
                    ['category' => 'Feature', 'title' => 'Support one', 'excerpt' => 'Support copy one.', 'author' => 'Editor', 'date' => 'March 2026', 'href' => '#support-one', 'image' => ''],
                    ['category' => 'Feature', 'title' => 'Support two', 'excerpt' => 'Support copy two.', 'author' => 'Editor', 'date' => 'March 2026', 'href' => '#support-two', 'image' => ''],
                ],
            ],
        ],
        [
            'id' => 'blog-carousel-spotlight',
            'type' => 'blog_carousel',
            'layout' => 'spotlight',
            'props' => [
                'badge' => 'Stories',
                'title' => 'Spotlight stories',
                'subtitle' => 'Main story plus supporting rail.',
                'posts' => [
                    ['category' => 'Story', 'title' => 'Spotlight lead', 'excerpt' => 'Lead story.', 'author' => 'Editor', 'date' => 'March 2026', 'href' => '#spotlight-lead', 'image' => ''],
                    ['category' => 'Story', 'title' => 'Spotlight support', 'excerpt' => 'Secondary story.', 'author' => 'Editor', 'date' => 'March 2026', 'href' => '#spotlight-support', 'image' => ''],
                ],
            ],
        ],
        [
            'id' => 'blog-carousel-compact',
            'type' => 'blog_carousel',
            'layout' => 'compact',
            'props' => [
                'badge' => 'Stories',
                'title' => 'Compact stories',
                'subtitle' => 'Tighter article rail.',
                'posts' => [
                    ['category' => 'Story', 'title' => 'Compact one', 'excerpt' => 'Short brief one.', 'author' => 'Editor', 'date' => 'March 2026', 'href' => '#compact-one', 'image' => ''],
                    ['category' => 'Story', 'title' => 'Compact two', 'excerpt' => 'Short brief two.', 'author' => 'Editor', 'date' => 'March 2026', 'href' => '#compact-two', 'image' => ''],
                    ['category' => 'Story', 'title' => 'Compact three', 'excerpt' => 'Short brief three.', 'author' => 'Editor', 'date' => 'March 2026', 'href' => '#compact-three', 'image' => ''],
                ],
            ],
        ],
    ],
];
$sprint4Html = ccms_render_capsule_body($sprint4Capsule, $data['site']);
lc_assert(str_contains($sprint4Html, 'data-ccms-layout="stacked"'), 'pricing toggle stacked layout is rendered on capsule output');
lc_assert(str_contains($sprint4Html, 'ccms-pricing-stack-card'), 'pricing toggle stacked layout uses stacked pricing composition');
lc_assert(str_contains($sprint4Html, 'Save 25% yearly'), 'pricing toggle layout preserves annual helper label');
lc_assert(str_contains($sprint4Html, 'data-ccms-layout="reversed"'), 'blog featured reversed layout is rendered on capsule output');
lc_assert(strpos($sprint4Html, 'Support one') < strpos($sprint4Html, 'Lead feature'), 'blog featured reversed layout swaps featured and side content order');
lc_assert(str_contains($sprint4Html, 'data-ccms-layout="spotlight"'), 'blog carousel spotlight layout is rendered on capsule output');
lc_assert(str_contains($sprint4Html, 'Spotlight lead'), 'blog carousel spotlight layout renders lead story');
lc_assert(str_contains($sprint4Html, 'data-ccms-layout="compact"'), 'blog carousel compact layout is rendered on capsule output');
lc_assert(substr_count($sprint4Html, 'padding:22px"><span class="ccms-kicker">') >= 3, 'blog carousel compact layout uses compact article cards');
$businessPage = ccms_page_by_slug($data, 'casa-maria');
lc_assert($businessPage !== null, 'business mode page fixture is available');
$publicSiteConfig = ccms_public_site_config($data);
lc_assert(($publicSiteConfig['live_data']['slots']['business.menu.primary']['payload']['price'] ?? '') === '11.50', 'public site config carries live data payload');
$businessItems = ccms_business_mode_collect_items($businessPage, $data['live_data'] ?? []);
lc_assert(count($businessItems) === 4, 'business mode collects editable items from business page');
lc_assert(count(array_filter($businessItems, static fn (array $item): bool => ($item['source'] ?? '') === 'live_data')) === 3, 'business mode identifies live data backed items');
$heroBusinessItem = ccms_business_mode_find_item($businessPage, $data['live_data'] ?? [], 'hero-business');
lc_assert(($heroBusinessItem['label'] ?? '') === 'Texto principal', 'business mode keeps configured capsule quick edit label');
lc_assert(count($heroBusinessItem['fields'] ?? []) === 3, 'business mode exposes configured capsule quick edit fields');
$menuBusinessItem = ccms_business_mode_find_item($businessPage, $data['live_data'] ?? [], 'menu-business');
lc_assert(($menuBusinessItem['slot_key'] ?? '') === 'business.menu.primary', 'business mode resolves live data slot keys');
lc_assert(($menuBusinessItem['payload']['sections'][1]['items'][0] ?? '') === 'Pollo al horno', 'business mode loads live data payload into editable items');
$businessCapsule = ccms_capsule_decode($businessPage);
$businessBodyHtml = ccms_render_capsule_body($businessCapsule ?? [], $publicSiteConfig);
lc_assert(str_contains($businessBodyHtml, '11.50 EUR'), 'business capsule render uses live menu price');
lc_assert(str_contains($businessBodyHtml, 'Menu degustacion'), 'business capsule render uses live price list data');
lc_assert(str_contains($businessBodyHtml, 'Cocina casera desde 1987'), 'business capsule render keeps capsule copy fields');
$businessPublicHtml = ccms_render_public_page($publicSiteConfig, $businessPage, ccms_menu_pages($data));
lc_assert(str_contains($businessPublicHtml, 'Casa Maria'), 'business public page renders business title');
lc_assert(str_contains($businessPublicHtml, 'Merluza a la plancha'), 'business public page renders live menu sections');
lc_assert(str_contains($businessPublicHtml, 'Reserva por telefono.'), 'business public page renders live price list note');
$aiJson = ccms_render_ai_well_known($data);
lc_assert(str_contains($aiJson, '"schema_version":"1.0"'), 'ai feed renders schema version');
lc_assert(str_contains($aiJson, '"type":"restaurant"'), 'ai feed serializes business type');
lc_assert(str_contains($aiJson, '"slot":"business.menu.primary"'), 'ai feed links menu slot');
lc_assert(str_contains($aiJson, 'Menu degustacion'), 'ai feed includes price list content');
$submissionFixture = [
    'id' => 'sub_fixture',
    'kind' => 'lead_form',
    'status' => 'new',
    'created_at' => ccms_now_iso(),
    'updated_at' => ccms_now_iso(),
    'page_id' => (string) ($pageRecord['id'] ?? ''),
    'page_slug' => (string) ($pageRecord['slug'] ?? ''),
    'page_title' => (string) ($pageRecord['title'] ?? ''),
    'block_id' => 'fixture-block',
    'block_type' => 'lead_form',
    'source_url' => '/' . (string) ($pageRecord['slug'] ?? ''),
    'delivery' => ['attempted' => false, 'sent' => false, 'channel' => 'mail', 'target' => ''],
    'fields' => [
        'name' => 'Lead Fixture',
        'email' => 'lead@example.com',
        'message' => 'Interested in your service.',
    ],
];
ccms_store_submission($data, $submissionFixture);
lc_assert(($data['submissions'][0]['id'] ?? '') === 'sub_fixture', 'submissions can be stored in inbox');
$data['site']['analytics_provider'] = 'ga4';
$data['site']['analytics_id'] = 'G-DEEPTEST1';
$previewHtml = ccms_admin_preview_html($publicHtml = ccms_render_public_page(ccms_public_site_config($data), $homepage, ccms_menu_pages($data)));
lc_assert(str_contains($previewHtml, 'ccms-preview-action'), 'admin preview includes inline action hook');
lc_assert(str_contains($previewHtml, 'ccms-preview-quick-text'), 'admin preview includes quick text hook');
lc_assert(str_contains($previewHtml, 'ccms-preview-apply-text'), 'admin preview includes inline text apply hook');
lc_assert(str_contains($previewHtml, 'ccms-preview-quick-media'), 'admin preview includes quick media hook');
lc_assert(str_contains($previewHtml, 'ccms-preview-quick-link'), 'admin preview includes quick link hook');
lc_assert(str_contains($previewHtml, 'ccms-preview-apply-link'), 'admin preview includes inline link apply hook');
lc_assert(str_contains($previewHtml, 'ccms-preview-apply-button'), 'admin preview includes inline button apply hook');
lc_assert(str_contains($previewHtml, 'Editar contenido'), 'admin preview includes edit content action');
lc_assert(str_contains($previewHtml, 'Editar enlace'), 'admin preview includes edit link action');
lc_assert(str_contains($previewHtml, 'Editar media'), 'admin preview includes edit media action');
lc_assert(str_contains($previewHtml, 'Editar estilo'), 'admin preview includes edit style action');
lc_assert(str_contains($previewHtml, 'Insertar después'), 'admin preview includes insert action');
$adminHtml = ob_get_clean();
$_GET = ['tab' => 'pages'];
$_SERVER['REQUEST_METHOD'] = 'GET';
$adminPagesHtml = lc_capture_include($sourceRoot . '/r-admin/index.php');
ob_start();
lc_assert(str_contains($adminPagesHtml, '/r-admin/assets/admin.js'), 'admin pages view includes extracted admin javascript asset');
lc_assert(str_contains($adminPagesHtml, '/r-admin/assets/admin.css'), 'admin pages view includes extracted admin stylesheet asset');
$adminJsAsset = (string) file_get_contents($sourceRoot . '/r-admin/assets/admin.js');
lc_assert(str_contains($adminJsAsset, 'data-builder-layout-field'), 'admin builder asset includes block layout selector logic');
lc_assert(str_contains($adminJsAsset, 'background_effect'), 'admin builder asset includes background effect control');
lc_assert(str_contains($adminJsAsset, 'comparison'), 'admin builder asset includes pricing layout options');
lc_assert(str_contains($adminJsAsset, 'pricing_toggle'), 'admin builder asset includes pricing toggle layout options');
lc_assert(str_contains($adminJsAsset, 'masonry'), 'admin builder asset includes gallery layout options');
lc_assert(str_contains($adminJsAsset, 'featured-left'), 'admin builder asset includes blog featured layout option');
lc_assert(str_contains($adminJsAsset, 'blog_featured'), 'admin builder asset includes blog featured block layout map');
lc_assert(str_contains($adminJsAsset, 'blog_carousel'), 'admin builder asset includes blog carousel layout map');
lc_assert(str_contains($adminJsAsset, 'compact'), 'admin builder asset includes compact blog carousel layout option');
lc_assert(str_contains($adminJsAsset, 'data-builder-quick-edit'), 'admin builder asset includes quick edit field bindings');
lc_assert(str_contains($adminJsAsset, 'Modo Negocio'), 'admin builder asset includes business mode panel copy');
lc_assert(str_contains($adminJsAsset, 'Campos editables'), 'admin builder asset includes quick edit field editor');
$publicHtml = ccms_render_public_page(ccms_public_site_config($data), $homepage, ccms_menu_pages($data));
lc_assert(str_contains($publicHtml, '<!doctype html>'), 'public page is full html');
lc_assert(str_contains($publicHtml, 'Corporate Law for fast-moving businesses'), 'public page contains generated title');
lc_assert(str_contains($publicHtml, 'OTM Lawyers'), 'public page contains business name');
lc_assert(str_contains($publicHtml, 'Book a consultation'), 'public page contains CTA text');
lc_assert(str_contains($publicHtml, '--site-surface-radius:16px'), 'public page includes editorial theme radius');
lc_assert(str_contains($publicHtml, '--site-heading-weight:400'), 'public page includes editorial heading weight');
lc_assert(str_contains($publicHtml, 'id="ccms-custom-css"'), 'public page includes custom css block');
lc_assert(str_contains($publicHtml, 'data-ccms-plugin="announcement-chip"'), 'public page includes enabled plugin markup');
lc_assert(str_contains($publicHtml, 'property="og:title"'), 'public page includes og title');
lc_assert(str_contains($publicHtml, 'rel="canonical"'), 'public page includes canonical link');
lc_assert(str_contains($publicHtml, 'application/ld+json'), 'public page includes schema json-ld');
lc_assert(str_contains($publicHtml, '"@type":"Restaurant"'), 'public page includes business schema type');
lc_assert(str_contains($publicHtml, '"openingHoursSpecification"'), 'public page includes business opening hours schema');
lc_assert(str_contains($publicHtml, '"hasMenuSection"'), 'public page includes structured menu schema');
lc_assert(str_contains($publicHtml, 'googletagmanager.com/gtag/js?id=G-DEEPTEST1'), 'public page includes GA4 script');
lc_assert(str_contains($publicHtml, 'gtag("config","G-DEEPTEST1")') || str_contains($publicHtml, 'gtag("config", "G-DEEPTEST1")'), 'public page includes GA4 config');
lc_assert(!str_contains($publicHtml, 'Agency Console'), 'public page does not leak admin white-label branding');
$corporateSite = $data['site'];
$corporateSite['theme_preset'] = 'corporate';
$corporateSite['font_pairing'] = 'humanist';
$corporateHtml = ccms_render_public_page($corporateSite + ['live_data' => ccms_public_site_config($data)['live_data'] ?? []], $homepage, ccms_menu_pages($data));
lc_assert(str_contains($corporateHtml, '--site-surface-radius:8px'), 'corporate profile sets tight surface radius');
lc_assert(str_contains($corporateHtml, 'Verdana, Geneva, sans-serif'), 'font pairing overrides corporate body font');
$playfulSite = $data['site'];
$playfulSite['theme_preset'] = 'playful';
$playfulSite['font_pairing'] = 'modern';
$playfulHtml = ccms_render_public_page($playfulSite + ['live_data' => ccms_public_site_config($data)['live_data'] ?? []], $homepage, ccms_menu_pages($data));
lc_assert(str_contains($playfulHtml, '--site-surface-radius:32px'), 'playful profile sets large surface radius');
lc_assert(str_contains($playfulHtml, '--site-space-scale:1.3'), 'playful profile increases spacing scale');
lc_assert(ccms_capsule_can_render(['blocks' => [['type' => 'unknown_block']]]) === false, 'unknown block capsule falls back correctly');
$sitemapXml = ccms_render_sitemap_xml($data);
lc_assert(str_contains($sitemapXml, '<urlset'), 'sitemap xml renders urlset');
lc_assert(str_contains($sitemapXml, ccms_base_url()), 'sitemap includes public base url');
$robotsTxt = ccms_render_robots_txt();
lc_assert(str_contains($robotsTxt, 'Sitemap:'), 'robots.txt includes sitemap reference');
lc_assert(str_contains($robotsTxt, 'User-agent: *'), 'robots.txt includes user agent');
$blogArchiveHtml = ccms_render_blog_archive_page($data['site'], ccms_posts_published($data), ccms_menu_pages($data));
lc_assert(str_contains($blogArchiveHtml, 'How founders should approach contract review'), 'blog archive renders published post title');
lc_assert(str_contains($blogArchiveHtml, '/blog/how-founders-should-approach-contract-review'), 'blog archive links to post slug');
$blogPost = ccms_post_by_slug($data, 'how-founders-should-approach-contract-review');
$blogPostHtml = ccms_render_blog_post_page($data['site'], $blogPost, ccms_menu_pages($data));
lc_assert(str_contains($blogPostHtml, 'Founder contract review checklist'), 'blog post render uses meta title');
lc_assert(str_contains($blogPostHtml, 'Review risk, scope, payment terms'), 'blog post render includes content html');
$rssXml = ccms_render_blog_rss($data['site'], ccms_posts_published($data));
lc_assert(str_contains($rssXml, '<rss version="2.0">'), 'blog rss renders rss root');
lc_assert(str_contains($rssXml, '<title>How founders should approach contract review</title>'), 'blog rss includes post item title');

$pluginsDisabledSite = $data['site'];
$pluginsDisabledSite['trusted_plugins_enabled'] = false;
$pluginsDisabledHtml = ccms_render_public_page($pluginsDisabledSite, $homepage, ccms_menu_pages($data));
lc_assert(!str_contains($pluginsDisabledHtml, 'data-ccms-plugin="announcement-chip"'), 'public page skips plugin markup when trusted plugins disabled');

$unsafePage = $homepage;
$unsafePage['capsule_json'] = '{}';
$unsafePage['html_content'] = '<section><img src="x" onerror="alert(1)"><p>Safe text</p><a href="javascript:alert(1)">bad</a></section>';
$unsafePublic = ccms_render_public_page($data['site'], $unsafePage, ccms_menu_pages($data));
lc_assert(!str_contains($unsafePublic, 'onerror'), 'public render strips event handlers from html_content');
lc_assert(!str_contains($unsafePublic, 'javascript:'), 'public render strips javascript links from html_content');
lc_assert(str_contains($unsafePublic, 'Safe text'), 'public render preserves safe html content');

$backupPayload = ccms_export_backup_payload($data);
lc_assert(($backupPayload['format'] ?? '') === 'linuxcms-backup', 'backup payload uses linuxcms format');
lc_assert(count($backupPayload['uploads'] ?? []) >= 1, 'backup payload includes uploaded files');
$mutatedPayload = $backupPayload;
$mutatedPayload['data']['site']['title'] = 'Restored Test Site';
$mutatedPayload['data']['site']['theme_preset'] = 'playful';
$mutatedPayload['data']['site']['font_pairing'] = 'evil';
$mutatedPayload['data']['site']['trusted_plugins_enabled'] = true;
$mutatedPayload['data']['site']['enabled_plugins'] = ['announcement-chip', 'malicious-plugin'];
$mutatedPayload['data']['site']['business_profile'] = [
    'type' => 'restaurant',
    'name' => 'Restored Casa Maria',
    'ai_feed_enabled' => true,
    'schema_enabled' => true,
    'daily_menu_slot' => 'business.menu.primary',
];
$mutatedPayload['data']['site']['colors']['evil'] = '#000000';
$mutatedPayload['uploads'][] = [
    'filename' => 'restored-upload.txt',
    'content_base64' => base64_encode('restored upload'),
];
$restoredData = ccms_import_backup_payload($mutatedPayload);
lc_assert(($restoredData['site']['title'] ?? '') === 'Restored Test Site', 'backup import restores mutated site title');
lc_assert(($restoredData['site']['theme_preset'] ?? '') === 'playful', 'backup import restores extended visual profile');
lc_assert(($restoredData['site']['font_pairing'] ?? '') === 'auto', 'backup import normalizes invalid font pairing');
lc_assert(($restoredData['site']['trusted_plugins_enabled'] ?? false) === true, 'backup import restores trusted plugins flag');
lc_assert(($restoredData['site']['enabled_plugins'] ?? []) === ['announcement-chip'], 'backup import filters unknown plugins');
lc_assert(($restoredData['site']['business_profile']['name'] ?? '') === 'Restored Casa Maria', 'backup import restores business profile');
lc_assert(!isset($restoredData['site']['colors']['evil']), 'backup import whitelists site color keys');
lc_assert(is_file(ccms_uploads_dir() . '/restored-upload.txt'), 'backup import restores uploaded files');
ccms_save_data($restoredData);
$data = ccms_load_data();
lc_assert(($data['site']['title'] ?? '') === 'Restored Test Site', 'restored data can be saved and reloaded');
$staticBuild = ccms_static_export_build($data);
lc_assert(is_dir((string) ($staticBuild['dir'] ?? '')), 'static export creates export directory');
lc_assert(is_file($staticBuild['dir'] . '/index.html'), 'static export creates root index.html');
lc_assert(is_file($staticBuild['dir'] . '/' . $pageRecord['slug'] . '/index.html'), 'static export creates slug folder index');
lc_assert(is_file($staticBuild['dir'] . '/casa-maria/index.html'), 'static export creates business mode page');
lc_assert(is_file($staticBuild['dir'] . '/.well-known/ai.json'), 'static export creates ai feed file when business profile is enabled');
lc_assert(!is_dir($staticBuild['dir'] . '/' . $futurePageSlug), 'static export skips future scheduled page');
lc_assert(is_file($staticBuild['dir'] . '/' . $pastPageSlug . '/index.html'), 'static export includes past scheduled page');
lc_assert(is_file($staticBuild['dir'] . '/blog/index.html'), 'static export creates blog archive index');
lc_assert(is_file($staticBuild['dir'] . '/blog/how-founders-should-approach-contract-review/index.html'), 'static export creates blog post index');
lc_assert(!is_dir($staticBuild['dir'] . '/blog/' . $futurePostSlug), 'static export skips future scheduled post');
lc_assert(is_file($staticBuild['dir'] . '/blog/' . $pastPostSlug . '/index.html'), 'static export includes past scheduled post');
lc_assert(is_file($staticBuild['dir'] . '/blog/category/legal/index.html'), 'static export creates category archive index');
lc_assert(is_file($staticBuild['dir'] . '/blog/tag/contracts/index.html'), 'static export creates tag archive index');
lc_assert(is_file($staticBuild['dir'] . '/feed.xml'), 'static export creates rss feed');
lc_assert(is_file($staticBuild['dir'] . '/uploads/restored-upload.txt'), 'static export copies uploads');
if (!empty($generatedImageVariants['generated'])) {
    $firstGeneratedVariant = basename((string) ($generatedImageVariants['generated'][0]['path'] ?? ''));
    lc_assert(is_file($staticBuild['dir'] . '/uploads/' . $firstGeneratedVariant), 'static export copies generated image variants');
}
if (!empty($generatedImageVariants['webp_generated'])) {
    $firstGeneratedWebp = basename((string) ($generatedImageVariants['webp_generated'][0]['path'] ?? ''));
    lc_assert(is_file($staticBuild['dir'] . '/uploads/' . $firstGeneratedWebp), 'static export copies generated webp variants');
}
lc_assert(str_contains((string) file_get_contents($staticBuild['dir'] . '/index.html'), 'Corporate Law for fast-moving businesses'), 'static export root html contains page title');
lc_assert(str_contains((string) file_get_contents($staticBuild['dir'] . '/blog/index.html'), 'How founders should approach contract review'), 'static export blog archive contains post title');
lc_assert(str_contains((string) file_get_contents($staticBuild['dir'] . '/casa-maria/index.html'), 'Menu degustacion'), 'static export keeps business live data in rendered page');
lc_assert(str_contains((string) file_get_contents($staticBuild['dir'] . '/.well-known/ai.json'), '"business"'), 'static export ai feed contains business payload');
$sitemapXml = ccms_render_sitemap_xml($data);
lc_assert(!str_contains($sitemapXml, $futurePageSlug), 'sitemap excludes future scheduled page');
lc_assert(str_contains($sitemapXml, $pastPageSlug), 'sitemap includes past scheduled page');
lc_assert(!str_contains($sitemapXml, $futurePostSlug), 'sitemap excludes future scheduled post');
lc_assert(str_contains($sitemapXml, $pastPostSlug), 'sitemap includes past scheduled post');
if (class_exists(ZipArchive::class)) {
    $staticZip = ccms_static_export_zip($staticBuild);
    lc_assert(is_file($staticZip), 'static export creates zip when ZipArchive exists');
}

// Site wrapper logic
$simplePage = [
    'title' => 'Simple page',
    'slug' => 'simple-page',
    'status' => 'published',
    'is_homepage' => false,
    'show_in_menu' => true,
    'menu_label' => 'Simple',
    'meta_title' => 'Simple page',
    'meta_description' => 'Simple description',
    'capsule_json' => '{}',
    'html_content' => '<section><h1>Simple Content</h1></section>',
];
$simpleHtml = ccms_render_public_page(ccms_public_site_config($data), $simplePage, ccms_menu_pages($data));
lc_assert(str_contains($simpleHtml, 'Simple Content'), 'public wrapper preserves html_content');
lc_assert(str_contains($simpleHtml, 'site-header'), 'public wrapper adds header when capsule absent');
lc_assert(str_contains($simpleHtml, 'site-footer'), 'public wrapper adds footer when capsule absent');

// Include install page output
$_SESSION = [];
$_GET = [];
$_POST = [];
$_SERVER['REQUEST_METHOD'] = 'GET';
$installRoot = sys_get_temp_dir() . '/linuxcms_deep_test_install_runtime';
lc_rrmdir($installRoot);
lc_rcopy((string) $sourceRoot, $installRoot);
@unlink($installRoot . '/data/app.json');
@unlink($installRoot . '/data/app.sqlite');
@unlink($installRoot . '/data/storage.json');
putenv('CCMS_ROOT=' . $installRoot);
$_SERVER['REQUEST_METHOD'] = 'GET';
$installHtml = lc_capture_include($sourceRoot . '/install.php');
lc_assert(str_contains($installHtml, 'Instalar LinuxCMS'), 'install page renders LinuxCMS title');
lc_assert(str_contains($installHtml, 'Instalación rápida'), 'install page content renders');
putenv('CCMS_ROOT=' . $runtimeRoot);

// Include admin page output as owner
$_GET = ['tab' => 'studio'];
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SESSION['ccms_admin'] = [
    'id' => $data['users'][0]['id'],
    'username' => $data['users'][0]['username'],
    'email' => $data['users'][0]['email'],
    'role' => 'owner',
    'must_change_password' => false,
];
$adminHtml = lc_capture_include($sourceRoot . '/r-admin/index.php');
lc_assert(str_contains($adminHtml, 'Studio local'), 'admin shows studio tab');
lc_assert(str_contains($adminHtml, 'Crea una web completa desde un brief'), 'studio panel is rendered');
lc_assert(str_contains($adminHtml, 'Guardar configuración'), 'studio settings form rendered');
lc_assert(str_contains($adminHtml, 'Generar borrador con LM Studio'), 'studio generate button rendered');
lc_assert(str_contains($adminHtml, 'Agency Console'), 'admin renders white-label brand name');
lc_assert(str_contains($adminHtml, 'Private client portal'), 'admin renders white-label brand tagline');
lc_assert(str_contains($adminHtml, 'Modo cliente'), 'admin renders client mode toggle');

// Include site settings screen
$_GET = ['tab' => 'site'];
$_SERVER['REQUEST_METHOD'] = 'GET';
$siteHtml = lc_capture_include($sourceRoot . '/r-admin/index.php');
lc_assert(str_contains($siteHtml, 'Perfil visual'), 'site settings render visual profile selector');
lc_assert(str_contains($siteHtml, 'Tipografía'), 'site settings render font pairing selector');
lc_assert(str_contains($siteHtml, 'CSS personalizado del sitio'), 'site settings render custom css editor');
lc_assert(str_contains($siteHtml, 'Google Analytics 4'), 'site settings render analytics provider selector');
lc_assert(str_contains($siteHtml, 'ID / dominio analytics'), 'site settings render analytics id field');
lc_assert(str_contains($siteHtml, 'Activar white-label para agencias'), 'site settings render white-label toggle');
lc_assert(str_contains($siteHtml, 'Nombre de marca del admin'), 'site settings render white-label brand field');
lc_assert(str_contains($siteHtml, 'Perfil de negocio'), 'site settings render business profile section');
lc_assert(str_contains($siteHtml, '/.well-known/ai.json'), 'site settings explain ai feed endpoint');

// Include business mode screens
$_GET = [];
$_POST = [];
$_SERVER['REQUEST_METHOD'] = 'GET';
unset($_SESSION['ccms_admin'], $_SESSION['ccms_pending_2fa']);
$businessLoginHtml = lc_capture_include($sourceRoot . '/mi-negocio/index.php');
lc_assert(str_contains($businessLoginHtml, 'Modo negocio'), 'business mode login screen renders');
lc_assert(str_contains($businessLoginHtml, 'Usuario o email'), 'business mode login form renders');
lc_assert(str_contains($businessLoginHtml, 'Entra para actualizar menu, precios, horarios y textos clave desde el movil.'), 'business mode login explains quick edit scope');

$clientUser = ccms_find_user($data, (string) $data['users'][3]['id']);
$_GET = ['page' => 'casa-maria'];
$_SESSION['ccms_admin'] = [
    'id' => $clientUser['id'],
    'username' => $clientUser['username'],
    'email' => $clientUser['email'],
    'role' => 'client',
    'must_change_password' => false,
];
$businessDashboardHtml = lc_capture_include($sourceRoot . '/mi-negocio/index.php');
lc_assert(str_contains($businessDashboardHtml, 'Textos y fotos'), 'business mode dashboard groups capsule quick edits');
lc_assert(str_contains($businessDashboardHtml, 'Menu del dia'), 'business mode dashboard groups menu quick edits');
lc_assert(str_contains($businessDashboardHtml, 'Editar'), 'business mode dashboard renders edit actions');

$_GET = ['page' => 'casa-maria', 'edit' => 'menu-business'];
$businessMenuEditHtml = lc_capture_include($sourceRoot . '/mi-negocio/index.php');
lc_assert(str_contains($businessMenuEditHtml, 'Guardar cambios'), 'business mode live data edit screen renders save action');
lc_assert(str_contains($businessMenuEditHtml, 'Seccion 1'), 'business mode menu edit screen renders menu sections');
lc_assert(str_contains($businessMenuEditHtml, 'Pollo al horno'), 'business mode menu edit screen loads current live data');

$_GET = ['page' => 'casa-maria', 'edit' => 'hero-business'];
$businessHeroEditHtml = lc_capture_include($sourceRoot . '/mi-negocio/index.php');
lc_assert(str_contains($businessHeroEditHtml, 'Texto principal'), 'business mode capsule edit screen renders block label');
lc_assert(str_contains($businessHeroEditHtml, 'Title'), 'business mode capsule edit screen renders configured field labels');
lc_assert(str_contains($businessHeroEditHtml, '/uploads/demo.png'), 'business mode capsule edit screen loads existing image value');

// Include ai feed endpoint
$_GET = [];
$_POST = [];
$_SERVER['REQUEST_METHOD'] = 'GET';
$aiEndpointHtml = lc_capture_include($sourceRoot . '/ai-json.php');
lc_assert(str_contains($aiEndpointHtml, '"schema_type":"Restaurant"'), 'ai feed endpoint renders schema type');
lc_assert(str_contains($aiEndpointHtml, '"slot":"business.menu.primary"'), 'ai feed endpoint renders linked live data slots');

$_GET = ['tab' => 'extensions'];
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SESSION['ccms_admin'] = [
    'id' => $data['users'][0]['id'],
    'username' => $data['users'][0]['username'],
    'email' => $data['users'][0]['email'],
    'role' => 'owner',
    'must_change_password' => false,
];
$extensionsHtml = lc_capture_include($sourceRoot . '/r-admin/index.php');
lc_assert(str_contains($extensionsHtml, 'Extensiones ligeras del sitio'), 'extensions tab renders');
lc_assert(str_contains($extensionsHtml, 'Announcement Chip'), 'extensions view shows available plugin');
lc_assert(str_contains($extensionsHtml, 'Permitir trusted plugins PHP'), 'extensions view shows trusted plugins toggle');

// Include login screen in pending 2FA mode
$_GET = ['step' => '2fa'];
unset($_SESSION['ccms_admin']);
$_SESSION['ccms_pending_2fa'] = [
    'id' => $data['users'][0]['id'],
    'username' => $data['users'][0]['username'],
    'email' => $data['users'][0]['email'],
    'role' => 'owner',
];
$twoFactorHtml = lc_capture_include($sourceRoot . '/r-admin/index.php');
lc_assert(str_contains($twoFactorHtml, 'Verificación en dos pasos'), 'pending 2fa screen renders');
lc_assert(str_contains($twoFactorHtml, 'Código 2FA'), 'pending 2fa form renders');
lc_assert(str_contains($twoFactorHtml, 'Agency Console'), 'pending 2fa screen uses white-label branding');
unset($_SESSION['ccms_pending_2fa']);

// Include login screen in password-reset mode
$data = ccms_load_data();
$_GET = ['reset' => ccms_create_password_reset_token($data, $data['users'][1], $data['users'][0])];
ccms_save_data($data);
$resetHtml = lc_capture_include($sourceRoot . '/r-admin/index.php');
lc_assert(str_contains($resetHtml, 'Restablecer contraseña'), 'password reset screen renders');
lc_assert(str_contains($resetHtml, 'Guardar contraseña'), 'password reset form renders');

// Include admin as viewer
$_GET = ['tab' => 'pages'];
$_SESSION['ccms_admin'] = [
    'id' => $data['users'][2]['id'],
    'username' => $data['users'][2]['username'],
    'email' => $data['users'][2]['email'],
    'role' => 'viewer',
    'must_change_password' => false,
];
$viewerHtml = lc_capture_include($sourceRoot . '/r-admin/index.php');
lc_assert(str_contains($viewerHtml, 'Modo solo lectura'), 'viewer sees read-only banner');
lc_assert(!str_contains($viewerHtml, 'Generar borrador con LM Studio'), 'viewer does not see studio generate form');
lc_assert(!str_contains($viewerHtml, 'Guardar página'), 'viewer does not see save page button');

// Include pages admin as owner and verify client quick actions
$_GET = ['tab' => 'pages'];
$_SESSION['ccms_admin'] = [
    'id' => $data['users'][0]['id'],
    'username' => $data['users'][0]['username'],
    'email' => $data['users'][0]['email'],
    'role' => 'owner',
    'must_change_password' => false,
];
$pagesHtml = lc_capture_include($sourceRoot . '/r-admin/index.php');
lc_assert(str_contains($pagesHtml, 'Textos y fotos'), 'pages admin renders client quick actions');
lc_assert(str_contains($pagesHtml, 'Empieza por aquí'), 'pages admin renders quick start guide');
lc_assert(str_contains($pagesHtml, 'Cómo editar desde la vista previa'), 'pages admin renders preview helper guidance');
lc_assert(str_contains($pagesHtml, 'data-tab-target="builder"') && str_contains($pagesHtml, 'Secciones'), 'pages admin uses friendly sections label');
lc_assert(str_contains($pagesHtml, 'data-tab-target="publish"'), 'pages admin keeps publish tab');
lc_assert(str_contains($pagesHtml, 'id="clientModeToggle"'), 'pages admin includes client mode button');

// Include admin as forced-password user
$_GET = ['tab' => 'pages'];
$data = ccms_load_data();
$editorIndex = ccms_find_user_index($data, (string) $data['users'][1]['id']);
$data['users'][$editorIndex]['must_change_password'] = true;
ccms_save_data($data);
$_SESSION['ccms_admin'] = [
    'id' => $data['users'][1]['id'],
    'username' => $data['users'][1]['username'],
    'email' => $data['users'][1]['email'],
    'role' => 'editor',
    'must_change_password' => true,
];
$forcedHtml = lc_capture_include($sourceRoot . '/r-admin/index.php');
lc_assert(str_contains($forcedHtml, 'Tu cuenta usa una contraseña temporal'), 'forced-password banner is visible');
lc_assert(str_contains($forcedHtml, 'Guardar nueva contraseña'), 'forced-password form is rendered');
lc_assert(!str_contains($forcedHtml, 'Generar borrador con LM Studio'), 'forced-password mode hides the studio view');

// Include account tab with 2FA enabled
$_GET = ['tab' => 'account'];
$_SESSION['ccms_admin'] = [
    'id' => $data['users'][0]['id'],
    'username' => $data['users'][0]['username'],
    'email' => $data['users'][0]['email'],
    'role' => 'owner',
    'must_change_password' => false,
    'totp_enabled' => true,
];
$data = ccms_load_data();
$ownerIndex = ccms_find_user_index($data, (string) $data['users'][0]['id']);
$data['users'][$ownerIndex]['totp_secret'] = ccms_generate_totp_secret();
$data['users'][$ownerIndex]['totp_enabled'] = true;
ccms_save_data($data);
$account2faHtml = lc_capture_include($sourceRoot . '/r-admin/index.php');
lc_assert(str_contains($account2faHtml, 'Autenticación en dos pasos'), 'account tab renders 2fa section');
lc_assert(str_contains($account2faHtml, 'Desactivar 2FA'), 'account tab renders disable 2fa action');
$totpUri = ccms_totp_otpauth_uri($data['users'][0], ccms_generate_totp_secret());
lc_assert(str_contains($totpUri, rawurlencode('Agency Console')), 'totp uri uses white-label issuer');

// Include admin audit tab as owner
$_GET = ['tab' => 'audit'];
$_SESSION['ccms_admin'] = [
    'id' => $data['users'][0]['id'],
    'username' => $data['users'][0]['username'],
    'email' => $data['users'][0]['email'],
    'role' => 'owner',
    'must_change_password' => false,
];
$auditHtml = lc_capture_include($sourceRoot . '/r-admin/index.php');
lc_assert(str_contains($auditHtml, 'Actividad reciente'), 'audit tab renders for owner');
lc_assert(str_contains($auditHtml, 'Synthetic audit entry'), 'audit tab includes stored entries');

// Include admin inbox tab as owner
$_GET = ['tab' => 'inbox'];
$_SESSION['ccms_admin'] = [
    'id' => $data['users'][0]['id'],
    'username' => $data['users'][0]['username'],
    'email' => $data['users'][0]['email'],
    'role' => 'owner',
    'must_change_password' => false,
];
$inboxHtml = lc_capture_include($sourceRoot . '/r-admin/index.php');
lc_assert(str_contains($inboxHtml, 'Contactos recibidos'), 'inbox tab renders');
lc_assert(str_contains($inboxHtml, 'Lead Fixture'), 'inbox tab shows stored lead');
lc_assert(str_contains($inboxHtml, 'Guardar estado'), 'inbox tab allows status updates');

// Include admin posts tab as owner
$_GET = ['tab' => 'posts', 'post' => 'how-founders-should-approach-contract-review'];
$_SESSION['ccms_admin'] = [
    'id' => $data['users'][0]['id'],
    'username' => $data['users'][0]['username'],
    'email' => $data['users'][0]['email'],
    'role' => 'owner',
    'must_change_password' => false,
];
$postsAdminHtml = lc_capture_include($sourceRoot . '/r-admin/index.php');
lc_assert(str_contains($postsAdminHtml, 'Nuevo post'), 'posts tab renders creation form');
lc_assert(str_contains($postsAdminHtml, 'How founders should approach contract review'), 'posts tab renders selected post title');
lc_assert(str_contains($postsAdminHtml, 'Abrir post'), 'posts tab renders public post action');

// Media path helpers
lc_assert(ccms_public_upload_url('demo.png') === '/uploads/demo.png', 'public upload url helper works');
lc_assert(str_contains(ccms_capsule_media_url('', 'demo-seed', 800, 600), 'picsum.photos'), 'empty media url falls back to picsum');
lc_assert(ccms_capsule_media_url('/uploads/demo.png', 'seed', 800, 600) === '/uploads/demo.png', 'existing media url preserved');
$optimizedFragment = ccms_optimize_public_images_html('<figure><img src="/uploads/demo.png" alt="Demo"></figure>');
lc_assert(str_contains($optimizedFragment, 'loading="lazy"'), 'optimized public images add lazy loading');
lc_assert(str_contains($optimizedFragment, 'decoding="async"'), 'optimized public images add async decoding');
if (!empty($generatedImageVariants['generated']) || !empty($generatedImageVariants['webp_generated'])) {
    lc_assert(str_contains($optimizedFragment, 'srcset='), 'optimized public images add srcset when variants exist');
}
if (!empty($generatedImageVariants['webp_generated'])) {
    lc_assert(str_contains($optimizedFragment, '<picture>'), 'optimized public images wrap local uploads in picture when webp variants exist');
}

// Style helpers
$styleAttr = ccms_capsule_section_style_attr(['style' => ['padding_top' => 80, 'background' => '#fff']], 'padding:10px');
lc_assert(str_contains($styleAttr, 'padding-top:80px'), 'section style attr includes padding top');
lc_assert(str_contains($styleAttr, 'background:#fff'), 'section style attr includes background');
$buttonStyleAttr = ccms_capsule_section_style_attr(['style' => ['button_bg' => '#112233', 'button_text_color' => '#ffffff']], '');
lc_assert(str_contains($buttonStyleAttr, '--ccms-button-bg:#112233'), 'section style attr includes button background var');
lc_assert(str_contains($buttonStyleAttr, '--ccms-button-color:#ffffff'), 'section style attr includes button text color var');
$backgroundEffectAttr = ccms_capsule_section_style_attr(['style' => ['background_effect' => 'dots']], '');
lc_assert(str_contains($backgroundEffectAttr, 'data-ccms-bg="dots"'), 'section style attr includes background effect attribute');
lc_assert(ccms_capsule_button_classes(['style' => []], false) === 'ccms-btn', 'button classes default to primary');
lc_assert(ccms_capsule_button_classes(['style' => ['button_variant' => 'ghost']], false) === 'ccms-btn ccms-btn--ghost', 'button classes support ghost variant');
$innerAttr = ccms_capsule_inner_style_attr(['style' => ['content_width' => 900]]);
lc_assert(str_contains($innerAttr, '900px'), 'inner style attr includes content width');

// Render helper coverage
lc_assert(ccms_capsule_link_text(['text' => 'Hello']) === 'Hello', 'link text helper prefers text');
lc_assert(ccms_capsule_link_text(['label' => 'Hello']) === 'Hello', 'link text helper falls back to label');
lc_assert(str_contains(ccms_capsule_bool_icon(true, ccms_capsule_style($capsule)), '&#10003;'), 'bool icon renders check');
lc_assert(ccms_capsule_stars(3) === '&#9733;&#9733;&#9733;', 'stars helper renders stars');

// Page lookup and indices
$pageIndex = ccms_find_page_index($data, (string) $pageRecord['id']);
lc_assert($pageIndex !== null, 'find page index works');
lc_assert((ccms_find_page($data, (string) $pageRecord['id'])['title'] ?? '') === 'Corporate Law for fast-moving businesses', 'find page returns generated page');
lc_assert(ccms_find_user_index($data, (string) $data['users'][1]['id']) !== null, 'find user index works');
lc_assert((ccms_find_user($data, (string) $data['users'][1]['id'])['username'] ?? '') === 'editor-user', 'find user works');

// Revision growth and cap
$page = $pageRecord;
for ($i = 0; $i < 25; $i++) {
    $page['title'] = 'Revision ' . $i;
    ccms_push_page_revision($page, 'Revision ' . $i, 20);
}
lc_assert(count($page['revisions']) === 20, 'revisions are capped at 20');

// Login attempts cleanup file exists after failed attempts
ccms_register_login_failure('owner', '127.0.0.1');
lc_assert(is_file(ccms_login_attempts_file()), 'login attempts file created');
$attempts = ccms_load_login_attempts();
lc_assert(count($attempts) >= 1, 'login attempts stored');
ccms_clear_login_attempts('owner', '127.0.0.1');

// Security helpers and backup import protections
$etagSeedPage = [
    'id' => 'page_test',
    'slug' => 'test',
    'title' => 'Test',
    'updated_at' => '2026-03-12T00:00:00Z',
    'status' => 'published',
    'html_content' => '<p>Test</p>',
    'capsule_json' => '',
];
$etagSeedSite = [
    'title' => 'Site',
    'tagline' => 'Tagline',
    'footer_text' => 'Footer',
    'theme_preset' => 'warm',
    'custom_css' => '',
    'colors' => ['bg' => '#fff'],
];
$etagA = '"' . hash('sha256', json_encode([
    'page' => $etagSeedPage,
    'site' => $etagSeedSite,
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) . '"';
$etagB = '"' . hash('sha256', json_encode([
    'page' => array_merge($etagSeedPage, ['title' => 'Changed']),
    'site' => $etagSeedSite,
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) . '"';
lc_assert($etagA !== $etagB, 'etag seed changes when page content changes');

$pageBySlug = ccms_load_page_by_slug((string) ($pageRecord['slug'] ?? ''));
lc_assert(is_array($pageBySlug) && ($pageBySlug['id'] ?? '') === ($pageRecord['id'] ?? ''), 'load_page_by_slug returns page');
$siteConfig = ccms_load_site_config();
lc_assert(is_array($siteConfig) && ($siteConfig['title'] ?? '') === ($data['site']['title'] ?? ''), 'load_site_config returns site settings');

$uploadsDir = ccms_uploads_dir();
@mkdir($uploadsDir, 0775, true);
$legacyUpload = $uploadsDir . DIRECTORY_SEPARATOR . 'legacy-demo.txt';
file_put_contents($legacyUpload, 'legacy');
$importPayload = ccms_export_backup_payload($data);
$importPayload['uploads'] = [
    [
        'filename' => 'fresh-demo.txt',
        'content_base64' => base64_encode('fresh'),
    ],
];
$restoredData = ccms_import_backup_payload($importPayload);
lc_assert(is_array($restoredData) && !empty($restoredData['site']), 'import backup returns normalized site data');
$freshUpload = $uploadsDir . DIRECTORY_SEPARATOR . 'fresh-demo.txt';
lc_assert(is_file($freshUpload), 'import backup restores uploaded file payloads');
$backupDirs = glob($uploadsDir . '_backup_*') ?: [];
lc_assert(count($backupDirs) >= 1, 'import backup preserves previous uploads in timestamped backup directory');
$backupContainsLegacy = false;
foreach ($backupDirs as $backupDir) {
    if (is_file($backupDir . DIRECTORY_SEPARATOR . 'legacy-demo.txt')) {
        $backupContainsLegacy = true;
        break;
    }
}
lc_assert($backupContainsLegacy, 'timestamped upload backup contains previous upload file');

echo 'DEEP TEST OK - ' . $GLOBALS['lc_assert_count'] . " checks passed.\n";

$buffer = ob_get_clean();
if (is_string($buffer) && $buffer !== '') {
    fwrite(STDOUT, $buffer);
}
