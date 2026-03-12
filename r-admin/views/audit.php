<div class="pages-layout">
  <aside class="stack">
    <div class="card sidebar-card">
      <h2>Auditoría</h2>
      <p class="small">Este registro resume acciones sensibles del panel: inicios de sesión, cambios de sitio, usuarios, páginas, media e importaciones.</p>
    </div>
  </aside>
  <section class="workspace">
    <div class="card editor-card">
      <div class="editor-header">
        <div class="editor-title">
          <div class="chip">Audit log</div>
          <h2>Actividad reciente</h2>
          <p class="muted" style="margin:0">Útil para soporte, control de cambios y revisión rápida de lo ocurrido en `/r-admin`.</p>
        </div>
      </div>
      <div class="stack">
        <?php foreach ($auditLogs as $entry): ?>
          <div class="help-box" style="background:#fff">
            <div class="split-2">
              <div>
                <strong><?= ccms_h((string) ($entry['label'] ?? 'Audit entry')) ?></strong>
                <div class="small"><?= ccms_h((string) ($entry['action'] ?? '')) ?></div>
              </div>
              <div class="small" style="text-align:right"><?= ccms_h((string) ($entry['created_at'] ?? '')) ?></div>
            </div>
            <div class="small" style="margin-top:8px">
              Usuario: <strong><?= ccms_h((string) ($entry['user']['username'] ?? 'system')) ?></strong>
              <?php if (!empty($entry['user']['role'])): ?>
                · Rol: <?= ccms_h((string) $entry['user']['role']) ?>
              <?php endif; ?>
            </div>
            <?php if (!empty($entry['meta']) && is_array($entry['meta'])): ?>
              <pre class="builder-json" style="margin-top:10px;min-height:auto"><?= ccms_h(json_encode($entry['meta'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) ?></pre>
            <?php endif; ?>
          </div>
        <?php endforeach; ?>
        <?php if (empty($auditLogs)): ?>
          <p class="muted">Todavía no hay actividad registrada.</p>
        <?php endif; ?>
      </div>
    </div>
  </section>
</div>
