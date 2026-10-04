# Porfolio (hosted at [zcraig.me](https://zcraig.me))

My personal portfolio and hobby-tools site

## Features

### Portfolio homepage
Project showcase and tech-stack breakdown

The banner background rotates through a curated set
of images with support for pinned/scheduled entries and responsive art
direction per breakpoint.

### Amateur radio logbook & map
Imports my latest radio logs from the QRZ API daily, parses the ADIF and fetches additional data via the parks on the air API

Exposes an API endpoint that lets you query my logbook by radio band and mode, as well as search

Also supports a GeoJSON endpoint that's used to render the map pins on the main logbook page for interesting visualization

### Photo gallery
VueJS based gallery of some photos i've taken using the flickr API

### Phone number lookup ("Who's Calling Me?")
A public reverse phone lookup tool backed by Twilio, with per-IP/user rate
limiting and identity details gated behind authentication.

### File uploads
Authenticated users get a personal file area — upload, browse, and delete
files stored on a configurable disk (DigitalOcean Spaces in production),
served through a CDN with automatic cache purging on delete.

### Admin panel
A small custom Livewire-based admin area (no external package) for managing
the background image schedule and logbook data

### Account panel
Livewire forms for users to self-manage API keys and revoke them on-demand powered by Laravel Sanctum

### Ops
[Laravel Pulse](https://laravel.com/docs/pulse) for basic performance/usage monitoring

## Tech stack
- **Backend:** Laravel 13, PHP 8.4, MySQL 8, Redis
- **Frontend:** Blade + Tailwind CSS v4 + Vite, with Vue 3 "islands" for
  interactive pieces (the map, photo gallery, homepage showcase) and
  Livewire 3 for the admin and account panels
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

The app runs at `http://localhost` by default

```bash
./vendor/bin/sail artisan test    # PHPUnit TEST suite (Unit + Feature)
./vendor/bin/sail npm run dev     # Vite dev server with hot-reload
```

### Frontend bundles

Vue keeps its template compiler because the root adopts Blade-rendered markup.
The showcase, photo gallery, and radio map load on demand; MapLibre's JS, CSS,
and worker are only requested by the radio island. Loading and reload-on-error
messages cover deferred component requests. The radio chunk can still exceed
Vite's 500 kB advisory limit; it is not needed on other pages.

Font Awesome uses a small SVG icon registry in `resources/js/icons.js`, with
DOM watching for Vue/Livewire updates and support for legacy aliases and icon
stacks. Add icons there when changing Blade or config-driven project icons.
Unused Vuetify/MDI imports are not part of the active bundle; dependencies and
legacy Sass files remain available. Banner image URLs, including the emergency
fallback, come from `HomeBanner` via `asset()` rather than Vite CSS resolution.

```bash
./vendor/bin/sail npm run build          # generate manifest for bundle checks
./vendor/bin/sail npm run test:frontend  # icon, island, and built manifest checks
./vendor/bin/sail npm run test:qso-map   # radio map lifecycle and behavior
```
