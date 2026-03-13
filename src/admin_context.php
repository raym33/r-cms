<?php
declare(strict_types=1);

function ccms_admin_section_templates(): array
{
    return [
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
}

function ccms_admin_capsule_builder_templates(): array
{
    return [
        ['type' => 'sticky_header', 'label' => 'Sticky Header', 'category' => 'Header', 'props' => ['brand' => 'Brand', 'announcement' => 'New release available now.', 'links' => [['text' => 'Home', 'href' => '#hero'], ['text' => 'Services', 'href' => '#features'], ['text' => 'Contact', 'href' => '#contact']], 'cta_text' => 'Start Project', 'cta_href' => '#contact']],
        ['type' => 'nav', 'label' => 'Navigation', 'category' => 'Header', 'props' => ['brand' => 'Brand', 'links' => [['text' => 'Home', 'href' => '#hero'], ['text' => 'About', 'href' => '#about'], ['text' => 'Contact', 'href' => '#contact']], 'cta_text' => 'Contact', 'cta_href' => '#contact']],
        ['type' => 'banner', 'label' => 'Promo Banner', 'category' => 'Header', 'props' => ['text' => 'Limited offer or important announcement.', 'cta_text' => 'Learn More', 'cta_href' => '#contact']],
        ['type' => 'hero_fullscreen', 'label' => 'Hero Fullscreen', 'category' => 'Hero', 'layout' => 'default', 'props' => ['badge' => 'Hero', 'title' => 'A clear promise for the visitor', 'subtitle' => 'Explain the offer in one confident paragraph.', 'background_image' => '', 'cta_primary' => 'Get Started', 'cta_secondary' => 'Learn More', 'cta_href' => '#contact']],
        ['type' => 'hero_split', 'label' => 'Hero Split', 'category' => 'Hero', 'layout' => 'default', 'props' => ['badge' => 'Hero', 'title' => 'A premium split hero', 'subtitle' => 'Combine strong copy with a supporting image.', 'image_url' => '', 'cta_primary' => 'Get Started', 'cta_secondary' => 'Learn More', 'cta_href' => '#contact']],
        ['type' => 'features', 'label' => 'Features Grid', 'category' => 'Content', 'layout' => '3-col', 'props' => ['badge' => 'Features', 'title' => 'What makes this offer valuable', 'subtitle' => 'Summarize your main benefits.', 'items' => [['title' => 'Feature one', 'desc' => 'Short explanation.'], ['title' => 'Feature two', 'desc' => 'Short explanation.'], ['title' => 'Feature three', 'desc' => 'Short explanation.']]]],
        ['type' => 'services_cards', 'label' => 'Services Cards', 'category' => 'Content', 'props' => ['badge' => 'Services', 'title' => 'Main services', 'subtitle' => 'Package the offer into cards.', 'services' => [['title' => 'Service one', 'desc' => 'What is included', 'bullets' => ['Point A', 'Point B'], 'cta_text' => 'Learn more', 'cta_href' => '#contact'], ['title' => 'Service two', 'desc' => 'What is included', 'bullets' => ['Point A', 'Point B'], 'cta_text' => 'Learn more', 'cta_href' => '#contact']]]],
        ['type' => 'split_image_left', 'label' => 'Split Image Left', 'category' => 'Content', 'props' => ['badge' => 'About', 'title' => 'Explain your difference', 'text' => 'Use this section for longer explanatory copy.', 'image_url' => '', 'bullets' => ['Point one', 'Point two', 'Point three']]],
        ['type' => 'split_image_right', 'label' => 'Split Image Right', 'category' => 'Content', 'props' => ['badge' => 'About', 'title' => 'Explain your process', 'text' => 'Use this section for longer explanatory copy.', 'image_url' => '', 'bullets' => ['Point one', 'Point two', 'Point three']]],
        ['type' => 'split_content', 'label' => 'Split Content', 'category' => 'Content', 'props' => ['badge' => 'About', 'title' => 'Why choose this brand', 'text' => 'Text with image seed-based visual.', 'image_seed' => 'studio', 'reversed' => false, 'bullets' => ['Point one', 'Point two']]],
        ['type' => 'text_block', 'label' => 'Rich Text Block', 'category' => 'Content', 'props' => ['badge' => 'Editorial', 'title' => 'Long-form explanation', 'paragraphs' => ['First paragraph.', 'Second paragraph.'], 'quote' => 'A short quote or key statement.', 'quote_author' => 'Brand name']],
        ['type' => 'menu_daily', 'label' => 'Menu Daily', 'category' => 'Business', 'props' => ['badge' => 'Actualizado hoy', 'title' => 'Menu del dia', 'subtitle' => 'Editalo rapido desde Modo Negocio.', 'price' => '11.50', 'currency' => 'EUR', 'includes' => 'Pan y bebida', 'sections' => [['name' => 'Primeros', 'items' => ['Ensalada mixta', 'Sopa del dia']], ['name' => 'Segundos', 'items' => ['Pollo al horno', 'Merluza a la plancha']], ['name' => 'Postres', 'items' => ['Flan casero', 'Fruta del tiempo']]],], 'quick_edit' => ['enabled' => true, 'source' => 'live_data', 'category' => 'menu', 'label' => 'Menu del dia', 'frequency' => 'daily']],
        ['type' => 'hours_status', 'label' => 'Hours Status', 'category' => 'Business', 'props' => ['badge' => 'Horario', 'title' => 'Abierto ahora', 'subtitle' => 'Muestra horario y estado en vivo.', 'timezone' => 'Europe/Madrid', 'days' => ['mon' => ['closed' => false, 'slots' => [['open' => '09:00', 'close' => '14:00'], ['open' => '17:00', 'close' => '20:00']]], 'tue' => ['closed' => false, 'slots' => [['open' => '09:00', 'close' => '14:00'], ['open' => '17:00', 'close' => '20:00']]], 'wed' => ['closed' => false, 'slots' => [['open' => '09:00', 'close' => '14:00'], ['open' => '17:00', 'close' => '20:00']]], 'thu' => ['closed' => false, 'slots' => [['open' => '09:00', 'close' => '14:00'], ['open' => '17:00', 'close' => '20:00']]], 'fri' => ['closed' => false, 'slots' => [['open' => '09:00', 'close' => '14:00'], ['open' => '17:00', 'close' => '20:00']]], 'sat' => ['closed' => false, 'slots' => [['open' => '10:00', 'close' => '14:00']]], 'sun' => ['closed' => true, 'slots' => []]],], 'quick_edit' => ['enabled' => true, 'source' => 'live_data', 'category' => 'horario', 'label' => 'Horario', 'frequency' => 'daily']],
        ['type' => 'price_list', 'label' => 'Price List', 'category' => 'Business', 'props' => ['badge' => 'Tarifas', 'title' => 'Precios y servicios', 'subtitle' => 'Lista simple para peluqueria, clinica o taller.', 'currency' => 'EUR', 'note' => 'Consulta disponibilidad por telefono.', 'items' => [['name' => 'Corte caballero', 'price' => '12', 'detail' => 'Incluye lavado'], ['name' => 'Corte senora', 'price' => '18', 'detail' => 'Secado rapido'], ['name' => 'Color raiz', 'price' => '35', 'detail' => 'Hasta 60 min']]], 'quick_edit' => ['enabled' => true, 'source' => 'live_data', 'category' => 'precios', 'label' => 'Lista de precios', 'frequency' => 'weekly']],
        ['type' => 'stats', 'label' => 'Stats', 'category' => 'Proof', 'props' => ['items' => [['value' => '500+', 'label' => 'Clients'], ['value' => '98%', 'label' => 'Satisfaction'], ['value' => '24/7', 'label' => 'Support']]]],
        ['type' => 'testimonial_cards', 'label' => 'Testimonial Cards', 'category' => 'Proof', 'layout' => 'grid', 'props' => ['badge' => 'Testimonials', 'title' => 'What clients say', 'subtitle' => 'Proof in a clear card grid.', 'items' => [['quote' => 'A strong testimonial.', 'name' => 'Client Name', 'role' => 'Role', 'stars' => 5], ['quote' => 'Another testimonial.', 'name' => 'Client Name', 'role' => 'Role', 'stars' => 5]]]],
        ['type' => 'portfolio_grid', 'label' => 'Portfolio Grid', 'category' => 'Proof', 'props' => ['badge' => 'Selected Work', 'title' => 'Recent projects', 'subtitle' => 'Show representative work.', 'projects' => [['category' => 'Project', 'title' => 'Project one', 'metric' => '+28% results', 'href' => '#contact', 'image' => ''], ['category' => 'Project', 'title' => 'Project two', 'metric' => '+41% results', 'href' => '#contact', 'image' => '']]]],
        ['type' => 'gallery', 'label' => 'Gallery', 'category' => 'Media', 'layout' => 'grid', 'props' => ['badge' => 'Gallery', 'title' => 'Visual gallery', 'images' => [['url' => '', 'alt' => 'Gallery image 1'], ['url' => '', 'alt' => 'Gallery image 2'], ['url' => '', 'alt' => 'Gallery image 3']]]],
        ['type' => 'blog_grid', 'label' => 'Blog Grid', 'category' => 'Content', 'layout' => 'grid', 'props' => ['badge' => 'Insights', 'title' => 'Latest articles', 'subtitle' => 'Use this for articles or news.', 'posts' => [['category' => 'Article', 'title' => 'Article title', 'excerpt' => 'Short description', 'author' => 'Author', 'date' => 'March 2026', 'href' => '#contact', 'image' => ''], ['category' => 'Article', 'title' => 'Article title', 'excerpt' => 'Short description', 'author' => 'Author', 'date' => 'March 2026', 'href' => '#contact', 'image' => '']]]],
        ['type' => 'blog_featured', 'label' => 'Blog Featured', 'category' => 'Content', 'layout' => 'split', 'props' => ['badge' => 'Featured', 'title' => 'Featured story', 'subtitle' => 'Lead with the main article and supporting reads.', 'posts' => [['category' => 'Feature', 'title' => 'Featured article', 'excerpt' => 'Longer description for the main piece.', 'author' => 'Editor', 'date' => 'March 2026', 'href' => '#contact', 'image' => ''], ['category' => 'Feature', 'title' => 'Supporting article', 'excerpt' => 'Short supporting copy.', 'author' => 'Editor', 'date' => 'March 2026', 'href' => '#contact', 'image' => ''], ['category' => 'Feature', 'title' => 'Supporting article', 'excerpt' => 'Short supporting copy.', 'author' => 'Editor', 'date' => 'March 2026', 'href' => '#contact', 'image' => '']]]],
        ['type' => 'blog_carousel', 'label' => 'Blog Stories', 'category' => 'Content', 'layout' => 'grid', 'props' => ['badge' => 'Stories', 'title' => 'More stories', 'subtitle' => 'Use this for article rails or editorial picks.', 'posts' => [['category' => 'Article', 'title' => 'Story title', 'excerpt' => 'Short description', 'author' => 'Author', 'date' => 'March 2026', 'href' => '#contact', 'image' => ''], ['category' => 'Article', 'title' => 'Story title', 'excerpt' => 'Short description', 'author' => 'Author', 'date' => 'March 2026', 'href' => '#contact', 'image' => ''], ['category' => 'Article', 'title' => 'Story title', 'excerpt' => 'Short description', 'author' => 'Author', 'date' => 'March 2026', 'href' => '#contact', 'image' => '']]]],
        ['type' => 'faq', 'label' => 'FAQ', 'category' => 'Proof', 'props' => ['badge' => 'FAQ', 'title' => 'Frequently asked questions', 'items' => [['q' => 'Question one?', 'a' => 'Answer one.'], ['q' => 'Question two?', 'a' => 'Answer two.']]]],
        ['type' => 'pricing', 'label' => 'Pricing', 'category' => 'Conversion', 'layout' => '3-col', 'props' => ['badge' => 'Pricing', 'title' => 'Simple pricing', 'plans' => [['name' => 'Basic', 'price' => '$29/mo', 'features' => ['Feature 1', 'Feature 2'], 'cta' => 'Get Started', 'highlighted' => false], ['name' => 'Pro', 'price' => '$79/mo', 'features' => ['Feature 1', 'Feature 2', 'Feature 3'], 'cta' => 'Get Started', 'highlighted' => true]]]],
        ['type' => 'pricing_toggle', 'label' => 'Pricing Toggle', 'category' => 'Conversion', 'layout' => '2-col', 'props' => ['badge' => 'Plans', 'title' => 'Monthly or annual plans', 'subtitle' => 'Show a pricing set with annual helper copy.', 'annual_label' => 'Save 20% with annual billing', 'plans' => [['name' => 'Starter', 'price' => '$29/mo', 'features' => ['Feature 1', 'Feature 2'], 'cta' => 'Start now', 'highlighted' => false], ['name' => 'Growth', 'price' => '$79/mo', 'features' => ['Feature 1', 'Feature 2', 'Feature 3'], 'cta' => 'Scale up', 'highlighted' => true]]]],
        ['type' => 'cta', 'label' => 'CTA', 'category' => 'Conversion', 'props' => ['title' => 'Ready to take the next step?', 'subtitle' => 'Invite the visitor to act.', 'button_text' => 'Contact us', 'button_href' => '#contact']],
        ['type' => 'lead_form', 'label' => 'Lead Form', 'category' => 'Conversion', 'props' => ['badge' => 'Contact', 'title' => 'Send us a message', 'subtitle' => 'Use this form as your conversion point.', 'button_text' => 'Send', 'privacy_text' => 'We will only use your details to respond.']],
        ['type' => 'newsletter', 'label' => 'Newsletter', 'category' => 'Conversion', 'props' => ['title' => 'Stay in the loop', 'subtitle' => 'Collect email subscribers.', 'placeholder' => 'Enter your email', 'button_text' => 'Subscribe']],
        ['type' => 'map_embed', 'label' => 'Map', 'category' => 'Contact', 'props' => ['badge' => 'Visit us', 'title' => 'Location and contact details', 'subtitle' => 'Show where visitors can find you.', 'embed_url' => '', 'address' => '123 Main Street', 'phone' => '+34 600 000 000', 'email' => 'hello@example.com', 'hours' => 'Mon-Fri 09:00-18:00']],
        ['type' => 'footer_multi', 'label' => 'Footer', 'category' => 'Footer', 'props' => ['brand' => 'Brand', 'description' => 'Use the footer to close the page with links and contact details.', 'columns' => [['title' => 'Company', 'links' => [['text' => 'About', 'href' => '#about'], ['text' => 'Contact', 'href' => '#contact']]]], 'contact_lines' => ['hello@example.com', '+34 600 000 000', 'Madrid, Spain'], 'copyright' => '© 2026 Brand. All rights reserved.']],
    ];
}

function ccms_admin_find_selected_page(array $data, string $selectedSlug): ?array
{
    if ($selectedSlug !== '') {
        foreach ($data['pages'] as $page) {
            if (($page['slug'] ?? '') === $selectedSlug || ($page['id'] ?? '') === $selectedSlug) {
                return $page;
            }
        }
    }
    return !empty($data['pages']) ? $data['pages'][0] : null;
}

function ccms_admin_find_selected_post(array $data, string $selectedSlug): ?array
{
    if ($selectedSlug !== '') {
        foreach ($data['posts'] ?? [] as $post) {
            if (($post['slug'] ?? '') === $selectedSlug || ($post['id'] ?? '') === $selectedSlug) {
                return $post;
            }
        }
    }
    return !empty($data['posts']) ? $data['posts'][0] : null;
}

function ccms_admin_tab_for_user(?array $currentAdmin): string
{
    $canManageSite = ccms_user_can('site_manage');
    $canManageUsers = ccms_user_can('users_manage');
    $canManageMedia = ccms_user_can('media_manage');
    $canManagePages = ccms_user_can('pages_manage');
    $canImportCapsules = ccms_user_can('import_capsules');
    $canGenerateAi = ccms_user_can('ai_generate');
    $tab = (string) ($_GET['tab'] ?? ($canGenerateAi ? 'studio' : 'pages'));
    if (($tab === 'users' && !$canManageUsers)
        || ($tab === 'site' && !$canManageSite)
        || ($tab === 'extensions' && !$canManageSite)
        || ($tab === 'backups' && !$canManageUsers)
        || ($tab === 'media' && !$canManageMedia)
        || ($tab === 'import' && !$canImportCapsules)
        || ($tab === 'studio' && !$canGenerateAi)
        || ($tab === 'audit' && !$canManageUsers)
        || ($tab === 'inbox' && !$canManagePages)
        || ($tab === 'posts' && !$canManagePages)) {
        $tab = 'pages';
    }
    if (!empty($currentAdmin['must_change_password'])) {
        return 'account';
    }
    return $tab;
}

function ccms_admin_menu_payload(array $menuPages): array
{
    return array_map(static function (array $page): array {
        return [
            'slug' => (string) ($page['slug'] ?? ''),
            'label' => trim((string) ($page['menu_label'] ?? '')) ?: (string) ($page['title'] ?? 'Untitled'),
            'is_homepage' => !empty($page['is_homepage']),
        ];
    }, $menuPages);
}

function ccms_admin_default_capsule_state(?array $selectedPage): array
{
    if ($selectedPage) {
        return ccms_capsule_decode($selectedPage) ?? [
            'meta' => ['business_name' => (string) ($selectedPage['title'] ?? 'Untitled')],
            'style' => [],
            'blocks' => [],
        ];
    }
    return ['meta' => ['business_name' => 'Untitled'], 'style' => [], 'blocks' => []];
}

function ccms_admin_onboarding_state(array $data): array
{
    $persisted = array_merge(
        ccms_default_onboarding_state(),
        is_array($data['site']['onboarding'] ?? null) ? $data['site']['onboarding'] : []
    );
    $homepage = ccms_homepage($data);
    $hasHomepage = is_array($homepage)
        && (
            trim((string) ($homepage['title'] ?? '')) !== ''
            || trim((string) ($homepage['html_content'] ?? '')) !== ''
            || trim((string) ($homepage['capsule_json'] ?? '')) !== ''
        );
    $hasMedia = !empty($data['media']);
    $hasPublicPages = !empty(array_filter(is_array($data['pages'] ?? null) ? $data['pages'] : [], static function (array $page): bool {
        return ccms_record_is_public($page);
    }));
    $hasPublicPosts = !empty(array_filter(is_array($data['posts'] ?? null) ? $data['posts'] : [], static function (array $post): bool {
        return ccms_record_is_public($post);
    }));
    $siteTitle = trim((string) ($data['site']['title'] ?? ''));
    $contactEmail = trim((string) ($data['site']['contact_email'] ?? ''));
    $hasPublishedOutput = $hasPublicPages || $hasPublicPosts || !empty($persisted['exported_at']);
    $steps = [
        [
            'key' => 'branding',
            'label' => 'Configura tu marca',
            'description' => 'Define nombre del sitio, email de contacto y branding básico.',
            'tab' => 'site',
            'href' => '/r-admin/?tab=site',
            'done' => $siteTitle !== '' && $contactEmail !== '',
        ],
        [
            'key' => 'homepage',
            'label' => 'Prepara tu home',
            'description' => 'Crea o edita la página principal con contenido real.',
            'tab' => 'pages',
            'href' => '/r-admin/?tab=pages',
            'done' => $hasHomepage,
        ],
        [
            'key' => 'media',
            'label' => 'Sube fotos',
            'description' => 'Carga al menos una imagen para empezar a personalizar el sitio.',
            'tab' => 'media',
            'href' => '/r-admin/?tab=media',
            'done' => $hasMedia,
        ],
        [
            'key' => 'publish',
            'label' => 'Publica o exporta',
            'description' => 'Deja una página o post público, o exporta el paquete estático.',
            'tab' => 'backups',
            'href' => '/r-admin/?tab=backups',
            'done' => $hasPublishedOutput,
        ],
    ];
    $completedCount = count(array_filter($steps, static function (array $step): bool {
        return !empty($step['done']);
    }));
    $allDone = $completedCount === count($steps);
    $nextStep = 'done';
    foreach ($steps as $step) {
        if (empty($step['done'])) {
            $nextStep = (string) $step['key'];
            break;
        }
    }

    return [
        'dismissed' => !$allDone && !empty($persisted['dismissed']),
        'completed' => !empty($persisted['completed']) || $allDone,
        'completed_at' => ($persisted['completed_at'] ?? null) ? (string) $persisted['completed_at'] : null,
        'exported_at' => ($persisted['exported_at'] ?? null) ? (string) $persisted['exported_at'] : null,
        'last_step' => trim((string) ($persisted['last_step'] ?? '')),
        'next_step' => $nextStep,
        'steps' => $steps,
        'completed_count' => $completedCount,
        'total_steps' => count($steps),
        'progress_percent' => count($steps) > 0 ? (int) round(($completedCount / count($steps)) * 100) : 0,
        'all_done' => $allDone,
    ];
}

function ccms_admin_sync_onboarding(array &$data): void
{
    $persisted = array_merge(
        ccms_default_onboarding_state(),
        is_array($data['site']['onboarding'] ?? null) ? $data['site']['onboarding'] : []
    );
    $state = ccms_admin_onboarding_state($data);
    $data['site']['onboarding'] = array_merge($persisted, [
        'dismissed' => $state['all_done'] ? false : !empty($persisted['dismissed']),
        'completed' => !empty($persisted['completed']) || $state['all_done'],
        'completed_at' => ($state['all_done'] && empty($persisted['completed_at']))
            ? ccms_now_iso()
            : (($persisted['completed_at'] ?? null) ? (string) $persisted['completed_at'] : null),
        'last_step' => (string) $state['next_step'],
    ]);
}

function ccms_build_admin_context(string $error = ''): array
{
    $data = ccms_load_data();
    $flash = ccms_consume_flash();
    $currentAdmin = ccms_current_admin();
    $pendingTwoFactor = ccms_pending_2fa();
    $canManageSite = ccms_user_can('site_manage');
    $canManageUsers = ccms_user_can('users_manage');
    $canManagePages = ccms_user_can('pages_manage');
    $canManagePosts = $canManagePages;
    $canManageMedia = ccms_user_can('media_manage');
    $canViewInbox = $canManagePages;
    $canImportCapsules = ccms_user_can('import_capsules');
    $canGenerateAi = ccms_user_can('ai_generate');
    $canViewAudit = $canManageUsers;
    $canManageBackups = $canManageUsers;
    $builderReadOnly = !$canManagePages;
    $mustChangePassword = !empty($currentAdmin['must_change_password']);
    $tab = ccms_admin_tab_for_user($currentAdmin);
    $selectedPage = ccms_admin_find_selected_page($data, trim((string) ($_GET['page'] ?? '')));
    $selectedPost = ccms_admin_find_selected_post($data, trim((string) ($_GET['post'] ?? '')));
    $menuPages = ccms_menu_pages($data);
    $previewHtml = $selectedPage ? ccms_admin_preview_html(ccms_render_public_page(ccms_public_site_config($data), $selectedPage, $menuPages)) : '';
    $postPreviewHtml = $selectedPost ? ccms_admin_preview_html(ccms_render_blog_post_page($data['site'], $selectedPost, $menuPages)) : '';
    $selectedRevisions = $selectedPage && is_array($selectedPage['revisions'] ?? null) ? $selectedPage['revisions'] : [];
    $selectedPostRevisions = $selectedPost && is_array($selectedPost['revisions'] ?? null) ? $selectedPost['revisions'] : [];
    $storageInfo = ccms_storage_runtime_info();
    $aiSettings = ccms_ai_settings($data);
    $premiumPacks = ccms_list_premium_packs();
    $premiumPacksByIndustry = ccms_group_premium_packs_by_industry();
    $auditLogs = array_slice(is_array($data['audit_logs'] ?? null) ? $data['audit_logs'] : [], 0, 80);
    $submissions = array_slice(is_array($data['submissions'] ?? null) ? $data['submissions'] : [], 0, 200);
    $submissionCounts = ['new' => 0, 'reviewed' => 0, 'contacted' => 0, 'archived' => 0];
    foreach ($submissions as $submission) {
        $status = trim((string) ($submission['status'] ?? 'new'));
        if (!array_key_exists($status, $submissionCounts)) {
            $submissionCounts[$status] = 0;
        }
        $submissionCounts[$status]++;
    }
    $availablePlugins = ccms_discover_plugins();
    $totpSetupSecret = $currentAdmin ? ccms_totp_setup_secret() : null;
    $resetTokenValue = !$currentAdmin ? trim((string) ($_GET['reset'] ?? '')) : '';
    $resetTokenEntry = (!$currentAdmin && $resetTokenValue !== '') ? ccms_find_valid_reset_token($data, $resetTokenValue) : null;
    $sectionTemplates = ccms_admin_section_templates();
    $capsuleBuilderTemplates = ccms_admin_capsule_builder_templates();
    $sectionTemplatesJson = json_encode($sectionTemplates, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    $capsuleBuilderTemplatesJson = json_encode($capsuleBuilderTemplates, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    $mediaItemsJson = json_encode($data['media'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    $adminBrand = ccms_admin_branding($data['site']);
    $previewSiteConfigJson = json_encode([
        'site' => ccms_public_site_config($data),
        'menu' => ccms_admin_menu_payload($menuPages),
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    $selectedCapsuleStateJson = json_encode(ccms_admin_default_capsule_state($selectedPage), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    $onboarding = ccms_admin_onboarding_state($data);
    $forceOnboarding = !empty($_GET['onboarding']);
    $showOnboarding = ($canManageSite || $canManagePages) && ($forceOnboarding || (!$onboarding['dismissed'] && !$onboarding['completed']));
    $showOnboardingLauncher = ($canManageSite || $canManagePages) && !$showOnboarding && !$onboarding['completed'];

    return compact(
        'adminBrand',
        'aiSettings',
        'auditLogs',
        'availablePlugins',
        'builderReadOnly',
        'canGenerateAi',
        'canImportCapsules',
        'canManageBackups',
        'canManageMedia',
        'canManagePages',
        'canManagePosts',
        'canManageSite',
        'canManageUsers',
        'canViewInbox',
        'canViewAudit',
        'capsuleBuilderTemplatesJson',
        'currentAdmin',
        'data',
        'error',
        'flash',
        'mediaItemsJson',
        'menuPages',
        'mustChangePassword',
        'onboarding',
        'pendingTwoFactor',
        'previewHtml',
        'postPreviewHtml',
        'premiumPacks',
        'premiumPacksByIndustry',
        'previewSiteConfigJson',
        'resetTokenEntry',
        'resetTokenValue',
        'sectionTemplates',
        'sectionTemplatesJson',
        'selectedCapsuleStateJson',
        'selectedPage',
        'selectedPost',
        'selectedPostRevisions',
        'selectedRevisions',
        'showOnboarding',
        'showOnboardingLauncher',
        'storageInfo',
        'submissionCounts',
        'submissions',
        'tab',
        'totpSetupSecret'
    ) + ['csrfToken' => ccms_csrf_token()];
}
