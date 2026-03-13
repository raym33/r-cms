<?php
declare(strict_types=1);
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= ccms_h((string) ($adminBrand['page_title'] ?? 'r-admin | LinuxCMS')) ?></title>
  <link rel="stylesheet" href="<?= htmlspecialchars(ccms_base_url() . '/r-admin/assets/admin.css', ENT_QUOTES, 'UTF-8') ?>">
</head>
<body>
  <div class="shell">
    <?php require __DIR__ . '/admin_chrome.php'; ?>
    <?php require __DIR__ . '/admin_tabs.php'; ?>
  </div>

  <script<?= ccms_script_nonce_attr() ?>>
    window.CCMS_ADMIN_BOOTSTRAP = {
      sectionTemplates: <?= $sectionTemplatesJson ?: '[]' ?>,
      capsuleBuilderTemplates: <?= $capsuleBuilderTemplatesJson ?: '[]' ?>,
      initialCapsuleState: <?= $selectedCapsuleStateJson ?: '{"meta":{},"style":{},"blocks":[]}' ?>,
      mediaItems: <?= $mediaItemsJson ?: '[]' ?>,
      previewSiteConfig: <?= $previewSiteConfigJson ?: '{"site":{},"menu":[]}' ?>,
      builderReadOnly: <?= $builderReadOnly ? 'true' : 'false' ?>,
      cspNonce: <?= json_encode(ccms_csp_nonce(), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>
    };
  </script>
  <script src="<?= htmlspecialchars(ccms_base_url() . '/r-admin/assets/admin.js', ENT_QUOTES, 'UTF-8') ?>"></script>
</body>
</html>
