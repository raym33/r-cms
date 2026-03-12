<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/src/bootstrap.php';
ccms_send_admin_headers();

$admin = ccms_require_admin();
unset($admin);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'Method not allowed';
    exit;
}

try {
    ccms_verify_same_origin_request();
    ccms_verify_csrf();
    $data = ccms_load_data();
    $pageId = trim((string) ($_POST['page_id'] ?? ''));
    $page = null;

    if ($pageId !== '') {
        $index = ccms_find_page_index($data, $pageId);
        if ($index !== null) {
            $page = $data['pages'][$index];
            $page['title'] = trim((string) ($_POST['title'] ?? $page['title'] ?? '')) ?: (string) ($page['title'] ?? 'Untitled');
            $page['slug'] = ccms_slugify((string) ($_POST['slug'] ?? $page['slug'] ?? $page['title'] ?? 'page'));
            $page['menu_label'] = trim((string) ($_POST['menu_label'] ?? $page['menu_label'] ?? $page['title'] ?? '')) ?: $page['title'];
            $page['meta_title'] = trim((string) ($_POST['meta_title'] ?? $page['meta_title'] ?? ''));
            $page['meta_description'] = trim((string) ($_POST['meta_description'] ?? $page['meta_description'] ?? ''));
            $page['capsule_json'] = (string) ($_POST['capsule_json'] ?? $page['capsule_json'] ?? '{}');
            $page['html_content'] = ccms_sanitize_html_fragment((string) ($_POST['html_content'] ?? $page['html_content'] ?? ''));
            $page['status'] = (string) ($_POST['status'] ?? $page['status'] ?? 'draft') === 'published' ? 'published' : 'draft';
            $page['show_in_menu'] = isset($_POST['show_in_menu']);
            $page['is_homepage'] = isset($_POST['is_homepage']);
            $data['pages'][$index] = $page;

            if ($page['is_homepage']) {
                foreach ($data['pages'] as $otherIndex => $candidate) {
                    if ($otherIndex !== $index) {
                        $data['pages'][$otherIndex]['is_homepage'] = false;
                    }
                }
            }
        }
    }

    if (!$page) {
        $page = [
            'id' => ccms_next_id('page'),
            'title' => trim((string) ($_POST['title'] ?? 'Preview')) ?: 'Preview',
            'slug' => ccms_slugify((string) ($_POST['slug'] ?? 'preview')),
            'status' => 'draft',
            'is_homepage' => false,
            'show_in_menu' => false,
            'menu_label' => trim((string) ($_POST['menu_label'] ?? 'Preview')) ?: 'Preview',
            'meta_title' => trim((string) ($_POST['meta_title'] ?? '')),
            'meta_description' => trim((string) ($_POST['meta_description'] ?? '')),
            'capsule_json' => (string) ($_POST['capsule_json'] ?? '{}'),
            'html_content' => ccms_sanitize_html_fragment((string) ($_POST['html_content'] ?? '')),
        ];
        $data['pages'][] = $page;
    }

    header('Content-Type: text/html; charset=utf-8');
    echo ccms_admin_preview_html(ccms_render_public_page($data['site'], $page, ccms_menu_pages($data)));
} catch (Throwable $e) {
    http_response_code(400);
    header('Content-Type: text/plain; charset=utf-8');
    echo $e->getMessage();
}
