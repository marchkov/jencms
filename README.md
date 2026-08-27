# JenCMS

JenCMS is a lightweight CMS starter built with PHP and SQLite.

It is intended to be a clean base for future site builds: simple to launch locally, easy to adapt, and free from project-specific branding.

## Features

- static pages
- post sections
- categories
- media uploads
- template editing from the admin panel
- SQLite-based setup with automatic bootstrap on first run
- sequential database migrations
- environment and permission checks in the admin settings

## Requirements

- PHP 8.1 or newer
- PDO SQLite extension
- Fileinfo extension for secure media uploads
- Mbstring extension is recommended for Unicode text handling

## Quick Start

Clone the repository and run the built-in PHP server from the project root. The
supported document root is `public/`; `public/router.php` forwards application
requests while PHP serves existing static files directly:

```powershell
php -S localhost:8000 -t public public/router.php
```

Open in browser:

- Site: `http://localhost:8000/`
- Admin: `http://localhost:8000/admin`

On first launch, JenCMS creates the SQLite database automatically using the
sequential SQL files in [`storage/migrations/`](storage/migrations/). Existing
databases are detected and upgraded without recreating their content.

## Default Admin Login

- Login: `admin`
- Password: `admin123`

Change the password after the first login.

The admin panel warns while this default password remains active under
`Settings → System Check`.

## Project Structure

- `public/` - the only web root; entrypoint, themes, and uploaded files
- `src/` - application core
- `public/themes/` - frontend themes and their public assets
- `views/` - admin panel views
- `storage/` - migrations, database, starter content, and template backups
- `settings.php` - main configuration

## Current Scope

JenCMS currently provides:

- one frontend theme: `public/themes/default`
- one-language content management
- SQLite storage
- admin panel for pages, sections, categories, posts, media, users, settings, and theme files
- read-only system checks for PHP, SQLite, filesystem access, upload limits, and basic deployment security

## Git Notes

- SQLite database file is stored in `storage/database.sqlite` and is ignored by git
- uploaded files in `public/uploads/` are ignored by git
- template backups in `storage/template-backups/` are ignored by git
- required empty folders are kept with `.gitkeep`

## Production web root

Configure the web server document root to the absolute path of `public/`. Route
requests for files and directories normally and send all other requests to
`public/index.php`. Never expose the repository root as the document root because
it contains application source, configuration, and storage files.

For Apache, the included `public/.htaccess` provides the rewrite rule when
`mod_rewrite` and per-directory overrides are enabled.

Only `storage/`, `public/uploads/`, and editable theme files need write access
from the web-server account. `Settings → System Check` tests the effective
access and provides platform-appropriate recommendations; it never changes
permissions automatically.

Administrator sessions use `HttpOnly` and `SameSite=Lax` cookies, regenerate
their ID after authentication, and expire after 30 minutes without admin
activity. Change `security.admin_session_idle_timeout` in `settings.php` when a
different timeout is required.

## Smoke tests

With PHP and PowerShell available on `PATH`, run the complete suite:

```powershell
.\tests\run-smoke.ps1
```

## Rebuilding the admin editor

The admin panel uses Tiptap 3.30.2 from the committed local bundle, so editing
content does not depend on a CDN. To rebuild that bundle after changing its
dependencies:

```powershell
npm install
npm run build:editor
```

Commit `package-lock.json` together with dependency upgrades and keep all Tiptap
packages pinned to the same version.

## Template variables

The active theme can use the following variables in `.tpl` files:

- content: `[HEADER]`, `[CONTENT]`, `[FOOTER]`
- metadata: `[PAGE_TITLE]`, `[META_KEYWORDS]`, `[META_DESCRIPTION]`
- site URLs: `[SITE_NAME]`, `[SITE_URL]`, `[HOME_URL]`, `[CURRENT_URL]`, `[ADMIN_URL]`
- theme and page values: `[THEME_URL]`, `[BODY_CLASS]`, `[HTML_LANG]`, `[CURRENT_YEAR]`

The same reference is available in the admin panel under Templates. Content,
header, and footer contain rendered HTML; other values are HTML-escaped.

## Roadmap

- cleaner starter content and a more neutral default homepage
- theme documentation for designers and frontend handoff
- role and permission improvements when a real project requires them

## Goal

JenCMS should remain a practical reusable foundation for launching new sites quickly, then customizing structure, theme, and content on top of it.
