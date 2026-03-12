<header class="topbar">
  <div>
    <h1>r-admin</h1>
    <p class="muted" style="margin:8px 0 0">Bienvenido, <?= ccms_h($currentAdmin['username']) ?>. Este panel ya se comporta más como un pequeño WordPress para hosting genérico.</p>
    <div class="editor-meta" style="margin-top:10px">
      <span class="chip">Rol · <?= ccms_h(strtoupper((string) ($currentAdmin['role'] ?? 'OWNER'))) ?></span>
      <?php if ($canManageUsers): ?><span class="chip">Acceso completo</span><?php elseif ($canManagePages): ?><span class="chip">Editor</span><?php else: ?><span class="chip">Solo lectura</span><?php endif; ?>
    </div>
  </div>
  <div class="toolbar">
    <a class="btn btn-secondary" href="/">Abrir web</a>
    <button class="btn btn-secondary" type="button" id="clientModeToggle" aria-pressed="false">Modo cliente</button>
    <?php if ($canGenerateAi): ?><a class="btn btn-secondary advanced-only" href="?tab=studio">Studio local</a><?php endif; ?>
    <?php if ($canManageSite): ?><a class="btn btn-secondary advanced-only" href="?tab=extensions">Extensiones</a><?php endif; ?>
    <?php if ($canManageBackups): ?><a class="btn btn-secondary advanced-only" href="?tab=backups">Backups</a><?php endif; ?>
    <?php if ($canManageMedia): ?><a class="btn btn-secondary" href="?tab=media">Media</a><?php endif; ?>
    <?php if ($canImportCapsules): ?><a class="btn btn-secondary advanced-only" href="?tab=import">Importar</a><?php endif; ?>
    <?php if ($canManageUsers): ?><a class="btn btn-secondary advanced-only" href="?tab=users">Usuarios</a><?php endif; ?>
    <a class="btn btn-secondary" href="/r-admin/logout.php">Salir</a>
  </div>
</header>

<?php if ($flash): ?><div class="flash <?= ccms_h($flash['type']) ?>"><?= ccms_h($flash['message']) ?></div><?php endif; ?>
<?php if ($error !== ''): ?><div class="flash error"><?= ccms_h($error) ?></div><?php endif; ?>
<div class="client-mode-banner" id="clientModeBanner">
  <div>
    <strong>Modo cliente activado</strong>
    <div class="small">Se muestran solo las acciones clave para editar textos, fotos, colores y publicar. Si necesitas todo el panel, cambia a <strong>Modo avanzado</strong>.</div>
  </div>
  <button class="btn btn-secondary" type="button" id="clientModeBannerToggle">Cambiar a modo avanzado</button>
</div>

<nav class="nav-tabs">
  <?php if ($canGenerateAi): ?><a class="advanced-only <?= $tab === 'studio' ? 'active' : '' ?>" href="/r-admin/?tab=studio"><span class="icon-dot"></span>Studio</a><?php endif; ?>
  <a class="<?= $tab === 'pages' ? 'active' : '' ?>" href="/r-admin/?tab=pages"><span class="icon-dot"></span>Páginas</a>
  <a class="<?= $tab === 'account' ? 'active' : '' ?>" href="/r-admin/?tab=account"><span class="icon-dot"></span>Cuenta</a>
  <?php if ($canManageSite): ?><a class="<?= $tab === 'site' ? 'active' : '' ?>" href="/r-admin/?tab=site"><span class="icon-dot"></span>Sitio</a><?php endif; ?>
  <?php if ($canManageSite): ?><a class="advanced-only <?= $tab === 'extensions' ? 'active' : '' ?>" href="/r-admin/?tab=extensions"><span class="icon-dot"></span>Extensiones</a><?php endif; ?>
  <?php if ($canManageBackups): ?><a class="advanced-only <?= $tab === 'backups' ? 'active' : '' ?>" href="/r-admin/?tab=backups"><span class="icon-dot"></span>Backups</a><?php endif; ?>
  <?php if ($canManageMedia): ?><a class="<?= $tab === 'media' ? 'active' : '' ?>" href="/r-admin/?tab=media"><span class="icon-dot"></span>Media</a><?php endif; ?>
  <?php if ($canImportCapsules): ?><a class="advanced-only <?= $tab === 'import' ? 'active' : '' ?>" href="/r-admin/?tab=import"><span class="icon-dot"></span>Importar cápsula</a><?php endif; ?>
  <?php if ($canManageUsers): ?><a class="advanced-only <?= $tab === 'users' ? 'active' : '' ?>" href="/r-admin/?tab=users"><span class="icon-dot"></span>Usuarios</a><?php endif; ?>
  <?php if ($canViewAudit): ?><a class="advanced-only <?= $tab === 'audit' ? 'active' : '' ?>" href="/r-admin/?tab=audit"><span class="icon-dot"></span>Auditoría</a><?php endif; ?>
</nav>

<?php if ($mustChangePassword): ?>
  <div class="flash error">Tu cuenta usa una contraseña temporal. Debes cambiarla ahora antes de editar páginas o configuración.</div>
<?php endif; ?>
