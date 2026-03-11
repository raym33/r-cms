<?php
declare(strict_types=1);

require_once __DIR__ . '/src/bootstrap.php';

if (!ccms_is_installed()) {
    ccms_redirect('/install.php');
}

$data = ccms_load_data();
$path = trim(ccms_request_path(), '/');
$page = $path === '' ? ccms_homepage($data) : ccms_page_by_slug($data, $path);

if (!$page) {
    http_response_code(404);
    echo ccms_render_public_page(
        $data['site'],
        [
            'title' => 'Página no encontrada',
            'meta_title' => 'Página no encontrada',
            'meta_description' => 'La página solicitada no existe.',
            'html_content' => '<section style="padding:64px 32px;text-align:center"><h1>Página no encontrada</h1><p>La URL que has pedido no existe o todavía no está publicada.</p></section>',
        ],
        ccms_menu_pages($data)
    );
    exit;
}

echo ccms_render_public_page($data['site'], $page, ccms_menu_pages($data));

