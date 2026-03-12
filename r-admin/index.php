<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/src/bootstrap.php';
require_once dirname(__DIR__) . '/src/admin_actions.php';
ccms_send_admin_headers();

if (!ccms_is_installed()) {
    ccms_redirect('/install.php');
}

$data = ccms_load_data();
$flash = ccms_consume_flash();
$currentAdmin = ccms_current_admin();
$pendingTwoFactor = ccms_pending_2fa();
$error = '';

try {
    ccms_admin_handle_post();
} catch (Throwable $e) {
    $error = $e->getMessage();
}

$data = ccms_load_data();
$currentAdmin = ccms_current_admin();
$pendingTwoFactor = ccms_pending_2fa();
$canManageSite = ccms_user_can('site_manage');
$canManageUsers = ccms_user_can('users_manage');
$canManagePages = ccms_user_can('pages_manage');
$canManageMedia = ccms_user_can('media_manage');
$canImportCapsules = ccms_user_can('import_capsules');
$canGenerateAi = ccms_user_can('ai_generate');
$canViewAudit = $canManageUsers;
$canManageBackups = $canManageUsers;
$builderReadOnly = !$canManagePages;
$tab = (string) ($_GET['tab'] ?? ($canGenerateAi ? 'studio' : 'pages'));
if (($tab === 'users' && !$canManageUsers)
    || ($tab === 'site' && !$canManageSite)
    || ($tab === 'extensions' && !$canManageSite)
    || ($tab === 'backups' && !$canManageBackups)
    || ($tab === 'media' && !$canManageMedia)
    || ($tab === 'import' && !$canImportCapsules)
    || ($tab === 'studio' && !$canGenerateAi)
    || ($tab === 'audit' && !$canViewAudit)) {
    $tab = 'pages';
}
$mustChangePassword = !empty($currentAdmin['must_change_password']);
if ($mustChangePassword) {
    $tab = 'account';
}
$selectedPage = null;
$selectedSlug = trim((string) ($_GET['page'] ?? ''));
if ($selectedSlug !== '') {
    foreach ($data['pages'] as $page) {
        if (($page['slug'] ?? '') === $selectedSlug || ($page['id'] ?? '') === $selectedSlug) {
            $selectedPage = $page;
            break;
        }
    }
}
if (!$selectedPage && !empty($data['pages'])) {
    $selectedPage = $data['pages'][0];
}
$csrfToken = ccms_csrf_token();
$menuPages = ccms_menu_pages($data);
$availablePlugins = ccms_discover_plugins();
$previewHtml = $selectedPage ? ccms_admin_preview_html(ccms_render_public_page($data['site'], $selectedPage, $menuPages)) : '';
$selectedRevisions = $selectedPage && is_array($selectedPage['revisions'] ?? null) ? $selectedPage['revisions'] : [];
$storageInfo = ccms_storage_runtime_info();
$aiSettings = ccms_ai_settings($data);
$auditLogs = array_slice(is_array($data['audit_logs'] ?? null) ? $data['audit_logs'] : [], 0, 80);
$totpSetupSecret = $currentAdmin ? ccms_totp_setup_secret() : null;
$resetTokenValue = !$currentAdmin ? trim((string) ($_GET['reset'] ?? '')) : '';
$resetTokenEntry = (!$currentAdmin && $resetTokenValue !== '') ? ccms_find_valid_reset_token($data, $resetTokenValue) : null;
$sectionTemplates = [
    [
        'id' => 'hero',
        'label' => 'Hero principal',
        'category' => 'Intro',
        'html' => '<section style="padding:84px 32px;background:linear-gradient(135deg,#f7f4ee 0%,#ffffff 100%)"><div style="max-width:1040px;margin:0 auto;display:grid;grid-template-columns:1.2fr .8fr;gap:28px;align-items:center"><div><span style="display:inline-block;padding:8px 14px;border-radius:999px;background:#f1e4dc;color:#8b5c4e;font-weight:700">Nuevo bloque</span><h1 style="font-size:56px;line-height:1.02;margin:18px 0 16px">Escribe aquí tu propuesta principal</h1><p style="font-size:18px;line-height:1.7;color:#6b5b53;margin:0 0 20px">Describe qué ofreces y por qué el visitante debería quedarse.</p><a href=\"#contact\" style=\"display:inline-flex;padding:14px 22px;border-radius:999px;background:#c86f5c;color:#fff;text-decoration:none;font-weight:700\">Llamada a la acción</a></div><div><div style=\"background:#fff;border-radius:26px;padding:18px;box-shadow:0 24px 50px -34px rgba(0,0,0,.22)\"><img src=\"https://images.unsplash.com/photo-1520607162513-77705c0f0d4a?auto=format&fit=crop&w=1200&q=80\" alt=\"Imagen principal\" style=\"width:100%;height:420px;object-fit:cover;border-radius:20px\"></div></div></div></section>',
    ],
    [
        'id' => 'intro-two-col',
        'label' => 'Imagen + texto',
        'category' => 'Contenido',
        'html' => '<section style="padding:72px 32px"><div style="max-width:1040px;margin:0 auto;display:grid;grid-template-columns:.9fr 1.1fr;gap:28px;align-items:center"><img src=\"https://images.unsplash.com/photo-1494790108377-be9c29b29330?auto=format&fit=crop&w=1200&q=80\" alt=\"Equipo\" style=\"width:100%;height:420px;object-fit:cover;border-radius:24px\"><div><h2 style=\"font-size:40px;line-height:1.08;margin:0 0 14px\">Cuenta quién está detrás de la marca</h2><p style=\"font-size:18px;line-height:1.75;color:#6b5b53\">Usa este bloque para explicar tu historia, experiencia o enfoque.</p><ul style=\"padding-left:18px;color:#6b5b53;line-height:1.8\"><li>Ventaja 1</li><li>Ventaja 2</li><li>Ventaja 3</li></ul></div></div></section>',
    ],
    [
        'id' => 'cards',
        'label' => 'Tres tarjetas',
        'category' => 'Servicios',
        'html' => '<section style="padding:72px 32px;background:#fff"><div style="max-width:1120px;margin:0 auto"><div style=\"text-align:center;margin-bottom:26px\"><h2 style=\"font-size:40px;line-height:1.08;margin:0 0 10px\">Servicios o beneficios</h2><p style=\"font-size:18px;line-height:1.7;color:#6b5b53;margin:0 auto;max-width:720px\">Presenta aquí lo más importante de tu oferta.</p></div><div style=\"display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:20px\"><article style=\"background:#f9f6f1;border-radius:22px;padding:26px\"><h3 style=\"margin:0 0 10px;font-size:24px\">Tarjeta 1</h3><p style=\"margin:0;color:#6b5b53;line-height:1.75\">Describe este punto clave.</p></article><article style=\"background:#f9f6f1;border-radius:22px;padding:26px\"><h3 style=\"margin:0 0 10px;font-size:24px\">Tarjeta 2</h3><p style=\"margin:0;color:#6b5b53;line-height:1.75\">Describe este punto clave.</p></article><article style=\"background:#f9f6f1;border-radius:22px;padding:26px\"><h3 style=\"margin:0 0 10px;font-size:24px\">Tarjeta 3</h3><p style=\"margin:0;color:#6b5b53;line-height:1.75\">Describe este punto clave.</p></article></div></div></section>',
    ],
    [
        'id' => 'testimonial',
        'label' => 'Testimonio',
        'category' => 'Prueba social',
        'html' => '<section style="padding:72px 32px"><div style="max-width:860px;margin:0 auto;background:#fff6f4;border-radius:28px;padding:34px;box-shadow:0 24px 50px -34px rgba(0,0,0,.18)"><p style=\"font-size:30px;line-height:1.35;margin:0 0 18px;font-weight:700\">“Escribe aquí una opinión potente de un cliente.”</p><p style=\"margin:0;color:#8b5c4e;letter-spacing:.12em;text-transform:uppercase;font-size:13px\">Nombre del cliente · Cargo o empresa</p></div></section>',
    ],
    [
        'id' => 'cta',
        'label' => 'CTA final',
        'category' => 'Conversión',
        'html' => '<section id=\"contact\" style="padding:80px 32px;background:linear-gradient(135deg,#2f241f 0%,#4b3a33 100%);color:#fff"><div style="max-width:980px;margin:0 auto;text-align:center"><h2 style=\"font-size:44px;line-height:1.08;margin:0 0 14px\">Invita al visitante a dar el siguiente paso</h2><p style=\"font-size:18px;line-height:1.75;color:rgba(255,255,255,.82);max-width:720px;margin:0 auto 22px\">Cierra la página con una acción clara: reservar, escribirte, comprar o pedir una propuesta.</p><a href=\"mailto:contacto@tudominio.com\" style=\"display:inline-flex;padding:14px 22px;border-radius:999px;background:#fff;color:#2f241f;text-decoration:none;font-weight:700\">Escríbeme</a></div></section>',
    ],
];
$capsuleBuilderTemplates = [
    ['type' => 'sticky_header', 'label' => 'Sticky Header', 'category' => 'Header', 'props' => ['brand' => 'Brand', 'announcement' => 'New release available now.', 'links' => [['text' => 'Home', 'href' => '#hero'], ['text' => 'Services', 'href' => '#features'], ['text' => 'Contact', 'href' => '#contact']], 'cta_text' => 'Start Project', 'cta_href' => '#contact']],
    ['type' => 'nav', 'label' => 'Navigation', 'category' => 'Header', 'props' => ['brand' => 'Brand', 'links' => [['text' => 'Home', 'href' => '#hero'], ['text' => 'About', 'href' => '#about'], ['text' => 'Contact', 'href' => '#contact']], 'cta_text' => 'Contact', 'cta_href' => '#contact']],
    ['type' => 'banner', 'label' => 'Promo Banner', 'category' => 'Header', 'props' => ['text' => 'Limited offer or important announcement.', 'cta_text' => 'Learn More', 'cta_href' => '#contact']],
    ['type' => 'hero_fullscreen', 'label' => 'Hero Fullscreen', 'category' => 'Hero', 'props' => ['badge' => 'Hero', 'title' => 'A clear promise for the visitor', 'subtitle' => 'Explain the offer in one confident paragraph.', 'background_image' => '', 'cta_primary' => 'Get Started', 'cta_secondary' => 'Learn More', 'cta_href' => '#contact']],
    ['type' => 'hero_split', 'label' => 'Hero Split', 'category' => 'Hero', 'props' => ['badge' => 'Hero', 'title' => 'A premium split hero', 'subtitle' => 'Combine strong copy with a supporting image.', 'image_url' => '', 'cta_primary' => 'Get Started', 'cta_secondary' => 'Learn More', 'cta_href' => '#contact']],
    ['type' => 'features', 'label' => 'Features Grid', 'category' => 'Content', 'props' => ['badge' => 'Features', 'title' => 'What makes this offer valuable', 'subtitle' => 'Summarize your main benefits.', 'items' => [['title' => 'Feature one', 'desc' => 'Short explanation.'], ['title' => 'Feature two', 'desc' => 'Short explanation.'], ['title' => 'Feature three', 'desc' => 'Short explanation.']]]],
    ['type' => 'services_cards', 'label' => 'Services Cards', 'category' => 'Content', 'props' => ['badge' => 'Services', 'title' => 'Main services', 'subtitle' => 'Package the offer into cards.', 'services' => [['title' => 'Service one', 'desc' => 'What is included', 'bullets' => ['Point A', 'Point B'], 'cta_text' => 'Learn more', 'cta_href' => '#contact'], ['title' => 'Service two', 'desc' => 'What is included', 'bullets' => ['Point A', 'Point B'], 'cta_text' => 'Learn more', 'cta_href' => '#contact']]]],
    ['type' => 'split_image_left', 'label' => 'Split Image Left', 'category' => 'Content', 'props' => ['badge' => 'About', 'title' => 'Explain your difference', 'text' => 'Use this section for longer explanatory copy.', 'image_url' => '', 'bullets' => ['Point one', 'Point two', 'Point three']]],
    ['type' => 'split_image_right', 'label' => 'Split Image Right', 'category' => 'Content', 'props' => ['badge' => 'About', 'title' => 'Explain your process', 'text' => 'Use this section for longer explanatory copy.', 'image_url' => '', 'bullets' => ['Point one', 'Point two', 'Point three']]],
    ['type' => 'split_content', 'label' => 'Split Content', 'category' => 'Content', 'props' => ['badge' => 'About', 'title' => 'Why choose this brand', 'text' => 'Text with image seed-based visual.', 'image_seed' => 'studio', 'reversed' => false, 'bullets' => ['Point one', 'Point two']]],
    ['type' => 'text_block', 'label' => 'Rich Text Block', 'category' => 'Content', 'props' => ['badge' => 'Editorial', 'title' => 'Long-form explanation', 'paragraphs' => ['First paragraph.', 'Second paragraph.'], 'quote' => 'A short quote or key statement.', 'quote_author' => 'Brand name']],
    ['type' => 'stats', 'label' => 'Stats', 'category' => 'Proof', 'props' => ['items' => [['value' => '500+', 'label' => 'Clients'], ['value' => '98%', 'label' => 'Satisfaction'], ['value' => '24/7', 'label' => 'Support']]]],
    ['type' => 'testimonial_cards', 'label' => 'Testimonial Cards', 'category' => 'Proof', 'props' => ['badge' => 'Testimonials', 'title' => 'What clients say', 'subtitle' => 'Proof in a clear card grid.', 'items' => [['quote' => 'A strong testimonial.', 'name' => 'Client Name', 'role' => 'Role', 'stars' => 5], ['quote' => 'Another testimonial.', 'name' => 'Client Name', 'role' => 'Role', 'stars' => 5]]]],
    ['type' => 'portfolio_grid', 'label' => 'Portfolio Grid', 'category' => 'Proof', 'props' => ['badge' => 'Selected Work', 'title' => 'Recent projects', 'subtitle' => 'Show representative work.', 'projects' => [['category' => 'Project', 'title' => 'Project one', 'metric' => '+28% results', 'href' => '#contact', 'image' => ''], ['category' => 'Project', 'title' => 'Project two', 'metric' => '+41% results', 'href' => '#contact', 'image' => '']]]],
    ['type' => 'gallery', 'label' => 'Gallery', 'category' => 'Media', 'props' => ['badge' => 'Gallery', 'title' => 'Visual gallery', 'images' => [['url' => '', 'alt' => 'Gallery image 1'], ['url' => '', 'alt' => 'Gallery image 2'], ['url' => '', 'alt' => 'Gallery image 3']]]],
    ['type' => 'blog_grid', 'label' => 'Blog Grid', 'category' => 'Content', 'props' => ['badge' => 'Insights', 'title' => 'Latest articles', 'subtitle' => 'Use this for articles or news.', 'posts' => [['category' => 'Article', 'title' => 'Article title', 'excerpt' => 'Short description', 'author' => 'Author', 'date' => 'March 2026', 'href' => '#contact', 'image' => ''], ['category' => 'Article', 'title' => 'Article title', 'excerpt' => 'Short description', 'author' => 'Author', 'date' => 'March 2026', 'href' => '#contact', 'image' => '']]]],
    ['type' => 'faq', 'label' => 'FAQ', 'category' => 'Proof', 'props' => ['badge' => 'FAQ', 'title' => 'Frequently asked questions', 'items' => [['q' => 'Question one?', 'a' => 'Answer one.'], ['q' => 'Question two?', 'a' => 'Answer two.']]]],
    ['type' => 'pricing', 'label' => 'Pricing', 'category' => 'Conversion', 'props' => ['badge' => 'Pricing', 'title' => 'Simple pricing', 'plans' => [['name' => 'Basic', 'price' => '$29/mo', 'features' => ['Feature 1', 'Feature 2'], 'cta' => 'Get Started', 'highlighted' => false], ['name' => 'Pro', 'price' => '$79/mo', 'features' => ['Feature 1', 'Feature 2', 'Feature 3'], 'cta' => 'Get Started', 'highlighted' => true]]]],
    ['type' => 'cta', 'label' => 'CTA', 'category' => 'Conversion', 'props' => ['title' => 'Ready to take the next step?', 'subtitle' => 'Invite the visitor to act.', 'button_text' => 'Contact us', 'button_href' => '#contact']],
    ['type' => 'lead_form', 'label' => 'Lead Form', 'category' => 'Conversion', 'props' => ['badge' => 'Contact', 'title' => 'Send us a message', 'subtitle' => 'Use this form as your conversion point.', 'button_text' => 'Send', 'privacy_text' => 'We will only use your details to respond.']],
    ['type' => 'newsletter', 'label' => 'Newsletter', 'category' => 'Conversion', 'props' => ['title' => 'Stay in the loop', 'subtitle' => 'Collect email subscribers.', 'placeholder' => 'Enter your email', 'button_text' => 'Subscribe']],
    ['type' => 'map_embed', 'label' => 'Map', 'category' => 'Contact', 'props' => ['badge' => 'Visit us', 'title' => 'Location and contact details', 'subtitle' => 'Show where visitors can find you.', 'embed_url' => '', 'address' => '123 Main Street', 'phone' => '+34 600 000 000', 'email' => 'hello@example.com', 'hours' => 'Mon-Fri 09:00-18:00']],
    ['type' => 'footer_multi', 'label' => 'Footer', 'category' => 'Footer', 'props' => ['brand' => 'Brand', 'description' => 'Use the footer to close the page with links and contact details.', 'columns' => [['title' => 'Company', 'links' => [['text' => 'About', 'href' => '#about'], ['text' => 'Contact', 'href' => '#contact']]]], 'contact_lines' => ['hello@example.com', '+34 600 000 000', 'Madrid, Spain'], 'copyright' => '© 2026 Brand. All rights reserved.']],
];
$sectionTemplatesJson = json_encode($sectionTemplates, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
$capsuleBuilderTemplatesJson = json_encode($capsuleBuilderTemplates, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
$mediaItemsJson = json_encode($data['media'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
$previewSiteConfigJson = json_encode([
    'site' => $data['site'],
    'menu' => array_map(static function (array $page): array {
        return [
            'slug' => (string) ($page['slug'] ?? ''),
            'label' => trim((string) ($page['menu_label'] ?? '')) ?: (string) ($page['title'] ?? 'Untitled'),
            'is_homepage' => !empty($page['is_homepage']),
        ];
    }, $menuPages),
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
$selectedCapsuleStateJson = json_encode($selectedPage ? (ccms_capsule_decode($selectedPage) ?? [
    'meta' => ['business_name' => (string) ($selectedPage['title'] ?? 'Untitled')],
    'style' => [],
    'blocks' => [],
]) : ['meta' => ['business_name' => 'Untitled'], 'style' => [], 'blocks' => []], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>r-admin | LinuxCMS</title>
  <link rel="stylesheet" href="<?= htmlspecialchars(ccms_base_url() . '/r-admin/assets/admin.css', ENT_QUOTES, 'UTF-8') ?>">
</head>
<body>
  <div class="shell">
    <?php if (!$currentAdmin): ?>
      <?php require __DIR__ . '/views/auth_shell.php'; ?>
    <?php else: ?>
      <?php require __DIR__ . '/views/admin_chrome.php'; ?>

      <?php require __DIR__ . '/views/admin_tabs.php'; ?>
    <?php endif; ?>
  </div>

  <?php if ($currentAdmin): ?>
  <script<?= ccms_script_nonce_attr() ?>>
    window.CCMS_ADMIN_BOOTSTRAP = {
      sectionTemplates: <?= $sectionTemplatesJson ?: '[]' ?>,
      capsuleBuilderTemplates: <?= $capsuleBuilderTemplatesJson ?: '[]' ?>,
      initialCapsuleState: <?= $selectedCapsuleStateJson ?: '{"meta":{},"style":{},"blocks":[]}' ?>,
      mediaItems: <?= $mediaItemsJson ?: '[]' ?>,
      previewSiteConfig: <?= $previewSiteConfigJson ?: '{"site":{},"menu":[]}' ?>,
      builderReadOnly: <?= $builderReadOnly ? 'true' : 'false' ?>
    };
  </script>
  <script src="<?= htmlspecialchars(ccms_base_url() . '/r-admin/assets/admin.js', ENT_QUOTES, 'UTF-8') ?>"></script>
  <?php endif; ?>
</body>
</html>
    function normalizeInsertIndex(index) {
      const max = Array.isArray(capsuleState.blocks) ? capsuleState.blocks.length : 0;
      if (!Number.isFinite(index)) return max;
      return Math.max(0, Math.min(Number(index), max));
    }

    function setPendingInsertIndex(index, options = {}) {
      const { render = true } = options;
      pendingInsertIndex = normalizeInsertIndex(index);
      if (render) {
        renderBuilderTemplateLibrary();
        renderBuilderContext();
        renderBuilderBlocks();
      }
    }
