<?php
declare(strict_types=1);

function ccms_exports_dir(): string
{
    return ccms_root_path('data/exports');
}

function ccms_rrmdir(string $dir): void
{
    if (!is_dir($dir)) {
        return;
    }
    foreach (scandir($dir) ?: [] as $entry) {
        if ($entry === '.' || $entry === '..') {
            continue;
        }
        $path = $dir . DIRECTORY_SEPARATOR . $entry;
        if (is_dir($path) && !is_link($path)) {
            ccms_rrmdir($path);
        } else {
            @unlink($path);
        }
    }
    @rmdir($dir);
}

function ccms_static_export_build(array $data): array
{
    $stamp = gmdate('Ymd-His');
    $dir = ccms_exports_dir() . DIRECTORY_SEPARATOR . 'static-site-' . $stamp . '-' . substr(bin2hex(random_bytes(4)), 0, 8);
    ccms_rrmdir($dir);
    @mkdir($dir, 0775, true);
    @mkdir($dir . DIRECTORY_SEPARATOR . 'uploads', 0775, true);

    $menuPages = ccms_menu_pages($data);
    $homepage = ccms_homepage($data);
    $pages = array_values(array_filter($data['pages'] ?? [], static function (array $page): bool {
        return ($page['status'] ?? 'draft') === 'published';
    }));

    foreach ($pages as $page) {
        $html = ccms_render_public_page($data['site'], $page, $menuPages);
        $slug = trim((string) ($page['slug'] ?? ''));
        if ($homepage && (($page['id'] ?? '') === ($homepage['id'] ?? ''))) {
            file_put_contents($dir . DIRECTORY_SEPARATOR . 'index.html', $html);
        }
        if ($slug !== '') {
            $pageDir = $dir . DIRECTORY_SEPARATOR . $slug;
            @mkdir($pageDir, 0775, true);
            file_put_contents($pageDir . DIRECTORY_SEPARATOR . 'index.html', $html);
        }
    }

    foreach (scandir(ccms_uploads_dir()) ?: [] as $entry) {
        if ($entry === '.' || $entry === '..') {
            continue;
        }
        $from = ccms_uploads_dir() . DIRECTORY_SEPARATOR . $entry;
        $to = $dir . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . $entry;
        if (is_file($from)) {
            copy($from, $to);
        }
    }

    $guide = "LinuxCMS static export\n\n";
    $guide .= "Upload the contents of this folder to the root of your basic hosting.\n";
    $guide .= "Keep index.html at the root and keep the slug folders and uploads folder together.\n";
    file_put_contents($dir . DIRECTORY_SEPARATOR . 'README-STATIC-EXPORT.txt', $guide);

    return [
        'dir' => $dir,
        'homepage' => $homepage ? ($homepage['slug'] ?? '') : '',
        'pages' => array_map(static fn(array $page): string => (string) ($page['slug'] ?? ''), $pages),
    ];
}

function ccms_static_export_zip(array $build): string
{
    if (!class_exists(ZipArchive::class)) {
        throw new RuntimeException('ZipArchive is not available in this PHP runtime.');
    }
    $dir = (string) ($build['dir'] ?? '');
    if ($dir === '' || !is_dir($dir)) {
        throw new RuntimeException('Static export directory not found.');
    }
    $zipPath = $dir . '.zip';
    $zip = new ZipArchive();
    if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
        throw new RuntimeException('Could not create the export ZIP.');
    }
    $baseLen = strlen($dir) + 1;
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );
    foreach ($iterator as $item) {
        $path = (string) $item;
        $localName = substr($path, $baseLen);
        if ($item->isDir()) {
            $zip->addEmptyDir(str_replace('\\', '/', $localName));
        } elseif ($item->isFile()) {
            $zip->addFile($path, str_replace('\\', '/', $localName));
        }
    }
    $zip->close();
    return $zipPath;
}
