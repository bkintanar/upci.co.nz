# Feature: UPCI NZ Site Overhaul — Regions, Galleries, Site Settings, Locator, Navigation

## Summary

Eleven related requirements delivered on top of a Laravel 12 + Filament v4 + Vue 3 SPA that is further along than it looks: the region FK, the organisational-region API filter, the department relation managers, and the announcements pipeline already work. The plan therefore front-loads a **P0 storage bug that silently breaks every non-CMS image upload**, then builds one reusable polymorphic gallery, a real `Region` content model, and a `SiteSetting` singleton, before layering on the region pages, church-locator rework, and navigation changes. Visual language throughout is **Direction B (GOV.UK transfer)**, approved 2026-08-17 and recorded in `.claude/design/upci-redesign/direction-approved.md`.

## User Story

As a **UPCI NZ national administrator**
I want **the logo, department and region branding, galleries, regional content and navigation all editable from the admin panel, on a site that looks consistent and current**
So that **the public site can be kept accurate without a developer, and a visitor can find their nearest church in seconds**.

## Problem Statement

Concrete, reproducible symptoms today:

1. `storage/app/private/department-images/01KPZ4VS5HD6SKYDYM0JD4PVE3.png` (2.1 MB) and `storage/app/private/gallery/01KPZ4KV9PVTEMHS7AHSKQE8YG.png` (2.0 MB) are **real uploads sitting in private storage, unservable**. The Men's Department hero and the only gallery image are both broken in production right now.
2. The main logo is a Vite build-time import (`resources/js/components/Navbar.vue:143`, `Footer.vue:89`) — changing it requires a developer and a redeploy.
3. `resources/css` loads the **Figtree** webfont in `app.blade.php`, but `tailwind.config.js` has an empty `theme.extend`, so nothing ever references it. The font is downloaded and discarded on every page load.
4. `regions` is a bare lookup table (`id, name, slug, sort_order`) — no logo, no content. There are no region pages, and no "Regions" nav item.
5. `events` has no region relationship, so a national calendar cannot be separated from regional events.
6. `gallery_items.department` is a free-text varchar defaulting to `'general'` — not a relationship, and unable to serve regions.
7. `ChurchLocator.vue` filters on the **legacy free-text** `churches.region` column ("Auckland", "Wellington") via `/api/churches-regions`, while the organisational region FK, its API filter, and `/api/churches-organizational-regions` all already exist and go unused.
8. The Leaflet map has no `maxBounds` — a visitor can pan to Norway.
9. Footer social icons are three hard-coded inline SVGs, all `href="#"`, of which **two are Twitter-bird variants and one is Pinterest**. No Facebook, Instagram or YouTube.
10. Fabricated statistics ("200+", "6M+ Members", "40K+ Churches") appear in **two** files. `Home.vue:109-117` is dead code — `/` is served by `CmsPage.vue`. But `views/about/GeneralSuperintendent.vue:101,107,113` is **live**, on a page D5 explicitly keeps. ⚠️ *An earlier draft of this plan targeted only `Home.vue` and would have left the live copy in place while reporting the requirement done.*

## Solution Statement

Fix the storage disk first (it blocks requirements 1 and 2). Then introduce three shared structures — `SiteSetting` singleton, an enriched `Region` model, and a **polymorphic** `gallery_items` — so that departments, regions and the general gallery share one gallery implementation rather than three. Region pages, the locator rework and navigation follow as consumers of that foundation. Every change mirrors a pattern that already exists in the repo.

## Metadata

| Field | Value |
|---|---|
| Type | ENHANCEMENT + REFACTOR |
| Complexity | HIGH (11 requirements, 5 phases, schema + admin + API + SPA + visual system) |
| Systems Affected | Filament admin, Eloquent/migrations, public JSON API, Vue SPA, Tailwind design system |
| Dependencies | filament/filament v4.0.0, laravel/framework 12.23.1, PHP 8.4.23, vue 3.5.22, leaflet 1.9.4, tailwindcss 3.4.0, pestphp/pest 3.8 |
| Estimated Tasks | 72 (T0–T71) |

---

## Decisions on record (do not re-litigate)

| # | Decision | Source |
|---|---|---|
| D1 | Visual direction = **B, GOV.UK transfer**. Task-first hero — **but see D11: the task is *find your congregation*, expressed as the list itself, not a search box**. Originally specified as a search hero, no hero photography, flat lists over cards, department colour-bar tags in one harmonised family. | User, 2026-08-17, `direction-approved.md` |
| D2 | Primary colour = fern green `#4D7B37` ≈ `oklch(0.47 0.09 143)`, **sampled from the UPCI NZ logo**. Not Tailwind `blue-600`. Not UPCA's `#2B5672`. | `brand-spec.md` |
| D3 | upca.org.au is the **basis, not a template** — information architecture and quality bar only. | User, verbatim |
| D4 | Regions **renamed to Northern / Central / Southern, including slugs** (`northern`, `central`, `southern`). Nothing public links to the old slugs yet. | User |
| D5 | General Superintendent: **remove the nav link, keep the page working** at `/about/general-superintendent`. The pending `make-general-superintendent-page-cms-editable.plan.md` stays parked, unimplemented. | User |
| D6 | Church Triumphant Wellington in **South Region is correct** — deliberate lower-North + South Island grouping. Do not "fix" the data. | User |
| D7 | Rev. Peter Lloyd gets an **honest placeholder**; all leadership images must be CMS-editable. | User |
| D8 | SBQ and JBQ become **rows in the `departments` table** (UPCA precedent: they serve `/junior-bible-quizzing` top-level). Their *menu items* sit under a new top-level "Youth & Children's" parent. | Analysis, UPCA verified |
| D9 | **Northern / Central / Southern are NAMES ONLY — not geography.** They are administrative groupings and are *not* tied to the North and South Islands. Corroborated by the data: Hamilton appears in both Northern and Central; Wellington (North Island) is in Southern. | User |
| D10 | **Missing content gets an honest, visibly-labelled placeholder** — never a substitute, never a blocker. Ship the structure with the gap marked. | User |
| D11 | **The concept motif is "ten congregations, named."** The hero shows all ten churches grouped by region instead of a search box. Smallness is the identity — it is true, specific to UPCI NZ, unswappable (another membership body has *branches*, not ten nameable congregations), and implies no geography, so it survives D9. Free-text search is demoted to a filter *over the visible list*, never a gate in front of it. | Assumed — overrulable |
| D12 | **Adopt GOV.UK's two-row header** — masthead, then a full-width service-navigation bar carrying the nine top-level items, with the 5px green rule moved to close that bar. A transfer, not an invention; B2 already proved it. | Assumed — overrulable |
| D13 | **Admit one third hue, reserved exclusively for errors and validation.** Failure must not be signalled by weight alone. Scoped to `.error-*` and form validation; it never appears as decoration, and the two-hue rule stands everywhere else. | Assumed — overrulable |
| D14 | **Untrack the `upci` SQLite database before any migration runs.** It holds live user data and 12+ migrations will rewrite a versioned binary, producing unreviewable diffs and likely conflicts. | Assumed — overrulable |

---

## 1. Current Architecture

**Framework / CMS.** Laravel 12.23.1 on PHP 8.4.23. Admin is **Filament v4.0.0** (stable — note `composer.json` says `^4.0@beta`, `composer.lock` says `v4.0.0`). Database is **SQLite**, a tracked file `upci` at the repo root.

**CMS model.** `pages` table with a JSON `content` column holding an array of blocks. Block types are defined in `app/Filament/Resources/Pages/Schemas/PageForm.php` via `Builder::make('content')`: `hero`, `text`, `image`, `two_column`, `cta`, `cards` (a nested `Builder` of `card` items), `embed`. Rendered by `resources/js/views/CmsPage.vue`, which dispatches on `block.type`. Ten pages exist, all published.

**Routing.** There is **no `routes/api.php`**. Every API route lives in `routes/web.php` inside `Route::prefix('api')`, declared before the SPA catch-all `Route::get('/{any}')`. CSRF is exempted for `api/*` in `bootstrap/app.php`. `/admin` is not excluded by pattern — it works purely because Filament registers more specific concrete routes.

**Vue SPA.** `resources/js/app.js` → `App.vue` (Navbar + `<router-view>` + Footer). `routes.js` has explicit routes plus a catch-all `/:slug(.*)` → `CmsPage.vue`, so any unmatched path is treated as a CMS slug. Note `views/about/Leadership.vue`, `AboutUPCI.vue`, `OurBeliefs.vue`, `OnenessPentecostalism.vue` and `views/Home.vue` are **dead code** — their routes point at `CmsPage.vue` instead.

**Navigation.** `menu_items` table, self-referential `parent_id`. `MenuItemController::header()` eager-loads `children` and formats **exactly two levels** — `formatMenuItem()` never recurses past children, and `Navbar.vue` has no third-level template. Nav is CMS-driven. **`Footer.vue` is 100% hard-coded** and does not consume `/api/menu/footer`, which exists but returns zero rows.

**Events/calendar.** `events` table: `name, slug, description, start_date, end_date, location, url, is_published, sort_order, department_id`. 49 rows. **No region, no scope/type column.** Public `/api/events` filters by `from`/`to`/`department` only. `Events.vue` groups by month; `Calendar.vue` renders a month grid over a 2-month window. Shared status helper at `resources/js/utils/eventStatus.js`.

**Churches/map.** `churches` carries **both** a legacy free-text `region` (geographic — "Auckland") and a `region_id` FK to `regions` (organisational — exposed as the `organizationalRegion` relation to avoid the name clash). All 10 churches have `region_id` populated (4/2/4). `ChurchController::formatChurchForApi()` **already returns `organizational_region`**, and `index()` **already filters on it** via `whereHas`. `ChurchLocator.vue` nonetheless uses the legacy text endpoint. Leaflet map is centred on NZ at zoom 6 with **no `maxBounds`**, and `updateMarkers()` calls `fitBounds()` on every filter change.

**Access control.** `App\Enums\AccessLevel` (local/regional/national) cast on `users.access_level`. `App\Filament\Concerns\ScopesToAccessLevel` provides `getEloquentQuery()` scoping via abstract `localScope()`/`regionalScope()` closures. `App\Policies\NationalOnlyPolicy` is an abstract base extended by `AGSUpdatePolicy`, `ContactMessagePolicy`, `GalleryItemPolicy`, `MenuItemPolicy`, `PagePolicy`. `NationalOrRegionalOnly` middleware 404s local users off `/admin/events`.

---

## 2. Existing vs Missing

| # | Requirement | Status | Evidence |
|---|---|---|---|
| 1a | Main logo CMS-editable | **MISSING** | `Navbar.vue:143` / `Footer.vue:89` `import upciLogo from '../../images/upci-nz-logo.png'` — build-time Vite import. No settings table (`sqlite3 upci .tables`). |
| 1b | Per-department logo | **PARTIAL / NEEDS REFACTOR** | `departments.hero_image` exists but is a *hero*, not a logo; only 1 of 6 populated, and that one is **broken** (private disk). No `logo` column. |
| 2a | Department galleries | **NEEDS REFACTOR** | `gallery_items.department` is a free-text varchar, not a relationship. 1 row. |
| 2b | Gallery in main nav | **PARTIAL** | Menu item id 14 exists but is nested under Departments with url `/departments#gallery`. No `/gallery` route in `routes.js`. |
| 2c | Regional galleries | **MISSING** | No link from `gallery_items` to `regions` of any kind. |
| 3a | Leadership detail modal | **MISSING** | Live `/about/leadership` is a CMS `cards` block; `CmsPage.vue:117-131` renders plain `<div>`s — not clickable. A working modal *does* exist inline in `ChurchLocator.vue` to extract. |
| 3b | Portrait leadership images | **NEEDS REFACTOR** | `CmsPage.vue` renders `w-24 h-24 rounded-full object-cover` — circular, not portrait. |
| 4 | ABC page | **PARTIAL** | 3 pages exist and are published. `apostolic-bible-college/enrollment` has **two duplicate URL pairs** (`forms.gle/iqVTh9YQH3nhZdw48` twice, `forms.gle/DoBQxwMmbQfgduWq9` twice) and is the only one unedited since seeding. |
| 5a | NZ-only map | **PARTIAL** | Centred on NZ, but no `maxBounds`/`minZoom` — world panning possible. |
| 5b | Church "More Info" | **PARTIAL** | Whole card is clickable and opens a modal; no explicit affordance. Map popup has a "View Details" button driven by a global `window.selectChurchFromMap()`. |
| 5c | Filter/group by region (CMS data) | **NEEDS REFACTOR** | Backend fully ready (`organizational_region` filter + payload field + `/api/churches-organizational-regions`). Frontend still on the legacy text endpoint. **Frontend-only change.** |
| 6 | Announcements | **ALREADY IMPLEMENTED** | Full pipeline works: model → `AnnouncementsRelationManager` → `/api/departments/{slug}` → `Department.vue:41-57`. 2 live rows. Gap is only that there's no top-level resource or standalone feed. |
| 7 | Navigation cleanup | **PARTIAL** | GS = menu id 6; Gallery = id 14 (nested); no Regions item; no "Youth & Children's"; SBQ/JBQ absent entirely. |
| 8 | Remove Twitter/X | **MISSING (and worse than stated)** | `Footer.vue:15-31` — three hard-coded SVGs, all `href="#"`. Two are Twitter-bird variants, one is Pinterest. Not CMS-driven. |
| 9 | National vs regional calendar | **MISSING** | `events` has no region or scope column. Region appears only as free text inside event *names* ("PM – Central Region, Waikato"). |
| 10 | SBQ / JBQ under Youth & Children's | **MISSING** | Neither exists anywhere. Only trace is "CM – JBQ Mini-tourney" event names in `NationalCalendar2026Seeder`. |
| 11 | Reusable Regions section | **PARTIAL** | `regions` table + FK + `organizationalRegion` relation exist and are populated. No logo/message columns, no pages, no routes, no Filament resource. |

---

## 3. Data Model Changes

### 3.1 NEW `site_settings` — singleton

| Column | Type | Notes |
|---|---|---|
| `id` | pk | Always row 1 |
| `header_logo_path` | string, nullable | Navbar mark. Currently the **stacked** lockup (variant 01) |
| `footer_logo_path` | string, nullable | Footer mark. Currently the **horizontal** lockup (variant 03) |

🔴 **Two logos, not one.** The header and footer take different lockups from the same pack and must be editable independently — the navbar is a horizontal strip that suits a compact stacked mark, while the footer has room for the wide one. A single `logo_path` cannot express that. Both fall back to a bundled default when empty.
| `social_links` | json, nullable | `[{platform, url}]` — replaces hard-coded SVGs |
| `contact_email` | string, nullable | Footer currently hard-codes `info@upci.org.nz` |
| `footer_blurb` | text, nullable | Footer paragraph, currently hard-coded |

No `spatie/laravel-settings`. **`Filament\Pages\SettingsPage` is NOT in Filament v4 core** — it ships in `filament/spatie-laravel-settings-plugin`. Use a plain `Filament\Pages\Page` (see `research-notes.md` §1).

### 3.2 `regions` — enrich

| Column | Change |
|---|---|
| `name` | **Data migration**: "North Region" → "Northern Region", "South Region" → "Southern Region" (D4) |
| `slug` | **Data migration**: `north` → `northern`, `south` → `southern` (D4) |
| `logo_path` | ADD string nullable |
| `intro` | ADD text nullable — markdown, the region "message" |
| `presbyter_name` | ADD string nullable |
| `is_published` | ADD boolean default true |

Relationships to add on `Region`: `events()` HasMany, `galleryItems()` MorphMany. `churches()` already exists.

### 3.3 `departments` — add logo

| Column | Change |
|---|---|
| `logo_path` | ADD string nullable. Falls back to the site logo when null. Distinct from the existing `hero_image`. |

### 3.4 `events` — add region + scope

| Column | Change |
|---|---|
| `region_id` | ADD nullable FK → `regions`, `nullOnDelete`, indexed |
| `scope` | ADD string(16) default `'national'` — `national` \| `regional` \| `department` |

Backfill: all 49 existing rows are the 2026 national calendar → `scope = 'national'`, `region_id = null`. Mirrors the existing backfill migration `2026_04_24_100001_assign_access_level_to_existing_users.php`.

### 3.5 `gallery_items` — polymorphic (the key reuse decision)

| Column | Change |
|---|---|
| `galleryable_id` / `galleryable_type` | ADD via **`$table->nullableMorphs('galleryable')`** — null = general gallery. 🔴 **NOT `morphs()`**: that is NOT NULL, and SQLite rejects adding a NOT NULL column with no default to a populated table (`gallery_items` has a row). Probed and confirmed to fail. |
| `is_published` | **ADD** boolean default true. 🔴 The table has **no visibility column at all** and `GalleryController::index()` applies no filter — every upload is public the instant it saves. §9's new `gallery` block widens that exposure to any CMS page. |
| `department` | KEEP for now, drop in a later migration once backfilled |

Only **one row** exists, so this migration is near-free. Register `Relation::enforceMorphMap(['department' => Department::class, 'region' => Region::class])` in `AppServiceProvider::boot()` — `enforceMorphMap`, not `morphMap`, so unmapped classes hard-fail instead of writing FQCNs.

### 3.6 `departments` — two new rows

SBQ and JBQ as departments (D8), giving them `/departments/sbq` and `/departments/jbq` for free.

### 3.7 NOT changing

- `churches.region` (legacy free-text) stays — it drives geographic search and is separate from organisational region.
- `churches.region_id` needs no backfill; all 10 rows are populated.
- Wellington's South Region assignment is correct (D6).

---

## 4. Frontend Changes

| File | Action | Why |
|---|---|---|
| `tailwind.config.js` | UPDATE | `theme.extend` with brand tokens + font family. Currently empty — the reason Figtree is loaded and unused. |
| `resources/views/app.blade.php` | UPDATE | Swap Figtree for Poppins (Direction B), or wire Figtree properly. Decide once. |
| `resources/js/components/Navbar.vue` | UPDATE | Logo from `/api/site-settings` instead of the Vite import. Direction B nav treatment. |
| `resources/js/components/Footer.vue` | UPDATE | Consume `/api/menu/footer` + `/api/site-settings`. Remove the two Twitter SVGs and Pinterest. Kill the dead `href="#"` links. |
| `resources/js/views/Regions.vue` | **CREATE** | Region index. |
| `resources/js/views/Region.vue` | **CREATE** | Region detail — message, churches, events, gallery. |
| `resources/js/views/Gallery.vue` | **CREATE** | Standalone `/gallery`. |
| `resources/js/components/Modal.vue` | **CREATE** | Shared accessible modal — native `<dialog>` + `<Teleport>`, plus a focus trap (`<dialog>` doesn't provide one). |
| `resources/js/components/GalleryGrid.vue` | **CREATE** | One grid used by department, region and general gallery. |
| `resources/js/views/CmsPage.vue` | UPDATE | `cards` block: clickable when a `bio` is present → `Modal.vue`. Portrait `aspect-[3/4] object-cover object-top`. |
| `resources/js/views/ChurchLocator.vue` | UPDATE | Switch to `organizational_region`; add `maxBounds`; explicit "More info"; group by region. |
| `resources/js/views/Department.vue` | UPDATE | Department logo + gallery section. |
| `resources/js/views/Events.vue` / `Calendar.vue` | UPDATE | National vs regional split. |
| `resources/js/router/routes.js` | UPDATE | Add `/regions`, `/regions/:slug`, `/gallery`. **Keep** `/about/general-superintendent` (D5). |
| `resources/js/utils/theme.js` | **CREATE** | Department/region class lookup **map of complete literal strings** — mirrors `eventStatus.js`, which already documents why (Tailwind JIT can't see concatenated names). Do **not** use regex `safelist`. |

---

## 5. Navigation Changes

Target hierarchy (all via `menu_items` data migrations, mirroring the five existing menu migrations — always `DB::table('menu_items')`, scoped by `where('location','header')->whereNull('parent_id')`, with a symmetric `down()`):

```
About the UPCI NZ
  ├─ About the UPCI            /about/upci
  ├─ Oneness Pentecostalism    /about/oneness-pentecostalism
  ├─ Our Beliefs               /about/beliefs
  ├─ Leadership                /about/leadership
  └─ AGS Updates               /ags-updates
      ✗ General Superintendent  — REMOVED from nav (id 6). Page still works. (D5)

Departments
  ├─ Mens Department           /departments/mens
  ├─ Ladies Department         /departments/ladies
  ├─ Missions Department       /departments/missions
  ├─ Prayer Ministry           /departments/prayer
  └─ Social                    (external)
      ✗ Gallery  — MOVED OUT to top level
      ✗ Youth Ministry, Children's Ministry — MOVED to Youth & Children's

Youth & Children's            ← NEW top-level parent
  ├─ Youth Ministry            /departments/youth
  ├─ Children's Ministry       /departments/childrens
  ├─ SBQ                       /departments/sbq      ← NEW
  └─ JBQ                       /departments/jbq      ← NEW

Regions                       ← NEW top-level
  ├─ Northern Region           /regions/northern
  ├─ Central Region            /regions/central
  └─ Southern Region           /regions/southern

Gallery                        /gallery              ← NEW top-level
Find a Church                  /find-church
Apostolic Bible College        /apostolic-bible-college
Calendar of Events             /events
Connect with Us                /connect-with-us
```

Two notes. **The 2-level limit is respected** — SBQ/JBQ are children of a *top-level* "Youth & Children's", never grandchildren. And top-level `sort_order` must be renumbered: **"Find a Church" (id 8) and "Apostolic Bible College" (id 10) both sit at `sort_order = 3`** — the only collision, verified by query. Order is currently resolved by id, which is accidental.

---

## 6. Implementation Phases — DELETED (superseded)

> 🔴 **This section has been removed, not archived.** It held the *pre-review* version of five tasks, and an implementer following it literally would have:
> - built `SiteSettingPolicy extends NationalOnlyPolicy` — a control that provably does nothing (§12.4);
> - run the region rename without the `name`→`slug` coupling fix or the four test files, silently nulling `region_id` on church writes (§12.6, §12.9);
> - omitted `is_published` from `gallery_items`, leaving every upload public on save (§3.5);
> - used a migration for the Phase 0 file move, which runs under `RefreshDatabase` on every test run (§12.8);
> - written a `down()` that aborts on SQLite (§12.3).
>
> **The authoritative task list is the Recommended Execution Order (T0–T71) below.** Nothing else in this document defines work.

## 7. Risks and Open Questions

| Risk | Likelihood | Impact | Mitigation |
|---|---|---|---|
| **Shared `card` block gains `bio`** — blast radius measured: **7 pages, 8 `cards` blocks, 41 `card` items** (`welcome`, `home`, `about/upci`, `about/beliefs`, `about/leadership` ×2, `apostolic-bible-college`, `.../enrollment`) | HIGH | MED | `bio` optional; cards stay non-clickable without it. Visually diff all 7 pages before/after. |
| **The `upci` SQLite DB is tracked in git** and holds live user data | HIGH | HIGH | Migrations rewrite a versioned binary — expect noisy diffs and possible conflicts. Consider untracking; back up before every migration. |
| Region slug rename breaks a link | LOW | LOW | Nothing public references them yet. Verified: no `menu_items` row and no `pages.content` reference. |
| Tailwind JIT drops dynamically-built classes | MED | MED | Literal-string lookup map per `eventStatus.js`. No regex safelist (v3-only, unsafe under v4 Oxide). |
| N+1 on new region endpoints | MED | MED | `ChurchController::formatLeadershipForApi()` already runs ~5 queries **per church**. Eager-load on all new endpoints; don't copy that pattern. |
| Filament v4 layout regressions | MED | LOW | `Grid`/`Section`/`Fieldset` no longer span all columns by default in v4 — add `->columnSpanFull()` explicitly. |
| `->disk('public')` fix reveals more broken images | MED | LOW | Audit all `FileUpload` fields in Phase 0, not just the two known. |

**Assumptions, flagged:** the `scope` enum is `national|regional|department` — reasonable but unconfirmed. Region logos and intro copy do not exist yet and will need supplying; placeholders until then.

### 🔴 RESOLVED — and the answer was "no": Direction B does NOT fit the existing block library

This was previously listed as the plan's riskiest assumption. It has now been checked and **disproved**. See §9.

---

## 8. Verification Checklist

**Automated gates.** The full suite is **not** a valid gate — 16 tests fail before any change, all pre-existing Livewire starter-kit scaffolding (`Livewire\Volt\Volt` not found) in `tests/Feature/{Auth,Settings}/` and `DashboardTest`. Baseline is **38 passed / 16 failed**; the four project-owned files pass **27**.

```bash
vendor/bin/pint --test
npm run build
./vendor/bin/pest tests/Feature/AccessLevelScopingTest.php \
                  tests/Feature/ChurchPolicyLocalEditTest.php \
                  tests/Feature/EventAccessPolicyTest.php \
                  tests/Feature/PanelAccessGateTest.php
```

New tests to add (Pest, `test('...', function(){})`, `Region::firstOrCreate` + `Model::create` — **only `UserFactory` exists**, there are no factories for Region/Church/Event/Department/GalleryItem):
- `SiteSettingsAccessTest` — national-only, singleton behaviour
- `RegionApiTest` — index/show shape, unpublished excluded
- `GalleryPolymorphicTest` — morph map enforced, department vs region vs general
- `EventScopeTest` — national vs regional filtering

**Manual checklist**
- [ ] Logo swapped in admin appears in header *and* footer without a redeploy
- [ ] Department image upload lands in `storage/app/public/` and renders publicly
- [ ] Same gallery component serves department, region and `/gallery`
- [ ] Map cannot pan outside NZ; single-result filter doesn't over-zoom
- [ ] Locator filters by Northern/Central/Southern from the FK, not free text
- [ ] Leadership modal: opens on click, Escape closes, focus returns to trigger, `aria-labelledby` set
- [ ] Leadership images portrait; Rev. Peter Lloyd shows an honest placeholder
- [ ] `/events` shows national calendar; each region page shows only its own events
- [ ] Desktop **and** mobile nav: no General Superintendent; Gallery, Regions, Youth & Children's (with SBQ + JBQ) present
- [ ] `/about/general-superintendent` still loads (D5)
- [ ] No Twitter/X or Pinterest icon anywhere; remaining social links resolve (no `href="#"`)
- [ ] No fabricated statistics remain
- [ ] 390px: no horizontal overflow on any new or changed view

---

## Recommended Execution Order

**This is the single authoritative task list.** It supersedes **§6 (now deleted)** and the per-section numbering still quoted in §9 and §11.5 (`1.17`, `2.17`, `0.5`, `5.5`… — all superseded; read them as prose, not as tasks). Tasks are **T0–T71**.

⚠️ **T-numbers are identifiers, not sequence.** Blocks F and G carry the highest numbers but must run *early* — see the ordering summary.

**Standing gate.** Run after *every* task that touches a migration, a model, or a policy — not once at the end:
```bash
vendor/bin/pint --test --dirty && npm run build && ./vendor/bin/pest \
  tests/Feature/AccessLevelScopingTest.php tests/Feature/ChurchPolicyLocalEditTest.php \
  tests/Feature/EventAccessPolicyTest.php tests/Feature/PanelAccessGateTest.php \
  tests/Feature/SecurityRegressionTest.php
```
Baseline is **38 passing** in those five files. The full suite is *not* a gate — 16 tests fail before any change.

🔴 **`--dirty` is load-bearing, not a nicety.** Plain `vendor/bin/pint --test` exits **1** on a clean tree with **83 pre-existing failures** — including `AccessLevelScopingTest.php` and `PanelAccessGateTest.php`, two of the four gate files. Because the chain is `&&`, the build and the tests **never execute**: the gate reported failure regardless of what you did. `--dirty` lints only changed files and exits 0 on a clean tree (both verified). Do **not** "fix" this by running bare `vendor/bin/pint` — that produces an 83-file reformat diff which then tangles into your first real commit.

### T0 — Before any migration runs (D14)

| # | Task | Notes / hazards |
|---|---|---|
| T0 | `git rm --cached upci`, add it to `.gitignore`, commit; take a dated backup copy first | 🔴 The DB is tracked and holds live user data. T12/T13/T18/T21 each rewrite a versioned binary → unreviewable diffs, likely conflicts, and a real chance of clobbering live data on a merge. Do this **first** |

### Block A — Start here. No dependencies, fixes live production defects

| # | Task | Notes / hazards |
|---|---|---|
| T1 | Add `->disk('public')->visibility('public')` to `GalleryItemForm.php:21` and `DepartmentForm.php:45` | Mirror `PageForm.php`. Unblocks reqs 1 + 2 |
| T2 | **Artisan command** (⚠️ *not* a migration) to move the 2 DB-referenced files out of `storage/app/private/` | §12.8 — migrations run under `RefreshDatabase` on every test run |
| T3 | Audit every remaining `FileUpload` in `app/Filament/` | Confirmed only 2 are wrong; verify nothing new |
| T4 | `ContactMessageResource` + register the existing policy | 🔴 Live contact submissions are currently unreadable by anyone |
| T5 | Data-cleanup migration: null the 4 fake pastor names; blank the stale Christchurch address | §15 — false data, not missing data |
| T6 | Remove fabricated statistics from **`GeneralSuperintendent.vue:101,107,113`** (the live file) | Keep the page itself (D5) |
| T7 | Unpublish the `welcome` and `about-cms` demo pages | Publicly reachable today |
| T8 | Remove production `console.log`s; self-host the Leaflet marker images | `Navbar.vue:160`, `ChurchLocator.vue:490-491,651-655,370-372` |

### Block B — Data architecture

| # | Task | Notes / hazards |
|---|---|---|
| T9 | `site_settings` migration + model, **and seed `header_logo_path` + `footer_logo_path` via an artisan command — NOT in the migration** | 🔴 §12.8 forbids file copies in migrations (they run under `RefreshDatabase` on every test); T9 originally told you to copy a **293 KB** asset in one. Also `storage/app/public/site/` does not exist — create it. Without the seed, T11 *removes* the logo |
| T10 | `ManageSiteSettings` page with **`canAccess()` + `shouldRegisterNavigation()` overrides** | 🔴 §12.4 — a policy does nothing here; a custom Page is open to all. ⚠️ **~1 day, not one line.** Needs: `protected static string $view` → a Blade file in **`resources/views/filament/pages/` which does not exist and no task creates**; `HasForms`+`InteractsWithForms`; a `$data` array; `mount()` hydrating row 1; a `save()`; singleton semantics (`firstOrCreate(['id'=>1])`); and the `logo_path` `FileUpload` **must** carry `->disk('public')` or T1's bug returns on day one |
| T11 | `GET /api/site-settings`; wire `Navbar.vue` to `header_logo_path` and `Footer.vue` to `footer_logo_path`, each keeping its bundled import as the `v-else` fallback | |
| T12 | **Region rename migration** — names *and* slugs, **plus** all **SEVEN** `ChurchController` coupling sites, **plus** all four test files' `firstOrCreate(['slug'=>'north'])` | 🔴 Sites: `:28` filter · `:103` + `:149` `exists:regions,name` validation · `:125` + `:172` `Region::where('name')` · `:223` `pluck('name')` · `:285` payload. **Correction to §12.6:** the failure is *not* a silent null — `exists:regions,name` fires first and returns a **loud 422**. Fix all seven or the API's own list endpoint (`:223`) returns values its own filter (`:28`) rejects |
| T13 ✅ | Region enrichment migration (`logo_path`, `intro`, `presbyter_name`, `is_published`) + update `Region::$fillable` + casts | §12.11 |
| T14 ✅ | Filter `/api/churches-organizational-regions` by `is_published` | §12.6 — currently leaks |
| T15 ✅ | `RegionResource` + `RegionPolicy` — **decide regional-presbyter ownership semantics first** | §5.1 — this determines whether `GalleryItemPolicy` can stay national-only |
| T16 ✅ | `departments.logo_path` migration + form field + site-logo fallback | Req 1b |
| T17 ✅ | `DepartmentController` — return `logo_path` on both methods | §12.5 |
| T18 ✅ | `events.region_id` + `scope` migration + backfill 49 rows | ⚠️ `down()` must `dropIndex(['region_id'])` **before** `dropConstrainedForeignId()` (§12.3) |
| T19 ✅ | Fix the same latent bug in `2026_04_19_000003_add_department_id_to_events_table.php` | Its `down()` has never been exercised |
| T20 ✅ | `EventController` — add `?scope=` / `?region=` filters and both fields to the payload | §12.5 |
| T21 ✅ | `gallery_items`: **`nullableMorphs('galleryable')`** + **`is_published`** + `enforceMorphMap` + backfill | 🔴 `morphs()` aborts on SQLite (§12.2); no visibility column exists (§3.5) |
| T22 ✅ | `GalleryController` — owner filtering, `is_published` filter, drop the free-text `department` scope | §12.5 + §4.2 |
| T23 ✅ | Update `GalleryItemResource` form/table for the new owner fields | §5.2 — otherwise admin-created items are invisible |
| T24 ✅ | `GalleryItemsRelationManager` on Department **and** Region | Mirrors `AnnouncementsRelationManager` |
| T25 ✅ | `/api/regions` index + show — **define the response shape explicitly**; eager-load | §12.5. Do not copy `formatLeadershipForApi`'s N+1 |

### Block C — CMS block library

| # | Task | Notes / hazards |
|---|---|---|
| T26 ✅ | **Declare `icon_svg` in the `card` block schema** | 🔴 §11.3 — **must precede T31** (the task that adds `bio` to `card`). T30 is the data-bound blocks and never touches `card`. Run T31 first and the 5 live `icon_svg` values are permanently stripped |
| T27 ✅ | Replace the four presentation heuristics with explicit author options | §11.2 — `getTextBlockClasses(index)`, `hasStats()`, `getCardsGridClasses()`, `isRegistrationBlock()` |
| T28 ✅ | Add a `ratio` option to `two_column`; remove the forced grey box | §11.4 — cannot express 2/3 + 1/3 today |
| T29 ✅ | Per-block async loader in `CmsPage.vue` — loading / error / empty states | §9 + §10 |
| T30 ✅ | Six data-bound blocks, each with an **authored empty-state message field** | §9 + §13.5. Depends on T20, T22, T25 |
| T31 ✅ | `card` block `bio` + clickable cards — **decide `bio` vs `link_url` precedence** | §12.11 — nested interactive elements otherwise |
| T32 ✅ | Re-author the `home` page to Direction B's block sequence | |

### Block D — Frontend

| # | Task | Notes / hazards |
|---|---|---|
| T33 ✅ | Tailwind `theme.extend` with brand tokens; settle Figtree vs Poppins | Currently the webfont is loaded and never used |
| T34 ✅ | `utils/theme.js` — literal class-string lookup map | Mirror `eventStatus.js`; no regex safelist |
| T35 ✅ | `Modal.vue` (native `<dialog>` + `<Teleport>` + focus trap); refactor `ChurchLocator.vue` onto it | |
| T36 ✅ | Locator: switch to `organizational_region`; Leaflet `maxBounds`; cap `fitBounds`; group by region; "More info"; kill the `window.selectChurchFromMap` global; fix the hard-coded hero stats and dead CTA | §12 + G9 + G10 |
| T37 | Events national/regional split | ⚠️ Must precede T38 or region pages ship with permanently empty feeds |
| T38 ✅ | `Regions.vue` + `Region.vue` + routes | |
| T39 ✅ | Rebuild `GetInvolved.vue` from `/api/departments` | G6 — largest hard-coded surface |
| T40 ✅ | `Gallery.vue` + `/gallery` + `GalleryGrid.vue` | |
| T41 ✅ | Portrait leadership + labelled Peter Lloyd placeholder; **decide the role/name field mapping** | §12.11 — `title` holds the role, `description` the name |
| T42 ✅ | Apply `meta_description` + `document.title` on route change | G14 |
| T43 | ABC enrolment: placeholder the two ambiguous links | ⚠️ **BLOCKED on client** for the real URLs (§15) |

### Block E — Navigation and cleanup

| # | Task | Notes / hazards |
|---|---|---|
| T44 ✅ | Seed `location='footer'` menu rows, **then** the menu migration (all nav changes at once, renumber the `sort_order` collision), **then** the footer rebuild — social links, remove both Twitter variants + Pinterest, delete dead components (keep `GeneralSuperintendent.vue`) | ⚠️ Order within this task matters: seeding must precede wiring or the footer renders empty (G1) |

### Block F — Design system (was gated; resolved by D11–D13)

Do these **before** T32 (re-authoring the homepage) and T39–T41, which consume them.

| # | Task | Notes / hazards |
|---|---|---|
| T45 | Rework B's hero to the "ten congregations, named" motif (D11) — list above the fold under three region headings; demote search to a filter over the visible list | §14.1a. Reuses `.church-list` / `.regions-grid`; no new components |
| T46 ✅ | Two-row header: masthead + full-width service-nav bar carrying all nine items; move the 5px green rule to close it (D12) | §13.2. Port from `B2-coverage-screens.html`, which already implements it |
| T47 | Add the error hue token + `.error-summary` / field-validation treatment (D13) | Scoped to errors only; two-hue rule stands elsewhere |
| T48 ✅ | Port `.breadcrumb`, `.page-header`, `.contents` from B2 into the shared layout | 🔴 §13.1 — **prerequisites**: B cannot render any non-homepage page without them |
| T49 | Apply the deferred craft fixes: type scale (h2 → 40px, body → 17px), department greens spread by hue, symmetric region gutters, `filter:grayscale(1)` on the leadership row, ABC grid `5fr 7fr` | Critique F5–F9, F11. ~1hr; moves Craft 4 → ~7 |

### Block G — Spikes for the two un-de-risked archetypes

Time-boxed. Do them **early** — both can invalidate downstream work if they fail.

| # | Task | Notes / hazards |
|---|---|---|
| T50 | **Spike: `/calendar` month grid.** B has no table or cellular vocabulary; its entire language is one-dimensional rows. Build one month at 1440 and 390 before committing | §13.6. If B cannot carry a 7×5 grid with multi-day spans, the fallback is to keep `/calendar` as a grouped list and let `/events` be the only calendar view |
| T51 | **Spike: the `cards` block under B.** 41 items across 7 pages; B's stated position is anti-card | §13.6. Whatever `cards` becomes changes 7 pages at once — prove it on `about/leadership` and `apostolic-bible-college` before T31 |

### Block H — Gaps found by the traceability pass (all verified)

| # | Task | Notes / hazards |
|---|---|---|
| T52 ✅ | 🔴 **Fix B-1 — reconcile the region filter contract, inside T12/T14.** `organizationalRegions()` (`ChurchController:223`) plucks **`name`**; T12 switches the filter at `:28` to **`slug`**. After both land, the dropdown offers "Northern Region", the filter expects "northern", `whereHas` matches nothing, and **every region filter silently returns zero churches** | Return `[{slug,name}]` from the endpoint, or accept both forms. §12.6 caught the *write* path (`:125`,`:172`) and missed this *read* path |
| T53 ✅ | 🔴 **`Department.vue`: render `logo_path` (with site-logo fallback) + a `<GalleryGrid>` section** | Reqs 1b + 2a had **no rendering surface at all** — verified 0 mentions of `Department.vue` in the T-list. T16/T17 add the column and API field; nothing consumed them. `/departments/:slug` is a Vue view, not a CMS page, so T30's blocks don't reach it |
| T54 | **Decide and task `/calendar`.** T50 is a *spike*, not a deliverable | Verified 0 mentions of `Calendar.vue` in the T-list. Either implement the month grid or retire the route — if retired, the catch-all sends it to `CmsPage.vue` → 404 with no 404 view (T56) |
| T55 | **Assign the 49 existing events to regions, or declare it client data in writing** | T18 backfills everything to `scope='national', region_id=null`. Region appears today only as free text inside event *names* ("PM – Central Region, Waikato", `NationalCalendar2026Seeder.php:39`). Without this, req 9b ships structure with zero data. Compare T5, which *does* task the equivalent cleanup |
| T56 | **Real 404 view** | §10 requires it; nothing delivered it. T7 actively creates the need by unpublishing `welcome` + `about-cms` |
| T57 | **`Navbar.vue` menu fallback** | §10 + appendix: the catch sets `menuItems = []`, so a `/api/menu/header` failure renders navigation **completely empty**, despite a comment claiming otherwise. Req 7 is a navigation requirement |
| T58 | **Resolve the gallery morph target before T21 runs** | The single `gallery_items` row has `department = "Apostolic Bible College"`, which is **not** a `departments` row. `enforceMorphMap` is chosen precisely so unmapped classes hard-fail — the backfill will abort or null |
| T59 | **Req 6 — fix announcement publishing.** `scopePublished()` is `where('is_published', true)` **only** — verified. It never compares `published_at` to `now()`, and the toggle **defaults to true**, so a future date publishes immediately. The date picker looks like scheduling and is not | Either add `->where('published_at','<=',now())` or tell the client plainly that publishing is a toggle, not a schedule |
| T60 | **Req 6 — announcement detail links.** Verified: `department_announcements` has **no `slug` column**, no route, no detail view. Full content is dumped inline; there is no way to link to one announcement | The brief lists "detail links" explicitly. Either add them or get written agreement that inline-only satisfies it |
| T61 | **Req 4 — an actual ABC inspect/fix task.** T43 (blocked) + T49 (a grid tweak) cover ~15% of a requirement asking for layout, responsive behaviour, CMS content, missing sections, broken links and visual inconsistencies | The enrollment page is the only one unedited since seeding — its CMS content is still placeholder, and nothing addresses that |
| T62 | **Sanitise or clean announcement content** (§13.8) | Real rows contain emoji-heavy Facebook copy and raw `<iframe>` inside broken markdown, rendered via `v-html` at `Department.vue:56`. This is req 6's display logic |
| T63 | **Audit the existing public API surface for auth/throttle** | The plan gates only the *new* resources. This is how the unauthenticated church DELETE survived every earlier pass — it never reached a policy |
| T64 | **Split T36.** It carries seven clauses across four sub-requirements, in a plan whose stated standard is "every task is independently completable" | Also reword **T41** to name the shared component (`CmsPage.vue:117-131`) — req 3b's whole point is fixing it in the shared component, and the task text doesn't say so |

### Block I — Rescued from the deleted §6, plus untasked findings

| # | Task | Notes / hazards |
|---|---|---|
| T65 | **Seed the three region intros / logos as editable content** | The **only** item in the deleted §6 with no T-number. Sequence after T13 (columns exist) and before T38 (`Region.vue` consumes them). Ships §15 placeholders where copy is absent |
| T66 | Convert `/about` to a CMS page | G7 — it is **live and routed** (`routes.js:8-11`) and 100% hard-coded, unlike the unrouted `views/about/*.vue` dead files. Identified in §11.1 and never tasked |
| T67 | Derive the calendar year from data | G12 — `Events.vue:7` hard-codes "2026 National Calendar"; stale on 1 Jan 2027 |
| T68 | Footer copyright + the three `href="#"` legal links into site settings | G3/G4 — **and add the columns to §3.1's `site_settings` schema**, which currently has none for them |
| T69 | Extend the department colour family from **6 to 8** for SBQ/JBQ, **or** decide they share the Youth tint | D8 creates 8 departments; §13.7 says the palette holds exactly 6 and "has run out of room". Colour lives in **three** places that must change together (§11.4) — miss one and hero gradients silently fall back to blue |
| T70 | Drop the orphaned `gallery_items.department` column once T21's backfill is verified | §3.5 says "drop in a later migration"; no task ever did. T22 removes its only consumer, leaving it permanently orphaned |
| T71 | Apply §14.3's copy fix and enforce §14.2's "must NOT be built" constraints in review | Both are concrete requirements with no owner. §14.2 needs a checklist line, not just prose |

### Corrections to earlier gradings

- **§2 req 5c "frontend-only" is wrong.** T12 and T14 are both backend, and B-1 proves backend work is mandatory.
- **§2 req 6 "ALREADY IMPLEMENTED" is wrong.** 4 of 6 elements are real; **visibility/publishing** and **detail links** are not, and neither had a task. The plan's own line conceding a "gap" contradicted its own grade.
- **Line 359 says "Tasks are `T1…T44`".** There are now 65 (T0–T64).
- **Anti-requirement watch:** nothing asserts "no hard-coded region names in `resources/js`". Currently clean (grep returns zero outside `tests/`), but §13.7 distinguishes regions by *treatment* rather than colour, which invites a per-region conditional. Added to the §8 manual checklist.

### Ordering summary

```
T0 ──► Block A (T1–T8)  ─┐
                          ├─► Block B (T9–T25) ──► Block C (T26–T32) ──► Block D (T33–T43) ──► Block E (T44)
Block F (T45–T49) ───────┘                              ▲
Block G (T50–T51) ─────────────────────────────────────┘
```
Blocks A and F are independent and can run in parallel. Block G gates T31 and T50's own consumer only.

---

## 9. Data-Bound Blocks — the gap this plan originally missed

### The finding

**Every existing CMS block renders static, hand-authored content. Not one is data-bound.** Verified:

- `resources/js/views/CmsPage.vue:166` contains the *only* network call in the entire renderer: `fetch('/api/pages/' + slug)`. No block triggers a second request.
- Block types defined in `PageForm.php` and rendered in `CmsPage.vue` are in sync: `hero`, `text`, `image`, `two_column`, `cta`, `cards`, `embed` (plus `card` as a nested sub-block). All seven render text and images typed in by the author.
- The live `home` page is literally `hero → text → cards → text → cta`.

Direction B's homepage requires **live data**: a church finder, a region-grouped church directory, a dated events list, and a colour-tagged department list. None of that is expressible as a static block.

### Why this is not a small problem

Building B on the existing library would mean an administrator hand-retyping all 10 churches and every event into `cards` blocks — content that already exists in `churches` and `events` tables. It would drift from the database the first time anything changed, and it defeats the premise of the CMS. The client's stated requirement is that content be editable by a non-developer; duplicating the database by hand is the opposite of that.

### What is actually needed

A new category of **data-bound block**. Each one is three pieces of work, not one:

1. a `Builder\Block` definition in `PageForm.php` exposing *configuration* (how many, which region/department, ordering) rather than content;
2. a renderer branch in `CmsPage.vue` that fetches and renders live data;
3. in some cases an API endpoint or query parameter that does not exist yet.

| New block | Config the author sets | Data source | Serves |
|---|---|---|---|
| `church_finder` | placeholder text, target route | — (posts to `/find-church`) | B's hero, req 5 |
| `church_directory` | group-by-region on/off, region filter, limit | `/api/churches` | B, req 5c, region pages |
| `events_feed` | scope (national/regional), region, department, limit, date window | `/api/events` | B, req 9, region pages |
| `department_list` | show logos on/off, limit | `/api/departments` | B, reqs 1b + 2a |
| `region_list` | show logos on/off | `/api/regions` *(new)* | B, req 11 |
| `gallery` | owner (department / region / general), limit | `/api/gallery` | reqs 2a + 2c |

### Consequences for the rest of the plan

- **The homepage stays a CMS page.** `/` → `CmsPage.vue` → `/api/pages/home` still holds; it just gains blocks that can express B. The alternative — reverting the homepage to a hard-coded Vue view — was rejected because it removes the homepage from the CMS entirely.
- **These blocks are reusable across region and department pages**, which is what makes requirement 11's "reusable region template" achievable without bespoke views per region.
- **`CmsPage.vue` needs a fetching abstraction.** Six blocks each doing an ad-hoc `fetch` in a component that currently does exactly one will not age well. Introduce a small per-block async loader with loading and error states (see §10).
- `image` and `embed` are defined and rendered but used on **zero** pages. Harmless, but do not assume they are proven in production.

### Added tasks (fold into Phase 1, before Phase 2 consumes them)

- 1.17 Per-block async data loader in `CmsPage.vue` — loading skeleton, error state, empty state.
- 1.18 `church_finder` block (Filament def + renderer).
- 1.19 `church_directory` block.
- 1.20 `events_feed` block (depends on 1.12, `events.region_id`/`scope`).
- 1.21 `department_list` block (depends on 1.11, `departments.logo_path`).
- 1.22 `region_list` block (depends on 2.17, `/api/regions`).
- 1.23 `gallery` block (depends on 1.13, polymorphic `gallery_items`).
- 1.24 Re-author the `home` page's content to Direction B's block sequence.

---

## 10. Empty, Loading and Error States

The plan previously said nothing about these. They are where CMS-driven sites break in production, because an administrator can empty any field at any time.

| Situation | Current behaviour | Required |
|---|---|---|
| Header menu API fails | `Navbar.vue` sets `menuItems = []` → **navigation renders empty**, despite a comment claiming a fallback exists | Retain last-known menu, or render a minimal static fallback |
| CMS page not found | `CmsPage.vue` catch-all means any bad URL hits `/api/pages/{slug}` | Real 404 view |
| Data-bound block returns zero rows | n/a (new) | Honest empty copy — "No events scheduled for this region yet" |
| Data-bound block request fails | n/a (new) | Inline error, never a blank section |
| Region has no logo / no intro | n/a (new) | Fall back to site logo; hide the intro block rather than render an empty heading |
| Department has no logo | n/a (new) | Fall back to site logo (explicitly required by req 1b) |
| Leader has no portrait | n/a | Honest placeholder — **required now**, Rev. Peter Lloyd (D7) |
| Gallery empty | n/a | Hide the section rather than render an empty grid |
| Church has no coordinates | `withCoordinates()` scope **silently excludes it from the API entirely** | Deliberate: such a church is invisible on the locator. Decide whether it should still be listed |

Add to the verification checklist: for every new data-bound block, test all three of populated / empty / failed.

---

## 11. CMS Coverage Gaps — from a dedicated audit pass

A full audit of every Vue view against the admin surface found substantial hard-coded content the earlier draft missed, plus a class of defect worse than "not editable": **presentation inferred from content**.

### 11.1 Hard-coded content the plan did not cover

| # | Gap | Evidence | Fix |
|---|---|---|---|
| G6 | **`/departments` ignores the `departments` table entirely.** Four hard-coded ministries with age ranges, activities and scripture; the DB has six, a *different* set. `/api/departments` exists and is consumed by **nothing**. Adding a department in the admin changes nothing on the page. | `GetInvolved.vue:44-235` | Rebuild from `/api/departments`. Largest hard-coded surface on the site. |
| G13 | **Contact submissions are unreadable by anyone.** `POST /api/contact` → `ContactMessage::create()`. No mail, no notification, **no Filament resource**. `ContactMessagePolicy` exists and gates a resource that was never built — dead code. | `ContactController.php:21`; `ls app/Filament/Resources/` confirms absence | Build `ContactMessageResource`. ~30 min. The site is losing real messages now. |
| G7 | `/about` is live and 100% hard-coded (four topic cards, GS block, CTA). Unlike the other `views/about/*.vue`, this one **is** routed. | `About.vue:14-73`, `routes.js:8-11` | Convert to a CMS page. |
| G14 | **`meta_description` is editable but never rendered.** Collected in the form, returned by the API, never applied — `CmsPage.vue` sets no `document.title` and no meta tag. Ten pages have hand-written SEO text that reaches the browser as unused JSON. | `PageForm.php:46-51`, `PageController.php:59` | Apply title + meta on route change. |
| G1 | Wiring `Footer.vue` to `/api/menu/footer` **without seeding rows empties the footer** — `menu_items` has **zero** `location='footer'` rows. | verified by query | Add a seed step *before* task 32. |
| G15 | The one gallery row has `department = "Apostolic Bible College"`, but the gallery view fetches `?department=general`, so it renders nowhere — **a second reason it's invisible, independent of the disk bug**. ABC is not a row in `departments`, so the polymorphic backfill has no morph target for it. | `gallery_items` row 1; `GetInvolved.vue:252` | Decide the morph target before backfilling. |
| G9 | Church-locator hero stats are hard-coded **and one is wrong** — a literal "6" for Regions; there are 3. | `ChurchLocator.vue:36-37` | Include the hero in the locator rework. |
| G10 | Dead CTA — a `<button>` with no `@click` and no `href` under "Can't Find a Church Near You?" | `ChurchLocator.vue:353` | Wire or remove. |
| G18 | Two CMS-scaffolding demo pages, `welcome` and `about-cms`, are **published and publicly reachable** through the SPA catch-all. | `pages` table | Unpublish or delete. |
| G3/G4 | Footer copyright line and three legal links (Privacy / Terms / Cookie), all `href="#"`. | `Footer.vue:73-79` | Fold into site settings. |
| G12 | Hard-coded year — "2026 National Calendar". Stale on 1 Jan 2027. | `Events.vue:7` | Derive from data. |
| G5 | Production `console.log` noise, including six debug lines about "Auckland UPCI". | `Navbar.vue:160`; `ChurchLocator.vue:490-491,651-655` | Remove. |
| G11 | Leaflet marker images hard-coded to `cdnjs.cloudflare.com` — a third-party runtime dependency on a core page. | `ChurchLocator.vue:370-372` | Self-host. |

### 11.2 🔴 Presentation is inferred from content — the worst class of defect

An editor cannot control layout, and **reordering blocks silently changes appearance**. All verified verbatim:

```js
// CmsPage.vue:220-227 — background depends on ARRAY POSITION
const getTextBlockClasses = (index) => {
    if (index === 1) { return 'py-20 bg-white' }
    return 'py-20 bg-slate-50'
}

// CmsPage.vue:229-232 — 48px gradient "stat" styling triggered by a STRING MATCH
const hasStats = (content) => content && content.includes('- **')

// CmsPage.vue:313-318 — column count keyed off ITEM COUNT: 4 items → 2 cols, 3 or 5+ → 3 cols
const getCardsGridClasses = (items) => { const count = items.length; … }

// CmsPage.vue:320-323 — a card grid silently restyles as "registration buttons"
const isRegistrationBlock = (block) =>
    block.data.items.length >= 3 && block.data.items.every(i => i.data.link_url?.startsWith('http'))
```

Consequences an administrator will hit with no warning: inserting a block above another changes an unrelated section's background; writing a bulleted list with a bold lead-in turns every `<strong>` into 48px gradient text with no way to switch it off; adding a fourth card halves the grid; adding a third external link flips a whole block's styling.

**These heuristics must be replaced by explicit, author-set options** (a `style` select on `text` and `cards`, an explicit `columns` option) as part of the Direction-B block work in §9. Otherwise the new design will be fighting invisible rules.

### 11.3 🔴 `icon_svg` — silent data loss on edit

`CmsPage.vue` branches on `card.data.icon_svg` in **18 places**. `PageForm.php` declares it **zero** times. Live data contains **5** occurrences (`blue-ministry` ×3, `green-ministry` ×2).

Filament's Builder rebuilds block state from its declared schema on save. **Editing any affected card in the admin silently drops `icon_svg`, and the card permanently loses its styling.**

This intersects task 21 (adding `bio` to `card`): anyone editing those cards to add a biography strips `icon_svg` at the same moment. **Declare `icon_svg` in the schema before task 21 runs**, or migrate the five values to a declared field first.

### 11.4 Two Direction-B consequences

- **Department colour lives in three places that must change together**: `DepartmentForm.php:51-61` (the Select options), `Department.vue:97-104` (`THEME_CLASSES` hero gradients), `eventStatus.js:83-98` (`departmentChipClasses`). Miss one and hero gradients silently fall back to blue.
- **`two_column` cannot express B's 2/3 + 1/3 layout** — it hard-codes `lg:grid-cols-2` and forces the right column to `bg-gray-100 p-8 rounded-lg` (`CmsPage.vue:93-95`). It needs a ratio option and removal of the forced grey box. Confirms §9: **5 of 5** Direction-B components need new or extended blocks.

### 11.5 Added tasks

- 0.5 `ContactMessageResource` (G13) — before Phase 0 proper; the policy already exists.
- 1.25 Declare `icon_svg` in the `card` schema (G17) — **must precede task 21**.
- 1.26 Replace the four presentation heuristics with explicit author options (§11.2).
- 1.27 Add a `ratio` option to `two_column`; remove the forced grey box.
- 2.5 Rebuild `GetInvolved.vue` from `/api/departments` (G6).
- 5.5 Seed `location='footer'` menu rows **before** wiring the footer (G1).
- 5.6 Apply `meta_description` + `document.title` on route change (G14).
- 5.7 Unpublish the `welcome` and `about-cms` demo pages (G18).
- 5.8 Remove fabricated stats from **`GeneralSuperintendent.vue`** — the live one (corrected G8).

---

## 12. Red-Team Findings — verified failures

A dedicated adversarial pass probed the migrations against this project's real stack (Laravel 12.23.1 / SQLite 3.46.1, in-memory, via the real bootstrap). Probe script kept at `scratchpad/probe.php`.

### 12.1 ✅ Two hazards I wrongly feared — do not spend effort here

| Operation | Result |
|---|---|
| Multi-column `dropColumn(['a','b'])` | **OK** — SQLite 3.35+ native DROP COLUMN |
| Adding an FK via `constrained()` to a populated table | **OK** — rows preserved. Already proven in-repo by `2026_04_20_100002_add_region_id_to_churches_table.php` |
| `string(16)->default('national')` / `boolean()->default(true)` on populated tables | **OK** |

### 12.2 🔴 `morphs()` aborts the migration

Covered in §3.5. `morphs()` is NOT NULL; use `nullableMorphs()`. Probed: fails vs passes.

### 12.3 🔴 The `events.region_id` rollback is broken — and so is an existing migration

`dropConstrainedForeignId()` drops the column without first dropping an **explicitly declared** index on it, leaving a dangling index and aborting the rebuild. Probed:
- `dropConstrainedForeignId()` with a separate `$table->index(...)` in `up()` → **FAIL**
- `dropIndex([...])` first, then drop → **OK**
- no explicit index at all → **OK**

§3.4 specifies `region_id` "indexed", so the plan inherits this. **So does the existing repo migration** `2026_04_19_000003_add_department_id_to_events_table.php`, whose `down()` has evidently never been run. Fix both:

```php
public function down(): void {
    Schema::table('events', function (Blueprint $table) {
        $table->dropIndex(['region_id']);          // must come first
        $table->dropConstrainedForeignId('region_id');
    });
}
```

### 12.4 🔴 SECURITY — the site-settings page would be open to every admin user

A custom `Filament\Pages\Page` **has no model and never consults a policy.** Verified in vendor source:

```php
// vendor/filament/filament/src/Pages/Concerns/CanAuthorizeAccess.php:12-15
public static function canAccess(): bool { return true; }
```
(`Page.php:30` uses that trait.)

`AdminPanelProvider.php:47` already calls `discoverPages()`, so `ManageSiteSettings` would auto-register into the nav for **every** panel user. A `local` church user could replace the site logo, social links and contact email. A `SiteSettingPolicy` would do **nothing**.

**Fix:** override on the page itself —
```php
public static function canAccess(): bool { return auth()->user()?->isNational() ?? false; }
public static function shouldRegisterNavigation(): bool { return static::canAccess(); }
```
**And the test must assert a `local` user gets 403 on the page URL**, not that a policy method returns false — that assertion would pass while the page stayed wide open.

### 12.5 🔴 The API layer is missing, and §9 assumes it exists

No task touches `GalleryController`, `DepartmentController` or `EventController`, yet §9's blocks assume capabilities none of them have:

| §9 block | Assumes | Reality |
|---|---|---|
| `events_feed` | `?scope=` / `?region=` filters | `EventController::index()` filters `from`/`to`/`department` only; payload has neither field |
| `department_list` | `logo_path` in payload | returns `hero_image` only (`DepartmentController.php:23,74`) |
| `gallery` | filter by owner | filters the **free-text** column: `where('department', …)` |

`/api/regions` is correctly marked new but its **response shape is never defined**. Add explicit controller tasks with named fields, sequenced **between each migration and its consumer**.

### 12.6 🔴 The region rename breaks church writes silently

The verification log checked old **slugs**. The coupling is on **`name`**, in three places:
- `ChurchController.php:28` — `whereHas('organizationalRegion', fn ($r) => $r->where('name', …))`
- `ChurchController.php:125` and `:172` — `Region::where('name', …)->value('id')`

After `North Region` → `Northern Region`, a `POST /api/churches` carrying the old name **silently writes `region_id = null`** — `value()` returns null, no error, no validation failure. **Switch these to `slug` in the same step as the rename.** Also `/api/churches-organizational-regions` (`:221-229`) is an unfiltered `Region::pluck('name')` and will leak unpublished regions once §3.2 adds `is_published`.

### 12.7 🔴 Live placeholder data is visitor-facing

**Four churches carry seeded fake pastor names**, verified in the live DB:

| Church | `pastor_name` |
|---|---|
| Southside Pentecostal Fellowship | **Pastor John Smith** |
| Apostolics of Christchurch | **Pastor Michael Brown** |
| Grace Fellowship | **Pastor David Wilson** |
| Daystar Fellowship | **Pastor Robert Taylor** |

These render on the church locator today. Also flagged: Apostolics of Christchurch has a stale legacy `address` ("789 Colombo Street") that disagrees with its coordinates, and Church Triumphant Wellington's phone/email look seeded. **Client data cleanup task — not developer work.**

### 12.8 🟠 Two order-independent breakages

- **Wiring the logo to `/api/site-settings` removes the logo.** Every `site_settings` column is nullable and no step seeds `logo_path`. The current logo is a Vite asset (`resources/images/upci-nz-logo.png`), not in `storage/app/public`. Fix: copy it into `storage/app/public/site/` and seed `logo_path` in the same migration; keep the bundled import as the `v-else`.
- **The Phase 0 file move must not be a migration.** Migrations run under `RefreshDatabase` on **every** Pest run and on fresh deploys where the source files don't exist. Make it an artisan command, or guard with `File::exists()` and a no-op `down()`.

### 12.9 🟠 The rename silently corrupts the test database

All four project-owned tests call `Region::firstOrCreate(['slug' => 'north'], ['name' => 'North Region', …])`. Post-rename the lookup misses, so it **inserts** — no UNIQUE violation, nothing fails loudly, and the DB ends up with **six** regions. Existing tests still pass (they count churches), but the proposed `RegionApiTest` would see six. **Update all four test files in the same step as the rename.** The earlier verification-log grep covered `resources/js` and `app/Http` but never `tests/`.

### 12.10 🟠 Task numbering collides

§9 adds `1.17-1.24`, §11.5 adds `0.5, 1.25-1.27, 2.5, 5.5-5.8`, while §6 already uses a flat 1-34 and the execution order uses 1-30. §9's "depends on 2.17" means §6 item 17, but `1.17` also exists. **Renumber into one scheme before anyone executes.**

### 12.11 🟡 Smaller but real

- `Region::$fillable` is `['name','slug','sort_order']` — no step adds the four new columns or casts `is_published`.
- **Region pages ship with permanently empty event feeds**: the backfill sets all 49 events to `region_id = null`, and `Region.vue` ships before the national/regional split. Reorder or state the gap.
- **SBQ/JBQ will render empty** — no description, hero or colour — and will appear on the `/departments` index despite being filed under "Youth & Children's" in the nav.
- **Card `bio` vs `link_url` precedence undefined** — `CmsPage.vue:131-133` already renders an `<a>` inside the card; making the card clickable nests interactive elements.
- **No palette migration for stored values** — live pages carry `style: "gradient-blue"`, `"indigo"`; §11.4 catches the three department-colour locations but not these stored strings.
- **`about/leadership` is 13 cards in two blocks, not six people**, and `title` holds the **role** while `description` holds the **name**. Requirement 3a's modal needs that mapping decided, or the modal heading will read as a job title.
- **Run the scoped test gate after each migration step**, not once at the end — the migration steps are precisely the ones that can break the four existing files.

---

## 13. Design Coverage — where Direction B does not reach

From building five uncovered screens (`design-demos/B2-coverage-screens.html`, verified well-formed and free of horizontal overflow at 1440/1080/900/640/390px).

### 13.1 🔴 B as shipped cannot render any page that is not the homepage

It has no breadcrumb, no page-title band (its hero is a *task*, not a title) and no in-page navigation. Three GOV.UK transfers — `.breadcrumb`, `.page-header`, `.contents` — are **prerequisites, not enhancements**.

### 13.2 🔴 B's header cannot hold the plan's own IA

B places six nav items beside the wordmark; §5 specifies **nine** top-level items, which wrap to a ragged second line at 1200px. The fix is GOV.UK's own two-row pattern — a full-width service-navigation bar below the masthead, with the 5px green rule moved to close it. **This modifies an approved artefact and needs sign-off.**

### 13.3 🟠 The palette has no error hue

`brand-spec.md` commits to two hues; there is no red. GOV.UK uses `#d4351c` for validation precisely because failure must not be signalled by weight alone. An ink-only treatment works visually but leaves colour doing no work. **Decision needed:** accept ink-only, or admit a third hue reserved exclusively for errors. Recommendation: the third hue, since the chosen benchmark does exactly that.

### 13.4 🟠 The map is the one place B manages a foreign object

B's founding move is "no hero photography"; a slippy map is an uncontrollable full-bleed image on the highest-traffic page. The workable treatment reframes it as a **form control** — ink border matching the finder, a caption bar stating what it is and its bounds, square ink zoom controls. There is no version of B that makes a tile map feel native; the alternative is to demote it below the list.

### 13.5 🟠 Every data-bound block needs an authored empty-state message

Building the region page hit **two** empty states on one page purely from real data. Without an author-supplied empty message, a region or department with no content renders a hole. **Make this a field on every §9 block, not an afterthought.**

### 13.6 🟠 Two archetypes remain genuinely un-de-risked

- **`/calendar` month grid** — B has no table or cellular vocabulary anywhere; its entire language is one-dimensional rows. A 7×5 grid with multi-day spans is structurally different. **Highest remaining design risk.**
- **The CMS `cards` block** — 41 items across 7 pages, and B's stated position is anti-card. Whatever `cards` becomes changes 7 pages at once. Blast radius is known; the visual resolution is not.

### 13.7 Taxonomy budget is spent

All six harmonised tints go to departments. Regions are distinguished by **treatment** instead (filled vs outlined). That works — but there is no third treatment left. If SBQ/JBQ become a distinct class, the system has run out of room.

### 13.8 CMS content will fight the design

`department_announcements` rows contain emoji-heavy Facebook copy and raw `<iframe>` embeds wrapped in broken markdown (`[<iframe …>](https://)`), while `brand-spec.md` forbids emoji icons. **Decide: sanitise on render, or clean the content.**

---

## 14. Consequences of D9 — regions are names, not geography

### 14.1 🔴 The proposed concept fix is REJECTED

The design critique scored Concept 5/10 (veto: total capped at 6.0) because B passes the client-swap test — substitute another national membership body and the page still works. Its single proposed remedy was to *"stack the three regions vertically on a shared spine, sized by church count, ordered Northern → Central → Southern down the page, so the layout becomes the country."*

**Under D9 that remedy is wrong and must not be built.** It would assert a geography that does not exist. The data disproves it directly: Hamilton is in **both** Northern and Central; Wellington, in the North Island, is **Southern**. A visitor reading a north-to-south spine would conclude their nearest church is in the wrong region.

### 14.1a RESOLVED — the motif is "ten congregations, named" (D11)

Three seeds were considered: the fern in the mark, the three-tier structure as organisation, and the smallness of the network. **Smallness wins**, because it is the only one that is simultaneously true, specific, and load-bearing:

- **It passes the client-swap test.** Another national membership body has *branches* or *chapters*. It does not have ten congregations you could read in one screen. Swap the client name and the design stops making sense — which is exactly the property Concept 5/10 was missing.
- **It implies no geography**, so it survives D9 where the spine fix did not.
- **It fixes a functional defect at the same time.** The critique's F4 was that free-text search over ten records is worse than showing the ten records — type "Dunedin" and a national church site answers "nothing", which reads as *nothing here for me*. Naming all ten removes the failure mode entirely.

**What changes:** the hero stops being a search box and becomes the list itself — ten churches under three region headings, above the fold, with the count stated plainly. Search demotes to a filter *over* the visible list, never a gate in front of it. The existing `.church-list` and `.regions-grid` components already carry this; the change is one of hierarchy and position, not new vocabulary.

**What does not change:** task-first priority, no hero photography, flat lists, the department colour family, the two-hue system. Everything in the critique's KEEP list survives.

### 14.2 What must NOT be built anywhere

- No map, diagram or layout that implies regions partition the country geographically.
- No copy phrased as "in the north / lower North Island / South Island".
- Region *ordering* is `sort_order` (an administrative sequence), not latitude.
- The church locator may show pins **and** region filters, but must not imply the two are the same taxonomy. A visitor filtering by "Northern" is filtering an administrative grouping, not a part of the country.

### 14.3 Copy that needs rewording

`B-benchmark-transfer.html` currently reads *"Ten churches, organised across the Northern, Central and Southern regions of New Zealand."* The trailing "of New Zealand" attaches the region names to the country's geography. Reword to *"organised into three regions"*.

---

## 15. Placeholder Policy (D10)

Missing content ships as a **visible, labelled placeholder**. It is never substituted with stock or invented content, and it never blocks the surrounding structure.

Two components, both now in `B-benchmark-transfer.html`:

```css
.placeholder-note   /* inline, 11px caps, clay — for a missing image beside real content */
.placeholder-block  /* dashed 2px block — for a missing section of content */
```

### What is missing, and what it gets

| Missing | Placeholder | Notes |
|---|---|---|
| **Rev. Peter Lloyd's portrait** | `PL` initials tile + `.placeholder-note` reading "Photograph to be supplied" | Applied. Previously the tile read as a designed avatar with no indication anything was absent. |
| **Region logos** (`regions.logo_path` — column doesn't exist yet, no assets) | Fall back to the site logo; `.placeholder-note` in the admin, not on the public page | Falling back is not misleading — a region legitimately uses the national mark. |
| **Region intro / presbyter message** (`regions.intro`) | `.placeholder-block` — "Message from the presbyter to be supplied" | Do **not** hide the section; the gap should be visible to an editor. |
| **The four fake pastor names** — "Pastor John Smith", "Pastor Michael Brown", "Pastor David Wilson", "Pastor Robert Taylor" | **Null them, then render nothing.** | 🔴 These are *worse* than missing — they are false and visitor-facing today. A blank field is honest; a fabricated name is not. Add a data-cleanup migration nulling exactly these four values. |
| **Apostolics of Christchurch address** — stale "789 Colombo Street", disagrees with its coordinates | Show city only until corrected | Same class of problem: wrong beats absent. |
| **ABC enrolment duplicate URLs** — two `forms.gle` links each serving two different options | `.placeholder-note` "Registration link to be supplied" on the two whose target is unknown | Requirement 4 cannot be *completed* by an implementer; the correct URLs are client-held. Ship the placeholder rather than leaving a link that sends people to the wrong form. |
| **Regional photography / gallery** | `.placeholder-block` + honest empty state | Ties to §13.5 — every data-bound block needs an authored empty-state message. |
| **Department logos** (`departments.logo_path`) | Fall back to the site logo (explicitly required by req 1b) | Only one of six has even a `hero_image`, and that one is currently broken. |

### Rule for implementers

If a field is empty, render the placeholder — **do not** hide the component silently and **do not** invent filler. The one exception is content that is genuinely optional (an empty gallery hides its section rather than showing a dashed box), which §10 lists case by case.

---

## Appendix — Verification Log

Every claim below was checked against the live codebase/database on 2026-08-17, not inferred. Anything **not** in this table is an assumption and is labelled as such in §7.

| Claim | How verified | Result |
|---|---|---|
| Two DB-referenced images are in private storage | `find storage/app/private` cross-referenced against `departments.hero_image` + `gallery_items.image_path` | ✅ Exactly 2 referenced-and-broken; 11 further unreferenced orphans |
| CMS page images are unaffected | 16 `page-images` refs extracted from `pages.content`, diffed against `ls storage/app/public/page-images/` | ✅ All 16 resolve; 0 broken |
| Old region slugs are referenced nowhere | `menu_items.url` LIKE query; `pages.content` LIKE query; `grep -rn` over `resources/js` + `app/Http` | ✅ Zero hits — rename is safe |
| Region rename won't violate a constraint | `.schema regions` | ⚠️ UNIQUE on **both** `name` and `slug`. "Northern/Southern Region" don't collide with existing values, so safe — but the migration must not do a two-step swap through a duplicate |
| SBQ/JBQ inserts won't collide | `.schema departments` | ⚠️ UNIQUE on `slug`; `sbq`/`jbq` unused → safe |
| `events` has no region/scope column | `PRAGMA table_info(events)` | ✅ Confirmed: only `department_id` |
| `app/Filament/Pages/` does not exist | `ls -d` | ✅ Absent; `AdminPanelProvider` already calls `discoverPages()` on it |
| `card` block blast radius | `sqlite3` count of `"type":"cards"` / `"type":"card"` across `pages` | ✅ 7 pages, 8 blocks, **41 card items** |
| Top-level menu `sort_order` collision | `GROUP BY sort_order HAVING COUNT(*)>1` | ✅ Exactly one: "Find a Church" + "Apostolic Bible College" at 3 |
| `Home.vue` is dead code | `routes.js` — `path: '/'` → `CmsPage.vue` | ✅ Confirmed |
| Test baseline | `./vendor/bin/pest` executed | ✅ 38 pass / 16 fail; the 16 are pre-existing `Livewire\Volt\Volt` scaffolding |
| Build is green | `npm run build` executed | ✅ Builds in ~1.1s |
| Filament version | `composer show filament/filament` | ✅ **v4.0.0 stable** (despite `composer.json` saying `^4.0@beta`) |
| Announcements already work | Model → RelationManager → `/api/departments/{slug}` → `Department.vue:41-57`; 2 live rows | ✅ Requirement 6 needs no build work |
| Region filtering already works server-side | `ChurchController::index()` `whereHas('organizationalRegion')`; `formatChurchForApi()` returns `organizational_region`; `/api/churches-organizational-regions` exists | ✅ Requirement 5c is frontend-only |
| Logo is build-time bundled | `Navbar.vue:143`, `Footer.vue:89` `import upciLogo from ...` | ✅ Not CMS-editable today |
| Webfont loaded but unused | `app.blade.php` loads Figtree; `tailwind.config.js` `theme.extend` is `{}` | ✅ Downloaded and discarded |
| Footer social icons | Read `Footer.vue:15-31` and inspected SVG path data | ✅ 3 icons, all `href="#"`; **2 Twitter-bird variants + 1 Pinterest**; no FB/IG/YT |
| Filament v4 `SettingsPage` not in core | Research agent; `filamentphp.com/api/4.x/.../SettingsPage.html` 404s | ✅ Ships in the Spatie plugin — not installed here |

| **Does Direction B fit the existing block library?** | `grep` of `Builder\Block::make` in `PageForm.php` vs `block.type ===` in `CmsPage.vue` vs `"type":"…"` counts in `pages.content`; plus every network call in `CmsPage.vue` | ❌ **NO — disproved.** `CmsPage.vue:166` is the only fetch in the renderer; all 7 block types are static. **6 new data-bound blocks required.** See §9 |
| Blocks defined vs rendered are in sync | Same greps | ✅ In sync. `image` and `embed` are defined + rendered but used on **zero** pages |
| The `home` page is a CMS page | `routes.js` `path:'/'` → `CmsPage.vue`; `pages.slug='home'` exists | ✅ Sequence is `hero → text → cards → text → cta` |
| Navbar has no real menu fallback | `Navbar.vue` catch sets `menuItems = []` despite a comment claiming otherwise | ✅ Nav renders **empty** if `/api/menu/header` fails |
| Churches without coordinates are hidden | `ChurchController::index()` chains `->withCoordinates()` | ✅ Silently excluded from the API entirely |

**Known unverified (assumptions):** the `scope` enum values (`national|regional|department`); that region logos and intro copy will be supplied by the client.
