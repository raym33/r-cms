<?php
declare(strict_types=1);

function ccms_root_path(string $relative = ''): string
{
    $root = trim((string) getenv('CCMS_ROOT'));
    if ($root === '') {
        $root = dirname(__DIR__);
    }
    if ($relative === '') {
        return $root;
    }
    return $root . DIRECTORY_SEPARATOR . ltrim($relative, DIRECTORY_SEPARATOR);
}

function ccms_h(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function ccms_slugify(string $value): string
{
    $value = trim(mb_strtolower($value, 'UTF-8'));
    $value = preg_replace('/[^a-z0-9]+/u', '-', $value) ?? '';
    $value = trim($value, '-');
    return $value !== '' ? $value : 'page';
}

function ccms_now_iso(): string
{
    return gmdate('c');
}

function ccms_redirect(string $url): never
{
    header('Location: ' . $url);
    exit;
}

function ccms_request_path(): string
{
    $path = $_GET['path'] ?? parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
    $path = is_string($path) ? $path : '/';
    return '/' . trim($path, '/');
}

function ccms_base_url(): string
{
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? '127.0.0.1:8088';
    return $scheme . '://' . $host;
}

function ccms_send_common_security_headers(): void
{
    if (headers_sent()) {
        return;
    }
    @header_remove('X-Powered-By');
    $nonce = ccms_csp_nonce();
    header('X-Frame-Options: SAMEORIGIN');
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: same-origin');
    header("Permissions-Policy: camera=(), microphone=(), geolocation=()");
    header("Content-Security-Policy: default-src 'self' https: data: blob:; img-src 'self' https: data: blob:; style-src 'self' 'unsafe-inline' https:; script-src 'self' 'nonce-{$nonce}'; frame-src 'self'; connect-src 'self' http://127.0.0.1:1234 http://localhost:1234 https:;");
}

function ccms_send_admin_headers(): void
{
    ccms_send_common_security_headers();
    if (headers_sent()) {
        return;
    }
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Pragma: no-cache');
    header('Expires: 0');
}

function ccms_flash(string $type, string $message): void
{
    $_SESSION['ccms_flash'] = ['type' => $type, 'message' => $message];
}

function ccms_consume_flash(): ?array
{
    if (!isset($_SESSION['ccms_flash']) || !is_array($_SESSION['ccms_flash'])) {
        return null;
    }
    $flash = $_SESSION['ccms_flash'];
    unset($_SESSION['ccms_flash']);
    return $flash;
}

function ccms_public_upload_url(string $filename): string
{
    return '/uploads/' . rawurlencode($filename);
}

function ccms_csp_nonce(): string
{
    static $nonce = null;
    if (is_string($nonce) && $nonce !== '') {
        return $nonce;
    }
    $nonce = rtrim(strtr(base64_encode(random_bytes(18)), '+/', '-_'), '=');
    return $nonce;
}

function ccms_script_nonce_attr(): string
{
    return ' nonce="' . ccms_h(ccms_csp_nonce()) . '"';
}

function ccms_sanitize_url(string $value, bool $allowDataImage = false): string
{
    $value = trim($value);
    if ($value === '') {
        return '';
    }
    if (
        str_starts_with($value, '#')
        || str_starts_with($value, '/')
        || str_starts_with($value, './')
        || str_starts_with($value, '../')
    ) {
        return $value;
    }
    if ($allowDataImage && preg_match('/^data:image\/[a-z0-9.+-]+;base64,[a-z0-9+\/=\s]+$/i', $value)) {
        return $value;
    }
    $scheme = strtolower((string) parse_url($value, PHP_URL_SCHEME));
    if (in_array($scheme, ['http', 'https', 'mailto', 'tel'], true)) {
        return $value;
    }
    return '';
}

function ccms_sanitize_css_value(string $value): string
{
    $value = trim(preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $value) ?? '');
    if ($value === '') {
        return '';
    }
    if (preg_match('/(?:expression\s*\(|javascript:|vbscript:|@import|-moz-binding|behavior\s*:)/i', $value)) {
        return '';
    }
    $value = preg_replace('/url\s*\([^)]*\)/i', '', $value) ?? '';
    return trim($value);
}

function ccms_sanitize_style_attribute(string $style): string
{
    $allowed = [
        'align-items', 'align-self', 'aspect-ratio', 'background', 'background-color', 'border',
        'border-color', 'border-radius', 'bottom', 'box-shadow', 'color', 'display', 'flex',
        'flex-direction', 'flex-wrap', 'font-size', 'font-weight', 'gap', 'grid-column',
        'grid-row', 'grid-template-columns', 'grid-template-rows', 'height', 'justify-content',
        'justify-self', 'left', 'letter-spacing', 'line-height', 'margin', 'margin-bottom',
        'margin-left', 'margin-right', 'margin-top', 'max-height', 'max-width', 'min-height',
        'min-width', 'object-fit', 'opacity', 'overflow', 'padding', 'padding-bottom',
        'padding-left', 'padding-right', 'padding-top', 'position', 'right', 'text-align',
        'text-transform', 'top', 'transform', 'width', 'z-index',
    ];
    $rules = [];
    foreach (explode(';', $style) as $declaration) {
        $parts = explode(':', $declaration, 2);
        if (count($parts) !== 2) {
            continue;
        }
        $property = strtolower(trim($parts[0]));
        if ($property === '') {
            continue;
        }
        if (!in_array($property, $allowed, true) && !str_starts_with($property, '--ccms-')) {
            continue;
        }
        $value = ccms_sanitize_css_value($parts[1]);
        if ($value === '') {
            continue;
        }
        $rules[] = $property . ':' . $value;
    }
    return implode(';', $rules);
}

function ccms_sanitize_custom_css(string $css): string
{
    $css = preg_replace('/\/\*.*?\*\//s', '', $css) ?? '';
    $css = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $css) ?? '';
    if ($css === '') {
        return '';
    }
    if (preg_match('/(?:expression\s*\(|javascript:|vbscript:|@import|-moz-binding|behavior\s*:)/i', $css)) {
        $css = preg_replace('/(?:expression\s*\(|javascript:|vbscript:|@import|-moz-binding|behavior\s*:)/i', '', $css) ?? '';
    }
    $css = preg_replace('/url\s*\([^)]*\)/i', '', $css) ?? '';
    return trim($css);
}

function ccms_sanitize_html_fragment(string $html): string
{
    $html = trim($html);
    if ($html === '') {
        return '';
    }
    if (!class_exists(DOMDocument::class)) {
        return strip_tags($html, '<section><div><span><p><h1><h2><h3><h4><h5><h6><a><img><ul><ol><li><strong><em><b><i><small><blockquote><br><hr><form><input><textarea><button><label>');
    }

    $wrapped = '<div id="ccms-sanitize-root">' . $html . '</div>';
    $previous = libxml_use_internal_errors(true);
    $dom = new DOMDocument('1.0', 'UTF-8');
    $dom->loadHTML('<?xml encoding="utf-8" ?>' . $wrapped, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
    libxml_clear_errors();
    libxml_use_internal_errors($previous);

    $root = $dom->getElementById('ccms-sanitize-root');
    if (!$root instanceof DOMElement) {
        return '';
    }

    $sanitizeNode = static function (DOMNode $node) use (&$sanitizeNode, $dom): void {
        $allowedTags = [
            'section', 'div', 'span', 'p', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'a', 'img',
            'ul', 'ol', 'li', 'strong', 'em', 'b', 'i', 'small', 'blockquote', 'br', 'hr',
            'form', 'input', 'textarea', 'button', 'label',
        ];
        if ($node instanceof DOMComment) {
            $node->parentNode?->removeChild($node);
            return;
        }
        if ($node instanceof DOMElement) {
            $tag = strtolower($node->tagName);
            if (!in_array($tag, $allowedTags, true)) {
                $parent = $node->parentNode;
                if ($parent) {
                    while ($node->firstChild) {
                        $parent->insertBefore($node->firstChild, $node);
                    }
                    $parent->removeChild($node);
                }
                return;
            }

            $allowedAttrs = ['alt', 'aria-label', 'aria-hidden', 'class', 'decoding', 'for', 'height', 'href', 'id', 'loading', 'name', 'placeholder', 'rel', 'role', 'src', 'style', 'target', 'title', 'type', 'value', 'width', 'rows', 'cols'];
            $toRemove = [];
            foreach (iterator_to_array($node->attributes ?? []) as $attr) {
                $name = strtolower($attr->name);
                $value = $attr->value;
                if (str_starts_with($name, 'on')) {
                    $toRemove[] = $attr->name;
                    continue;
                }
                if (!in_array($name, $allowedAttrs, true)) {
                    $toRemove[] = $attr->name;
                    continue;
                }
                if ($name === 'href') {
                    $sanitized = ccms_sanitize_url($value, false);
                    if ($sanitized === '') {
                        $toRemove[] = $attr->name;
                    } else {
                        $node->setAttribute($attr->name, $sanitized);
                    }
                    continue;
                }
                if ($name === 'src') {
                    $sanitized = ccms_sanitize_url($value, true);
                    if ($sanitized === '') {
                        $toRemove[] = $attr->name;
                    } else {
                        $node->setAttribute($attr->name, $sanitized);
                    }
                    continue;
                }
                if ($name === 'style') {
                    $sanitized = ccms_sanitize_style_attribute($value);
                    if ($sanitized === '') {
                        $toRemove[] = $attr->name;
                    } else {
                        $node->setAttribute($attr->name, $sanitized);
                    }
                    continue;
                }
                if ($name === 'target') {
                    $target = in_array($value, ['_blank', '_self'], true) ? $value : '_self';
                    $node->setAttribute($attr->name, $target);
                    if ($target === '_blank') {
                        $node->setAttribute('rel', 'noopener noreferrer');
                    }
                    continue;
                }
            }
            foreach ($toRemove as $attrName) {
                $node->removeAttribute($attrName);
            }
        }

        foreach (iterator_to_array($node->childNodes ?? []) as $child) {
            $sanitizeNode($child);
        }
    };

    foreach (iterator_to_array($root->childNodes) as $child) {
        $sanitizeNode($child);
    }

    $output = '';
    foreach (iterator_to_array($root->childNodes) as $child) {
        $output .= $dom->saveHTML($child);
    }
    return $output;
}
