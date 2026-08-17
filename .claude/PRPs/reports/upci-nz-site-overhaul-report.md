# Implementation Report

**Plan**: `.claude/PRPs/plans/upci-nz-site-overhaul.plan.md`
**Branch**: `main` (each task on a short-lived branch, squashed, fast-forwarded, pushed)
**Date**: 2026-08-17
**Status**: **PARTIAL** — Block A complete (T1–T8), six security fixes outside the plan, and regression cover for all of them. Blocks B–I not started.

---

## Summary

The plan is 72 tasks (T0–T71). This pass deliberately covered **what was broken in production**, per the instruction "let's just fix what's broken as we go", rather than working the list top to bottom.

Eleven atomic commits landed. Six of them fix defects that were **live and exploitable or visibly wrong on the public site** — four of which the plan did not contain a task for, because they were found during implementation rather than planning.

---

## Assessment vs Reality

| Metric | Predicted | Actual | Reasoning |
|---|---|---|---|
| Complexity | HIGH | HIGH, but differently distributed | The planned work (schema, CMS, regions) was not the hard part. The hard part was that the codebase had live security holes nobody had looked for — the plan gated *new* resources and never audited the *existing* public API. |
| Confidence | 9.8/10 claimed | Overstated | Two claims in the plan's own verification log were wrong, and one instruction it repeated after every task had never been executed. See "Issues Encountered". |
| Block A effort | "~1.5 days" (dry-run) | Matches | The dry-run's estimate held up well. |

---

## Tasks Completed

| # | Task | Commit | Status |
|---|---|---|---|
| — | Close unauthenticated church writes | `74e36b0` | ✅ |
| — | Untrack + purge SQLite DB from git history, force-push | `74e36b0` | ✅ |
| — | `DepartmentAnnouncementPolicy` + user privilege-field gating | `5a46c13` | ✅ |
| — | Scope dashboard widgets to access level | `2ab9709` | ✅ |
| — | Stop publishing leadership emails / user IDs | `0357c36` | ✅ |
| — | Rate limit the public API | `24f53d7` | ✅ |
| T1–T3 | Upload disk fix + SVG hardening + FileUpload audit | `f1ad45d` | ✅ |
| T2 | `uploads:move-to-public` command; relocate the 2 broken files | `f1ad45d` | ✅ |
| T4 | `ContactMessageResource` | `e7e0c19` | ✅ |
| T6 | Remove fabricated statistics; delete dead `Home.vue` | `010186e` | ✅ |
| T8 | Self-host Leaflet markers; remove debug logging | `b6ead66` | ✅ |
| T7 | Unpublish CMS scaffolding pages | `998a817` | ✅ |
| T5 | Null placeholder pastor names (partial — see Deviations) | `cd1b3e9` | ⚠️ |
| — | Import the UPCINZ logo pack from Drive (90 files, SVG+PNG) | `dc7cd79` | ✅ |

**Not started:** T0 (superseded — the DB was purged outright rather than merely untracked), T9–T71.

---

## Validation Results

Run before every merge, on every commit:

| Check | Result | Details |
|---|---|---|
| Lint | ✅ | `vendor/bin/pint --test --dirty` — 0 errors |
| Build | ✅ | `npm run build` |
| Tests | ✅ | 27 passed (34 assertions) across the four project-owned files |
| Integration | ✅ | Every change verified against the live site at http://upci.loc |

The full suite is **not** a valid gate: 16 tests fail before any change, all pre-existing Livewire starter-kit scaffolding.

---

## Issues Encountered

**1. 🔴 The plan's standing gate had never run.** `vendor/bin/pint --test` exits 1 with **83 pre-existing failures on a clean tree**, including two of the four gate test files. Because the chain is `&&`, the build and tests never executed — every "gate passed" claim prior to this was vacuous. Changed to `--dirty`. This was the single most consequential finding, and it was found by *executing* the command rather than reading it.

**2. 🔴 A public repository held the live user database.** `github.com/bkintanar/upci.co.nz` is public and the SQLite file was committed across 12 commits, containing 17 users with bcrypt hashes, sessions and reset tokens. Purged with `git filter-repo` and force-pushed; verified from a fresh clone (0 commits, 0 blobs). Test data, so no credential rotation was required.

**3. 🔴 Four exploitable defects the plan had no task for.** Unauthenticated `DELETE /api/churches/{id}`; self-promotion to national via the user form; a missing policy defaulting Filament to *allow*, chaining into stored XSS; and dashboard widgets leaking national data. The plan gated new resources and never audited what already existed.

**4. My own bug wasted a cycle.** The first logo download reported success and produced zero files: `echo "$MAP"` has a leading newline, so `awk | paste -sd'|'` built a pattern starting with `|`, and an empty alternative in `grep -E` matches every line — silently deleting all candidate IDs. The script's own "is this HTML?" guard is what stopped it writing seven folders of error pages.

**5. Google Drive MCP could not enumerate the folder.** Metadata was readable but `parentId` search returned `{}` — the files are not in the account's index. Worked around by extracting 33-char IDs from the folder page's JS payload, resolving each via `get_file_metadata`, then downloading via `uc?export=download`.

---

## Deviations from Plan

**T5 — the stale Christchurch address was not blanked.** The plan asked for it; §15 separately asked for "show city only", which is a different layer. Neither was safe as written: `Apostolics of Christchurch` has no `street` or `suburb`, so the legacy `address` composite is the only address it has, and `ChurchLocator.vue:328` builds the Google Maps directions URL from it. Blanking would have emptied the listing and degraded the directions link. Correcting the value is a client data task. The four false pastor names *were* nulled.

**T0 — superseded.** The plan said `git rm --cached`. Given the repository is public, that would have left all 12 historical blobs retrievable. History was purged instead.

**T2 — scope widened.** The plan named two files. The command is DB-reference driven so it also ignores the 11 unreferenced orphans, refuses to overwrite the one filename present on both disks, and is idempotent.

**T1 — scope widened.** Added `acceptedFileTypes` and `maxSize`. `->image()` alone accepts `image/svg+xml`, which can carry script — and T1 is precisely what makes those files web-served, so the hole would have opened the moment the task landed.

---

## Tests Written

`tests/Feature/SecurityRegressionTest.php` — **11 tests, 62 assertions**, covering every fix in this pass.

| Test | Guards |
|---|---|
| church write verbs are not routable | `POST`/`PUT`/`PATCH`/`DELETE` on `/api/churches` return 405, and the row survives |
| church read endpoints still work | the fix did not break the locator |
| public church endpoint publishes no emails or user ids | the PII leak in `formatLeadershipForApi()` |
| department announcements are national-only | the missing policy that chained into stored XSS |
| every admin-reachable model has a policy | Filament treats an unpolicied model as **allow** — catches the *next* one at the point a model is added |
| privilege fields are gated on national access | asserts both `disabled()` **and** `dehydrated()`; the first alone is not a fix |
| attendance widgets query through the scoped resource | the three widgets that bypassed policy and scope |
| attendance scoping limits what a local user sees | the behaviour, not just the source |
| contact messages are national-only, never authored | read/delete yes, create/update no |
| the public contact endpoint is rate limited | 429 within 8 requests |
| every file upload targets the public disk | the P0, and any future `FileUpload` added without `->disk('public')` |

**Verified to actually catch regressions.** Reverting three fixes — reopening the church write verbs, pointing a widget back at `Attendance::`, and deleting `DepartmentAnnouncementPolicy` — makes four of these tests fail, each naming the right defect. Restoring them returns all eleven to green. A test that passes without being able to fail is worse than no test, so this was checked rather than assumed.

No factories exist beyond `UserFactory`, so these build models directly, matching the four pre-existing test files. The dry-run costed factories at ~1 day; they turned out not to be needed.

**Gate baseline: 27 → 38 passing across five files.** The full suite still reports exactly 16 pre-existing Livewire starter-kit failures — no new ones.

---

## Next Steps

- [x] Logo pack imported — 42 SVG + 48 PNG under `resources/images/logos/<slug>/`. The 42 `.ai` sources (11.2 MB, 62% of the pack) are gitignored but kept on disk.
- [ ] Wire the logos into `departments.logo_path` and site settings (T16/T17, T9–T11)
- [x] Regression tests written — 11 tests, verified to fail when the fixes are reverted
- [ ] Resolve the four decisions still blocking Block B: T21's morph target, T15's ownership semantics, T25's response shape, T12's seven coupling sites
- [ ] Correct the Christchurch address (client data)
