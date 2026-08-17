# Feature: Brand consistency follow-ups

## Summary

The site overhaul left the brand migration **partly applied**. Blocks rendered through
`CmsPage.vue` and the homepage now use brand tokens and flat chrome; the standalone Vue views
do not. The result is a site where `/` and `/about/leadership` look designed and `/find-church`
looks like the old one, side by side. This plan finishes the migration across **23 Vue files —
399 stock colour tokens and 64 legacy chrome occurrences** — then resolves three smaller
follow-ups that the overhaul deliberately left open.

## User Story

As a **visitor moving between pages of the UPCI NZ site**
I want **every page to look like it belongs to the same organisation**
So that **the site reads as a national church body rather than a half-finished template**

## Problem Statement

Testable, and each number was measured rather than estimated:

1. **399 stock Tailwind colour tokens** (`slate-*`, `gray-*`, `blue-*`, `emerald-*`, `indigo-*`)
   remain across 23 `.vue` files. The brand spec says in as many words that these are "the
   single biggest reason the UI reads as generic".
2. **64 legacy chrome occurrences** (`shadow-*`, `rounded-xl/2xl`, `hover:-translate-y-*`,
   `hover:scale-*`) remain, so the flat card treatment shipped for the CMS blocks is
   contradicted by every standalone view next to it.
3. **The navbar is `bg-slate-800`** — the most-seen element on the site is a colour that
   appears nowhere in the mark.
4. **Off-brand hues are still live**: `hover:to-indigo-700` (×2) and `from-blue-500` in
   `ChurchLocator.vue`, `border-blue-600` spinners in 3 views, and a whole
   `gradient-indigo` hero style (`from-indigo-600 via-indigo-700 to-purple-800`) in
   `CmsPage.vue:403`. The site has no blue, indigo or purple in its palette.
5. **`text-body` / `text-h2` are defined and consumed by nothing** — 0 usages, absent from all
   5 built stylesheets.
6. **Church directory cards are dead ends** — naming ten congregations is D2's whole premise,
   and none of the names is clickable.

## Solution Statement

Collapse every neutral onto the **five authored neutrals the brand spec already defines**
(`ink`, `paper`, `grey-600/400/200`), replace legacy chrome with the flat treatment already
shipped in `CmsPage.vue:511`, and delete the off-brand hues outright. Then resolve the type
scale, the greyscale question and the church detail links as separately-gated items.

The collapse is **not** a find-and-replace: 19 distinct grey weights map onto 5 tokens, so each
mapping is a judgement about contrast, and contrast must be verified rather than assumed.

## Metadata

| Field | Value |
|---|---|
| Type | REFACTOR (Phases 1-2) + ENHANCEMENT (Phase 4) |
| Complexity | **MEDIUM-HIGH** — mechanically simple, high blast radius, contrast-sensitive |
| Systems Affected | All 23 Vue views/components, `tailwind.config.js`, `ChurchController`, router |
| Dependencies | Tailwind 3.4, Vue 3.5.22, vue-router 4.5.1, Playwright 1.62.1 (verification) |
| Estimated Tasks | 18 |

---

## UX Design

### Before State

```
╔══════════════════════════════════════════════════════════════════════════╗
║  A visitor moves through the site and the design changes under them.     ║
╠══════════════════════════════════════════════════════════════════════════╣
║                                                                          ║
║   ┌────────────────┐      ┌────────────────┐      ┌──────────────────┐  ║
║   │  /  (home)     │ ───► │ /find-church   │ ───► │ /about/leadership│  ║
║   │  ✅ brand      │      │  ❌ slate-800  │      │  ✅ brand         │  ║
║   │  ✅ flat cards │      │  ❌ rounded-2xl│      │  ✅ flat cards    │  ║
║   │  ✅ paper bg   │      │  ❌ shadow-xl  │      │                   │  ║
║   │                │      │  ❌ indigo-700 │      │                   │  ║
║   │                │      │  ❌ blue-500   │      │                   │  ║
║   └────────────────┘      └────────────────┘      └──────────────────┘  ║
║          ▲                        ▲                        ▲            ║
║          └────────── same navbar: bg-slate-800 ────────────┘            ║
║                                                                          ║
║   PAIN: the redesign is visibly half-applied. The locator — the page     ║
║         D2 sends every visitor to — is the worst offender: 73 stock      ║
║         tokens and 15 chrome occurrences.                                ║
║   PAIN: ten congregations are named on the homepage; none is clickable.  ║
╚══════════════════════════════════════════════════════════════════════════╝
```

### After State

```
╔══════════════════════════════════════════════════════════════════════════╗
║  One palette, one chrome, one system.                                    ║
╠══════════════════════════════════════════════════════════════════════════╣
║                                                                          ║
║   ┌────────────────┐      ┌────────────────┐      ┌──────────────────┐  ║
║   │  /  (home)     │ ───► │ /find-church   │ ───► │ /about/leadership│  ║
║   │  ✅ brand      │      │  ✅ brand      │      │  ✅ brand         │  ║
║   │  ✅ flat       │      │  ✅ flat       │      │  ✅ flat          │  ║
║   └───────┬────────┘      └────────────────┘      └──────────────────┘  ║
║           │                        ▲                                     ║
║           │  church name clicked   │                                     ║
║           └────────────────────────┘  ◄── NEW: names are links           ║
║                                                                          ║
║           └────────── navbar: brand-ink ──────────┘                     ║
║                                                                          ║
║   VALUE: no page contradicts the one before it.                          ║
║   VALUE: D2's promise completes — naming a church now leads somewhere.   ║
╚══════════════════════════════════════════════════════════════════════════╝
```

### Interaction Changes

| Location | Before | After | User Impact |
|---|---|---|---|
| `components/Navbar.vue` | `bg-slate-800` | `bg-brand-ink` | Site-wide: the most-seen element joins the brand |
| `views/ChurchLocator.vue` | 73 stock tokens, 15 chrome, indigo + blue gradients | brand tokens, flat | The page D2 funnels everyone to stops contradicting the homepage |
| `views/Calendar.vue` | `slate-900/600`, `rounded-xl shadow-md` | brand tokens, flat | Month grid matches the site |
| Homepage church names | plain text | link → locator, church open | Naming a church leads somewhere |

---

## Mandatory Reading

| Priority | File | Lines | Why Read This |
|---|---|---|---|
| **P0** | `.claude/design/upci-redesign/brand-spec.md` | 20-45 | The authored palette. **Only 3 grey stops exist by design** — "2 chromatic + 1 neutral ramp". Do not add stops to dodge a hard mapping call |
| **P0** | `resources/js/views/CmsPage.vue` | 494-520 | The flat card treatment already shipped. **MIRROR THIS EXACTLY** |
| **P0** | `resources/js/utils/theme.js` | 1-45 | `surface()` / `button()` / `errorClasses()` already return brand class bundles. Reuse rather than re-derive |
| **P1** | `tailwind.config.js` | 28-80 | Token definitions, and the comment explaining why oklch is authoritative |
| **P1** | `resources/js/components/blocks/ChurchDirectoryBlock.vue` | 1-40 | A component already migrated — the target style |
| **P2** | `.claude/design/upci-redesign/direction-approved.md` | all | D2 is approved; this work is *iteration within* it, not a new direction |

**External Documentation:**

| Source | Section | Why Needed |
|---|---|---|
| [Tailwind 3.4 — Content config](https://tailwindcss.com/docs/content-configuration#dynamic-class-names) | Dynamic class names | JIT scans source as **plain text**; a constructed class is never emitted. This is why `theme.js` holds literal strings |
| [WCAG 2.2 §1.4.3](https://www.w3.org/WAI/WCAG22/Understanding/contrast-minimum.html) | Contrast minimum | Collapsing 19 greys to 3 changes contrast ratios. 4.5:1 body, 3:1 large text |
| [oklch.com](https://oklch.com) | — | To check a mapping's perceptual lightness before committing it |

---

## Patterns to Mirror

**FLAT CARD — the target chrome (already shipped, do not reinvent):**
```js
// SOURCE: resources/js/views/CmsPage.vue:511-518
// This was `rounded-xl shadow-lg hover:shadow-xl hover:-translate-y-2` —
// the lifting card every Tailwind-era site shipped 2020-2024.
// Flat, bordered, square. Hover moves the border, not the card.
return 'group bg-white p-8 border border-brand-grey-200 hover:border-brand-green-700 transition-colors'
```

**SURFACE BUNDLES — prefer these over hand-picking tokens:**
```js
// SOURCE: resources/js/utils/theme.js:48-84
export function surface(kind) {
    switch (kind) {
        case 'panel':
            return {
                surface: 'bg-white border border-brand-grey-200',
                heading: 'text-brand-ink',
                body: 'text-brand-ink',
                muted: 'text-brand-grey-600',
                rule: 'border-brand-grey-200',
            }
        // …
    }
}
```

**ALREADY-MIGRATED COMPONENT — the reference implementation:**
```vue
<!-- SOURCE: resources/js/components/blocks/ChurchDirectoryBlock.vue:1-12 -->
<section class="py-16 bg-brand-paper">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <h2 v-if="data.heading" class="text-3xl font-bold text-brand-ink mb-8">{{ data.heading }}</h2>
```

**LITERAL CLASS STRINGS — non-negotiable:**
```js
// SOURCE: resources/js/utils/theme.js:1-9
// Every class here is a LITERAL string. Tailwind's JIT scanner reads source
// files as plain text, so a constructed class like `bg-brand-green-${weight}`
// is never emitted into the stylesheet and silently renders unstyled.
```

**API ORDERING — the leftJoin pattern, if Phase 4 touches the endpoint:**
```php
// SOURCE: app/Http/Controllers/Api/ChurchController.php
// leftJoin, not join: churches with no region must still be returned.
$churches = $query
    ->leftJoin('regions', 'churches.region_id', '=', 'regions.id')
    ->orderByRaw('CASE WHEN regions.sort_order IS NULL THEN 1 ELSE 0 END')
    ->orderBy('regions.sort_order')
    ->select('churches.*')
```

---

## The neutral mapping (the one real design decision in Phase 1)

19 distinct grey weights are in use. The brand spec authorises **5 neutrals**. This table is
the contract — apply it, do not improvise per file.

| Current (count) | → | Brand token | Rationale |
|---|---|---|---|
| `gray-900` (58), `slate-900` (43), `slate-800` (4), `slate-700` (25), `gray-700` (3) | → | `brand-ink` | All are "the darkest thing on the page". One near-black, not five |
| `gray-600` (49), `slate-600` (30), `slate-500` (40), `gray-500` (17) | → | `brand-grey-600` | Secondary/muted text. **Verify 4.5:1 on `brand-paper`** |
| `slate-400` (13), `gray-400` (1) | → | `brand-grey-400` | Tertiary — timestamps, "not on the map yet" |
| `slate-300` (10), `slate-200` (24), `gray-300` (1), `gray-200` (1) | → | `brand-grey-200` | Borders and rules only |
| `slate-100` (15), `slate-50` (18), `gray-100` (7), `gray-50` (16) | → | `brand-paper` *or* `bg-white` | Page ground vs panel ground — decide **per use**, not globally |
| `blue-*`, `indigo-*`, `emerald-*`, `purple-*` | → | **delete** | Not in the palette. Spinners → `border-brand-green-700`; `gradient-indigo` → retire the style |

> ⚠️ **The last row is the risky one.** `slate-50`/`gray-50` is used both as a page background
> (→ `brand-paper`) and as a panel fill against a white page (→ stays `bg-white`, with the
> border doing the work). Collapsing both to one token flattens the figure/ground
> relationship. Judge each occurrence.

---

## Files to Change

| File | Action | Stock tokens | Chrome | Notes |
|---|---|---|---|---|
| `views/ChurchLocator.vue` | UPDATE | 73 | 15 | **Largest.** Do first — highest traffic under D2 |
| `views/GetInvolved.vue` | UPDATE | 31 | 1 | |
| `components/Navbar.vue` | UPDATE | 23 | 3 | **Site-wide visibility.** `bg-slate-800` → `bg-brand-ink` |
| `views/Region.vue` | UPDATE | 21 | 3 | |
| `views/Department.vue` | UPDATE | 19 | 3 | |
| `views/CmsPage.vue` | UPDATE | 18 | 4 | Includes retiring `gradient-indigo` (`:403`) |
| `views/Calendar.vue` | UPDATE | 16 | 1 | Follow-up item #4 |
| `views/ConnectWithUs.vue` | UPDATE | 13 | 1 | |
| `views/Events.vue` | UPDATE | 11 | 1 | |
| `views/AgsUpdates.vue` | UPDATE | 10 | 1 | |
| `components/Footer.vue` | UPDATE | 10 | 1 | |
| `views/Regions.vue` | UPDATE | 9 | 2 | |
| `components/blocks/EventsFeedBlock.vue` | UPDATE | 7 | 1 | |
| `views/Gallery.vue` | UPDATE | 6 | 0 | |
| `components/blocks/RegionListBlock.vue` | UPDATE | 5 | 2 | |
| `components/blocks/DepartmentListBlock.vue` | UPDATE | 5 | 1 | |
| `components/GalleryGrid.vue` | UPDATE | 4 | 1 | |
| `components/BlockState.vue` | UPDATE | 4 | 0 | |
| `tailwind.config.js` | UPDATE | — | — | Phase 2 only: wire or remove `text-body`/`text-h2` |
| `router/routes.js` | UPDATE | — | — | Phase 4 only |

---

## NOT Building (Scope Limits)

- **No layout changes.** This is palette and chrome only. If a page's structure is wrong, that
  is a separate design task with its own direction gate.
- **No new brand tokens.** The spec authorises 5 neutrals; adding a `grey-800` to dodge a hard
  mapping call defeats the exercise. If a mapping genuinely cannot work, raise it — do not
  quietly extend the ramp.
- **No greyscale on leadership portraits** (follow-up #3) unless explicitly approved — Phase 3
  below.
- **No type scale application** without a decision on which scale — Phase 2 below.
- **No church `slug` column** unless Phase 4 chooses the slug route over the id route.
- **No touching the 16 pre-existing Livewire starter-kit test failures.** Out of scope, and
  they predate all of this.

---

## Step-by-Step Tasks

### Phase 1 — Palette and chrome (no decisions needed; D2 is already approved)

> **Design gate:** this phase is *iteration within an approved direction*, applying
> `brand-spec.md` and the D2 decision already recorded in `direction-approved.md`. It does
> **not** need a new three-direction gate. Phases 2 and 3 do.

**Task 1: Baseline the current state**
- **ACTION**: Capture reference screenshots of all affected routes BEFORE any change
- **IMPLEMENT**: Playwright, 1440 and 390, full-page, into `/tmp/claude-0/before/`
- **WHY**: A palette migration has no failing test. Before/after images are the only way to
  catch a mapping that destroys a figure/ground relationship
- **VALIDATE**: One PNG per route per width exists

**Task 2: UPDATE `resources/js/components/Navbar.vue`**
- **ACTION**: Migrate 23 stock tokens + 3 chrome occurrences
- **IMPLEMENT**: `bg-slate-800` → `bg-brand-ink`; `border-slate-700` → `border-brand-grey-600`;
  dropdown `rounded-xl shadow-xl` (`:32`) → `border border-brand-grey-200`;
  `group-hover:scale-105` on the logo (`:9`) → remove
- **MIRROR**: `CmsPage.vue:511`
- **GOTCHA**: The navbar is on every page — a mistake here is a mistake everywhere. Verify the
  3-level menu still renders and dropdowns still open
- **VALIDATE**: `npm run build`, then browser-check a dropdown opens and menu items are legible

**Task 3: UPDATE `resources/js/views/ChurchLocator.vue`**
- **ACTION**: Migrate 73 stock tokens + 15 chrome occurrences
- **IMPLEMENT**: Per the mapping table. Specifically delete `hover:to-indigo-700` (`:139`,
  `:255`) and `from-blue-500` (`:212`); replace `rounded-2xl shadow-xl` panels (`:80`, `:148`,
  `:165`) with `border border-brand-grey-200`; drop `transform hover:scale-105` and
  `hover:-translate-y-1` (`:200`)
- **GOTCHA**: `:201` sets a selected-state class — the selected church must remain visually
  distinct after shadows are removed. Use `border-brand-green-700` + `bg-brand-green-100`
- **GOTCHA**: `.map-container` (`:161`) — Leaflet owns that element's box. Do not restyle it
- **VALIDATE**: Browser — map renders, a church card opens the modal, the modal closes and the
  overlay clears (this exact bug shipped once before)

**Tasks 4-17: UPDATE the remaining 15 files**
- **ACTION**: One task per file, in the descending order of the Files to Change table
- **IMPLEMENT**: The mapping table; replace chrome with the flat treatment
- **MIRROR**: `ChurchDirectoryBlock.vue` for section/card structure
- **SPECIAL — `CmsPage.vue` hero styles (`getHeroClasses`, `:398-408`)**: **do not retire any
  style key — repoint its value.** Measured: `gradient-indigo` is used by **5 published pages**
  (`apostolic-bible-college/enrollment`, `apostolic-bible-college/connect`,
  `departments/youth/sbq`, `departments/childrens/jbq`, `about`), so deleting the key would
  unstyle five heroes. The key is a legacy identifier stored in CMS content; the value is the
  colour — the same "key names the slot, token names the colour" rule used by
  `DEPARTMENT_THEMES`. **This is already half-done**: `gradient-blue`, `solid-blue` and
  `solid-indigo` were repointed to brand values and the other three were left. Finish it:
  `gradient-indigo` (`from-indigo-600 via-indigo-700 to-purple-800`) and `gradient-purple`
  (`from-purple-600 via-purple-700 to-pink-800`) → brand green ramp; **`gradient-slate` matters
  most because it is the `||` fallback for every unrecognised style**. Also replace
  `border-blue-600` spinners with `border-brand-green-700`
- **NO content migration is needed** if the keys are preserved — this is the whole reason to
  repoint rather than retire
- **SPECIAL — `Calendar.vue`**: this is follow-up item #4; the month grid's day cells and
  event chips need brand tokens, and `rounded-xl shadow-md` (`:9`) goes flat
- **VALIDATE (each)**: `npm run build` + browser-render the owning route, 0 console errors

**Task 18: Contrast audit**
- **ACTION**: Verify every migrated text/background pair meets WCAG AA
- **IMPLEMENT**: Playwright `getComputedStyle` over rendered text nodes; compute contrast
  against the resolved background
- **WHY**: The `slate-500`/`gray-500` → `brand-grey-600` collapse *raises* lightness in some
  contexts. 40+49 occurrences ride on that one mapping being safe
- **VALIDATE**: No pair below 4.5:1 for body text, 3:1 for large text. Report every failure
  with file, element and measured ratio

---

### Phase 2 — Type scale (⛔ NEEDS A DECISION BEFORE STARTING)

`text-body` (17px) and `text-h2` (40px) exist in `tailwind.config.js` and are used **nowhere**.
They were added for Direction B, which was rejected. Two honest exits:

| Option | Effect |
|---|---|
| **A — Apply them** | Site-wide type change. Visible on every page. Needs the design gate |
| **B — Remove them** | Deletes dead config. Zero visual change. The scale can return later with a decision behind it |

**Recommendation: B for now, A as a separate design task.** Leaving defined-but-unused tokens
in the config is the specific failure ("wire infrastructure to a real consumer in the same
change") that this codebase has already recorded twice.

- **Task 19 (Option B)**: Remove the `fontSize` block from `tailwind.config.js`
- **VALIDATE**: `grep -r "text-body\|text-h2" resources/` returns 0; `npm run build` passes

---

### Phase 3 — Greyscale leadership portraits (⛔ NEEDS APPROVAL — DO NOT BUILD UNASKED)

T49④ specified `filter:grayscale(1)` on the leadership row. **It has never been shown to the
client.** It desaturates photographs of named people, and five design changes have already been
rejected for landing without review.

- **Do not implement speculatively.** Build it as a *preview* — one screenshot, greyscale
  vs colour, side by side — and ask.
- If approved: `CmsPage.vue:189`, add `grayscale group-hover:grayscale-0 transition-[filter]`
  to the person-variant `<img>`. One line, one file.

---

### Phase 4 — Church detail links (ENHANCEMENT — needs one routing decision)

D2 names ten congregations on the homepage and **none of them is clickable**. That is the
direction's central promise left half-delivered.

**Established facts:**
- `GET /api/churches/{church}` **already exists** (`churches.show`)
- `churches` has **no `slug` column** — only `id`, `name`, `city`

| Option | URL | Cost |
|---|---|---|
| **A — Deep-link the locator** | `/find-church?church=12` | No migration. Locator reads the param on mount and opens that church's modal. **Recommended** — reuses the modal, the map and the data that already work |
| **B — Real detail route** | `/churches/christchurch` | Needs a `slug` column + backfill + a new view. More URLs to keep working forever, for content that is currently 4 fields |

**Recommendation: A.** The locator already owns church detail; B builds a second surface for
the same data.

- **Task 20**: `ChurchLocator.vue` — read `?church=<id>` on mount, select and open that church
- **Task 21**: `ChurchDirectoryBlock.vue` — wrap each card in a `<router-link>` to
  `/find-church?church=<id>`
- **GOTCHA**: The card is currently a `<div>`. Making it a link must keep the "Not on the map
  yet" note readable and must not nest interactive elements
- **VALIDATE**: Click a homepage church name → locator opens with that church's modal already
  showing. Verify with Playwright, including a church that has **no coordinates**

---

## Testing Strategy

### Automated

| Check | Command | Expect |
|---|---|---|
| Lint | `./vendor/bin/pint --test --dirty` | exit 0 |
| Build | `npm run build` | exit 0 |
| Tests | `php artisan test` | 117 passed; **exactly 16 pre-existing failures**, none new |
| Route sweep | Playwright, 21 routes, **6.5s pacing** | 0 page errors, 0 overflow |

> 🔴 **Pace the sweep at ≥6.5s per route.** ~6 API calls per page against a 60/min limiter.
> This harness has produced false failures **twice** by pacing too fast and reporting its own
> 429s as site defects. Compute pacing from the limit; do not guess.

### Manual / visual

- [ ] Before/after screenshots compared for all 18 files' routes at 1440 and 390
- [ ] No page has a lower text contrast than before
- [ ] Selected-church state in the locator is still obvious without shadows
- [ ] Navbar dropdowns still open; 3-level nesting intact
- [ ] Church modal opens **and closes**, overlay clears (regression: this shipped broken once)
- [ ] `grep -r "slate-\|gray-\|blue-\|indigo-\|emerald-" resources/js --include='*.vue'` → 0

### Edge cases

- [ ] A church with **no coordinates** (5 of 10) still lists and still deep-links
- [ ] A department with a **null `color_theme`** still gets a hero (falls back to blue slot)
- [ ] The **404 view** still renders with brand tokens
- [ ] `BlockState` loading/error/empty states all still legible after the collapse
- [ ] Dark UI elements (navbar, footer, department heroes) — white text still passes on `ink`

---

## Validation Commands

```bash
# Level 1 — static
./vendor/bin/pint --test --dirty && npm run build

# Level 2 — tests
php artisan test          # expect 117 passed / 16 pre-existing failures

# Level 3 — no stock tokens survive
grep -rn "slate-[0-9]\|gray-[0-9]\|blue-[0-9]\|indigo-[0-9]\|emerald-[0-9]" \
  resources/js --include='*.vue' | grep -v "brand-dept" | wc -l   # expect 0

# Level 4 — browser sweep (paced!)
cd /tmp/claude-0 && node sweep.mjs    # 21 routes @ 6.5s, expect "no problems found"
```

---

## Acceptance Criteria

- [ ] 0 stock colour tokens remain in `resources/js/**/*.vue`
- [ ] 0 legacy chrome occurrences remain (`shadow-*`, `rounded-xl/2xl`, `translate-y`, `scale-1xx`)
- [ ] No page's text contrast is worse than before; all body text ≥ 4.5:1
- [ ] Route sweep clean across 21 routes, 0 page errors
- [ ] Test suite unchanged at 117 passed / 16 pre-existing failures
- [ ] Phase 2 resolved (tokens wired **or** removed — not left dead)
- [ ] Phase 3 **not built** unless explicitly approved
- [ ] Phase 4: homepage church names lead somewhere

---

## Risks and Mitigations

| Risk | Likelihood | Impact | Mitigation |
|---|---|---|---|
| **Grey collapse destroys figure/ground** — `slate-50` panels on white become invisible | **HIGH** | HIGH | Judge `*-50/100` per occurrence, never globally. Before/after screenshots on every file |
| **Contrast regression** on the 4-way `→ grey-600` merge (136 occurrences) | MED | HIGH | Task 18 computes real ratios from rendered DOM |
| **Locator selected-state becomes invisible** once shadows go | MED | MED | Replace with border + tint, verify visually |
| **A Vue regression invisible to lint/build/tests** | **HIGH** | HIGH | Seven such regressions shipped in the last run. Browser-verify every file's route |
| **Sweep produces false failures** by tripping the rate limiter | **HIGH** | MED | 6.5s pacing, stated in the plan. Has already caused this twice |
| Scope creep into layout changes | MED | MED | "NOT Building" is explicit — palette and chrome only |
| ~~`gradient-indigo` is in use by real CMS content~~ — **measured: it is. 5 published pages.** Rated LOW before checking; it was a certainty | **CERTAIN** | HIGH | **Repoint the value, keep the key.** No content migration. Deleting the key unstyles 5 heroes. `gradient-slate` is also the `\|\|` fallback for every unrecognised style |

---

## Notes

**Why collapse rather than extend the ramp.** 19 grey weights map onto 3 stops, and the obvious
dodge is to add `grey-50/100/300/700/800`. The brand spec deliberately authorises "2 chromatic
+ 1 neutral ramp" — the discipline *is* the design. Nineteen greys is what an unmanaged palette
looks like. If a mapping genuinely cannot work, that is worth raising as a spec question, not
patching around silently.

**Why ChurchLocator goes first.** Under D2, the homepage is a funnel into `/find-church`. It is
also the single worst offender (73 tokens, 15 chrome). Doing it first means the two pages a
visitor sees back-to-back agree with each other, even if the plan stalls afterwards.

**Ordering within Phase 1 is by blast radius, not by size.** Navbar is task 2 despite being
small, because it is on every page and its correctness gates the visual check of every
subsequent task.

**On Phases 2 and 3.** Both are deliberately *not* mechanical work. The type scale needs a
decision about which scale; greyscaling portraits of named people needs a person to say yes.
Neither belongs inside a refactor pass, and both have been left explicitly open rather than
quietly resolved in whichever direction was convenient.
