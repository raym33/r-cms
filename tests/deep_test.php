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

$sourceRoot = realpath(__DIR__ . '/..');
$runtimeRoot = sys_get_temp_dir() . '/linuxcms_deep_test_runtime';
lc_rrmdir($runtimeRoot);
lc_rcopy((string) $sourceRoot, $runtimeRoot);
putenv('CCMS_ROOT=' . $runtimeRoot);

require $sourceRoot . '/src/bootstrap.php';

$checks = 0;

// Helpers and storage basics
lc_assert(is_dir(ccms_root_path()), 'runtime root exists');
lc_assert(str_contains(ccms_root_path('data'), 'linuxcms_deep_test_runtime'), 'root override works');
lc_assert(is_array(ccms_default_data()), 'default data returns array');
$defaults = ccms_default_data();
lc_assert(($defaults['site']['title'] ?? '') === 'LinuxCMS', 'default site title is LinuxCMS');
lc_assert(isset($defaults['local_ai']['endpoint']), 'default data includes local_ai settings');
lc_assert(ccms_storage_runtime_info()['driver'] === 'json', 'default storage driver is json');
lc_assert(is_bool(ccms_storage_runtime_info()['sqlite_available'] ?? null), 'storage runtime exposes sqlite availability flag');
lc_assert(ccms_slugify('Hello Premium World') === 'hello-premium-world', 'slugify normalizes titles');
lc_assert(str_contains(ccms_base_url(), '127.0.0.1'), 'base url helper uses current host');

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
foreach ($data['pages'] as $idx => $page) {
    $data['pages'][$idx]['is_homepage'] = (($page['id'] ?? '') === ($pageRecord['id'] ?? ''));
    if (($page['id'] ?? '') === ($pageRecord['id'] ?? '')) {
        $data['pages'][$idx]['status'] = 'published';
        $data['pages'][$idx]['show_in_menu'] = true;
    }
}
ccms_save_data($data);
$data = ccms_load_data();
$homepage = ccms_homepage($data);
ccms_push_audit_log($data, 'test.event', 'Synthetic audit entry', ccms_current_admin(), ['source' => 'deep_test']);
ccms_save_data($data);
$data = ccms_load_data();
lc_assert(($homepage['slug'] ?? '') === ($pageRecord['slug'] ?? ''), 'homepage resolves generated page');
lc_assert(ccms_page_by_slug($data, (string) $pageRecord['slug']) !== null, 'page by slug works');
lc_assert(count(ccms_menu_pages($data)) >= 1, 'menu pages list contains published page');
lc_assert(count($data['audit_logs'] ?? []) >= 1, 'audit log entries can be stored');
lc_assert(($data['audit_logs'][0]['action'] ?? '') === 'test.event', 'audit log preserves latest action');

// Block render coverage
$capsule = $fallback['page']['capsule'];
$supported = ccms_capsule_supported_blocks();
foreach (['sticky_header','hero_fullscreen','split_image_right','features','testimonial_cards','faq','lead_form','footer_multi'] as $type) {
    lc_assert(in_array($type, $supported, true), 'supported blocks include ' . $type);
}
lc_assert(ccms_capsule_can_render($capsule) === true, 'generated capsule is fully renderable');
$bodyHtml = ccms_render_capsule_body($capsule);
lc_assert(str_contains($bodyHtml, 'Corporate Law for fast-moving businesses'), 'render body contains generated title');
lc_assert(str_contains($bodyHtml, 'Book a consultation'), 'render body contains CTA');
lc_assert(str_contains($bodyHtml, 'Start the conversation'), 'render body contains form block');
$previewHtml = ccms_admin_preview_html($publicHtml = ccms_render_public_page($data['site'], $homepage, ccms_menu_pages($data)));
lc_assert(str_contains($previewHtml, 'ccms-preview-action'), 'admin preview includes inline action hook');
lc_assert(str_contains($previewHtml, 'Edit content'), 'admin preview includes edit content action');
lc_assert(str_contains($previewHtml, 'Edit media'), 'admin preview includes edit media action');
lc_assert(str_contains($previewHtml, 'Edit style'), 'admin preview includes edit style action');
$publicHtml = ccms_render_public_page($data['site'], $homepage, ccms_menu_pages($data));
lc_assert(str_contains($publicHtml, '<!doctype html>'), 'public page is full html');
lc_assert(str_contains($publicHtml, 'Corporate Law for fast-moving businesses'), 'public page contains generated title');
lc_assert(str_contains($publicHtml, 'OTM Lawyers'), 'public page contains business name');
lc_assert(str_contains($publicHtml, 'Book a consultation'), 'public page contains CTA text');
lc_assert(ccms_capsule_can_render(['blocks' => [['type' => 'unknown_block']]]) === false, 'unknown block capsule falls back correctly');

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
$simpleHtml = ccms_render_public_page($data['site'], $simplePage, ccms_menu_pages($data));
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
putenv('CCMS_ROOT=' . $installRoot);
ob_start();
$_SERVER['REQUEST_METHOD'] = 'GET';
include $sourceRoot . '/install.php';
$installHtml = ob_get_clean();
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
ob_start();
include $sourceRoot . '/r-admin/index.php';
$adminHtml = ob_get_clean();
lc_assert(str_contains($adminHtml, 'Studio local'), 'admin shows studio tab');
lc_assert(str_contains($adminHtml, 'Crea una web completa desde un brief'), 'studio panel is rendered');
lc_assert(str_contains($adminHtml, 'Guardar configuración'), 'studio settings form rendered');
lc_assert(str_contains($adminHtml, 'Generar borrador con LM Studio'), 'studio generate button rendered');
lc_assert(str_contains($adminHtml, 'Cómo funciona LinuxCMS'), 'admin renders LinuxCMS guidance copy');

// Include login screen in pending 2FA mode
$_GET = ['step' => '2fa'];
unset($_SESSION['ccms_admin']);
$_SESSION['ccms_pending_2fa'] = [
    'id' => $data['users'][0]['id'],
    'username' => $data['users'][0]['username'],
    'email' => $data['users'][0]['email'],
    'role' => 'owner',
];
ob_start();
include $sourceRoot . '/r-admin/index.php';
$twoFactorHtml = ob_get_clean();
lc_assert(str_contains($twoFactorHtml, 'Verificación en dos pasos'), 'pending 2fa screen renders');
lc_assert(str_contains($twoFactorHtml, 'Código 2FA'), 'pending 2fa form renders');
unset($_SESSION['ccms_pending_2fa']);

// Include login screen in password-reset mode
$data = ccms_load_data();
$_GET = ['reset' => ccms_create_password_reset_token($data, $data['users'][1], $data['users'][0])];
ccms_save_data($data);
ob_start();
include $sourceRoot . '/r-admin/index.php';
$resetHtml = ob_get_clean();
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
ob_start();
include $sourceRoot . '/r-admin/index.php';
$viewerHtml = ob_get_clean();
lc_assert(str_contains($viewerHtml, 'Modo solo lectura'), 'viewer sees read-only banner');
lc_assert(!str_contains($viewerHtml, 'Generar borrador con LM Studio'), 'viewer does not see studio generate form');
lc_assert(!str_contains($viewerHtml, 'Guardar página'), 'viewer does not see save page button');

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
ob_start();
include $sourceRoot . '/r-admin/index.php';
$forcedHtml = ob_get_clean();
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
ob_start();
include $sourceRoot . '/r-admin/index.php';
$account2faHtml = ob_get_clean();
lc_assert(str_contains($account2faHtml, 'Autenticación en dos pasos'), 'account tab renders 2fa section');
lc_assert(str_contains($account2faHtml, 'Desactivar 2FA'), 'account tab renders disable 2fa action');

// Include admin audit tab as owner
$_GET = ['tab' => 'audit'];
$_SESSION['ccms_admin'] = [
    'id' => $data['users'][0]['id'],
    'username' => $data['users'][0]['username'],
    'email' => $data['users'][0]['email'],
    'role' => 'owner',
    'must_change_password' => false,
];
ob_start();
include $sourceRoot . '/r-admin/index.php';
$auditHtml = ob_get_clean();
lc_assert(str_contains($auditHtml, 'Actividad reciente'), 'audit tab renders for owner');
lc_assert(str_contains($auditHtml, 'Synthetic audit entry'), 'audit tab includes stored entries');

// Media path helpers
lc_assert(ccms_public_upload_url('demo.png') === '/uploads/demo.png', 'public upload url helper works');
lc_assert(str_contains(ccms_capsule_media_url('', 'demo-seed', 800, 600), 'picsum.photos'), 'empty media url falls back to picsum');
lc_assert(ccms_capsule_media_url('/uploads/demo.png', 'seed', 800, 600) === '/uploads/demo.png', 'existing media url preserved');

// Style helpers
$styleAttr = ccms_capsule_section_style_attr(['style' => ['padding_top' => 80, 'background' => '#fff']], 'padding:10px');
lc_assert(str_contains($styleAttr, 'padding-top:80px'), 'section style attr includes padding top');
lc_assert(str_contains($styleAttr, 'background:#fff'), 'section style attr includes background');
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

echo 'DEEP TEST OK - ' . $GLOBALS['lc_assert_count'] . " checks passed.\n";

$buffer = ob_get_clean();
if (is_string($buffer) && $buffer !== '') {
    fwrite(STDOUT, $buffer);
}
