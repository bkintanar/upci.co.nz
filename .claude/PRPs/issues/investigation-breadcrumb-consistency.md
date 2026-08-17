# Investigation: Breadcrumbs appear on 5 of 19 routes, following the renderer rather than a rule

**Issue**: free-form report — "some pages have breadcrumbs, some doesn't. I think only the landing page should not have it no?"
**Type**: BUG
**Investigated**: 2026-08-17

### Assessment

| Metric | Value | Reasoning |
|---|---|---|
| **Severity** | **MEDIUM** | Orientation defect on 14 of 19 routes, including nested pages where the trail is the only "up" affordance — but nothing is broken or lost, and the browser back button is a workaround |
| **Complexity** | **MEDIUM** | The fix itself is 3 files, but it moves a component from per-view to global, which means touching the title channel it depends on and preserving a 404 behaviour that currently works by accident |
| **Confidence** | **HIGH** | Root cause is a single line, and presence/absence was measured in a real browser across all 19 routes rather than inferred |

---

## Problem Statement

`<Breadcrumb>` is rendered in exactly one place — `CmsPage.vue:61`. Because the router points
different paths at nine different view components, breadcrumb presence follows **which component
renders a route**, not any editorial rule. Measured in a browser: **5 of 19 routes show a
breadcrumb, 14 do not.** The trail also breaks *mid-path*, so going deeper into the site can gain
a breadcrumb while its own parent has none.

**The reporter's proposed rule is correct** — and the component already implements it. See
"The rule is already written" below.

---

## Analysis

### Evidence Chain

**WHY** do some pages have breadcrumbs and others not?

↓ **BECAUSE** the breadcrumb is rendered inside one view component, not in the layout.
Evidence: `resources/js/views/CmsPage.vue:61` — the **only** render site in the codebase:
```vue
<Breadcrumb v-if="page" :current="page.title" />
```
`grep -rn "Breadcrumb" resources/js` returns exactly three hits, all in `CmsPage.vue`
(render, import, registration) plus the component's own definition.

↓ **BECAUSE** the router points 19 paths at **nine** different components, and only `CmsPage`
carries the breadcrumb.
Evidence: `resources/js/router/routes.js` —

| Renders breadcrumb | Does not |
|---|---|
| `CmsPage.vue` (11 routes) | `GetInvolved.vue`, `Department.vue`, `Region.vue`, `Regions.vue`, `Gallery.vue`, `ChurchLocator.vue`, `Events.vue`, `Calendar.vue`, `AgsUpdates.vue`, `ConnectWithUs.vue`, `GeneralSuperintendent.vue` |

↓ **ROOT CAUSE**: a cross-cutting layout concern was placed in a leaf component. Any route not
served by that component silently has no breadcrumb, and any view added later inherits the same
gap by default.
Evidence: `resources/js/App.vue:1-10` is the layout that *every* route passes through, and it has
no breadcrumb:
```vue
<div id="app" class="min-h-screen flex flex-col">
    <Navbar />
    <main class="flex-grow">
        <router-view />
    </main>
    <Footer />
</div>
```

### Measured behaviour (browser, 1440px, paced under the rate limiter)

| | Route | Trail rendered |
|---|---|---|
| ❌ | `/` | — (**correct**, see below) |
| ✅ | `/about` | Home › About UPCI New Zealand |
| ✅ | `/about/leadership` | Home › About › Leadership |
| ❌ | `/about/general-superintendent` | — ← **nested child of `/about`; its sibling has one** |
| ❌ | `/departments` | — |
| ❌ | `/departments/youth` | — |
| ✅ | `/departments/youth/sbq` | Home › Departments › Youth › SBQ |
| ✅ | `/departments/childrens/jbq` | Home › Departments › Childrens › JBQ |
| ❌ | `/regions`, `/regions/northern` | — |
| ❌ | `/find-church`, `/events`, `/calendar`, `/gallery` | — |
| ❌ | `/connect-with-us`, `/get-involved`, `/ags-updates` | — |
| ✅ | `/apostolic-bible-college/enrollment` | Home › Apostolic Bible College › Register |
| ❌ | `/this-does-not-exist` (404) | — (**correct**, see risks) |

**The most concrete symptom:** `/departments/youth/sbq` renders
`Home › Departments › Youth › SBQ`, and **both** intermediate crumbs link to pages that have no
breadcrumb of their own. A visitor gains orientation by going deeper and loses it by going up.

### The rule is already written

The reporter asks whether only the landing page should be excluded. That rule is **already
implemented inside the component** and needs no new logic:

Evidence: `resources/js/components/layout/Breadcrumb.vue:2`
```vue
<nav v-if="crumbs.length > 1" class="border-b border-brand-grey-200" aria-label="Breadcrumb">
```
`crumbs` always begins with `{ path: '/', label: 'Home' }` and appends one entry per path
segment (`Breadcrumb.vue:66-88`). At `/` there are no segments, so `crumbs.length === 1` and the
nav hides itself. **On every other route it is ≥ 2 and shows.**

So the fix is not "add a rule". It is "put the component somewhere every route passes through,
and let the rule it already has do the work."

### A second defect found while investigating (same shape, same cause)

Five views never set a document title, so the tab, bookmarks and shared links all read the
static blade title. Measured:

| Route | `document.title` |
|---|---|
| `/events` | `UPCI - New Zealand` ← unset |
| `/calendar` | `UPCI - New Zealand` ← unset |
| `/connect-with-us` | `UPCI - New Zealand` ← unset |
| `/ags-updates` | `UPCI - New Zealand` ← unset |
| `/about/general-superintendent` | `UPCI - New Zealand` ← unset |
| `/about/leadership` | `Leadership - UPCI New Zealand` ✓ |

Cause is identical: `usePageMeta` is a shared composable called **per view**, and 5 of 12 views
don't call it. Evidence: `grep -rln usePageMeta resources/js` returns `ChurchLocator`,
`GetInvolved`, `Gallery`, `Regions`, `Department`, `CmsPage`, `Region` — and no others.

This matters to the breadcrumb fix because **`usePageMeta` is the existing owner of "the human
title of the current page"**, which is exactly what the breadcrumb's last crumb needs.

### Affected Files

| File | Lines | Action | Description |
|---|---|---|---|
| `resources/js/composables/usePageMeta.js` | 1, 76-110 | UPDATE | Publish the current title (and a suppression flag) as module-level refs |
| `resources/js/App.vue` | 3-7 | UPDATE | Render `<Breadcrumb>` once, inside `<main>`, above `<router-view />` |
| `resources/js/views/CmsPage.vue` | 61, 293, 308 | UPDATE | Remove the local render, import and registration; set the suppression flag on error |
| `resources/js/views/Events.vue` | — | UPDATE | Call `usePageMeta` (fixes title **and** breadcrumb leaf) |
| `resources/js/views/Calendar.vue` | — | UPDATE | Same |
| `resources/js/views/ConnectWithUs.vue` | — | UPDATE | Same |
| `resources/js/views/AgsUpdates.vue` | — | UPDATE | Same |
| `resources/js/views/GeneralSuperintendent.vue` | — | UPDATE | Same |

### Integration Points

- `App.vue` wraps every route — the single place a cross-cutting layout concern belongs
- `Breadcrumb.vue` reads `useRoute()` directly, so it needs **no** props to build the trail; the
  `current` prop only improves the leaf label
- `usePageMeta.onBeforeUnmount` (`:101-107`) already resets on navigation — the new refs must
  reset in the same hook or a stale leaf label survives one route change
- `CmsPage.vue:62` `PageHeader v-if="page && !hasHero && !isHome"` is a **separate** concern and
  must not be touched

### Git History

- **Introduced**: `216ba89` — "Port the breadcrumb, page header and contents patterns (T48)" —
  the only commit to ever touch `Breadcrumb.vue`
- **Implication**: **not a regression.** The component was correct on the day it landed; T48
  scoped it to `CmsPage` because that was the view being fixed, and no task ever revisited the
  other eight view components. This is an original gap, which is why it has never looked broken
  in any single page's review — it is only visible by moving between pages.

---

## Implementation Plan

### Step 1: Publish the current page title from `usePageMeta`

**File**: `resources/js/composables/usePageMeta.js` · **Action**: UPDATE

**Current** (`:1`, and the export at `:76`):
```js
import { onBeforeUnmount } from 'vue'
```

**Required change** — add module-level reactive state and set it inside `setPageMeta`:
```js
import { onBeforeUnmount, ref } from 'vue'

/**
 * The current page's human title, and whether the breadcrumb should be
 * suppressed. Module-level so the app-wide breadcrumb can read the same title
 * this composable already computes, rather than a second mechanism deriving it
 * from the slug — "apostolic-bible-college" is not "Apostolic Bible College".
 *
 * Suppression exists for one case: a page that does not exist must not assert a
 * trail to itself. On a 404 the crumbs would otherwise read
 * "Home › This Does Not Exist", naming a page that was never there.
 */
export const currentPageTitle = ref(null)
export const breadcrumbSuppressed = ref(false)
```

Inside `setPageMeta`, after `document.title` is computed:
```js
// The leaf crumb wants the bare title, without the site-name suffix several
// CMS titles carry — the same strip Breadcrumb.vue:82 was doing locally.
currentPageTitle.value = trimmed ? trimmed.split(/\s+[-|]\s+/)[0].trim() : null
```

In the existing `onBeforeUnmount` (`:101-107`), alongside the title reset:
```js
currentPageTitle.value = null
breadcrumbSuppressed.value = false
```

**Why**: gives the global breadcrumb the one label a route path cannot supply, from the
mechanism that already owns page titles. No second source of truth.

---

### Step 2: Render the breadcrumb once, in the layout

**File**: `resources/js/App.vue` · **Action**: UPDATE

**Current** (`:1-10`):
```vue
<div id="app" class="min-h-screen flex flex-col">
    <Navbar />
    <main class="flex-grow">
        <router-view />
    </main>
    <Footer />
</div>
```

**Required change**:
```vue
<div id="app" class="min-h-screen flex flex-col">
    <Navbar />
    <main class="flex-grow">
        <!-- One breadcrumb for every route. It lived in CmsPage.vue, which is
             one of nine view components, so 14 of 19 routes silently had none
             and every new view inherited the gap. The component hides itself at
             the root (crumbs.length > 1), so the "no breadcrumb on the landing
             page" rule needs no condition here. -->
        <Breadcrumb v-if="!breadcrumbSuppressed" :current="currentPageTitle" />
        <router-view />
    </main>
    <Footer />
</div>
```

Script additions:
```js
import Breadcrumb from './components/layout/Breadcrumb.vue'
import { currentPageTitle, breadcrumbSuppressed } from './composables/usePageMeta'

export default defineComponent({
    name: 'App',
    components: { Navbar, Footer, Breadcrumb },
    setup() {
        return { currentPageTitle, breadcrumbSuppressed }
    },
})
```

**Why**: `App.vue` is the only component every route passes through, so this structurally
prevents the bug recurring when the next view is added.

---

### Step 3: Remove the local breadcrumb from `CmsPage` and suppress on error

**File**: `resources/js/views/CmsPage.vue` · **Action**: UPDATE

- **Delete** `:61` `<Breadcrumb v-if="page" :current="page.title" />`
- **Delete** the import (`:293`) and the `components` entry (`:308`)
- **Keep** `:62` `PageHeader` exactly as it is — different concern
- In the error path (where `error.value` is set / `isMissing` becomes true), set
  `breadcrumbSuppressed.value = true`; clear it on a successful load

**Why**: two breadcrumbs would otherwise render on CMS pages. The suppression flag preserves a
behaviour that currently works only as a side effect of `v-if="page"` — make it deliberate.

---

### Step 4: Give the five title-less views a title

**Files**: `Events.vue`, `Calendar.vue`, `ConnectWithUs.vue`, `AgsUpdates.vue`,
`GeneralSuperintendent.vue` · **Action**: UPDATE

Mirror the existing call sites exactly. Pattern from a current consumer:
```js
// SOURCE: resources/js/views/Regions.vue (and 6 others)
import { usePageMeta } from '../composables/usePageMeta'
// …inside setup():
const { setPageMeta } = usePageMeta()
onMounted(() => setPageMeta('Regions', '…'))
```

Suggested titles: `Calendar of Events`, `Month Calendar`, `Connect with Us`, `AGS Updates`,
`General Superintendent`.

**Why**: fixes the tab/bookmark defect **and** supplies a correct breadcrumb leaf. Without it
these five fall back to a humanised slug — `/connect-with-us` would read "Connect With Us"
rather than "Connect with Us".

---

## Patterns to Follow

**The self-hiding root rule — do not reimplement it:**
```vue
<!-- SOURCE: resources/js/components/layout/Breadcrumb.vue:2 -->
<nav v-if="crumbs.length > 1" class="border-b border-brand-grey-200" aria-label="Breadcrumb">
```

**Trail derivation is already route-driven — no props needed for the path:**
```js
// SOURCE: resources/js/components/layout/Breadcrumb.vue:66-88
const segments = route.path.split('/').filter(Boolean)
const trail = [{ path: '/', label: 'Home' }]
segments.forEach((segment, index) => { /* … */ })
```

**Existing per-view meta call (mirror for Step 4):**
```js
// SOURCE: resources/js/views/Department.vue / Regions.vue / Gallery.vue
const { setPageMeta } = usePageMeta()
```

---

## Edge Cases & Risks

| Risk / Edge case | Mitigation |
|---|---|
| **404 would claim a trail** — `Home › This Does Not Exist` names a page that never existed. Currently avoided only as a side effect of `v-if="page"` | Step 3's explicit `breadcrumbSuppressed` flag. **Test `/this-does-not-exist` after the change** |
| **Two breadcrumbs on CMS pages** if Step 3 is skipped | Steps 2 and 3 must land in the same commit |
| **Stale leaf label** surviving one navigation | Reset both refs in the existing `onBeforeUnmount` (`:101-107`), not in a new hook |
| **Dynamic routes** — `/regions/:slug`, `/departments/:slug` — leaf from slug reads "Northern" not "Northern Region" | Those views already call `usePageMeta`, so the real title flows through. Verify `/regions/northern` |
| **Breadcrumb above a full-bleed hero** — CMS pages with a `hero` block previously had the crumb bar above it; that is unchanged, but views like `Department.vue` have their own coloured hero and will now get a bar above it | Visual check on `/departments/youth` and `/regions/northern`. This is a **new** visual state — screenshot before/after |
| Trail label case differs from the on-page `h1` (e.g. "Connect With Us" vs "Connect with Us") | Step 4 removes the humanised fallback for the five worst cases |
| `usePageMeta` is imported by `App.vue`, creating a layout→composable dependency | Acceptable: it is a composable, not a view, and already has no view dependencies |

---

## Validation

### Automated

```bash
./vendor/bin/pint --test --dirty
npm run build
php artisan test        # expect 117 passed / 16 pre-existing failures, none new
```

### Browser (the only way this bug is visible)

```bash
# Pace at >=6.5s per route: ~6 API calls per page against a 60/min limiter.
# Under-pacing produces 429s that look like page failures.
cd /tmp/claude-0 && node bc.mjs
```

Expected after the fix — **18 of 19 routes show a trail; two do not**:

- [ ] `/` → **no** breadcrumb (the reported rule)
- [ ] `/this-does-not-exist` → **no** breadcrumb (404 must not assert a trail)
- [ ] All 17 others → a trail beginning `Home ›`
- [ ] `/about/general-superintendent` → `Home › About › General Superintendent`
- [ ] `/departments/youth` → `Home › Departments › Youth` (the mid-path gap closed)
- [ ] `/events` → `Home › Calendar of Events`, and `document.title` no longer `UPCI - New Zealand`
- [ ] Exactly **one** `nav[aria-label="Breadcrumb"]` per page (no duplicate on CMS pages)
- [ ] No horizontal overflow at 390px on any route
- [ ] Intermediate crumbs are clickable and land on the right page

---

## Scope Boundaries

**IN SCOPE**
- Moving `<Breadcrumb>` from `CmsPage.vue` to `App.vue`
- Publishing the current title + suppression flag from `usePageMeta`
- Adding `usePageMeta` to the five views that lack it

**OUT OF SCOPE — do not touch**
- `PageHeader` and its `!hasHero && !isHome` condition (`CmsPage.vue:62`) — separate concern
- The breadcrumb's **visual design**. It keeps its current brand-token treatment; restyling
  belongs to `brand-consistency-follow-ups.plan.md`
- The stock colour tokens in the five views being edited — same plan owns those; touching them
  here would mix a navigation fix with a palette migration
- Server-rendered meta tags for SEO. `usePageMeta` is explicitly client-side
  (`usePageMeta.js:11-14`) and that limitation is unchanged
- Adding a `/departments/youth` → `Youth Ministry` title alias, or any other label rewriting
  beyond the five `setPageMeta` calls

---

## Answer to the reporter's question

> "I think only the landing page should not have it no?"

**Yes — with one addition you did not mention: the 404 page.** A page that does not exist should
not render a trail naming itself. Everything else gets one.

Worth knowing: the component **already** implements your rule. It hides itself whenever the trail
has only one entry, which happens on `/` and nowhere else. The bug is not a missing rule; it is
that the component lives inside one of nine view components instead of in the layout every route
passes through — which is also why the next view added would have inherited the same gap.

---

## Metadata

- **Investigated by**: Claude
- **Timestamp**: 2026-08-17
- **Artifact**: `.claude/PRPs/issues/investigation-breadcrumb-consistency.md`
- **GitHub**: not posted — free-form report, no issue number
