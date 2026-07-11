# Native Dads Network — Website & CMS Build Schematic

**Project:** Complete rebuild of nativedadsnetwork.org
**Builder:** Isaac Hollow Horn Bear — The Lakota Dev
**Stack:** Laravel 13 (official React starter kit) + Inertia 3 + React 19 + TypeScript + MySQL 9.7 LTS (latest 9.7.x patch) + AWS (EC2 + CloudFront + SES; S3 for off-box backups only), served by Ubuntu-maintained Apache 2.4 + PHP-FPM 8.5
**Admin access model:** Invite-only, hard-locked to `@nativedadsnetwork.org` email addresses
**Media storage:** Local disk on the app server (EBS-backed `storage/app/public`), served through CloudFront — S3 is **not** used for media at this scale; the media library disk is a one-line config switch if that ever changes
**Document version:** 1.2 — July 2026 (storage simplified to local disk; architecture and dependency review applied)

---

## Table of Contents

1. [Project Overview & Goals](#1-project-overview--goals)
2. [Architecture Decisions](#2-architecture-decisions)
3. [Repository & Folder Structure](#3-repository--folder-structure)
4. [Local Development Environment](#4-local-development-environment)
5. [Database Schema](#5-database-schema)
6. [Authentication — Invite-Only + Domain Lock](#6-authentication--invite-only--domain-lock)
7. [Roles & Permissions](#7-roles--permissions)
8. [CMS Modules](#8-cms-modules)
9. [Page Builder (Block System)](#9-page-builder-block-system)
10. [Media Library (local disk)](#10-media-library-local-disk)
11. [Public Site Rendering & SEO](#11-public-site-rendering--seo)
12. [Content Migration Plan](#12-content-migration-plan)
13. [AWS Infrastructure](#13-aws-infrastructure)
14. [Deployment Pipeline](#14-deployment-pipeline)
15. [Backups, Monitoring, Logging & Scheduled Automation](#15-backups-monitoring-logging--scheduled-automation)
16. [Security Hardening Checklist](#16-security-hardening-checklist)
17. [Testing Strategy](#17-testing-strategy)
18. [Training & Handoff Documentation](#18-training--handoff-documentation)
19. [Build Timeline & Phases](#19-build-timeline--phases)
20. [Monthly Cost Estimate](#20-monthly-cost-estimate)

---

## 1. Project Overview & Goals

Native Dads Network (NDN) needs a modern, mobile-friendly website that its own team can manage without a developer on retainer. The promises made in the proposal translate into these hard requirements:

| Proposal promise | Technical requirement |
|---|---|
| Completely new, mobile-friendly design | Responsive React frontend, Tailwind CSS, tested down to 360px |
| Secure content management system | Laravel backend, CSRF/session auth, rate limiting, audit log |
| Individual administrator logins | One account per staff member, no shared logins, invite-only |
| Locked to NDN emails | Registration/invites rejected unless email ends in `@nativedadsnetwork.org` |
| Create and edit pages | Block-based page builder with draft/publish workflow |
| Manage programs, events, news, photos, team | Dedicated CRUD modules for each content type |
| Migration of existing content | Scripted scrape + import of current site content and media |
| AWS setup, security, deployment | Single EC2 (media on EBS) + CloudFront + SES + S3 backups, hardened, ~$20–50/mo |
| Training & documentation | Admin manual + screencasts + this document |
| Team owns it | Everything in NDN-owned AWS account and GitHub org; no vendor lock-in |

### Non-goals (v1)

- No e-commerce / payment processing (can be added later; Stripe-ready structure).
- No multi-language support in v1 (schema leaves room for it — see `pages.locale`).
- No public user accounts — only admin accounts exist.

---

## 2. Architecture Decisions

### 2.1 The monolith: Laravel + Inertia.js + React

One Laravel application serves **both** the public website and the admin CMS. The project is scaffolded from **Laravel's official React starter kit** (`laravel new ndn-website` → select React), which ships Inertia 3, React 19, TypeScript, Tailwind CSS 4, shadcn/ui, and Fortify out of the box. Inertia replaces the traditional REST-API-plus-SPA split: Laravel controllers return Inertia responses, and React 19 components render them. There is no separate API server, no token juggling, no CORS configuration, and one deploy target.

All frontend code is **TypeScript** (`.tsx`) — shared types for models/props live in `resources/js/types/` and mirror the Eloquent models, so the block builder, forms, and Inertia page props are all type-checked (`tsc --noEmit` runs in CI).

```
Browser
   │
   ▼
Apache 2.4 (event MPM) ──► PHP-FPM 8.5 (mod_proxy_fcgi) ──► Laravel 13
                          │
                          ├── routes/web.php (public pages, Inertia)
                          ├── routes/admin.php (CMS, Inertia, auth-gated)
                          │
                          ├── MySQL 9.7 LTS (content, users, invites)
                          ├── Local disk / EBS (media originals + conversions, storage/app/public)
                          ├── Redis (cache, sessions, queues) — optional; database driver is fine at this scale
                          └── SES (invite + password reset emails)
                          (S3 is used only for nightly off-box backups — Section 15)
```

Why this beats a separate API + SPA for NDN:

- **Session-cookie auth** — Laravel's battle-tested session guard, no JWT/refresh-token complexity.
- **One codebase, one deploy** — a nonprofit team (or a future volunteer dev) only has to understand a single repo.
- **Server-side routing** — every page has a real URL handled by Laravel, so SEO, redirects, and permissions live in one place.
- **SSR available** — Inertia SSR can be enabled for the public pages if SEO demands it (Section 11).

### 2.2 Key package choices

| Concern | Package | Why |
|---|---|---|
| Inertia adapter | `inertiajs/inertia-laravel` + `@inertiajs/react` (Inertia 3) | Ships with the starter kit; polling, prefetch, deferred props built in |
| Auth scaffolding | `laravel/fortify` (headless) | Included in the starter kit: login, password reset, email verification, 2FA — React pages, no Blade |
| UI components | shadcn/ui (Radix primitives) | Included in the starter kit; accessible, owned-in-repo components — no component library dependency to outgrow |
| Roles/permissions | `spatie/laravel-permission` | De-facto standard, cached, well-documented |
| Media | `spatie/laravel-medialibrary` | Local `public` disk (EBS) by default, automatic conversions (thumb/medium/large/webp), responsive images; disk is a config switch (`MEDIA_DISK`) so S3 remains available without code changes |
| Slugs | `spatie/laravel-sluggable` | Auto slug generation with uniqueness |
| Activity/audit log | `spatie/laravel-activitylog` | "Who changed what, when" for every model |
| Backups | `spatie/laravel-backup` | Nightly DB + media directory to S3 (the only S3 use) |
| Sitemap | `spatie/laravel-sitemap` | Auto-generated sitemap.xml |
| Rich text | Tiptap (React) | Modern, JSON-based, sanitizable — used inside the block builder |
| Styling | Tailwind CSS 4 | CSS-first config (`@theme`), faster builds, easy for future maintainers |
| Build tool | Vite (latest, `laravel-vite-plugin`) | Laravel default |
| Frontend | React 19 + TypeScript | Function components + hooks only; no legacy APIs anywhere in the codebase |
| Frontend lint/format | ESLint + Prettier (starter kit config, `tabWidth: 4`) | Enforced in CI |

Version policy: `composer.json` and `package.json` constrain supported major versions, while committed lockfiles pin the exact tested dependency graph. Dependabot security updates are handled promptly; routine dependency updates are grouped into a monthly PR and tested in staging. As of July 2026, the intended package lines are Laravel 13, Inertia 3, React 19.2, Tailwind CSS 4.3, Vite 8.1, PHP 8.5, Node 24 LTS, and MySQL 9.7 LTS. Key Laravel package lines are `spatie/laravel-permission:^8.3`, `spatie/laravel-medialibrary:^11.23`, `spatie/laravel-sluggable:^4.0`, `spatie/laravel-activitylog:^5.0`, `spatie/laravel-backup:^10.3`, `spatie/laravel-sitemap:^8.2`, and `spatie/laravel-feed:^4.5`.

### 2.3 Environments

| Env | Where | Purpose |
|---|---|---|
| `local` | Developer machine (Laravel Sail / Valet / Herd) | Development |
| `staging` | Same EC2 box, separate vhost + separate DB (`ndn_staging`) | NDN team previews before launch; can be torn down after launch to save nothing (it's the same box) |
| `production` | EC2 `ndn_production` DB | Live site |

---

## 3. Repository & Folder Structure

Single repo, owned by an NDN GitHub organization (Isaac added as maintainer). Suggested name: `ndn-website`.

```
ndn-website/
├── app/
│   ├── Console/Commands/
│   │   ├── ImportLegacyContent.php        # one-time migration command
│   │   └── PruneExpiredInvites.php
│   ├── Enums/
│   │   ├── PublishStatus.php              # draft | published | archived
│   │   └── Role.php                       # super_admin | admin | editor
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Admin/                     # all CMS controllers
│   │   │   │   ├── DashboardController.php
│   │   │   │   ├── PageController.php
│   │   │   │   ├── ProgramController.php
│   │   │   │   ├── EventController.php
│   │   │   │   ├── PostController.php     # news
│   │   │   │   ├── GalleryController.php
│   │   │   │   ├── TeamMemberController.php
│   │   │   │   ├── MediaController.php
│   │   │   │   ├── MenuController.php
│   │   │   │   ├── SettingController.php
│   │   │   │   ├── UserController.php
│   │   │   │   └── InviteController.php
│   │   │   ├── Auth/
│   │   │   │   └── AcceptInviteController.php
│   │   │   └── Public/                    # public site controllers
│   │   │       ├── HomeController.php
│   │   │       ├── PageController.php     # catch-all slug renderer
│   │   │       ├── ProgramController.php
│   │   │       ├── EventController.php
│   │   │       ├── PostController.php
│   │   │       └── ContactController.php
│   │   ├── Middleware/
│   │   │   ├── HandleInertiaRequests.php
│   │   │   └── EnsureNdnEmailDomain.php   # defense-in-depth check
│   │   └── Requests/                      # FormRequest validation per module
│   ├── Models/
│   │   ├── User.php
│   │   ├── Invite.php
│   │   ├── Page.php
│   │   ├── Program.php
│   │   ├── Event.php
│   │   ├── Post.php
│   │   ├── Category.php
│   │   ├── Gallery.php
│   │   ├── TeamMember.php
│   │   ├── Menu.php
│   │   ├── MenuItem.php
│   │   ├── Setting.php
│   │   └── ContactSubmission.php
│   ├── Policies/                          # one policy per model
│   ├── Rules/
│   │   └── AllowedEmailDomain.php         # THE domain lock rule
│   └── Services/
│       ├── BlockRenderer.php              # validates/normalizes page blocks
│       └── LegacyImporter.php
├── database/
│   ├── migrations/
│   └── seeders/
│       ├── RolePermissionSeeder.php
│       ├── SettingSeeder.php
│       └── SuperAdminSeeder.php           # seeds Isaac + Mike from env vars
├── resources/
│   ├── js/                                # starter-kit structure, all TypeScript
│   │   ├── app.tsx
│   │   ├── ssr.tsx
│   │   ├── components/                    # shared UI (starter kit) + ui/ (shadcn/ui)
│   │   ├── blocks/                        # one React component per page block type
│   │   ├── hooks/                         # starter kit hooks + ours (useAutosave, ...)
│   │   ├── lib/                           # utils, Tiptap config
│   │   ├── layouts/
│   │   │   ├── public-layout.tsx
│   │   │   └── admin-layout.tsx
│   │   ├── pages/
│   │   │   ├── public/                    # home, page, programs, events, news, contact...
│   │   │   ├── admin/                     # dashboard, pages/, programs/, events/...
│   │   │   └── auth/                      # login, forgot-password, accept-invite, two-factor
│   │   └── types/                         # shared TS types mirroring Eloquent models
│   └── views/app.blade.php                # single Blade root for Inertia
├── routes/
│   ├── web.php                            # public routes
│   ├── admin.php                          # /admin/* routes
│   └── console.php
├── tests/
│   ├── Feature/                           # auth, invites, CRUD, publishing
│   └── Unit/
├── .github/workflows/
│   ├── ci.yml                             # lint + test on every PR
│   └── deploy.yml                         # deploy on push to main
└── deploy/
    ├── apache-vhost.conf
    ├── php-fpm-pool.conf
    └── deploy.sh
```

Coding standard: **4-space indentation everywhere** (PHP via `.editorconfig` + Laravel Pint, TS/TSX via Prettier `tabWidth: 4`).

`.editorconfig`:

```ini
root = true

[*]
charset = utf-8
end_of_line = lf
indent_size = 4
indent_style = space
insert_final_newline = true
trim_trailing_whitespace = true
```

---

## 4. Local Development Environment

```bash
# Requirements: PHP 8.5, Composer 2, Node 24 LTS, MySQL 9.7 LTS (or Sail/Docker)
# (Repo was originally scaffolded with: laravel new ndn-website → React starter kit)
git clone git@github.com:nativedadsnetwork/ndn-website.git
cd ndn-website
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
npm run dev          # Vite dev server (HMR)
php artisan serve    # or Herd/Valet
```

Critical `.env` keys (the domain lock is configuration, not hard-code):

```dotenv
APP_NAME="Native Dads Network"
APP_URL=http://ndn.test

# --- ADMIN DOMAIN LOCK ---
ADMIN_ALLOWED_DOMAINS=nativedadsnetwork.org
# comma-separated; e.g. add thelakotadev.com during the build/support year:
# ADMIN_ALLOWED_DOMAINS=nativedadsnetwork.org,thelakotadev.com

# --- Seeded super admins (run SuperAdminSeeder once) ---
SEED_SUPERADMIN_NAME="Mike ..."
SEED_SUPERADMIN_EMAIL=mike@nativedadsnetwork.org

DB_CONNECTION=mysql
DB_DATABASE=ndn
SESSION_DRIVER=database
QUEUE_CONNECTION=database
CACHE_STORE=database

# --- MEDIA STORAGE (local disk; no S3 for media) ---
FILESYSTEM_DISK=local
MEDIA_DISK=public           # storage/app/public, exposed via `php artisan storage:link`
# To move media to S3 later, set MEDIA_DISK=s3 and fill the AWS_* keys below — no code change.
# AWS_BUCKET=ndn-media
# AWS_DEFAULT_REGION=us-west-2

# --- Off-box backups only (Section 15) ---
BACKUP_DISK=s3
AWS_BACKUP_BUCKET=ndn-backups

MAIL_MAILER=ses
MAIL_FROM_ADDRESS=no-reply@nativedadsnetwork.org
```

Config bridge (`config/admin.php`):

```php
<?php

return [
    'allowed_domains' => array_filter(array_map(
        'trim',
        explode(',', (string) env('ADMIN_ALLOWED_DOMAINS', 'nativedadsnetwork.org'))
    )),
    'invite_expiry_days' => 7,
];
```

---

## 5. Database Schema

MySQL 9.7 LTS (install the latest 9.7.x patch; supported through 2034; MySQL 8.0 entered Oracle Sustaining Support on April 21, 2026), InnoDB, `utf8mb4` with the default `utf8mb4_0900_ai_ci` collation. Editorial content tables carry `created_by` / `updated_by` foreign keys plus soft deletes. Operational/package tables use their own documented retention rules.

### 5.1 Entity-relationship overview

```
users ─────┬──< invites (invited_by)
           ├──< activity_log (causer)
           └──< pages / programs / events / posts / ... (created_by, updated_by)

pages          (block-builder pages, hierarchical via parent_id)
programs       (NDN programs, e.g. fatherhood curriculum, youth services)
events >──── event_registrations (optional, v1.1)
posts >────< categories (many-to-many, for news)
galleries >────< media_assets (ordered gallery pivot)
team_members
menus ──< menu_items (self-nesting via parent_id)
settings       (key/value site-wide config)
contact_submissions
media_assets   (first-class global assets) ──< media (Spatie-owned files/conversions)
media_references (normalized usage index for JSON blocks and structured fields)
```

### 5.2 Table definitions

**users**

| column | type | notes |
|---|---|---|
| id | bigint PK | |
| name | varchar(255) | |
| email | varchar(255) unique | must match allowed domain (enforced at invite + registration + login) |
| password | varchar(255) | argon2id |
| two_factor_secret / two_factor_recovery_codes | text nullable | Fortify 2FA |
| is_active | boolean default true | deactivate instead of delete |
| last_login_at | timestamp nullable | shown in user list |
| email_verified_at, remember_token, timestamps, softDeletes | | |

**invites**

| column | type | notes |
|---|---|---|
| id | bigint PK | |
| email | varchar(255) | normalized lowercase; validated against allowed domains **at creation** |
| pending_email | generated varchar nullable unique | lowercase email while `accepted_at IS NULL`, otherwise NULL; permits audited history without blocking future invites |
| role | varchar(50) | role granted on acceptance |
| token | varchar(64) unique | random 64-char, hashed at rest (store `hash('sha256', $token)`) |
| invited_by | FK users | |
| expires_at | timestamp | now + 7 days |
| accepted_at | timestamp nullable | |
| timestamps | | |

**pages**

| column | type | notes |
|---|---|---|
| id | bigint PK | |
| parent_id | FK pages nullable | hierarchy → URL nesting (`/about/history`) |
| title | varchar(255) | |
| slug | varchar(255) | unique per parent; root uniqueness must account for nullable `parent_id` |
| blocks | json | ordered array of block objects (Section 9) |
| status | enum: draft/published/archived | |
| published_at | timestamp nullable | scheduled publishing |
| seo_title, seo_description | varchar nullable | |
| og_media_asset_id | FK media_assets nullable | |
| locale | varchar(8) default 'en' | future i18n |
| is_locked | boolean default false | protects Home/Contact from deletion |
| sort_order | int | |
| created_by, updated_by, timestamps, softDeletes | | |

MySQL permits multiple `NULL` values in a composite unique key, so a plain unique index on `(parent_id, slug)` does **not** protect root-page slugs. Add a stored/generated `parent_key` that maps a root `NULL` to `0`, and enforce `UNIQUE (parent_key, slug, locale)`, or use an equivalent functional index. Page creation, moves, and slug changes run in a transaction. Renaming or moving a page also creates redirects for its old path and every affected descendant path.

**programs**

| column | type | notes |
|---|---|---|
| id, title, slug, status, published_at | | as above |
| excerpt | text | card/listing text |
| blocks | json | full page-builder body |
| contact_name, contact_email, contact_phone | nullable | program-specific contact |
| external_url | varchar nullable | for programs hosted elsewhere |
| sort_order, created_by, updated_by, timestamps, softDeletes | | |

**events**

| column | type | notes |
|---|---|---|
| id, title, slug, status, published_at | | |
| description | json (blocks) | |
| starts_at, ends_at | datetime | ends_at nullable |
| all_day | boolean | |
| timezone | varchar(64) default 'America/Los_Angeles' | |
| location_name, address, city, state, zip | nullable | |
| is_virtual | boolean; virtual_url nullable | |
| registration_url | varchar nullable | link out to Eventbrite/Google Form v1 |
| created_by, updated_by, timestamps, softDeletes | | |

**posts** (news)

| column | type | notes |
|---|---|---|
| id, title, slug, status, published_at | | |
| excerpt | text | |
| blocks | json | |
| author_id | FK users nullable | display author |
| is_featured | boolean | pinned on News index / homepage |
| created_by, updated_by, timestamps, softDeletes | | |

**categories** + pivot `category_post` — id, name, slug.

**galleries** — id, title, slug, description, status, sort_order, audit columns. Photos use an ordered `gallery_media_asset` pivot with optional per-gallery caption/alt overrides.

**team_members** — id, name, slug, title (role at NDN), bio (text), email/phone (nullable, optionally hidden), `photo_media_asset_id`, sort_order, is_active, audit columns.

**menus** — id, name, slot (`header` / `footer` unique).
**menu_items** — id, menu_id FK, parent_id self-FK, label, linkable_type/linkable_id (morph to Page/Program/Post/Event) **or** custom_url, opens_new_tab boolean, sort_order.

**settings** — id, key unique, value json, group (`general` / `contact` / `social` / `seo`). Seeded keys: `site_name`, `tagline`, `logo`, `contact_email`, `contact_phone`, `mailing_address`, `facebook_url`, `instagram_url`, `youtube_url`, `footer_text`, `partner_banner` (the Lakota Dev / Native Story Book banner from the agreement), `google_analytics_id`.

**contact_submissions** — id, name, email, phone, subject, message, ip_hash, is_read, timestamps. Honeypot + rate-limited.

**media_assets** — id, uuid, type, original_name, alt_text, caption, credit, focal_point json nullable, status, created_by, updated_by, timestamps, softDeletes. Each asset owns its original/conversions through Spatie Media Library.

**media_references** — id, media_asset_id, referencer_type, referencer_id, block_id nullable, field, timestamps; indexed by both the asset and referencer. It is regenerated in the same transaction whenever a referencer's blocks/structured media fields change.

Plus package tables: `permission_tables` (spatie), `media` (medialibrary), `activity_log`, `sessions`, `jobs`, `cache`, `password_reset_tokens`.

### 5.3 Sample migration (invites)

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invites', function (Blueprint $table) {
            $table->id();
            $table->string('email');
            $table->string('role', 50)->default('editor');
            $table->string('token', 64)->unique();
            $table->foreignId('invited_by')->constrained('users');
            $table->timestamp('expires_at');
            $table->timestamp('accepted_at')->nullable();
            $table->string('pending_email')->nullable()
                ->storedAs("if(`accepted_at` is null, lower(`email`), null)")
                ->unique();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invites');
    }
};
```

---

## 6. Authentication — Invite-Only + Domain Lock

This is the core of "locking it to nativedadsnetwork emails." The lock is enforced at **four independent layers** so a single mistake can never open the door:

1. **Invite creation** — a super admin cannot even *send* an invite to a non-NDN address.
2. **Invite acceptance / registration** — the registration form re-validates the email domain and requires a valid, unexpired, single-use token. There is **no public registration route at all**.
3. **Login** — the session guard refuses authentication for any account whose email domain is no longer allowed (covers the edge case where the allowed-domain list changes after accounts exist).
4. **Middleware** — every `/admin/*` request re-checks the authenticated user's domain + `is_active` flag.

### 6.1 The domain rule

`app/Rules/AllowedEmailDomain.php`:

```php
<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Str;

class AllowedEmailDomain implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $domain = Str::lower(Str::afterLast((string) $value, '@'));
        $allowed = array_map('strtolower', config('admin.allowed_domains'));

        if (! in_array($domain, $allowed, true)) {
            $fail('Administrator accounts must use an official Native Dads Network email address.');
        }
    }
}
```

Notes:

- Comparison is exact-match on the domain part, lowercased. Subdomains (`user@mail.nativedadsnetwork.org`) are **rejected** unless explicitly listed.
- The list comes from `ADMIN_ALLOWED_DOMAINS` in `.env`, so during the build year `thelakotadev.com` can be added for Isaac's support account and removed later without touching code.

### 6.2 Invite flow

```
Super Admin (in CMS)                         Invitee (staff member)
────────────────────                         ──────────────────────
POST /admin/invites
  • validate email: required|email|
    unique:users|unique:invites|
    AllowedEmailDomain
  • generate 64-char random token
  • store sha256(token), expires_at=+7d
  • queue InviteMail via SES ────────────►  receives email with signed link:
                                            /invite/{token}?signature=...
                                                    │
                                            GET  → AcceptInvite React page
                                            POST /invite/{token}
                                              • token exists, unexpired,
                                                not yet accepted
                                              • signed URL valid
                                              • re-validate AllowedEmailDomain
                                              • password: min 12, mixed,
                                                uncompromised (HIBP check)
                                              • create user, assign role,
                                                mark invite accepted,
                                                log in, redirect /admin
```

`app/Http/Controllers/Admin/InviteController.php` (store action):

```php
<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\InviteMail;
use App\Models\Invite;
use App\Rules\AllowedEmailDomain;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class InviteController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Invite::class);

        $data = $request->validate([
            'email' => [
                'required', 'email:rfc,dns',
                Rule::unique('users', 'email'),
                Rule::unique('invites', 'email'),
                new AllowedEmailDomain(),
            ],
            'role' => ['required', Rule::in(['admin', 'editor'])],
        ]);

        $plainToken = Str::random(64);

        $invite = Invite::create([
            'email' => Str::lower($data['email']),
            'role' => $data['role'],
            'token' => hash('sha256', $plainToken),
            'invited_by' => $request->user()->id,
            'expires_at' => now()->addDays(config('admin.invite_expiry_days')),
        ]);

        Mail::to($invite->email)->queue(new InviteMail($invite, $plainToken));

        return back()->with('success', "Invite sent to {$invite->email}.");
    }
}
```

`app/Http/Controllers/Auth/AcceptInviteController.php`:

```php
<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Invite;
use App\Models\User;
use App\Rules\AllowedEmailDomain;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;

class AcceptInviteController extends Controller
{
    public function show(string $token): Response
    {
        $invite = $this->findValidInvite($token);

        return Inertia::render('auth/accept-invite', [
            'email' => $invite->email,
            'token' => $token,
        ]);
    }

    public function store(Request $request, string $token): RedirectResponse
    {
        $inviteEmail = $this->findValidInvite($token)->email;

        $data = $request->merge(['email' => $inviteEmail])->validate([
            'email' => ['required', 'email', new AllowedEmailDomain()],
            'name' => ['required', 'string', 'max:255'],
            'password' => ['required', 'confirmed', Password::min(12)->mixedCase()->numbers()->uncompromised()],
        ]);

        $user = DB::transaction(function () use ($data, $token) {
            // Re-query and lock inside the transaction so two submissions
            // cannot consume the same invite concurrently.
            $invite = Invite::query()
                ->where('token', hash('sha256', $token))
                ->whereNull('accepted_at')
                ->where('expires_at', '>', now())
                ->lockForUpdate()
                ->firstOrFail();

            $user = User::create([
                'name' => $data['name'],
                'email' => $invite->email,
                'password' => Hash::make($data['password']),
                'email_verified_at' => now(),   // invite email == verification
            ]);

            $user->assignRole($invite->role);
            $invite->update(['accepted_at' => now()]);

            return $user;
        });

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('admin.dashboard');
    }

    private function findValidInvite(string $token): Invite
    {
        return Invite::query()
            ->where('token', hash('sha256', $token))
            ->whereNull('accepted_at')
            ->where('expires_at', '>', now())
            ->firstOrFail();
    }
}
```

### 6.3 Login-time and request-time enforcement

Fortify `authenticateUsing` closure (in `FortifyServiceProvider`) — layer 3:

```php
Fortify::authenticateUsing(function (Request $request) {
    $user = User::where('email', Str::lower($request->email))->first();

    if (! $user || ! Hash::check($request->password, $user->password)) {
        return null;
    }

    if (! $user->is_active || ! $user->hasAllowedDomain()) {
        return null;    // same generic "credentials do not match" error — no info leak
    }

    return $user;
});
```

`User::hasAllowedDomain()`:

```php
public function hasAllowedDomain(): bool
{
    $domain = Str::lower(Str::afterLast($this->email, '@'));

    return in_array($domain, array_map('strtolower', config('admin.allowed_domains')), true);
}
```

`app/Http/Middleware/EnsureNdnEmailDomain.php` — layer 4, applied to the whole `admin` route group:

```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureNdnEmailDomain
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! $user->is_active || ! $user->hasAllowedDomain()) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')
                ->withErrors(['email' => 'Your account is not authorized to access the CMS.']);
        }

        return $next($request);
    }
}
```

### 6.4 Additional auth hardening

- **Two-factor authentication** via Fortify (TOTP apps). Optional for editors, **required for super admins** (enforced by middleware that redirects to 2FA setup if `two_factor_secret` is null and the user has the `super_admin` role).
- **Rate limiting:** `throttle:5,1` on login and invite-acceptance POST routes; lockout events logged.
- **Sessions:** database driver, `SESSION_SECURE_COOKIE=true`, `same_site=lax`, 120-minute idle timeout, "log out other sessions" on password change.
- **Password resets** go through the standard signed-token flow; the reset mail only ever goes to the account's NDN address.
- **Scheduled cleanup:** `PruneExpiredInvites` runs daily via the Laravel scheduler — fully automated, see Section 15.1.
- **No public registration route exists.** `Route::get('/register')` is never defined; Fortify's registration feature is disabled in `config/fortify.php` (`'features' => [Features::resetPasswords(), Features::twoFactorAuthentication()]`).

### 6.5 Routes

`routes/admin.php`:

```php
<?php

use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'ndn.domain', 'verified'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('/', DashboardController::class)->name('dashboard');

        Route::resource('pages', PageController::class);
        Route::resource('programs', ProgramController::class);
        Route::resource('events', EventController::class);
        Route::resource('posts', PostController::class);
        Route::resource('galleries', GalleryController::class);
        Route::resource('team', TeamMemberController::class)->parameters(['team' => 'teamMember']);
        Route::resource('menus', MenuController::class)->only(['index', 'update']);
        Route::resource('media', MediaController::class)->only(['index', 'store', 'update', 'destroy']);

        Route::get('settings', [SettingController::class, 'edit'])->name('settings.edit');
        Route::put('settings', [SettingController::class, 'update'])->name('settings.update');

        Route::middleware('role:super_admin|admin')->group(function () {
            Route::resource('users', UserController::class)->except(['create', 'store']);
            Route::resource('invites', InviteController::class)->only(['index', 'store', 'destroy']);
            Route::post('invites/{invite}/resend', [InviteController::class, 'resend'])->name('invites.resend');
        });
    });

// Invite acceptance — public but signed + token-gated
Route::middleware('guest')->group(function () {
    Route::get('invite/{token}', [AcceptInviteController::class, 'show'])
        ->middleware('signed')->name('invite.show');
    Route::post('invite/{token}', [AcceptInviteController::class, 'store'])
        ->middleware(['signed', 'throttle:5,1'])->name('invite.accept');
});
```

The acceptance form submits to the complete temporary signed URL, preserving its `expires` and `signature` query parameters. Invite URLs are redacted from application/CDN access logs where practical and responses use `Referrer-Policy: no-referrer`, because the path contains a credential. Accepted invites are retained for audit but excluded from the uniqueness rule for pending invitations (for example, by moving accepted records to invite history or using a nullable pending-email key), so an expired/accepted row cannot permanently block a future legitimate invitation.

---

## 7. Roles & Permissions

Three roles via `spatie/laravel-permission`. Keep it simple — a nonprofit team does not need 40 granular toggles.

| Capability | super_admin | admin | editor |
|---|---|---|---|
| Manage users & invites | ✅ | ✅ (cannot touch super_admins) | ❌ |
| Edit site settings & menus | ✅ | ✅ | ❌ |
| Create/edit all content | ✅ | ✅ | ✅ |
| Publish / unpublish | ✅ | ✅ | ✅ |
| Delete content (soft) | ✅ | ✅ | own drafts only |
| Restore / force-delete | ✅ | ❌ | ❌ |
| View activity log | ✅ | ✅ | ❌ |

Rules of the system:

- **Isaac (support year) + Mike = super_admin.** Everyone else starts as `editor`.
- Roles are assigned at invite time and changeable by super_admins in the Users screen.
- The **last remaining super_admin cannot be deactivated or demoted**. The mutation and active-super-admin count run in one transaction with row locks so concurrent requests cannot violate the invariant; the policy provides the user-facing authorization layer.
- Every mutation is recorded by `laravel-activitylog` (model, changed attributes diff, causer) and surfaced in an "Activity" screen for admins. A custom activity tap adds the request IP (or a privacy-preserving keyed hash) because the package does not add it automatically.

`database/seeders/RolePermissionSeeder.php` creates the three roles and permissions (`pages.view/create/update/delete/publish`, mirrored per module) and syncs role-permission maps; policies check permissions, not roles, so future granularity is a seeder change away.

---

## 8. CMS Modules

Every module follows the same resource pattern, so once the team learns one screen they know them all:

```
Index (table: title, status, updated, author)  ── search, status filter, pagination
  └── Create / Edit form (React)
        ├── content fields (module-specific)
        ├── block builder (where applicable)
        ├── SEO panel (title, description, OG image)
        ├── Status: Draft / Published (+ optional publish date for scheduling)
        └── Save Draft  |  Publish  |  Preview (signed preview URL renders drafts)
```

Shared backend behaviors (a `HasPublishing` trait + `PublishedScope`):

```php
<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;

trait HasPublishing
{
    public function scopePublished(Builder $query): Builder
    {
        return $query
            ->where('status', 'published')
            ->where(function (Builder $q) {
                $q->whereNull('published_at')->orWhere('published_at', '<=', now());
            });
    }

    public function isPublished(): bool
    {
        return $this->status === 'published'
            && ($this->published_at === null || $this->published_at->isPast());
    }
}
```

### 8.1 Pages

- Hierarchical (`parent_id`) — URL is the joined slug path (`/about/our-story`). Max depth 2 to keep URLs sane.
- Body is the **block builder** (Section 9).
- `is_locked` pages (Home, Contact) cannot be deleted or re-slugged, only edited.
- Drafts previewable via a temporary **signed URL** (`URL::temporarySignedRoute('pages.preview', now()->addHour(), ['page' => $id])`) so staff can share previews internally without publishing.

### 8.2 Programs

- The heart of NDN's content: fatherhood programs, youth services, community events programming, etc.
- Card grid on `/programs`, detail page at `/programs/{slug}` rendered through the same block system.
- Optional per-program contact info and external link (for grant-partner programs hosted elsewhere).
- Manual `sort_order` (drag-to-reorder in the index screen — `@dnd-kit/sortable` on the frontend, a single `PATCH /admin/programs/reorder` endpoint accepting an array of IDs).

### 8.3 Events

- Calendar-aware listing: `/events` shows **Upcoming / in progress** (default) and **Past** tabs. Current events use `COALESCE(ends_at, starts_at) >= now()`; future/current results are ordered by `starts_at` ascending.
- Admin form: date/time pickers with timezone (defaults to America/Los_Angeles), all-day toggle, physical vs. virtual location, optional external registration URL. Timed instants are converted to UTC for storage and converted back to the recorded display timezone. All-day dates are stored as dates rather than timezone-shifted midnight timestamps.
- Homepage widget shows the next 3 upcoming events.
- v1.1 option: built-in RSVP (`event_registrations` table) — deliberately out of v1 scope.

### 8.4 News (Posts)

- Standard blog: excerpt, block body, categories, display author, featured flag.
- `/news` index with category filter + pagination; `/news/{slug}` detail.
- RSS feed at `/news/feed` (`spatie/laravel-feed`) so partners can syndicate.

### 8.5 Photo Galleries

- Gallery = title + description + ordered photo set.
- Photos upload to the media library on local disk (up to ~20 at a time), auto-converted to thumb/medium/large + WebP by queued jobs.
- Per-photo caption and alt text fields (alt text required — accessibility is enforced at the form level).
- Public: `/gallery` grid of galleries → lightbox viewer per gallery.

### 8.6 Team Members

- Name, title, bio, photo, optional contact info with a "show publicly" toggle per field.
- Drag-to-reorder; `is_active` hides without deleting (staff transitions happen).
- Public: `/about/team` grid; team members can also be embedded in any page via the Team block.

### 8.7 Menus

- Two fixed slots: **Header** and **Footer**.
- Items link to internal content (morph select: Page / Program / Post / Event — stores the model reference so slugs can change without breaking nav) or a custom URL.
- One level of nesting in header (dropdowns). Drag-and-drop tree editor.

### 8.8 Site Settings

- Single form, grouped tabs: General (name, tagline, logo), Contact (email, phone, address), Social links, SEO defaults, Partner banner (the Native Story Book banner + The Lakota Dev partner listing from the agreement — editable, not hard-coded).
- Settings cached (`Cache::rememberForever('settings')`, busted on save) and shared to every Inertia page via `HandleInertiaRequests::share()`.

### 8.9 Contact Form Submissions

- Public `/contact` form → stored in DB **and** emailed via SES to `settings.contact_email`.
- Spam controls: honeypot field, minimum-time-to-submit check, `throttle:3,10` per IP. No CAPTCHA in v1 (accessibility), can add Turnstile if spam becomes a problem.
- Admin inbox screen with read/unread; retention pruned after 24 months (scheduled command) to respect privacy.

### 8.10 Sample controller (Programs — the pattern all modules follow)

```php
<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProgramRequest;
use App\Models\Program;
use App\Services\BlockRenderer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProgramController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Program::class, 'program');
    }

    public function index(Request $request): Response
    {
        return Inertia::render('admin/programs/index', [
            'programs' => Program::query()
                ->with('updatedBy:id,name')
                ->when($request->search, fn ($q, $s) => $q->where('title', 'like', "%{$s}%"))
                ->when($request->status, fn ($q, $s) => $q->where('status', $s))
                ->orderBy('sort_order')
                ->paginate(20)
                ->withQueryString(),
            'filters' => $request->only(['search', 'status']),
        ]);
    }

    public function store(ProgramRequest $request, BlockRenderer $blocks): RedirectResponse
    {
        $program = Program::create([
            ...$request->validated(),
            'blocks' => $blocks->sanitize($request->validated('blocks', [])),
            'created_by' => $request->user()->id,
            'updated_by' => $request->user()->id,
        ]);

        return redirect()
            ->route('admin.programs.edit', $program)
            ->with('success', 'Program created.');
    }

    // edit(), update(), destroy() follow the same shape…
}
```

---

## 9. Page Builder (Block System)

Pages, programs, posts, and event descriptions all share one JSON block format. This is the piece that makes "the team can build pages themselves" true without giving them raw HTML (which breaks designs) or a rigid template (which frustrates them).

### 9.1 Block format

```json
{
    "blocks": [
        { "id": "b1", "type": "hero", "data": { "heading": "...", "sub": "...", "media_asset_id": 42, "cta": { "label": "Learn more", "url": "/programs" } } },
        { "id": "b2", "type": "rich_text", "data": { "content": { "tiptap-json": "..." } } },
        { "id": "b3", "type": "image", "data": { "media_asset_id": 43, "caption": "...", "alt": "...", "width": "wide" } },
        { "id": "b4", "type": "gallery_embed", "data": { "gallery_id": 3 } },
        { "id": "b5", "type": "cta_banner", "data": { "heading": "...", "button": { "label": "...", "url": "..." } } }
    ]
}
```

### 9.2 v1 block types

| Block | Purpose |
|---|---|
| `hero` | Full-width header image + heading + optional CTA |
| `rich_text` | Tiptap editor: headings (h2/h3), lists, links, bold/italic, blockquote |
| `image` | Single image with caption/alt, normal/wide/full width |
| `image_text` | Image beside text, left/right |
| `gallery_embed` | Embed an existing gallery |
| `video_embed` | YouTube/Vimeo URL → privacy-enhanced embed (no arbitrary iframes) |
| `cards` | 2–4 column cards (icon/image, title, text, link) |
| `cta_banner` | Colored call-to-action strip |
| `events_list` | Auto-pulls next N upcoming events |
| `news_list` | Auto-pulls latest N posts |
| `team_grid` | Embed team members |
| `accordion` | FAQ-style expandables |
| `divider` / `spacer` | Layout control |

### 9.3 How it stays safe and consistent

- Each block type has a **PHP-side schema** (a `BlockType` class with a `rules(): array` method). `BlockRenderer::sanitize()` validates every block against its schema on save, strips unknown keys/types, and sanitizes Tiptap JSON server-side (allow-listed marks/nodes only — no script, no raw HTML).
- Each block type has **one React component** in `resources/js/blocks/`, used by **both** the admin live preview and the public renderer — what editors see is exactly what visitors get:

```tsx
// resources/js/blocks/block-renderer.tsx
import Hero from './hero';
import RichText from './rich-text';
import ImageBlock from './image-block';
import { type Block } from '@/types';
// ...

const registry: Record<string, React.ComponentType<any>> = {
    hero: Hero,
    rich_text: RichText,
    image: ImageBlock,
    // ...
};

export default function BlockRenderer({ blocks }: { blocks: Block[] }) {
    return (
        <>
            {blocks.map((block) => {
                const Component = registry[block.type];
                return Component ? <Component key={block.id} {...block.data} /> : null;
            })}
        </>
    );
}
```

- Admin editor UX: vertical stack of blocks, "+ Add block" picker between blocks, drag handle to reorder (`@dnd-kit`), duplicate/delete per block, collapse blocks for long pages, autosave draft every 30 seconds via a debounced `PATCH`.
- Images inside blocks reference a first-class `media_assets` record by ID (`media_asset_id`), never a raw Spatie `media.id` or URL. Each `MediaAsset` owns its Spatie media collection. A normalized `media_references` table is rebuilt transactionally when block JSON is saved, recording `(media_asset_id, referencer_type, referencer_id, block_id, field)`. This makes global replacement, usage reporting, and deletion protection reliable without querying arbitrary JSON.

---

## 10. Media Library (local disk)

`spatie/laravel-medialibrary` on the **`public` disk** (`storage/app/public`, exposed at `/storage` via `php artisan storage:link`). The old site shipped its images directly in the project tree; a CMS can't do that (uploads would land in git and vanish on deploy), so instead files live on the server's persistent EBS volume, outside the repo, and are served through CloudFront.

**Why not S3 at this scale.** S3 earns its keep only when you run more than one app server (shared storage), your host wipes the filesystem on deploy (ephemeral), or you need CDN-scale media offload. A single EC2 box with a persistent EBS volume and a media footprint in the low hundreds of MB hits none of those. Local disk is simpler, cheaper, has no extra IAM/CORS/presign surface, and — because medialibrary addresses files by disk — **moving to S3 later is a one-line `MEDIA_DISK=s3` change**, never a rewrite. So S3 stays available but unused for media; it is used only for nightly off-box backups (Section 15).

- **Disk & delivery:** `MEDIA_DISK=public` → `storage/app/public`, symlinked to `public/storage`. CloudFront caches `/storage/*` (long-lived, immutable hashed conversion paths) with the EC2 box as origin. Apache is configured to **never execute PHP** from under `public/storage` (Section 13 vhost), so an uploaded file can never run as code.
- **Asset model:** a dedicated `media_assets` table/model owns the underlying Spatie record and global metadata (alt text, caption defaults, credit, focal point, status). Galleries and content blocks reference the asset instead of attaching duplicate media rows.
- **Conversions** generated on upload via queued jobs: `thumb` (400px), `medium` (800px), `large` (1600px), each also as WebP; originals retained.
- **Upload flow:** files POST to the app (`multipart/form-data`) into a temporary location; a finalize step verifies ownership, size, magic-byte MIME, image decoding, and checksum before creating the asset and moving it onto the media disk. A scheduled command sweeps abandoned temp files. (No presigned-URL/quarantine-bucket dance is needed with a local disk — the app already sits in the request path.)
- **Upload constraints:** images `jpg/png/webp/heic` ≤ 15 MB (HEIC transcoded on upload); documents `pdf` ≤ 25 MB (for flyers, program applications). MIME is sniffed and decoded server-side, not extension-trusted. Production provisioning installs Imagick/ImageMagick with HEIF support and tests HEIC conversion during deploy/health checks.
- **Central media screen** in the CMS: grid, search by filename/alt, see which content uses each file, edit alt text globally, delete (blocked if in use).
- **EXIF stripped** on upload (privacy — photos of families and youth may carry GPS data). This matters for NDN's community photos.
- **Backup coverage:** because media lives on the box (not in a versioned bucket), the media directory is included in the nightly backup set (Section 15.3) so a lost EBS volume is recoverable.
- Responsive `srcset` generated by medialibrary and consumed by the shared `<Img>` React component.

---

## 11. Public Site Rendering & SEO

- Public pages are Inertia/React pages under `resources/js/pages/public/`, wrapped in `public-layout.tsx` (header menu, footer, partner banner from settings).
- **SSR enabled for public routes** (`inertiajs/inertia-laravel` SSR via a Node sidecar process managed by Supervisor, `php artisan inertia:start-ssr`). Crawlers and social scrapers get full HTML; this also improves LCP on slow reservations/rural connections — a real consideration for NDN's audience.
- Per-page `<Head>`: SEO title/description fields, canonical URL, OpenGraph + Twitter cards with the page's OG image (fallback to site default).
- `sitemap.xml` regenerated hourly by the scheduler from published content; `robots.txt` blocks `/admin`.
- 301 redirect map table (`redirects`: from_path, to_path) editable by admins — critical for migration (Section 12) so old URLs keep working.
- Accessibility target: WCAG 2.2 AA — semantic landmarks, skip link, focus states (including focus-not-obscured), target sizing, required alt text, color-contrast-checked palette, keyboard-navigable menus and lightbox.
- Performance budget: < 200KB JS on public routes (code-split per page via Vite), CloudFront-cached static assets with immutable hashes, lazy-loaded images.
- Route resolution for hierarchical pages — a catch-all at the **bottom** of `routes/web.php`:

```php
Route::get('/{slugPath}', [Public\PageController::class, 'show'])
    ->where('slugPath', '^(?!admin|invite|login)[a-z0-9\-\/]+$')
    ->name('pages.show');
```

---

## 12. Content Migration Plan

Goal: nothing the current site says is lost, and no inbound link breaks.

1. **Inventory (with NDN):** crawl the existing site (`wget --mirror` + a spreadsheet), list every page, image, PDF, and external link. NDN marks each row *keep / rewrite / drop*.
2. **Media first:** pull all images/PDFs, deduplicate, upload to the media library via `ImportLegacyContent` (batch, sets filename-derived alt text placeholders for the team to review — flagged in an "Alt text needed" filter).
3. **Content import:** `php artisan legacy:import` maps old pages → new Pages/Programs/Posts. HTML bodies are converted into `rich_text` + `image` blocks (DOMDocument walk; anything unmappable lands in a rich_text block for hand-cleanup). Everything imports as **draft**.
4. **Redirect map:** every old URL gets a row in `redirects` pointing at its new home. Verified with a script that requests every legacy URL and asserts 301 → 200.
5. **Editorial pass:** NDN team reviews drafts in staging (this doubles as their hands-on training), then publishes.
6. **Freeze window:** 48 hours before launch, old site content is frozen; final delta re-import if anything changed.

---

## 13. AWS Infrastructure

Everything lives in an **NDN-owned AWS account** (Isaac as IAM admin during the support year — no personal-account hosting, so ownership is real).

```
Route 53 (nativedadsnetwork.org)
    │
    ├── CloudFront (CDN) ── ACM cert (us-east-1)
    │       ├── default origin ──► EC2 (Apache; dynamic caching disabled)
    │       ├── /build/* origin ─► EC2 (immutable hashed assets)
    │       └── /storage/* origin ─► EC2 (media on EBS via storage:link, long-lived cache)
    │
    ├── EC2 t3a.small (2 vCPU, 2GB) — Ubuntu 24.04 LTS
    │       ├── Ubuntu-maintained Apache 2.4 (event MPM) + PHP-FPM 8.5 via mod_proxy_fcgi
    │       ├── MySQL 9.7 LTS, latest patch (local, Oracle apt repo — fits budget; RDS is the upgrade path)
    │       ├── Supervisor: queue:work, inertia SSR
    │       ├── EBS gp3 30GB, encrypted
    │       └── Elastic IP
    │
    ├── S3: ndn-backups only (versioned, lifecycle 90d → Glacier) — media is on EBS, not S3
    ├── SES (invite/reset/contact emails; domain verified, DKIM+SPF+DMARC)
    └── CloudWatch (alarms: CPU, disk, status checks) + AWS Budgets alert at $50
```

Provisioning notes:

- **CloudFront behaviors:** the default Laravel/Inertia behavior forwards required methods, query strings, headers, and cookies and uses minimum/default/maximum TTL `0`; it must never cache authenticated, CSRF, preview, invite, or `Set-Cookie` responses. Only `/build/*` and `/storage/*` (media) receive long-lived cache policies. The `/storage/*` behavior is tested explicitly, and Apache blocks PHP execution under that path.
- **Origin protection:** ports 80/443 accept CloudFront origin traffic, and Apache requires a rotated secret origin header added by CloudFront (with a controlled exception for certificate renewal/health administration). This prevents direct Elastic-IP access from bypassing CloudFront. Port 22 remains closed; use SSM Session Manager.
- **IAM:** EC2 uses an instance role scoped to the backups bucket + SES send; no long-lived keys exist on the box. GitHub Actions uses a repository-, environment-, and branch-restricted OIDC role with temporary credentials—not an IAM user/access key.
- **MySQL local vs RDS:** local MySQL keeps the bill ≈ $25/mo. Nightly dumps to S3 (Section 15) cover durability. If NDN's funding recovers, `RDS db.t4g.micro` (~+$15/mo) is a one-evening migration.
- **capacity:** begin with `t3a.small` only after a production-like load test and explicit PHP-FPM/MySQL/SSR memory limits. Build frontend assets in CI, not on the 2GB production server. A 2GB swapfile is an OOM safety net, not capacity; upgrade to a 4GB instance if normal peak memory approaches the limit.
- **staging isolation:** same-box staging has its own `APP_KEY`, database/user, session cookie name/domain, cache prefix, queue prefix, media storage path + backup prefix, logs, Supervisor processes, and scheduler policy so it cannot collide with production.
- **SES:** request production access before launch and configure bounce/complaint event handling and suppression monitoring in addition to DKIM, SPF, and DMARC.
- **Unattended-upgrades** enabled for daily security patches, with reboots handled by the automated monthly maintenance window (Section 15.2); PHP from the ondrej/php PPA, Apache from Ubuntu's maintained packages.

Apache configuration (`deploy/apache-vhost.conf`) — event MPM with PHP-FPM over `mod_proxy_fcgi` (never `mod_php`, which forces the slower prefork MPM and bloats memory on a 2GB box):

```apache
<VirtualHost *:80>
    ServerName nativedadsnetwork.org
    ServerAlias www.nativedadsnetwork.org
    Redirect permanent / https://nativedadsnetwork.org/
</VirtualHost>

<VirtualHost *:443>
    ServerName nativedadsnetwork.org
    DocumentRoot /var/www/ndn/current/public

    SSLEngine on
    SSLCertificateFile /etc/letsencrypt/live/nativedadsnetwork.org/fullchain.pem
    SSLCertificateKeyFile /etc/letsencrypt/live/nativedadsnetwork.org/privkey.pem
    Protocols h2 http/1.1

    <Directory /var/www/ndn/current/public>
        AllowOverride None
        Require all granted
        FallbackResource /index.php
    </Directory>

    <FilesMatch \.php$>
        SetHandler "proxy:unix:/run/php/php8.5-fpm-ndn.sock|fcgi://localhost"
    </FilesMatch>

    # Never execute PHP from the uploads path; block dotfiles
    <DirectoryMatch "^/var/www/ndn/current/public/storage">
        <FilesMatch \.php$>
            Require all denied
        </FilesMatch>
    </DirectoryMatch>
    <FilesMatch "^\.">
        Require all denied
    </FilesMatch>

    # Hashed build assets — cache forever (CloudFront respects these headers)
    <LocationMatch "^/build/">
        Header set Cache-Control "public, max-age=31536000, immutable"
    </LocationMatch>

    ErrorLog ${APACHE_LOG_DIR}/ndn-error.log
    CustomLog ${APACHE_LOG_DIR}/ndn-access.log combined
</VirtualHost>
```

Required modules: `a2enmod proxy_fcgi setenvif rewrite headers ssl http2` + `a2enconf php8.5-fpm`. Note `AllowOverride None` with `FallbackResource /index.php` replaces Laravel's `public/.htaccess` — routing rules live in the vhost, which is faster (no per-request `.htaccess` scan) and can't be broken by a stray uploaded file. If ACM/CloudFront terminates TLS in front, the origin cert can be Let's Encrypt via `certbot --apache` with auto-renewal; either way Apache still serves 443 so origin traffic is encrypted end to end.

---

## 14. Deployment Pipeline

GitHub Actions → AWS OIDC → S3 release artifact + SSM Run Command, with atomic releases. Port 22 remains closed and GitHub stores no AWS access key or server SSH key.

```
push to main
    └── ci.yml: pint --test, phpstan, php artisan test, npm run build (fail fast)
            └── deploy.yml (needs: ci):
                    1. npm ci && npm run build:ssr in CI
                    2. package source + built assets; upload immutable artifact to deployment S3
                    3. assume branch/environment-restricted AWS role through GitHub OIDC
                    4. invoke a versioned SSM document on the target instance
                    5. instance downloads/verifies artifact into releases/{timestamp}
                    6. composer install --no-dev --optimize-autoloader
                    7. link shared/.env and shared/storage
                    8. run backward-compatible `php artisan migrate --force`
                    9. cache config/routes/views/events; atomically switch `current`
                   10. restart queues + SSR; health-check; app-code rollback on failure
```

- `php artisan down --render="errors::maintenance"` is **not** needed for normal deploys (atomic symlink), only for destructive migrations.
- Staging deploys from the `staging` branch to the staging vhost with the same script.
- Rollback repoints the application symlink only. It never automatically runs `migrate:rollback`, which can discard data. Database changes follow expand/contract releases: additions are backward-compatible with the old and new application, and destructive cleanup occurs only in a later release after the rollback window closes.

---

## 15. Backups, Monitoring, Logging & Scheduled Automation

### 15.1 Application automation (Laravel scheduler)

Nothing in day-to-day operation requires a human. A single cron entry drives everything:

```cron
* * * * * cd /var/www/ndn/current && php artisan schedule:run >> /dev/null 2>&1
```

All schedules are defined in code (`routes/console.php`), so they're version-controlled and identical across environments:

```php
<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('invites:prune')->dailyAt('00:10');           // expired invites
Schedule::command('backup:clean')->dailyAt('00:30');            // rotate old backups
Schedule::command('backup:run')->dailyAt('01:00');              // DB dump → S3
Schedule::command('backup:monitor')->dailyAt('08:00');          // alert if last backup missing/stale
Schedule::command('sitemap:generate')->hourly();
Schedule::command('contact:prune')->monthly();                  // 24-month retention (Section 8.9)
Schedule::command('activitylog:clean')->weekly();               // trim audit log per config
```

Every scheduled task pings a free healthchecks.io check on success (`->thenPing($url)`); if a ping goes missing, Isaac gets an email. That closes the classic failure mode of cron jobs dying silently for months. `backup:monitor` is the second safety net — it inspects the S3 bucket itself and alerts if the newest backup is older than a day or suspiciously small.

### 15.2 Server maintenance automation (unattended, monthly reboot)

Two layers, both hands-off:

**Layer 1 — daily security patches (no reboot).** Ubuntu's `unattended-upgrades` applies security updates automatically every day. Reboot-required patches (kernel, libc) are installed but the reboot itself is deferred to Layer 2 (`Unattended-Upgrade::Automatic-Reboot "false"`), so the site never restarts unexpectedly mid-day.

**Layer 2 — monthly full update + reboot at midnight.** A systemd timer runs a maintenance script on the 1st of each month at 00:15 Pacific:

`/etc/systemd/system/ndn-maintenance.timer`:

```ini
[Unit]
Description=Monthly full update and reboot for NDN server

[Timer]
OnCalendar=*-*-01 00:15:00
Persistent=true

[Install]
WantedBy=timers.target
```

(Server clock is set to `America/Los_Angeles` via `timedatectl set-timezone`, so 00:15 means 12:15am Pacific. `Persistent=true` runs a missed window at next boot.)

`/etc/systemd/system/ndn-maintenance.service`:

```ini
[Unit]
Description=NDN monthly maintenance

[Service]
Type=oneshot
ExecStart=/usr/local/bin/ndn-maintenance.sh
```

`/usr/local/bin/ndn-maintenance.sh`:

```bash
#!/usr/bin/env bash
set -euo pipefail
exec >> /var/log/ndn-maintenance.log 2>&1

echo "=== Maintenance run: $(date -Is) ==="

# 1. Fresh off-schedule backup before touching anything
cd /var/www/ndn/current
sudo -u www-data php artisan backup:run --only-db

# 2. Apply updates within the explicitly pinned Ubuntu/PHP/MySQL release tracks.
#    Validate the package plan before enabling this unattended in production.
apt-get update
DEBIAN_FRONTEND=noninteractive apt-get -y \
    -o Dpkg::Options::="--force-confdef" \
    -o Dpkg::Options::="--force-confold" \
    upgrade
apt-get clean

# 3. Run local health checks; reboot only if the OS reports it is required.
apachectl configtest
php artisan about >/dev/null
mysqladmin ping

if [[ -f /var/run/reboot-required ]]; then
    echo "Rebooting at $(date -Is)"
    systemctl reboot
fi
```

Enable once: `chmod +x /usr/local/bin/ndn-maintenance.sh && systemctl daemon-reload && systemctl enable --now ndn-maintenance.timer`. Verify the schedule anytime with `systemctl list-timers ndn-maintenance.timer`.

**What makes the reboot safe with nobody watching:**

- Every service is systemd-managed and enabled at boot: `apache2`, `mysql`, `php8.5-fpm`, `supervisor` (which in turn restarts `queue:work` and the Inertia SSR process). Swap is in `/etc/fstab`. There is **zero** manual start step — this is verified during the launch-week reboot drill (Phase 5).
- Total downtime is the reboot itself, ~60–90 seconds at 12:15am Pacific on the 1st — the lowest-traffic moment available.
- The uptime monitor (below) pings `/up` every minute. If the box isn't serving again within 5 minutes, Isaac gets an email + SMS. So the failure mode of "rebooted and didn't come back" pages a human automatically instead of being discovered days later.
- The pre-upgrade DB backup means even a catastrophic bad-kernel scenario is recoverable to a fresh instance from S3.
- MySQL is given a clean shutdown by systemd ordering (default `TimeoutStopSec` is sufficient at this DB size; InnoDB crash recovery covers the worst case anyway).

**What stays reviewed through a PR:** PHP/MySQL series upgrades and Composer/npm dependency changes. Security updates are handled promptly; routine dependency updates are grouped monthly rather than postponed for a quarter. External apt repositories are pinned to the intended PHP 8.5 and MySQL 9.7 tracks so the OS timer cannot silently cross a runtime/database series.

### 15.3 Backups

- **Nightly (automated, Section 15.1)** `spatie/laravel-backup`: mysqldump + the media directory (`storage/app/public`) + a **redacted** configuration inventory → `ndn-backups` S3 bucket (versioned, encrypted, lifecycle to Glacier after 90 days, retained 1 year). Because media lives on EBS rather than a versioned bucket, including it here is what makes uploaded photos recoverable after a lost volume. Never place plaintext `.env` secrets in the normal backup archive; recovery secrets live in Parameter Store/Secrets Manager or a separately encrypted, separately authorized recovery package. Coordinate Spatie cleanup with S3 versioning/noncurrent-version lifecycle so the combined policy really retains one year without unbounded storage.
- **Weekly automated restore test** to a scratch database on the same box (`backup:restore-test` custom command, scheduled `weeklyOn(0, '02:00')`) — a backup that's never been restored is a rumor.

### 15.4 Monitoring & logging

- **Uptime:** external ping via a free tier monitor (UptimeRobot/Hetrix) on `/up` (Laravel health route) → email + SMS to Isaac. This is also the watchdog for the monthly automated reboot (Section 15.2).
- **CloudWatch alarms:** CPU > 80% (15 min), disk > 80%, memory pressure, instance status check fail, AWS Budgets alert at $50. Disk/memory/swap metrics require the configured CloudWatch Agent; they are not default EC2 metrics.
- **App errors:** Laravel log → daily driver; exceptions also to a free Sentry project (10k events/mo free tier) during the support year.
- **Activity log** (Section 7) gives content-level auditability; Apache access logs rotated 14 days via logrotate.

---

## 16. Security Hardening Checklist

Application:

- [x] No public registration; invite-only, domain-locked at 4 layers (Section 6)
- [x] Argon2id password hashing; 12-char minimum, HIBP-checked
- [x] 2FA available; enforced for super admins
- [x] CSRF on all state changes (Laravel default); session cookies Secure/HttpOnly/SameSite
- [x] All queries via Eloquent/bindings (no raw SQL string interpolation)
- [x] Block content sanitized server-side; no raw-HTML block exists
- [x] Signed URLs for invites and draft previews
- [x] Rate limiting: login 5/min, invite accept 5/min, contact form 3/10min
- [x] File uploads: MIME-sniffed, size-capped, EXIF-stripped; stored on EBS outside the repo, served through CloudFront with PHP execution denied under `public/storage`
- [x] Security headers via middleware: CSP with a per-request Laravel Vite nonce (`Vite::useCspNonce()`), X-Frame-Options DENY, X-Content-Type-Options, `Referrer-Policy: no-referrer`, and HSTS (preload only after every applicable subdomain is confirmed HTTPS-ready)
- [x] `APP_DEBUG=false` in production; verbose errors never leak

Infrastructure:

- [x] SSH via SSM only (or key-only + IP-restricted); root login disabled; fail2ban if port 22 stays open
- [x] ufw: only 80/443 exposed
- [x] Media on EBS (not S3); the only bucket is the private `ndn-backups` (Block Public Access ON, encrypted); zero public buckets
- [x] IAM least-privilege instance role; no static AWS keys in `.env` on the server
- [x] Encrypted EBS, encrypted S3 (SSE-S3), TLS 1.2+ only at CloudFront
- [x] Unattended security upgrades; monthly manual patch review during support year
- [x] MySQL bound to 127.0.0.1; distinct app user with scoped grants
- [x] `composer audit` + `npm audit` in CI; Dependabot enabled on the repo

---

## 17. Testing Strategy

Pest 4 is installed explicitly with its Laravel plugin (the current React starter kit ships PHPUnit, not Pest, by default). Larastan/PHPStan is also installed explicitly because CI invokes it. Feature tests are the priority; this codebase's risk is in auth and publishing logic, not algorithms.

Must-have feature tests (the domain lock gets the deepest coverage):

```php
// tests/Feature/InviteTest.php — the critical suite
it('rejects invites to non-NDN email domains', function () {
    $admin = User::factory()->superAdmin()->create();

    $this->actingAs($admin)
        ->post(route('admin.invites.store'), [
            'email' => 'outsider@gmail.com',
            'role' => 'editor',
        ])
        ->assertSessionHasErrors('email');

    expect(Invite::count())->toBe(0);
});

it('rejects lookalike subdomain emails', function () {
    // user@nativedadsnetwork.org.evil.com and user@fake-nativedadsnetwork.org
});

it('rejects expired, reused, and tampered invite tokens', function () { /* … */ });

it('blocks login for users whose domain is removed from the allow list', function () { /* … */ });

it('logs out an active session when the account is deactivated', function () { /* … */ });

it('never allows the last super admin to be demoted or deactivated', function () { /* … */ });
```

Plus per-module CRUD/policy tests (editors can't delete others' content, drafts invisible publicly, scheduled posts appear on time — time-travel with `$this->travelTo()`), block sanitizer unit tests (script injection attempts stripped), and a smoke test that every seeded public route returns 200. CI runs the suite on every PR; `main` is branch-protected and requires green CI.

---

## 18. Training & Handoff Documentation

Deliverables to NDN at launch:

1. **Admin Manual** (PDF + a locked page inside the CMS itself): screenshots of every screen, step-by-step "How do I…" recipes — add a news post, create an event, build a page from blocks, reorder the menu, invite a new staff member, deactivate a departed one.
2. **Screencasts** (5–8 min each, unlisted YouTube or files in their Drive): CMS tour; pages & blocks; events/news/galleries; user management & invites.
3. **Two live training sessions** (recorded): one editorial (whole team), one admin-level (Mike + designated admin) covering invites, roles, settings, and what to do if something looks wrong.
4. **Runbook** (for Isaac during year 1, then whoever follows): server access, deploy, rollback, restore-from-backup drill, DNS/SES notes, monthly maintenance checklist, "hiring a future developer" onboarding notes.
5. **Ownership manifest:** NDN owns the AWS account, GitHub org, domain registrar access, and this documentation. Isaac holds delegated access that NDN can revoke at any time — stated in writing.

---

## 19. Build Timeline & Phases

Realistic part-time schedule (~10–12 weeks calendar time):

| Phase | Weeks | Work | Exit criteria |
|---|---|---|---|
| 0. Discovery & design | 1–2 | Content inventory with NDN, sitemap, Figma-level mockups (home, program, event, news, generic page), design sign-off | Mike approves mockups |
| 1. Foundation | 3–4 | Repo, CI, Laravel+Inertia scaffold, auth + invites + domain lock, roles, admin shell, media library | Invite flow demo; all Section 17 auth tests green |
| 2. Content modules | 5–7 | Pages + block builder, programs, events, news, galleries, team, menus, settings, contact | NDN staging accounts created; team can build a page unassisted |
| 3. Public site | 7–8 | Public layouts, all public routes, SSR, SEO, accessibility pass | Lighthouse ≥ 90 perf/a11y/SEO on key pages |
| 4. Migration | 8–9 | Legacy import, redirect map, NDN editorial review (doubles as training) | Every legacy URL 301s correctly; content approved |
| 5. Hardening & launch | 10 | AWS prod build-out, backups + restore drill, security checklist, load sanity check, DNS cutover | Launch ✅ |
| 6. Training & handoff | 11–12 | Manual, screencasts, live sessions, runbook, ownership transfer | NDN publishes a post with zero help |
| 7. Support year | ongoing | OS patching, reboots, backups, and invite cleanup are fully automated (Section 15); Isaac does a quarterly dependency-upgrade PR (~30 min), fixes, small tweaks | Monthly automated maintenance log + check-in note to Mike |

---

## 20. Monthly Cost Estimate

| Item | Monthly |
|---|---|
| EC2 t3a.small (on-demand) | ~$14 |
| Public IPv4 / Elastic IP (~730h × $0.005) | ~$3.65 |
| EBS 30GB gp3 + snapshots (holds app + media) | ~$3 |
| S3 (backups only, ~10GB versioned) | ~$0.50 |
| CloudFront (low traffic tier) | ~$1–3 |
| Route 53 hosted zone | $0.50 |
| SES (transactional volume) | < $1 |
| Data transfer | ~$1–3 |
| **Total** | **≈ $26–31/mo** |

Comfortably inside the quoted $20–50/mo, with headroom for RDS (~+$15) or a bigger instance if traffic grows. A 1-year EC2 savings plan cuts the instance cost ~30% if NDN wants to prepay.

Domain registration and any optional paid third-party service (e.g., paid Sentry, Turnstile alternatives) are outside this estimate and would be approved by NDN first, per the agreement.

---

## Appendix A — Quick Reference: Where the Domain Lock Lives

| Layer | File | What it does |
|---|---|---|
| Config | `config/admin.php` + `ADMIN_ALLOWED_DOMAINS` env | Single source of truth for allowed domains |
| Validation rule | `app/Rules/AllowedEmailDomain.php` | Reusable rule |
| Invite creation | `Admin/InviteController@store` | Can't invite outside domains |
| Invite acceptance | `Auth/AcceptInviteController@store` | Re-validates at registration |
| Login | `FortifyServiceProvider` `authenticateUsing` | Refuses auth if domain no longer allowed |
| Every admin request | `Middleware/EnsureNdnEmailDomain` (`ndn.domain`) | Kills sessions of unauthorized/deactivated users |
| Tests | `tests/Feature/InviteTest.php` etc. | Locks the behavior in CI forever |

## Appendix B — Commands Cheat Sheet

```bash
php artisan migrate --seed              # fresh setup
php artisan db:seed --class=SuperAdminSeeder
php artisan legacy:import --dry-run     # migration preview
php artisan schedule:list               # see every automated task + next run time
php artisan invites:prune               # runs daily automatically; manual override
php artisan backup:run                  # runs nightly automatically; manual override
php artisan inertia:start-ssr           # SSR server (Supervisor-managed in prod)
./deploy/deploy.sh production           # manual deploy (CI normally does this)

# Server maintenance (all automated — these are for inspection only)
systemctl list-timers ndn-maintenance.timer     # next monthly update+reboot
tail /var/log/ndn-maintenance.log               # what the last run did
systemctl start ndn-maintenance.service         # force a maintenance run now
```

---

*Prepared by Isaac Hollow Horn Bear — The Lakota Dev — as the technical companion to the Native Dads Network website partnership proposal.*
