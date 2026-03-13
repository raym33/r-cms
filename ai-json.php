<?php
declare(strict_types=1);

require_once __DIR__ . '/src/bootstrap.php';

$data = ccms_load_data();
$site = ccms_public_site_config($data);

header('Content-Type: application/json; charset=utf-8');

if (!ccms_business_profile_feed_enabled($site)) {
    http_response_code(404);
    echo json_encode([
        'error' => 'Business profile AI feed is not enabled.',
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    return;
}

echo ccms_render_ai_well_known($data);
