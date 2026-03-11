<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/src/bootstrap.php';

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "This script must be run from the command line.\n");
    exit(1);
}

if (!ccms_is_installed()) {
    fwrite(STDERR, "Install the CMS first by opening /install.php\n");
    exit(1);
}

$options = getopt('', ['capsule:', 'html:', 'title::', 'slug::', 'homepage::']);

$capsulePath = $options['capsule'] ?? '';
$htmlPath = $options['html'] ?? '';
$title = trim((string) ($options['title'] ?? 'Imported Page'));
$slug = ccms_slugify((string) ($options['slug'] ?? $title));
$makeHomepage = isset($options['homepage']) && in_array(strtolower((string) $options['homepage']), ['1', 'true', 'yes'], true);

if ($capsulePath === '' || $htmlPath === '') {
    fwrite(STDERR, "Usage: php tools/import-from-aivoiceweb.php --capsule=/path/file.json --html=/path/file.html [--title=\"...\"] [--slug=\"...\"] [--homepage=true]\n");
    exit(1);
}

if (!is_file($capsulePath) || !is_file($htmlPath)) {
    fwrite(STDERR, "Capsule or HTML file not found.\n");
    exit(1);
}

$capsuleJson = file_get_contents($capsulePath);
$html = file_get_contents($htmlPath);
if ($capsuleJson === false || $html === false) {
    fwrite(STDERR, "Could not read source files.\n");
    exit(1);
}

$bodyHtml = $html;
if (preg_match('/<body[^>]*>(.*)<\/body>/is', $html, $matches) === 1) {
    $bodyHtml = trim((string) $matches[1]);
}

$data = ccms_load_data();
$pageId = ccms_next_id('page');
$capsuleMeta = json_decode($capsuleJson, true);
$metaTitle = $title;
$metaDescription = '';
if (is_array($capsuleMeta)) {
    $metaTitle = trim((string) ($capsuleMeta['meta']['page_title'] ?? '')) ?: $title;
    $metaDescription = trim((string) ($capsuleMeta['meta']['description'] ?? ''));
}
$page = [
    'id' => $pageId,
    'title' => $title,
    'slug' => $slug,
    'status' => 'published',
    'is_homepage' => $makeHomepage,
    'show_in_menu' => true,
    'menu_label' => $title,
    'meta_title' => $metaTitle,
    'meta_description' => $metaDescription,
    'capsule_json' => $capsuleJson,
    'html_content' => $bodyHtml,
    'created_at' => ccms_now_iso(),
    'updated_at' => ccms_now_iso(),
    'revisions' => [],
];
ccms_push_page_revision($page, 'Imported from aivoiceweb');
$data['pages'][] = $page;

if ($makeHomepage) {
    foreach ($data['pages'] as $index => $page) {
        if (($page['id'] ?? '') !== $pageId) {
            $data['pages'][$index]['is_homepage'] = false;
        }
    }
}

ccms_save_data($data);

fwrite(STDOUT, "Imported page: {$title}\n");
fwrite(STDOUT, "Public URL: /" . ($makeHomepage ? '' : $slug) . "\n");
fwrite(STDOUT, "Admin URL: /r-admin/?page={$slug}\n");
