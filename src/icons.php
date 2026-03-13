<?php
declare(strict_types=1);

function ccms_icon(string $name, int $size = 18, string $className = 'icon-svg'): string
{
    static $icons = [
        'alert-triangle' => '<path d="m12 3 10 18H2Z"/><path d="M12 9v4"/><path d="M12 17h.01"/>',
        'archive' => '<rect x="3" y="4" width="18" height="4" rx="1"/><path d="M5 8v11a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8"/><path d="M10 12h4"/>',
        'chevron-down' => '<path d="m6 9 6 6 6-6"/>',
        'chevron-right' => '<path d="m9 6 6 6-6 6"/>',
        'chevron-up' => '<path d="m18 15-6-6-6 6"/>',
        'circle-check' => '<circle cx="12" cy="12" r="10"/><path d="m9 12 2 2 4-4"/>',
        'circle-dashed' => '<circle cx="12" cy="12" r="10" stroke-dasharray="3 3"/>',
        'circle-dot' => '<circle cx="12" cy="12" r="10"/><circle cx="12" cy="12" r="1.75" fill="currentColor" stroke="none"/>',
        'copy' => '<rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/>',
        'download' => '<path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><path d="m7 10 5 5 5-5"/><path d="M12 15V3"/>',
        'eye' => '<path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/>',
        'file-text' => '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/><path d="M16 13H8"/><path d="M16 17H8"/><path d="M10 9H8"/>',
        'globe' => '<circle cx="12" cy="12" r="10"/><path d="M2 12h20"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10Z"/>',
        'grip-vertical' => '<circle cx="9" cy="5" r="1"/><circle cx="15" cy="5" r="1"/><circle cx="9" cy="12" r="1"/><circle cx="15" cy="12" r="1"/><circle cx="9" cy="19" r="1"/><circle cx="15" cy="19" r="1"/>',
        'image' => '<rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="9" cy="9" r="2"/><path d="m21 15-3.5-3.5a2 2 0 0 0-2.8 0L6 20"/>',
        'log-out' => '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><path d="M16 17l5-5-5-5"/><path d="M21 12H9"/>',
        'mail' => '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 6 9-6"/>',
        'pencil' => '<path d="M12 20h9"/><path d="m16.5 3.5 4 4L7 21l-4 1 1-4Z"/>',
        'plus' => '<path d="M12 5v14"/><path d="M5 12h14"/>',
        'plus-circle' => '<circle cx="12" cy="12" r="10"/><path d="M12 8v8"/><path d="M8 12h8"/>',
        'puzzle' => '<path d="M19 10h-2a2 2 0 1 0 0 4h2v3a2 2 0 0 1-2 2h-3v-2a2 2 0 1 0-4 0v2H7a2 2 0 0 1-2-2v-3h2a2 2 0 1 0 0-4H5V7a2 2 0 0 1 2-2h3v2a2 2 0 1 0 4 0V5h3a2 2 0 0 1 2 2z"/>',
        'save' => '<path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2Z"/><path d="M17 21v-8H7v8"/><path d="M7 3v5h8"/>',
        'search' => '<circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3"/>',
        'settings' => '<circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.7 1.7 0 0 0 .3 1.8l.1.1a2 2 0 1 1-2.8 2.8l-.1-.1a1.7 1.7 0 0 0-1.8-.3 1.7 1.7 0 0 0-1 1.5V21a2 2 0 1 1-4 0v-.1a1.7 1.7 0 0 0-1-1.5 1.7 1.7 0 0 0-1.8.3l-.1.1a2 2 0 1 1-2.8-2.8l.1-.1a1.7 1.7 0 0 0 .3-1.8 1.7 1.7 0 0 0-1.5-1H3a2 2 0 1 1 0-4h.1a1.7 1.7 0 0 0 1.5-1 1.7 1.7 0 0 0-.3-1.8l-.1-.1a2 2 0 1 1 2.8-2.8l.1.1a1.7 1.7 0 0 0 1.8.3H9a1.7 1.7 0 0 0 1-1.5V3a2 2 0 1 1 4 0v.1a1.7 1.7 0 0 0 1 1.5 1.7 1.7 0 0 0 1.8-.3l.1-.1a2 2 0 0 1 2.8 2.8l-.1.1a1.7 1.7 0 0 0-.3 1.8V9c0 .7.4 1.3 1 1.5H21a2 2 0 1 1 0 4h-.1a1.7 1.7 0 0 0-1.5 1Z"/>',
        'shield' => '<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>',
        'sparkles' => '<path d="M12 3l1.9 4.1L18 9l-4.1 1.9L12 15l-1.9-4.1L6 9l4.1-1.9z"/><path d="M5 3v4"/><path d="M3 5h4"/><path d="M19 17v4"/><path d="M17 19h4"/>',
        'trash-2' => '<path d="M3 6h18"/><path d="M8 6V4h8v2"/><path d="M19 6l-1 14H6L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/>',
        'upload' => '<path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><path d="m7 10 5-5 5 5"/><path d="M12 5v12"/>',
        'user-circle' => '<circle cx="12" cy="12" r="10"/><circle cx="12" cy="10" r="3"/><path d="M7 19a6 6 0 0 1 10 0"/>',
        'users' => '<path d="M16 21v-2a4 4 0 0 0-4-4H7a4 4 0 0 0-4 4v2"/><circle cx="9.5" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>',
    ];

    $markup = $icons[$name] ?? $icons['circle-dot'];
    $classAttr = trim($className) !== '' ? ' class="' . ccms_h($className) . '"' : '';

    return '<svg' . $classAttr
        . ' width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" fill="none"'
        . ' stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"'
        . ' aria-hidden="true" focusable="false">'
        . $markup
        . '</svg>';
}

function ccms_status_icon(string $status, int $size = 14, string $className = 'icon-svg status-icon'): string
{
    $normalized = strtolower(trim($status));
    $icon = match ($normalized) {
        'published', 'contacted', 'sent', 'success' => 'circle-check',
        'draft', 'scheduled', 'reviewed', 'archived' => 'circle-dashed',
        'error', 'failed' => 'alert-triangle',
        default => 'circle-dot',
    };

    return ccms_icon($icon, $size, $className);
}
