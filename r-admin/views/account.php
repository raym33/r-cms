<div class="pages-layout">
  <aside class="stack">
    <div class="card sidebar-card">
      <h2>Tu cuenta</h2>
      <p class="small">Gestiona tu acceso al panel. Si acabas de recibir una contraseña temporal, cámbiala aquí primero.</p>
      <div class="small"><strong>Usuario:</strong> <?= ccms_h((string) ($currentAdmin['username'] ?? '')) ?></div>
      <div class="small"><strong>Email:</strong> <?= ccms_h((string) ($currentAdmin['email'] ?? '')) ?></div>
      <div class="small"><strong>Rol:</strong> <?= ccms_h((string) ($currentAdmin['role'] ?? '')) ?></div>
      <div class="small"><strong>Último acceso:</strong> <?= ccms_h((string) ($currentAdmin['last_login_at'] ?? 'Sin registrar')) ?></div>
      <div class="small"><strong>2FA:</strong> <?= !empty($currentAdmin['totp_enabled']) ? 'Activado' : 'Desactivado' ?></div>
    </div>
  </aside>
  <section class="workspace">
    <div class="card editor-card">
      <div class="editor-header">
        <div class="editor-title">
          <div class="chip">Cuenta</div>
          <h2>Cambiar contraseña</h2>
          <p class="muted" style="margin:0">Usa una contraseña fuerte. Si tu cuenta fue creada por otra persona, esta es la primera acción que deberías hacer.</p>
        </div>
      </div>
      <form method="post" class="stack" style="max-width:560px">
        <input type="hidden" name="action" value="change_own_password">
        <input type="hidden" name="csrf_token" value="<?= ccms_h($csrfToken) ?>">
        <div class="field"><label>Nueva contraseña</label><input name="new_password" type="password" required></div>
        <div class="field"><label>Repite la nueva contraseña</label><input name="confirm_new_password" type="password" required></div>
        <div class="toolbar">
          <button class="btn" type="submit">Guardar nueva contraseña</button>
        </div>
      </form>
    </div>
    <div class="card editor-card">
      <div class="editor-header">
        <div class="editor-title">
          <div class="chip">2FA</div>
          <h2>Autenticación en dos pasos</h2>
          <p class="muted" style="margin:0">Añade una capa extra de seguridad con una app tipo Google Authenticator, 1Password o Authy.</p>
        </div>
      </div>
      <?php if (!empty($currentAdmin['totp_enabled'])): ?>
        <div class="help-box">
          <h4>2FA activado</h4>
          <p class="small">Tu cuenta ya requiere un código adicional al iniciar sesión.</p>
        </div>
        <form method="post" class="stack" style="max-width:560px">
          <input type="hidden" name="action" value="disable_totp">
          <input type="hidden" name="csrf_token" value="<?= ccms_h($csrfToken) ?>">
          <div class="field"><label>Contraseña actual</label><input name="current_password" type="password" required></div>
          <div class="toolbar">
            <button class="btn btn-danger" type="submit">Desactivar 2FA</button>
          </div>
        </form>
      <?php else: ?>
        <?php if ($totpSetupSecret): ?>
          <div class="help-box">
            <h4>Configura tu app</h4>
            <p class="small">Añade esta clave manualmente en tu app de autenticación y escribe un código de 6 dígitos para confirmar.</p>
            <div class="small"><strong>Clave secreta:</strong> <code><?= ccms_h($totpSetupSecret) ?></code></div>
            <div class="small" style="margin-top:8px;word-break:break-all"><strong>URI:</strong> <code><?= ccms_h(ccms_totp_otpauth_uri($currentAdmin, $totpSetupSecret)) ?></code></div>
          </div>
          <form method="post" class="stack" style="max-width:560px">
            <input type="hidden" name="action" value="enable_totp">
            <input type="hidden" name="csrf_token" value="<?= ccms_h($csrfToken) ?>">
            <div class="field"><label>Código de verificación</label><input name="totp_code" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" required></div>
            <div class="toolbar">
              <button class="btn" type="submit">Activar 2FA</button>
            </div>
          </form>
          <form method="post" style="margin-top:10px">
            <input type="hidden" name="action" value="cancel_totp_setup">
            <input type="hidden" name="csrf_token" value="<?= ccms_h($csrfToken) ?>">
            <button class="btn btn-secondary" type="submit">Cancelar configuración</button>
          </form>
        <?php else: ?>
          <div class="help-box">
            <h4>2FA desactivado</h4>
            <p class="small">Actívalo para que el acceso al panel requiera tu contraseña y un código temporal de 6 dígitos.</p>
          </div>
          <form method="post">
            <input type="hidden" name="action" value="start_totp_setup">
            <input type="hidden" name="csrf_token" value="<?= ccms_h($csrfToken) ?>">
            <button class="btn" type="submit">Empezar configuración 2FA</button>
          </form>
        <?php endif; ?>
      <?php endif; ?>
    </div>
  </section>
</div>
