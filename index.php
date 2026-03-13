<?php
declare(strict_types=1);

require_once __DIR__ . '/src/bootstrap.php';

if (!ccms_is_installed()) {
    ccms_redirect('/install.php');
}

$data = ccms_load_data();
$publicSite = ccms_public_site_config($data);
$path = trim(ccms_request_path(), '/');
$menuPages = ccms_menu_pages($data);

if ($path === 'blog') {
    echo ccms_render_blog_archive_page($publicSite, ccms_posts_published($data), $menuPages);
    exit;
}

if (preg_match('#^blog/category/([^/]+)$#', $path, $matches)) {
    $categorySlug = rawurldecode((string) $matches[1]);
    $posts = ccms_posts_for_category_slug($data, $categorySlug);
    $categoryLabel = '';
    foreach (ccms_blog_categories($data) as $category) {
        if (ccms_taxonomy_slug($category) === $categorySlug) {
            $categoryLabel = $category;
            break;
        }
    }
    if ($categoryLabel === '') {
        http_response_code(404);
        echo ccms_render_public_page(
            $publicSite,
            [
                'title' => 'Categoría no encontrada',
                'meta_title' => 'Categoría no encontrada',
                'meta_description' => 'La categoría solicitada no existe.',
                'html_content' => '<section style="padding:64px 32px;text-align:center"><h1>Categoría no encontrada</h1><p>La categoría que has pedido no existe.</p></section>',
            ],
            $menuPages
        );
        exit;
    }
    echo ccms_render_blog_archive_page($publicSite, $posts, $menuPages, $categoryLabel, null);
    exit;
}

if (preg_match('#^blog/tag/([^/]+)$#', $path, $matches)) {
    $tagSlug = rawurldecode((string) $matches[1]);
    $posts = ccms_posts_for_tag_slug($data, $tagSlug);
    $tagLabel = '';
    foreach (ccms_blog_tags($data) as $tag) {
        if (ccms_taxonomy_slug($tag) === $tagSlug) {
            $tagLabel = $tag;
            break;
        }
    }
    if ($tagLabel === '') {
        http_response_code(404);
        echo ccms_render_public_page(
            $publicSite,
            [
                'title' => 'Etiqueta no encontrada',
                'meta_title' => 'Etiqueta no encontrada',
                'meta_description' => 'La etiqueta solicitada no existe.',
                'html_content' => '<section style="padding:64px 32px;text-align:center"><h1>Etiqueta no encontrada</h1><p>La etiqueta que has pedido no existe.</p></section>',
            ],
            $menuPages
        );
        exit;
    }
    echo ccms_render_blog_archive_page($publicSite, $posts, $menuPages, null, $tagLabel);
    exit;
}

if (preg_match('#^blog/([^/]+)$#', $path, $matches)) {
    $postSlug = rawurldecode((string) $matches[1]);
    $post = ccms_post_by_slug($data, $postSlug);
    if (!$post) {
        http_response_code(404);
        echo ccms_render_public_page(
            $publicSite,
            [
                'title' => 'Artículo no encontrado',
                'meta_title' => 'Artículo no encontrado',
                'meta_description' => 'El artículo solicitado no existe.',
                'html_content' => '<section style="padding:64px 32px;text-align:center"><h1>Artículo no encontrado</h1><p>El artículo que has pedido no existe o todavía no está publicado.</p></section>',
            ],
            $menuPages
        );
        exit;
    }
    echo ccms_render_blog_post_page($publicSite, $post, $menuPages);
    exit;
}

$page = $path === '' ? ccms_homepage($data) : ccms_page_by_slug($data, $path);

if (!$page) {
    http_response_code(404);
    echo ccms_render_public_page(
        $publicSite,
        [
            'title' => 'Página no encontrada',
            'meta_title' => 'Página no encontrada',
            'meta_description' => 'La página solicitada no existe.',
            'html_content' => '<section style="padding:64px 32px;text-align:center"><h1>Página no encontrada</h1><p>La URL que has pedido no existe o todavía no está publicada.</p></section>',
        ],
        $menuPages
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
            'font_pairing' => (string) ($data['site']['font_pairing'] ?? ''),
            'custom_css' => (string) ($data['site']['custom_css'] ?? ''),
            'business_profile' => is_array($data['site']['business_profile'] ?? null) ? $data['site']['business_profile'] : [],
            'colors' => is_array($data['site']['colors'] ?? null) ? $data['site']['colors'] : [],
        ],
        'live_data' => $data['live_data'] ?? [],
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

echo ccms_render_public_page($publicSite, $page, $menuPages);
