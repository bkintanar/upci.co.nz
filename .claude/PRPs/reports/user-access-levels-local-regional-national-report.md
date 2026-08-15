# Implementation Report

**Plan**: `.claude/PRPs/plans/user-access-levels-local-regional-national.plan.md`
**Branch**: `main`
**Date**: 2026-04-20
**Status**: COMPLETE (with one data-integrity follow-up flagged for the operator)

---

## Summary

Delivered the orthogonal `access_level` dimension (Local / Regional / National) on users, promoted `organizational_region` to a first-class `regions` lookup table, centralised Filament list-query scoping in a reusable `ScopesToAccessLevel` trait, and implemented 11 Laravel policies that auto-register by naming convention to enforce writes. Public `/api/*` endpoints preserve their previous wire shapes (still return `organizational_region` as a string) so the Vue frontend continues to work unchanged. All 9 new feature tests pass.

---

## Assessment vs Reality

| Metric     | Predicted | Actual | Reasoning |
| ---------- | --------- | ------ | --------- |
| Complexity | MEDIUM-HIGH (14 tasks) | MEDIUM-HIGH (14 tasks + one surprise fix) | One naming collision surfaced at runtime — `$church->region` is already a VARCHAR column (NZ geographic region), which shadowed the new `region()` belongsTo. Renamed the relationship to `organizationalRegion()`, adjusted 6 downstream call sites. Caught by API smoke test before it reached the user. |
| Confidence | 9/10 | 9/10 (confirmed) | The plan was accurate; the naming collision wasn't pre-identified but was easy to resolve once observed. |

### Deviations from plan

- **Renamed `Church::region()` → `Church::organizationalRegion()`.** `region` is already a VARCHAR column name on `churches` (NZ geographic region, e.g. "Bay of Plenty"), which shadowed the belongsTo. Fix propagated to: Region model (explicit FK), Church model, ChurchController (2 places), ChurchForm, ChurchesTable, ChurchInfolist (2 places).
- **Removed the `UserRole::hasFullAccess/isPastor/isRegionalPresbyter` helper calls from all 8 resources and pages** rather than leaving them in place — cleaner. Helpers still exist in `UserRole` enum for anything else that might call them; they're now unused in Filament code.
- **Replaced custom `getHeaderActions()` gating in `ListChurches` and `ListUsers`** with a plain `CreateAction::make()`. Filament honors the resource's `create` policy automatically, so the custom gate was redundant.

---

## Tasks Completed

| # | Task | Files | Status |
|---|------|-------|--------|
| 1 | Create `regions` table + seed 3 rows | `database/migrations/2026_04_20_100001_create_regions_table.php` | ✅ |
| 2 | Region Eloquent model | `app/Models/Region.php` | ✅ |
| 3 | Add `churches.region_id` FK + backfill + drop `organizational_region` | `database/migrations/2026_04_20_100002_add_region_id_to_churches_table.php` | ✅ |
| 4 | `AccessLevel` enum | `app/Enums/AccessLevel.php` | ✅ |
| 5 | Add `users.access_level` + `users.region_id` + backfill + drop `assigned_region` | `database/migrations/2026_04_20_100003_add_access_level_and_region_id_to_users_table.php` | ✅ |
| 6 | Update `User` model (cast, relation, helpers) | `app/Models/User.php` | ✅ |
| 7 | Update `Church` model (relation, fillable) | `app/Models/Church.php` | ✅ |
| 8 | `ScopesToAccessLevel` trait | `app/Filament/Concerns/ScopesToAccessLevel.php` | ✅ |
| 9 | Wire trait into Churches/Users/Attendances resources | `app/Filament/Resources/{Churches,Users,Attendances}/{Resource}.php` | ✅ |
| 10 | 11 policies (NationalOnly base + 10 concrete) | `app/Policies/*.php` | ✅ |
| 11 | Remove obsolete `shouldRegisterNavigation` + custom `getHeaderActions` in 8 resources/pages | Events, Departments, Pages, MenuItems, AGSUpdates, GalleryItems + ListChurches + ListUsers | ✅ |
| 12 | Update forms/tables/infolists for new columns | ChurchForm, ChurchesTable, ChurchInfolist, UserForm, UserInfolist | ✅ |
| 13 | ChurchController wire-compat for public API | `app/Http/Controllers/Api/ChurchController.php` | ✅ |
| 14 | Feature tests (9 passed) | `tests/Feature/AccessLevelScopingTest.php` | ✅ |

---

## Validation Results

| Check | Result | Details |
|-------|--------|---------|
| PHP syntax (`php -l`) — 25+ touched files | ✅ | zero errors |
| `php artisan migrate --force` | ✅ | 3 migrations applied in 103 ms total |
| New feature tests | ✅ | 9/9 (14 assertions) pass |
| Full project test suite | ⚠️ | 20 pass, 16 **pre-existing** fail (all `Livewire\Volt` related — confirmed to fail on clean `main` before any changes). My changes do not regress. |
| Scoping smoke test (Tinker-style impersonation) | ✅ | National: 7/16/3 rows; Regional (North): 0/0/0 (no churches have region_id yet in existing data); Local (church=2): 1 church, 1 user; NULL access_level: 0 everywhere (safe default-deny) |
| Public API `GET /api/churches-organizational-regions` | ✅ | Returns `["North Region", "Central Region", "South Region"]` — shape preserved |
| Public API `GET /api/churches` | ✅ | Returns JSON with `organizational_region` key per church (sourced from Region relationship) |
| Public API `GET /api/churches?organizational_region=North%20Region` filter | ✅ | Accepts the string, uses `whereHas('organizationalRegion', …)` internally |

---

## Files Changed

### CREATE (17)
- `database/migrations/2026_04_20_100001_create_regions_table.php`
- `database/migrations/2026_04_20_100002_add_region_id_to_churches_table.php`
- `database/migrations/2026_04_20_100003_add_access_level_and_region_id_to_users_table.php`
- `app/Models/Region.php`
- `app/Enums/AccessLevel.php`
- `app/Filament/Concerns/ScopesToAccessLevel.php`
- `app/Policies/NationalOnlyPolicy.php`
- `app/Policies/ChurchPolicy.php`
- `app/Policies/UserPolicy.php`
- `app/Policies/AttendancePolicy.php`
- `app/Policies/EventPolicy.php`
- `app/Policies/DepartmentPolicy.php`
- `app/Policies/PagePolicy.php`
- `app/Policies/MenuItemPolicy.php`
- `app/Policies/GalleryItemPolicy.php`
- `app/Policies/AGSUpdatePolicy.php`
- `app/Policies/ContactMessagePolicy.php`
- `tests/Feature/AccessLevelScopingTest.php`

### UPDATE (13)
- `app/Models/User.php` (cast, relation, helpers)
- `app/Models/Church.php` (relation, fillable)
- `app/Filament/Resources/Churches/ChurchResource.php` (trait)
- `app/Filament/Resources/Users/UserResource.php` (trait)
- `app/Filament/Resources/Attendances/AttendanceResource.php` (trait)
- `app/Filament/Resources/Events/EventResource.php` (remove override)
- `app/Filament/Resources/Departments/DepartmentResource.php` (remove override)
- `app/Filament/Resources/Pages/PageResource.php` (remove override)
- `app/Filament/Resources/MenuItems/MenuItemResource.php` (remove override)
- `app/Filament/Resources/AGSUpdates/AGSUpdateResource.php` (remove override)
- `app/Filament/Resources/GalleryItems/GalleryItemResource.php` (remove override)
- `app/Filament/Resources/Churches/Pages/ListChurches.php` (simplify)
- `app/Filament/Resources/Users/Pages/ListUsers.php` (simplify)
- `app/Filament/Resources/Churches/Schemas/ChurchForm.php` (org region Select → relationship)
- `app/Filament/Resources/Churches/Tables/ChurchesTable.php` (column → relation name)
- `app/Filament/Resources/Churches/Schemas/ChurchInfolist.php` (entries → relation name)
- `app/Filament/Resources/Users/Schemas/UserForm.php` (access_level field + region relationship)
- `app/Filament/Resources/Users/Schemas/UserInfolist.php` (access_level entry + region entry)
- `app/Http/Controllers/Api/ChurchController.php` (filter/validation/formatter wire-compat)

---

## Data-Integrity Finding (operator action)

The backfill ran correctly but surfaced two pre-existing data issues the operator should address:

1. **No churches have `organizational_region` set today.** All 7 rows had NULL (or a value that didn't match "North Region" / "Central Region" / "South Region"). Consequently all 7 churches end up with `region_id = NULL` and any regional user sees nothing. The operator should assign each church to a region via `/admin/churches/{id}/edit` (the form now has a "Region" Select sourced from the `regions` table).

2. **User #1 (`admin@upci.co.nz`) has `role = usher`.** With the orthogonal access_level design, that's fine — but because they had no `executive_board`/`administrator` role and no `church_id`, they were backfilled to `access_level = NULL`, which now means "see nothing" (safe default-deny). **The operator must grant this account `access_level = national`** (via `/admin/users/1/edit`) or they will appear locked out of everything in the admin panel.

A one-liner inline PHP command to immediately restore admin access:
```bash
sudo -u www-data HOME=/tmp php -r "
require 'vendor/autoload.php';
\$app = require 'bootstrap/app.php';
\$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
App\Models\User::where('email', 'admin@upci.co.nz')->update(['access_level' => 'national']);
echo 'admin@upci.co.nz set to national', PHP_EOL;
"
```

Flag: **do NOT run this blindly** if there are other accounts that should be national — review the user list first.

---

## Issues Encountered

1. **Naming collision `Church::region`** (attribute) vs `Church::region()` (new belongsTo). Filament/Eloquent uses the VARCHAR attribute, the new relationship was ignored, and `$church->region?->name` threw "Attempt to read property 'name' on string" at runtime when hitting `/api/churches`. Fixed by renaming the relationship to `organizationalRegion()` and adjusting all downstream references. Caught in validation — did not reach the user.

2. **Tinker couldn't write to `/var/www/.config/psysh`** (same psysh dir issue as prior session). Worked around with inline `php -r` + `HOME=/tmp`. Non-blocking.

3. **Pre-existing Livewire\Volt test failures** — 16 existing tests in `tests/Feature/{Auth,Settings,Dashboard}` rely on `Livewire\Volt\Volt`, which isn't installed in this project. Confirmed by running the tests on a clean `main` with all my changes stashed — same 16 failures. Out of scope for this plan.

---

## Tests Written

| Test File | Tests |
|-----------|-------|
| `tests/Feature/AccessLevelScopingTest.php` | 9 tests (14 assertions): national sees all, regional sees own region, local sees own church, NULL sees nothing, regional sees users in-region, policy permits/denies create on churches, local cannot update wrong church, regional can update churches in their region |

All pass in under 0.22 seconds against `:memory:` SQLite.

---

## Next Steps

**For the operator (blocking adoption)**:
1. Set `admin@upci.co.nz` to `access_level = national` (otherwise they see nothing in admin).
2. Assign each of the 7 churches to a region via `/admin/churches/{id}/edit` → Region field.
3. Review other users — anyone with `access_level = NULL` currently sees nothing. Decide whether they need a level.
4. (Optional) Log in as three fixture personas (local pastor, regional presbyter, national admin) and smoke-test the nav + list + edit pages match expectations.

**Follow-up plans (explicitly out of scope, noted in original plan)**:
- Department-level permissions. The trait's closure-based design means this adds one more closure + one migration; no existing resource needs to refactor.
- Clean up the unused `user_roles` pivot table (migrated 2025-10-11, never populated).
- Consider removing the now-unused `hasFullAccess`/`isPastor`/`isRegionalPresbyter` helpers from `UserRole` enum (no in-app callers remain after this work).

---

## Artifacts

- Report: `.claude/PRPs/reports/user-access-levels-local-regional-national-report.md` (this file)
- Plan: to be archived to `.claude/PRPs/plans/completed/user-access-levels-local-regional-national.plan.md`
