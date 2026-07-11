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
- [~] Admin layout — reuse starter `AppLayout` sidebar shell; **TODO** swap nav to CMS modules (permission-gated).
- [ ] Public layout (`public-layout.tsx`) — header menu + footer from settings/menus, partner banner, skip link.
- [ ] Shared `<Img>`, `<Pagination>`, `<StatusBadge>`, index `<DataTable>` (search + status filter), form field components.
- [ ] Update backend test suite for Inertia responses (currently assert JSON) — keep green.

## Phase 1 — Block system (shared admin preview + public render)

- [ ] Block TS types + registry.
- [ ] `block-renderer.tsx` — maps block type → component, used by both admin preview and public site.
- [ ] Per-block components (13): hero, rich_text, image, image_text, gallery_embed, video_embed, cards, cta_banner, events_list, news_list, team_grid, accordion, divider/spacer.
- [ ] Tiptap editor integration (rich_text) with allow-listed marks/nodes matching server sanitizer.
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
- [ ] RSS feed for news, sitemap.xml/robots.txt (deferred — needs spatie/laravel-feed + sitemap).
- [ ] SSR runtime enabled (build:ssr produces a bundle; the Supervisor SSR process is a deploy-phase item).

## Phase 5 — Content migration (from nativedadsnetwork.org) — DONE

- [x] `LegacyContentSeeder` imports org settings, team roster (Mike Duncan, Albert Titman, Patricia Titman, Joshua Hoaglen Sr.), programs (Wellness/Youth/Conferences), events (Red Road to Wellbriety, Wake Up With Wellness, Parenting Circle), news (Woodland Mural, Thicha Wole), an About page, and header/footer menus — all published.
- [ ] Real media/image import from the legacy host (deferred with the production S3 upload flow).

## Verification

- [x] `npm run types:check`, `npm run lint:check`, `npm run format:check` clean.
- [x] `npm run build` and `npm run build:ssr` succeed.
- [x] Backend `pint` + `phpstan` (level 7) clean after the conversion.
- [x] Pest suite updated for Inertia responses — 85 passed / 2 skipped.
- [x] Manual smoke: public home + admin dashboard + program block editor verified in a real browser session.

## Requested enhancements (planned — July 11, 2026)

Design ground rules (confirmed): the public site's visual/brand design is a **blank slate**, fully restyleable later without touching content. The CMS team edits **content** (text/media) and **layout + order** (block builder); the theme/look of each block is developer-controlled. **Workflow:** the design will be produced in Claude Design; once approved, an agent here re-implements the theme + block components against it — globally, without re-entering any content.

**Source material — use the legacy repo, not live-site scraping:** https://github.com/NativeDadsNetwork/Nativedads (public). It contains the full static HTML of the old site (exact copy for board/staff bios, programs, about — `director.html`, `boardChair.html`, `purpose.html`, `wellness.html`, etc.) **and `img/` with 407 real images**: `img/partners` (funder logos — 7th Generation Fund, California Endowment, Elevate Youth California, Native Voices Rising, Common Counsel), `img/avatars` (team photos), `img/gallery` + `img/newGallery`, `img/logos` (NDN logo + Boys With Braids + fatherhood), `img/mural-project`. This upgrades every migration/enhancement item below with real copy + assets.

- [ ] **Calendar view for events.** A public month-grid calendar of published events (in addition to the current upcoming/past lists), events clickable to their detail page; optional admin calendar view. Uses the existing event date/timezone data.

- [ ] **Import real assets + copy from the legacy repo.** Pull `img/*` into the media library (team avatars, partner logos, gallery, mural, org logo/favicon), and enrich `LegacyContentSeeder` with the exact board/staff roster + bios and program/about copy from the repo's HTML. Replaces the placeholder "Laravel Starter Kit" logo with the real NDN logo.

- [ ] **Partners module.** A dedicated Partners CRUD (name, logo image, website URL, sort_order, is_active) mirroring the Team module — fits the existing content-module pattern. Add a `partners` block for pages + a partners/funders strip on the home page and footer, replacing the single `partner_banner` setting. Seed the legacy funders (7th Generation Vitality Grant, Elevate Youth California, Common Counsel Foundation, Native Voices Rising, The California Endowment).
- [ ] **Team public pages + tabbed browsing.** Public team section where visitors cycle through members (tabs/carousel), each member with its own shareable URL (e.g. `/about/team/{slug}`), SSR-enabled so crawlers and social scrapers get full HTML.
- [ ] **Auto-generated social (OG) image per team member.** On save, composite the member's photo + name + title into a share image so link previews look polished. Needs a server-side image pipeline (Intervention/Imagick or HTML-to-image). Same pattern reusable for programs/news later.
- [ ] **Enable SSR runtime for public routes** (prerequisite for the team social/SEO above). The SSR bundle already builds via `build:ssr`; this wires up the Node SSR process (`inertia:start-ssr` under Supervisor in prod, local dev toggle).
- [ ] **Visual (in-place) page editor — second editing mode.** Render the real page and let editors click text to edit inline (Tiptap), replace images in place, drag blocks, and insert blocks between them — coexisting with the current form + preview flow (editors choose either). Feasible because block components are already shared between admin preview and public render; largest item of this set.

## Deferred by design
- [ ] Final visual/brand design of the public site (colors, typography, imagery, motion).
- [ ] Real media asset migration from legacy host; production S3 upload flow.
- [ ] RSS + sitemap packages; trashed-content recovery UI.
