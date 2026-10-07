# Implementation Report

**Plan**: `.claude/PRPs/plans/abc-new-student-enrolment-form.plan.md` (archived to `.claude/PRPs/plans/completed/`)
**Branch**: `feature/abc-enrolment-form` (from `feature/site-audit-fixes` @ `6919bd2`)
**Date**: 2026-10-02
**Status**: COMPLETE. Migrated on live on 2026-10-08, with the fee schedule filled in.

---

## Summary

The "First time student" card on `/apostolic-bible-college/enrollment` now opens the site's own enrolment form at
`/apostolic-bible-college/enrollment/new-student` instead of the Google Form "Apostolic Bible School Enrolment Form for
New Students".

- **The page:** a Vue view built to the approved design direction D3, "the application as a document". It has all 26
  Google Form questions with the same wording, plus an optional transcript upload.
- **The API:** submissions go to `POST /api/abc-enrolments`. The endpoint is throttled, has a honeypot field, and
  accepts multipart data. Transcripts are stored on the private disk.
- **The admin:** submissions land in a national-only Filament inbox, "ABC Enrolments". From there admins can download
  the transcript and delete the record.
- **Email:** a queued email notification is built, but it is sent only once `ABC_ENROLMENT_NOTIFY_EMAIL` is set.

Backup taken before any change: `/var/www/personal/upci.co.nz-backups/pre-abc-enrolment-20261002-215629/`, plus tag
`pre-abc-enrolment-20261002-215629`.

## Assessment vs Reality

| Metric | Predicted | Actual | Reasoning |
|---|---|---|---|
| Complexity | MEDIUM | MEDIUM | The backend matched the plan closely. The Vue port of D3 was the bulk of the work. |
| Confidence | 8/10 | Met | The plan held up. Two problems surfaced only in the real browser run: a pre-existing throttle bug, and error-message wording. |

## Deviations from Plan

1. **Order changed.** I built the backend (Tasks 2–8 and 12–14) before the design gate (Task 9), because only the Vue
   page depends on the chosen design.
2. **Fee schedule (Task 1)** was filled in once the owner approved copying it (2026-10-08). It is a real table rather
   than the Google Form's image.
3. **Error messages.** Laravel's default `:attribute field is required` produced phrases like "The whether you live in
   New Zealand field is required". Every rule now has a plain instruction instead, such as "Select whether you are
   currently residing in New Zealand". A test pins the wording.
4. **Found and fixed a pre-existing bug: the throttles shared one counter.** An unnamed `throttle:N,1` keys on
   `domain|ip`, the same key as the `/api` group's 60/min baseline. So the menu and settings reads the SPA makes on
   every page load used up the contact form's 5/min allowance, and **after about two page views, `/api/contact` refused
   a real visitor with a 429**.
   - Each route-level limit now has its own counter (`throttle:5,1,contact`, `throttle:20,1,address-search`,
     `throttle:5,1,abc-enrolment`).
   - Proven with a probe first. `ThrottleIsolationTest` fails without the fix (4 of 4 tests) and passes with it.
5. **Fonts.** `app.blade.php` loaded Figtree at 400/500/600 only. Weights 300, 700 and 800 and italic 500 were added,
   because D3 uses them. Every page that uses `font-bold` now gets the real 700 instead of a synthesised one.
6. **Church list kept in two places.** The list lives in `AbcEnrolment::CHURCHES` and in the Vue file, because there is
   no endpoint for it. `AbcEnrolmentNewStudentPageTest` fails if they drift apart.

## Tasks Completed

| # | Task | Files | Status |
|---|---|---|---|
| 0 | Backup | backups dir + git tag | ✅ |
| 1 | Fee schedule | Copied from the Google Form's page 6 (headless, never submitted) and set as an HTML table: Full Time $350, Part Time $250 per year | ✅ |
| 2 | Config + env | `config/abc.php`, `.env.example` | ✅ |
| 3 | Table | `2026_10_02_000001_create_abc_enrolments_table.php` | ✅ (run on live 2026-10-08) |
| 4 | Model | `app/Models/AbcEnrolment.php` | ✅ |
| 5 | Policy | `app/Policies/AbcEnrolmentPolicy.php` | ✅ |
| 6 | Notification | `app/Notifications/NewAbcEnrolment.php` | ✅ |
| 7 | Controller | `app/Http/Controllers/Api/AbcEnrolmentController.php` | ✅ |
| 8 | Route | `routes/web.php` | ✅ |
| 9 | huashu-design gate | `.claude/design/abc-enrolment/` (spec, 3 demos, `direction-approved.md`: "let's do D3.") | ✅ |
| 10 | Vue page | `resources/js/views/AbcNewStudentEnrolment.vue` | ✅ |
| 11 | SPA route | `resources/js/router/routes.js` | ✅ |
| 12 | API tests | `tests/Feature/AbcEnrolmentApiTest.php` | ✅ |
| 13 | Admin inbox + tests | `app/Filament/Resources/AbcEnrolments/*`, `tests/Feature/AbcEnrolmentAdminTest.php` | ✅ |
| 14 | Card link | `2026_10_02_000002_point_abc_first_time_card_at_native_form.php`, seeder, `AbcEnrolmentPageLinkTest.php` | ✅ (run on live 2026-10-08; card verified in the browser) |

## Validation Results

| Check | Result | Details |
|---|---|---|
| Syntax | ✅ | `php -l` on every changed PHP file |
| Lint | ✅ | `pint --test`: 20 files pass |
| Tests | ✅ | **273 passed, 25 skipped, 0 failed** (baseline 263 / 25 / 0). The 25 skipped are the starter-kit tests kept on purpose. |
| Build | ✅ | `npm run build` |
| Browser (Playwright, against a migrated **copy** of the live DB) | ✅ | See the breakdown below. |
| Live DB | ✅ | Migrated 2026-10-08 after a fresh backup (`upci.co.nz-backups/pre-abc-migrate-20261008-081835`). First-time card points to the new page; the work-exemption card is unchanged. |

Browser checks at 1440 and 375:
- No horizontal scroll and no console errors.
- An empty submit gives 21 errors, focuses the error summary, and its links focus the field (Gender lands on `#gender`).
- A full submission, exercising every conditional and attaching a transcript, returns 201. The row is stored correctly
  (yes/no answers as booleans, Other texts, residence country, transcript name).
- The receipt shows with focus on its heading.
- The running head marked all five sections done, and the signature echo read "Signed by Test Applicant on 2 October 2026".
- The two test transcript files that landed in `storage/app/private/abc-enrolments/transcripts/` were moved out to the
  session scratchpad, not deleted.

## Tests Written

| File | Covers |
|---|---|
| `AbcEnrolmentApiTest.php` (33) | Happy path, every required field, the yes/no allow-list, 3 conditionals (missing and stray), church allow-list for every church, transcript (private disk, type, size, dropped when "No"), dates, honeypot, notification on and off, no personal data in the email, throttle, plain-language messages |
| `AbcEnrolmentAdminTest.php` (11) | Policy matrix, 200/403 on the routes, no create or edit route, "No" answers render, XSS escaped, transcript download and the action hidden when there is none, delete removes the file, sort and filter |
| `AbcEnrolmentPageLinkTest.php` (4) | Migration changes only the first-time card (work exemption untouched), rollback restores, no-op when the page is missing, seeder |
| `AbcEnrolmentNewStudentPageTest.php` (5) | Vue church list and learning difficulties match the model, multipart POST with no hand-set Content-Type, route sits before the catch-all, the SPA serves the URL |
| `ThrottleIsolationTest.php` (4) | Browsing doesn't use up the contact, enrolment or address-search limits; each still has its own limit |

## Next Steps

- [ ] When decided, set `ABC_ENROLMENT_NOTIFY_EMAIL`, and make sure a queue worker and real `MAIL_*` settings exist in production.
- [ ] Owner decisions still open:
  - the "Work exemption application" card also points to the first-time Google Form;
  - which contact email to show;
  - retention period for enrolments;
  - wording of the privacy notice.
- [ ] Push the branches and open PRs: `feature/site-audit-fixes`, then `feature/abc-enrolment-form` on top of it.
