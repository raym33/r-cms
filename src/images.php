<?php
declare(strict_types=1);

function ccms_image_variant_widths(): array
{
    return [480, 960, 1600];
}

function ccms_image_optimization_available(): bool
{
    return extension_loaded('gd')
        && function_exists('getimagesize')
        && function_exists('imagecreatetruecolor')
        && function_exists('imagecopyresampled');
}

function ccms_upload_filename_from_url(string $url): ?string
{
    $path = (string) parse_url($url, PHP_URL_PATH);
    if (!str_starts_with($path, '/uploads/')) {
        return null;
    }
    $filename = rawurldecode(substr($path, strlen('/uploads/')));
    if ($filename === '' || str_contains($filename, '..') || str_contains($filename, '/')) {
        return null;
    }
    return $filename;
}

function ccms_upload_path_from_url(string $url): ?string
{
    $filename = ccms_upload_filename_from_url($url);
    if ($filename === null) {
        return null;
    }
    $path = ccms_uploads_dir() . DIRECTORY_SEPARATOR . $filename;
    return is_file($path) ? $path : null;
}

function ccms_image_variant_filename(string $filename, int $width, string $extension): string
{
    $base = pathinfo($filename, PATHINFO_FILENAME);
    return $base . '--' . $width . 'w.' . strtolower($extension);
}

function ccms_image_variant_public_url(string $filename, int $width, string $extension): string
{
    return ccms_public_upload_url(ccms_image_variant_filename($filename, $width, $extension));
}

function ccms_image_supported_output_extensions(): array
{
    $extensions = [];
    if (function_exists('imagejpeg')) {
        $extensions[] = 'jpg';
        $extensions[] = 'jpeg';
    }
    if (function_exists('imagepng')) {
        $extensions[] = 'png';
    }
    if (function_exists('imagewebp')) {
        $extensions[] = 'webp';
    }
    return array_values(array_unique($extensions));
}

function ccms_image_resource_from_file(string $path, string $mime)
{
    return match ($mime) {
        'image/jpeg' => function_exists('imagecreatefromjpeg') ? @imagecreatefromjpeg($path) : false,
        'image/png' => function_exists('imagecreatefrompng') ? @imagecreatefrompng($path) : false,
        'image/webp' => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($path) : false,
        default => false,
    };
}

function ccms_image_prepare_canvas(int $width, int $height)
{
    $canvas = imagecreatetruecolor($width, $height);
    imagealphablending($canvas, false);
    imagesavealpha($canvas, true);
    $transparent = imagecolorallocatealpha($canvas, 0, 0, 0, 127);
    imagefilledrectangle($canvas, 0, 0, $width, $height, $transparent);
    return $canvas;
}

function ccms_image_write_resource($resource, string $targetPath, string $extension): bool
{
    return match (strtolower($extension)) {
        'jpg', 'jpeg' => function_exists('imagejpeg') ? @imagejpeg($resource, $targetPath, 84) : false,
        'png' => function_exists('imagepng') ? @imagepng($resource, $targetPath, 6) : false,
        'webp' => function_exists('imagewebp') ? @imagewebp($resource, $targetPath, 84) : false,
        default => false,
    };
}

function ccms_generate_image_variants(string $filename): array
{
    $path = ccms_uploads_dir() . DIRECTORY_SEPARATOR . $filename;
    if (!is_file($path)) {
        return ['generated' => [], 'webp_generated' => [], 'reason' => 'missing_original'];
    }
    if (!ccms_image_optimization_available()) {
        return ['generated' => [], 'webp_generated' => [], 'reason' => 'gd_unavailable'];
    }

    $mime = ccms_detect_mime_type($path);
    if (!in_array($mime, ['image/jpeg', 'image/png', 'image/webp'], true)) {
        return ['generated' => [], 'webp_generated' => [], 'reason' => 'unsupported_mime'];
    }

    $size = @getimagesize($path);
    if (!is_array($size) || empty($size[0]) || empty($size[1])) {
        return ['generated' => [], 'webp_generated' => [], 'reason' => 'invalid_image'];
    }

    $source = ccms_image_resource_from_file($path, $mime);
    if (!is_resource($source) && !($source instanceof GdImage)) {
        return ['generated' => [], 'webp_generated' => [], 'reason' => 'resource_failed'];
    }

    $originalWidth = (int) $size[0];
    $originalHeight = (int) $size[1];
    $originalExt = strtolower((string) pathinfo($filename, PATHINFO_EXTENSION));
    $generated = [];
    $webpGenerated = [];
    $targets = array_values(array_unique(array_merge(
        array_filter(ccms_image_variant_widths(), static fn (int $width): bool => $width > 0 && $width < $originalWidth),
        function_exists('imagewebp') ? [$originalWidth] : []
    )));

    foreach ($targets as $targetWidth) {
        $targetHeight = max(1, (int) round(($originalHeight / $originalWidth) * $targetWidth));
        $canvas = ccms_image_prepare_canvas($targetWidth, $targetHeight);
        imagecopyresampled($canvas, $source, 0, 0, 0, 0, $targetWidth, $targetHeight, $originalWidth, $originalHeight);

        if ($targetWidth < $originalWidth && in_array($originalExt, ccms_image_supported_output_extensions(), true)) {
            $variantName = ccms_image_variant_filename($filename, $targetWidth, $originalExt);
            $variantPath = ccms_uploads_dir() . DIRECTORY_SEPARATOR . $variantName;
            if (ccms_image_write_resource($canvas, $variantPath, $originalExt)) {
                $generated[] = [
                    'width' => $targetWidth,
                    'url' => ccms_public_upload_url($variantName),
                    'path' => $variantPath,
                ];
            }
        }

        if (function_exists('imagewebp')) {
            $variantName = ccms_image_variant_filename($filename, $targetWidth, 'webp');
            $variantPath = ccms_uploads_dir() . DIRECTORY_SEPARATOR . $variantName;
            if (ccms_image_write_resource($canvas, $variantPath, 'webp')) {
                $webpGenerated[] = [
                    'width' => $targetWidth,
                    'url' => ccms_public_upload_url($variantName),
                    'path' => $variantPath,
                ];
            }
        }

        imagedestroy($canvas);
    }

    imagedestroy($source);

    return [
        'generated' => $generated,
        'webp_generated' => $webpGenerated,
        'width' => $originalWidth,
        'height' => $originalHeight,
        'mime' => $mime,
    ];
}

function ccms_collect_image_variants(string $filename): array
{
    $path = ccms_uploads_dir() . DIRECTORY_SEPARATOR . $filename;
    if (!is_file($path)) {
        return [];
    }
    $variants = [];
    foreach (ccms_image_variant_widths() as $width) {
        foreach (array_unique(array_merge([strtolower((string) pathinfo($filename, PATHINFO_EXTENSION))], ['webp'])) as $extension) {
            if ($extension === '') {
                continue;
            }
            $variantName = ccms_image_variant_filename($filename, $width, $extension);
            $variantPath = ccms_uploads_dir() . DIRECTORY_SEPARATOR . $variantName;
            if (!is_file($variantPath)) {
                continue;
            }
            $variants[$extension][] = [
                'width' => $width,
                'url' => ccms_public_upload_url($variantName),
            ];
        }
    }
    foreach ($variants as $extension => $items) {
        usort($items, static fn (array $a, array $b): int => ($a['width'] ?? 0) <=> ($b['width'] ?? 0));
        $variants[$extension] = $items;
    }
    return $variants;
}

function ccms_build_image_srcset(array $variants): string
{
    $parts = [];
    foreach ($variants as $variant) {
        $url = trim((string) ($variant['url'] ?? ''));
        $width = (int) ($variant['width'] ?? 0);
        if ($url === '' || $width <= 0) {
            continue;
        }
        $parts[] = $url . ' ' . $width . 'w';
    }
    return implode(', ', $parts);
}

function ccms_optimize_public_images_html(string $html): string
{
    if (trim($html) === '' || !str_contains($html, '<img')) {
        return $html;
    }
    if (!class_exists(DOMDocument::class)) {
        $html = preg_replace('/<img\b(?![^>]*\bloading=)/i', '<img loading="lazy" ', $html) ?? $html;
        $html = preg_replace('/<img\b(?![^>]*\bdecoding=)/i', '<img decoding="async" ', $html) ?? $html;
        return $html;
    }

    $wrapped = '<div id="ccms-image-opt-root">' . $html . '</div>';
    $previous = libxml_use_internal_errors(true);
    $dom = new DOMDocument('1.0', 'UTF-8');
    $dom->loadHTML('<?xml encoding="utf-8" ?>' . $wrapped, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
    libxml_clear_errors();
    libxml_use_internal_errors($previous);
    $root = $dom->getElementById('ccms-image-opt-root');
    if (!$root instanceof DOMElement) {
        return $html;
    }

    $images = [];
    foreach ($root->getElementsByTagName('img') as $image) {
        $images[] = $image;
    }

    foreach ($images as $img) {
        if (!$img instanceof DOMElement) {
            continue;
        }
        if (!$img->hasAttribute('loading')) {
            $img->setAttribute('loading', 'lazy');
        }
        if (!$img->hasAttribute('decoding')) {
            $img->setAttribute('decoding', 'async');
        }

        $src = trim((string) $img->getAttribute('src'));
        $filename = ccms_upload_filename_from_url($src);
        if ($filename === null) {
            continue;
        }

        $variants = ccms_collect_image_variants($filename);
        $fallbackExt = strtolower((string) pathinfo($filename, PATHINFO_EXTENSION));
        $fallbackSrcset = ccms_build_image_srcset($variants[$fallbackExt] ?? []);
        $webpSrcset = ccms_build_image_srcset($variants['webp'] ?? []);
        if ($fallbackSrcset !== '' && !$img->hasAttribute('srcset')) {
            $img->setAttribute('srcset', $fallbackSrcset);
        }
        if (($fallbackSrcset !== '' || $webpSrcset !== '') && !$img->hasAttribute('sizes')) {
            $img->setAttribute('sizes', '(max-width: 960px) 100vw, 960px');
        }
        if ($webpSrcset === '' || strtolower($img->parentNode?->nodeName ?? '') === 'picture') {
            continue;
        }

        $picture = $dom->createElement('picture');
        $source = $dom->createElement('source');
        $source->setAttribute('type', 'image/webp');
        $source->setAttribute('srcset', $webpSrcset);
        if ($img->hasAttribute('sizes')) {
            $source->setAttribute('sizes', (string) $img->getAttribute('sizes'));
        }
        $picture->appendChild($source);
        $picture->appendChild($img->cloneNode(true));
        $img->parentNode?->replaceChild($picture, $img);
    }

    $output = '';
    foreach (iterator_to_array($root->childNodes) as $child) {
        $output .= $dom->saveHTML($child);
    }
    return $output;
}
