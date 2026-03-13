<div class="pages-layout">
  <aside class="stack">
    <div class="card sidebar-card">
      <h2>LM Studio local</h2>
      <form method="post" class="stack">
        <input type="hidden" name="action" value="save_ai_settings">
        <input type="hidden" name="csrf_token" value="<?= ccms_h($csrfToken) ?>">
        <div class="field"><label>Endpoint</label><input name="ai_endpoint" value="<?= ccms_h((string) $aiSettings['endpoint']) ?>" placeholder="http://127.0.0.1:1234/v1"></div>
        <div class="field"><label>Modelo</label><input name="ai_model" value="<?= ccms_h((string) $aiSettings['model']) ?>" placeholder="Déjalo vacío para usar el primero disponible"></div>
        <div class="split-2">
          <div class="field"><label>Temperature</label><input name="ai_temperature" type="number" min="0" max="1.2" step="0.1" value="<?= ccms_h((string) $aiSettings['temperature']) ?>"></div>
          <div class="field"><label>Max tokens</label><input name="ai_max_tokens" type="number" min="600" max="6000" step="100" value="<?= ccms_h((string) $aiSettings['max_tokens']) ?>"></div>
        </div>
        <div class="field"><label>Timeout (segundos)</label><input name="ai_timeout" type="number" min="5" max="120" step="1" value="<?= ccms_h((string) $aiSettings['timeout']) ?>"></div>
        <div class="toolbar">
          <button class="btn" type="submit">Guardar configuración</button>
        </div>
      </form>
      <form method="post" class="stack" style="margin-top:12px">
        <input type="hidden" name="action" value="probe_ai">
        <input type="hidden" name="csrf_token" value="<?= ccms_h($csrfToken) ?>">
        <input type="hidden" name="ai_endpoint" value="<?= ccms_h((string) $aiSettings['endpoint']) ?>">
        <input type="hidden" name="ai_model" value="<?= ccms_h((string) $aiSettings['model']) ?>">
        <input type="hidden" name="ai_temperature" value="<?= ccms_h((string) $aiSettings['temperature']) ?>">
        <input type="hidden" name="ai_max_tokens" value="<?= ccms_h((string) $aiSettings['max_tokens']) ?>">
        <input type="hidden" name="ai_timeout" value="<?= ccms_h((string) $aiSettings['timeout']) ?>">
        <button class="btn btn-secondary" type="submit">Probar conexión con LM Studio</button>
      </form>
    </div>
    <div class="help-box">
      <h4>Cómo funciona LinuxCMS</h4>
      <ul>
        <li>Esta pestaña genera el primer borrador de la web con <strong>LM Studio local</strong>.</li>
        <li>Después, la página cae en <strong>Páginas</strong>, donde la editas con builder, preview y media.</li>
        <li>Al subir el proyecto a hosting básico, el cliente final sigue entrando por <strong>/r-admin</strong> para editarla manualmente.</li>
        <li>Si LM Studio no responde, LinuxCMS crea un draft base para no dejarte bloqueado.</li>
      </ul>
    </div>
  </aside>
  <section class="workspace">
    <div class="card editor-card">
      <div class="editor-header">
        <div class="editor-title">
          <div class="chip">Studio local · teclado</div>
          <h2>Crea una web completa desde un brief</h2>
          <p class="muted" style="margin:0">Todo desde teclado. Sin voz. LM Studio genera una primera cápsula editable y el CMS se encarga del resto.</p>
        </div>
      </div>
      <form method="post" class="stack">
        <input type="hidden" name="action" value="ai_generate_page">
        <input type="hidden" name="csrf_token" value="<?= ccms_h($csrfToken) ?>">
        <div class="split-2">
          <div class="field"><label>Nombre del negocio</label><input name="business_name" placeholder="OTM Lawyers" required></div>
          <div class="field"><label>Título de la página</label><input name="page_title" placeholder="Corporate Law for fast-moving businesses"></div>
          <div class="field"><label>Slug</label><input name="page_slug" placeholder="otm-lawyers"></div>
          <div class="field">
            <label>Industria</label>
            <select name="industry">
              <option value="generic">Genérica</option>
              <option value="lawyer">Legal</option>
              <option value="saas">SaaS / Tech</option>
              <option value="restaurant">Restaurante</option>
              <option value="real-estate">Inmobiliaria</option>
              <option value="creative">Creativa / portfolio</option>
              <option value="clinic">Clínica / salud</option>
              <option value="beauty">Beauty / salon</option>
              <option value="public-sector">Portal público</option>
            </select>
          </div>
        </div>
        <div class="field">
          <label>Pack premium de partida</label>
          <select name="pack_id">
            <option value="auto" <?= (($aiSettings['preferred_pack_id'] ?? 'auto') === 'auto') ? 'selected' : '' ?>>Auto (elige un pack según industria y negocio)</option>
            <?php foreach ($premiumPacksByIndustry as $industryGroup): ?>
              <optgroup label="<?= ccms_h((string) ($industryGroup['label'] ?? 'Packs')) ?>">
                <?php foreach (($industryGroup['packs'] ?? []) as $pack): ?>
                  <?php $packId = (string) ($pack['id'] ?? ''); ?>
                  <option value="<?= ccms_h($packId) ?>" <?= (($aiSettings['preferred_pack_id'] ?? 'auto') === $packId) ? 'selected' : '' ?>>
                    <?= ccms_h((string) ($pack['label'] ?? $packId)) ?> · <?= ccms_h((string) ($pack['description'] ?? '')) ?>
                  </option>
                <?php endforeach; ?>
              </optgroup>
            <?php endforeach; ?>
          </select>
          <p class="small" style="margin-top:8px">El Studio arrancará desde un pack premium real y LM Studio reescribirá el contenido sin perder la estructura visual base.</p>
        </div>
        <div class="field"><label>Oferta o servicio</label><textarea name="offer" style="min-height:100px" placeholder="Describe qué vendes y por qué importa." required></textarea></div>
        <div class="split-2">
          <div class="field"><label>Cliente ideal</label><textarea name="audience" style="min-height:100px" placeholder="¿Para quién es esta web?"></textarea></div>
          <div class="field"><label>Objetivo principal</label><textarea name="goal" style="min-height:100px" placeholder="Reservas, leads, ventas, llamadas..." required></textarea></div>
        </div>
        <div class="split-2">
          <div class="field"><label>Texto del CTA</label><input name="cta_text" placeholder="Book a call"></div>
          <div class="field"><label>Tono</label><input name="tone" placeholder="Premium, calm, editorial, direct..."></div>
        </div>
        <div class="field"><label>Notas extra</label><textarea name="notes" style="min-height:120px" placeholder="Referencias visuales, secciones obligatorias, cosas que no quieres, etc."></textarea></div>
        <div class="check-grid">
          <label class="check"><input type="checkbox" name="set_as_homepage" checked> Usar esta página como homepage</label>
          <label class="check"><input type="checkbox" name="apply_site_branding" checked> Aplicar también el branding generado al sitio</label>
        </div>
        <div class="toolbar">
          <button class="btn" type="submit">Generar borrador con LM Studio</button>
          <a class="btn btn-secondary" href="/r-admin/?tab=pages">Abrir páginas existentes</a>
        </div>
      </form>
    </div>
    <div class="help-box">
      <h4>Qué genera esta pantalla</h4>
      <ul>
        <li>Una página nueva con una <strong>cápsula completa</strong>.</li>
        <li>Una estructura premium basada en un <strong>pack curado</strong>, no en una cápsula genérica.</li>
        <li>Una base visual que luego puedes afinar por sección desde <strong>Páginas</strong>.</li>
        <li>Un resultado más variable entre clientes, porque el diseño base cambia antes de que LM Studio escriba el copy.</li>
      </ul>
      <p class="small" style="margin-top:12px"><strong>Consejo:</strong> usa esta pantalla para crear la primera versión y el builder para pulirla a nivel de detalle.</p>
    </div>
  </section>
</div>
