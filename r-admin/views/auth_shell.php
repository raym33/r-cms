<div class="card" style="max-width:520px;margin:40px auto;padding:26px">
  <?php if (($adminBrand['logo_url'] ?? '') !== ''): ?>
    <img class="auth-brand-logo" src="<?= ccms_h((string) $adminBrand['logo_url']) ?>" alt="<?= ccms_h((string) $adminBrand['name']) ?>">
  <?php endif; ?>
  <p class="muted"><strong><?= ccms_h((string) ($adminBrand['name'] ?? 'LinuxCMS')) ?></strong></p>
  <h1 style="margin:0 0 12px;font-size:42px;line-height:1"><?= $pendingTwoFactor ? 'Verificación en dos pasos' : ($resetTokenEntry ? 'Restablecer contraseña' : 'Entrar al panel') ?></h1>
  <p class="muted">
    <?php if ($pendingTwoFactor): ?>
      Introduce el código de 6 dígitos de tu app de autenticación para completar el acceso.
    <?php elseif ($resetTokenEntry): ?>
      Elige una nueva contraseña segura para recuperar esta cuenta.
    <?php else: ?>
      <?= ccms_h((string) ($adminBrand['tagline'] ?? 'Usa tu usuario y contraseña para editar páginas, colores, medios y contenido publicado.')) ?>
    <?php endif; ?>
  </p>
  <?php if ($flash): ?><div class="flash <?= ccms_h($flash['type']) ?>"><?= ccms_h($flash['message']) ?></div><?php endif; ?>
  <?php if ($error !== ''): ?><div class="flash error"><?= ccms_h($error) ?></div><?php endif; ?>
  <?php if ($pendingTwoFactor): ?>
    <form method="post">
      <input type="hidden" name="action" value="verify_2fa">
      <input type="hidden" name="csrf_token" value="<?= ccms_h(ccms_csrf_token()) ?>">
      <div class="field">
        <label for="totp_code">Código 2FA</label>
        <input id="totp_code" name="totp_code" inputmode="numeric" autocomplete="one-time-code" pattern="[0-9]{6}" maxlength="6" required>
      </div>
      <button class="btn" type="submit">Validar y entrar</button>
    </form>
  <?php elseif ($resetTokenValue !== ''): ?>
    <?php if ($resetTokenEntry): ?>
      <form method="post">
        <input type="hidden" name="action" value="complete_password_reset">
        <input type="hidden" name="csrf_token" value="<?= ccms_h(ccms_csrf_token()) ?>">
        <input type="hidden" name="reset_token" value="<?= ccms_h($resetTokenValue) ?>">
        <div class="field">
          <label for="new_password">Nueva contraseña</label>
          <input id="new_password" name="new_password" type="password" minlength="10" required>
        </div>
        <div class="field">
          <label for="confirm_new_password">Repite la nueva contraseña</label>
          <input id="confirm_new_password" name="confirm_new_password" type="password" minlength="10" required>
        </div>
        <button class="btn" type="submit">Guardar contraseña</button>
      </form>
    <?php else: ?>
      <div class="flash error">El enlace de recuperación no es válido o ha caducado.</div>
      <a class="btn btn-secondary" href="/r-admin/">Volver al acceso</a>
    <?php endif; ?>
  <?php else: ?>
    <form method="post">
      <input type="hidden" name="action" value="login">
      <div class="field">
        <label for="username">Usuario</label>
        <input id="username" name="username" required>
      </div>
      <div class="field">
        <label for="password">Contraseña</label>
        <input id="password" name="password" type="password" required>
      </div>
      <button class="btn" type="submit">Entrar</button>
    </form>
  <?php endif; ?>
</div>
