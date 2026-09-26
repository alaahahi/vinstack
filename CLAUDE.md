# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project

"KAML KAMAL" (`vinstack-lite`) — a Laravel 12 + Vue 3 SPA for managing imported vehicles: it syncs vehicles from the external **Vinstack** and **AutoShipper** APIs, lets an admin assign them to dealers, and gives each dealer a portal for their own vehicles/containers. There is also an auction-search feature backed by the **Apibara** vehicle-auction API.

UI is trilingual (`ar` default, `en`, `ckb`) with RTL for `ar`/`ckb`. Much of the user-facing copy, and some code comments/docs, are in Arabic — keep that convention when editing those files.

## Commands

```bash
composer dev          # all-in-one: php artisan serve + queue:listen + pail (logs) + vite
php artisan serve     # backend only  (http://127.0.0.1:8000)
npm run dev           # vite dev server only
npm run build         # production assets into public/build (COMMITTED to git — see Deployment)

composer test                                    # config:clear then artisan test
php artisan test --filter=AdminVehicleIndexTest  # single test class
php artisan test tests/Unit/SqliteBusyTest.php   # single file
php artisan test --testsuite=Unit                # one suite (Unit | Feature)

vendor/bin/pint       # formatter (Laravel Pint, no pint.json — default preset)

php artisan vinstack:sync [--force]     # pull vehicles from Vinstack /autos
php artisan autoshipper:sync
php artisan image-transfers:process     # drain queued Cloudinary image-transfer jobs
php artisan schedule:work               # runs the three above on their schedule
```

Node 20+ is required (Vite 7). Tests run against an in-memory SQLite DB (see `phpunit.xml`); they do not touch `database/database.sqlite`.

## Architecture

### Request flow

`routes/web.php` serves the `app` Blade view for **every** path except `/api/*` — the Vue router owns all UI routing. Don't add web routes that could shadow `/api`; the file has a comment explaining the failure mode.

`routes/api.php` is the whole backend surface, split into three guarded groups behind `auth:sanctum`:

- `role:admin,dealer` + `/auctions` — Apibara auction search, favorites, spotlight
- `role:admin` + `/admin` — vehicles, dealers, containers, settings, system/backup, Vinstack browse
- `role:dealer` + `/dealer` — the dealer's own vehicles, containers, profile, messages

`role` is the alias for `App\Http\Middleware\EnsureUserHasRole` (registered in `bootstrap/app.php`), which compares `User::$role` against the `UserRole` enum.

Controllers mirror that split: `app/Http/Controllers/{Admin,Dealer,Api}`. Controllers stay thin — business logic lives in `app/Services` (~35 services) and single-purpose `app/Actions` (`SyncVehiclesAction`, `AssignVehicleAction`, `CreateManualVehicleAction`, …).

### Auth

Phone-number login, Sanctum bearer tokens stored client-side (`localStorage`, or `sessionStorage` when `auth_isolated` is set — see `resources/js/api/client.js`).

- Admin: phone → token immediately.
- Dealer: phone → either a **2FA setup** or **2FA challenge** response carrying a short-lived opaque token. `App\Support\TwoFactorToken` holds these in the cache for 5 minutes keyed `2fa:{type}:{token}`; the dealer then posts to `/api/two-factor/{setup,confirm,challenge}`.

Fortify is installed but `Fortify::ignoreRoutes()` — only its 2FA plumbing and the `two-factor` rate limiter are used. Token lifetime comes from `SANCTUM_EXPIRATION`.

### Vehicles: the `raw_data` convention

`Vehicle` has typed columns (`vin`, `make`, `model`, `year`, `price`, `eta`, `status`, `source`) plus a JSON `raw_data` blob holding the untouched upstream payload. A lot of logistics data is only in `raw_data`, read through helpers rather than ad-hoc `Arr::get`:

- `App\Support\VehicleRawDataLocations` — origin/destination/port key fallbacks and the logistics scalar whitelist
- `App\Support\VehicleLogisticsStatus`, `VehicleEta`, `VehicleOptions`
- `Vehicle::scopeNewestFirst()` sorts by `raw_data->purchase_date` and has **separate SQLite and MySQL SQL branches** — any similar JSON-ordering query needs both.

`source` (`VehicleSource`) distinguishes `vinstack` / `autoshipper` / `manual` / `nujoom_al_jazeera` records; sync only touches its own source. Vehicles are soft-deleted, and sync reports "restorable" matches instead of recreating them.

### Vehicle images

Images come from three places and are merged for display:

1. Vinstack/upstream URLs in `raw_data` / `images`
2. Admin-uploaded files (`VehicleUploadedImage`)
3. Admin-persisted ordering in `raw_data.images_by_stage`

`App\Support\VehicleImageStages` classifies URLs into the three stages `terminal | pickup | destination`; `App\Support\VehicleGalleryMerger` merges upstream + uploaded + persisted order into the gallery payload. Go through the merger rather than reading `images` directly.

### Image transfer pipeline (Cloudinary)

Bulk ZIP uploads for container/vehicle photos are asynchronous by design, because the target hosting often has no queue worker:

`ImageTransferService` extracts the ZIP to `storage/app/image-transfers/{uuid}`, matches each entry to a vehicle, and writes an `ImageTransferJob` row with a manifest. `ImageTransferProcessor` then works the manifest in batches — driven by the `image-transfers:process` scheduled command (every minute) and/or `ProcessImageTransferBatch`. Live progress lives in `ImageTransferProgressStore` (cache), not the DB, and is flushed periodically; stale `processing` jobs are reclaimed after `ImageTransferJob::STALE_AFTER_MINUTES`.

### SQLite concurrency

Default DB is SQLite, with PHP-FPM, the scheduler, and image transfers all writing. This shapes several deliberate choices — don't "simplify" them away:

- `config/database.php` sets WAL, `busy_timeout`, `synchronous=NORMAL`; `.env.example` pushes `SESSION_DRIVER=file`, `CACHE_STORE=file`, `QUEUE_CONNECTION=sync` so sessions/cache/jobs don't contend with app data.
- `App\Support\SqliteBusy::soft()` swallows lock errors for non-critical writes; `::retry()` retries critical ones.
- `App\Models\PersonalAccessToken` skips/throttles Sanctum `last_used_at` writes (`SANCTUM_SKIP_LAST_USED_AT_ON_SQLITE`).
- `AdminVehicleIndexCache` caches the expensive admin vehicle list for 300s under a version key; `AdminVehicleIndexCacheObserver` (registered in `AppServiceProvider`) bumps that version on `Vehicle` / `VehicleAssignment` / `VehicleUploadedImage` writes. New writes that should invalidate the list need to go through that observer.

### Settings live in the DB, not just `.env`

`VinstackSetting::current()` is a singleton row holding API base URLs, encrypted tokens, Cloudinary creds, WhatsApp-queue config, sync toggles and `dealer_notification_events`. `.env` values are only the seed defaults. Auction API keys are per-provider rows (`AuctionApiProvider`) with quota tracking and automatic failover in `AuctionApiProviderService`. When adding a setting, prefer the DB row so admins can change it from Admin → Settings.

### External integrations

| Service | Entry point | Notes |
|---|---|---|
| Vinstack | `VinstackService` | Base URL `https://app.vinstack.com/api/v1/client`; vehicles come from `/autos`, **not** `/vehicles`. Also `/containers`, `/invoices`, `/loading-lists`, `/payments`, `/parts`, `/quotes`. Container tracking probes several paths and returns `null` on 404. |
| AutoShipper | `AutoShipperService` | Second vehicle source, synced hourly at :30. |
| Apibara | `ApibaraAuctionService`, `ApibaraUsageService` | Auction search; server-side key only, never exposed to Vite. Usage/quota logged to `ApibaraRequestLog`. |
| Cloudinary | `CloudinaryService` | Image hosting for container/vehicle ZIP uploads. |
| WA Queue | `WaQueueService`, `DealerNotificationService` | WhatsApp dealer notifications, event-gated by `dealer_notification_events`. |

Containers are **not** a local table — `ContainerService` builds them by merging the Vinstack `/containers` response with containers derived from `Vehicle.raw_data.container_number`. Only container *images* are stored locally (`ContainerImage`).

### Monitor module

`app/Monitor/**` is a **self-contained, portable observability module** (own service provider in `bootstrap/providers.php`, own routes under `/monitor/api/*` and `/monitor/dashboard`, own tests in `tests/{Unit,Feature}/Monitor`). It writes JSONL to `storage/logs/monitor/` — no DB tables — and is copied between projects via `scripts/copy-monitor-module.*`. Keep it free of app-specific dependencies; it targets Laravel 9+/PHP 8.0+ while the host app is Laravel 12/PHP 8.2. Global exception reporting is wired to it in `bootstrap/app.php`.

### Frontend

`resources/js/` — Vue 3 (Composition API, `<script setup>`), Pinia, Vue Router, PrimeVue (Aura preset, dark mode via `[data-theme="dark"]`), Tailwind 4, vue-i18n.

- `api/client.js` — the single axios instance: injects the bearer token, `Accept-Language`, strips the JSON content-type for `FormData`, and redirects to login on 401. Add API calls as functions in `api/*.js`, not inline `axios` calls.
- `router/index.js` — routes carry `meta: { requiresAuth, role, guest, titleKey, subtitleKey }`; the `beforeEach` guard enforces role and the layouts read `titleKey` for the page header, so new pages need those meta keys plus entries in all three `i18n/messages/*.js` files.
- `constants/locales.js` is the source of truth for locale codes and direction; `i18n/index.js` applies `lang`/`dir`/`data-locale` to `<html>`.
- Layouts: `AdminLayout.vue` / `DealerLayout.vue`.

## Deployment

The target hosting has **no SSH** (see `docs/DEPLOY_NO_SSH.md`), which is why:

- `public/build/` is **committed** — run `npm run build` locally and commit the output before deploying.
- Root-level `index.php`, `.htaccess`, and `web.config` exist so the document root can be the project root instead of `public/`. Don't delete them.
- Migrations and cache clearing are exposed through the admin UI (`SystemController`, `DatabaseBackupController`) because artisan isn't reachable on the server.
