<?php
declare(strict_types=1);
?>
<div class="business-login">
  <div class="business-panel business-stack">
    <div class="business-brand">
      <span class="business-chip">Primer acceso</span>
      <h1>Cambia tu contrasena temporal</h1>
      <p>Antes de editar el contenido, define una contrasena nueva para esta cuenta.</p>
    </div>
    <form method="post" class="business-form">
      <input type="hidden" name="action" value="business_change_password">
      <input type="hidden" name="csrf_token" value="<?= ccms_h($csrfToken) ?>">
      <div class="business-field">
        <label>Nueva contrasena</label>
        <input name="new_password" type="password" autocomplete="new-password" required>
      </div>
      <div class="business-field">
        <label>Repite la contrasena</label>
        <input name="confirm_new_password" type="password" autocomplete="new-password" required>
      </div>
      <button class="business-btn" type="submit">Guardar contrasena</button>
    </form>
  </div>
</div>
