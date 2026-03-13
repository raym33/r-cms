<?php
declare(strict_types=1);

function ccms_premium_pack_dir(): string
{
    return ccms_root_path('data/premium_packs');
}

function ccms_premium_pack_catalog(): array
{
    return [
        [
            'id' => 'legal-prestige',
            'label' => 'Legal Prestige',
            'industry' => 'lawyer',
            'industry_label' => 'Legal',
            'description' => 'Despacho editorial con serif elegante y sensación premium.',
            'visual_profile' => 'luxury',
            'source' => 'portfolio_senda-legal.json',
        ],
        [
            'id' => 'legal-corporate',
            'label' => 'Legal Corporate',
            'industry' => 'lawyer',
            'industry_label' => 'Legal',
            'description' => 'Despacho sobrio y directo con jerarquía corporativa.',
            'visual_profile' => 'corporate',
            'source' => 'portfolio_costa-defensa.json',
        ],
        [
            'id' => 'saas-editorial',
            'label' => 'SaaS Editorial',
            'industry' => 'saas',
            'industry_label' => 'SaaS / Tech',
            'description' => 'Producto B2B con narrativa de valor y look pulido.',
            'visual_profile' => 'startup',
            'source' => 'portfolio_nodo-crm.json',
        ],
        [
            'id' => 'saas-product',
            'label' => 'SaaS Product',
            'industry' => 'saas',
            'industry_label' => 'SaaS / Tech',
            'description' => 'SaaS con foco en producto, métricas y prueba visual.',
            'visual_profile' => 'startup',
            'source' => 'portfolio_flujobase.json',
        ],
        [
            'id' => 'restaurant-editorial',
            'label' => 'Restaurant Editorial',
            'industry' => 'restaurant',
            'industry_label' => 'Restaurante',
            'description' => 'Restaurante con look gastronómico, oscuro y editorial.',
            'visual_profile' => 'luxury',
            'source' => 'portfolio_fuego-alto.json',
        ],
        [
            'id' => 'restaurant-warm',
            'label' => 'Restaurant Warm',
            'industry' => 'restaurant',
            'industry_label' => 'Restaurante',
            'description' => 'Restaurante cálido y cercano con grid visual potente.',
            'visual_profile' => 'playful',
            'source' => 'portfolio_patio-grano.json',
        ],
        [
            'id' => 'realestate-premium',
            'label' => 'Real Estate Premium',
            'industry' => 'real-estate',
            'industry_label' => 'Inmobiliaria',
            'description' => 'Inmobiliaria premium con fotos grandes y tono aspiracional.',
            'visual_profile' => 'luxury',
            'source' => 'portfolio_costa-magna-homes.json',
        ],
        [
            'id' => 'realestate-modern',
            'label' => 'Real Estate Modern',
            'industry' => 'real-estate',
            'industry_label' => 'Inmobiliaria',
            'description' => 'Inmobiliaria moderna con portfolio y composición clara.',
            'visual_profile' => 'corporate',
            'source' => 'portfolio_monte-claro-living.json',
        ],
        [
            'id' => 'creative-portfolio',
            'label' => 'Creative Portfolio',
            'industry' => 'creative',
            'industry_label' => 'Creativa / portfolio',
            'description' => 'Portfolio creativo con presencia visual y ritmo editorial.',
            'visual_profile' => 'bold',
            'source' => 'portfolio_claudia-mistral.json',
        ],
        [
            'id' => 'creative-studio',
            'label' => 'Creative Studio',
            'industry' => 'creative',
            'industry_label' => 'Creativa / portfolio',
            'description' => 'Estudio creativo con layout más experimental y limpio.',
            'visual_profile' => 'editorial',
            'source' => 'portfolio_bruma-sur-estudio.json',
        ],
        [
            'id' => 'clinic-clean',
            'label' => 'Clinic Clean',
            'industry' => 'clinic',
            'industry_label' => 'Clínica / salud',
            'description' => 'Salud limpia y confiable, centrada en claridad y calma.',
            'visual_profile' => 'minimal',
            'source' => 'portfolio_centro-fisio-origen.json',
        ],
        [
            'id' => 'clinic-premium',
            'label' => 'Clinic Premium',
            'industry' => 'clinic',
            'industry_label' => 'Clínica / salud',
            'description' => 'Clínica premium con before/after y prueba visual.',
            'visual_profile' => 'luxury',
            'source' => 'portfolio_optica-lienzo.json',
        ],
        [
            'id' => 'beauty-glow',
            'label' => 'Beauty Glow',
            'industry' => 'beauty',
            'industry_label' => 'Beauty / salon',
            'description' => 'Beauty con glassmorphism, tono aspiracional y look soft.',
            'visual_profile' => 'playful',
            'source' => 'portfolio_piel-serena-studio.json',
        ],
        [
            'id' => 'public-service',
            'label' => 'Public Service',
            'industry' => 'public-sector',
            'industry_label' => 'Portal público',
            'description' => 'Portal institucional con agenda, servicios y foco en accesos.',
            'visual_profile' => 'corporate',
            'source' => 'portfolio_ayuntamiento-de-ribera-nueva.json',
        ],
    ];
}

function ccms_find_premium_pack(string $packId): ?array
{
    $packId = trim($packId);
    if ($packId === '') {
        return null;
    }
    foreach (ccms_premium_pack_catalog() as $pack) {
        if (($pack['id'] ?? '') === $packId) {
            return $pack;
        }
    }
    return null;
}

function ccms_list_premium_packs(?string $industry = null): array
{
    $industry = trim((string) $industry);
    $packs = [];
    foreach (ccms_premium_pack_catalog() as $pack) {
        if ($industry !== '' && ($pack['industry'] ?? '') !== $industry) {
            continue;
        }
        $file = ccms_premium_pack_dir() . DIRECTORY_SEPARATOR . (string) ($pack['source'] ?? '');
        if (!is_file($file)) {
            continue;
        }
        $packs[] = $pack + ['file' => $file];
    }
    return $packs;
}

function ccms_group_premium_packs_by_industry(): array
{
    $grouped = [];
    foreach (ccms_list_premium_packs() as $pack) {
        $industry = (string) ($pack['industry'] ?? 'generic');
        $label = (string) ($pack['industry_label'] ?? ucfirst($industry));
        if (!isset($grouped[$industry])) {
            $grouped[$industry] = [
                'label' => $label,
                'packs' => [],
            ];
        }
        $grouped[$industry]['packs'][] = $pack;
    }
    return $grouped;
}

function ccms_load_premium_pack(string $packId): ?array
{
    $pack = ccms_find_premium_pack($packId);
    if (!$pack) {
        return null;
    }
    $file = ccms_premium_pack_dir() . DIRECTORY_SEPARATOR . (string) ($pack['source'] ?? '');
    if (!is_file($file)) {
        return null;
    }
    $decoded = json_decode((string) file_get_contents($file), true);
    if (!is_array($decoded)) {
        return null;
    }
    $capsule = ccms_normalize_capsule($decoded);
    if (!ccms_capsule_can_render($capsule)) {
        return null;
    }
    return $pack + ['capsule' => $capsule];
}

function ccms_resolve_premium_pack_id(string $requestedPackId, string $industry, string $businessName = ''): string
{
    $requestedPackId = trim($requestedPackId);
    if ($requestedPackId !== '' && $requestedPackId !== 'auto' && ccms_find_premium_pack($requestedPackId)) {
        return $requestedPackId;
    }

    $candidates = ccms_list_premium_packs($industry);
    if ($candidates === []) {
        $candidates = ccms_list_premium_packs();
    }
    if ($candidates === []) {
        return '';
    }

    $seed = trim($businessName) !== '' ? $businessName : $industry;
    $index = abs(crc32($seed)) % count($candidates);
    return (string) ($candidates[$index]['id'] ?? '');
}
