<?php
declare(strict_types=1);
?>
<div class="stack">
  <div class="card editor-card">
    <div class="editor-header">
      <div class="editor-title">
        <div class="chip">Inbox · formularios</div>
        <h2>Contactos recibidos</h2>
        <p class="muted" style="margin:0">Aquí aparecen los leads y suscripciones que llegan desde los bloques <code>contact</code>, <code>lead_form</code> y <code>newsletter</code>.</p>
      </div>
      <div class="editor-meta">
        <?php foreach ($submissionCounts as $status => $count): ?>
          <span class="chip"><?= ccms_h(ucfirst($status)) ?> · <?= (int) $count ?></span>
        <?php endforeach; ?>
      </div>
    </div>
    <div class="search-toolbar-row">
      <div class="search-input-wrap">
        <?= ccms_icon('search', 16) ?>
        <input id="searchInbox" class="search-input search-inbox" type="search" placeholder="Buscar por nombre, email, página o contenido">
      </div>
      <select id="filterInboxStatus" class="filter-inbox-status">
        <option value="">Todos los estados</option>
        <option value="new">Nuevo</option>
        <option value="reviewed">Revisado</option>
        <option value="contacted">Contactado</option>
        <option value="archived">Archivado</option>
      </select>
    </div>
  </div>

  <?php if (empty($submissions)): ?>
    <div class="card editor-card">
      <div class="empty-state">
        <h3>No hay contactos todavía</h3>
        <p class="muted">Cuando un visitante use un formulario público, aparecerá aquí con su estado de seguimiento.</p>
      </div>
    </div>
  <?php else: ?>
    <?php foreach ($submissions as $submission): ?>
      <?php
      $submissionId = (string) ($submission['id'] ?? '');
      $status = trim((string) ($submission['status'] ?? 'new')) ?: 'new';
      $kind = trim((string) ($submission['kind'] ?? 'lead_form')) ?: 'lead_form';
      $fields = is_array($submission['fields'] ?? null) ? $submission['fields'] : [];
      $delivery = is_array($submission['delivery'] ?? null) ? $submission['delivery'] : [];
      $pageTitle = trim((string) ($submission['page_title'] ?? ''));
      $pageSlug = trim((string) ($submission['page_slug'] ?? ''));
      $searchBlob = trim(implode(' ', array_filter([
          (string) ($fields['name'] ?? ''),
          (string) ($fields['email'] ?? ''),
          (string) ($fields['message'] ?? ''),
          $pageTitle,
          $pageSlug,
          $kind,
          $status,
      ])));
      ?>
      <article
        class="card editor-card stack"
        data-submission-id="<?= ccms_h($submissionId) ?>"
        data-submission-status="<?= ccms_h($status) ?>"
        data-submission-search="<?= ccms_h($searchBlob) ?>"
      >
        <div class="editor-header">
          <div class="editor-title">
            <div class="editor-meta">
              <span class="chip"><?= ccms_h(strtoupper($kind)) ?></span>
              <span class="chip">Estado · <?= ccms_h(ucfirst($status)) ?></span>
              <span class="chip"><?= ccms_h((string) ($submission['created_at'] ?? '')) ?></span>
            </div>
            <h3 style="margin:0">
              <?= ccms_h((string) ($fields['name'] ?? $fields['email'] ?? 'Nuevo contacto')) ?>
            </h3>
            <p class="muted" style="margin:0">
              <?= $pageTitle !== '' ? ccms_h($pageTitle) : 'Página sin título' ?>
              <?php if ($pageSlug !== ''): ?>
                · <code>/<?= ccms_h($pageSlug) ?></code>
              <?php endif; ?>
            </p>
          </div>
          <form method="post" class="toolbar" style="margin:0">
            <input type="hidden" name="action" value="update_submission_status">
            <input type="hidden" name="csrf_token" value="<?= ccms_h($csrfToken) ?>">
            <input type="hidden" name="submission_id" value="<?= ccms_h($submissionId) ?>">
            <select name="submission_status">
              <?php foreach (['new' => 'Nuevo', 'reviewed' => 'Revisado', 'contacted' => 'Contactado', 'archived' => 'Archivado'] as $value => $label): ?>
                <option value="<?= ccms_h($value) ?>" <?= $status === $value ? 'selected' : '' ?>><?= ccms_h($label) ?></option>
              <?php endforeach; ?>
            </select>
            <button class="btn btn-secondary" type="submit"><?= ccms_icon('save', 16) ?>Guardar estado</button>
          </form>
        </div>

        <div class="split-2">
          <div class="stack">
            <div class="help-box">
              <h4 style="margin:0 0 8px">Datos enviados</h4>
              <dl class="stack" style="margin:0">
                <?php foreach ($fields as $fieldKey => $fieldValue): ?>
                  <div>
                    <dt class="small" style="font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:var(--muted)"><?= ccms_h(str_replace('_', ' ', $fieldKey)) ?></dt>
                    <dd style="margin:6px 0 0;white-space:pre-wrap"><?= ccms_h((string) $fieldValue) ?></dd>
                  </div>
                <?php endforeach; ?>
              </dl>
            </div>
          </div>
          <div class="stack">
            <div class="help-box">
              <h4 style="margin:0 0 8px">Entrega</h4>
              <ul class="small" style="margin:0;padding-left:18px">
                <li>Intentado: <?= !empty($delivery['attempted']) ? 'Sí' : 'No' ?></li>
                <li>Enviado: <?= !empty($delivery['sent']) ? 'Sí' : 'No' ?></li>
                <li>Canal: <?= ccms_h((string) ($delivery['channel'] ?? 'mail')) ?></li>
                <li>Destino: <?= ccms_h((string) ($delivery['target'] ?? '')) ?></li>
              </ul>
            </div>
            <div class="help-box">
              <h4 style="margin:0 0 8px">Origen</h4>
              <p class="small" style="margin:0 0 8px">Bloque: <code><?= ccms_h((string) ($submission['block_type'] ?? '')) ?></code></p>
              <p class="small" style="margin:0 0 8px">URL: <code><?= ccms_h((string) ($submission['source_url'] ?? '')) ?></code></p>
              <p class="small" style="margin:0">Actualizado: <?= ccms_h((string) ($submission['updated_at'] ?? '')) ?></p>
            </div>
          </div>
        </div>
      </article>
    <?php endforeach; ?>
  <?php endif; ?>
</div>
