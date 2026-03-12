<div class="import-layout">
  <div class="card editor-card">
    <div class="editor-header">
      <div class="editor-title">
        <div class="chip">Backups</div>
        <h2>Exporta o restaura el sitio completo</h2>
        <p class="muted" style="margin:0">Descarga una copia completa del contenido, usuarios, configuración y biblioteca media, o restaura una copia anterior.</p>
      </div>
    </div>
    <div class="split-2">
      <div class="metabox">
        <h3>Exportar backup</h3>
        <p class="small">Genera un archivo JSON portable con la configuración del sitio, páginas, revisiones, usuarios y archivos de subida.</p>
        <form method="post" class="stack">
          <input type="hidden" name="action" value="export_backup">
          <input type="hidden" name="csrf_token" value="<?= ccms_h($csrfToken) ?>">
          <button class="btn" type="submit">Descargar backup del sitio</button>
        </form>
      </div>
      <div class="metabox">
        <h3>Exportar paquete estático</h3>
        <p class="small">Genera un ZIP listo para hosting básico, con <code>index.html</code>, carpetas por slug y la carpeta <code>uploads</code>.</p>
        <form method="post" class="stack">
          <input type="hidden" name="action" value="export_static_site">
          <input type="hidden" name="csrf_token" value="<?= ccms_h($csrfToken) ?>">
          <button class="btn" type="submit">Descargar paquete para hosting</button>
        </form>
      </div>
    </div>
    <div class="split-2">
      <div class="metabox">
        <h3>Importar backup</h3>
        <p class="small">Restaura un backup completo. Esto sustituye el estado actual del sitio y te pedirá volver a iniciar sesión.</p>
        <form method="post" enctype="multipart/form-data" class="stack">
          <input type="hidden" name="action" value="import_backup">
          <input type="hidden" name="csrf_token" value="<?= ccms_h($csrfToken) ?>">
          <div class="field">
            <label>Archivo backup JSON</label>
            <input type="file" name="backup_file" accept=".json,application/json">
          </div>
          <div class="field">
            <label>O pega aquí el JSON del backup</label>
            <textarea name="backup_json" style="min-height:220px" placeholder="{ ... }"></textarea>
          </div>
          <button class="btn btn-secondary" type="submit">Restaurar backup</button>
        </form>
      </div>
    </div>
  </div>
  <div class="help-box">
    <h4>Qué incluye el backup</h4>
    <ul>
      <li>Configuración del sitio y tema.</li>
      <li>Páginas, cápsulas, HTML guardado y revisiones.</li>
      <li>Usuarios y ajustes de acceso.</li>
      <li>Biblioteca media y archivos subidos.</li>
      <li>El paquete estático crea una versión sin PHP lista para hosting básico.</li>
    </ul>
    <p class="small" style="margin-top:12px"><strong>Consejo:</strong> descarga un backup antes de grandes cambios o antes de mover el sitio a otro hosting.</p>
  </div>
</div>
