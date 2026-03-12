<div class="site-layout">
  <div class="card editor-card">
    <div class="editor-header">
      <div class="editor-title">
        <div class="chip">Plugins</div>
        <h2>Extensiones ligeras del sitio</h2>
        <p class="muted" style="margin:0">Activa o desactiva plugins sencillos para añadir comportamiento o HTML adicional sin tocar el núcleo.</p>
      </div>
    </div>
    <form method="post" class="stack">
      <input type="hidden" name="action" value="save_plugins">
      <input type="hidden" name="csrf_token" value="<?= ccms_h($csrfToken) ?>">
      <label class="check">
        <input type="checkbox" name="trusted_plugins_enabled" value="1" <?= !empty($data['site']['trusted_plugins_enabled']) ? 'checked' : '' ?>>
        <span>
          <strong>Permitir trusted plugins PHP</strong>
          <span class="small" style="display:block;margin-top:4px">Déjalo apagado salvo que confíes en el código del plugin y en su integridad.</span>
        </span>
      </label>
      <div class="check-grid">
        <?php if ($availablePlugins === []): ?>
          <div class="help-box">
            <h4 style="margin:0 0 8px">No hay plugins instalados</h4>
            <p class="small" style="margin:0">Añade carpetas dentro de <code>plugins/</code> con un <code>manifest.json</code> y un <code>plugin.php</code> para ampliar el sitio.</p>
          </div>
        <?php else: ?>
          <?php foreach ($availablePlugins as $pluginSlug => $pluginManifest): ?>
            <?php $isEnabled = in_array($pluginSlug, array_map('strval', is_array($data['site']['enabled_plugins'] ?? null) ? $data['site']['enabled_plugins'] : []), true); ?>
            <label class="check">
              <input type="checkbox" name="enabled_plugins[]" value="<?= ccms_h($pluginSlug) ?>" <?= $isEnabled ? 'checked' : '' ?> <?= empty($data['site']['trusted_plugins_enabled']) || empty($pluginManifest['loadable']) ? 'disabled' : '' ?>>
              <span>
                <strong><?= ccms_h((string) ($pluginManifest['name'] ?? $pluginSlug)) ?></strong>
                <span class="small" style="display:block;margin-top:4px">
                  v<?= ccms_h((string) ($pluginManifest['version'] ?? '1.0.0')) ?> · <?= ccms_h((string) ($pluginManifest['description'] ?? 'No description')) ?>
                  <?php if (empty($pluginManifest['trusted'])): ?> · not trusted<?php endif; ?>
                  <?php if (!empty($pluginManifest['trusted']) && empty($pluginManifest['integrity_ok'])): ?> · integrity failed<?php endif; ?>
                </span>
              </span>
            </label>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
      <div class="toolbar">
        <button class="btn" type="submit">Guardar extensiones</button>
      </div>
    </form>
  </div>
  <div class="help-box">
    <h4>Cómo usar esta sección</h4>
    <ul>
      <li>Piensa en esto como una primera capa de plugins tipo WordPress, pero más ligera.</li>
      <li>Los plugins PHP están desactivados por defecto y son explícitamente <strong>trusted</strong>.</li>
      <li>Solo se cargan si activas el modo trusted y si el hash del <code>plugin.php</code> coincide con el manifiesto.</li>
      <li>Los fragmentos HTML/CSS que insertan en la web pública se sanean antes de renderizarse.</li>
      <li>Empieza activando solo una extensión cada vez para validar el efecto visual.</li>
    </ul>
  </div>
</div>
