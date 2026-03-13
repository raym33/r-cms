<?php
declare(strict_types=1);

require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/render.php';
require_once __DIR__ . '/premium_packs.php';

function ccms_ai_defaults(): array
{
    return [
        'endpoint' => 'http://127.0.0.1:1234/v1',
        'model' => '',
        'temperature' => 0.2,
        'max_tokens' => 2800,
        'timeout' => 20,
        'preferred_pack_id' => 'auto',
    ];
}

function ccms_ai_settings(array $data): array
{
    $settings = is_array($data['local_ai'] ?? null) ? $data['local_ai'] : [];
    $defaults = ccms_ai_defaults();
    return [
        'endpoint' => trim((string) ($settings['endpoint'] ?? $defaults['endpoint'])) ?: $defaults['endpoint'],
        'model' => trim((string) ($settings['model'] ?? $defaults['model'])),
        'temperature' => max(0, min(1.2, (float) ($settings['temperature'] ?? $defaults['temperature']))),
        'max_tokens' => max(600, min(6000, (int) ($settings['max_tokens'] ?? $defaults['max_tokens']))),
        'timeout' => max(5, min(120, (int) ($settings['timeout'] ?? $defaults['timeout']))),
        'preferred_pack_id' => ccms_resolve_premium_pack_id(
            (string) ($settings['preferred_pack_id'] ?? $defaults['preferred_pack_id']),
            'generic'
        ) ?: 'auto',
    ];
}

function ccms_ai_settings_input(array $post, ?array $existing = null): array
{
    $defaults = ccms_ai_defaults();
    $existing = is_array($existing) ? $existing : [];
    $preferredPackId = ccms_resolve_premium_pack_id(
        (string) ($post['preferred_pack_id'] ?? $existing['preferred_pack_id'] ?? $defaults['preferred_pack_id']),
        'generic'
    ) ?: 'auto';
    return [
        'endpoint' => trim((string) ($post['ai_endpoint'] ?? $defaults['endpoint'])) ?: $defaults['endpoint'],
        'model' => trim((string) ($post['ai_model'] ?? '')),
        'temperature' => max(0, min(1.2, (float) ($post['ai_temperature'] ?? $defaults['temperature']))),
        'max_tokens' => max(600, min(6000, (int) ($post['ai_max_tokens'] ?? $defaults['max_tokens']))),
        'timeout' => max(5, min(120, (int) ($post['ai_timeout'] ?? $defaults['timeout']))),
        'preferred_pack_id' => $preferredPackId,
    ];
}

function ccms_ai_brief_text(array $brief, string $key, string $fallback = ''): string
{
    return trim((string) ($brief[$key] ?? '')) ?: $fallback;
}

function ccms_ai_resolve_pack(array $brief, ?string $requestedPackId = null): ?array
{
    $industry = ccms_ai_brief_text($brief, 'industry', 'generic');
    $businessName = ccms_ai_brief_text($brief, 'business_name', '');
    $resolvedPackId = ccms_resolve_premium_pack_id((string) ($requestedPackId ?? ($brief['pack_id'] ?? 'auto')), $industry, $businessName);
    return $resolvedPackId !== '' ? ccms_load_premium_pack($resolvedPackId) : null;
}

function ccms_ai_pack_offer_line(array $brief): string
{
    $offer = ccms_ai_brief_text($brief, 'offer', 'Una propuesta clara, útil y fácil de entender.');
    $audience = ccms_ai_brief_text($brief, 'audience', '');
    if ($audience === '') {
        return $offer;
    }
    return $offer . ' Para ' . $audience . '.';
}

function ccms_ai_pack_support_points(array $brief): array
{
    $offer = ccms_ai_brief_text($brief, 'offer', 'Explica aquí la oferta principal.');
    $audience = ccms_ai_brief_text($brief, 'audience', 'Clientes que necesitan claridad antes de decidir.');
    $goal = ccms_ai_brief_text($brief, 'goal', 'Generar conversaciones y oportunidades reales.');
    return [
        ['title' => 'Oferta clara', 'desc' => $offer],
        ['title' => 'Cliente ideal', 'desc' => $audience],
        ['title' => 'Objetivo', 'desc' => $goal],
    ];
}

function ccms_ai_placeholder_testimonials(string $businessName): array
{
    return [
        ['quote' => 'Añade aquí una reseña real para reforzar la confianza.', 'name' => 'Cliente real', 'role' => $businessName, 'stars' => 5],
        ['quote' => 'Sustituye este placeholder por una prueba social concreta.', 'name' => 'Caso o testimonio', 'role' => $businessName, 'stars' => 5],
    ];
}

function ccms_ai_placeholder_stats(): array
{
    return [
        ['value' => '01', 'label' => 'Oferta clara'],
        ['value' => '02', 'label' => 'Prueba visible'],
        ['value' => '03', 'label' => 'CTA directo'],
        ['value' => '04', 'label' => 'CMS editable'],
    ];
}

function ccms_ai_capsule_has_block_type(array $capsule, array $types): bool
{
    foreach (($capsule['blocks'] ?? []) as $block) {
        $type = (string) ($block['type'] ?? '');
        if (in_array($type, $types, true)) {
            return true;
        }
    }
    return false;
}

function ccms_ai_customize_pack_capsule(array $capsule, array $brief, array $pack): array
{
    $businessName = ccms_ai_brief_text($brief, 'business_name', 'Nuevo proyecto');
    $pageTitle = ccms_ai_brief_text($brief, 'page_title', $businessName);
    $offer = ccms_ai_brief_text($brief, 'offer', 'Explica aquí la propuesta principal.');
    $audience = ccms_ai_brief_text($brief, 'audience', 'Personas que buscan una solución clara.');
    $goal = ccms_ai_brief_text($brief, 'goal', 'Generar leads y conversaciones cualificadas.');
    $ctaText = ccms_ai_brief_text($brief, 'cta_text', 'Contactar');
    $tone = ccms_ai_brief_text($brief, 'tone', 'Claro, útil y confiable.');
    $notes = ccms_ai_brief_text($brief, 'notes', '');
    $supportPoints = ccms_ai_pack_support_points($brief);
    $packLabel = ccms_ai_brief_text($pack, 'label', 'Premium Pack');

    $capsule['meta']['business_name'] = $businessName;
    $capsule['meta']['page_title'] = $pageTitle;
    $capsule['meta']['description'] = $offer;
    $capsule['meta']['template'] = (string) ($pack['id'] ?? ($capsule['meta']['template'] ?? 'premium-pack'));
    $capsule['meta']['industry'] = ccms_ai_brief_text($brief, 'industry', ccms_ai_brief_text($pack, 'industry', 'generic'));

    foreach (($capsule['blocks'] ?? []) as $index => $block) {
        $type = (string) ($block['type'] ?? '');
        $props = is_array($block['props'] ?? null) ? $block['props'] : [];

        switch ($type) {
            case 'nav':
            case 'sticky_header':
            case 'offcanvas_menu':
                $props['brand'] = $businessName;
                if (isset($props['announcement'])) {
                    $props['announcement'] = ccms_ai_brief_text($pack, 'industry_label', 'Premium pack') . ' · ' . $packLabel;
                }
                if (isset($props['cta_text'])) {
                    $props['cta_text'] = $ctaText;
                }
                if (isset($props['cta_href'])) {
                    $props['cta_href'] = '#contact';
                }
                break;

            case 'hero':
            case 'hero_fullscreen':
            case 'hero_split':
            case 'hero_slider':
            case 'hero_particles':
            case 'hero_video':
                $props['title'] = $pageTitle;
                $props['subtitle'] = ccms_ai_pack_offer_line($brief);
                $props['badge'] = ccms_ai_brief_text($pack, 'industry_label', (string) ($props['badge'] ?? 'Premium'));
                if (isset($props['cta_primary'])) {
                    $props['cta_primary'] = $ctaText;
                }
                if (isset($props['cta_text'])) {
                    $props['cta_text'] = $ctaText;
                }
                if (isset($props['cta_href'])) {
                    $props['cta_href'] = '#contact';
                }
                if ($type === 'hero_slider' && is_array($props['slides'] ?? null)) {
                    foreach ($props['slides'] as $slideIndex => $slide) {
                        if (!is_array($slide)) {
                            continue;
                        }
                        $props['slides'][$slideIndex]['title'] = $slideIndex === 0 ? $pageTitle : ($slide['title'] ?? 'Bloque visual');
                        $props['slides'][$slideIndex]['subtitle'] = $slideIndex === 0 ? ccms_ai_pack_offer_line($brief) : (string) ($slide['subtitle'] ?? '');
                        $props['slides'][$slideIndex]['cta_text'] = $ctaText;
                        $props['slides'][$slideIndex]['cta_href'] = '#contact';
                    }
                }
                break;

            case 'split_image_left':
            case 'split_image_right':
            case 'split_content':
                $props['title'] = (string) ($props['title'] ?? 'Por qué esta propuesta merece atención');
                $props['text'] = $offer . ' ' . $goal . ' ' . ($notes !== '' ? $notes : 'Puedes pulir esta sección luego desde el builder.');
                $props['bullets'] = [
                    'Enfoque para ' . $audience,
                    'Objetivo: ' . $goal,
                    'Tono: ' . $tone,
                ];
                break;

            case 'text_block':
                $props['title'] = (string) ($props['title'] ?? 'La historia detrás de la oferta');
                $props['paragraphs'] = [
                    $offer,
                    $goal . ($notes !== '' ? ' ' . $notes : ''),
                ];
                $props['quote'] = 'Este bloque sirve como punto de partida. Reemplázalo con una narrativa real del negocio.';
                $props['quote_author'] = $businessName;
                break;

            case 'features':
                $props['title'] = (string) ($props['title'] ?? 'Puntos clave de la propuesta');
                $props['subtitle'] = 'Usa estas tarjetas como primer borrador estructurado.';
                $props['items'] = $supportPoints;
                break;

            case 'numbered_features':
                $props['title'] = (string) ($props['title'] ?? 'Tres razones para quedarse');
                $props['subtitle'] = 'Estructura base para explicar la propuesta sin repetirte.';
                $props['items'] = array_map(static function (array $item, int $itemIndex): array {
                    return [
                        'number' => str_pad((string) ($itemIndex + 1), 2, '0', STR_PAD_LEFT),
                        'title' => $item['title'],
                        'desc' => $item['desc'],
                    ];
                }, $supportPoints, array_keys($supportPoints));
                break;

            case 'services_cards':
                $props['title'] = (string) ($props['title'] ?? 'Cómo se ordena la oferta');
                $props['subtitle'] = 'Tres tarjetas iniciales para explicar valor, público y resultado.';
                $props['services'] = [
                    [
                        'icon' => 'target',
                        'title' => 'Oferta',
                        'desc' => $offer,
                        'bullets' => ['Qué incluye', 'Qué resuelve', 'Por qué importa'],
                        'cta_text' => $ctaText,
                        'cta_href' => '#contact',
                    ],
                    [
                        'icon' => 'users',
                        'title' => 'Cliente ideal',
                        'desc' => $audience,
                        'bullets' => ['Dolor principal', 'Momento de compra', 'Necesidad concreta'],
                        'cta_text' => $ctaText,
                        'cta_href' => '#contact',
                    ],
                    [
                        'icon' => 'zap',
                        'title' => 'Resultado esperado',
                        'desc' => $goal,
                        'bullets' => ['Siguiente paso claro', 'Confianza', 'Conversión'],
                        'cta_text' => $ctaText,
                        'cta_href' => '#contact',
                    ],
                ];
                break;

            case 'stats':
                $props['items'] = ccms_ai_placeholder_stats();
                break;

            case 'testimonial_cards':
            case 'testimonial_carousel':
            case 'reviews_summary':
                $props['title'] = (string) ($props['title'] ?? 'Prueba social');
                $props['subtitle'] = 'Sustituye estos placeholders por reseñas reales del cliente.';
                if ($type === 'reviews_summary') {
                    $props['summary'] = 'Añade aquí métricas o reseñas reales cuando estén disponibles.';
                    $props['items'] = ccms_ai_placeholder_testimonials($businessName);
                } else {
                    $props['items'] = ccms_ai_placeholder_testimonials($businessName);
                }
                break;

            case 'faq':
                $props['title'] = (string) ($props['title'] ?? 'Preguntas frecuentes');
                $props['items'] = [
                    ['q' => '¿Para quién es esta propuesta?', 'a' => $audience],
                    ['q' => '¿Qué resultado busca esta web?', 'a' => $goal],
                ];
                break;

            case 'tabs_content':
                $props['title'] = (string) ($props['title'] ?? 'Cómo se estructura la propuesta');
                if (is_array($props['tabs'] ?? null)) {
                    $tabTexts = [$offer, $audience, $goal];
                    foreach ($props['tabs'] as $tabIndex => $tab) {
                        if (!is_array($tab)) {
                            continue;
                        }
                        $props['tabs'][$tabIndex]['text'] = $tabTexts[$tabIndex] ?? (string) ($tab['text'] ?? '');
                    }
                }
                break;

            case 'timeline':
            case 'process':
                $props['title'] = (string) ($props['title'] ?? 'De la visita al siguiente paso');
                if (is_array($props['items'] ?? null)) {
                    $steps = ['Claridad', 'Confianza', 'Acción'];
                    foreach ($props['items'] as $itemIndex => $item) {
                        if (!is_array($item)) {
                            continue;
                        }
                        $props['items'][$itemIndex]['title'] = $steps[$itemIndex] ?? (string) ($item['title'] ?? 'Paso');
                    }
                }
                break;

            case 'lead_form':
            case 'contact':
                $props['badge'] = (string) ($props['badge'] ?? 'Contacto');
                $props['title'] = 'Listo para dar el siguiente paso?';
                $props['subtitle'] = 'Usa este formulario como punto de entrada para ' . $goal;
                $props['button_text'] = $ctaText;
                break;

            case 'newsletter':
                $props['title'] = 'Mantén el contacto';
                $props['subtitle'] = 'Usa este bloque si la estrategia necesita seguimiento por email.';
                $props['button_text'] = $ctaText;
                break;

            case 'cta':
            case 'popup_cta':
                $props['title'] = 'Convierte el interés en conversación';
                $props['subtitle'] = $goal;
                $props['button_text'] = $ctaText;
                $props['button_href'] = '#contact';
                break;

            case 'footer_multi':
                $props['brand'] = $businessName;
                $props['description'] = $offer;
                $props['contact_lines'] = [
                    'Añade aquí tu email real',
                    'Añade aquí tu teléfono real',
                ];
                $props['copyright'] = '© ' . gmdate('Y') . ' ' . $businessName;
                break;
        }

        $capsule['blocks'][$index]['props'] = $props;
    }

    if (!ccms_ai_capsule_has_block_type($capsule, ['lead_form', 'contact', 'newsletter'])) {
        $capsule['blocks'][] = [
            'id' => ccms_next_id('block'),
            'type' => 'lead_form',
            'props' => [
                'badge' => 'Contacto',
                'title' => 'Start the conversation',
                'subtitle' => 'Usa este formulario como primer paso para ' . $goal,
                'button_text' => $ctaText,
                'privacy_text' => 'Sustituye este texto por el flujo real de captación del proyecto.',
            ],
        ];
    }

    return $capsule;
}

function ccms_ai_payload_from_pack(array $brief, array $pack): array
{
    $businessName = ccms_ai_brief_text($brief, 'business_name', 'New Project');
    $pageTitle = ccms_ai_brief_text($brief, 'page_title', $businessName);
    $offer = ccms_ai_brief_text($brief, 'offer', 'A clear offer worth exploring.');
    $capsule = ccms_ai_customize_pack_capsule($pack['capsule'], $brief, $pack);
    $style = is_array($capsule['style'] ?? null) ? $capsule['style'] : [];

    return [
        'site' => [
            'title' => $businessName,
            'tagline' => $offer,
            'footer_text' => 'Powered by LinuxCMS',
            'contact_email' => '',
            'theme_preset' => (string) ($pack['visual_profile'] ?? 'warm'),
            'font_pairing' => 'auto',
            'colors' => [
                'bg' => (string) ($style['bg_from'] ?? '#f7f4ee'),
                'surface' => (string) ($style['bg_to'] ?? '#ffffff'),
                'text' => (string) ($style['text_primary'] ?? '#2f241f'),
                'muted' => (string) ($style['text_secondary'] ?? '#6b5b53'),
                'primary' => (string) ($style['accent'] ?? '#c86f5c'),
                'secondary' => (string) ($style['accent_dark'] ?? '#ab5d4e'),
            ],
        ],
        'page' => [
            'title' => $pageTitle,
            'slug' => ccms_slugify((string) ($brief['slug'] ?? $pageTitle)),
            'status' => 'draft',
            'show_in_menu' => true,
            'menu_label' => $pageTitle,
            'meta_title' => $pageTitle,
            'meta_description' => $offer,
            'capsule' => $capsule,
        ],
    ];
}

function ccms_ai_merge_payload_with_base_pack(array $generatedPayload, array $basePayload): array
{
    $merged = $basePayload;
    if (is_array($generatedPayload['site'] ?? null)) {
        $merged['site'] = array_replace_recursive($basePayload['site'], $generatedPayload['site']);
        $merged['site']['colors'] = array_replace_recursive($basePayload['site']['colors'] ?? [], $generatedPayload['site']['colors'] ?? []);
    }
    if (is_array($generatedPayload['page'] ?? null)) {
        $merged['page'] = array_replace_recursive($basePayload['page'], $generatedPayload['page']);
    }

    $baseCapsule = is_array($basePayload['page']['capsule'] ?? null) ? $basePayload['page']['capsule'] : [];
    $incomingCapsule = is_array($generatedPayload['page']['capsule'] ?? null) ? $generatedPayload['page']['capsule'] : [];
    $mergedCapsule = $baseCapsule;
    $mergedCapsule['meta'] = array_replace_recursive($baseCapsule['meta'] ?? [], $incomingCapsule['meta'] ?? []);
    $mergedCapsule['style'] = array_replace_recursive($baseCapsule['style'] ?? [], $incomingCapsule['style'] ?? []);

    $baseBlocks = is_array($baseCapsule['blocks'] ?? null) ? $baseCapsule['blocks'] : [];
    $incomingBlocks = is_array($incomingCapsule['blocks'] ?? null) ? array_values($incomingCapsule['blocks']) : [];
    $mergedBlocks = [];
    foreach ($baseBlocks as $index => $baseBlock) {
        $candidate = is_array($incomingBlocks[$index] ?? null) ? $incomingBlocks[$index] : [];
        if (($candidate['type'] ?? '') !== ($baseBlock['type'] ?? '')) {
            $candidate = [];
        }
        $mergedBlock = $baseBlock;
        if ($candidate !== []) {
            $mergedBlock['props'] = array_replace_recursive(
                is_array($baseBlock['props'] ?? null) ? $baseBlock['props'] : [],
                is_array($candidate['props'] ?? null) ? $candidate['props'] : []
            );
            $mergedBlock['style'] = array_replace_recursive(
                is_array($baseBlock['style'] ?? null) ? $baseBlock['style'] : [],
                is_array($candidate['style'] ?? null) ? $candidate['style'] : []
            );
            $mergedBlock['layout'] = trim((string) ($candidate['layout'] ?? ($baseBlock['layout'] ?? '')));
        }
        $mergedBlocks[] = $mergedBlock;
    }
    $mergedCapsule['blocks'] = $mergedBlocks;
    $merged['page']['capsule'] = ccms_normalize_capsule($mergedCapsule);
    return $merged;
}

function ccms_ai_http_json(string $method, string $url, ?array $payload = null, int $timeout = 30): array
{
    $headers = ['Content-Type: application/json'];
    $body = $payload !== null ? json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null;
    $connectTimeout = max(2, min(5, $timeout));

    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, strtoupper($method));
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $connectTimeout);
        curl_setopt($ch, CURLOPT_NOSIGNAL, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        if ($body !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        }
        $responseBody = curl_exec($ch);
        $curlError = curl_error($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        if ($responseBody === false) {
            throw new RuntimeException('LM Studio no responde: ' . $curlError);
        }
    } else {
        $context = stream_context_create([
            'http' => [
                'method' => strtoupper($method),
                'timeout' => $timeout,
                'header' => implode("\r\n", $headers),
                'content' => $body ?? '',
            ],
        ]);
        $responseBody = @file_get_contents($url, false, $context);
        if ($responseBody === false) {
            throw new RuntimeException('LM Studio no responde o la extensión curl no está disponible.');
        }
        $statusLine = $http_response_header[0] ?? 'HTTP/1.1 500';
        preg_match('/\s(\d{3})\s/', $statusLine, $matches);
        $status = isset($matches[1]) ? (int) $matches[1] : 500;
    }

    $decoded = json_decode((string) $responseBody, true);
    return [
        'status' => $status,
        'body' => $decoded,
        'raw' => (string) $responseBody,
    ];
}

function ccms_ai_prepare_runtime(int $timeout): void
{
    $target = max(20, min(180, $timeout + 15));
    if (function_exists('ignore_user_abort')) {
        @ignore_user_abort(true);
    }
    if (function_exists('set_time_limit')) {
        @set_time_limit($target);
    }
    @ini_set('max_execution_time', (string) $target);
}

function ccms_ai_probe(array $settings): array
{
    $endpoint = rtrim((string) ($settings['endpoint'] ?? ''), '/');
    if ($endpoint === '') {
        throw new RuntimeException('Define primero el endpoint de LM Studio.');
    }
    $probeTimeout = max(3, min(6, (int) ($settings['timeout'] ?? 20)));
    $response = ccms_ai_http_json('GET', $endpoint . '/models', null, $probeTimeout);
    if ($response['status'] >= 400) {
        throw new RuntimeException('LM Studio devolvió HTTP ' . $response['status'] . ' al consultar modelos.');
    }
    $models = [];
    foreach (($response['body']['data'] ?? []) as $row) {
        $id = trim((string) ($row['id'] ?? ''));
        if ($id !== '') {
            $models[] = $id;
        }
    }
    return [
        'ok' => !empty($models),
        'models' => $models,
    ];
}

function ccms_ai_extract_json(string $text): array
{
    $trimmed = trim($text);
    if ($trimmed === '') {
        throw new RuntimeException('LM Studio devolvió una respuesta vacía.');
    }
    $trimmed = preg_replace('/^```(?:json)?/i', '', $trimmed) ?? $trimmed;
    $trimmed = preg_replace('/```$/', '', $trimmed) ?? $trimmed;
    $trimmed = trim($trimmed);
    $decoded = json_decode($trimmed, true);
    if (is_array($decoded)) {
        return $decoded;
    }
    $firstBrace = strpos($trimmed, '{');
    $lastBrace = strrpos($trimmed, '}');
    if ($firstBrace === false || $lastBrace === false || $lastBrace <= $firstBrace) {
        throw new RuntimeException('No se pudo extraer JSON válido de la respuesta de LM Studio.');
    }
    $candidate = substr($trimmed, $firstBrace, $lastBrace - $firstBrace + 1);
    $decoded = json_decode($candidate, true);
    if (!is_array($decoded)) {
        throw new RuntimeException('LM Studio respondió, pero no con un JSON utilizable.');
    }
    return $decoded;
}

function ccms_ai_default_palette(string $industry): array
{
    $industry = ccms_slugify($industry);
    $presets = [
        'lawyer' => ['accent' => '#8f5f4d', 'accent_dark' => '#6d4638', 'bg_from' => '#f5efe9', 'bg_to' => '#fffdfa', 'text_primary' => '#2d221d', 'text_secondary' => '#665851'],
        'restaurant' => ['accent' => '#c86f5c', 'accent_dark' => '#a85747', 'bg_from' => '#f7efe8', 'bg_to' => '#fffaf6', 'text_primary' => '#2f241f', 'text_secondary' => '#6e5c53'],
        'real-estate' => ['accent' => '#5c7db8', 'accent_dark' => '#3d5e97', 'bg_from' => '#f1f5fb', 'bg_to' => '#ffffff', 'text_primary' => '#1f2a3a', 'text_secondary' => '#5f6d80'],
        'saas' => ['accent' => '#4f77ff', 'accent_dark' => '#2f57df', 'bg_from' => '#eef4ff', 'bg_to' => '#ffffff', 'text_primary' => '#1f2840', 'text_secondary' => '#5b6782'],
        'creative' => ['accent' => '#b86f78', 'accent_dark' => '#934f58', 'bg_from' => '#f8f0f1', 'bg_to' => '#fffefe', 'text_primary' => '#2f2025', 'text_secondary' => '#6b5960'],
        'clinic' => ['accent' => '#5b9f96', 'accent_dark' => '#3e8178', 'bg_from' => '#eef8f6', 'bg_to' => '#ffffff', 'text_primary' => '#1f2f2d', 'text_secondary' => '#57706d'],
        'beauty' => ['accent' => '#d46aa1', 'accent_dark' => '#b34c81', 'bg_from' => '#fff3f8', 'bg_to' => '#fffefe', 'text_primary' => '#34212d', 'text_secondary' => '#7b6071'],
        'public-sector' => ['accent' => '#22c55e', 'accent_dark' => '#15803d', 'bg_from' => '#edf8f1', 'bg_to' => '#ffffff', 'text_primary' => '#153524', 'text_secondary' => '#4f6f5d'],
    ];
    return $presets[$industry] ?? ['accent' => '#c86f5c', 'accent_dark' => '#ab5d4e', 'bg_from' => '#f7f4ee', 'bg_to' => '#ffffff', 'text_primary' => '#2f241f', 'text_secondary' => '#6b5b53'];
}

function ccms_ai_fallback_payload(array $brief): array
{
    $pack = ccms_ai_resolve_pack($brief);
    if ($pack) {
        return ccms_ai_payload_from_pack($brief, $pack);
    }

    $businessName = trim((string) ($brief['business_name'] ?? 'New Project')) ?: 'New Project';
    $pageTitle = trim((string) ($brief['page_title'] ?? $businessName)) ?: $businessName;
    $industry = trim((string) ($brief['industry'] ?? 'business')) ?: 'business';
    $offer = trim((string) ($brief['offer'] ?? 'A professional offer presented with clarity and confidence.')) ?: 'A professional offer presented with clarity and confidence.';
    $audience = trim((string) ($brief['audience'] ?? 'Potential clients who want a clear explanation of the service.')) ?: 'Potential clients who want a clear explanation of the service.';
    $goal = trim((string) ($brief['goal'] ?? 'Generate qualified leads and enquiries.')) ?: 'Generate qualified leads and enquiries.';
    $ctaText = trim((string) ($brief['cta_text'] ?? 'Contact us')) ?: 'Contact us';
    $tone = trim((string) ($brief['tone'] ?? 'Warm, clear and trustworthy.')) ?: 'Warm, clear and trustworthy.';
    $notes = trim((string) ($brief['notes'] ?? '')) ?: '';
    $palette = ccms_ai_default_palette((string) $industry);

    $capsule = [
        'meta' => [
            'business_name' => $businessName,
            'template' => 'generic',
            'industry' => $industry,
        ],
        'style' => array_merge($palette, [
            'text_muted' => $palette['text_secondary'],
            'font_family' => 'Inter, Arial, Helvetica, sans-serif',
            'font_heading' => 'Inter, Arial, Helvetica, sans-serif',
        ]),
        'blocks' => [
            [
                'id' => ccms_next_id('block'),
                'type' => 'sticky_header',
                'props' => [
                    'brand' => $businessName,
                    'announcement' => 'Built locally in LinuxCMS and ready to refine.',
                    'links' => [
                        ['text' => 'Home', 'href' => '#hero'],
                        ['text' => 'Offer', 'href' => '#offer'],
                        ['text' => 'Contact', 'href' => '#contact'],
                    ],
                    'cta_text' => $ctaText,
                    'cta_href' => '#contact',
                ],
            ],
            [
                'id' => ccms_next_id('block'),
                'type' => 'hero_fullscreen',
                'props' => [
                    'badge' => strtoupper(substr($industry, 0, 18)),
                    'title' => $pageTitle,
                    'subtitle' => $offer . ' Designed for ' . $audience,
                    'background_image' => '',
                    'cta_primary' => $ctaText,
                    'cta_secondary' => 'Learn more',
                    'cta_href' => '#contact',
                ],
            ],
            [
                'id' => ccms_next_id('block'),
                'type' => 'split_image_right',
                'props' => [
                    'badge' => 'About',
                    'title' => 'A clearer way to explain the offer',
                    'text' => 'This first draft focuses on ' . $goal . '. The tone is ' . $tone . '. ' . ($notes !== '' ? $notes : 'You can refine every section from the builder or the content editor.'),
                    'image_url' => '',
                    'bullets' => [
                        'Tailored for ' . $audience,
                        'Built around the goal: ' . $goal,
                        'Ready to edit with keyboard only',
                    ],
                ],
            ],
            [
                'id' => ccms_next_id('block'),
                'type' => 'features',
                'props' => [
                    'badge' => 'What this page highlights',
                    'title' => 'Core points to communicate',
                    'subtitle' => 'Use these cards as the first structured draft.',
                    'items' => [
                        ['title' => 'Offer', 'desc' => $offer],
                        ['title' => 'Audience', 'desc' => $audience],
                        ['title' => 'Goal', 'desc' => $goal],
                    ],
                ],
            ],
            [
                'id' => ccms_next_id('block'),
                'type' => 'testimonial_cards',
                'props' => [
                    'badge' => 'Proof',
                    'title' => 'Social proof placeholders',
                    'subtitle' => 'Replace these quotes with real testimonials later.',
                    'items' => [
                        ['quote' => 'This is a solid first draft that can be refined quickly.', 'name' => 'Client one', 'role' => 'Example role', 'stars' => 5],
                        ['quote' => 'The message is clear, direct and easy to customise.', 'name' => 'Client two', 'role' => 'Example role', 'stars' => 5],
                    ],
                ],
            ],
            [
                'id' => ccms_next_id('block'),
                'type' => 'faq',
                'props' => [
                    'badge' => 'FAQ',
                    'title' => 'Questions worth answering',
                    'items' => [
                        ['q' => 'Who is this for?', 'a' => $audience],
                        ['q' => 'What is the main outcome?', 'a' => $goal],
                    ],
                ],
            ],
            [
                'id' => ccms_next_id('block'),
                'type' => 'lead_form',
                'props' => [
                    'badge' => 'Contact',
                    'title' => 'Start the conversation',
                    'subtitle' => 'Use this section to collect leads and enquiries.',
                    'button_text' => $ctaText,
                    'privacy_text' => 'This form is ready to be connected to your preferred delivery flow.',
                ],
            ],
            [
                'id' => ccms_next_id('block'),
                'type' => 'footer_multi',
                'props' => [
                    'brand' => $businessName,
                    'description' => $offer,
                    'columns' => [
                        ['title' => 'Explore', 'links' => [['text' => 'Home', 'href' => '#hero'], ['text' => 'Offer', 'href' => '#offer']]],
                        ['title' => 'Contact', 'links' => [['text' => $ctaText, 'href' => '#contact']]],
                    ],
                    'contact_lines' => ['Replace with your email', 'Replace with your phone'],
                    'copyright' => '© ' . gmdate('Y') . ' ' . $businessName,
                ],
            ],
        ],
    ];

    return [
        'site' => [
            'title' => $businessName,
            'tagline' => $offer,
            'footer_text' => 'Powered by LinuxCMS',
            'contact_email' => '',
            'colors' => [
                'bg' => $palette['bg_from'],
                'surface' => '#ffffff',
                'text' => $palette['text_primary'],
                'muted' => $palette['text_secondary'],
                'primary' => $palette['accent'],
                'secondary' => $palette['accent_dark'],
            ],
        ],
        'page' => [
            'title' => $pageTitle,
            'slug' => ccms_slugify((string) ($brief['slug'] ?? $pageTitle)),
            'status' => 'draft',
            'show_in_menu' => true,
            'menu_label' => $pageTitle,
            'meta_title' => $pageTitle,
            'meta_description' => $offer,
            'capsule' => $capsule,
        ],
    ];
}

function ccms_ai_prompt(array $brief, array $basePayload, array $pack): string
{
    return "You are adapting a premium website pack for a local CMS that renders blocks in PHP.\n"
        . "Return JSON only. No markdown, no prose.\n"
        . "Keep the SAME block order, SAME block types, SAME visual style and SAME layout choices as the base pack.\n"
        . "Rewrite the content so it fits the business brief.\n"
        . "Do not invent fake awards, fake client names or false numerical claims. If proof is missing, keep placeholders neutral.\n"
        . "Preserve anchors like #contact, #lead-form and internal section links.\n"
        . "Selected premium pack:\n"
        . json_encode([
            'id' => $pack['id'] ?? '',
            'label' => $pack['label'] ?? '',
            'industry' => $pack['industry'] ?? '',
            'description' => $pack['description'] ?? '',
            'visual_profile' => $pack['visual_profile'] ?? '',
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
        . "\nBusiness brief:\n"
        . json_encode($brief, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
        . "\nBase payload to adapt:\n"
        . json_encode($basePayload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}

function ccms_ai_generate_payload(array $brief, array $settings): array
{
    $requestTimeout = (int) ($settings['timeout'] ?? 20);
    ccms_ai_prepare_runtime($requestTimeout);
    $pack = ccms_ai_resolve_pack($brief, (string) ($settings['preferred_pack_id'] ?? ($brief['pack_id'] ?? 'auto')));
    $basePayload = $pack ? ccms_ai_payload_from_pack($brief, $pack) : ccms_ai_fallback_payload($brief);

    $endpoint = rtrim((string) ($settings['endpoint'] ?? ''), '/');
    $model = trim((string) ($settings['model'] ?? ''));
    if ($model === '' && $endpoint !== '') {
        try {
            $probe = ccms_ai_probe($settings);
            $model = $probe['models'][0] ?? '';
        } catch (Throwable $e) {
            $model = '';
        }
    }
    if ($endpoint === '' || $model === '') {
        $fallback = $basePayload;
        $fallback['_meta'] = [
            'mode' => 'fallback',
            'reason' => 'LM Studio endpoint or model not available',
            'pack_id' => $pack['id'] ?? '',
        ];
        return $fallback;
    }

    try {
        $response = ccms_ai_http_json('POST', $endpoint . '/chat/completions', [
            'model' => $model,
            'temperature' => (float) ($settings['temperature'] ?? 0.2),
            'max_tokens' => (int) ($settings['max_tokens'] ?? 2800),
            'messages' => [
                ['role' => 'system', 'content' => 'You generate structured website capsules for a local CMS. Return valid JSON only.'],
                ['role' => 'user', 'content' => ccms_ai_prompt($brief, $basePayload, $pack ?? [])],
            ],
        ], $requestTimeout);
        if ($response['status'] >= 400) {
            throw new RuntimeException('LM Studio devolvió HTTP ' . $response['status']);
        }
        $content = (string) ($response['body']['choices'][0]['message']['content'] ?? '');
        $decoded = ccms_ai_extract_json($content);
        if (!is_array($decoded['page']['capsule'] ?? null)) {
            throw new RuntimeException('La respuesta de LM Studio no incluye una cápsula válida.');
        }
        $decoded = ccms_ai_merge_payload_with_base_pack($decoded, $basePayload);
        $decoded['_meta'] = [
            'mode' => 'lm_studio',
            'model' => $model,
            'pack_id' => $pack['id'] ?? '',
        ];
        return $decoded;
    } catch (Throwable $e) {
        $fallback = $basePayload;
        $fallback['_meta'] = [
            'mode' => 'fallback',
            'reason' => $e->getMessage(),
            'pack_id' => $pack['id'] ?? '',
        ];
        return $fallback;
    }
}

function ccms_ai_page_record_from_payload(array $payload, array $existingPages, bool $setHomepage = false): array
{
    $page = is_array($payload['page'] ?? null) ? $payload['page'] : [];
    $site = is_array($payload['site'] ?? null) ? $payload['site'] : [];
    $capsule = is_array($page['capsule'] ?? null) ? $page['capsule'] : [];
    $title = trim((string) ($page['title'] ?? $site['title'] ?? 'Generated Page')) ?: 'Generated Page';
    $slugBase = trim((string) ($page['slug'] ?? $title)) ?: $title;
    $slug = ccms_slugify($slugBase);
    $used = array_map(static fn(array $row): string => (string) ($row['slug'] ?? ''), $existingPages);
    $candidate = $slug;
    $suffix = 2;
    while (in_array($candidate, $used, true)) {
        $candidate = $slug . '-' . $suffix;
        $suffix++;
    }
    $slug = $candidate;
    $capsule['meta'] ??= [];
    $capsule['meta']['business_name'] = (string) ($capsule['meta']['business_name'] ?? $title);
    $capsule['meta']['template'] = (string) ($capsule['meta']['template'] ?? 'generic');
    $capsule['style'] ??= [];
    $htmlContent = ccms_render_capsule_body($capsule, $site);
    $status = trim((string) ($page['status'] ?? 'draft'));
    if (!in_array($status, ['draft', 'published', 'scheduled'], true)) {
        $status = 'draft';
    }
    $publishedAt = trim((string) ($page['published_at'] ?? ''));
    if ($status === 'published' && $publishedAt === '') {
        $publishedAt = ccms_now_iso();
    }
    return [
        'id' => ccms_next_id('page'),
        'title' => $title,
        'slug' => $slug,
        'status' => $status,
        'published_at' => $publishedAt,
        'is_homepage' => $setHomepage || empty($existingPages),
        'show_in_menu' => array_key_exists('show_in_menu', $page) ? !empty($page['show_in_menu']) : true,
        'menu_label' => trim((string) ($page['menu_label'] ?? $title)) ?: $title,
        'meta_title' => trim((string) ($page['meta_title'] ?? $title)) ?: $title,
        'meta_description' => trim((string) ($page['meta_description'] ?? ($site['tagline'] ?? ''))),
        'capsule_json' => json_encode($capsule, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        'html_content' => $htmlContent,
        'created_at' => ccms_now_iso(),
        'updated_at' => ccms_now_iso(),
        'revisions' => [],
    ];
}
