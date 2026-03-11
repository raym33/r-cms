<?php
declare(strict_types=1);

require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/storage.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/render.php';
require_once __DIR__ . '/ai.php';

ccms_start_session();
ccms_ensure_runtime_dirs();
