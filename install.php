<?php
declare(strict_types=1);

require_once __DIR__ . '/src/bootstrap.php';

if (ccms_is_installed()) {
    ccms_redirect('/r-admin/');
}

$error = '';
$storageInfo = ccms_storage_runtime_info();
$defaultInstallDriver = $storageInfo['sqlite_available'] ? 'sqlite' : 'json';
$submitted = [
    'site_title' => '',
    'tagline' => '',
    'admin_email' => '',
    'admin_username' => '',
    'storage_driver' => $defaultInstallDriver,
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $siteTitle = trim((string) ($_POST['site_title'] ?? ''));
        $tagline = trim((string) ($_POST['tagline'] ?? ''));
        $adminEmail = trim((string) ($_POST['admin_email'] ?? ''));
        $username = trim((string) ($_POST['admin_username'] ?? ''));
        $password = (string) ($_POST['admin_password'] ?? '');
        $confirm = (string) ($_POST['admin_password_confirm'] ?? '');
        $storageDriver = strtolower(trim((string) ($_POST['storage_driver'] ?? $defaultInstallDriver)));
        $submitted = [
            'site_title' => $siteTitle,
            'tagline' => $tagline,
            'admin_email' => $adminEmail,
            'admin_username' => $username,
            'storage_driver' => $storageDriver,
        ];

        if ($siteTitle === '' || $adminEmail === '' || $username === '' || $password === '') {
            throw new RuntimeException('Completa todos los campos obligatorios.');
        }
        if (!filter_var($adminEmail, FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException('El email del administrador no es válido.');
        }
        if ($password !== $confirm) {
            throw new RuntimeException('Las contraseñas no coinciden.');
        }
        if (strlen($password) < 10) {
            throw new RuntimeException('La contraseña debe tener al menos 10 caracteres.');
        }
        if (!in_array($storageDriver, ['json', 'sqlite'], true)) {
            throw new RuntimeException('Selecciona un tipo de almacenamiento válido.');
        }
        if ($storageDriver === 'sqlite' && !$storageInfo['sqlite_available']) {
            throw new RuntimeException('Este PHP no tiene SQLite disponible. Usa JSON o activa pdo_sqlite.');
        }

        $homepageId = ccms_next_id('page');
        $data = ccms_default_data();
        $data['installed_at'] = ccms_now_iso();
        $data['site']['title'] = $siteTitle;
        $data['site']['tagline'] = $tagline !== '' ? $tagline : 'Edita tu web como WordPress, pero con cápsulas.';
        $data['site']['contact_email'] = $adminEmail;
        $data['admin'] = [
            'id' => ccms_next_id('admin'),
            'username' => $username,
            'email' => $adminEmail,
            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            'created_at' => ccms_now_iso(),
        ];
        $data['users'] = [[
            'id' => $data['admin']['id'],
            'username' => $username,
            'email' => $adminEmail,
            'password_hash' => $data['admin']['password_hash'],
            'role' => 'owner',
            'created_at' => $data['admin']['created_at'],
            'updated_at' => ccms_now_iso(),
        ]];
        $data['pages'][] = [
            'id' => $homepageId,
            'title' => 'Inicio',
            'slug' => 'inicio',
            'status' => 'published',
            'is_homepage' => true,
            'show_in_menu' => true,
            'menu_label' => 'Inicio',
            'meta_title' => $siteTitle,
            'meta_description' => $data['site']['tagline'],
            'capsule_json' => "{\n  \"meta\": {\n    \"business_name\": \"" . addslashes($siteTitle) . "\"\n  },\n  \"blocks\": []\n}",
            'html_content' => '<section style="padding:72px 32px;text-align:center"><p style="display:inline-block;padding:8px 12px;border-radius:999px;background:#f1ece4;font-weight:700;margin:0 0 14px">Starter page</p><h1 style="font-size:48px;line-height:1.05;margin:0 0 14px">' . ccms_h($siteTitle) . '</h1><p style="max-width:720px;margin:0 auto;color:#6b5b53;font-size:18px;line-height:1.7">' . ccms_h($data['site']['tagline']) . '</p><div style="margin-top:24px"><a href="/r-admin/" style="display:inline-flex;padding:14px 20px;border-radius:999px;background:#c86f5c;color:#fff;font-weight:700;text-decoration:none">Open admin</a></div></section>',
            'created_at' => ccms_now_iso(),
            'updated_at' => ccms_now_iso(),
        ];
        ccms_set_storage_driver($storageDriver);
        ccms_save_data($data);
        ccms_flash('success', 'Instalación completada. Ya puedes entrar al panel.');
        ccms_redirect('/r-admin/');
    } catch (Throwable $e) {
        $error = $e->getMessage();
    }
}
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Instalar LinuxCMS</title>
  <style>
    body{margin:0;background:#f5f0e8;color:#3b2d28;font-family:Arial,Helvetica,sans-serif}
    .shell{width:min(860px,calc(100% - 24px));margin:0 auto;padding:28px 0 48px}
    .card{background:#fff;border-radius:24px;padding:28px;box-shadow:0 24px 50px -30px rgba(0,0,0,.25)}
    h1{font-size:42px;line-height:1.04;margin:12px 0 14px}
    h2{font-size:24px;margin:0 0 16px}
    p{color:#6b5b53;line-height:1.6}
    .grid{display:grid;gap:16px;grid-template-columns:1fr 1fr}
    label{display:block;font-size:14px;font-weight:700;margin-bottom:8px}
    input{width:100%;min-height:48px;padding:12px 14px;border:1px solid #ddcfc4;border-radius:14px;font:inherit}
    .btn{display:inline-flex;align-items:center;justify-content:center;padding:14px 20px;border-radius:999px;border:0;background:linear-gradient(135deg,#c86f5c 0%,#d9c4b3 100%);color:#fff;font-weight:700;cursor:pointer}
    .error{background:#fde9e8;color:#8d4038;border-radius:14px;padding:14px 16px;margin-bottom:16px}
    .muted{font-size:14px;color:#6b5b53}
    @media (max-width:700px){.grid{grid-template-columns:1fr}}
  </style>
</head>
<body>
  <div class="shell">
    <div class="card">
      <p><strong>LinuxCMS</strong></p>
      <h1>Instalación rápida</h1>
      <p>Este proyecto está pensado para hosting genérico. No necesita Node ni Python para funcionar online. Solo PHP y permisos de escritura en las carpetas <code>data/</code> y <code>uploads/</code>.</p>
      <?php if ($error !== ''): ?>
        <div class="error"><?= ccms_h($error) ?></div>
      <?php endif; ?>
      <form method="post">
        <div class="grid">
          <div>
            <label for="site_title">Nombre del sitio</label>
            <input id="site_title" name="site_title" value="<?= ccms_h($submitted['site_title']) ?>" required>
          </div>
          <div>
            <label for="tagline">Subtítulo</label>
            <input id="tagline" name="tagline" value="<?= ccms_h($submitted['tagline']) ?>" placeholder="Tu mensaje principal">
          </div>
          <div>
            <label for="admin_email">Email del administrador</label>
            <input id="admin_email" name="admin_email" type="email" value="<?= ccms_h($submitted['admin_email']) ?>" required>
          </div>
          <div>
            <label for="admin_username">Usuario</label>
            <input id="admin_username" name="admin_username" value="<?= ccms_h($submitted['admin_username']) ?>" required>
          </div>
          <div>
            <label for="admin_password">Contraseña</label>
            <input id="admin_password" name="admin_password" type="password" required>
          </div>
          <div>
            <label for="admin_password_confirm">Repite la contraseña</label>
            <input id="admin_password_confirm" name="admin_password_confirm" type="password" required>
          </div>
          <div>
            <label for="storage_driver">Almacenamiento</label>
            <select id="storage_driver" name="storage_driver" style="width:100%;min-height:48px;padding:12px 14px;border:1px solid #ddcfc4;border-radius:14px;font:inherit;background:#fff">
              <option value="sqlite" <?= $submitted['storage_driver'] === 'sqlite' ? 'selected' : '' ?> <?= !$storageInfo['sqlite_available'] ? 'disabled' : '' ?>>SQLite (recomendado)</option>
              <option value="json" <?= $submitted['storage_driver'] === 'json' ? 'selected' : '' ?>>JSON</option>
            </select>
          </div>
        </div>
        <div style="margin-top:20px">
          <button class="btn" type="submit">Instalar y abrir el admin</button>
        </div>
      </form>
      <p class="muted" style="margin-top:18px">Después podrás entrar al panel en <code>/r-admin</code>.</p>
      <p class="muted">Estado actual de este PHP: SQLite <?= $storageInfo['sqlite_available'] ? 'disponible' : 'no disponible' ?>.</p>
    </div>
  </div>
</body>
</html>
