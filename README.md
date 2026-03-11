# LinuxCMS

LinuxCMS is the new unified direction for the project:

- a **local website studio**
- driven by **LM Studio**
- with **keyboard-first editing**
- based on **capsules**
- and publishable as a **generic-hosting CMS** through `/r-admin`

The idea is simple:

1. Build the first website locally from a brief.
2. Refine it with the visual builder, media library and page editor.
3. Upload the same project to basic hosting.
4. Let the final client edit it manually from `/r-admin` with username and password.

## Why LinuxCMS exists

The previous flows were useful but fragmented:

- the voice builder was powerful, but not ideal for every user
- the Python CMS was harder to deploy on basic hosting
- non-technical users got stuck with local installs across Linux, macOS and Windows

LinuxCMS starts from a stricter rule:

> One local app for creation and editing, one generic-hosting runtime for the client.

## Stack

- PHP 8+
- JSON or SQLite storage
- native PHP sessions
- file uploads in `/uploads`
- LM Studio via local OpenAI-compatible HTTP API

No Python is required in the final hosted runtime.

## Current scope

LinuxCMS already includes:

- `/install.php` installer
- `/r-admin` login and admin
- multiuser roles:
  - `owner`
  - `editor`
  - `viewer`
- local `Studio` tab for generating a first draft from a typed brief
- LM Studio endpoint/model settings inside the admin
- fallback local generator when LM Studio is unavailable
- page CRUD
- visual capsule builder
- capsule-wide style tokens
- per-block layout and style controls
- visual repeaters for cards/items/links/bullets
- direct media pickers inside image fields
- drag and drop for repeater cards
- live preview rendered from the current form state
- block-aware preview: click a section in preview to jump to the matching builder block
- contextual active-block toolbar for content/style/duplicate/delete
- inline preview actions on selected blocks for edit content, edit style, duplicate and delete
- direct media-focused action from the preview for image-bearing blocks
- double-click visible text inside the selected preview block to jump to the most likely text field
- double-click visible images in the selected preview block to jump to the matching media field
- page revisions and restore
- page duplication
- media library
- capsule import from the older aivoiceweb tools
- PHP-native rendering for a large set of capsule blocks
- admin hardening for `/r-admin`:
  - CSRF protection
  - same-origin POST enforcement
  - login throttling and temporary lockout
  - `HttpOnly` session cookies with `SameSite=Lax`
  - security headers and admin no-cache headers
  - audit log for sensitive admin actions
  - forced password change for temporary user credentials
  - optional TOTP 2FA for admin accounts
  - owner-generated password reset links

## Local development

```bash
php -S 127.0.0.1:8088 router.php
```

Then open:

- `http://127.0.0.1:8088/install.php`
- `http://127.0.0.1:8088/r-admin`

## LM Studio setup

1. Run LM Studio locally.
2. Start its OpenAI-compatible local server.
3. In LinuxCMS go to:
   - `/r-admin`
   - `Studio`
4. Set the endpoint, usually:
   - `http://127.0.0.1:1234/v1`
5. Save the settings.
6. Probe the connection.
7. Generate the first draft page from a typed brief.

If LM Studio is not available, LinuxCMS can still create a structured fallback draft so the editor flow is not blocked.

## Generic hosting deployment

1. Upload the whole project to the hosting root.
2. Make sure `data/` and `uploads/` are writable.
3. Open `/install.php`.
4. Choose `SQLite` if available, otherwise `JSON`.
5. Create the first admin user.
6. Enter `/r-admin`.
7. The final client edits the website there manually.

## `/r-admin` security model

LinuxCMS is designed so the public website can live on cheap hosting while the admin stays reasonably hardened by default.

Current protections include:

- CSRF validation on admin writes
- same-origin validation for admin POST requests
- login throttling with temporary lockout after repeated failures
- role-based access control:
  - `owner`
  - `editor`
  - `viewer`
- optional TOTP 2FA for admin accounts
- owner-generated password reset links
- `HttpOnly` session cookies
- `SameSite=Lax` cookies
- `X-Frame-Options`, `nosniff`, `Referrer-Policy`, `Permissions-Policy`
- Content Security Policy headers
- admin no-cache headers

Recommended hosting-side protections:

- protect `/r-admin` with HTTPS only
- keep `data/` and `uploads/` writable but not directory-listable
- use strong admin passwords
- restrict PHP execution to the project only
- if possible, add IP restriction or basic auth in front of `/r-admin`

LinuxCMS is safer than a raw PHP admin prototype, but it is still an early product and should be treated as a hardened lightweight CMS, not as a fully audited enterprise platform.

## Testing

LinuxCMS includes a deep CLI regression script:

```bash
php tests/deep_test.php
```

This test exercises more than 100 checks, including:

- JSON and SQLite storage
- CSRF and same-origin checks
- login, logout and throttling
- TOTP 2FA login completion
- password reset token generation and consumption
- role permissions
- LM Studio fallback generation
- capsule rendering
- install page rendering
- admin view rendering for `owner` and `viewer`
- revisions and helper coverage

Use it before shipping a client build or before pushing changes.

## Import from the older builder

```bash
php tools/import-from-aivoiceweb.php \
  --capsule=/path/to/page.json \
  --html=/path/to/page.html \
  --title="Imported Page" \
  --slug="imported-page" \
  --homepage=true
```

## What is not finished yet

- full block coverage from the entire capsule ecosystem
- full drag-and-drop page composition
- theme marketplace
- plugin system
- advanced client-safe simplified mode

## Docs

- [Architecture](docs/ARCHITECTURE.md)
- [Security notes](SECURITY.md)

LinuxCMS is now the base for the product you described:

- local creation
- no voice requirement
- keyboard-first editing
- capsule-based rendering
- uploadable CMS for basic hosting
