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
- [~] Pest suite updated for Inertia responses (in progress — being brought back to green).
- [ ] Manual smoke of each admin flow and public page.

## Deferred by design
- [ ] Final visual/brand design of the public site (colors, typography, imagery, motion).
- [ ] Real media asset migration from legacy host; production S3 upload flow.
- [ ] RSS + sitemap packages; SSR supervisor process; trashed-content recovery UI.
