<?php
declare(strict_types=1);

$projectRoot = dirname(__DIR__);
$exampleSlug = 'studio-norte-demo';
$exampleRoot = $projectRoot . '/examples/' . $exampleSlug;
$runtimeRoot = $exampleRoot . '/runtime';

putenv('CCMS_ROOT=' . $runtimeRoot);

require_once $projectRoot . '/src/bootstrap.php';

function example_rrmdir(string $path): void
{
    if (!file_exists($path)) {
        return;
    }
    if (is_file($path) || is_link($path)) {
        @unlink($path);
        return;
    }
    foreach (scandir($path) ?: [] as $entry) {
        if ($entry === '.' || $entry === '..') {
            continue;
        }
        example_rrmdir($path . DIRECTORY_SEPARATOR . $entry);
    }
    @rmdir($path);
}

function example_write_file(string $path, string $contents): void
{
    @mkdir(dirname($path), 0775, true);
    file_put_contents($path, $contents);
}

function example_make_svg(string $title, string $subtitle, string $from, string $to): string
{
    $title = ccms_h($title);
    $subtitle = ccms_h($subtitle);
    return <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1600 1000" role="img" aria-label="{$title}">
  <defs>
    <linearGradient id="bg" x1="0%" y1="0%" x2="100%" y2="100%">
      <stop offset="0%" stop-color="{$from}"/>
      <stop offset="100%" stop-color="{$to}"/>
    </linearGradient>
    <filter id="blur">
      <feGaussianBlur stdDeviation="40"/>
    </filter>
  </defs>
  <rect width="1600" height="1000" fill="url(#bg)"/>
  <circle cx="280" cy="220" r="160" fill="rgba(255,255,255,.18)" filter="url(#blur)"/>
  <circle cx="1280" cy="780" r="220" fill="rgba(255,255,255,.12)" filter="url(#blur)"/>
  <path d="M0 760 C260 660 430 930 760 820 C1060 720 1240 520 1600 650 L1600 1000 L0 1000 Z" fill="rgba(255,255,255,.14)"/>
  <rect x="118" y="118" width="1364" height="764" rx="42" fill="rgba(255,255,255,.10)" stroke="rgba(255,255,255,.28)" stroke-width="2"/>
  <text x="160" y="240" fill="#ffffff" font-family="Inter,Arial,sans-serif" font-size="28" font-weight="700" opacity=".84">LinuxCMS example</text>
  <text x="160" y="420" fill="#ffffff" font-family="Georgia,Times New Roman,serif" font-size="94" font-weight="700">{$title}</text>
  <text x="160" y="500" fill="#ffffff" font-family="Inter,Arial,sans-serif" font-size="34" font-weight="500" opacity=".92">{$subtitle}</text>
  <text x="160" y="820" fill="#ffffff" font-family="Inter,Arial,sans-serif" font-size="24" font-weight="700" opacity=".72">studio-norte-demo</text>
</svg>
SVG;
}

function example_media_item(string $filename, string $original): array
{
    return [
        'id' => ccms_next_id('media'),
        'filename' => $filename,
        'original_name' => $original,
        'url' => ccms_public_upload_url($filename),
        'uploaded_at' => ccms_now_iso(),
        'optimized' => [
            'available' => false,
            'variants' => 0,
            'webp_variants' => 0,
        ],
    ];
}

function example_newsletter_form(array $page, string $blockId): string
{
    return ccms_render_public_form_feedback($blockId)
        . '<form action="' . ccms_h(ccms_public_form_action()) . '" method="post" style="display:grid;grid-template-columns:minmax(0,1fr) auto;gap:12px;align-items:center">'
        . ccms_render_public_form_hidden_inputs($page, 'newsletter', $blockId)
        . '<label style="display:block"><span style="display:block;font-size:13px;font-weight:700;color:var(--muted);margin-bottom:8px">Email</span><input type="email" name="email" required placeholder="tu@empresa.com" style="width:100%;padding:16px 18px;border:1px solid rgba(0,0,0,.12);border-radius:999px;font:inherit;background:#fff"></label>'
        . '<button class="ccms-btn" type="submit">Recibir novedades</button>'
        . '</form>';
}

function example_contact_form(array $page, string $blockId): string
{
    return ccms_render_public_form_feedback($blockId)
        . '<form action="' . ccms_h(ccms_public_form_action()) . '" method="post" style="display:grid;gap:14px">'
        . ccms_render_public_form_hidden_inputs($page, 'contact', $blockId)
        . '<div style="display:grid;grid-template-columns:1fr 1fr;gap:14px">'
        . '<label style="display:block"><span style="display:block;font-size:13px;font-weight:700;color:var(--muted);margin-bottom:8px">Nombre</span><input type="text" name="name" required placeholder="Tu nombre" style="width:100%;padding:15px 16px;border:1px solid rgba(0,0,0,.12);border-radius:16px;font:inherit;background:#fff"></label>'
        . '<label style="display:block"><span style="display:block;font-size:13px;font-weight:700;color:var(--muted);margin-bottom:8px">Email</span><input type="email" name="email" required placeholder="tu@empresa.com" style="width:100%;padding:15px 16px;border:1px solid rgba(0,0,0,.12);border-radius:16px;font:inherit;background:#fff"></label>'
        . '</div>'
        . '<div style="display:grid;grid-template-columns:1fr 1fr;gap:14px">'
        . '<label style="display:block"><span style="display:block;font-size:13px;font-weight:700;color:var(--muted);margin-bottom:8px">Telefono</span><input type="text" name="phone" placeholder="+34 600 000 000" style="width:100%;padding:15px 16px;border:1px solid rgba(0,0,0,.12);border-radius:16px;font:inherit;background:#fff"></label>'
        . '<label style="display:block"><span style="display:block;font-size:13px;font-weight:700;color:var(--muted);margin-bottom:8px">Empresa</span><input type="text" name="company" placeholder="Nombre de tu estudio" style="width:100%;padding:15px 16px;border:1px solid rgba(0,0,0,.12);border-radius:16px;font:inherit;background:#fff"></label>'
        . '</div>'
        . '<label style="display:block"><span style="display:block;font-size:13px;font-weight:700;color:var(--muted);margin-bottom:8px">Que quieres lanzar</span><textarea name="message" rows="6" required placeholder="Cuantanos el proyecto, el plazo y el objetivo comercial." style="width:100%;padding:16px;border:1px solid rgba(0,0,0,.12);border-radius:18px;font:inherit;background:#fff;resize:vertical"></textarea></label>'
        . '<button class="ccms-btn" type="submit" style="justify-self:start">Pedir propuesta</button>'
        . '</form>';
}

function example_homepage_html(array $page, array $posts, array $images): string
{
    $postCards = '';
    foreach (array_slice($posts, 0, 3) as $post) {
        $url = '/blog/' . rawurlencode((string) $post['slug']);
        $postCards .= '<article style="background:#fff;border:1px solid rgba(0,0,0,.08);border-radius:24px;overflow:hidden;box-shadow:0 20px 40px -34px rgba(0,0,0,.18)">'
            . '<img src="' . ccms_h((string) $post['cover_image']) . '" alt="' . ccms_h((string) $post['title']) . '" style="width:100%;height:220px;object-fit:cover">'
            . '<div style="padding:22px">'
            . '<div style="font-size:12px;letter-spacing:.08em;text-transform:uppercase;font-weight:800;color:var(--primary);margin-bottom:10px">' . ccms_h(implode(' · ', (array) $post['categories'])) . '</div>'
            . '<h3 style="font-size:28px;line-height:1.1;margin:0 0 12px">' . ccms_h((string) $post['title']) . '</h3>'
            . '<p style="font-size:16px;line-height:1.75;color:var(--muted);margin:0 0 16px">' . ccms_h(ccms_post_excerpt($post, 140)) . '</p>'
            . '<a href="' . ccms_h($url) . '" style="font-weight:800;color:var(--primary);text-decoration:none">Leer articulo</a>'
            . '</div></article>';
    }

    $newsletter = example_newsletter_form($page, 'newsletter-home');

    return <<<HTML
<section style="padding:72px 0 36px">
  <div class="shell" style="display:grid;grid-template-columns:1.1fr .9fr;gap:32px;align-items:center">
    <div>
      <span class="ccms-chip">Ejemplo completo LinuxCMS</span>
      <h1 style="font-size:64px;line-height:1.02;margin:18px 0 18px">Web de agencia con blog, formularios, SEO y paginas publicas listas para vender.</h1>
      <p style="font-size:20px;line-height:1.8;color:var(--muted);margin:0 0 26px">Studio Norte combina estrategia, identidad y lanzamiento tecnico para estudios creativos, despachos y marcas de autor. Este ejemplo ensena lo que LinuxCMS ya puede entregar hoy sin depender de WordPress.</p>
      <div style="display:flex;flex-wrap:wrap;gap:14px">
        <a class="ccms-btn" href="/contact">Pedir propuesta</a>
        <a class="ccms-btn ccms-btn--ghost" href="/services">Ver servicios</a>
      </div>
      <div style="display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:14px;margin-top:28px">
        <div style="background:#fff;border-radius:20px;padding:18px;border:1px solid rgba(0,0,0,.08)"><strong style="display:block;font-size:34px;line-height:1">18</strong><span style="color:var(--muted)">sitios lanzados en 12 meses</span></div>
        <div style="background:#fff;border-radius:20px;padding:18px;border:1px solid rgba(0,0,0,.08)"><strong style="display:block;font-size:34px;line-height:1">9 dias</strong><span style="color:var(--muted)">para una home lista</span></div>
        <div style="background:#fff;border-radius:20px;padding:18px;border:1px solid rgba(0,0,0,.08)"><strong style="display:block;font-size:34px;line-height:1">100%</strong><span style="color:var(--muted)">editable desde LinuxCMS</span></div>
      </div>
    </div>
    <div>
      <img src="{$images['hero']}" alt="Studio Norte hero" style="width:100%;height:560px;object-fit:cover;border-radius:30px;box-shadow:0 30px 60px -36px rgba(0,0,0,.24)">
    </div>
  </div>
</section>

<section style="padding:18px 0 36px">
  <div class="shell" style="background:#fff;border-radius:28px;padding:28px;border:1px solid rgba(0,0,0,.08);box-shadow:0 20px 45px -38px rgba(0,0,0,.18)">
    <div style="display:flex;justify-content:space-between;gap:18px;flex-wrap:wrap;align-items:end;margin-bottom:18px">
      <div>
        <span class="ccms-chip">Servicios</span>
        <h2 style="font-size:42px;line-height:1.06;margin:14px 0 10px">Una oferta pequena, clara y rentable.</h2>
      </div>
      <a href="/services" style="font-weight:800;color:var(--primary);text-decoration:none">Ver detalle completo</a>
    </div>
    <div style="display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:18px">
      <article style="padding:22px;border-radius:22px;background:#f8f3ee"><h3 style="margin:0 0 10px;font-size:24px">Sprint de lanzamiento</h3><p style="margin:0;color:var(--muted);line-height:1.75">Home, estructura, copy base y checklist tecnico en menos de dos semanas.</p></article>
      <article style="padding:22px;border-radius:22px;background:#f8f3ee"><h3 style="margin:0 0 10px;font-size:24px">Sistema de contenidos</h3><p style="margin:0;color:var(--muted);line-height:1.75">Paginas, blog, leads, SEO y handoff listo para un equipo pequeno.</p></article>
      <article style="padding:22px;border-radius:22px;background:#f8f3ee"><h3 style="margin:0 0 10px;font-size:24px">Retainer mensual</h3><p style="margin:0;color:var(--muted);line-height:1.75">Cambios, nuevas paginas, optimizacion de mensajes y revisiones continuas.</p></article>
    </div>
  </div>
</section>

<section style="padding:18px 0 36px">
  <div class="shell">
    <div style="display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:18px">
      <article style="background:#fff;border-radius:26px;overflow:hidden;border:1px solid rgba(0,0,0,.08)"><img src="{$images['case_one']}" alt="Caso Sierra" style="width:100%;height:220px;object-fit:cover"><div style="padding:20px"><h3 style="margin:0 0 8px;font-size:26px">Sierra Atelier</h3><p style="margin:0 0 14px;color:var(--muted);line-height:1.75">Nueva web editorial para un estudio de interiores con leads semanales consistentes.</p><a href="/contact" style="font-weight:800;color:var(--primary);text-decoration:none">Quiero algo asi</a></div></article>
      <article style="background:#fff;border-radius:26px;overflow:hidden;border:1px solid rgba(0,0,0,.08)"><img src="{$images['case_two']}" alt="Caso Atlas" style="width:100%;height:220px;object-fit:cover"><div style="padding:20px"><h3 style="margin:0 0 8px;font-size:26px">Atlas Legal</h3><p style="margin:0 0 14px;color:var(--muted);line-height:1.75">Sitio para despacho boutique con servicios claros, blog y formulario de consultas.</p><a href="/services" style="font-weight:800;color:var(--primary);text-decoration:none">Ver stack</a></div></article>
      <article style="background:#fff;border-radius:26px;overflow:hidden;border:1px solid rgba(0,0,0,.08)"><img src="{$images['case_three']}" alt="Caso Vela" style="width:100%;height:220px;object-fit:cover"><div style="padding:20px"><h3 style="margin:0 0 8px;font-size:26px">Vela Product</h3><p style="margin:0 0 14px;color:var(--muted);line-height:1.75">Landing de producto con narrativa de lanzamiento y seguimiento desde analytics.</p><a href="/blog" style="font-weight:800;color:var(--primary);text-decoration:none">Leer insights</a></div></article>
    </div>
  </div>
</section>

<section style="padding:22px 0 40px">
  <div class="shell" style="display:grid;grid-template-columns:1fr 1fr;gap:24px;align-items:start">
    <article style="background:#fff;border-radius:28px;padding:28px;border:1px solid rgba(0,0,0,.08)">
      <span class="ccms-chip">Proceso</span>
      <h2 style="font-size:40px;line-height:1.06;margin:16px 0 14px">Un flujo pequeno, claro y repetible.</h2>
      <ol style="margin:0;padding-left:20px;display:grid;gap:12px;color:var(--muted);line-height:1.8">
        <li>Brief de negocio, publico y objetivo comercial.</li>
        <li>Arquitectura de paginas, mensajes y estilo.</li>
        <li>Lanzamiento rapido con LinuxCMS y handoff limpio.</li>
      </ol>
    </article>
    <article style="background:#2f241f;color:#fff;border-radius:28px;padding:28px;box-shadow:0 28px 55px -34px rgba(0,0,0,.35)">
      <span style="display:inline-block;padding:8px 14px;border-radius:999px;background:rgba(255,255,255,.12);font-size:12px;letter-spacing:.08em;text-transform:uppercase;font-weight:800">Newsletter</span>
      <h2 style="font-size:38px;line-height:1.06;margin:16px 0 12px">Recibe ideas para lanzar una web mejor en menos tiempo.</h2>
      <p style="color:rgba(255,255,255,.76);line-height:1.8;margin:0 0 18px">Una secuencia corta sobre estructura, copy, entregables y handoff. Este bloque usa el formulario real de LinuxCMS.</p>
      {$newsletter}
    </article>
  </div>
</section>

<section style="padding:22px 0 72px">
  <div class="shell">
    <div style="display:flex;justify-content:space-between;gap:18px;flex-wrap:wrap;align-items:end;margin-bottom:18px">
      <div>
        <span class="ccms-chip">Blog</span>
        <h2 style="font-size:42px;line-height:1.06;margin:16px 0 10px">Contenido publico, categorias, tags y feed RSS.</h2>
      </div>
      <a href="/blog" style="font-weight:800;color:var(--primary);text-decoration:none">Ver archivo completo</a>
    </div>
    <div style="display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:18px">
      {$postCards}
    </div>
  </div>
</section>
HTML;
}

function example_services_html(array $images): string
{
    return <<<HTML
<section style="padding:64px 0 28px">
  <div class="shell">
    <span class="ccms-chip">Servicios</span>
    <h1 style="font-size:56px;line-height:1.04;margin:16px 0 14px">Paquetes pensados para entregar una web clara, bonita y mantenible.</h1>
    <p style="font-size:19px;line-height:1.8;color:var(--muted);max-width:760px;margin:0">Este ejemplo ensena que LinuxCMS puede vivir como web corporativa, blog, generador de leads y paquete estatico para hosting basico.</p>
  </div>
</section>
<section style="padding:18px 0 36px">
  <div class="shell" style="display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:20px">
    <article style="background:#fff;border:1px solid rgba(0,0,0,.08);border-radius:26px;padding:24px"><h2 style="font-size:28px;margin:0 0 10px">Launch Sprint</h2><p style="color:var(--muted);line-height:1.75;margin:0 0 16px">Home, servicios, contacto y configuracion SEO minima.</p><strong style="font-size:32px">2.900 EUR</strong><ul style="line-height:1.8;color:var(--muted)"><li>Arquitectura de mensajes</li><li>4 paginas</li><li>Formulario funcionando</li></ul></article>
    <article style="background:#fff;border:1px solid rgba(0,0,0,.08);border-radius:26px;padding:24px;box-shadow:0 26px 50px -38px rgba(0,0,0,.24)"><div style="display:inline-flex;padding:8px 12px;border-radius:999px;background:rgba(200,111,92,.12);color:var(--primary);font-size:12px;font-weight:800;letter-spacing:.08em;text-transform:uppercase;margin-bottom:10px">Mas vendido</div><h2 style="font-size:28px;margin:0 0 10px">Site System</h2><p style="color:var(--muted);line-height:1.75;margin:0 0 16px">Web completa con blog, handoff y export estatico.</p><strong style="font-size:32px">5.800 EUR</strong><ul style="line-height:1.8;color:var(--muted)"><li>Paginas + blog</li><li>SEO + analytics</li><li>Documentacion de handoff</li></ul></article>
    <article style="background:#fff;border:1px solid rgba(0,0,0,.08);border-radius:26px;padding:24px"><h2 style="font-size:28px;margin:0 0 10px">Monthly Ops</h2><p style="color:var(--muted);line-height:1.75;margin:0 0 16px">Soporte para nuevas landings, articulos y optimizacion.</p><strong style="font-size:32px">780 EUR/mes</strong><ul style="line-height:1.8;color:var(--muted)"><li>1 sprint mensual</li><li>Cambios priorizados</li><li>Revision de conversion</li></ul></article>
  </div>
</section>
<section style="padding:12px 0 64px">
  <div class="shell" style="display:grid;grid-template-columns:1fr 1fr;gap:24px;align-items:center">
    <div>
      <h2 style="font-size:40px;line-height:1.06;margin:0 0 14px">Lo que se entrega al final</h2>
      <ul style="line-height:1.9;color:var(--muted);font-size:18px;padding-left:20px">
        <li>Web publicada y editable desde LinuxCMS.</li>
        <li>Blog con categorias, tags y RSS.</li>
        <li>SEO tecnico minimo y analytics activados.</li>
        <li>Export estatico listo para hosting basico.</li>
      </ul>
    </div>
    <img src="{$images['case_two']}" alt="Services" style="width:100%;height:420px;object-fit:cover;border-radius:30px;box-shadow:0 30px 60px -36px rgba(0,0,0,.24)">
  </div>
</section>
HTML;
}

function example_about_html(array $images): string
{
    return <<<HTML
<section style="padding:64px 0 26px">
  <div class="shell" style="display:grid;grid-template-columns:.9fr 1.1fr;gap:28px;align-items:center">
    <img src="{$images['portrait']}" alt="Camila Norte" style="width:100%;height:520px;object-fit:cover;border-radius:30px;box-shadow:0 30px 60px -36px rgba(0,0,0,.24)">
    <div>
      <span class="ccms-chip">Sobre Studio Norte</span>
      <h1 style="font-size:54px;line-height:1.04;margin:18px 0 14px">Un equipo pequeno para marcas que no quieren otra web generica.</h1>
      <p style="font-size:19px;line-height:1.8;color:var(--muted);margin:0 0 18px">Studio Norte nace para reducir el salto entre estrategia, diseno y lanzamiento. Este ejemplo muestra una marca de servicios con narrativa clara, formularios reales y blog integrado.</p>
      <p style="font-size:18px;line-height:1.8;color:var(--muted);margin:0">Trabajamos con estudios, despachos y equipos fundadores que necesitan una web sobria, comercial y facil de mantener sin cargar con un stack enorme.</p>
    </div>
  </div>
</section>
<section style="padding:18px 0 64px">
  <div class="shell" style="display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:18px">
    <article style="background:#fff;border:1px solid rgba(0,0,0,.08);border-radius:24px;padding:22px"><h2 style="font-size:26px;margin:0 0 10px">Clarity first</h2><p style="margin:0;color:var(--muted);line-height:1.8">Lo primero es ordenar oferta, publico y CTA antes de hablar de animaciones o adornos.</p></article>
    <article style="background:#fff;border:1px solid rgba(0,0,0,.08);border-radius:24px;padding:22px"><h2 style="font-size:26px;margin:0 0 10px">Small systems</h2><p style="margin:0;color:var(--muted);line-height:1.8">Preferimos sistemas pequenos, bien documentados y faciles de entregar a un cliente real.</p></article>
    <article style="background:#fff;border:1px solid rgba(0,0,0,.08);border-radius:24px;padding:22px"><h2 style="font-size:26px;margin:0 0 10px">Shipping over polish</h2><p style="margin:0;color:var(--muted);line-height:1.8">La mejor web es la que sale a tiempo, convierte y el cliente puede mantener sin miedo.</p></article>
  </div>
</section>
HTML;
}

function example_contact_html(array $page, array $images): string
{
    $form = example_contact_form($page, 'contact-main');
    return <<<HTML
<section style="padding:64px 0 28px">
  <div class="shell" style="display:grid;grid-template-columns:1fr 1fr;gap:24px;align-items:start">
    <article style="background:#fff;border:1px solid rgba(0,0,0,.08);border-radius:30px;padding:28px">
      <span class="ccms-chip">Contacto</span>
      <h1 style="font-size:50px;line-height:1.04;margin:16px 0 14px">Cuéntanos el proyecto y te respondemos con un siguiente paso concreto.</h1>
      <p style="font-size:18px;line-height:1.8;color:var(--muted);margin:0 0 18px">Este formulario usa el flujo real de LinuxCMS: guarda el lead, lo deja en Inbox y tambien intenta enviar email a la direccion del sitio.</p>
      <div style="display:grid;gap:12px;color:var(--muted);font-size:16px;line-height:1.8;margin:0 0 22px">
        <div><strong style="color:var(--text)">Email:</strong> hello@studionorte.test</div>
        <div><strong style="color:var(--text)">Ciudad:</strong> Madrid, trabajo remoto para toda Espana</div>
        <div><strong style="color:var(--text)">Tiempo de respuesta:</strong> 1 dia laborable</div>
      </div>
      {$form}
    </article>
    <div style="display:grid;gap:18px">
      <img src="{$images['case_one']}" alt="Contacto" style="width:100%;height:280px;object-fit:cover;border-radius:28px;box-shadow:0 30px 60px -36px rgba(0,0,0,.24)">
      <article style="background:#2f241f;color:#fff;border-radius:28px;padding:24px">
        <h2 style="font-size:28px;margin:0 0 10px">Que suele salir de la primera llamada</h2>
        <ul style="margin:0;padding-left:18px;line-height:1.9;color:rgba(255,255,255,.78)">
          <li>prioridad real de la web</li>
          <li>estructura minima de paginas</li>
          <li>que se puede lanzar primero y que no</li>
          <li>si conviene LinuxCMS + export o mantenimiento continuo</li>
        </ul>
      </article>
    </div>
  </div>
</section>
HTML;
}

function example_post_html(string $title, string $lead, string $imageUrl, array $sections): string
{
    $body = '<p style="font-size:20px;line-height:1.8;color:var(--muted)">' . ccms_h($lead) . '</p>';
    $body .= '<p><img src="' . ccms_h($imageUrl) . '" alt="' . ccms_h($title) . '" style="width:100%;height:auto;border-radius:24px;box-shadow:0 20px 45px -34px rgba(0,0,0,.18)"></p>';
    foreach ($sections as $section) {
        $body .= '<h2>' . ccms_h((string) $section['title']) . '</h2>';
        foreach ((array) $section['paragraphs'] as $paragraph) {
            $body .= '<p>' . ccms_h((string) $paragraph) . '</p>';
        }
        if (!empty($section['bullets'])) {
            $body .= '<ul>';
            foreach ((array) $section['bullets'] as $bullet) {
                $body .= '<li>' . ccms_h((string) $bullet) . '</li>';
            }
            $body .= '</ul>';
        }
    }
    return $body;
}

example_rrmdir($exampleRoot);
@mkdir($runtimeRoot, 0775, true);

$data = ccms_default_data();
$now = ccms_now_iso();
$demoPassword = 'Demo-StudioNorte-2026!';
$passwordHash = password_hash($demoPassword, PASSWORD_DEFAULT);

$data['installed_at'] = $now;
$data['site'] = array_replace_recursive($data['site'], [
    'title' => 'Studio Norte',
    'tagline' => 'Brand, web and launch systems for studios and professional service firms.',
    'footer_text' => 'Studio Norte - ejemplo completo creado con LinuxCMS.',
    'contact_email' => 'hello@studionorte.test',
    'analytics_provider' => 'plausible',
    'analytics_id' => 'studio-norte-demo.local',
    'theme_preset' => 'editorial',
    'colors' => [
        'bg' => '#f5efe7',
        'surface' => '#fffdf9',
        'text' => '#2d241f',
        'muted' => '#6e6158',
        'primary' => '#c86f5c',
        'secondary' => '#dcc4b2',
    ],
]);

$owner = [
    'id' => ccms_next_id('user'),
    'username' => 'demo',
    'email' => 'demo@studionorte.test',
    'password_hash' => $passwordHash,
    'role' => 'owner',
    'must_change_password' => false,
    'last_login_at' => null,
    'totp_secret' => '',
    'totp_enabled' => false,
    'created_at' => $now,
    'updated_at' => $now,
];
$data['users'] = [$owner];
$data['admin'] = [
    'id' => $owner['id'],
    'username' => $owner['username'],
    'email' => $owner['email'],
    'password_hash' => $owner['password_hash'],
    'created_at' => $now,
];

$svgFiles = [
    'studio-norte-hero.svg' => example_make_svg('Studio Norte', 'Brand systems, launch pages and content operations.', '#b56c58', '#2f241f'),
    'studio-norte-case-sierra.svg' => example_make_svg('Sierra Atelier', 'Interior design site, editorial and lead-driven.', '#d0937a', '#473831'),
    'studio-norte-case-atlas.svg' => example_make_svg('Atlas Legal', 'Boutique law firm site with blog and inquiries.', '#8f7d73', '#2a2623'),
    'studio-norte-case-vela.svg' => example_make_svg('Vela Product', 'Launch page, articles and analytics.', '#cf9d78', '#3a2f28'),
    'studio-norte-portrait.svg' => example_make_svg('Camila Norte', 'Founder and launch strategist.', '#c6a28c', '#43342d'),
];

$images = [];
foreach ($svgFiles as $filename => $svg) {
    example_write_file(ccms_uploads_dir() . DIRECTORY_SEPARATOR . $filename, $svg);
    $imagesKey = match ($filename) {
        'studio-norte-hero.svg' => 'hero',
        'studio-norte-case-sierra.svg' => 'case_one',
        'studio-norte-case-atlas.svg' => 'case_two',
        'studio-norte-case-vela.svg' => 'case_three',
        default => 'portrait',
    };
    $images[$imagesKey] = ccms_public_upload_url($filename);
    $data['media'][] = example_media_item($filename, $filename);
}

$homePage = [
    'id' => ccms_next_id('page'),
    'title' => 'Inicio',
    'slug' => 'inicio',
    'status' => 'published',
    'published_at' => $now,
    'is_homepage' => true,
    'show_in_menu' => true,
    'menu_label' => 'Inicio',
    'meta_title' => 'Studio Norte - sitio de ejemplo completo para LinuxCMS',
    'meta_description' => 'Ejemplo completo de web de agencia con paginas, blog, formularios, SEO y export estatico.',
    'capsule_json' => '{}',
    'html_content' => '',
    'created_at' => $now,
    'updated_at' => $now,
    'revisions' => [],
];

$servicesPage = [
    'id' => ccms_next_id('page'),
    'title' => 'Servicios',
    'slug' => 'services',
    'status' => 'published',
    'published_at' => $now,
    'is_homepage' => false,
    'show_in_menu' => true,
    'menu_label' => 'Servicios',
    'meta_title' => 'Servicios - Studio Norte',
    'meta_description' => 'Paquetes de lanzamiento, sistema de contenidos y soporte mensual.',
    'capsule_json' => '{}',
    'html_content' => '',
    'created_at' => $now,
    'updated_at' => $now,
    'revisions' => [],
];

$aboutPage = [
    'id' => ccms_next_id('page'),
    'title' => 'Sobre nosotros',
    'slug' => 'about',
    'status' => 'published',
    'published_at' => $now,
    'is_homepage' => false,
    'show_in_menu' => true,
    'menu_label' => 'Sobre nosotros',
    'meta_title' => 'Sobre Studio Norte',
    'meta_description' => 'Equipo, enfoque y principios del ejemplo de agencia creado con LinuxCMS.',
    'capsule_json' => '{}',
    'html_content' => '',
    'created_at' => $now,
    'updated_at' => $now,
    'revisions' => [],
];

$contactPage = [
    'id' => ccms_next_id('page'),
    'title' => 'Contacto',
    'slug' => 'contact',
    'status' => 'published',
    'published_at' => $now,
    'is_homepage' => false,
    'show_in_menu' => true,
    'menu_label' => 'Contacto',
    'meta_title' => 'Contacto - Studio Norte',
    'meta_description' => 'Formulario real de contacto y siguientes pasos comerciales.',
    'capsule_json' => '{}',
    'html_content' => '',
    'created_at' => $now,
    'updated_at' => $now,
    'revisions' => [],
];

$posts = [
    [
        'id' => ccms_next_id('post'),
        'title' => 'Como lanzar una web de servicios en diez dias sin perder claridad',
        'slug' => 'lanzar-web-servicios-en-diez-dias',
        'status' => 'published',
        'excerpt' => 'Una estructura pequena, un CTA claro y un handoff limpio suelen valer mas que seis semanas de indecision.',
        'content_html' => example_post_html(
            'Como lanzar una web de servicios en diez dias sin perder claridad',
            'Cuando una web tarda demasiado en salir, normalmente no falta diseno: falta decidir que se quiere vender primero.',
            $images['case_one'],
            [
                [
                    'title' => 'Empieza por la oferta',
                    'paragraphs' => [
                        'Antes de hablar de layouts, define una oferta principal y una accion clara. Si la web intenta vender todo al mismo tiempo, el visitante no sabe por donde entrar.',
                        'En LinuxCMS esto se traduce muy bien a una home, una pagina de servicios y una pagina de contacto con mensajes simples.'
                    ],
                    'bullets' => ['una promesa principal', 'un publico claro', 'un CTA visible']
                ],
                [
                    'title' => 'Entrega ligera',
                    'paragraphs' => [
                        'Para muchos clientes, una entrega ligera con blog, formularios y export estatico es mas util que una instalacion pesada con demasiadas dependencias.'
                    ],
                ],
            ]
        ),
        'cover_image' => $images['case_one'],
        'author_name' => 'Camila Norte',
        'categories' => ['Estrategia'],
        'tags' => ['Lanzamiento', 'Servicios', 'Conversion'],
        'meta_title' => 'Lanzar una web de servicios en 10 dias',
        'meta_description' => 'Guia corta sobre estructura, CTA y lanzamiento para webs de servicios.',
        'published_at' => $now,
        'created_at' => $now,
        'updated_at' => $now,
    ],
    [
        'id' => ccms_next_id('post'),
        'title' => 'Cuando conviene exportar una web estatica y cuando no',
        'slug' => 'cuando-conviene-exportar-web-estatica',
        'status' => 'published',
        'excerpt' => 'No todos los proyectos necesitan el mismo runtime. A veces el mejor stack es el mas pequeno.',
        'content_html' => example_post_html(
            'Cuando conviene exportar una web estatica y cuando no',
            'Una exportacion estatica no es una version pobre del sitio. En muchos proyectos pequenos es la opcion mas estable y barata.',
            $images['case_two'],
            [
                [
                    'title' => 'Cuatro casos claros',
                    'paragraphs' => [
                        'Si el sitio tiene pocas paginas, pocas actualizaciones y un formulario sencillo, una exportacion estatica suele ser suficiente.',
                    ],
                    'bullets' => ['microsites', 'landings de campana', 'sites corporativos pequenos', 'proyectos con hosting basico']
                ],
                [
                    'title' => 'Cuando necesitas CMS vivo',
                    'paragraphs' => [
                        'Si el cliente va a publicar posts a menudo, revisar leads o tocar secciones, conviene mantener LinuxCMS como backend activo.'
                    ],
                ],
            ]
        ),
        'cover_image' => $images['case_two'],
        'author_name' => 'Camila Norte',
        'categories' => ['Tecnico'],
        'tags' => ['Hosting', 'Static Export', 'LinuxCMS'],
        'meta_title' => 'Cuando exportar una web estatica',
        'meta_description' => 'Criterios practicos para elegir entre CMS activo y export estatico.',
        'published_at' => $now,
        'created_at' => $now,
        'updated_at' => $now,
    ],
    [
        'id' => ccms_next_id('post'),
        'title' => 'Tres decisiones de copy que mejoran una home sin rediseñarla',
        'slug' => 'tres-decisiones-de-copy-que-mejoran-una-home',
        'status' => 'published',
        'excerpt' => 'Cambiar la promesa, el orden y el CTA suele mover mas negocio que una nueva animacion.',
        'content_html' => example_post_html(
            'Tres decisiones de copy que mejoran una home sin redisenarla',
            'El mejor upgrade de una home no suele ser visual. Suele ser verbal.',
            $images['case_three'],
            [
                [
                    'title' => 'Promesa primero',
                    'paragraphs' => [
                        'La primera frase debe decir con claridad que problema resuelves y para quien.'
                    ],
                ],
                [
                    'title' => 'El CTA no puede ser abstracto',
                    'paragraphs' => [
                        'Evita botones como "Saber mas" si lo que en realidad quieres es una llamada, una demo o una consulta.'
                    ],
                    'bullets' => ['Reservar llamada', 'Pedir propuesta', 'Descargar dossier']
                ],
            ]
        ),
        'cover_image' => $images['case_three'],
        'author_name' => 'Camila Norte',
        'categories' => ['Copy'],
        'tags' => ['Home', 'CTA', 'Mensajes'],
        'meta_title' => 'Tres decisiones de copy para mejorar una home',
        'meta_description' => 'Promesa, orden y CTA: tres cambios concretos para una home mas clara.',
        'published_at' => $now,
        'created_at' => $now,
        'updated_at' => $now,
    ],
];

$homePage['html_content'] = example_homepage_html($homePage, $posts, $images);
$servicesPage['html_content'] = example_services_html($images);
$aboutPage['html_content'] = example_about_html($images);
$contactPage['html_content'] = example_contact_html($contactPage, $images);

foreach ([$homePage, $servicesPage, $aboutPage, $contactPage] as &$page) {
    ccms_push_page_revision($page, 'Example site seed');
}
unset($page);

$data['pages'] = [$homePage, $servicesPage, $aboutPage, $contactPage];
$data['posts'] = $posts;
$data['audit_logs'] = [
    ccms_audit_log_entry('example.seeded', 'Example site generated', $owner, [
        'example_slug' => $exampleSlug,
        'pages' => count($data['pages']),
        'posts' => count($data['posts']),
    ]),
];

ccms_save_data($data);

$backupPayload = ccms_export_backup_payload($data);
example_write_file(
    $exampleRoot . '/backup.json',
    json_encode($backupPayload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
);

$build = ccms_static_export_build($data);
$staticDir = $exampleRoot . '/static-site';
$staticZip = $exampleRoot . '/static-site.zip';
example_rrmdir($staticDir);
@mkdir(dirname($staticDir), 0775, true);
rename((string) $build['dir'], $staticDir);
$generatedZip = ccms_static_export_zip(['dir' => $staticDir]);
if (is_file($generatedZip)) {
    @rename($generatedZip, $staticZip);
}

$summary = <<<MD
# Studio Norte Demo

Ejemplo completo de web que se puede crear con LinuxCMS.

## Que incluye

- 4 paginas publicas:
  - Inicio
  - Servicios
  - Sobre nosotros
  - Contacto
- 3 posts publicados
- Blog con archivo, categorias, tags y RSS
- Formularios reales de newsletter y contacto
- SEO minimo activo
- Analytics configurado en modo demo
- Export estatico listo para hosting basico

## Credenciales del backup de ejemplo

- usuario: demo
- password: {$demoPassword}

Importante:
- El backup reemplaza sitio, paginas, posts, media y usuarios.
- Importalo en una instalacion de prueba o en una instalacion nueva.

## Archivos principales

- backup importable: `backup.json`
- export estatico: `static-site/`
- zip del export estatico: `static-site.zip`
- runtime aislado de ejemplo: `runtime/`

## Como usarlo

### Ver el export estatico

Abre `static-site/index.html` o sube el contenido de `static-site/` a un hosting basico.

### Importarlo dentro de LinuxCMS

1. Arranca LinuxCMS.
2. Entra en `/r-admin`.
3. Ve a `Backups`.
4. Importa `backup.json`.

### Runtime aislado

El directorio `runtime/` contiene `data/` y `uploads/` del ejemplo. Sirve para pruebas, exportes o desarrollo.
MD;

example_write_file($exampleRoot . '/site-summary.md', $summary . "\n");

fwrite(STDOUT, "Example generated in:\n");
fwrite(STDOUT, $exampleRoot . "\n");
fwrite(STDOUT, "Backup:\n");
fwrite(STDOUT, $exampleRoot . "/backup.json\n");
fwrite(STDOUT, "Static export:\n");
fwrite(STDOUT, $staticDir . "\n");
fwrite(STDOUT, "Static zip:\n");
fwrite(STDOUT, $staticZip . "\n");
