<?php
declare(strict_types=1);

require_once __DIR__ . '/src/bootstrap.php';

header('Content-Type: application/xml; charset=utf-8');
echo ccms_render_sitemap_xml(ccms_load_data());
