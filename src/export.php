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
        return ccms_record_is_public($page);
    }));
    $posts = ccms_posts_published($data);
    $categories = ccms_blog_categories($data);
    $tags = ccms_blog_tags($data);

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

    if (!empty($posts)) {
        $blogDir = $dir . DIRECTORY_SEPARATOR . 'blog';
        @mkdir($blogDir, 0775, true);
        file_put_contents(
            $blogDir . DIRECTORY_SEPARATOR . 'index.html',
            ccms_render_blog_archive_page($data['site'], $posts, $menuPages)
        );

        foreach ($posts as $post) {
            $postSlug = trim((string) ($post['slug'] ?? ''));
            if ($postSlug === '') {
                continue;
            }
            $postDir = $blogDir . DIRECTORY_SEPARATOR . $postSlug;
            @mkdir($postDir, 0775, true);
            file_put_contents(
                $postDir . DIRECTORY_SEPARATOR . 'index.html',
                ccms_render_blog_post_page($data['site'], $post, $menuPages)
            );
        }

        if (!empty($categories)) {
            $categoryRoot = $blogDir . DIRECTORY_SEPARATOR . 'category';
            @mkdir($categoryRoot, 0775, true);
            foreach ($categories as $category) {
                $categoryLabel = trim((string) $category);
                $categorySlug = ccms_taxonomy_slug($categoryLabel);
                if ($categorySlug === '') {
                    continue;
                }
                $categoryDir = $categoryRoot . DIRECTORY_SEPARATOR . $categorySlug;
                @mkdir($categoryDir, 0775, true);
                file_put_contents(
                    $categoryDir . DIRECTORY_SEPARATOR . 'index.html',
                    ccms_render_blog_archive_page(
                        $data['site'],
                        ccms_posts_for_category_slug($data, $categorySlug),
                        $menuPages,
                        $categoryLabel,
                        null
                    )
                );
            }
        }

        if (!empty($tags)) {
            $tagRoot = $blogDir . DIRECTORY_SEPARATOR . 'tag';
            @mkdir($tagRoot, 0775, true);
            foreach ($tags as $tag) {
                $tagLabel = trim((string) $tag);
                $tagSlug = ccms_taxonomy_slug($tagLabel);
                if ($tagSlug === '') {
                    continue;
                }
                $tagDir = $tagRoot . DIRECTORY_SEPARATOR . $tagSlug;
                @mkdir($tagDir, 0775, true);
                file_put_contents(
                    $tagDir . DIRECTORY_SEPARATOR . 'index.html',
                    ccms_render_blog_archive_page(
                        $data['site'],
                        ccms_posts_for_tag_slug($data, $tagSlug),
                        $menuPages,
                        null,
                        $tagLabel
                    )
                );
            }
        }

        file_put_contents($dir . DIRECTORY_SEPARATOR . 'feed.xml', ccms_render_blog_rss($data['site'], $posts));
    }

    $uploadIterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator(ccms_uploads_dir(), FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );
    $uploadsBaseLen = strlen(ccms_uploads_dir()) + 1;
    foreach ($uploadIterator as $item) {
        $path = (string) $item;
        $relative = substr($path, $uploadsBaseLen);
        if ($relative === false || $relative === '') {
            continue;
        }
        $targetPath = $dir . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . $relative;
        if ($item->isDir()) {
            @mkdir($targetPath, 0775, true);
            continue;
        }
        @mkdir(dirname($targetPath), 0775, true);
        copy($path, $targetPath);
    }

    $guide = "LinuxCMS static export\n\n";
    $guide .= "Upload the contents of this folder to the root of your basic hosting.\n";
    $guide .= "Keep index.html at the root and keep the slug folders and uploads folder together.\n";
    file_put_contents($dir . DIRECTORY_SEPARATOR . 'README-STATIC-EXPORT.txt', $guide);
    file_put_contents($dir . DIRECTORY_SEPARATOR . 'sitemap.xml', ccms_render_sitemap_xml($data));
    file_put_contents($dir . DIRECTORY_SEPARATOR . 'robots.txt', ccms_render_robots_txt());

    return [
        'dir' => $dir,
        'homepage' => $homepage ? ($homepage['slug'] ?? '') : '',
        'pages' => array_map(static fn(array $page): string => (string) ($page['slug'] ?? ''), $pages),
        'posts' => array_map(static fn(array $post): string => (string) ($post['slug'] ?? ''), $posts),
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
