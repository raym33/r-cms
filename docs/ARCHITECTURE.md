# LinuxCMS Architecture

## Product goal

LinuxCMS is meant to unify three previously separate concerns:

1. local website creation
2. capsule-based visual editing
3. hosted CMS delivery for non-technical clients

The target flow is:

- local author creates the site with LM Studio + keyboard
- local author refines it with the builder and CMS
- project is uploaded to generic hosting
- client logs into `/r-admin` and edits manually

## Runtime principles

LinuxCMS uses:

- PHP 8+
- JSON or SQLite
- sessions + CSRF
- uploads in `/uploads`
- PHP-native public rendering

This keeps the hosted runtime compatible with generic shared hosting.

There is also a container packaging layer for the same runtime:

- Apache + PHP 8.3
- `.htaccess` enabled through `AllowOverride All`
- persistent volumes for `data/`, `uploads/`, and `.linuxcms-runtime`
- same public/admin routes as generic hosting

## Testing layers

LinuxCMS now has two complementary test layers:

- `tests/deep_test.php`
  - CLI-heavy regression coverage for storage, auth, security helpers, rendering, backups, and exports
- `tests/e2e/admin.spec.js`
  - browser smoke coverage for a real install, login, page creation, and preview visibility in `/r-admin`

## Local AI layer

LinuxCMS adds a local-only authoring layer through LM Studio:

- settings are stored in `local_ai`
- the admin `Studio` tab sends prompts to LM Studio's OpenAI-compatible endpoint
- model probing happens through `GET /models`
- generation happens through `POST /chat/completions`
- if LM Studio is unavailable, LinuxCMS falls back to a deterministic capsule generator

This means the same project can still function on hosting where LM Studio does not exist.

## Deployment surfaces

LinuxCMS currently supports:

1. built-in PHP server for local authoring
2. generic Apache/PHP hosting
3. Docker/Apache deployment through `compose.yaml`

## Data model

Application data is stored as one payload in:

- `data/app.json`
- or `data/app.sqlite`

Main structures:

- `site`
- `local_ai`
- `admin` (legacy compatibility)
- `users`
- `pages`
- `posts`
- `media`
- `submissions`

## Roles

- `owner`
  - full control
- `editor`
  - pages, media, imports, AI generation
- `viewer`
  - read-only admin review mode
- newly created users can be forced to change their temporary password on first login
- admin accounts can optionally enable TOTP 2FA
- owners can generate one-time password reset links for other users
- sensitive actions are recorded in `audit_logs`

## Page model

Each page stores:

- title
- slug
- status
- menu visibility
- homepage flag
- meta title
- meta description
- `capsule_json`
- `html_content`
- revisions

## Rendering strategy

LinuxCMS uses a mixed public rendering strategy:

- if the page capsule is supported natively in PHP, render from `capsule_json`
- otherwise fall back to stored `html_content`

This keeps migration from older tools practical while progressively expanding PHP-native support.

## Admin structure

`/r-admin` currently contains:

- `Studio`
  - local LM Studio settings
  - typed brief to generate a first draft page
- `Pages`
  - visual page editor
  - builder
  - preview
  - revisions
- `Posts`
  - post CRUD
  - categories and tags
  - preview
  - archive/public post flow
- `Site`
  - site-wide branding and palette
  - white-label admin branding for agencies
- `Media`
  - upload and reuse images
  - generate responsive local variants on upload
  - serve best-effort WebP variants when available
- `Import`
  - import from older capsule/html workflows
- `Users`
  - owner/editor/viewer management

The admin is still UI-heavy in a single entry file, but the first structural cut is now done:

- `src/admin_actions.php`
  - centralizes authenticated and guest-facing `POST` actions from `/r-admin`
- `r-admin/views/pages.php`
  - dedicated view module for the `Pages` tab, including the visual builder, preview, revisions, and read-only fallback
- `r-admin/views/users.php`
  - dedicated view module for the `Users` tab, including user creation, role management, password resets, and user removal
- `r-admin/views/media.php`
  - dedicated view module for the `Media` tab, including uploads and the reusable asset library
- `r-admin/views/backups.php`
  - dedicated view module for the `Backups` tab, including full backup export, restore, and static hosting package export
- `r-admin/views/site.php`
  - dedicated view module for the `Site` tab, including branding, theme preset, colors, custom CSS settings, and agency white-label admin branding
- `r-admin/views/audit.php`
  - dedicated view module for the `Audit` tab, focused on recent privileged actions and metadata inspection
- `r-admin/views/extensions.php`
  - dedicated view module for the `Extensions` tab, including trusted plugin mode and plugin activation controls
- `r-admin/views/account.php`
  - dedicated view module for the `Account` tab, including password change and TOTP 2FA setup/disable flows
- `r-admin/views/studio.php`
  - dedicated view module for the `Studio` tab, including LM Studio settings, connectivity probe, and brief-driven first-draft generation
- `r-admin/views/import.php`
  - dedicated view module for the `Import` tab, including quick HTML/capsule import into a new page
- `r-admin/views/auth_shell.php`
  - dedicated view module for the login, 2FA verification, and password reset entry shell
- `r-admin/views/login.php`
  - top-level unauthenticated wrapper that renders the access shell and loads shared admin assets
- `r-admin/views/layout.php`
  - top-level authenticated wrapper that bootstraps admin state, loads shared assets, and composes admin chrome + tab content
- `r-admin/views/admin_chrome.php`
  - shared admin chrome for the topbar, flash messages, client-mode banner, and tab navigation
- `r-admin/views/admin_tabs.php`
  - shared tab switch that delegates to the dedicated view modules for account, studio, site, extensions, backups, media, import, audit, users, and pages
- `r-admin/handlers/*.php`
  - route entrypoints grouped by action family
  - they delegate authenticated and guest-facing write operations through the shared `src/admin_actions.php` layer
- `r-admin/assets/admin.css`
  - shared admin styling for the topbar, builder, preview, media modal, client mode, and responsive layout
- `r-admin/assets/admin.js`
  - shared admin client-side behavior for the builder, preview sync, media picker, inline editing, and client mode
- `src/admin_actions.php`
  - centralizes admin `POST` action handling
  - separates write-side request logic from the view-heavy `r-admin/index.php`
- `src/admin_context.php`
  - centralizes admin read-side context building
  - resolves permissions, selected page, preview HTML, builder templates, and JSON bootstrap payloads
  - resolves agency/admin white-label branding for the login shell, admin chrome, and page titles

This is the first step toward splitting the admin into smaller modules such as pages, media, users, site settings and backups.

## Security hardening notes

Recent hardening work added these runtime protections:

- `ccms_sanitize_html()` / `ccms_sanitize_html_fragment()`
  - sanitize stored `html_content` before public rendering
- `ccms_sanitize_css()` / `ccms_sanitize_custom_css()`
  - sanitize custom CSS before storage and public rendering
- request-scoped CSP nonces
  - applied to public and admin inline script/style blocks
- trusted plugin mode
  - plugin discovery restricted to valid slugs, trusted paths and integrity-checked `plugin.php`
- safer backup restore
  - uploaded files are preserved into a timestamped backup directory before replacement
- partial public data access helpers
  - `ccms_load_page_by_slug()`
  - `ccms_load_site_config()`
- public cache support
  - short-lived `ETag` + `Cache-Control` on rendered pages

## Public routing surfaces

The public runtime is reachable through:

- `index.php`
  - main page resolver
  - pages
  - `/blog`
  - `/blog/{slug}`
  - `/blog/category/{slug}`
  - `/blog/tag/{slug}`
- `router.php`
  - built-in PHP server router for local development
- `.htaccess`
  - Apache rewrite layer for shared hosting and Docker Apache
- `api/health/index.php`
  - Apache-compatible health endpoint
- `api/forms/submit/index.php`
  - Apache-compatible public forms endpoint
- `feed.php`
  - Apache-compatible RSS endpoint for posts
- `sitemap.php`
  - Apache-compatible sitemap endpoint
- `robots.php`
  - Apache-compatible robots endpoint

## Builder direction

The current builder already supports:

- block insertion from templates
- capsule-wide visual tokens
- per-block style/layout settings
- visual repeaters for arrays
- drag-and-drop of repeater cards
- direct media picker in image fields
- live preview iframe from current form state
- block-aware preview selection synced with the builder
- contextual active-block actions for content/style focus and quick duplication/removal
- inline preview action pills inside selected blocks
- direct link jump from preview to the first matching URL field in the builder
- direct media jump from preview to the first matching image field in the builder
- direct media modal from preview to choose an asset from the library and write it back into the selected block
- double-click text in preview to jump to the closest matching text field in the builder
- double-click images in preview to jump to the closest matching media field in the builder
- inline text editing in preview with save-on-enter/blur back into capsule-backed fields
- double-click links in preview to edit both the visible label and the URL, then sync them back to capsule-backed fields
- double-click buttons in preview to edit label, URL and basic colors, then persist them through capsule-backed fields and block style
- quick-start guidance inside the page editor for non-technical users
- permanent preview hints explaining the edit model without requiring external docs
- friendlier UI language in client mode (`Sections`, `Advanced JSON`) to reduce builder jargon
- lightweight site themes with preset styling and optional custom CSS at the site level

## Product-side runtime additions

LinuxCMS now also includes the first commercial baseline features:

- public forms submitted to `/api/forms/submit`
- lead storage in `submissions`
- inbox review inside `/r-admin`
- delivery attempt through PHP `mail()` to `site.contact_email`
- analytics injection at the site level:
  - Google Analytics 4
  - Plausible
- public SEO baseline:
  - canonical
  - Open Graph
  - Twitter cards
  - JSON-LD
  - `sitemap.xml`
  - `robots.txt`
- lightweight plugins/extensions discovered from `plugins/` and enabled from the admin
  - disabled by default
  - only loaded in trusted mode
  - require manifest trust flag and `plugin.php` SHA-256 integrity match
- full-site backup export/import with JSON payloads that also carry uploaded files
- backup restore preserves the previous `uploads/` files in a timestamped backup directory before replacement
- public runtime sends `ETag` and short-lived cache headers for rendered pages
- static export packaging that writes a hosting-ready site with `index.html`, slug folders and `uploads/`
- public HTML image post-processing:
  - adds `loading="lazy"`
  - adds `decoding="async"`
  - injects `srcset`/`sizes` for local uploads when generated variants exist
  - wraps local images in `<picture>` when WebP variants exist

It is not yet a full Elementor/Figma-style page canvas, but it is already beyond a raw JSON editor.

## Why this is the right base

LinuxCMS is the first branch that aligns with the actual product requirement:

- one local app
- LM Studio-based creation
- no voice required
- editable by keyboard
- exportable/deployable to basic hosting

## Product readiness notes

LinuxCMS now has the first practical product-facing layer beyond raw architecture:

- user quickstart documentation
- user admin guide
- terms of service template
- privacy policy template

That does not replace legal review, but it reduces one of the main blockers between "technical project" and "sellable self-hosted product".
- client still gets `/r-admin`
