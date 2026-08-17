# Investigation: The homepage church search navigates but its term is discarded

**Issue**: free-form report — "the find a church near you input box. when clicking search it just
redirects to the find a church locator. doesn't update the search/filter there."
**Type**: BUG
**Investigated**: 2026-08-18

### Assessment

| Metric | Value | Reasoning |
|---|---|---|
| **Severity** | **HIGH** | This is the homepage's primary call to action under the chosen search-led direction, and it fails silently — the visitor is navigated, the box is empty, and all ten churches are returned unfiltered, so nothing signals that their input was thrown away |
| **Complexity** | **LOW** | One file and roughly a dozen lines; the locator already has the filter refs, the watcher and the fetch it needs |
| **Confidence** | **HIGH** | Reproduced end-to-end in a browser, and `git log -S "useRoute"` proves the locator has never read the URL in its history |

---

## Problem Statement

`ChurchFinderBlock` (the homepage search) navigates to `/find-church?search=<term>`, but
`ChurchLocator.vue` never reads `route.query`. The parameter is written into the URL and then
completely ignored: the locator's own search box renders empty and its API call goes out with no
filters at all. **Deep links are equally broken** — pasting `/find-church?search=Christchurch`
does nothing either.

---

## Analysis

### Reproduced, not inferred

Typed "Christchurch" into the homepage box and clicked Search:

| Observation | Result |
|---|---|
| URL after submit | `/find-church?search=Christchurch` ✅ navigation works |
| Locator's search input value | `""` ❌ **empty** |
| API request issued | `/api/churches?` ❌ **no parameters** |
| Direct deep link `/find-church?search=Christchurch` | identical failure — empty box, unfiltered request |

### Evidence Chain

**WHY** does the search term have no effect?

↓ **BECAUSE** the locator never looks at the URL.
Evidence: `grep -n "route.query\|useRoute\|\$route" resources/js/views/ChurchLocator.vue` returns
**one** unrelated hit (`:780`, a Leaflet popup element lookup). `useRoute` is not imported —
`ChurchLocator.vue:386` imports only `defineComponent, ref, computed, onMounted, onUnmounted,
nextTick, watch` from vue, plus `usePageMeta` and Leaflet.

↓ **BECAUSE** its filter state is initialised to empty constants, with nothing seeding it.
Evidence: `ChurchLocator.vue:409-411`
```js
const searchQuery = ref('')
const selectedRegion = ref('')
const selectedServiceDays = ref([])
```

↓ **BECAUSE** the fetch reads only those refs, so on mount they are all empty and no parameters
are appended.
Evidence: `ChurchLocator.vue:508-521`
```js
const params = new URLSearchParams()
if (searchQuery.value) params.append('search', searchQuery.value)
// organizational_region, not region: the latter filters the
// free-text geographic column and would never match a slug.
if (selectedRegion.value) params.append('organizational_region', selectedRegion.value)
if (selectedServiceDays.value.length > 0) { … }
```

↓ **ROOT CAUSE**: the routing contract is one-sided. The block was written to hand its query to
the locator through the URL; the locator was never taught to receive it.
Evidence: `ChurchFinderBlock.vue:49-56` — and note the comment, which asserts the very behaviour
that does not exist:
```js
// Routed rather than posted: the locator reads its filters from the
// URL, so this stays a link the user can bookmark and share.
const submit = () => {
    router.push({
        path: '/find-church',
        query: query.value.trim() ? { search: query.value.trim() } : {},
    })
}
```

**That comment was false when it was written.** It is the third instance of this failure mode in
this codebase — the same shape as `ChurchDirectoryBlock`'s comment claiming the churches API
supplied region ordering (fixed in `0a4b743`), and `Navbar.vue`'s comment claiming a menu
fallback existed while the catch emptied the array (T57). A comment asserting a collaborator's
behaviour is a claim that needs verifying, not documentation.

### Affected Files

| File | Lines | Action | Description |
|---|---|---|---|
| `resources/js/views/ChurchLocator.vue` | 386, 409-411, + a new watcher | UPDATE | Read `route.query` into the filter refs; keep the URL in sync |
| `resources/js/components/blocks/ChurchFinderBlock.vue` | 49-51 | UPDATE | Correct the comment so it describes what is true |

### Integration Points

- `ChurchFinderBlock.vue:52` is the **only** producer of `/find-church` query params. Every other
  link to the route is bare: `CmsPage.vue:335` (404 onward links), `Footer.vue:63`,
  `Navbar.vue:189`, `routes.js:94`
- `ChurchLocator.vue:708` already watches `[searchQuery, selectedRegion, selectedServiceDays]` and
  calls `fetchChurches()` — so **seeding the refs is sufficient**; no new fetch call is needed
- `ChurchLocator.vue:805-820` `onMounted` calls `fetchChurches()` explicitly, after `nextTick()`
- `ChurchLocator.vue:635-640` `clearFilters()` resets all three refs and refetches — it must also
  clear the URL, or the filters return on reload
- `/api/churches` accepts `search`, `organizational_region` and repeated `service_day`
  (`ChurchController` — `search` matches name, city, address and the legacy free-text region)

### Git History

- **`f1ab078`** "Add six data-bound CMS blocks (T30)" — introduced `ChurchFinderBlock` with the
  false comment
- **`8244200`** — last touched the block (added the `isFirst` h1 prop); did not touch `submit`
- **`git log -S "useRoute" -- ChurchLocator.vue`** → **no commits**. The locator has never read the
  URL at any point in its history
- **Implication**: **not a regression.** The block shipped broken on arrival, and the defect only
  became visible when the search-led homepage (`0a4b743`) put this box front and centre as the
  site's main entry point

---

## Implementation Plan

### Step 1: Read the URL into the locator's filters

**File**: `resources/js/views/ChurchLocator.vue` · **Action**: UPDATE

**Current** (`:386`):
```js
import { defineComponent, ref, computed, onMounted, onUnmounted, nextTick, watch } from 'vue'
```
**Required change** — add the router imports:
```js
import { defineComponent, ref, computed, onMounted, onUnmounted, nextTick, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
```

**Current** (`:409-411`, note the unusual 16-space indentation in this file):
```js
                const searchQuery = ref('')
                const selectedRegion = ref('')
                const selectedServiceDays = ref([])
```
**Required change** — seed from the query string:
```js
                const route = useRoute()
                const router = useRouter()

                // A repeated query param arrives as an array and a single one as
                // a string, so normalise before handing it to a ref typed as an
                // array. `filter(Boolean)` drops `?service_day=` with no value.
                const queryList = (value) => [].concat(value ?? []).filter(Boolean)

                // Seeded from the URL, not empty. The homepage's finder hands its
                // term over as `?search=`, and this is the half of that contract
                // that was missing — the block has always pushed the param and
                // the locator never read it.
                //
                // Seeded here in setup() rather than in onMounted: assigning a
                // ref's INITIAL value does not fire the watcher at :708, so the
                // single fetchChurches() in onMounted picks these up and there is
                // no duplicate request.
                const searchQuery = ref(typeof route.query.search === 'string' ? route.query.search : '')
                const selectedRegion = ref(typeof route.query.region === 'string' ? route.query.region : '')
                const selectedServiceDays = ref(queryList(route.query.service_day))
```

**Why**: the locator already watches these three refs and already fetches on mount, so seeding
them is the whole fix for the reported symptom.

**GOTCHA**: use `region` as the public query name, not `organizational_region`. The latter is the
API's internal parameter name; `ChurchDirectoryBlock` already accepts `region` as its authored
option. Keep the URL a public contract and the API name an implementation detail.

---

### Step 2: Keep the URL in sync, so the comment becomes true

**File**: `resources/js/views/ChurchLocator.vue` · **Action**: UPDATE

Beside the existing filter watcher (`:708-710`):
```js
watch([searchQuery, selectedRegion, selectedServiceDays], () => {
    fetchChurches()
})
```
add a URL writer:
```js
// Mirror the filters back into the URL so the locator's own state is
// shareable and survives a reload — which is what routing was chosen for in
// the first place. `replace`, not `push`: typing in a search box should not
// stack a history entry per keystroke.
watch([searchQuery, selectedRegion, selectedServiceDays], () => {
    const query = {}
    if (searchQuery.value) query.search = searchQuery.value
    if (selectedRegion.value) query.region = selectedRegion.value
    if (selectedServiceDays.value.length) query.service_day = selectedServiceDays.value

    router.replace({ path: '/find-church', query })
})
```

**Why**: makes the bookmarkability claim real, and makes `clearFilters()` clear the URL for free —
it resets the three refs, which fires this watcher and writes an empty query.

**GOTCHA — do NOT add a `watch(() => route.query, …)` that writes back into the refs.** With the
writer above, that is a feedback loop. It is also unnecessary: the only producer of these params
is the homepage block, and reaching `/find-church` from the homepage always mounts the component
fresh, so `setup()` re-runs. (Contrast `CmsPage.vue`, which *is* reused across route changes —
that reuse is exactly what made the breadcrumb suppression flag load-bearing in `f22dab9`. It does
not apply here, because nothing navigates from `/find-church` to `/find-church`.)

**Accepted limitation**: browser back/forward between two different `/find-church?search=…` URLs
will not re-seed the filters, because the component is not re-created. Given nothing in the app
produces two such entries in a row, and `replace` deliberately avoids creating them, this is not
worth a loop-guarded round-trip watcher. **Record it rather than fix it.**

---

### Step 3: Correct the false comment

**File**: `resources/js/components/blocks/ChurchFinderBlock.vue` · **Action**: UPDATE

**Current** (`:49-51`):
```js
// Routed rather than posted: the locator reads its filters from the
// URL, so this stays a link the user can bookmark and share.
```
**Required change**:
```js
// Routed rather than posted, so the result is a link the visitor can
// bookmark and share. The locator seeds its filters from these params in
// its setup() — this comment used to assert that and it was not true, which
// is why the search term was silently discarded for several releases.
```

**Why**: the comment is what made this bug invisible in review. Leaving it would leave the trap.

---

### Step 4: Test

**File**: `tests/Feature/…` — ⚠️ **note the gap honestly.** This is a Vue-side defect and the suite
is Pest/PHP; there is no JS test runner installed (`package.json` has no `vitest`/`jest`). Adding
one is out of scope for a one-file fix.

**So this must be verified in a browser**, and that is not optional — this exact class of defect
(a Vue change that lints, builds and tests clean while the page is broken) has occurred seven
times in this codebase. Use `/tmp/claude-0/repro.mjs`, which already reproduces the bug and will
serve as the pass/fail check.

What can reasonably be added on the PHP side is a guard on the API contract the fix depends on:

```php
// tests/Feature/ChurchSearchTest.php
it('filters churches by the search term', function () {
    // MIRROR: tests/Feature/SecurityRegressionTest.php for the get()/assertJson() style
    $response = $this->getJson('/api/churches?search=Christchurch');

    $response->assertOk();
    expect(collect($response->json('data'))->pluck('name'))
        ->each(fn ($name) => $name->toContain('Christchurch'));
});
```
This pins the endpoint the locator relies on; it does **not** cover the wiring that was broken.

---

## Patterns to Follow

**Reading a query param that may be a string or an array:**
```js
// The locator's own fetch already treats service_day as repeatable —
// SOURCE: resources/js/views/ChurchLocator.vue:516-520
if (selectedServiceDays.value.length > 0) {
    selectedServiceDays.value.forEach(day => {
        params.append('service_day', day)
    })
}
```

**`region` as the public option name, `organizational_region` as the API's:**
```js
// SOURCE: resources/js/components/blocks/ChurchDirectoryBlock.vue
// organizational_region, not region: the latter filters the
// free-text geographic column and never matches a slug.
if (props.data.region) params.append('organizational_region', props.data.region)
```

**Seeding state from a route in setup:**
```js
// SOURCE: resources/js/components/layout/Breadcrumb.vue
const route = useRoute()
const crumbs = computed(() => { const segments = route.path.split('/').filter(Boolean) … })
```

---

## Edge Cases & Risks

| Risk / Edge case | Mitigation |
|---|---|
| **Duplicate fetch on mount** — seeding plus the watcher plus `onMounted` | Seed in `setup()`. An initial ref value does not trigger the watcher, so `onMounted`'s single `fetchChurches()` is the only request. **Assert this in the browser: exactly one `/api/churches` call on load** |
| **Feedback loop** if a `route.query` watcher is added alongside the URL writer | Explicitly out of scope — see Step 2's gotcha |
| `?search=` present but empty | `typeof === 'string'` keeps it a string; empty string is falsy in `fetchChurches`, so no param is appended |
| `?service_day=Sunday&service_day=Friday` | `queryList` normalises string vs array |
| A `?region=` slug that does not exist | The API's `whereHas` returns zero churches — the locator's existing empty state and "clear filters" button already cover it. Do not add special handling |
| `clearFilters()` leaves stale params in the URL | Handled for free by Step 2's watcher |
| Cache-busters `&_t=…&_cb=…` on the fetch (`:522`) make request assertions noisy | Strip them when asserting, as `repro.mjs` does. **Do not remove them in this fix** — unrelated |
| The locator's own search box is debounced or not | Unchanged by this fix; the watcher already fires per keystroke and did before |

---

## Validation

### Automated

```bash
./vendor/bin/pint --test --dirty
npm run build
php artisan test          # expect 117 passed / 16 pre-existing failures, plus any new test
```

### Browser — required, not optional

```bash
cd /tmp/claude-0 && node repro.mjs
```

Expected after the fix:

- [ ] Homepage → type "Christchurch" → Search → URL is `/find-church?search=Christchurch`
- [ ] The locator's search box **contains** "Christchurch"
- [ ] The API request is `/api/churches?search=Christchurch` — **not** `/api/churches?`
- [ ] Results are filtered to matching churches only
- [ ] **Exactly one** `/api/churches` request on load (no duplicate from seed + mount)
- [ ] Deep link `/find-church?search=Christchurch` pasted fresh behaves identically
- [ ] `/find-church?region=northern` filters by region
- [ ] Editing the box on the locator updates the URL, and reloading keeps the filter
- [ ] "Clear filters" empties both the filters and the URL
- [ ] Bare `/find-church` still shows all ten churches, no console errors

---

## Scope Boundaries

**IN SCOPE**
- Seeding the locator's three filters from `route.query`
- Mirroring filter changes back into the URL
- Correcting the false comment in `ChurchFinderBlock`

**OUT OF SCOPE — do not touch**
- The `&_t=`/`&_cb=` cache-busters on the fetch (`ChurchLocator.vue:522`) — a real smell, but
  unrelated to this defect
- The locator's stock colour tokens and legacy chrome (73 and 15 occurrences) — owned by
  `brand-consistency-follow-ups.plan.md`
- A `?church=<id>` deep link to open a specific church's modal — that is Phase 4 of the
  follow-ups plan, and a separate feature
- Installing a JS test runner
- Debouncing the locator's search input
- The homepage block's own markup or styling