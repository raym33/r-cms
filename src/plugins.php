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

function ccms_register_plugin_hook(string $hook, callable $callback): void
{
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
        if (!is_dir($pluginDir)) {
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
        $manifest['path'] = $pluginDir;
        $manifest['entry'] = $pluginDir . DIRECTORY_SEPARATOR . 'plugin.php';
        $manifest['active'] = false;
        $plugins[$manifest['slug']] = $manifest;
    }
    ksort($plugins);
    return $plugins;
}

function ccms_load_enabled_plugins(array $site): array
{
    ccms_plugin_runtime_reset();
    $plugins = ccms_discover_plugins();
    $enabled = array_values(array_filter(array_map('strval', is_array($site['enabled_plugins'] ?? null) ? $site['enabled_plugins'] : [])));
    foreach ($enabled as $slug) {
        if (!isset($plugins[$slug])) {
            continue;
        }
        $entry = (string) ($plugins[$slug]['entry'] ?? '');
        if (!is_file($entry)) {
            continue;
        }
        $manifest = include $entry;
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
            $output .= $fragment;
        }
    }
    return $output;
}
