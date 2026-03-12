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

if (!headers_sent()) {
    $etagPayload = json_encode([
        'page' => [
            'id' => (string) ($page['id'] ?? ''),
            'slug' => (string) ($page['slug'] ?? ''),
            'title' => (string) ($page['title'] ?? ''),
            'updated_at' => (string) ($page['updated_at'] ?? ''),
            'status' => (string) ($page['status'] ?? ''),
            'html_content' => (string) ($page['html_content'] ?? ''),
            'capsule_json' => (string) ($page['capsule_json'] ?? ''),
        ],
        'site' => [
            'title' => (string) ($data['site']['title'] ?? ''),
            'tagline' => (string) ($data['site']['tagline'] ?? ''),
            'footer_text' => (string) ($data['site']['footer_text'] ?? ''),
            'theme_preset' => (string) ($data['site']['theme_preset'] ?? ''),
            'custom_css' => (string) ($data['site']['custom_css'] ?? ''),
            'colors' => is_array($data['site']['colors'] ?? null) ? $data['site']['colors'] : [],
        ],
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '';
    $etag = '"' . hash('sha256', $etagPayload) . '"';
    header('Cache-Control: public, max-age=300');
    header('ETag: ' . $etag);
    if (strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET')) === 'GET') {
        $ifNoneMatch = trim((string) ($_SERVER['HTTP_IF_NONE_MATCH'] ?? ''));
        if ($ifNoneMatch === $etag) {
            http_response_code(304);
            exit;
        }
    }
}

echo ccms_render_public_page($data['site'], $page, ccms_menu_pages($data));
