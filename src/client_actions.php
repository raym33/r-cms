<?php
declare(strict_types=1);

function ccms_quick_edit_category_definitions(): array
{
    return [
        'menu' => ['label' => 'Menu del dia', 'description' => 'Actualizaciones rapidas del menu o carta.'],
        'horario' => ['label' => 'Horario', 'description' => 'Horarios de apertura y cierres puntuales.'],
        'precios' => ['label' => 'Precios', 'description' => 'Tarifas, servicios y listas de precios.'],
        'textos' => ['label' => 'Textos y fotos', 'description' => 'Titulares, textos clave e imagenes.'],
        'blog' => ['label' => 'Blog', 'description' => 'Contenido rapido y noticias.'],
        'promos' => ['label' => 'Promociones', 'description' => 'Mensajes comerciales y campañas activas.'],
    ];
}

function ccms_quick_edit_category_meta(string $category): array
{
    $definitions = ccms_quick_edit_category_definitions();
    $category = trim($category);
    if (isset($definitions[$category])) {
        return $definitions[$category] + ['key' => $category];
    }

    return [
        'key' => $category !== '' ? $category : 'textos',
        'label' => $category !== '' ? ucfirst(str_replace('_', ' ', $category)) : 'Textos y fotos',
        'description' => 'Contenido rapido para el cliente.',
    ];
}

function ccms_business_mode_item_label(array $block, array $quickEdit): string
{
    $label = trim((string) ($quickEdit['label'] ?? ''));
    if ($label !== '') {
        return $label;
    }

    foreach (['title', 'brand', 'badge'] as $key) {
        $value = trim((string) (($block['props'][$key] ?? '')));
        if ($value !== '') {
            return $value;
        }
    }

    return ucfirst(str_replace('_', ' ', (string) ($block['type'] ?? 'Bloque')));
}

function ccms_quick_edit_infer_field_keys(array $block): array
{
    $keys = [];
    foreach ((array) ($block['props'] ?? []) as $key => $value) {
        if (!is_scalar($value) && $value !== null) {
            continue;
        }
        $normalized = strtolower(trim((string) $key));
        if ($normalized === '' || in_array($normalized, ['id', 'slug', 'status'], true)) {
            continue;
        }
        if (str_contains($normalized, 'href') || str_contains($normalized, 'embed') || str_contains($normalized, 'video')) {
            continue;
        }
        if (str_ends_with($normalized, '_url') && !preg_match('/image|photo|cover|logo|icon/', $normalized)) {
            continue;
        }
        $keys[] = (string) $key;
    }

    return array_values(array_unique($keys));
}

function ccms_quick_edit_field_type(string $key, $value): string
{
    if (is_bool($value)) {
        return 'boolean';
    }
    if (is_numeric($value) && !is_string($value)) {
        return 'number';
    }
    if (preg_match('/image|photo|cover|logo|icon/', strtolower($key))) {
        return 'image';
    }
    if (is_string($value) && (strlen($value) > 100 || preg_match('/subtitle|text|description|privacy|info|note|quote/', strtolower($key)))) {
        return 'textarea';
    }
    return 'text';
}

function ccms_quick_edit_field_label(string $key): string
{
    return ucfirst(str_replace('_', ' ', trim($key)));
}

function ccms_business_mode_item_fields(array $block, array $quickEdit): array
{
    $keys = $quickEdit['fields'] ?? [];
    if ($keys === []) {
        $keys = ccms_quick_edit_infer_field_keys($block);
    }

    $fields = [];
    foreach ($keys as $key) {
        $value = $block['props'][$key] ?? null;
        if ((!is_scalar($value) && $value !== null) || !array_key_exists($key, (array) ($block['props'] ?? []))) {
            continue;
        }
        $fields[] = [
            'key' => (string) $key,
            'label' => ccms_quick_edit_field_label((string) $key),
            'type' => ccms_quick_edit_field_type((string) $key, $value),
            'value' => $value,
        ];
    }

    return $fields;
}

function ccms_business_mode_collect_items(array $page, array $liveData): array
{
    $capsule = ccms_capsule_decode($page);
    if (!ccms_capsule_can_render($capsule)) {
        return [];
    }

    $liveData = ccms_normalize_live_data_structure($liveData);
    $items = [];
    foreach (($capsule['blocks'] ?? []) as $index => $block) {
        $quickEdit = ccms_normalize_capsule_quick_edit($block['quick_edit'] ?? [], $block);
        if (!$quickEdit['enabled']) {
            continue;
        }
        $category = ccms_quick_edit_category_meta((string) ($quickEdit['category'] ?? 'textos'));
        $baseItem = [
            'block_id' => (string) ($block['id'] ?? ''),
            'block_type' => (string) ($block['type'] ?? ''),
            'block_index' => $index,
            'label' => ccms_business_mode_item_label($block, $quickEdit),
            'category' => $category['key'],
            'category_label' => $category['label'],
            'category_description' => $category['description'],
            'source' => (string) ($quickEdit['source'] ?? 'capsule'),
            'frequency' => (string) ($quickEdit['frequency'] ?? ''),
            'quick_edit' => $quickEdit,
        ];

        if ($baseItem['source'] === 'live_data') {
            $slotKey = trim((string) ($quickEdit['slot'] ?? ''));
            $slotType = (string) ($block['type'] ?? '');
            if ($slotKey === '' || !in_array($slotType, ccms_live_data_slot_types(), true)) {
                continue;
            }
            $slot = is_array($liveData['slots'][$slotKey] ?? null) ? $liveData['slots'][$slotKey] : null;
            $items[] = $baseItem + [
                'slot_key' => $slotKey,
                'slot_type' => $slotType,
                'updated_at' => (string) ($slot['updated_at'] ?? ''),
                'payload' => array_replace_recursive(
                    ccms_live_data_default_payload($slotType),
                    ccms_normalize_live_data_payload($slotType, $block['props'] ?? []),
                    $slot ? ccms_normalize_live_data_payload($slotType, $slot['payload'] ?? []) : []
                ),
                'fields' => [],
            ];
            continue;
        }

        $fields = ccms_business_mode_item_fields($block, $quickEdit);
        if ($fields === []) {
            continue;
        }
        $items[] = $baseItem + [
            'slot_key' => '',
            'slot_type' => '',
            'updated_at' => '',
            'payload' => [],
            'fields' => $fields,
        ];
    }

    return $items;
}

function ccms_business_mode_pages(array $data): array
{
    $pages = [];
    foreach ((array) ($data['pages'] ?? []) as $page) {
        if (!is_array($page)) {
            continue;
        }
        $items = ccms_business_mode_collect_items($page, $data['live_data'] ?? []);
        if ($items === []) {
            continue;
        }
        $page['_business_items'] = $items;
        $pages[] = $page;
    }

    return $pages;
}

function ccms_business_mode_selected_page(array $data): ?array
{
    $pages = ccms_business_mode_pages($data);
    if ($pages === []) {
        return null;
    }

    $requested = trim((string) ($_GET['page'] ?? ''));
    if ($requested !== '') {
        foreach ($pages as $page) {
            if (($page['slug'] ?? '') === $requested || ($page['id'] ?? '') === $requested) {
                return $page;
            }
        }
    }

    $homepage = ccms_homepage($data);
    if ($homepage) {
        foreach ($pages as $page) {
            if (($page['id'] ?? '') === ($homepage['id'] ?? '')) {
                return $page;
            }
        }
    }

    return $pages[0];
}

function ccms_business_mode_find_item(array $page, array $liveData, string $blockId): ?array
{
    foreach (ccms_business_mode_collect_items($page, $liveData) as $item) {
        if (($item['block_id'] ?? '') === $blockId) {
            return $item;
        }
    }
    return null;
}

function ccms_require_business_mode_user(): array
{
    $user = ccms_current_admin();
    if (!$user) {
        ccms_redirect('/mi-negocio/');
    }
    if (!ccms_user_can_access_business_mode($user)) {
        throw new RuntimeException('Tu cuenta no tiene acceso a Modo Negocio.');
    }
    return $user;
}

function ccms_business_mode_lines(string $value): array
{
    return array_values(array_filter(array_map(static function ($item): string {
        return trim((string) $item);
    }, preg_split('/\r\n|\r|\n/', $value) ?: []), static function (string $item): bool {
        return $item !== '';
    }));
}

function ccms_business_mode_live_payload_from_post(string $type, array $post): array
{
    if ($type === 'menu_daily') {
        $sectionNames = is_array($post['section_name'] ?? null) ? $post['section_name'] : [];
        $sectionItems = is_array($post['section_items'] ?? null) ? $post['section_items'] : [];
        $sections = [];
        foreach ($sectionNames as $index => $sectionName) {
            $name = trim((string) $sectionName);
            $items = ccms_business_mode_lines((string) ($sectionItems[$index] ?? ''));
            if ($name === '' && $items === []) {
                continue;
            }
            $sections[] = [
                'name' => $name !== '' ? $name : 'Seccion',
                'items' => $items,
            ];
        }

        return [
            'price' => trim((string) ($post['price'] ?? '')),
            'currency' => trim((string) ($post['currency'] ?? 'EUR')),
            'includes' => trim((string) ($post['includes'] ?? '')),
            'sections' => $sections,
        ];
    }

    if ($type === 'hours_status') {
        $days = [];
        foreach (ccms_business_hours_day_keys() as $day) {
            $slots = [];
            foreach ([1, 2] as $slotIndex) {
                $open = trim((string) ($post['hours'][$day]['open_' . $slotIndex] ?? ''));
                $close = trim((string) ($post['hours'][$day]['close_' . $slotIndex] ?? ''));
                if ($open === '' && $close === '') {
                    continue;
                }
                $slots[] = ['open' => $open, 'close' => $close];
            }
            $days[$day] = [
                'closed' => !empty($post['hours'][$day]['closed']),
                'slots' => $slots,
            ];
        }

        return [
            'timezone' => trim((string) ($post['timezone'] ?? date_default_timezone_get())),
            'closed_today' => !empty($post['closed_today']),
            'closure_label' => trim((string) ($post['closure_label'] ?? '')),
            'reopens_on' => trim((string) ($post['reopens_on'] ?? '')),
            'days' => $days,
        ];
    }

    if ($type === 'price_list') {
        $itemNames = is_array($post['item_name'] ?? null) ? $post['item_name'] : [];
        $itemPrices = is_array($post['item_price'] ?? null) ? $post['item_price'] : [];
        $itemDetails = is_array($post['item_detail'] ?? null) ? $post['item_detail'] : [];
        $items = [];
        foreach ($itemNames as $index => $itemName) {
            $name = trim((string) $itemName);
            $price = trim((string) ($itemPrices[$index] ?? ''));
            $detail = trim((string) ($itemDetails[$index] ?? ''));
            if ($name === '' && $price === '' && $detail === '') {
                continue;
            }
            $items[] = [
                'name' => $name,
                'price' => $price,
                'detail' => $detail,
            ];
        }

        return [
            'currency' => trim((string) ($post['currency'] ?? 'EUR')),
            'note' => trim((string) ($post['note'] ?? '')),
            'items' => $items,
        ];
    }

    return [];
}

function ccms_business_mode_handle_post(): void
{
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
        return;
    }

    $action = trim((string) ($_POST['action'] ?? ''));
    if ($action === 'business_login') {
        ccms_verify_same_origin_request();
        $username = trim((string) ($_POST['username'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');
        if (!ccms_login($username, $password)) {
            throw new RuntimeException('Usuario o contrasena incorrectos.');
        }
        if (ccms_pending_2fa()) {
            ccms_flash('success', 'Contrasena correcta. Completa ahora el codigo 2FA.');
            ccms_redirect('/r-admin/?step=2fa');
        }
        $loggedUser = ccms_current_admin();
        if (!$loggedUser || !ccms_user_can_access_business_mode($loggedUser)) {
            ccms_logout();
            throw new RuntimeException('Tu cuenta no tiene acceso a Modo Negocio.');
        }
        ccms_flash('success', 'Sesion iniciada.');
        ccms_redirect('/mi-negocio/');
    }

    if ($action === 'business_logout') {
        ccms_logout();
        ccms_redirect('/mi-negocio/');
    }

    $user = ccms_require_business_mode_user();
    ccms_verify_same_origin_request();
    ccms_verify_csrf();
    $data = ccms_load_data();

    if (!empty($user['must_change_password']) && $action !== 'business_change_password') {
        throw new RuntimeException('Debes cambiar tu contrasena temporal antes de continuar.');
    }

    if ($action === 'business_change_password') {
        $newPassword = (string) ($_POST['new_password'] ?? '');
        $confirmPassword = (string) ($_POST['confirm_new_password'] ?? '');
        if (strlen($newPassword) < 10) {
            throw new RuntimeException('La nueva contrasena debe tener al menos 10 caracteres.');
        }
        if ($newPassword !== $confirmPassword) {
            throw new RuntimeException('Las contrasenas no coinciden.');
        }
        $index = ccms_find_user_index($data, (string) ($user['id'] ?? ''));
        if ($index === null) {
            throw new RuntimeException('Usuario no encontrado.');
        }
        $data['users'][$index]['password_hash'] = password_hash($newPassword, PASSWORD_DEFAULT);
        $data['users'][$index]['must_change_password'] = false;
        $data['users'][$index]['updated_at'] = ccms_now_iso();
        ccms_push_audit_log($data, 'business.password_changed', 'Business mode password changed', $data['users'][$index]);
        ccms_save_data($data);
        $_SESSION['ccms_admin']['must_change_password'] = false;
        ccms_flash('success', 'Contrasena actualizada.');
        ccms_redirect('/mi-negocio/');
    }

    if ($action !== 'business_save_item') {
        throw new RuntimeException('Accion no valida.');
    }

    $pageId = trim((string) ($_POST['page_id'] ?? ''));
    $blockId = trim((string) ($_POST['block_id'] ?? ''));
    $pageIndex = ccms_find_page_index($data, $pageId);
    if ($pageIndex === null) {
        throw new RuntimeException('Pagina no encontrada.');
    }

    $page = $data['pages'][$pageIndex];
    $item = ccms_business_mode_find_item($page, $data['live_data'] ?? [], $blockId);
    if (!$item) {
        throw new RuntimeException('Bloque editable no encontrado.');
    }

    if (($item['source'] ?? '') === 'live_data') {
        $slotKey = (string) ($item['slot_key'] ?? '');
        $slotType = (string) ($item['slot_type'] ?? '');
        $payload = ccms_normalize_live_data_payload($slotType, ccms_business_mode_live_payload_from_post($slotType, $_POST));
        $data['live_data'] = ccms_normalize_live_data_structure($data['live_data'] ?? []);
        $data['live_data']['slots'][$slotKey] = [
            'type' => $slotType,
            'updated_at' => ccms_now_iso(),
            'payload' => $payload,
        ];
        ccms_push_audit_log($data, 'business.live_data_saved', 'Business mode live data updated', $user, [
            'page_slug' => (string) ($page['slug'] ?? ''),
            'slot' => $slotKey,
            'type' => $slotType,
        ]);
    } else {
        $capsule = ccms_capsule_decode($page);
        if (!$capsule) {
            throw new RuntimeException('La pagina no usa una capsula editable.');
        }
        foreach (($capsule['blocks'] ?? []) as $index => $block) {
            if (($block['id'] ?? '') !== $blockId) {
                continue;
            }
            foreach (($item['fields'] ?? []) as $field) {
                $key = (string) ($field['key'] ?? '');
                $type = (string) ($field['type'] ?? 'text');
                if ($key === '') {
                    continue;
                }
                if ($type === 'boolean') {
                    $capsule['blocks'][$index]['props'][$key] = !empty($_POST['field'][$key]);
                } elseif ($type === 'number') {
                    $capsule['blocks'][$index]['props'][$key] = trim((string) ($_POST['field'][$key] ?? ''));
                } elseif ($type === 'image') {
                    $capsule['blocks'][$index]['props'][$key] = ccms_sanitize_url((string) ($_POST['field'][$key] ?? ''), true);
                } else {
                    $capsule['blocks'][$index]['props'][$key] = trim((string) ($_POST['field'][$key] ?? ''));
                }
            }
            break;
        }
        $data['pages'][$pageIndex]['capsule_json'] = json_encode($capsule, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '{}';
        $data['pages'][$pageIndex]['updated_at'] = ccms_now_iso();
        ccms_push_audit_log($data, 'business.capsule_saved', 'Business mode capsule fields updated', $user, [
            'page_slug' => (string) ($page['slug'] ?? ''),
            'block_id' => $blockId,
        ]);
    }

    ccms_save_data($data);
    ccms_flash('success', 'Cambios guardados.');
    ccms_redirect('/mi-negocio/?page=' . rawurlencode((string) ($page['slug'] ?? '')) . '&edit=' . rawurlencode($blockId));
}
