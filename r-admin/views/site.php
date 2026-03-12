<div class="site-layout">
  <div class="card editor-card">
    <div class="editor-header">
      <div class="editor-title">
        <div class="chip">Branding · sitio</div>
        <h2>Configuración general del sitio</h2>
        <p class="muted" style="margin:0">Cambia título, mensaje general, colores y datos de contacto. Esto afecta al encabezado y al wrapper de todas las páginas.</p>
      </div>
    </div>
    <form method="post" class="stack">
      <input type="hidden" name="action" value="save_site">
      <input type="hidden" name="csrf_token" value="<?= ccms_h($csrfToken) ?>">
      <div class="split-2">
        <div class="field"><label>Título del sitio</label><input name="site_title" value="<?= ccms_h((string) $data['site']['title']) ?>"></div>
        <div class="field"><label>Email de contacto</label><input name="contact_email" type="email" value="<?= ccms_h((string) $data['site']['contact_email']) ?>"></div>
      </div>
      <div class="field"><label>Subtítulo</label><input name="site_tagline" value="<?= ccms_h((string) $data['site']['tagline']) ?>"></div>
      <div class="field"><label>Texto del footer</label><input name="footer_text" value="<?= ccms_h((string) $data['site']['footer_text']) ?>"></div>
      <div class="split-2">
        <div class="field">
          <label>Tema visual</label>
          <select name="theme_preset">
            <?php
              $themeOptions = [
                  'warm' => 'Warm',
                  'editorial' => 'Editorial',
                  'minimal' => 'Minimal',
                  'bold' => 'Bold',
              ];
              $activeThemePreset = (string) ($data['site']['theme_preset'] ?? 'warm');
            ?>
            <?php foreach ($themeOptions as $themeValue => $themeLabel): ?>
              <option value="<?= ccms_h($themeValue) ?>" <?= $activeThemePreset === $themeValue ? 'selected' : '' ?>><?= ccms_h($themeLabel) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="help-box" style="padding:14px 16px">
          <h4 style="margin:0 0 6px">Cómo funciona</h4>
          <p class="small" style="margin:0">El preset cambia tipografía, radios, sombras y el look general. Los colores siguen mandando desde la paleta de abajo.</p>
        </div>
      </div>
      <div class="color-grid">
        <?php
          $siteColors = [
              'bg' => 'Fondo general',
              'surface' => 'Tarjetas y superficies',
              'text' => 'Texto principal',
              'muted' => 'Texto secundario',
              'primary' => 'Color principal',
              'secondary' => 'Color secundario',
          ];
        ?>
        <?php foreach ($siteColors as $key => $label): ?>
          <div class="color-field">
            <label><?= ccms_h($label) ?></label>
            <div class="color-pair">
              <input type="color" value="<?= ccms_h((string) $data['site']['colors'][$key]) ?>" data-sync-color="color_<?= ccms_h($key) ?>">
              <input id="color_<?= ccms_h($key) ?>" name="color_<?= ccms_h($key) ?>" value="<?= ccms_h((string) $data['site']['colors'][$key]) ?>">
            </div>
          </div>
        <?php endforeach; ?>
      </div>
      <div class="field">
        <label>CSS personalizado del sitio</label>
        <textarea name="custom_css" class="html-editor" style="min-height:180px" placeholder="/* CSS opcional para retoques globales del tema */"><?= ccms_h((string) ($data['site']['custom_css'] ?? '')) ?></textarea>
        <p class="small">Este CSS se inyecta al final de la página pública. Úsalo para ajustes finos de estilo del tema, no para editar el contenido.</p>
      </div>
      <div class="toolbar">
        <button class="btn" type="submit">Guardar cambios del sitio</button>
      </div>
    </form>
  </div>
  <div class="help-box">
    <h4>Cómo usar esta sección</h4>
    <ul>
      <li>Cambia los colores principales si quieres adaptar toda la web a una nueva marca.</li>
      <li>Elige un preset visual si quieres cambiar el tono general sin tocar cada bloque.</li>
      <li>Usa el CSS personalizado solo para ajustes globales más avanzados.</li>
      <li>El título y el subtítulo aparecen en la cabecera y ayudan al posicionamiento básico.</li>
      <li>El email de contacto se puede reutilizar luego en formularios y páginas.</li>
    </ul>
    <p class="small" style="margin:14px 0 0"><strong>Storage actual:</strong> <?= ccms_h(strtoupper((string) $storageInfo['driver'])) ?><?php if (($storageInfo['driver'] ?? '') === 'sqlite'): ?> · <?= ccms_h((string) $storageInfo['sqlite_file']) ?><?php else: ?> · <?= ccms_h((string) $storageInfo['json_file']) ?><?php endif; ?></p>
  </div>
</div>
