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
          <button class="btn" type="submit"><?= ccms_icon('upload', 16) ?>Subir a la librería</button>
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
    <div class="search-toolbar">
      <div class="search-input-wrap">
        <?= ccms_icon('search', 16) ?>
        <input id="searchMedia" class="search-input search-media" type="search" placeholder="Buscar por nombre o URL">
      </div>
    </div>
    <div class="media-grid" id="mediaGrid">
      <?php foreach ($data['media'] as $asset): ?>
        <?php
          $mediaSearch = trim(implode(' ', array_filter([
              (string) ($asset['original_name'] ?? ''),
              (string) ($asset['url'] ?? ''),
          ])));
        ?>
        <article class="media-card" data-media-search="<?= ccms_h($mediaSearch) ?>">
          <img src="<?= ccms_h((string) $asset['url']) ?>" alt="">
          <div class="small"><strong><?= ccms_h((string) $asset['original_name']) ?></strong><br><?= ccms_h((string) $asset['url']) ?></div>
          <button
            class="btn btn-secondary"
            type="button"
            data-copy-url="<?= ccms_h((string) $asset['url']) ?>"
            data-copy-success-label="URL copiada"
          ><?= ccms_icon('copy', 16) ?>Copiar URL</button>
        </article>
      <?php endforeach; ?>
      <?php if (empty($data['media'])): ?>
        <div class="empty-state">
          <div class="empty-state-title">Todavía no hay imágenes subidas</div>
          <p class="empty-state-desc">Sube tu primera imagen para reutilizarla luego en páginas y bloques.</p>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>
