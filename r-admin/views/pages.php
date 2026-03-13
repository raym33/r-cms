<?php
$selectedPagePublishedAt = '';
if ($selectedPage && !empty($selectedPage['published_at'])) {
    $selectedPagePublishedAt = date('Y-m-d\TH:i', strtotime((string) $selectedPage['published_at']));
}
?>
<div class="pages-layout">
  <aside class="stack">
    <div class="card sidebar-card">
      <h2>Nueva página</h2>
      <?php if ($canManagePages): ?>
        <form method="post" class="stack">
          <input type="hidden" name="action" value="create_page">
          <input type="hidden" name="csrf_token" value="<?= ccms_h($csrfToken) ?>">
          <div class="field"><label>Título</label><input name="title" required></div>
          <div class="field"><label>Slug</label><input name="slug" placeholder="opcional"></div>
          <button class="btn" type="submit"><?= ccms_icon('plus', 16) ?>Crear página</button>
        </form>
      <?php else: ?>
        <p class="small">Tu rol actual es de solo lectura. Puedes revisar páginas y su vista previa, pero no crear ni modificar contenido.</p>
      <?php endif; ?>
    </div>
    <div class="card sidebar-card">
      <h2>Páginas</h2>
      <div class="search-toolbar">
        <div class="search-input-wrap">
          <?= ccms_icon('search', 16) ?>
          <input id="searchPages" class="search-input search-pages" type="search" placeholder="Buscar por título, slug o estado">
        </div>
      </div>
      <div class="page-list" id="pageList">
        <?php foreach ($data['pages'] as $page): ?>
          <?php $isActive = $selectedPage && ($selectedPage['id'] ?? '') === ($page['id'] ?? ''); ?>
          <?php
            $pageSearch = trim(implode(' ', array_filter([
                (string) ($page['title'] ?? ''),
                (string) ($page['slug'] ?? ''),
                (string) ($page['status'] ?? ''),
                !empty($page['is_homepage']) ? 'homepage' : '',
            ])));
          ?>
          <a
            class="page-item <?= $isActive ? 'active' : '' ?>"
            href="/r-admin/?tab=pages&page=<?= ccms_h((string) $page['slug']) ?>"
            data-page-search="<?= ccms_h($pageSearch) ?>"
            data-page-title="<?= ccms_h((string) ($page['title'] ?? '')) ?>"
            data-page-slug="<?= ccms_h((string) ($page['slug'] ?? '')) ?>"
            data-page-status="<?= ccms_h((string) ($page['status'] ?? '')) ?>"
          >
            <strong><?= ccms_h((string) $page['title']) ?></strong>
            <span class="status"><?= ccms_status_icon((string) $page['status']) ?><?= ccms_h((string) $page['status']) ?><?= !empty($page['is_homepage']) ? ' · homepage' : '' ?></span>
            <div class="small" style="margin-top:8px">/<?= ccms_h((string) $page['slug']) ?></div>
          </a>
        <?php endforeach; ?>
        <?php if (empty($data['pages'])): ?>
          <div class="empty-state">
            <div class="empty-state-title">No hay páginas todavía</div>
            <p class="empty-state-desc">Crea tu primera página para empezar a construir el sitio.</p>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </aside>

  <section class="workspace">
    <?php if ($selectedPage): ?>
      <div class="card editor-card">
        <div class="editor-header">
          <div class="editor-title">
            <div class="chip">Editando · <?= ccms_h((string) $selectedPage['slug']) ?></div>
            <h2><?= ccms_h((string) $selectedPage['title']) ?></h2>
            <div class="editor-meta">
              <span class="chip"><?= ccms_h((string) $selectedPage['status']) ?></span>
              <?php if (!empty($selectedPage['is_homepage'])): ?><span class="chip">Homepage</span><?php endif; ?>
              <span class="chip"><?= count($selectedRevisions) ?> revisiones</span>
              <span class="chip">Actualizada · <?= ccms_h((string) $selectedPage['updated_at']) ?></span>
            </div>
          </div>
          <div class="toolbar">
            <a class="btn btn-secondary" href="<?= !empty($selectedPage['is_homepage']) ? '/' : '/' . rawurlencode((string) $selectedPage['slug']) ?>" target="_blank" rel="noopener"><?= ccms_icon('eye', 16) ?>Abrir página</a>
          </div>
        </div>
        <div class="client-quick-actions" id="clientQuickActions">
          <button class="btn btn-secondary" type="button" data-client-focus="content">Textos y fotos</button>
          <button class="btn btn-secondary" type="button" data-client-focus="builder">Secciones</button>
          <button class="btn btn-secondary" type="button" data-client-focus="site">Colores globales</button>
          <button class="btn btn-secondary" type="button" data-client-focus="publish">Publicar</button>
        </div>
        <?php if ($canManagePages): ?>
        <form method="post" id="pageEditorForm">
          <input type="hidden" name="action" value="save_page">
          <input type="hidden" name="csrf_token" value="<?= ccms_h($csrfToken) ?>">
          <input type="hidden" name="page_id" value="<?= ccms_h((string) $selectedPage['id']) ?>">
          <input type="hidden" name="autosave" value="0" id="pageAutosaveFlag">

          <div class="editor-statusbar" id="editorStatusbar">
            <div class="editor-status-copy">
              <span class="autosave-pill is-saved" id="autosaveStatus"><?= ccms_icon('circle-check', 14) ?>Guardado</span>
              <span class="autosave-meta" id="autosaveMeta">Sin cambios pendientes.</span>
            </div>
            <div class="small">Pulsa <strong>Ctrl/Cmd + S</strong> para guardar manualmente. El contenido se autoguarda mientras editas.</div>
          </div>

          <div class="editor-layout">
            <div class="editor-main">
              <div class="metabox">
                <div class="editor-workbench">
                  <aside class="editor-nav">
                    <div class="subtabs subtabs-vertical" id="editorTabs">
                      <button type="button" class="active" data-tab-target="content"><?= ccms_icon('pencil', 16) ?><span>Contenido</span></button>
                      <button type="button" data-tab-target="builder"><?= ccms_icon('grip-vertical', 16) ?><span>Secciones</span></button>
                      <button type="button" class="advanced-only" data-tab-target="seo"><?= ccms_icon('search', 16) ?><span>SEO y menú</span></button>
                      <button type="button" class="advanced-only" data-tab-target="capsule"><?= ccms_icon('settings', 16) ?><span>JSON avanzado</span></button>
                      <button type="button" data-tab-target="publish"><?= ccms_icon('globe', 16) ?><span>Publicación</span></button>
                    </div>
                  </aside>
                  <div class="editor-panels">
                    <section class="subpanel active" data-tab-panel="content">
                      <div class="quickstart-guide">
                        <div class="quickstart-head">
                          <h3>Empieza por aquí</h3>
                          <p>Si no eres técnico, sigue estos cuatro pasos: primero cambia los textos principales, luego sustituye las fotos, después revisa el orden de las secciones y al final publica.</p>
                        </div>
                        <div class="quickstart-grid">
                          <article class="quickstart-step">
                            <span class="quickstart-step-number">1</span>
                            <strong>Cambia los textos</strong>
                            <p>Haz clic en una sección de la vista previa y luego doble clic sobre un título, párrafo o botón para editarlo.</p>
                            <button class="btn btn-secondary" type="button" data-guide-target="preview-text"><?= ccms_icon('pencil', 16) ?>Ir a textos</button>
                          </article>
                          <article class="quickstart-step">
                            <span class="quickstart-step-number">2</span>
                            <strong>Sustituye las fotos</strong>
                            <p>Haz doble clic sobre una imagen o usa la biblioteca media para poner fotos reales de tu negocio.</p>
                            <button class="btn btn-secondary" type="button" data-guide-target="preview-media"><?= ccms_icon('image', 16) ?>Ir a fotos</button>
                          </article>
                          <article class="quickstart-step">
                            <span class="quickstart-step-number">3</span>
                            <strong>Ordena las secciones</strong>
                            <p>En la pestaña <strong>Secciones</strong> puedes añadir, duplicar, mover o borrar bloques sin tocar código.</p>
                            <button class="btn btn-secondary" type="button" data-guide-target="builder"><?= ccms_icon('plus-circle', 16) ?>Ir a secciones</button>
                          </article>
                          <article class="quickstart-step">
                            <span class="quickstart-step-number">4</span>
                            <strong>Revisa antes de publicar</strong>
                            <p>Comprueba enlaces, textos pendientes y estado de la página desde la pestaña de publicación.</p>
                            <button class="btn btn-secondary" type="button" data-guide-target="publish"><?= ccms_icon('save', 16) ?>Ir a publicar</button>
                          </article>
                        </div>
                      </div>
                      <div class="help-box advanced-only">
                        <h4>Bloques rápidos</h4>
                        <ul>
                          <li>Inserta una sección y luego personaliza el texto directamente en el HTML.</li>
                          <li>Si ya subiste imágenes, puedes insertarlas desde la biblioteca media más abajo.</li>
                          <li>Este flujo intenta parecerse más a WordPress/Elementor, pero sigue siendo muy portátil.</li>
                        </ul>
                      </div>
                      <div class="section-grid" id="sectionGrid">
                        <?php foreach ($sectionTemplates as $index => $template): ?>
                          <article class="section-card">
                            <span><?= ccms_h((string) $template['category']) ?></span>
                            <strong><?= ccms_h((string) $template['label']) ?></strong>
                            <div class="small">Inserta una sección base para no empezar desde cero.</div>
                            <button class="btn btn-secondary" type="button" data-insert-template="<?= (int) $index ?>">Insertar sección</button>
                          </article>
                        <?php endforeach; ?>
                      </div>

                      <div class="metabox" style="background:#fdfbf8">
                        <h3>Biblioteca media para esta página</h3>
                        <div class="media-grid">
                          <?php foreach ($data['media'] as $asset): ?>
                            <article class="media-card">
                              <img src="<?= ccms_h((string) $asset['url']) ?>" alt="">
                              <div class="small"><strong><?= ccms_h((string) $asset['original_name']) ?></strong></div>
                              <button class="btn btn-secondary" type="button" data-insert-media="<?= ccms_h((string) $asset['url']) ?>">Insertar imagen</button>
                            </article>
                          <?php endforeach; ?>
                          <?php if (empty($data['media'])): ?>
                            <p class="muted">Todavía no hay imágenes en la librería. Súbelas en la pestaña <strong>Media</strong>.</p>
                          <?php endif; ?>
                        </div>
                      </div>

                      <div class="field">
                        <label>HTML de la página</label>
                        <textarea id="html_content" class="html-editor" name="html_content"><?= ccms_h((string) $selectedPage['html_content']) ?></textarea>
                      </div>
                    </section>

                    <section class="subpanel" data-tab-panel="builder">
                      <div class="builder-layout">
                        <div class="builder-note">
                          Aquí editas la <strong>estructura de la página</strong> de forma visual: puedes añadir secciones, cambiar su orden, duplicarlas y tocar sus ajustes principales sin entrar en código. Cuando guardes la página, el CMS actualizará el <strong>JSON avanzado</strong> automáticamente.
                        </div>
                        <div class="builder-toolbar advanced-only">
                          <div class="builder-stats">
                            <span class="chip" id="builderBlockCount">0 bloques</span>
                            <span class="chip">Solo bloques PHP soportados</span>
                          </div>
                          <div class="toolbar">
                            <button class="btn btn-secondary" type="button" id="builderSyncJson">Sincronizar JSON</button>
                          </div>
                        </div>
                        <div class="builder-context" id="builderContext"></div>
                        <div class="builder-surface">
                          <div>
                            <h3>Marca y estilo de la cápsula</h3>
                            <p class="small" style="margin:6px 0 0">Edita los tokens visuales que usa el renderer PHP: colores principales, fondos, tipografía y navegación.</p>
                          </div>
                          <div id="builderGlobalStyle" class="builder-style-grid"></div>
                        </div>
                        <div class="builder-library">
                          <div>
                            <h3 style="margin:0 0 8px">Añadir bloque</h3>
                            <p class="small" id="builderInsertHint" style="margin:0">Elige una plantilla base y el CMS la insertará en la posición seleccionada.</p>
                          </div>
                          <div class="builder-library-grid" id="builderTemplateGrid"></div>
                        </div>
                        <div>
                          <h3 style="margin:0 0 10px">Bloques de la cápsula</h3>
                          <div id="builderList" class="builder-list"></div>
                        </div>
                      </div>
                    </section>

                    <section class="subpanel" data-tab-panel="seo">
                      <div class="split-2">
                        <div class="field"><label>Título</label><input id="page_title" name="title" value="<?= ccms_h((string) $selectedPage['title']) ?>"></div>
                        <div class="field"><label>Slug</label><input id="page_slug" name="slug" value="<?= ccms_h((string) $selectedPage['slug']) ?>"></div>
                        <div class="field"><label>Texto de menú</label><input id="menu_label" name="menu_label" value="<?= ccms_h((string) $selectedPage['menu_label']) ?>"></div>
                        <div class="field"><label>Meta title</label><input name="meta_title" value="<?= ccms_h((string) $selectedPage['meta_title']) ?>"></div>
                      </div>
                      <div class="field"><label>Meta description</label><textarea name="meta_description"><?= ccms_h((string) $selectedPage['meta_description']) ?></textarea></div>
                      <div class="check-grid">
                        <label class="check"><input type="checkbox" name="show_in_menu" <?= !empty($selectedPage['show_in_menu']) ? 'checked' : '' ?>> Mostrar esta página en el menú principal</label>
                        <label class="check"><input type="checkbox" name="is_homepage" <?= !empty($selectedPage['is_homepage']) ? 'checked' : '' ?>> Usar como homepage del sitio</label>
                      </div>
                    </section>

                    <section class="subpanel" data-tab-panel="capsule">
                      <div class="help-box">
                        <h4>JSON avanzado</h4>
                        <ul>
                          <li>Guarda aquí la versión estructurada si la página vino del builder original.</li>
                          <li>No es obligatorio para publicar en este CMS, pero ayuda a conservar trazabilidad.</li>
                        </ul>
                      </div>
                      <div class="field"><label>JSON avanzado</label><textarea name="capsule_json" class="html-editor" style="min-height:420px"><?= ccms_h((string) $selectedPage['capsule_json']) ?></textarea></div>
                    </section>

                    <section class="subpanel" data-tab-panel="publish">
                      <div class="split-2">
                        <div class="field">
                          <label>Estado</label>
                          <select name="status">
                            <option value="draft" <?= ($selectedPage['status'] ?? '') === 'draft' ? 'selected' : '' ?>>Borrador</option>
                            <option value="published" <?= ($selectedPage['status'] ?? '') === 'published' ? 'selected' : '' ?>>Publicado</option>
                            <option value="scheduled" <?= ($selectedPage['status'] ?? '') === 'scheduled' ? 'selected' : '' ?>>Programado</option>
                          </select>
                        </div>
                        <div class="field">
                          <label>Fecha de publicación</label>
                          <input type="datetime-local" name="published_at" value="<?= ccms_h($selectedPagePublishedAt) ?>">
                        </div>
                      </div>
                      <div class="help-box">
                        <h4>Publicación</h4>
                        <ul>
                          <li><strong>Borrador</strong>: la página no se publica.</li>
                          <li><strong>Publicado</strong>: aparece en la web y puede salir en el menú.</li>
                          <li><strong>Programado</strong>: se publicará automáticamente cuando llegue la fecha indicada.</li>
                        </ul>
                      </div>
                      <div class="toolbar">
                        <button class="btn" type="submit"><?= ccms_icon('save', 16) ?>Guardar página</button>
                      </div>
                      <div class="help-box">
                        <h4>Consejo editorial</h4>
                        <ul>
                          <li>Guarda a menudo. Cada guardado crea una revisión recuperable.</li>
                          <li>Duplica una página si quieres crear una variante sin tocar la original.</li>
                        </ul>
                      </div>
                    </section>
                  </div>
                </div>
              </div>
            </div>

            <aside class="stack sticky-actions">
              <div class="card editor-card">
                <h2 style="margin-bottom:10px">Vista previa</h2>
                <p class="small">Se actualiza al guardar o al pulsar refrescar vista previa. Úsala para editar con más confianza.</p>
                <div class="preview-helper">
                  <div class="preview-helper-head">
                    <strong>Cómo editar desde la vista previa</strong>
                    <span>No hace falta tocar código: usa la propia maqueta para saltar al lugar correcto.</span>
                  </div>
                  <div class="preview-helper-list">
                    <div class="preview-helper-item"><span class="chip" style="padding:4px 10px">1</span><span><b>Haz clic en una sección</b> para seleccionarla y ver sus opciones.</span></div>
                    <div class="preview-helper-item"><span class="chip" style="padding:4px 10px">2</span><span><b>Doble clic en un texto</b> para editar títulos, párrafos o botones.</span></div>
                    <div class="preview-helper-item"><span class="chip" style="padding:4px 10px">3</span><span><b>Doble clic en una imagen</b> para abrir la biblioteca media y cambiar la foto.</span></div>
                  </div>
                </div>
                <div class="preview-shell is-loading" id="previewShell">
                  <div class="preview-skeleton" aria-hidden="true">
                    <span class="preview-skeleton-bar preview-skeleton-bar--sm"></span>
                    <span class="preview-skeleton-bar preview-skeleton-bar--lg"></span>
                    <span class="preview-skeleton-bar preview-skeleton-bar--md"></span>
                    <div class="preview-skeleton-surface"></div>
                  </div>
                  <iframe id="pagePreview" class="preview-frame" srcdoc="<?= ccms_h($previewHtml) ?>"></iframe>
                </div>
                <div class="preview-actions">
                <button class="btn btn-secondary js-refresh-preview" type="button"><?= ccms_icon('eye', 16) ?>Refrescar vista previa</button>
                  <a class="btn btn-secondary" href="<?= !empty($selectedPage['is_homepage']) ? '/' : '/' . rawurlencode((string) $selectedPage['slug']) ?>" target="_blank" rel="noopener"><?= ccms_icon('globe', 16) ?>Abrir publicada</a>
                </div>
              </div>
              <div class="card editor-card">
                <h2 style="margin-bottom:10px">Guardar y limpiar</h2>
                <div class="toolbar">
                  <button class="btn" type="submit" form="pageEditorForm"><?= ccms_icon('save', 16) ?>Guardar página</button>
                </div>
                <p class="small" style="margin-top:14px">Consejo: inserta una sección, cambia los textos directamente en el HTML y usa la vista previa para validar antes de publicar.</p>
              </div>
              <div class="card editor-card advanced-only">
                <h2 style="margin-bottom:10px">Duplicar página</h2>
                <form method="post">
                  <input type="hidden" name="action" value="duplicate_page">
                  <input type="hidden" name="csrf_token" value="<?= ccms_h($csrfToken) ?>">
                  <input type="hidden" name="page_id" value="<?= ccms_h((string) $selectedPage['id']) ?>">
                  <button class="btn btn-secondary" type="submit"><?= ccms_icon('copy', 16) ?>Crear una copia borrador</button>
                </form>
              </div>
              <div class="card editor-card advanced-only">
                <h2 style="margin-bottom:10px">Historial de revisiones</h2>
                <?php if (!empty($selectedRevisions)): ?>
                  <div class="stack">
                    <?php foreach (array_slice($selectedRevisions, 0, 8) as $revision): ?>
                      <div class="help-box" style="background:#fff">
                        <strong><?= ccms_h((string) ($revision['label'] ?? 'Revision')) ?></strong>
                        <div class="small"><?= ccms_h((string) ($revision['saved_at'] ?? '')) ?></div>
                        <form method="post" style="margin-top:10px">
                          <input type="hidden" name="action" value="restore_revision">
                          <input type="hidden" name="csrf_token" value="<?= ccms_h($csrfToken) ?>">
                          <input type="hidden" name="page_id" value="<?= ccms_h((string) $selectedPage['id']) ?>">
                          <input type="hidden" name="revision_id" value="<?= ccms_h((string) ($revision['id'] ?? '')) ?>">
                          <button class="btn btn-secondary" type="submit"><?= ccms_icon('download', 16) ?>Restaurar esta versión</button>
                        </form>
                      </div>
                    <?php endforeach; ?>
                  </div>
                <?php else: ?>
                  <p class="small">Todavía no hay revisiones guardadas para esta página.</p>
                <?php endif; ?>
              </div>
              <div class="card editor-card advanced-only">
                <h2 style="margin-bottom:10px">Eliminar página</h2>
                <form method="post" data-confirm-title="¿Eliminar esta página?" data-confirm-message="Esta acción no se puede deshacer y la página dejará de estar disponible en la web.">
                  <input type="hidden" name="action" value="delete_page">
                  <input type="hidden" name="csrf_token" value="<?= ccms_h($csrfToken) ?>">
                  <input type="hidden" name="page_id" value="<?= ccms_h((string) $selectedPage['id']) ?>">
                  <button class="btn btn-danger" type="submit"><?= ccms_icon('trash-2', 16) ?>Eliminar página</button>
                </form>
              </div>
            </aside>
          </div>
        </form>
        <?php else: ?>
        <div class="editor-layout">
          <div class="editor-main">
            <div class="metabox">
              <div class="help-box">
                <h4>Modo solo lectura</h4>
                <ul>
                  <li>Tu usuario puede revisar páginas, bloques y vista previa, pero no modificar ni publicar cambios.</li>
                  <li>Si necesitas editar contenido, pide a un <strong>owner</strong> o <strong>editor</strong> que te dé un rol con permisos.</li>
                </ul>
              </div>
              <div class="split-2" style="margin-top:16px">
                <div class="field"><label>Título</label><input value="<?= ccms_h((string) $selectedPage['title']) ?>" disabled></div>
                <div class="field"><label>Slug</label><input value="<?= ccms_h((string) $selectedPage['slug']) ?>" disabled></div>
                <div class="field"><label>Texto de menú</label><input value="<?= ccms_h((string) $selectedPage['menu_label']) ?>" disabled></div>
                <div class="field"><label>Estado</label><input value="<?= ccms_h((string) $selectedPage['status']) ?>" disabled></div>
                <div class="field"><label>Meta title</label><input value="<?= ccms_h((string) $selectedPage['meta_title']) ?>" disabled></div>
                <div class="field"><label>Actualizada</label><input value="<?= ccms_h((string) $selectedPage['updated_at']) ?>" disabled></div>
              </div>
              <div class="field" style="margin-top:14px"><label>Meta description</label><textarea disabled><?= ccms_h((string) $selectedPage['meta_description']) ?></textarea></div>
              <div class="check-grid" style="margin-top:14px">
                <label class="check"><input type="checkbox" <?= !empty($selectedPage['show_in_menu']) ? 'checked' : '' ?> disabled> Mostrar esta página en el menú principal</label>
                <label class="check"><input type="checkbox" <?= !empty($selectedPage['is_homepage']) ? 'checked' : '' ?> disabled> Página principal del sitio</label>
              </div>
            </div>
            <div class="metabox">
              <h3>HTML guardado</h3>
              <p class="small">Puedes revisar el contenido actual, pero no editarlo desde este rol.</p>
              <textarea class="html-editor" disabled><?= ccms_h((string) $selectedPage['html_content']) ?></textarea>
            </div>
            <div class="metabox">
              <h3>JSON avanzado</h3>
              <p class="small">Esta es la versión estructurada de la página, útil para auditoría o soporte.</p>
              <textarea class="html-editor" style="min-height:420px" disabled><?= ccms_h((string) $selectedPage['capsule_json']) ?></textarea>
            </div>
          </div>
          <aside class="stack sticky-actions">
            <div class="card editor-card">
              <h2 style="margin-bottom:10px">Vista previa</h2>
              <p class="small">Puedes inspeccionar la página publicada o su vista previa en este panel.</p>
              <div class="preview-shell is-loading" id="previewShell">
                <div class="preview-skeleton" aria-hidden="true">
                  <span class="preview-skeleton-bar preview-skeleton-bar--sm"></span>
                  <span class="preview-skeleton-bar preview-skeleton-bar--lg"></span>
                  <span class="preview-skeleton-bar preview-skeleton-bar--md"></span>
                  <div class="preview-skeleton-surface"></div>
                </div>
                <iframe id="pagePreview" class="preview-frame" srcdoc="<?= ccms_h($previewHtml) ?>"></iframe>
              </div>
              <div class="preview-actions">
                <a class="btn btn-secondary" href="<?= !empty($selectedPage['is_homepage']) ? '/' : '/' . rawurlencode((string) $selectedPage['slug']) ?>" target="_blank" rel="noopener"><?= ccms_icon('globe', 16) ?>Abrir publicada</a>
              </div>
            </div>
            <div class="card editor-card">
              <h2 style="margin-bottom:10px">Historial de revisiones</h2>
              <?php if (!empty($selectedRevisions)): ?>
                <div class="stack">
                  <?php foreach (array_slice($selectedRevisions, 0, 8) as $revision): ?>
                    <div class="help-box" style="background:#fff">
                      <strong><?= ccms_h((string) ($revision['label'] ?? 'Revision')) ?></strong>
                      <div class="small"><?= ccms_h((string) ($revision['saved_at'] ?? '')) ?></div>
                    </div>
                  <?php endforeach; ?>
                </div>
              <?php else: ?>
                <p class="small">Todavía no hay revisiones guardadas para esta página.</p>
              <?php endif; ?>
            </div>
          </aside>
        </div>
        <?php endif; ?>
      </div>
    <?php else: ?>
      <div class="card editor-card">
        <h2>No hay páginas todavía</h2>
        <p class="muted">Crea una primera página desde la columna izquierda para empezar.</p>
      </div>
    <?php endif; ?>
  </section>
</div>
