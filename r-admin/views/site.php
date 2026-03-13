<div class="site-layout">
  <?php
    $businessProfile = ccms_normalize_business_profile($data['site']['business_profile'] ?? []);
    $businessTypeCatalog = ccms_business_profile_type_catalog();
    $liveDataSlots = ccms_normalize_live_data_structure($data['live_data'] ?? [])['slots'] ?? [];
    $slotOptions = [
        'menu_daily' => [],
        'hours_status' => [],
        'price_list' => [],
    ];
    foreach ($liveDataSlots as $slotKey => $slot) {
        $slotType = (string) ($slot['type'] ?? '');
        if (!isset($slotOptions[$slotType])) {
            continue;
        }
        $slotOptions[$slotType][] = [
            'key' => (string) $slotKey,
            'label' => (string) $slotKey,
        ];
    }
  ?>
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
      <div class="card" style="padding:20px;border-radius:22px;background:#fcfaf7;border:1px solid var(--line);box-shadow:none">
        <div class="editor-title" style="margin-bottom:14px">
          <div class="chip">Perfil de negocio</div>
          <h3 style="margin:8px 0 6px;font-size:22px">Datos estructurados para negocio local</h3>
          <p class="muted" style="margin:0">Este bloque alimenta el Schema.org público y el endpoint <code>/.well-known/ai.json</code>. Si además conectas slots de <strong>live_data</strong>, LinuxCMS publicará menú, horarios y precios vivos sin tocar la maqueta.</p>
        </div>
        <div class="split-2">
          <div class="field">
            <label>Tipo de negocio</label>
            <select name="business_type">
              <?php foreach ($businessTypeCatalog as $businessTypeValue => $businessTypeMeta): ?>
                <option value="<?= ccms_h($businessTypeValue) ?>" <?= $businessProfile['type'] === $businessTypeValue ? 'selected' : '' ?>><?= ccms_h((string) ($businessTypeMeta['label'] ?? $businessTypeValue)) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="field">
            <label>Nombre público del negocio</label>
            <input name="business_name" value="<?= ccms_h((string) ($businessProfile['name'] ?? '')) ?>" placeholder="Casa Maria">
          </div>
        </div>
        <div class="field">
          <label>Descripción corta</label>
          <textarea name="business_description" style="min-height:110px" placeholder="Restaurante de cocina casera, despacho legal, peluquería de barrio..."><?= ccms_h((string) ($businessProfile['description'] ?? '')) ?></textarea>
        </div>
        <div class="split-2">
          <div class="field"><label>Teléfono</label><input name="business_phone" value="<?= ccms_h((string) ($businessProfile['phone'] ?? '')) ?>" placeholder="+34 963 123 456"></div>
          <div class="field"><label>Email del negocio</label><input name="business_email" type="email" value="<?= ccms_h((string) ($businessProfile['email'] ?? '')) ?>" placeholder="info@negocio.com"></div>
        </div>
        <div class="split-2">
          <div class="field"><label>Dirección</label><input name="business_street_address" value="<?= ccms_h((string) ($businessProfile['street_address'] ?? '')) ?>" placeholder="Calle Mayor 15"></div>
          <div class="field"><label>Código postal</label><input name="business_postal_code" value="<?= ccms_h((string) ($businessProfile['postal_code'] ?? '')) ?>" placeholder="46001"></div>
        </div>
        <div class="split-3">
          <div class="field"><label>Ciudad</label><input name="business_city" value="<?= ccms_h((string) ($businessProfile['city'] ?? '')) ?>" placeholder="Valencia"></div>
          <div class="field"><label>Región / provincia</label><input name="business_region" value="<?= ccms_h((string) ($businessProfile['region'] ?? '')) ?>" placeholder="Valencia"></div>
          <div class="field"><label>País</label><input name="business_country" value="<?= ccms_h((string) ($businessProfile['country'] ?? '')) ?>" placeholder="ES"></div>
        </div>
        <div class="split-2">
          <div class="field"><label>Latitud</label><input name="business_latitude" value="<?= ccms_h((string) ($businessProfile['latitude'] ?? '')) ?>" placeholder="39.4699"></div>
          <div class="field"><label>Longitud</label><input name="business_longitude" value="<?= ccms_h((string) ($businessProfile['longitude'] ?? '')) ?>" placeholder="-0.3763"></div>
        </div>
        <div class="split-2">
          <div class="field"><label>Rango de precios</label><input name="business_price_range" value="<?= ccms_h((string) ($businessProfile['price_range'] ?? '')) ?>" placeholder="€€ o desde 12€"></div>
          <div class="field"><label>Moneda aceptada</label><input name="business_currencies_accepted" value="<?= ccms_h((string) ($businessProfile['currencies_accepted'] ?? 'EUR')) ?>" placeholder="EUR"></div>
        </div>
        <div class="split-2">
          <div class="field"><label>Cocina / especialidad</label><input name="business_serves_cuisine" value="<?= ccms_h((string) ($businessProfile['serves_cuisine'] ?? '')) ?>" placeholder="Mediterránea, clínica dental, derecho mercantil..."></div>
          <div class="field"><label>URL de reservas</label><input name="business_reservation_url" value="<?= ccms_h((string) ($businessProfile['reservation_url'] ?? '')) ?>" placeholder="https://..."></div>
        </div>
        <div class="field"><label>URL del menú o carta</label><input name="business_menu_url" value="<?= ccms_h((string) ($businessProfile['menu_url'] ?? '')) ?>" placeholder="https://... o /uploads/carta.pdf"></div>
        <div class="split-3">
          <div class="field">
            <label>Slot de menú diario</label>
            <select name="business_daily_menu_slot">
              <option value="">Auto o sin conectar</option>
              <?php foreach ($slotOptions['menu_daily'] as $slotOption): ?>
                <option value="<?= ccms_h((string) $slotOption['key']) ?>" <?= ($businessProfile['daily_menu_slot'] ?? '') === $slotOption['key'] ? 'selected' : '' ?>><?= ccms_h((string) $slotOption['label']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="field">
            <label>Slot de horario</label>
            <select name="business_hours_slot">
              <option value="">Auto o sin conectar</option>
              <?php foreach ($slotOptions['hours_status'] as $slotOption): ?>
                <option value="<?= ccms_h((string) $slotOption['key']) ?>" <?= ($businessProfile['hours_slot'] ?? '') === $slotOption['key'] ? 'selected' : '' ?>><?= ccms_h((string) $slotOption['label']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="field">
            <label>Slot de precios</label>
            <select name="business_price_list_slot">
              <option value="">Auto o sin conectar</option>
              <?php foreach ($slotOptions['price_list'] as $slotOption): ?>
                <option value="<?= ccms_h((string) $slotOption['key']) ?>" <?= ($businessProfile['price_list_slot'] ?? '') === $slotOption['key'] ? 'selected' : '' ?>><?= ccms_h((string) $slotOption['label']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
        <div class="split-2">
          <label style="display:flex;align-items:center;gap:10px;text-transform:none;letter-spacing:0;color:var(--text);font-size:15px">
            <input type="checkbox" name="business_schema_enabled" value="1" <?= !empty($businessProfile['schema_enabled']) ? 'checked' : '' ?> style="width:auto;min-width:18px;min-height:18px;padding:0">
            Incluir Schema.org de negocio en el HTML
          </label>
          <label style="display:flex;align-items:center;gap:10px;text-transform:none;letter-spacing:0;color:var(--text);font-size:15px">
            <input type="checkbox" name="business_ai_feed_enabled" value="1" <?= !empty($businessProfile['ai_feed_enabled']) ? 'checked' : '' ?> style="width:auto;min-width:18px;min-height:18px;padding:0">
            Publicar <code>/.well-known/ai.json</code>
          </label>
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
      <li>El perfil de negocio añade datos estructurados y puede exponer <code>/.well-known/ai.json</code> para answer engines o integraciones.</li>
      <li>Usa el CSS personalizado solo para ajustes globales más avanzados.</li>
      <li>El título y el subtítulo aparecen en la cabecera y ayudan al posicionamiento básico.</li>
      <li>El email de contacto se puede reutilizar luego en formularios y páginas.</li>
      <li>El campo analytics inserta el script de medición en todas las páginas públicas.</li>
    </ul>
    <p class="small" style="margin:14px 0 0"><strong>Storage actual:</strong> <?= ccms_h(strtoupper((string) $storageInfo['driver'])) ?><?php if (($storageInfo['driver'] ?? '') === 'sqlite'): ?> · <?= ccms_h((string) $storageInfo['sqlite_file']) ?><?php else: ?> · <?= ccms_h((string) $storageInfo['json_file']) ?><?php endif; ?></p>
  </div>
</div>
