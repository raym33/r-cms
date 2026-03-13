<?php
declare(strict_types=1);
$item = $selectedBusinessItem;
$payload = is_array($item['payload'] ?? null) ? $item['payload'] : [];
?>
<div class="business-stack">
  <header class="business-topbar">
    <div class="business-brand">
      <span class="business-chip"><?= ccms_h((string) ($item['category_label'] ?? 'Editar')) ?></span>
      <h1><?= ccms_h((string) ($item['label'] ?? 'Bloque editable')) ?></h1>
      <p><?= ccms_h((string) ($selectedPage['title'] ?? 'Pagina')) ?> · <?= ccms_h((string) ($item['block_type'] ?? 'bloque')) ?></p>
    </div>
    <div class="business-actions">
      <a class="business-btn ghost" href="/mi-negocio/?page=<?= rawurlencode((string) ($selectedPage['slug'] ?? '')) ?>">Volver</a>
      <a class="business-btn secondary" href="<?= ccms_h(ccms_public_page_url($selectedPage)) ?>" target="_blank" rel="noopener">Ver pagina</a>
    </div>
  </header>

  <div class="business-panel business-stack">
    <div class="business-category-head">
      <div>
        <h2>Editar ahora</h2>
        <p>Guarda y la web publica se actualizara con este contenido.</p>
      </div>
      <?php if (($item['updated_at'] ?? '') !== ''): ?>
        <span class="business-pill"><?= ccms_h((string) ($item['updated_at'] ?? '')) ?></span>
      <?php endif; ?>
    </div>

    <form method="post" class="business-form">
      <input type="hidden" name="action" value="business_save_item">
      <input type="hidden" name="csrf_token" value="<?= ccms_h($csrfToken) ?>">
      <input type="hidden" name="page_id" value="<?= ccms_h((string) ($selectedPage['id'] ?? '')) ?>">
      <input type="hidden" name="block_id" value="<?= ccms_h((string) ($item['block_id'] ?? '')) ?>">

      <?php if (($item['source'] ?? '') === 'live_data' && ($item['slot_type'] ?? '') === 'menu_daily'): ?>
        <div class="business-split">
          <div class="business-field">
            <label>Precio</label>
            <input name="price" value="<?= ccms_h((string) ($payload['price'] ?? '')) ?>">
          </div>
          <div class="business-field">
            <label>Moneda</label>
            <input name="currency" value="<?= ccms_h((string) ($payload['currency'] ?? 'EUR')) ?>">
          </div>
        </div>
        <div class="business-field">
          <label>Incluye</label>
          <input name="includes" value="<?= ccms_h((string) ($payload['includes'] ?? '')) ?>" placeholder="Pan y bebida">
        </div>
        <?php
          $sections = array_values(is_array($payload['sections'] ?? null) ? $payload['sections'] : []);
          while (count($sections) < 3) {
              $sections[] = ['name' => '', 'items' => []];
          }
        ?>
        <?php foreach ($sections as $index => $section): ?>
          <div class="business-field">
            <label>Seccion <?= $index + 1 ?></label>
            <input name="section_name[]" value="<?= ccms_h((string) ($section['name'] ?? '')) ?>" placeholder="Primeros / Segundos / Postres">
            <textarea name="section_items[]" placeholder="Un plato por linea"><?= ccms_h(implode("\n", array_map('strval', is_array($section['items'] ?? null) ? $section['items'] : []))) ?></textarea>
          </div>
        <?php endforeach; ?>

      <?php elseif (($item['source'] ?? '') === 'live_data' && ($item['slot_type'] ?? '') === 'price_list'): ?>
        <div class="business-split">
          <div class="business-field">
            <label>Moneda</label>
            <input name="currency" value="<?= ccms_h((string) ($payload['currency'] ?? 'EUR')) ?>">
          </div>
          <div class="business-field">
            <label>Nota final</label>
            <input name="note" value="<?= ccms_h((string) ($payload['note'] ?? '')) ?>" placeholder="IVA incluido, cita previa...">
          </div>
        </div>
        <?php
          $items = array_values(is_array($payload['items'] ?? null) ? $payload['items'] : []);
          while (count($items) < 4) {
              $items[] = ['name' => '', 'price' => '', 'detail' => ''];
          }
        ?>
        <?php foreach ($items as $index => $row): ?>
          <div class="business-slot-grid business-card">
            <div class="business-split">
              <div class="business-field">
                <label>Servicio <?= $index + 1 ?></label>
                <input name="item_name[]" value="<?= ccms_h((string) ($row['name'] ?? '')) ?>">
              </div>
              <div class="business-field">
                <label>Precio</label>
                <input name="item_price[]" value="<?= ccms_h((string) ($row['price'] ?? '')) ?>">
              </div>
            </div>
            <div class="business-field">
              <label>Detalle</label>
              <input name="item_detail[]" value="<?= ccms_h((string) ($row['detail'] ?? '')) ?>" placeholder="Duracion, condiciones o aclaracion">
            </div>
          </div>
        <?php endforeach; ?>

      <?php elseif (($item['source'] ?? '') === 'live_data' && ($item['slot_type'] ?? '') === 'hours_status'): ?>
        <div class="business-split">
          <label class="business-check">
            <input type="checkbox" name="closed_today" value="1" <?= !empty($payload['closed_today']) ? 'checked' : '' ?>>
            Cerrar hoy por una incidencia puntual
          </label>
          <div class="business-field">
            <label>Timezone</label>
            <input name="timezone" value="<?= ccms_h((string) ($payload['timezone'] ?? date_default_timezone_get())) ?>">
          </div>
        </div>
        <div class="business-split">
          <div class="business-field">
            <label>Motivo del cierre</label>
            <input name="closure_label" value="<?= ccms_h((string) ($payload['closure_label'] ?? '')) ?>" placeholder="Vacaciones / festivo / reforma">
          </div>
          <div class="business-field">
            <label>Volvemos el</label>
            <input name="reopens_on" value="<?= ccms_h((string) ($payload['reopens_on'] ?? '')) ?>" placeholder="16 agosto">
          </div>
        </div>
        <?php foreach (ccms_business_day_labels() as $dayKey => $dayLabel): ?>
          <?php $day = is_array($payload['days'][$dayKey] ?? null) ? $payload['days'][$dayKey] : ['closed' => true, 'slots' => []]; ?>
          <div class="business-day-row">
            <div class="business-stack">
              <strong><?= ccms_h($dayLabel) ?></strong>
              <label class="business-check">
                <input type="checkbox" name="hours[<?= ccms_h($dayKey) ?>][closed]" value="1" <?= !empty($day['closed']) ? 'checked' : '' ?>>
                Cerrado
              </label>
            </div>
            <div class="business-day-times">
              <?php
                $slotA = is_array($day['slots'][0] ?? null) ? $day['slots'][0] : ['open' => '', 'close' => ''];
                $slotB = is_array($day['slots'][1] ?? null) ? $day['slots'][1] : ['open' => '', 'close' => ''];
              ?>
              <div class="business-day-times-grid">
                <div class="business-field">
                  <label>Primer tramo abre</label>
                  <input name="hours[<?= ccms_h($dayKey) ?>][open_1]" value="<?= ccms_h((string) ($slotA['open'] ?? '')) ?>" placeholder="09:00">
                </div>
                <div class="business-field">
                  <label>Primer tramo cierra</label>
                  <input name="hours[<?= ccms_h($dayKey) ?>][close_1]" value="<?= ccms_h((string) ($slotA['close'] ?? '')) ?>" placeholder="14:00">
                </div>
              </div>
              <div class="business-day-times-grid">
                <div class="business-field">
                  <label>Segundo tramo abre</label>
                  <input name="hours[<?= ccms_h($dayKey) ?>][open_2]" value="<?= ccms_h((string) ($slotB['open'] ?? '')) ?>" placeholder="17:00">
                </div>
                <div class="business-field">
                  <label>Segundo tramo cierra</label>
                  <input name="hours[<?= ccms_h($dayKey) ?>][close_2]" value="<?= ccms_h((string) ($slotB['close'] ?? '')) ?>" placeholder="20:00">
                </div>
              </div>
            </div>
          </div>
        <?php endforeach; ?>

      <?php else: ?>
        <?php foreach (($item['fields'] ?? []) as $field): ?>
          <div class="business-field">
            <label><?= ccms_h((string) ($field['label'] ?? 'Campo')) ?></label>
            <?php if (($field['type'] ?? '') === 'textarea'): ?>
              <textarea name="field[<?= ccms_h((string) ($field['key'] ?? '')) ?>]"><?= ccms_h((string) ($field['value'] ?? '')) ?></textarea>
            <?php elseif (($field['type'] ?? '') === 'boolean'): ?>
              <label class="business-check">
                <input type="checkbox" name="field[<?= ccms_h((string) ($field['key'] ?? '')) ?>]" value="1" <?= !empty($field['value']) ? 'checked' : '' ?>>
                Activado
              </label>
            <?php else: ?>
              <input
                name="field[<?= ccms_h((string) ($field['key'] ?? '')) ?>]"
                value="<?= ccms_h((string) ($field['value'] ?? '')) ?>"
                <?= ($field['type'] ?? '') === 'number' ? 'type="number"' : '' ?>
                placeholder="<?= ($field['type'] ?? '') === 'image' ? 'Pega una URL o /uploads/imagen.jpg' : '' ?>"
              >
            <?php endif; ?>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>

      <div class="business-toolbar">
        <button class="business-btn" type="submit">Guardar cambios</button>
        <a class="business-btn ghost" href="/mi-negocio/?page=<?= rawurlencode((string) ($selectedPage['slug'] ?? '')) ?>">Cancelar</a>
      </div>
    </form>
  </div>
</div>
