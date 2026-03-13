<?php
declare(strict_types=1);
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= ccms_h((string) ($businessTitle ?? 'Mi negocio')) ?></title>
  <link rel="stylesheet" href="<?= ccms_h(ccms_base_url() . '/mi-negocio/assets/business.css') ?>">
</head>
<body>
  <div class="business-shell">
    <?php if (!empty($flash) && is_array($flash)): ?>
      <div class="business-flash <?= ccms_h((string) ($flash['type'] ?? 'success')) ?>"><?= ccms_h((string) ($flash['message'] ?? '')) ?></div>
    <?php endif; ?>
    <?php if (($error ?? '') !== ''): ?>
      <div class="business-flash error"><?= ccms_h((string) $error) ?></div>
    <?php endif; ?>
    <?= $businessBody ?? '' ?>
  </div>
</body>
</html>
