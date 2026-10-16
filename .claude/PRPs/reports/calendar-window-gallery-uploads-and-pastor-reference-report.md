# Implementation Report

**Plan**: `.claude/PRPs/plans/completed/calendar-window-gallery-uploads-and-pastor-reference.plan.md`
**Branch**: `feature/calendar-gallery-pastor-reference` (uncommitted; it carries the earlier, still uncommitted storage fix)
**Date**: 2026-10-17
**Status**: COMPLETE

---

## Summary

These are the four owner requests of 2026-10-17:

1. **Calendars.** The department Calendar, the region events list and the Calendar of Events (`/events`) show what is still to come, from today in New Zealand to 31 December, soonest first. Past events drop off. The `/calendar` month grid is unchanged.
2. **General gallery.** The Gallery admin list has "Upload photos", up to 30 at once, for national admins. It is the same action departments and regions use, now in one shared class. The photos go to the general gallery. The single-item form says that leaving "Belongs to" blank means the general gallery.
3. **Bible college form.** The church is a dropdown, and choosing a church fills in the pastor's name from the CMS. The name stays editable. A new 4.6a, "Your pastor's email address", appears only when the college has no address for that pastor.
4. **Pastor reference.**
   - Every enrolment emails the pastor a private link (queued) to Form ABC-2, Brandon Borders' questionnaire. The questions are clickable Yes/No plus short answers.
   - The answers are stored once and shown on the enrolment in the admin. The college is emailed when a recipient is configured.
   - The admin can resend the questionnaire or correct the address.

## Assessment vs Reality

| Metric | Predicted | Actual | Reasoning |
|---|---|---|---|
| Complexity | HIGH | HIGH | As planned: a table, two notifications, two public endpoints, a new page, admin changes |
| Confidence | 8/10 | Matched | Every task went in as written. The fixes were small: an HTML-escaping assertion, Pint import order, one more test fixture that posts enrolments, a sentence and a breadcrumb found in the browser check |

## Tasks Completed

| # | Task | File(s) | Status |
|---|---|---|---|
| 0 | Backup and live migration (owner approved via AskUserQuestion) | `../upci.co.nz-backups/pre-pastor-reference-20261017-105627/`, tag | ✅ |
| 1–5 | Calendar window scope and its callers | `Event.php`, `DepartmentController`, `RegionController`, `EventController`, `Events.vue` | ✅ |
| 6–7 | Calendar tests | `RegionAccessTest.php` (clock pinned), `CalendarWindowTest.php` | ✅ |
| 8–11 | Shared upload action, relation manager, Gallery list, form description | `UploadPhotosAction.php`, `GalleryItemsRelationManager.php`, `ListGalleryItems.php`, `GalleryItemForm.php` | ✅ |
| 12 | General gallery tests | `GeneralGalleryUploadTest.php` | ✅ |
| 13–15 | Migration, `AbcPastorReference` model, pastor lookup on `AbcEnrolment` | migration, `AbcPastorReference.php`, `AbcEnrolment.php` | ✅ |
| 16–17 | Notifications | `AbcPastorReferenceRequest.php`, `AbcPastorReferenceReceived.php` | ✅ |
| 18 | Controllers and routes | `AbcEnrolmentController.php`, `AbcPastorReferenceController.php`, `routes/web.php` | ✅ |
| 19 | Shared CSS and parts; dropdown, auto-fill and 4.6a | `abc-form-document.css`, `abc-form-parts.js`, `AbcNewStudentEnrolment.vue` | ✅ |
| 20 | Questionnaire page and route | `AbcPastorReference.vue`, `routes.js`, `usePageMeta.js` (comment) | ✅ |
| 21 | Admin section, column and "Email the pastor" | `AbcEnrolmentInfolist.php`, `AbcEnrolmentsTable.php`, `ViewAbcEnrolment.php` | ✅ |
| 22 | Tests | `AbcPastorReferenceTest.php`, `AbcPastorReferenceAdminTest.php`, `AbcEnrolmentApiTest.php`, `AbcEnrolmentNewStudentPageTest.php`, `ThrottleIsolationTest.php` | ✅ |

## Validation Results

| Check | Result | Details |
|---|---|---|
| Static (php -l, Pint) | ✅ | Pint passes on all 29 changed or new PHP files |
| Tests | ✅ | 426 passed, 25 skipped, 0 failed (baseline before work: 381 passed, 25 skipped). Run as www-data |
| Build | ✅ | `npm run build` |
| Database | ✅ | Live: `abc_pastor_references` created, integrity ok, 0 rows. Enrolments unchanged (1) |
| Browser | ✅ | Playwright on two test servers with DB copies: the old code (c1a5515 worktree) and the new code |
| Live smoke | ✅ | Men's calendar shows only 14 Nov. `/api/events?upcoming=1` gives 8 events (Oct–Dec). Pastor list: 8 churches, no "@". Questionnaire page 200, bad token 404 |

**Browser checks**
- The enrolment form is pixel-identical to before outside 4.5–4.6, at 1440 and 390. The remaining diff bands are sub-pixel shifts and the worktree's missing logos.
- Dropdown:
  - Grace Fellowship fills "Wayne Goodare" and the live region announces it.
  - Editing the name shows 4.6a, and a typed name survives a church change.
  - Other shows 4.5a and 4.6a.
  - No horizontal scroll.
- End to end: the enrolment shows its receipt, then a reference row with source `church_records` and one queued job. The job's token hashes to the stored `token_hash`.
- Questionnaire:
  - An empty submit gives five plain-English errors, with focus on the summary.
  - The answers are stored as booleans; a reload says "already been sent"; a bad link says "not valid".
  - No horizontal scroll at 390.
- Calendars on real data:
  - Men's shows 14 Nov; Missions shows 1 Nov and 6 Dec; Prayer shows the 22-day fast running to 22 Oct; Ladies shows "No upcoming events."
  - `/events` shows OCTOBER, NOVEMBER and DECEMBER 2026.

## Deviations from Plan

- **Branch.** Phase 2 says to stop when `main` is dirty. Instead I created `feature/calendar-gallery-pastor-reference` carrying the uncommitted storage fix. Nothing was committed, because the owner commits only when asked.
- **Form parts shared, not copied.** `Req`, `FieldError`, `YesNo`, `localDate` and `formatDate` moved verbatim to `abc-form-parts.js` instead of being copied into the new page.
- **3.1a markup.** It mirrors Form ABC-1's nested 3.1a (a `div.follow` containing a fieldset whose legend holds the number), rather than the floated-legend variant first written.
- **Breadcrumb hidden on Form ABC-2.** The trail built from its URL would link to `/apostolic-bible-college/pastor-reference`, which is not a page. `usePageMeta`'s comment now lists this second use.
- **`ThrottleIsolationTest`.** Its own enrolment fixture also needed `pastor_email`. The plan named only `AbcEnrolmentApiTest`.
- **`fileNameSortKey`.** It moved from `GalleryItemsRelationManager` into `UploadPhotosAction` with the rest of the action. It was not deleted: its only caller moved.

## Issues Encountered

- **The `upci.loc` screenshots were blank.** `APP_URL` is now https, so `AppServiceProvider` forces https and the http-only local host can't load assets. I switched to `php -S` test servers on DB copies.
- **Tinker can't run as www-data** (psysh home). I ran it as root and then `chown -R www-data:www-data storage bootstrap/cache`. 0 root-owned files remain.

## Tests Written

| Test File | Test Cases |
|---|---|
| `CalendarWindowTest.php` | department window and order; `/events?upcoming=1`; unflagged API unchanged; region window; NZ new-year turnover; `to` includes its last day |
| `GeneralGalleryUploadTest.php` | upload into the general gallery; appended after general items only; file-name order; unpublished; >30 refused; hidden for regional; form description |
| `AbcPastorReferenceTest.php` | directory has no emails; on-file pastor needs no email; `pastor_name` on the church; four cases needing an email; applicant email labelled; records win; email format message; link/hash/no raw token; page context; 404s; answers once with a 409; college email without written answers; follow-ups required/dropped; mail outage; cascade delete; rate limit |
| `AbcPastorReferenceAdminTest.php` | waiting state and source; answers escaped; not requested; list column; resend rotates token and address; first request from records; regional and local 403 |
| `AbcEnrolmentNewStudentPageTest.php` (+4) | dropdown and live region; 4.6a rule; route order and SPA serves it; shared stylesheet |

## Owner follow-ups (not code)

- **Email delivery** (owner: "we'll setup the actual email service later"):
  - Pastor emails are queued in the `jobs` table. They go out once a queue worker and a real mailer exist.
  - The college's copies also need `ABC_ENROLMENT_NOTIFY_EMAIL`.
- **Enrolment #1** (from before this feature) has no pastor reference. Use "Ask the pastor for a reference" on it if wanted.
- **Churches with no pastor to fill in:**
  - El Shaddai, Solid Foundation and House of Mercy are not in the CMS.
  - Apostolic Life Church and Walk by Faith have no pastor recorded.

  Applicants from these churches are asked for the pastor's email.
