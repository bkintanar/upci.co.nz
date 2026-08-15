# Feature: Events Page — Filters, Quick-Jump, and Card Polish

## Summary

Upgrade `/events` from a single flat month-grouped list into a proper events-calendar page: a sticky filter bar (time-scope tabs + department multi-select pills), a month quick-jump strip for scroll navigation, a stats strip showing weekly / monthly / upcoming counts, and a visual refresh on the cards themselves (left colour-accent rail keyed to department, bigger date block with relative-time hint, subtle gradient, stronger typography, improved hover). No backend or schema change — the `/api/events` response already carries everything we need.

## User Story

As a **public visitor landing on `/events`**
I want **to filter events by department and time window, jump to any month with one click, and see richer, less-bland event cards**
So that **I can find events relevant to my ministry in seconds and the page actually feels like a calendar, not a log dump**.

## Problem Statement

User feedback (verbatim): *"The boxes still look bland. Can you improve it like a proper events calendar? Maybe show some filters for this month, or departments, region or whatever."*

Current `/events` after the prior redesign:
- Month-grouped list works, sticky month headers work, but cards are uniform white-with-date-block — visually monotone at 49 events.
- No filtering: to find Youth events you scroll past 40 non-Youth cards.
- No jump navigation: to see November you scroll through ten months.
- No summary: a visitor can't tell "is anything happening this week?" without reading 49 rows.
- Department context is a small chip; easy to miss while scrolling.

Testable success signals:
- A visitor can click a department pill ("Prayer Ministry") and see only the 6 prayer events across the year, retaining the month grouping.
- A visitor can click "Nov" in the jump bar and the page smooth-scrolls to the November section.
- A visitor can click the "Upcoming" time tab (default) and past events disappear; clicking "All" brings them back.
- The top stats strip reads, for today (2026-04-20): `This Week: 2 · This Month: 2 · Upcoming: 35 · Past: 13` (numbers computed from the data).
- Cards visually distinguish by department at first glance (coloured left rail), not by a small chip only.

## Solution Statement

Three user-visible additions plus a card-level restyle, all in `resources/js/views/Events.vue` with one small helper addition to `resources/js/utils/eventStatus.js` for relative-time text:

1. **Stats strip** — four tiles above the filter bar: This Week / This Month / Upcoming / Past (counts), each subtly clickable to scope the list.
2. **Sticky filter bar** (below Navbar, above content) — three controls:
   - Time-scope tabs: `Upcoming` | `All` | `Past` (single-select, default `Upcoming`).
   - Department pills: multi-select, counts shown per pill, "Clear" resets.
   - Month quick-jump chips: `JAN FEB MAR … DEC`, click scrolls to that section. Dim months that have 0 events after filters are applied.
3. **Card restyle**:
   - Coloured left rail (4 px) keyed to the event's department `color_theme` (blue/green/pink/yellow/purple/indigo) — the dominant visual cue.
   - Date block gets a subtle gradient (`bg-gradient-to-br from-X-500 to-X-700`), larger day number (`text-4xl`), and `tracking-widest` month label.
   - Title bumped to `text-xl sm:text-2xl`, tighter leading.
   - New "relative time" chip next to the "This week" / "Happening now" pill: `in 5 days` / `next Saturday` / `in 3 months` / `2 months ago` / `today`.
   - Hover lift (`hover:-translate-y-0.5`) + stronger shadow transition.
4. **State management**: reactive refs for `timeScope`, `selectedDepartments` (Set), and a computed `filteredGroupedEvents`. Month quick-jump uses `scrollIntoView({behavior:'smooth', block:'start'})` with `scroll-margin-top` set to account for the sticky filter bar.

## Metadata

| Field | Value |
|---|---|
| Type | ENHANCEMENT |
| Complexity | MEDIUM |
| Systems Affected | `resources/js/views/Events.vue`, `resources/js/utils/eventStatus.js` |
| Dependencies | None — pure Vue 3 + Tailwind |
| Estimated Tasks | 5 (helper tweak, filter state, stats strip, filter bar, card restyle) |

---

## UX Design

### Before State
```
╔═══════════════════════════════════════════════════════════════════════════════╗
║                              BEFORE STATE                                      ║
╠═══════════════════════════════════════════════════════════════════════════════╣
║                                                                               ║
║   [ Calendar of Events — subtitle ]                                           ║
║                                                                               ║
║   ━━━━━ JANUARY 2026 · 4 events ━━━━━  (sticky)                              ║
║   ┌──┬──────────────────────────────────┐                                    ║
║   │04│ Mission Sunday Promotion          │                                   ║
║   │JAN│ 📅 4 Jan 2026  📍 Nationwide     │                                   ║
║   │2026│ [Missions Dept chip]             │                                   ║
║   └──┴──────────────────────────────────┘                                    ║
║   …more cards, all visually identical apart from status colour…              ║
║                                                                               ║
║   PAIN: no filter, no summary, no way to jump; department encoded only        ║
║         in a small chip; cards read as a wall of near-identical rows.         ║
╚═══════════════════════════════════════════════════════════════════════════════╝
```

### After State
```
╔═══════════════════════════════════════════════════════════════════════════════╗
║                               AFTER STATE                                      ║
╠═══════════════════════════════════════════════════════════════════════════════╣
║                                                                               ║
║   [ Calendar of Events ]  [ 2026 National Calendar · 49 events ]              ║
║                                                                               ║
║   ┌────────┐ ┌────────┐ ┌────────┐ ┌────────┐                                 ║
║   │ 2      │ │ 2      │ │ 35     │ │ 13     │  ← stats tiles (clickable)     ║
║   │ THIS   │ │ THIS   │ │UPCOMING│ │  PAST  │                                ║
║   │ WEEK   │ │ MONTH  │ │        │ │        │                                 ║
║   └────────┘ └────────┘ └────────┘ └────────┘                                ║
║                                                                               ║
║   ╔═══════════════════════════════════════════════════════════════════╗     ║
║   ║ FILTER BAR (sticky under nav)                                      ║     ║
║   ║ Show:  [●Upcoming] [  All  ] [  Past  ]                            ║     ║
║   ║ Dept:  [🔵 Missions(13)] [🟣 Prayer(6)] [🟡 Children(8)] [🟢 Men] ║     ║
║   ║        [🩷 Ladies] [🟦 Youth]        [Clear]                       ║     ║
║   ║ Jump:  [JAN] [FEB] [MAR] [APR] [MAY]…[DEC]   (dim = 0 matches)     ║     ║
║   ╚═══════════════════════════════════════════════════════════════════╝     ║
║                                                                               ║
║   ━━━━━ APRIL 2026 · 6 events ━━━━━                                           ║
║   ┃                                                                           ║
║   ║ ┌──┬──────────────────────────────────────────────────────┐              ║
║   ║ │25│ LM – General Director Visitation     [This week · in 5d]│            ║
║   ║ │APR│ 📅 25 April 2026  📍 Whangarei                      │             ║
║   ║ │2026│ [🩷 Ladies' Department]                              │             ║
║   ║ └──┴──────────────────────────────────────────────────────┘              ║
║    ↑ 4px LH pink left rail (matches Ladies color)                            ║
║                                                                               ║
║   …cards for each event…                                                      ║
║                                                                               ║
║   VALUE_ADDS:                                                                 ║
║    - instant scope via tabs/pills/month chips                                 ║
║    - at-a-glance department recognition via coloured rail                     ║
║    - relative time ("in 5 days") answers "how soon?" without date math       ║
║    - stats strip satisfies "is anything happening soon?" in 1 glance         ║
╚═══════════════════════════════════════════════════════════════════════════════╝
```

### Interaction Changes
| Location | Before | After | User Impact |
|---|---|---|---|
| Page header | Title + subtitle | Title + subtitle + stats strip (4 tiles) | Immediate summary of calendar state |
| Time scope | implicit — past events shown greyed | explicit 3-way tab (Upcoming/All/Past), default `Upcoming` | Clean-by-default, past still reachable |
| Department filter | none | multi-select pills with counts | Triage to the ministry that matters to you |
| Month nav | scroll | one-click jump chips per month | Skip months with zero matches |
| Card left edge | plain border | 4 px colour rail keyed to department | Visual ministry tag without reading the chip |
| Date block | flat colour | subtle gradient + larger day number | More visual weight; reads as "calendar cell" |
| Title size | `text-lg sm:text-xl` | `text-xl sm:text-2xl` with tighter leading | Scannability, feels less cramped |
| Relative-time hint | none | `in 5 days` / `next Saturday` / `2 months ago` chip | Eliminates date arithmetic |
| Hover | shadow only | shadow + subtle lift `-translate-y-0.5` | Interactive feel |
| Empty filter result | would render empty page | shows "No events match these filters" + "Clear filters" button | Recoverable dead-end |

---

## Mandatory Reading

| Priority | File | Lines | Why |
|---|---|---|---|
| P0 | `resources/js/views/Events.vue` | whole file | The file being upgraded; grouping + date-block + helpers already there |
| P0 | `resources/js/utils/eventStatus.js` | whole file | Already exports `getEventStatus`, `eventStatusClasses`, `departmentChipClasses`; extending with `relativeTimeLabel()` and `departmentRailClasses()` |
| P0 | `app/Http/Controllers/Api/EventController.php` | 35-48 | Confirms `department` is returned as `{slug, name, color_theme}` and the JSON includes `start_date` / `end_date` / `location` |
| P1 | `database/migrations/2026_04_19_000004_seed_departments_and_menu_items.php` | 10-59 | Canonical list of department slugs + color_theme values (blue/green/pink/yellow/purple/indigo) |
| P1 | `resources/js/components/Navbar.vue` | 1-30 | Navbar has `h-28` — sticky filter bar must use `top-28` to clear it |
| P1 | `resources/js/views/Calendar.vue` | 155-179 | Existing `prevMonth/nextMonth` navigation pattern — reference only; jump chips use `scrollIntoView` not state |
| P2 | `tailwind.config.js` | whole | Confirm Tailwind v3 (gradient utilities, `scroll-margin-top` are available) |

No external docs beyond Tailwind (`https://tailwindcss.com/docs/gradient-color-stops`) and MDN `Element.scrollIntoView` — both stable, no version gotchas.

---

## Patterns to Mirror

**EXISTING_HELPER_EXPORT_STYLE** (`resources/js/utils/eventStatus.js`):
```javascript
export function departmentChipClasses(colorTheme) {
    switch (colorTheme) {
        case 'green':  return 'bg-green-100 text-green-800 border-green-200'
        // …
    }
}
```

Mirror for the two new exports:
```javascript
export function departmentRailClasses(colorTheme) { /* left-rail bg-{color}-500 */ }
export function relativeTimeLabel(event, now = new Date()) { /* 'in 5 days' / 'today' / 'next Saturday' / '2 months ago' */ }
```

**EXISTING_COMPUTED_GROUPING** (`Events.vue` post-redesign):
```javascript
const groupedEvents = computed(() => {
    const groups = new Map()
    for (const ev of events.value) { /* bucket by YYYY-MM */ }
    return [...groups.values()].sort(/* … */)
})
```

Extend to `filteredGroupedEvents = computed(() => group(applyFilters(events.value)))`, keeping the same shape so the template doesn't care whether the list was filtered.

**EXISTING_NAVBAR_HEIGHT** (`Navbar.vue:4`):
```html
<div class="flex justify-between items-center h-28">
```
Sticky filter bar uses `top-28` to sit flush. Stats strip scrolls normally (no sticky).

**SEEDED DEPARTMENTS** (values used verbatim in the pill list):
| slug | name | color_theme |
|---|---|---|
| missions | World Missions Department | blue |
| prayer | Prayer Ministry | purple |
| childrens | Children's Ministry | yellow |
| mens | Men's Department | green |
| ladies | Ladies' Department | pink |
| youth | Youth Ministry | indigo |

---

## Files to Change

| File | Action | Justification |
|---|---|---|
| `resources/js/utils/eventStatus.js` | UPDATE | Add `relativeTimeLabel(event)` + `departmentRailClasses(colorTheme)` |
| `resources/js/views/Events.vue` | UPDATE | Stats strip + sticky filter bar (tabs/pills/jump chips) + card restyle + filtered grouping |

No migrations. No controller changes. No new Vue files — keeping everything in one component is simpler than factoring out `<FilterBar />` / `<EventCard />` at this scale (49 events, one page). If any subcomponents later need to be reused (e.g., on `/calendar` list view), a refactor plan can split them then.

---

## NOT Building (Scope Limits)

- **Not adding a "Region" filter.** Events have no `region_id` or `region` column, and mapping `location` varchars ("Nationwide", "Auckland / Whangarei", "Wellington (CTW)") to a finite region set is a non-trivial text-matching problem. Worth a separate plan if product wants it. The user explicitly wrote "or whatever" — department + month + time scope covers the core intent.
- **Not adding event images.** Still deferred (requires schema + Filament FileUpload + admin flow). Visual polish comes from colour rail + gradient + typography.
- **Not persisting filter state** in URL params or localStorage. Every page visit starts with `Upcoming` selected and no departments filtered. If the team later wants shareable filter URLs, it's a small vue-router tweak.
- **Not adding fuzzy search**. Department pills + time scope + month jump are enough for 49 events.
- **Not refactoring `/calendar`**. Month-grid page remains as-is; this plan only touches the list view at `/events`.
- **Not introducing new Tailwind plugins.** Gradient utilities, `scroll-margin-top`, and `backdrop-blur` are all built-in.
- **Not adding tests.** No JS test infra; acceptance is visual.

---

## Step-by-Step Tasks

### Task 1: UPDATE `resources/js/utils/eventStatus.js`

- **ACTION**: Add two exported helpers.
- **IMPLEMENT** (additions — keep existing exports untouched):
  ```javascript
  /**
   * Left-rail background class keyed to department color_theme.
   * Single flat colour (saturated 500) — legible against the white card.
   */
  export function departmentRailClasses(colorTheme) {
      switch (colorTheme) {
          case 'green':  return 'bg-green-500'
          case 'pink':   return 'bg-pink-500'
          case 'yellow': return 'bg-yellow-500'
          case 'purple': return 'bg-purple-500'
          case 'indigo': return 'bg-indigo-500'
          case 'blue':
          default:       return 'bg-blue-500'
      }
  }

  /**
   * Date-block gradient keyed to event status.
   */
  export function dateBlockGradient(status) {
      switch (status) {
          case 'past':   return 'bg-gradient-to-br from-slate-200 to-slate-300 text-slate-600'
          case 'live':   return 'bg-gradient-to-br from-green-500 to-green-700 text-white'
          case 'soon':   return 'bg-gradient-to-br from-amber-400 to-amber-600 text-white'
          case 'future':
          default:       return 'bg-gradient-to-br from-blue-500 to-blue-700 text-white'
      }
  }

  /**
   * Natural-language relative time label for an event's start date.
   * Examples: 'today', 'tomorrow', 'in 5 days', 'next Saturday', 'in 3 months', '2 months ago'.
   */
  export function relativeTimeLabel(event, now = new Date()) {
      if (!event || !event.start_date) return ''
      const today = new Date(now.getFullYear(), now.getMonth(), now.getDate())
      const start = new Date(event.start_date + 'T00:00:00')
      const diffMs = start - today
      const diffDays = Math.round(diffMs / (24 * 60 * 60 * 1000))

      if (diffDays === 0) return 'today'
      if (diffDays === 1) return 'tomorrow'
      if (diffDays === -1) return 'yesterday'
      if (diffDays > 1 && diffDays <= 6) {
          return `${start.toLocaleDateString('en-NZ', { weekday: 'long' })}`
      }
      if (diffDays < -1 && diffDays >= -6) {
          return `${Math.abs(diffDays)} days ago`
      }
      if (diffDays > 6 && diffDays <= 13) return 'next week'
      if (diffDays >= 14 && diffDays <= 30) return `in ${Math.round(diffDays / 7)} weeks`
      if (diffDays > 30 && diffDays <= 365) {
          const m = Math.round(diffDays / 30)
          return `in ${m} month${m === 1 ? '' : 's'}`
      }
      if (diffDays < -6 && diffDays >= -30) {
          const w = Math.round(Math.abs(diffDays) / 7)
          return `${w} week${w === 1 ? '' : 's'} ago`
      }
      if (diffDays < -30) {
          const m = Math.round(Math.abs(diffDays) / 30)
          return `${m} month${m === 1 ? '' : 's'} ago`
      }
      return `in ${Math.round(diffDays / 365)} year(s)`
  }
  ```
- **GOTCHA**: All class strings stay literal for Tailwind JIT. The existing `eventStatusClasses().dateBlock` keeps its flat-colour version — the new gradient function is additive; `Events.vue` chooses which one to apply. Keep both so the Calendar.vue chips don't break.
- **VALIDATE**: `node --check resources/js/utils/eventStatus.js`.

### Task 2: UPDATE `Events.vue` — add filter state + `filteredGroupedEvents`

- **ACTION**: Introduce reactive refs, derived computeds, and helper methods. No template change yet.
- **IMPLEMENT** (additions in the `setup()`):
  ```javascript
  import { defineComponent, ref, computed, onMounted } from 'vue'
  import {
      getEventStatus, eventStatusClasses, departmentChipClasses,
      departmentRailClasses, dateBlockGradient, relativeTimeLabel,
  } from '../utils/eventStatus'

  // …

  const timeScope = ref('upcoming')        // 'upcoming' | 'all' | 'past'
  const selectedDepartments = ref(new Set())  // Set of department slugs

  const toggleDepartment = (slug) => {
      if (selectedDepartments.value.has(slug)) {
          selectedDepartments.value.delete(slug)
      } else {
          selectedDepartments.value.add(slug)
      }
      // trigger reactivity on Set mutation
      selectedDepartments.value = new Set(selectedDepartments.value)
  }
  const clearDepartments = () => { selectedDepartments.value = new Set() }

  const matchesTimeScope = (event) => {
      const s = getEventStatus(event)
      if (timeScope.value === 'all') return true
      if (timeScope.value === 'past') return s === 'past'
      // 'upcoming' → anything not past (live / soon / future)
      return s !== 'past'
  }

  const matchesDepartment = (event) => {
      if (!selectedDepartments.value.size) return true
      return event.department && selectedDepartments.value.has(event.department.slug)
  }

  const filteredEvents = computed(() =>
      events.value.filter((e) => matchesTimeScope(e) && matchesDepartment(e))
  )

  const filteredGroupedEvents = computed(() => {
      const groups = new Map()
      for (const ev of filteredEvents.value) {
          if (!ev.start_date) continue
          const d = parseDate(ev.start_date)
          const key = `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}`
          if (!groups.has(key)) {
              const label = d.toLocaleDateString('en-NZ', { month: 'long', year: 'numeric' }).toUpperCase()
              groups.set(key, { monthKey: key, monthLabel: label, items: [] })
          }
          groups.get(key).items.push(ev)
      }
      return [...groups.values()].sort((a, b) => a.monthKey.localeCompare(b.monthKey))
  })

  // Department list with counts (respecting active time scope).
  const departmentOptions = computed(() => {
      const byTimeScope = events.value.filter(matchesTimeScope)
      const tally = new Map()
      for (const e of byTimeScope) {
          if (!e.department) continue
          const s = e.department.slug
          if (!tally.has(s)) tally.set(s, { ...e.department, count: 0 })
          tally.get(s).count += 1
      }
      return [...tally.values()].sort((a, b) => a.name.localeCompare(b.name))
  })

  // Month chips (built from current year range of events — dim when 0 after filters).
  const monthChips = computed(() => {
      const allKeys = new Set(events.value.filter(e => e.start_date).map((e) => e.start_date.slice(0, 7)))
      const filteredKeys = new Set(filteredGroupedEvents.value.map((g) => g.monthKey))
      return [...allKeys].sort().map((k) => {
          const d = new Date(k + '-01T00:00:00')
          return {
              key: k,
              short: d.toLocaleDateString('en-NZ', { month: 'short' }).toUpperCase(),
              enabled: filteredKeys.has(k),
          }
      })
  })

  // Stats strip.
  const stats = computed(() => {
      const base = events.value
      let week = 0, month = 0, upcoming = 0, past = 0
      const now = new Date()
      const today = new Date(now.getFullYear(), now.getMonth(), now.getDate())
      const weekEnd = new Date(today); weekEnd.setDate(today.getDate() + 7)
      const monthEnd = new Date(today.getFullYear(), today.getMonth() + 1, 0)
      for (const e of base) {
          if (!e.start_date) continue
          const start = parseDate(e.start_date)
          const status = getEventStatus(e, now)
          if (status === 'past') past++
          else {
              upcoming++
              if (start <= weekEnd) week++
              if (start <= monthEnd) month++
          }
      }
      return { week, month, upcoming, past }
  })

  // Scroll to a given month's section — paired with an id on each <section>.
  const jumpToMonth = (monthKey) => {
      const el = document.getElementById(`month-${monthKey}`)
      if (el) el.scrollIntoView({ behavior: 'smooth', block: 'start' })
  }

  // For the card.
  const railClass = (ev) => ev.department ? departmentRailClasses(ev.department.color_theme) : 'bg-slate-300'
  const gradientClass = (ev) => dateBlockGradient(getEventStatus(ev))
  const relTime = (ev) => relativeTimeLabel(ev)
  ```
- **GOTCHA**: `Set` is not reactive on `.add` / `.delete` — reassign `selectedDepartments.value = new Set(...)` after mutation (shown above).
- **GOTCHA**: Keep `parseDate` and the existing helpers; don't duplicate.
- **VALIDATE**: `npm run build` still compiles (Task 3 adds the template).

### Task 3: UPDATE `Events.vue` — template additions

- **ACTION**: Insert stats strip + filter bar, change each `<section>` to add an `id`, rewrite each `<article>` card to use the new rail/gradient/title/relative-time pieces.
- **IMPLEMENT** (patches, not full rewrite):

  After the page header, **insert stats strip**:
  ```html
  <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-6">
      <button type="button" @click="timeScope = 'upcoming'"
              class="rounded-lg bg-white border border-slate-200 p-4 text-left hover:border-blue-300 transition-colors">
          <div class="text-3xl font-bold text-blue-600">{{ stats.week }}</div>
          <div class="text-xs uppercase tracking-widest text-slate-500 mt-1">This Week</div>
      </button>
      <button type="button" @click="timeScope = 'upcoming'"
              class="rounded-lg bg-white border border-slate-200 p-4 text-left hover:border-blue-300 transition-colors">
          <div class="text-3xl font-bold text-slate-900">{{ stats.month }}</div>
          <div class="text-xs uppercase tracking-widest text-slate-500 mt-1">This Month</div>
      </button>
      <button type="button" @click="timeScope = 'upcoming'"
              class="rounded-lg bg-white border border-slate-200 p-4 text-left hover:border-blue-300 transition-colors">
          <div class="text-3xl font-bold text-slate-900">{{ stats.upcoming }}</div>
          <div class="text-xs uppercase tracking-widest text-slate-500 mt-1">Upcoming</div>
      </button>
      <button type="button" @click="timeScope = 'past'"
              class="rounded-lg bg-white border border-slate-200 p-4 text-left hover:border-slate-400 transition-colors">
          <div class="text-3xl font-bold text-slate-400">{{ stats.past }}</div>
          <div class="text-xs uppercase tracking-widest text-slate-500 mt-1">Past</div>
      </button>
  </div>
  ```

  Then **sticky filter bar**:
  ```html
  <div class="sticky top-28 z-10 bg-slate-50/95 backdrop-blur border-b border-slate-200 py-3 mb-8 -mx-4 sm:-mx-6 lg:-mx-8 px-4 sm:px-6 lg:px-8">
      <div class="flex flex-col gap-3">
          <!-- Time tabs -->
          <div class="flex items-center gap-2">
              <span class="text-xs uppercase tracking-widest text-slate-500 w-20 shrink-0">Show</span>
              <div class="inline-flex rounded-lg border border-slate-200 bg-white p-0.5">
                  <button v-for="opt in ['upcoming','all','past']"
                          :key="opt"
                          type="button"
                          @click="timeScope = opt"
                          :class="timeScope === opt ? 'bg-blue-600 text-white' : 'text-slate-600 hover:bg-slate-100'"
                          class="px-3 py-1 text-sm font-semibold rounded-md capitalize transition-colors">
                      {{ opt }}
                  </button>
              </div>
          </div>

          <!-- Department pills -->
          <div class="flex items-center gap-2 flex-wrap">
              <span class="text-xs uppercase tracking-widest text-slate-500 w-20 shrink-0">Dept</span>
              <button v-for="d in departmentOptions"
                      :key="d.slug"
                      type="button"
                      @click="toggleDepartment(d.slug)"
                      :class="[
                          selectedDepartments.has(d.slug)
                              ? 'ring-2 ring-offset-1 ring-slate-400'
                              : 'opacity-80 hover:opacity-100',
                          deptPillClass(d)
                      ]"
                      class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-medium border transition-all">
                  {{ d.name }} <span class="text-slate-500 text-[10px]">({{ d.count }})</span>
              </button>
              <button v-if="selectedDepartments.size"
                      type="button"
                      @click="clearDepartments"
                      class="text-xs text-slate-500 hover:text-slate-700 underline ml-1">
                  Clear
              </button>
          </div>

          <!-- Month quick-jump -->
          <div class="flex items-center gap-2 overflow-x-auto">
              <span class="text-xs uppercase tracking-widest text-slate-500 w-20 shrink-0">Jump</span>
              <button v-for="m in monthChips"
                      :key="m.key"
                      type="button"
                      :disabled="!m.enabled"
                      @click="jumpToMonth(m.key)"
                      :class="m.enabled
                          ? 'text-slate-700 bg-white border-slate-200 hover:bg-blue-50 hover:border-blue-300'
                          : 'text-slate-300 bg-slate-50 border-slate-100 cursor-not-allowed'"
                      class="px-2.5 py-0.5 rounded-md text-xs font-semibold tracking-wider border transition-colors">
                  {{ m.short }}
              </button>
          </div>
      </div>
  </div>
  ```
  Add a helper `deptPillClass(d)` that returns `departmentChipClasses(d.color_theme)`.

  Then **each month section gets an `id`**:
  ```html
  <section v-for="group in filteredGroupedEvents" :key="group.monthKey" :id="`month-${group.monthKey}`" class="scroll-mt-40">
      <!-- existing header -->
  </section>
  ```
  `scroll-mt-40` ensures the sticky filter bar doesn't hide the section header after `scrollIntoView`.

  Then **card restyle**:
  ```html
  <article
      v-for="event in group.items"
      :key="event.id"
      class="flex gap-0 rounded-xl shadow-sm border overflow-hidden transition-all hover:shadow-lg hover:-translate-y-0.5"
      :class="statusClasses(event).card"
  >
      <!-- Department rail (new) -->
      <div class="w-1 sm:w-1.5 flex-shrink-0" :class="railClass(event)"></div>

      <!-- Date block (gradient version) -->
      <div
          class="flex-shrink-0 w-20 sm:w-24 flex flex-col items-center justify-center py-5 text-center"
          :class="gradientClass(event)"
      >
          <div class="text-3xl sm:text-4xl font-bold leading-none">
              {{ dayNumber(event.start_date) }}<template v-if="isMultiDay(event)">–{{ dayNumber(event.end_date) }}</template>
          </div>
          <div class="text-[10px] sm:text-xs uppercase tracking-widest mt-1 font-semibold opacity-90">
              <template v-if="isMultiDay(event) && !sameMonth(event)">
                  {{ monthAbbr(event.start_date) }}–{{ monthAbbr(event.end_date) }}
              </template>
              <template v-else>
                  {{ monthAbbr(event.start_date) }}
              </template>
          </div>
          <div class="text-[10px] opacity-70 mt-0.5">{{ yearFrom(event.start_date) }}</div>
      </div>

      <!-- Right content (title bumped) -->
      <div class="flex-1 py-4 pl-4 sm:pl-5 pr-4 sm:pr-6 min-w-0">
          <div class="flex items-start justify-between gap-3">
              <h3 class="text-xl sm:text-2xl font-bold leading-tight" :class="statusClasses(event).title">
                  {{ event.name }}
              </h3>
              <div class="flex flex-col items-end gap-1 flex-shrink-0">
                  <span v-if="statusClasses(event).pillLabel"
                        :class="statusClasses(event).pill"
                        class="whitespace-nowrap">
                      {{ statusClasses(event).pillLabel }}
                  </span>
                  <span v-if="relTime(event)"
                        class="text-[11px] text-slate-500 font-medium whitespace-nowrap">
                      {{ relTime(event) }}
                  </span>
              </div>
          </div>

          <!-- existing date+location row unchanged -->
          <!-- existing description unchanged -->
          <!-- existing department chip + Details link unchanged -->
      </div>
  </article>
  ```

  Add an **empty-filter** state at the bottom of the `<div v-else>`:
  ```html
  <div v-if="!filteredGroupedEvents.length" class="text-center py-20 text-slate-500">
      No events match these filters.
      <button type="button" @click="resetFilters"
              class="block mx-auto mt-3 text-blue-600 hover:underline font-medium">
          Clear all filters
      </button>
  </div>
  ```
  Where `resetFilters = () => { timeScope.value = 'upcoming'; clearDepartments(); }`.

- **GOTCHA**: Replace the current `v-for="group in groupedEvents"` loop with `v-for="group in filteredGroupedEvents"`. Keep `groupedEvents` defined if you like, but it's no longer referenced.
- **GOTCHA**: The existing `<header class="sticky top-28 …">` inside each `<section>` previously stuck the month label. Because the new filter bar now sits at `top-28` and is itself sticky, the inner section headers would collide. Fix: remove the `sticky` from the inner `<header>` (keep the `border-b` and the label styling) — the filter bar becomes the new sticky anchor and the month header scrolls with the content.
- **VALIDATE**: `npm run build`.

### Task 4: BUILD + smoke test

- **ACTION**: `cd /var/www/personal/upci.co.nz && npm run build`.
- **VALIDATE**:
  - Build exits 0.
  - `curl -sS --resolve upci.b8.co.nz:80:127.0.0.1 -H "Accept: application/json" "http://upci.b8.co.nz/api/events" | python3 -c "import json,sys; print(len(json.load(sys.stdin)['data']))"` still prints `49`.
  - Hard-refresh `http://upci.b8.co.nz/events` in a browser:
    - Stats strip shows 4 tiles with non-zero `Upcoming` + `Past` (at 2026-04-20: Week=2, Month=2, Upcoming=35, Past=13 or similar).
    - Filter bar sticky under the nav as you scroll.
    - Default view (Upcoming) hides past events; Past count tile still shows total past.
    - Click a department pill → only that department's events; count in pill matches.
    - Click `MAY` jump chip → smooth scroll to "MAY 2026" section.
    - Card has a coloured left rail (pink for Ladies, blue for Missions, etc.) and a gradient date block.
    - Title reads larger; relative-time "in 5 days" appears next to "This week" pill.
    - Hover on a card → subtle lift + shadow growth.

---

## Testing Strategy

No JS tests (no infra). Browser-based smoke:

| Scenario | Expected |
|---|---|
| Click "All" tab | Past events re-appear greyed; stats unchanged |
| Click "Past" tab | Only past events listed; month jump chips reflect only past months |
| Click two department pills | Union (OR) of both — cards match either department |
| Click same pill twice | Pill toggles off |
| Click "Clear" | Pills reset; all events return |
| Click month chip that's dim | Nothing happens (`disabled`) |
| Click "MAY" when it has 3 events | Smooth-scroll, "MAY 2026" header visible below filter bar |
| No matches (filters collide) | Empty state shows with "Clear all filters" link |
| Multi-day event that spans months | Card still renders correctly with `DD–DD MMM–MMM YYYY` in date block |
| Event with null department | No rail colour (falls back to `bg-slate-300`); no pill in filter list |

### Edge cases

- [ ] `events` is empty → stats all zero, filter bar still renders with zero pills, empty state shows the existing "No upcoming events" branch (not the "No events match these filters" one — gate with `events.length`)
- [ ] API error → existing error state still renders (filter bar hidden)
- [ ] Browser with `backdrop-blur` unsupported → falls through to solid `bg-slate-50`
- [ ] `scrollIntoView({behavior: 'smooth'})` unsupported (older iOS) → jumps instantly; acceptable fallback
- [ ] User deselects all departments but leaves timeScope='past' and there are no past events → empty-filter state

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
**EXPECT**: `✓ built in <2s`; `Events-*.js` chunk larger; CSS bundle grows by a few kB for gradients + ring utilities.

### Level 3: API regression
```bash
curl -sS --resolve upci.b8.co.nz:80:127.0.0.1 -H "Accept: application/json" "http://upci.b8.co.nz/api/events" | python3 -c "import json,sys; print(len(json.load(sys.stdin)['data']))"
```
**EXPECT**: `49`.

### Level 4: Browser
See scenarios above.

---

## Acceptance Criteria

- [ ] Stats strip renders 4 tiles (This Week / This Month / Upcoming / Past) with live counts from the data.
- [ ] Clicking a stats tile sets the time scope filter (Past tile → Past; others → Upcoming).
- [ ] Filter bar is sticky under the Navbar (`top-28`), with time tabs, department pills (multi-select with counts), and month-jump chips.
- [ ] Month chips smooth-scroll to the target section and stay enabled/disabled based on current filters.
- [ ] Event cards show a coloured left rail keyed to the event's department `color_theme`, a gradient date block keyed to status, a relative-time hint, and lift/shadow on hover.
- [ ] Removing all filters restores the full list.
- [ ] Empty-filter state shows "No events match these filters" + reset button.
- [ ] No regression in `/api/events` (still 49 events, same shape).
- [ ] No regression in `/calendar`.

---

## Completion Checklist

- [ ] Task 1 — helper updated (relative-time + rail/gradient exports)
- [ ] Task 2 — filter state + computeds in `Events.vue` setup
- [ ] Task 3 — template additions (stats + filter bar + card restyle + empty state)
- [ ] Task 4 — `npm run build` passes
- [ ] Browser smoke across all 10 table rows above

---

## Risks and Mitigations

| Risk | Likelihood | Impact | Mitigation |
|---|---|---|---|
| Filter bar `top-28` + old inner `<header>` `sticky top-28` collide, producing double sticky | MED | LOW | Task 3 explicitly removes the inner `sticky`. Visual smoke catches if missed. |
| `Set` mutation not reactive | LOW | LOW | `toggleDepartment` re-assigns `new Set(...)` on every change (documented). |
| Month chip `scroll-margin-top` wrong → header lands under filter bar | MED | LOW | Using `scroll-mt-40` (160 px) — filter bar ≈ 128 px sticky + stats strip not sticky. Easy to adjust per visual. |
| `scrollIntoView({behavior: 'smooth'})` unsupported in older Safari | LOW | LOW | Falls back to instant jump; still functional. |
| Tailwind JIT misses new gradient classes (`from-amber-400`, `to-amber-600`, …) | LOW | LOW | All classes are literal strings in a `switch` — JIT scans them; this is the established pattern. |
| Filter bar too tall on narrow viewports (three rows: tabs + pills + jump) | MED | LOW | `flex-col gap-3` already scopes each row; on < 400 px the pills wrap naturally. Can collapse into accordion later. |
| Past count tile labelled "Past" but user may expect it to toggle past-only on click (it does) | LOW | LOW | Shown as a button with `hover:border-slate-400` to hint at interactivity. |
| Stats tile "This Week" counts events starting within 7 days — semantically that could include today's live event | LOW | LOW | Current computation includes `start <= weekEnd` which covers today and next 7 days; matches common "this week" usage. |

---

## Notes

- **Why multi-select on departments.** A visitor likely cares about "Youth + Children's" (they're a parent), not single-ministry drill-down. Multi-select is union (OR) semantics — match ≥1 department.
- **Why stats strip over a hero card.** The "next upcoming event" hero seemed tempting but would duplicate information already visible in the filtered list. Stats strip answers "is anything happening?" without preempting the list.
- **Why only the month chip row scrolls horizontally.** Twelve chips always fit on a desktop; on mobile they scroll-x naturally. The department pill row wraps vertically (pills grow in count gracefully if new departments are added).
- **Why not URL params for filter state.** Simpler first cut; can be added in a follow-up with `vue-router` query sync (`?dept=prayer&scope=past`). Keeps this plan tightly scoped.
- **Why `scroll-mt-40`.** Tailwind's `scroll-margin-top: 10rem` (160 px). The sticky filter bar is ~144 px tall in the worst case (three rows + padding). Testing will confirm or suggest `scroll-mt-48` (192 px) if needed.
- **Why keep `eventStatusClasses().dateBlock` even after adding `dateBlockGradient()`.** `Calendar.vue` still consumes `.dateBlock` for its "events this month" list. Leaving it intact avoids touching the calendar page.
- **Confidence: 9/10.** Only material unknowns are (a) precise `scroll-mt-*` value — trivial to tweak, (b) how the filter bar wraps on very narrow phones (will watch for a single edge case during browser smoke). Everything else is standard Vue 3 + Tailwind.
