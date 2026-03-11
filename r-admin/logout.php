<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/src/bootstrap.php';

ccms_logout();
ccms_flash('success', 'Sesión cerrada.');
ccms_redirect('/r-admin/');

