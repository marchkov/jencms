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

## Requirements

- PHP 8.1 or newer

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

On first launch, JenCMS creates the SQLite database automatically using
[`storage/migrations/001_init.sql`](storage/migrations/001_init.sql).

## Default Admin Login

- Login: `admin`
- Password: `admin123`

Change the password after the first login.

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

## Routing smoke test

With PHP available on `PATH`, run:

```powershell
.\tests\routing-smoke.ps1
php .\tests\content-storage-smoke.php
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

## Roadmap

- cleaner starter content and a more neutral default homepage
- theme documentation for designers and frontend handoff
- optional installation script or setup checks
- role and permission improvements

## Goal

JenCMS should remain a practical reusable foundation for launching new sites quickly, then customizing structure, theme, and content on top of it.
