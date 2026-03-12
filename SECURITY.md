# LinuxCMS Security Notes

## Scope

LinuxCMS is a lightweight PHP CMS designed for:

- local authoring with LM Studio
- generic hosting deployment
- client editing through `/r-admin`

It is security-conscious by default, but it is not presented as a formally audited security product.

## Current protections

- CSRF validation on admin write actions
- same-origin validation on admin POST requests
- login throttling with temporary lockout after repeated failures
- role-based access control:
  - `owner`
  - `editor`
  - `viewer`
- optional TOTP 2FA for admin accounts
- forced password change for newly created or reset user accounts
- audit log for sensitive admin actions
- owner-generated password reset links
- `HttpOnly` + `SameSite=Lax` session cookies
- session rotation on successful login
- basic idle session expiry handling
- sanitization of stored `html_content` before render/output
- sanitization of site-level custom CSS before output
- sanitization of plugin-provided head/body fragments before public render
- generic request throttling helpers for sensitive endpoints
- media upload validation by extension, MIME type and maximum file size
- SVG uploads disabled by default
- backup imports restricted by file type and maximum payload size
- public `/api/health` rate limited by IP
- trusted PHP plugins disabled by default
- plugin discovery restricted to valid slugs and trusted real paths inside `plugins/`
- plugin hook registration restricted to a public hook whitelist
- trusted plugin loading only when:
  - trusted mode is enabled
  - the plugin manifest marks the plugin as trusted
  - the `plugin.php` SHA-256 matches the manifest
- common security headers:
  - `X-Frame-Options: SAMEORIGIN`
  - `X-Content-Type-Options: nosniff`
  - `Referrer-Policy: same-origin`
  - `Permissions-Policy`
  - `Content-Security-Policy`
- no-cache headers for admin responses

## Hosting recommendations

For `/r-admin`, use:

- HTTPS only
- strong unique passwords
- writable `data/` and `uploads/` folders
- disabled directory listing
- no public backup files in web root
- trusted-only plugins inside `plugins/`
- if available:
  - IP allowlisting
  - extra HTTP auth in front of `/r-admin`
  - WAF / provider-side rate limiting

## Known limitations

- no external identity provider yet
- shared-hosting hardening still depends partly on provider configuration
- plugins are still trusted PHP code, not sandboxed extensions
- the admin remains monolithic and should be modularized further

## Development guidance

Before shipping a client build:

```bash
php tests/deep_test.php
```

Also run:

```bash
php -l install.php
php -l r-admin/index.php
php -l r-admin/preview.php
php -l src/auth.php
php -l src/helpers.php
```

## Reporting

If LinuxCMS becomes public-facing beyond experiments, add a private contact method here for security reports.
