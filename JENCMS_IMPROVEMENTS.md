# JenCMS improvement backlog

This document tracks improvements transferred from the Cesta prototype into the main JenCMS project.

## 1. Routing and the `public` directory

**Status: implemented in JenCMS.** The supported document root is now `public/`.
Local development uses `public/router.php`, theme files live under
`public/themes/`, Apache rewriting is configured by `public/.htaccess`, and
`tests/routing-smoke.ps1` covers application routes, static assets, uploads, and
missing resources.

Implemented result:

- one documented public web-root strategy;
- an official router for the PHP development server;
- direct serving of `/themes/*`, `/uploads/*`, and admin assets;
- consistent application routing in local and production setups;
- routing smoke tests for pages, posts, admin routes, assets, uploads, and 404s.

## 2. Content creation and editing pages

**Status: implemented in JenCMS.** Post and page settings and content now use a
consistent vertical form flow, the editor occupies the full available width,
and section, category, and template forms follow the same stacked layout. Editor
height, spacing, and form actions adapt for tablet and mobile widths.

Implemented result:

- a clear single-column content-editing flow;
- full-width post and page editors;
- grouped secondary fields without narrowing the editor;
- consistent create/edit screens;
- responsive spacing and controls.

## 3. Content editor

**Status: implemented in JenCMS.** The admin editor now uses a locally bundled,
pinned Tiptap 3.30.2 build with visual/source and fullscreen modes, formatting
controls, image selection and authenticated AJAX upload through Media Library,
raw page HTML preservation, responsive styling, and unsaved-change protection.
Editor CSS and JavaScript are separate admin assets, and the bundle source, lock
file, rebuild command, and focused storage test are included.

Editor foundation:

- Tiptap 3.30.2 with `StarterKit` and the official Image extension;
- dependencies bundled locally instead of loaded from a CDN;
- semantic HTML remains the database storage format;
- the deprecated `document.execCommand` implementation is removed;
- responsive layout and fullscreen mode.

Formatting features:

- paragraphs and headings H2-H4;
- bold, italic, underline, and strike-through;
- bullet and numbered lists;
- blockquotes and horizontal rules;
- add, edit, and remove links;
- undo, redo, and clear formatting;
- visual and HTML/source modes.

Image workflow:

- insert an image using an external URL;
- edit alternative text;
- select an existing Media Library image;
- upload from the editor dialog directly into Media Library;
- authenticated JSON responses with CSRF protection;
- automatic selection of newly uploaded images.

Page HTML behavior:

- pages open in visual mode by default, with source mode available from the toolbar;
- original textarea HTML is preserved when saving without visual changes or while in source mode;
- Tiptap normalization occurs only after the user changes visual content.

## Workflow

1. Keep CMS fixes separate from project-specific design and content.
2. Update JenCMS documentation and launch instructions with architectural changes.
3. Run routing, content-storage, PHP syntax, and JavaScript checks before releases.
4. Do not commit or push changes without explicit approval.
