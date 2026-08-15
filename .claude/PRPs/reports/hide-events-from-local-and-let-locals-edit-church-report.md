# Implementation Report

**Plan**: `.claude/PRPs/plans/hide-events-from-local-and-let-locals-edit-church.plan.md`
**Branch**: `main`
**Date**: 2026-04-24
**Status**: COMPLETE

---

## Summary

Three policy-layer changes plus one small middleware landed, matching the plan exactly. (1) `EventPolicy::viewAny/view` now require national or regional access — Filament auto-hides the Events sidebar item for local users via `HasNavigation.php:50` → `canAccess()` → policy. (2) A new `NationalOrRegionalOnly` middleware, wired into `EventResource::getRouteMiddleware()`, converts direct-URL access from a 403 into a 404 for local users. (3) `ChurchPolicy::update` gained a local-user branch (`$church->id === $user->church_id`) so local pastors can finally edit their own church — this was a real bug prior to this plan, not just a nice-to-have.

Discovered and fixed a stale test in `AccessLevelScopingTest` that had asserted the old (buggy) behavior where locals couldn't update any church; updated it to reflect the new, correct behavior.

---

## Assessment vs Reality

| Metric     | Predicted | Actual | Reasoning                                                                                  |
| ---------- | --------- | ------ | ------------------------------------------------------------------------------------------ |
| Complexity | LOW       | LOW    | All three changes landed in under 30 LOC each, as predicted                                |
| Confidence | 9/10      | 9/10   | Plan anticipated every gotcha; only surprise was a pre-existing test asserting old behavior |

**One non-code deviation**: `AccessLevelScopingTest.php:115-124` asserted that a local user could NOT update their own church. That was correct against the old policy but wrong against the new one — the test title ("local user cannot update a church outside their assignment") suggested the author intended to check the "other church" case but wrote assertions that also covered the "own church" case. Updated the test to match the corrected behavior and renamed it to "local user can update their own church but not others" — the intent that the author actually wanted.

---

## Tasks Completed

| #   | Task                                                 | File                                                   | Status |
| --- | ---------------------------------------------------- | ------------------------------------------------------ | ------ |
| 1   | Tighten `EventPolicy` viewAny/view                   | `app/Policies/EventPolicy.php`                         | ✅     |
| 2   | Create `NationalOrRegionalOnly` middleware           | `app/Http/Middleware/NationalOrRegionalOnly.php`       | ✅     |
| 3   | Wire middleware into `EventResource`                 | `app/Filament/Resources/Events/EventResource.php`     | ✅     |
| 4   | Add local branch to `ChurchPolicy::update`           | `app/Policies/ChurchPolicy.php`                        | ✅     |
| 5a  | `EventAccessPolicyTest` (7 tests)                    | `tests/Feature/EventAccessPolicyTest.php`              | ✅     |
| 5b  | `ChurchPolicyLocalEditTest` (7 tests)                | `tests/Feature/ChurchPolicyLocalEditTest.php`          | ✅     |
| 5c  | Fix stale assertion in `AccessLevelScopingTest`      | `tests/Feature/AccessLevelScopingTest.php` (1 test)   | ✅     |

---

## Validation Results

| Check                                  | Result | Details                                                      |
| -------------------------------------- | ------ | ------------------------------------------------------------ |
| Syntax (`php -l`) each touched file    | ✅     | No errors                                                    |
| Pint (lint)                            | ✅     | 5 auto-fixable import-order issues fixed; PASS on re-run    |
| `EventAccessPolicyTest`                | ✅     | 7/7 pass — includes end-to-end HTTP test proving 404 works  |
| `ChurchPolicyLocalEditTest`            | ✅     | 7/7 pass — covers own/other/null and create/delete invariants |
| `AccessLevelScopingTest` (updated)     | ✅     | 9/9 pass                                                     |
| `PanelAccessGateTest` (prior)          | ✅     | 4/4 pass                                                     |
| Full relevant suite                    | ✅     | **27/27 tests pass (34 assertions)**                         |
| Filament routes still register         | ✅     | `artisan route:list --path=admin/events` shows 4 routes      |

### Pre-existing Breeze test failures still present

The 16 Breeze scaffolding tests in `tests/Feature/Auth/*` and `tests/Feature/Settings/*` continue to fail (as noted in the previous report). They reference `Livewire\Volt\Volt` which isn't installed and test routes that were removed when the app moved to Filament-only auth. Orthogonal to this plan; cleanup remains a separate concern.

---

## Files Changed

| File                                                            | Action | Lines       |
| --------------------------------------------------------------- | ------ | ----------- |
| `app/Policies/EventPolicy.php`                                  | UPDATE | +5 / -3     |
| `app/Policies/ChurchPolicy.php`                                 | UPDATE | +4          |
| `app/Filament/Resources/Events/EventResource.php`               | UPDATE | +8          |
| `app/Http/Middleware/NationalOrRegionalOnly.php`                | CREATE | +21         |
| `tests/Feature/EventAccessPolicyTest.php`                       | CREATE | +78         |
| `tests/Feature/ChurchPolicyLocalEditTest.php`                   | CREATE | +96         |
| `tests/Feature/AccessLevelScopingTest.php`                      | UPDATE | +1 / -1    |

---

## Deviations from Plan

1. **Updated an existing test in `AccessLevelScopingTest.php`.** The plan didn't mention this test at all, but it had an assertion that directly conflicted with Task 4's intended behavior (locals being able to update their own church). Kept the test case but flipped the assertion and renamed to match the actual behavior check. No loss of coverage — the "can't edit another church" assertion is retained.

2. **Added `slug` to Event creation in `EventAccessPolicyTest`.** The plan's test stub used `['title' => 'T', ...]` but the `events` table schema requires `name` + `slug` (both NOT NULL). Adjusted to use `['name' => 'T', 'slug' => 't-'.uniqid(), ...]`. This is a plan-accuracy deviation; the plan's test snippet was written from memory, not from schema.

---

## Issues Encountered

1. **One test initially failed — schema mismatch.** `Event::create(['title' => ...])` violated the `events.name NOT NULL` constraint. Fixed by using the actual schema fields (`name`, `slug`). Resolved in one iteration.

2. **One existing test failed after policy change** (`AccessLevelScopingTest::local user cannot update a church outside their assignment`). The plan didn't flag this because it wasn't visible in the policy-focused review. Updated the assertion to match the new correct behavior; the test name was updated to match its actual intent.

3. **Pint flagged import-order on all 5 touched PHP files.** Auto-fixed by `vendor/bin/pint` (non-`--test`). All files pass on re-run.

---

## Tests Written

| Test File                                       | Test Cases                                                                                   |
| ----------------------------------------------- | -------------------------------------------------------------------------------------------- |
| `tests/Feature/EventAccessPolicyTest.php`       | local cannot viewAny events; national can; regional can; local cannot view record; **local GET /admin/events → 404** (HTTP end-to-end); national → 200; regional → 200 |
| `tests/Feature/ChurchPolicyLocalEditTest.php`   | local can update own; local cannot update different; local without church_id cannot; local still cannot create; local still cannot delete; regional scoped-by-region; national can update any |
| `tests/Feature/AccessLevelScopingTest.php` (existing, updated) | "local user can update their own church but not others" — flipped assertion |

---

## Next Steps

- [x] Plan archived to `.claude/PRPs/plans/completed/`
- [x] Report written to `.claude/PRPs/reports/`
- [ ] Manual browser smoke test: log in as `andrew.kintanar@churchtriumphant.co.nz` (local) — confirm no Events in sidebar, `/admin/events` 404s, `/admin/churches/2/edit` has a working Save button.
- [ ] Optional follow-up: if Departments should also hide-for-local, add the same `getRouteMiddleware` + tighten `DepartmentPolicy::viewAny` (~4 LOC).
- [ ] Optional follow-up: tighten `ChurchForm.php` fields so local users can't edit `region_id` / `is_active` (flagged as "NOT Building" in the plan — decide if you want it now).
- [ ] Consider deleting `tests/Feature/Auth/*` and `tests/Feature/Settings/*` (Breeze scaffolding, already broken, unrelated to this work).
