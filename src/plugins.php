<?php
declare(strict_types=1);

function ccms_plugins_dir(): string
{
    return ccms_root_path('plugins');
}

function ccms_plugin_runtime_reset(): void
{
    $GLOBALS['ccms_plugin_hooks'] = [];
    $GLOBALS['ccms_plugin_manifests'] = [];
}

function ccms_plugins_trusted_enabled(array $site): bool
{
    $env = strtolower(trim((string) getenv('CCMS_TRUSTED_PLUGINS')));
    if (in_array($env, ['1', 'true', 'yes', 'on'], true)) {
        return true;
    }
    return !empty($site['trusted_plugins_enabled']);
}

function ccms_plugin_allowed_hooks(): array
{
    return ['public_head_end', 'public_body_end'];
}

function ccms_plugin_slug_is_valid(string $slug): bool
{
    return preg_match('/^[a-z0-9][a-z0-9_-]{1,63}$/', $slug) === 1;
}

function ccms_plugin_path_is_trusted(string $path): bool
{
    $pluginsRoot = realpath(ccms_plugins_dir());
    $realPath = realpath($path);
    if (!$pluginsRoot || !$realPath) {
        return false;
    }
    return $realPath === $pluginsRoot || str_starts_with($realPath, $pluginsRoot . DIRECTORY_SEPARATOR);
}

function ccms_include_plugin_file(string $entry)
{
    return include $entry;
}

function ccms_register_plugin_hook(string $hook, callable $callback): void
{
    if (!in_array($hook, ccms_plugin_allowed_hooks(), true)) {
        return;
    }
    $GLOBALS['ccms_plugin_hooks'][$hook] ??= [];
    $GLOBALS['ccms_plugin_hooks'][$hook][] = $callback;
}

function ccms_discover_plugins(): array
{
    $plugins = [];
    $dir = ccms_plugins_dir();
    if (!is_dir($dir)) {
        return [];
    }
    foreach (scandir($dir) ?: [] as $entry) {
        if ($entry === '.' || $entry === '..') {
            continue;
        }
        $pluginDir = $dir . DIRECTORY_SEPARATOR . $entry;
        if (!is_dir($pluginDir) || is_link($pluginDir) || !ccms_plugin_slug_is_valid($entry)) {
            continue;
        }
        $manifest = [
            'slug' => $entry,
            'name' => ucwords(str_replace(['-', '_'], ' ', $entry)),
            'description' => '',
            'version' => '1.0.0',
        ];
        $manifestPath = $pluginDir . DIRECTORY_SEPARATOR . 'manifest.json';
        if (is_file($manifestPath)) {
            $decoded = json_decode((string) file_get_contents($manifestPath), true);
            if (is_array($decoded)) {
                $manifest = array_merge($manifest, $decoded);
            }
        }
        $manifest['slug'] = (string) ($manifest['slug'] ?? $entry);
        if ($manifest['slug'] !== $entry || !ccms_plugin_slug_is_valid($manifest['slug'])) {
            continue;
        }
        $manifest['path'] = $pluginDir;
        $manifest['entry'] = $pluginDir . DIRECTORY_SEPARATOR . 'plugin.php';
        $manifest['active'] = false;
        $manifest['trusted'] = !empty($manifest['trusted']);
        $manifest['path_trusted'] = ccms_plugin_path_is_trusted($pluginDir);
        $manifest['entry_trusted'] = is_file($manifest['entry']) && !is_link($manifest['entry']) && ccms_plugin_path_is_trusted($manifest['entry']);
        $entrySha = trim((string) ($manifest['entry_sha256'] ?? ''));
        $manifest['entry_sha256'] = preg_match('/^[a-f0-9]{64}$/i', $entrySha) ? strtolower($entrySha) : '';
        $manifest['integrity_ok'] = false;
        if (!empty($manifest['entry_trusted']) && $manifest['entry_sha256'] !== '') {
            $actualHash = hash_file('sha256', $manifest['entry']);
            $manifest['integrity_ok'] = is_string($actualHash) && hash_equals($manifest['entry_sha256'], strtolower($actualHash));
        }
        $manifest['loadable'] = $manifest['trusted'] && !empty($manifest['path_trusted']) && !empty($manifest['entry_trusted']) && $manifest['integrity_ok'];
        $plugins[$manifest['slug']] = $manifest;
    }
    ksort($plugins);
    return $plugins;
}

function ccms_load_enabled_plugins(array $site): array
{
    ccms_plugin_runtime_reset();
    $plugins = ccms_discover_plugins();
    if (!ccms_plugins_trusted_enabled($site)) {
        return $plugins;
    }
    $enabled = array_values(array_filter(array_map('strval', is_array($site['enabled_plugins'] ?? null) ? $site['enabled_plugins'] : [])));
    foreach ($enabled as $slug) {
        if (!isset($plugins[$slug])) {
            continue;
        }
        if (empty($plugins[$slug]['loadable'])) {
            continue;
        }
        $entry = (string) ($plugins[$slug]['entry'] ?? '');
        if (!is_file($entry) || empty($plugins[$slug]['entry_trusted'])) {
            continue;
        }
        try {
            $manifest = ccms_include_plugin_file($entry);
        } catch (Throwable $e) {
            continue;
        }
        if (is_array($manifest)) {
            $plugins[$slug] = array_merge($plugins[$slug], $manifest);
        }
        $plugins[$slug]['active'] = true;
        $GLOBALS['ccms_plugin_manifests'][$slug] = $plugins[$slug];
    }
    return $plugins;
}

function ccms_render_plugin_fragments(string $hook, array $context = []): string
{
    $output = '';
    foreach (($GLOBALS['ccms_plugin_hooks'][$hook] ?? []) as $callback) {
        $fragment = $callback($context);
        if (is_string($fragment) && $fragment !== '') {
            $output .= ccms_sanitize_plugin_fragment($hook, $fragment);
        }
    }
    return $output;
}

function ccms_sanitize_plugin_fragment(string $hook, string $fragment): string
{
    if ($hook === 'public_head_end') {
        return ccms_sanitize_plugin_head_fragment($fragment);
    }
    return ccms_sanitize_html_fragment($fragment);
}

function ccms_sanitize_plugin_head_fragment(string $html): string
{
    $html = trim($html);
    if ($html === '' || !class_exists(DOMDocument::class)) {
        return '';
    }

    $wrapped = '<div id="ccms-plugin-head-root">' . $html . '</div>';
    $previous = libxml_use_internal_errors(true);
    $dom = new DOMDocument('1.0', 'UTF-8');
    $dom->loadHTML('<?xml encoding="utf-8" ?>' . $wrapped, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
    libxml_clear_errors();
    libxml_use_internal_errors($previous);

    $root = $dom->getElementById('ccms-plugin-head-root');
    if (!$root instanceof DOMElement) {
        return '';
    }

    $allowedTags = ['style', 'meta', 'link'];
    $output = '';
    foreach (iterator_to_array($root->childNodes) as $node) {
        if (!$node instanceof DOMElement) {
            continue;
        }
        $tag = strtolower($node->tagName);
        if (!in_array($tag, $allowedTags, true)) {
            continue;
        }
        if ($tag === 'style') {
            $css = ccms_sanitize_custom_css($node->textContent ?? '');
            if ($css !== '') {
                $id = $node->getAttribute('id');
                $idAttr = $id !== '' ? ' id="' . ccms_h($id) . '"' : '';
                $output .= '<style' . $idAttr . '>' . $css . '</style>';
            }
            continue;
        }
        if ($tag === 'meta') {
            $attrs = [];
            foreach (['name', 'content', 'property', 'charset'] as $attr) {
                $value = trim($node->getAttribute($attr));
                if ($value !== '') {
                    $attrs[] = $attr . '="' . ccms_h($value) . '"';
                }
            }
            if ($attrs !== []) {
                $output .= '<meta ' . implode(' ', $attrs) . '>';
            }
            continue;
        }
        if ($tag === 'link') {
            $href = ccms_sanitize_url($node->getAttribute('href'));
            if ($href === '') {
                continue;
            }
            $rel = trim($node->getAttribute('rel'));
            if (!in_array($rel, ['stylesheet', 'preconnect', 'dns-prefetch', 'icon'], true)) {
                continue;
            }
            $attrs = ['rel="' . ccms_h($rel) . '"', 'href="' . ccms_h($href) . '"'];
            foreach (['media', 'crossorigin', 'referrerpolicy'] as $attr) {
                $value = trim($node->getAttribute($attr));
                if ($value !== '') {
                    $attrs[] = $attr . '="' . ccms_h($value) . '"';
                }
            }
            $output .= '<link ' . implode(' ', $attrs) . '>';
        }
    }

    return $output;
}
