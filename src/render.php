<?php
declare(strict_types=1);

require_once __DIR__ . '/storage.php';

function ccms_capsule_decode(array $page): ?array
{
    $raw = trim((string) ($page['capsule_json'] ?? ''));
    if ($raw === '') {
        return null;
    }
    $decoded = json_decode($raw, true);
    return is_array($decoded) ? $decoded : null;
}

function ccms_capsule_supported_blocks(): array
{
    return [
        'nav',
        'sticky_header',
        'offcanvas_menu',
        'banner',
        'hero',
        'hero_video',
        'hero_slider',
        'hero_particles',
        'hero_fullscreen',
        'hero_split',
        'features',
        'numbered_features',
        'process',
        'faq',
        'accordion_rich',
        'pricing',
        'pricing_toggle',
        'pricing_comparison',
        'gallery',
        'image_grid_masonry',
        'logo_cloud',
        'team',
        'awards_bar',
        'press_mentions',
        'contact',
        'map_embed',
        'tabs_content',
        'instagram_feed',
        'content_stack',
        'split_content',
        'newsletter',
        'comparison',
        'case_studies',
        'split_image_left',
        'split_image_right',
        'text_block',
        'icon_boxes',
        'services_cards',
        'stats',
        'testimonials',
        'testimonial_carousel',
        'reviews_summary',
        'testimonial_cards',
        'timeline',
        'blog_grid',
        'blog_featured',
        'blog_carousel',
        'portfolio_grid',
        'video_embed',
        'before_after',
        'parallax_section',
        'divider_fancy',
        'spacer',
        'columns_2',
        'columns_3',
        'sidebar_layout',
        'cta',
        'lead_form',
        'booking_widget',
        'countdown_timer',
        'popup_cta',
        'footer_multi',
    ];
}

function ccms_capsule_can_render(?array $capsule): bool
{
    if (!$capsule || !is_array($capsule['blocks'] ?? null) || empty($capsule['blocks'])) {
        return false;
    }
    $supported = ccms_capsule_supported_blocks();
    foreach ($capsule['blocks'] as $block) {
        $type = (string) ($block['type'] ?? '');
        if (!in_array($type, $supported, true)) {
            return false;
        }
    }
    return true;
}

function ccms_shadow_css(string $style): string
{
    return match ($style) {
        'none' => 'none',
        'hard' => '0 0 0 1px rgba(15,23,42,.18), 12px 12px 0 rgba(15,23,42,.12)',
        'glow' => '0 0 0 1px rgba(99,102,241,.14), 0 24px 60px -28px rgba(99,102,241,.4)',
        'layered' => '0 1px 2px rgba(15,23,42,.08), 0 18px 42px -18px rgba(15,23,42,.18), 0 42px 86px -40px rgba(15,23,42,.24)',
        default => '0 28px 55px -34px rgba(0,0,0,.18)',
    };
}

function ccms_float_in_range($value, float $fallback, float $min, float $max): float
{
    if (!is_numeric($value)) {
        return $fallback;
    }
    $resolved = (float) $value;
    return max($min, min($max, $resolved));
}

function ccms_format_decimal(float $value): string
{
    $formatted = rtrim(rtrim(sprintf('%.2F', $value), '0'), '.');
    return $formatted === '-0' ? '0' : $formatted;
}

function ccms_font_pairing_tokens(string $pairing): array
{
    return match ($pairing) {
        'modern' => [
            'font_body' => 'Inter, Arial, Helvetica, sans-serif',
            'font_heading' => 'Inter, Arial, Helvetica, sans-serif',
        ],
        'editorial' => [
            'font_body' => 'Georgia, "Times New Roman", serif',
            'font_heading' => 'system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif',
        ],
        'elegant' => [
            'font_body' => 'Palatino, "Book Antiqua", Georgia, serif',
            'font_heading' => 'Optima, Candara, "Segoe UI", system-ui, sans-serif',
        ],
        'classic' => [
            'font_body' => '"Times New Roman", Times, serif',
            'font_heading' => 'Arial, Helvetica, sans-serif',
        ],
        'mono' => [
            'font_body' => 'ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace',
            'font_heading' => 'system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif',
        ],
        'humanist' => [
            'font_body' => 'Verdana, Geneva, sans-serif',
            'font_heading' => 'Georgia, "Times New Roman", serif',
        ],
        default => [],
    };
}

function ccms_capsule_set_current_style(?array $style): void
{
    $GLOBALS['ccms_current_capsule_style'] = $style;
}

function ccms_capsule_current_style(): array
{
    $style = $GLOBALS['ccms_current_capsule_style'] ?? null;
    return is_array($style) ? $style : [];
}

function ccms_scale_css_px_values(string $value, float $scale): string
{
    return preg_replace_callback('/(-?\d+(?:\.\d+)?)px\b/', static function (array $matches) use ($scale): string {
        return ccms_format_decimal(((float) $matches[1]) * $scale) . 'px';
    }, $value) ?? $value;
}

function ccms_scale_layout_style(string $style, float $scale): string
{
    if ($style === '' || abs($scale - 1.0) < 0.001) {
        return $style;
    }
    $scalable = [
        'padding',
        'padding-top',
        'padding-right',
        'padding-bottom',
        'padding-left',
        'margin',
        'margin-top',
        'margin-bottom',
        'gap',
        'column-gap',
        'row-gap',
    ];
    $rules = [];
    foreach (explode(';', $style) as $declaration) {
        $parts = explode(':', $declaration, 2);
        if (count($parts) !== 2) {
            continue;
        }
        $property = trim(strtolower($parts[0]));
        $value = trim($parts[1]);
        if ($property === '') {
            continue;
        }
        if (in_array($property, $scalable, true)) {
            $value = ccms_scale_css_px_values($value, $scale);
        }
        $rules[] = $property . ':' . $value;
    }
    return implode(';', $rules);
}

function ccms_allowed_block_animations(): array
{
    return ['fade-up', 'fade-left', 'fade-right', 'scale', 'none'];
}

function ccms_allowed_background_effects(): array
{
    return ['none', 'gradient', 'mesh', 'grain', 'dots', 'glass'];
}

function ccms_capsule_style(array $capsule, array $site = []): array
{
    $style = is_array($capsule['style'] ?? null) ? $capsule['style'] : [];
    $theme = ccms_site_theme_preset($site);
    $siteColors = is_array($site['colors'] ?? null) ? $site['colors'] : [];

    $shadowStyle = (string) ($style['shadow_style'] ?? $theme['shadow_style']);
    if (!in_array($shadowStyle, ['none', 'soft', 'hard', 'glow', 'layered'], true)) {
        $shadowStyle = $theme['shadow_style'];
    }

    $headingWeight = (string) ($style['font_weight_heading'] ?? $theme['font_weight_heading']);
    if (!in_array($headingWeight, ['400', '500', '600', '700', '800', '900'], true)) {
        $headingWeight = $theme['font_weight_heading'];
    }

    $headingTransform = (string) ($style['text_transform_heading'] ?? $theme['text_transform_heading']);
    if (!in_array($headingTransform, ['none', 'uppercase', 'capitalize'], true)) {
        $headingTransform = $theme['text_transform_heading'];
    }

    $spacingScale = ccms_float_in_range($style['spacing_scale'] ?? $theme['spacing_scale'], (float) $theme['spacing_scale'], 0.75, 1.6);
    $lineHeightBody = ccms_float_in_range($style['line_height_body'] ?? $theme['line_height_body'], (float) $theme['line_height_body'], 1.35, 2.1);

    return [
        'accent' => (string) ($style['accent'] ?? $siteColors['primary'] ?? '#c86f5c'),
        'accent_dark' => (string) ($style['accent_dark'] ?? $siteColors['secondary'] ?? '#ab5d4e'),
        'bg_from' => (string) ($style['bg_from'] ?? $siteColors['bg'] ?? '#f7f4ee'),
        'bg_to' => (string) ($style['bg_to'] ?? $siteColors['surface'] ?? '#ffffff'),
        'card_bg' => (string) ($style['card_bg'] ?? 'rgba(255,255,255,0.96)'),
        'card_border' => (string) ($style['card_border'] ?? 'rgba(0,0,0,0.08)'),
        'gradient_accent' => (string) ($style['gradient_accent'] ?? 'linear-gradient(135deg,#c86f5c 0%,#d9c4b3 100%)'),
        'text_primary' => (string) ($style['text_primary'] ?? $siteColors['text'] ?? '#2f241f'),
        'text_secondary' => (string) ($style['text_secondary'] ?? $siteColors['muted'] ?? '#6b5b53'),
        'text_muted' => (string) ($style['text_muted'] ?? '#7c6a60'),
        'nav_bg' => (string) ($style['nav_bg'] ?? 'rgba(255,255,255,0.92)'),
        'font_family' => (string) ($style['font_family'] ?? $theme['font_body']),
        'font_heading' => (string) ($style['font_heading'] ?? $theme['font_heading']),
        'surface_radius' => (string) ($style['surface_radius'] ?? $theme['surface_radius']),
        'card_radius' => (string) ($style['card_radius'] ?? $theme['card_radius']),
        'button_radius' => (string) ($style['button_radius'] ?? $theme['button_radius']),
        'image_radius' => (string) ($style['image_radius'] ?? $theme['image_radius']),
        'shadow_style' => $shadowStyle,
        'shadow' => ccms_shadow_css($shadowStyle),
        'spacing_scale' => ccms_format_decimal($spacingScale),
        'font_weight_heading' => $headingWeight,
        'letter_spacing_heading' => (string) ($style['letter_spacing_heading'] ?? $theme['letter_spacing_heading']),
        'text_transform_heading' => $headingTransform,
        'line_height_body' => ccms_format_decimal($lineHeightBody),
    ];
}

function ccms_capsule_block_style(array $block): array
{
    return is_array($block['style'] ?? null) ? $block['style'] : [];
}

function ccms_capsule_background_effect_attr(array $block): string
{
    $effect = trim((string) (ccms_capsule_block_style($block)['background_effect'] ?? ''));
    if ($effect === '' || !in_array($effect, ccms_allowed_background_effects(), true)) {
        return '';
    }
    return ' data-ccms-bg="' . ccms_h($effect) . '"';
}

function ccms_capsule_section_style_attr(array $block, string $defaultStyle = ''): string
{
    $style = ccms_capsule_block_style($block);
    $rules = [];
    if ($defaultStyle !== '') {
        $scale = ccms_float_in_range(ccms_capsule_current_style()['spacing_scale'] ?? 1, 1, 0.75, 1.6);
        $rules[] = rtrim(ccms_scale_layout_style($defaultStyle, $scale), ';');
    }
    if (isset($style['padding_top']) && is_numeric($style['padding_top'])) {
        $rules[] = 'padding-top:' . (int) $style['padding_top'] . 'px';
    }
    if (isset($style['padding_bottom']) && is_numeric($style['padding_bottom'])) {
        $rules[] = 'padding-bottom:' . (int) $style['padding_bottom'] . 'px';
    }
    if (!empty($style['background'])) {
        $rules[] = 'background:' . (string) $style['background'];
    }
    if (!empty($style['text_align'])) {
        $rules[] = 'text-align:' . (string) $style['text_align'];
    }
    if (!empty($style['text_color'])) {
        $rules[] = 'color:' . (string) $style['text_color'];
    }
    if (!empty($style['button_bg'])) {
        $rules[] = '--ccms-button-bg:' . (string) $style['button_bg'];
    }
    if (!empty($style['button_text_color'])) {
        $rules[] = '--ccms-button-color:' . (string) $style['button_text_color'];
    }
    if (!empty($style['button_border_color'])) {
        $rules[] = '--ccms-button-border:' . (string) $style['button_border_color'];
    }
    if (!empty($style['button_ghost_bg'])) {
        $rules[] = '--ccms-button-ghost-bg:' . (string) $style['button_ghost_bg'];
    }
    if (!empty($style['button_ghost_text_color'])) {
        $rules[] = '--ccms-button-ghost-color:' . (string) $style['button_ghost_text_color'];
    }
    if (!empty($style['button_ghost_border_color'])) {
        $rules[] = '--ccms-button-ghost-border:' . (string) $style['button_ghost_border_color'];
    }
    $attributes = ccms_capsule_background_effect_attr($block);
    if ($rules !== []) {
        $attributes .= ' style="' . ccms_h(implode(';', $rules) . ';') . '"';
    }
    return $attributes;
}

function ccms_capsule_inner_style_attr(array $block): string
{
    $style = ccms_capsule_block_style($block);
    $rules = [];
    if (isset($style['content_width']) && is_numeric($style['content_width'])) {
        $rules[] = 'width:min(' . (int) $style['content_width'] . 'px,calc(100% - 48px))';
    }
    return $rules === [] ? '' : ' style="' . ccms_h(implode(';', $rules) . ';') . '"';
}

function ccms_capsule_button_classes(array $block, bool $ghost = false): string
{
    $style = ccms_capsule_block_style($block);
    $variant = (string) ($style['button_variant'] ?? '');
    $classes = ['ccms-btn'];
    if ($ghost || $variant === 'ghost') {
        $classes[] = 'ccms-btn--ghost';
    }
    return implode(' ', $classes);
}

function ccms_capsule_media_url(string $value, string $seed = 'capsule', int $width = 1200, int $height = 900): string
{
    $trimmed = trim($value);
    if ($trimmed !== '') {
        return $trimmed;
    }
    return 'https://picsum.photos/seed/' . rawurlencode($seed) . '/' . $width . '/' . $height;
}

function ccms_site_theme_preset(array $site): array
{
    $preset = ccms_normalize_theme_preset((string) ($site['theme_preset'] ?? 'warm'));
    $themes = [
        'warm' => [
            'font_body' => 'Inter, Arial, Helvetica, sans-serif',
            'font_heading' => 'Inter, Arial, Helvetica, sans-serif',
            'header_bg' => 'rgba(255,255,255,.92)',
            'header_border' => 'rgba(0,0,0,.05)',
            'surface_radius' => '28px',
            'card_radius' => '24px',
            'button_radius' => '999px',
            'image_radius' => '28px',
            'shadow_style' => 'soft',
            'spacing_scale' => '1',
            'font_weight_heading' => '800',
            'letter_spacing_heading' => '0',
            'text_transform_heading' => 'none',
            'line_height_body' => '1.7',
        ],
        'editorial' => [
            'font_body' => 'Georgia, "Times New Roman", serif',
            'font_heading' => 'Inter, Arial, Helvetica, sans-serif',
            'header_bg' => 'rgba(255,255,255,.88)',
            'header_border' => 'rgba(47,36,31,.09)',
            'surface_radius' => '16px',
            'card_radius' => '16px',
            'button_radius' => '14px',
            'image_radius' => '16px',
            'shadow_style' => 'none',
            'spacing_scale' => '1.45',
            'font_weight_heading' => '400',
            'letter_spacing_heading' => '0.02em',
            'text_transform_heading' => 'none',
            'line_height_body' => '1.95',
        ],
        'minimal' => [
            'font_body' => 'Inter, Arial, Helvetica, sans-serif',
            'font_heading' => 'Inter, Arial, Helvetica, sans-serif',
            'header_bg' => 'rgba(255,255,255,.96)',
            'header_border' => 'rgba(0,0,0,.04)',
            'surface_radius' => '18px',
            'card_radius' => '18px',
            'button_radius' => '10px',
            'image_radius' => '18px',
            'shadow_style' => 'soft',
            'spacing_scale' => '0.95',
            'font_weight_heading' => '700',
            'letter_spacing_heading' => '0',
            'text_transform_heading' => 'none',
            'line_height_body' => '1.65',
        ],
        'bold' => [
            'font_body' => 'Inter, Arial, Helvetica, sans-serif',
            'font_heading' => '"Arial Black", Inter, Arial, Helvetica, sans-serif',
            'header_bg' => 'rgba(255,255,255,.94)',
            'header_border' => 'rgba(47,36,31,.12)',
            'surface_radius' => '22px',
            'card_radius' => '22px',
            'button_radius' => '999px',
            'image_radius' => '22px',
            'shadow_style' => 'hard',
            'spacing_scale' => '1.05',
            'font_weight_heading' => '900',
            'letter_spacing_heading' => '-0.02em',
            'text_transform_heading' => 'none',
            'line_height_body' => '1.6',
        ],
        'corporate' => [
            'font_body' => 'system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif',
            'font_heading' => 'system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif',
            'header_bg' => 'rgba(255,255,255,.98)',
            'header_border' => 'rgba(15,23,42,.08)',
            'surface_radius' => '8px',
            'card_radius' => '8px',
            'button_radius' => '6px',
            'image_radius' => '8px',
            'shadow_style' => 'hard',
            'spacing_scale' => '0.85',
            'font_weight_heading' => '700',
            'letter_spacing_heading' => '0',
            'text_transform_heading' => 'none',
            'line_height_body' => '1.55',
        ],
        'playful' => [
            'font_body' => 'system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif',
            'font_heading' => 'system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif',
            'header_bg' => 'rgba(255,255,255,.9)',
            'header_border' => 'rgba(0,0,0,.04)',
            'surface_radius' => '32px',
            'card_radius' => '32px',
            'button_radius' => '999px',
            'image_radius' => '32px',
            'shadow_style' => 'soft',
            'spacing_scale' => '1.3',
            'font_weight_heading' => '900',
            'letter_spacing_heading' => '-0.02em',
            'text_transform_heading' => 'none',
            'line_height_body' => '1.75',
        ],
        'brutalist' => [
            'font_body' => 'ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace',
            'font_heading' => 'ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace',
            'header_bg' => '#ffffff',
            'header_border' => 'rgba(15,23,42,.32)',
            'surface_radius' => '0px',
            'card_radius' => '0px',
            'button_radius' => '0px',
            'image_radius' => '0px',
            'shadow_style' => 'none',
            'spacing_scale' => '0.8',
            'font_weight_heading' => '900',
            'letter_spacing_heading' => '0.06em',
            'text_transform_heading' => 'uppercase',
            'line_height_body' => '1.45',
        ],
        'luxury' => [
            'font_body' => 'Georgia, "Times New Roman", serif',
            'font_heading' => 'Georgia, Palatino, "Times New Roman", serif',
            'header_bg' => 'rgba(255,255,255,.9)',
            'header_border' => 'rgba(47,36,31,.12)',
            'surface_radius' => '4px',
            'card_radius' => '4px',
            'button_radius' => '2px',
            'image_radius' => '4px',
            'shadow_style' => 'layered',
            'spacing_scale' => '1.4',
            'font_weight_heading' => '400',
            'letter_spacing_heading' => '0.12em',
            'text_transform_heading' => 'uppercase',
            'line_height_body' => '1.9',
        ],
        'startup' => [
            'font_body' => 'Inter, Arial, Helvetica, sans-serif',
            'font_heading' => 'Inter, Arial, Helvetica, sans-serif',
            'header_bg' => 'rgba(255,255,255,.82)',
            'header_border' => 'rgba(99,102,241,.16)',
            'surface_radius' => '20px',
            'card_radius' => '20px',
            'button_radius' => '999px',
            'image_radius' => '22px',
            'shadow_style' => 'glow',
            'spacing_scale' => '1.1',
            'font_weight_heading' => '800',
            'letter_spacing_heading' => '-0.02em',
            'text_transform_heading' => 'none',
            'line_height_body' => '1.7',
        ],
    ];
    $theme = $themes[$preset] ?? $themes['warm'];
    $pairing = ccms_normalize_font_pairing((string) ($site['font_pairing'] ?? 'auto'));
    if ($pairing !== 'auto') {
        $theme = array_merge($theme, ccms_font_pairing_tokens($pairing));
    }
    $theme['shadow'] = ccms_shadow_css((string) $theme['shadow_style']);
    $theme['card_shadow'] = $theme['shadow'];
    $theme['font_pairing'] = $pairing;
    $theme['theme_preset'] = $preset;
    return $theme;
}

function ccms_capsule_link_text(array $link, string $fallback = 'Link'): string
{
    return (string) ($link['text'] ?? $link['label'] ?? $link['title'] ?? $fallback);
}

function ccms_capsule_bool_icon(bool $value, array $style): string
{
    $bg = $value ? 'rgba(168,202,186,.24)' : 'rgba(229,115,115,.16)';
    $fg = $value ? '#3d6c5a' : $style['accent_dark'];
    return '<span style="display:inline-flex;align-items:center;justify-content:center;width:28px;height:28px;border-radius:999px;background:' . ccms_h($bg) . ';color:' . ccms_h($fg) . ';font-weight:900">' . ($value ? '&#10003;' : '&#10005;') . '</span>';
}

function ccms_capsule_stars(int $stars): string
{
    if ($stars <= 0) {
        return '';
    }
    return str_repeat('&#9733;', min($stars, 5));
}

function ccms_set_current_public_page(?array $page): void
{
    $GLOBALS['ccms_current_public_page'] = $page;
}

function ccms_current_public_page(): ?array
{
    $page = $GLOBALS['ccms_current_public_page'] ?? null;
    return is_array($page) ? $page : null;
}

function ccms_blog_archive_url(): string
{
    return rtrim(ccms_base_url(), '/') . '/blog';
}

function ccms_blog_category_url(string $categorySlug): string
{
    return ccms_blog_archive_url() . '/category/' . rawurlencode($categorySlug);
}

function ccms_blog_tag_url(string $tagSlug): string
{
    return ccms_blog_archive_url() . '/tag/' . rawurlencode($tagSlug);
}

function ccms_post_public_url(array $post): string
{
    return ccms_blog_archive_url() . '/' . rawurlencode((string) ($post['slug'] ?? ''));
}

function ccms_public_page_url(array $page): string
{
    $canonical = ccms_sanitize_url((string) ($page['canonical_url'] ?? ''));
    if ($canonical !== '') {
        return $canonical;
    }
    if (($page['content_type'] ?? '') === 'post') {
        return ccms_post_public_url($page);
    }
    $base = rtrim(ccms_base_url(), '/');
    if (!empty($page['is_homepage']) || trim((string) ($page['slug'] ?? '')) === '') {
        return $base . '/';
    }
    return $base . '/' . rawurlencode((string) ($page['slug'] ?? ''));
}

function ccms_page_primary_image(array $page): string
{
    $coverImage = ccms_sanitize_url((string) ($page['cover_image'] ?? ''), true);
    if ($coverImage !== '') {
        return ccms_capsule_media_url($coverImage, 'og-' . ((string) ($page['id'] ?? 'post')), 1200, 630);
    }
    $capsule = ccms_capsule_decode($page);
    if (!is_array($capsule)) {
        return '';
    }
    foreach (($capsule['blocks'] ?? []) as $block) {
        $props = is_array($block['props'] ?? null) ? $block['props'] : [];
        foreach (['image_url', 'background_image', 'image', 'photo', 'logo', 'thumbnail'] as $key) {
            $candidate = ccms_sanitize_url((string) ($props[$key] ?? ''), true);
            if ($candidate !== '') {
                return ccms_capsule_media_url($candidate, 'og-' . ((string) ($block['id'] ?? 'page')), 1200, 630);
            }
        }
        foreach (['images', 'items', 'posts', 'projects', 'services'] as $collectionKey) {
            foreach ((array) ($props[$collectionKey] ?? []) as $item) {
                if (!is_array($item)) {
                    continue;
                }
                foreach (['image', 'image_url', 'photo', 'logo', 'thumbnail', 'url'] as $itemKey) {
                    $candidate = ccms_sanitize_url((string) ($item[$itemKey] ?? ''), true);
                    if ($candidate !== '') {
                        return ccms_capsule_media_url($candidate, 'og-' . ((string) ($block['id'] ?? 'page')), 1200, 630);
                    }
                }
            }
        }
    }
    return '';
}

function ccms_render_public_schema(array $site, array $page): string
{
    $contentType = (string) ($page['content_type'] ?? '');
    if ($contentType === 'post') {
        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'BlogPosting',
            'headline' => trim((string) ($page['meta_title'] ?? '')) ?: trim((string) ($page['title'] ?? '')),
            'description' => trim((string) ($page['meta_description'] ?? '')) ?: trim((string) ($page['excerpt'] ?? '')),
            'url' => ccms_public_page_url($page),
            'datePublished' => trim((string) ($page['published_at'] ?? $page['updated_at'] ?? '')),
            'dateModified' => trim((string) ($page['updated_at'] ?? $page['published_at'] ?? '')),
            'author' => [
                '@type' => 'Person',
                'name' => trim((string) ($page['author_name'] ?? '')) ?: trim((string) ($site['title'] ?? 'LinuxCMS')),
            ],
        ];
        $siteTitle = trim((string) ($site['title'] ?? ''));
        if ($siteTitle !== '') {
            $schema['publisher'] = ['@type' => 'Organization', 'name' => $siteTitle];
        }
        $image = ccms_page_primary_image($page);
        if ($image !== '') {
            $schema['image'] = $image;
        }
        if (!empty($page['categories'][0])) {
            $schema['articleSection'] = (string) $page['categories'][0];
        }
        return json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '{}';
    }
    $schema = [
        '@context' => 'https://schema.org',
        '@type' => !empty($page['is_homepage']) ? 'WebSite' : 'WebPage',
        'name' => trim((string) ($page['meta_title'] ?? '')) ?: trim((string) ($page['title'] ?? $site['title'] ?? 'LinuxCMS')),
        'description' => trim((string) ($page['meta_description'] ?? '')) ?: trim((string) ($site['tagline'] ?? '')),
        'url' => ccms_public_page_url($page),
    ];
    $siteTitle = trim((string) ($site['title'] ?? ''));
    $contactEmail = trim((string) ($site['contact_email'] ?? ''));
    if ($siteTitle !== '') {
        $schema['publisher'] = ['@type' => 'Organization', 'name' => $siteTitle];
        if ($contactEmail !== '') {
            $schema['publisher']['email'] = $contactEmail;
        }
    }
    $image = ccms_page_primary_image($page);
    if ($image !== '') {
        $schema['image'] = $image;
    }
    return json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '{}';
}

function ccms_render_public_analytics(array $site): string
{
    $provider = trim((string) ($site['analytics_provider'] ?? ''));
    $analyticsId = trim((string) ($site['analytics_id'] ?? ''));
    if ($provider === '' || $analyticsId === '') {
        return '';
    }
    if ($provider === 'ga4') {
        return '<script async src="https://www.googletagmanager.com/gtag/js?id=' . ccms_h($analyticsId) . '"></script>'
            . '<script' . ccms_script_nonce_attr() . '>window.dataLayer=window.dataLayer||[];function gtag(){dataLayer.push(arguments);}gtag("js",new Date());gtag("config",' . json_encode($analyticsId) . ');</script>';
    }
    if ($provider === 'plausible') {
        return '<script defer data-domain="' . ccms_h($analyticsId) . '" src="https://plausible.io/js/script.js"></script>';
    }
    return '';
}

function ccms_render_sitemap_xml(array $data): string
{
    $pages = array_values(array_filter(is_array($data['pages'] ?? null) ? $data['pages'] : [], static function (array $page): bool {
        return ccms_record_is_public($page);
    }));
    $posts = ccms_posts_published($data);
    usort($pages, static function (array $a, array $b): int {
        return strcmp((string) ($b['updated_at'] ?? ''), (string) ($a['updated_at'] ?? ''));
    });
    $urls = '';
    foreach ($pages as $page) {
        $urls .= "<url>\n"
            . '  <loc>' . ccms_h(ccms_public_page_url($page)) . "</loc>\n"
            . '  <lastmod>' . ccms_h((string) ($page['updated_at'] ?? ccms_now_iso())) . "</lastmod>\n"
            . "</url>\n";
    }
    if (!empty($posts)) {
        $urls .= "<url>\n"
            . '  <loc>' . ccms_h(ccms_blog_archive_url()) . "</loc>\n"
            . '  <lastmod>' . ccms_h((string) ($posts[0]['updated_at'] ?? ccms_now_iso())) . "</lastmod>\n"
            . "</url>\n";
    }
    foreach ($posts as $post) {
        $urls .= "<url>\n"
            . '  <loc>' . ccms_h(ccms_post_public_url($post)) . "</loc>\n"
            . '  <lastmod>' . ccms_h((string) ($post['updated_at'] ?? $post['published_at'] ?? ccms_now_iso())) . "</lastmod>\n"
            . "</url>\n";
    }
    foreach (ccms_blog_categories($data) as $category) {
        $slug = ccms_taxonomy_slug($category);
        $categoryPosts = ccms_posts_for_category_slug($data, $slug);
        if (empty($categoryPosts)) {
            continue;
        }
        $urls .= "<url>\n"
            . '  <loc>' . ccms_h(ccms_blog_category_url($slug)) . "</loc>\n"
            . '  <lastmod>' . ccms_h((string) ($categoryPosts[0]['updated_at'] ?? ccms_now_iso())) . "</lastmod>\n"
            . "</url>\n";
    }
    foreach (ccms_blog_tags($data) as $tag) {
        $slug = ccms_taxonomy_slug($tag);
        $tagPosts = ccms_posts_for_tag_slug($data, $slug);
        if (empty($tagPosts)) {
            continue;
        }
        $urls .= "<url>\n"
            . '  <loc>' . ccms_h(ccms_blog_tag_url($slug)) . "</loc>\n"
            . '  <lastmod>' . ccms_h((string) ($tagPosts[0]['updated_at'] ?? ccms_now_iso())) . "</lastmod>\n"
            . "</url>\n";
    }
    return "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n"
        . "<urlset xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\">\n"
        . $urls
        . "</urlset>\n";
}

function ccms_render_robots_txt(): string
{
    return "User-agent: *\nAllow: /\nSitemap: " . rtrim(ccms_base_url(), '/') . "/sitemap.xml\n";
}

function ccms_post_excerpt(array $post, int $limit = 180): string
{
    $excerpt = trim((string) ($post['excerpt'] ?? ''));
    if ($excerpt === '') {
        $source = trim(strip_tags((string) ($post['content_html'] ?? '')));
        $excerpt = preg_replace('/\s+/u', ' ', $source) ?? $source;
    }
    if (mb_strlen($excerpt, 'UTF-8') <= $limit) {
        return $excerpt;
    }
    return rtrim(mb_substr($excerpt, 0, $limit - 1, 'UTF-8')) . '…';
}

function ccms_render_blog_post_body_html(array $post): string
{
    $coverImage = ccms_sanitize_url((string) ($post['cover_image'] ?? ''), true);
    $publishedLabel = trim((string) ($post['published_at'] ?? '')) !== '' ? date('j M Y', strtotime((string) $post['published_at'])) : '';
    $categories = array_map(static function ($category): string {
        $label = trim((string) $category);
        if ($label === '') {
            return '';
        }
        return '<a href="' . ccms_h('/blog/category/' . rawurlencode(ccms_taxonomy_slug($label))) . '" style="text-decoration:none;color:var(--primary)">' . ccms_h($label) . '</a>';
    }, (array) ($post['categories'] ?? []));
    $categories = array_values(array_filter($categories));
    $tags = array_map(static function ($tag): string {
        $label = trim((string) $tag);
        if ($label === '') {
            return '';
        }
        return '<a href="' . ccms_h('/blog/tag/' . rawurlencode(ccms_taxonomy_slug($label))) . '" style="display:inline-flex;padding:8px 12px;border-radius:999px;background:#f3ece6;text-decoration:none;color:var(--text);font-size:13px;font-weight:700">' . ccms_h($label) . '</a>';
    }, (array) ($post['tags'] ?? []));
    $tags = array_values(array_filter($tags));
    return '<section style="padding:56px 32px 20px"><div class="shell" style="max-width:880px">'
        . (!empty($categories) ? '<div style="display:flex;flex-wrap:wrap;gap:10px;margin-bottom:14px">' . implode('', $categories) . '</div>' : '')
        . '<h1 style="font-size:52px;line-height:1.04;margin:0 0 14px">' . ccms_h((string) ($post['title'] ?? 'Untitled post')) . '</h1>'
        . '<div style="display:flex;flex-wrap:wrap;gap:12px;color:var(--muted);font-size:14px;margin-bottom:18px">'
        . ($publishedLabel !== '' ? '<span>Publicado · ' . ccms_h($publishedLabel) . '</span>' : '')
        . (trim((string) ($post['author_name'] ?? '')) !== '' ? '<span>Autor · ' . ccms_h((string) $post['author_name']) . '</span>' : '')
        . '</div>'
        . '<p style="font-size:19px;line-height:1.8;color:var(--muted);margin:0 0 20px">' . ccms_h(ccms_post_excerpt($post, 240)) . '</p>'
        . ($coverImage !== '' ? '<img src="' . ccms_h($coverImage) . '" alt="' . ccms_h((string) ($post['title'] ?? '')) . '" style="width:100%;height:min(54vw,460px);object-fit:cover;border-radius:var(--site-image-radius);box-shadow:var(--site-card-shadow);margin:0 0 28px">' : '')
        . '<div class="page-content" style="background:var(--surface);border-radius:var(--site-card-radius);padding:30px;box-shadow:var(--site-card-shadow)">' . ccms_sanitize_html((string) ($post['content_html'] ?? '')) . '</div>'
        . (!empty($tags) ? '<div style="display:flex;flex-wrap:wrap;gap:10px;margin-top:24px">' . implode('', $tags) . '</div>' : '')
        . '</div></section>';
}

function ccms_render_blog_archive_body_html(array $posts, ?string $categoryLabel = null, ?string $tagLabel = null): string
{
    $title = 'Blog';
    $subtitle = 'Últimos artículos publicados.';
    if ($categoryLabel !== null && $categoryLabel !== '') {
        $title = 'Categoría: ' . $categoryLabel;
        $subtitle = 'Artículos archivados en esta categoría.';
    } elseif ($tagLabel !== null && $tagLabel !== '') {
        $title = 'Etiqueta: ' . $tagLabel;
        $subtitle = 'Artículos relacionados con esta etiqueta.';
    }
    $items = '';
    foreach ($posts as $post) {
        $url = '/blog/' . rawurlencode((string) ($post['slug'] ?? ''));
        $image = ccms_sanitize_url((string) ($post['cover_image'] ?? ''), true);
        $publishedLabel = trim((string) ($post['published_at'] ?? '')) !== '' ? date('j M Y', strtotime((string) $post['published_at'])) : '';
        $categories = implode(' · ', array_map('strval', (array) ($post['categories'] ?? [])));
        $items .= '<article style="background:var(--surface);border-radius:var(--site-card-radius);box-shadow:var(--site-card-shadow);overflow:hidden">'
            . ($image !== '' ? '<a href="' . ccms_h($url) . '" style="display:block"><img src="' . ccms_h($image) . '" alt="' . ccms_h((string) ($post['title'] ?? '')) . '" style="width:100%;height:280px;object-fit:cover"></a>' : '')
            . '<div style="padding:24px">'
            . ($categories !== '' ? '<div style="font-size:12px;font-weight:800;letter-spacing:.08em;text-transform:uppercase;color:var(--primary);margin-bottom:10px">' . ccms_h($categories) . '</div>' : '')
            . '<h2 style="font-size:30px;line-height:1.08;margin:0 0 12px"><a href="' . ccms_h($url) . '" style="text-decoration:none">' . ccms_h((string) ($post['title'] ?? 'Untitled post')) . '</a></h2>'
            . '<p style="font-size:17px;line-height:1.75;color:var(--muted);margin:0 0 16px">' . ccms_h(ccms_post_excerpt($post)) . '</p>'
            . '<div style="display:flex;justify-content:space-between;gap:16px;align-items:center;flex-wrap:wrap">'
            . '<span style="color:var(--muted);font-size:14px">' . ccms_h($publishedLabel) . '</span>'
            . '<a href="' . ccms_h($url) . '" style="font-weight:800;text-decoration:none;color:var(--primary)">Leer artículo</a>'
            . '</div></div></article>';
    }
    if ($items === '') {
        $items = '<div style="background:var(--surface);border-radius:var(--site-card-radius);box-shadow:var(--site-card-shadow);padding:28px;color:var(--muted)">Todavía no hay artículos publicados.</div>';
    }
    return '<section style="padding:56px 32px"><div class="shell"><div style="max-width:760px;margin-bottom:28px"><span class="ccms-chip">Blog</span><h1 style="font-size:48px;line-height:1.04;margin:16px 0 14px">' . ccms_h($title) . '</h1><p style="font-size:18px;line-height:1.75;color:var(--muted);margin:0">' . ccms_h($subtitle) . '</p></div><div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:24px">' . $items . '</div></div></section>';
}

function ccms_render_blog_archive_page(array $site, array $posts, array $menuPages, ?string $categoryLabel = null, ?string $tagLabel = null): string
{
    $path = '/blog';
    $title = 'Blog';
    $description = 'Archivo de artículos del sitio.';
    if ($categoryLabel !== null && $categoryLabel !== '') {
        $title = 'Blog · ' . $categoryLabel;
        $description = 'Archivo de artículos de la categoría ' . $categoryLabel . '.';
        $path = '/blog/category/' . rawurlencode(ccms_taxonomy_slug($categoryLabel));
    } elseif ($tagLabel !== null && $tagLabel !== '') {
        $title = 'Blog · ' . $tagLabel;
        $description = 'Archivo de artículos con la etiqueta ' . $tagLabel . '.';
        $path = '/blog/tag/' . rawurlencode(ccms_taxonomy_slug($tagLabel));
    }
    $pseudoPage = [
        'id' => 'blog_archive_' . md5($path),
        'title' => $title,
        'meta_title' => $title,
        'meta_description' => $description,
        'html_content' => ccms_render_blog_archive_body_html($posts, $categoryLabel, $tagLabel),
        'canonical_url' => rtrim(ccms_base_url(), '/') . $path,
        'content_type' => 'blog_archive',
        'updated_at' => $posts[0]['updated_at'] ?? ccms_now_iso(),
    ];
    return ccms_render_public_page($site, $pseudoPage, $menuPages);
}

function ccms_render_blog_post_page(array $site, array $post, array $menuPages): string
{
    $pseudoPage = [
        'id' => (string) ($post['id'] ?? ccms_next_id('post')),
        'slug' => (string) ($post['slug'] ?? ''),
        'title' => (string) ($post['title'] ?? 'Untitled post'),
        'meta_title' => trim((string) ($post['meta_title'] ?? '')) ?: (string) ($post['title'] ?? 'Untitled post'),
        'meta_description' => trim((string) ($post['meta_description'] ?? '')) ?: ccms_post_excerpt($post),
        'html_content' => ccms_render_blog_post_body_html($post),
        'content_type' => 'post',
        'cover_image' => (string) ($post['cover_image'] ?? ''),
        'author_name' => (string) ($post['author_name'] ?? ''),
        'categories' => (array) ($post['categories'] ?? []),
        'excerpt' => (string) ($post['excerpt'] ?? ''),
        'published_at' => (string) ($post['published_at'] ?? ''),
        'updated_at' => (string) ($post['updated_at'] ?? ccms_now_iso()),
    ];
    return ccms_render_public_page($site, $pseudoPage, $menuPages);
}

function ccms_render_blog_rss(array $site, array $posts): string
{
    $siteTitle = trim((string) ($site['title'] ?? 'LinuxCMS'));
    $siteTagline = trim((string) ($site['tagline'] ?? ''));
    $siteLink = ccms_blog_archive_url();
    $items = '';
    foreach ($posts as $post) {
        $description = ccms_post_excerpt($post, 400);
        $publishedAt = trim((string) ($post['published_at'] ?? $post['updated_at'] ?? ''));
        $items .= "<item>\n"
            . '  <title>' . ccms_h((string) ($post['title'] ?? 'Untitled post')) . "</title>\n"
            . '  <link>' . ccms_h(ccms_post_public_url($post)) . "</link>\n"
            . '  <guid>' . ccms_h(ccms_post_public_url($post)) . "</guid>\n"
            . '  <description>' . ccms_h($description) . "</description>\n"
            . ($publishedAt !== '' ? '  <pubDate>' . ccms_h(gmdate(DATE_RSS, strtotime($publishedAt))) . "</pubDate>\n" : '')
            . "</item>\n";
    }
    return "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n"
        . "<rss version=\"2.0\">\n"
        . "<channel>\n"
        . '  <title>' . ccms_h($siteTitle . ' Blog') . "</title>\n"
        . '  <link>' . ccms_h($siteLink) . "</link>\n"
        . '  <description>' . ccms_h($siteTagline !== '' ? $siteTagline : ('RSS feed for ' . $siteTitle)) . "</description>\n"
        . $items
        . "</channel>\n"
        . "</rss>\n";
}

function ccms_capsule_animation_attr(array $block): string
{
    $animation = trim((string) (ccms_capsule_block_style($block)['animation'] ?? ''));
    if ($animation === '' || $animation === 'none' || !in_array($animation, ccms_allowed_block_animations(), true)) {
        return '';
    }
    return ' data-ccms-animate="' . ccms_h($animation) . '"';
}

function ccms_page_body_html(array $page, array $site = []): string
{
    $capsule = ccms_capsule_decode($page);
    if (ccms_capsule_can_render($capsule)) {
        ccms_set_current_public_page($page);
        try {
            return ccms_render_capsule_body($capsule, $site);
        } finally {
            ccms_set_current_public_page(null);
        }
    }
    return ccms_sanitize_html((string) ($page['html_content'] ?? '<section><p>Empty page.</p></section>'));
}

function ccms_render_public_page(array $site, array $page, array $menuPages): string
{
    ccms_load_enabled_plugins($site);
    $colors = $site['colors'] ?? [];
    $theme = ccms_site_theme_preset($site);
    $customCss = ccms_sanitize_css(trim((string) ($site['custom_css'] ?? '')));
    $pageUrl = ccms_public_page_url($page);
    $pageImage = ccms_page_primary_image($page);
    $analyticsHtml = ccms_render_public_analytics($site);
    $schemaJson = ccms_render_public_schema($site, $page);
    $pluginHead = ccms_render_plugin_fragments('public_head_end', [
        'site' => $site,
        'page' => $page,
        'menu_pages' => $menuPages,
    ]);
    $pluginBodyEnd = ccms_render_plugin_fragments('public_body_end', [
        'site' => $site,
        'page' => $page,
        'menu_pages' => $menuPages,
    ]);
    $pageTitle = trim((string) ($page['meta_title'] ?? '')) ?: trim((string) ($page['title'] ?? ''));
    $metaDescription = trim((string) ($page['meta_description'] ?? '')) ?: trim((string) ($site['tagline'] ?? ''));
    $contentType = (string) ($page['content_type'] ?? '');
    $ogType = $contentType === 'post' ? 'article' : 'website';
    $capsule = ccms_capsule_decode($page);
    $usesNativeCapsule = ccms_capsule_can_render($capsule);
    $blockTypes = [];
    if ($usesNativeCapsule) {
        foreach (($capsule['blocks'] ?? []) as $block) {
            $blockTypes[] = (string) ($block['type'] ?? '');
        }
    }
    $hasOwnHeader = $usesNativeCapsule && array_intersect($blockTypes, ['nav', 'sticky_header']);
    $hasOwnFooter = $usesNativeCapsule && array_intersect($blockTypes, ['footer_multi', 'contact']);
    $content = ccms_optimize_public_images_html(ccms_page_body_html($page, $site));
    $styleNonceAttr = ccms_style_nonce_attr();
    $menuHtml = '';
    foreach ($menuPages as $menuPage) {
        $href = !empty($menuPage['is_homepage']) ? '/' : '/' . rawurlencode((string) $menuPage['slug']);
        $label = trim((string) ($menuPage['menu_label'] ?? '')) ?: (string) ($menuPage['title'] ?? 'Untitled');
        $menuHtml .= '<a href="' . ccms_h($href) . '">' . ccms_h($label) . '</a>';
    }

    $outerHeader = '';
    if (!$hasOwnHeader) {
        $outerHeader = '<header class="site-header">
    <div class="shell site-header-inner">
      <a class="brand" href="/">' . ccms_h((string) ($site['title'] ?? 'LinuxCMS')) . '</a>
      <nav class="menu">' . $menuHtml . '</nav>
    </div>
  </header>';
    }

    $outerFooter = '';
    if (!$hasOwnFooter) {
        $outerFooter = '<footer class="site-footer">
    <div class="shell">' . ccms_h((string) ($site['footer_text'] ?? 'Powered by LinuxCMS')) . '</div>
  </footer>';
    }

    if ($usesNativeCapsule) {
        $mainHtml = '<main>' . $content . '</main>';
    } else {
        $mainHtml = '<main class="shell page-shell">
    <div class="page-surface">
      <div class="page-content">' . $content . '</div>
    </div>
  </main>';
    }

    $scriptNonceAttr = ccms_script_nonce_attr();
    return '<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>' . ccms_h($pageTitle) . '</title>
  <meta name="description" content="' . ccms_h($metaDescription) . '">
  <link rel="canonical" href="' . ccms_h($pageUrl) . '">
  <meta property="og:title" content="' . ccms_h($pageTitle) . '">
  <meta property="og:description" content="' . ccms_h($metaDescription) . '">
  <meta property="og:type" content="' . ccms_h($ogType) . '">
  <meta property="og:url" content="' . ccms_h($pageUrl) . '">
  <meta property="og:site_name" content="' . ccms_h((string) ($site['title'] ?? 'LinuxCMS')) . '">
  <meta name="twitter:card" content="summary_large_image">
  <meta name="twitter:title" content="' . ccms_h($pageTitle) . '">
  <meta name="twitter:description" content="' . ccms_h($metaDescription) . '">' . ($contentType === 'post' && trim((string) ($page['published_at'] ?? '')) !== '' ? '
  <meta property="article:published_time" content="' . ccms_h((string) $page['published_at']) . '">' : '') . ($pageImage !== '' ? '
  <meta property="og:image" content="' . ccms_h($pageImage) . '">
  <meta name="twitter:image" content="' . ccms_h($pageImage) . '">' : '') . '
  <style' . $styleNonceAttr . '>
    :root{
      --bg:' . ccms_h((string) ($colors['bg'] ?? '#f7f4ee')) . ';
      --surface:' . ccms_h((string) ($colors['surface'] ?? '#ffffff')) . ';
      --text:' . ccms_h((string) ($colors['text'] ?? '#2f241f')) . ';
      --muted:' . ccms_h((string) ($colors['muted'] ?? '#6b5b53')) . ';
      --primary:' . ccms_h((string) ($colors['primary'] ?? '#c86f5c')) . ';
      --secondary:' . ccms_h((string) ($colors['secondary'] ?? '#d9c4b3')) . ';
      --max:1200px;
      --site-surface-radius:' . ccms_h($theme['surface_radius']) . ';
      --site-card-radius:' . ccms_h($theme['card_radius']) . ';
      --site-button-radius:' . ccms_h($theme['button_radius']) . ';
      --site-image-radius:' . ccms_h($theme['image_radius']) . ';
      --site-space-scale:' . ccms_h($theme['spacing_scale']) . ';
      --site-heading-weight:' . ccms_h($theme['font_weight_heading']) . ';
      --site-heading-spacing:' . ccms_h($theme['letter_spacing_heading']) . ';
      --site-heading-transform:' . ccms_h($theme['text_transform_heading']) . ';
      --site-body-leading:' . ccms_h($theme['line_height_body']) . ';
      --site-shadow:' . ccms_h($theme['shadow']) . ';
      --site-card-shadow:' . ccms_h($theme['card_shadow']) . ';
    }
    *{box-sizing:border-box}
    body{margin:0;background:var(--bg);color:var(--text);font-family:' . ccms_h($theme['font_body']) . ';line-height:var(--site-body-leading)}
    a{color:inherit}
    .shell{width:min(var(--max),calc(100% - 28px));margin:0 auto}
    .site-header{position:sticky;top:0;z-index:30;background:' . ccms_h($theme['header_bg']) . ';backdrop-filter:blur(10px);border-bottom:1px solid ' . ccms_h($theme['header_border']) . '}
    .site-header-inner{display:flex;align-items:center;justify-content:space-between;gap:16px;min-height:72px}
    .brand{font-weight:var(--site-heading-weight);font-size:20px;text-decoration:none;font-family:' . ccms_h($theme['font_heading']) . ';letter-spacing:var(--site-heading-spacing);text-transform:var(--site-heading-transform)}
    .menu{display:flex;flex-wrap:wrap;gap:14px}
    .menu a{text-decoration:none;color:var(--muted);font-weight:700}
    .menu a:hover{color:var(--text)}
    .page-shell{padding:32px 0 48px}
    .page-surface{background:var(--surface);border-radius:var(--site-surface-radius);box-shadow:var(--site-shadow);overflow:hidden}
    .page-content{padding:0}
    .site-footer{padding:22px 0 42px;color:var(--muted);font-size:14px;text-align:center}
    .site-footer a{text-decoration:none;color:var(--text)}
    .ccms-btn{border-radius:var(--site-button-radius)!important}
    h1,h2,h3,h4,h5,h6,.ccms-title,.ccms-section-title{font-family:' . ccms_h($theme['font_heading']) . ';font-weight:var(--site-heading-weight);letter-spacing:var(--site-heading-spacing);text-transform:var(--site-heading-transform)}
    @media (max-width:1100px){
      .ccms-title{font-size:44px}
      .ccms-section-title{font-size:36px}
    }
    @media (max-width:768px){
      .site-header-inner{display:block;padding:12px 0}
      .brand{display:block;margin-bottom:10px}
      .menu{gap:10px}
      .ccms-title{font-size:36px}
      .ccms-section-title{font-size:28px}
    }
    @media (max-width:480px){
      .ccms-title{font-size:28px}
      .ccms-section-title{font-size:24px}
      .ccms-btn{width:100%;text-align:center}
    }
  </style>' . ($customCss !== '' ? '
  <style' . $styleNonceAttr . ' id="ccms-custom-css">
' . $customCss . '
  </style>' : '') . '
  <script type="application/ld+json"' . $scriptNonceAttr . '>' . $schemaJson . '</script>' . ($analyticsHtml !== '' ? '
' . $analyticsHtml : '') . ($pluginHead !== '' ? '
' . $pluginHead : '') . '
</head>
<body>
  ' . $outerHeader . '
  ' . $mainHtml . '
  ' . $outerFooter . '
  ' . $pluginBodyEnd . '
</body>
</html>';
}

function ccms_render_capsule_body(array $capsule, array $site = []): string
{
    $style = ccms_capsule_style($capsule, $site);
    $scriptNonceAttr = ccms_script_nonce_attr();
    $styleNonceAttr = ccms_style_nonce_attr();
    $html = '<style' . $styleNonceAttr . '>
      .ccms-capsule{background:linear-gradient(180deg,' . ccms_h($style['bg_from']) . ' 0%,' . ccms_h($style['bg_to']) . ' 100%);color:' . ccms_h($style['text_primary']) . ';font-family:' . ccms_h($style['font_family']) . ';--site-surface-radius:' . ccms_h($style['surface_radius']) . ';--site-card-radius:' . ccms_h($style['card_radius']) . ';--site-button-radius:' . ccms_h($style['button_radius']) . ';--site-image-radius:' . ccms_h($style['image_radius']) . ';--site-space-scale:' . ccms_h($style['spacing_scale']) . ';--site-heading-weight:' . ccms_h($style['font_weight_heading']) . ';--site-heading-spacing:' . ccms_h($style['letter_spacing_heading']) . ';--site-heading-transform:' . ccms_h($style['text_transform_heading']) . ';--site-body-leading:' . ccms_h($style['line_height_body']) . ';--site-shadow:' . ccms_h($style['shadow']) . ';--site-card-shadow:' . ccms_h($style['shadow']) . '}
      .ccms-capsule *{box-sizing:border-box}
      .ccms-capsule section{position:relative}
      [data-ccms-bg]{position:relative;isolation:isolate;overflow:hidden}
      [data-ccms-bg] > .ccms-c-inner{position:relative;z-index:1}
      [data-ccms-bg="gradient"]{background-image:linear-gradient(135deg,rgba(255,255,255,.14),rgba(0,0,0,.03))}
      [data-ccms-bg="mesh"]::before,
      [data-ccms-bg="grain"]::before,
      [data-ccms-bg="dots"]::before,
      [data-ccms-bg="glass"]::before{
        content:"";
        position:absolute;
        inset:0;
        pointer-events:none;
        z-index:0
      }
      [data-ccms-bg="mesh"]::before{
        opacity:.55;
        background:
          radial-gradient(circle at 15% 20%,rgba(200,111,92,.24),transparent 32%),
          radial-gradient(circle at 82% 18%,rgba(171,93,78,.18),transparent 30%),
          radial-gradient(circle at 52% 78%,rgba(217,196,179,.26),transparent 36%)
      }
      [data-ccms-bg="grain"]::before{
        opacity:.12;
        background-image:url("data:image/svg+xml,%3Csvg xmlns=%27http://www.w3.org/2000/svg%27 width=%27180%27 height=%27180%27 viewBox=%270 0 180 180%27%3E%3Cfilter id=%27n%27%3E%3CfeTurbulence type=%27fractalNoise%27 baseFrequency=%27.82%27 numOctaves=%272%27 stitchTiles=%27stitch%27/%3E%3C/filter%3E%3Crect width=%27180%27 height=%27180%27 filter=%27url(%23n)%27 opacity=%27.9%27/%3E%3C/svg%3E");
        background-size:180px 180px;
        mix-blend-mode:multiply
      }
      [data-ccms-bg="dots"]::before{
        opacity:.4;
        background-image:radial-gradient(circle,rgba(47,36,31,.09) 1px,transparent 1px);
        background-size:18px 18px
      }
      [data-ccms-bg="glass"]::before{
        background:linear-gradient(135deg,rgba(255,255,255,.4),rgba(255,255,255,.12));
        backdrop-filter:blur(18px);
        border:1px solid rgba(255,255,255,.24)
      }
      .ccms-c-inner{width:min(1180px,calc(100% - 48px));margin:0 auto}
      .ccms-chip{display:inline-flex;padding:calc(8px * var(--site-space-scale)) calc(14px * var(--site-space-scale));border-radius:999px;background:rgba(229,115,115,.12);color:' . ccms_h($style['accent_dark']) . ';font-size:12px;font-weight:800;letter-spacing:.08em;text-transform:uppercase}
      .ccms-btn{display:inline-flex;align-items:center;justify-content:center;gap:8px;padding:calc(14px * var(--site-space-scale)) calc(22px * var(--site-space-scale));border-radius:var(--site-button-radius)!important;background:var(--ccms-button-bg,' . ccms_h($style['gradient_accent']) . ');color:var(--ccms-button-color,#fff);text-decoration:none;font-weight:800;box-shadow:var(--site-card-shadow);border:1px solid var(--ccms-button-border,transparent)}
      .ccms-btn--ghost{background:var(--ccms-button-ghost-bg,#fff);color:var(--ccms-button-ghost-color,' . ccms_h($style['text_primary']) . ');border:1px solid var(--ccms-button-ghost-border,rgba(0,0,0,.08))}
      .ccms-grid-2{display:grid;grid-template-columns:1fr 1fr;gap:calc(28px * var(--site-space-scale));align-items:center}
      .ccms-grid-3{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:calc(22px * var(--site-space-scale))}
      .ccms-pricing-stack-card{display:grid;grid-template-columns:minmax(0,.9fr) minmax(0,1.2fr) auto;gap:calc(22px * var(--site-space-scale));align-items:center}
      .ccms-gallery-masonry{columns:3 220px;column-gap:calc(22px * var(--site-space-scale))}
      .ccms-gallery-masonry .ccms-card{display:inline-block;width:100%;margin:0 0 calc(22px * var(--site-space-scale))}
      .ccms-gallery-spotlight{display:grid;grid-template-columns:minmax(0,1.15fr) minmax(0,.85fr);gap:calc(22px * var(--site-space-scale));align-items:stretch}
      .ccms-blog-list-card{display:grid;grid-template-columns:240px minmax(0,1fr);gap:calc(18px * var(--site-space-scale));align-items:stretch}
      .ccms-card{background:' . ccms_h($style['card_bg']) . ';border:1px solid ' . ccms_h($style['card_border']) . ';border-radius:var(--site-card-radius)!important;box-shadow:var(--site-card-shadow)!important}
      .ccms-title{font-family:' . ccms_h($style['font_heading']) . ';font-size:52px;line-height:1.02;margin:0 0 16px;font-weight:var(--site-heading-weight);letter-spacing:var(--site-heading-spacing);text-transform:var(--site-heading-transform)}
      .ccms-subtitle{font-size:18px;line-height:var(--site-body-leading);color:' . ccms_h($style['text_secondary']) . ';margin:0}
      .ccms-section-title{font-family:' . ccms_h($style['font_heading']) . ';font-size:42px;line-height:1.08;margin:0 0 14px;font-weight:var(--site-heading-weight);letter-spacing:var(--site-heading-spacing);text-transform:var(--site-heading-transform)}
      .ccms-text{font-size:18px;line-height:var(--site-body-leading);color:' . ccms_h($style['text_secondary']) . '}
      .ccms-kicker{display:inline-block;margin-bottom:12px;color:' . ccms_h($style['accent_dark']) . ';font-size:13px;font-weight:800;letter-spacing:.12em;text-transform:uppercase}
      .ccms-list{margin:0;padding-left:18px;display:grid;gap:8px}
      .ccms-list li{color:' . ccms_h($style['text_secondary']) . ';line-height:var(--site-body-leading)}
      .ccms-stat{text-align:center;padding:calc(26px * var(--site-space-scale)) calc(18px * var(--site-space-scale))}
      .ccms-stat strong{display:block;font-size:34px;line-height:1;margin-bottom:8px}
      .ccms-media{width:100%;height:460px;object-fit:cover;border-radius:var(--site-image-radius)!important;box-shadow:var(--site-card-shadow)}
      .ccms-media-sm{width:100%;height:250px;object-fit:cover;border-radius:var(--site-image-radius)!important}
      .ccms-table{width:100%;border-collapse:collapse}
      .ccms-table th,.ccms-table td{padding:16px 14px;border-bottom:1px solid rgba(0,0,0,.08);text-align:left;vertical-align:top}
      .ccms-table th{font-size:14px;letter-spacing:.08em;text-transform:uppercase;color:' . ccms_h($style['text_muted']) . '}
      .ccms-note{font-size:14px;line-height:var(--site-body-leading);color:' . ccms_h($style['text_muted']) . '}
      .ccms-footer{background:' . ccms_h($style['text_primary']) . ';color:#fff}
      .ccms-footer a{color:#fff;text-decoration:none}
      [data-ccms-animate]{opacity:0;transition:opacity .55s ease,transform .55s ease;will-change:opacity,transform}
      [data-ccms-animate="fade-up"]{transform:translateY(32px)}
      [data-ccms-animate="fade-left"]{transform:translateX(-32px)}
      [data-ccms-animate="fade-right"]{transform:translateX(32px)}
      [data-ccms-animate="scale"]{transform:scale(.95)}
      [data-ccms-animate].is-visible{opacity:1;transform:none}
      @media (prefers-reduced-motion: reduce){
        [data-ccms-animate]{opacity:1;transform:none;transition:none}
      }
      @media (max-width:1100px){
        .ccms-grid-3{grid-template-columns:repeat(2,minmax(0,1fr))}
        .ccms-gallery-spotlight{grid-template-columns:1fr}
        .ccms-blog-list-card{grid-template-columns:200px minmax(0,1fr)}
        .ccms-title{font-size:44px}
      }
      @media (max-width:768px){
        .ccms-c-inner{width:min(1180px,calc(100% - 28px))}
        .ccms-grid-2,.ccms-grid-3{grid-template-columns:1fr}
        .ccms-pricing-stack-card,.ccms-blog-list-card{grid-template-columns:1fr}
        .ccms-gallery-masonry{columns:2 180px}
        .ccms-title{font-size:36px}
        .ccms-section-title{font-size:28px}
      }
      @media (max-width:480px){
        .ccms-gallery-masonry{columns:1 100%}
        .ccms-title{font-size:28px}
        .ccms-section-title{font-size:24px}
        .ccms-btn{width:100%;text-align:center}
      }
    </style>';
    $hasAnimatedBlocks = false;
    ccms_capsule_set_current_style($style);
    try {
        $html .= '<div class="ccms-capsule">';
        foreach (($capsule['blocks'] ?? []) as $index => $block) {
            $blockType = (string) ($block['type'] ?? 'block');
            $blockId = (string) ($block['id'] ?? ($blockType . '_' . $index));
            $animationAttr = ccms_capsule_animation_attr($block);
            if ($animationAttr !== '') {
                $hasAnimatedBlocks = true;
            }
            $html .= '<div class="ccms-block-shell" data-ccms-block-index="' . $index . '" data-ccms-block-type="' . ccms_h($blockType) . '" data-ccms-block-id="' . ccms_h($blockId) . '"' . $animationAttr . '>';
            $html .= ccms_render_capsule_block($block, $style);
            $html .= '</div>';
        }
        $html .= '</div>';
    } finally {
        ccms_capsule_set_current_style(null);
    }
    if ($hasAnimatedBlocks) {
        $html .= '<script' . $scriptNonceAttr . '>(function(){var nodes=document.querySelectorAll("[data-ccms-animate]");if(!nodes.length)return;if(window.matchMedia&&window.matchMedia("(prefers-reduced-motion: reduce)").matches){nodes.forEach(function(node){node.classList.add("is-visible");});return;}if(!("IntersectionObserver" in window)){nodes.forEach(function(node){node.classList.add("is-visible");});return;}var observer=new IntersectionObserver(function(entries){entries.forEach(function(entry){if(entry.isIntersecting){entry.target.classList.add("is-visible");observer.unobserve(entry.target);}});},{threshold:0.15});nodes.forEach(function(node){observer.observe(node);});})();</script>';
    }
    return $html;
}

function ccms_admin_preview_html(string $html): string
{
    $scriptNonceAttr = ccms_script_nonce_attr();
    $styleNonceAttr = ccms_style_nonce_attr();
    $injected = '<style' . $styleNonceAttr . '>
      .ccms-block-shell{position:relative}
      .ccms-block-shell[data-ccms-block-index]{cursor:pointer}
      .ccms-block-shell.is-ccms-selected{outline:3px solid rgba(200,111,92,.72);outline-offset:-3px}
      .ccms-block-shell.is-ccms-selected::after{
        content:attr(data-ccms-block-type);
        position:absolute;
        left:16px;
        top:16px;
        z-index:120;
        display:inline-flex;
        align-items:center;
        padding:8px 12px;
        border-radius:999px;
        background:rgba(47,36,31,.92);
        color:#fff;
        font:700 11px/1.1 Arial,Helvetica,sans-serif;
        letter-spacing:.08em;
        text-transform:uppercase;
        pointer-events:none
      }
      .ccms-block-toolbar{
        position:absolute;
        right:16px;
        top:16px;
        z-index:121;
        display:none;
        align-items:center;
        gap:8px;
        flex-wrap:wrap;
        max-width:min(100% - 32px, 520px)
      }
      .ccms-block-shell.is-ccms-selected .ccms-block-toolbar{display:flex}
      .ccms-block-toolbar button{
        appearance:none;
        border:1px solid rgba(0,0,0,.08);
        background:rgba(255,255,255,.96);
        color:#2f241f;
        border-radius:999px;
        padding:9px 12px;
        font:700 12px/1 Arial,Helvetica,sans-serif;
        cursor:pointer;
        box-shadow:0 14px 28px -20px rgba(0,0,0,.28)
      }
      .ccms-block-toolbar button[data-ccms-action="remove"]{
        background:rgba(200,111,92,.14);
        color:#8a4638;
        border-color:rgba(200,111,92,.18)
      }
      .ccms-block-shell.is-ccms-selected h1,
      .ccms-block-shell.is-ccms-selected h2,
      .ccms-block-shell.is-ccms-selected h3,
      .ccms-block-shell.is-ccms-selected h4,
      .ccms-block-shell.is-ccms-selected h5,
      .ccms-block-shell.is-ccms-selected h6,
      .ccms-block-shell.is-ccms-selected p,
      .ccms-block-shell.is-ccms-selected li,
      .ccms-block-shell.is-ccms-selected a,
      .ccms-block-shell.is-ccms-selected button{
        transition:box-shadow .18s ease, background-color .18s ease
      }
      .ccms-block-shell.is-ccms-selected h1:hover,
      .ccms-block-shell.is-ccms-selected h2:hover,
      .ccms-block-shell.is-ccms-selected h3:hover,
      .ccms-block-shell.is-ccms-selected h4:hover,
      .ccms-block-shell.is-ccms-selected h5:hover,
      .ccms-block-shell.is-ccms-selected h6:hover,
      .ccms-block-shell.is-ccms-selected p:hover,
      .ccms-block-shell.is-ccms-selected li:hover,
      .ccms-block-shell.is-ccms-selected a:hover,
      .ccms-block-shell.is-ccms-selected button:hover{
        box-shadow:0 0 0 2px rgba(200,111,92,.16);
        background:rgba(200,111,92,.06)
      }
      .ccms-inline-editing{
        outline:2px dashed rgba(200,111,92,.55)!important;
        outline-offset:3px;
        background:rgba(255,255,255,.92)!important;
        cursor:text!important
      }
      @media (max-width:800px){
        .ccms-block-shell.is-ccms-selected::after{left:12px;top:12px}
        .ccms-block-toolbar{left:12px;right:12px;top:44px;max-width:none}
        .ccms-block-toolbar button{padding:8px 10px;font-size:11px}
      }
    </style>
    <script' . $scriptNonceAttr . '>
      (function(){
        const blocks = Array.from(document.querySelectorAll("[data-ccms-block-index]"));
        function setSelected(index){
          blocks.forEach((node) => node.classList.toggle("is-ccms-selected", Number(node.dataset.ccmsBlockIndex) === index));
        }
        function postToParent(payload){
          try {
            window.parent.postMessage(payload, "*");
          } catch (error) {}
        }
        function attachQuickMediaTargets(node){
          node.querySelectorAll("img,video").forEach((el) => {
            el.addEventListener("dblclick", (event) => {
              event.preventDefault();
              event.stopPropagation();
              const index = Number(node.dataset.ccmsBlockIndex || -1);
              if (index < 0) return;
              setSelected(index);
              const src = (el.currentSrc || el.getAttribute("src") || "").trim();
              postToParent({
                type: "ccms-preview-quick-media",
                index,
                src,
                tag: (el.tagName || "").toLowerCase(),
                blockType: node.dataset.ccmsBlockType || "",
                blockId: node.dataset.ccmsBlockId || ""
              });
            }, true);
          });
        }
        function attachQuickTextTargets(node){
          node.querySelectorAll("h1,h2,h3,h4,h5,h6,p,li,a,button").forEach((el) => {
            if (el.closest(".ccms-block-toolbar")) return;
            el.addEventListener("dblclick", (event) => {
              event.preventDefault();
              event.stopPropagation();
              const index = Number(node.dataset.ccmsBlockIndex || -1);
              if (index < 0) return;
              setSelected(index);
              const originalText = (el.textContent || "").trim();
              const tag = (el.tagName || "").toLowerCase();
              const isButtonLike = el.matches(".ccms-btn, button, [role=\"button\"]");
              if (isButtonLike) {
                const originalHref = String(el.getAttribute("href") || "").trim();
                const currentStyle = window.getComputedStyle(el);
                const currentBg = currentStyle.backgroundColor || "";
                const currentColor = currentStyle.color || "";
                const newText = window.prompt("Editar texto del botón", originalText);
                if (newText === null) {
                  return;
                }
                const hrefPrompt = tag === "a" ? window.prompt("Editar enlace del botón", originalHref) : originalHref;
                if (hrefPrompt === null) {
                  return;
                }
                const bgPrompt = window.prompt("Editar color de fondo del botón", currentBg);
                if (bgPrompt === null) {
                  return;
                }
                const colorPrompt = window.prompt("Editar color del texto del botón", currentColor);
                if (colorPrompt === null) {
                  return;
                }
                if (newText.trim() !== originalText || String(hrefPrompt).trim() !== originalHref || String(bgPrompt).trim() !== currentBg || String(colorPrompt).trim() !== currentColor) {
                  postToParent({
                    type: "ccms-preview-apply-button",
                    index,
                    tag,
                    oldText: originalText.slice(0, 220),
                    newText: newText.trim().slice(0, 220),
                    oldHref: originalHref,
                    newHref: String(hrefPrompt).trim().slice(0, 500),
                    buttonBg: String(bgPrompt).trim().slice(0, 120),
                    buttonTextColor: String(colorPrompt).trim().slice(0, 120),
                    ghost: el.classList.contains("ccms-btn--ghost") ? 1 : 0,
                    blockType: node.dataset.ccmsBlockType || "",
                    blockId: node.dataset.ccmsBlockId || ""
                  });
                }
                return;
              }
              if (tag === "a") {
                const originalHref = String(el.getAttribute("href") || "").trim();
                postToParent({
                  type: "ccms-preview-quick-link",
                  index,
                  href: originalHref,
                  text: originalText.slice(0, 220),
                  blockType: node.dataset.ccmsBlockType || "",
                  blockId: node.dataset.ccmsBlockId || ""
                });
                const newText = window.prompt("Editar texto del enlace", originalText);
                if (newText === null) {
                  return;
                }
                const newHref = window.prompt("Editar enlace URL", originalHref);
                if (newHref === null) {
                  return;
                }
                if (newText.trim() !== originalText || newHref.trim() !== originalHref) {
                  postToParent({
                    type: "ccms-preview-apply-link",
                    index,
                    tag,
                    oldText: originalText.slice(0, 220),
                    newText: newText.trim().slice(0, 220),
                    oldHref: originalHref,
                    newHref: newHref.trim().slice(0, 500),
                    blockType: node.dataset.ccmsBlockType || "",
                    blockId: node.dataset.ccmsBlockId || ""
                  });
                }
                return;
              }
              if (el.dataset.ccmsInlineEditing === "1") return;
              el.dataset.ccmsInlineEditing = "1";
              el.classList.add("ccms-inline-editing");
              el.setAttribute("contenteditable", "true");
              el.focus();
              try {
                const selection = window.getSelection();
                const range = document.createRange();
                range.selectNodeContents(el);
                selection.removeAllRanges();
                selection.addRange(range);
              } catch (error) {}
              const finish = (save) => {
                const newText = (el.textContent || "").trim();
                el.removeAttribute("contenteditable");
                el.classList.remove("ccms-inline-editing");
                delete el.dataset.ccmsInlineEditing;
                if (save && newText !== originalText) {
                  postToParent({
                    type: "ccms-preview-apply-text",
                    index,
                    tag,
                    oldText: originalText.slice(0, 220),
                    newText: newText.slice(0, 220),
                    blockType: node.dataset.ccmsBlockType || "",
                    blockId: node.dataset.ccmsBlockId || ""
                  });
                } else {
                  el.textContent = originalText;
                  postToParent({
                    type: "ccms-preview-quick-text",
                    index,
                    tag,
                    text: originalText.slice(0, 220),
                    blockType: node.dataset.ccmsBlockType || "",
                    blockId: node.dataset.ccmsBlockId || ""
                  });
                }
              };
              const onKeyDown = (keyEvent) => {
                if (keyEvent.key === "Escape") {
                  keyEvent.preventDefault();
                  el.removeEventListener("keydown", onKeyDown, true);
                  el.removeEventListener("blur", onBlur, true);
                  finish(false);
                } else if (keyEvent.key === "Enter" && tag !== "li") {
                  keyEvent.preventDefault();
                  el.removeEventListener("keydown", onKeyDown, true);
                  el.removeEventListener("blur", onBlur, true);
                  finish(true);
                }
              };
              const onBlur = () => {
                el.removeEventListener("keydown", onKeyDown, true);
                el.removeEventListener("blur", onBlur, true);
                finish(true);
              };
              el.addEventListener("keydown", onKeyDown, true);
              el.addEventListener("blur", onBlur, true);
            }, true);
          });
        }
        function attachToolbar(node){
          const toolbar = document.createElement("div");
          toolbar.className = "ccms-block-toolbar";
          [
            ["content", "Editar contenido"],
            ["link", "Editar enlace"],
            ["media", "Editar media"],
            ["style", "Editar estilo"],
            ["insert", "Insertar después"],
            ["duplicate", "Duplicar"],
            ["remove", "Eliminar"]
          ].forEach(([action, label]) => {
            const button = document.createElement("button");
            button.type = "button";
            button.dataset.ccmsAction = action;
            button.textContent = label;
            button.addEventListener("click", (event) => {
              event.preventDefault();
              event.stopPropagation();
              const index = Number(node.dataset.ccmsBlockIndex || -1);
              if (index < 0) return;
              setSelected(index);
              postToParent({
                type: "ccms-preview-action",
                action,
                index,
                blockType: node.dataset.ccmsBlockType || "",
                blockId: node.dataset.ccmsBlockId || ""
              });
            }, true);
            toolbar.appendChild(button);
          });
          node.appendChild(toolbar);
        }
        blocks.forEach((node) => {
          attachToolbar(node);
          attachQuickMediaTargets(node);
          attachQuickTextTargets(node);
          node.addEventListener("click", (event) => {
            event.preventDefault();
            event.stopPropagation();
            const index = Number(node.dataset.ccmsBlockIndex || -1);
            if (index < 0) return;
            setSelected(index);
            postToParent({
              type: "ccms-preview-select-block",
              index,
              blockType: node.dataset.ccmsBlockType || "",
              blockId: node.dataset.ccmsBlockId || ""
            });
          }, true);
        });
        window.addEventListener("message", (event) => {
          const data = event.data || {};
          if (data && data.type === "ccms-parent-highlight-block") {
            const index = Number(data.index || -1);
            if (index >= 0) setSelected(index);
          }
        });
      }());
    </script>';

    if (str_contains($html, '</body>')) {
        return str_replace('</body>', $injected . '</body>', $html);
    }
    return $html . $injected;
}

function ccms_capsule_layout_options(string $type): array
{
    return match ($type) {
        'hero', 'hero_fullscreen', 'hero_split' => ['default', 'reversed', 'centered', 'text-only'],
        'features' => ['3-col', '2x2-grid', 'stacked', '4-col'],
        'testimonials', 'testimonial_cards' => ['grid', 'spotlight', 'review-list'],
        'pricing', 'pricing_toggle' => ['3-col', '2-col', 'stacked', 'comparison'],
        'gallery' => ['grid', 'masonry', 'spotlight'],
        'blog_grid' => ['grid', 'featured-left', 'list'],
        'blog_featured' => ['split', 'reversed', 'stacked'],
        'blog_carousel' => ['grid', 'spotlight', 'compact'],
        default => [],
    };
}

function ccms_capsule_block_layout(array $block, string $fallback = 'default'): string
{
    $type = (string) ($block['type'] ?? '');
    $layout = trim((string) ($block['layout'] ?? ''));
    $allowed = ccms_capsule_layout_options($type);
    if ($allowed === []) {
        return $fallback;
    }
    return in_array($layout, $allowed, true) ? $layout : $fallback;
}

function ccms_capsule_layout_attr(string $layout): string
{
    return ' data-ccms-layout="' . ccms_h($layout) . '"';
}

function ccms_render_hero_actions(array $block, array $props): string
{
    $actions = '<a class="' . ccms_h(ccms_capsule_button_classes($block)) . '" href="' . ccms_h((string) ($props['cta_href'] ?? '#')) . '">' . ccms_h((string) ($props['cta_primary'] ?? 'Get Started')) . '</a>';
    if (!empty($props['cta_secondary'])) {
        $actions .= '<a class="ccms-btn ccms-btn--ghost" href="' . ccms_h((string) ($props['cta_secondary_href'] ?? '#')) . '">' . ccms_h((string) $props['cta_secondary']) . '</a>';
    }
    return '<div style="display:flex;flex-wrap:wrap;gap:14px;margin-top:26px">' . $actions . '</div>';
}

function ccms_render_hero_copy(array $block, array $props, array $style, string $titleTag = 'h1', bool $centered = false): string
{
    $heading = $titleTag === 'h2' ? 'h2' : 'h1';
    $alignStyle = $centered ? 'text-align:center;max-width:860px;margin:0 auto' : 'max-width:620px';
    $actions = ccms_render_hero_actions($block, $props);
    if ($centered) {
        $actions = str_replace('display:flex;', 'display:flex;justify-content:center;', $actions);
    }
    return '<div style="' . ccms_h($alignStyle) . '"><span class="ccms-chip">' . ccms_h((string) ($props['badge'] ?? '')) . '</span><' . $heading . ' class="ccms-title" style="margin-top:18px">' . ccms_h((string) ($props['title'] ?? 'Hero title')) . '</' . $heading . '><p class="ccms-subtitle">' . ccms_h((string) ($props['subtitle'] ?? '')) . '</p>' . $actions . '</div>';
}

function ccms_render_hero_visual(string $imageUrl, string $blockId): string
{
    return '<div class="ccms-card" style="padding:12px;overflow:hidden"><img class="ccms-media" src="' . ccms_h(ccms_capsule_media_url($imageUrl, 'hero-layout-' . $blockId, 1280, 960)) . '" alt=""></div>';
}

function ccms_render_hero_block_variant(string $type, array $block, array $props, array $style, string $sectionId, string $blockId): string
{
    $layout = ccms_capsule_block_layout($block, 'default');
    $imageUrl = trim((string) ($type === 'hero_split' ? ($props['image_url'] ?? '') : ($props['background_image'] ?? '')));
    $hasVisual = $imageUrl !== '' && $layout !== 'text-only';
    $padding = $type === 'hero_fullscreen' ? 'padding:112px 0' : 'padding:92px 0';
    $background = 'background:linear-gradient(135deg,' . $style['bg_from'] . ' 0%,' . $style['bg_to'] . ' 100%)';
    if (($type === 'hero' || $type === 'hero_fullscreen') && $imageUrl !== '' && in_array($layout, ['centered', 'text-only'], true)) {
        $background = 'background:linear-gradient(135deg,rgba(245,240,232,.88) 0%,rgba(255,255,255,.72) 100%),url(' . $imageUrl . ') center/cover no-repeat';
    }
    $sectionOpen = '<section id="' . ccms_h($sectionId) . '"' . ccms_capsule_layout_attr($layout) . ccms_capsule_section_style_attr($block, $padding . ';' . $background) . '><div class="ccms-c-inner"' . ccms_capsule_inner_style_attr($block) . '>';
    $visual = $hasVisual ? ccms_render_hero_visual($imageUrl, $blockId) : '';
    if ($layout === 'centered') {
        $copy = ccms_render_hero_copy($block, $props, $style, 'h1', true);
        $body = $copy;
        if ($visual !== '' && $type === 'hero_split') {
            $body .= '<div style="margin-top:26px">' . $visual . '</div>';
        }
        return $sectionOpen . $body . '</div></section>';
    }
    if ($layout === 'text-only' || $visual === '') {
        return $sectionOpen . ccms_render_hero_copy($block, $props, $style, 'h1', false) . '</div></section>';
    }
    $copy = ccms_render_hero_copy($block, $props, $style, 'h1', false);
    $content = $layout === 'reversed' ? $visual . $copy : $copy . $visual;
    return $sectionOpen . '<div class="ccms-grid-2" style="align-items:center">' . $content . '</div></div></section>';
}

function ccms_render_features_block_variant(array $block, array $props, array $style, string $sectionId): string
{
    $layout = ccms_capsule_block_layout($block, '3-col');
    $items = is_array($props['items'] ?? null) ? $props['items'] : [];
    $itemsHtml = '';
    foreach ($items as $index => $item) {
        $bullets = '';
        foreach (($item['bullets'] ?? []) as $bullet) {
            $bullets .= '<li>' . ccms_h((string) $bullet) . '</li>';
        }
        if ($layout === 'stacked') {
            $itemsHtml .= '<article class="ccms-card" style="padding:30px;display:grid;grid-template-columns:88px 1fr;gap:20px;align-items:start"><div><span class="ccms-chip" style="padding:10px 14px">' . str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT) . '</span></div><div><span class="ccms-kicker">' . ccms_h((string) ($item['kicker'] ?? 'Feature')) . '</span><h3 style="margin:0 0 10px;font-size:24px;font-family:' . ccms_h($style['font_heading']) . '">' . ccms_h((string) ($item['title'] ?? '')) . '</h3><p class="ccms-text" style="margin:0 0 14px">' . ccms_h((string) ($item['desc'] ?? $item['text'] ?? '')) . '</p>' . ($bullets !== '' ? '<ul class="ccms-list">' . $bullets . '</ul>' : '') . '</div></article>';
            continue;
        }
        $itemsHtml .= '<article class="ccms-card" style="padding:26px"><span class="ccms-kicker">' . ccms_h((string) ($item['kicker'] ?? 'Feature')) . '</span><h3 style="margin:0 0 10px;font-size:24px;font-family:' . ccms_h($style['font_heading']) . '">' . ccms_h((string) ($item['title'] ?? '')) . '</h3><p class="ccms-text" style="margin:0 0 14px">' . ccms_h((string) ($item['desc'] ?? $item['text'] ?? '')) . '</p>' . ($bullets !== '' ? '<ul class="ccms-list">' . $bullets . '</ul>' : '') . '</article>';
    }
    $gridOpen = match ($layout) {
        '2x2-grid' => '<div style="display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:24px">',
        'stacked' => '<div style="display:grid;gap:18px;max-width:980px;margin:0 auto">',
        '4-col' => '<div style="display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:20px">',
        default => '<div class="ccms-grid-3">',
    };
    return '<section id="' . ccms_h($sectionId) . '"' . ccms_capsule_layout_attr($layout) . ccms_capsule_section_style_attr($block, 'padding:76px 0;background:rgba(255,255,255,.58)') . '><div class="ccms-c-inner"' . ccms_capsule_inner_style_attr($block) . '><div style="text-align:center;max-width:860px;margin:0 auto 26px"><span class="ccms-chip">' . ccms_h((string) ($props['badge'] ?? 'Features')) . '</span><h2 class="ccms-section-title" style="margin-top:18px">' . ccms_h((string) ($props['title'] ?? '')) . '</h2><p class="ccms-subtitle">' . ccms_h((string) ($props['subtitle'] ?? '')) . '</p></div>' . $gridOpen . $itemsHtml . '</div></div></section>';
}

function ccms_render_testimonial_item(array $item, array $style, bool $withMetaCard = false, bool $compact = false): string
{
    $quoteSize = $compact ? '20px' : '24px';
    $quoteMargin = $compact ? '0 0 12px' : '0 0 16px';
    if (!$withMetaCard) {
        return '<p style="font-size:' . $quoteSize . ';line-height:1.5;margin:' . $quoteMargin . ';font-weight:700">“' . ccms_h((string) ($item['quote'] ?? '')) . '”</p><p style="margin:0;color:' . ccms_h($style['accent_dark']) . ';font-size:13px;font-weight:800;text-transform:uppercase;letter-spacing:.1em">' . ccms_h((string) ($item['name'] ?? '')) . ' · ' . ccms_h((string) ($item['role'] ?? '')) . '</p>';
    }
    $photo = '';
    if (!empty($item['image'])) {
        $photo = '<img src="' . ccms_h(ccms_capsule_media_url((string) $item['image'], 'testimonial-card-' . md5((string) ($item['name'] ?? 'client')), 280, 280)) . '" alt="" style="width:62px;height:62px;border-radius:999px;object-fit:cover">';
    }
    return '<div style="display:flex;align-items:center;gap:14px;margin-bottom:14px">' . $photo . '<div><div style="font-size:14px;color:#c68a55;letter-spacing:.08em">' . ccms_capsule_stars((int) ($item['stars'] ?? 0)) . '</div><p style="margin:4px 0 0;font-weight:800">' . ccms_h((string) ($item['name'] ?? '')) . '</p><p class="ccms-note" style="margin:2px 0 0">' . ccms_h((string) ($item['role'] ?? '')) . '</p></div></div><p style="font-size:' . $quoteSize . ';line-height:1.55;margin:0;font-weight:700">“' . ccms_h((string) ($item['quote'] ?? '')) . '”</p>';
}

function ccms_render_testimonials_block_variant(array $block, array $props, array $style, string $sectionId, bool $withMetaCard = false): string
{
    $layout = ccms_capsule_block_layout($block, 'grid');
    $items = array_values(is_array($props['items'] ?? null) ? $props['items'] : []);
    $header = '<div style="text-align:center;max-width:760px;margin:0 auto 26px"><span class="ccms-chip">' . ccms_h((string) ($props['badge'] ?? ($withMetaCard ? 'Success stories' : 'Testimonials'))) . '</span><h2 class="ccms-section-title" style="margin-top:18px">' . ccms_h((string) ($props['title'] ?? '')) . '</h2>' . (!empty($props['subtitle']) ? '<p class="ccms-subtitle">' . ccms_h((string) $props['subtitle']) . '</p>' : '') . '</div>';
    if ($layout === 'spotlight' && $items !== []) {
        $featured = array_shift($items);
        $sideCards = '';
        foreach ($items as $item) {
            $sideCards .= '<article class="ccms-card" style="padding:24px">' . ccms_render_testimonial_item($item, $style, $withMetaCard, true) . '</article>';
        }
        if ($sideCards === '') {
            $sideCards = '<article class="ccms-card" style="padding:24px">' . ccms_render_testimonial_item($featured, $style, $withMetaCard, true) . '</article>';
        }
        return '<section id="' . ccms_h($sectionId) . '"' . ccms_capsule_layout_attr($layout) . ccms_capsule_section_style_attr($block, 'padding:76px 0;background:rgba(255,255,255,.5)') . '><div class="ccms-c-inner"' . ccms_capsule_inner_style_attr($block) . '>' . $header . '<div class="ccms-grid-2" style="align-items:stretch"><article class="ccms-card" style="padding:34px">' . ccms_render_testimonial_item($featured, $style, $withMetaCard, false) . '</article><div style="display:grid;gap:18px">' . $sideCards . '</div></div></div></section>';
    }
    if ($layout === 'review-list') {
        $list = '';
        foreach ($items as $item) {
            $list .= '<article class="ccms-card" style="padding:24px">' . ccms_render_testimonial_item($item, $style, $withMetaCard, true) . '</article>';
        }
        return '<section id="' . ccms_h($sectionId) . '"' . ccms_capsule_layout_attr($layout) . ccms_capsule_section_style_attr($block, 'padding:76px 0;background:rgba(255,255,255,.5)') . '><div class="ccms-c-inner"' . ccms_capsule_inner_style_attr($block) . '>' . $header . '<div style="display:grid;gap:18px;max-width:920px;margin:0 auto">' . $list . '</div></div></section>';
    }
    $cards = '';
    foreach ($items as $item) {
        $cards .= '<article class="ccms-card" style="padding:28px">' . ccms_render_testimonial_item($item, $style, $withMetaCard, false) . '</article>';
    }
    return '<section id="' . ccms_h($sectionId) . '"' . ccms_capsule_layout_attr($layout) . ccms_capsule_section_style_attr($block, 'padding:76px 0;background:rgba(255,255,255,.5)') . '><div class="ccms-c-inner"' . ccms_capsule_inner_style_attr($block) . '>' . $header . '<div class="ccms-grid-3">' . $cards . '</div></div></section>';
}

function ccms_render_pricing_plan_card(array $block, array $plan, array $style, bool $stacked = false, string $supportingNote = ''): string
{
    $features = '';
    foreach (($plan['features'] ?? []) as $feature) {
        $features .= '<li>' . ccms_h((string) $feature) . '</li>';
    }
    $ctaHref = (string) ($plan['cta_href'] ?? $plan['href'] ?? '#contact');
    $highlight = !empty($plan['highlighted'])
        ? 'background:linear-gradient(180deg,rgba(229,115,115,.08) 0%,rgba(255,255,255,.98) 100%);border-color:rgba(229,115,115,.26);transform:translateY(-6px);'
        : '';
    $note = trim((string) ($plan['summary'] ?? ''));
    if ($supportingNote !== '') {
        $note = $supportingNote;
    }
    if ($stacked) {
        return '<article class="ccms-card ccms-pricing-stack-card" style="padding:28px;' . ccms_h($highlight) . '"><div><span class="ccms-kicker">' . ccms_h((string) ($plan['eyebrow'] ?? 'Plan')) . '</span><h3 style="margin:0 0 8px;font-size:24px;font-family:' . ccms_h($style['font_heading']) . '">' . ccms_h((string) ($plan['name'] ?? 'Plan')) . '</h3><p style="font-size:34px;font-weight:900;margin:0 0 10px">' . ccms_h((string) ($plan['price'] ?? '')) . '</p>' . ($note !== '' ? '<p class="ccms-note" style="margin:0">' . ccms_h($note) . '</p>' : '') . '</div><ul class="ccms-list" style="margin:0">' . $features . '</ul><div><a class="ccms-btn" href="' . ccms_h($ctaHref) . '">' . ccms_h((string) ($plan['cta'] ?? 'Get Started')) . '</a></div></article>';
    }
    return '<article class="ccms-card" style="padding:28px;' . ccms_h($highlight) . '"><h3 style="margin:0 0 8px;font-size:24px;font-family:' . ccms_h($style['font_heading']) . '">' . ccms_h((string) ($plan['name'] ?? 'Plan')) . '</h3><p style="font-size:34px;font-weight:900;margin:0 0 10px">' . ccms_h((string) ($plan['price'] ?? '')) . '</p>' . ($note !== '' ? '<p class="ccms-note" style="margin:0 0 12px;color:' . ccms_h($style['accent_dark']) . '">' . ccms_h($note) . '</p>' : '') . '<ul class="ccms-list" style="margin-bottom:20px">' . $features . '</ul><a class="ccms-btn" href="' . ccms_h($ctaHref) . '">' . ccms_h((string) ($plan['cta'] ?? 'Get Started')) . '</a></article>';
}

function ccms_render_pricing_block_variant(array $block, array $props, array $style, string $sectionId, bool $showAnnualLabel = false): string
{
    $layout = ccms_capsule_block_layout($block, '3-col');
    $plans = array_values(is_array($props['plans'] ?? null) ? $props['plans'] : []);
    $header = '<div style="text-align:center;max-width:820px;margin:0 auto 26px"><span class="ccms-chip">' . ccms_h((string) ($props['badge'] ?? 'Pricing')) . '</span><h2 class="ccms-section-title" style="margin-top:18px">' . ccms_h((string) ($props['title'] ?? '')) . '</h2>' . (!empty($props['subtitle']) ? '<p class="ccms-subtitle">' . ccms_h((string) $props['subtitle']) . '</p>' : '') . '</div>';
    $annualLabel = $showAnnualLabel ? trim((string) ($props['annual_label'] ?? '')) : '';
    if ($layout === 'comparison') {
        $featureMap = [];
        foreach ($plans as $plan) {
            foreach (($plan['features'] ?? []) as $feature) {
                $featureLabel = trim((string) $feature);
                if ($featureLabel !== '') {
                    $featureMap[$featureLabel] = true;
                }
            }
        }
        $features = array_keys($featureMap);
        $tableHead = '<tr><th>Feature</th>';
        foreach ($plans as $plan) {
            $tableHead .= '<th>' . ccms_h((string) ($plan['name'] ?? 'Plan')) . '</th>';
        }
        $tableHead .= '</tr>';
        $rows = '';
        foreach ($features as $featureLabel) {
            $row = '<tr><td style="font-weight:700">' . ccms_h($featureLabel) . '</td>';
            foreach ($plans as $plan) {
                $row .= '<td>' . ccms_capsule_bool_icon(in_array($featureLabel, $plan['features'] ?? [], true), $style) . '</td>';
            }
            $row .= '</tr>';
            $rows .= $row;
        }
        return '<section id="' . ccms_h($sectionId) . '"' . ccms_capsule_layout_attr($layout) . ccms_capsule_section_style_attr($block, 'padding:76px 0') . '><div class="ccms-c-inner"' . ccms_capsule_inner_style_attr($block) . '>' . $header . '<div class="ccms-card" style="padding:20px;overflow:auto"><table class="ccms-table"><thead>' . $tableHead . '</thead><tbody>' . $rows . '</tbody></table></div></div></section>';
    }
    $cards = '';
    foreach ($plans as $plan) {
        $cards .= ccms_render_pricing_plan_card($block, $plan, $style, $layout === 'stacked', $annualLabel);
    }
    $gridOpen = match ($layout) {
        '2-col' => '<div style="display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:22px;max-width:980px;margin:0 auto">',
        'stacked' => '<div style="display:grid;gap:18px;max-width:1080px;margin:0 auto">',
        default => '<div class="ccms-grid-3">',
    };
    return '<section id="' . ccms_h($sectionId) . '"' . ccms_capsule_layout_attr($layout) . ccms_capsule_section_style_attr($block, 'padding:76px 0') . '><div class="ccms-c-inner"' . ccms_capsule_inner_style_attr($block) . '>' . $header . $gridOpen . $cards . '</div></div></section>';
}

function ccms_render_gallery_figure($image, string $blockId, int $index, string $variant = 'grid'): string
{
    $url = is_array($image) ? (string) ($image['url'] ?? '') : (string) $image;
    $alt = is_array($image) ? (string) ($image['alt'] ?? 'Gallery image') : 'Gallery image';
    if ($variant === 'featured') {
        return '<figure class="ccms-card" style="padding:12px;margin:0;height:100%"><img class="ccms-media" src="' . ccms_h(ccms_capsule_media_url($url, 'gallery-featured-' . $blockId . '-' . $index, 1280, 960)) . '" alt="' . ccms_h($alt) . '"></figure>';
    }
    if ($variant === 'masonry') {
        $height = $index % 3 === 0 ? 360 : ($index % 2 === 0 ? 260 : 310);
        return '<figure class="ccms-card" style="padding:10px;margin:0"><img src="' . ccms_h(ccms_capsule_media_url($url, 'gallery-masonry-' . $blockId . '-' . $index, 960, 960)) . '" alt="' . ccms_h($alt) . '" style="width:100%;height:' . $height . 'px;object-fit:cover;border-radius:var(--site-image-radius)!important"></figure>';
    }
    return '<figure class="ccms-card" style="padding:10px;margin:0"><img class="ccms-media-sm" src="' . ccms_h(ccms_capsule_media_url($url, 'gallery-' . $blockId . '-' . $index, 900, 720)) . '" alt="' . ccms_h($alt) . '"></figure>';
}

function ccms_render_gallery_block_variant(array $block, array $props, array $style, string $sectionId, string $blockId): string
{
    $layout = ccms_capsule_block_layout($block, 'grid');
    $images = array_values(is_array($props['images'] ?? null) ? $props['images'] : []);
    $header = '<div style="text-align:center;max-width:780px;margin:0 auto 26px"><span class="ccms-chip">' . ccms_h((string) ($props['badge'] ?? 'Gallery')) . '</span><h2 class="ccms-section-title" style="margin-top:18px">' . ccms_h((string) ($props['title'] ?? '')) . '</h2>' . (!empty($props['subtitle']) ? '<p class="ccms-subtitle">' . ccms_h((string) $props['subtitle']) . '</p>' : '') . '</div>';
    if ($layout === 'spotlight' && $images !== []) {
        $featured = array_shift($images);
        $supporting = '';
        foreach ($images as $index => $image) {
            $supporting .= ccms_render_gallery_figure($image, $blockId, $index + 2);
        }
        if ($supporting === '') {
            $supporting = ccms_render_gallery_figure($featured, $blockId, 1);
        }
        return '<section id="' . ccms_h($sectionId) . '"' . ccms_capsule_layout_attr($layout) . ccms_capsule_section_style_attr($block, 'padding:76px 0;background:rgba(255,255,255,.48)') . '><div class="ccms-c-inner"' . ccms_capsule_inner_style_attr($block) . '>' . $header . '<div class="ccms-gallery-spotlight"><div>' . ccms_render_gallery_figure($featured, $blockId, 1, 'featured') . '</div><div style="display:grid;gap:18px">' . $supporting . '</div></div></div></section>';
    }
    $figures = '';
    foreach ($images as $index => $image) {
        $figures .= ccms_render_gallery_figure($image, $blockId, $index + 1, $layout === 'masonry' ? 'masonry' : 'grid');
    }
    $gridOpen = $layout === 'masonry' ? '<div class="ccms-gallery-masonry">' : '<div class="ccms-grid-3">';
    return '<section id="' . ccms_h($sectionId) . '"' . ccms_capsule_layout_attr($layout) . ccms_capsule_section_style_attr($block, 'padding:76px 0;background:rgba(255,255,255,.48)') . '><div class="ccms-c-inner"' . ccms_capsule_inner_style_attr($block) . '>' . $header . $gridOpen . $figures . '</div></div></section>';
}

function ccms_render_blog_post_card(array $post, array $style, string $blockId, int $index, string $variant = 'grid'): string
{
    $image = ccms_capsule_media_url((string) ($post['image'] ?? ''), 'blog-' . $blockId . '-' . md5((string) ($post['title'] ?? '')) . '-' . $index, 960, 720);
    $meta = ccms_h((string) ($post['author'] ?? '')) . ' · ' . ccms_h((string) ($post['date'] ?? ''));
    $link = ccms_h((string) ($post['href'] ?? '#'));
    if ($variant === 'featured') {
        return '<article class="ccms-card" style="overflow:hidden"><img class="ccms-media" src="' . ccms_h($image) . '" alt=""><div style="padding:26px"><span class="ccms-kicker">' . ccms_h((string) ($post['category'] ?? 'Featured')) . '</span><h2 class="ccms-section-title" style="font-size:36px">' . ccms_h((string) ($post['title'] ?? 'Featured article')) . '</h2><p class="ccms-text" style="margin:0 0 14px">' . ccms_h((string) ($post['excerpt'] ?? '')) . '</p><p class="ccms-note" style="margin:0 0 16px">' . $meta . '</p><a href="' . $link . '" class="ccms-btn">Read article</a></div></article>';
    }
    if ($variant === 'compact') {
        return '<article class="ccms-card" style="padding:22px"><span class="ccms-kicker">' . ccms_h((string) ($post['category'] ?? 'Article')) . '</span><h3 style="margin:0 0 10px;font-size:22px;font-family:' . ccms_h($style['font_heading']) . '">' . ccms_h((string) ($post['title'] ?? '')) . '</h3><p class="ccms-note" style="margin:0 0 12px">' . ccms_h((string) ($post['excerpt'] ?? '')) . '</p><p class="ccms-note" style="margin:0 0 12px">' . $meta . '</p><a href="' . $link . '" style="font-weight:800;color:' . ccms_h($style['accent_dark']) . ';text-decoration:none">Read more</a></article>';
    }
    if ($variant === 'list') {
        return '<article class="ccms-card ccms-blog-list-card" style="padding:14px;overflow:hidden"><img class="ccms-media-sm" src="' . ccms_h($image) . '" alt="" style="height:100%"><div style="padding:6px 6px 6px 0"><span class="ccms-kicker">' . ccms_h((string) ($post['category'] ?? 'Article')) . '</span><h3 style="margin:0 0 10px;font-size:24px;font-family:' . ccms_h($style['font_heading']) . '">' . ccms_h((string) ($post['title'] ?? '')) . '</h3><p class="ccms-text" style="margin:0 0 12px">' . ccms_h((string) ($post['excerpt'] ?? '')) . '</p><p class="ccms-note" style="margin:0 0 14px">' . $meta . '</p><a href="' . $link . '" style="font-weight:800;color:' . ccms_h($style['accent_dark']) . ';text-decoration:none">Read more</a></div></article>';
    }
    return '<article class="ccms-card" style="overflow:hidden"><img class="ccms-media-sm" src="' . ccms_h($image) . '" alt=""><div style="padding:22px"><span class="ccms-kicker">' . ccms_h((string) ($post['category'] ?? 'Article')) . '</span><h3 style="margin:0 0 10px;font-size:24px;font-family:' . ccms_h($style['font_heading']) . '">' . ccms_h((string) ($post['title'] ?? '')) . '</h3><p class="ccms-text" style="margin:0 0 12px">' . ccms_h((string) ($post['excerpt'] ?? '')) . '</p><p class="ccms-note" style="margin:0 0 14px">' . $meta . '</p><a href="' . $link . '" style="font-weight:800;color:' . ccms_h($style['accent_dark']) . ';text-decoration:none">Read more</a></div></article>';
}

function ccms_render_blog_grid_block_variant(array $block, array $props, array $style, string $sectionId, string $blockId): string
{
    $layout = ccms_capsule_block_layout($block, 'grid');
    $posts = array_values(is_array($props['posts'] ?? null) ? $props['posts'] : []);
    $header = '<div style="text-align:center;max-width:820px;margin:0 auto 26px"><span class="ccms-chip">' . ccms_h((string) ($props['badge'] ?? 'Insights')) . '</span><h2 class="ccms-section-title" style="margin-top:18px">' . ccms_h((string) ($props['title'] ?? '')) . '</h2><p class="ccms-subtitle">' . ccms_h((string) ($props['subtitle'] ?? '')) . '</p></div>';
    if ($layout === 'featured-left' && $posts !== []) {
        $featured = array_shift($posts);
        $sideHtml = '';
        foreach ($posts as $index => $post) {
            $sideHtml .= ccms_render_blog_post_card($post, $style, $blockId, $index + 2, 'compact');
        }
        if ($sideHtml === '') {
            $sideHtml = ccms_render_blog_post_card($featured, $style, $blockId, 1, 'compact');
        }
        return '<section id="' . ccms_h($sectionId) . '"' . ccms_capsule_layout_attr($layout) . ccms_capsule_section_style_attr($block, 'padding:76px 0;background:rgba(255,255,255,.52)') . '><div class="ccms-c-inner"' . ccms_capsule_inner_style_attr($block) . '>' . $header . '<div class="ccms-grid-2" style="align-items:stretch"><div>' . ccms_render_blog_post_card($featured, $style, $blockId, 1, 'featured') . '</div><div style="display:grid;gap:18px">' . $sideHtml . '</div></div></div></section>';
    }
    if ($layout === 'list') {
        $list = '';
        foreach ($posts as $index => $post) {
            $list .= ccms_render_blog_post_card($post, $style, $blockId, $index + 1, 'list');
        }
        return '<section id="' . ccms_h($sectionId) . '"' . ccms_capsule_layout_attr($layout) . ccms_capsule_section_style_attr($block, 'padding:76px 0;background:rgba(255,255,255,.52)') . '><div class="ccms-c-inner"' . ccms_capsule_inner_style_attr($block) . '>' . $header . '<div style="display:grid;gap:18px;max-width:1040px;margin:0 auto">' . $list . '</div></div></section>';
    }
    $cards = '';
    foreach ($posts as $index => $post) {
        $cards .= ccms_render_blog_post_card($post, $style, $blockId, $index + 1);
    }
    return '<section id="' . ccms_h($sectionId) . '"' . ccms_capsule_layout_attr($layout) . ccms_capsule_section_style_attr($block, 'padding:76px 0;background:rgba(255,255,255,.52)') . '><div class="ccms-c-inner"' . ccms_capsule_inner_style_attr($block) . '>' . $header . '<div class="ccms-grid-3">' . $cards . '</div></div></section>';
}

function ccms_resolve_blog_featured_posts(array $props): array
{
    $featuredPosts = array_values(is_array($props['featured_posts'] ?? null) ? $props['featured_posts'] : []);
    $sidePosts = array_values(is_array($props['side_posts'] ?? null) ? $props['side_posts'] : []);
    if ($featuredPosts === [] && is_array($props['posts'] ?? null)) {
        $featuredPosts = [($props['posts'][0] ?? [])];
        $sidePosts = array_slice($props['posts'], 1, 3);
    }
    if ($featuredPosts === [] && $sidePosts !== []) {
        $featuredPosts = [array_shift($sidePosts)];
    }
    return [$featuredPosts[0] ?? [], $sidePosts];
}

function ccms_render_blog_featured_block_variant(array $block, array $props, array $style, string $sectionId, string $blockId): string
{
    $layout = ccms_capsule_block_layout($block, 'split');
    [$featured, $sidePosts] = ccms_resolve_blog_featured_posts($props);
    $header = '';
    if (!empty($props['badge']) || !empty($props['title']) || !empty($props['subtitle'])) {
        $header = '<div style="text-align:center;max-width:820px;margin:0 auto 26px"><span class="ccms-chip">' . ccms_h((string) ($props['badge'] ?? 'Featured')) . '</span><h2 class="ccms-section-title" style="margin-top:18px">' . ccms_h((string) ($props['title'] ?? 'Featured article')) . '</h2>' . (!empty($props['subtitle']) ? '<p class="ccms-subtitle">' . ccms_h((string) $props['subtitle']) . '</p>' : '') . '</div>';
    }
    $featuredCard = ccms_render_blog_post_card($featured, $style, $blockId, 1, 'featured');
    $sideCards = '';
    foreach ($sidePosts as $index => $post) {
        $sideCards .= ccms_render_blog_post_card($post, $style, $blockId, $index + 2, 'compact');
    }
    if ($sideCards === '') {
        $sideCards = ccms_render_blog_post_card($featured, $style, $blockId, 1, 'compact');
    }
    if ($layout === 'stacked') {
        return '<section id="' . ccms_h($sectionId) . '"' . ccms_capsule_layout_attr($layout) . ccms_capsule_section_style_attr($block, 'padding:76px 0;background:rgba(255,255,255,.52)') . '><div class="ccms-c-inner"' . ccms_capsule_inner_style_attr($block) . '>' . $header . '<div style="display:grid;gap:18px;max-width:1120px;margin:0 auto"><div>' . $featuredCard . '</div><div class="ccms-grid-2" style="align-items:stretch">' . $sideCards . '</div></div></div></section>';
    }
    $columns = $layout === 'reversed'
        ? '<div style="display:grid;gap:18px">' . $sideCards . '</div><div>' . $featuredCard . '</div>'
        : '<div>' . $featuredCard . '</div><div style="display:grid;gap:18px">' . $sideCards . '</div>';
    return '<section id="' . ccms_h($sectionId) . '"' . ccms_capsule_layout_attr($layout) . ccms_capsule_section_style_attr($block, 'padding:76px 0;background:rgba(255,255,255,.52)') . '><div class="ccms-c-inner"' . ccms_capsule_inner_style_attr($block) . '>' . $header . '<div class="ccms-grid-2" style="align-items:stretch">' . $columns . '</div></div></section>';
}

function ccms_render_blog_carousel_block_variant(array $block, array $props, array $style, string $sectionId, string $blockId): string
{
    $layout = ccms_capsule_block_layout($block, 'grid');
    $posts = array_values(is_array($props['posts'] ?? null) ? $props['posts'] : []);
    $header = '<div style="text-align:center;max-width:820px;margin:0 auto 26px"><span class="ccms-chip">' . ccms_h((string) ($props['badge'] ?? 'Stories')) . '</span><h2 class="ccms-section-title" style="margin-top:18px">' . ccms_h((string) ($props['title'] ?? '')) . '</h2><p class="ccms-subtitle">' . ccms_h((string) ($props['subtitle'] ?? '')) . '</p></div>';
    if ($layout === 'spotlight' && $posts !== []) {
        $featured = array_shift($posts);
        $supporting = '';
        foreach ($posts as $index => $post) {
            $supporting .= ccms_render_blog_post_card($post, $style, $blockId, $index + 2, 'compact');
        }
        if ($supporting === '') {
            $supporting = ccms_render_blog_post_card($featured, $style, $blockId, 1, 'compact');
        }
        return '<section id="' . ccms_h($sectionId) . '"' . ccms_capsule_layout_attr($layout) . ccms_capsule_section_style_attr($block, 'padding:76px 0;background:rgba(255,255,255,.52)') . '><div class="ccms-c-inner"' . ccms_capsule_inner_style_attr($block) . '>' . $header . '<div class="ccms-grid-2" style="align-items:stretch"><div>' . ccms_render_blog_post_card($featured, $style, $blockId, 1, 'featured') . '</div><div style="display:grid;gap:18px">' . $supporting . '</div></div></div></section>';
    }
    $cards = '';
    foreach ($posts as $index => $post) {
        $cards .= ccms_render_blog_post_card($post, $style, $blockId, $index + 1, $layout === 'compact' ? 'compact' : 'grid');
    }
    return '<section id="' . ccms_h($sectionId) . '"' . ccms_capsule_layout_attr($layout) . ccms_capsule_section_style_attr($block, 'padding:76px 0;background:rgba(255,255,255,.52)') . '><div class="ccms-c-inner"' . ccms_capsule_inner_style_attr($block) . '>' . $header . '<div class="ccms-grid-3">' . $cards . '</div></div></section>';
}

function ccms_render_capsule_block(array $block, array $style): string
{
    $type = (string) ($block['type'] ?? '');
    $props = is_array($block['props'] ?? null) ? $block['props'] : [];
    $blockId = (string) ($block['id'] ?? $type);
    $sectionId = $type !== '' ? $type : $blockId;

    switch ($type) {
        case 'nav':
            $links = '';
            foreach (($props['links'] ?? []) as $link) {
                $links .= '<a href="' . ccms_h((string) ($link['href'] ?? '#')) . '" style="text-decoration:none;color:' . ccms_h($style['text_secondary']) . ';font-weight:700">' . ccms_h(ccms_capsule_link_text($link)) . '</a>';
            }
            $ctaHref = (string) ($props['cta_href'] ?? '#');
            $ctaText = (string) ($props['cta_text'] ?? 'Contactar');
            return '<section id="' . ccms_h($sectionId) . '" style="position:sticky;top:0;z-index:40;background:' . ccms_h($style['nav_bg']) . ';backdrop-filter:blur(10px);border-bottom:1px solid rgba(0,0,0,.05)"><div class="ccms-c-inner" style="display:flex;align-items:center;justify-content:space-between;gap:18px;min-height:78px"><div style="font-weight:900;font-size:22px;font-family:' . ccms_h($style['font_heading']) . '">' . ccms_h((string) ($props['brand'] ?? 'Brand')) . '</div><nav style="display:flex;flex-wrap:wrap;gap:16px;align-items:center">' . $links . '<a class="' . ccms_h(ccms_capsule_button_classes($block)) . '" href="' . ccms_h($ctaHref) . '">' . ccms_h($ctaText) . '</a></nav></div></section>';

        case 'sticky_header':
            $links = '';
            foreach (($props['links'] ?? []) as $link) {
                $links .= '<a href="' . ccms_h((string) ($link['href'] ?? '#')) . '" style="text-decoration:none;color:' . ccms_h($style['text_secondary']) . ';font-weight:700">' . ccms_h(ccms_capsule_link_text($link)) . '</a>';
            }
            return '<section id="' . ccms_h($sectionId) . '" style="position:sticky;top:0;z-index:45"><div style="background:' . ccms_h($style['text_primary']) . ';color:#fff;padding:10px 0;font-size:14px;text-align:center">' . ccms_h((string) ($props['announcement'] ?? '')) . '</div><div style="background:' . ccms_h($style['nav_bg']) . ';backdrop-filter:blur(10px);border-bottom:1px solid rgba(0,0,0,.05)"><div class="ccms-c-inner" style="display:flex;align-items:center;justify-content:space-between;gap:18px;min-height:78px"><div style="font-weight:900;font-size:22px;font-family:' . ccms_h($style['font_heading']) . '">' . ccms_h((string) ($props['brand'] ?? 'Brand')) . '</div><nav style="display:flex;flex-wrap:wrap;gap:16px;align-items:center">' . $links . '<a class="' . ccms_h(ccms_capsule_button_classes($block)) . '" href="' . ccms_h((string) ($props['cta_href'] ?? '#')) . '">' . ccms_h((string) ($props['cta_text'] ?? 'Contactar')) . '</a></nav></div></div></section>';

        case 'offcanvas_menu':
            $uid = preg_replace('/[^a-z0-9_-]+/i', '-', $blockId) ?: 'offcanvas-menu';
            $links = '';
            foreach (($props['links'] ?? []) as $link) {
                $links .= '<a href="' . ccms_h((string) ($link['href'] ?? '#')) . '" style="display:block;font-size:clamp(28px,4vw,44px);line-height:1.1;font-weight:900;text-decoration:none;color:' . ccms_h($style['text_primary']) . '">' . ccms_h(ccms_capsule_link_text($link)) . '</a>';
            }
            return '<section id="' . ccms_h($sectionId) . '" style="position:sticky;top:0;z-index:48;background:' . ccms_h($style['nav_bg']) . ';backdrop-filter:blur(14px);border-bottom:1px solid rgba(0,0,0,.05)"><div class="ccms-c-inner" style="display:flex;align-items:center;justify-content:space-between;gap:18px;min-height:78px"><div><div style="font-size:12px;letter-spacing:.16em;text-transform:uppercase;font-weight:800;color:' . ccms_h($style['accent_dark']) . ';margin-bottom:6px">' . ccms_h((string) ($props['title'] ?? 'Open the full site menu')) . '</div><div style="font-weight:900;font-size:22px;font-family:' . ccms_h($style['font_heading']) . '">' . ccms_h((string) ($props['brand'] ?? 'Brand')) . '</div></div><button type="button" data-open-offcanvas="' . ccms_h($uid) . '" style="display:inline-flex;align-items:center;justify-content:center;padding:14px 20px;border-radius:999px;border:1px solid rgba(0,0,0,.08);background:#fff;color:' . ccms_h($style['accent_dark']) . ';font-weight:800;cursor:pointer">' . ccms_h((string) ($props['button_text'] ?? 'Menu')) . '</button></div><div id="offcanvas-' . ccms_h($uid) . '" style="position:fixed;inset:0;z-index:70;background:rgba(0,0,0,.54);display:none"><div data-offcanvas-panel="' . ccms_h($uid) . '" style="position:absolute;right:0;top:0;bottom:0;width:min(520px,100%);background:' . ccms_h($style['bg_to']) . ';padding:34px 28px;transform:translateX(100%);transition:transform .35s cubic-bezier(.4,0,.2,1);overflow:auto"><div style="display:flex;align-items:start;justify-content:space-between;gap:16px;margin-bottom:26px"><div><div style="font-size:12px;letter-spacing:.16em;text-transform:uppercase;font-weight:800;color:' . ccms_h($style['accent_dark']) . ';margin-bottom:8px">' . ccms_h((string) ($props['brand'] ?? 'Brand')) . '</div><h2 style="margin:0;font-size:32px;line-height:1.05;font-family:' . ccms_h($style['font_heading']) . '">' . ccms_h((string) ($props['title'] ?? 'Open the full site menu')) . '</h2></div><button type="button" data-close-offcanvas="' . ccms_h($uid) . '" aria-label="Close menu" style="width:46px;height:46px;border-radius:999px;border:1px solid rgba(0,0,0,.08);background:#fff;color:' . ccms_h($style['accent_dark']) . ';font-size:28px;cursor:pointer">&times;</button></div><p class="ccms-note" style="margin:0 0 24px">' . ccms_h((string) ($props['helper_text'] ?? '')) . '</p><div style="display:grid;gap:18px">' . $links . '</div><div style="margin-top:30px"><a class="ccms-btn" href="' . ccms_h((string) ($props['cta_href'] ?? '#contact')) . '" style="width:100%">' . ccms_h((string) ($props['cta_text'] ?? 'Talk to us')) . '</a></div></div></div><script' . $scriptNonceAttr . '>(function(){var uid=' . json_encode($uid) . ';var openBtn=document.querySelector(\'[data-open-offcanvas=\"\'+uid+\'\"]\');var overlay=document.getElementById(\'offcanvas-\'+uid);if(!openBtn||!overlay)return;var panel=overlay.querySelector(\'[data-offcanvas-panel=\"\'+uid+\'\"]\');var closeBtn=overlay.querySelector(\'[data-close-offcanvas=\"\'+uid+\'\"]\');function openMenu(){overlay.style.display=\'block\';requestAnimationFrame(function(){panel.style.transform=\'translateX(0)\';});}function closeMenu(){panel.style.transform=\'translateX(100%)\';setTimeout(function(){overlay.style.display=\'none\';},250);}openBtn.addEventListener(\'click\',openMenu);if(closeBtn)closeBtn.addEventListener(\'click\',closeMenu);overlay.addEventListener(\'click\',function(e){if(e.target===overlay)closeMenu();});document.addEventListener(\'keydown\',function(e){if(e.key===\'Escape\'&&overlay.style.display===\'block\'){closeMenu();}});}());</script></section>';

        case 'banner':
            return '<section id="' . ccms_h($sectionId) . '"' . ccms_capsule_section_style_attr($block, 'padding:14px 0;background:' . $style['gradient_accent']) . '><div class="ccms-c-inner" style="display:flex;align-items:center;justify-content:space-between;gap:18px;flex-wrap:wrap;color:#fff"><p style="margin:0;font-weight:800">' . ccms_h((string) ($props['text'] ?? '')) . '</p><a class="ccms-btn ccms-btn--ghost" href="' . ccms_h((string) ($props['cta_href'] ?? '#')) . '">' . ccms_h((string) ($props['cta_text'] ?? 'Ver más')) . '</a></div></section>';

        case 'hero':
        case 'hero_fullscreen':
            if ($type === 'hero_fullscreen' && !empty($props['cta_secondary']) && stripos((string) ($props['cta_secondary'] ?? ''), 'instagram') !== false && empty($props['cta_secondary_href'])) {
                $props['cta_secondary_href'] = 'https://www.instagram.com/tradingycafeconarantxa/';
            }
            return ccms_render_hero_block_variant($type, $block, $props, $style, $sectionId, $blockId);

        case 'hero_video':
            $videoUrl = trim((string) ($props['video_url'] ?? ''));
            $embed = $videoUrl !== ''
                ? '<div class="ccms-card" style="padding:14px;overflow:hidden"><iframe src="' . ccms_h($videoUrl) . '" title="' . ccms_h((string) ($props['title'] ?? 'Video hero')) . '" style="width:100%;height:420px;border:0;border-radius:20px" allowfullscreen loading="lazy"></iframe></div>'
                : '<div class="ccms-card" style="padding:14px"><img class="ccms-media" src="' . ccms_h(ccms_capsule_media_url('', 'hero-video-' . $blockId, 1280, 960)) . '" alt=""></div>';
            return '<section id="' . ccms_h($sectionId) . '"' . ccms_capsule_section_style_attr($block, 'padding:92px 0;background:linear-gradient(135deg,' . $style['bg_from'] . ' 0%,' . $style['bg_to'] . ' 100%)') . '><div class="ccms-c-inner ccms-grid-2"' . ccms_capsule_inner_style_attr($block) . '><div><span class="ccms-chip">' . ccms_h((string) ($props['badge'] ?? 'Video')) . '</span><h1 class="ccms-title" style="margin-top:18px">' . ccms_h((string) ($props['title'] ?? 'Hero title')) . '</h1><p class="ccms-subtitle">' . ccms_h((string) ($props['subtitle'] ?? '')) . '</p><div style="display:flex;flex-wrap:wrap;gap:14px;margin-top:28px"><a class="ccms-btn" href="' . ccms_h((string) ($props['cta_href'] ?? '#')) . '">' . ccms_h((string) ($props['cta_primary'] ?? 'Watch now')) . '</a>' . (!empty($props['cta_secondary']) ? '<a class="ccms-btn ccms-btn--ghost" href="' . ccms_h((string) ($props['cta_secondary_href'] ?? '#')) . '">' . ccms_h((string) $props['cta_secondary']) . '</a>' : '') . '</div></div>' . $embed . '</div></section>';

        case 'hero_slider':
            $slides = is_array($props['slides'] ?? null) ? $props['slides'] : [];
            $mainSlide = $slides[0] ?? [];
            $thumbs = '';
            foreach (array_slice($slides, 0, 3) as $slideIndex => $slide) {
                $thumbs .= '<article class="ccms-card" style="padding:18px"><p class="ccms-kicker">Slide ' . ($slideIndex + 1) . '</p><h3 style="margin:0 0 8px;font-size:22px;font-family:' . ccms_h($style['font_heading']) . '">' . ccms_h((string) ($slide['title'] ?? 'Slide')) . '</h3><p class="ccms-note" style="margin:0">' . ccms_h((string) ($slide['subtitle'] ?? '')) . '</p></article>';
            }
            return '<section id="' . ccms_h($sectionId) . '"' . ccms_capsule_section_style_attr($block, 'padding:92px 0;background:linear-gradient(135deg,' . $style['bg_from'] . ' 0%,' . $style['bg_to'] . ' 100%)') . '><div class="ccms-c-inner"' . ccms_capsule_inner_style_attr($block) . '><div class="ccms-grid-2" style="align-items:stretch"><div style="display:grid;gap:16px"><span class="ccms-chip">' . ccms_h((string) ($props['badge'] ?? 'Featured highlights')) . '</span><h1 class="ccms-title">' . ccms_h((string) ($mainSlide['title'] ?? $props['title'] ?? 'Hero title')) . '</h1><p class="ccms-subtitle">' . ccms_h((string) ($mainSlide['subtitle'] ?? $props['subtitle'] ?? '')) . '</p><div style="display:flex;flex-wrap:wrap;gap:14px"><a class="ccms-btn" href="' . ccms_h((string) ($props['cta_href'] ?? '#')) . '">' . ccms_h((string) ($props['cta_primary'] ?? 'Get started')) . '</a>' . (!empty($props['cta_secondary']) ? '<a class="ccms-btn ccms-btn--ghost" href="' . ccms_h((string) ($props['cta_secondary_href'] ?? '#')) . '">' . ccms_h((string) $props['cta_secondary']) . '</a>' : '') . '</div></div><div class="ccms-card" style="overflow:hidden"><img class="ccms-media" src="' . ccms_h(ccms_capsule_media_url((string) ($mainSlide['image'] ?? ''), 'hero-slider-' . $blockId, 1280, 960)) . '" alt=""></div></div><div class="ccms-grid-3" style="margin-top:18px">' . $thumbs . '</div></div></section>';

        case 'hero_particles':
            return '<section id="' . ccms_h($sectionId) . '" style="padding:100px 0;overflow:hidden;position:relative;background:radial-gradient(circle at 20% 20%,rgba(229,115,115,.18),transparent 0 26%),radial-gradient(circle at 80% 18%,rgba(168,202,186,.22),transparent 0 22%),radial-gradient(circle at 50% 80%,rgba(200,111,92,.12),transparent 0 26%),linear-gradient(180deg,' . ccms_h($style['bg_from']) . ' 0%,' . ccms_h($style['bg_to']) . ' 100%)"><div class="ccms-c-inner" style="position:relative"><div style="max-width:860px;margin:0 auto;text-align:center"><span class="ccms-chip">' . ccms_h((string) ($props['badge'] ?? 'Motion-led intro')) . '</span><h1 class="ccms-title" style="margin-top:18px">' . ccms_h((string) ($props['title'] ?? 'Hero title')) . '</h1><p class="ccms-subtitle">' . ccms_h((string) ($props['subtitle'] ?? '')) . '</p><div style="display:flex;justify-content:center;flex-wrap:wrap;gap:14px;margin-top:28px"><a class="ccms-btn" href="' . ccms_h((string) ($props['cta_href'] ?? '#')) . '">' . ccms_h((string) ($props['cta_primary'] ?? 'Explore')) . '</a>' . (!empty($props['cta_secondary']) ? '<a class="ccms-btn ccms-btn--ghost" href="' . ccms_h((string) ($props['cta_secondary_href'] ?? '#')) . '">' . ccms_h((string) $props['cta_secondary']) . '</a>' : '') . '</div></div></div></section>';

        case 'hero_split':
            return ccms_render_hero_block_variant($type, $block, $props, $style, $sectionId, $blockId);

        case 'features':
            return ccms_render_features_block_variant($block, $props, $style, $sectionId);

        case 'numbered_features':
            $itemsHtml = '';
            $featureIndex = 1;
            foreach (($props['items'] ?? []) as $item) {
                $itemsHtml .= '<article class="ccms-card" style="padding:28px"><div style="font-size:42px;font-weight:900;color:' . ccms_h($style['accent_dark']) . ';margin-bottom:10px">' . sprintf('%02d', $featureIndex) . '</div><h3 style="margin:0 0 10px;font-size:24px;font-family:' . ccms_h($style['font_heading']) . '">' . ccms_h((string) ($item['title'] ?? '')) . '</h3><p class="ccms-text" style="margin:0">' . ccms_h((string) ($item['desc'] ?? $item['text'] ?? '')) . '</p></article>';
                $featureIndex++;
            }
            return '<section id="' . ccms_h($sectionId) . '"' . ccms_capsule_section_style_attr($block, 'padding:76px 0') . '><div class="ccms-c-inner"' . ccms_capsule_inner_style_attr($block) . '><div style="text-align:center;max-width:860px;margin:0 auto 26px"><span class="ccms-chip">' . ccms_h((string) ($props['badge'] ?? 'Pillars')) . '</span><h2 class="ccms-section-title" style="margin-top:18px">' . ccms_h((string) ($props['title'] ?? '')) . '</h2><p class="ccms-subtitle">' . ccms_h((string) ($props['subtitle'] ?? '')) . '</p></div><div class="ccms-grid-3">' . $itemsHtml . '</div></div></section>';

        case 'process':
            $steps = '';
            $index = 1;
            foreach (($props['steps'] ?? $props['items'] ?? []) as $step) {
                $steps .= '<article class="ccms-card" style="padding:26px;position:relative"><div style="width:44px;height:44px;border-radius:14px;background:rgba(229,115,115,.14);display:flex;align-items:center;justify-content:center;font-weight:900;margin-bottom:16px">' . ccms_h((string) ($step['number'] ?? $index)) . '</div><h3 style="margin:0 0 10px;font-size:24px;font-family:' . ccms_h($style['font_heading']) . '">' . ccms_h((string) ($step['title'] ?? '')) . '</h3><p class="ccms-text" style="margin:0">' . ccms_h((string) ($step['desc'] ?? $step['text'] ?? '')) . '</p></article>';
                $index++;
            }
            return '<section id="' . ccms_h($sectionId) . '"' . ccms_capsule_section_style_attr($block, 'padding:76px 0') . '><div class="ccms-c-inner"' . ccms_capsule_inner_style_attr($block) . '><div style="text-align:center;max-width:860px;margin:0 auto 26px"><span class="ccms-chip">' . ccms_h((string) ($props['badge'] ?? 'Process')) . '</span><h2 class="ccms-section-title" style="margin-top:18px">' . ccms_h((string) ($props['title'] ?? '')) . '</h2></div><div class="ccms-grid-3">' . $steps . '</div></div></section>';

        case 'faq':
            $itemsHtml = '';
            foreach (($props['items'] ?? []) as $item) {
                $itemsHtml .= '<details class="ccms-card" style="padding:20px 24px"><summary style="cursor:pointer;font-weight:800;font-size:20px;list-style:none">' . ccms_h((string) ($item['q'] ?? $item['title'] ?? 'Question')) . '</summary><p class="ccms-text" style="margin:14px 0 0">' . ccms_h((string) ($item['a'] ?? $item['text'] ?? '')) . '</p></details>';
            }
            return '<section id="' . ccms_h($sectionId) . '"' . ccms_capsule_section_style_attr($block, 'padding:76px 0;background:rgba(255,255,255,.58)') . '><div class="ccms-c-inner"' . ccms_capsule_inner_style_attr($block) . '><div style="text-align:center;max-width:760px;margin:0 auto 26px"><span class="ccms-chip">' . ccms_h((string) ($props['badge'] ?? 'FAQ')) . '</span><h2 class="ccms-section-title" style="margin-top:18px">' . ccms_h((string) ($props['title'] ?? '')) . '</h2></div><div style="display:grid;gap:16px">' . $itemsHtml . '</div></div></section>';

        case 'accordion_rich':
            $itemsHtml = '';
            foreach (($props['items'] ?? []) as $item) {
                $icon = trim((string) ($item['icon'] ?? ''));
                $media = trim((string) ($item['image'] ?? ''));
                $itemsHtml .= '<details class="ccms-card" style="padding:22px 24px"><summary style="cursor:pointer;display:flex;align-items:center;gap:12px;font-weight:800;font-size:20px;list-style:none"><span style="width:38px;height:38px;border-radius:14px;background:rgba(229,115,115,.14);display:inline-flex;align-items:center;justify-content:center;font-size:18px">' . ccms_h($icon !== '' ? $icon : '•') . '</span><span>' . ccms_h((string) ($item['q'] ?? $item['title'] ?? 'Question')) . '</span></summary>' . ($media !== '' ? '<img class="ccms-media-sm" src="' . ccms_h(ccms_capsule_media_url($media, 'accordion-' . $blockId, 960, 720)) . '" alt="" style="margin-top:16px">' : '') . '<p class="ccms-text" style="margin:16px 0 0">' . ccms_h((string) ($item['a'] ?? $item['text'] ?? '')) . '</p></details>';
            }
            return '<section id="' . ccms_h($sectionId) . '"' . ccms_capsule_section_style_attr($block, 'padding:76px 0;background:rgba(255,255,255,.58)') . '><div class="ccms-c-inner"' . ccms_capsule_inner_style_attr($block) . '><div style="text-align:center;max-width:820px;margin:0 auto 26px"><span class="ccms-chip">' . ccms_h((string) ($props['badge'] ?? 'Key questions')) . '</span><h2 class="ccms-section-title" style="margin-top:18px">' . ccms_h((string) ($props['title'] ?? '')) . '</h2><p class="ccms-subtitle">' . ccms_h((string) ($props['subtitle'] ?? '')) . '</p></div><div style="display:grid;gap:16px">' . $itemsHtml . '</div></div></section>';

        case 'pricing':
            return ccms_render_pricing_block_variant($block, $props, $style, $sectionId);

        case 'pricing_toggle':
            return ccms_render_pricing_block_variant($block, $props, $style, $sectionId, true);

        case 'pricing_comparison':
            $planNames = [];
            foreach (($props['plans'] ?? []) as $plan) {
                $planNames[] = (string) ($plan['name'] ?? 'Plan');
            }
            $header = '<tr><th>Feature</th>';
            foreach ($planNames as $planName) {
                $header .= '<th>' . ccms_h($planName) . '</th>';
            }
            $header .= '</tr>';
            $rows = '';
            foreach (($props['rows'] ?? []) as $row) {
                $values = [];
                if (is_array($row['values'] ?? null)) {
                    $values = $row['values'];
                } elseif (is_array($row['plans'] ?? null)) {
                    $values = $row['plans'];
                } else {
                    foreach ($planNames as $planName) {
                        $planKey = strtolower(preg_replace('/[^a-z0-9]+/i', '_', $planName) ?? '');
                        if (array_key_exists($planKey, $row)) {
                            $values[] = $row[$planKey];
                        }
                    }
                }
                $rowHtml = '<tr><td style="font-weight:700">' . ccms_h((string) ($row['feature'] ?? $row['label'] ?? 'Feature')) . '</td>';
                foreach ($values as $value) {
                    if (is_bool($value)) {
                        $valueHtml = ccms_capsule_bool_icon($value, $style);
                    } else {
                        $valueHtml = ccms_h((string) $value);
                    }
                    $rowHtml .= '<td>' . $valueHtml . '</td>';
                }
                $rowHtml .= '</tr>';
                $rows .= $rowHtml;
            }
            return '<section id="' . ccms_h($sectionId) . '"' . ccms_capsule_section_style_attr($block, 'padding:76px 0') . '><div class="ccms-c-inner"' . ccms_capsule_inner_style_attr($block) . '><div style="text-align:center;max-width:820px;margin:0 auto 26px"><span class="ccms-chip">' . ccms_h((string) ($props['badge'] ?? 'Compare')) . '</span><h2 class="ccms-section-title" style="margin-top:18px">' . ccms_h((string) ($props['title'] ?? '')) . '</h2><p class="ccms-subtitle">' . ccms_h((string) ($props['subtitle'] ?? '')) . '</p></div><div class="ccms-card" style="padding:20px;overflow:auto"><table class="ccms-table"><thead>' . $header . '</thead><tbody>' . $rows . '</tbody></table></div></div></section>';

        case 'gallery':
            return ccms_render_gallery_block_variant($block, $props, $style, $sectionId, $blockId);

        case 'image_grid_masonry':
            $images = '';
            $index = 1;
            foreach (($props['images'] ?? []) as $image) {
                $url = is_array($image) ? (string) ($image['url'] ?? '') : (string) $image;
                $alt = is_array($image) ? (string) ($image['alt'] ?? 'Gallery image') : 'Gallery image';
                $height = $index % 3 === 0 ? 340 : ($index % 2 === 0 ? 260 : 300);
                $images .= '<figure class="ccms-card" style="padding:10px;margin:0"><img src="' . ccms_h(ccms_capsule_media_url($url, 'masonry-' . $blockId . '-' . $index, 900, 900)) . '" alt="' . ccms_h($alt) . '" style="width:100%;height:' . $height . 'px;object-fit:cover;border-radius:18px"></figure>';
                $index++;
            }
            return '<section id="' . ccms_h($sectionId) . '"' . ccms_capsule_section_style_attr($block, 'padding:76px 0;background:rgba(255,255,255,.48)') . '><div class="ccms-c-inner"' . ccms_capsule_inner_style_attr($block) . '><div style="text-align:center;max-width:780px;margin:0 auto 26px"><span class="ccms-chip">' . ccms_h((string) ($props['badge'] ?? 'Gallery')) . '</span><h2 class="ccms-section-title" style="margin-top:18px">' . ccms_h((string) ($props['title'] ?? '')) . '</h2><p class="ccms-subtitle">' . ccms_h((string) ($props['subtitle'] ?? '')) . '</p></div><div class="ccms-grid-3">' . $images . '</div></div></section>';

        case 'logo_cloud':
            $logos = '';
            foreach (($props['logos'] ?? []) as $logo) {
                $name = is_array($logo) ? (string) ($logo['name'] ?? 'Logo') : (string) $logo;
                $logos .= '<div class="ccms-card" style="padding:18px;text-align:center;font-weight:800;color:' . ccms_h($style['text_secondary']) . '">' . ccms_h($name) . '</div>';
            }
            return '<section id="' . ccms_h($sectionId) . '"' . ccms_capsule_section_style_attr($block, 'padding:38px 0') . '><div class="ccms-c-inner"' . ccms_capsule_inner_style_attr($block) . '><div style="text-align:center;margin-bottom:16px"><h2 style="margin:0;font-size:20px;color:' . ccms_h($style['text_secondary']) . '">' . ccms_h((string) ($props['title'] ?? 'Trusted by')) . '</h2></div><div class="ccms-grid-3">' . $logos . '</div></div></section>';

        case 'team':
            $members = '';
            foreach (($props['members'] ?? []) as $member) {
                $name = (string) ($member['name'] ?? 'Team member');
                $role = (string) ($member['role'] ?? '');
                $bio = (string) ($member['bio'] ?? '');
                $seed = preg_replace('/[^a-z0-9]+/i', '', strtolower($name)) ?: 'member';
                $members .= '<article class="ccms-card" style="overflow:hidden"><div style="position:relative;height:220px;background:linear-gradient(180deg,rgba(0,0,0,.04),rgba(0,0,0,.18))"><img src="https://i.pravatar.cc/720?u=' . ccms_h($seed) . '" alt="' . ccms_h($name) . '" style="width:100%;height:100%;object-fit:cover"></div><div style="padding:24px"><h3 style="margin:0 0 6px;font-size:24px;font-family:' . ccms_h($style['font_heading']) . '">' . ccms_h($name) . '</h3><p style="margin:0 0 12px;color:' . ccms_h($style['accent_dark']) . ';font-weight:800;letter-spacing:.08em;text-transform:uppercase;font-size:13px">' . ccms_h($role) . '</p><p class="ccms-text" style="margin:0">' . ccms_h($bio) . '</p></div></article>';
            }
            return '<section id="' . ccms_h($sectionId) . '"' . ccms_capsule_section_style_attr($block, 'padding:76px 0') . '><div class="ccms-c-inner"' . ccms_capsule_inner_style_attr($block) . '><div style="text-align:center;max-width:820px;margin:0 auto 26px"><span class="ccms-chip">' . ccms_h((string) ($props['badge'] ?? 'Our team')) . '</span><h2 class="ccms-section-title" style="margin-top:18px">' . ccms_h((string) ($props['title'] ?? 'Meet the team')) . '</h2></div><div class="ccms-grid-3">' . $members . '</div></div></section>';

        case 'awards_bar':
        case 'press_mentions':
            $itemsHtml = '';
            foreach (($props['items'] ?? []) as $item) {
                $name = is_array($item) ? (string) ($item['name'] ?? $item['title'] ?? $item['label'] ?? 'Mention') : (string) $item;
                $itemsHtml .= '<div class="ccms-card" style="padding:18px;text-align:center;font-weight:800;color:' . ccms_h($style['text_secondary']) . '">' . ccms_h($name) . '</div>';
            }
            return '<section id="' . ccms_h($sectionId) . '"' . ccms_capsule_section_style_attr($block, 'padding:38px 0') . '><div class="ccms-c-inner"' . ccms_capsule_inner_style_attr($block) . '><div style="text-align:center;margin-bottom:16px"><h2 style="margin:0;font-size:20px;color:' . ccms_h($style['text_secondary']) . '">' . ccms_h((string) ($props['title'] ?? 'Recognition')) . '</h2></div><div class="ccms-grid-3">' . $itemsHtml . '</div></div></section>';

        case 'split_content':
            $bullets = '';
            foreach (($props['bullets'] ?? []) as $bullet) {
                $bullets .= '<li>' . ccms_h((string) $bullet) . '</li>';
            }
            $visual = '<div><img class="ccms-media" src="' . ccms_h(ccms_capsule_media_url('', (string) ($props['image_seed'] ?? ('split-' . $blockId)), 1280, 960)) . '" alt=""></div>';
            $copy = '<div><span class="ccms-chip">' . ccms_h((string) ($props['badge'] ?? 'About')) . '</span><h2 class="ccms-section-title" style="margin-top:18px">' . ccms_h((string) ($props['title'] ?? '')) . '</h2><p class="ccms-text">' . ccms_h((string) ($props['text'] ?? '')) . '</p>' . ($bullets !== '' ? '<ul class="ccms-list">' . $bullets . '</ul>' : '') . '</div>';
            $content = !empty($props['reversed']) ? $visual . $copy : $copy . $visual;
            return '<section id="' . ccms_h($sectionId) . '"' . ccms_capsule_section_style_attr($block, 'padding:84px 0') . '><div class="ccms-c-inner ccms-grid-2"' . ccms_capsule_inner_style_attr($block) . '>' . $content . '</div></section>';

        case 'newsletter':
            $page = ccms_current_public_page() ?? ['slug' => '', 'title' => ''];
            $feedback = ccms_render_public_form_feedback($blockId);
            $hidden = ccms_render_public_form_hidden_inputs($page, 'newsletter', $blockId);
            return '<section id="' . ccms_h($sectionId) . '"' . ccms_capsule_section_style_attr($block, 'padding:74px 0;background:rgba(255,255,255,.64)') . '><div class="ccms-c-inner"' . ccms_capsule_inner_style_attr($block) . '>' . $feedback . '<div class="ccms-card" style="padding:30px;display:flex;align-items:center;justify-content:space-between;gap:20px;flex-wrap:wrap"><div><h2 class="ccms-section-title" style="margin:0 0 8px">' . ccms_h((string) ($props['title'] ?? '')) . '</h2><p class="ccms-subtitle">' . ccms_h((string) ($props['subtitle'] ?? '')) . '</p></div><form method="post" action="' . ccms_h(ccms_public_form_action()) . '" style="display:flex;gap:12px;flex-wrap:wrap">' . $hidden . '<input name="email" type="email" placeholder="' . ccms_h((string) ($props['placeholder'] ?? 'Enter your email')) . '" style="min-width:280px;padding:14px 16px;border-radius:16px;border:1px solid rgba(0,0,0,.08)" required><button type="submit" class="ccms-btn">' . ccms_h((string) ($props['button_text'] ?? 'Subscribe')) . '</button></form></div></div></section>';

        case 'booking_widget':
            $services = '';
            foreach (($props['services'] ?? []) as $service) {
                $services .= '<span class="chip" style="background:rgba(168,202,186,.18);color:#3d6c5a;padding:10px 14px">' . ccms_h(is_array($service) ? (string) ($service['name'] ?? $service['title'] ?? 'Service') : (string) $service) . '</span>';
            }
            $times = '';
            foreach (($props['times'] ?? []) as $time) {
                $times .= '<span class="chip" style="padding:10px 14px">' . ccms_h((string) $time) . '</span>';
            }
            return '<section id="' . ccms_h($sectionId) . '"' . ccms_capsule_section_style_attr($block, 'padding:76px 0') . '><div class="ccms-c-inner ccms-grid-2"' . ccms_capsule_inner_style_attr($block) . '><div><span class="ccms-chip">' . ccms_h((string) ($props['badge'] ?? 'Booking')) . '</span><h2 class="ccms-section-title" style="margin-top:18px">' . ccms_h((string) ($props['title'] ?? '')) . '</h2><p class="ccms-subtitle">' . ccms_h((string) ($props['subtitle'] ?? '')) . '</p><p class="ccms-note" style="margin-top:16px">' . ccms_h((string) ($props['helper_text'] ?? '')) . '</p></div><div class="ccms-card" style="padding:28px;display:grid;gap:18px"><div><p class="ccms-kicker">Services</p><div style="display:flex;flex-wrap:wrap;gap:10px">' . $services . '</div></div><div><p class="ccms-kicker">Times</p><div style="display:flex;flex-wrap:wrap;gap:10px">' . $times . '</div></div><div class="ccms-grid-2"><input placeholder="Name" style="width:100%;padding:14px 16px;border-radius:16px;border:1px solid rgba(0,0,0,.08)"><input placeholder="Email" style="width:100%;padding:14px 16px;border-radius:16px;border:1px solid rgba(0,0,0,.08)"></div><button type="button" class="ccms-btn">' . ccms_h((string) ($props['button_text'] ?? 'Request booking')) . '</button></div></div></section>';

        case 'countdown_timer':
            $target = (string) ($props['target_date'] ?? '');
            return '<section id="' . ccms_h($sectionId) . '"' . ccms_capsule_section_style_attr($block, 'padding:70px 0;background:rgba(255,255,255,.48)') . '><div class="ccms-c-inner" style="text-align:center"' . ccms_capsule_inner_style_attr($block) . '><span class="ccms-chip">' . ccms_h((string) ($props['badge'] ?? 'Limited window')) . '</span><h2 class="ccms-section-title" style="margin-top:18px">' . ccms_h((string) ($props['title'] ?? '')) . '</h2><p class="ccms-subtitle">' . ccms_h((string) ($props['subtitle'] ?? '')) . '</p><div class="ccms-grid-3" style="grid-template-columns:repeat(4,minmax(0,1fr));max-width:760px;margin:26px auto 0"><div class="ccms-card ccms-stat"><strong>07</strong><span class="ccms-text" style="font-size:15px">Days</span></div><div class="ccms-card ccms-stat"><strong>14</strong><span class="ccms-text" style="font-size:15px">Hours</span></div><div class="ccms-card ccms-stat"><strong>22</strong><span class="ccms-text" style="font-size:15px">Minutes</span></div><div class="ccms-card ccms-stat"><strong>08</strong><span class="ccms-text" style="font-size:15px">Seconds</span></div></div><p class="ccms-note" style="margin-top:14px">' . ccms_h($target !== '' ? ('Target: ' . $target) : '') . '</p>' . (!empty($props['button_text']) ? '<div style="margin-top:18px"><a class="ccms-btn" href="' . ccms_h((string) ($props['button_href'] ?? '#contact')) . '">' . ccms_h((string) $props['button_text']) . '</a></div>' : '') . '</div></section>';

        case 'comparison':
            $rows = '';
            foreach (($props['rows'] ?? []) as $row) {
                $us = is_bool($row['us'] ?? null) ? ccms_capsule_bool_icon((bool) $row['us'], $style) : ccms_h((string) ($row['us'] ?? ''));
                $them = is_bool($row['them'] ?? null) ? ccms_capsule_bool_icon((bool) $row['them'], $style) : ccms_h((string) ($row['them'] ?? ''));
                $rows .= '<tr><td style="font-weight:700">' . ccms_h((string) ($row['feature'] ?? '')) . '</td><td>' . $us . '</td><td>' . $them . '</td></tr>';
            }
            return '<section id="' . ccms_h($sectionId) . '"' . ccms_capsule_section_style_attr($block, 'padding:76px 0') . '><div class="ccms-c-inner"' . ccms_capsule_inner_style_attr($block) . '><div style="text-align:center;max-width:760px;margin:0 auto 26px"><span class="ccms-chip">' . ccms_h((string) ($props['badge'] ?? 'Why us')) . '</span><h2 class="ccms-section-title" style="margin-top:18px">' . ccms_h((string) ($props['title'] ?? '')) . '</h2></div><div class="ccms-card" style="padding:20px"><table class="ccms-table"><thead><tr><th>Feature</th><th>' . ccms_h((string) ($props['our_name'] ?? 'Us')) . '</th><th>' . ccms_h((string) ($props['their_name'] ?? 'Others')) . '</th></tr></thead><tbody>' . $rows . '</tbody></table></div></div></section>';

        case 'case_studies':
            $cards = '';
            foreach (($props['items'] ?? []) as $item) {
                $cards .= '<article class="ccms-card" style="overflow:hidden"><img class="ccms-media-sm" src="' . ccms_h(ccms_capsule_media_url((string) ($item['image'] ?? ''), 'case-' . $blockId . '-' . md5((string) ($item['title'] ?? '')), 960, 720)) . '" alt=""><div style="padding:22px"><span class="ccms-kicker">' . ccms_h((string) ($item['category'] ?? 'Case study')) . '</span><h3 style="margin:0 0 10px;font-size:24px;font-family:' . ccms_h($style['font_heading']) . '">' . ccms_h((string) ($item['title'] ?? '')) . '</h3><p class="ccms-text" style="margin:0 0 12px">' . ccms_h((string) ($item['desc'] ?? $item['summary'] ?? '')) . '</p><p class="ccms-note" style="margin:0 0 14px">' . ccms_h((string) ($item['metric'] ?? '')) . '</p><a href="' . ccms_h((string) ($item['href'] ?? '#')) . '" style="font-weight:800;color:' . ccms_h($style['accent_dark']) . ';text-decoration:none">Read case</a></div></article>';
            }
            return '<section id="' . ccms_h($sectionId) . '"' . ccms_capsule_section_style_attr($block, 'padding:76px 0') . '><div class="ccms-c-inner"' . ccms_capsule_inner_style_attr($block) . '><div style="text-align:center;max-width:820px;margin:0 auto 26px"><span class="ccms-chip">' . ccms_h((string) ($props['badge'] ?? 'Case studies')) . '</span><h2 class="ccms-section-title" style="margin-top:18px">' . ccms_h((string) ($props['title'] ?? '')) . '</h2><p class="ccms-subtitle">' . ccms_h((string) ($props['subtitle'] ?? '')) . '</p></div><div class="ccms-grid-3">' . $cards . '</div></div></section>';

        case 'tabs_content':
            $tabs = '';
            foreach (($props['tabs'] ?? []) as $tab) {
                $bullets = '';
                foreach (($tab['bullets'] ?? []) as $bullet) {
                    $bullets .= '<li>' . ccms_h((string) $bullet) . '</li>';
                }
                $tabs .= '<article class="ccms-card" style="padding:26px"><span class="ccms-kicker">' . ccms_h((string) ($tab['label'] ?? 'Tab')) . '</span><h3 style="margin:0 0 10px;font-size:24px;font-family:' . ccms_h($style['font_heading']) . '">' . ccms_h((string) ($tab['title'] ?? '')) . '</h3><p class="ccms-text" style="margin:0 0 14px">' . ccms_h((string) ($tab['text'] ?? '')) . '</p>' . ($bullets !== '' ? '<ul class="ccms-list">' . $bullets . '</ul>' : '') . '</article>';
            }
            return '<section id="' . ccms_h($sectionId) . '"' . ccms_capsule_section_style_attr($block, 'padding:76px 0;background:rgba(255,255,255,.48)') . '><div class="ccms-c-inner"' . ccms_capsule_inner_style_attr($block) . '><div style="text-align:center;max-width:780px;margin:0 auto 26px"><span class="ccms-chip">' . ccms_h((string) ($props['badge'] ?? 'Inside the offer')) . '</span><h2 class="ccms-section-title" style="margin-top:18px">' . ccms_h((string) ($props['title'] ?? '')) . '</h2><p class="ccms-subtitle">' . ccms_h((string) ($props['subtitle'] ?? '')) . '</p></div><div class="ccms-grid-3">' . $tabs . '</div></div></section>';

        case 'instagram_feed':
            $items = '';
            $index = 1;
            foreach (($props['images'] ?? []) as $image) {
                $url = is_array($image) ? (string) ($image['url'] ?? '') : (string) $image;
                $alt = is_array($image) ? (string) ($image['alt'] ?? 'Instagram image') : 'Instagram image';
                $likes = is_array($image) ? (string) ($image['likes'] ?? '') : '';
                $items .= '<figure class="ccms-card" style="padding:10px;margin:0"><img class="ccms-media-sm" src="' . ccms_h(ccms_capsule_media_url($url, 'insta-' . $blockId . '-' . $index, 720, 720)) . '" alt="' . ccms_h($alt) . '"><figcaption class="ccms-note" style="padding:10px 6px 2px">' . ccms_h($likes !== '' ? ($likes . ' likes') : '') . '</figcaption></figure>';
                $index++;
            }
            return '<section id="' . ccms_h($sectionId) . '"' . ccms_capsule_section_style_attr($block, 'padding:76px 0') . '><div class="ccms-c-inner"' . ccms_capsule_inner_style_attr($block) . '><div style="text-align:center;max-width:780px;margin:0 auto 26px"><span class="ccms-chip">' . ccms_h((string) ($props['badge'] ?? 'Social feed')) . '</span><h2 class="ccms-section-title" style="margin-top:18px">' . ccms_h((string) ($props['title'] ?? '')) . '</h2><p class="ccms-subtitle">' . ccms_h((string) ($props['handle'] ?? '')) . '</p></div><div class="ccms-grid-3">' . $items . '</div></div></section>';

        case 'content_stack':
            $items = '';
            foreach (($props['items'] ?? []) as $item) {
                $bullets = '';
                foreach (($item['bullets'] ?? []) as $bullet) {
                    $bullets .= '<li>' . ccms_h((string) $bullet) . '</li>';
                }
                $items .= '<article class="ccms-card" style="padding:26px"><span class="ccms-kicker">' . ccms_h((string) ($item['eyebrow'] ?? 'Section')) . '</span><h3 style="margin:0 0 10px;font-size:24px;font-family:' . ccms_h($style['font_heading']) . '">' . ccms_h((string) ($item['title'] ?? '')) . '</h3><p class="ccms-text" style="margin:0 0 14px">' . ccms_h((string) ($item['text'] ?? '')) . '</p>' . ($bullets !== '' ? '<ul class="ccms-list" style="margin-bottom:18px">' . $bullets . '</ul>' : '') . '<a class="ccms-btn ccms-btn--ghost" href="' . ccms_h((string) ($item['cta_href'] ?? '#contact')) . '">' . ccms_h((string) ($item['cta_text'] ?? 'Learn more')) . '</a></article>';
            }
            return '<section id="' . ccms_h($sectionId) . '"' . ccms_capsule_section_style_attr($block, 'padding:76px 0;background:rgba(255,255,255,.4)') . '><div class="ccms-c-inner"' . ccms_capsule_inner_style_attr($block) . '><div style="text-align:center;max-width:780px;margin:0 auto 26px"><span class="ccms-chip">' . ccms_h((string) ($props['badge'] ?? 'Content stack')) . '</span><h2 class="ccms-section-title" style="margin-top:18px">' . ccms_h((string) ($props['title'] ?? '')) . '</h2><p class="ccms-subtitle">' . ccms_h((string) ($props['subtitle'] ?? '')) . '</p></div><div class="ccms-grid-3">' . $items . '</div></div></section>';

        case 'map_embed':
            $map = trim((string) ($props['embed_url'] ?? ''));
            $mapHtml = $map !== ''
                ? '<iframe src="' . ccms_h($map) . '" loading="lazy" referrerpolicy="no-referrer-when-downgrade" style="width:100%;min-height:420px;border:0;border-radius:26px;box-shadow:0 28px 50px -34px rgba(0,0,0,.22)"></iframe>'
                : '<div class="ccms-card" style="min-height:420px;display:flex;align-items:center;justify-content:center;padding:24px;text-align:center"><div><p style="font-size:20px;font-weight:800;margin:0 0 10px">Map placeholder</p><p class="ccms-note" style="margin:0">Add an embed URL to show the real map.</p></div></div>';
            $details = '<div><span class="ccms-chip">' . ccms_h((string) ($props['badge'] ?? 'Visit us')) . '</span><h2 class="ccms-section-title" style="margin-top:18px">' . ccms_h((string) ($props['title'] ?? '')) . '</h2><p class="ccms-subtitle">' . ccms_h((string) ($props['subtitle'] ?? '')) . '</p><div class="ccms-card" style="padding:24px;margin-top:20px"><ul style="list-style:none;padding:0;margin:0;display:grid;gap:12px"><li><strong>Address:</strong> ' . ccms_h((string) ($props['address'] ?? '')) . '</li><li><strong>Phone:</strong> ' . ccms_h((string) ($props['phone'] ?? '')) . '</li><li><strong>Email:</strong> ' . ccms_h((string) ($props['email'] ?? '')) . '</li><li><strong>Hours:</strong> ' . ccms_h((string) ($props['hours'] ?? '')) . '</li></ul></div></div>';
            return '<section id="' . ccms_h($sectionId) . '"' . ccms_capsule_section_style_attr($block, 'padding:76px 0') . '><div class="ccms-c-inner ccms-grid-2"' . ccms_capsule_inner_style_attr($block) . '>' . $mapHtml . $details . '</div></section>';

        case 'contact':
            $page = ccms_current_public_page() ?? ['slug' => '', 'title' => ''];
            $feedback = ccms_render_public_form_feedback($blockId);
            $hidden = ccms_render_public_form_hidden_inputs($page, 'contact', $blockId);
            $contactDetails = '';
            foreach ([
                'Email' => (string) ($props['email'] ?? $props['contact_email'] ?? ''),
                'Phone' => (string) ($props['phone'] ?? ''),
                'Address' => (string) ($props['address'] ?? ''),
                'Hours' => (string) ($props['hours'] ?? ''),
            ] as $label => $value) {
                if (trim($value) === '') {
                    continue;
                }
                $contactDetails .= '<li><strong>' . ccms_h($label) . ':</strong> ' . ccms_h($value) . '</li>';
            }
            return '<section id="' . ccms_h($sectionId) . '"' . ccms_capsule_section_style_attr($block, 'padding:72px 0;background:rgba(255,255,255,.56)') . '><div class="ccms-c-inner ccms-grid-2"' . ccms_capsule_inner_style_attr($block) . '><div><span class="ccms-chip">' . ccms_h((string) ($props['badge'] ?? 'Contact')) . '</span><h2 class="ccms-section-title" style="margin-top:18px">' . ccms_h((string) ($props['title'] ?? $props['brand'] ?? 'Contact')) . '</h2><p class="ccms-subtitle">' . ccms_h((string) ($props['subtitle'] ?? $props['info'] ?? '')) . '</p>' . ($contactDetails !== '' ? '<ul class="ccms-list" style="margin-top:18px">' . $contactDetails . '</ul>' : '') . '</div><div><div class="ccms-card" style="padding:28px">' . $feedback . '<form method="post" action="' . ccms_h(ccms_public_form_action()) . '" style="display:grid;gap:14px">' . $hidden . '<input name="name" placeholder="Name" style="width:100%;padding:14px 16px;border-radius:16px;border:1px solid rgba(0,0,0,.08)" required><input name="email" type="email" placeholder="Email" style="width:100%;padding:14px 16px;border-radius:16px;border:1px solid rgba(0,0,0,.08)" required><input name="phone" placeholder="Phone" style="width:100%;padding:14px 16px;border-radius:16px;border:1px solid rgba(0,0,0,.08)"><textarea name="message" placeholder="Message" style="width:100%;min-height:150px;padding:14px 16px;border-radius:16px;border:1px solid rgba(0,0,0,.08);font:inherit" required></textarea><button type="submit" class="ccms-btn">' . ccms_h((string) ($props['button_text'] ?? 'Send message')) . '</button></form></div></div></div></section>';

        case 'split_image_left':
        case 'split_image_right':
            $imageFirst = $type === 'split_image_left';
            $imageHtml = '<div><img class="ccms-media" src="' . ccms_h(ccms_capsule_media_url((string) ($props['image_url'] ?? ''), 'split-' . $blockId, 1280, 960)) . '" alt=""></div>';
            $bullets = '';
            foreach (($props['bullets'] ?? []) as $bullet) {
                $bullets .= '<li>' . ccms_h((string) $bullet) . '</li>';
            }
            $textHtml = '<div><span class="ccms-chip">' . ccms_h((string) ($props['badge'] ?? '')) . '</span><h2 class="ccms-section-title" style="margin-top:18px">' . ccms_h((string) ($props['title'] ?? 'Section title')) . '</h2><p class="ccms-text">' . ccms_h((string) ($props['text'] ?? '')) . '</p>' . ($bullets !== '' ? '<ul class="ccms-text" style="padding-left:18px">' . $bullets . '</ul>' : '') . '</div>';
            return '<section id="' . ccms_h($sectionId) . '"' . ccms_capsule_section_style_attr($block, 'padding:84px 0') . '><div class="ccms-c-inner ccms-grid-2"' . ccms_capsule_inner_style_attr($block) . '>' . ($imageFirst ? $imageHtml . $textHtml : $textHtml . $imageHtml) . '</div></section>';

        case 'text_block':
            $paragraphs = '';
            foreach (($props['paragraphs'] ?? []) as $paragraph) {
                $paragraphs .= '<p class="ccms-text" style="margin:0 0 16px">' . ccms_h((string) $paragraph) . '</p>';
            }
            $quote = '';
            if (!empty($props['quote'])) {
                $quote = '<div class="ccms-card" style="padding:28px;background:#fff6f4;border-color:rgba(229,115,115,.22)"><p style="font-size:30px;line-height:1.35;margin:0 0 12px;font-weight:800">“' . ccms_h((string) $props['quote']) . '”</p><p style="margin:0;color:' . ccms_h($style['accent_dark']) . ';font-size:13px;font-weight:800;text-transform:uppercase;letter-spacing:.12em">' . ccms_h((string) ($props['quote_author'] ?? '')) . '</p></div>';
            }
            return '<section id="' . ccms_h($sectionId) . '"' . ccms_capsule_section_style_attr($block, 'padding:76px 0') . '><div class="ccms-c-inner"' . ccms_capsule_inner_style_attr($block) . '><div class="ccms-card" style="padding:34px 34px 30px"><span class="ccms-chip">' . ccms_h((string) ($props['badge'] ?? '')) . '</span><h2 class="ccms-section-title" style="margin-top:18px">' . ccms_h((string) ($props['title'] ?? '')) . '</h2>' . $paragraphs . $quote . '</div></div></section>';

        case 'icon_boxes':
            $itemsHtml = '';
            foreach (($props['items'] ?? []) as $item) {
                $itemsHtml .= '<article class="ccms-card" style="padding:26px"><div style="width:48px;height:48px;border-radius:16px;background:rgba(229,115,115,.14);margin-bottom:14px"></div><h3 style="margin:0 0 10px;font-size:24px;font-family:' . ccms_h($style['font_heading']) . '">' . ccms_h((string) ($item['title'] ?? '')) . '</h3><p class="ccms-text" style="margin:0">' . ccms_h((string) ($item['desc'] ?? '')) . '</p></article>';
            }
            return '<section id="' . ccms_h($sectionId) . '"' . ccms_capsule_section_style_attr($block, 'padding:76px 0;background:rgba(255,255,255,.58)') . '><div class="ccms-c-inner"' . ccms_capsule_inner_style_attr($block) . '><div style="text-align:center;max-width:860px;margin:0 auto 26px"><span class="ccms-chip">' . ccms_h((string) ($props['badge'] ?? '')) . '</span><h2 class="ccms-section-title" style="margin-top:18px">' . ccms_h((string) ($props['title'] ?? '')) . '</h2><p class="ccms-subtitle">' . ccms_h((string) ($props['subtitle'] ?? '')) . '</p></div><div class="ccms-grid-3">' . $itemsHtml . '</div></div></section>';

        case 'services_cards':
            $itemsHtml = '';
            foreach (($props['services'] ?? []) as $service) {
                $bullets = '';
                foreach (($service['bullets'] ?? []) as $bullet) {
                    $bullets .= '<li>' . ccms_h((string) $bullet) . '</li>';
                }
                $itemsHtml .= '<article class="ccms-card" style="padding:28px"><span class="ccms-kicker">' . ccms_h((string) ($service['icon'] ?? 'Service')) . '</span><h3 style="margin:0 0 10px;font-size:24px;font-family:' . ccms_h($style['font_heading']) . '">' . ccms_h((string) ($service['title'] ?? '')) . '</h3><p class="ccms-text" style="margin:0 0 14px">' . ccms_h((string) ($service['desc'] ?? '')) . '</p>' . ($bullets !== '' ? '<ul class="ccms-list" style="margin-bottom:18px">' . $bullets . '</ul>' : '') . '<a class="ccms-btn" href="' . ccms_h((string) ($service['cta_href'] ?? '#contact')) . '">' . ccms_h((string) ($service['cta_text'] ?? 'Learn more')) . '</a></article>';
            }
            return '<section id="' . ccms_h($sectionId) . '"' . ccms_capsule_section_style_attr($block, 'padding:76px 0') . '><div class="ccms-c-inner"' . ccms_capsule_inner_style_attr($block) . '><div style="text-align:center;max-width:860px;margin:0 auto 26px"><span class="ccms-chip">' . ccms_h((string) ($props['badge'] ?? 'Services')) . '</span><h2 class="ccms-section-title" style="margin-top:18px">' . ccms_h((string) ($props['title'] ?? '')) . '</h2><p class="ccms-subtitle">' . ccms_h((string) ($props['subtitle'] ?? '')) . '</p></div><div class="ccms-grid-3">' . $itemsHtml . '</div></div></section>';

        case 'stats':
            $itemsHtml = '';
            foreach (($props['items'] ?? []) as $item) {
                $itemsHtml .= '<article class="ccms-card ccms-stat"><strong>' . ccms_h((string) ($item['value'] ?? '')) . '</strong><span class="ccms-text" style="font-size:15px">' . ccms_h((string) ($item['label'] ?? '')) . '</span></article>';
            }
            return '<section id="' . ccms_h($sectionId) . '"' . ccms_capsule_section_style_attr($block, 'padding:36px 0 76px') . '><div class="ccms-c-inner"' . ccms_capsule_inner_style_attr($block) . '><div class="ccms-grid-3">' . $itemsHtml . '</div></div></section>';

        case 'testimonials':
            return ccms_render_testimonials_block_variant($block, $props, $style, $sectionId, false);

        case 'testimonial_carousel':
            $itemsHtml = '';
            foreach (($props['items'] ?? []) as $item) {
                $itemsHtml .= '<article class="ccms-card" style="padding:28px"><p style="font-size:22px;line-height:1.55;margin:0 0 14px;font-weight:700">“' . ccms_h((string) ($item['quote'] ?? '')) . '”</p><p class="ccms-note" style="margin:0;color:' . ccms_h($style['accent_dark']) . '">' . ccms_h((string) ($item['name'] ?? '')) . ' · ' . ccms_h((string) ($item['role'] ?? '')) . '</p></article>';
            }
            return '<section id="' . ccms_h($sectionId) . '"' . ccms_capsule_section_style_attr($block, 'padding:76px 0;background:rgba(255,255,255,.5)') . '><div class="ccms-c-inner"' . ccms_capsule_inner_style_attr($block) . '><div style="text-align:center;max-width:760px;margin:0 auto 26px"><span class="ccms-chip">' . ccms_h((string) ($props['badge'] ?? 'Testimonials')) . '</span><h2 class="ccms-section-title" style="margin-top:18px">' . ccms_h((string) ($props['title'] ?? '')) . '</h2></div><div class="ccms-grid-3">' . $itemsHtml . '</div></div></section>';

        case 'reviews_summary':
            $breakdown = '';
            foreach (($props['breakdown'] ?? []) as $row) {
                $value = max(0, min(100, (int) ($row['value'] ?? 0)));
                $breakdown .= '<div style="display:grid;grid-template-columns:120px 1fr 48px;gap:12px;align-items:center"><span class="ccms-note">' . ccms_h((string) ($row['label'] ?? '')) . '</span><div style="height:10px;border-radius:999px;background:rgba(0,0,0,.08);overflow:hidden"><div style="height:100%;width:' . $value . '%;background:' . ccms_h($style['gradient_accent']) . '"></div></div><strong>' . $value . '%</strong></div>';
            }
            return '<section id="' . ccms_h($sectionId) . '"' . ccms_capsule_section_style_attr($block, 'padding:72px 0;background:rgba(255,255,255,.46)') . '><div class="ccms-c-inner ccms-grid-2"' . ccms_capsule_inner_style_attr($block) . '><div><span class="ccms-chip">' . ccms_h((string) ($props['badge'] ?? 'Ratings')) . '</span><h2 class="ccms-section-title" style="margin-top:18px">' . ccms_h((string) ($props['title'] ?? '')) . '</h2><p class="ccms-subtitle">' . ccms_h((string) ($props['source'] ?? '')) . '</p></div><div class="ccms-card" style="padding:28px"><div style="display:flex;align-items:end;gap:14px;margin-bottom:18px"><strong style="font-size:48px;line-height:1">' . ccms_h((string) ($props['score'] ?? '4.9/5')) . '</strong><span class="ccms-note">' . ccms_h((string) ($props['review_count'] ?? '')) . '</span></div><div style="display:grid;gap:12px">' . $breakdown . '</div></div></div></section>';

        case 'testimonial_cards':
            return ccms_render_testimonials_block_variant($block, $props, $style, $sectionId, true);

        case 'timeline':
            $itemsHtml = '';
            foreach (($props['items'] ?? []) as $item) {
                $itemsHtml .= '<article class="ccms-card" style="padding:24px;display:grid;grid-template-columns:140px 1fr;gap:18px;align-items:start"><div><span class="ccms-chip" style="background:rgba(168,202,186,.18);color:#3d6c5a">' . ccms_h((string) ($item['year'] ?? '')) . '</span><p class="ccms-note" style="margin:12px 0 0">' . ccms_h((string) ($item['metric'] ?? '')) . '</p></div><div><h3 style="margin:0 0 8px;font-size:24px;font-family:' . ccms_h($style['font_heading']) . '">' . ccms_h((string) ($item['title'] ?? '')) . '</h3><p class="ccms-text" style="margin:0">' . ccms_h((string) ($item['desc'] ?? '')) . '</p></div></article>';
            }
            return '<section id="' . ccms_h($sectionId) . '"' . ccms_capsule_section_style_attr($block, 'padding:76px 0') . '><div class="ccms-c-inner"' . ccms_capsule_inner_style_attr($block) . '><div style="text-align:center;max-width:760px;margin:0 auto 26px"><span class="ccms-chip">' . ccms_h((string) ($props['badge'] ?? 'Timeline')) . '</span><h2 class="ccms-section-title" style="margin-top:18px">' . ccms_h((string) ($props['title'] ?? '')) . '</h2><p class="ccms-subtitle">' . ccms_h((string) ($props['subtitle'] ?? '')) . '</p></div><div style="display:grid;gap:18px">' . $itemsHtml . '</div></div></section>';

        case 'blog_grid':
            return ccms_render_blog_grid_block_variant($block, $props, $style, $sectionId, $blockId);

        case 'blog_featured':
            return ccms_render_blog_featured_block_variant($block, $props, $style, $sectionId, $blockId);

        case 'blog_carousel':
            return ccms_render_blog_carousel_block_variant($block, $props, $style, $sectionId, $blockId);

        case 'portfolio_grid':
            $projects = '';
            foreach (($props['projects'] ?? []) as $project) {
                $projects .= '<article class="ccms-card" style="overflow:hidden"><img class="ccms-media-sm" src="' . ccms_h(ccms_capsule_media_url((string) ($project['image'] ?? ''), 'project-' . $blockId . '-' . md5((string) ($project['title'] ?? '')), 960, 720)) . '" alt=""><div style="padding:22px"><span class="ccms-kicker">' . ccms_h((string) ($project['category'] ?? 'Project')) . '</span><h3 style="margin:0 0 10px;font-size:24px;font-family:' . ccms_h($style['font_heading']) . '">' . ccms_h((string) ($project['title'] ?? '')) . '</h3><p class="ccms-note" style="margin:0 0 14px">' . ccms_h((string) ($project['metric'] ?? '')) . '</p><a href="' . ccms_h((string) ($project['href'] ?? '#')) . '" style="font-weight:800;color:' . ccms_h($style['accent_dark']) . ';text-decoration:none">View project</a></div></article>';
            }
            return '<section id="' . ccms_h($sectionId) . '"' . ccms_capsule_section_style_attr($block, 'padding:76px 0') . '><div class="ccms-c-inner"' . ccms_capsule_inner_style_attr($block) . '><div style="text-align:center;max-width:820px;margin:0 auto 26px"><span class="ccms-chip">' . ccms_h((string) ($props['badge'] ?? 'Selected work')) . '</span><h2 class="ccms-section-title" style="margin-top:18px">' . ccms_h((string) ($props['title'] ?? '')) . '</h2><p class="ccms-subtitle">' . ccms_h((string) ($props['subtitle'] ?? '')) . '</p></div><div class="ccms-grid-3">' . $projects . '</div></div></section>';

        case 'video_embed':
            $videoUrl = trim((string) ($props['video_url'] ?? ''));
            $media = $videoUrl !== ''
                ? '<iframe src="' . ccms_h($videoUrl) . '" title="' . ccms_h((string) ($props['title'] ?? 'Video')) . '" style="width:100%;height:520px;border:0;border-radius:24px" allowfullscreen loading="lazy"></iframe>'
                : '<img class="ccms-media" src="' . ccms_h(ccms_capsule_media_url('', 'video-embed-' . $blockId, 1280, 960)) . '" alt="">';
            return '<section id="' . ccms_h($sectionId) . '"' . ccms_capsule_section_style_attr($block, 'padding:76px 0;background:rgba(255,255,255,.48)') . '><div class="ccms-c-inner"' . ccms_capsule_inner_style_attr($block) . '><div style="text-align:center;max-width:820px;margin:0 auto 26px"><span class="ccms-chip">' . ccms_h((string) ($props['badge'] ?? 'Video')) . '</span><h2 class="ccms-section-title" style="margin-top:18px">' . ccms_h((string) ($props['title'] ?? '')) . '</h2><p class="ccms-subtitle">' . ccms_h((string) ($props['subtitle'] ?? '')) . '</p></div><div class="ccms-card" style="padding:14px;overflow:hidden">' . $media . '</div>' . (!empty($props['caption']) ? '<p class="ccms-note" style="margin:14px 0 0;text-align:center">' . ccms_h((string) $props['caption']) . '</p>' : '') . '</div></section>';

        case 'before_after':
            $beforeImage = ccms_capsule_media_url((string) ($props['before_image'] ?? ''), 'before-' . $blockId, 1200, 900);
            $afterImage = ccms_capsule_media_url((string) ($props['after_image'] ?? ''), 'after-' . $blockId, 1200, 900);
            return '<section id="' . ccms_h($sectionId) . '"' . ccms_capsule_section_style_attr($block, 'padding:76px 0') . '><div class="ccms-c-inner"' . ccms_capsule_inner_style_attr($block) . '><div style="text-align:center;max-width:820px;margin:0 auto 26px"><span class="ccms-chip">' . ccms_h((string) ($props['badge'] ?? 'Comparison')) . '</span><h2 class="ccms-section-title" style="margin-top:18px">' . ccms_h((string) ($props['title'] ?? '')) . '</h2><p class="ccms-subtitle">' . ccms_h((string) ($props['subtitle'] ?? '')) . '</p></div><div class="ccms-grid-2"><figure class="ccms-card" style="padding:12px;margin:0"><img class="ccms-media" src="' . ccms_h($beforeImage) . '" alt=""><figcaption class="ccms-note" style="padding:12px 8px 4px;text-align:center">' . ccms_h((string) ($props['before_label'] ?? 'Before')) . '</figcaption></figure><figure class="ccms-card" style="padding:12px;margin:0"><img class="ccms-media" src="' . ccms_h($afterImage) . '" alt=""><figcaption class="ccms-note" style="padding:12px 8px 4px;text-align:center">' . ccms_h((string) ($props['after_label'] ?? 'After')) . '</figcaption></figure></div></div></section>';

        case 'parallax_section':
            $height = is_numeric($props['height'] ?? null) ? max(320, (int) $props['height']) : 520;
            $bg = ccms_capsule_media_url((string) ($props['image'] ?? $props['background_image'] ?? ''), 'parallax-' . $blockId, 1440, 1080);
            return '<section id="' . ccms_h($sectionId) . '"' . ccms_capsule_section_style_attr($block, 'padding:0;background:linear-gradient(135deg,rgba(47,36,31,.55),rgba(171,93,78,.45)),url(' . $bg . ') center/cover fixed no-repeat;min-height:' . $height . 'px;color:#fff;display:flex;align-items:center') . '><div class="ccms-c-inner" style="text-align:center"' . ccms_capsule_inner_style_attr($block) . '><span class="ccms-chip" style="background:rgba(255,255,255,.16);color:#fff">' . ccms_h((string) ($props['badge'] ?? 'Brand statement')) . '</span><h2 class="ccms-section-title" style="margin-top:18px;color:#fff">' . ccms_h((string) ($props['title'] ?? '')) . '</h2><p style="font-size:18px;line-height:1.75;color:rgba(255,255,255,.84);max-width:760px;margin:0 auto 24px">' . ccms_h((string) ($props['subtitle'] ?? '')) . '</p>' . (!empty($props['button_text']) ? '<a class="ccms-btn ccms-btn--ghost" href="' . ccms_h((string) ($props['button_href'] ?? '#contact')) . '">' . ccms_h((string) $props['button_text']) . '</a>' : '') . '</div></section>';

        case 'divider_fancy':
            $dividerStyle = (string) ($props['style'] ?? 'wave');
            $height = is_numeric($props['height'] ?? null) ? (int) $props['height'] : 80;
            $gradient = $dividerStyle === 'dots'
                ? 'radial-gradient(circle at 20px 20px,' . $style['accent'] . ' 2px,transparent 3px) 0 0/24px 24px,transparent'
                : ($dividerStyle === 'zigzag'
                    ? 'repeating-linear-gradient(-45deg,' . $style['accent'] . ' 0 10px,' . $style['secondary'] . ' 10px 20px)'
                    : ($dividerStyle === 'diagonal'
                        ? 'linear-gradient(175deg,transparent 0 46%,' . $style['accent'] . ' 46% 54%,transparent 54% 100%)'
                        : 'radial-gradient(120% 100% at 50% 0%,' . $style['accent'] . ' 0 20%,transparent 21%)'));
            return '<div id="' . ccms_h($sectionId) . '"' . ccms_capsule_section_style_attr($block, 'height:' . $height . 'px;background:' . $gradient) . '></div>';

        case 'spacer':
            $height = is_numeric($props['height'] ?? null) ? (int) $props['height'] : 92;
            return '<div id="' . ccms_h($sectionId) . '" style="height:' . $height . 'px"></div>';

        case 'columns_2':
        case 'columns_3':
            $columns = is_array($props['columns'] ?? null) ? $props['columns'] : [];
            $cards = '';
            foreach ($columns as $column) {
                $bullets = '';
                foreach (($column['bullets'] ?? []) as $bullet) {
                    $bullets .= '<li>' . ccms_h((string) $bullet) . '</li>';
                }
                $cards .= '<article class="ccms-card" style="padding:26px"><span class="ccms-kicker">' . ccms_h((string) ($column['eyebrow'] ?? $column['label'] ?? 'Column')) . '</span><h3 style="margin:0 0 10px;font-size:24px;font-family:' . ccms_h($style['font_heading']) . '">' . ccms_h((string) ($column['title'] ?? '')) . '</h3><p class="ccms-text" style="margin:0 0 14px">' . ccms_h((string) ($column['text'] ?? $column['desc'] ?? '')) . '</p>' . ($bullets !== '' ? '<ul class="ccms-list" style="margin-bottom:18px">' . $bullets . '</ul>' : '') . (!empty($column['cta_text']) ? '<a class="ccms-btn ccms-btn--ghost" href="' . ccms_h((string) ($column['cta_href'] ?? '#')) . '">' . ccms_h((string) $column['cta_text']) . '</a>' : '') . '</article>';
            }
            $gridClass = $type === 'columns_2' ? 'ccms-grid-2' : 'ccms-grid-3';
            return '<section id="' . ccms_h($sectionId) . '"' . ccms_capsule_section_style_attr($block, 'padding:76px 0;background:rgba(255,255,255,.46)') . '><div class="ccms-c-inner"' . ccms_capsule_inner_style_attr($block) . '><div style="text-align:center;max-width:820px;margin:0 auto 26px"><span class="ccms-chip">' . ccms_h((string) ($props['badge'] ?? 'Columns')) . '</span><h2 class="ccms-section-title" style="margin-top:18px">' . ccms_h((string) ($props['title'] ?? '')) . '</h2><p class="ccms-subtitle">' . ccms_h((string) ($props['subtitle'] ?? '')) . '</p></div><div class="' . $gridClass . '">' . $cards . '</div></div></section>';

        case 'sidebar_layout':
            $paragraphs = '';
            foreach (($props['content_paragraphs'] ?? []) as $paragraph) {
                $paragraphs .= '<p class="ccms-text" style="margin:0 0 16px">' . ccms_h((string) $paragraph) . '</p>';
            }
            $sidebarItems = '';
            foreach (($props['sidebar_items'] ?? []) as $item) {
                if (is_array($item)) {
                    $label = (string) ($item['label'] ?? $item['title'] ?? 'Item');
                    $value = (string) ($item['value'] ?? $item['text'] ?? '');
                    $sidebarItems .= '<li><strong>' . ccms_h($label) . ':</strong> ' . ccms_h($value) . '</li>';
                } else {
                    $sidebarItems .= '<li>' . ccms_h((string) $item) . '</li>';
                }
            }
            return '<section id="' . ccms_h($sectionId) . '"' . ccms_capsule_section_style_attr($block, 'padding:76px 0') . '><div class="ccms-c-inner" style="display:grid;grid-template-columns:minmax(0,1.4fr) 360px;gap:28px"' . ccms_capsule_inner_style_attr($block) . '><div class="ccms-card" style="padding:30px"><span class="ccms-chip">' . ccms_h((string) ($props['badge'] ?? 'Summary')) . '</span><h2 class="ccms-section-title" style="margin-top:18px">' . ccms_h((string) ($props['content_title'] ?? $props['title'] ?? '')) . '</h2>' . $paragraphs . '</div><aside class="ccms-card" style="padding:26px;height:fit-content"><h3 style="margin:0 0 12px;font-size:24px;font-family:' . ccms_h($style['font_heading']) . '">' . ccms_h((string) ($props['sidebar_title'] ?? 'Quick facts')) . '</h3><ul class="ccms-list" style="margin-bottom:18px">' . $sidebarItems . '</ul>' . (!empty($props['sidebar_cta_text']) ? '<a class="ccms-btn" href="' . ccms_h((string) ($props['sidebar_cta_href'] ?? '#contact')) . '">' . ccms_h((string) $props['sidebar_cta_text']) . '</a>' : '') . '</aside></div></section>';

        case 'cta':
            return '<section id="' . ccms_h($sectionId) . '"' . ccms_capsule_section_style_attr($block, 'padding:88px 0;background:linear-gradient(135deg,' . $style['text_primary'] . ' 0%,' . $style['accent_dark'] . ' 100%);color:#fff') . '><div class="ccms-c-inner"><div style="text-align:center;max-width:860px;margin:0 auto"><h2 class="ccms-section-title" style="color:#fff">' . ccms_h((string) ($props['title'] ?? '')) . '</h2><p style="font-size:18px;line-height:1.75;color:rgba(255,255,255,.82);margin:0 0 24px">' . ccms_h((string) ($props['subtitle'] ?? '')) . '</p><a class="ccms-btn ccms-btn--ghost" href="' . ccms_h((string) ($props['button_href'] ?? '#')) . '">' . ccms_h((string) ($props['button_text'] ?? 'Contactar')) . '</a></div></div></section>';

        case 'popup_cta':
            return '<section id="' . ccms_h($sectionId) . '"' . ccms_capsule_section_style_attr($block, 'padding:28px 0') . '><div class="ccms-c-inner"' . ccms_capsule_inner_style_attr($block) . '><div class="ccms-card" style="padding:18px 20px;display:flex;align-items:center;justify-content:space-between;gap:16px;flex-wrap:wrap;background:linear-gradient(135deg,rgba(229,115,115,.12) 0%,rgba(217,196,179,.4) 100%)"><p style="margin:0;font-weight:800">' . ccms_h((string) ($props['text'] ?? '')) . '</p><div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap"><span class="ccms-note">' . ccms_h((string) ($props['disclaimer'] ?? '')) . '</span><a class="ccms-btn" href="' . ccms_h((string) ($props['button_href'] ?? '#contact')) . '">' . ccms_h((string) ($props['button_text'] ?? 'Learn more')) . '</a></div></div></div></section>';

        case 'lead_form':
            $page = ccms_current_public_page() ?? ['slug' => '', 'title' => ''];
            $feedback = ccms_render_public_form_feedback($blockId);
            $hidden = ccms_render_public_form_hidden_inputs($page, 'lead_form', $blockId);
            return '<section id="' . ccms_h($sectionId) . '"' . ccms_capsule_section_style_attr($block, 'padding:76px 0') . '><div class="ccms-c-inner"' . ccms_capsule_inner_style_attr($block) . '><div class="ccms-card" style="padding:34px"><span class="ccms-chip">' . ccms_h((string) ($props['badge'] ?? 'Contacto')) . '</span><h2 class="ccms-section-title" style="margin-top:18px">' . ccms_h((string) ($props['title'] ?? '')) . '</h2><p class="ccms-subtitle">' . ccms_h((string) ($props['subtitle'] ?? '')) . '</p>' . $feedback . '<form method="post" action="' . ccms_h(ccms_public_form_action()) . '" style="display:grid;gap:14px;margin-top:24px">' . $hidden . '<div class="ccms-grid-2"><input name="name" placeholder="Nombre" style="width:100%;padding:14px 16px;border-radius:16px;border:1px solid rgba(0,0,0,.08)" required><input name="email" type="email" placeholder="Email" style="width:100%;padding:14px 16px;border-radius:16px;border:1px solid rgba(0,0,0,.08)" required></div><div class="ccms-grid-2"><input name="phone" placeholder="Teléfono" style="width:100%;padding:14px 16px;border-radius:16px;border:1px solid rgba(0,0,0,.08)"><input name="company" placeholder="Empresa" style="width:100%;padding:14px 16px;border-radius:16px;border:1px solid rgba(0,0,0,.08)"></div><textarea name="message" placeholder="Mensaje" style="width:100%;min-height:150px;padding:14px 16px;border-radius:16px;border:1px solid rgba(0,0,0,.08);font:inherit" required></textarea><div style="display:flex;align-items:center;justify-content:space-between;gap:14px;flex-wrap:wrap"><small style="color:' . ccms_h($style['text_muted']) . ';line-height:1.6">' . ccms_h((string) ($props['privacy_text'] ?? '')) . '</small><button type="submit" class="ccms-btn">' . ccms_h((string) ($props['button_text'] ?? 'Enviar')) . '</button></div></form></div></div></section>';

        case 'footer_multi':
            $columns = '';
            foreach (($props['columns'] ?? []) as $column) {
                $links = '';
                foreach (($column['links'] ?? []) as $link) {
                    $links .= '<li><a href="' . ccms_h((string) ($link['href'] ?? '#')) . '">' . ccms_h(ccms_capsule_link_text($link)) . '</a></li>';
                }
                $columns .= '<div><h3 style="margin:0 0 12px;font-size:18px;color:#fff">' . ccms_h((string) ($column['title'] ?? '')) . '</h3><ul style="list-style:none;padding:0;margin:0;display:grid;gap:10px;color:rgba(255,255,255,.78)">' . $links . '</ul></div>';
            }
            $contact = '';
            foreach (($props['contact_lines'] ?? []) as $line) {
                $contact .= '<li>' . ccms_h((string) $line) . '</li>';
            }
            return '<footer id="' . ccms_h($sectionId) . '" class="ccms-footer" style="padding:58px 0 34px"><div class="ccms-c-inner"><div class="ccms-grid-3" style="align-items:flex-start"><div><h3 style="margin:0 0 12px;font-size:22px;color:#fff;font-family:' . ccms_h($style['font_heading']) . '">' . ccms_h((string) ($props['brand'] ?? '')) . '</h3><p style="margin:0;color:rgba(255,255,255,.76);line-height:1.75">' . ccms_h((string) ($props['description'] ?? '')) . '</p></div>' . $columns . '<div><h3 style="margin:0 0 12px;font-size:18px;color:#fff">Contacto</h3><ul style="list-style:none;padding:0;margin:0;display:grid;gap:10px;color:rgba(255,255,255,.76)">' . $contact . '</ul></div></div><div style="border-top:1px solid rgba(255,255,255,.14);margin-top:28px;padding-top:18px;color:rgba(255,255,255,.72);font-size:14px">' . ccms_h((string) ($props['copyright'] ?? '')) . '</div></div></footer>';
    }

    return '<section id="' . ccms_h($sectionId) . '" style="padding:64px 32px"><div class="ccms-c-inner"><div class="ccms-card" style="padding:24px"><p class="ccms-text">Este bloque todavía no está soportado por el renderer PHP.</p></div></div></section>';
}
