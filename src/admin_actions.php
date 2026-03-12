<?php
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

function ccms_admin_handle_post(): void
{
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
        return;
    }

    $action = (string) ($_POST['action'] ?? '');
    $currentAdmin = ccms_current_admin();

    if (!$currentAdmin) {
        ccms_admin_handle_guest_post($action);
        throw new RuntimeException('Acción no válida.');
    }

    $currentAdmin = ccms_require_admin();
    ccms_verify_same_origin_request();
    ccms_verify_csrf();
    $data = ccms_load_data();
    $mustChangePassword = !empty($currentAdmin['must_change_password']);

    if ($mustChangePassword && $action !== 'change_own_password') {
        throw new RuntimeException('Debes cambiar tu contraseña temporal antes de continuar.');
    }

    ccms_admin_handle_authenticated_post($action, $data, $currentAdmin);
    throw new RuntimeException('Acción no válida.');
}

function ccms_admin_handle_guest_post(string $action): void
{
    if ($action === 'login') {
        ccms_verify_same_origin_request();
        $username = trim((string) ($_POST['username'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');
        if (!ccms_login($username, $password)) {
            throw new RuntimeException('Usuario o contraseña incorrectos.');
        }
        if (ccms_pending_2fa()) {
            ccms_flash('success', 'Contraseña correcta. Introduce ahora el código de tu app de autenticación.');
            ccms_redirect('/r-admin/?step=2fa');
        }
        $freshData = ccms_load_data();
        $loggedUser = ccms_current_admin();
        if ($loggedUser) {
            ccms_push_audit_log($freshData, 'auth.login', 'User logged in', $loggedUser, [
                'ip' => ccms_client_ip(),
            ]);
            ccms_save_data($freshData);
        }
        ccms_flash('success', 'Sesión iniciada.');
        ccms_redirect('/r-admin/');
    }

    if ($action === 'verify_2fa') {
        ccms_verify_same_origin_request();
        ccms_verify_csrf();
        $code = trim((string) ($_POST['totp_code'] ?? ''));
        if (!ccms_complete_pending_2fa($code)) {
            throw new RuntimeException('Código 2FA no válido.');
        }
        $freshData = ccms_load_data();
        $loggedUser = ccms_current_admin();
        if ($loggedUser) {
            ccms_push_audit_log($freshData, 'auth.login_2fa', 'User completed 2FA login', $loggedUser, [
                'ip' => ccms_client_ip(),
            ]);
            ccms_save_data($freshData);
        }
        ccms_flash('success', 'Sesión iniciada con 2FA.');
        ccms_redirect('/r-admin/');
    }

    if ($action === 'complete_password_reset') {
        ccms_verify_same_origin_request();
        ccms_verify_csrf();
        $token = trim((string) ($_POST['reset_token'] ?? ''));
        $newPassword = (string) ($_POST['new_password'] ?? '');
        $confirmPassword = (string) ($_POST['confirm_new_password'] ?? '');
        if (strlen($newPassword) < 10) {
            throw new RuntimeException('La nueva contraseña debe tener al menos 10 caracteres.');
        }
        if ($newPassword !== $confirmPassword) {
            throw new RuntimeException('Las contraseñas no coinciden.');
        }
        $data = ccms_load_data();
        $resetUser = ccms_consume_password_reset_token($data, $token, $newPassword);
        if (!$resetUser) {
            throw new RuntimeException('El enlace de recuperación no es válido o ha caducado.');
        }
        ccms_push_audit_log($data, 'auth.password_reset', 'Password reset completed', $resetUser, [
            'ip' => ccms_client_ip(),
        ]);
        ccms_save_data($data);
        ccms_flash('success', 'Contraseña restablecida. Ya puedes iniciar sesión.');
        ccms_redirect('/r-admin/');
    }
}

function ccms_admin_handle_authenticated_post(string $action, array &$data, array $currentAdmin): void
{
    switch ($action) {
        case 'start_totp_setup':
            ccms_begin_totp_setup();
            ccms_flash('success', 'Se ha generado una clave 2FA nueva. Añádela en tu app y confirma un código.');
            ccms_redirect('/r-admin/?tab=account&setup_totp=1');

        case 'cancel_totp_setup':
            ccms_clear_totp_setup();
            ccms_flash('success', 'Configuración 2FA cancelada.');
            ccms_redirect('/r-admin/?tab=account');

        case 'enable_totp':
            $setupSecret = ccms_totp_setup_secret();
            if (!$setupSecret) {
                throw new RuntimeException('Primero debes generar una clave de configuración 2FA.');
            }
            $code = trim((string) ($_POST['totp_code'] ?? ''));
            if (!ccms_verify_totp_code($setupSecret, $code)) {
                throw new RuntimeException('El código de verificación no es válido.');
            }
            $selfIndex = ccms_find_user_index($data, (string) ($currentAdmin['id'] ?? ''));
            if ($selfIndex === null) {
                throw new RuntimeException('Usuario no encontrado.');
            }
            $data['users'][$selfIndex]['totp_secret'] = $setupSecret;
            $data['users'][$selfIndex]['totp_enabled'] = true;
            $data['users'][$selfIndex]['updated_at'] = ccms_now_iso();
            ccms_push_audit_log($data, 'auth.2fa_enabled', 'Two-factor authentication enabled', $data['users'][$selfIndex]);
            ccms_save_data($data);
            ccms_clear_totp_setup();
            $_SESSION['ccms_admin']['totp_enabled'] = true;
            ccms_flash('success', '2FA activado correctamente.');
            ccms_redirect('/r-admin/?tab=account');

        case 'disable_totp':
            $currentPassword = (string) ($_POST['current_password'] ?? '');
            $selfIndex = ccms_find_user_index($data, (string) ($currentAdmin['id'] ?? ''));
            if ($selfIndex === null) {
                throw new RuntimeException('Usuario no encontrado.');
            }
            if (!password_verify($currentPassword, (string) ($data['users'][$selfIndex]['password_hash'] ?? ''))) {
                throw new RuntimeException('La contraseña actual no es correcta.');
            }
            $data['users'][$selfIndex]['totp_secret'] = '';
            $data['users'][$selfIndex]['totp_enabled'] = false;
            $data['users'][$selfIndex]['updated_at'] = ccms_now_iso();
            ccms_push_audit_log($data, 'auth.2fa_disabled', 'Two-factor authentication disabled', $data['users'][$selfIndex]);
            ccms_save_data($data);
            ccms_clear_totp_setup();
            $_SESSION['ccms_admin']['totp_enabled'] = false;
            ccms_flash('success', '2FA desactivado.');
            ccms_redirect('/r-admin/?tab=account');

        case 'change_own_password':
            $newPassword = (string) ($_POST['new_password'] ?? '');
            $confirmPassword = (string) ($_POST['confirm_new_password'] ?? '');
            if (strlen($newPassword) < 10) {
                throw new RuntimeException('La nueva contraseña debe tener al menos 10 caracteres.');
            }
            if ($newPassword !== $confirmPassword) {
                throw new RuntimeException('Las contraseñas no coinciden.');
            }
            $selfIndex = ccms_find_user_index($data, (string) ($currentAdmin['id'] ?? ''));
            if ($selfIndex === null) {
                throw new RuntimeException('Usuario no encontrado.');
            }
            $data['users'][$selfIndex]['password_hash'] = password_hash($newPassword, PASSWORD_DEFAULT);
            $data['users'][$selfIndex]['must_change_password'] = false;
            $data['users'][$selfIndex]['updated_at'] = ccms_now_iso();
            ccms_push_audit_log($data, 'auth.password_changed', 'Password changed', $data['users'][$selfIndex], [
                'forced' => true,
            ]);
            ccms_save_data($data);
            $_SESSION['ccms_admin']['must_change_password'] = false;
            ccms_flash('success', 'Contraseña actualizada.');
            ccms_redirect('/r-admin/?tab=account');

        case 'save_site':
            ccms_require_capability('site_manage');
            $data['site']['title'] = trim((string) ($_POST['site_title'] ?? '')) ?: $data['site']['title'];
            $data['site']['tagline'] = trim((string) ($_POST['site_tagline'] ?? ''));
            $data['site']['footer_text'] = trim((string) ($_POST['footer_text'] ?? ''));
            $data['site']['contact_email'] = trim((string) ($_POST['contact_email'] ?? ''));
            $themePreset = trim((string) ($_POST['theme_preset'] ?? 'warm'));
            if (!in_array($themePreset, ['warm', 'editorial', 'minimal', 'bold'], true)) {
                $themePreset = 'warm';
            }
            $data['site']['theme_preset'] = $themePreset;
            $data['site']['custom_css'] = ccms_sanitize_custom_css(trim((string) ($_POST['custom_css'] ?? '')));
            $data['site']['colors'] = [
                'bg' => trim((string) ($_POST['color_bg'] ?? '#f7f4ee')),
                'surface' => trim((string) ($_POST['color_surface'] ?? '#ffffff')),
                'text' => trim((string) ($_POST['color_text'] ?? '#2f241f')),
                'muted' => trim((string) ($_POST['color_muted'] ?? '#6b5b53')),
                'primary' => trim((string) ($_POST['color_primary'] ?? '#c86f5c')),
                'secondary' => trim((string) ($_POST['color_secondary'] ?? '#d9c4b3')),
            ];
            ccms_push_audit_log($data, 'site.updated', 'Site settings updated', $currentAdmin, [
                'contact_email' => $data['site']['contact_email'],
                'theme_preset' => $data['site']['theme_preset'],
            ]);
            ccms_save_data($data);
            ccms_flash('success', 'Configuración del sitio guardada.');
            ccms_redirect('/r-admin/?tab=site');

        case 'save_plugins':
            ccms_require_capability('site_manage');
            $availablePlugins = ccms_discover_plugins();
            $requestedPlugins = array_values(array_filter(array_map('strval', is_array($_POST['enabled_plugins'] ?? null) ? $_POST['enabled_plugins'] : [])));
            $data['site']['trusted_plugins_enabled'] = isset($_POST['trusted_plugins_enabled']);
            $data['site']['enabled_plugins'] = array_values(array_filter($requestedPlugins, static function (string $slug) use ($availablePlugins): bool {
                return isset($availablePlugins[$slug]);
            }));
            ccms_push_audit_log($data, 'site.plugins_updated', 'Site plugins updated', $currentAdmin, [
                'trusted_plugins_enabled' => !empty($data['site']['trusted_plugins_enabled']),
                'enabled_plugins' => $data['site']['enabled_plugins'],
            ]);
            ccms_save_data($data);
            ccms_flash('success', 'Extensiones guardadas.');
            ccms_redirect('/r-admin/?tab=extensions');

        case 'save_ai_settings':
            ccms_require_capability('ai_generate');
            $data['local_ai'] = ccms_ai_settings_input($_POST);
            ccms_push_audit_log($data, 'ai.settings_updated', 'LM Studio settings updated', $currentAdmin, [
                'endpoint' => $data['local_ai']['endpoint'] ?? '',
                'model' => $data['local_ai']['model'] ?? '',
            ]);
            ccms_save_data($data);
            ccms_flash('success', 'Configuración local de LM Studio guardada.');
            ccms_redirect('/r-admin/?tab=studio');

        case 'probe_ai':
            ccms_require_capability('ai_generate');
            $settings = ccms_ai_settings_input($_POST);
            $data['local_ai'] = $settings;
            ccms_save_data($data);
            $probe = ccms_ai_probe($settings);
            if (empty($probe['models'])) {
                throw new RuntimeException('LM Studio responde, pero no ha devuelto modelos disponibles.');
            }
            ccms_flash('success', 'LM Studio responde. Modelos detectados: ' . implode(', ', $probe['models']));
            ccms_redirect('/r-admin/?tab=studio');

        case 'ai_generate_page':
            ccms_require_capability('ai_generate');
            $brief = [
                'business_name' => trim((string) ($_POST['business_name'] ?? '')),
                'page_title' => trim((string) ($_POST['page_title'] ?? '')),
                'slug' => trim((string) ($_POST['page_slug'] ?? '')),
                'industry' => trim((string) ($_POST['industry'] ?? 'generic')),
                'offer' => trim((string) ($_POST['offer'] ?? '')),
                'audience' => trim((string) ($_POST['audience'] ?? '')),
                'goal' => trim((string) ($_POST['goal'] ?? '')),
                'cta_text' => trim((string) ($_POST['cta_text'] ?? '')),
                'tone' => trim((string) ($_POST['tone'] ?? '')),
                'notes' => trim((string) ($_POST['notes'] ?? '')),
            ];
            if ($brief['business_name'] === '' || $brief['offer'] === '' || $brief['goal'] === '') {
                throw new RuntimeException('Para generar la web hacen falta al menos nombre del negocio, oferta y objetivo.');
            }
            $settings = ccms_ai_settings_input($_POST);
            $payload = ccms_ai_generate_payload($brief, $settings);
            $pageRecord = ccms_ai_page_record_from_payload($payload, $data['pages'], isset($_POST['set_as_homepage']));
            ccms_push_page_revision($pageRecord, 'Generated by LinuxCMS Studio');
            $data['pages'][] = $pageRecord;
            if (isset($_POST['apply_site_branding']) && is_array($payload['site'] ?? null)) {
                $data['site']['title'] = trim((string) ($payload['site']['title'] ?? $data['site']['title'])) ?: $data['site']['title'];
                $data['site']['tagline'] = trim((string) ($payload['site']['tagline'] ?? $data['site']['tagline']));
                $data['site']['footer_text'] = trim((string) ($payload['site']['footer_text'] ?? $data['site']['footer_text']));
                $data['site']['contact_email'] = trim((string) ($payload['site']['contact_email'] ?? $data['site']['contact_email']));
                if (is_array($payload['site']['colors'] ?? null)) {
                    $data['site']['colors'] = array_merge($data['site']['colors'] ?? [], $payload['site']['colors']);
                }
            }
            if (!empty($pageRecord['is_homepage'])) {
                foreach ($data['pages'] as $otherIndex => $page) {
                    if (($page['id'] ?? '') !== ($pageRecord['id'] ?? '')) {
                        $data['pages'][$otherIndex]['is_homepage'] = false;
                    }
                }
            }
            $modeLabel = (($payload['_meta']['mode'] ?? '') === 'lm_studio') ? 'LM Studio' : 'fallback local';
            ccms_push_audit_log($data, 'ai.page_generated', 'Page generated from Studio', $currentAdmin, [
                'page_slug' => $pageRecord['slug'] ?? '',
                'mode' => $payload['_meta']['mode'] ?? '',
            ]);
            ccms_save_data($data);
            ccms_flash('success', 'Página generada con ' . $modeLabel . '. Ya puedes pulirla desde Páginas.');
            ccms_redirect('/r-admin/?tab=pages&page=' . rawurlencode((string) $pageRecord['slug']));

        case 'create_page':
            ccms_require_capability('pages_manage');
            $title = trim((string) ($_POST['title'] ?? ''));
            if ($title === '') {
                throw new RuntimeException('El título es obligatorio.');
            }
            $slug = ccms_slugify((string) ($_POST['slug'] ?? $title));
            $newPage = [
                'id' => ccms_next_id('page'),
                'title' => $title,
                'slug' => $slug,
                'status' => 'draft',
                'is_homepage' => false,
                'show_in_menu' => true,
                'menu_label' => $title,
                'meta_title' => $title,
                'meta_description' => '',
                'capsule_json' => "{\n  \"meta\": {\n    \"business_name\": \"" . addslashes($title) . "\"\n  },\n  \"blocks\": []\n}",
                'html_content' => '<section style="padding:64px 32px"><div style="max-width:960px;margin:0 auto"><span style="display:inline-block;padding:8px 14px;border-radius:999px;background:#f1e4dc;color:#8b5c4e;font-weight:700">Nueva sección</span><h1 style="font-size:48px;line-height:1.05;margin:18px 0 14px">Edita esta página con el panel visual</h1><p style="font-size:18px;line-height:1.7;color:#6b5b53">Empieza cambiando este texto, añade una imagen o inserta una sección desde la biblioteca.</p></div></section>',
                'created_at' => ccms_now_iso(),
                'updated_at' => ccms_now_iso(),
                'revisions' => [],
            ];
            ccms_push_page_revision($newPage, 'Initial page');
            $data['pages'][] = $newPage;
            ccms_push_audit_log($data, 'page.created', 'Page created', $currentAdmin, [
                'page_slug' => $slug,
            ]);
            ccms_save_data($data);
            ccms_flash('success', 'Página creada.');
            ccms_redirect('/r-admin/?tab=pages&page=' . rawurlencode($slug));

        case 'save_page':
            ccms_require_capability('pages_manage');
            $pageId = (string) ($_POST['page_id'] ?? '');
            $index = ccms_find_page_index($data, $pageId);
            if ($index === null) {
                throw new RuntimeException('Página no encontrada.');
            }
            $title = trim((string) ($_POST['title'] ?? ''));
            $slug = ccms_slugify((string) ($_POST['slug'] ?? $title));
            if ($title === '') {
                throw new RuntimeException('El título es obligatorio.');
            }
            ccms_push_page_revision($data['pages'][$index], 'Before manual save');
            $data['pages'][$index]['title'] = $title;
            $data['pages'][$index]['slug'] = $slug;
            $data['pages'][$index]['menu_label'] = trim((string) ($_POST['menu_label'] ?? '')) ?: $title;
            $data['pages'][$index]['status'] = (string) ($_POST['status'] ?? 'draft') === 'published' ? 'published' : 'draft';
            $data['pages'][$index]['show_in_menu'] = isset($_POST['show_in_menu']);
            $data['pages'][$index]['is_homepage'] = isset($_POST['is_homepage']);
            $data['pages'][$index]['meta_title'] = trim((string) ($_POST['meta_title'] ?? ''));
            $data['pages'][$index]['meta_description'] = trim((string) ($_POST['meta_description'] ?? ''));
            $data['pages'][$index]['capsule_json'] = (string) ($_POST['capsule_json'] ?? '{}');
            $data['pages'][$index]['html_content'] = ccms_sanitize_html_fragment((string) ($_POST['html_content'] ?? ''));
            $data['pages'][$index]['updated_at'] = ccms_now_iso();
            if ($data['pages'][$index]['is_homepage']) {
                foreach ($data['pages'] as $otherIndex => $page) {
                    if ($otherIndex !== $index) {
                        $data['pages'][$otherIndex]['is_homepage'] = false;
                    }
                }
            }
            ccms_push_audit_log($data, 'page.saved', 'Page saved', $currentAdmin, [
                'page_slug' => $slug,
                'status' => $data['pages'][$index]['status'],
            ]);
            ccms_save_data($data);
            ccms_flash('success', 'Página guardada.');
            ccms_redirect('/r-admin/?tab=pages&page=' . rawurlencode($slug));

        case 'duplicate_page':
            ccms_require_capability('pages_manage');
            $pageId = (string) ($_POST['page_id'] ?? '');
            $index = ccms_find_page_index($data, $pageId);
            if ($index === null) {
                throw new RuntimeException('Página no encontrada.');
            }
            $source = $data['pages'][$index];
            $copy = $source;
            $copy['id'] = ccms_next_id('page');
            $copy['title'] = trim((string) $source['title']) . ' (copia)';
            $copy['slug'] = ccms_slugify((string) $source['slug'] . '-copia');
            $copy['status'] = 'draft';
            $copy['is_homepage'] = false;
            $copy['created_at'] = ccms_now_iso();
            $copy['updated_at'] = ccms_now_iso();
            $copy['revisions'] = [];
            ccms_push_page_revision($copy, 'Duplicated from ' . (string) ($source['slug'] ?? 'page'));
            $data['pages'][] = $copy;
            ccms_push_audit_log($data, 'page.duplicated', 'Page duplicated', $currentAdmin, [
                'page_slug' => $copy['slug'],
                'source_slug' => $source['slug'] ?? '',
            ]);
            ccms_save_data($data);
            ccms_flash('success', 'Página duplicada.');
            ccms_redirect('/r-admin/?tab=pages&page=' . rawurlencode((string) $copy['slug']));

        case 'restore_revision':
            ccms_require_capability('pages_manage');
            $pageId = (string) ($_POST['page_id'] ?? '');
            $revisionId = (string) ($_POST['revision_id'] ?? '');
            $index = ccms_find_page_index($data, $pageId);
            if ($index === null) {
                throw new RuntimeException('Página no encontrada.');
            }
            $page = $data['pages'][$index];
            $revisions = is_array($page['revisions'] ?? null) ? $page['revisions'] : [];
            $targetRevision = null;
            foreach ($revisions as $revision) {
                if (($revision['id'] ?? '') === $revisionId) {
                    $targetRevision = $revision;
                    break;
                }
            }
            if (!$targetRevision || !is_array($targetRevision['page'] ?? null)) {
                throw new RuntimeException('Revisión no encontrada.');
            }
            ccms_push_page_revision($data['pages'][$index], 'Before restore');
            $restored = $targetRevision['page'];
            $restored['id'] = $page['id'];
            $restored['created_at'] = $page['created_at'] ?? ccms_now_iso();
            $restored['updated_at'] = ccms_now_iso();
            $restored['revisions'] = $data['pages'][$index]['revisions'] ?? [];
            $data['pages'][$index] = $restored;
            ccms_push_audit_log($data, 'page.restored', 'Revision restored', $currentAdmin, [
                'page_slug' => $restored['slug'] ?? '',
                'revision_id' => $revisionId,
            ]);
            ccms_save_data($data);
            ccms_flash('success', 'Revisión restaurada.');
            ccms_redirect('/r-admin/?tab=pages&page=' . rawurlencode((string) $restored['slug']));

        case 'delete_page':
            ccms_require_capability('pages_manage');
            $pageId = (string) ($_POST['page_id'] ?? '');
            $index = ccms_find_page_index($data, $pageId);
            if ($index === null) {
                throw new RuntimeException('Página no encontrada.');
            }
            $deletedSlug = (string) ($data['pages'][$index]['slug'] ?? '');
            array_splice($data['pages'], $index, 1);
            ccms_push_audit_log($data, 'page.deleted', 'Page deleted', $currentAdmin, [
                'page_slug' => $deletedSlug,
            ]);
            ccms_save_data($data);
            ccms_flash('success', 'Página eliminada.');
            ccms_redirect('/r-admin/?tab=pages');

        case 'upload_media':
            ccms_require_capability('media_manage');
            ccms_hit_rate_limit('upload_media', ccms_client_ip(), 20, 300, 'Demasiadas subidas seguidas. Espera un momento antes de volver a intentarlo.');
            if (!isset($_FILES['media_file']) || !is_array($_FILES['media_file'])) {
                throw new RuntimeException('No se recibió ningún archivo.');
            }
            $file = $_FILES['media_file'];
            if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
                throw new RuntimeException('No se pudo subir el archivo.');
            }
            $original = (string) ($file['name'] ?? 'upload');
            $validatedAsset = ccms_validate_uploaded_asset(
                (string) ($file['tmp_name'] ?? ''),
                $original,
                (int) ($file['size'] ?? 0),
                [
                    'jpg' => ['image/jpeg'],
                    'jpeg' => ['image/jpeg'],
                    'png' => ['image/png'],
                    'webp' => ['image/webp'],
                    'gif' => ['image/gif'],
                ],
                8 * 1024 * 1024
            );
            $extension = (string) $validatedAsset['extension'];
            $safeFile = ccms_slugify(pathinfo($original, PATHINFO_FILENAME)) . '-' . time() . '.' . $extension;
            $target = ccms_uploads_dir() . DIRECTORY_SEPARATOR . $safeFile;
            if (!move_uploaded_file((string) $file['tmp_name'], $target)) {
                throw new RuntimeException('No se pudo mover el archivo subido.');
            }
            $data['media'][] = [
                'id' => ccms_next_id('media'),
                'filename' => $safeFile,
                'original_name' => $original,
                'url' => ccms_public_upload_url($safeFile),
                'uploaded_at' => ccms_now_iso(),
            ];
            ccms_push_audit_log($data, 'media.uploaded', 'Media uploaded', $currentAdmin, [
                'filename' => $safeFile,
            ]);
            ccms_save_data($data);
            ccms_flash('success', 'Imagen subida.');
            ccms_redirect('/r-admin/?tab=media');

        case 'quick_import':
            ccms_require_capability('import_capsules');
            $title = trim((string) ($_POST['import_title'] ?? ''));
            $slug = ccms_slugify((string) ($_POST['import_slug'] ?? $title));
            $html = ccms_sanitize_html_fragment((string) ($_POST['import_html'] ?? ''));
            $capsuleJson = trim((string) ($_POST['import_capsule_json'] ?? ''));
            if ($title === '' || trim($html) === '') {
                throw new RuntimeException('Para importar hace falta título y HTML.');
            }
            $importedPage = [
                'id' => ccms_next_id('page'),
                'title' => $title,
                'slug' => $slug,
                'status' => 'published',
                'is_homepage' => empty($data['pages']),
                'show_in_menu' => true,
                'menu_label' => $title,
                'meta_title' => $title,
                'meta_description' => '',
                'capsule_json' => $capsuleJson !== '' ? $capsuleJson : "{\n  \"meta\": {\n    \"business_name\": \"" . addslashes($title) . "\"\n  },\n  \"blocks\": []\n}",
                'html_content' => $html,
                'created_at' => ccms_now_iso(),
                'updated_at' => ccms_now_iso(),
                'revisions' => [],
            ];
            ccms_push_page_revision($importedPage, 'Imported page');
            $data['pages'][] = $importedPage;
            ccms_push_audit_log($data, 'page.imported', 'Page imported', $currentAdmin, [
                'page_slug' => $importedPage['slug'] ?? '',
            ]);
            ccms_save_data($data);
            ccms_flash('success', 'Página importada.');
            ccms_redirect('/r-admin/?tab=pages');

        case 'export_backup':
            ccms_require_capability('users_manage');
            $payload = ccms_export_backup_payload($data);
            $filename = 'linuxcms-backup-' . date('Y-m-d-His') . '.json';
            header('Content-Type: application/json; charset=utf-8');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('X-Content-Type-Options: nosniff');
            echo json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            exit;

        case 'export_static_site':
            ccms_require_capability('pages_manage');
            $build = ccms_static_export_build($data);
            $zipPath = ccms_static_export_zip($build);
            ccms_push_audit_log($data, 'site.static_exported', 'Static hosting package exported', $currentAdmin, [
                'pages' => count($build['pages'] ?? []),
                'homepage' => $build['homepage'] ?? '',
            ]);
            ccms_save_data($data);
            header('Content-Type: application/zip');
            header('Content-Disposition: attachment; filename="' . basename($zipPath) . '"');
            header('Content-Length: ' . filesize($zipPath));
            header('X-Content-Type-Options: nosniff');
            readfile($zipPath);
            exit;

        case 'import_backup':
            ccms_require_capability('users_manage');
            ccms_hit_rate_limit('import_backup', ccms_client_ip(), 6, 300, 'Demasiadas importaciones seguidas. Espera un momento antes de volver a intentarlo.');
            $rawBackup = trim((string) ($_POST['backup_json'] ?? ''));
            if ($rawBackup === '' && isset($_FILES['backup_file']) && is_array($_FILES['backup_file']) && (int) ($_FILES['backup_file']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) {
                $backupFile = $_FILES['backup_file'];
                $backupName = (string) ($backupFile['name'] ?? 'backup.json');
                $backupExt = strtolower(pathinfo($backupName, PATHINFO_EXTENSION));
                if ($backupExt !== 'json') {
                    throw new RuntimeException('El backup subido debe ser un archivo JSON.');
                }
                if ((int) ($backupFile['size'] ?? 0) > (5 * 1024 * 1024)) {
                    throw new RuntimeException('El backup supera el tamaño máximo permitido.');
                }
                $rawBackup = (string) file_get_contents((string) $backupFile['tmp_name']);
            }
            if ($rawBackup === '') {
                throw new RuntimeException('Sube un backup JSON o pega su contenido.');
            }
            ccms_assert_payload_size($rawBackup, 5 * 1024 * 1024, 'El backup');
            $payload = json_decode($rawBackup, true);
            if (!is_array($payload)) {
                throw new RuntimeException('El backup no es un JSON válido.');
            }
            $restoredData = ccms_import_backup_payload($payload);
            ccms_push_audit_log($restoredData, 'site.backup_imported', 'Backup imported', $currentAdmin, [
                'pages' => count($restoredData['pages'] ?? []),
                'media' => count($restoredData['media'] ?? []),
                'users' => count($restoredData['users'] ?? []),
            ]);
            ccms_save_data($restoredData);
            unset($_SESSION['ccms_admin']);
            ccms_flash('success', 'Backup importado. Vuelve a iniciar sesión.');
            ccms_redirect('/r-admin/');

        case 'create_user':
            ccms_require_capability('users_manage');
            $username = trim((string) ($_POST['user_username'] ?? ''));
            $email = trim((string) ($_POST['user_email'] ?? ''));
            $password = (string) ($_POST['user_password'] ?? '');
            $role = in_array((string) ($_POST['user_role'] ?? 'editor'), ['owner', 'editor', 'viewer'], true) ? (string) $_POST['user_role'] : 'editor';
            if ($username === '' || $email === '' || $password === '') {
                throw new RuntimeException('Completa usuario, email y contraseña.');
            }
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new RuntimeException('El email del usuario no es válido.');
            }
            if (strlen($password) < 10) {
                throw new RuntimeException('La contraseña del usuario debe tener al menos 10 caracteres.');
            }
            foreach (($data['users'] ?? []) as $existingUser) {
                if (($existingUser['username'] ?? '') === $username) {
                    throw new RuntimeException('Ya existe un usuario con ese nombre.');
                }
                if (($existingUser['email'] ?? '') === $email) {
                    throw new RuntimeException('Ya existe un usuario con ese email.');
                }
            }
            $data['users'][] = [
                'id' => ccms_next_id('user'),
                'username' => $username,
                'email' => $email,
                'password_hash' => password_hash($password, PASSWORD_DEFAULT),
                'role' => $role,
                'must_change_password' => true,
                'last_login_at' => null,
                'totp_secret' => '',
                'totp_enabled' => false,
                'created_at' => ccms_now_iso(),
                'updated_at' => ccms_now_iso(),
            ];
            ccms_push_audit_log($data, 'user.created', 'User created', $currentAdmin, [
                'username' => $username,
                'role' => $role,
            ]);
            ccms_save_data($data);
            ccms_flash('success', 'Usuario creado.');
            ccms_redirect('/r-admin/?tab=users');

        case 'update_user':
            ccms_require_capability('users_manage');
            $userId = (string) ($_POST['user_id'] ?? '');
            $index = ccms_find_user_index($data, $userId);
            if ($index === null) {
                throw new RuntimeException('Usuario no encontrado.');
            }
            $username = trim((string) ($_POST['user_username'] ?? ''));
            $email = trim((string) ($_POST['user_email'] ?? ''));
            $role = in_array((string) ($_POST['user_role'] ?? 'editor'), ['owner', 'editor', 'viewer'], true) ? (string) $_POST['user_role'] : 'editor';
            $password = (string) ($_POST['user_password'] ?? '');
            if ($username === '' || $email === '') {
                throw new RuntimeException('Usuario y email son obligatorios.');
            }
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new RuntimeException('El email del usuario no es válido.');
            }
            foreach (($data['users'] ?? []) as $otherIndex => $existingUser) {
                if ($otherIndex === $index) {
                    continue;
                }
                if (($existingUser['username'] ?? '') === $username) {
                    throw new RuntimeException('Ya existe un usuario con ese nombre.');
                }
                if (($existingUser['email'] ?? '') === $email) {
                    throw new RuntimeException('Ya existe un usuario con ese email.');
                }
            }
            $ownerCount = count(array_filter($data['users'] ?? [], static fn(array $user): bool => ($user['role'] ?? '') === 'owner'));
            if (($data['users'][$index]['role'] ?? '') === 'owner' && $role !== 'owner' && $ownerCount <= 1) {
                throw new RuntimeException('Debe existir al menos un owner.');
            }
            $data['users'][$index]['username'] = $username;
            $data['users'][$index]['email'] = $email;
            $data['users'][$index]['role'] = $role;
            if ($password !== '') {
                if (strlen($password) < 10) {
                    throw new RuntimeException('La nueva contraseña debe tener al menos 10 caracteres.');
                }
                $data['users'][$index]['password_hash'] = password_hash($password, PASSWORD_DEFAULT);
                $data['users'][$index]['must_change_password'] = true;
            }
            $data['users'][$index]['updated_at'] = ccms_now_iso();
            ccms_push_audit_log($data, 'user.updated', 'User updated', $currentAdmin, [
                'username' => $username,
                'role' => $role,
                'password_reset' => $password !== '',
            ]);
            ccms_save_data($data);
            ccms_flash('success', 'Usuario actualizado.');
            ccms_redirect('/r-admin/?tab=users');

        case 'create_password_reset_token':
            ccms_require_capability('users_manage');
            $userId = (string) ($_POST['user_id'] ?? '');
            $index = ccms_find_user_index($data, $userId);
            if ($index === null) {
                throw new RuntimeException('Usuario no encontrado.');
            }
            $token = ccms_create_password_reset_token($data, $data['users'][$index], $currentAdmin);
            $resetUrl = ccms_base_url() . '/r-admin/?reset=' . rawurlencode($token);
            ccms_push_audit_log($data, 'user.reset_link_created', 'Password reset link created', $currentAdmin, [
                'target_user' => $data['users'][$index]['username'] ?? '',
            ]);
            ccms_save_data($data);
            ccms_flash('success', 'Enlace de restablecimiento generado: ' . $resetUrl);
            ccms_redirect('/r-admin/?tab=users');

        case 'delete_user':
            ccms_require_capability('users_manage');
            $userId = (string) ($_POST['user_id'] ?? '');
            $index = ccms_find_user_index($data, $userId);
            if ($index === null) {
                throw new RuntimeException('Usuario no encontrado.');
            }
            if (($data['users'][$index]['id'] ?? '') === ($currentAdmin['id'] ?? '')) {
                throw new RuntimeException('No puedes borrar tu propia cuenta desde aquí.');
            }
            $ownerCount = count(array_filter($data['users'] ?? [], static fn(array $user): bool => ($user['role'] ?? '') === 'owner'));
            if (($data['users'][$index]['role'] ?? '') === 'owner' && $ownerCount <= 1) {
                throw new RuntimeException('No puedes borrar el último owner.');
            }
            $deletedUsername = (string) ($data['users'][$index]['username'] ?? '');
            array_splice($data['users'], $index, 1);
            ccms_push_audit_log($data, 'user.deleted', 'User deleted', $currentAdmin, [
                'username' => $deletedUsername,
            ]);
            ccms_save_data($data);
            ccms_flash('success', 'Usuario eliminado.');
            ccms_redirect('/r-admin/?tab=users');
    }
}
