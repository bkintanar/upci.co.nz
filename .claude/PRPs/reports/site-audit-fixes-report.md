# Implementation Report

**Plan**: `.claude/PRPs/plans/site-audit-fixes.plan.md` (archived to `.claude/PRPs/plans/completed/`)
**Source Issue**: none. It came from a multi-agent site audit (workflow `wf_c4076799-024`).
**Branch**: `feature/site-audit-fixes` (from `main` @ `17787fd`)
**Date**: 2026-10-02 (implemented 2026-09-30 and re-validated 2026-10-02)
**Status**: COMPLETE. All 34 tasks are done and nothing is committed.

---

## Summary

The audit confirmed 49 defects, and the plan grouped them into 34 tasks across the Filament admin,
the public Vue SPA, the JSON API, routing and the auth leftovers, and the seeders. Six fixer agents
implemented them in parallel, each owning its own set of files (workflow `wf_27a90e69-bc7`). An
integrator agent brought the full suite to green. An independent reviewer then tested the security
fixes and found one more hole. The three tasks that needed the owner's decision (20, 26 and 27) were
done by hand after those decisions came in. The owner's rule was that **nothing gets deleted**: stale
tests are kept and skipped, and dead routes, controllers and methods are kept.

Backup taken before any change: `/var/www/personal/upci.co.nz-backups/pre-audit-20260930-063604/`
(repo bundle, tree tarball, live SQLite DB, uncommitted patch), plus git tag `pre-audit-20260930-063604`.

---

## Assessment vs Reality

| Metric | Predicted | Actual | Reasoning |
|---|---|---|---|
| Complexity | MEDIUM-HIGH | MEDIUM-HIGH | Most tasks were small. Tasks 2, 3 and 7 needed real authorisation design (`outranks()`, `SyncsLeadership`) |
| Confidence | High (49/49 confirmed, 0 refuted) | High | Every fix has a regression test, and fixers checked their tests by reverting the fix and watching them fail |

**Where the work went past the plan:**

- **Stored XSS in the admin address finder.** The plan only covered the public locator popups. The
  reviewer found unescaped `bindPopup` calls and `innerHTML` with an inline `onclick` in
  `packages/filament-address-finder`, which a local admin could use against a national admin. It is
  fixed, and the published copy in `public/js/...` is re-synced.
- **`/admin` sent through `<router-link>`.** Doing Task 20 (menu item 32 → `/admin`) showed that the
  SPA sent server-side paths through Vue's router, which ends on the SPA's not-found route.
  `resources/js/utils/menuUrl.js` `isPlainAnchor()` now sends `/admin` and `/storage` links through plain anchors.

---

## Tasks Completed

| # | Task | Key files | Status |
|---|---|---|---|
| 1 | Gallery admin 500 (`MorphToSelect::nullable()`) + regional owner bypass | `GalleryItemForm.php`, `GalleryItemResource.php` | ✅ |
| 2 | Regional → national account takeover; regional self-edit | `UserPolicy.php`, `UserResource.php` | ✅ |
| 3 | "Assign Leadership" cross-church hijack; leadership chosen on create was lost | `ChurchForm.php`, `Concerns/SyncsLeadership.php`, `Create/EditChurch.php` | ✅ |
| 4 | Church coordinates never saved | `ChurchForm.php`, `address-finder.js` | ✅ |
| 5 | Null region_id/church_id matching another null | `User.php`, `ChurchPolicy.php`, `AttendancePolicy.php` | ✅ |
| 6 | Local and regional users could move a church's region | `ChurchForm.php` | ✅ |
| 7 | Attendance took church_id from its creator | `CreateAttendance.php`, `AttendanceForm.php` | ✅ |
| 8 | is_published badge always read "Draft" (`$s` was never injected) | 4 tables | ✅ |
| 9 | ChurchesTable columns never visible | `ChurchesTable.php` | ✅ |
| 10 | MorphTo sort threw a SQL error | `GalleryItemsTable.php` | ✅ |
| 11 | Stored XSS in the locator popups | `ChurchLocator.vue` | ✅ |
| 12 | Modal map crashed (NaN) for churches with no coordinates | `ChurchLocator.vue` | ✅ |
| 13 | Multi-select service days filtered by the last day only | `ChurchLocator.vue`, `ChurchController.php` | ✅ |
| 14 | Empty gallery tabs showed nothing | `Gallery.vue` | ✅ |
| 15 | Homepage directory never showed the pastor | `ChurchDirectoryBlock.vue` | ✅ |
| 16 | `tel:null` links and empty rows | `ChurchLocator.vue` | ✅ |
| 17 | Dead "Contact Us About Starting a Church" button | `ChurchLocator.vue` | ✅ |
| 18 | Locator search race, no debounce | `ChurchLocator.vue` | ✅ |
| 19 | External grandchild and footer links turned into in-app routes | `MenuLink.vue`, `Navbar.vue`, `Footer.vue` | ✅ |
| 20 | "Minister's Log-in" pointed at `#` | live DB `menu_items.id=32` → `/admin` + `utils/menuUrl.js` | ✅ (owner approved) |
| 21 | Placeholder social links on /departments | `GetInvolved.vue` | ✅ |
| 22 | Calendar had no loading or error state | `Calendar.vue` | ✅ |
| 23 | Events feed used the UTC "today" | `EventsFeedBlock.vue` | ✅ |
| 24 | Inactive child menu items leaked | `MenuItemController.php` | ✅ |
| 25 | Draft departments and regions exposed through filters | `GalleryController.php`, `EventController.php` | ✅ |
| 26 | AGSUpdate ignored published_at | `AGSUpdate.php`, `AGSUpdateForm.php` | ✅ (owner: schedule) |
| 27 | `trustProxies('*')` let clients bypass the throttles | `bootstrap/app.php`, `.env.example` | ✅ (owner: Cloudflare) |
| 28 | API errors came back as HTML, not JSON | `bootstrap/app.php`, `useBlockData.js` + views | ✅ |
| 29 | Auth routes shadowed by the SPA catch-all; v3 redirect targets | `routes/web.php`, `routes/auth.php`, `VerifyEmailController.php` | ✅ (amended: nothing deleted) |
| 30 | Stale Auth tests | `tests/Feature/Auth/*` skipped + `FilamentAuthTest.php` | ✅ (amended: skipped, not deleted) |
| 31 | Stale Settings tests | `tests/Feature/Settings/*` skipped | ✅ (amended) |
| 32 | DashboardTest | old cases skipped + new `/admin` cases | ✅ |
| 33 | ChurchSeeder crashed | `ChurchSeeder.php` | ✅ |
| 34 | Seeded admin couldn't log in | `DatabaseSeeder.php`, `UserFactory.php` (`national()` state) | ✅ |

---

## Validation Results

| Check | Result | Details |
|---|---|---|
| Syntax (L1) | ✅ | `php -l` clean on every changed and new PHP file |
| Routes (L2) | ✅ | `login` → RedirectController; 0 `admin/register` or `admin/password/reset` routes |
| v3 leftovers (L3) | ✅ | 0 `fn ($s)`, 0 `MorphToSelect…nullable`, 0 `visible(fn ($record)` in the Churches tables |
| Lint (pint) | ✅ | `pint --test`: 56 files pass |
| Build (L4) | ✅ | `npm run build` built in 1.27s |
| Tests (L5) | ✅ | **216 passed, 25 skipped, 0 failed** (763 assertions). The baseline was 16 failed / 121 passed |
| Integration | ✅ | `php artisan serve` smoke test: `/login` → 302 `/admin/login`; `/forgot-password` → 302 `/admin/password-reset/request`; `/api/nope` → 404 JSON; `/api/gallery?department=nope` → 422 JSON; `/admin` → 302 login; `/find-church` 200; header menu has `Minister's Log-in → /admin` |
| Deletions | ✅ | `git status` shows 0 deleted paths |

The 25 skipped tests are the stale starter-kit tests that were kept on purpose (16 Auth, 7 Settings and
2 old `/dashboard` cases). Each one gives a reason that names its replacement.

---

## Files Changed

- **Updated:** 65 tracked files (+1366/−276 lines). Most of the removed lines are code replaced as part of a fix; no file was deleted.
- **Created:** 12 files.
  - `app/Filament/Resources/Churches/Concerns/SyncsLeadership.php` (+87)
  - `resources/js/components/MenuLink.vue` (+39)
  - `resources/js/utils/menuUrl.js` (+14)
  - 9 test files (listed below)
- **Data:** one row in the live DB, `menu_items.id = 32`, url `#` → `/admin`.

---

## Deviations from Plan

1. **No deletions** (owner constraint, recorded in the plan's OWNER CONSTRAINTS section):
   - The stale tests are skipped, not deleted.
   - `VerifyEmailController` is kept and only its redirect target was fixed.
   - Every route in `routes/auth.php` is kept, with its redirect pointed at the real v4 panel URL.
   - The empty `admin` route group is kept.
   - `Church::setAssignedLeadershipAttribute()`, the dead address-finder handlers, the unreachable gallery fallback and the cache-busters are all kept.
2. **Tasks 20, 26 and 27** were done after the owner decided them (they were deferred in the plan).
3. **Additions beyond the plan:**
   - The admin address-finder XSS fix.
   - `isPlainAnchor()` for server paths.
   - `localScope` self-inclusion in `UserResource`.
   - `AttendanceEditScopeTest` and `MapPopupEscapingTest`.
4. **Integration note:** `APP_DEBUG=true` in the local `.env` puts stack traces in JSON error responses. Production must run with `APP_DEBUG=false`. That was already true of the old HTML error pages, so it isn't new.

## Issues Encountered

- The workflow script failed to parse the first time, because apostrophes broke the single-quoted strings. Switching to template literals fixed it.
- `TrustedProxyTest` first registered its probe route outside `/api`, where the SPA catch-all swallowed it. Moving the probe under `/api/_test/` fixed it.
- The auth-test fixer couldn't check its JSON-404 assertion until the API fixer's `bootstrap/app.php` change had landed. The integrator ran it afterwards and it passed.

## Tests Written

| Test file | Covers |
|---|---|
| `GalleryOwnershipTest.php` (+) | National CRUD pages return 200; regional standalone create → 403; a tampered owner is ignored; national can reassign |
| `SecurityRegressionTest.php` (+) | Regional can't view or update national users or regional peers; self-edit works with no church; attendance scoping and tampering |
| `AccessLevelScopingTest.php` (+) | A null region or church never matches another null in User, ChurchPolicy or AttendancePolicy |
| `ChurchPolicyLocalEditTest.php` (+) | Leadership select is disabled for local users and its options are scoped; nothing is written before save; forbidden syncs → 403; national create keeps leaders; region_id is national-only; lat/lng persist |
| `AdminTablesTest.php` | Published/Draft badges; ChurchesTable columns visible; gallery owner column isn't sortable |
| `ChurchSearchFilterTest.php` (+) | `service_day[]` multi-select, and old scalar links still work |
| `MenuApiTest.php` | Inactive children and grandchildren are excluded |
| `ApiVisibilityTest.php` | Draft department/region filters give 422; draft-owned events are hidden; API errors are JSON |
| `FilamentAuthTest.php` | Panel login, password reset, `/login` and `/forgot-password` redirects, logout, JSON 404 for unknown `/api/*` |
| `DashboardTest.php` (+) | `/admin` guest redirect; access-level user gets 200; NULL access_level gets 403 |
| `SeederTest.php` | ChurchSeeder runs on the current schema; the seeded admin can access the panel |
| `AttendanceEditScopeTest.php` | Moving an attendance record on edit stays within the actor's scope |
| `MapPopupEscapingTest.php` | Every popup interpolation is escaped in the locator and the address finder; the published asset matches the source |
| `AGSUpdateSchedulingTest.php` | A future published_at is hidden until it passes; drafts stay hidden |
| `TrustedProxyTest.php` | X-Forwarded-For is ignored from an untrusted client and honoured from Cloudflare; rotating XFF can't get past the contact throttle |

---

## Open Owner Decisions (not defects introduced by this work)

- An unfiltered `/api/gallery` still returns items that belong to unpublished departments or regions.
- Regional users can still see national and regional users' names and emails in the Users list. View and edit are refused.
- Moving an attendance record on edit, within the actor's scope, is allowed. Add `->disabledOn('edit')` to block it.
- `ChurchSeeder` guessed the region slug for Hamilton and Tauranga ("central").
- Read-only check: are there live users with `access_level = regional` and a NULL `region_id`?

## Next Steps

- [ ] Browser checks:
  - picking an address in the admin saves lat/lng
  - `/find-church` popups, the no-map fallback, fast typing
  - external menu links
  - the calendar's error state
  - Minister's Log-in → `/admin`
- [ ] Commit, possibly split by area, then open a PR (`/prp-pr`)
- [ ] Confirm `APP_DEBUG=false` in production before deploying
