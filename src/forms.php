<?php
declare(strict_types=1);

function ccms_public_form_action(): string
{
    return '/api/forms/submit';
}

function ccms_public_form_honeypot_field(): string
{
    return 'company_website';
}

function ccms_public_form_flash_set(string $blockId, string $status, string $message): void
{
    $_SESSION['ccms_public_form_flash'] = [
        'block_id' => $blockId,
        'status' => $status,
        'message' => $message,
    ];
}

function ccms_public_form_flash_for_block(string $blockId): ?array
{
    $flash = $_SESSION['ccms_public_form_flash'] ?? null;
    if (!is_array($flash) || trim((string) ($flash['block_id'] ?? '')) !== $blockId) {
        return null;
    }
    unset($_SESSION['ccms_public_form_flash']);
    return $flash;
}

function ccms_render_public_form_feedback(string $blockId): string
{
    $flash = ccms_public_form_flash_for_block($blockId);
    if (!$flash) {
        return '';
    }
    $status = (string) ($flash['status'] ?? 'success');
    $message = trim((string) ($flash['message'] ?? ''));
    if ($message === '') {
        return '';
    }
    $background = $status === 'success' ? 'rgba(168,202,186,.18)' : 'rgba(200,111,92,.14)';
    $color = $status === 'success' ? '#315c4c' : '#8b4b3e';
    return '<div class="ccms-note" style="margin:0 0 16px;padding:14px 16px;border-radius:16px;background:' . ccms_h($background) . ';color:' . ccms_h($color) . ';font-weight:700">' . ccms_h($message) . '</div>';
}

function ccms_render_public_form_hidden_inputs(array $page, string $blockType, string $blockId): string
{
    $returnPath = !empty($page['is_homepage']) ? '/' : '/' . rawurlencode((string) ($page['slug'] ?? ''));
    return
        '<input type="hidden" name="kind" value="' . ccms_h($blockType) . '">' .
        '<input type="hidden" name="page_id" value="' . ccms_h((string) ($page['id'] ?? '')) . '">' .
        '<input type="hidden" name="page_slug" value="' . ccms_h((string) ($page['slug'] ?? '')) . '">' .
        '<input type="hidden" name="page_title" value="' . ccms_h((string) ($page['title'] ?? '')) . '">' .
        '<input type="hidden" name="block_id" value="' . ccms_h($blockId) . '">' .
        '<input type="hidden" name="block_type" value="' . ccms_h($blockType) . '">' .
        '<input type="hidden" name="return_to" value="' . ccms_h($returnPath) . '">' .
        '<input type="text" name="' . ccms_h(ccms_public_form_honeypot_field()) . '" value="" tabindex="-1" autocomplete="off" style="position:absolute;left:-9999px;opacity:0;pointer-events:none" aria-hidden="true">';
}

function ccms_store_submission(array &$data, array $submission, int $max = 1000): void
{
    $data['submissions'] ??= [];
    array_unshift($data['submissions'], $submission);
    if (count($data['submissions']) > $max) {
        $data['submissions'] = array_slice($data['submissions'], 0, $max);
    }
}

function ccms_public_form_mail_subject(array $site, array $submission): string
{
    $siteTitle = trim((string) ($site['title'] ?? 'LinuxCMS'));
    $kind = match ((string) ($submission['kind'] ?? 'lead_form')) {
        'newsletter' => 'New newsletter signup',
        'contact' => 'New contact request',
        default => 'New lead form submission',
    };
    $pageTitle = trim((string) ($submission['page_title'] ?? ''));
    return $pageTitle !== '' ? "{$kind} · {$siteTitle} · {$pageTitle}" : "{$kind} · {$siteTitle}";
}

function ccms_public_form_mail_body(array $submission): string
{
    $lines = [
        'LinuxCMS form submission',
        '',
        'Kind: ' . (string) ($submission['kind'] ?? ''),
        'Page: ' . (string) ($submission['page_title'] ?? ''),
        'Slug: ' . (string) ($submission['page_slug'] ?? ''),
        'Submitted at: ' . (string) ($submission['created_at'] ?? ''),
        'Source URL: ' . (string) ($submission['source_url'] ?? ''),
        '',
        'Fields:',
    ];
    foreach ((array) ($submission['fields'] ?? []) as $key => $value) {
        $lines[] = '- ' . $key . ': ' . (string) $value;
    }
    return implode("\n", $lines);
}

function ccms_try_send_submission_mail(array $site, array $submission): array
{
    $target = trim((string) ($site['contact_email'] ?? ''));
    if ($target === '' || !filter_var($target, FILTER_VALIDATE_EMAIL)) {
        return ['attempted' => false, 'sent' => false, 'channel' => 'mail', 'target' => $target];
    }

    $replyTo = trim((string) (($submission['fields']['email'] ?? '')));
    $headers = [
        'MIME-Version: 1.0',
        'Content-Type: text/plain; charset=UTF-8',
    ];
    if ($replyTo !== '' && filter_var($replyTo, FILTER_VALIDATE_EMAIL)) {
        $headers[] = 'Reply-To: ' . $replyTo;
    }

    $sent = @mail($target, ccms_public_form_mail_subject($site, $submission), ccms_public_form_mail_body($submission), implode("\r\n", $headers));
    return ['attempted' => true, 'sent' => $sent, 'channel' => 'mail', 'target' => $target];
}

function ccms_public_form_return_path(string $value): string
{
    $value = trim($value);
    if ($value === '' || str_contains($value, '://')) {
        return '/';
    }
    return '/' . ltrim($value, '/');
}

function ccms_public_form_redirect(string $path, string $anchor = ''): never
{
    $location = ccms_public_form_return_path($path);
    if ($anchor !== '') {
        $location .= '#' . rawurlencode(ltrim($anchor, '#'));
    }
    ccms_redirect($location);
}

function ccms_handle_public_form_submission(): never
{
    if (strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET')) !== 'POST') {
        http_response_code(405);
        exit('Method not allowed');
    }

    try {
        ccms_hit_rate_limit('public_form', ccms_client_ip(), 12, 300, 'Has enviado demasiados formularios. Inténtalo otra vez en unos minutos.');
    } catch (Throwable $e) {
        ccms_public_form_flash_set(trim((string) ($_POST['block_id'] ?? '')), 'error', $e->getMessage());
        ccms_public_form_redirect((string) ($_POST['return_to'] ?? '/'), trim((string) ($_POST['block_id'] ?? '')));
    }

    $honeypot = trim((string) ($_POST[ccms_public_form_honeypot_field()] ?? ''));
    $blockId = trim((string) ($_POST['block_id'] ?? ''));
    $kind = trim((string) ($_POST['kind'] ?? 'lead_form'));
    $returnTo = (string) ($_POST['return_to'] ?? '/');
    if ($honeypot !== '') {
        ccms_public_form_flash_set($blockId, 'success', 'Thanks. Your request has been received.');
        ccms_public_form_redirect($returnTo, $blockId);
    }

    $allowedKinds = ['lead_form', 'newsletter', 'contact'];
    if (!in_array($kind, $allowedKinds, true)) {
        $kind = 'lead_form';
    }

    $fields = [
        'name' => trim(strip_tags((string) ($_POST['name'] ?? ''))),
        'email' => trim((string) ($_POST['email'] ?? '')),
        'phone' => trim(strip_tags((string) ($_POST['phone'] ?? ''))),
        'company' => trim(strip_tags((string) ($_POST['company'] ?? ''))),
        'message' => trim(strip_tags((string) ($_POST['message'] ?? ''))),
    ];
    foreach ($fields as $key => $value) {
        $fields[$key] = mb_substr($value, 0, $key === 'message' ? 4000 : 255);
    }

    if ($fields['email'] !== '' && !filter_var($fields['email'], FILTER_VALIDATE_EMAIL)) {
        ccms_public_form_flash_set($blockId, 'error', 'Introduce un email válido.');
        ccms_public_form_redirect($returnTo, $blockId);
    }

    if ($kind === 'newsletter') {
        if ($fields['email'] === '') {
            ccms_public_form_flash_set($blockId, 'error', 'Necesitamos tu email para suscribirte.');
            ccms_public_form_redirect($returnTo, $blockId);
        }
        $fields = ['email' => $fields['email']];
    } else {
        if ($fields['name'] === '' || $fields['email'] === '' || $fields['message'] === '') {
            ccms_public_form_flash_set($blockId, 'error', 'Completa nombre, email y mensaje.');
            ccms_public_form_redirect($returnTo, $blockId);
        }
    }

    $data = ccms_load_data();
    $submission = [
        'id' => ccms_next_id('sub'),
        'kind' => $kind,
        'status' => 'new',
        'created_at' => ccms_now_iso(),
        'updated_at' => ccms_now_iso(),
        'page_id' => trim((string) ($_POST['page_id'] ?? '')),
        'page_slug' => trim((string) ($_POST['page_slug'] ?? '')),
        'page_title' => trim((string) ($_POST['page_title'] ?? '')),
        'block_id' => $blockId,
        'block_type' => trim((string) ($_POST['block_type'] ?? $kind)),
        'source_url' => ccms_public_form_return_path($returnTo),
        'delivery' => ['attempted' => false, 'sent' => false, 'channel' => 'mail', 'target' => ''],
        'meta' => [
            'ip' => ccms_client_ip(),
            'user_agent' => mb_substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 500),
        ],
        'fields' => array_filter($fields, static fn ($value): bool => trim((string) $value) !== ''),
    ];
    $submission['delivery'] = ccms_try_send_submission_mail($data['site'] ?? [], $submission);
    ccms_store_submission($data, $submission);
    ccms_push_audit_log($data, 'forms.submission_received', 'Public form submission received', null, [
        'kind' => $submission['kind'],
        'page_slug' => $submission['page_slug'],
        'delivery_sent' => !empty($submission['delivery']['sent']),
    ]);
    ccms_save_data($data);

    $successMessage = $kind === 'newsletter'
        ? 'Gracias. Tu suscripción se ha guardado.'
        : 'Gracias. Hemos recibido tu mensaje.';
    ccms_public_form_flash_set($blockId, 'success', $successMessage);
    ccms_public_form_redirect($returnTo, $blockId);
}
