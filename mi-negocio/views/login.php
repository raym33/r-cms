<?php
declare(strict_types=1);
$adminBrand = ccms_admin_branding($data['site'] ?? []);
?>
<div class="business-login">
  <div class="business-panel business-stack">
    <div class="business-brand">
      <span class="business-chip">Modo negocio</span>
      <h1><?= ccms_h((string) ($data['site']['title'] ?? 'Mi negocio')) ?></h1>
      <p>Entra para actualizar menu, precios, horarios y textos clave desde el movil.</p>
    </div>
    <form method="post" class="business-form">
      <input type="hidden" name="action" value="business_login">
      <div class="business-field">
        <label>Usuario o email</label>
        <input name="username" autocomplete="username" required>
      </div>
      <div class="business-field">
        <label>Contrasena</label>
        <input name="password" type="password" autocomplete="current-password" required>
      </div>
      <button class="business-btn" type="submit">Entrar</button>
    </form>
    <p class="business-header-note">Si necesitas editar diseno, secciones o configuracion avanzada, entra en <a href="/r-admin/"><strong><?= ccms_h((string) ($adminBrand['admin_title'] ?? 'r-admin')) ?></strong></a>.</p>
  </div>
</div>
