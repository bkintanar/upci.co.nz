# Feature: Events Page Redesign (Warm, Scannable, Month-Grouped List)

## Summary

Replace the current 3-column card grid on `/events` with a single-column, vertically-flowing list of large horizontal event cards — grouped under sticky month headers — inspired by [upca.org.au/nationalcalendar](https://www.upca.org.au/nationalcalendar). Each card gets a prominent "date block" (day + month + year, color-accented by status), a generous title, calendar + location icons, a department tag, and the existing status pills ("This week" / "Happening now" / "Past"). No schema change; no backend change. The existing `eventStatus.js` helper keeps driving the colour tiers.

## User Story

As a **public visitor landing on `/events`**
I want **to scan upcoming UPCI NZ events by month at a glance with clear hierarchy**
So that **I don't stare at a sea of small, identical blue rectangles and I can easily see what's this week, this month, and later in the year**.

## Problem Statement

User feedback: *"The events page doesn't look good. It looks like a bunch of buttons and nothing else."*

Current `Events.vue` symptoms:
- Three-column responsive grid of near-identical small cards — "wall of buttons" effect.
- No month grouping — a visitor sees 49 events with no chronological landmarks.
- Same visual weight for every event (status tiers help, but the grid shape dominates).
- Date text is small and buried above the title; the card is the click target, not the date.
- No icons, no department affiliation, no visual hierarchy between "name" and "where/when".
- No action buttons — a card with `url` still just shows "Learn more" as a small link.

Success signal: after the redesign, a first-time visitor can:
1. Identify the current month in under 1 second (sticky month header).
2. Tell past from upcoming without reading dates (opacity/colour done; layout now too).
3. Click a specific event's "Register" / "Details" CTA without hunting.
4. Skim 49 events top-to-bottom without the eye fatigue of a grid.

## Solution Statement

Pure frontend re-layout in `resources/js/views/Events.vue`. Switch from grid → list. Add a computed `groupedEvents` that buckets events by `YYYY-MM` and exposes a list of `{ monthLabel, year, items }`. Render a sticky month header above each group and a horizontal flex card per event: left cell = large "date block" (day number, 3-letter month, year), right cell = name + icons + location + description + department chip + status pill + optional CTA button.

Keep the existing status-aware `eventStatusClasses()` colours for the date-block backgrounds and status pills. Add Heroicons-style calendar + location SVGs inline (matching existing pattern). No new dependencies.

## Metadata

| Field | Value |
|---|---|
| Type | REFACTOR (visual) |
| Complexity | LOW |
| Systems Affected | `Events.vue` only |
| Dependencies | None — pure Vue + Tailwind |
| Estimated Tasks | 3 |

---

## UX Design

### Before State
```
╔═══════════════════════════════════════════════════════════════════════════════╗
║                              BEFORE STATE                                      ║
╠═══════════════════════════════════════════════════════════════════════════════╣
║                                                                               ║
║   /events                                                                     ║
║   [ card ] [ card ] [ card ]     ← md:grid-cols-2 lg:grid-cols-3             ║
║   [ card ] [ card ] [ card ]                                                  ║
║   [ card ] [ card ] [ card ]                                                  ║
║   …×49 cards…                                                                 ║
║                                                                               ║
║   Each card:                                                                  ║
║   ┌──────────────────────────┐                                               ║
║   │ 2026-02-21               │ ← 12px blue text                              ║
║   │ Annual Ministers Meeting │ ← 20px bold                                   ║
║   │ Lorem ipsum              │ ← 14px muted                                  ║
║   │ Tauranga                 │                                                ║
║   │ (optional "Learn more") │                                                ║
║   └──────────────────────────┘                                               ║
║                                                                               ║
║   PAIN: grid + small cards = "bunch of buttons"; no month anchors; no        ║
║         visible CTA; no department context; no scale hierarchy.               ║
║                                                                               ║
╚═══════════════════════════════════════════════════════════════════════════════╝
```

### After State
```
╔═══════════════════════════════════════════════════════════════════════════════╗
║                               AFTER STATE                                      ║
╠═══════════════════════════════════════════════════════════════════════════════╣
║                                                                               ║
║   /events                                                                     ║
║                                                                               ║
║   ━━━━━━━ FEBRUARY 2026 ━━━━━━━    ← sticky, uppercase, slate-200 divider   ║
║                                                                               ║
║   ┌──┬────────────────────────────────────────────────────────────────┐     ║
║   │21│ Annual Ministers Meeting (AMM) — Ministers Seminar             │     ║
║   │Feb│ 📅 21 February 2026  📍 Tauranga                                │     ║
║   │ 26│ [Ministers · chip]                                  [Details →]│     ║
║   └──┴────────────────────────────────────────────────────────────────┘     ║
║                                                                               ║
║   ┌──┬────────────────────────────────────────────────────────────────┐     ║
║   │28│ PM – PAC Regional Prayer & Fasting                 [Soon pill] │     ║
║   │Feb│ 📅 28 Feb – 2 Mar 2026  📍 Nationwide                          │     ║
║   │–02│ [Prayer Ministry · chip]                                       │     ║
║   └──┴────────────────────────────────────────────────────────────────┘     ║
║                                                                               ║
║   ━━━━━━━ MARCH 2026 ━━━━━━━                                                 ║
║   … repeats …                                                                 ║
║                                                                               ║
║   DATA_FLOW:                                                                  ║
║     GET /api/events → events → computed groupedEvents[{ monthLabel, items }] ║
║       → v-for month in groupedEvents → v-for event in month.items            ║
║       → date-block bg keyed by eventStatus; pill keyed by status             ║
║                                                                               ║
║   VALUE_ADD: month anchors, scannable left-column date blocks, obvious       ║
║              CTA buttons, ministry context, one-column = mobile-identical.    ║
║                                                                               ║
╚═══════════════════════════════════════════════════════════════════════════════╝
```

### Interaction Changes
| Location | Before | After | User Impact |
|---|---|---|---|
| `/events` layout | 3-col grid of 49 small cards | single-column list of 49 large cards grouped by month | Easier scanning; obvious hierarchy |
| Month context | None | Sticky "FEBRUARY 2026" headers; stay visible as you scroll | Instant temporal orientation |
| Date display | small text above title | large date block on left (day/mon/yr stacked), status-coloured | Date becomes the first thing the eye lands on |
| Multi-day events | "2026-02-28 – 2026-03-02" inline | Date block shows `28/Feb → 02/Mar`; inline date still present below title | Span is obvious at a glance |
| Department | not shown | small pill below title, tinted by the department's `color_theme` | Visitors know which ministry the event is for |
| Status pill | already in place (added in prior plan) | stays, but moves to the right side of the card for consistent alignment | Scannability preserved |
| CTA | small "Learn more" link if url exists | "Details →" button, right-aligned under the card row | Clear call-to-action; matches upca.org.au pattern |
| Mobile | grid collapses to 1 col | already 1 col; date-block + text flex behaves natively | Identical look, no extra breakpoint logic |

---

## Mandatory Reading

| Priority | File | Lines | Why |
|---|---|---|---|
| P0 | `resources/js/views/Events.vue` | whole file | The file being redesigned |
| P0 | `resources/js/utils/eventStatus.js` | whole file | Still the source of status → classes; may need one new slot `dateBlock` (see Task 1) |
| P1 | `resources/js/views/Calendar.vue` | 149-152 | Existing `formatDate` helper style to mirror |
| P1 | `resources/js/components/Footer.vue` | 48-52 | Inline Heroicon-style SVG pattern used elsewhere (location pin) — reuse the same SVG paths |
| P2 | `app/Http/Controllers/Api/EventController.php` | 1-60 | Confirms `department` is eager-loaded and returned as a relation in the JSON |
| P2 | `app/Models/Department.php` | — | Confirms `color_theme` column (blue/green/pink/yellow/purple/indigo) — used for the department chip tint |

**External reference**: [upca.org.au/nationalcalendar](https://www.upca.org.au/nationalcalendar) — month-grouped single-column list of event cards with date blocks on the left, large titles, calendar icons, status buttons.

---

## Patterns to Mirror

**EXISTING_DATE_FORMAT** (Events.vue:77-79 / Calendar.vue:149-152):
```javascript
const formatDate = (dateStr) => {
    if (!dateStr) return ''
    const d = new Date(dateStr)
    return d.toLocaleDateString('en-NZ', { day: 'numeric', month: 'long', year: 'numeric' })
}
```

**EXISTING_INLINE_HEROICON** (Footer.vue:48-52 — location pin SVG):
```html
<svg class="w-5 h-5 text-blue-400 mt-1 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
</svg>
```

**EXISTING_STATUS_CLASS_CONSUMPTION** (Events.vue, after prior plan):
```html
<div :class="statusClasses(event).card">
  <div :class="statusClasses(event).date">{{ formatDate(event.start_date) }}</div>
  <span v-if="statusClasses(event).pillLabel" :class="statusClasses(event).pill">{{ statusClasses(event).pillLabel }}</span>
</div>
```

**Department `color_theme` values** (from `database/migrations/2026_04_19_000004_seed_departments_and_menu_items.php` — already seeded):
`blue | green | pink | yellow | purple | indigo`

---

## Files to Change

| File | Action | Justification |
|---|---|---|
| `resources/js/utils/eventStatus.js` | UPDATE | Add a new `dateBlock` slot to the class bundle so the new date-block column gets status-aware colouring; add a `dept` helper that maps a department's `color_theme` string → Tailwind chip classes |
| `resources/js/views/Events.vue` | UPDATE | Template rewrite: grouping computed, month header, horizontal card with date-block left + content right |
| `resources/js/views/Calendar.vue` | (no change) | Calendar page already works; redesign is scoped to `/events` |

Build writes to `public/build/assets/...`. No other files touched.

---

## NOT Building (Scope Limits)

- **Not adding an `image` column to `events`.** Events model has no image field. Adding one means migration + Filament FileUpload in the admin + image storage/serving. Worth a separate plan if the team wants photo-forward cards. This redesign achieves warmth through typography + colour + layout without images.
- **Not adding department filters / tabs.** "Showing only Youth events" is a future nice-to-have. Current list + status colouring + month grouping is enough to address the immediate complaint.
- **Not adding a toggle "hide past events".** Past events are already greyed via the status helper; user wanted them visible-but-deemphasised. Same policy here.
- **Not introducing a sidebar of month anchors.** Nice-to-have; keep first pass clean.
- **Not touching the admin side or the API.** Backend returns the same shape today (49 events with `department` eager-loaded).
- **Not redesigning `/calendar`.** Month grid page is unchanged.
- **Not adding tests.** No JS test infra in this project; acceptance is visual.

---

## Step-by-Step Tasks

### Task 1: UPDATE `resources/js/utils/eventStatus.js`

- **ACTION**: Extend the class bundle with a `dateBlock` slot (background for the left date column), and add a new exported helper `departmentChipClasses(colorTheme)` that maps `blue|green|pink|yellow|purple|indigo` → Tailwind classes.
- **IMPLEMENT** (additions only — existing exports unchanged):
  ```javascript
  // Inside each case of eventStatusClasses(), add a `dateBlock` field:
  case 'past':
      return {
          ...existingFields,
          dateBlock: 'bg-slate-200 text-slate-500',
      }
  case 'live':
      return {
          ...existingFields,
          dateBlock: 'bg-green-600 text-white',
      }
  case 'soon':
      return {
          ...existingFields,
          dateBlock: 'bg-amber-500 text-white',
      }
  case 'future':
  default:
      return {
          ...existingFields,
          dateBlock: 'bg-blue-600 text-white',
      }

  // New export:
  export function departmentChipClasses(colorTheme) {
      switch (colorTheme) {
          case 'green':  return 'bg-green-100 text-green-800 border-green-200'
          case 'pink':   return 'bg-pink-100 text-pink-800 border-pink-200'
          case 'yellow': return 'bg-yellow-100 text-yellow-800 border-yellow-200'
          case 'purple': return 'bg-purple-100 text-purple-800 border-purple-200'
          case 'indigo': return 'bg-indigo-100 text-indigo-800 border-indigo-200'
          case 'blue':
          default:       return 'bg-blue-100 text-blue-800 border-blue-200'
      }
  }
  ```
- **GOTCHA**: Keep class strings literal (no `${colorTheme}-100` interpolation) so Tailwind JIT scanner picks them up. This is the same lesson learned in the prior event-status plan.
- **GOTCHA**: The class bundle for `past` uses `bg-slate-200 text-slate-500` so the past date block is a **dimmer grey** — still a date block, but visually receded. The other three states use their saturated accent as the date block fill.
- **VALIDATE**: `node --check resources/js/utils/eventStatus.js` → no syntax error.

### Task 2: UPDATE `resources/js/views/Events.vue`

- **ACTION**: Full template rewrite (the script block gets minor additions). Computed `groupedEvents` buckets events by `YYYY-MM`; template renders month headers + horizontal cards.
- **IMPLEMENT** (skeleton of the new `<template>` — prose, not literal; implementer fills concrete classes):
  ```html
  <template>
    <div class="py-12 bg-slate-50">
      <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-10">
          <h1 class="text-4xl font-bold text-slate-900 mb-2">Calendar of Events</h1>
          <p class="text-lg text-slate-600">UPCI New Zealand — 2026 National Calendar</p>
        </div>

        <div v-if="loading" …>loading…</div>
        <div v-else-if="error" …>{{ error }}</div>

        <div v-else class="space-y-12">
          <section v-for="group in groupedEvents" :key="group.monthKey">
            <!-- Month header -->
            <header
              class="sticky top-28 z-10 bg-slate-50/95 backdrop-blur border-b-2 border-slate-200 py-3 mb-4 flex items-center gap-3"
            >
              <h2 class="text-sm font-bold uppercase tracking-widest text-slate-700">
                {{ group.monthLabel }}
              </h2>
              <span class="text-xs text-slate-400">{{ group.items.length }} event{{ group.items.length === 1 ? '' : 's' }}</span>
            </header>

            <!-- Cards -->
            <div class="space-y-4">
              <article
                v-for="event in group.items"
                :key="event.id"
                class="flex gap-4 sm:gap-6 rounded-xl shadow-sm border transition-shadow hover:shadow-md overflow-hidden"
                :class="statusClasses(event).card"
              >
                <!-- Left date block -->
                <div
                  class="flex-shrink-0 w-20 sm:w-24 flex flex-col items-center justify-center py-5 text-center"
                  :class="statusClasses(event).dateBlock"
                >
                  <div class="text-2xl sm:text-3xl font-bold leading-none">
                    {{ dayNumber(event.start_date) }}<template v-if="isMultiDay(event)">–{{ dayNumber(event.end_date) }}</template>
                  </div>
                  <div class="text-xs uppercase tracking-widest mt-1">
                    {{ monthAbbr(event.start_date) }}<template v-if="isMultiDay(event) && !sameMonth(event)"> – {{ monthAbbr(event.end_date) }}</template>
                  </div>
                  <div class="text-[11px] opacity-80 mt-0.5">{{ yearFrom(event.start_date) }}</div>
                </div>

                <!-- Right content -->
                <div class="flex-1 py-4 pr-4 sm:pr-6 min-w-0">
                  <div class="flex items-start justify-between gap-3">
                    <h3 class="text-lg sm:text-xl font-bold leading-snug" :class="statusClasses(event).title">
                      {{ event.name }}
                    </h3>
                    <span
                      v-if="statusClasses(event).pillLabel"
                      :class="statusClasses(event).pill"
                      class="flex-shrink-0 mt-1"
                    >
                      {{ statusClasses(event).pillLabel }}
                    </span>
                  </div>

                  <div class="mt-2 flex flex-wrap items-center gap-x-4 gap-y-1 text-sm" :class="statusClasses(event).date">
                    <span class="inline-flex items-center gap-1.5">
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                      {{ formatDateRange(event) }}
                    </span>
                    <span v-if="event.location" class="inline-flex items-center gap-1.5 text-slate-600">
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                      {{ event.location }}
                    </span>
                  </div>

                  <p v-if="event.description" class="mt-2 text-sm text-slate-600 line-clamp-2">{{ event.description }}</p>

                  <div class="mt-3 flex items-center gap-3">
                    <span
                      v-if="event.department"
                      :class="deptChip(event.department)"
                      class="inline-block px-2.5 py-0.5 rounded-full text-xs font-medium border"
                    >
                      {{ departmentLabel(event.department) }}
                    </span>
                    <a
                      v-if="event.url"
                      :href="event.url"
                      target="_blank"
                      rel="noopener"
                      class="ml-auto inline-flex items-center text-sm font-semibold text-blue-600 hover:text-blue-700 hover:underline"
                    >
                      Details
                      <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                    </a>
                  </div>
                </div>
              </article>
            </div>
          </section>
        </div>

        <div v-if="!loading && !error && !events.length" class="text-center py-16 text-slate-500">
          No upcoming events at the moment. Check back soon.
        </div>

        <div class="mt-16 text-center">
          <router-link to="/calendar" class="inline-flex items-center px-6 py-3 bg-blue-600 text-white font-semibold rounded-lg hover:bg-blue-700 transition-colors">
            View month calendar
            <svg class="w-5 h-5 ml-2" …/>
          </router-link>
        </div>
      </div>
    </div>
  </template>
  ```
- **IMPLEMENT** (`<script>` additions):
  ```javascript
  import { defineComponent, ref, computed, onMounted } from 'vue'
  import { getEventStatus, eventStatusClasses, departmentChipClasses } from '../utils/eventStatus'

  export default defineComponent({
    name: 'Events',
    setup() {
      const events = ref([])
      const loading = ref(true)
      const error = ref(null)

      const parseDate = (s) => new Date(s + 'T00:00:00')
      const dayNumber = (s) => s ? String(parseDate(s).getDate()).padStart(2, '0') : ''
      const monthAbbr = (s) => s ? parseDate(s).toLocaleDateString('en-NZ', { month: 'short' }).toUpperCase() : ''
      const yearFrom  = (s) => s ? parseDate(s).getFullYear() : ''
      const sameMonth = (ev) => ev.end_date && parseDate(ev.start_date).getMonth() === parseDate(ev.end_date).getMonth()
      const isMultiDay = (ev) => ev.end_date && ev.end_date !== ev.start_date

      const formatDateRange = (ev) => {
        const d1 = parseDate(ev.start_date).toLocaleDateString('en-NZ', { day: 'numeric', month: 'long', year: 'numeric' })
        if (!ev.end_date || ev.end_date === ev.start_date) return d1
        const d2 = parseDate(ev.end_date).toLocaleDateString('en-NZ', { day: 'numeric', month: 'long', year: 'numeric' })
        return `${d1} – ${d2}`
      }

      const groupedEvents = computed(() => {
        const groups = new Map()
        for (const ev of events.value) {
          const d = parseDate(ev.start_date)
          const key = `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}`
          const label = d.toLocaleDateString('en-NZ', { month: 'long', year: 'numeric' }).toUpperCase()
          if (!groups.has(key)) groups.set(key, { monthKey: key, monthLabel: label, items: [] })
          groups.get(key).items.push(ev)
        }
        return [...groups.values()].sort((a, b) => a.monthKey.localeCompare(b.monthKey))
      })

      const statusClasses = (event) => eventStatusClasses(getEventStatus(event))
      const deptChip = (dept) => departmentChipClasses(dept?.color_theme)
      const departmentLabel = (dept) => dept?.name || 'General'

      const fetchEvents = async () => {
        try {
          const res = await fetch('/api/events')
          const data = await res.json()
          if (data.success && data.data) events.value = data.data
          else error.value = 'Failed to load events'
        } catch (e) {
          error.value = e.message || 'Failed to load events'
        } finally {
          loading.value = false
        }
      }
      onMounted(fetchEvents)

      return {
        events, loading, error,
        groupedEvents,
        statusClasses, deptChip, departmentLabel,
        dayNumber, monthAbbr, yearFrom, sameMonth, isMultiDay, formatDateRange,
      }
    }
  })
  ```
- **GOTCHA**: `event.department` is an object (eager-loaded via `EventController::index`'s `->with('department')`) — has `name` and `color_theme`. Some events have `department_id = NULL` → `event.department` is null. `deptChip(null)` + `departmentLabel(null)` guard with optional chaining.
- **GOTCHA**: `sticky top-28` uses the existing Navbar height (`h-28` at line 4 of Navbar.vue). Match it exactly or the sticky header sits under the nav.
- **GOTCHA**: `bg-slate-50/95 backdrop-blur` — the `/95` opacity syntax requires Tailwind v3+. Confirmed in use elsewhere (Tailwind v3 per `tailwind.config.js`); if the build errors, fall back to `bg-slate-50`.
- **GOTCHA**: Dates stored as `YYYY-MM-DD`; parse with explicit `T00:00:00` to avoid UTC drift (same lesson as the status helper).
- **GOTCHA**: For a multi-day event that spans months (e.g., Feb 28 – Mar 2), the date block shows `28–02 FEB – MAR 2026`. Confirm this is readable; if not, fall back to `28/Feb → 02/Mar` stacked vertically.
- **VALIDATE**: `npm run build` must exit 0 and emit an updated `Events-*.js` chunk.

### Task 3: BUILD + smoke test

- **ACTION**: `cd /var/www/personal/upci.co.nz && npm run build`
- **VALIDATE**:
  - `curl -sS --resolve upci.b8.co.nz:80:127.0.0.1 -H "Accept: application/json" "http://upci.b8.co.nz/api/events" | python3 -c "import json,sys; d=json.load(sys.stdin); print('events:', len(d['data']))"` → `49` (no backend change).
  - Hard-refresh `http://upci.b8.co.nz/events` in a browser:
    - Expect 12 month sections (JANUARY 2026 → DECEMBER 2026) each with a sticky header.
    - Expect each card to be full-width, horizontal, ~100 px tall on desktop.
    - Expect January/February events to show grey date blocks and opacity-60 bodies.
    - Expect Apr 25 / Apr 26 events (as of 2026-04-20) to show amber date blocks + "This week" pill.
    - Expect department chips tinted per `color_theme`.

---

## Testing Strategy

No JS tests — same reasoning as prior plans. Visual spot-check:

| Scenario | Expected |
|---|---|
| Page first paint | Month header "APRIL 2026" visible near top if scrolled to current month; earlier months above, still loaded |
| Scroll past "APRIL" | "APRIL" header detaches from top, "MAY 2026" slides up into the sticky slot |
| Single-day event | Date block shows `04 JAN 2026`; status colour applied |
| Multi-day intra-month | Date block shows `26–31 JAN 2026` |
| Multi-day cross-month (Feb 28 – Mar 2) | Date block shows `28–02 FEB–MAR 2026` |
| Event with no location | Location icon + text not rendered (existing `v-if="event.location"`) |
| Event with no department | Department chip not rendered |
| Event with no `url` | "Details" link not rendered |
| `live` status | Green date block + "Happening now" pill + green hover ring |
| `past` status | Slate-200 date block, body opacity-60 |

### Edge cases

- [ ] Event at very start of year (Jan 1) — still grouped under "JANUARY 2026"
- [ ] Event spanning year boundary (none this year, but defensive) — grouped under its start month's year
- [ ] Multi-day event same month — date block `A–B MMM YYYY`
- [ ] Multi-day event cross-month — date block `A–B MMM–MMM YYYY`
- [ ] No events at all — existing "No upcoming events" empty state still renders
- [ ] API error — existing error state still renders

---

## Validation Commands

### Level 1: JS sanity
```bash
node --check resources/js/utils/eventStatus.js
```

### Level 2: Vite build
```bash
cd /var/www/personal/upci.co.nz && npm run build
```
**EXPECT**: `✓ built in <1s`, updated `Events-*.js`, CSS bundle may grow slightly as new Tailwind classes are picked up.

### Level 3: API regression
```bash
curl -sS --resolve upci.b8.co.nz:80:127.0.0.1 -H "Accept: application/json" "http://upci.b8.co.nz/api/events" | python3 -c "import json,sys; d=json.load(sys.stdin); print(len(d['data']))"
```
**EXPECT**: `49`.

### Level 4: Browser
- Hard-refresh `http://upci.b8.co.nz/events`. Verify the "after" state diagram.
- Scroll to verify sticky month headers.
- Hover a card to verify shadow transition.
- Click "Details" on an event that has a `url` set (currently none in seeded data — you can set one via `/admin/events` to smoke-test).

---

## Acceptance Criteria

- [ ] `/events` renders as a single-column vertical list (no more grid).
- [ ] Events are grouped under sticky month headers (`JANUARY 2026`, `FEBRUARY 2026`, …).
- [ ] Each event has a left date block showing day + month abbreviation + year.
- [ ] Date blocks are status-coloured (grey for past, blue for future, amber for soon, green for live).
- [ ] Each event surfaces a calendar icon + formatted date, a location icon + location (when present), optional description, department chip (when linked), status pill (when applicable), and a right-aligned "Details" link (when `url` is set).
- [ ] Layout looks equivalent on mobile (< 640 px) — no horizontal overflow.
- [ ] Build passes, API unchanged.
- [ ] No regressions in `/calendar` or other pages.

---

## Completion Checklist

- [ ] Task 1 — eventStatus.js extended (dateBlock + departmentChipClasses)
- [ ] Task 2 — Events.vue rewritten
- [ ] Task 3 — npm run build passes, browser smoke passes

---

## Risks and Mitigations

| Risk | Likelihood | Impact | Mitigation |
|---|---|---|---|
| Sticky `top-28` collides with non-standard Navbar heights | LOW | LOW | `h-28` is the current Navbar constant; easy to adjust if it changes |
| Department `color_theme` null on older rows | LOW | LOW | `departmentChipClasses(null)` falls through to the blue default |
| Multi-day cross-month date-block label is cramped (`28–02 FEB–MAR 2026` in a 96px column) | MED | LOW | Confirmed during visual smoke; fallback plan: drop to 2-line split (`28→02` / `Feb→Mar`) |
| Tailwind JIT misses the new `bg-{color}-100` classes in `departmentChipClasses` | LOW | LOW | Classes are literal strings in a `switch` — JIT scans the source file; confirmed pattern from prior plan |
| Events.vue consumers elsewhere (none found) break on the renamed helper slots | LOW | LOW | Only change is adding `dateBlock` + new export; nothing removed |
| The sticky-header backdrop-blur causes white flash during scroll on some browsers | LOW | LOW | Fallback to solid `bg-slate-50` if users report it |

---

## Notes

- **Why no images.** The `events` table has no image column. Adding one is a meaningful feature (schema migration + Filament FileUpload + admin workflow change) and deserves its own plan. This redesign solves the "bunch of buttons" complaint using typography + colour + layout alone — achievable in one small plan.
- **Why month sticky headers.** The upca.org.au reference uses implicit section dividers ("NATIONAL EVENTS", "LOCAL & REGIONAL EVENTS"). Our data is ministry-tagged but not categorised at the national/regional/local level; month is the natural sort. Sticky headers are the standard UX for long month-based event lists (Google Calendar list view, Apple Calendar, Eventbrite).
- **Why keep status colours on the date block rather than on the card.** Card background is white for readability of long titles + descriptions. Status colour concentrates on the date block for instant scanning. Status pill still appears on the right for extra emphasis when `soon` / `live`.
- **Why the department chip.** Events have `department_id` eager-loaded; users browsing likely want to know "is this a Men's event or Youth event?" at a glance. Low-cost, high-signal addition.
- **Why 12 month sections, not "upcoming only".** Consistent with the prior decision: past events stay visible but grey. The list page doubles as a year-in-review archive by December.
- **Confidence: 9/10.** The only unknowns are (a) how the multi-day cross-month date block looks in a 96 px column (addressed in Risks) and (b) whether `backdrop-blur` Tailwind class is available (it's in Tailwind 2.1+; this project uses v3 so fine). Everything else is standard layout.
