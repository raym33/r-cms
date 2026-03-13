<?php
declare(strict_types=1);

require_once __DIR__ . '/src/bootstrap.php';

header('Content-Type: text/plain; charset=utf-8');
echo ccms_render_robots_txt();
