<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/src/bootstrap.php';

ccms_send_admin_headers();

if (!ccms_is_installed()) {
    ccms_redirect('/install.php');
}

$error = '';
try {
    ccms_business_mode_handle_post();
} catch (Throwable $e) {
    $error = $e->getMessage();
}

$data = ccms_load_data();
$flash = ccms_consume_flash();
$csrfToken = ccms_csrf_token();
$currentUser = ccms_current_admin();

if ($currentUser && !ccms_user_can_access_business_mode($currentUser)) {
    ccms_logout();
    $currentUser = null;
    $error = 'Tu cuenta no tiene acceso a Modo Negocio.';
}

$pagesWithBusinessMode = ccms_business_mode_pages($data);
$selectedPage = $currentUser ? ccms_business_mode_selected_page($data) : null;
$businessItems = $selectedPage ? ($selectedPage['_business_items'] ?? ccms_business_mode_collect_items($selectedPage, $data['live_data'] ?? [])) : [];
$groupedBusinessItems = [];
foreach ($businessItems as $item) {
    $groupedBusinessItems[$item['category'] ?? 'textos'][] = $item;
}
$selectedBusinessItem = null;
if ($selectedPage && trim((string) ($_GET['edit'] ?? '')) !== '') {
    $selectedBusinessItem = ccms_business_mode_find_item($selectedPage, $data['live_data'] ?? [], trim((string) $_GET['edit']));
    if (!$selectedBusinessItem) {
        $error = $error !== '' ? $error : 'No se ha encontrado el bloque solicitado.';
    }
}

if (!$currentUser) {
    $businessTitle = 'Mi negocio | Login';
    ob_start();
    require __DIR__ . '/views/login.php';
    $businessBody = ob_get_clean();
    require __DIR__ . '/views/layout.php';
    return;
}

if (!empty($currentUser['must_change_password'])) {
    $businessTitle = 'Mi negocio | Cambiar contrasena';
    ob_start();
    require __DIR__ . '/views/password.php';
    $businessBody = ob_get_clean();
    require __DIR__ . '/views/layout.php';
    return;
}

$businessTitle = $selectedBusinessItem
    ? 'Mi negocio | ' . (string) ($selectedBusinessItem['label'] ?? 'Editar')
    : 'Mi negocio';

ob_start();
if ($selectedBusinessItem) {
    require __DIR__ . '/views/edit_slot.php';
} else {
    require __DIR__ . '/views/dashboard.php';
}
$businessBody = ob_get_clean();
require __DIR__ . '/views/layout.php';
