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

Clone the repository and run the built-in PHP server from the project root:

```powershell
php -S localhost:8000 index.php
```

Open in browser:

- Site: `http://localhost:8000/`
- Admin: `http://localhost:8000/admin`

On first launch, JenCMS creates the SQLite database automatically using [storage/migrations/001_init.sql](/d:/Denis/JenCms/storage/migrations/001_init.sql).

## Default Admin Login

- Login: `admin`
- Password: `admin123`

Change the password after the first login.

## Project Structure

- `public/` - public entrypoint and uploaded files
- `src/` - application core
- `themes/` - frontend themes
- `views/` - admin panel views
- `storage/` - migrations, database, starter content, and template backups
- `settings.php` - main configuration

## Current Scope

JenCMS currently provides:

- one frontend theme: `themes/default`
- one-language content management
- SQLite storage
- admin panel for pages, sections, categories, posts, media, users, settings, and theme files

## Git Notes

- SQLite database file is stored in `storage/database.sqlite` and is ignored by git
- uploaded files in `public/uploads/` are ignored by git
- template backups in `storage/template-backups/` are ignored by git
- required empty folders are kept with `.gitkeep`

## Roadmap

- cleaner starter content and a more neutral default homepage
- theme documentation for designers and frontend handoff
- optional installation script or setup checks
- role and permission improvements
- cleaner public release structure and documentation

## Planned Cleanup

- remove older leftover artifacts if any appear during future refactors
- simplify admin wording and polish UX
- review naming consistency across modules and directories
- add a small smoke-test checklist for releases

## Goal

JenCMS should remain a practical reusable foundation for launching new sites quickly, then customizing structure, theme, and content on top of it.
