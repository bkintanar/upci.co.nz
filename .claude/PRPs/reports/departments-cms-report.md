# Implementation Report

**Plan**: `.claude/PRPs/plans/departments-cms.plan.md`
**Branch**: `main`
**Date**: 2026-04-19
**Status**: COMPLETE

---

## Summary

Added a full "Departments" CMS feature to upci.co.nz: six seeded department rows (Mens, Ladies, Missions, Youth, Children's, Prayer Ministry), each with editable Description, Announcements, and Calendar. New `departments` + `department_announcements` tables; additive `events.department_id` FK. A Filament v4 `DepartmentResource` with two nested RelationManagers (Announcements, Calendar) provides full back-office CRUD. Public `/api/departments/{slug}` bundles description, announcements, and events in one response; a new `resources/js/views/Department.vue` binds to `/departments/:slug`. Six header menu children were inserted under the existing "Departments" parent via the same seed migration.

---

## Assessment vs Reality

| Metric     | Predicted | Actual | Reasoning                                                                 |
| ---------- | --------- | ------ | ------------------------------------------------------------------------- |
| Complexity | MEDIUM    | MEDIUM | Matched. 24 atomic tasks; no surprises in Filament v4 API or Laravel FK.  |
| Confidence | 8/10      | 9/10   | Patterns mirrored cleanly; only pint style fixes and a small Vue detail were adjusted on the fly. |

**Deviations from plan:**
- Combined `create-departments` and `seed-departments` into 4 migrations (plan said 3 + 1 seed) — executed as the plan intended but with the seed migration assigned the `2026_04_19_000004_*` slot (noted as Task 18 in plan but also functioning as the final migration).
- EventsRelationManager announcement `DateTimePicker::make('published_at')` uses `->default(now())` so editors don't have to open the picker for the common case.
- `Department.vue` uses a `THEME_CLASSES` lookup map for hero gradients instead of computing class strings inline — cleaner and covers all six color themes.

---

## Tasks Completed

| #   | Task                                                                                 | File                                                                                     | Status |
| --- | ------------------------------------------------------------------------------------ | ---------------------------------------------------------------------------------------- | ------ |
| 1   | Migration: create `departments`                                                      | `database/migrations/2026_04_19_000001_create_departments_table.php`                     | ✅     |
| 2   | Migration: create `department_announcements`                                         | `database/migrations/2026_04_19_000002_create_department_announcements_table.php`        | ✅     |
| 3   | Migration: add `department_id` to events                                             | `database/migrations/2026_04_19_000003_add_department_id_to_events_table.php`            | ✅     |
| 4   | Model: Department                                                                    | `app/Models/Department.php`                                                              | ✅     |
| 5   | Model: DepartmentAnnouncement                                                        | `app/Models/DepartmentAnnouncement.php`                                                  | ✅     |
| 6   | Update Event model (fillable + relation)                                             | `app/Models/Event.php`                                                                   | ✅     |
| 7   | Run migrations                                                                       | —                                                                                        | ✅     |
| 8   | Filament DepartmentResource                                                          | `app/Filament/Resources/Departments/DepartmentResource.php`                              | ✅     |
| 9   | DepartmentForm                                                                       | `app/Filament/Resources/Departments/Schemas/DepartmentForm.php`                          | ✅     |
| 10  | DepartmentInfolist stub                                                              | `app/Filament/Resources/Departments/Schemas/DepartmentInfolist.php`                      | ✅     |
| 11  | DepartmentsTable                                                                     | `app/Filament/Resources/Departments/Tables/DepartmentsTable.php`                         | ✅     |
| 12  | ListDepartments page                                                                 | `app/Filament/Resources/Departments/Pages/ListDepartments.php`                           | ✅     |
| 13  | CreateDepartment page                                                                | `app/Filament/Resources/Departments/Pages/CreateDepartment.php`                          | ✅     |
| 14  | EditDepartment page                                                                  | `app/Filament/Resources/Departments/Pages/EditDepartment.php`                            | ✅     |
| 15  | ViewDepartment page                                                                  | `app/Filament/Resources/Departments/Pages/ViewDepartment.php`                            | ✅     |
| 16  | AnnouncementsRelationManager                                                         | `app/Filament/Resources/Departments/RelationManagers/AnnouncementsRelationManager.php`   | ✅     |
| 17  | EventsRelationManager                                                                | `app/Filament/Resources/Departments/RelationManagers/EventsRelationManager.php`          | ✅     |
| 18  | Seed migration (6 depts + 6 menu children)                                           | `database/migrations/2026_04_19_000004_seed_departments_and_menu_items.php`              | ✅     |
| 19  | DepartmentController (index + show)                                                  | `app/Http/Controllers/Api/DepartmentController.php`                                      | ✅     |
| 20  | Register `/api/departments*` routes                                                  | `routes/web.php`                                                                         | ✅     |
| 21  | EventController: `?department=<slug>` filter + output `department` slug              | `app/Http/Controllers/Api/EventController.php`                                           | ✅     |
| 22  | EventForm: add `Select::make('department_id')`                                       | `app/Filament/Resources/Events/Schemas/EventForm.php`                                    | ✅     |
| 23  | Department.vue view                                                                  | `resources/js/views/Department.vue`                                                      | ✅     |
| 24  | Register `/departments/:slug` in routes.js (before catch-all)                        | `resources/js/router/routes.js`                                                          | ✅     |

---

## Validation Results

| Check                | Result | Details                                                                                   |
| -------------------- | ------ | ----------------------------------------------------------------------------------------- |
| PHP syntax (php -l)  | ✅     | All 21 touched files clean                                                                |
| Pint style           | ✅     | 17 style issues auto-fixed; subsequent `pint --test` clean                                |
| Vite build           | ✅     | 96 modules transformed, `Department-*.js` chunk generated (5.25 kB)                        |
| API smoke tests      | ✅     | `/api/departments` lists 6; `/api/departments/mens` returns bundle; 404 correct; `/api/events?department=mens` filters correctly |
| Menu API             | ✅     | `/api/menu/header` shows 6 department children under the "Departments" parent             |
| Filament routes      | ✅     | 4 admin routes registered (index/create/view/edit) with 2 RelationManagers                |
| Migrations           | ✅     | 4 migrations ran cleanly; 6 rows seeded; 6 menu children inserted                         |
| Existing test suite  | ⚠️    | 16 failures, 11 passes — **all 16 are pre-existing `Livewire\Volt\Volt` errors unrelated to this change** (confirmed: same failures with this work stashed). No Department code is referenced by any failing test. |

---

## Files Changed

| File                                                                                                       | Action | Notes                                                 |
| ---------------------------------------------------------------------------------------------------------- | ------ | ----------------------------------------------------- |
| `database/migrations/2026_04_19_000001_create_departments_table.php`                                       | CREATE | +28                                                   |
| `database/migrations/2026_04_19_000002_create_department_announcements_table.php`                          | CREATE | +29                                                   |
| `database/migrations/2026_04_19_000003_add_department_id_to_events_table.php`                              | CREATE | +27                                                   |
| `database/migrations/2026_04_19_000004_seed_departments_and_menu_items.php`                                | CREATE | +140                                                  |
| `app/Models/Department.php`                                                                                | CREATE | +44                                                   |
| `app/Models/DepartmentAnnouncement.php`                                                                    | CREATE | +37                                                   |
| `app/Models/Event.php`                                                                                     | UPDATE | +7 (fillable entry, relation, BelongsTo import)       |
| `app/Filament/Resources/Departments/DepartmentResource.php`                                                | CREATE | +78                                                   |
| `app/Filament/Resources/Departments/Schemas/DepartmentForm.php`                                            | CREATE | +73                                                   |
| `app/Filament/Resources/Departments/Schemas/DepartmentInfolist.php`                                        | CREATE | +17                                                   |
| `app/Filament/Resources/Departments/Tables/DepartmentsTable.php`                                           | CREATE | +49                                                   |
| `app/Filament/Resources/Departments/Pages/ListDepartments.php`                                             | CREATE | +19                                                   |
| `app/Filament/Resources/Departments/Pages/CreateDepartment.php`                                            | CREATE | +11                                                   |
| `app/Filament/Resources/Departments/Pages/EditDepartment.php`                                              | CREATE | +21                                                   |
| `app/Filament/Resources/Departments/Pages/ViewDepartment.php`                                              | CREATE | +19                                                   |
| `app/Filament/Resources/Departments/RelationManagers/AnnouncementsRelationManager.php`                     | CREATE | +63                                                   |
| `app/Filament/Resources/Departments/RelationManagers/EventsRelationManager.php`                            | CREATE | +70                                                   |
| `app/Http/Controllers/Api/DepartmentController.php`                                                        | CREATE | +83                                                   |
| `app/Http/Controllers/Api/EventController.php`                                                             | UPDATE | +6 (filter + eager load + department slug)            |
| `app/Filament/Resources/Events/Schemas/EventForm.php`                                                      | UPDATE | +8 (import + Select field)                            |
| `routes/web.php`                                                                                           | UPDATE | +4 (import + 2 routes)                                |
| `resources/js/views/Department.vue`                                                                        | CREATE | +157                                                  |
| `resources/js/router/routes.js`                                                                            | UPDATE | +5 (new `/departments/:slug` entry before catch-all)  |

**Totals**: 18 files created, 5 files updated.

---

## Deviations from Plan

- **DateTimePicker default**: Announcements form uses `->default(now())` for `published_at` — a small ergonomic tweak not in the plan. Harmless; editors can still override.
- **Theme lookup map in Department.vue**: Plan suggested a `heroClasses(theme)` function; I centralised the mapping in a module-level `THEME_CLASSES` const which is cleaner.
- **Pint re-ordered imports and unary spacing** across most files. Accepted (style only, no behavior change).

---

## Issues Encountered

- **Pre-existing test failures**: `php artisan test` reports 16 failures, all `Class "Livewire\Volt\Volt" not found` from stock Livewire starter-kit auth tests (`AuthenticationTest`, `PasswordResetTest`, etc.). Confirmed identical failure count with my changes stashed — these predate this feature and are unrelated. Not addressed in this PRP.
- **No other issues.**

---

## Tests Written

No new unit/feature tests were written. Rationale documented in the plan: existing feature areas (Events, AGSUpdates, CMS Pages) ship without feature tests in this project; adding tests only for this feature would be inconsistent. Feature was validated end-to-end via curl against the running `php artisan serve` and via Vite build verification.

---

## Next Steps

- [ ] Review diff: `git status` shows 4 new migrations, 2 new models, 1 new controller, 1 new Filament resource directory (9 files), 1 new Vue view, and 5 updated files. Plan also added at `.claude/PRPs/plans/completed/departments-cms.plan.md`.
- [ ] Manual browser walkthrough: `npm run dev` + visit `/departments/mens` to confirm hero + description renders with live reload.
- [ ] Optional: commit the uncommitted pre-existing work (Events/AGSUpdates/GalleryItems) separately from this feature before creating the PR — makes the diff easier to review.
- [ ] Create PR when ready.
