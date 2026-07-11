# Native Dads Network CMS — Backend Build Checklist

Last updated: July 10, 2026

This checklist tracks local database and Laravel backend work based on `docs/ndn-cms-build-schematic.md`. Frontend, AWS, production deployment, and server configuration are intentionally out of scope for this phase.

## Completed

### Local foundation

- [x] Confirmed the project is Laravel 13 and uses SQLite locally.
- [x] Kept `DB_CONNECTION=sqlite` and local filesystem storage for development.
- [x] Added the CMS package dependencies for permissions, activity logging, slugs, and media handling.
- [x] Published package configuration and migrations.
- [x] Added `config/admin.php` for allowed administrator domains and invite expiration.
- [x] Added local environment placeholders for the domain lock and optional seeded super administrator.
- [x] Added an environment-backed local admin seeder and seeded Isaac's requested local administrator account.
- [x] Kept Gmail authorization local-only; `.env.example` and production defaults remain locked to `nativedadsnetwork.org`.
- [x] Removed stale starter-kit registration imports, links, and page after public registration was disabled.

### Database structure

- [x] Added administrator account status, last-login timestamp, and soft deletes to users.
- [x] Added invite records with hashed tokens, pending-email uniqueness, roles, expiration, and acceptance history.
- [x] Added pages with hierarchy, locale-aware sibling slugs, JSON blocks, publishing, SEO, locking, sorting, audit columns, and soft deletes.
- [x] Added programs with JSON blocks, publishing, SEO, contact details, sorting, audit columns, and soft deletes.
- [x] Added events with JSON descriptions, publishing, SEO, timezone/location/virtual-event fields, audit columns, and soft deletes.
- [x] Added news posts, categories, and the category/post pivot.
- [x] Added galleries and the ordered gallery/media pivot with per-gallery captions and alt text.
- [x] Added team members with optional public contact fields and first-class photo references.
- [x] Added header/footer menus and nested polymorphic menu items.
- [x] Added grouped JSON settings.
- [x] Added contact form submissions with read state and privacy-preserving IP hash storage.
- [x] Added first-class media assets backed by Spatie Media Library.
- [x] Added normalized media references for block and structured-field usage tracking.
- [x] Added redirect records for future page path and legacy URL redirects.
- [x] Added Spatie permission, activity log, and media package tables.

### Eloquent and domain behavior

- [x] Added publish-status and role enums.
- [x] Added Eloquent models, casts, soft deletes, relationships, audit relationships, and activity logging.
- [x] Added reusable published query scope and `isPublished()` behavior.
- [x] Added page path generation and SQLite/MySQL-consistent root slug uniqueness support.
- [x] Added event upcoming/in-progress query behavior.
- [x] Added local media collections and image conversions.
- [x] Added cached site settings with automatic cache invalidation.

### Authentication and authorization

- [x] Disabled public Fortify registration.
- [x] Added exact-match, case-insensitive administrator email-domain validation.
- [x] Added login-time enforcement for active accounts and allowed email domains.
- [x] Added request-time CMS middleware that logs out unauthorized or deactivated accounts.
- [x] Added hashed, expiring, single-use invite creation and acceptance.
- [x] Added queued invite email support using the local log mailer.
- [x] Added database transactions and row locking around invite acceptance.
- [x] Added super-admin, admin, and editor roles with module permissions.
- [x] Added content policies, including the editor restriction to deleting only their own drafts.
- [x] Added the scheduled expired-invite pruning command.

### Content backend

- [x] Added a shared JSON block sanitizer with a v1 block allowlist.
- [x] Added Tiptap JSON node/mark allowlisting and unsafe-link removal.
- [x] Added YouTube/Vimeo-only validation for video blocks.
- [x] Added unknown block field stripping, block ID validation, and basic layout value normalization.
- [x] Added transactional media-reference rebuilding for JSON blocks and structured media fields.
- [x] Added shared backend CRUD behavior for content modules.
- [x] Added page, program, event, and post validation/controllers.
- [x] Added CMS dashboard count data and invite endpoints.
- [x] Added invite resend support.
- [x] Added category management for news posts.
- [x] Added activity-log access for authorized administrators.
- [x] Added gallery, team member, media, menu, setting, contact inbox, and user-management controllers.
- [x] Added reorder endpoints for programs, galleries, and team members.
- [x] Added page path-change redirect generation for renamed pages and their descendants.
- [x] Added media deletion protection for block references, galleries, SEO images, and team photos.
- [x] Added signed, one-hour JSON preview links for draft pages, programs, events, and posts.
- [x] Added super-admin restore and permanent-delete endpoints for soft-deleted content.
- [x] Added date-only start/end fields and upcoming-query behavior for all-day events.
- [x] Added privacy-preserving request IP hashes to activity records and weekly activity retention cleanup.
- [x] Added media-reference indexing for media IDs stored in JSON site settings.
- [x] Added backend-only JSON responses so no React admin screens are required during this phase.

### Verification

- [x] Added last-super-admin demotion/deactivation protection with transactional row locks.
- [x] Added super-admin 2FA enforcement middleware.
- [x] Disabled administrator self-deletion; accounts are deactivated and retained for audit history.
- [x] Added the public contact submission endpoint, rate limit, honeypot, minimum-submit-time check, queued mail notification, and 24-month retention command.
- [x] Added a Native Dads Network user factory state/default for backend tests.
- [x] Added invite/domain, account security, publishing, block sanitizer, page hierarchy/redirect, and contact feature tests.
- [x] Ran local SQLite migrations and seeders successfully.
- [x] Verified every migration reports as applied.
- [x] Ran Laravel Pint successfully.
- [x] Ran Larastan/PHPStan at level 7 with zero errors.
- [x] Ran TypeScript, ESLint, Prettier, client build, and Inertia SSR build successfully.
- [x] Added module-level CRUD/policy coverage for programs, posts/categories, galleries, team members, menus, recovery, previews, and settings media references.
- [x] Added endpoint coverage for timed/all-day events, private local document uploads, categories, contact inbox management, and user-management boundaries.
- [x] Ran the full Pest suite: 70 tests, 68 passed, 2 intentionally skipped, 253 assertions.
- [x] Verified CMS routes and scheduled commands are registered.

## Audit follow-up (July 10, 2026)

A full adversarial re-audit of the claimed-done backend confirmed every mechanical claim above (migrations applied, routes/scheduler registered, Pest 68→76 passing, Pint clean, PHPStan level 7 clean, TS/ESLint/Prettier/build/SSR clean) and surfaced several real defects, now fixed:

### Fixed

- [x] **Privilege escalation:** an `admin` could promote any account (including their own) to `super_admin` via `PATCH /admin/users/{user}`; the `role` field was validated against the full enum. Now restricted so only a super admin may grant `super_admin` (`UserController::update`), matching the invite flow.
- [x] **Two-factor bypass:** super-admin 2FA middleware checked `two_factor_secret !== null`, which is set at setup start; with Fortify `confirm => true`, 2FA is not active until confirmed. Now checks `hasEnabledTwoFactorAuthentication()` (`EnsureSuperAdminHasTwoFactor`).
- [x] **Passkey login bypassed the domain/active check:** passkey auth does not run Fortify's `authenticateUsing`, so `is_active`/allowed-domain enforcement was skipped at login. Added a `Login` event listener (`FortifyServiceProvider`) that logs out and rejects any deactivated or off-domain account on every authentication path.
- [x] **Nested block content was unsanitized:** `cards` and `accordion` block bodies (strings, links, and nested Tiptap content) bypassed the sanitizer, a latent stored-XSS hole once the block renderer is built. `BlockRenderer` now deep-sanitizes nested list fields and re-runs the Tiptap allowlist on nested rich content.
- [x] **Open-redirect in links:** `isSafeLink` accepted protocol-relative (`//host`) and backslash (`/\host`) URLs as first-party. Now rejected.
- [x] **Media in Settings was deletable while in use:** the logo/partner-banner keys did not create a `media_references` row (only keys literally ending in `media_asset_id` did), so deletion protection failed. `SettingController` now indexes configured settings-media keys (`config('admin.settings_media_keys')`) for both scalar and object value shapes.
- [x] **Under-reported usage:** team-member photos were never written to `media_references` (deletion was still blocked directly, but usage reporting was wrong). `TeamMemberController` now syncs `photo_media_asset_id`.
- [x] **Recursion DoS hardening:** added depth caps to `BlockRenderer::sanitizeTiptap`/`sanitizeDeep` and `MediaReferenceSynchronizer::collect`.
- [x] **MySQL migration landmine:** `blocks`/`description` JSON columns used a literal `default('[]')`, which MySQL rejects on JSON columns (SQLite silently accepts it). Switched to a parenthesized expression default valid on both engines.
- [x] **Soft-deleted slugs are now reusable.** Added a `slug_lock` discriminator (0 while live, the row id once trashed) folded into every content slug-unique index — same portable, app-maintained approach as `parent_key`, no generated columns. A shared `HasReusableSlug` trait maintains it on soft-delete/restore; the six content controllers scope slug validation to live rows (`whereNull('deleted_at')`); the five `HasSlug` models use `extraScope` so auto-generated slugs ignore trashed rows; and `ContentController::restore` returns a clear 422 (instead of a DB error) when a live row has since reclaimed the slug. Covered by `tests/Feature/SlugReuseTest.php` (5 tests).
- [x] Added `tests/Feature/SecurityHardeningTest.php` covering the security fixes above (8 tests).

### Resolved (decisions made July 11, 2026)

- [x] **Accounts are deactivate-only; no user deletes.** Dropped the unused `users.deleted_at` column (`User` stays hard-delete-free and uses `is_active`). Made `invites.invited_by` nullable + `nullOnDelete` so the FK can never block, per the "no deletes, deactivate only" decision.
- [x] **Galleries are now indexed in `media_references`.** `GalleryController::afterSave` mirrors the photo pivot into the reference table, so the media library's usage report is complete. The reference table is now the single source of truth for usage reporting across blocks, SEO/OG images, team photos, gallery photos, and settings media (direct relations still back deletion protection). Covered by `tests/Feature/RemainingDecisionsTest.php`.
- [x] **Per-key Settings validation enforced now.** `SettingController` validates each value against a typed per-key ruleset (email/url/string bounds; media keys must reference an existing asset), closing the injection vector without waiting on the Settings UI. Empty strings are treated as cleared.
- [x] **Editors can delete their own team members.** `ContentPolicy::delete` now treats records without a draft/publish lifecycle (team members) as deletable by their owner; status-bearing content still requires own-draft. Editors still cannot delete another user's records.

## Remaining backend enhancements

- [ ] Add production-grade media quarantine/finalize flow, magic-byte validation, HEIC conversion, EXIF stripping, WebP conversions, and queued processing.

## Deferred by design

- [ ] React/Inertia admin screens and all public frontend work.
- [ ] S3, CloudFront, SES production transport, and direct multipart upload workflow.
- [ ] MySQL production migration details and generated-column optimization.
- [ ] AWS/server provisioning, deployment automation, backups, monitoring, and production scheduling.
- [ ] Legacy website scrape/import and production redirect map.
