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
  <style>
    :root{
      --bg:#f3eee8;
      --panel:#ffffff;
      --panel-alt:#faf7f2;
      --text:#2f241f;
      --muted:#6b5b53;
      --line:#e7ddd4;
      --primary:#c86f5c;
      --primary-dark:#ab5d4e;
      --secondary:#d9c4b3;
      --success:#1e8f61;
      --danger:#b34b44;
      --shadow:0 28px 60px -38px rgba(43,26,18,.28);
      --radius-xl:28px;
      --radius-lg:20px;
      --radius-md:16px;
      --max:1440px;
    }
    *{box-sizing:border-box}
    html,body{margin:0;padding:0;background:var(--bg);color:var(--text);font-family:Inter,Arial,Helvetica,sans-serif}
    a{color:inherit}
    .shell{width:min(var(--max),calc(100% - 28px));margin:0 auto;padding:24px 0 42px}
    .card{background:var(--panel);border:1px solid var(--line);border-radius:var(--radius-xl);box-shadow:var(--shadow)}
    .stack{display:grid;gap:18px}
    .topbar{display:flex;justify-content:space-between;align-items:flex-start;gap:16px;margin-bottom:18px}
    .topbar h1{margin:0;font-size:36px;line-height:1}
    .muted{color:var(--muted)}
    .toolbar{display:flex;flex-wrap:wrap;gap:10px}
    .btn{display:inline-flex;align-items:center;justify-content:center;gap:8px;min-height:44px;padding:11px 18px;border:0;border-radius:999px;background:linear-gradient(135deg,var(--primary) 0%,var(--secondary) 100%);color:#fff;font-weight:800;text-decoration:none;cursor:pointer}
    .btn:hover{filter:brightness(.98)}
    .btn-secondary{background:#fff;color:var(--text);border:1px solid var(--line)}
    .btn-ghost{background:var(--panel-alt);color:var(--text)}
    .btn-danger{background:var(--danger)}
    .flash{padding:14px 16px;border-radius:16px;margin-bottom:16px}
    .flash.success{background:#eaf7f0;color:#1b5a3c}
    .flash.error{background:#fde9e7;color:#8e3c34}
    .client-mode-banner{display:none;align-items:flex-start;justify-content:space-between;gap:14px;padding:16px 18px;border-radius:20px;background:#fff7f4;border:1px solid rgba(200,111,92,.24);margin:0 0 18px}
    .client-mode-banner strong{display:block;margin-bottom:4px}
    .nav-tabs{display:flex;flex-wrap:wrap;gap:10px;margin:0 0 20px}
    .nav-tabs a{display:inline-flex;align-items:center;gap:8px;padding:12px 16px;border-radius:999px;background:#ece4da;color:var(--text);text-decoration:none;font-weight:800}
    .nav-tabs a.active{background:var(--text);color:#fff}
    .icon-dot{width:10px;height:10px;border-radius:999px;background:currentColor;opacity:.55}
    .field{display:grid;gap:8px}
    .field + .field{margin-top:14px}
    label{font-size:13px;font-weight:800;letter-spacing:.02em;text-transform:uppercase;color:var(--muted)}
    input,textarea,select{width:100%;padding:12px 14px;border:1px solid var(--line);border-radius:16px;font:inherit;background:#fff;color:var(--text)}
    textarea{min-height:140px;resize:vertical}
    .split-2{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px}
    .split-3{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:14px}
    .small{font-size:13px;color:var(--muted);line-height:1.6}
    .pages-layout{display:grid;grid-template-columns:320px minmax(0,1fr);gap:20px}
    .sidebar-card{padding:18px}
    .sidebar-card h2,.editor-card h2{margin:0 0 12px;font-size:20px}
    .page-list{display:grid;gap:10px;max-height:420px;overflow:auto;padding-right:4px}
    .page-item{display:block;padding:15px;border-radius:18px;border:1px solid var(--line);text-decoration:none;background:#fff}
    .page-item strong{display:block;font-size:16px;margin-bottom:4px}
    .page-item.active{border-color:rgba(200,111,92,.45);background:#fff7f4;box-shadow:0 18px 40px -32px rgba(200,111,92,.42)}
    .page-item .status{display:inline-flex;align-items:center;gap:6px;font-size:12px;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:.08em}
    .workspace{display:grid;gap:18px}
    .editor-card{padding:20px}
    .editor-header{display:flex;align-items:flex-start;justify-content:space-between;gap:14px;margin-bottom:16px}
    .editor-title{display:grid;gap:8px}
    .editor-title h2{margin:0;font-size:28px}
    .editor-meta{display:flex;flex-wrap:wrap;gap:8px}
    .chip{display:inline-flex;align-items:center;gap:8px;padding:8px 12px;border-radius:999px;background:#f1e7de;color:var(--muted);font-size:12px;font-weight:800;text-transform:uppercase;letter-spacing:.08em}
    .editor-layout{display:grid;grid-template-columns:minmax(0,1.25fr) 360px;gap:18px}
    .editor-main{display:grid;gap:16px}
    .metabox{padding:18px;border-radius:22px;border:1px solid var(--line);background:#fff}
    .metabox h3{margin:0 0 12px;font-size:18px}
    .subtabs{display:flex;flex-wrap:wrap;gap:10px;margin-bottom:14px}
    .subtabs button{display:inline-flex;align-items:center;gap:8px;padding:10px 14px;border-radius:999px;border:1px solid var(--line);background:#fff;font:inherit;font-weight:800;color:var(--muted);cursor:pointer}
    .subtabs button.active{background:var(--text);border-color:var(--text);color:#fff}
    .subpanel{display:none}
    .subpanel.active{display:grid;gap:14px}
    .section-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:12px}
    .section-card{display:grid;gap:8px;padding:14px;border-radius:18px;border:1px solid var(--line);background:var(--panel-alt)}
    .section-card strong{font-size:15px}
    .section-card span{font-size:12px;font-weight:800;color:var(--muted);text-transform:uppercase;letter-spacing:.08em}
    .section-card button{margin-top:auto}
    .builder-layout{display:grid;gap:16px}
    .builder-toolbar{display:flex;flex-wrap:wrap;gap:10px;align-items:center;justify-content:space-between}
    .builder-stats{display:flex;flex-wrap:wrap;gap:8px}
    .builder-context{display:grid;gap:12px;padding:18px;border-radius:22px;border:1px solid var(--line);background:#fff7f4}
    .builder-context-head{display:flex;flex-wrap:wrap;align-items:flex-start;justify-content:space-between;gap:12px}
    .builder-context-title{display:grid;gap:6px}
    .builder-context-title strong{font-size:20px}
    .builder-context-title .small strong{font-size:inherit}
    .builder-context-actions{display:flex;flex-wrap:wrap;gap:8px}
    .builder-list{display:grid;gap:14px}
    .builder-insert-slot{display:grid;justify-items:center;gap:8px;padding:6px 0}
    .builder-insert-slot button{display:inline-flex;align-items:center;gap:8px;padding:10px 14px;border-radius:999px;border:1px dashed var(--line);background:#fffaf7;color:var(--muted);font:inherit;font-weight:800;cursor:pointer;transition:.18s ease}
    .builder-insert-slot button:hover{border-color:rgba(200,111,92,.45);background:#fff2ec;color:var(--ink)}
    .builder-insert-slot.is-active button{border-style:solid;border-color:rgba(200,111,92,.65);background:#fff0e9;color:var(--ink);box-shadow:0 12px 24px -20px rgba(200,111,92,.5)}
    .builder-insert-slot .small{font-size:12px;color:var(--muted)}
    .builder-block{padding:18px;border-radius:22px;border:1px solid var(--line);background:#fff;display:grid;gap:14px}
    .builder-block.is-selected{border-color:rgba(200,111,92,.55);box-shadow:0 20px 40px -30px rgba(200,111,92,.45)}
    .builder-block-header{display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:12px}
    .builder-block-title{display:grid;gap:6px}
    .builder-block-title strong{font-size:18px}
    .builder-actions{display:flex;flex-wrap:wrap;gap:8px}
    .builder-block-body{display:none;gap:14px}
    .builder-block.is-selected .builder-block-body{display:grid}
    .builder-block-summary{display:grid;gap:6px;padding:12px 14px;border-radius:16px;background:#fcfaf7;color:var(--muted);font-size:14px;line-height:1.65}
    .builder-block.is-selected .builder-block-summary{display:none}
    .builder-fields{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px}
    .builder-fields .field{margin:0}
    .builder-full{grid-column:1 / -1}
    .builder-json{min-height:150px;font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,monospace;font-size:13px;line-height:1.55}
    .builder-empty{padding:22px;border-radius:20px;border:1px dashed var(--line);background:var(--panel-alt);color:var(--muted)}
    .builder-library{display:grid;gap:14px}
    .builder-library-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(190px,1fr));gap:12px}
    .builder-template{display:grid;gap:8px;padding:14px;border-radius:18px;border:1px solid var(--line);background:#fff}
    .builder-template span{font-size:12px;font-weight:800;letter-spacing:.08em;text-transform:uppercase;color:var(--muted)}
    .builder-note{padding:14px 16px;border-radius:18px;background:#f8f4ef;border:1px solid var(--line);font-size:14px;color:var(--muted);line-height:1.7}
    .builder-surface{display:grid;gap:14px;padding:18px;border-radius:22px;border:1px solid var(--line);background:#fff}
    .builder-surface h3{margin:0;font-size:18px}
    .builder-style-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px}
    .builder-style-grid .field{margin:0}
    .builder-subsection{display:grid;gap:12px;padding-top:14px;border-top:1px dashed var(--line)}
    .builder-subsection h4{margin:0;font-size:13px;letter-spacing:.08em;text-transform:uppercase;color:var(--muted)}
    .builder-repeater{display:grid;gap:12px}
    .builder-repeater-toolbar{display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap}
    .builder-repeater-list{display:grid;gap:12px}
    .builder-repeater-card{display:grid;gap:12px;padding:14px;border:1px solid var(--line);border-radius:18px;background:var(--panel-alt)}
    .builder-repeater-card[draggable="true"]{cursor:grab}
    .builder-repeater-card.is-dragging{opacity:.45;transform:scale(.995)}
    .builder-repeater-card.is-drop-target{border-color:var(--primary);box-shadow:0 0 0 2px rgba(200,111,92,.16)}
    .builder-repeater-card-header{display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap}
    .builder-repeater-card-title{display:grid;gap:4px}
    .builder-repeater-card-title strong{font-size:15px}
    .builder-repeater-card-actions{display:flex;flex-wrap:wrap;gap:8px}
    .builder-repeater-fields{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px}
    .builder-inline-list{display:grid;gap:10px}
    .builder-inline-item{display:grid;grid-template-columns:minmax(0,1fr) auto;gap:8px;align-items:center}
    .builder-inline-actions{display:flex;gap:8px;flex-wrap:wrap}
    .builder-media-picker{display:grid;gap:10px;margin-top:10px;padding:12px;border:1px solid var(--line);border-radius:16px;background:#fcfaf7}
    .builder-media-picker summary{cursor:pointer;font-weight:700;color:var(--ink)}
    .builder-media-picker summary::-webkit-details-marker{display:none}
    .builder-media-picker-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(92px,1fr));gap:10px}
    .builder-media-option{display:grid;gap:8px;padding:8px;border-radius:14px;border:1px solid var(--line);background:#fff;text-align:left}
    .builder-media-option img{width:100%;height:72px;object-fit:cover;border-radius:10px;background:#efe7de}
    .builder-media-option span{font-size:11px;line-height:1.35;color:var(--muted);overflow:hidden;text-overflow:ellipsis;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical}
    .builder-media-empty{font-size:13px;color:var(--muted);line-height:1.6}
    .media-modal-backdrop{position:fixed;inset:0;background:rgba(15,23,42,.55);backdrop-filter:blur(4px);display:none;align-items:center;justify-content:center;padding:24px;z-index:2000}
    .media-modal-backdrop.is-open{display:flex}
    .media-modal{width:min(920px,100%);max-height:min(82vh,900px);display:grid;grid-template-rows:auto auto minmax(0,1fr) auto;gap:16px;padding:20px;border-radius:24px;border:1px solid var(--line);background:#fff;box-shadow:0 26px 80px rgba(15,23,42,.22)}
    .media-modal-head{display:flex;align-items:start;justify-content:space-between;gap:16px}
    .media-modal-head h3{margin:0;font-size:24px}
    .media-modal-head p{margin:6px 0 0;color:var(--muted);font-size:14px;line-height:1.6}
    .media-modal-toolbar{display:grid;grid-template-columns:minmax(0,1fr) auto;gap:12px;align-items:center}
    .media-modal-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(150px,1fr));gap:12px;overflow:auto;padding-right:4px}
    .media-modal-card{display:grid;gap:10px;padding:10px;border-radius:18px;border:1px solid var(--line);background:#fcfaf7;text-align:left;cursor:pointer}
    .media-modal-card img{width:100%;height:112px;object-fit:cover;border-radius:12px;background:#efe7de}
    .media-modal-card span{font-size:12px;line-height:1.45;color:var(--muted);overflow:hidden;text-overflow:ellipsis;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical}
    .media-modal-empty{padding:24px;border-radius:18px;border:1px dashed var(--line);background:#fcfaf7;color:var(--muted);line-height:1.7}
    .media-modal-footer{display:flex;justify-content:space-between;gap:12px;flex-wrap:wrap;align-items:center}
    .html-editor{min-height:520px;font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,monospace;font-size:14px;line-height:1.6}
    .preview-frame{width:100%;min-height:760px;border:1px solid var(--line);border-radius:22px;background:#fff}
    .preview-actions{display:flex;flex-wrap:wrap;gap:10px;margin-top:12px}
    .client-quick-actions{display:none;flex-wrap:wrap;gap:10px;margin-bottom:16px}
    .client-quick-actions .btn{min-height:40px;padding:10px 16px}
    .quickstart-guide{display:grid;gap:14px;padding:18px;border-radius:22px;border:1px solid var(--line);background:linear-gradient(180deg,#fffaf7 0%,#fff 100%)}
    .quickstart-head{display:grid;gap:6px}
    .quickstart-head h3{margin:0;font-size:20px}
    .quickstart-head p{margin:0;color:var(--muted);line-height:1.65}
    .quickstart-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:12px}
    .quickstart-step{display:grid;gap:10px;padding:14px;border-radius:18px;border:1px solid var(--line);background:#fff;box-shadow:0 12px 30px -24px rgba(47,36,31,.18)}
    .quickstart-step-number{display:inline-flex;align-items:center;justify-content:center;width:30px;height:30px;border-radius:999px;background:#f1e7de;color:var(--text);font-size:13px;font-weight:800}
    .quickstart-step strong{font-size:15px}
    .quickstart-step p{margin:0;font-size:13px;line-height:1.6;color:var(--muted)}
    .preview-helper{display:grid;gap:10px;margin:0 0 14px;padding:14px 16px;border-radius:18px;border:1px solid var(--line);background:#fcfaf7}
    .preview-helper-head{display:grid;gap:4px}
    .preview-helper-head strong{font-size:15px}
    .preview-helper-head span{font-size:13px;color:var(--muted);line-height:1.6}
    .preview-helper-list{display:grid;gap:8px}
    .preview-helper-item{display:flex;align-items:flex-start;gap:10px;font-size:13px;line-height:1.6;color:var(--muted)}
    .preview-helper-item b{color:var(--text)}
    .media-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(160px,1fr));gap:12px}
    .media-card{display:grid;gap:10px;padding:12px;border-radius:18px;border:1px solid var(--line);background:#fff}
    .media-card img{width:100%;height:120px;object-fit:cover;border-radius:14px;background:#f1ece6}
    .color-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(190px,1fr));gap:12px}
    .color-field{display:grid;gap:8px;padding:14px;border-radius:18px;border:1px solid var(--line);background:#fff}
    .color-pair{display:grid;grid-template-columns:56px 1fr;gap:10px;align-items:center}
    .color-pair input[type=color]{width:56px;height:44px;padding:0;border-radius:14px;border:1px solid var(--line);background:#fff}
    .check-grid{display:grid;gap:10px}
    .check{display:flex;align-items:center;gap:10px;padding:12px 14px;border:1px solid var(--line);border-radius:16px;background:#fff}
    .check input{width:auto}
    .import-layout,.site-layout,.media-layout{display:grid;gap:18px}
    .help-box{padding:16px 18px;border-radius:18px;background:#f8f4ef;border:1px solid var(--line)}
    .help-box h4{margin:0 0 8px}
    .help-box ul{margin:0;padding-left:18px;color:var(--muted);line-height:1.7}
    .sticky-actions{position:sticky;top:16px}
    body.client-mode .advanced-only{display:none !important}
    body.client-mode .client-mode-banner{display:flex}
    body.client-mode .client-quick-actions{display:flex}
    body.client-mode .editor-layout{grid-template-columns:minmax(0,1fr) 320px}
    body.client-mode .builder-context{background:#fffdf9}
    body.client-mode .builder-context-actions .btn-secondary{background:#fff}
    @media (max-width:1180px){
      .editor-layout{grid-template-columns:1fr}
    }
    @media (max-width:980px){
      .pages-layout,.split-2,.split-3{grid-template-columns:1fr}
      .builder-fields{grid-template-columns:1fr}
      .builder-style-grid{grid-template-columns:1fr}
      .builder-repeater-fields{grid-template-columns:1fr}
      .quickstart-grid{grid-template-columns:repeat(2,minmax(0,1fr))}
      .topbar,.editor-header{flex-direction:column}
      .preview-frame{min-height:520px}
      .media-modal-toolbar{grid-template-columns:1fr}
    }
    @media (max-width:640px){
      .quickstart-grid{grid-template-columns:1fr}
    }
  </style>
</head>
<body>
  <div class="shell">
    <?php if (!$currentAdmin): ?>
      <div class="card" style="max-width:520px;margin:40px auto;padding:26px">
        <p class="muted"><strong>LinuxCMS</strong></p>
        <h1 style="margin:0 0 12px;font-size:42px;line-height:1"><?= $pendingTwoFactor ? 'Verificación en dos pasos' : ($resetTokenEntry ? 'Restablecer contraseña' : 'Entrar al panel') ?></h1>
        <p class="muted">
          <?php if ($pendingTwoFactor): ?>
            Introduce el código de 6 dígitos de tu app de autenticación para completar el acceso.
          <?php elseif ($resetTokenEntry): ?>
            Elige una nueva contraseña segura para recuperar esta cuenta.
          <?php else: ?>
            Usa tu usuario y contraseña para editar páginas, colores, medios y contenido publicado.
          <?php endif; ?>
        </p>
        <?php if ($flash): ?><div class="flash <?= ccms_h($flash['type']) ?>"><?= ccms_h($flash['message']) ?></div><?php endif; ?>
        <?php if ($error !== ''): ?><div class="flash error"><?= ccms_h($error) ?></div><?php endif; ?>
        <?php if ($pendingTwoFactor): ?>
          <form method="post">
            <input type="hidden" name="action" value="verify_2fa">
            <input type="hidden" name="csrf_token" value="<?= ccms_h(ccms_csrf_token()) ?>">
            <div class="field">
              <label for="totp_code">Código 2FA</label>
              <input id="totp_code" name="totp_code" inputmode="numeric" autocomplete="one-time-code" pattern="[0-9]{6}" maxlength="6" required>
            </div>
            <button class="btn" type="submit">Validar y entrar</button>
          </form>
        <?php elseif ($resetTokenValue !== ''): ?>
          <?php if ($resetTokenEntry): ?>
            <form method="post">
              <input type="hidden" name="action" value="complete_password_reset">
              <input type="hidden" name="csrf_token" value="<?= ccms_h(ccms_csrf_token()) ?>">
              <input type="hidden" name="reset_token" value="<?= ccms_h($resetTokenValue) ?>">
              <div class="field">
                <label for="new_password">Nueva contraseña</label>
                <input id="new_password" name="new_password" type="password" minlength="10" required>
              </div>
              <div class="field">
                <label for="confirm_new_password">Repite la nueva contraseña</label>
                <input id="confirm_new_password" name="confirm_new_password" type="password" minlength="10" required>
              </div>
              <button class="btn" type="submit">Guardar contraseña</button>
            </form>
          <?php else: ?>
            <div class="flash error">El enlace de recuperación no es válido o ha caducado.</div>
            <a class="btn btn-secondary" href="/r-admin/">Volver al acceso</a>
          <?php endif; ?>
        <?php else: ?>
          <form method="post">
            <input type="hidden" name="action" value="login">
            <div class="field">
              <label for="username">Usuario</label>
              <input id="username" name="username" required>
            </div>
            <div class="field">
              <label for="password">Contraseña</label>
              <input id="password" name="password" type="password" required>
            </div>
            <button class="btn" type="submit">Entrar</button>
          </form>
        <?php endif; ?>
      </div>
    <?php else: ?>
      <header class="topbar">
        <div>
          <h1>r-admin</h1>
          <p class="muted" style="margin:8px 0 0">Bienvenido, <?= ccms_h($currentAdmin['username']) ?>. Este panel ya se comporta más como un pequeño WordPress para hosting genérico.</p>
          <div class="editor-meta" style="margin-top:10px">
            <span class="chip">Rol · <?= ccms_h(strtoupper((string) ($currentAdmin['role'] ?? 'OWNER'))) ?></span>
            <?php if ($canManageUsers): ?><span class="chip">Acceso completo</span><?php elseif ($canManagePages): ?><span class="chip">Editor</span><?php else: ?><span class="chip">Solo lectura</span><?php endif; ?>
          </div>
        </div>
        <div class="toolbar">
          <a class="btn btn-secondary" href="/">Abrir web</a>
          <button class="btn btn-secondary" type="button" id="clientModeToggle" aria-pressed="false">Modo cliente</button>
          <?php if ($canGenerateAi): ?><a class="btn btn-secondary advanced-only" href="?tab=studio">Studio local</a><?php endif; ?>
          <?php if ($canManageSite): ?><a class="btn btn-secondary advanced-only" href="?tab=extensions">Extensiones</a><?php endif; ?>
          <?php if ($canManageBackups): ?><a class="btn btn-secondary advanced-only" href="?tab=backups">Backups</a><?php endif; ?>
          <?php if ($canManageMedia): ?><a class="btn btn-secondary" href="?tab=media">Media</a><?php endif; ?>
          <?php if ($canImportCapsules): ?><a class="btn btn-secondary advanced-only" href="?tab=import">Importar</a><?php endif; ?>
          <?php if ($canManageUsers): ?><a class="btn btn-secondary advanced-only" href="?tab=users">Usuarios</a><?php endif; ?>
          <a class="btn btn-secondary" href="/r-admin/logout.php">Salir</a>
        </div>
      </header>

      <?php if ($flash): ?><div class="flash <?= ccms_h($flash['type']) ?>"><?= ccms_h($flash['message']) ?></div><?php endif; ?>
      <?php if ($error !== ''): ?><div class="flash error"><?= ccms_h($error) ?></div><?php endif; ?>
      <div class="client-mode-banner" id="clientModeBanner">
        <div>
          <strong>Modo cliente activado</strong>
          <div class="small">Se muestran solo las acciones clave para editar textos, fotos, colores y publicar. Si necesitas todo el panel, cambia a <strong>Modo avanzado</strong>.</div>
        </div>
        <button class="btn btn-secondary" type="button" id="clientModeBannerToggle">Cambiar a modo avanzado</button>
      </div>

      <nav class="nav-tabs">
        <?php if ($canGenerateAi): ?><a class="advanced-only <?= $tab === 'studio' ? 'active' : '' ?>" href="/r-admin/?tab=studio"><span class="icon-dot"></span>Studio</a><?php endif; ?>
        <a class="<?= $tab === 'pages' ? 'active' : '' ?>" href="/r-admin/?tab=pages"><span class="icon-dot"></span>Páginas</a>
        <a class="<?= $tab === 'account' ? 'active' : '' ?>" href="/r-admin/?tab=account"><span class="icon-dot"></span>Cuenta</a>
        <?php if ($canManageSite): ?><a class="<?= $tab === 'site' ? 'active' : '' ?>" href="/r-admin/?tab=site"><span class="icon-dot"></span>Sitio</a><?php endif; ?>
        <?php if ($canManageSite): ?><a class="advanced-only <?= $tab === 'extensions' ? 'active' : '' ?>" href="/r-admin/?tab=extensions"><span class="icon-dot"></span>Extensiones</a><?php endif; ?>
        <?php if ($canManageBackups): ?><a class="advanced-only <?= $tab === 'backups' ? 'active' : '' ?>" href="/r-admin/?tab=backups"><span class="icon-dot"></span>Backups</a><?php endif; ?>
        <?php if ($canManageMedia): ?><a class="<?= $tab === 'media' ? 'active' : '' ?>" href="/r-admin/?tab=media"><span class="icon-dot"></span>Media</a><?php endif; ?>
        <?php if ($canImportCapsules): ?><a class="advanced-only <?= $tab === 'import' ? 'active' : '' ?>" href="/r-admin/?tab=import"><span class="icon-dot"></span>Importar cápsula</a><?php endif; ?>
        <?php if ($canManageUsers): ?><a class="advanced-only <?= $tab === 'users' ? 'active' : '' ?>" href="/r-admin/?tab=users"><span class="icon-dot"></span>Usuarios</a><?php endif; ?>
        <?php if ($canViewAudit): ?><a class="advanced-only <?= $tab === 'audit' ? 'active' : '' ?>" href="/r-admin/?tab=audit"><span class="icon-dot"></span>Auditoría</a><?php endif; ?>
      </nav>

      <?php if ($mustChangePassword): ?>
        <div class="flash error">Tu cuenta usa una contraseña temporal. Debes cambiarla ahora antes de editar páginas o configuración.</div>
      <?php endif; ?>

      <?php if ($tab === 'account'): ?>
        <div class="pages-layout">
          <aside class="stack">
            <div class="card sidebar-card">
              <h2>Tu cuenta</h2>
              <p class="small">Gestiona tu acceso al panel. Si acabas de recibir una contraseña temporal, cámbiala aquí primero.</p>
              <div class="small"><strong>Usuario:</strong> <?= ccms_h((string) ($currentAdmin['username'] ?? '')) ?></div>
              <div class="small"><strong>Email:</strong> <?= ccms_h((string) ($currentAdmin['email'] ?? '')) ?></div>
              <div class="small"><strong>Rol:</strong> <?= ccms_h((string) ($currentAdmin['role'] ?? '')) ?></div>
              <div class="small"><strong>Último acceso:</strong> <?= ccms_h((string) ($currentAdmin['last_login_at'] ?? 'Sin registrar')) ?></div>
              <div class="small"><strong>2FA:</strong> <?= !empty($currentAdmin['totp_enabled']) ? 'Activado' : 'Desactivado' ?></div>
            </div>
          </aside>
          <section class="workspace">
            <div class="card editor-card">
              <div class="editor-header">
                <div class="editor-title">
                  <div class="chip">Cuenta</div>
                  <h2>Cambiar contraseña</h2>
                  <p class="muted" style="margin:0">Usa una contraseña fuerte. Si tu cuenta fue creada por otra persona, esta es la primera acción que deberías hacer.</p>
                </div>
              </div>
              <form method="post" class="stack" style="max-width:560px">
                <input type="hidden" name="action" value="change_own_password">
                <input type="hidden" name="csrf_token" value="<?= ccms_h($csrfToken) ?>">
                <div class="field"><label>Nueva contraseña</label><input name="new_password" type="password" required></div>
                <div class="field"><label>Repite la nueva contraseña</label><input name="confirm_new_password" type="password" required></div>
                <div class="toolbar">
                  <button class="btn" type="submit">Guardar nueva contraseña</button>
                </div>
              </form>
            </div>
            <div class="card editor-card">
              <div class="editor-header">
                <div class="editor-title">
                  <div class="chip">2FA</div>
                  <h2>Autenticación en dos pasos</h2>
                  <p class="muted" style="margin:0">Añade una capa extra de seguridad con una app tipo Google Authenticator, 1Password o Authy.</p>
                </div>
              </div>
              <?php if (!empty($currentAdmin['totp_enabled'])): ?>
                <div class="help-box">
                  <h4>2FA activado</h4>
                  <p class="small">Tu cuenta ya requiere un código adicional al iniciar sesión.</p>
                </div>
                <form method="post" class="stack" style="max-width:560px">
                  <input type="hidden" name="action" value="disable_totp">
                  <input type="hidden" name="csrf_token" value="<?= ccms_h($csrfToken) ?>">
                  <div class="field"><label>Contraseña actual</label><input name="current_password" type="password" required></div>
                  <div class="toolbar">
                    <button class="btn btn-danger" type="submit">Desactivar 2FA</button>
                  </div>
                </form>
              <?php else: ?>
                <?php if ($totpSetupSecret): ?>
                  <div class="help-box">
                    <h4>Configura tu app</h4>
                    <p class="small">Añade esta clave manualmente en tu app de autenticación y escribe un código de 6 dígitos para confirmar.</p>
                    <div class="small"><strong>Clave secreta:</strong> <code><?= ccms_h($totpSetupSecret) ?></code></div>
                    <div class="small" style="margin-top:8px;word-break:break-all"><strong>URI:</strong> <code><?= ccms_h(ccms_totp_otpauth_uri($currentAdmin, $totpSetupSecret)) ?></code></div>
                  </div>
                  <form method="post" class="stack" style="max-width:560px">
                    <input type="hidden" name="action" value="enable_totp">
                    <input type="hidden" name="csrf_token" value="<?= ccms_h($csrfToken) ?>">
                    <div class="field"><label>Código de verificación</label><input name="totp_code" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" required></div>
                    <div class="toolbar">
                      <button class="btn" type="submit">Activar 2FA</button>
                    </div>
                  </form>
                  <form method="post" style="margin-top:10px">
                    <input type="hidden" name="action" value="cancel_totp_setup">
                    <input type="hidden" name="csrf_token" value="<?= ccms_h($csrfToken) ?>">
                    <button class="btn btn-secondary" type="submit">Cancelar configuración</button>
                  </form>
                <?php else: ?>
                  <div class="help-box">
                    <h4>2FA desactivado</h4>
                    <p class="small">Actívalo para que el acceso al panel requiera tu contraseña y un código temporal de 6 dígitos.</p>
                  </div>
                  <form method="post">
                    <input type="hidden" name="action" value="start_totp_setup">
                    <input type="hidden" name="csrf_token" value="<?= ccms_h($csrfToken) ?>">
                    <button class="btn" type="submit">Empezar configuración 2FA</button>
                  </form>
                <?php endif; ?>
              <?php endif; ?>
            </div>
          </section>
        </div>
      <?php elseif ($tab === 'studio'): ?>
        <div class="pages-layout">
          <aside class="stack">
            <div class="card sidebar-card">
              <h2>LM Studio local</h2>
              <form method="post" class="stack">
                <input type="hidden" name="action" value="save_ai_settings">
                <input type="hidden" name="csrf_token" value="<?= ccms_h($csrfToken) ?>">
                <div class="field"><label>Endpoint</label><input name="ai_endpoint" value="<?= ccms_h((string) $aiSettings['endpoint']) ?>" placeholder="http://127.0.0.1:1234/v1"></div>
                <div class="field"><label>Modelo</label><input name="ai_model" value="<?= ccms_h((string) $aiSettings['model']) ?>" placeholder="Déjalo vacío para usar el primero disponible"></div>
                <div class="split-2">
                  <div class="field"><label>Temperature</label><input name="ai_temperature" type="number" min="0" max="1.2" step="0.1" value="<?= ccms_h((string) $aiSettings['temperature']) ?>"></div>
                  <div class="field"><label>Max tokens</label><input name="ai_max_tokens" type="number" min="600" max="6000" step="100" value="<?= ccms_h((string) $aiSettings['max_tokens']) ?>"></div>
                </div>
                <div class="field"><label>Timeout (segundos)</label><input name="ai_timeout" type="number" min="5" max="120" step="1" value="<?= ccms_h((string) $aiSettings['timeout']) ?>"></div>
                <div class="toolbar">
                  <button class="btn" type="submit">Guardar configuración</button>
                </div>
              </form>
              <form method="post" class="stack" style="margin-top:12px">
                <input type="hidden" name="action" value="probe_ai">
                <input type="hidden" name="csrf_token" value="<?= ccms_h($csrfToken) ?>">
                <input type="hidden" name="ai_endpoint" value="<?= ccms_h((string) $aiSettings['endpoint']) ?>">
                <input type="hidden" name="ai_model" value="<?= ccms_h((string) $aiSettings['model']) ?>">
                <input type="hidden" name="ai_temperature" value="<?= ccms_h((string) $aiSettings['temperature']) ?>">
                <input type="hidden" name="ai_max_tokens" value="<?= ccms_h((string) $aiSettings['max_tokens']) ?>">
                <input type="hidden" name="ai_timeout" value="<?= ccms_h((string) $aiSettings['timeout']) ?>">
                <button class="btn btn-secondary" type="submit">Probar conexión con LM Studio</button>
              </form>
            </div>
            <div class="help-box">
              <h4>Cómo funciona LinuxCMS</h4>
              <ul>
                <li>Esta pestaña genera el primer borrador de la web con <strong>LM Studio local</strong>.</li>
                <li>Después, la página cae en <strong>Páginas</strong>, donde la editas con builder, preview y media.</li>
                <li>Al subir el proyecto a hosting básico, el cliente final sigue entrando por <strong>/r-admin</strong> para editarla manualmente.</li>
                <li>Si LM Studio no responde, LinuxCMS crea un draft base para no dejarte bloqueado.</li>
              </ul>
            </div>
          </aside>
          <section class="workspace">
            <div class="card editor-card">
              <div class="editor-header">
                <div class="editor-title">
                  <div class="chip">Studio local · teclado</div>
                  <h2>Crea una web completa desde un brief</h2>
                  <p class="muted" style="margin:0">Todo desde teclado. Sin voz. LM Studio genera una primera cápsula editable y el CMS se encarga del resto.</p>
                </div>
              </div>
              <form method="post" class="stack">
                <input type="hidden" name="action" value="ai_generate_page">
                <input type="hidden" name="csrf_token" value="<?= ccms_h($csrfToken) ?>">
                <div class="split-2">
                  <div class="field"><label>Nombre del negocio</label><input name="business_name" placeholder="OTM Lawyers" required></div>
                  <div class="field"><label>Título de la página</label><input name="page_title" placeholder="Corporate Law for fast-moving businesses"></div>
                  <div class="field"><label>Slug</label><input name="page_slug" placeholder="otm-lawyers"></div>
                  <div class="field">
                    <label>Industria</label>
                    <select name="industry">
                      <option value="generic">Genérica</option>
                      <option value="lawyer">Legal</option>
                      <option value="saas">SaaS / Tech</option>
                      <option value="restaurant">Restaurante</option>
                      <option value="real-estate">Inmobiliaria</option>
                      <option value="creative">Creativa / portfolio</option>
                      <option value="clinic">Clínica / salud</option>
                    </select>
                  </div>
                </div>
                <div class="field"><label>Oferta o servicio</label><textarea name="offer" style="min-height:100px" placeholder="Describe qué vendes y por qué importa." required></textarea></div>
                <div class="split-2">
                  <div class="field"><label>Cliente ideal</label><textarea name="audience" style="min-height:100px" placeholder="¿Para quién es esta web?"></textarea></div>
                  <div class="field"><label>Objetivo principal</label><textarea name="goal" style="min-height:100px" placeholder="Reservas, leads, ventas, llamadas..." required></textarea></div>
                </div>
                <div class="split-2">
                  <div class="field"><label>Texto del CTA</label><input name="cta_text" placeholder="Book a call"></div>
                  <div class="field"><label>Tono</label><input name="tone" placeholder="Premium, calm, editorial, direct..."></div>
                </div>
                <div class="field"><label>Notas extra</label><textarea name="notes" style="min-height:120px" placeholder="Referencias visuales, secciones obligatorias, cosas que no quieres, etc."></textarea></div>
                <div class="check-grid">
                  <label class="check"><input type="checkbox" name="set_as_homepage" checked> Usar esta página como homepage</label>
                  <label class="check"><input type="checkbox" name="apply_site_branding" checked> Aplicar también el branding generado al sitio</label>
                </div>
                <div class="toolbar">
                  <button class="btn" type="submit">Generar borrador con LM Studio</button>
                  <a class="btn btn-secondary" href="/r-admin/?tab=pages">Abrir páginas existentes</a>
                </div>
              </form>
            </div>
            <div class="help-box">
              <h4>Qué genera esta pantalla</h4>
              <ul>
                <li>Una página nueva con una <strong>cápsula completa</strong>.</li>
                <li>Una estructura ya pensada para editarse luego en el builder: header, hero, contenido, prueba, contacto y footer.</li>
                <li>Una base visual que luego puedes afinar por sección desde <strong>Páginas</strong>.</li>
              </ul>
              <p class="small" style="margin-top:12px"><strong>Consejo:</strong> usa esta pantalla para crear la primera versión y el builder para pulirla a nivel de detalle.</p>
            </div>
          </section>
        </div>
      <?php elseif ($tab === 'site'): ?>
        <?php require __DIR__ . '/views/site.php'; ?>
      <?php elseif ($tab === 'extensions'): ?>
        <?php require __DIR__ . '/views/extensions.php'; ?>
      <?php elseif ($tab === 'backups'): ?>
        <?php require __DIR__ . '/views/backups.php'; ?>
      <?php elseif ($tab === 'media'): ?>
        <?php require __DIR__ . '/views/media.php'; ?>
      <?php elseif ($tab === 'import'): ?>
        <div class="import-layout">
          <div class="card editor-card">
            <div class="editor-header">
              <div class="editor-title">
                <div class="chip">Importar</div>
                <h2>Importación rápida de una cápsula o HTML</h2>
                <p class="muted" style="margin:0">Pega el HTML ya renderizado de una cápsula y, opcionalmente, el JSON para conservar la referencia original.</p>
              </div>
            </div>
            <form method="post" class="stack">
              <input type="hidden" name="action" value="quick_import">
              <input type="hidden" name="csrf_token" value="<?= ccms_h($csrfToken) ?>">
              <div class="split-2">
                <div class="field"><label>Título</label><input name="import_title" required></div>
                <div class="field"><label>Slug</label><input name="import_slug" placeholder="opcional"></div>
              </div>
              <div class="field"><label>HTML</label><textarea name="import_html" style="min-height:260px" required></textarea></div>
              <div class="field"><label>Capsule JSON (opcional)</label><textarea name="import_capsule_json" style="min-height:220px"></textarea></div>
              <div class="toolbar">
                <button class="btn" type="submit">Importar página</button>
              </div>
            </form>
            <p class="small">También puedes usar el script CLI <code>php tools/import-from-aivoiceweb.php</code>.</p>
          </div>
        </div>
      <?php elseif ($tab === 'audit'): ?>
        <?php require __DIR__ . '/views/audit.php'; ?>
      <?php elseif ($tab === 'users'): ?>
        <?php require __DIR__ . '/views/users.php'; ?>
      <?php else: ?>
        <?php require __DIR__ . '/views/pages.php'; ?>
      <?php endif; ?>
    <?php endif; ?>
  </div>

  <?php if ($currentAdmin): ?>
  <script<?= ccms_script_nonce_attr() ?>>
    const sectionTemplates = <?= $sectionTemplatesJson ?: '[]' ?>;
    const capsuleBuilderTemplates = <?= $capsuleBuilderTemplatesJson ?: '[]' ?>;
    const initialCapsuleState = <?= $selectedCapsuleStateJson ?: '{"meta":{},"style":{},"blocks":[]}' ?>;
    const mediaItems = <?= $mediaItemsJson ?: '[]' ?>;
    const previewSiteConfig = <?= $previewSiteConfigJson ?: '{"site":{},"menu":[]}' ?>;

    function insertAtCursor(textarea, snippet) {
      if (!textarea) return;
      const start = textarea.selectionStart ?? textarea.value.length;
      const end = textarea.selectionEnd ?? textarea.value.length;
      const before = textarea.value.slice(0, start);
      const after = textarea.value.slice(end);
      const glue = before && !before.endsWith("\n") ? "\n\n" : "";
      textarea.value = before + glue + snippet + "\n\n" + after;
      const nextPos = before.length + glue.length + snippet.length + 2;
      textarea.focus();
      textarea.selectionStart = textarea.selectionEnd = nextPos;
      textarea.dispatchEvent(new Event("input", { bubbles: true }));
    }

    function buildPreviewDoc(pageTitle, htmlContent) {
      const site = previewSiteConfig.site || {};
      const colors = site.colors || {};
      const menu = Array.isArray(previewSiteConfig.menu) ? previewSiteConfig.menu : [];
      const menuHtml = menu.map((item) => {
        const href = item.is_homepage ? "/" : "/" + encodeURIComponent(item.slug || "");
        const label = item.label || "Página";
        return `<a href="${href}">${label}</a>`;
      }).join("");
      const title = pageTitle || site.title || "LinuxCMS";
      const description = site.tagline || "";
      return `<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>${title}</title>
  <meta name="description" content="${description}">
  <style>
    :root{
      --bg:${colors.bg || "#f7f4ee"};
      --surface:${colors.surface || "#ffffff"};
      --text:${colors.text || "#2f241f"};
      --muted:${colors.muted || "#6b5b53"};
      --primary:${colors.primary || "#c86f5c"};
      --secondary:${colors.secondary || "#d9c4b3"};
      --max:1200px;
    }
    *{box-sizing:border-box}
    body{margin:0;background:var(--bg);color:var(--text);font-family:Arial,Helvetica,sans-serif}
    a{color:inherit}
    .shell{width:min(var(--max),calc(100% - 28px));margin:0 auto}
    .site-header{position:sticky;top:0;z-index:30;background:rgba(255,255,255,.92);backdrop-filter:blur(10px);border-bottom:1px solid rgba(0,0,0,.05)}
    .site-header-inner{display:flex;align-items:center;justify-content:space-between;gap:16px;min-height:72px}
    .brand{font-weight:800;font-size:20px;text-decoration:none}
    .menu{display:flex;flex-wrap:wrap;gap:14px}
    .menu a{text-decoration:none;color:var(--muted);font-weight:700}
    .menu a:hover{color:var(--text)}
    .page-shell{padding:32px 0 48px}
    .page-surface{background:var(--surface);border-radius:28px;box-shadow:0 30px 60px -35px rgba(0,0,0,.22);overflow:hidden}
    .page-content{padding:0}
    .site-footer{padding:22px 0 42px;color:var(--muted);font-size:14px;text-align:center}
    @media (max-width:800px){.site-header-inner{display:block;padding:12px 0}.brand{display:block;margin-bottom:10px}.menu{gap:10px}}
  </style>
</head>
<body>
  <header class="site-header">
    <div class="shell site-header-inner">
      <a class="brand" href="/">${site.title || "LinuxCMS"}</a>
      <nav class="menu">${menuHtml}</nav>
    </div>
  </header>
  <main class="shell page-shell">
    <div class="page-surface">
      <div class="page-content">${htmlContent || "<section style='padding:64px 32px'><p>Página vacía.</p></section>"}</div>
    </div>
  </main>
  <footer class="site-footer">
    <div class="shell">${site.footer_text || ""}</div>
  </footer>
</body>
</html>`;
    }

    const tabs = document.querySelectorAll("[data-tab-target]");
    const panels = document.querySelectorAll("[data-tab-panel]");
    const clientModeToggle = document.getElementById("clientModeToggle");
    const clientModeBannerToggle = document.getElementById("clientModeBannerToggle");
    const clientQuickActions = document.getElementById("clientQuickActions");
    const quickstartGuide = document.querySelector(".quickstart-guide");
    const clientModeStorageKey = "ccms-client-mode";
    tabs.forEach((tabButton) => {
      tabButton.addEventListener("click", () => {
        const target = tabButton.dataset.tabTarget;
        tabs.forEach((button) => button.classList.toggle("active", button === tabButton));
        panels.forEach((panel) => panel.classList.toggle("active", panel.dataset.tabPanel === target));
      });
    });

    function activateEditorTab(target) {
      const button = Array.from(tabs).find((item) => item.dataset.tabTarget === target && item.offsetParent !== null);
      button?.click();
    }

    function setClientMode(enabled) {
      document.body.classList.toggle("client-mode", enabled);
      if (clientModeToggle) {
        clientModeToggle.textContent = enabled ? "Modo avanzado" : "Modo cliente";
        clientModeToggle.setAttribute("aria-pressed", enabled ? "true" : "false");
      }
      if (clientModeBannerToggle) {
        clientModeBannerToggle.textContent = enabled ? "Cambiar a modo avanzado" : "Volver a modo cliente";
      }
      try {
        window.localStorage.setItem(clientModeStorageKey, enabled ? "1" : "0");
      } catch (error) {
        console.warn("Could not persist client mode:", error);
      }
      const activeHiddenTab = Array.from(tabs).find((button) => button.classList.contains("active") && button.offsetParent === null);
      if (enabled && activeHiddenTab) {
        activateEditorTab("content");
      }
    }

    function getInitialClientMode() {
      try {
        const stored = window.localStorage.getItem(clientModeStorageKey);
        if (stored === "0" || stored === "1") {
          return stored === "1";
        }
      } catch (error) {
        console.warn("Could not read client mode:", error);
      }
      return true;
    }

    clientModeToggle?.addEventListener("click", () => {
      setClientMode(!document.body.classList.contains("client-mode"));
    });

    clientModeBannerToggle?.addEventListener("click", () => {
      setClientMode(!document.body.classList.contains("client-mode"));
    });

    clientQuickActions?.addEventListener("click", (event) => {
      const button = event.target.closest("[data-client-focus]");
      if (!button) return;
      const target = button.dataset.clientFocus || "";
      if (target === "site") {
        window.location.href = "/r-admin/?tab=site";
        return;
      }
      if (target) {
        activateEditorTab(target);
      }
    });

    function focusPreviewPanel() {
      const previewCard = preview?.closest(".editor-card");
      previewCard?.scrollIntoView({ block: "start", behavior: "smooth" });
      preview?.focus?.({ preventScroll: true });
    }

    quickstartGuide?.addEventListener("click", (event) => {
      const button = event.target.closest("[data-guide-target]");
      if (!button) return;
      const target = button.dataset.guideTarget || "";
      if (target === "preview-text" || target === "preview-media") {
        activateEditorTab("content");
        window.setTimeout(() => focusPreviewPanel(), 80);
        return;
      }
      if (target === "builder" || target === "publish") {
        activateEditorTab(target);
        return;
      }
      if (target === "site") {
        window.location.href = "/r-admin/?tab=site";
      }
    });

    const htmlEditor = document.getElementById("html_content");
    const preview = document.getElementById("pagePreview");
    const pageTitle = document.getElementById("page_title");
    const capsuleTextarea = document.querySelector('textarea[name="capsule_json"]');
    const pageEditorForm = document.getElementById("pageEditorForm");
    const builderList = document.getElementById("builderList");
    const builderTemplateGrid = document.getElementById("builderTemplateGrid");
    const builderInsertHint = document.getElementById("builderInsertHint");
    const builderBlockCount = document.getElementById("builderBlockCount");
    const builderSyncButton = document.getElementById("builderSyncJson");
    const builderContext = document.getElementById("builderContext");
    const builderGlobalStyle = document.getElementById("builderGlobalStyle");
    const previewEndpoint = "/r-admin/preview.php";
    const builderReadOnly = <?= $builderReadOnly ? 'true' : 'false' ?>;
    let previewTimer = null;

    const capsuleGlobalStyleFields = [
      { key: "accent", label: "Accent", type: "color", fallback: "#c86f5c" },
      { key: "accent_dark", label: "Accent dark", type: "color", fallback: "#ab5d4e" },
      { key: "bg_from", label: "Background from", type: "color", fallback: "#f7f4ee" },
      { key: "bg_to", label: "Background to", type: "color", fallback: "#ffffff" },
      { key: "card_bg", label: "Card background", type: "text", fallback: "rgba(255,255,255,0.96)" },
      { key: "card_border", label: "Card border", type: "text", fallback: "rgba(0,0,0,0.08)" },
      { key: "gradient_accent", label: "Gradient accent", type: "text", fallback: "linear-gradient(135deg,#c86f5c 0%,#d9c4b3 100%)" },
      { key: "text_primary", label: "Text primary", type: "color", fallback: "#2f241f" },
      { key: "text_secondary", label: "Text secondary", type: "color", fallback: "#6b5b53" },
      { key: "text_muted", label: "Text muted", type: "color", fallback: "#7c6a60" },
      { key: "nav_bg", label: "Navigation background", type: "text", fallback: "rgba(255,255,255,0.92)" },
      { key: "font_family", label: "Body font", type: "text", fallback: "Inter, Arial, Helvetica, sans-serif" },
      { key: "font_heading", label: "Heading font", type: "text", fallback: "Inter, Arial, Helvetica, sans-serif" },
    ];

    const blockStyleFields = [
      { key: "padding_top", label: "Padding top", type: "number", placeholder: "Default" },
      { key: "padding_bottom", label: "Padding bottom", type: "number", placeholder: "Default" },
      { key: "content_width", label: "Content width", type: "number", placeholder: "Default" },
      { key: "text_align", label: "Text align", type: "select", options: ["", "left", "center", "right"] },
      { key: "background", label: "Background override", type: "text", placeholder: "#fff or linear-gradient(...)" },
      { key: "text_color", label: "Text color", type: "color", placeholder: "Optional" },
      { key: "button_bg", label: "Button background", type: "color", placeholder: "Optional" },
      { key: "button_text_color", label: "Button text color", type: "color", placeholder: "Optional" },
      { key: "button_border_color", label: "Button border color", type: "color", placeholder: "Optional" },
      { key: "button_ghost_bg", label: "Ghost button background", type: "color", placeholder: "Optional" },
      { key: "button_ghost_text_color", label: "Ghost button text color", type: "color", placeholder: "Optional" },
      { key: "button_ghost_border_color", label: "Ghost button border color", type: "color", placeholder: "Optional" },
    ];

    function escapeHtml(value) {
      return String(value ?? "")
        .replaceAll("&", "&amp;")
        .replaceAll("<", "&lt;")
        .replaceAll(">", "&gt;")
        .replaceAll('"', "&quot;")
        .replaceAll("'", "&#039;");
    }

    function deepClone(value) {
      return JSON.parse(JSON.stringify(value));
    }

    function isPlainObject(value) {
      return !!value && typeof value === "object" && !Array.isArray(value);
    }

    function isScalarValue(value) {
      return ["string", "number", "boolean"].includes(typeof value) || value === null;
    }

    function isSimpleScalarArray(value) {
      return Array.isArray(value) && value.every((item) => isScalarValue(item));
    }

    function isSimpleObjectArray(value) {
      return Array.isArray(value) && value.every((item) => {
        if (!isPlainObject(item)) return false;
        return Object.values(item).every((nested) => isScalarValue(nested) || isSimpleScalarArray(nested));
      });
    }

    function createDefaultScalarItem(parentKey) {
      const key = String(parentKey || "").toLowerCase();
      if (key.includes("feature")) return "Feature";
      if (key.includes("bullet")) return "New bullet";
      if (key.includes("line")) return "New line";
      if (key.includes("tag")) return "New tag";
      return "New item";
    }

    function createDefaultObjectItem(parentKey) {
      const key = String(parentKey || "").toLowerCase();
      if (key.includes("link")) return { text: "New link", href: "#" };
      if (key.includes("image")) return { url: "", alt: "New image" };
      if (key.includes("plan")) return { name: "New plan", price: "$0", features: ["Feature"], cta: "Start", highlighted: false };
      if (key.includes("post")) return { category: "Article", title: "New post", excerpt: "Short description", href: "#" };
      if (key.includes("project")) return { category: "Project", title: "New project", metric: "New metric", href: "#" };
      if (key.includes("service")) return { title: "New service", desc: "Short description", bullets: ["Point"], cta_text: "Learn more", cta_href: "#" };
      if (key.includes("column")) return { title: "Column", links: [{ text: "Link", href: "#" }] };
      if (key.includes("item")) return { title: "New item", desc: "Short description" };
      return { title: "New item", text: "Edit this content" };
    }

    function dataAttributes(attributes) {
      return Object.entries(attributes).map(([key, value]) => `data-${escapeHtml(key)}="${escapeHtml(value)}"`).join(" ");
    }

    function isImageLikeKey(key, parentKey = "") {
      const currentKey = String(key || "").toLowerCase();
      const parent = String(parentKey || "").toLowerCase();
      if (/(image|photo|avatar|logo|thumbnail|background|banner|cover|poster|mockup|favicon)/.test(currentKey)) return true;
      if ((currentKey === "url" || currentKey === "src") && /(image|photo|avatar|logo|thumbnail|gallery|banner|hero|project|post|portfolio)/.test(parent)) return true;
      return false;
    }

    function isLinkLikeKey(key, parentKey = "") {
      const haystack = `${parentKey} ${key}`.toLowerCase();
      if (isImageLikeKey(key, parentKey)) return false;
      return /href|url|link|cta_href|button_href|button_url|profile_url|instagram|youtube|facebook|linkedin|tiktok|whatsapp|telegram/.test(haystack);
    }

    function renderMediaPicker(scope, attributes) {
      if (!Array.isArray(mediaItems) || !mediaItems.length) {
        return `<div class="builder-media-picker"><div class="builder-media-empty">Todavía no hay archivos en la biblioteca media. Sube imágenes en la pestaña <strong>Media</strong> y volverán a aparecer aquí.</div></div>`;
      }
      const safeAttrs = dataAttributes(attributes);
      return `
        <details class="builder-media-picker">
          <summary>Elegir desde la biblioteca media</summary>
          <div class="builder-media-picker-grid">
            ${mediaItems.slice(0, 10).map((asset) => `
              <button class="builder-media-option" type="button" data-builder-pick-media="${escapeHtml(scope)}" data-media-url="${escapeHtml(asset.url || "")}" ${safeAttrs}>
                <img src="${escapeHtml(asset.url || "")}" alt="${escapeHtml(asset.name || "Media")}">
                <span>${escapeHtml(asset.name || asset.url || "Imagen")}</span>
              </button>
            `).join("")}
          </div>
        </details>
      `;
    }

    function createBlockId() {
      const randomPart = Math.random().toString(16).slice(2, 10);
      return "block_" + randomPart;
    }

    function normalizeCapsule(input) {
      const capsule = (input && typeof input === "object") ? deepClone(input) : {};
      if (!capsule.meta || typeof capsule.meta !== "object") capsule.meta = {};
      if (!capsule.style || typeof capsule.style !== "object") capsule.style = {};
      if (!Array.isArray(capsule.blocks)) capsule.blocks = [];
      capsule.blocks = capsule.blocks.map((block, index) => ({
        id: block && block.id ? String(block.id) : createBlockId(),
        type: block && block.type ? String(block.type) : "text_block",
        props: block && typeof block.props === "object" && block.props ? block.props : {},
        style: block && typeof block.style === "object" && block.style ? block.style : {},
        _order: index,
      }));
      return capsule;
    }

    let capsuleState = normalizeCapsule(initialCapsuleState);
    let builderDragState = null;
    let activeBuilderBlockIndex = capsuleState.blocks.length ? 0 : -1;
    let pendingInsertIndex = capsuleState.blocks.length;
    let mediaModalState = null;

    function isLongTextField(key, value) {
      return ["subtitle", "text", "quote", "description", "privacy_text", "info", "copyright"].includes(key)
        || (typeof value === "string" && value.length > 80);
    }

    function syncCapsuleTextarea() {
      if (!capsuleTextarea) return;
      const capsuleToSave = {
        meta: capsuleState.meta || {},
        style: capsuleState.style || {},
        blocks: (capsuleState.blocks || []).map(({ _order, ...block }) => block),
      };
      capsuleTextarea.value = JSON.stringify(capsuleToSave, null, 2);
      if (builderBlockCount) {
        const count = Array.isArray(capsuleToSave.blocks) ? capsuleToSave.blocks.length : 0;
        builderBlockCount.textContent = `${count} bloque${count === 1 ? "" : "s"}`;
      }
      schedulePreviewRefresh();
    }

    function highlightPreviewBlock(index) {
      if (!preview || !preview.contentWindow || index < 0) return;
      try {
        preview.contentWindow.postMessage({ type: "ccms-parent-highlight-block", index }, "*");
      } catch (error) {
        console.warn("Preview highlight failed:", error);
      }
    }

    function selectBuilderBlock(index, options = {}) {
      const { scroll = true, syncPreview = true } = options;
      if (!Array.isArray(capsuleState.blocks) || !capsuleState.blocks.length) {
        activeBuilderBlockIndex = -1;
        pendingInsertIndex = 0;
        renderBuilderBlocks();
        return;
      }
      const normalizedIndex = Math.max(0, Math.min(index, capsuleState.blocks.length - 1));
      activeBuilderBlockIndex = normalizedIndex;
      pendingInsertIndex = normalizeInsertIndex(normalizedIndex + 1);
      renderBuilderBlocks();
      renderBuilderContext();
      const selected = builderList?.querySelector(`[data-builder-block="${normalizedIndex}"]`);
      if (scroll && selected) {
        selected.scrollIntoView({ block: "nearest", behavior: "smooth" });
      }
      if (syncPreview) {
        highlightPreviewBlock(normalizedIndex);
      }
    }

    function schedulePreviewRefresh(delay = 220) {
      if (!preview) return;
      window.clearTimeout(previewTimer);
      previewTimer = window.setTimeout(() => {
        refreshPreview();
      }, delay);
    }

    function renderBuilderGlobalStyle() {
      if (!builderGlobalStyle) return;
      builderGlobalStyle.innerHTML = capsuleGlobalStyleFields.map((field) => {
        const currentValue = capsuleState.style?.[field.key] ?? field.fallback ?? "";
        if (field.type === "color") {
          return `
            <div class="field">
              <label>${escapeHtml(field.label)}</label>
              <div style="display:grid;grid-template-columns:56px minmax(0,1fr);gap:10px;align-items:center">
                <input type="color" value="${escapeHtml(currentValue)}" data-builder-global-style="${escapeHtml(field.key)}" data-mode="color">
                <input type="text" value="${escapeHtml(currentValue)}" data-builder-global-style="${escapeHtml(field.key)}" data-mode="text">
              </div>
            </div>
          `;
        }
        return `
          <div class="field">
            <label>${escapeHtml(field.label)}</label>
            <input type="text" value="${escapeHtml(currentValue)}" data-builder-global-style="${escapeHtml(field.key)}" data-mode="text" placeholder="${escapeHtml(field.fallback || "")}">
          </div>
        `;
      }).join("");
    }

    function blockDisplayName(block) {
      return (block?.props && (block.props.title || block.props.brand || block.props.badge || block.props.name)) || block?.type || "Bloque";
    }

    function renderBuilderContext() {
      if (!builderContext) return;
      if (!Array.isArray(capsuleState.blocks) || !capsuleState.blocks.length || activeBuilderBlockIndex < 0) {
        builderContext.innerHTML = `
          <div class="builder-context-title">
            <strong>No hay bloque seleccionado</strong>
            <div class="small">Haz clic en un bloque del builder o en una sección dentro de la preview para empezar a editarla.</div>
          </div>
        `;
        return;
      }
      const block = capsuleState.blocks[activeBuilderBlockIndex];
      const insertLabel = pendingInsertIndex <= 0
        ? "al principio"
        : pendingInsertIndex >= capsuleState.blocks.length
          ? "al final"
          : `después del bloque ${pendingInsertIndex}`;
      builderContext.innerHTML = `
        <div class="builder-context-head">
          <div class="builder-context-title">
            <span class="chip">Bloque activo · ${activeBuilderBlockIndex + 1}</span>
            <strong>${escapeHtml(blockDisplayName(block))}</strong>
            <div class="small"><strong>${escapeHtml(block.type || "block")}</strong> · ID ${escapeHtml(block.id || "")}</div>
            <div class="small">La siguiente inserción irá <strong>${escapeHtml(insertLabel)}</strong>.</div>
          </div>
          <div class="builder-context-actions">
            <button class="btn btn-secondary" type="button" data-builder-context="content">Editar contenido</button>
            <button class="btn btn-secondary" type="button" data-builder-context="link">Editar enlace</button>
            <button class="btn btn-secondary" type="button" data-builder-context="media">Editar imágenes</button>
            <button class="btn btn-secondary" type="button" data-builder-context="style">Editar estilo</button>
            <button class="btn btn-secondary" type="button" data-builder-context="insert">Insertar después</button>
            <button class="btn btn-secondary" type="button" data-builder-context="duplicate">Duplicar</button>
            <button class="btn btn-danger" type="button" data-builder-context="remove">Eliminar</button>
          </div>
        </div>
        <div class="small">La preview y el builder están sincronizados. Haz clic en una sección de la preview para saltar directamente a su bloque editable.</div>
      `;
    }

    function focusSelectedBlockField(kind = "content") {
      const blockEl = builderList?.querySelector(`[data-builder-block="${activeBuilderBlockIndex}"]`);
      if (!blockEl) return;
      const selector = kind === "style"
        ? ".builder-subsection [data-builder-style-field]"
        : ".builder-fields [data-builder-field], .builder-fields [data-builder-object-field], .builder-fields [data-builder-scalar-item], .builder-fields textarea, .builder-fields input";
      const target = blockEl.querySelector(selector);
      if (target) {
        target.focus({ preventScroll: false });
        target.scrollIntoView({ block: "center", behavior: "smooth" });
      } else {
        blockEl.scrollIntoView({ block: "nearest", behavior: "smooth" });
      }
    }

    function findSelectedBlockTextField(preferredText = "", tag = "") {
      const blockEl = builderList?.querySelector(`[data-builder-block="${activeBuilderBlockIndex}"]`);
      if (!blockEl) return null;
      const textFields = Array.from(blockEl.querySelectorAll("[data-builder-field][data-mode='string'],[data-builder-object-field][data-mode='string'],[data-builder-nested-object-field][data-mode='string'],[data-builder-scalar-item],[data-builder-nested-scalar]"))
        .filter((field) => {
          const key = field.dataset.key || field.dataset.nestedKey || field.dataset.deepKey || "";
          const parentKey = field.dataset.nestedKey || field.dataset.key || "";
          return !isImageLikeKey(key, parentKey);
        });
      if (!textFields.length) {
        return null;
      }
      const preferred = (preferredText || "").trim().toLowerCase();
      let target = null;
      if (preferred) {
        target = textFields.find((field) => {
          const value = String(field.value || "").trim().toLowerCase();
          return value && (value.includes(preferred) || preferred.includes(value.slice(0, Math.min(value.length, 40))));
        }) || null;
      }
      if (!target) {
        const wantLong = tag === "p" || tag === "li";
        const wantShort = tag === "a" || tag === "button" || /^h[1-6]$/.test(tag);
        target = textFields.find((field) => wantLong && field.tagName === "TEXTAREA")
          || textFields.find((field) => wantShort && field.tagName !== "TEXTAREA")
          || textFields[0];
      }
      return target;
    }

    function focusSelectedBlockTextField(preferredText = "", tag = "") {
      const target = findSelectedBlockTextField(preferredText, tag);
      if (!target) {
        focusSelectedBlockField("content");
        return;
      }
      target.focus({ preventScroll: false });
      if (typeof target.select === "function") {
        target.select();
      }
      target.scrollIntoView({ block: "center", behavior: "smooth" });
    }

    function applySelectedBlockTextField(oldText = "", newText = "", tag = "") {
      const target = findSelectedBlockTextField(oldText, tag);
      if (!target) {
        focusSelectedBlockField("content");
        return false;
      }
      target.value = newText;
      target.dispatchEvent(new Event("input", { bubbles: true }));
      target.focus({ preventScroll: false });
      if (typeof target.select === "function") {
        target.select();
      }
      target.scrollIntoView({ block: "center", behavior: "smooth" });
      return true;
    }

    function findSelectedBlockMediaField(preferredSrc = "") {
      const blockEl = builderList?.querySelector(`[data-builder-block="${activeBuilderBlockIndex}"]`);
      if (!blockEl) return null;
      const candidates = Array.from(blockEl.querySelectorAll("[data-builder-field],[data-builder-object-field],[data-builder-nested-object-field]"));
      const preferred = (preferredSrc || "").trim();
      let target = null;
      if (preferred) {
        target = candidates.find((field) => String(field.value || "").trim() === preferred) || null;
      }
      if (!target) {
        target = candidates.find((field) => {
        const key = field.dataset.key || field.dataset.nestedKey || field.dataset.deepKey || "";
        const parentKey = field.dataset.nestedKey || field.dataset.key || "";
        return isImageLikeKey(key, parentKey);
        }) || null;
      }
      return target;
    }

    function focusSelectedBlockMediaField(preferredSrc = "") {
      const blockEl = builderList?.querySelector(`[data-builder-block="${activeBuilderBlockIndex}"]`);
      if (!blockEl) return;
      const target = findSelectedBlockMediaField(preferredSrc);
      if (target) {
        target.focus({ preventScroll: false });
        if (typeof target.select === "function") {
          target.select();
        }
        target.scrollIntoView({ block: "center", behavior: "smooth" });
        return;
      }
      const mediaButton = blockEl.querySelector("[data-builder-pick-media]");
      if (mediaButton) {
        mediaButton.scrollIntoView({ block: "center", behavior: "smooth" });
      } else {
        blockEl.scrollIntoView({ block: "nearest", behavior: "smooth" });
      }
    }

    function focusSelectedBlockLinkField(preferredHref = "", preferredText = "") {
      const blockEl = builderList?.querySelector(`[data-builder-block="${activeBuilderBlockIndex}"]`);
      if (!blockEl) return;
      const candidates = Array.from(blockEl.querySelectorAll("[data-builder-field][data-mode='string'],[data-builder-object-field][data-mode='string'],[data-builder-nested-object-field][data-mode='string']"));
      const preferred = (preferredHref || "").trim();
      const preferredLabel = (preferredText || "").trim().toLowerCase();
      let target = null;
      if (preferred) {
        target = candidates.find((field) => String(field.value || "").trim() === preferred) || null;
      }
      if (!target && preferredLabel) {
        target = candidates.find((field) => {
          const wrapper = field.closest(".builder-field, .builder-object-field, .builder-nested-object-field");
          const label = String(wrapper?.querySelector("label, strong")?.textContent || "").trim().toLowerCase();
          return label && (label.includes(preferredLabel) || preferredLabel.includes(label));
        }) || null;
      }
      if (!target) {
        target = candidates.find((field) => {
          const key = field.dataset.deepKey || field.dataset.nestedKey || field.dataset.key || "";
          const parentKey = field.dataset.nestedKey || field.dataset.key || "";
          return isLinkLikeKey(key, parentKey);
        }) || null;
      }
      if (target) {
        target.focus({ preventScroll: false });
        if (typeof target.select === "function") target.select();
        target.scrollIntoView({ block: "center", behavior: "smooth" });
        return;
      }
      focusSelectedBlockField("content");
    }

    function ensureMediaModal() {
      let backdrop = document.getElementById("media-modal-backdrop");
      if (backdrop) return backdrop;
      backdrop = document.createElement("div");
      backdrop.id = "media-modal-backdrop";
      backdrop.className = "media-modal-backdrop";
      backdrop.innerHTML = `
        <div class="media-modal" role="dialog" aria-modal="true" aria-labelledby="media-modal-title">
          <div class="media-modal-head">
            <div>
              <h3 id="media-modal-title">Seleccionar imagen</h3>
              <p>Elige una imagen de la biblioteca para aplicarla directamente al bloque seleccionado.</p>
            </div>
            <button class="btn btn-secondary" type="button" data-media-modal-close>Cerrar</button>
          </div>
          <div class="media-modal-toolbar">
            <input type="search" id="media-modal-search" placeholder="Buscar por nombre o URL">
            <button class="btn btn-secondary" type="button" data-media-modal-fallback>Ir al campo del builder</button>
          </div>
          <div class="media-modal-grid" id="media-modal-grid"></div>
          <div class="media-modal-footer">
            <div class="small" id="media-modal-status">Elige una imagen o pega una URL directamente en el builder.</div>
            <button class="btn btn-secondary" type="button" data-media-modal-close>Cerrar</button>
          </div>
        </div>
      `;
      document.body.appendChild(backdrop);
      backdrop.addEventListener("click", (event) => {
        if (event.target === backdrop || event.target.closest("[data-media-modal-close]")) {
          closeMediaModal();
        }
      });
      backdrop.querySelector("#media-modal-search")?.addEventListener("input", () => {
        renderMediaModalGrid();
      });
      backdrop.querySelector("[data-media-modal-fallback]")?.addEventListener("click", () => {
        if (mediaModalState?.preferredSrc) {
          focusSelectedBlockMediaField(mediaModalState.preferredSrc);
        } else {
          focusSelectedBlockMediaField();
        }
        closeMediaModal();
      });
      window.addEventListener("keydown", (event) => {
        if (event.key === "Escape" && backdrop.classList.contains("is-open")) {
          closeMediaModal();
        }
      });
      return backdrop;
    }

    function renderMediaModalGrid() {
      const backdrop = ensureMediaModal();
      const grid = backdrop.querySelector("#media-modal-grid");
      const status = backdrop.querySelector("#media-modal-status");
      const search = String(backdrop.querySelector("#media-modal-search")?.value || "").trim().toLowerCase();
      if (!grid) return;
      if (!Array.isArray(mediaItems) || !mediaItems.length) {
        grid.innerHTML = `<div class="media-modal-empty">Todavía no hay imágenes en la biblioteca. Sube imágenes en la pestaña <strong>Media</strong> y vuelve a intentarlo.</div>`;
        if (status) status.textContent = "No hay archivos disponibles en la biblioteca media.";
        return;
      }
      const items = mediaItems.filter((asset) => {
        if (!search) return true;
        const haystack = `${asset.name || ""} ${asset.original_name || ""} ${asset.url || ""}`.toLowerCase();
        return haystack.includes(search);
      });
      if (!items.length) {
        grid.innerHTML = `<div class="media-modal-empty">No hay resultados para esa búsqueda.</div>`;
        if (status) status.textContent = "No se han encontrado imágenes con ese criterio.";
        return;
      }
      grid.innerHTML = items.map((asset) => `
        <button class="media-modal-card" type="button" data-media-modal-select="${escapeHtml(asset.url || "")}">
          <img src="${escapeHtml(asset.url || "")}" alt="${escapeHtml(asset.name || "Media")}">
          <span>${escapeHtml(asset.name || asset.original_name || asset.url || "Imagen")}</span>
        </button>
      `).join("");
      grid.querySelectorAll("[data-media-modal-select]").forEach((button) => {
        button.addEventListener("click", () => {
          if (!mediaModalState?.target) {
            focusSelectedBlockMediaField(mediaModalState?.preferredSrc || "");
            closeMediaModal();
            return;
          }
          mediaModalState.target.value = button.dataset.mediaModalSelect || "";
          mediaModalState.target.dispatchEvent(new Event("input", { bubbles: true }));
          mediaModalState.target.focus({ preventScroll: false });
          mediaModalState.target.scrollIntoView({ block: "center", behavior: "smooth" });
          if (status) status.textContent = "Imagen aplicada al bloque seleccionado.";
          closeMediaModal();
        });
      });
      if (status) status.textContent = `${items.length} imagen${items.length === 1 ? "" : "es"} disponible${items.length === 1 ? "" : "s"} para aplicar.`;
    }

    function openPreviewMediaPicker(preferredSrc = "") {
      const target = findSelectedBlockMediaField(preferredSrc);
      if (!target) {
        focusSelectedBlockMediaField(preferredSrc);
        return;
      }
      const backdrop = ensureMediaModal();
      mediaModalState = {
        target,
        preferredSrc: String(preferredSrc || "").trim(),
      };
      backdrop.classList.add("is-open");
      const search = backdrop.querySelector("#media-modal-search");
      if (search) search.value = "";
      renderMediaModalGrid();
      search?.focus({ preventScroll: true });
    }

    function closeMediaModal() {
      const backdrop = document.getElementById("media-modal-backdrop");
      if (!backdrop) return;
      backdrop.classList.remove("is-open");
      mediaModalState = null;
    }

    function applySelectedBlockLinkField(oldHref = "", newHref = "", preferredText = "") {
      const blockEl = builderList?.querySelector(`[data-builder-block="${activeBuilderBlockIndex}"]`);
      if (!blockEl) return false;
      const candidates = Array.from(blockEl.querySelectorAll("[data-builder-field][data-mode='string'],[data-builder-object-field][data-mode='string'],[data-builder-nested-object-field][data-mode='string']"));
      const previousHref = (oldHref || "").trim();
      const nextHref = (newHref || "").trim();
      const preferredLabel = (preferredText || "").trim().toLowerCase();
      let target = null;
      if (previousHref) {
        target = candidates.find((field) => String(field.value || "").trim() === previousHref) || null;
      }
      if (!target && preferredLabel) {
        target = candidates.find((field) => {
          const wrapper = field.closest(".builder-field, .builder-object-field, .builder-nested-object-field");
          const label = String(wrapper?.querySelector("label, strong")?.textContent || "").trim().toLowerCase();
          return label && (label.includes(preferredLabel) || preferredLabel.includes(label));
        }) || null;
      }
      if (!target) {
        target = candidates.find((field) => {
          const key = field.dataset.deepKey || field.dataset.nestedKey || field.dataset.key || "";
          const parentKey = field.dataset.nestedKey || field.dataset.key || "";
          return isLinkLikeKey(key, parentKey);
        }) || null;
      }
      if (!target) {
        focusSelectedBlockLinkField(previousHref, preferredText);
        return false;
      }
      target.value = nextHref;
      target.dispatchEvent(new Event("input", { bubbles: true }));
      target.focus({ preventScroll: false });
      if (typeof target.select === "function") target.select();
      target.scrollIntoView({ block: "center", behavior: "smooth" });
      return true;
    }

    function applySelectedBlockButtonStyle(styleUpdates = {}) {
      if (activeBuilderBlockIndex < 0 || !capsuleState.blocks[activeBuilderBlockIndex]) return false;
      capsuleState.blocks[activeBuilderBlockIndex].style ||= {};
      Object.entries(styleUpdates || {}).forEach(([key, value]) => {
        const normalized = String(value || "").trim();
        if (normalized === "") {
          delete capsuleState.blocks[activeBuilderBlockIndex].style[key];
        } else {
          capsuleState.blocks[activeBuilderBlockIndex].style[key] = normalized;
        }
      });
      if (Object.prototype.hasOwnProperty.call(styleUpdates || {}, "button_variant")) {
        const variantValue = String(styleUpdates.button_variant || "").trim();
        if (variantValue === "") {
          delete capsuleState.blocks[activeBuilderBlockIndex].style.button_variant;
        } else {
          capsuleState.blocks[activeBuilderBlockIndex].style.button_variant = variantValue;
        }
      }
      renderBuilderBlocks();
      selectBuilderBlock(activeBuilderBlockIndex, { scroll: false, syncPreview: true });
      return true;
    }

    function renderBlockStyleField(index, field, value) {
      const safeKey = escapeHtml(field.key);
      const safeLabel = escapeHtml(field.label);
      if (field.type === "select") {
        const options = (field.options || []).map((option) => {
          const optionLabel = option === "" ? "Default" : option;
          return `<option value="${escapeHtml(option)}" ${value === option ? "selected" : ""}>${escapeHtml(optionLabel)}</option>`;
        }).join("");
        return `
          <div class="field">
            <label>${safeLabel}</label>
            <select data-builder-style-field="${index}" data-key="${safeKey}" data-mode="select">${options}</select>
          </div>
        `;
      }
      if (field.type === "color") {
        const colorValue = value || "#000000";
        return `
          <div class="field">
            <label>${safeLabel}</label>
            <div style="display:grid;grid-template-columns:56px minmax(0,1fr);gap:10px;align-items:center">
              <input type="color" value="${escapeHtml(colorValue)}" data-builder-style-field="${index}" data-key="${safeKey}" data-mode="color">
              <input type="text" value="${escapeHtml(value || "")}" data-builder-style-field="${index}" data-key="${safeKey}" data-mode="text" placeholder="${escapeHtml(field.placeholder || "")}">
            </div>
          </div>
        `;
      }
      return `
        <div class="field">
          <label>${safeLabel}</label>
          <input type="${field.type === "number" ? "number" : "text"}" value="${escapeHtml(value ?? "")}" data-builder-style-field="${index}" data-key="${safeKey}" data-mode="${escapeHtml(field.type)}" placeholder="${escapeHtml(field.placeholder || "")}">
        </div>
      `;
    }

    function renderNestedScalarList(blockIndex, key, parentIndex, nestedKey, values) {
      const items = values.length ? values : [createDefaultScalarItem(nestedKey)];
      return `
        <div class="builder-full" data-parent-item-index="${parentIndex}">
          <label>${escapeHtml(nestedKey)}</label>
          <div class="builder-inline-list">
            ${items.map((item, nestedIndex) => `
              <div class="builder-inline-item">
                <input value="${escapeHtml(item ?? "")}" data-builder-nested-scalar="${blockIndex}" data-key="${escapeHtml(key)}" data-parent-index="${parentIndex}" data-nested-key="${escapeHtml(nestedKey)}" data-item-index="${nestedIndex}">
                <div class="builder-inline-actions">
                  <button class="btn btn-secondary" type="button" data-builder-nested-action="nested-scalar-up" data-index="${blockIndex}" data-key="${escapeHtml(key)}" data-parent-index="${parentIndex}" data-nested-key="${escapeHtml(nestedKey)}" data-item-index="${nestedIndex}">↑</button>
                  <button class="btn btn-secondary" type="button" data-builder-nested-action="nested-scalar-down" data-index="${blockIndex}" data-key="${escapeHtml(key)}" data-parent-index="${parentIndex}" data-nested-key="${escapeHtml(nestedKey)}" data-item-index="${nestedIndex}">↓</button>
                  <button class="btn btn-danger" type="button" data-builder-nested-action="nested-scalar-remove" data-index="${blockIndex}" data-key="${escapeHtml(key)}" data-parent-index="${parentIndex}" data-nested-key="${escapeHtml(nestedKey)}" data-item-index="${nestedIndex}">Eliminar</button>
                </div>
              </div>
            `).join("")}
            <button class="btn btn-secondary" type="button" data-builder-nested-action="nested-scalar-add" data-index="${blockIndex}" data-key="${escapeHtml(key)}" data-parent-index="${parentIndex}" data-nested-key="${escapeHtml(nestedKey)}">Añadir ${escapeHtml(nestedKey)}</button>
          </div>
        </div>
      `;
    }

    function renderNestedObjectArray(blockIndex, key, parentIndex, nestedKey, values) {
      const items = values.length ? values : [createDefaultObjectItem(nestedKey)];
      return `
        <div class="builder-full" data-parent-item-index="${parentIndex}">
          <label>${escapeHtml(nestedKey)}</label>
          <div class="builder-repeater-list">
            ${items.map((item, nestedIndex) => {
              const title = item.title || item.text || item.label || item.name || `Item ${nestedIndex + 1}`;
              const fields = Object.entries(item).map(([deepKey, deepValue]) => {
                if (typeof deepValue === "boolean") {
                  return `
                    <label class="check builder-full">
                      <input type="checkbox" data-builder-nested-object-field="${blockIndex}" data-key="${escapeHtml(key)}" data-parent-index="${parentIndex}" data-nested-key="${escapeHtml(nestedKey)}" data-item-index="${nestedIndex}" data-deep-key="${escapeHtml(deepKey)}" data-mode="boolean" ${deepValue ? "checked" : ""}>
                      ${escapeHtml(deepKey)}
                    </label>
                  `;
                }
                if (typeof deepValue === "number") {
                  return `
                    <div class="field">
                      <label>${escapeHtml(deepKey)}</label>
                      <input type="number" value="${escapeHtml(deepValue)}" data-builder-nested-object-field="${blockIndex}" data-key="${escapeHtml(key)}" data-parent-index="${parentIndex}" data-nested-key="${escapeHtml(nestedKey)}" data-item-index="${nestedIndex}" data-deep-key="${escapeHtml(deepKey)}" data-mode="number">
                    </div>
                  `;
                }
                if (isScalarValue(deepValue)) {
                  const longField = isLongTextField(deepKey, deepValue);
                  const imageField = typeof deepValue === "string" && isImageLikeKey(deepKey, nestedKey);
                  if (longField) {
                    return `
                      <div class="field builder-full">
                        <label>${escapeHtml(deepKey)}</label>
                        <textarea data-builder-nested-object-field="${blockIndex}" data-key="${escapeHtml(key)}" data-parent-index="${parentIndex}" data-nested-key="${escapeHtml(nestedKey)}" data-item-index="${nestedIndex}" data-deep-key="${escapeHtml(deepKey)}" data-mode="string">${escapeHtml(deepValue ?? "")}</textarea>
                        ${imageField ? renderMediaPicker("nested-object", {
                          "target-block": blockIndex,
                          "target-key": key,
                          "target-parent-index": parentIndex,
                          "target-nested-key": nestedKey,
                          "target-item-index": nestedIndex,
                          "target-deep-key": deepKey,
                        }) : ""}
                      </div>
                    `;
                  }
                  if (imageField) {
                    return `
                      <div class="field builder-full">
                        <label>${escapeHtml(deepKey)}</label>
                        <input value="${escapeHtml(deepValue ?? "")}" data-builder-nested-object-field="${blockIndex}" data-key="${escapeHtml(key)}" data-parent-index="${parentIndex}" data-nested-key="${escapeHtml(nestedKey)}" data-item-index="${nestedIndex}" data-deep-key="${escapeHtml(deepKey)}" data-mode="string">
                        ${renderMediaPicker("nested-object", {
                          "target-block": blockIndex,
                          "target-key": key,
                          "target-parent-index": parentIndex,
                          "target-nested-key": nestedKey,
                          "target-item-index": nestedIndex,
                          "target-deep-key": deepKey,
                        })}
                      </div>
                    `;
                  }
                  return `
                    <div class="field">
                      <label>${escapeHtml(deepKey)}</label>
                      <input value="${escapeHtml(deepValue ?? "")}" data-builder-nested-object-field="${blockIndex}" data-key="${escapeHtml(key)}" data-parent-index="${parentIndex}" data-nested-key="${escapeHtml(nestedKey)}" data-item-index="${nestedIndex}" data-deep-key="${escapeHtml(deepKey)}" data-mode="string">
                    </div>
                  `;
                }
                return `
                  <div class="field builder-full">
                    <label>${escapeHtml(deepKey)} (JSON)</label>
                    <textarea class="builder-json" data-builder-nested-object-json="${blockIndex}" data-key="${escapeHtml(key)}" data-parent-index="${parentIndex}" data-nested-key="${escapeHtml(nestedKey)}" data-item-index="${nestedIndex}" data-deep-key="${escapeHtml(deepKey)}">${escapeHtml(JSON.stringify(deepValue, null, 2))}</textarea>
                  </div>
                `;
              }).join("");

              return `
                <article class="builder-repeater-card" draggable="true" data-builder-drag-scope="nested-object" data-builder-drag-block="${blockIndex}" data-builder-drag-key="${escapeHtml(key)}" data-builder-drag-parent-index="${parentIndex}" data-builder-drag-nested-key="${escapeHtml(nestedKey)}" data-builder-drag-item-index="${nestedIndex}">
                  <div class="builder-repeater-card-header">
                    <div class="builder-repeater-card-title">
                      <span class="chip">${nestedIndex + 1}</span>
                      <strong>${escapeHtml(title)}</strong>
                    </div>
                    <div class="builder-repeater-card-actions">
                      <button class="btn btn-secondary" type="button" data-builder-nested-action="nested-object-up" data-index="${blockIndex}" data-key="${escapeHtml(key)}" data-parent-index="${parentIndex}" data-nested-key="${escapeHtml(nestedKey)}" data-item-index="${nestedIndex}">Subir</button>
                      <button class="btn btn-secondary" type="button" data-builder-nested-action="nested-object-down" data-index="${blockIndex}" data-key="${escapeHtml(key)}" data-parent-index="${parentIndex}" data-nested-key="${escapeHtml(nestedKey)}" data-item-index="${nestedIndex}">Bajar</button>
                      <button class="btn btn-secondary" type="button" data-builder-nested-action="nested-object-duplicate" data-index="${blockIndex}" data-key="${escapeHtml(key)}" data-parent-index="${parentIndex}" data-nested-key="${escapeHtml(nestedKey)}" data-item-index="${nestedIndex}">Duplicar</button>
                      <button class="btn btn-danger" type="button" data-builder-nested-action="nested-object-remove" data-index="${blockIndex}" data-key="${escapeHtml(key)}" data-parent-index="${parentIndex}" data-nested-key="${escapeHtml(nestedKey)}" data-item-index="${nestedIndex}">Eliminar</button>
                    </div>
                  </div>
                  <div class="builder-repeater-fields">
                    ${fields}
                  </div>
                </article>
              `;
            }).join("")}
            <button class="btn btn-secondary" type="button" data-builder-nested-action="nested-object-add" data-index="${blockIndex}" data-key="${escapeHtml(key)}" data-parent-index="${parentIndex}" data-nested-key="${escapeHtml(nestedKey)}">Añadir ${escapeHtml(nestedKey)}</button>
          </div>
        </div>
      `;
    }

    function renderRepeaterArray(blockIndex, key, values) {
      const list = Array.isArray(values) ? values : [];
      const isScalarArray = isSimpleScalarArray(list);
      const isObjectArray = isSimpleObjectArray(list);

      if (!isScalarArray && !isObjectArray) {
        return `
          <div class="field builder-full">
            <label>${escapeHtml(key)} (JSON)</label>
            <textarea class="builder-json" data-builder-field="${blockIndex}" data-key="${escapeHtml(key)}" data-mode="json">${escapeHtml(JSON.stringify(values, null, 2))}</textarea>
          </div>
        `;
      }

      if (isScalarArray) {
        const items = list.length ? list : [createDefaultScalarItem(key)];
        return `
          <div class="builder-full builder-repeater">
            <div class="builder-repeater-toolbar">
              <div>
                <label style="margin:0">${escapeHtml(key)}</label>
                <div class="small">Lista simple editable sin tocar JSON.</div>
              </div>
              <button class="btn btn-secondary" type="button" data-builder-array-action="add-scalar" data-index="${blockIndex}" data-key="${escapeHtml(key)}">Añadir elemento</button>
            </div>
            <div class="builder-inline-list">
              ${items.map((item, itemIndex) => `
                <div class="builder-inline-item">
                  <input value="${escapeHtml(item ?? "")}" data-builder-scalar-item="${blockIndex}" data-key="${escapeHtml(key)}" data-item-index="${itemIndex}">
                  <div class="builder-inline-actions">
                    <button class="btn btn-secondary" type="button" data-builder-array-action="scalar-up" data-index="${blockIndex}" data-key="${escapeHtml(key)}" data-item-index="${itemIndex}">↑</button>
                    <button class="btn btn-secondary" type="button" data-builder-array-action="scalar-down" data-index="${blockIndex}" data-key="${escapeHtml(key)}" data-item-index="${itemIndex}">↓</button>
                    <button class="btn btn-secondary" type="button" data-builder-array-action="scalar-duplicate" data-index="${blockIndex}" data-key="${escapeHtml(key)}" data-item-index="${itemIndex}">Duplicar</button>
                    <button class="btn btn-danger" type="button" data-builder-array-action="scalar-remove" data-index="${blockIndex}" data-key="${escapeHtml(key)}" data-item-index="${itemIndex}">Eliminar</button>
                  </div>
                </div>
              `).join("")}
            </div>
          </div>
        `;
      }

      return `
        <div class="builder-full builder-repeater">
          <div class="builder-repeater-toolbar">
            <div>
              <label style="margin:0">${escapeHtml(key)}</label>
              <div class="small">Edita cards, items y sublistas sin entrar en JSON.</div>
            </div>
            <button class="btn btn-secondary" type="button" data-builder-array-action="add-object" data-index="${blockIndex}" data-key="${escapeHtml(key)}">Añadir item</button>
          </div>
          <div class="builder-repeater-list">
            ${list.map((item, itemIndex) => {
              const title = item.title || item.name || item.text || item.label || item.q || item.category || `Item ${itemIndex + 1}`;
              const scalarFields = [];
              const nestedFields = [];
              Object.entries(item).forEach(([nestedKey, nestedValue]) => {
                if (isSimpleScalarArray(nestedValue)) {
                  nestedFields.push(renderNestedScalarList(blockIndex, key, itemIndex, nestedKey, nestedValue));
                  return;
                }
                if (isSimpleObjectArray(nestedValue)) {
                  nestedFields.push(renderNestedObjectArray(blockIndex, key, itemIndex, nestedKey, nestedValue));
                  return;
                }
                if (isScalarValue(nestedValue)) {
                  const imageField = typeof nestedValue === "string" && isImageLikeKey(nestedKey, key);
                  if (typeof nestedValue === "boolean") {
                    scalarFields.push(`
                      <label class="check builder-full">
                        <input type="checkbox" data-builder-object-field="${blockIndex}" data-key="${escapeHtml(key)}" data-item-index="${itemIndex}" data-nested-key="${escapeHtml(nestedKey)}" data-mode="boolean" ${nestedValue ? "checked" : ""}>
                        ${escapeHtml(nestedKey)}
                      </label>
                    `);
                    return;
                  }
                  if (typeof nestedValue === "number") {
                    scalarFields.push(`
                      <div class="field">
                        <label>${escapeHtml(nestedKey)}</label>
                        <input type="number" value="${escapeHtml(nestedValue)}" data-builder-object-field="${blockIndex}" data-key="${escapeHtml(key)}" data-item-index="${itemIndex}" data-nested-key="${escapeHtml(nestedKey)}" data-mode="number">
                      </div>
                    `);
                    return;
                  }
                  const longField = isLongTextField(nestedKey, nestedValue);
                  if (longField) {
                    scalarFields.push(`
                      <div class="field builder-full">
                        <label>${escapeHtml(nestedKey)}</label>
                        <textarea data-builder-object-field="${blockIndex}" data-key="${escapeHtml(key)}" data-item-index="${itemIndex}" data-nested-key="${escapeHtml(nestedKey)}" data-mode="string">${escapeHtml(nestedValue ?? "")}</textarea>
                        ${imageField ? renderMediaPicker("object", {
                          "target-block": blockIndex,
                          "target-key": key,
                          "target-item-index": itemIndex,
                          "target-nested-key": nestedKey,
                        }) : ""}
                      </div>
                    `);
                  } else {
                    scalarFields.push(`
                      <div class="field ${imageField ? "builder-full" : ""}">
                        <label>${escapeHtml(nestedKey)}</label>
                        <input value="${escapeHtml(nestedValue ?? "")}" data-builder-object-field="${blockIndex}" data-key="${escapeHtml(key)}" data-item-index="${itemIndex}" data-nested-key="${escapeHtml(nestedKey)}" data-mode="string">
                        ${imageField ? renderMediaPicker("object", {
                          "target-block": blockIndex,
                          "target-key": key,
                          "target-item-index": itemIndex,
                          "target-nested-key": nestedKey,
                        }) : ""}
                      </div>
                    `);
                  }
                  return;
                }
                nestedFields.push(`
                  <div class="field builder-full">
                    <label>${escapeHtml(nestedKey)} (JSON)</label>
                    <textarea class="builder-json" data-builder-object-json="${blockIndex}" data-key="${escapeHtml(key)}" data-item-index="${itemIndex}" data-nested-key="${escapeHtml(nestedKey)}">${escapeHtml(JSON.stringify(nestedValue, null, 2))}</textarea>
                  </div>
                `);
              });

              return `
                <article class="builder-repeater-card" draggable="true" data-builder-drag-scope="object" data-builder-drag-block="${blockIndex}" data-builder-drag-key="${escapeHtml(key)}" data-builder-drag-item-index="${itemIndex}">
                  <div class="builder-repeater-card-header">
                    <div class="builder-repeater-card-title">
                      <span class="chip">${itemIndex + 1}</span>
                      <strong>${escapeHtml(title)}</strong>
                    </div>
                    <div class="builder-repeater-card-actions">
                      <button class="btn btn-secondary" type="button" data-builder-array-action="object-up" data-index="${blockIndex}" data-key="${escapeHtml(key)}" data-item-index="${itemIndex}">Subir</button>
                      <button class="btn btn-secondary" type="button" data-builder-array-action="object-down" data-index="${blockIndex}" data-key="${escapeHtml(key)}" data-item-index="${itemIndex}">Bajar</button>
                      <button class="btn btn-secondary" type="button" data-builder-array-action="object-duplicate" data-index="${blockIndex}" data-key="${escapeHtml(key)}" data-item-index="${itemIndex}">Duplicar</button>
                      <button class="btn btn-danger" type="button" data-builder-array-action="object-remove" data-index="${blockIndex}" data-key="${escapeHtml(key)}" data-item-index="${itemIndex}">Eliminar</button>
                    </div>
                  </div>
                  <div class="builder-repeater-fields">
                    ${scalarFields.join("")}
                    ${nestedFields.join("")}
                  </div>
                </article>
              `;
            }).join("")}
          </div>
        </div>
      `;
    }

    function renderBuilderTemplateLibrary() {
      if (!builderTemplateGrid) return;
      if (builderInsertHint) {
        const insertLabel = pendingInsertIndex <= 0
          ? "al principio de la página"
          : pendingInsertIndex >= capsuleState.blocks.length
            ? "al final de la cápsula"
            : `después del bloque ${pendingInsertIndex}`;
        builderInsertHint.textContent = `Elige una plantilla base y el CMS la insertará ${insertLabel}.`;
      }
      builderTemplateGrid.innerHTML = capsuleBuilderTemplates.map((template, index) => `
        <article class="builder-template">
          <span>${escapeHtml(template.category || "Bloque")}</span>
          <strong>${escapeHtml(template.label || template.type || "Block")}</strong>
          <div class="small">${escapeHtml(template.type || "")}</div>
          <button class="btn btn-secondary" type="button" data-builder-add="${index}">Insertar aquí</button>
        </article>
      `).join("");
    }

    function renderBuilderBlocks() {
      if (!builderList) return;
      if (!capsuleState.blocks.length) {
        activeBuilderBlockIndex = -1;
        builderList.innerHTML = '<div class="builder-empty">Todavía no hay bloques en la cápsula. Usa la biblioteca de arriba para añadir uno.</div>';
        renderBuilderContext();
        syncCapsuleTextarea();
        return;
      }
      if (activeBuilderBlockIndex < 0 || activeBuilderBlockIndex >= capsuleState.blocks.length) {
        activeBuilderBlockIndex = 0;
      }
      const pieces = [];
      const renderInsertSlot = (slotIndex) => `
        <div class="builder-insert-slot ${pendingInsertIndex === slotIndex ? "is-active" : ""}">
          <button class="btn btn-secondary" type="button" data-builder-insert-slot="${slotIndex}">+ Insertar aquí</button>
          <div class="small">${slotIndex === 0 ? "Antes del primer bloque" : slotIndex >= capsuleState.blocks.length ? "Después del último bloque" : `Entre ${slotIndex} y ${slotIndex + 1}`}</div>
        </div>
      `;
      pieces.push(renderInsertSlot(0));
      capsuleState.blocks.forEach((block, index) => {
        const scalarFields = [];
        const complexFields = [];
        const styleFields = blockStyleFields.map((field) => renderBlockStyleField(index, field, block.style?.[field.key] ?? ""));
        Object.entries(block.props || {}).forEach(([key, value]) => {
          if (Array.isArray(value)) {
            complexFields.push(renderRepeaterArray(index, key, value));
            return;
          }
          if (value && typeof value === "object") {
            complexFields.push(`
              <div class="field builder-full">
                <label>${escapeHtml(key)} (JSON)</label>
                <textarea class="builder-json" data-builder-field="${index}" data-key="${escapeHtml(key)}" data-mode="json">${escapeHtml(JSON.stringify(value, null, 2))}</textarea>
              </div>
            `);
            return;
          }
          if (typeof value === "boolean") {
            scalarFields.push(`
              <label class="check builder-full">
                <input type="checkbox" data-builder-field="${index}" data-key="${escapeHtml(key)}" data-mode="boolean" ${value ? "checked" : ""}>
                ${escapeHtml(key)}
              </label>
            `);
            return;
          }
          if (typeof value === "number") {
            scalarFields.push(`
              <div class="field">
                <label>${escapeHtml(key)}</label>
                <input type="number" data-builder-field="${index}" data-key="${escapeHtml(key)}" data-mode="number" value="${escapeHtml(value)}">
              </div>
            `);
            return;
          }
          const inputTag = isLongTextField(key, value) ? "textarea" : "input";
          const imageField = typeof value === "string" && isImageLikeKey(key);
          const valueAttr = escapeHtml(value);
          if (inputTag === "textarea") {
            scalarFields.push(`
              <div class="field builder-full">
                <label>${escapeHtml(key)}</label>
                <textarea data-builder-field="${index}" data-key="${escapeHtml(key)}" data-mode="string">${valueAttr}</textarea>
                ${imageField ? renderMediaPicker("field", {
                  "target-block": index,
                  "target-key": key,
                }) : ""}
              </div>
            `);
          } else {
            scalarFields.push(`
              <div class="field ${imageField ? "builder-full" : ""}">
                <label>${escapeHtml(key)}</label>
                <input data-builder-field="${index}" data-key="${escapeHtml(key)}" data-mode="string" value="${valueAttr}">
                ${imageField ? renderMediaPicker("field", {
                  "target-block": index,
                  "target-key": key,
                }) : ""}
              </div>
            `);
          }
        });
        const summaryText = scalarFields.length || complexFields.length
          ? "Haz clic para desplegar y editar contenido, listas, imágenes y estilo."
          : "Este bloque no tiene campos visuales detectados. Puedes seguir editándolo desde el JSON de la cápsula.";
        pieces.push(`
          <article class="builder-block ${activeBuilderBlockIndex === index ? "is-selected" : ""}" data-builder-block="${index}">
            <div class="builder-block-header">
              <div class="builder-block-title">
                <span class="chip">${index + 1} · ${escapeHtml(block.type)}</span>
                <strong>${escapeHtml((block.props && (block.props.title || block.props.brand || block.props.badge)) || block.type)}</strong>
                <div class="small">${escapeHtml(block.id || "")}</div>
              </div>
              <div class="builder-actions">
                <button class="btn btn-secondary" type="button" data-builder-action="select" data-index="${index}">${activeBuilderBlockIndex === index ? "Editando" : "Editar"}</button>
                <button class="btn btn-secondary" type="button" data-builder-action="up" data-index="${index}">Subir</button>
                <button class="btn btn-secondary" type="button" data-builder-action="down" data-index="${index}">Bajar</button>
                <button class="btn btn-secondary" type="button" data-builder-action="duplicate" data-index="${index}">Duplicar</button>
                <button class="btn btn-danger" type="button" data-builder-action="remove" data-index="${index}">Eliminar</button>
              </div>
            </div>
            <div class="builder-block-summary">${escapeHtml(summaryText)}</div>
            <div class="builder-block-body">
              <div class="builder-fields">
                ${scalarFields.join("")}
                ${complexFields.length ? `<div class="builder-full builder-note">Las listas y cards del bloque ya se editan de forma visual. El JSON queda como fallback solo para estructuras especiales.</div>${complexFields.join("")}` : ""}
                <div class="builder-full builder-subsection">
                  <h4>Layout y estilo del bloque</h4>
                  <div class="builder-style-grid">
                    ${styleFields.join("")}
                  </div>
                </div>
              </div>
            </div>
          </article>
        `);
        pieces.push(renderInsertSlot(index + 1));
      });
      builderList.innerHTML = pieces.join("");
      renderBuilderContext();
      syncCapsuleTextarea();
    }

    function addBuilderBlock(templateIndex) {
      if (builderReadOnly) return;
      const template = capsuleBuilderTemplates[templateIndex];
      if (!template) return;
      const insertAt = normalizeInsertIndex(pendingInsertIndex);
      capsuleState.blocks.splice(insertAt, 0, {
        id: createBlockId(),
        type: template.type,
        props: deepClone(template.props || {}),
        style: {},
      });
      selectBuilderBlock(insertAt, { scroll: true, syncPreview: true });
    }

    function ensureArrayProp(blockIndex, key) {
      capsuleState.blocks[blockIndex].props ||= {};
      if (!Array.isArray(capsuleState.blocks[blockIndex].props[key])) {
        capsuleState.blocks[blockIndex].props[key] = [];
      }
      return capsuleState.blocks[blockIndex].props[key];
    }

    function moveArrayItem(list, fromIndex, toIndex) {
      if (!Array.isArray(list)) return;
      if (fromIndex === toIndex || fromIndex < 0 || toIndex < 0 || fromIndex >= list.length || toIndex > list.length) return;
      const [moved] = list.splice(fromIndex, 1);
      const normalizedIndex = fromIndex < toIndex ? toIndex - 1 : toIndex;
      list.splice(normalizedIndex, 0, moved);
    }

    function clearRepeaterDragClasses() {
      if (!builderList) return;
      builderList.querySelectorAll(".builder-repeater-card.is-dragging,.builder-repeater-card.is-drop-target").forEach((card) => {
        card.classList.remove("is-dragging", "is-drop-target");
      });
    }

    function parseCardDragMeta(card) {
      if (!card) return null;
      const scope = card.dataset.builderDragScope || "";
      const blockIndex = Number(card.dataset.builderDragBlock || -1);
      const key = card.dataset.builderDragKey || "";
      const itemIndex = Number(card.dataset.builderDragItemIndex || -1);
      const parentIndex = Number(card.dataset.builderDragParentIndex || -1);
      const nestedKey = card.dataset.builderDragNestedKey || "";
      if (!scope || blockIndex < 0 || !key || itemIndex < 0) return null;
      return { scope, blockIndex, key, itemIndex, parentIndex, nestedKey };
    }

    function sameRepeaterScope(a, b) {
      if (!a || !b) return false;
      if (a.scope !== b.scope) return false;
      if (a.blockIndex !== b.blockIndex || a.key !== b.key) return false;
      if (a.scope === "nested-object") {
        return a.parentIndex === b.parentIndex && a.nestedKey === b.nestedKey;
      }
      return true;
    }

    function applyMediaSelection(button) {
      if (builderReadOnly) return false;
      const url = button.dataset.mediaUrl || "";
      const scope = button.dataset.builderPickMedia || "";
      if (!url || !scope) return false;
      let selector = "";
      if (scope === "field") {
        selector = `[data-builder-field="${button.dataset.targetBlock || ""}"][data-key="${button.dataset.targetKey || ""}"][data-mode="string"]`;
      } else if (scope === "object") {
        selector = `[data-builder-object-field="${button.dataset.targetBlock || ""}"][data-key="${button.dataset.targetKey || ""}"][data-item-index="${button.dataset.targetItemIndex || ""}"][data-nested-key="${button.dataset.targetNestedKey || ""}"][data-mode="string"]`;
      } else if (scope === "nested-object") {
        selector = `[data-builder-nested-object-field="${button.dataset.targetBlock || ""}"][data-key="${button.dataset.targetKey || ""}"][data-parent-index="${button.dataset.targetParentIndex || ""}"][data-nested-key="${button.dataset.targetNestedKey || ""}"][data-item-index="${button.dataset.targetItemIndex || ""}"][data-deep-key="${button.dataset.targetDeepKey || ""}"][data-mode="string"]`;
      }
      if (!selector) return false;
      const target = builderList ? builderList.querySelector(selector) : null;
      if (!target) return false;
      target.value = url;
      target.dispatchEvent(new Event("input", { bubbles: true }));
      const details = button.closest("details");
      if (details) details.open = false;
      return true;
    }

    async function refreshPreview() {
      if (!preview || !pageEditorForm) return;
      try {
        const formData = new FormData(pageEditorForm);
        const response = await fetch(previewEndpoint, {
          method: "POST",
          body: formData,
          credentials: "same-origin",
          headers: { "X-Requested-With": "XMLHttpRequest" },
        });
        if (!response.ok) {
          throw new Error(`Preview request failed with ${response.status}`);
        }
        preview.srcdoc = await response.text();
      } catch (error) {
        console.warn("Preview fallback:", error);
        if (!htmlEditor) return;
        preview.srcdoc = buildPreviewDoc(pageTitle ? pageTitle.value : "", htmlEditor.value);
      }
    }

    function applyBuilderReadOnlyState() {
      if (!builderReadOnly) return;
      if (builderSyncButton) {
        builderSyncButton.disabled = true;
        builderSyncButton.textContent = "Solo lectura";
      }
      [builderTemplateGrid, builderGlobalStyle, builderList, builderContext].forEach((container) => {
        if (!container) return;
        container.querySelectorAll("button, input, select, textarea").forEach((element) => {
          element.disabled = true;
        });
        container.querySelectorAll(".builder-repeater-card[draggable]").forEach((card) => {
          card.setAttribute("draggable", "false");
        });
      });
    }

    document.querySelectorAll("[data-insert-template]").forEach((button) => {
      button.addEventListener("click", () => {
        const index = Number(button.dataset.insertTemplate || -1);
        const template = sectionTemplates[index];
        if (!template || !htmlEditor) return;
        insertAtCursor(htmlEditor, template.html);
        refreshPreview();
      });
    });

    document.querySelectorAll("[data-insert-media]").forEach((button) => {
      button.addEventListener("click", () => {
        const url = button.dataset.insertMedia || "";
        if (!url || !htmlEditor) return;
        insertAtCursor(htmlEditor, `<img src="${url}" alt="" style="width:100%;height:auto;border-radius:18px">`);
        refreshPreview();
      });
    });

    document.querySelectorAll("[data-copy-url]").forEach((button) => {
      button.addEventListener("click", async () => {
        const url = button.dataset.copyUrl || "";
        if (!url) return;
        try {
          await navigator.clipboard.writeText(url);
          button.textContent = "URL copiada";
          setTimeout(() => { button.textContent = "Copiar URL"; }, 1400);
        } catch (error) {
          console.error(error);
        }
      });
    });

    document.querySelectorAll("[data-sync-color]").forEach((colorInput) => {
      colorInput.addEventListener("input", () => {
        const targetId = colorInput.getAttribute("data-sync-color");
        const target = targetId ? document.getElementById(targetId) : null;
        if (target) target.value = colorInput.value;
      });
    });

    document.querySelectorAll(".js-refresh-preview").forEach((button) => {
      button.addEventListener("click", refreshPreview);
    });

    if (builderTemplateGrid) {
      builderTemplateGrid.addEventListener("click", (event) => {
        if (builderReadOnly) return;
        const button = event.target.closest("[data-builder-add]");
        if (!button) return;
        addBuilderBlock(Number(button.dataset.builderAdd || -1));
      });
    }

    if (builderList) {
      builderList.addEventListener("click", (event) => {
        const insertSlot = event.target.closest("[data-builder-insert-slot]");
        if (insertSlot) {
          if (builderReadOnly) return;
          const slotIndex = Number(insertSlot.dataset.builderInsertSlot || -1);
          if (slotIndex >= 0) {
            setPendingInsertIndex(slotIndex);
          }
          return;
        }
        const block = event.target.closest("[data-builder-block]");
        const actionButton = event.target.closest("[data-builder-action],[data-builder-array-action],[data-builder-nested-action],[data-builder-pick-media]");
        if (!block || actionButton) return;
        const index = Number(block.dataset.builderBlock || -1);
        if (index >= 0) {
          selectBuilderBlock(index, { scroll: false, syncPreview: true });
        }
      });

      builderList.addEventListener("dragstart", (event) => {
        if (builderReadOnly) return;
        const card = event.target.closest(".builder-repeater-card[draggable='true']");
        const meta = parseCardDragMeta(card);
        if (!card || !meta) return;
        builderDragState = meta;
        card.classList.add("is-dragging");
        if (event.dataTransfer) {
          event.dataTransfer.effectAllowed = "move";
          event.dataTransfer.setData("text/plain", JSON.stringify(meta));
        }
      });

      builderList.addEventListener("dragover", (event) => {
        if (builderReadOnly) return;
        const card = event.target.closest(".builder-repeater-card[draggable='true']");
        const meta = parseCardDragMeta(card);
        if (!card || !meta || !builderDragState || !sameRepeaterScope(builderDragState, meta) || builderDragState.itemIndex === meta.itemIndex) return;
        event.preventDefault();
        clearRepeaterDragClasses();
        card.classList.add("is-drop-target");
        const dragging = builderList.querySelector(".builder-repeater-card.is-dragging");
        if (dragging) dragging.classList.add("is-dragging");
      });

      builderList.addEventListener("dragleave", (event) => {
        if (builderReadOnly) return;
        const card = event.target.closest(".builder-repeater-card[draggable='true']");
        if (!card) return;
        const related = event.relatedTarget;
        if (related && card.contains(related)) return;
        card.classList.remove("is-drop-target");
      });

      builderList.addEventListener("drop", (event) => {
        if (builderReadOnly) return;
        const card = event.target.closest(".builder-repeater-card[draggable='true']");
        const meta = parseCardDragMeta(card);
        if (!card || !meta || !builderDragState || !sameRepeaterScope(builderDragState, meta) || builderDragState.itemIndex === meta.itemIndex) return;
        event.preventDefault();
        const rect = card.getBoundingClientRect();
        const insertAfter = event.clientY > rect.top + rect.height / 2;
        if (meta.scope === "object") {
          const list = ensureArrayProp(meta.blockIndex, meta.key);
          moveArrayItem(list, builderDragState.itemIndex, meta.itemIndex + (insertAfter ? 1 : 0));
        } else if (meta.scope === "nested-object") {
          const list = ensureArrayProp(meta.blockIndex, meta.key);
          if (!isPlainObject(list[meta.parentIndex])) list[meta.parentIndex] = {};
          if (!Array.isArray(list[meta.parentIndex][meta.nestedKey])) list[meta.parentIndex][meta.nestedKey] = [];
          moveArrayItem(list[meta.parentIndex][meta.nestedKey], builderDragState.itemIndex, meta.itemIndex + (insertAfter ? 1 : 0));
        }
        builderDragState = null;
        clearRepeaterDragClasses();
        renderBuilderBlocks();
      });

      builderList.addEventListener("dragend", () => {
        if (builderReadOnly) return;
        builderDragState = null;
        clearRepeaterDragClasses();
      });

      builderList.addEventListener("click", (event) => {
        if (builderReadOnly) return;
        const mediaButton = event.target.closest("[data-builder-pick-media]");
        if (!mediaButton) return;
        event.preventDefault();
        applyMediaSelection(mediaButton);
      });

      builderList.addEventListener("click", (event) => {
        if (builderReadOnly) return;
        const button = event.target.closest("[data-builder-action]");
        if (!button) return;
        const index = Number(button.dataset.index || -1);
        if (index < 0 || index >= capsuleState.blocks.length) return;
        const action = button.dataset.builderAction;
        if (action === "select") {
          selectBuilderBlock(index, { scroll: true, syncPreview: true });
          return;
        } else if (action === "up" && index > 0) {
          [capsuleState.blocks[index - 1], capsuleState.blocks[index]] = [capsuleState.blocks[index], capsuleState.blocks[index - 1]];
          activeBuilderBlockIndex = index - 1;
        } else if (action === "down" && index < capsuleState.blocks.length - 1) {
          [capsuleState.blocks[index + 1], capsuleState.blocks[index]] = [capsuleState.blocks[index], capsuleState.blocks[index + 1]];
          activeBuilderBlockIndex = index + 1;
        } else if (action === "duplicate") {
          const copy = deepClone(capsuleState.blocks[index]);
          copy.id = createBlockId();
          capsuleState.blocks.splice(index + 1, 0, copy);
          activeBuilderBlockIndex = index + 1;
        } else if (action === "remove") {
          capsuleState.blocks.splice(index, 1);
          activeBuilderBlockIndex = capsuleState.blocks.length ? Math.max(0, Math.min(index, capsuleState.blocks.length - 1)) : -1;
        }
        if (activeBuilderBlockIndex >= 0) {
          selectBuilderBlock(activeBuilderBlockIndex, { scroll: true, syncPreview: true });
        } else {
          renderBuilderBlocks();
        }
      });

      builderList.addEventListener("click", (event) => {
        if (builderReadOnly) return;
        const button = event.target.closest("[data-builder-array-action]");
        if (!button) return;
        const blockIndex = Number(button.dataset.index || -1);
        const key = button.dataset.key || "";
        const itemIndex = Number(button.dataset.itemIndex || -1);
        if (blockIndex < 0 || blockIndex >= capsuleState.blocks.length || !key) return;
        const action = button.dataset.builderArrayAction;
        const list = ensureArrayProp(blockIndex, key);
        if (action === "add-scalar") {
          list.push(createDefaultScalarItem(key));
        } else if (action === "scalar-up" && itemIndex > 0) {
          [list[itemIndex - 1], list[itemIndex]] = [list[itemIndex], list[itemIndex - 1]];
        } else if (action === "scalar-down" && itemIndex >= 0 && itemIndex < list.length - 1) {
          [list[itemIndex + 1], list[itemIndex]] = [list[itemIndex], list[itemIndex + 1]];
        } else if (action === "scalar-duplicate" && itemIndex >= 0) {
          list.splice(itemIndex + 1, 0, deepClone(list[itemIndex]));
        } else if (action === "scalar-remove" && itemIndex >= 0) {
          list.splice(itemIndex, 1);
        } else if (action === "add-object") {
          const base = list[0] && isPlainObject(list[0]) ? deepClone(list[0]) : createDefaultObjectItem(key);
          list.push(base);
        } else if (action === "object-up" && itemIndex > 0) {
          [list[itemIndex - 1], list[itemIndex]] = [list[itemIndex], list[itemIndex - 1]];
        } else if (action === "object-down" && itemIndex >= 0 && itemIndex < list.length - 1) {
          [list[itemIndex + 1], list[itemIndex]] = [list[itemIndex], list[itemIndex + 1]];
        } else if (action === "object-duplicate" && itemIndex >= 0) {
          list.splice(itemIndex + 1, 0, deepClone(list[itemIndex]));
        } else if (action === "object-remove" && itemIndex >= 0) {
          list.splice(itemIndex, 1);
        }
        renderBuilderBlocks();
      });

      builderList.addEventListener("click", (event) => {
        if (builderReadOnly) return;
        const button = event.target.closest("[data-builder-nested-action]");
        if (!button) return;
        const blockIndex = Number(button.dataset.index || -1);
        const key = button.dataset.key || "";
        const nestedKey = button.dataset.nestedKey || "";
        const parentIndex = Number(button.dataset.parentIndex || -1);
        const itemIndex = Number(button.dataset.itemIndex || -1);
        if (blockIndex < 0 || blockIndex >= capsuleState.blocks.length || !key || !nestedKey || parentIndex < 0) return;
        const list = ensureArrayProp(blockIndex, key);
        if (!isPlainObject(list[parentIndex])) list[parentIndex] = {};
        if (!Array.isArray(list[parentIndex][nestedKey])) list[parentIndex][nestedKey] = [];
        const nestedList = list[parentIndex][nestedKey];
        const action = button.dataset.builderNestedAction;
        if (action === "nested-scalar-add") {
          nestedList.push(createDefaultScalarItem(nestedKey));
        } else if (action === "nested-scalar-up" && itemIndex > 0) {
          [nestedList[itemIndex - 1], nestedList[itemIndex]] = [nestedList[itemIndex], nestedList[itemIndex - 1]];
        } else if (action === "nested-scalar-down" && itemIndex >= 0 && itemIndex < nestedList.length - 1) {
          [nestedList[itemIndex + 1], nestedList[itemIndex]] = [nestedList[itemIndex], nestedList[itemIndex + 1]];
        } else if (action === "nested-scalar-remove" && itemIndex >= 0) {
          nestedList.splice(itemIndex, 1);
        } else if (action === "nested-object-add") {
          nestedList.push(isPlainObject(nestedList[0]) ? deepClone(nestedList[0]) : createDefaultObjectItem(nestedKey));
        } else if (action === "nested-object-up" && itemIndex > 0) {
          [nestedList[itemIndex - 1], nestedList[itemIndex]] = [nestedList[itemIndex], nestedList[itemIndex - 1]];
        } else if (action === "nested-object-down" && itemIndex >= 0 && itemIndex < nestedList.length - 1) {
          [nestedList[itemIndex + 1], nestedList[itemIndex]] = [nestedList[itemIndex], nestedList[itemIndex + 1]];
        } else if (action === "nested-object-duplicate" && itemIndex >= 0) {
          nestedList.splice(itemIndex + 1, 0, deepClone(nestedList[itemIndex]));
        } else if (action === "nested-object-remove" && itemIndex >= 0) {
          nestedList.splice(itemIndex, 1);
        }
        renderBuilderBlocks();
      });

      builderList.addEventListener("input", (event) => {
        if (builderReadOnly) return;
        const field = event.target.closest("[data-builder-field]");
        if (!field) return;
        const index = Number(field.dataset.builderField || -1);
        const key = field.dataset.key || "";
        if (index < 0 || index >= capsuleState.blocks.length || !key) return;
        const mode = field.dataset.mode || "string";
        if (mode === "boolean") {
          capsuleState.blocks[index].props[key] = !!field.checked;
        } else if (mode === "number") {
          capsuleState.blocks[index].props[key] = field.value === "" ? 0 : Number(field.value);
        } else if (mode === "string") {
          capsuleState.blocks[index].props[key] = field.value;
        }
        syncCapsuleTextarea();
      });

      builderList.addEventListener("input", (event) => {
        if (builderReadOnly) return;
        const field = event.target.closest("[data-builder-scalar-item]");
        if (!field) return;
        const blockIndex = Number(field.dataset.builderScalarItem || -1);
        const key = field.dataset.key || "";
        const itemIndex = Number(field.dataset.itemIndex || -1);
        if (blockIndex < 0 || blockIndex >= capsuleState.blocks.length || !key || itemIndex < 0) return;
        const list = ensureArrayProp(blockIndex, key);
        list[itemIndex] = field.value;
        syncCapsuleTextarea();
      });

      builderList.addEventListener("input", (event) => {
        if (builderReadOnly) return;
        const field = event.target.closest("[data-builder-object-field]");
        if (!field) return;
        const blockIndex = Number(field.dataset.builderObjectField || -1);
        const key = field.dataset.key || "";
        const itemIndex = Number(field.dataset.itemIndex || -1);
        const nestedKey = field.dataset.nestedKey || "";
        if (blockIndex < 0 || blockIndex >= capsuleState.blocks.length || !key || !nestedKey || itemIndex < 0) return;
        const list = ensureArrayProp(blockIndex, key);
        if (!isPlainObject(list[itemIndex])) list[itemIndex] = {};
        const mode = field.dataset.mode || "string";
        if (mode === "boolean") {
          list[itemIndex][nestedKey] = !!field.checked;
        } else if (mode === "number") {
          list[itemIndex][nestedKey] = field.value === "" ? 0 : Number(field.value);
        } else {
          list[itemIndex][nestedKey] = field.value;
        }
        syncCapsuleTextarea();
      });

      builderList.addEventListener("input", (event) => {
        if (builderReadOnly) return;
        const field = event.target.closest("[data-builder-style-field]");
        if (!field) return;
        const index = Number(field.dataset.builderStyleField || -1);
        const key = field.dataset.key || "";
        if (index < 0 || index >= capsuleState.blocks.length || !key) return;
        const mode = field.dataset.mode || "text";
        field.parentElement?.querySelectorAll?.(`[data-builder-style-field="${index}"][data-key="${key}"]`).forEach((peer) => {
          if (peer !== field && peer.value !== field.value) peer.value = field.value;
        });
        capsuleState.blocks[index].style ||= {};
        if (field.value === "") {
          delete capsuleState.blocks[index].style[key];
        } else if (mode === "number") {
          capsuleState.blocks[index].style[key] = Number(field.value);
        } else {
          capsuleState.blocks[index].style[key] = field.value;
        }
        syncCapsuleTextarea();
      });

      builderList.addEventListener("change", (event) => {
        if (builderReadOnly) return;
        const field = event.target.closest("[data-builder-field][data-mode='json']");
        if (!field) return;
        const index = Number(field.dataset.builderField || -1);
        const key = field.dataset.key || "";
        if (index < 0 || index >= capsuleState.blocks.length || !key) return;
        try {
          capsuleState.blocks[index].props[key] = JSON.parse(field.value || "[]");
          field.style.borderColor = "";
          syncCapsuleTextarea();
        } catch (error) {
          field.style.borderColor = "var(--danger)";
          window.alert(`El JSON del campo "${key}" no es válido.`);
        }
      });

      builderList.addEventListener("input", (event) => {
        if (builderReadOnly) return;
        const field = event.target.closest("[data-builder-nested-scalar]");
        if (!field) return;
        const blockIndex = Number(field.dataset.builderNestedScalar || -1);
        const key = field.dataset.key || "";
        const nestedKey = field.dataset.nestedKey || "";
        const itemIndex = Number(field.dataset.itemIndex || -1);
        const parentIndex = Number(field.dataset.parentIndex || -1);
        if (blockIndex < 0 || blockIndex >= capsuleState.blocks.length || !key || !nestedKey || itemIndex < 0 || parentIndex < 0) return;
        const list = ensureArrayProp(blockIndex, key);
        if (!isPlainObject(list[parentIndex])) list[parentIndex] = {};
        if (!Array.isArray(list[parentIndex][nestedKey])) list[parentIndex][nestedKey] = [];
        list[parentIndex][nestedKey][itemIndex] = field.value;
        syncCapsuleTextarea();
      });

      builderList.addEventListener("input", (event) => {
        if (builderReadOnly) return;
        const field = event.target.closest("[data-builder-nested-object-field]");
        if (!field) return;
        const blockIndex = Number(field.dataset.builderNestedObjectField || -1);
        const key = field.dataset.key || "";
        const nestedKey = field.dataset.nestedKey || "";
        const parentIndex = Number(field.dataset.parentIndex || -1);
        const itemIndex = Number(field.dataset.itemIndex || -1);
        const deepKey = field.dataset.deepKey || "";
        if (blockIndex < 0 || blockIndex >= capsuleState.blocks.length || !key || !nestedKey || parentIndex < 0 || itemIndex < 0 || !deepKey) return;
        const list = ensureArrayProp(blockIndex, key);
        if (!isPlainObject(list[parentIndex])) list[parentIndex] = {};
        if (!Array.isArray(list[parentIndex][nestedKey])) list[parentIndex][nestedKey] = [];
        if (!isPlainObject(list[parentIndex][nestedKey][itemIndex])) list[parentIndex][nestedKey][itemIndex] = {};
        const mode = field.dataset.mode || "string";
        if (mode === "boolean") {
          list[parentIndex][nestedKey][itemIndex][deepKey] = !!field.checked;
        } else if (mode === "number") {
          list[parentIndex][nestedKey][itemIndex][deepKey] = field.value === "" ? 0 : Number(field.value);
        } else {
          list[parentIndex][nestedKey][itemIndex][deepKey] = field.value;
        }
        syncCapsuleTextarea();
      });

      builderList.addEventListener("change", (event) => {
        if (builderReadOnly) return;
        const field = event.target.closest("[data-builder-object-json]");
        if (!field) return;
        const blockIndex = Number(field.dataset.builderObjectJson || -1);
        const key = field.dataset.key || "";
        const itemIndex = Number(field.dataset.itemIndex || -1);
        const nestedKey = field.dataset.nestedKey || "";
        if (blockIndex < 0 || blockIndex >= capsuleState.blocks.length || !key || !nestedKey || itemIndex < 0) return;
        const list = ensureArrayProp(blockIndex, key);
        if (!isPlainObject(list[itemIndex])) list[itemIndex] = {};
        try {
          list[itemIndex][nestedKey] = JSON.parse(field.value || "null");
          field.style.borderColor = "";
          syncCapsuleTextarea();
        } catch (error) {
          field.style.borderColor = "var(--danger)";
          window.alert(`El JSON del campo "${nestedKey}" no es válido.`);
        }
      });

      builderList.addEventListener("change", (event) => {
        if (builderReadOnly) return;
        const field = event.target.closest("[data-builder-nested-object-json]");
        if (!field) return;
        const blockIndex = Number(field.dataset.builderNestedObjectJson || -1);
        const key = field.dataset.key || "";
        const nestedKey = field.dataset.nestedKey || "";
        const parentIndex = Number(field.dataset.parentIndex || -1);
        const itemIndex = Number(field.dataset.itemIndex || -1);
        const deepKey = field.dataset.deepKey || "";
        if (blockIndex < 0 || blockIndex >= capsuleState.blocks.length || !key || !nestedKey || parentIndex < 0 || itemIndex < 0 || !deepKey) return;
        const list = ensureArrayProp(blockIndex, key);
        if (!isPlainObject(list[parentIndex])) list[parentIndex] = {};
        if (!Array.isArray(list[parentIndex][nestedKey])) list[parentIndex][nestedKey] = [];
        if (!isPlainObject(list[parentIndex][nestedKey][itemIndex])) list[parentIndex][nestedKey][itemIndex] = {};
        try {
          list[parentIndex][nestedKey][itemIndex][deepKey] = JSON.parse(field.value || "null");
          field.style.borderColor = "";
          syncCapsuleTextarea();
        } catch (error) {
          field.style.borderColor = "var(--danger)";
          window.alert(`El JSON del campo "${deepKey}" no es válido.`);
        }
      });
    }

    if (builderGlobalStyle) {
      builderGlobalStyle.addEventListener("input", (event) => {
        if (builderReadOnly) return;
        const field = event.target.closest("[data-builder-global-style]");
        if (!field) return;
        const key = field.dataset.builderGlobalStyle || "";
        if (!key) return;
        field.parentElement?.querySelectorAll?.(`[data-builder-global-style="${key}"]`).forEach((peer) => {
          if (peer !== field && peer.value !== field.value) peer.value = field.value;
        });
        if (field.value === "") {
          delete capsuleState.style[key];
        } else {
          capsuleState.style[key] = field.value;
        }
        syncCapsuleTextarea();
      });
    }

    if (builderSyncButton) {
      builderSyncButton.addEventListener("click", () => {
        if (builderReadOnly) return;
        syncCapsuleTextarea();
        window.alert("JSON avanzado sincronizado con las secciones.");
      });
    }

    if (builderContext) {
      builderContext.addEventListener("click", (event) => {
        if (builderReadOnly) return;
        const button = event.target.closest("[data-builder-context]");
        if (!button || activeBuilderBlockIndex < 0) return;
        const action = button.dataset.builderContext || "";
        if (action === "content") {
          selectBuilderBlock(activeBuilderBlockIndex, { scroll: true, syncPreview: true });
          focusSelectedBlockField("content");
          return;
        }
        if (action === "link") {
          selectBuilderBlock(activeBuilderBlockIndex, { scroll: true, syncPreview: true });
          focusSelectedBlockLinkField();
          return;
        }
        if (action === "media") {
          selectBuilderBlock(activeBuilderBlockIndex, { scroll: true, syncPreview: true });
          openPreviewMediaPicker();
          return;
        }
        if (action === "style") {
          selectBuilderBlock(activeBuilderBlockIndex, { scroll: true, syncPreview: true });
          focusSelectedBlockField("style");
          return;
        }
        if (action === "insert") {
          setPendingInsertIndex(activeBuilderBlockIndex + 1, { render: false });
          renderBuilderTemplateLibrary();
          renderBuilderContext();
          renderBuilderBlocks();
          builderTemplateGrid?.scrollIntoView({ block: "center", behavior: "smooth" });
          return;
        }
        if (action === "duplicate") {
          const copy = deepClone(capsuleState.blocks[activeBuilderBlockIndex]);
          copy.id = createBlockId();
          capsuleState.blocks.splice(activeBuilderBlockIndex + 1, 0, copy);
          selectBuilderBlock(activeBuilderBlockIndex + 1, { scroll: true, syncPreview: true });
          return;
        }
        if (action === "remove") {
          capsuleState.blocks.splice(activeBuilderBlockIndex, 1);
          if (capsuleState.blocks.length) {
            selectBuilderBlock(Math.max(0, Math.min(activeBuilderBlockIndex, capsuleState.blocks.length - 1)), { scroll: true, syncPreview: true });
          } else {
            renderBuilderBlocks();
          }
        }
      });
    }

    [htmlEditor, pageTitle].forEach((input) => {
      if (!input) return;
      input.addEventListener("input", () => {
        schedulePreviewRefresh();
      });
    });

    if (pageEditorForm) {
      pageEditorForm.addEventListener("submit", () => {
        syncCapsuleTextarea();
      });
    }

    if (preview) {
      preview.addEventListener("load", () => {
        if (activeBuilderBlockIndex >= 0) {
          window.setTimeout(() => highlightPreviewBlock(activeBuilderBlockIndex), 40);
        }
      });
    }

    window.addEventListener("message", (event) => {
      const payload = event.data || {};
      if (payload && payload.type === "ccms-preview-select-block") {
        const index = Number(payload.index || -1);
        if (index >= 0) {
          selectBuilderBlock(index, { scroll: true, syncPreview: false });
        }
        return;
      }
      if (payload && payload.type === "ccms-preview-quick-text") {
        const index = Number(payload.index || -1);
        if (index < 0) return;
        selectBuilderBlock(index, { scroll: true, syncPreview: false });
        if (builderReadOnly) return;
        focusSelectedBlockTextField(String(payload.text || ""), String(payload.tag || ""));
        return;
      }
      if (payload && payload.type === "ccms-preview-apply-text") {
        const index = Number(payload.index || -1);
        if (index < 0) return;
        selectBuilderBlock(index, { scroll: true, syncPreview: false });
        if (builderReadOnly) return;
        applySelectedBlockTextField(String(payload.oldText || ""), String(payload.newText || ""), String(payload.tag || ""));
        return;
      }
      if (payload && payload.type === "ccms-preview-quick-media") {
        const index = Number(payload.index || -1);
        if (index < 0) return;
        selectBuilderBlock(index, { scroll: true, syncPreview: false });
        if (builderReadOnly) return;
        openPreviewMediaPicker(String(payload.src || ""));
        return;
      }
      if (payload && payload.type === "ccms-preview-quick-link") {
        const index = Number(payload.index || -1);
        if (index < 0) return;
        selectBuilderBlock(index, { scroll: true, syncPreview: false });
        if (builderReadOnly) return;
        focusSelectedBlockLinkField(String(payload.href || ""), String(payload.text || ""));
        return;
      }
      if (payload && payload.type === "ccms-preview-apply-link") {
        const index = Number(payload.index || -1);
        if (index < 0) return;
        selectBuilderBlock(index, { scroll: true, syncPreview: false });
        if (builderReadOnly) return;
        applySelectedBlockLinkField(String(payload.oldHref || ""), String(payload.newHref || ""), String(payload.text || payload.oldText || ""));
        if (String(payload.oldText || "") !== String(payload.newText || "")) {
          applySelectedBlockTextField(String(payload.oldText || ""), String(payload.newText || ""), String(payload.tag || "a"));
        }
        return;
      }
      if (payload && payload.type === "ccms-preview-apply-button") {
        const index = Number(payload.index || -1);
        if (index < 0) return;
        selectBuilderBlock(index, { scroll: true, syncPreview: false });
        if (builderReadOnly) return;
        if (String(payload.oldHref || "") || String(payload.newHref || "")) {
          applySelectedBlockLinkField(String(payload.oldHref || ""), String(payload.newHref || ""), String(payload.oldText || payload.text || ""));
        }
        if (String(payload.oldText || "") !== String(payload.newText || "")) {
          applySelectedBlockTextField(String(payload.oldText || ""), String(payload.newText || ""), String(payload.tag || "button"));
        }
        applySelectedBlockButtonStyle({
          button_bg: String(payload.buttonBg || ""),
          button_text_color: String(payload.buttonTextColor || ""),
          button_variant: Number(payload.ghost || 0) ? "ghost" : "",
        });
        return;
      }
      if (payload && payload.type === "ccms-preview-action") {
        const index = Number(payload.index || -1);
        if (index < 0) return;
        selectBuilderBlock(index, { scroll: true, syncPreview: false });
        if (builderReadOnly) return;
        const action = payload.action || "";
        if (action === "content") {
          focusSelectedBlockField("content");
          return;
        }
        if (action === "link") {
          focusSelectedBlockLinkField(String(payload.href || ""), String(payload.text || ""));
          return;
        }
        if (action === "media") {
          openPreviewMediaPicker(String(payload.src || ""));
          return;
        }
        if (action === "style") {
          focusSelectedBlockField("style");
          return;
        }
        if (action === "insert") {
          setPendingInsertIndex(index + 1, { render: false });
          renderBuilderTemplateLibrary();
          renderBuilderContext();
          renderBuilderBlocks();
          builderTemplateGrid?.scrollIntoView({ block: "center", behavior: "smooth" });
          return;
        }
        if (action === "duplicate") {
          const copy = deepClone(capsuleState.blocks[index]);
          copy.id = createBlockId();
          capsuleState.blocks.splice(index + 1, 0, copy);
          selectBuilderBlock(index + 1, { scroll: true, syncPreview: true });
          return;
        }
        if (action === "remove") {
          capsuleState.blocks.splice(index, 1);
          if (capsuleState.blocks.length) {
            selectBuilderBlock(Math.max(0, Math.min(index, capsuleState.blocks.length - 1)), { scroll: true, syncPreview: true });
          } else {
            renderBuilderBlocks();
          }
        }
      }
    });

    renderBuilderTemplateLibrary();
    renderBuilderGlobalStyle();
    renderBuilderBlocks();
    applyBuilderReadOnlyState();
    setClientMode(getInitialClientMode());
    if (activeBuilderBlockIndex >= 0) {
      highlightPreviewBlock(activeBuilderBlockIndex);
    }
  </script>
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
