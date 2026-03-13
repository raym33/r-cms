# LinuxCMS User Guide

This guide is for the person who edits the website from `/r-admin`.

It assumes LinuxCMS is already installed and that you have a username and password.

## 1. Log in

Open:

- `/r-admin`

Then:

1. Enter your username or email.
2. Enter your password.
3. If your account uses 2FA, enter the TOTP code.
4. If you were given a temporary password, LinuxCMS will ask you to change it first.

## 2. Understand the main admin areas

Inside `/r-admin` you will normally use these tabs:

- `Pages`
  - create and edit pages
- `Media`
  - upload and reuse images
- `Site`
  - change logo, colors, footer text, analytics, and theme preset
- `Inbox`
  - review contact form, newsletter, and lead submissions
- `Backups`
  - export the full site or a static package

If client mode is enabled, the editing UI is simplified and focuses on text, photos, colors, and publishing.

## 3. Create a page

Go to `Pages`.

Then:

1. Click the action to create a new page.
2. Enter:
   - page title
   - slug
   - publish status
3. Save the page.

If you want LinuxCMS to draft the first version for you, use the `Studio` tab first, then come back to `Pages`.

## 4. Edit text and sections

In `Pages`:

1. Select the page you want to edit.
2. Use the visual builder in `Sections`.
3. Click a section in the preview to select it.
4. Double-click visible text in the preview to edit it directly.
5. Use the builder fields if you want more control.

You can also:

- duplicate sections
- remove sections
- insert sections before or after an existing block
- reorder repeater cards/items

## 5. Change images

There are three common ways:

1. Go to `Media` and upload images first.
2. In the page preview, double-click an image to open the media picker.
3. In the builder, use the image/media field for the selected section.

Images uploaded in `Media` can be reused across the site.

## 6. Change colors and branding

Go to `Site`.

There you can change:

- site title
- tagline
- footer text
- theme preset
- palette colors
- custom CSS

If you want fast changes, start with the theme preset and colors before touching custom CSS.

## 7. Review leads and contact requests

Go to `Inbox`.

You will see submissions from:

- contact forms
- lead forms
- newsletter blocks

Each submission can be marked as:

- `new`
- `reviewed`
- `contacted`
- `archived`

By default LinuxCMS also tries to send an email notification to the site's `contact_email`.

## 8. Add analytics

Go to `Site`.

LinuxCMS supports:

- Google Analytics 4
- Plausible

Paste the correct measurement ID or domain value in the analytics settings and save.

## 9. Export the website

Go to `Backups`.

You have two useful export types:

- full backup
  - for restoring the whole LinuxCMS site later
- static hosting package
  - for basic hosting without PHP

Use the static export if you want to upload the final website as plain files.

## 10. Restore or move the site

Also from `Backups` you can:

- export a full backup
- import a full backup

This includes:

- pages
- media
- users
- site settings
- revisions
- submissions

## 11. Best practices

- Use strong passwords.
- Enable 2FA for owner accounts.
- Keep regular backups before large edits.
- Use `published` only for pages that should be public.
- Keep `data/` and `uploads/` writable but not publicly listable.
- Prefer `Site` settings for branding changes before editing raw CSS.

## 12. Typical handoff flow for agencies

1. Install LinuxCMS.
2. Create the site and initial pages.
3. Upload branding assets.
4. Configure analytics.
5. Test public forms.
6. Create client user accounts.
7. Force password change for temporary users.
8. Deliver the `/r-admin` URL and this guide.
