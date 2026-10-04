# zcraig.me

My personal portfolio and hobby-tools site — a Laravel app that doubles as a
place to point people at my work and a place to run a few things I actually
use day to day.

## Features

### Portfolio homepage
Project showcase and tech-stack breakdown, both driven from config rather
than hardcoded markup. The banner background rotates through a curated set
of images with support for pinned/scheduled entries and responsive art
direction per breakpoint.

### Amateur radio logbook & map
The feature I've spent the most time on. A queued job pulls my logbook from
QRZ's XML API, parses the ADIF records, and enriches any Parks on the Air
(POTA) activations by resolving park references against the POTA API —
including a crosswalk for POTA's historical reference-numbering migrations,
so old-format references from years-old QSOs still resolve correctly.
Contacts are served as GeoJSON to a Vue + MapLibre GL map with:
- Band and mode filtering, full-text search across station/location fields
- Markers color-coded by band and shaped by mode (phone, digital, SSTV, etc.)
- Newer contacts rendered larger and brighter than older ones
- A curved path drawn from my QTH to a selected contact, antimeridian-aware
  so it doesn't cut a straight line across the whole map for DX contacts
- A legend that stays in sync with the actual marker colors

### Photo gallery
A lightweight Vue component pulling directly from my Flickr photostream.

### Phone number lookup ("Who's Calling Me?")
A public reverse phone lookup tool backed by Twilio, with per-IP/user rate
limiting and identity details gated behind authentication.

### File uploads
Authenticated users get a personal file area — upload, browse, and delete
files stored on a configurable disk (DigitalOcean Spaces in production),
served through a CDN with automatic cache purging on delete.

### Admin panel
A small custom Livewire-based admin area (no external package) for managing
homepage backgrounds and logbook visibility overrides, gated by an
`is_admin` flag rather than a full roles/permissions system.

### Ops
[Laravel Pulse](https://laravel.com/docs/pulse) for basic performance/usage
monitoring, restricted to admins.

## Tech stack

- **Backend:** Laravel 13, PHP 8.4, MySQL 8, Redis
- **Frontend:** Blade + Tailwind CSS v4 + Vite, with Vue 3 "islands" for
  interactive pieces (the map, photo gallery, homepage showcase) and
  Livewire 3 for the admin panel
- **Maps:** MapLibre GL JS
- **Local dev:** Docker via [Laravel Sail](https://laravel.com/docs/sail)
- **External services:** QRZ (logbook import), Parks on the Air (POTA park
  data), Twilio (phone lookup), Flickr (photo gallery), DigitalOcean Spaces
  (file storage + CDN), Sentry (error tracking)

## Local development

Requires Docker.

```bash
composer install
cp .env.example .env
./vendor/bin/sail up -d
./vendor/bin/sail artisan key:generate
./vendor/bin/sail artisan migrate
./vendor/bin/sail npm install && ./vendor/bin/sail npm run build
```

(`composer install` runs on the host to bootstrap `vendor/bin/sail` itself; every
step after that runs inside the container. If you don't have PHP/Composer on
your host, run that first step in a one-off container instead, e.g.
`docker run --rm -v "$(pwd):/var/www/html" -w /var/www/html laravelsail/php84-composer:latest composer install`.)

The app runs at `http://localhost:8090` by default (see `.env` for the full
set of non-default ports this project uses, so it can run alongside other
projects on the same machine).

```bash
./vendor/bin/sail artisan test    # PHPUnit suite (Unit + Feature)
./vendor/bin/sail npm run dev     # Vite dev server with HMR
```

## Application configuration

The app uses Laravel's modern application structure:

- `bootstrap/app.php` configures routing, middleware, and Sentry exception
  reporting. Laravel supplies the standard middleware, including Laravel 13's
  origin-aware request-forgery protection with CSRF-token fallback.
- `bootstrap/providers.php` registers application providers. `AppServiceProvider`
  owns policies, gates, production HTTPS URL generation, and production-only
  Sentry provider registration.
- `routes/console.php` defines console routes and the daily `logbook:import`
  schedule. The server still needs to run Laravel's scheduler.
- `config/trustedproxy.php` retains the optional `TRUSTED_PROXIES` environment
  setting using Laravel's built-in proxy middleware. Leave it unset for direct
  Nginx/PHP-FPM hosting; only configure verified upstream proxy addresses or
  CIDRs, not `*`.

For the first deployment of this structure, run `php artisan config:clear` on
the old release before replacing its files: an old configuration cache can
still reference the removed providers. After deployment, run
`php artisan optimize` to rebuild configuration, route, event, and view caches,
and `php artisan queue:restart` to reload long-running queue workers. Use the
site's PHP version. Legacy application kernels, exception handler, and
routing/auth/event providers have been removed.
