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
- page revisions and restore
- page duplication
- media library
- capsule import from the older aivoiceweb tools
- PHP-native rendering for a large set of capsule blocks

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

LinuxCMS is now the base for the product you described:

- local creation
- no voice requirement
- keyboard-first editing
- capsule-based rendering
- uploadable CMS for basic hosting
