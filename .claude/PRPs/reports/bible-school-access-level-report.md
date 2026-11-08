# Implementation Report

**Plan**: `.claude/PRPs/plans/completed/bible-school-access-level.plan.md`
**Branch**: `main` (owner: work on main, no branches; not committed)
**Date**: 2026-11-02
**Status**: COMPLETE

---

## Summary

Added the **Bible School (ABC enrolments only)** access level. A national admin assigns it in Admin › Users › Access Level. A login with it can:
- get into `/admin`;
- see only ABC Enrolments: the list, each full record including the pastor's reference, transcript downloads, and "Ask the pastor for a reference / Email the pastor again".

It cannot delete. Every other resource and the site settings page are hidden and return 403. The dashboard loads without the attendance widgets.

The five policy checks that granted on "any access level" now ask `User::hasChurchScope()`, so any future level starts with nothing. A regional presbyter can no longer view or edit (and set the password of) a Bible School login.

---

## Assessment vs Reality

| Metric     | Predicted | Actual | Reasoning |
| ---------- | --------- | ------ | --------- |
| Complexity | MEDIUM    | MEDIUM | Small diff across the gates the audit found. No new gate turned up during implementation. |
| Confidence | 9/10      | 9/10   | One test assertion needed a fix (an HTML-escaped apostrophe). The implementation passed first time. |

---

## Tasks Completed

| # | Task | File | Status |
|---|------|------|--------|
| 0 | Backup + baseline (503 passed, 25 skipped) | `../upci.co.nz-backups/pre-bible-school-access-20261102-100700/` | ✅ |
| 1 | New enum case + label/colour arms | `app/Enums/AccessLevel.php` | ✅ |
| 2 | `isBibleSchool()`, `hasChurchScope()` | `app/Models/User.php` | ✅ |
| 3 | `viewAny`/`view`/`emailPastor` for Bible School | `app/Policies/AbcEnrolmentPolicy.php` | ✅ |
| 4 | 5 any-level grants → `hasChurchScope()`; `outranks()` excludes Bible School | `AttendancePolicy.php`, `ChurchPolicy.php`, `UserPolicy.php` | ✅ |
| 5 | Explicit `BIBLE_SCHOOL => 1=0` arm | `app/Filament/Concerns/ScopesToAccessLevel.php` | ✅ |
| 6 | `canView()` = `AttendanceResource::canViewAny()` | 3 widgets in `app/Filament/Widgets/` | ✅ |
| 7 | `->authorize('emailPastor')` + comments | `app/Filament/Resources/AbcEnrolments/Pages/ViewAbcEnrolment.php` | ✅ |
| 8 | Helper text names the level | `app/Filament/Resources/Users/Schemas/UserForm.php` | ✅ |
| 9 | 11 feature tests | `tests/Feature/BibleSchoolAccessTest.php` | ✅ |
| 10 | Pint, full suite, chown storage | — | ✅ |

---

## Validation Results

| Check | Result | Details |
|-------|--------|---------|
| Syntax (`php -l`) | ✅ | All changed PHP files |
| Lint (Pint, changed files only) | ✅ | 13 files pass |
| Any-level grants left | ✅ | `grep '(bool) $user->access_level' app/Policies` is empty |
| New tests | ✅ | 11 passed (83 assertions) |
| Full suite | ✅ | 514 passed, 25 skipped, 0 failed (baseline 503 + 11) |
| Build | ⏭️ | No frontend change |
| Live smoke | ✅ | `upci.co.nz/admin/login` 200, `/` 200; no `UnhandledMatchError` in `laravel.log`; `abc-enrolments` routes unchanged (index + view) |
| DB | ⏭️ | No migration; `users.access_level` is a nullable string |

---

## Files Changed

| File | Action |
|------|--------|
| `app/Enums/AccessLevel.php` | UPDATE |
| `app/Models/User.php` | UPDATE |
| `app/Policies/AbcEnrolmentPolicy.php` | UPDATE |
| `app/Policies/AttendancePolicy.php` | UPDATE |
| `app/Policies/ChurchPolicy.php` | UPDATE |
| `app/Policies/UserPolicy.php` | UPDATE |
| `app/Filament/Concerns/ScopesToAccessLevel.php` | UPDATE |
| `app/Filament/Widgets/MainStatsWidget.php` | UPDATE |
| `app/Filament/Widgets/AttendanceTrendChart.php` | UPDATE |
| `app/Filament/Widgets/CategoryBreakdownWidget.php` | UPDATE |
| `app/Filament/Resources/AbcEnrolments/Pages/ViewAbcEnrolment.php` | UPDATE |
| `app/Filament/Resources/Users/Schemas/UserForm.php` | UPDATE |
| `tests/Feature/BibleSchoolAccessTest.php` | CREATE |

---

## Deviations from Plan

- **Pint touched two pre-existing lines in `ScopesToAccessLevel.php`**: the import order, and the padded `LOCAL    =>` alignment. My longer `BIBLE_SCHOOL` arm broke that alignment anyway. Formatting only.
- **Test file additions**:
  - Test 2 also asserts that national keeps `emailPastor` and regional/local don't have it.
  - Test 4 asserts at least 6 index URLs were checked, so it can't pass vacuously.
  - Test 11 also opens the user's view page and sees the new label, which covers `UserInfolist`'s `getLabel`/`getColor`.
- **No remote sync, no branch.** This follows the owner's standing instruction to work on main.

## Issues Encountered

- `assertSee("Pastor's reference", false)` failed because the page HTML-escapes the apostrophe (`&#039;`). Fixed by using the default escaped `assertSee`.
- zsh doesn't word-split `$F`. Used an array for the Pint file list.
- A Pest warning about writing `vendor/pestphp/pest/.temp/test-results` (root-owned result cache from an earlier root run). Harmless, and it doesn't affect results. Left alone.

## Tests Written

`tests/Feature/BibleSchoolAccessTest.php`:
1. a Bible School login gets into the admin panel, and the level can be chosen
2. it can read enrolments and ask for a pastor's reference, but not create, edit or delete them
3. ABC Enrolments is the only resource it can open (iterates every registered resource)
4. every other admin list, and the site settings, refuse it by URL
5. the dashboard opens, without the attendance widgets
6. it sees the list and the whole enrolment, the pastor's answers included
7. it can download a transcript
8. it can email the pastor for a reference
9. church-scoped lists come back empty for it, not broken
10. a regional presbyter cannot view or edit a Bible School login in their region
11. a national administrator can give a login Bible School access

## Next Steps

- [ ] Owner creates the login: Admin › Users › New. Enter name, email and **password** (reset emails don't send while mail is log-only), set Access Level to "Bible School (ABC enrolments only)", choose any role, and leave Church empty.
- [ ] Owner signs in as it once to confirm: one sidebar item, enrolment opens, no Delete.
- [ ] Commit/push when the owner asks. Uncommitted work from earlier features is also in the tree.
