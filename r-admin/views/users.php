<div class="pages-layout">
  <aside class="stack">
    <div class="card sidebar-card">
      <h2>Nuevo usuario</h2>
      <form method="post" class="stack">
        <input type="hidden" name="action" value="create_user">
        <input type="hidden" name="csrf_token" value="<?= ccms_h($csrfToken) ?>">
        <div class="field"><label>Usuario</label><input name="user_username" required></div>
        <div class="field"><label>Email</label><input name="user_email" type="email" required></div>
        <div class="field"><label>Contraseña temporal</label><input name="user_password" type="password" required></div>
        <div class="field">
          <label>Rol</label>
          <select name="user_role">
            <option value="editor">Editor</option>
            <option value="viewer">Viewer</option>
            <option value="owner">Owner</option>
          </select>
        </div>
        <p class="small">Los usuarios nuevos deberán cambiar esta contraseña al entrar por primera vez.</p>
        <button class="btn" type="submit">Crear usuario</button>
      </form>
    </div>
    <div class="help-box">
      <h4>Roles</h4>
      <ul>
        <li><strong>Owner</strong>: controla sitio, usuarios, páginas, media e importación.</li>
        <li><strong>Editor</strong>: puede editar páginas, media e importar cápsulas.</li>
        <li><strong>Viewer</strong>: solo lectura del contenido y la vista previa.</li>
      </ul>
    </div>
  </aside>
  <section class="workspace">
    <div class="card editor-card">
      <div class="editor-header">
        <div class="editor-title">
          <div class="chip">Usuarios</div>
          <h2>Acceso al CMS</h2>
          <p class="muted" style="margin:0">Gestiona quién puede entrar al panel y qué puede hacer cada persona.</p>
        </div>
      </div>
      <div class="stack">
        <?php foreach (($data['users'] ?? []) as $user): ?>
          <div class="help-box" style="background:#fff">
            <form method="post" class="stack">
              <input type="hidden" name="action" value="update_user">
              <input type="hidden" name="csrf_token" value="<?= ccms_h($csrfToken) ?>">
              <input type="hidden" name="user_id" value="<?= ccms_h((string) ($user['id'] ?? '')) ?>">
              <div class="split-3">
                <div class="field"><label>Usuario</label><input name="user_username" value="<?= ccms_h((string) ($user['username'] ?? '')) ?>" required></div>
                <div class="field"><label>Email</label><input name="user_email" type="email" value="<?= ccms_h((string) ($user['email'] ?? '')) ?>" required></div>
                <div class="field">
                  <label>Rol</label>
                  <select name="user_role">
                    <?php foreach (['owner' => 'Owner', 'editor' => 'Editor', 'viewer' => 'Viewer'] as $roleKey => $roleLabel): ?>
                      <option value="<?= ccms_h($roleKey) ?>" <?= (($user['role'] ?? '') === $roleKey) ? 'selected' : '' ?>><?= ccms_h($roleLabel) ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
              </div>
              <div class="split-2">
                <div class="field"><label>Nueva contraseña (opcional)</label><input name="user_password" type="password" placeholder="Déjalo vacío para mantenerla"></div>
                <div class="field"><label>Creado</label><input value="<?= ccms_h((string) ($user['created_at'] ?? '')) ?>" disabled></div>
              </div>
              <div class="small">
                Último acceso: <?= ccms_h((string) (($user['last_login_at'] ?? null) ?: 'Sin registrar')) ?>
                · Cambio obligatorio de contraseña: <?= !empty($user['must_change_password']) ? 'Sí' : 'No' ?>
                · 2FA: <?= !empty($user['totp_enabled']) ? 'Sí' : 'No' ?>
              </div>
              <div class="toolbar">
                <button class="btn" type="submit">Guardar usuario</button>
              </div>
            </form>
            <?php if (($user['id'] ?? '') !== ($currentAdmin['id'] ?? '')): ?>
              <form method="post" style="margin-top:10px">
                <input type="hidden" name="action" value="create_password_reset_token">
                <input type="hidden" name="csrf_token" value="<?= ccms_h($csrfToken) ?>">
                <input type="hidden" name="user_id" value="<?= ccms_h((string) ($user['id'] ?? '')) ?>">
                <button class="btn btn-secondary" type="submit">Generar enlace de recuperación</button>
              </form>
            <?php endif; ?>
            <?php if (($user['id'] ?? '') !== ($currentAdmin['id'] ?? '')): ?>
              <form method="post" style="margin-top:10px" onsubmit="return confirm('¿Seguro que quieres eliminar este usuario?');">
                <input type="hidden" name="action" value="delete_user">
                <input type="hidden" name="csrf_token" value="<?= ccms_h($csrfToken) ?>">
                <input type="hidden" name="user_id" value="<?= ccms_h((string) ($user['id'] ?? '')) ?>">
                <button class="btn btn-danger" type="submit">Eliminar usuario</button>
              </form>
            <?php endif; ?>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </section>
</div>
