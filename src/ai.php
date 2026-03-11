<?php
declare(strict_types=1);

require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/render.php';

function ccms_ai_defaults(): array
{
    return [
        'endpoint' => 'http://127.0.0.1:1234/v1',
        'model' => '',
        'temperature' => 0.2,
        'max_tokens' => 2800,
        'timeout' => 20,
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
    ];
}

function ccms_ai_settings_input(array $post): array
{
    $defaults = ccms_ai_defaults();
    return [
        'endpoint' => trim((string) ($post['ai_endpoint'] ?? $defaults['endpoint'])) ?: $defaults['endpoint'],
        'model' => trim((string) ($post['ai_model'] ?? '')),
        'temperature' => max(0, min(1.2, (float) ($post['ai_temperature'] ?? $defaults['temperature']))),
        'max_tokens' => max(600, min(6000, (int) ($post['ai_max_tokens'] ?? $defaults['max_tokens']))),
        'timeout' => max(5, min(120, (int) ($post['ai_timeout'] ?? $defaults['timeout']))),
    ];
}

function ccms_ai_http_json(string $method, string $url, ?array $payload = null, int $timeout = 30): array
{
    $headers = ['Content-Type: application/json'];
    $body = $payload !== null ? json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null;
    $connectTimeout = max(3, min(8, $timeout));

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

function ccms_ai_probe(array $settings): array
{
    $endpoint = rtrim((string) ($settings['endpoint'] ?? ''), '/');
    if ($endpoint === '') {
        throw new RuntimeException('Define primero el endpoint de LM Studio.');
    }
    $response = ccms_ai_http_json('GET', $endpoint . '/models', null, (int) ($settings['timeout'] ?? 20));
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
    ];
    return $presets[$industry] ?? ['accent' => '#c86f5c', 'accent_dark' => '#ab5d4e', 'bg_from' => '#f7f4ee', 'bg_to' => '#ffffff', 'text_primary' => '#2f241f', 'text_secondary' => '#6b5b53'];
}

function ccms_ai_fallback_payload(array $brief): array
{
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

function ccms_ai_prompt(array $brief): string
{
    $supportedBlocks = [
        'sticky_header',
        'hero_fullscreen',
        'hero_split',
        'split_image_left',
        'split_image_right',
        'text_block',
        'features',
        'services_cards',
        'stats',
        'testimonial_cards',
        'faq',
        'pricing',
        'portfolio_grid',
        'blog_grid',
        'gallery',
        'cta',
        'lead_form',
        'map_embed',
        'footer_multi',
    ];

    return "You are generating a first-draft website capsule for a local CMS that renders blocks in PHP.\n"
        . "Return JSON only. No markdown, no prose.\n"
        . "Use only these block types: " . implode(', ', $supportedBlocks) . ".\n"
        . "The JSON format must be:\n"
        . "{\n"
        . "  \"site\": {\n"
        . "    \"title\": \"...\",\n"
        . "    \"tagline\": \"...\",\n"
        . "    \"footer_text\": \"...\",\n"
        . "    \"contact_email\": \"...\",\n"
        . "    \"colors\": {\n"
        . "      \"bg\": \"#...\",\n"
        . "      \"surface\": \"#...\",\n"
        . "      \"text\": \"#...\",\n"
        . "      \"muted\": \"#...\",\n"
        . "      \"primary\": \"#...\",\n"
        . "      \"secondary\": \"#...\"\n"
        . "    }\n"
        . "  },\n"
        . "  \"page\": {\n"
        . "    \"title\": \"...\",\n"
        . "    \"slug\": \"...\",\n"
        . "    \"status\": \"draft\",\n"
        . "    \"show_in_menu\": true,\n"
        . "    \"menu_label\": \"...\",\n"
        . "    \"meta_title\": \"...\",\n"
        . "    \"meta_description\": \"...\",\n"
        . "    \"capsule\": {\n"
        . "      \"meta\": {\"business_name\": \"...\", \"template\": \"generic\"},\n"
        . "      \"style\": {\"accent\": \"#...\", \"accent_dark\": \"#...\", \"bg_from\": \"#...\", \"bg_to\": \"#...\", \"text_primary\": \"#...\", \"text_secondary\": \"#...\", \"text_muted\": \"#...\", \"font_family\": \"Inter, Arial, Helvetica, sans-serif\", \"font_heading\": \"Inter, Arial, Helvetica, sans-serif\"},\n"
        . "      \"blocks\": [ ... ]\n"
        . "    }\n"
        . "  }\n"
        . "}\n"
        . "Keep the first draft practical: header, hero, supporting section, benefits/services, proof, contact, footer.\n"
        . "Business brief:\n"
        . json_encode($brief, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}

function ccms_ai_generate_payload(array $brief, array $settings): array
{
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
        $fallback = ccms_ai_fallback_payload($brief);
        $fallback['_meta'] = ['mode' => 'fallback', 'reason' => 'LM Studio endpoint or model not available'];
        return $fallback;
    }

    try {
        $response = ccms_ai_http_json('POST', $endpoint . '/chat/completions', [
            'model' => $model,
            'temperature' => (float) ($settings['temperature'] ?? 0.2),
            'max_tokens' => (int) ($settings['max_tokens'] ?? 2800),
            'messages' => [
                ['role' => 'system', 'content' => 'You generate structured website capsules for a local CMS. Return valid JSON only.'],
                ['role' => 'user', 'content' => ccms_ai_prompt($brief)],
            ],
        ], (int) ($settings['timeout'] ?? 45));
        if ($response['status'] >= 400) {
            throw new RuntimeException('LM Studio devolvió HTTP ' . $response['status']);
        }
        $content = (string) ($response['body']['choices'][0]['message']['content'] ?? '');
        $decoded = ccms_ai_extract_json($content);
        if (!is_array($decoded['page']['capsule'] ?? null)) {
            throw new RuntimeException('La respuesta de LM Studio no incluye una cápsula válida.');
        }
        $decoded['_meta'] = ['mode' => 'lm_studio', 'model' => $model];
        return $decoded;
    } catch (Throwable $e) {
        $fallback = ccms_ai_fallback_payload($brief);
        $fallback['_meta'] = ['mode' => 'fallback', 'reason' => $e->getMessage()];
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
    $htmlContent = ccms_render_capsule_body($capsule);
    return [
        'id' => ccms_next_id('page'),
        'title' => $title,
        'slug' => $slug,
        'status' => ($page['status'] ?? 'draft') === 'published' ? 'published' : 'draft',
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
