# Implementation Report

**Plan**: `.claude/PRPs/plans/admin-panel-login-gate-and-credentials.plan.md`
**Branch**: `main`
**Date**: 2026-04-24
**Status**: COMPLETE

---

## Summary

Closed the login-side gap in the access-level system. Three moving parts landed:

1. `User` implements `Filament\Models\Contracts\FilamentUser` with `canAccessPanel()` returning `(bool) $this->access_level`. Users with `access_level = NULL` are now rejected at `/admin/login` instead of reaching blank lists.
2. `AdminPanelProvider` calls `->passwordReset()`, registering the request + reset pages at `/admin/password-reset/*` under signed URLs.
3. CLI break-glass: `php artisan users:reset-password <email>` dispatches a Filament-aware reset link that honours the access gate.

The hygiene migration was a no-op on real data (prior plan's backfill had already promoted the 6 pastors with `church_id` to `local`) but is idempotent and documents the mapping.

---

## Assessment vs Reality

| Metric     | Predicted | Actual | Reasoning                                                                                                                |
| ---------- | --------- | ------ | ------------------------------------------------------------------------------------------------------------------------ |
| Complexity | LOW-MEDIUM | LOW-MEDIUM (as predicted) | All patterns mirrored cleanly. One predicted-but-glossed gotcha materialized: the default `Password::broker()` produces Laravel URLs, not Filament URLs. |
| Confidence | 9/10 | 9/10 | Every predicted risk surfaced; mitigation paths worked. |

**Deviation 1 — CLI had to use `Filament::getAuthPasswordBroker()` instead of default `Password::broker()`.**

The plan specified the simple form: `Password::broker()->sendResetLink(['email' => $email])`. On first run, the generated URL was Laravel's default (`/reset-password/{token}?email=...`) — which in this app redirects via `routes/auth.php:11` to `/admin/password/reset` (a broken 404 URL, typo for `/admin/password-reset/request`) and drops the token. I rewrote the CLI to mirror Filament's own `RequestPasswordReset::requestPasswordReset()` method (`vendor/filament/filament/src/Auth/Pages/PasswordReset/RequestPasswordReset.php:65-89`): use Filament's auth broker, pass a callback that builds the Filament URL via `Filament::getResetPasswordUrl($token, $user)`, and gate on `canAccessPanel()` the same way Filament's form does. Result: the CLI now generates signed `/admin/password-reset/reset` URLs that drop cleanly into the Filament flow, and refuses to email NULL-access users.

**Deviation 2 — Added `tests/Feature/PanelAccessGateTest.php` despite the plan calling it "optional".**

The plan said feature tests were out of scope because "the existing repo has no feature tests." That was wrong — `AccessLevelScopingTest` already exists with 9 passing Pest tests. Adding 4 gate tests alongside it cost ~30 seconds, matches the existing test pattern exactly, and gives the gate permanent coverage. All 4 pass.

---

## Tasks Completed

| #   | Task                                     | File                                                                                        | Status |
| --- | ---------------------------------------- | ------------------------------------------------------------------------------------------- | ------ |
| 1   | Implement `FilamentUser` on User         | `app/Models/User.php`                                                                       | ✅     |
| 2   | Enable `->passwordReset()` on panel      | `app/Providers/Filament/AdminPanelProvider.php`                                             | ✅     |
| 3   | Verify mail config                       | `.env` (read-only)                                                                          | ✅     |
| 4   | Hygiene migration                        | `database/migrations/2026_04_24_100001_assign_access_level_to_existing_users.php`           | ✅     |
| 5   | `users:reset-password` CLI               | `app/Console/Commands/ResetUserPasswordCommand.php`                                         | ✅     |
| 6   | Programmatic gate verification           | `tests/Feature/PanelAccessGateTest.php` (replaces manual browser step with 4 feature tests) | ✅     |
| 7   | Update auto-memory                       | `project_login_gate.md` + `MEMORY.md` entry                                                 | ✅     |

---

## Validation Results

| Check                              | Result | Details                                                                 |
| ---------------------------------- | ------ | ----------------------------------------------------------------------- |
| Syntax (`php -l`) — each new file  | ✅     | No errors on any of the 4 edited/created PHP files                      |
| Pint (lint)                        | ✅     | 3 auto-fixable style issues fixed, PASS on re-run                       |
| Password-reset routes registered   | ✅     | `admin/password-reset/request` and `admin/password-reset/reset` present |
| Artisan command discovered         | ✅     | `users:reset-password` appears in `artisan list`                        |
| CLI smoke test — national user     | ✅     | "Reset link sent"; queue-processed; URL is Filament signed format       |
| CLI smoke test — NULL-access user  | ✅     | Throws Exception with clear refusal message                             |
| `canAccessPanel` — 3 personas      | ✅     | national=true, local=true, NULL=false (verified via bootstrap script)  |
| `AccessLevelScopingTest`           | ✅     | All 9 existing tests still pass — no regressions in upstream scoping   |
| `PanelAccessGateTest` (new)        | ✅     | 4 passed (0 failed)                                                     |
| Data integrity spot-check          | ✅     | 1 national, 6 local, 0 regional, 9 NULL — as expected                   |

### Full test suite

Pre-existing failures: 16 tests fail in `Tests\Feature\Auth\*` and `Tests\Feature\Settings\*`. Root cause: Laravel Breeze/Volt scaffolding that references `Livewire\Volt\Volt` (package not installed) and tests `/login`/`/register`/`/reset-password` routes that are now redirected to `/admin/*`. These failures **predate this plan** and are orthogonal to my changes. They should be deleted in a separate cleanup task.

| Suite                        | Result             |
| ---------------------------- | ------------------ |
| Tests\Feature\AccessLevelScopingTest | 9 passed       |
| Tests\Feature\PanelAccessGateTest    | 4 passed (new) |
| Tests\Feature\Auth\*                 | Pre-existing failures (scaffolding) |
| Tests\Feature\Settings\*             | Pre-existing failures (scaffolding) |

---

## Files Changed

| File                                                                                         | Action | Lines       |
| -------------------------------------------------------------------------------------------- | ------ | ----------- |
| `app/Models/User.php`                                                                        | UPDATE | +8 / -2     |
| `app/Providers/Filament/AdminPanelProvider.php`                                              | UPDATE | +1          |
| `app/Console/Commands/ResetUserPasswordCommand.php`                                          | CREATE | +48         |
| `database/migrations/2026_04_24_100001_assign_access_level_to_existing_users.php`            | CREATE | +22         |
| `tests/Feature/PanelAccessGateTest.php`                                                      | CREATE | +54         |
| `/root/.claude-config/.../memory/project_login_gate.md`                                      | CREATE | +14 (memory) |
| `/root/.claude-config/.../memory/MEMORY.md`                                                  | UPDATE | +1 (memory) |

---

## Deviations from Plan

1. **CLI implementation** — rewrote to use Filament's auth broker + URL generator (instead of default `Password::broker()`) to produce working Filament URLs. See "Assessment vs Reality" for detail.
2. **Added feature tests** — 4 `PanelAccessGateTest` tests replace Task 6's manual browser check with programmatic verification. The existing Pest/test infrastructure made this free.
3. **Did not touch `routes/auth.php`** — the plan noted the existing redirects would "no longer collide" with Filament's new reset routes, which turned out to be half-right. The redirects at lines 10-11 point to `/admin/password/reset` (a non-existent URL — missing the hyphen in `password-reset`) and drop query params. Since the CLI now generates direct Filament URLs and the `/admin/password-reset/request` form is the user-facing entry, the broken Laravel-scoped redirects are dead code. Left them alone — cleanup is a separate concern.

---

## Issues Encountered

1. **Queue driver is `database`, not `sync`.** First CLI run appeared to send a Laravel-default URL because the queued notification hadn't been processed. Resolved by running `php artisan queue:work --stop-when-empty` — URL then rendered correctly. In production, a worker must be running for reset emails to dispatch.

2. **Pint auto-fixed 3 style issues** (import order, unary operator spacing, class brace position). All auto-applied; re-test passed.

3. **Tinker has no write access to `/var/www/.config/psysh`.** Switched to direct `php -r` with manual bootstrap — cleaner for one-off verification anyway.

4. **`app/Console/` directory didn't exist.** Laravel 11's minimal skeleton omits it by default. Created with `sudo -u www-data mkdir -p`. Laravel 11 auto-discovers commands placed there — no kernel registration needed.

---

## Tests Written

| Test File                                   | Test Cases                                    |
| ------------------------------------------- | --------------------------------------------- |
| `tests/Feature/PanelAccessGateTest.php`     | user with null access_level cannot access panel; national user can access panel; regional user can access panel; local user can access panel |

---

## Next Steps

- [x] Plan archived to `.claude/PRPs/plans/completed/`
- [x] Report written to `.claude/PRPs/reports/`
- [x] Auto-memory updated
- [ ] Review the CLI deviation — confirm the Filament-aware broker pattern is what you wanted
- [ ] Consider deleting the Laravel Breeze scaffolding tests in `tests/Feature/Auth/*` and `tests/Feature/Settings/*` — they were already broken, and cleaning them up is ~5 minutes
- [ ] Consider fixing the typo in `routes/auth.php:10-11` (`/admin/password/reset` → `/admin/password-reset/request`) if anyone hits that redirect via a stale bookmark
- [ ] Before rolling out: configure real SMTP in `.env` (currently `MAIL_MAILER=log`) and ensure a queue worker is running
- [ ] If you want LOCAL users with `church_id = NULL` rejected at the gate too, tighten `canAccessPanel` — noted as "Open question" in the plan
- [ ] Commit the changes (many pre-existing modified files in git status; recommend splitting into logical commits)
