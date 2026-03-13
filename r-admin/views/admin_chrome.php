<header class="topbar">
  <div class="admin-brand-block">
    <?php if (($adminBrand['logo_url'] ?? '') !== ''): ?>
      <img class="admin-brand-logo" src="<?= ccms_h((string) $adminBrand['logo_url']) ?>" alt="<?= ccms_h((string) $adminBrand['name']) ?>">
    <?php endif; ?>
    <div class="admin-brand-copy">
      <h1><?= ccms_h((string) ($adminBrand['admin_title'] ?? 'r-admin')) ?></h1>
      <p class="muted" style="margin:8px 0 0">Bienvenido, <?= ccms_h($currentAdmin['username']) ?>. <?= ccms_h((string) ($adminBrand['tagline'] ?? '')) ?></p>
    </div>
    <div class="editor-meta" style="margin-top:10px">
      <span class="chip">Rol · <?= ccms_h(strtoupper((string) ($currentAdmin['role'] ?? 'OWNER'))) ?></span>
      <?php if ($canManageUsers): ?><span class="chip">Acceso completo</span><?php elseif ($canManagePages): ?><span class="chip">Editor</span><?php else: ?><span class="chip">Solo lectura</span><?php endif; ?>
    </div>
  </div>
  <div class="toolbar">
    <a class="btn btn-secondary" href="/"><?= ccms_icon('globe', 16) ?>Abrir web</a>
    <?php if (ccms_user_can('business_mode')): ?><a class="btn btn-secondary" href="/mi-negocio/"><?= ccms_icon('eye', 16) ?>Modo negocio</a><?php endif; ?>
    <button class="btn btn-secondary" type="button" id="clientModeToggle" aria-pressed="false"><?= ccms_icon('eye', 16) ?>Modo cliente</button>
    <?php if ($canGenerateAi): ?><a class="btn btn-secondary advanced-only" href="?tab=studio"><?= ccms_icon('sparkles', 16) ?>Studio local</a><?php endif; ?>
    <?php if ($canViewInbox): ?><a class="btn btn-secondary" href="?tab=inbox"><?= ccms_icon('mail', 16) ?>Inbox</a><?php endif; ?>
    <?php if ($canManageSite): ?><a class="btn btn-secondary advanced-only" href="?tab=extensions"><?= ccms_icon('puzzle', 16) ?>Extensiones</a><?php endif; ?>
    <?php if ($canManageBackups): ?><a class="btn btn-secondary advanced-only" href="?tab=backups"><?= ccms_icon('archive', 16) ?>Backups</a><?php endif; ?>
    <?php if ($canManageMedia): ?><a class="btn btn-secondary" href="?tab=media"><?= ccms_icon('image', 16) ?>Media</a><?php endif; ?>
    <?php if ($canImportCapsules): ?><a class="btn btn-secondary advanced-only" href="?tab=import"><?= ccms_icon('upload', 16) ?>Importar</a><?php endif; ?>
    <?php if ($canManageUsers): ?><a class="btn btn-secondary advanced-only" href="?tab=users"><?= ccms_icon('users', 16) ?>Usuarios</a><?php endif; ?>
    <a class="btn btn-secondary" href="/r-admin/logout.php"><?= ccms_icon('log-out', 16) ?>Salir</a>
  </div>
</header>

<?php if ($flash): ?><div class="flash <?= ccms_h($flash['type']) ?>"><?= ccms_h($flash['message']) ?></div><?php endif; ?>
<?php if ($error !== ''): ?><div class="flash error"><?= ccms_h($error) ?></div><?php endif; ?>
<div class="client-mode-banner" id="clientModeBanner">
  <div>
    <strong>Modo cliente activado</strong>
    <div class="small">Se muestran solo las acciones clave para editar textos, fotos, colores y publicar. Si necesitas todo el panel, cambia a <strong>Modo avanzado</strong>.</div>
  </div>
  <button class="btn btn-secondary" type="button" id="clientModeBannerToggle"><?= ccms_icon('settings', 16) ?>Cambiar a modo avanzado</button>
</div>

<nav class="nav-tabs">
  <?php if ($canGenerateAi): ?><a class="advanced-only <?= $tab === 'studio' ? 'active' : '' ?>" href="/r-admin/?tab=studio"><?= ccms_icon('sparkles', 16) ?>Studio</a><?php endif; ?>
  <a class="<?= $tab === 'pages' ? 'active' : '' ?>" href="/r-admin/?tab=pages"><?= ccms_icon('file-text', 16) ?>Páginas</a>
  <?php if ($canManagePosts): ?><a class="<?= $tab === 'posts' ? 'active' : '' ?>" href="/r-admin/?tab=posts"><?= ccms_icon('file-text', 16) ?>Posts</a><?php endif; ?>
  <?php if ($canViewInbox): ?><a class="<?= $tab === 'inbox' ? 'active' : '' ?>" href="/r-admin/?tab=inbox"><?= ccms_icon('mail', 16) ?>Inbox</a><?php endif; ?>
  <a class="<?= $tab === 'account' ? 'active' : '' ?>" href="/r-admin/?tab=account"><?= ccms_icon('user-circle', 16) ?>Cuenta</a>
  <?php if ($canManageSite): ?><a class="<?= $tab === 'site' ? 'active' : '' ?>" href="/r-admin/?tab=site"><?= ccms_icon('settings', 16) ?>Sitio</a><?php endif; ?>
  <?php if ($canManageSite): ?><a class="advanced-only <?= $tab === 'extensions' ? 'active' : '' ?>" href="/r-admin/?tab=extensions"><?= ccms_icon('puzzle', 16) ?>Extensiones</a><?php endif; ?>
  <?php if ($canManageBackups): ?><a class="advanced-only <?= $tab === 'backups' ? 'active' : '' ?>" href="/r-admin/?tab=backups"><?= ccms_icon('archive', 16) ?>Backups</a><?php endif; ?>
  <?php if ($canManageMedia): ?><a class="<?= $tab === 'media' ? 'active' : '' ?>" href="/r-admin/?tab=media"><?= ccms_icon('image', 16) ?>Media</a><?php endif; ?>
  <?php if ($canImportCapsules): ?><a class="advanced-only <?= $tab === 'import' ? 'active' : '' ?>" href="/r-admin/?tab=import"><?= ccms_icon('upload', 16) ?>Importar cápsula</a><?php endif; ?>
  <?php if ($canManageUsers): ?><a class="advanced-only <?= $tab === 'users' ? 'active' : '' ?>" href="/r-admin/?tab=users"><?= ccms_icon('users', 16) ?>Usuarios</a><?php endif; ?>
  <?php if ($canViewAudit): ?><a class="advanced-only <?= $tab === 'audit' ? 'active' : '' ?>" href="/r-admin/?tab=audit"><?= ccms_icon('shield', 16) ?>Auditoría</a><?php endif; ?>
</nav>

<?php if ($mustChangePassword): ?>
  <div class="flash error">Tu cuenta usa una contraseña temporal. Debes cambiarla ahora antes de editar páginas o configuración.</div>
<?php endif; ?>

<div class="toast-container" id="toastContainer" aria-live="polite" aria-atomic="true"></div>

<div class="confirm-modal-backdrop" id="confirmModalBackdrop" hidden>
  <div class="confirm-modal" role="dialog" aria-modal="true" aria-labelledby="confirmModalTitle" aria-describedby="confirmModalMessage">
    <div class="confirm-modal-icon" aria-hidden="true"><?= ccms_icon('alert-triangle', 22) ?></div>
    <div class="confirm-modal-copy">
      <h3 id="confirmModalTitle">¿Confirmar acción?</h3>
      <p id="confirmModalMessage">Esta acción no se puede deshacer.</p>
    </div>
    <div class="confirm-modal-actions">
      <button class="btn btn-secondary" type="button" id="confirmModalCancel">Cancelar</button>
      <button class="btn btn-danger" type="button" id="confirmModalConfirm"><?= ccms_icon('trash-2', 16) ?>Confirmar</button>
    </div>
  </div>
</div>
