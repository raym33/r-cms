<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/src/bootstrap.php';
require_once dirname(__DIR__) . '/src/admin_actions.php';

ccms_send_admin_headers();

if (!ccms_is_installed()) {
    ccms_redirect('/install.php');
}

$error = '';
try {
    ccms_admin_handle_post();
} catch (Throwable $e) {
    $error = $e->getMessage();
}

extract(ccms_build_admin_context($error), EXTR_SKIP);

if (!$currentAdmin) {
    require __DIR__ . '/views/login.php';
    return;
}

if (($currentAdmin['role'] ?? '') === 'client') {
    ccms_redirect('/mi-negocio/');
}

require __DIR__ . '/views/layout.php';
