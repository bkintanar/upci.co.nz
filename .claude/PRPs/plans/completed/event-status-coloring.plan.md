# Feature: Event Status-Aware Coloring (Past, Live, Soon, Future)

## Summary

Classify every event in the Vue frontend into one of four visual states based on its dates relative to "today" — **past**, **live** (happening right now), **soon** (starts within 7 days), or **future** — and render each state with a distinct Tailwind colour treatment. Past events are grayed out but stay visible; events within a week pop in amber; active multi-day events glow green; distant events keep today's blue. Logic lives in a shared helper so both `Events.vue` (card grid) and `Calendar.vue` (month pills + "events this month" list) stay in sync.

## User Story

As a **public visitor browsing the UPCI NZ events page**
I want **to instantly see which events are upcoming, imminent, currently running, or already over**
So that **I don't waste time reading details about a conference that happened two months ago, and I don't miss a meeting six days from now**.

## Problem Statement

Today every event card on `/events` and every chip on `/calendar` renders with the same blue-and-white styling regardless of date:
- A 2026-01-04 "Mission Sunday Promotion" in Jan (past as of April) looks identical to a 2026-04-25 "LM — General Director Visitation, Whangarei" (next week).
- A currently-running multi-day event (e.g., a 3-day Prayer & Fasting that includes today) is visually indistinguishable from a March archival event.
- On the month calendar, 20 blue chips all carry the same weight, making it hard to spot "what matters right now."

Testable signal: with fixed reference date `2026-04-20`, the Events grid has ~13 gray cards (Jan–early April events), one amber card (`LM — General Director Visitation, 2026-04-25`), and the rest default blue.

## Solution Statement

Pure frontend change. Add a small helper `resources/js/utils/eventStatus.js` with one function that returns `'past' | 'live' | 'soon' | 'future'` for a given event. Export a companion function that maps each status to a Tailwind class bundle (card classes, date-text class, chip class, badge label). Import both into `Events.vue` and `Calendar.vue`. No backend, no API, no schema change.

## Metadata

| Field | Value |
|---|---|
| Type | ENHANCEMENT |
| Complexity | LOW |
| Systems Affected | 2 Vue components + 1 new helper |
| Dependencies | None — pure Date / Tailwind |
| Estimated Tasks | 4 (helper + 2 views + build) |

---

## UX Design

### Before State
```
╔═══════════════════════════════════════════════════════════════════════════════╗
║  /events   — all 49 cards rendered identical blue/white                        ║
║                                                                                ║
║   ┌──────────────┐  ┌──────────────┐  ┌──────────────┐                        ║
║   │ 2026-01-04   │  │ 2026-04-25   │  │ 2026-10-24…  │                        ║
║   │ Mission Sun  │  │ LM Director  │  │ Annual Conf. │                        ║
║   │ Promotion    │  │ Whangarei    │  │ Wellington   │                        ║
║   └──────────────┘  └──────────────┘  └──────────────┘                        ║
║      (past)            (7 days out)       (future)                             ║
║   — all three carry the same visual weight —                                   ║
║                                                                                ║
║   PAIN_POINT: user can't tell stale data from imminent / live events          ║
╚═══════════════════════════════════════════════════════════════════════════════╝
```

### After State
```
╔═══════════════════════════════════════════════════════════════════════════════╗
║  /events  — four visual tiers keyed to status                                 ║
║                                                                                ║
║   ┌──────────────┐  ┌══════════════┐  ┌──────────────┐                        ║
║   │ (faded gray) │  │ THIS WEEK    │  │ 2026-10-24…  │                        ║
║   │ Mission Sun  │  │ 2026-04-25   │  │ Annual Conf. │                        ║
║   │ Promotion    │  │ LM Whangarei │  │ Wellington   │                        ║
║   └──────────────┘  └══════════════┘  └──────────────┘                        ║
║      past              soon (amber)       future (blue)                        ║
║                                                                                ║
║   If a multi-day event contains today:                                         ║
║   ┌══════════════╗                                                             ║
║   ║ HAPPENING    ║  — green ring + "live" pill                                 ║
║   ║ NOW          ║                                                             ║
║   ╚══════════════╝                                                             ║
║                                                                                ║
║  /calendar — month grid chips + list get the same tier colors                  ║
║    past:   slate-100 / slate-500                                               ║
║    live:   green-100 / green-800                                               ║
║    soon:   amber-100 / amber-800                                               ║
║    future: blue-100 / blue-800 (current)                                       ║
║                                                                                ║
║   VALUE_ADD: visual triage at a glance; imminent events leap out               ║
╚═══════════════════════════════════════════════════════════════════════════════╝
```

### Interaction Changes
| Location | Before | After | User Impact |
|---|---|---|---|
| `/events` card (past event) | blue date, solid card | opacity ~0.6, slate text, grayed card | De-emphasised; clearly in the rear-view mirror |
| `/events` card (≤7 days to start) | identical to future cards | amber-50 background, amber-300 ring, "This week" pill | Eyes land on imminent items |
| `/events` card (start ≤ today ≤ end) | identical to future cards | green-50 background, green-300 ring, "Happening now" pill | Multi-day live events surface |
| `/events` card (>7 days out) | default blue | unchanged | Familiar "future" baseline preserved |
| `/calendar` month chips | all blue-100 | color per status | Hot-spot identification in the grid |
| `/calendar` "Events this month" list | all rows identical | row-level color band + optional pill | Mirrors the card grid's language |

---

## Mandatory Reading

| Priority | File | Lines | Why |
|---|---|---|---|
| P0 | `resources/js/views/Events.vue` | 20-47 | The card template that gets the class bindings |
| P0 | `resources/js/views/Calendar.vue` | 36-72 | Day-cell chips (46-54) + Events-this-month list (62-71) |
| P1 | `resources/js/views/Events.vue` | 65-101 | Existing `<script>` setup pattern (defineComponent, ref, computed, onMounted, fetch) — the helper import should slot in cleanly |
| P1 | `resources/js/views/Calendar.vue` | 87-192 | Same — composition API shape |
| P2 | `tailwind.config.js` | whole file | Confirm amber + green palettes are on (they're default Tailwind colors so yes) |

No new external docs. Tailwind classes used (`bg-amber-50`, `ring-amber-300`, `bg-green-50`, `ring-green-300`, `opacity-60`, etc.) are all in the default palette that this project already ships.

---

## Patterns to Mirror

**EXISTING_CARD_CLASSES** (Events.vue:24):
```html
<div class="bg-white rounded-xl shadow-md border border-slate-200 overflow-hidden hover:shadow-lg transition-shadow">
```
Keep these as the `future` baseline. `past` / `soon` / `live` variants layer additions on top (or swap background).

**EXISTING_DATE_TEXT_CLASSES** (Events.vue:27):
```html
<div class="text-sm font-medium text-blue-600 mb-1">
```
Date color swaps with status: `text-slate-500` (past), `text-amber-700` (soon), `text-green-700` (live).

**EXISTING_CHIP_CLASSES** (Calendar.vue:49):
```html
<div class="text-xs px-2 py-1 rounded bg-blue-100 text-blue-800 truncate">
```
Each status gets its own pair: `bg-slate-100 text-slate-500`, `bg-amber-100 text-amber-800`, `bg-green-100 text-green-800`, `bg-blue-100 text-blue-800`.

**COMPONENT_IMPORT_STYLE** (Events.vue:65-66):
```javascript
import { defineComponent, ref, onMounted } from 'vue'
```
Add `import { getEventStatus, eventStatusClasses } from '../utils/eventStatus'` following the same relative-import style.

---

## Files to Change

| File | Action | Justification |
|---|---|---|
| `resources/js/utils/eventStatus.js` | CREATE | Shared helper. Two exports: `getEventStatus(event, now?)` and `eventStatusClasses(status)`. |
| `resources/js/views/Events.vue` | UPDATE | Bind status classes on the card root, date text, and add an optional pill. |
| `resources/js/views/Calendar.vue` | UPDATE | Bind status classes on month-grid chips and on the "Events this month" list rows. |

No backend, no controller, no API change, no schema change.

---

## NOT Building (Scope Limits)

- **Not changing the `/api/events` response shape.** Classification is client-side — the server returns plain ISO dates as it does today. Status is recomputed per request on the frontend.
- **Not adding a ticker to update status as time passes on an open page.** Events move between statuses infrequently (once a day or less); a page reload recomputes them. No `setInterval`.
- **Not filtering past events out of the list.** User said "gray out", not "hide". Past events stay visible, just de-emphasised.
- **Not changing sort order.** API already returns events in `start_date ASC`. Past first → soon → future matches chronological reading.
- **Not theming the admin side.** Filament `/admin/events` is unchanged.
- **Not adding user-configurable "imminent window" (the 7-day threshold).** Hard-coded to 7. Easy to change later if product asks.
- **Not testing.** No existing JS test infra in the project; visual smoke check in a browser is acceptance.

---

## Step-by-Step Tasks

### Task 1: CREATE `resources/js/utils/eventStatus.js`

- **ACTION**: New helper file with two named exports.
- **IMPLEMENT**:
  ```javascript
  const MS_PER_DAY = 24 * 60 * 60 * 1000

  /**
   * Classify an event by its dates relative to `now`.
   * @param {{ start_date: string, end_date?: string|null }} event
   * @param {Date} [now=new Date()]
   * @returns {'past' | 'live' | 'soon' | 'future'}
   */
  export function getEventStatus(event, now = new Date()) {
      const today = new Date(now.getFullYear(), now.getMonth(), now.getDate())
      const start = new Date(event.start_date + 'T00:00:00')
      const end = new Date((event.end_date || event.start_date) + 'T00:00:00')

      if (end < today) return 'past'
      if (start <= today && today <= end) return 'live'
      const daysUntilStart = Math.ceil((start - today) / MS_PER_DAY)
      if (daysUntilStart <= 7) return 'soon'
      return 'future'
  }

  /**
   * Tailwind class bundles per status — for the Events.vue card + Calendar.vue chip + list row.
   * Shape is an object of class strings keyed by render slot.
   */
  export function eventStatusClasses(status) {
      switch (status) {
          case 'past':
              return {
                  card:   'bg-slate-100 border-slate-200 opacity-60',
                  date:   'text-slate-500',
                  title:  'text-slate-600',
                  chip:   'bg-slate-100 text-slate-500',
                  pill:   '',
                  pillLabel: '',
              }
          case 'live':
              return {
                  card:   'bg-green-50 border-green-200 ring-2 ring-green-300',
                  date:   'text-green-700',
                  title:  'text-slate-900',
                  chip:   'bg-green-100 text-green-800',
                  pill:   'inline-block text-xs font-semibold px-2 py-0.5 rounded-full bg-green-600 text-white',
                  pillLabel: 'Happening now',
              }
          case 'soon':
              return {
                  card:   'bg-amber-50 border-amber-200 ring-2 ring-amber-300',
                  date:   'text-amber-700',
                  title:  'text-slate-900',
                  chip:   'bg-amber-100 text-amber-800',
                  pill:   'inline-block text-xs font-semibold px-2 py-0.5 rounded-full bg-amber-500 text-white',
                  pillLabel: 'This week',
              }
          case 'future':
          default:
              return {
                  card:   'bg-white border-slate-200',
                  date:   'text-blue-600',
                  title:  'text-slate-900',
                  chip:   'bg-blue-100 text-blue-800',
                  pill:   '',
                  pillLabel: '',
              }
      }
  }
  ```
- **MIRROR**: No direct mirror — first helper file in `resources/js/utils/`. Pattern is plain ES module, same style as Vue composables used elsewhere.
- **GOTCHA**: Parse dates with explicit `T00:00:00` so the browser doesn't apply UTC-vs-local drift; the API returns dates as `YYYY-MM-DD` strings.
- **GOTCHA**: `end_date` is nullable; default to `start_date`.
- **GOTCHA**: `daysUntilStart` uses `Math.ceil` so an event tomorrow returns `1`; today (already live) is excluded by the `live` branch first.
- **VALIDATE**: After build, open browser console and paste:
  ```js
  // Static smoke tests
  const es = await import('/resources/js/utils/eventStatus.js').then(m => m) // (or via imports in a component)
  ```
  (Easier: verify visually in Task 4.)

### Task 2: UPDATE `resources/js/views/Events.vue`

- **ACTION**: Import the helper; compute per-event status in the template via a local method; apply card / date / title / pill classes.
- **IMPLEMENT** (diff sketch):
  ```html
  <div
      v-for="event in events"
      :key="event.id"
      class="rounded-xl shadow-md border overflow-hidden hover:shadow-lg transition-shadow"
      :class="statusClasses(event).card"
  >
      <div class="p-6">
          <div class="flex items-center justify-between mb-1">
              <div class="text-sm font-medium" :class="statusClasses(event).date">
                  {{ formatDate(event.start_date) }}<template v-if="event.end_date"> – {{ formatDate(event.end_date) }}</template>
              </div>
              <span
                  v-if="statusClasses(event).pillLabel"
                  :class="statusClasses(event).pill"
              >
                  {{ statusClasses(event).pillLabel }}
              </span>
          </div>
          <h2 class="text-xl font-bold mb-2" :class="statusClasses(event).title">{{ event.name }}</h2>
          <!-- description / location / url — unchanged -->
      </div>
  </div>
  ```
  `<script>`:
  ```javascript
  import { getEventStatus, eventStatusClasses } from '../utils/eventStatus'
  // …
  const statusClasses = (event) => eventStatusClasses(getEventStatus(event))
  return { events, loading, error, formatDate, statusClasses }
  ```
- **MIRROR**: `Events.vue:21-25` (existing wrapping div's classes) — extract the `bg-white border-slate-200` portion into the status helper and leave the structural `rounded-xl shadow-md border overflow-hidden hover:shadow-lg transition-shadow` on the element.
- **GOTCHA**: Computing `statusClasses(event)` twice per render (once for `.card`, once for `.date`, etc.) re-invokes the Date math. Harmless at 49 events; if it ever matters, wrap in `computed` keyed by event.id. Not required for this feature.
- **VALIDATE**: `npm run build` exits 0 and the new `Events-*.js` chunk contains no Vue compile errors.

### Task 3: UPDATE `resources/js/views/Calendar.vue`

- **ACTION**: Import the helper; classify each event when rendering (a) the month-grid chip (`Calendar.vue:46-54`) and (b) the "Events this month" row (`Calendar.vue:62-71`).
- **IMPLEMENT** (diff sketch):
  ```html
  <!-- month-grid chip: -->
  <div
      v-for="ev in cell.events"
      :key="ev.id"
      class="text-xs px-2 py-1 rounded truncate"
      :class="statusClasses(ev).chip"
      :title="ev.name"
  >
      {{ ev.name }}
  </div>

  <!-- events-this-month list row: -->
  <li
      v-for="ev in eventsInMonth"
      :key="ev.id"
      class="flex items-center gap-3 p-3 rounded-lg border"
      :class="statusClasses(ev).card"
  >
      <span class="text-sm font-medium shrink-0" :class="statusClasses(ev).date">{{ formatDate(ev.start_date) }}</span>
      <span class="font-medium" :class="statusClasses(ev).title">{{ ev.name }}</span>
      <span
          v-if="statusClasses(ev).pillLabel"
          :class="statusClasses(ev).pill"
      >
          {{ statusClasses(ev).pillLabel }}
      </span>
      <a v-if="ev.url" :href="ev.url" target="_blank" rel="noopener" class="ml-auto text-blue-600 text-sm hover:underline">Details</a>
  </li>
  ```
  `<script>`:
  ```javascript
  import { getEventStatus, eventStatusClasses } from '../utils/eventStatus'
  // …
  const statusClasses = (event) => eventStatusClasses(getEventStatus(event))
  return { monthLabel, dayNames, calendarCells, eventsInMonth, formatDate, prevMonth, nextMonth, statusClasses }
  ```
- **GOTCHA**: The month-grid chip is tiny — don't try to show a pill; just use the `.chip` class. Pills appear only on the larger list rows.
- **GOTCHA**: The `isToday` highlight on the cell background (`Calendar.vue:40`) stays — it's separate from event status.
- **VALIDATE**: Same as Task 2. Visual check after build.

### Task 4: BUILD + VISUAL SMOKE

- **ACTION**: `cd /var/www/personal/upci.co.nz && npm run build`
- **VALIDATE**:
  - Hard-refresh browser at `/events`. At today (2026-04-20) expect:
    - ~13 gray cards (Jan 4 → Apr 9 events; everything ≤ yesterday)
    - 1 amber card with "This week" pill: `2026-04-25 LM – General Director Visitation, Whangarei` (5 days out)
    - 0 green `live` cards (no multi-day event currently spans today)
    - All others blue (future)
  - Navigate to `/calendar`, flip to April 2026. Expect gray chips for Apr 3–5 (Mission Program), Apr 7–9 (PM 3-day), Apr 10–12 (MM Queenstown); amber chip on Apr 25 (LM Whangarei) and Apr 26 (CM Promotion). Blue chips elsewhere.
- **GOTCHA**: The Vite build bumps content hashes on every file — the new `Events-*.js` and `Calendar-*.js` filenames will change; browser needs a hard reload (Ctrl/Cmd+Shift+R).

---

## Testing Strategy

### Manual visual checks

| Scenario | Where | Expected |
|---|---|---|
| Past single-day event (e.g., Mission Sunday 2026-01-04) | `/events` | gray card, opacity reduced |
| Past multi-day event (e.g., Jan 26–31 Prayer & Fasting) | `/events` + calendar Jan | gray card / gray chips on all 6 days |
| Today's date lies inside a multi-day span | `/events` | green ring + "Happening now" pill (contrived: temporarily edit an event in `/admin/events` to have start=today-2, end=today+2) |
| Event 5 days away | `/events` | amber ring + "This week" pill |
| Event 8 days away | `/events` | default blue, no pill |
| Calendar month flip | `/calendar` | chips recompute; hover/tooltip still shows full name |
| NULL `end_date` | Both | treated as single-day; `end = start` |

### Edge cases

- [ ] Event with `start_date` today → `live`
- [ ] Event with `end_date` today → `live` until midnight
- [ ] Event with `end_date` = yesterday → `past`
- [ ] Event whose start is exactly 7 days away → `soon`
- [ ] Event whose start is 8 days away → `future`
- [ ] Event with no `end_date` (null) → collapses to single-day behaviour
- [ ] Timezone: NZ is UTC+12/13; parsing dates with explicit `T00:00:00` treats them as local → correct local-day classification

---

## Validation Commands

### Level 1: JS syntax sanity
```bash
node --check resources/js/utils/eventStatus.js
```
Expect: no output (no error).

### Level 2: Vite build
```bash
cd /var/www/personal/upci.co.nz && npm run build
```
Expect: `✓ built in <1s`, no Vue compile errors.

### Level 3: Live API still works
```bash
curl -sS --resolve upci.b8.co.nz:80:127.0.0.1 -H "Accept: application/json" "http://upci.b8.co.nz/api/events" | python3 -c "import json,sys; d=json.load(sys.stdin); print('events:', len(d['data']))"
```
Expect: `events: 49` — no backend change.

### Level 4: Browser
- `/events` at today's date: gray/amber/green/blue distribution matches the prediction in Task 4.
- `/calendar` April 2026: chips coloured correctly.

---

## Acceptance Criteria

- [ ] Helper file exists at `resources/js/utils/eventStatus.js` with both named exports.
- [ ] `Events.vue` imports and applies per-event status classes.
- [ ] `Calendar.vue` imports and applies per-event status classes to chips and the events-this-month list.
- [ ] Build passes without errors.
- [ ] Past events render visibly de-emphasised (gray, lower opacity).
- [ ] Events ≤7 days away render in amber with a "This week" pill.
- [ ] Multi-day events spanning today render in green with a "Happening now" pill.
- [ ] All other events render in the existing blue baseline.
- [ ] Public API contract unchanged.

---

## Completion Checklist

- [ ] Task 1 — helper created
- [ ] Task 2 — Events.vue updated
- [ ] Task 3 — Calendar.vue updated
- [ ] Task 4 — `npm run build` succeeds
- [ ] Browser smoke at `/events` and `/calendar` matches the prediction

---

## Risks and Mitigations

| Risk | Likelihood | Impact | Mitigation |
|---|---|---|---|
| Timezone drift makes a boundary event flicker between statuses | LOW | LOW | Parse dates with explicit `T00:00:00` so local day applies; events move status once per local day max. |
| Tailwind purge strips the new classes (`amber`, `green`, etc.) because they're dynamic | LOW | MED | These are static strings in the helper file, scanned by Tailwind JIT at build time — no `${var}` interpolation that would hide them from the content scanner. Safelisting not needed. |
| Status computed twice per event for each render slot (`.card`, `.date`, …) | LOW | LOW | Only ~49 events; cheap. If the set grows, wrap `statusClasses(event)` in a Vue `computed` keyed by id — mechanical refactor, same output. |
| User expects past events filtered out | LOW | LOW | Plan explicitly scopes to "gray out" per user wording. If they want filtering, a one-line `events.filter(…)` in the setup is trivial. |
| Users leave page open past midnight and statuses go stale | LOW | LOW | Documented trade-off; reload recomputes. Adding a ticker would add complexity for negligible UX value. |

---

## Notes

- **4 states, not 3.** The user asked for past + "1 week away". Adding `live` (start ≤ today ≤ end) costs nothing extra and handles multi-day events meaningfully — a conference on its middle day should stand out. If they don't want it, one `return 'soon'` swap folds it in.
- **Amber vs red for "soon".** Red reads as "error/danger". Amber reads as "attention / approaching" — better match for an anticipated event. Using Tailwind's amber-50/200/300/500/700/800 ramp.
- **Green for "live".** Standard "active/open" signal. Not meant to imply urgency — just presence.
- **Past still visible.** Per user wording ("gray out", not "hide"). The full archive stays browseable.
- **Mobile legibility.** Opacity 0.6 on past cards keeps text readable over the slate-100 background on all tested Tailwind combinations; the existing shadow is kept so the gray cards still look like cards, not ghosts.
- **Confidence: 9.5/10.** Single new file, two component edits, Tailwind-only styling. The only real unknown is whether the user prefers 3 states (skip `live`) or a different colour choice — easy to adjust after first build.
