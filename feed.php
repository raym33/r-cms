<?php
declare(strict_types=1);

require_once __DIR__ . '/src/bootstrap.php';

$data = ccms_load_data();
header('Content-Type: application/rss+xml; charset=utf-8');
echo ccms_render_blog_rss($data['site'], ccms_posts_published($data));
