# Native Dads Network CMS — Frontend Build Checklist

Last updated: July 11, 2026

Tracks the Inertia + React 19 + TypeScript frontend: the admin content manager, the public site, the shared block system, and content migration from the legacy nativedadsnetwork.org. Backend is complete and verified (see `backend-build-checklist.md`).

Scope note: **functional completeness first.** Visual/brand design of the public site is a later pass — build clean, accessible, Tailwind/shadcn UI now; polish later. Content is seeded from the legacy site.

Status legend: `[ ]` todo · `[~]` in progress · `[x]` done

## Phase 0 — Foundation

- [x] Confirmed Inertia bootstrap (server-side per-page vite resolution via `app.blade.php`; `app.tsx` maps page-name → layout). Shared props extended: auth user + roles + permissions, `flash`, cached `settings`.
- [x] Shared TypeScript types (`types/models.ts`): Page, Program, NdnEvent, Post, Category, Gallery, MediaAsset, TeamMember, Menu, MenuItem, Setting, ContactSubmission, Invite, Activity + Block union, Paginated<T>. User type extended with roles/permissions.
- [x] Installed frontend libs: `@dnd-kit/*`, `@tiptap/*`, Radix (tabs, switch, alert-dialog, popover, accordion). Added shadcn primitives: textarea, tabs, switch, table, alert-dialog, popover, accordion.
- [x] Converted admin controllers to Inertia: content modules (pages/programs/events/posts/galleries/team) via `ContentController` (index/create/edit render, mutations redirect); dashboard, activity, contacts, menus, users, settings, invites, media (dual-mode JSON+Inertia). Routes switched `apiResource`→`resource` (create/edit). Kept category/media-mutation/invite-mutation/contact-mutation as JSON for in-page AJAX.
- [x] Admin layout with permission-gated CMS navigation.
- [x] Public layout (`public-layout.tsx`) — nested header menu, footer from settings/menus, partner strip, skip link.
- [x] Shared pagination/status/index/form components. A responsive shared `<Img>` abstraction remains deferred.
- [x] Backend test suite updated for Inertia responses and kept green.

## Phase 1 — Block system (shared admin preview + public render)

- [x] Block TS types + registry.
- [x] `block-renderer.tsx` — maps block type → component, used by both admin preview and public site.
- [x] Per-block components: hero, rich_text, image, image_text, gallery_embed, video_embed, cards, cta_banner, events_list, news_list, team_grid, partners, accordion, divider/spacer.
- [x] Tiptap editor integration (rich_text) with allow-listed marks/nodes matching server sanitizer.
- [x] Block builder editor — add/reorder (@dnd-kit), duplicate/delete/collapse, per-block settings panels (`blocks/block-builder.tsx`). Server-side `BlockHydrator` resolves media/gallery/events refs for both public render and admin edit previews.
- [x] Block registry (`blocks/registry.tsx`) — editor + renderer for all 14 types; Tiptap editor + dependency-light renderer (`blocks/tiptap.tsx`); shared `block-renderer.tsx`.

## Phase 2 — Admin content manager (screens) — DONE

- [x] Dashboard (CMS counts + quick actions).
- [x] Pages — index, create/edit (hierarchy parent select, lock handling, block builder, SEO, publish).
- [x] Programs — index, create/edit (blocks, contact, external URL, SEO).
- [x] Events — index (upcoming/past tabs), create/edit (date/time/tz, all-day toggle, location vs virtual, registration URL).
- [x] News/Posts — index, create/edit (excerpt, blocks, categories, author, featured) + inline category management.
- [x] Galleries — index, create/edit (media picker + dnd-ordered photos, per-photo alt/caption).
- [x] Team members — index, create/edit (photo picker, bio, public contact toggles, is_active).
- [x] Media library — grid, upload (Inertia useForm), search + type filter, alt/caption/credit edit dialog, "used by" count, in-use delete protection.
- [x] Menus — header/footer editor, morph link picker (Page/Program/Post/Event) or custom URL, one-level nesting.
- [x] Settings — grouped tabs (general/contact/social/seo), media pickers, per-key validation.
- [x] Users (role/activate inline) & Invites (create/resend/revoke, status).
- [x] Activity log table.
- [x] Contact inbox — read/unread, expandable message, delete.
- [ ] Recovery UI for trashed content restore / force-delete (backend endpoints exist; a dedicated admin screen is not yet built — deferred).

## Phase 3 — Auth screens

- [x] Accept-invite page (controller now Inertia) — name/password, submits to the signed accept URL.
- [x] Login / forgot / reset / verify-email / two-factor / passkeys — starter-kit pages retained.
- [x] Flash toasts wired globally (`components/flash-toaster.tsx`) from shared `flash` prop.

## Phase 4 — Public site — DONE (functional; visual design deferred)

- [x] `PublicLayout` (header/footer from menus + settings), home (programs/events/news/team).
- [x] Hierarchical page catch-all via block renderer + 301 redirect handling.
- [x] Programs index/detail; Events index (upcoming/past) + detail; News index (category filter, pagination) + detail.
- [x] Gallery index + detail with lightbox; Contact (form, honeypot, min-time).
- [x] Per-page SEO `<Head>` (`components/public/seo-head.tsx` — title/description/OG/Twitter).
- [x] First-party `sitemap.xml` plus `robots.txt` crawler exclusions.
- [ ] RSS feed for news.
- [x] SSR runtime enabled (`config/inertia.php` `ssr.bundle` → `bootstrap/ssr/app.js`; verified initial HTML is server-rendered incl. per-page OG meta). Prod runs `inertia:start-ssr` under Supervisor.

## Phase 5 — Content migration (from nativedadsnetwork.org) — DONE

- [x] `LegacyContentSeeder` imports org settings, team roster (Mike Duncan, Albert Titman, Patricia Titman, Joshua Hoaglen Sr.), programs (Wellness/Youth/Conferences), events (Red Road to Wellbriety, Wake Up With Wellness, Parenting Circle), news (Woodland Mural, Thicha Wole), an About page, and header/footer menus — all published.
- [x] Real media/image import from the legacy repo into the media library on **local disk** (`storage/app/public` via `php artisan storage:link`).

## Verification

- [x] `npm run types:check`, `npm run lint:check`, `npm run format:check` clean.
- [x] `npm run build` and `npm run build:ssr` succeed.
- [x] Backend `pint` + `phpstan` (level 7) clean after the conversion.
- [x] Pest suite — 121 passed / 2 intentionally skipped (503 assertions).
- [x] Manual smoke **re-run with the SSR runtime on** (July 11, second pass): public home, events, admin dashboard, pages index, and the page editor verified in a real browser with a clean console.

> **Run the browser smoke test with `inertia:start-ssr` running.** Two defects (below) were invisible to the entire PHP/TS/build suite *and* to a client-rendered browser session — they only appear once SSR is serving the initial HTML, which is how production runs.

## Browser-only regressions found and fixed (July 11, 2026 — second pass)

- [x] **The CSP broke every admin screen under SSR.** `style-src` had a nonce *and* `'unsafe-inline'`; a nonce makes browsers ignore `'unsafe-inline'`, so inline `style="..."` attributes were dropped. SSR emits the sidebar's `style="--sidebar-width:16rem"` into the HTML and React does not re-apply it on hydration, so the variable resolved empty, the sidebar spacer collapsed to `0`, and admin content rendered under the fixed sidebar. Fixed with `style-src-attr 'unsafe-inline'`. (React's runtime styles go through the CSSOM, which CSP does not block — hence invisible without SSR.)
- [x] **Every YouTube embed was CSP-blocked** — `registry.tsx` built `youtube.com/embed/...` while `frame-src` only allowed `youtube-nocookie.com`. The renderer now emits `youtube-nocookie.com`.
- [x] **All-day event dates displayed one day early**, and **editing an all-day event silently blanked its date fields** (a full ISO timestamp fed to `<input type="date">` renders empty, so Save then failed validation with no visible cause). Added `formatDateOnly()` / `toDateInput()` in `lib/format.ts`.
- [x] **`published_at` drifted by the browser's UTC offset on every save** across all five content forms (the input was browser-local, the server re-parsed it as UTC). Forms now submit an absolute instant via `fromDatetimeLocal()`.
- [x] **The visual editor swallowed failed draft saves** — `page-canvas.tsx` ignored `response.ok`, so a 422/419 resolved silently and the preview iframe showed stale blocks while the author believed the edit had saved. It now surfaces the error and will not show a misleading preview.
- [x] **Gallery index photo count was permanently blank** (`media_assets` was never loaded); now uses a `withCount` `media_assets_count`.

## Requested enhancements (planned — July 11, 2026)

Design ground rules (confirmed): the public site's visual/brand design is a **blank slate**, fully restyleable later without touching content. The CMS team edits **content** (text/media) and **layout + order** (block builder); the theme/look of each block is developer-controlled. **Workflow:** the design will be produced in Claude Design; once approved, an agent here re-implements the theme + block components against it — globally, without re-entering any content.

**Source material — use the legacy repo, not live-site scraping:** https://github.com/NativeDadsNetwork/Nativedads (public). It contains the full static HTML of the old site (exact copy for board/staff bios, programs, about — `director.html`, `boardChair.html`, `purpose.html`, `wellness.html`, etc.) **and `img/` with 407 real images**: `img/partners` (funder logos — 7th Generation Fund, California Endowment, Elevate Youth California, Native Voices Rising, Common Counsel), `img/avatars` (team photos), `img/gallery` + `img/newGallery`, `img/logos` (NDN logo + Boys With Braids + fatherhood), `img/mural-project`. This upgrades every migration/enhancement item below with real copy + assets.

- [x] **Calendar view for events.** Public month-grid calendar at `/events/calendar` (route ordered before `events/{slug}`): 6-week Sun–Sat grid, prev/next month navigation via `?month=YYYY-MM`, today highlighted, out-of-month days dimmed, events placed on their day and linked to details. Timed input is interpreted in the selected IANA time zone, stored as UTC, and converted back to the event's zone for admin/public display; all-day dates remain date-only.

- [x] **Import real assets + copy from the legacy repo.** Curated assets committed under `database/seeders/assets/` (logos, 11 real team headshots, funder logos, gallery, mural). `LegacyMediaSeeder` imports them into MediaAssets on local disk; `LegacyContentSeeder` seeds the real 12-person board/staff roster with verbatim bios + photos, 4 real programs, the real About page (purpose/vision/background), 2 galleries, and the real org settings (tagline "Little Efforts Make Big Changes", YouTube, real logo). Fixed two latent backend bugs found in the process: (1) media conversions never generated (the `registerMediaConversions` guard read `$this->type`, which is null on medialibrary's bare model instance — now keys off the media file's mime type); (2) `LegacyContentSeeder` wrote settings via a query-builder `update()` that bypassed the json cast, silently dropping every string setting — now uses model saves. Added a `group` (staff/board) column to team_members. Image driver set to imagick (GD OOM'd on the large headshots).

- [x] **Partners module.** Full CRUD (name, slug, logo, website URL, sort_order, is_active) mirroring Team: `Partner` model + migration (with slug-reuse discriminator), `PartnerPolicy`, `PartnerController` (extends `ContentController`), routes, `partners.*` permissions, admin index (with logo thumbnails via a new `indexRelations()` hook)/create/edit/form, and a sidebar entry (Handshake icon). Public: a site-wide **funder wall** strip above the footer (`PartnersStrip`, shared via `PublicController` + cached in `public_partners`) that shows each partner's logo **and name** so white-knockout logos stay legible, plus a `partners` page block (registry + hydrator + sanitizer). Seeded the 5 real funders with their logos. 4 feature tests. Also replaced the sidebar "Laravel Starter Kit" text with the real site name from shared settings.
- [x] **Team public pages + tabbed browsing.** `/about/team` (routes registered before the page catch-all) with Staff/Board tabs (shadcn Tabs) over a photo grid, and `/about/team/{slug}` member pages showing the full bio with prev/next cycling within the member's group. Home team cards now link through, plus a "Meet the whole team" link. `TeamController` (public) + `public/team/{index,show}.tsx`. 4 feature tests.
- [x] **Auto-generated social (OG) image per team member.** `TeamOgImageGenerator` (Imagick) composites a 1200×630 card — photo panel + name + title + org name on a dark background — written to `storage/app/public/og/team/{slug}.jpg`. Fired by a `saved` model hook (regenerates when name/title/photo change) and explicitly in the seeder (model events are muted there). The member page's `og:image` points at it. DejaVuSans bundled at `resources/fonts/` for portability; `og_image_path` column added.
- [x] **Enable SSR runtime for public routes.** `config/inertia.php` `ssr.bundle` points at `bootstrap/ssr/app.js` (what `@inertiajs/vite` emits). Verified: with `php artisan inertia:start-ssr` running, initial HTML contains SSR-rendered content **and** the per-member `og:image`/OG meta, so crawlers and social scrapers get full pages. Prod: run the SSR process under Supervisor; local: `inertia:start-ssr` alongside `php artisan serve`.
- [x] **Visual editor — three modes in the admin (staging area).** A `BlockEditor` wrapper adds a **Form / Visual / Preview** toggle above the block area in all four block-editing forms (pages, programs, news, events); all three share the same blocks array. It stays **inside the admin portal as a staging area** — edits are drafts and only go live on publish (a banner says so).
  - **Form** — the structured block-builder (collapsible cards + settings).
  - **Visual** — the page rendered inline with click-to-edit text. `rich_text` shows as clean prose (a **seamless** Tiptap mode — the formatting toolbar floats in only when you click into the text); other blocks show their real rendered preview with a hover toolbar (drag-reorder, duplicate, delete, settings gear) plus insert-between/add-at-end.
  - **Visual = a full-page canvas** (`PageCanvas`) with a **device switcher** (desktop / tablet / mobile — reflows responsively) and an **Edit / Preview** sub-toggle:
    - **Edit** renders the blocks editable inside a public-layout shell (header nav, full-width content, partner strip, footer — from the `SiteChrome` service shared to authenticated requests; `PublicController` delegates to it). Click text to edit (seamless Tiptap), **click an image to replace it** (image/hero/image_text show a "Replace image" overlay → media picker), per-block hover toolbar (drag/duplicate/delete/settings), drag-to-reorder, insert-between, add-at-end.
    - **Preview** loads the **actual page in an `<iframe>`** via a signed route (`PreviewController` renders the draft through the real public component + layout, bypassing the published scope; the signed `previewUrl` comes from the edit endpoint). This is the literal live page, so it can **never drift** from the site and will show whatever design (e.g. from Claude Design) is implemented later — no replica to maintain. Clicking Preview (or Refresh) **auto-saves the current blocks as a draft first** (background `PATCH .../blocks` → `ContentController::updateBlocks`, JSON, blocks-only) so the iframe reflects your just-made edits — no "I added something but it's not in the preview" gap. The preview routes also send `Cache-Control: no-store` (`NoStoreCache` middleware) so the iframe — which reloads the same signed URL each time — never serves a stale cached copy. Note for prod: if `X-Frame-Options` is added, allow `SAMEORIGIN` (or exempt `/preview/*`) so the iframe still loads.
  - **Live list-block previews:** newly-added Events / News / Team / Partners blocks render **real sample content immediately** (from a `SiteChrome::blockPreviews()` set shared to the admin), instead of an empty box, before the page is even saved.
  - **Empty-block placeholders:** blocks that need configuring (image/hero/gallery/video with nothing chosen yet) render a labelled dashed placeholder ("Click to choose a gallery" etc.) instead of nothing, so adding them gives clear feedback; a `gallery` with an ID chosen shows a "preview after you save" note.
  - **Readable in dark mode:** the canvas always shows the white public page, so a `.canvas-light` class overrides Tailwind's resolved `--color-*` tokens (not the source `--*` vars — `@theme` resolves those once at `:root`) to keep the in-canvas admin controls (add-block, toolbars, settings inputs) readable on white regardless of the admin's dark/light theme.
  - Files: `blocks/page-canvas.tsx`, `blocks/visual-editor.tsx` (exports `VisualBlocks`), `blocks/block-editor.tsx`, `components/cms/public-shell.tsx`, seamless mode in `blocks/tiptap.tsx`, `Services/SiteChrome.php`. Minor known item: admin forms are also SSR'd and one nullable form input logs a harmless React SSR warning (pre-existing; admin pages don't need SSR — could scope SSR to public routes).

## Deferred by design
- [ ] Final visual/brand design of the public site (colors, typography, imagery, motion).
- [ ] Production deploy wiring for local-disk media (`storage:link`, CloudFront `/storage/*` behavior, media dir in nightly backups). S3 for media is explicitly **not** planned — media library disk is a one-line switch if that ever changes (schematic §10).
- [ ] RSS feed; trashed-content recovery UI; responsive shared image/srcset component.
