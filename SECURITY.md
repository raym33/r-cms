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
- `HttpOnly` + `SameSite=Lax` session cookies
- session rotation on successful login
- basic idle session expiry handling
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
- if available:
  - IP allowlisting
  - extra HTTP auth in front of `/r-admin`
  - WAF / provider-side rate limiting

## Known limitations

- no 2FA yet
- no formal audit log yet
- no external identity provider yet
- shared-hosting hardening still depends partly on provider configuration
- CSP is intentionally permissive enough to support the current inline admin UX and LM Studio local integration

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
