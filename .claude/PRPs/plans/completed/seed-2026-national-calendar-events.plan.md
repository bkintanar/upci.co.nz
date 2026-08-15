# Feature: Seed 2026 National Calendar Events (One-Time Script)

## Summary

Create a one-time, idempotent Laravel seeder that loads all 47 events from the PDF `01 NATIONAL CALENDAR OF EVENTS 2026.pdf` into the `events` table. Each event is keyed on a deterministic slug so the seeder can be re-run safely (via `firstOrCreate`). Ministry-prefixed events (CM, LM, MM, YM, PM, Mission) are linked to their existing department via `department_id`; administrative / cross-department events (ABC, AMM, AGM, AGC, MTD) are left as `department_id = NULL`. Runs via `php artisan db:seed --class=NationalCalendar2026Seeder`.

## User Story

As a **UPCI NZ national administrator**
I want **the full 2026 calendar loaded into the admin events list in one step**
So that **local / regional / national users can see the aggregated national calendar without 47 manual form submissions, and the public `/events` + `/calendar` pages render the right data**.

## Problem Statement

The existing `events` table has 2 placeholder rows from `EventSeeder.php` (seeded with `now()->year` — resolved to `2026-01-15` and `2026-03-01`). The 47 real events from the published PDF calendar have never been loaded. Operators would otherwise need to open `/admin/events/create` 47 times.

Success signal: after running the seeder, `SELECT COUNT(*) FROM events WHERE YEAR(start_date) = 2026` returns ≥ 47, every event has a unique slug, and `GET /api/events` returns them in date order for the public frontend.

## Solution Statement

One seeder file: `database/seeders/NationalCalendar2026Seeder.php`. Inside, a flat PHP array of 47 event definitions with normalised `start_date` / `end_date` / `location` / ministry department slug. The seeder resolves each department slug to an id via a single in-memory lookup, then calls `Event::firstOrCreate(['slug' => …], [rest])` for each record. No migration (schema already in place). No API or UI changes. No test suite (one-off data load, verified by a SELECT).

## Metadata

| Field            | Value |
| ---------------- | ----- |
| Type             | NEW_CAPABILITY (one-time data seed) |
| Complexity       | LOW |
| Systems Affected | `events` table only |
| Dependencies     | None — existing Laravel 11 + Filament v4 + SQLite stack |
| Estimated Tasks  | 2 (write seeder + run it) |

---

## UX Design

### Before State
```
╔═══════════════════════════════════════════════════════════════════════════════╗
║                              BEFORE STATE                                      ║
╠═══════════════════════════════════════════════════════════════════════════════╣
║                                                                               ║
║   /admin/events                                                               ║
║   ┌──────────────────────────────────────────┐                               ║
║   │ #1  General Conference          2026-01-15 │                             ║
║   │ #2  Annual Minister's Meeting   2026-03-01 │                             ║
║   │ (2 placeholder rows from EventSeeder.php)   │                             ║
║   └──────────────────────────────────────────┘                               ║
║                                                                               ║
║   Public /events, /calendar → 2 entries, neither correct                      ║
║   PAIN_POINT: 47 events from the published PDF not in the system.             ║
║                                                                               ║
╚═══════════════════════════════════════════════════════════════════════════════╝
```

### After State
```
╔═══════════════════════════════════════════════════════════════════════════════╗
║                               AFTER STATE                                      ║
╠═══════════════════════════════════════════════════════════════════════════════╣
║                                                                               ║
║   Operator runs:                                                              ║
║     php artisan db:seed --class=NationalCalendar2026Seeder                    ║
║                                                                               ║
║   /admin/events shows 47 + 2 = 49 rows (existing 2 kept, idempotent on slug). ║
║   Ordered by start_date:                                                      ║
║     Mission Sunday Promotion  (Jan 4)    → Missions Dept                      ║
║     General Conference        (Jan 15)   ← existing placeholder               ║
║     National 7 Day Prayer…    (Jan 26–31)→ Prayer Ministry                    ║
║     ABC – Enrollment Closed   (Jan 31)   → (no dept)                          ║
║     Mission Sunday Promotion  (Feb 1)    → Missions Dept                      ║
║     …etc through Dec                                                          ║
║                                                                               ║
║   VALUE_ADD: full 2026 calendar accurate in admin + public endpoints.         ║
║   DATA_FLOW:                                                                  ║
║     Seeder class → Event::firstOrCreate(slug, attrs) → events table.          ║
║     Re-run safe: slugs are deterministic ("mission-sunday-promo-2026-01-04").║
║                                                                               ║
╚═══════════════════════════════════════════════════════════════════════════════╝
```

### Interaction Changes
| Location | Before | After | User Impact |
|----------|--------|-------|-------------|
| `/admin/events` list | 2 rows | 49 rows | National user sees correct calendar |
| Public `/events` (aggregated view) | 2 placeholder cards | 47 real events | End users can browse the year |
| Public `/calendar` (month grid) | mostly empty | populated | End users see the year-at-a-glance |
| `GET /api/events` JSON | 2 objects | 49 objects | Frontend renders correctly |

---

## Mandatory Reading

| Priority | File | Lines | Why |
|----------|------|-------|-----|
| P0 | `database/seeders/EventSeeder.php` | 1-40 | Exact `firstOrCreate` pattern to mirror |
| P0 | `database/seeders/MenuItemSeeder.php` | — | Second example of idempotent seeder (if present) |
| P0 | `app/Models/Event.php` | 1-41 | Fillable fields, casts, department relation |
| P1 | `database/migrations/2026_03_10_000005_create_events_table.php` | 11-24 | Column list, nullability, defaults |
| P1 | `database/migrations/2026_04_19_000003_add_department_id_to_events_table.php` | — | Confirms `department_id` is a nullable FK to departments |
| P1 | `database/migrations/2026_04_19_000004_seed_departments_and_menu_items.php` | 10-59 | Confirms department slugs: `mens`, `ladies`, `missions`, `youth`, `childrens`, `prayer` |
| P2 | The PDF at `01 NATIONAL CALENDAR OF EVENTS 2026.pdf` | all | Source of truth for dates / names / locations |

**External Documentation:**
| Source | Section | Why |
|--------|---------|-----|
| [Laravel Seeding](https://laravel.com/docs/11.x/seeding#running-seeders) | Running a single seeder | `db:seed --class=` syntax |
| [Eloquent firstOrCreate](https://laravel.com/docs/11.x/eloquent#retrieving-or-creating-models) | "Retrieve or Create Models" | Idempotent pattern for repeat runs |

---

## Patterns to Mirror

**SEEDER_SHAPE:**
```php
// SOURCE: database/seeders/EventSeeder.php:8-39
class EventSeeder extends Seeder
{
    public function run(): void
    {
        Event::firstOrCreate(
            ['slug' => 'general-conference'],
            [
                'name' => 'General Conference',
                'description' => 'UPCI New Zealand General Conference.',
                'start_date' => now()->year . '-01-15',
                'end_date' => null,
                'location' => null,
                'url' => null,
                'is_published' => true,
                'sort_order' => 1,
            ]
        );
        // …
    }
}
```

**DEPARTMENT_LOOKUP_CACHE** (avoid N+1 in the loop):
```php
$deptIds = \App\Models\Department::pluck('id', 'slug'); // ['mens' => 1, 'ladies' => 2, …]
// Later: 'department_id' => $deptIds[$row['dept_slug']] ?? null,
```

---

## Files to Change

| File | Action | Justification |
|------|--------|---------------|
| `database/seeders/NationalCalendar2026Seeder.php` | CREATE | The whole feature. |

No migrations. No model changes. No API/UI changes.

---

## NOT Building (Scope Limits)

- **Not registering the seeder in `DatabaseSeeder::run()`.** It's a one-time load; auto-running it on fresh installs of future dev environments is fine, but not a priority. If the user wants it auto-included, one line can be added later.
- **Not deleting the 2 existing placeholder events** (`general-conference`, `annual-ministers-meeting`). They have their own slugs; new seeder uses different slugs. Let the operator decide whether to prune them.
- **Not editing the PDF parsing or storing the PDF anywhere.** The PDF is our source; data is transcribed manually into the seeder definition.
- **Not creating per-location duplicate rows for multi-location events.** JBQ Mini-tourneys on the same day in two cities stay as ONE event with `location = "Auckland / Whangarei"` (slash-separated). AYC (Jun 27 – Jul 6, multi-city) is one event with combined location. This matches the PDF's own formatting.
- **Not adding a `theme` / `series` column.** The "Pentecost EveryDay – A Sustainable Annual Harvest" theme that repeats under every Mission Sunday row is folded into the `description` field rather than a new column.
- **Not scheduling events as recurring.** Each Mission Sunday row is a discrete event with its own start_date. Recurring-event support would require a schema change.
- **Not writing tests.** This is a one-time data load; acceptance is "after running, `SELECT COUNT(*)` ≥ 47 and spot-checks of dates/locations look right". Adding PHPUnit tests for seed data would be over-engineering.

---

## Dataset

The full 47-row dataset with derived slug and department:

**Slug format**: `kebab-case(event_name)-YYYY-MM-DD` using the **start date**. Multi-day events use the start date in the slug.

**Department slug → id** (from existing rows — verify with SELECT at seed time):
| Ministry prefix | Department slug | Typical name |
|---|---|---|
| `CM` | `childrens` | Children's Ministry |
| `LM` | `ladies` | Ladies' Department |
| `MM` | `mens` | Men's Department |
| `YM` | `youth` | Youth Ministry |
| `PM` | `prayer` | Prayer Ministry |
| `Mission Sunday` / `Mission Program` | `missions` | World Missions Department |
| `AYC` (Apostolic Youth Corp) | `youth` | Youth Ministry |
| `ABC` / `AMM` / `AGM` / `AGC` / `MTD` | — | (national / admin, no dept) |

**Events table** (ordered by start_date, department slug = `null` unless noted):

| # | name | start_date | end_date | location | dept | description |
|---|------|------------|----------|----------|------|-------------|
| 1  | Mission Sunday Promotion                           | 2026-01-04 | null       | Nationwide                                 | missions  | Theme: Pentecost EveryDay – A Sustainable Annual Harvest |
| 2  | National 7 Day Prayer & Fasting                    | 2026-01-26 | 2026-01-31 | Nationwide                                 | prayer    | |
| 3  | ABC – Enrollment Closed                            | 2026-01-31 | null       | Nationwide                                 | null      | Apostolic Bible College enrolment deadline. |
| 4  | Mission Sunday Promotion                           | 2026-02-01 | null       | Nationwide                                 | missions  | Theme: Pentecost EveryDay – A Sustainable Annual Harvest |
| 5  | ABC – Teachers Training Seminar                    | 2026-02-06 | 2026-02-07 | Hamilton                                   | null      | |
| 6  | PM – Central Region, Waikato (Pastors & Teams)     | 2026-02-14 | null       | Storehouse Chapel — 4pm                    | prayer    | Pastors, Prayer Coordinator, prayer teams. |
| 7  | CM – Promotion                                     | 2026-02-15 | null       | Nationwide                                 | childrens | |
| 8  | Annual Ministers Meeting (AMM) — Ministers Seminar | 2026-02-21 | null       | Tauranga                                   | null      | |
| 9  | PM – PAC Regional Prayer & Fasting                 | 2026-02-28 | 2026-03-02 | Nationwide                                 | prayer    | |
| 10 | Mission Sunday Promotion                           | 2026-03-01 | null       | Nationwide                                 | missions  | Theme: Pentecost EveryDay – A Sustainable Annual Harvest |
| 11 | ABC – Classes Commence                             | 2026-03-03 | null       | Virtual / Nationwide                       | null      | |
| 12 | LM – General Director Visitation                   | 2026-03-28 | null       | South Island (AOC / POR)                   | ladies    | |
| 13 | Mission Program                                    | 2026-04-03 | 2026-04-05 | Rangiora                                   | missions  | |
| 14 | Mission Sunday Promotion                           | 2026-04-05 | null       | Nationwide                                 | missions  | Theme: Pentecost EveryDay – A Sustainable Annual Harvest |
| 15 | PM – 3 Day Prayer & Fasting (General Men's Conf.)  | 2026-04-07 | 2026-04-09 | Nationwide                                 | prayer    | Aligned with the General Men's Conference. |
| 16 | MM – Apostolic Men's Conference                    | 2026-04-10 | 2026-04-12 | Queenstown                                 | mens      | |
| 17 | LM – General Director Visitation                   | 2026-04-25 | null       | Whangarei                                  | ladies    | |
| 18 | CM – Promotion                                     | 2026-04-26 | null       | Nationwide                                 | childrens | |
| 19 | Mission Sunday Promotion                           | 2026-05-03 | null       | Nationwide                                 | missions  | Theme: Pentecost EveryDay – A Sustainable Annual Harvest |
| 20 | LM – General Director Visitation                   | 2026-05-09 | null       | ALC Bay of Plenty / SFC Hawkes Bay         | ladies    | |
| 21 | YM – Nth Island Youth Rally                        | 2026-05-16 | null       | Hamilton                                   | youth     | |
| 22 | PM – Prayer Breakfast                              | 2026-06-06 | null       | Christchurch                               | prayer    | |
| 23 | Mission Sunday Promotion                           | 2026-06-07 | null       | Nationwide                                 | missions  | Theme: Pentecost EveryDay – A Sustainable Annual Harvest |
| 24 | Minister's Training Development (MTD)              | 2026-06-13 | null       | Virtual Session                            | null      | |
| 25 | AYC                                                | 2026-06-27 | 2026-07-06 | Auckland / Dunedin / Christchurch          | youth     | Apostolic Youth Corp. |
| 26 | CM – JBQ Mini-tourney                              | 2026-07-04 | null       | Auckland / Whangarei                       | childrens | |
| 27 | Mission Sunday Promotion                           | 2026-07-05 | null       | Nationwide                                 | missions  | Theme: Pentecost EveryDay – A Sustainable Annual Harvest |
| 28 | LM – General Director Visitation                   | 2026-07-11 | null       | Waikato (Grace / Storehouse)               | ladies    | |
| 29 | YM – South Island Youth Rally                      | 2026-07-11 | null       | Christchurch                               | youth     | |
| 30 | CM – JBQ Mini-tourney                              | 2026-07-18 | null       | Hamilton / Tauranga                        | childrens | |
| 31 | CM – Promotion                                     | 2026-07-26 | null       | Nationwide                                 | childrens | |
| 32 | Mission Sunday Promotion                           | 2026-08-02 | null       | Nationwide                                 | missions  | Theme: Pentecost EveryDay – A Sustainable Annual Harvest |
| 33 | LM – General Director Visitation                   | 2026-08-08 | null       | Auckland (NZ Family / El Shaddai / SSPF / Walk by Faith) | ladies | |
| 34 | CM – JBQ Mini-tourney                              | 2026-08-15 | null       | Wellington / Hawkes Bay                    | childrens | |
| 35 | MM – Men's Online Virtual Training                 | 2026-08-22 | null       | Nationwide (online)                        | mens      | |
| 36 | Mission Sunday Promotion                           | 2026-09-06 | null       | Nationwide                                 | missions  | Theme: Pentecost EveryDay – A Sustainable Annual Harvest |
| 37 | LM – General Director Visitation                   | 2026-09-19 | null       | Wellington (CTW)                           | ladies    | |
| 38 | CM – JBQ Mini-tourney                              | 2026-09-26 | null       | South Island (AOC / POR)                   | childrens | |
| 39 | PM – 22 Day Prayer & Fasting (General Conference)  | 2026-10-01 | 2026-10-22 | Nationwide                                 | prayer    | |
| 40 | Mission Sunday Promotion                           | 2026-10-04 | null       | Nationwide                                 | missions  | Theme: Pentecost EveryDay – A Sustainable Annual Harvest |
| 41 | Executive Board Meeting                            | 2026-10-22 | null       | Wellington                                 | null      | Pre-AGC. |
| 42 | Annual General Meeting (AGM) & Ministers Seminar   | 2026-10-23 | null       | Wellington                                 | null      | Day before the Annual General Conference. |
| 43 | Annual General Conference                          | 2026-10-24 | 2026-10-25 | Wellington                                 | null      | |
| 44 | Mission Sunday Promotion                           | 2026-11-01 | null       | Nationwide                                 | missions  | Theme: Pentecost EveryDay – A Sustainable Annual Harvest |
| 45 | MM – Men's Online Virtual Training                 | 2026-11-14 | null       | Nationwide (online)                        | mens      | |
| 46 | CM – Promotion                                     | 2026-11-22 | null       | Nationwide                                 | childrens | |
| 47 | Mission Sunday Promotion                           | 2026-12-06 | null       | Nationwide                                 | missions  | Theme: Pentecost EveryDay – A Sustainable Annual Harvest |

**`is_published`**: `true` for all 47.
**`sort_order`**: `100 + row index` so they sort AFTER the 2 existing placeholders if someone orders by `sort_order` alone — but the admin table already sorts by `start_date` so this is largely cosmetic.
**`url`**: `null` for all 47 (no external links in the PDF).

---

## Step-by-Step Tasks

### Task 1: CREATE `database/seeders/NationalCalendar2026Seeder.php`

- **ACTION**: Write one seeder class with an inline `$events` array matching the 47-row dataset above.
- **MIRROR**: `database/seeders/EventSeeder.php:8-39` (firstOrCreate pattern).
- **IMPLEMENT** (sketch):
  ```php
  <?php

  namespace Database\Seeders;

  use App\Models\Department;
  use App\Models\Event;
  use Illuminate\Database\Seeder;
  use Illuminate\Support\Str;

  class NationalCalendar2026Seeder extends Seeder
  {
      public function run(): void
      {
          $deptIds = Department::pluck('id', 'slug'); // ['missions' => 3, …]

          $theme = 'Theme: Pentecost EveryDay – A Sustainable Annual Harvest';

          $events = [
              // [name, start_date, end_date?, location, department_slug?, description?]
              ['Mission Sunday Promotion',                           '2026-01-04', null,         'Nationwide',                                 'missions',  $theme],
              ['National 7 Day Prayer & Fasting',                    '2026-01-26', '2026-01-31', 'Nationwide',                                 'prayer',    null],
              ['ABC – Enrollment Closed',                            '2026-01-31', null,         'Nationwide',                                 null,        'Apostolic Bible College enrolment deadline.'],
              // …all 47 rows…
          ];

          foreach ($events as $i => [$name, $start, $end, $location, $deptSlug, $description]) {
              $slug = Str::slug($name.' '.$start);

              Event::firstOrCreate(
                  ['slug' => $slug],
                  [
                      'name'         => $name,
                      'description'  => $description,
                      'start_date'   => $start,
                      'end_date'     => $end,
                      'location'     => $location,
                      'url'          => null,
                      'is_published' => true,
                      'sort_order'   => 100 + $i,
                      'department_id'=> $deptSlug ? ($deptIds[$deptSlug] ?? null) : null,
                  ]
              );
          }
      }
  }
  ```
- **GOTCHA**: `Str::slug()` strips the `–` (em-dash) and combining punctuation; results like "mission-sunday-promotion-2026-01-04" / "abc-enrollment-closed-2026-01-31" are stable and unique. Verify no two generated slugs collide (the 12 Mission Sunday events are distinguished by their dates).
- **GOTCHA**: `Str::slug('AYC', '-')` → `ayc`. Since the slug also includes the date, that's fine.
- **GOTCHA**: The project uses SQLite; column `slug` has a UNIQUE constraint. `firstOrCreate` handles the duplicate case; do NOT use `insert()` or you'll get a UNIQUE violation on re-run.
- **VALIDATE**:
  ```bash
  php -l database/seeders/NationalCalendar2026Seeder.php
  ```

### Task 2: RUN the seeder

- **ACTION**: Execute via artisan. Will require operator approval on this server (matches earlier migrations in this session).
- **COMMAND**:
  ```bash
  sudo -u www-data php artisan db:seed --class=NationalCalendar2026Seeder
  ```
- **VALIDATE** (expected output):
  ```
  INFO  Seeding database.
  Database\Seeders\NationalCalendar2026Seeder ... RUNNING
  Database\Seeders\NationalCalendar2026Seeder ... DONE
  ```
- **POST-RUN CHECK**:
  ```bash
  sqlite3 /var/www/personal/upci.co.nz/upci "
    SELECT COUNT(*) AS total_2026 FROM events WHERE start_date LIKE '2026-%';
    SELECT id, name, start_date, end_date, location FROM events WHERE start_date LIKE '2026-%' ORDER BY start_date LIMIT 10;
  "
  ```
  Expect: `total_2026` ≥ 47, first 10 rows match the dataset table above.
- **RE-RUN SAFETY**: Second invocation should no-op (every slug already exists). Confirm count stays at 47 after a second run.

---

## Testing Strategy

No automated tests — one-time data seed. Manual verification:

1. `sqlite3 upci "SELECT COUNT(*) FROM events"` → should be 47 existing 2026 rows + 2 placeholders = 49 total (or higher if prior data).
2. `sqlite3 upci "SELECT name, start_date, end_date, location FROM events WHERE start_date BETWEEN '2026-06-01' AND '2026-07-31' ORDER BY start_date"` → spot-check June/July block matches the PDF.
3. `curl -sS --resolve upci.b8.co.nz:80:127.0.0.1 -H "Accept: application/json" http://upci.b8.co.nz/api/events | python3 -c "import json,sys; d=json.load(sys.stdin); print('events:', len(d.get('data', [])))"` → public endpoint returns all 49.
4. Browser check: `/admin/events` shows all events sorted by start date; `/events` (public Vue page) renders the upcoming ones as cards; `/calendar` month grid populates February / March 2026 correctly (testing dates in the past/current month in the screenshot).

### Edge Cases Checklist

- [ ] Re-running the seeder leaves row count unchanged (slug UNIQUE + firstOrCreate).
- [ ] Multi-day events (`end_date != null`) render correctly in the Events.vue template (line formats `{start_date} – {end_date}` already).
- [ ] Events with NULL `department_id` still display (AGC, AMM, AGM, ABC events).
- [ ] Public `/api/events` JSON includes `department` relation eager-loaded (per existing `EventController::index` line 17: `Event::published()->with('department')`).
- [ ] Slug collisions: with `Str::slug("Mission Sunday Promotion 2026-01-04") = "mission-sunday-promotion-2026-01-04"`, each of the 12 monthly Mission Sunday rows produces a distinct slug. Verify via a distinct-count in the dataset mentally: 12 different dates → 12 different slugs.

---

## Validation Commands

### Level 1: Static check
```bash
php -l database/seeders/NationalCalendar2026Seeder.php
```
Expect: `No syntax errors detected`.

### Level 2: Dry run preview (without writing)
```bash
sudo -u www-data HOME=/tmp php -r "
require 'vendor/autoload.php';
\$app = require 'bootstrap/app.php';
\$app->make(Illuminate\\Contracts\\Console\\Kernel::class)->bootstrap();
\$deptIds = App\\Models\\Department::pluck('id', 'slug')->toArray();
print_r(\$deptIds);
"
```
Expect: array mapping `mens`, `ladies`, `missions`, `youth`, `childrens`, `prayer` → int ids. If any are missing, the seeder's `department_id` will fall back to `null` (safe but wrong).

### Level 3: Seed
```bash
sudo -u www-data php artisan db:seed --class=NationalCalendar2026Seeder
```
Expect: `DONE` without errors.

### Level 4: Row count + sample
```bash
sqlite3 /var/www/personal/upci.co.nz/upci "SELECT COUNT(*) FROM events WHERE start_date LIKE '2026-%';"
# Expect: 47 or 49 (49 if placeholders are counted; placeholders use 2026-01-15 and 2026-03-01).
```

### Level 5: Public API
```bash
curl -sS --resolve upci.b8.co.nz:80:127.0.0.1 -H "Accept: application/json" "http://upci.b8.co.nz/api/events" | python3 -m json.tool | head -30
```
Expect: full JSON with `success: true`, data array of 49 events, first sorted by start_date.

### Level 6: Browser
- `/admin/events` — all 49 events listed, sortable by start_date, department column populated for 35 of them.
- Public `/events` — cards render with pastoral blue dates, "View calendar" button still works.
- Public `/calendar` — flip to Feb 2026, see AMM (21), PM Waikato (14), etc.

---

## Acceptance Criteria

- [ ] `database/seeders/NationalCalendar2026Seeder.php` exists, passes `php -l`.
- [ ] `php artisan db:seed --class=NationalCalendar2026Seeder` completes without error.
- [ ] `events` table row count increases by 47 (or stays the same on subsequent runs).
- [ ] Every newly inserted row has a unique slug matching the pattern `{kebab-name}-YYYY-MM-DD`.
- [ ] Departments correctly linked for ministry-prefixed events (CM → childrens, LM → ladies, MM → mens, YM → youth, PM → prayer, Mission → missions).
- [ ] Admin-only events (ABC, AMM, AGM, AGC, MTD, Executive Board) have `department_id = NULL`.
- [ ] Public `/api/events` returns the new data.
- [ ] Spot check 3 random rows against the PDF — dates and locations match.

---

## Completion Checklist

- [ ] Seeder file written
- [ ] Syntax check passes
- [ ] `db:seed` runs clean
- [ ] Row count verified via SQL
- [ ] Public API smoke test passes
- [ ] Browser spot-check in `/admin/events` and `/events`

---

## Risks and Mitigations

| Risk | Likelihood | Impact | Mitigation |
|------|------------|--------|------------|
| Transcription errors from PDF to array | MED | LOW | Dataset table in this plan is the canonical source; double-check against PDF before committing. Post-run spot-check ensures data integrity. |
| Running on a different DB than SQLite in future | LOW | LOW | `firstOrCreate` is driver-agnostic; slug UNIQUE enforces idempotency universally. |
| Department slug missing (e.g., if `missions` was renamed) | LOW | LOW | Lookup returns `null` → event saved with `department_id = NULL` rather than erroring. Operator can fix via the admin UI. |
| Seeder accidentally re-run and creates duplicates | LOW | LOW | Slug is UNIQUE + `firstOrCreate`. Re-run is a no-op. |
| Event model `$fillable` doesn't include `department_id` | LOW | HIGH | Confirmed `department_id` is in `$fillable` at `app/Models/Event.php:20`. |
| PDF revised before seed runs | MED | LOW | The filename says "1/30/26 - revised". If a newer PDF arrives later, a second seeder (with a different class name) can add/update differences — or just edit the admin UI. |

---

## Notes

- **Why a seeder, not a migration.** Data-only loads belong in seeders. The codebase does have a precedent of data-in-migration (e.g. `2026_04_19_000004_seed_departments_and_menu_items.php`), but that was tied to adding menu rows in lockstep with UI work. For a pure data import the seeder is the idiomatic choice.
- **Why not an Artisan command.** Seeders can already be run individually via `db:seed --class=…`. Wrapping the same logic in a command would just add indirection.
- **The 2 existing placeholder events stay.** Their slugs (`general-conference`, `annual-ministers-meeting`) don't collide with anything in this plan. They can be deleted manually in `/admin/events` after verification if they duplicate the real AGC (Oct 24–25) / AMM (Feb 21).
- **AYC is a span event** (Jun 27 – Jul 6) with `end_date` populated. Confirmed `end_date` is nullable in the schema.
- **Multi-city locations use `/` separator** to match the PDF's own style. The `location` column is a free-form string; no schema changes needed.
- **"Pentecost EveryDay" theme** is folded into the description of each Mission Sunday entry rather than stored as a separate column. If it becomes a feature ("Theme of the year"), it warrants its own column later.
- **Confidence: 9.5/10.** The one uncertainty is whether any department slug has silently been renamed; the fallback to `null` covers that. The data is exhaustively documented in the dataset table; an implementing agent can transcribe directly.
