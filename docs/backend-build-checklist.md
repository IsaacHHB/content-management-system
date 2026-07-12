# Native Dads Network CMS — Backend Build Checklist

Last updated: July 11, 2026

This checklist tracks local database and Laravel backend work based on `docs/ndn-cms-build-schematic.md`, plus the July 11 full-codebase audit. AWS, production deployment, and server configuration remain intentionally out of scope for the local phase.

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

- [x] Added administrator account status and last-login timestamp; accounts are retained and deactivated rather than deleted.
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
- [x] Added local media collections plus queued thumb/medium/large and WebP conversions.
- [x] Added cached site settings with automatic cache invalidation.

### Authentication and authorization

- [x] Disabled public Fortify registration.
- [x] Added exact-match, case-insensitive administrator email-domain validation.
- [x] Added login-time enforcement for active accounts and allowed email domains.
- [x] Added global request-time web middleware that logs out unauthorized or deactivated sessions, including Fortify/passkey endpoints.
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
- [x] Added signed preview links for draft pages, programs, events, and posts. Shared links from `PreviewLinkController` expire in **1 hour**; the URL embedded in the edit screen's preview iframe (`ContentController::previewUrl`) expires in **6 hours** so it survives a long editing session. Both are signed and scoped to a single model key.
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
- [x] Ran the full Pest suite: 116 tests, 114 passed, 2 intentionally skipped, 488 assertions.
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

- [x] MIME-sniff and decode images, cap dimensions/size, require image alt text, strip original EXIF/metadata, and generate queued standard + WebP conversions.
- [ ] Add HEIC/HEIF transcoding and deploy-time codec health checks before accepting HEIC uploads.
- [ ] Add a resumable quarantine/finalize/checksum flow only if future direct or large-file uploads require it.
- [ ] Add responsive-image `srcset` generation/consumption through a shared public image component.

## Full-codebase audit follow-up (July 11, 2026)

- [x] Added nonce-based CSP, X-Content-Type-Options, SAMEORIGIN framing, no-referrer, Permissions-Policy, and production HTTPS HSTS middleware.
- [x] Added safe custom-menu URL validation to reject script, protocol-relative, malformed, and backslash-smuggled targets.
- [x] Fixed permanent public-menu cache staleness and added nested public navigation rendering.
- [x] Normalized invite/profile emails before uniqueness checks and storage.
- [x] Enforced uncompromised 12-character invite passwords.
- [x] Converted timed event input from its selected IANA time zone to UTC storage and back to that zone for forms/public display.
- [x] Added published-content `sitemap.xml`, crawler exclusions, canonical tags, and expanded OpenGraph/Twitter metadata.
- [x] Removed HTML injection sinks from pagination labels.
- [x] Reject missing, deleted, or non-image assets from image/OG/logo/photo/gallery references with validation errors instead of database failures.
- [x] Restricted dashboard contact counts to users authorized to manage contacts.
- [x] Verified the seeded local Isaac account is active, has the `admin` role, and its configured local password hash matches.
- [x] Composer and npm audits report zero known dependency advisories.

## Independent re-audit (July 11, 2026 — second pass)

An independent pass over the audit above ran the toolchain from scratch and exercised the app in a real browser with the SSR runtime on. It found the previous round's PHPStan/browser claims overstated and surfaced defects the automated checks could not see. All are fixed and covered by `tests/Feature/AuditFollowUpTest.php` (7 tests).

### Corrections to previously claimed status

- **PHPStan was not clean.** The new `SitemapController` produced 7 level-7 errors (`Collection` template invariance). `urls()` now builds a `list<>` and streams with `lazy()`. Level 7 is genuinely clean again.
- **The "manual browser smoke test" had not been re-run after the CSP landed**, so the two defects below shipped unnoticed. Both are browser-only failures — the whole PHP/TS/build suite stays green while the UI is broken.

### Fixed

- [x] **The CSP broke every admin screen under SSR.** `style-src` carried a nonce *and* `'unsafe-inline'`; per CSP Level 3 a nonce makes browsers **ignore `'unsafe-inline'`**, so inline `style="..."` attributes were dropped. SSR emits the sidebar's `style="--sidebar-width:16rem"` into the server HTML, React does not re-apply it on hydration, the variable resolved empty, the sidebar spacer collapsed to `0`, and all admin content rendered *underneath* the fixed sidebar. Added `style-src-attr 'unsafe-inline'`. (Client-only rendering masked this: React sets styles via the CSSOM, which CSP does not block — it only appears once `inertia:start-ssr` runs, i.e. in production.)
- [x] **The CSP blocked every YouTube embed.** `frame-src` allowed `youtube-nocookie.com`, but `registry.tsx` built `youtube.com/embed/...`. The renderer now emits `youtube-nocookie.com` (matches the CSP and avoids third-party cookies).
- [x] **The last-super-admin guard was bypassable by type juggling.** `($data['is_active'] ?? true) === false` compares against *raw* input, so a form-encoded `is_active=0` (string `"0"`) skipped the guard while the model cast still wrote `false` — deactivating the only super admin and making `content.restore` / `content.force-delete` / super-admin grants permanently unreachable without DB access. Now compared via `filter_var(..., FILTER_VALIDATE_BOOL)`. (Regression test confirmed failing against the old code.)
- [x] **All-day event dates rendered one day early.** `date`-cast columns still serialize as `"…T00:00:00.000000Z"`, so the `^\d{4}-\d{2}-\d{2}$` guard in `formatDate` never matched and any viewer west of UTC saw the previous day. Added an explicit `formatDateOnly()` for date-cast columns; `formatDate()` stays for true instants.
- [x] **Editing an all-day event silently blanked its dates.** The same full-ISO value was fed to `<input type="date">`, which requires `YYYY-MM-DD` and renders empty — so opening an existing all-day event and pressing Save failed validation with no indication the date had been dropped. Added `toDateInput()`.
- [x] **`published_at` walked backwards on every save.** The datetime-local input was rendered in *browser* wall-clock but re-parsed server-side in `app.timezone` (UTC), so an editor in LA re-saving an untouched post moved it 7 hours earlier, cumulatively. All five content forms now submit an absolute instant via `fromDatetimeLocal()`.
- [x] **A same-day all-day event appeared in both Upcoming and Past.** `scopeUpcoming` compared dates while the controller's PHP `isUpcoming()` compared instants. Added `scopePast()` as the exact complement of `scopeUpcoming()` so the two lists cannot overlap or gap, and deleted the duplicated PHP logic.
- [x] **Seeded event times were off by the UTC offset.** `LegacyContentSeeder` built wall-clock times in `app.timezone` (UTC) but stamped the rows `America/Los_Angeles`, so a 5:30 PM circle displayed as 10:30 AM. Times are now built in the event's zone and converted to UTC. **Existing seeded rows still hold the wrong instants — re-run the seeder to correct them.**
- [x] **A re-slugged page left the public nav pointing at a dead URL forever.** `public_menus` caches *resolved* URLs, but only `Menu`/`MenuItem` busted it — renaming or trashing a linked Page/Post/Program/Event did not. Added the `InvalidatesMenuCache` concern to all four menu targets.
- [x] **Cache invalidation ran inside the transaction.** Model `saved` hooks fire pre-commit, so a concurrent read could re-populate a `rememberForever` key with pre-commit data and keep it forever. All invalidation now goes through `CacheInvalidation::forgetAfterCommit()`.
- [x] **The visual editor swallowed failed draft saves.** `page-canvas.tsx` never checked `response.ok`, so a 422 (invalid block) or 419 (expired session) resolved silently and the preview iframe showed stale blocks while the author believed the edit had persisted. It now surfaces the failure and refuses to switch to a misleading preview.
- [x] **`robots.txt` advertised a relative `Sitemap:` URL**, which crawlers ignore. Now served from a route so it emits the absolute `route('sitemap')`.
- [x] **Gallery edit was N+1** (`mediaAssets` → `mediaAssets.media`; `MediaAsset` appends `url`/`thumb_url`, each hitting `getFirstMedia()`), and the gallery index's photo-count column was permanently blank. Added an `indexCounts()` hook using `withCount`.

### Known, accepted behavior (not defects — documented so it isn't mistaken for one)

- **"Editor" is broader than the name suggests.** `RolePermissionSeeder` grants editors `.publish` on all seven content modules and `media.manage`, and `ContentPolicy::update` has no ownership check, so an editor can edit and publish another user's content. Only *deletion* is restricted to their own drafts. Tighten the seeder if that is not intended.
- **The contact form's minimum-submit-time check is client-supplied** (`form_started_at`) and therefore forgeable. The honeypot, `throttle:3,10`, and HMAC IP hash still apply.
- **`img-src 'self'`** means media URLs must be same-origin. Media URLs are absolute and derived from `APP_URL`, so `APP_URL` must match the host the site is actually served from in production.

## Deferred by design

- [x] React/Inertia admin screens and functional public frontend (brand-design polish remains deferred).
- [ ] S3, CloudFront, SES production transport, and direct multipart upload workflow.
- [ ] MySQL production migration details and generated-column optimization.
- [ ] AWS/server provisioning, deployment automation, backups, monitoring, and production scheduling.
- [x] Curated legacy content/media import seeders.
- [ ] Manual production legacy-URL redirect inventory and redirect-management UI.
