<div class="site-layout">
  <div class="card editor-card">
    <div class="editor-header">
      <div class="editor-title">
        <div class="chip">Branding · sitio</div>
        <h2>Configuración general del sitio</h2>
        <p class="muted" style="margin:0">Cambia título, mensaje general, colores y datos de contacto. Esto afecta al encabezado y al wrapper de todas las páginas.</p>
      </div>
    </div>
    <form method="post" class="stack" id="siteSettingsForm">
      <input type="hidden" name="action" value="save_site">
      <input type="hidden" name="csrf_token" value="<?= ccms_h($csrfToken) ?>">
      <div class="split-2">
        <div class="field"><label>Título del sitio</label><input name="site_title" value="<?= ccms_h((string) $data['site']['title']) ?>"></div>
        <div class="field"><label>Email de contacto</label><input name="contact_email" type="email" value="<?= ccms_h((string) $data['site']['contact_email']) ?>"></div>
      </div>
      <div class="field"><label>Subtítulo</label><input name="site_tagline" value="<?= ccms_h((string) $data['site']['tagline']) ?>"></div>
      <div class="field"><label>Texto del footer</label><input name="footer_text" value="<?= ccms_h((string) $data['site']['footer_text']) ?>"></div>
      <div class="card" style="padding:20px;border-radius:22px;background:#fcfaf7;border:1px solid var(--line);box-shadow:none">
        <div class="editor-title" style="margin-bottom:14px">
          <div class="chip">Agencias · white-label</div>
          <h3 style="margin:8px 0 6px;font-size:22px">Marca el panel privado con tu agencia</h3>
          <p class="muted" style="margin:0">Afecta al login, la cabecera del admin y al nombre que usa el QR o URI de 2FA. No cambia la web pública salvo que edites también el contenido y footer del sitio.</p>
        </div>
        <div class="field">
          <label style="display:flex;align-items:center;gap:10px;text-transform:none;letter-spacing:0;color:var(--text);font-size:15px">
            <input type="checkbox" name="white_label_enabled" value="1" <?= !empty($data['site']['white_label_enabled']) ? 'checked' : '' ?> style="width:auto;min-width:18px;min-height:18px;padding:0">
            Activar white-label para agencias
          </label>
        </div>
        <div class="split-2">
          <div class="field"><label>Nombre de marca del admin</label><input name="admin_brand_name" value="<?= ccms_h((string) ($data['site']['admin_brand_name'] ?? '')) ?>" placeholder="Tu Agencia Studio"></div>
          <div class="field"><label>Logo del admin (URL o /uploads/...)</label><input name="admin_logo_url" value="<?= ccms_h((string) ($data['site']['admin_logo_url'] ?? '')) ?>" placeholder="/uploads/logo-agencia.png"></div>
        </div>
        <div class="field"><label>Subtítulo del admin</label><input name="admin_brand_tagline" value="<?= ccms_h((string) ($data['site']['admin_brand_tagline'] ?? '')) ?>" placeholder="Panel privado para tus clientes"></div>
      </div>
      <div class="split-2">
        <div class="field">
          <label>Analytics</label>
          <?php $activeAnalyticsProvider = (string) ($data['site']['analytics_provider'] ?? ''); ?>
          <select name="analytics_provider">
            <option value="" <?= $activeAnalyticsProvider === '' ? 'selected' : '' ?>>Sin analytics</option>
            <option value="ga4" <?= $activeAnalyticsProvider === 'ga4' ? 'selected' : '' ?>>Google Analytics 4</option>
            <option value="plausible" <?= $activeAnalyticsProvider === 'plausible' ? 'selected' : '' ?>>Plausible</option>
          </select>
        </div>
        <div class="field">
          <label>ID / dominio analytics</label>
          <input name="analytics_id" value="<?= ccms_h((string) ($data['site']['analytics_id'] ?? '')) ?>" placeholder="G-XXXXXXXXXX o tudominio.com">
          <p class="small">GA4 usa un ID tipo <code>G-XXXXXXXXXX</code>. Plausible usa el dominio del sitio.</p>
        </div>
      </div>
      <div class="split-2">
        <div class="field">
          <label>Perfil visual</label>
          <select name="theme_preset">
            <?php
              $themeOptions = [
                  'warm' => 'Warm',
                  'editorial' => 'Editorial',
                  'minimal' => 'Minimal',
                  'bold' => 'Bold',
                  'corporate' => 'Corporate',
                  'playful' => 'Playful',
                  'brutalist' => 'Brutalist',
                  'luxury' => 'Luxury',
                  'startup' => 'Startup',
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
          <p class="small" style="margin:0">El perfil cambia tipografía, radios, sombras, peso visual y espaciado general. Los colores siguen mandando desde la paleta de abajo.</p>
        </div>
      </div>
      <div class="split-2">
        <div class="field">
          <label>Tipografía</label>
          <?php $activeFontPairing = (string) ($data['site']['font_pairing'] ?? 'auto'); ?>
          <select name="font_pairing">
            <option value="auto" <?= $activeFontPairing === 'auto' ? 'selected' : '' ?>>Automática según perfil</option>
            <option value="modern" <?= $activeFontPairing === 'modern' ? 'selected' : '' ?>>Modern</option>
            <option value="editorial" <?= $activeFontPairing === 'editorial' ? 'selected' : '' ?>>Editorial</option>
            <option value="elegant" <?= $activeFontPairing === 'elegant' ? 'selected' : '' ?>>Elegant</option>
            <option value="classic" <?= $activeFontPairing === 'classic' ? 'selected' : '' ?>>Classic</option>
            <option value="mono" <?= $activeFontPairing === 'mono' ? 'selected' : '' ?>>Mono</option>
            <option value="humanist" <?= $activeFontPairing === 'humanist' ? 'selected' : '' ?>>Humanist</option>
          </select>
        </div>
        <div class="help-box" style="padding:14px 16px">
          <h4 style="margin:0 0 6px">Cuándo tocarlo</h4>
          <p class="small" style="margin:0">Déjalo en automático para respetar el perfil visual. Cámbialo si quieres una voz tipográfica distinta sin rehacer colores ni bloques.</p>
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
        <button class="btn" type="submit"><?= ccms_icon('save', 16) ?>Guardar cambios del sitio</button>
      </div>
    </form>
  </div>
  <div class="help-box">
    <h4>Cómo usar esta sección</h4>
    <ul>
      <li>Cambia los colores principales si quieres adaptar toda la web a una nueva marca.</li>
      <li>El bloque white-label sirve para entregar el panel con tu nombre, logo y marca en vez de LinuxCMS.</li>
      <li>Elige un perfil visual si quieres cambiar el tono general sin tocar cada bloque.</li>
      <li>Usa la tipografía en modo automático para que el perfil siga teniendo coherencia.</li>
      <li>Usa el CSS personalizado solo para ajustes globales más avanzados.</li>
      <li>El título y el subtítulo aparecen en la cabecera y ayudan al posicionamiento básico.</li>
      <li>El email de contacto se puede reutilizar luego en formularios y páginas.</li>
      <li>El campo analytics inserta el script de medición en todas las páginas públicas.</li>
    </ul>
    <p class="small" style="margin:14px 0 0"><strong>Storage actual:</strong> <?= ccms_h(strtoupper((string) $storageInfo['driver'])) ?><?php if (($storageInfo['driver'] ?? '') === 'sqlite'): ?> · <?= ccms_h((string) $storageInfo['sqlite_file']) ?><?php else: ?> · <?= ccms_h((string) $storageInfo['json_file']) ?><?php endif; ?></p>
  </div>
</div>
