# Changelog

All notable changes to this project will be documented in this file.

## [Unreleased]

### Changed

- made `public/` the single supported web root
- moved the default theme under `public/themes/`
- converted content forms to a full-width stacked layout
- replaced the legacy rich-text editor with locally bundled Tiptap 3.30.2
- changed automatic schema fixes to ordered, transaction-safe SQLite migrations
- hardened administrator sessions and filesystem creation permissions
- validate uploaded file content with Fileinfo and reject SVG uploads
- removed duplicate and unused template-variable aliases

### Added

- PHP development-server router and Apache rewrite configuration under `public/`
- visual/source and fullscreen editor modes
- Media Library image selection and authenticated AJAX image uploads
- reproducible npm build files for the editor bundle
- routing and raw HTML storage smoke tests
- `Settings → System Check` diagnostics for the PHP environment, filesystem, SQLite, uploads, and default password
- migration, session-security, and system-check smoke tests
- a single smoke-suite runner
- template-variable reference in the Templates section

### Fixed

- static theme, upload, and admin asset routing
- template-backup error handling

## [v0.1.0] - 2026-03-26

First public foundation release of JenCMS.

### Added

- lightweight PHP + SQLite CMS core
- admin panel for pages, sections, categories, posts, users, media, settings, and templates
- one-language content model
- default light theme
- automatic SQLite bootstrap on first run
- starter README and repository cleanup

### Notes

- JenCMS is positioned as a reusable base for future website projects
- this release establishes the initial git baseline for further development
