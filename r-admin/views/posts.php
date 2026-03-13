<?php
declare(strict_types=1);

$allPosts = is_array($data['posts'] ?? null) ? $data['posts'] : [];
$selectedPostCategories = $selectedPost ? implode(', ', array_map('strval', $selectedPost['categories'] ?? [])) : '';
$selectedPostTags = $selectedPost ? implode(', ', array_map('strval', $selectedPost['tags'] ?? [])) : '';
$selectedPublishedAt = '';
if ($selectedPost && !empty($selectedPost['published_at'])) {
    $selectedPublishedAt = date('Y-m-d\TH:i', strtotime((string) $selectedPost['published_at']));
}
?>
<div class="pages-layout">
  <aside class="stack">
    <div class="card sidebar-card">
      <h2>Nuevo post</h2>
      <?php if ($canManagePosts): ?>
        <form method="post" class="stack">
          <input type="hidden" name="action" value="create_post">
          <input type="hidden" name="csrf_token" value="<?= ccms_h($csrfToken) ?>">
          <div class="field"><label>Título</label><input name="post_title" required></div>
          <div class="field"><label>Slug</label><input name="post_slug" placeholder="opcional"></div>
          <button class="btn" type="submit"><?= ccms_icon('plus', 16) ?>Crear post</button>
        </form>
      <?php else: ?>
        <p class="small">Tu rol actual es de solo lectura. Puedes revisar posts y su vista previa, pero no crear ni modificar contenido.</p>
      <?php endif; ?>
    </div>

    <div class="card sidebar-card">
      <h2>Posts</h2>
      <div class="page-list">
        <?php foreach ($allPosts as $post): ?>
          <?php $isActive = $selectedPost && ($selectedPost['id'] ?? '') === ($post['id'] ?? ''); ?>
          <a class="page-item <?= $isActive ? 'active' : '' ?>" href="/r-admin/?tab=posts&post=<?= ccms_h((string) ($post['slug'] ?? '')) ?>">
            <strong><?= ccms_h((string) ($post['title'] ?? 'Untitled')) ?></strong>
            <span class="status"><?= ccms_status_icon((string) ($post['status'] ?? 'draft')) ?><?= ccms_h((string) ($post['status'] ?? 'draft')) ?></span>
            <div class="small" style="margin-top:8px">/blog/<?= ccms_h((string) ($post['slug'] ?? '')) ?></div>
          </a>
        <?php endforeach; ?>
        <?php if (empty($allPosts)): ?>
          <p class="muted">No hay posts todavía.</p>
        <?php endif; ?>
      </div>
    </div>
  </aside>

  <section class="workspace">
    <?php if ($selectedPost): ?>
      <div class="card editor-card">
        <div class="editor-header">
          <div class="editor-title">
            <div class="chip">Editando post · <?= ccms_h((string) ($selectedPost['slug'] ?? '')) ?></div>
            <h2><?= ccms_h((string) ($selectedPost['title'] ?? 'Untitled')) ?></h2>
            <div class="editor-meta">
              <span class="chip"><?= ccms_h((string) ($selectedPost['status'] ?? 'draft')) ?></span>
              <span class="chip"><?= count($selectedPostRevisions) ?> revisiones</span>
              <span class="chip">Actualizado · <?= ccms_h((string) ($selectedPost['updated_at'] ?? '')) ?></span>
            </div>
          </div>
          <div class="toolbar">
            <a class="btn btn-secondary" href="/blog/<?= rawurlencode((string) ($selectedPost['slug'] ?? '')) ?>" target="_blank" rel="noopener"><?= ccms_icon('eye', 16) ?>Abrir post</a>
          </div>
        </div>

        <form method="post" id="postEditorForm">
          <input type="hidden" name="action" value="save_post">
          <input type="hidden" name="csrf_token" value="<?= ccms_h($csrfToken) ?>">
          <input type="hidden" name="post_id" value="<?= ccms_h((string) ($selectedPost['id'] ?? '')) ?>">

          <div class="editor-layout">
            <div class="editor-main">
              <div class="metabox">
                <div class="subtabs">
                  <button type="button" class="active" data-tab-target="content">Contenido</button>
                  <button type="button" data-tab-target="seo">SEO</button>
                  <button type="button" data-tab-target="publish">Publicación</button>
                </div>

                <section class="subpanel active" data-tab-panel="content">
                  <div class="split-2">
                    <div class="field"><label>Título</label><input name="post_title" value="<?= ccms_h((string) ($selectedPost['title'] ?? '')) ?>" <?= $canManagePosts ? '' : 'readonly' ?>></div>
                    <div class="field"><label>Slug</label><input name="post_slug" value="<?= ccms_h((string) ($selectedPost['slug'] ?? '')) ?>" <?= $canManagePosts ? '' : 'readonly' ?>></div>
                    <div class="field"><label>Autor</label><input name="author_name" value="<?= ccms_h((string) ($selectedPost['author_name'] ?? '')) ?>" <?= $canManagePosts ? '' : 'readonly' ?>></div>
                    <div class="field"><label>Imagen de portada</label><input name="cover_image" value="<?= ccms_h((string) ($selectedPost['cover_image'] ?? '')) ?>" placeholder="https://..." <?= $canManagePosts ? '' : 'readonly' ?>></div>
                  </div>
                  <div class="field"><label>Extracto</label><textarea name="excerpt" <?= $canManagePosts ? '' : 'readonly' ?>><?= ccms_h((string) ($selectedPost['excerpt'] ?? '')) ?></textarea></div>
                  <div class="field"><label>Contenido HTML</label><textarea name="content_html" class="html-editor" style="min-height:420px" <?= $canManagePosts ? '' : 'readonly' ?>><?= ccms_h((string) ($selectedPost['content_html'] ?? '')) ?></textarea></div>
                </section>

                <section class="subpanel" data-tab-panel="seo">
                  <div class="split-2">
                    <div class="field"><label>Categorías</label><input name="categories" value="<?= ccms_h($selectedPostCategories) ?>" placeholder="Noticias, Guías, Casos" <?= $canManagePosts ? '' : 'readonly' ?>></div>
                    <div class="field"><label>Tags</label><input name="tags" value="<?= ccms_h($selectedPostTags) ?>" placeholder="seo, legal, branding" <?= $canManagePosts ? '' : 'readonly' ?>></div>
                    <div class="field"><label>Meta title</label><input name="meta_title" value="<?= ccms_h((string) ($selectedPost['meta_title'] ?? '')) ?>" <?= $canManagePosts ? '' : 'readonly' ?>></div>
                  </div>
                  <div class="field"><label>Meta description</label><textarea name="meta_description" <?= $canManagePosts ? '' : 'readonly' ?>><?= ccms_h((string) ($selectedPost['meta_description'] ?? '')) ?></textarea></div>
                </section>

                <section class="subpanel" data-tab-panel="publish">
                  <div class="split-2">
                    <div class="field">
                      <label>Estado</label>
                      <select name="post_status" <?= $canManagePosts ? '' : 'disabled' ?>>
                        <option value="draft" <?= ($selectedPost['status'] ?? '') === 'draft' ? 'selected' : '' ?>>Borrador</option>
                        <option value="published" <?= ($selectedPost['status'] ?? '') === 'published' ? 'selected' : '' ?>>Publicado</option>
                        <option value="scheduled" <?= ($selectedPost['status'] ?? '') === 'scheduled' ? 'selected' : '' ?>>Programado</option>
                      </select>
                    </div>
                    <div class="field">
                      <label>Fecha de publicación</label>
                      <input type="datetime-local" name="published_at" value="<?= ccms_h($selectedPublishedAt) ?>" <?= $canManagePosts ? '' : 'readonly' ?>>
                    </div>
                  </div>
                  <?php if ($canManagePosts): ?>
                    <div class="toolbar">
                      <button class="btn" type="submit"><?= ccms_icon('save', 16) ?>Guardar post</button>
                    </div>
                  <?php endif; ?>
                  <div class="help-box">
                    <h4>Consejo editorial</h4>
                    <ul>
                      <li>Usa categorías para agrupar contenido y tags para temas concretos.</li>
                      <li>Si publicas un post sin fecha, LinuxCMS usará la fecha actual.</li>
                      <li>Si eliges <strong>Programado</strong>, el post solo será visible cuando llegue la fecha indicada.</li>
                    </ul>
                  </div>
                </section>
              </div>
            </div>

            <aside class="stack sticky-actions">
              <div class="card editor-card">
                <h2 style="margin-bottom:10px">Vista previa</h2>
                <iframe class="preview-frame" srcdoc="<?= ccms_h($postPreviewHtml) ?>"></iframe>
                <div class="preview-actions">
                  <a class="btn btn-secondary" href="/blog/<?= rawurlencode((string) ($selectedPost['slug'] ?? '')) ?>" target="_blank" rel="noopener"><?= ccms_icon('globe', 16) ?>Abrir publicada</a>
                </div>
              </div>

              <div class="card editor-card">
                <h2>Acciones</h2>
                <?php if ($canManagePosts): ?>
                  <div class="toolbar" style="flex-wrap:wrap">
                    <form method="post" style="margin:0">
                      <input type="hidden" name="action" value="duplicate_post">
                      <input type="hidden" name="csrf_token" value="<?= ccms_h($csrfToken) ?>">
                      <input type="hidden" name="post_id" value="<?= ccms_h((string) ($selectedPost['id'] ?? '')) ?>">
                      <button class="btn btn-secondary" type="submit"><?= ccms_icon('copy', 16) ?>Duplicar</button>
                    </form>
                    <form method="post" style="margin:0" data-confirm-title="¿Eliminar este post?" data-confirm-message="Esta acción eliminará el post y sus revisiones guardadas.">
                      <input type="hidden" name="action" value="delete_post">
                      <input type="hidden" name="csrf_token" value="<?= ccms_h($csrfToken) ?>">
                      <input type="hidden" name="post_id" value="<?= ccms_h((string) ($selectedPost['id'] ?? '')) ?>">
                      <button class="btn btn-danger" type="submit"><?= ccms_icon('trash-2', 16) ?>Eliminar</button>
                    </form>
                  </div>
                <?php else: ?>
                  <p class="small">Tu rol actual es de solo lectura. Puedes revisar el post y su vista previa, pero no modificarlo.</p>
                <?php endif; ?>
              </div>
            </aside>
          </div>
        </form>
      </div>
    <?php else: ?>
      <div class="card editor-card">
        <div class="empty-state">
          <h3>No hay posts todavía</h3>
          <p class="muted">Crea tu primer artículo desde la barra lateral para activar el blog público, el feed RSS y los archivos por categoría y tag.</p>
        </div>
      </div>
    <?php endif; ?>
  </section>
</div>
