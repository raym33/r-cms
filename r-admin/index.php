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
