<?php
declare(strict_types=1);

ccms_register_plugin_hook('public_head_end', static function (array $context): string {
    return '<style id="ccms-plugin-announcement-chip">
      .ccms-plugin-announcement-chip{
        position:fixed;
        right:18px;
        bottom:18px;
        z-index:90;
        display:inline-flex;
        align-items:center;
        gap:8px;
        padding:12px 16px;
        border-radius:999px;
        background:rgba(47,36,31,.94);
        color:#fff;
        box-shadow:0 24px 40px -26px rgba(0,0,0,.35);
        font:700 12px/1.2 Inter,Arial,Helvetica,sans-serif;
        letter-spacing:.04em;
        text-transform:uppercase;
      }
      .ccms-plugin-announcement-chip strong{font-size:11px;color:rgba(255,255,255,.72)}
      @media (max-width:800px){
        .ccms-plugin-announcement-chip{left:14px;right:14px;bottom:14px;justify-content:center}
      }
    </style>';
});

ccms_register_plugin_hook('public_body_end', static function (array $context): string {
    $site = is_array($context['site'] ?? null) ? $context['site'] : [];
    $label = trim((string) ($site['title'] ?? 'LinuxCMS'));
    return '<div data-ccms-plugin="announcement-chip" class="ccms-plugin-announcement-chip"><strong>Live site</strong><span>' . ccms_h($label) . '</span></div>';
});

return [
    'slug' => 'announcement-chip',
    'name' => 'Announcement Chip',
    'version' => '1.0.0',
    'description' => 'Adds a small floating announcement badge to the public site.',
];
