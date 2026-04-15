# Changelog

All notable changes to Ultimate Dashboard are documented here.

---

## [0.0.3] - Favicon

> Bugfix release

### Added
- SVG favicon (`logo.svg`) on all pages - browser tab and bookmark bar now show the dashboard logo

### Docker Hub
- Published as `extremedatacenter/ultimatedashboard:0.0.3`
- Also tagged as `extremedatacenter/ultimatedashboard:latest`

---

## [0.0.2] - Particle Background Fix

> Bugfix release

### Fixed
- Particle network background animation not rendering on login and dashboard pages
  (removed `prefers-reduced-motion` early-return that blocked the canvas on most Windows systems)

### Docker Hub
- Published as `extremedatacenter/ultimatedashboard:0.0.2`
- Also tagged as `extremedatacenter/ultimatedashboard:latest`

---

## [0.0.1] - First Version

> Initial public release

### Added
- Tile-based dashboard with named group organisation
- Full CRUD for tiles and groups via Edit mode
- Drag-to-reorder tiles within and between groups
- Custom logo upload per tile - PNG, JPG, SVG, ICO, WebP (max 2 MB)
- Logos fill the entire upper portion of the tile card
- Auto-fetch favicon from any URL via Google Favicon Service
- Permanent remember-me login (1-year cookie) - ideal as a browser start page
- Animated particle-network canvas background
- Multi-user support with admin and regular user roles
- Settings page - custom app name and logo, user management
- SQLite database with WAL mode - zero external dependencies
- PHP 8.2 + Apache in a single Docker container
- Traefik v3 integration with Cloudflare DNS-01 automatic HTTPS
- CSRF protection on all API endpoints
- Keyboard shortcut `E` to toggle edit mode
- Mobile-responsive layout with hamburger menu
- Toast notifications for all actions
- Accent colour picker per tile
- 12 built-in group icons (grid, server, activity, shield, database, cloud, globe, tool, star, home, lock, mail)

### Docker Hub
- Published as `extremedatacenter/ultimatedashboard:0.0.1`
- Also tagged as `extremedatacenter/ultimatedashboard:latest`
- Image size: ~511 MB (PHP 8.2 Apache base)

---

*Format based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/).*
