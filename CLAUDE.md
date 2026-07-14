# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project

"CMP Jeunesse — Administration": a Laravel 13 / PHP 8.3 admin backend for a church youth ministry
(Communauté... CMP, `eglisecmp.com`) that runs "La Grande Retraite des Jeunes" (an annual youth
retreat): public registration, payment collection (Mobile Money + card via FlexPay, plus cash),
room/workshop ("chambres"/"ateliers") placement, attendance tracking, badge printing, SMS
notifications, and voluntary donations. The Filament admin panel is branded accordingly.

## Commands

- Install PHP deps: `composer install`
- Install JS deps: `npm install`
- Full local setup (env, key, migrate, npm build): `composer run setup`
- Dev (serves app + queue listener + `pail` logs + vite, concurrently): `composer run dev`
  - Equivalent to running `php artisan serve`, `php artisan queue:listen --tries=1 --timeout=0`,
    `php artisan pail --timeout=0`, and `npm run dev` together.
- Build frontend assets: `npm run build`
- Run tests: `composer run test` (clears config, then `php artisan test`)
  - Single test: `php artisan test --filter=TestName` (or a full path, e.g.
    `php artisan test tests/Feature/SomeTest.php`)
- Format PHP: `vendor/bin/pint` (Laravel Pint is a dev dependency; no static analysis tool
  such as PHPStan/Larastan is configured)

## Architecture

**Fat services, thin controllers/actions.** Business logic lives almost entirely in
`app/Services/` (30+ single-purpose services, e.g. `RetreatParticipantRegistrationService`,
`RetreatPlacementAssignmentService`, `RetreatInscriptionFunnelService`,
`RetreatEventLogisticsLifecycleService`), with domain sub-namespaces under
`app/Services/FlexPay/`, `app/Services/RetreatDonation/`, `app/Services/RetreatRegistration/`,
and `app/Services/QrCode/`. Controllers (`app/Http/Controllers`, `Api/`) are thin and delegate to
services. There are no repository or dedicated Action classes — logic is service-first. Only one
queued job exists: `app/Jobs/FulfillRetreatRegistrationJob.php`; queue driver is `database`.
Model/lifecycle side effects (e.g. `RetreatPaymentObserver`) live in `app/Observers/`.
Authorization beyond Filament Shield roles/permissions is enforced via `app/Policies/` and the
`EnsureSuperAdmin` middleware plus a `super_admin` route middleware guard.

**Admin panel:** Filament v5 panel (`app/Providers/Filament/AdminPanelProvider.php`), mounted at
`/admin`, branded "CMP Jeunesse — Administration". Uses `bezhansalleh/filament-shield` for
roles/permissions (`spatie/laravel-permission` underneath), plus several third-party Filament
plugins (media manager, draggable modals, record watcher, search spotlight, API docs builder,
tabbed forms, auth designer, sticky columns, hover-image column, QR/image-gallery components).
Resources live under `app/Filament/Resources/` — one per domain entity (RetreatParticipants,
RetreatPayments, RetreatChambres, RetreatAteliers, RetreatSessions, RetreatActivityPlans,
RetreatActivityAttendances, RetreatVoluntaryDonations, ChurchEvents, RegistrationFormConfigs,
Users, Roles, SmsMessageLogs/Operators, GeneratedQrCodes, etc.). A custom Filament page
(`FlexPayPaymentTest`) exists for manually exercising the payment integration.

**Routes:**
- `routes/web.php`: the public retreat portal (`/`, `/inscription-retraite`, `/don-retraite`),
  token-based public pages for tickets/justificatifs/access (`{token}` constrained to 32
  alphanumeric chars), the FlexPay card-payment return callback, an OTP-based
  "verification-retraite" staff portal (attendance scanning/marking), and a React-based
  "studio-badge" (badge design/printing) app restricted to `auth` + `super_admin`.
- `routes/api.php`: versioned JSON API under `api/v1/retreat/...` — public registration flow
  (event lookup, duplicate hints, OTP contact verification, registration, funnel tracking,
  mobile/card/cash payment initiation, FlexPay webhook), a separate `donations` sub-API
  (in-kind/cash/mobile/card donation flow + sponsorship vouchers), and an internal
  integration API (participants, chambres, ateliers, sessions, activity plans, attendances)
  presumably used to sync with another system.

**Payments (FlexPay):** Mobile Money and card payments go through `app/Services/FlexPay/`
(`FlexPayMobileService`, `FlexPayCardService`, `FlexPayMsisdnValidator`,
`FlexPayTransactionStatusReader`, plus a `FlexPayTestService`). Config in `config/retraite.php`.
Critical gotcha (see `docs/integration-paiement-flexpay/08-MOBILE-MONEY-CORRECTIFS.md`): the
FlexPay API `type` field must always be `"1"` for Mobile Money regardless of operator
(M-Pesa/Airtel/Orange/Afri), and `"2"` only for card payments — the operator selected in the UI
is only used for local phone-number validation, not for the API `type`. `docs/integration-paiement-flexpay/`
also contains full reference backend/frontend code and setup docs (`01-CONFIGURATION.md`,
`04-ROUTES.md`, `05-MIGRATIONS.md`, `07-EXEMPLE-ENV.md`) for this integration.

**Data model highlights:** `ChurchEvent` (retreats are `type = 'retraite'`), `RetreatParticipant`
(registration, funnel, family-group, badge/access state), `RetreatPayment` (+ failure alerts and
a `RetreatPaymentFailureAlert`/`RetreatPaymentFailureNotifier`), `RetreatChambre`/`RetreatAtelier`
(room/workshop placement), `RetreatActivityPlan`/`RetreatActivityAttendance` (schedule +
attendance), `RetreatVoluntaryDonation` + `RetreatSponsorshipVoucher` (donations/sponsorship),
`SmsMessageLog`/`SmsOperator` (SMS delivery tracking, sent via `KeccelSmsService`),
`GeneratedQrCode`, and `RegistrationFormConfigSet`/`RegistrationFormFieldItem` (dynamically
configurable public registration form). Migrations show the app replaced/absorbed a legacy
Django-based system (`create_legacy_django_schema` then `remove_django_legacy_and_redundant_schema`),
and there's a `ProductionBaseSyncController`/`ProductionBaseDataSyncService` for syncing base
data (token-protected route `system/sync-production-base/{token}`).

**Frontend:** Primarily server-rendered Blade views (`resources/views/`), including the public
retreat registration/donation portals (`retraite-inscription/`, `retraite-don/`) and Filament's
own Blade views/overrides (`resources/views/filament/`, `vendor/filament-panels/`). One isolated
React 19 app (`resources/js/studio-badge/`) is built via Vite (`@vitejs/plugin-react`,
`laravel-vite-plugin`) for the badge design studio; it uses `html2canvas`/`jspdf` for
badge image/PDF export. Styling via Tailwind CSS v4 (`@tailwindcss/vite`). No Livewire or
Inertia usage beyond what Filament itself uses internally.

**Auth:** Single `web` guard, Eloquent `User` provider (`app/Models/User.php`), roles/permissions
via Spatie permission + Filament Shield. A separate OTP-based session flow (not Laravel guards)
protects the staff "verification-retraite" attendance portal.
