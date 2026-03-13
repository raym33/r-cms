<?php
declare(strict_types=1);
?>
<div class="business-stack">
  <header class="business-topbar">
    <div class="business-brand">
      <span class="business-chip">Modo negocio</span>
      <h1><?= ccms_h((string) ($data['site']['title'] ?? 'Mi negocio')) ?></h1>
      <p>Hola, <?= ccms_h((string) ($currentUser['username'] ?? '')) ?>. Aqui solo aparecen los cambios rapidos que el cliente puede tocar sin entrar al builder.</p>
    </div>
    <div class="business-actions">
      <?php if ($selectedPage): ?>
        <a class="business-btn ghost" href="<?= ccms_h(ccms_public_page_url($selectedPage)) ?>" target="_blank" rel="noopener">Ver web</a>
      <?php endif; ?>
      <?php if (ccms_user_can('pages_manage')): ?>
        <a class="business-btn secondary" href="/r-admin/?tab=pages">Abrir editor</a>
      <?php endif; ?>
      <form method="post">
        <input type="hidden" name="action" value="business_logout">
        <button class="business-btn secondary" type="submit">Salir</button>
      </form>
    </div>
  </header>

  <?php if ($pagesWithBusinessMode !== []): ?>
    <div class="business-page-switcher">
      <?php foreach ($pagesWithBusinessMode as $pageOption): ?>
        <?php $active = ($selectedPage['id'] ?? '') === ($pageOption['id'] ?? ''); ?>
        <a class="business-page-link <?= $active ? 'is-active' : '' ?>" href="/mi-negocio/?page=<?= rawurlencode((string) ($pageOption['slug'] ?? '')) ?>">
          <?= ccms_h((string) ($pageOption['title'] ?? 'Pagina')) ?>
        </a>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

  <?php if (!$selectedPage): ?>
    <div class="business-panel business-empty">Todavia no hay bloques marcados para Modo Negocio.</div>
  <?php else: ?>
    <div class="business-panel">
      <div class="business-category-head">
        <div>
          <h2><?= ccms_h((string) ($selectedPage['title'] ?? 'Pagina')) ?></h2>
          <p>Pulsa una tarjeta para editarla. Los cambios se publican al guardar y no tocan el diseno de la pagina.</p>
        </div>
        <span class="business-pill"><?= count($businessItems) ?> cambios rapidos</span>
      </div>
    </div>

    <?php if ($groupedBusinessItems === []): ?>
      <div class="business-panel business-empty">Esta pagina no tiene bloques editables para el cliente.</div>
    <?php else: ?>
      <div class="business-grid">
        <?php foreach ($groupedBusinessItems as $categoryKey => $categoryItems): ?>
          <?php $meta = ccms_quick_edit_category_meta((string) $categoryKey); ?>
          <section class="business-category business-panel">
            <div class="business-category-head">
              <div>
                <h2><?= ccms_h((string) ($meta['label'] ?? 'Categoria')) ?></h2>
                <p><?= ccms_h((string) ($meta['description'] ?? '')) ?></p>
              </div>
              <span class="business-pill"><?= count($categoryItems) ?></span>
            </div>
            <?php foreach ($categoryItems as $item): ?>
              <article class="business-item">
                <div class="business-item-meta">
                  <span class="business-pill"><?= ccms_h((string) ($item['block_type'] ?? 'bloque')) ?></span>
                  <?php if (($item['frequency'] ?? '') !== ''): ?>
                    <span class="business-pill"><?= ccms_h((string) $item['frequency']) ?></span>
                  <?php endif; ?>
                </div>
                <h3><?= ccms_h((string) ($item['label'] ?? 'Bloque editable')) ?></h3>
                <p>
                  <?php if (($item['source'] ?? '') === 'live_data'): ?>
                    Datos vivos conectados al bloque y al contenido publico.
                  <?php else: ?>
                    Textos e imagenes simples del bloque.
                  <?php endif; ?>
                </p>
                <?php if (($item['updated_at'] ?? '') !== ''): ?>
                  <p class="business-header-note">Ultima actualizacion: <?= ccms_h((string) ($item['updated_at'] ?? '')) ?></p>
                <?php endif; ?>
                <a class="business-btn" href="/mi-negocio/?page=<?= rawurlencode((string) ($selectedPage['slug'] ?? '')) ?>&edit=<?= rawurlencode((string) ($item['block_id'] ?? '')) ?>">Editar</a>
              </article>
            <?php endforeach; ?>
          </section>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  <?php endif; ?>
</div>
