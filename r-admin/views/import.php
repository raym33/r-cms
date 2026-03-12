<div class="import-layout">
  <div class="card editor-card">
    <div class="editor-header">
      <div class="editor-title">
        <div class="chip">Importar</div>
        <h2>Importación rápida de una cápsula o HTML</h2>
        <p class="muted" style="margin:0">Pega el HTML ya renderizado de una cápsula y, opcionalmente, el JSON para conservar la referencia original.</p>
      </div>
    </div>
    <form method="post" class="stack">
      <input type="hidden" name="action" value="quick_import">
      <input type="hidden" name="csrf_token" value="<?= ccms_h($csrfToken) ?>">
      <div class="split-2">
        <div class="field"><label>Título</label><input name="import_title" required></div>
        <div class="field"><label>Slug</label><input name="import_slug" placeholder="opcional"></div>
      </div>
      <div class="field"><label>HTML</label><textarea name="import_html" style="min-height:260px" required></textarea></div>
      <div class="field"><label>Capsule JSON (opcional)</label><textarea name="import_capsule_json" style="min-height:220px"></textarea></div>
      <div class="toolbar">
        <button class="btn" type="submit">Importar página</button>
      </div>
    </form>
    <p class="small">También puedes usar el script CLI <code>php tools/import-from-aivoiceweb.php</code>.</p>
  </div>
</div>
