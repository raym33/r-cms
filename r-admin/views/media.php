<div class="media-layout">
  <div class="card editor-card">
    <div class="editor-header">
      <div class="editor-title">
        <div class="chip">Media library</div>
        <h2>Sube y reutiliza tus imágenes</h2>
        <p class="muted" style="margin:0">Todo lo que subas aquí puede insertarse luego en páginas con un clic desde el editor.</p>
      </div>
    </div>
    <div class="split-2">
      <div class="metabox">
        <h3>Subir nueva imagen</h3>
        <form method="post" enctype="multipart/form-data" class="stack">
          <input type="hidden" name="action" value="upload_media">
          <input type="hidden" name="csrf_token" value="<?= ccms_h($csrfToken) ?>">
          <div class="field">
            <label>Archivo</label>
            <input type="file" name="media_file" accept=".jpg,.jpeg,.png,.webp,.gif,.svg" required>
          </div>
          <button class="btn" type="submit">Subir a la librería</button>
        </form>
      </div>
      <div class="help-box">
        <h4>Consejo rápido</h4>
        <ul>
          <li>Sube imágenes optimizadas para web para no ralentizar la página.</li>
          <li>Después, ve a <strong>Páginas</strong> y usa el botón de insertar imagen desde la biblioteca.</li>
          <li>Las imágenes quedan publicadas en <code>/uploads/...</code>.</li>
        </ul>
      </div>
    </div>
  </div>
  <div class="card editor-card">
    <h2>Librería media</h2>
    <div class="media-grid">
      <?php foreach ($data['media'] as $asset): ?>
        <article class="media-card">
          <img src="<?= ccms_h((string) $asset['url']) ?>" alt="">
          <div class="small"><strong><?= ccms_h((string) $asset['original_name']) ?></strong><br><?= ccms_h((string) $asset['url']) ?></div>
          <button class="btn btn-secondary" type="button" data-copy-url="<?= ccms_h((string) $asset['url']) ?>">Copiar URL</button>
        </article>
      <?php endforeach; ?>
      <?php if (empty($data['media'])): ?>
        <p class="muted">Todavía no hay imágenes subidas.</p>
      <?php endif; ?>
    </div>
  </div>
</div>
