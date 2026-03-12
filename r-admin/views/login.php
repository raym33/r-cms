<?php
declare(strict_types=1);
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>r-admin | LinuxCMS</title>
  <link rel="stylesheet" href="<?= htmlspecialchars(ccms_base_url() . '/r-admin/assets/admin.css', ENT_QUOTES, 'UTF-8') ?>">
</head>
<body>
  <div class="shell">
    <?php require __DIR__ . '/auth_shell.php'; ?>
  </div>
</body>
</html>
