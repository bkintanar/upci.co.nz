---
iteration: 38
max_iterations: 40
plan_path: ".claude/PRPs/plans/upci-nz-site-overhaul.plan.md"
input_type: "plan"
started_at: "2026-08-17T14:20:00+12:00"
---

# PRP Ralph Loop State

## ⚠️ Completion criterion — read before emitting the promise

**The validation gate already passes with ~60 of 72 tasks unimplemented.** It is
green because it tests what exists, not what is missing. Emitting
`<promise>COMPLETE</promise>` on a green gate would be a lie.

Completion here means **the plan's tasks are done**, verified against the T0–T71
list, with the gate green as a floor rather than the definition.

## Codebase Patterns

- **Gate**: `vendor/bin/pint --test --dirty && npm run build && ./vendor/bin/pest <5 project-owned test files>`. Bare `pint --test` exits 1 on 83 pre-existing files and short-circuits the `&&` chain — `--dirty` is load-bearing.
- **Filament v4**: `form(Schema $schema): Schema` + `->components([])`. Layout in `Filament\Schemas\Components\*`, fields in `Filament\Forms\Components\*`, actions in `Filament\Actions\*`. Tables use `->recordActions()` / `->toolbarActions()`.
- **A missing policy means ALLOW**, not deny. Every new model needs one, and `SecurityRegressionTest` asserts this.
- **A custom Filament `Page` never consults a policy at all** — `canAccess()` hard-returns true. It must gate itself, and the test must assert 403 on the URL.
- `disabled()` without `dehydrated()` is not a fix — Filament re-hydrates disabled fields on save.
- **SQLite migrations are not transactional.** Verify `migrate:rollback` explicitly; keep `up()` idempotent.
- `morphs()` fails on a populated SQLite table — use `nullableMorphs()`. `dropConstrainedForeignId()` fails if `up()` declared a separate index — `dropIndex()` first.
- **File copies belong in an artisan command, not a migration** — migrations run under `RefreshDatabase` on every test.
- New columns need adding to `$fillable` or `update()` silently no-ops.
- Tests build models directly (`Model::create`, `Region::firstOrCreate`). Only `UserFactory` exists; no factories needed.
- `git commit -F <file>` for any message containing quotes, and verify `HEAD` afterwards — a failed `-m` leaves work staged while the follow-up merge/push reports success.
- `public/build` is gitignored; deploys must run `npm run build`.

## Current Task

Work the T0–T71 execution order. Requirement 1 complete; region foundation (T12) in.
Next: T13 → T14 → T15, which unblock requirement 11.

## Plan Reference

`.claude/PRPs/plans/upci-nz-site-overhaul.plan.md`

## Progress Log

### Iteration 1 — 2026-08-17

#### Completed
- **T13/T14/T15** (`c73f4ee`) — region content columns, `RegionResource`, `RegionPolicy`
- **T16/T17** — already done in an earlier pass; verified, not redone
- **T18/T19/T20** (`051c6c2`) — `events.scope` + `region_id`, `EventScope` enum, API filters, latent migration fix

#### Validation
- Lint PASS · Build PASS · Tests **60 passed (143 assertions)**, up from 42
- Both new test files verified to FAIL on revert before being trusted

#### Learnings
- **`can()` and Filament's resource gate disagree on a missing policy.** Laravel's
  `$user->can()` DENIES an unpolicied model; the "missing policy = allow" default
  lives in Filament's resource-authorization helper. So a negative policy test
  passes even with the policy deleted — pair it with a positive one, which is what
  actually catches deletion.
- **The T19 migration bug was real and is now proven.** `migrate:reset` on a scratch
  DB (`DB_DATABASE=<file> php artisan migrate:reset`) is the only way to exercise
  `down()`; it aborted with `no such column: department_id`. Worth running after any
  migration that adds a standalone index. The whole chain is now clean.
- **Hidden Filament fields are not dehydrated**, so a conditionally-hidden field keeps
  its old DB value. Clearing it needs an explicit `afterStateUpdated` + `->dehydrated()`.
  This is the mirror image of the `disabled()`/`dehydrated()` gotcha already noted.
- `->after('col')` is ignored by SQLite — columns land at the end. Cosmetic only.
- `region` and `organizational_region` are DIFFERENT church filters: the first is
  free-text geographic, the second is the region slug. `?region=northern` returning
  0 churches is correct, not a bug.

#### Deviation — T14
The plan asked for the locator's region endpoint to filter on `is_published`. That
flag describes whether a region's LANDING PAGE is ready, which is a different
question from whether its churches should be filterable — a bare filter repeats the
`withCoordinates()` defect fixed in `d1e0b0c`. Implemented as published OR has active
churches, so only a draft region with nothing in it is withheld.

#### Also completed this iteration
- **T21/T22/T23** (`ab47361`) — `nullableMorphs` owner + `is_published` on gallery_items,
  enforced morph map, rebuilt `GalleryController`, owner picker in the admin
- **T24** (`e0d3d9b`) — one shared relation manager on Department and Region, plus the
  `GalleryItemPolicy` ownership change it forced

#### T21's "blocking decision" was not blocking
The plan flagged it because the one gallery row has `department = "Apostolic Bible
College"`, which is not a `departments` row. But **a null owner IS the general gallery**
— requirement 2 asks for exactly that third case. The row keeps its legacy string and
becomes a general item, so nothing is lost if ABC later gets a model. No user decision
needed.

#### Live defect found and fixed on the way
`GetInvolved.vue` fetched `/api/gallery?department=general` — a department literally
named "general", which no row has ever matched. That section has rendered its empty
state since launch. Now points at the general gallery and shows its item.

#### More learnings
- **`enforceMorphMap` is app-wide, not per-relation.** Safe here only because
  `gallery_items` holds the only morph column in the schema and `GalleryItem` the only
  morph relation in `app/`. Check both before adding it anywhere else.
- **Validating a filter can turn a silent empty result into a 422 that breaks a page.**
  Always grep the frontend for existing callers before tightening an API filter — that
  is what caught the `?department=general` call.
- `$request->validate()` on these routes returns **302 without** `Accept: application/json`
  and 422 with it. Axios sends it; curl does not. Test with the header or you will
  misread a working validator as broken.

#### Next
T25 (`/api/regions` index + show) then the region landing pages — the remaining half of
requirement 11. Frontend work (Vue region pages, leadership modal, nav cleanup) is
untouched and is now the bulk of what is left.

---

### Iteration 2 — 2026-08-17

#### Completed
- **T25** (`2bbeda9`) — `RegionController` index + show, routes registered, `Region::events()` added
- **Requirement 4 (ABC)** — same commit, at the user's request to reconcile against the live site

#### Validation
Lint PASS · Build PASS · Tests **72 passed (173 assertions)** · migration up/down chain clean on a scratch DB

#### Requirement 4 findings
Most ABC content was ALREADY migrated, and **all four `forms.gle` URLs already matched the
live site** — so the plan's "ABC replacement forms.gle URLs" client-data blocker is resolved,
not outstanding. Two real defects instead:
1. Five registration cards where the live site offers four — "Foundation level" and
   "Foundation level course *NEW" pointed at the SAME form, so one destination appeared
   twice under two names. Deduped, reordered to the live site's order.
2. The live section has four sub-pages; **Connect did not exist here at all.** Added with a
   route and an Explore-card link.

#### Learnings
- **The live site is Wix and renders most body text client-side.** `WebFetch` truncates it and
  the served HTML does not contain it. Only the Principal's bio and the registration form URLs
  were recoverable by scraping. Anything else has to come from the client directly — do not
  burn cycles re-scraping.
- The `apostolic-bible-college/*` slugs already existed and matched the live URLs, so no
  redirects are needed.

#### Still outstanding for requirement 4
The Connect page's prose. It points at this site's existing `/contact` form rather than
inventing copy or standing up a second form.

#### Next
Region landing pages in Vue (the frontend half of requirement 11, now that `/api/regions`
exists), then requirement 5's remaining map work. Frontend is the bulk of what is left:
requirements 3, 6, 7, 8, 10 are untouched.

### Iteration 3 — 2026-08-17

#### Completed
- **T52** (`9705468`) — locator switched to the organizational region axis
- **T36** — partial: bounds, capped fitBounds, region grouping, "More info", global removed.
  Still open: refactoring the modal onto a shared `Modal.vue` (that is T35)

#### Validation
Lint PASS (no dirty PHP) · Build PASS · Tests **72 passed (173 assertions)**

#### The big finding — T52 was worse than the plan described
The plan predicted the dropdown would offer names while the filter expected slugs, giving
zero results. The reality: **the locator was never on the organizational axis at all.** It
read `/api/churches-regions` (the free-text `churches.region` column) and sent `?region=`.
That column holds eight inconsistent values — Auckland, Bay of Plenty, Canterbury,
**Rangiora**, **Rolleston**, Waikato, Wellington, Whangarei — two of which are towns. The
clean Northern/Central/Southern data sat unused.

This is why `?region=northern` returning 0 churches earlier was *correct*: two different
axes, not a bug. Both now exist; the locator uses the structural one.

#### Learnings
- **`churches.region` (free text, geographic) and `churches.region_id` → `regions` (structural)
  are two independent axes.** `?region=` filters the first, `?organizational_region=` the
  second. Do not assume a param named `region` means the region model.
- **Leaflet `maxBounds`/`minZoom` must be set in the `L.map()` constructor**, not after. Applied
  later, the first `fitBounds` escapes them and the map visibly snaps back.
- **Leaflet builds popup DOM only on open**, so a popup button listener has to attach on the
  `popupopen` event — binding at `bindPopup` time finds no element.
- Unguarded `setView([lat, lng])` throws inside Leaflet when either is null and kills the
  calling handler. Guard with `isMappable()` everywhere, not just in the marker loop.

#### Next
T38 (`Regions.vue` + `Region.vue` + routes) — `/api/regions` exists, so the region landing
pages are unblocked. Then T53 (`Department.vue` logo + gallery rendering), T35 (`Modal.vue`),
T39–T41. Requirements 3, 6, 7, 8, 10 still untouched.

### Iteration 4 — 2026-08-17

#### Completed
- **T38** (`c1bdada`) — `Regions.vue` + `Region.vue` + routes; 4 API-contract tests
- **T53** (`b88a034`) — site-logo fallback on `Department.vue`; shared `GalleryGrid.vue` on
  department and region pages
- **T40** (`826b286`) — `Gallery.vue` + `/gallery`, tabs built from the live department and
  region lists

#### Validation
Lint PASS · Build PASS · Tests **76 passed (189 assertions)** · full suite unchanged at 16
pre-existing failures

#### Notes
- `Department.vue` **already rendered `logo_path`** — what T53 was actually missing was the
  requirement-1b fallback to the site logo, plus the gallery section.
- `GalleryGrid.vue` is now the single grid for all three surfaces (department, region,
  standalone). It takes `preloaded` items so `Region.vue`, which already has them in its own
  payload, does not refetch.
- The grid renders **nothing** when there are no items and nothing failed, so pages can
  include it unconditionally without leaving an empty heading behind.

#### Learnings
- **Date-only strings must not go through `new Date()`.** `"2026-09-01"` parses as UTC
  midnight and renders as 31 August in NZ. Split the string instead.
- A filter-tab grid must be **keyed on the active filter**, or the previous tab's images sit
  under the new label until the request resolves.
- `useSiteSettings()` exposes `header_logo_url` / `footer_logo_url` (absolute URLs), not raw
  paths — no `imageUrl()` wrapper needed on those.

#### Next
T35 (`Modal.vue` + refactor the locator modal onto it, finishing T36), T39 (rebuild
`GetInvolved.vue` from `/api/departments`), T41 (portrait leadership + modal — requirement 3),
T44 (all navigation changes at once: Gallery, Regions, SBQ/JBQ, remove General Superintendent,
remove Twitter/X — requirements 7, 8, 10).

### Iteration 5 — 2026-08-17

#### Completed
- **T44** (`9cec55c`) — the whole navigation pass: requirements **7, 8 and 10** in one commit,
  plus the footer rebuild

#### Validation
Lint PASS · Build PASS · Tests **85 passed (208 assertions)** · migration up/down clean on a
scratch DB · full suite unchanged at 16 pre-existing failures

#### What was actually wrong
- **Gallery** was a child of About pointing at `/departments#gallery` — an anchor on a
  different page, not a gallery.
- **sort_order collided**: Find a Church and Apostolic Bible College were both `3`, so the
  order depended on insertion, not intent. Renumbered 10..90.
- **Zero `location='footer'` rows existed.** Seeding had to precede wiring or the footer
  would render empty (the plan's G1 warning, confirmed).
- **Requirement 8 had no Twitter link to delete.** The footer's three social icons were
  hard-coded with `href="#"` — two Twitter bird variants and a Pinterest, none live. Fixed at
  the settings level as the requirement asks, so there is no Twitter entry to render.
- Privacy Policy / Terms / Cookie Policy were three more `href="#"` dead ends for pages that
  do not exist. Removed.
- **SBQ and JBQ existed nowhere** — only inside event NAMES in the 2026 calendar seeder. Both
  now have a menu position, route and page, with explicitly placeholder copy.

#### Learnings
- **A test asserting on seeder-created rows passes vacuously under `RefreshDatabase`.** The
  "moved not duplicated" test found 0 rows, not 2, because department menu rows come from a
  seeder that does not run in tests. Rewritten as "no URL appears twice in the header", which
  holds on an empty table AND catches any future duplicate. Verified the real move separately
  on the dev DB.
- The menu endpoints are **`/api/menu/header`** and **`/api/menu/footer`** — not a
  `?location=` filter. I assumed the latter and it silently returned the SPA HTML shell.

#### Known consequence, flagged not hidden
The header is now **nine top-level items**, up from six. This worsens the pre-existing navbar
crowding (measured at 169px back at `010186e`, with the ORIGINAL logo — it was never
self-inflicted). **T46's two-row header is the fix.** Withholding items requirement 7 asks
for would not have been the right trade.

#### Next
T35 (`Modal.vue`, finishing T36) then T41 — together these are requirement 3, the leadership
modal and portrait images. Then T39 (rebuild `GetInvolved.vue` from `/api/departments`),
T42 (meta/title on route change), and requirement 6 (announcements).

### Iteration 6 — 2026-08-17

#### User steer mid-iteration
Asked when the huashu-design overhaul lands. Answered with the real state (approved
Direction B, zero tasks implemented, empty `theme.extend`) and the cost of waiting: every
view built in the current visual language accrues restyling debt. **User chose "tokens now,
full B after features."**

#### Completed
- **T33/T34** (`f2257e3`) — brand tokens in `tailwind.config.js`, `utils/theme.js`

#### Validation
Lint PASS · Build PASS · Tests **85 passed (208 assertions)**

#### Decision — Figtree over Poppins (T33's open question)
Figtree is **already loaded** from bunny.net and was never wired up, so the webfont was
fetched and unused. `body` already carries `font-sans`, so pointing that at Figtree fixes the
whole site with zero markup change. Poppins would add a second font request, and its
geometric roundness reads friendlier than a national church body should — Direction B's
register is plain and institutional.

#### Correction to brand-spec.md
The spec annotates `oklch(0.47 0.09 143)` as "≈ #4D7B37" (the raw logo sample). It actually
resolves to **#3a6838** — darker and less saturated. I converted OKLCH→sRGB independently and
got the same value the build emits, so **the toolchain is correct and the annotation was
wrong**. The darkening is deliberate per the spec's own argument ("reads as ink rather than
screen-green"), so the oklch value stands and the note was corrected.

I initially blamed the build chain for "shifting the brand colour" — wrong, and caught by
doing the conversion myself rather than assuming.

#### Learnings
- **Verify design tokens against the COMPILED CSS, never the config.** A token that does not
  emit is worthless, and the config looks identical either way.
- **`ls -t public/build/assets/*.css | head -1` is the wrong file.** Scoped component styles
  build to their own CSS; the real entry is whatever `manifest.json` maps
  `resources/css/app.css` to. My first check grepped a 124-byte component file and reported
  a false negative.
- **Tailwind JIT emits only classes it finds in content**, so an unused token legitimately
  does not appear. Confirming `text-h2`/`text-body` needed a throwaway probe component.
- Tailwind 3.4 converts `oklch()` to hex at build; authoring in oklch costs nothing at render
  and preserves the spec's intent.

#### Next
Requirement 3 (T35 `Modal.vue` + T41 portrait leadership) and requirement 6 (announcements)
are the last two untouched requirements. Then the full B rollout: T32, T45–T49, spikes
T50/T51. **T46 (two-row header) also fixes the nine-item navbar crowding.**

### Iteration 7 — 2026-08-17

#### Completed
- **Requirement 3 / T35 / T41 / T36 remainder** (`ea347d8`) — `Modal.vue`, leadership detail
  modal, portrait imagery, locator refactored onto the shared modal

#### 🔴 REGRESSION I INTRODUCED AND SHIPPED — now fixed
`groupedChurches` was computed in `9705468` but **never returned from `setup()`**. The
template iterated `undefined`, Vue rendered nothing, no error was raised, and **the church
list on `/find-church` was empty from that commit until this one.**

I reported it working having checked the API payload (4/2/4) and an HTTP 200. For an SPA a
200 means the shell loaded — it says nothing about what rendered. **Curl cannot verify a Vue
change.** Browser verification is now mandatory for any template work.

#### Verification method that actually works
Playwright (`npm install playwright` in the scratch dir; `npx playwright install chromium`).
Assert on rendered DOM, not status codes. This iteration it confirmed: 13 person cards,
portrait ratio 0.75, modal heading = name / subtitle = role, focus enters and returns,
Escape closes, 3 region headings, 10 church cards, 9 Leaflet tiles inside the dialog, zero
JS errors.

#### Decisions
- **T41 field mapping:** swapped in the DATA so `title` = name, matching every other card on
  the site. Special-casing the renderer for one page would be a heuristic — what T27 removes.
- **`variant: 'person'`** is an explicit author option, not inferred from heading text or
  item count.

#### Learnings
- **A Vue computed that is never returned from `setup()` fails silently.** `v-for` over
  `undefined` renders nothing and raises no error. The build passes. Only the DOM shows it.
- **Native `<dialog>` + `showModal()`** gives Escape, inertness and top-layer stacking free;
  the focus trap must be written. Teleport to `<body>` or a transformed ancestor clips it.
- **A teleported dialog needs TWO ticks** before its content is measurable — Modal opens in
  its own watcher, so one tick leaves the container null and Leaflet never initialises.
- Closing a modal must clear the selection, or reopening the same record is a no-op.

#### Client blocker RESOLVED
**Rev. Peter Lloyd's portrait exists** — a real 2048×2048 headshot. All 13 leadership photos
are present and distinct. Two (Matika, Lloyd) are landscape/square originals, which the 3:4
`object-cover` box handles.

#### Next
Requirement 6 (announcements) is the last untouched requirement. Then T39, T42, and the
Direction B rollout (T32, T45–T49, spikes).

### Iteration 8 — 2026-08-17

#### Completed
- **Requirement 6 — announcements** (`e83e24e`). This was the last untouched requirement.

#### Validation
Lint PASS · Build PASS · Tests **91 passed (218 assertions)** · browser-verified

#### The real defect
`published_at` gated nothing. `scopePublished()` checked only `is_published`, so an
announcement dated a year out went live on save. Proven by creating a 2027-dated row and
watching it appear on the public API. Null is treated as "no schedule", which preserves
existing rows.

#### What I got wrong, and how it surfaced
I also claimed `sort_order` was ignored and changed the controller. **It was never ignored** —
`Department::announcements()` applies `orderBy('sort_order')` at the RELATION level. The
revert test could not be made to fail, which is what exposed the false claim. Redundant
change reverted; the test kept as characterisation with a corrected comment.

**This is the second time in two iterations that the failability check caught something.**
Once a shipped regression, once a false claim. Reverting a fix and confirming the test breaks
is not ceremony — it is the only thing separating a real test from a decorative one.

#### Learnings
- **Check the relation before blaming the controller.** Eloquent relations can carry
  `orderBy`/`where` that make controller-level clauses redundant. `grep "function <relation>"`
  on the model first.
- A test that passes with the fix reverted is not evidence of anything. Treat an unfailing
  revert as a signal the diagnosis is wrong, not that the test is fine.
- Attribute console errors before claiming them. The one on department pages is Facebook's
  SDK from an embedded `plugins/post.php` iframe — it is absent on pages with no iframes.
- Broad DOM selectors overcount: `document.querySelectorAll('article')` returned 4 on a page
  with one announcement. Scope to the section.

#### Requirement status — all 11 now addressed
1 ✅ · 2 ✅ · 3 ✅ · 4 ✅ · 5 ✅ · 6 ✅ · 7 ✅ · 8 ✅ · 9 ✅ backend (data is T55) · 10 ✅ · 11 ✅

#### Next
Remaining work is refinement, not requirements: T39 (rebuild `GetInvolved.vue` from
`/api/departments`), T42 (meta/title on route change), T26–T31 (CMS block library), then the
Direction B rollout (T32, T45–T49) and spikes T50/T51. **T46 also fixes the nine-item navbar
crowding.**

### Iteration 9 — 2026-08-17

#### Completed
- **T42** (G14) — `usePageMeta` composable; titles and descriptions on route change
- **T46** — two-row header, pulled forward because the user hit the crowding
- Four user-reported UI defects (`35b3e73`)

#### Validation
Lint PASS · Build PASS · Tests **92 passed (222 assertions)** · browser-verified at 3 widths

#### 🔴 Second regression from the Modal refactor
The locator's ORIGINAL inner X button survived the refactor and still only set
`selectedChurch = null`. That emptied the panel while the `<dialog>` stayed open — backdrop
over a blank page. **My iteration-7 test missed it because I clicked `Modal`'s own close
button, not the one left in the locator's markup.**

Lesson: after replacing a component's chrome, **grep the old markup for leftover handlers**
(`grep -n "selectedChurch = null"` found it instantly). Testing the new affordance says
nothing about the old one still sitting in the template.

#### Navbar — measured, not guessed
Before: last nav item ended at **1614px in a 1440 viewport** (174px off-screen); 446px
overflow at 1024. After the two-row split and removing the extra flex gap: 9 items, **one
row, centred to 0px, logo vertically clear, no overflow, no horizontal page scroll.**

The 5px rule uses `border-brand-green-700` — first live use of the T33 tokens, visible in the
screenshot.

#### Learnings
- **`margin: 2rem auto` on a dialog panel pins it to the top.** Make the dialog
  `display:flex` **scoped to `[open]`** and give the panel `margin: auto`. Unscoped, it
  overrides the UA's `dialog:not([open]){display:none}` and the panel never hides.
- **Meta descriptions need markdown stripped.** A department description starting
  `**Who we are**` shipped literal asterisks and newlines into the tag.
- **Playwright measurements can flake** — one 1440 run returned 0 items where the previous
  returned 9. Wait on a specific selector (`waitForSelector`) before measuring, and re-run
  before believing an outlier.
- A test pinning presentational LABELS breaks on a wording change. Assert URLs.
- Again: assertions over seeder-created rows are unusable under `RefreshDatabase` — assert
  the `sort_order` values that encode the intent, and verify the rendered sequence against
  the dev DB separately.

#### Next
T39 (rebuild `GetInvolved.vue` from `/api/departments`), then T26–T31 (CMS block library) and
the rest of the Direction B rollout (T32, T45, T47–T49) plus spikes T50/T51.

### Iteration 10 — 2026-08-17

#### Completed
- **T39 / G6** (`6cace1c`) — `/departments` rebuilt from `/api/departments`

#### Validation
Lint PASS · Build PASS · Tests **92 passed (222 assertions)** · browser-verified

#### What G6 actually was
The page hard-coded **four** ministries with invented age ranges, activity lists and
scripture. The table holds **six**, a different set. `/api/departments` existed and was
consumed by nothing, so adding or renaming a department in the admin changed nothing on the
page it was meant to drive.

Now: six real cards, each with its own logo and a plain-text excerpt of its CMS description.
The invented detail was deliberately dropped rather than transcribed — reproducing it would
keep publishing copy nobody can edit, the same reasoning as the fabricated statistics removed
in `010186e`.

#### Flagged, not fixed
The **"Additional Opportunities"** section still hard-codes Music Ministry, Teaching Ministry
and Evangelism. None are rows in `departments`. Left in place: I cannot tell from here whether
they are real programmes that belong in the CMS or filler, and deleting possibly-real
ministries is the client's call, not mine.

#### Learnings
- **Scope browser selectors to page content.** `a[href^="/departments/"]` matched the navbar
  dropdown link first — hidden until hover — and the click timed out. The card grid needed
  `.prose a[...]`. A selector that matches chrome as well as content will find the chrome.
- Card excerpts should strip markdown to plain text rather than render HTML: the whole card is
  an `<a>`, and nesting block or interactive content inside a link is invalid.
- Send full field values from a list endpoint and let the caller truncate — server-side
  truncation of markdown risks cutting mid-syntax.

#### Next
T26–T31 (CMS block library): `icon_svg` in the card schema (**must precede T31**), remove the
four presentation heuristics, `two_column` ratio, per-block async loader, six data-bound
blocks, card `bio`. Then the rest of Direction B (T32, T45, T47–T49) and spikes T50/T51.

### Iteration 11 — 2026-08-17

#### Completed
- **T26/T31** — `icon_svg`, `variant`, `bio` declared in the card block schema
- Navbar reverted to one row + true three-level menu (`5cbd9dd`), all user-directed

#### Validation
Lint PASS · Build PASS · Tests **97 passed (238 assertions)** · migration up/down clean

#### The T26 bug was wider than the plan recorded
`CmsPage.vue` reads `icon_svg` in 18 places; `PageForm` declared it zero times. Filament's
Builder rebuilds block state from the DECLARED schema on save, so editing an affected card
dropped it permanently. **`variant` and `bio` — which I added in `ea347d8` — had the same gap
across 13 live cards**, so editing any leadership card would have destroyed the portrait
treatment and modal I had just built.

Accuracy note: the plan said 5 live `icon_svg` values; there are 4. The missing one is the
card *I* removed when deduping ABC registration in iteration 2 — not evidence of stripping.

#### Design reversal — the two-row header was wrong
The user rejected it: they want the logo left of the menu on one row, no green rule. Reverted.
Fitting nine items beside the logo needed a slimmer bar and mark, tighter item padding, and a
**wider container for the nav alone** — `max-w-7xl` minus padding cannot hold ~1240px. Below
1400px there is genuinely no room, so the hamburger takes over there.

**Lesson: measuring that a layout "fits" is not the same as it being the layout the user
wants.** I solved the overflow correctly and still had to undo it.

#### The interleaving was also the wrong fix
Ordering SBQ/JBQ next to their ministries was a workaround for the navbar rendering two
levels. The user wanted real nesting. Fixed properly: the API formatter is now recursive (it
stopped at children, so a grandchild could exist in the admin and never render), and both
menus render a third level. **When a structural limit forces a workaround, say so and offer to
lift the limit — do not quietly ship the workaround as the answer.**

#### Learnings
- **Filament's Builder silently drops undeclared block fields on save.** Any field the
  renderer reads MUST be declared. Adding a field to block data in a migration without adding
  it to the form schema is a delayed data-loss bug.
- Testing Filament schemas by instantiation needs a Livewire container; reading the declared
  names from source is simpler and survives upgrades.
- A moved page needs **slug, menu row and Vue route** changed together.
- Changing a Tailwind breakpoint (`md:` → `xl:`) silently breaks any test selector that
  hard-codes the old class.

#### Next
T27 (replace the four presentation heuristics), T28 (`two_column` ratio), T29 (per-block async
loader), T30 (six data-bound blocks), then Direction B (T32, T45, T47–T49) and spikes T50/T51.

### Iteration 12 — 2026-08-17

#### Completed
- **T27** (`02d674e`) — the five presentation heuristics replaced with authored fields

#### Validation
Lint PASS · Build PASS · Tests **98 passed (239 assertions)** · migration up/down clean ·
browser-verified against the old rules' output

#### The pattern that made this safe
Removing an inference rule restyles every page that relied on it. The fix is **backfill first,
then delete**: compute what each rule produces today, store it as data, and only then let the
renderer read the stored value. Both must land in one commit — a deploy between them would
show every page unstyled.

The plan named four rules; there were **five**. `getCardsSectionClasses` alternated a cards
section's background on its ordinal among cards blocks, which is the same defect and was not
listed.

#### Left alone, deliberately
`getCardIconClass()` still picks a colour by matching the substring `'16.707 5.293'` inside an
SVG path — same class of defect, narrower blast radius. Unpicking it needs an icon decision
that belongs with Direction B, so it is flagged rather than half-changed.

#### Learnings
- **Column/grid classes must be a literal lookup**, never `lg:grid-cols-${n}`. Tailwind reads
  source as text; an interpolated class is never emitted and the grid silently collapses to
  one column.
- When replacing inference with data, the test worth writing is "every block carries the field
  explicitly" — it catches anything that writes blocks outside the form.
- Check a data-driven test is not vacuous before trusting it: confirm the fixture actually
  contains the block types being asserted on (four text blocks here).

#### Next
T28 (`two_column` ratio + drop the forced grey box), T29 (per-block async loader with
loading/error/empty states), T30 (six data-bound blocks). Then Direction B (T32, T45, T47–T49)
and spikes T50/T51.

### Iteration 13 — 2026-08-17

#### Completed
- **T28** (`35396b8`) — `two_column` ratio + optional grey panel
- **T29** (`b5d7e63`) — `useBlockData` + `BlockState`; loading / error / empty per block

#### Validation
Lint PASS · Build PASS · Tests **98 passed (239 assertions)** · migration up/down clean ·
all four block states browser-verified

#### The bug that justified wiring infrastructure to a real consumer
`useBlockData` only unwrapped **functions**. `GalleryGrid` passes a `computed`, which is an
object, so the ref itself reached `fetch()`, stringified to `[object Object]` and 404'd —
every department gallery rendered an error panel that looked like a broken endpoint rather
than a broken call.

Had I shipped the composable as unused infrastructure "for T30", this would have surfaced
later and looked like an API fault. **Build infrastructure against a real consumer in the same
change, or it is unverified by construction.**

#### The backfill-then-change pattern held again (T28)
Seven `two_column` blocks backfilled to their current appearance (even split, panel on) before
the renderer started reading the fields. Verified both directions: unchanged pages still
render 584/584 with the panel; `ratio=2-1` with the panel off gives 795/373 and a transparent
column, while a page left on the default is unaffected — so the setting is genuinely per
block, not global.

#### Learnings
- **`typeof ref === 'object'`, not `'function'`.** A composable taking "a URL" must handle
  string, getter and ref, or a computed silently becomes `[object Object]`.
- An empty state must be distinguishable from a failure *and* from loading. Three states, three
  renderings — and the empty wording belongs to the author, not the component.
- Test the component where it is actually mounted. `/get-involved` redirects to `/departments`,
  which has its own inline gallery, so my first pass was measuring a different implementation
  entirely and reported false failures.

#### Next
T30 — six data-bound blocks (`events_feed`, `department_list`, `region_list`, `gallery`, plus
the remaining two), each with an authored empty-state message, now that the state handling
exists. Then Direction B (T32, T45, T47–T49) and spikes T50/T51.

### Iteration 14 — 2026-08-17

#### Completed
- **T30** (`f1ab078`) — six data-bound CMS blocks. **Block C is now finished** (T26–T31).

#### Validation
Lint PASS · Build PASS · Tests **98 passed (239 assertions)** · all six blocks
browser-verified against live data

#### What changed conceptually
Every block before these rendered authored content. These render live data — the author sets
configuration, and the page stays current on its own. Adding a church or publishing an event
now updates the page with nobody editing it. That is what makes requirement 11's "reusable
region template" achievable without bespoke views per region.

#### ⚠️ Environment issue found, NOT fixed — worth raising
A block's first render intermittently 500s with **`SQLSTATE[HY000]: database is locked`** on the
`cache` table. The rate limiter increments a counter per API request, the cache store is the
SQLite database, and several concurrent block fetches on one page collide.

curl succeeds while the browser fails, because the browser issues the requests concurrently.
It is intermittent and looks exactly like a broken endpoint. **Likely fix: move `CACHE_STORE`
off `database` (file or redis).** That is environment config, so it is flagged rather than
changed unilaterally — and it will get worse now that a page can hold six data-bound blocks.

#### Learnings
- **A 500 that curl cannot reproduce is a concurrency symptom.** Capture `response` events in
  Playwright to find which request failed; the page-level error said nothing useful.
- Apply a presentation-only `limit` client-side rather than inventing an API parameter for it —
  otherwise layout config leaks into the endpoint contract.
- Filter server-side where the endpoint already supports it (`from` for upcoming events), or
  the page downloads a whole calendar to discard most of it.

#### Next
Direction B rollout: **T48 first** — it is marked 🔴 prerequisite, because B cannot render any
non-homepage page until `.breadcrumb`, `.page-header` and `.contents` are ported. Then T32
(re-author home), T45 (hero), T47 (error hue), T49 (craft fixes), and spikes T50/T51.

### Iteration 15 — 2026-08-17

#### Completed
- **T48** (`216ba89`) — `Breadcrumb`, `PageHeader`, `Contents` ported into the shared layout.
  This was the 🔴 prerequisite gating the rest of the Direction B rollout.

#### Validation
Lint PASS · Build PASS · Tests **98 passed (239 assertions)** · all three components
browser-verified

#### Why these three came first
B's hero states a TASK, which suits the homepage and nothing else. Without a breadcrumb, a
title band and in-page navigation, B literally had nothing to put at the top of an interior
page — so T32 and T45 could not land ahead of them.

#### Design decisions
- The breadcrumb **derives from the route**, so no author maintains it, and strips the
  site-name suffix from the leaf (`"Leadership - UPCI New Zealand"` → `Leadership`).
- `PageHeader` renders **only when a page has no hero**, so it never states the same thing
  twice. Every current CMS page has a hero, so it is dormant until B replaces those.
- `Contents` is **derived from the page's own headings** rather than authored, so the index
  cannot drift from what it indexes. Shown only at 3+ sections.

#### Applied the session's own lesson
`Contents` had no data source. Rather than ship it as an unused component, I gave it a real
one. **Twice this session an unconsumed abstraction turned out to be broken** (the
`groupedChurches` computed, the `useBlockData` ref) — a component with no caller is unverified
by construction, so the verification cost is paid later and looks like a different fault.

#### Deviation recorded
§13.2 prescribes B's two-row header for the nine-item nav. Built, then **reverted at the
user's direction** in `5cbd9dd`; nine items now fit one row with the logo to its left. The
constraint the section describes is met by other means.

#### Learnings
- `pl-4.5` / `pt-13` are **not** in Tailwind's default scale and emit nothing. Use arbitrary
  values (`pl-[18px]`) when porting pixel values from a design file.
- Constrain a title band's measure in `ch`, not `px` — it should break by measure, not
  viewport.

#### Next
T32 (re-author home to B's block sequence — the six data-bound blocks now exist to express
it), T45 (hero → "ten congregations, named"), T47 (error hue), T49 (craft fixes), then spikes
T50 (`/calendar` month grid) and T51 (the cards block under B).

### Iteration 16–17 — 2026-08-17

#### Homepage: rejected, rolled back, re-approached — AWAITING USER CHOICE
The Direction B homepage was built, switched live, and **rejected on sight**. Rolled back; the
live page is its original content. The rest of the B work (tokens, breadcrumb, page header,
contents, data-bound blocks) is untouched and not in question.

Fetched `upca.org.au` rather than assuming it. Its homepage is **image-led**: one large
photograph carrying the current event, a single REGISTER button, a thin About, then the church
locator, then past-event media. B's founding move is the opposite — *no hero photography, the
front page is a task*. Near-inverses. B remains right for interior pages.

**🔴 The binding constraint, found by inventory:** there is **no hero photography**. 27 images
on the public disk — 23 leadership portraits, 2 logos, 1 department image, 1 gallery photo.
Nothing landscape. UPCA's approach is built on photography UPCINZ does not have, and
`brand-spec.md` forbids the substitute in as many words: no stock "community" photography.

Three directions built and shown (`design-demos/home-r2/`), each a different honest answer to
*what an image-led homepage does before the images exist*:
- **D1 Dark Editorial** — style roulette (`date +%S`=50 → #11)
- **D2 Search-led** — benchmark transfer from the Church of England's *A Church Near You*
  (verified: Archbishops' Council, deliberately search-led, no photography)
- **D3 Vessel** — Kenya Hara, emptiness as a container

**Stopped per the skill's three-direction gate. The choice is the user's.** Open question only
they can close: are conference/congregation photographs available?

#### Completed
- **T32** — homepage authored, switched, then rolled back at the user's direction (`981a379`,
  reverted). Statistics work from it **survives and is live**: `/api/church-statistics` counts
  from records, replacing four typed figures of which three had drifted.
- **T47** (`500ee1e`) — `ErrorSummary` + field validation treatment (D13)

#### Validation
Lint PASS · Build PASS · Tests **102 passed (246 assertions)**

#### 🔴 Fifth regression, caught in the browser
Adding `ErrorSummary` **broke the entire contact page**. The component was registered but never
imported — my edit matched `import { defineComponent, ref }` while the file imports `reactive`
too — so the view threw `ErrorSummary is not defined` and rendered nothing. **The build passed
throughout.** Only opening the page showed it.

That is now five for five: every Vue regression this session was invisible to lint, tests and
build, and visible immediately in a browser.

#### Learnings
- An exact-match string edit against an import line is fragile — the line drifts. Assert the
  replacement applied, or match on a prefix.
- `a:last-of-type` matches the last `<a>` *within each parent*, so in a list of one-link items
  it matches every link. My test reported a focus bug that did not exist.
- Statistics belong in an endpoint, not in prose. Three of four typed figures had drifted, and
  one category ("Daughter Works") never existed in the data at all.

#### Next
Blocked on the homepage choice for T45 (hero). Unblocked: T49 (craft fixes), T50/T51 spikes,
Block H/I gaps.

### Iteration 19 — 2026-08-17

#### Completed
- **T56 / T57** (`afd1211`) — resilient navigation and not-found handling
- **T59** marked done retrospectively — the announcement `published_at` gate landed in `e83e24e`

#### Validation
Lint PASS · Build PASS · Tests **102 passed (246 assertions)** · all three failure paths
browser-verified by aborting requests

#### Both tasks were mis-recorded in the plan
- **T57 was worse.** `Navbar.vue`'s catch carried the comment *"Keep default menu if API
  fails"* and then set `menuItems = []` — the comment promised the opposite of the code. A menu
  endpoint failure left the site with **no navigation at all**. Now falls back to six stable
  top-level destinations, hard-coded deliberately: a fallback that depends on the thing that
  just failed is not a fallback.
- **T56 was not missing.** A 404 view already existed. Its actual faults: an emoji heading
  (ruled out by `brand-spec.md` §4) and reporting *"Page not found"* for **any** failure,
  including a network error — which sends someone hunting for a page that is still there.

#### Learnings
- **Read the code, not the comment.** The most misleading thing in this codebase so far was a
  comment that stated the intended behaviour beside code doing the opposite. Nothing flagged
  it; the plan inherited the comment's claim.
- Distinguish *missing* from *failed to load* in any fetch-backed view. They need different
  words and different affordances — a retry button on a genuinely missing page is a lie.
- `page.route(url, r => r.abort())` is the cheapest way to exercise a failure path end to end.

#### Still blocked on the user
Homepage direction (D1 / D2 / D3), and whether conference or congregation **photography**
exists. T45 waits on both.

#### Next unblocked
T62 (announcement content carries raw `<iframe>` through `v-html`), T67 (calendar year is
hard-coded and goes stale on 1 Jan 2027), T66 (`/about` is live, routed and fully hard-coded),
T70 (drop the orphaned `gallery_items.department` column), T50/T51 spikes.

### Iterations 21–22 — 2026-08-17

#### The user's question, answered with a measurement
*"I'm still not seeing frontend ui changes."* Measured: **666 old-palette class uses against
105 brand-token uses.** The tokens landed in T33 but were only ever USED in components built
after it, so every existing page still rendered in Tailwind's default blue. Plenty had changed
structurally; nothing had changed chromatically. That gap was mine to have flagged.

#### Completed
- **Leadership modal** (`4ce47bc`) — the detail view was **60%** the width of the card that
  opened it (192px against 318px) from 1224×1804 sources. Now 132%. The panel widens only when
  a bio exists, because none do, and a wide panel otherwise reserves an empty column.
- **Palette rollout** (`99b58a4`) — 188 replacements across 19 files. Blue/indigo → fern green.
  **Now 37 blue/indigo against 286 brand tokens.**

#### Deliberately not migrated
- `eventStatus.js` — its blue/green/amber are **semantic** (past/live/soon/future). Mapping
  blue onto green would make "upcoming" and "happening now" identical.
- The department theme map — six departments must stay distinguishable; collapsing `blue` into
  green made Prayer and Men's look like the same page. My blanket pass hit it and I reverted
  that one key. Spreading the six is **T49** and needs a hue-per-department decision.
- Neutrals (`slate`, `gray`) — legitimate greys, not an arbitrary accent.

#### Learnings
- **A design token that nothing consumes is invisible.** Wiring the palette and rolling it out
  are two different pieces of work, and only the second one is visible to the client. Say which
  one has happened.
- A blanket find-and-replace across a palette will hit semantic colour maps that happen to use
  the same names. Check every lookup table the pass touched.

#### Still blocked on the user
1. Homepage direction — D1 / D2 / D3
2. **Do conference or congregation photographs exist?** This now also affects the leadership
   modal: no bios and no photography are the content gaps thinning several surfaces.

#### Next unblocked
T49 (department hues, type scale), T67 (calendar year hard-coded to 2026), T66 (`/about` live
and fully hard-coded), T70 (drop the orphaned `gallery_items.department` column).

### Iteration 23 — 2026-08-17

#### Completed
- **T67** (`5effcbe`) — calendar year derived from the events, not written into the copy

#### Declined with reason
- **T70 ⛔** — drop `gallery_items.department`. The task's premise is that T21's backfill gave
  every row an owner, so the column is orphaned. It did not: the single gallery row carries
  `"Apostolic Bible College"`, which is not a `departments` row — which is exactly why that row
  became a general-gallery item. **That string is the only surviving record of what the
  photograph is.** Dropping the column destroys it. Safe to remove once ABC has a model of its
  own, or the client confirms the label is disposable.

#### Validation
Lint PASS · Build PASS · Tests **102 passed (246 assertions)** · both year states
browser-verified

#### Learnings
- A year written into prose is a dated assertion with no expiry. Derive it, and **omit it when
  the data cannot support it** — a calendar with no events should not name a year.
- A "safe to drop, nothing consumes it" task deserves checking what the column still *holds*,
  not just who reads it. Orphaned by code is not the same as empty of meaning.

#### Still blocked on the user
1. Homepage direction — D1 / D2 / D3
2. **Do conference or congregation photographs exist?**

#### Next unblocked
T66 (`/about` live, routed, fully hard-coded), T49 (department hues — my palette pass
deliberately left the six theme colours alone because collapsing them into green made two
departments identical), T50/T51 spikes.

### Iteration 25 — 2026-08-17

#### Completed
- **T66** (`7141aca`) — `/about` converted from a hard-coded view to a CMS page

#### Validation
Lint PASS · Build PASS · Tests **102 passed (246 assertions)** · migration up/down clean ·
browser-verified

#### What it actually was
A hub page: 85 lines of hard-coded markup in front of five sub-pages that were **already**
CMS-managed. The hub was the only part of the About section its own editors could not touch.
Content carried across verbatim — the conversion is about who can change it, not about
changing it. `About.vue` deleted, since leaving it implies it still renders the route.

#### A content decision left to the client rather than taken
The General Superintendent block promotes a page whose **menu** link was removed in T44 while
its route was deliberately kept. Whether the hub should still feature it is a judgement call,
so it is carried across as-is — and as CMS content, removing it is now one click rather than
another deploy. Silently dropping it would have been me deciding.

#### Running theme this session
Three tasks in a row turned out to be mis-specified once the code was actually read: T56 (a 404
already existed), T57 (worse than described — the comment contradicted the code), T70 (the
column was not orphaned of meaning). **The plan is a good map but not the territory; check the
premise before executing the task.**

#### Still blocked on the user
1. Homepage direction — D1 / D2 / D3
2. **Do conference or congregation photographs exist?**
3. **Department hues** — which colour each of the six owns. My palette pass left them alone
   because collapsing them into brand green made two departments identical (T49).

#### Next unblocked
T50/T51 spikes, T61 (a real ABC inspect/fix pass), T63 (audit the wider public API surface for
auth/throttle), T71 (§14.3 copy fix).

### Iteration 26 — 2026-08-17

#### Completed
- **T63** (`db5b1e1`) — public API surface audited; `/api/churches` payload trimmed

#### Validation
Lint PASS · Build PASS · Tests **104 passed (251 assertions)** · locator, church detail and
region pages browser-verified after the trim

#### What the audit found
21 public endpoints, all GET except two POSTs. Write verbs genuinely gone; rate limiting
enforced (verified by hammering `/api/contact` to a 429 — `route:list` does not render
group-level middleware, which is why it looks absent).

**`/api/churches` published 31 fields, 17 with no caller — including `email`.** Four of the
nine stored addresses are **personal Gmail accounts belonging to named individuals**, two of
whom appear on the leadership page. Nothing displayed them. Pure exposure on an
unauthenticated, scrapeable endpoint.

Removed: `email`, `leadership`, `is_active`, `is_featured`, `potential_home_group`,
`created_at`, `updated_at`. **Phone kept** — the locator displays it with a `tel:` link, so
this was targeted, not a blanket strip.

#### A test of mine went vacuous and Pest caught it
Removing the `leadership` block left an existing PII test walking a structure that no longer
existed — every loop body skipped, **zero assertions ran**, flagged `risky`. Rewritten to
assert against the raw response, which a later change to the payload shape cannot hollow out.

**This is the vacuous-test problem I have hit repeatedly, and the first time tooling caught it
rather than me.** Pest's risky flag is worth watching for.

#### Cleaned up after myself
Two contact messages created while testing the rate limit were deleted.

#### Left to the client
Whether the directory should offer a contact route at all. If so, the right shape is a
per-church "show email" flag or a relay form — not publishing every stored address by default.

#### Still blocked on the user
1. Homepage direction — D1 / D2 / D3
2. Do conference or congregation photographs exist?
3. Department hues (T49)

### Iteration 28 — 2026-08-17

#### Completed
- **T61** (`d7325ab`) — ABC section audited across four pages at desktop and mobile

#### Validation
Lint PASS · Build PASS · Tests **104 passed (251 assertions)** · all four pages re-verified at
1440 and 390

#### Two real defects
1. **Two of four ABC pages scrolled sideways on a phone.** Facebook's embed code carries a
   fixed pixel width — the live pages ship `width="560"` and `width="500"` — so at 390px they
   ran up to **218px** past the right edge. Fixed in CSS, not content: those widths sit inside
   copy authors paste from Facebook, so a content-side fix needs repeating forever. Video
   plugins also get their 16:9 ratio back, since constraining width alone distorts them.
2. **The Connect page linked twice to `/contact`, which has no route** and rendered the 404.
   That was mine, from `2bbeda9` — the form lives at `/connect-with-us`. Corrected; no other
   reference exists in pages, menu rows or source.

#### 🔴 My own link check gave a false pass
It reported `/contact` fine. It used `domcontentloaded` with a 600ms wait — **not long enough
for an SPA to fetch and render its 404 state**. An impatient check is worse than no check: it
manufactures confidence. Re-run on `networkidle` with a real wait, it failed immediately.

Pairs with the vacuous-test lesson from iteration 26: a check that cannot fail, and a check
that finishes before the thing it measures exists, are the same defect wearing different
clothes.

#### Still blocked on the user
1. Homepage direction — D1 / D2 / D3
2. Do conference or congregation photographs exist?
3. Department hues (T49)

#### Next unblocked
T50/T51 spikes, T71 (§14.3 copy fix + §14.2 constraints), T64 (split T36's seven clauses),
T65 (seed region intros/logos as editable content — partly blocked on client copy).

### Iteration 29 — 2026-08-17

#### Completed (partial by design)
- **T65 ◐** (`e2b5428`) — presbyters linked to their regions. `intro` and `logo_path`
  deliberately left unset.

#### Validation
Lint PASS · Build PASS · Tests **106 passed (254 assertions)** · both region surfaces
browser-verified

#### What was actually wrong
The three presbyter names were already published — inside the leadership page's cards as
"Northern Region Presbyter" and so on — but only as **prose in a CMS block**. `presbyter_name`
was null, so nothing could query who leads a region. Read across from the cards rather than
retyped, so it cannot disagree with its source; a reworded card makes the migration a no-op
rather than writing a stale name.

#### Where I stopped, and why
`intro` is a message **from** the region. Inventing one puts words in a presbyter's mouth on a
public page. The template already omits the section when empty, so an unfilled intro costs
nothing while invented copy would cost credibility.

The user sanctioned placeholders for missing information, and I have used that latitude freely
elsewhere (SBQ/JBQ pages, region lettermarks, empty-state messages). **It does not extend to
attributed speech.** That is the line.

#### Remaining work is thin and mostly gated
42 done, 1 partial, 1 declined. What is left divides into:
- **Blocked on the user**: T45 (homepage hero), T49 (department hues), region intros/logos,
  T55 (assigning 49 events to regions), photography-dependent work
- **Design spikes**: T50 (`/calendar` month grid), T51 (cards under B) — both shaped by the
  homepage direction, so premature
- **Plan hygiene**: T64 (split T36), T71 (§14.3 copy fix)

The feature and security work is substantially complete. Further grinding without the three
outstanding decisions produces churn rather than progress.

### Iteration 31 — 2026-08-17

#### A full-route sweep found two things
Swept all 32 real routes (static routes + published pages + every department and region) for
404s, failed sections, thin bodies, missing h1, horizontal overflow and JS errors.

#### 🔴 The rejected homepage was live again — my failure
Switched in `981a379`, rejected on sight, rolled back with `migrate:rollback`. **That was not
enough.** `migrate:rollback` removes the migration's ROW, returning the file to "not yet run",
so the next `php artisan migrate` for unrelated work re-applied it. It went back silently and
stayed for several iterations. The sweep caught it only because the B homepage has no `h1`.

**Rolling back a migration undoes the DATA; it does not withdraw the INSTRUCTION.** A migration
whose change has been rejected must be emptied, not merely rolled back. Restored from the
`home-previous` archive; migration is now a documented no-op, proven by running `migrate`
three times and on a fresh database.

#### 🔴 The cache lock was worse than flagged — now fixed, not just reported
`/gallery` reported a failed section. Repeating five times: the **user-visible check passed
every time**, but **three of five page loads returned 500s** on `/api/departments`,
`/api/menu/header`, `/api/site-settings`. The site only looked healthy because the navbar
fallback and settings degradation were absorbing them.

`CACHE_STORE=database` on SQLite: the rate limiter writes a counter per API request and
concurrent calls collide. **148 lock errors in the log against 4 cache rows** — contention,
not volume. Changed to `file` in `.env` **and `.env.example`** — the example is the durable
half, tracked, carrying the same default, and would have propagated the fault to every deploy.
Zero 500s after.

#### Learnings
- **A green user-visible check can sit on top of a 60% server error rate.** Watch `response`
  events, not just rendered output — good fallbacks hide the fault they are compensating for.
- Reverting a decision expressed as a migration takes two steps: undo the data AND empty the
  file. One without the other silently re-applies.
- `.env.example` is tracked; a bad default there outlives any local fix.

#### Sweep now clean across all 32 routes.

### Iteration 32 — 2026-08-17

#### The plan's status was understating reality by fifteen tasks
T0–T12, T37, T58 and T62 were completed early in the session, before status markers were in
use, and were never marked. **Each verified against the code, not memory** — the database is
absent from git history, upload disks are set, `site_settings` exists, regions are renamed,
`events.scope` exists, the sanitiser is in place.

#### True position
**59 done · 2 partial · 2 declined with reasons · 9 remaining.**

#### Four decisions recorded rather than left looking unfinished
- **T43 ✅** was never blocked — all four `forms.gle` URLs already matched the live site.
- **T60 ⛔** declined. "Detail links *if relevant*" is not relevant for two ~600-character
  announcements rendered in full. Nobody is truncated, so nobody needs a read-more. Revisit if
  announcements grow long enough to excerpt.
- **T68 ✅** resolved by the footer rebuild. The three `href="#"` legal links are gone and the
  copyright derives from the clock. Settings columns pointing at pages that do not exist would
  be premature.
- **T5 ◐ / T65 ◐** partial by choice — the missing halves are client content that should not be
  invented (a stale address whose blanking degrades the listing twice over; a message *from* a
  region).

#### Of the 9 remaining, 8 are gated
- **Homepage direction** → T45, T50, T51, T54
- **Department hues** → T49, T69
- **Client data** → T55 (assigning 49 events to regions)
- **Plan hygiene only** → T64, T71

#### This is a genuine boundary
The feature, security and correctness work is substantially complete: all 11 original
requirements addressed, gate at 106 passing, a clean sweep across all 32 routes, and the
live-site defects found along the way fixed. What remains needs three decisions that are the
client's to make. Continuing to generate commits without them would be churn.

### Iteration 34 — 2026-08-17 — plan exhausted of unblocked work

#### Completed
- **T71 ✅** — both halves. §14.2 audit found shipped surfaces clean; §14.3 copy reworded.
- **T64 ⛔** — obsolete. It asks to split T36, which is finished. Splitting a completed task is
  bookkeeping, not work. The observation was sound when written and is recorded for the next
  plan author.

#### Final position
**60 done · 2 partial · 3 declined with reasons · 7 remaining.**

Every one of the 7 waits on a decision only the client can make:

| Waiting on | Tasks |
|---|---|
| Homepage direction (D1 / D2 / D3) | T45, T50, T51, T54 |
| Which hue each department owns | T49, T69 |
| Whether the 49 events belong to regions | T55 |

#### State of the work
All 11 original requirements addressed. Gate 106 passing (254 assertions). Full-route sweep
clean across 32 routes. Migration chain reverses cleanly. Full suite still at exactly the 16
pre-existing Livewire starter-kit failures it began with — none added.

Beyond the plan, this session also fixed live defects it did not contain: an unauthenticated
church DELETE, a public database in a public repo, personal email addresses on an
unauthenticated endpoint, mobile overflow on the ABC pages, a rejected homepage silently
re-applying itself, and a cache misconfiguration returning 500s on roughly 60% of page loads.

**No further progress is available without those three decisions.** Continuing would be churn.

#### 🔴 The sweep's `/gallery` failure was the sweep's fault, not the site's
The full-route sweep kept flagging `/gallery` as "section failed to load", and it reproduced
consistently — so it looked like a real defect. It was not.

Measured: **one sweep fires 132 API requests, 47 of them rate-limited, with the first 429 at
route #15.** The site rate-limits at 60/minute and each page makes roughly six API calls, so an
unpaced sweep blows through the limit a third of the way in. `/gallery` sits late enough in the
route order to take the hit, and `BlockState` correctly showed its error state for a 429.

So the site was behaving correctly — refusing an abusive request rate — and my harness reported
that correct behaviour as a page failure. Sweep now paced.

**This is the third time this session my own verification tooling produced a misleading
result**, and the pattern is worth naming:
1. A test that could not fail (Pest's `risky` flag caught it)
2. A check that finished before the thing it measured existed (false pass on a dead link)
3. A check whose own load broke what it was measuring (this one)

All three manufactured confidence rather than earning it, and two of them pointed at
non-existent defects while the first hid a real one. **Verification code deserves the same
scepticism as the code it verifies.**
