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

## Local AI layer

LinuxCMS adds a local-only authoring layer through LM Studio:

- settings are stored in `local_ai`
- the admin `Studio` tab sends prompts to LM Studio's OpenAI-compatible endpoint
- model probing happens through `GET /models`
- generation happens through `POST /chat/completions`
- if LM Studio is unavailable, LinuxCMS falls back to a deterministic capsule generator

This means the same project can still function on hosting where LM Studio does not exist.

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
- `media`

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
- `Site`
  - site-wide branding and palette
- `Media`
  - upload and reuse images
- `Import`
  - import from older capsule/html workflows
- `Users`
  - owner/editor/viewer management

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

It is not yet a full Elementor/Figma-style page canvas, but it is already beyond a raw JSON editor.

## Why this is the right base

LinuxCMS is the first branch that aligns with the actual product requirement:

- one local app
- LM Studio-based creation
- no voice required
- editable by keyboard
- exportable/deployable to basic hosting
- client still gets `/r-admin`
