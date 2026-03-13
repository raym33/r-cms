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

## Who LinuxCMS is for

Right now LinuxCMS is a strong fit for:

- agencies delivering editable sites to clients
- freelancers who want a lighter self-hosted CMS
- teams that want LM Studio-assisted first drafts without forcing a SaaS runtime

It is not yet positioned as a full multi-tenant SMB SaaS.

## Stack

- PHP 8+
- JSON or SQLite storage
- native PHP sessions
- file uploads in `/uploads`
- LM Studio via local OpenAI-compatible HTTP API

No Python is required in the final hosted runtime.

## Deployment modes

LinuxCMS supports three practical ways to run the same project:

- local authoring with the built-in PHP server
- generic Apache/PHP hosting with `.htaccess`
- container deployment with `docker compose`

The public runtime is available through the same route set in every mode:

- `/`
- `/blog`
- `/install.php`
- `/r-admin`
- `/api/health`
- `/api/forms/submit`
- `/feed.xml`
- `/sitemap.xml`
- `/robots.txt`

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
- blog/posts management with:
  - categories
  - tags
  - archive pages
  - RSS feed
- functional public forms for:
  - `lead_form`
  - `contact`
  - `newsletter`
- admin inbox for leads and form submissions
- per-submission status workflow:
  - `new`
  - `reviewed`
  - `contacted`
  - `archived`
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
- direct link-focused action from the preview for button and URL fields
- direct media-focused action from the preview for image-bearing blocks
- direct media selection modal from the preview, applying library images back into the matching block field
- double-click visible text inside the selected preview block to jump to the most likely text field
- double-click visible images in the selected preview block to jump to the matching media field
- inline text editing inside the preview for selected blocks, synced back to the builder
- double-click visible links in the selected preview block to edit both label and URL
- double-click visible buttons in the selected preview block to edit label, URL and basic button colors
- client mode toggle in `/r-admin` for a simpler editing surface focused on text, photos, colors and publishing
- quick-start guide inside the page editor for non-technical users
- preview-side editing hints that explain how to edit text, photos, and buttons
- friendlier wording in the page editor: `Sections` instead of a raw builder-first label and `Advanced JSON` instead of exposing capsule jargon by default
- lightweight site themes with presets and optional custom CSS
- analytics settings in the admin for:
  - Google Analytics 4
  - Plausible
- white-label branding for agencies:
  - custom admin brand name
  - custom admin tagline
  - custom admin logo
  - branded login shell and admin header
  - TOTP issuer aligned to the agency brand
- lightweight plugins/extensions with activation from the admin
  - disabled by default
  - opt-in trusted mode
  - manifest + `plugin.php` integrity check
- full site backup export/import from the admin, including uploads
  - backup restore preserves the previous `uploads/` files in a timestamped sibling backup folder before replacing them
- static hosting package export from the admin for basic hosting without PHP
  - generated responsive image variants are copied into the exported `uploads/` folder
- page revisions and restore
- page duplication
- media library
- automatic image optimization for local uploads:
  - responsive resized variants
  - best-effort WebP variants when the PHP runtime supports it
  - public lazy loading and async decoding
- capsule import from the older aivoiceweb tools
- PHP-native rendering for a large set of capsule blocks
- public SEO baseline:
  - canonical URLs
  - Open Graph tags
  - Twitter card tags
  - JSON-LD structured data
  - blog/category/tag entries in `sitemap.xml`
  - `feed.xml`
  - `sitemap.xml`
  - `robots.txt`
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
- public page `ETag` + short HTTP cache headers for basic hosting performance
- MIT license included in the repository

## Local development

```bash
bash start-local.sh
```

This launcher starts LinuxCMS in the background, stores a PID file, writes logs to `.linuxcms-runtime/server.log`, and waits for a real health check before reporting success.

Then open:

- `http://127.0.0.1:8088/install.php`
- `http://127.0.0.1:8088/r-admin`

Health endpoint:

- `http://127.0.0.1:8088/api/health`

### macOS stable mode

If you do not want the local server to depend on an open Terminal window, install the bundled `launchd` service:

```bash
bash scripts/install_launchd.sh
```

Then open:

- `http://127.0.0.1:8088/r-admin/`

To remove it later:

```bash
bash scripts/uninstall_launchd.sh
```

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

Apache/basic-hosting compatibility is handled through:

- [.htaccess](/Users/c/Desktop/videojuego/linuxcms/.htaccess)
- [api/health/index.php](/Users/c/Desktop/videojuego/linuxcms/api/health/index.php)
- [api/forms/submit/index.php](/Users/c/Desktop/videojuego/linuxcms/api/forms/submit/index.php)
- [sitemap.php](/Users/c/Desktop/videojuego/linuxcms/sitemap.php)
- [robots.php](/Users/c/Desktop/videojuego/linuxcms/robots.php)

## Docker quickstart

If you want a one-command local or VPS deployment:

```bash
docker compose up --build
```

Then open:

- `http://127.0.0.1:8088/install.php`
- `http://127.0.0.1:8088/r-admin`

Container packaging is defined in:

- [Dockerfile](/Users/c/Desktop/videojuego/linuxcms/Dockerfile)
- [compose.yaml](/Users/c/Desktop/videojuego/linuxcms/compose.yaml)
- [docker/apache-site.conf](/Users/c/Desktop/videojuego/linuxcms/docker/apache-site.conf)
- [docker/entrypoint.sh](/Users/c/Desktop/videojuego/linuxcms/docker/entrypoint.sh)

The compose stack persists:

- `./data`
- `./uploads`
- `./.linuxcms-runtime`

So installs, content, media, optimized image variants, and exports survive restarts.

## User and legal docs

For product delivery and handoff, start with:

- [docs/QUICKSTART.md](/Users/c/Desktop/videojuego/linuxcms/docs/QUICKSTART.md)
- [docs/USER_GUIDE.md](/Users/c/Desktop/videojuego/linuxcms/docs/USER_GUIDE.md)
- [docs/TERMS_OF_SERVICE_TEMPLATE.md](/Users/c/Desktop/videojuego/linuxcms/docs/TERMS_OF_SERVICE_TEMPLATE.md)
- [docs/PRIVACY_POLICY_TEMPLATE.md](/Users/c/Desktop/videojuego/linuxcms/docs/PRIVACY_POLICY_TEMPLATE.md)

These are templates and operational guides, not legal advice.

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
- first modular split of the admin action layer into `src/admin_actions.php`
- pages editor UI extracted from the admin monolith into `r-admin/views/pages.php`
- users management UI extracted from the admin monolith into `r-admin/views/users.php`
- media library UI extracted from the admin monolith into `r-admin/views/media.php`
- backup/export/restore UI extracted from the admin monolith into `r-admin/views/backups.php`
- site branding/theme UI extracted from the admin monolith into `r-admin/views/site.php`
- audit log UI extracted from the admin monolith into `r-admin/views/audit.php`
- extensions UI extracted from the admin monolith into `r-admin/views/extensions.php`
- account and 2FA UI extracted from the admin monolith into `r-admin/views/account.php`
- local LM Studio authoring UI extracted from the admin monolith into `r-admin/views/studio.php`
- import UI extracted from the admin monolith into `r-admin/views/import.php`
- login/reset/2FA access shell extracted from the admin monolith into `r-admin/views/auth_shell.php`
- unauthenticated page wrapper extracted into `r-admin/views/login.php`
- authenticated page wrapper extracted into `r-admin/views/layout.php`
- shared admin topbar, alerts and tab navigation extracted into `r-admin/views/admin_chrome.php`
- main admin tab switch extracted into `r-admin/views/admin_tabs.php`
- action-family handler entrypoints extracted into `r-admin/handlers/*.php`
- shared admin stylesheet extracted into `r-admin/assets/admin.css`
- shared admin JavaScript extracted into `r-admin/assets/admin.js`
- shared admin read-context bootstrap extracted into `src/admin_context.php`
- `HttpOnly` session cookies
- `SameSite=Lax` cookies
- `X-Frame-Options`, `nosniff`, `Referrer-Policy`, `Permissions-Policy`
- Content Security Policy headers
- CSP nonces for inline admin/public script and style blocks
- admin no-cache headers
- generic request throttling helpers for sensitive routes
- media uploads restricted by extension, MIME type and max size
- SVG uploads disabled by default
- backup imports restricted by size and file type
- public `/api/health` endpoint rate limited by IP
- stored `html_content` sanitized before public rendering
- custom CSS sanitized before storage and before public rendering
- plugin discovery restricted to valid slugs and trusted paths inside `plugins/`
- plugin hooks restricted to the public hook whitelist

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

LinuxCMS now also includes a browser E2E smoke with Playwright:

```bash
npm install
npx playwright install chromium
npm run test:e2e
```

The E2E smoke currently covers:

- fresh install
- admin login
- page creation from `/r-admin`
- selected page preview visible in the admin

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
- public HTML/CSS sanitization and CSP hardening

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
- simplified client mode for non-technical editing
