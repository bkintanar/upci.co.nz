# Feature: Calendar window, general-gallery uploads, church dropdown and pastor reference

## Summary

This plan covers four owner requests from 2026-10-17, built in one pass:

1. **Calendars.** The calendar lists (department pages, `/events`, region pages) show events that are not over yet, soonest first, through 31 December. Past events drop off.
2. **General gallery.** The CMS Gallery list gets the same bulk "Upload photos" action that departments and regions have. The upload logic is extracted so both share it, and photos uploaded there land in the general gallery.
3. **Church dropdown.** On the Bible college enrolment form, the 13-option church radio list becomes a dropdown. Choosing a church fills in the pastor's name from the CMS.
4. **Pastor reference.** Each enrolment creates a pastor-reference request. The pastor is emailed a private link to Brandon Borders' questionnaire. Their answers are stored, the college is emailed, and the answers appear on the enrolment in the admin.

## User Story

- As a **visitor**, I want the calendar to start at what's coming up and run to the end of the year, so that I'm not scrolling past last April to find next month.
- As a **national admin**, I want to upload a batch of photos straight into the general gallery, so that I don't add them one at a time.
- As an **applicant**, I want to pick my church from a dropdown and have my pastor's name filled in, so that the form is quicker and spelled right.
- As the **college** (Brandon Borders), I want the applicant's pastor emailed a short questionnaire with clickable Yes/No answers that comes straight back to us, so that we get a pastor's approval without chasing it by hand.

## Problem Statement

1. **Calendars.** `DepartmentController::show`, `RegionController::events` and `Events.vue` (`/api/events` with no params) list every published event from January on.
   - On 2026-10-17 the Men's calendar opens on April and August, both past.
   - The department page's empty text already says "No upcoming events."
2. **General gallery.** The Gallery admin list (`ListGalleryItems`) has only the single-photo Create. The bulk "Upload photos" exists only in `GalleryItemsRelationManager` (departments and regions).
   - "Leave Belongs to blank for the general gallery" is a code comment, not something the admin sees.
3. **Church dropdown.** Question 4.5 is a 13-option radio list plus Other. Question 4.6 "Who is your church pastor?" is free text, although the CMS knows most pastors (10 of 13 churches have a `churches` row, 8 of them with a pastor on file).
4. **Pastor reference.** There is no pastor reference at all. The college asks pastors by hand.

## Solution Statement

1. **Calendars.**
   - Add `Event::scopeUpcomingThisYear()`. "Today" is New Zealand's date (`config('app.display_timezone')`). It keeps events whose end date (or start date, when there is no end) is today or later and whose start is on or before 31 Dec of this year, using `whereDate` so SQLite compares dates rather than `'Y-m-d H:i:s'` strings.
   - Department and region payloads always use it. `/api/events` uses it when `?upcoming=1`, which `Events.vue` sends.
   - The `/calendar` month grid and the homepage feed are unchanged.
   - Fix the `to` filter's string comparison while here.
2. **General gallery.**
   - Extract the action to `App\Filament\Resources\Shared\UploadPhotosAction::make(Closure $owner)`. The closure returns the Department or Region, or null for the general gallery.
   - The relation manager and `ListGalleryItems` both use it, and `ListGalleryItems` authorises it with `GalleryItemResource::canCreate()` (national only).
   - The single-item form's Section gets a description: "Leave Belongs to blank…". `MorphToSelect` has no `helperText()`.
3. **Church dropdown.**
   - Keep the Google Form list (`AbcEnrolment::CHURCHES`; the owner chose it in the original enrolment plan) and render it as a `<select>`.
   - A new `GET /api/abc-enrolments/pastors` maps each listed church to a pastor's name and a `pastor_on_file` flag. It never sends an email address.
   - Choosing a church fills 4.6. The field stays editable, and the fill is announced in a polite live region.
   - When the college has no email for that pastor (Other church, no pastor on file, or the applicant changed the name), a new question 4.6a "Your pastor's email address" appears and is required.
4. **Pastor reference.**
   - New table `abc_pastor_references`, one per enrolment. It stores a sha256 token hash, pastor name and email, the email's source, and the answers.
   - On enrolment, create it and email the pastor `AbcPastorReferenceRequest`, queued and on demand. The link is `/apostolic-bible-college/pastor-reference/{token}`.
   - The SPA page `AbcPastorReference.vue` reuses the approved D3 form-document design. It GETs the context, then POSTs the answers.
   - On submit, store the answers once (an atomic `submitted_at` update) and email the college `AbcPastorReferenceReceived` at the existing `ABC_ENROLMENT_NOTIFY_EMAIL`.
   - Admin: a "Pastor's reference" infolist section, a table status column, and an "Email the pastor" header action to resend (rotates the token) or correct the address.

## Metadata

| Field | Value |
|---|---|
| Type | ENHANCEMENT (1–3) + NEW_CAPABILITY (4) |
| Complexity | HIGH: new table, two notifications, two public endpoints, a new SPA page and admin changes |
| Systems Affected | Event model + 3 API controllers + Events.vue; Filament gallery resource + relation manager; ABC enrolment API, model, Vue form, admin; new pastor reference model/API/page/notifications |
| Dependencies | Laravel 12.70, Filament 4.15.1, Livewire 3.8.10, Vue 3.5, Pest 3. No new packages |
| Estimated Tasks | 22 |

---

## UX Design

### Before State
```
╔══════════════════════════════════════════════════════════════════════════════╗
║ CALENDARS   /departments/mens ─► Calendar: 10 Apr (past) · 22 Aug (past) ·     ║
║             14 Nov              /events ─► JANUARY 2026 … greyed past months  ║
║             first, the current month is a long scroll down                     ║
║ GALLERY     Admin ▸ Gallery ▸ [New gallery item] one photo at a time;          ║
║             "Belongs to" blank = general gallery is undocumented               ║
║ ENROLMENT   4.5 13 radio boxes + Other ─► 4.6 type the pastor's name by hand   ║
║             submit ─► college (if configured) gets "New ABC enrolment"         ║
║ PASTOR      nothing: the college emails or phones the pastor by hand           ║
╚══════════════════════════════════════════════════════════════════════════════╝
```

### After State
```
╔══════════════════════════════════════════════════════════════════════════════╗
║ CALENDARS   department / region / Calendar of Events ─► 17–22 Oct (running) · ║
║             1 Nov · 14 Nov · 6 Dec, then stops at 31 Dec. Past ones are gone   ║
║             (the /calendar month grid still navigates any month)               ║
║ GALLERY     Admin ▸ Gallery ▸ [Upload photos] ─► up to 30 ─► N items, general  ║
║             gallery, file-name order. The form says blank = general gallery    ║
║ ENROLMENT   4.5 [ Select your church ▾ ] ─► 4.6 "Chester Kebasen" filled in    ║
║             (editable; live region: "Pastor filled in from church records")    ║
║             if no email on file / Other / edited ─► 4.6a pastor's email (req)  ║
║             submit ─► enrolment + pastor reference row (token hash)            ║
║                    ─► queued mail to pastor: "Dear Pastor, An applicant…"      ║
║                       [Fill out the questionnaire] → /apostolic-bible-college/ ║
║                       pastor-reference/{token}                                 ║
║ PASTOR      Form ABC-2 · Pastor's reference                                    ║
║             1.1–1.3 faithful in attendance / holiness & modesty / tithes Y/N   ║
║             2 serving? Y/N → 2a how (short answer)                             ║
║             3 approve? Y/N → 3a happy at Certificate Level (Year 1)? Y/N       ║
║             4 anything else (optional) · signature: pastor's name              ║
║             [Send to the college] ─► stored once ─► college mail + admin       ║
║ ADMIN       ABC Enrolments ▸ view ▸ "Pastor's reference" section (status,      ║
║             answers, email source) · [Email the pastor] resend/correct         ║
║             · table column "Pastor's reference": Waiting / Approved / …        ║
╚══════════════════════════════════════════════════════════════════════════════╝
```

### Interaction Changes
| Location | Before | After | User Impact |
|---|---|---|---|
| `/departments/{slug}` Calendar | all published events, from January | events not over, to 31 Dec, soonest first | sees what's next |
| `/regions/{slug}` events | all regional events | same window | same |
| `/events` | whole year, past greyed | from now to 31 Dec, grouped by month | starts at this month |
| Admin ▸ Gallery list | Create only | Upload photos (national) + Create | bulk general-gallery uploads |
| Admin ▸ Gallery item form | silent "blank = general" | section description says so | no guessing |
| Enrolment 4.5 | 14 radios | one `<select>` (13 + Other) | shorter form |
| Enrolment 4.6 | free text | auto-filled, editable | fewer typos |
| Enrolment 4.6a | — | pastor's email, only when not on file | reference can be sent |
| `/apostolic-bible-college/pastor-reference/{token}` | — | questionnaire page | pastor replies in ~2 minutes |
| Admin ▸ ABC enrolment view | no pastor info beyond the name | reference section + resend action | the college sees approval |

---

## Mandatory Reading

| Priority | File | Lines | Why |
|---|---|---|---|
| P0 | `app/Http/Controllers/Api/AbcEnrolmentController.php` | all | validation/messages/notification pattern to extend and mirror |
| P0 | `resources/js/views/AbcNewStudentEnrolment.vue` | 280–330, 449–704, 706–951 | question markup, helpers (`Req`, `FieldError`, `YesNo`, `q`, `invalid`, `describedBy`), submit flow, D3 CSS |
| P0 | `app/Filament/Resources/Shared/GalleryItemsRelationManager.php` | 83–186 | the action being extracted |
| P0 | `app/Models/Event.php` | all | scope style |
| P1 | `app/Notifications/NewAbcEnrolment.php` | all | scalar-only queued on-demand notification |
| P1 | `app/Filament/Resources/AbcEnrolments/Schemas/AbcEnrolmentInfolist.php` | all | Section/Grid/yesNo/longText helpers |
| P1 | `app/Filament/Resources/AbcEnrolments/Pages/ViewAbcEnrolment.php` | all | header action style |
| P1 | `app/Filament/Resources/AbcEnrolments/Tables/AbcEnrolmentsTable.php` | all | column style |
| P1 | `database/migrations/2026_10_15_000001_add_photo_to_department_announcements.php` | all | migration docblock style |
| P1 | `routes/web.php` | 17–90 | API group and named throttles |
| P1 | `resources/js/router/routes.js` | 55–70 | route placement before `/:slug(.*)` |
| P2 | `tests/Feature/AbcEnrolmentApiTest.php` | all | payload helper, Notification fakes |
| P2 | `tests/Feature/AbcEnrolmentAdminTest.php` | 1–70 | admin user/record helpers |
| P2 | `tests/Feature/GalleryBulkUploadTest.php` | all | Livewire action test pattern |
| P2 | `app/Models/Church.php` | 90–135 | `users()`, `pastors()`, `scopeActive` |
| P2 | `.claude/design/abc-enrolment/direction-approved.md` | all | D3 rules the new page inherits |

**External documentation**

| Source | Section | Why |
|---|---|---|
| [Laravel 12 Notifications](https://laravel.com/docs/12.x/notifications#on-demand-notifications) | On-Demand Notifications, Queueing | `Notification::route('mail', …)`, `ShouldQueue` |
| [Laravel 12 Mocking](https://laravel.com/docs/12.x/mocking#interacting-with-time) | Interacting With Time | `travelTo` with an explicit NZ instant |
| [Laravel 12 Queries](https://laravel.com/docs/12.x/queries#additional-where-clauses) | whereDate | SQLite compiles to `strftime('%Y-%m-%d', col)` |
| [Filament 4 Actions](https://filamentphp.com/docs/4.x/actions/overview) | Authorization | a closure `authorize()` returning false hides the action |
| [GOV.UK radios](https://design-system.service.gov.uk/components/radios/) | Yes/No | fieldset + legend, inline only for two short options |

---

## Patterns to Mirror

**On-demand queued notification, scalars only**
```php
// SOURCE: app/Notifications/NewAbcEnrolment.php:18-38
class NewAbcEnrolment extends Notification implements ShouldQueue
{
    use Queueable;
    public function __construct(public int $enrolmentId, public string $fullName) {}
    public function via(object $notifiable): array { return ['mail']; }
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)->subject("New ABC enrolment: {$this->fullName}")
            ->line("{$this->fullName} has submitted a first-time student enrolment for the Apostolic Bible College.")
            ->action('View in admin', url("/admin/abc-enrolments/{$this->enrolmentId}"));
    }
}
```

**Dispatch that must never fail the request**
```php
// SOURCE: AbcEnrolmentController.php:159-167
if ($to = config('abc.enrolment_notify_to')) {
    rescue(fn () => Notification::route('mail', $to)->notify(new NewAbcEnrolment($record->id, $record->full_name)), report: true);
}
```

**Validation with GOV.UK-style messages**: see `REQUIRED_MESSAGES` and `'church_name.in' => 'Select a church from the list, or choose Other'`.

**Named throttle per route**
```php
// SOURCE: routes/web.php (ABC block)
Route::post('/abc-enrolments', [AbcEnrolmentController::class, 'store'])->middleware('throttle:5,1,abc-enrolment');
```

**Vue question markup**
```html
<!-- SOURCE: AbcNewStudentEnrolment.vue:322-329 -->
<div :class="q('pastor_name')">
    <span class="qn">4.6</span>
    <div>
        <label class="l" for="pastor_name">Who is your church pastor?<Req /></label>
        <FieldError :errors="errors" field="pastor_name" />
        <input id="pastor_name" v-model="form.pastor_name" type="text" class="w-l" :aria-invalid="invalid('pastor_name')" :aria-describedby="describedBy('pastor_name')">
    </div>
</div>
```

**Infolist helpers**: `AbcEnrolmentInfolist::yesNo($field, $label)` and `::longText($field, $label)` (`TextLines::split`, escaped, never `->html()`).

**Tests**: Pest `test('…', function () {…})`.
- Notifications are asserted with `Notification::fake()` and `assertSentOnDemand(Class, fn ($n, $channels, $notifiable) => $notifiable->routes['mail'] === …)`.
- Livewire uses `callTableAction` and `assertNotified`.
- Run tests as www-data.

---

## Files to Change

| File | Action | Justification |
|---|---|---|
| `app/Models/Event.php` | UPDATE | `scopeUpcomingThisYear()` |
| `app/Http/Controllers/Api/DepartmentController.php` | UPDATE | events use the window |
| `app/Http/Controllers/Api/RegionController.php` | UPDATE | events use the window |
| `app/Http/Controllers/Api/EventController.php` | UPDATE | `?upcoming=1`; inclusive `to` via whereDate |
| `resources/js/views/Events.vue` | UPDATE | fetch `/api/events?upcoming=1` |
| `tests/Feature/RegionAccessTest.php` | UPDATE | pin the clock on a test whose fixed 2026-10-02 date is now past |
| `tests/Feature/CalendarWindowTest.php` | CREATE | window, NZ date, order, year end, `to` inclusive |
| `app/Filament/Resources/Shared/UploadPhotosAction.php` | CREATE | extracted bulk upload, owner or general |
| `app/Filament/Resources/Shared/GalleryItemsRelationManager.php` | UPDATE | use the shared action |
| `app/Filament/Resources/GalleryItems/Pages/ListGalleryItems.php` | UPDATE | header action, general gallery |
| `app/Filament/Resources/GalleryItems/Schemas/GalleryItemForm.php` | UPDATE | section description |
| `tests/Feature/GeneralGalleryUploadTest.php` | CREATE | general uploads, national only, ordering |
| `database/migrations/2026_10_17_000001_create_abc_pastor_references_table.php` | CREATE | table |
| `app/Models/AbcPastorReference.php` | CREATE | model, token helpers, status |
| `app/Models/AbcEnrolment.php` | UPDATE | `pastorReference()`, `pastorOnFile()`, `pastorDirectory()`, `samePastor()` |
| `app/Notifications/AbcPastorReferenceRequest.php` | CREATE | email to pastor |
| `app/Notifications/AbcPastorReferenceReceived.php` | CREATE | email to college |
| `app/Http/Controllers/Api/AbcEnrolmentController.php` | UPDATE | `pastors()`, `pastor_email`, create reference + notify |
| `app/Http/Controllers/Api/AbcPastorReferenceController.php` | CREATE | show + store by token |
| `routes/web.php` | UPDATE | 3 routes |
| `resources/js/views/abc-form-document.css` | CREATE | D3 form-document styles shared by both ABC pages (moved, not rewritten) |
| `resources/js/views/AbcNewStudentEnrolment.vue` | UPDATE | select, auto-fill, 4.6a, shared CSS |
| `resources/js/views/AbcPastorReference.vue` | CREATE | Form ABC-2 page |
| `resources/js/router/routes.js` | UPDATE | route before the catch-all |
| `app/Filament/Resources/AbcEnrolments/Schemas/AbcEnrolmentInfolist.php` | UPDATE | reference section |
| `app/Filament/Resources/AbcEnrolments/Tables/AbcEnrolmentsTable.php` | UPDATE | status column, eager load |
| `app/Filament/Resources/AbcEnrolments/Pages/ViewAbcEnrolment.php` | UPDATE | "Email the pastor" action |
| `tests/Feature/AbcEnrolmentApiTest.php` | UPDATE | payload helper gains `pastor_email`; "nothing sent" test narrowed to the college email |
| `tests/Feature/AbcPastorReferenceTest.php` | CREATE | lookup, enrolment → reference + mail, page API, submit once, college mail |
| `tests/Feature/AbcPastorReferenceAdminTest.php` | CREATE | infolist, status column, resend |
| `tests/Feature/AbcEnrolmentNewStudentPageTest.php` | UPDATE | add select/auto-fill/route source checks (existing checks kept) |

## NOT Building (Scope Limits)

- **A level-of-study choice on the enrolment form.** Every new student starts at Certificate Level (the form says so), so 3a names Certificate Level (Year 1). Add a field only if the college asks.
- **Replacing the Google Form church list with the `churches` table.** The owner chose this list in the original plan. El Shaddai, Solid Foundation and House of Mercy get pastor auto-fill once they're added in the CMS and mapped.
- **Real email delivery** (SMTP, queue worker, recipient). The owner said "we'll setup the actual email service later". Mail is queued (`QUEUE_CONNECTION=database`) and goes out once a worker and mailer exist.
- **Reminder emails to pastors, link expiry, PDF export, a pastor login.** Not asked. The resend action covers a lost or wrong email.
- **Changes to the `/calendar` month grid and the homepage events feed.** They are already date-driven.
- **A Filament database-notification bell.** The table status column and the college email cover it.

---

## Step-by-Step Tasks

### Task 0: Back up, and get the owner's OK for the live migration
- Before Task 13 (the migration), ask the owner whether to back up and add the `abc_pastor_references` table to the live DB.
- Back up:
  - `git bundle`
  - a site tarball (excluding node_modules and vendor)
  - `sqlite3 upci ".backup …"` plus `PRAGMA integrity_check`
  - the uncommitted diff
  - a git tag

  All of it goes to `/var/www/personal/upci.co.nz-backups/<date>-pastor-reference/`.
- Then `php artisan migrate --force`, run as www-data, so the DB file stays www-data owned.
- **VALIDATE**:
  - `sqlite3 upci ".schema abc_pastor_references"`
  - `/usr/bin/find storage bootstrap/cache -user root | wc -l` prints 0

### Task 1: UPDATE `app/Models/Event.php`, adding `scopeUpcomingThisYear`
```php
/**
 * What the calendar lists show (owner, 2026-10-17: "the latest first up until
 * December"): events that are not over yet, up to 31 December of this year.
 * An event that began earlier but is still running stays until its last day.
 * "Today" is New Zealand's date; the app runs in UTC, which is still
 * yesterday until midday or 1pm here. whereDate, because start_date and
 * end_date are stored as 'Y-m-d H:i:s' and a plain string comparison would
 * drop an event on the last day.
 */
public function scopeUpcomingThisYear($query)
{
    $today = now(config('app.display_timezone'));
    return $query
        ->where(fn ($q) => $q->whereDate('end_date', '>=', $today->toDateString())
            ->orWhere(fn ($q) => $q->whereNull('end_date')->whereDate('start_date', '>=', $today->toDateString())))
        ->whereDate('start_date', '<=', $today->copy()->endOfYear()->toDateString());
}
```
- **GOTCHA**: pass strings, not Carbon, so the app timezone can't shift the date.
- **VALIDATE**: CalendarWindowTest (Task 7).

### Tasks 2–3: UPDATE `DepartmentController` and `RegionController`
- Add `->upcomingThisYear()` after `->published()`, and keep `->orderBy('start_date')`.
- Add a one-line comment pointing at the scope.

### Task 4: UPDATE `EventController::index`
- `if ($request->boolean('upcoming')) { $query->upcomingThisYear(); }`, with a comment: Calendar of Events; other callers keep the whole list.
- The `to` filter becomes `$query->whereDate('start_date', '<=', $request->to)`, with a comment that an event on the `to` day was excluded by the string comparison.
- **GOTCHA**: `Calendar.vue` passes `to` = the 1st of next month and filters on the client, so including that day is harmless.

### Task 5: UPDATE `resources/js/views/Events.vue`
- `fetch('/api/events?upcoming=1', …)`.
- Update the comment above `fetchEvents`.
- The empty text "No upcoming events at the moment. Check back soon." already fits.

### Task 6: UPDATE `tests/Feature/RegionAccessTest.php`
- In "the region page shows only its own regional events", add `$this->travelTo(Carbon::parse('2026-09-01 12:00', 'Pacific/Auckland'));` at the top. Add a comment that region pages list only upcoming events, so the clock is pinned before the fixture dates.

### Task 7: CREATE `tests/Feature/CalendarWindowTest.php`
- Clock pinned at `Carbon::parse('2026-10-17 09:00', 'Pacific/Auckland')`, which is still 16 Oct in UTC.
- Events:
  - 16 Oct, single day (excluded);
  - 1–22 Oct (included, running);
  - 17 Oct (included);
  - 14 Nov (included);
  - 31 Dec (included);
  - 2 Jan 2027 (excluded);
  - 10 Apr (excluded);
  - an unpublished 20 Oct event (excluded).
- Assert:
  - the department payload order is `[1–22 Oct, 17 Oct, 14 Nov, 31 Dec]`;
  - a region payload with regional events applies the same window;
  - `/api/events?upcoming=1` gives the same set;
  - `/api/events` without the flag still lists all published events;
  - `/api/events?to=2026-12-31` includes the 31 Dec event;
  - `Events.vue` source contains `/api/events?upcoming=1`.
- **VALIDATE**: `sudo -u www-data php artisan test --compact tests/Feature/CalendarWindowTest.php tests/Feature/RegionAccessTest.php tests/Feature/EventScopeTest.php tests/Feature/ApiVisibilityTest.php`

### Task 8: CREATE `app/Filament/Resources/Shared/UploadPhotosAction.php`
- `final class UploadPhotosAction { public static function make(Closure $owner): Action }`.
- Move the action body from the relation manager unchanged:
  - schema, labels, modal copy;
  - `fileNameSortKey` (made private static here).
- `$owner()` returns a Model with `galleryItems()` (Department/Region), or null for the general gallery.
- `$next = (int) ($owner ? $owner->galleryItems() : GalleryItem::query()->general())->max('sort_order')`.
- Create through `$owner->galleryItems()->create($attrs)` or `GalleryItem::create($attrs)`.
- Keep every comment. The class docblock says where it's used.
- **NO authorize inside**: callers authorise, since the rule differs per mount.

### Task 9: UPDATE `GalleryItemsRelationManager`
- `uploadPhotosAction()` returns `UploadPhotosAction::make(fn () => $this->getOwnerRecord())->authorize(fn (): bool => ! $this->isReadOnly() && $this->canCreate())`.
- Keep the method and its authorisation comment. Remove the moved helper (its only caller moved).
- **VALIDATE**: `GalleryBulkUploadTest` passes unchanged.

### Task 10: UPDATE `ListGalleryItems`
- Header actions:
  ```php
  UploadPhotosAction::make(fn () => null)
      ->modalHeading('Upload photos to the general gallery')
      ->authorize(fn (): bool => GalleryItemResource::canCreate())
  ```
  then `CreateAction::make()`.
- Comment: department and region photos are uploaded from their own pages; this one is for the general gallery.

### Task 11: UPDATE `GalleryItemForm`
- `Section::make('Gallery item')->description('Leave "Belongs to" blank to put the photo in the general gallery (the Gallery page\'s "General" tab).')`.
- **GOTCHA**: `MorphToSelect` has no `helperText()` in v4.

### Task 12: CREATE `tests/Feature/GeneralGalleryUploadTest.php`
- A national admin calls `uploadPhotos` on `ListGalleryItems` with 3 photos and a caption. Expect:
  - 3 items with null `galleryable_type`;
  - appended after existing general items (`sort_order`);
  - file-name order;
  - `assertNotified('3 photos added')`;
  - they appear in `/api/gallery` (general) and not in a department's gallery.
- A regional admin: `assertActionHidden('uploadPhotos')`, or a 403 on the page (the resource list is visible to them, limited to their region).
- An unpublished toggle is respected.
- The form source or render contains the "general gallery" description.
- **VALIDATE**: `sudo -u www-data php artisan test --compact tests/Feature/GeneralGalleryUploadTest.php tests/Feature/GalleryBulkUploadTest.php tests/Feature/AdminTablesTest.php tests/Feature/PhotoUploadRulesTest.php`

### Task 13: CREATE the migration `2026_10_17_000001_create_abc_pastor_references_table.php`
Columns:
- `id`
- `foreignId('abc_enrolment_id')->unique()->constrained()->cascadeOnDelete()`
- `string('pastor_name')`, `string('pastor_email')`
- `string('pastor_email_source', 20)`: church_records | applicant | admin
- `string('token_hash', 64)->unique()`
- `timestamp('emailed_at')->nullable()`
- answers, all nullable:
  - `boolean('faithful_attendance')`, `boolean('faithful_holiness')`, `boolean('faithful_tithes')`
  - `boolean('is_serving')`, `text('serving_details')`
  - `boolean('approves')`, `boolean('approves_level')`
  - `text('comments')`
  - `string('signed_name')`
- `timestamp('submitted_at')->nullable()`
- `timestamps()`

Docblock (owner request, Brandon Borders' draft, why a hashed token and why one per enrolment). `down()` drops the table.

### Task 14: CREATE `app/Models/AbcPastorReference.php`
- Constants:
  - `ANSWER_FIELDS` (the yes/no answers)
  - `SOURCES = ['church_records' => 'From the church's records in the CMS', 'applicant' => 'Given by the applicant', 'admin' => 'Entered by an administrator']`
  - `LEVEL = 'Certificate Level (Year 1)'`
- `$fillable`, casts (booleans, `emailed_at`/`submitted_at` datetime).
- `enrolment(): BelongsTo` (`AbcEnrolment`, `abc_enrolment_id`).
- `public function issueToken(): string`: `$token = bin2hex(random_bytes(32)); $this->token_hash = hash('sha256', $token); return $token;` (caller saves).
- `public static function findByToken(string $token): ?self`: rejects anything that isn't 64 hex chars first, then `where('token_hash', hash('sha256', $token))->first()`.
- `isSubmitted(): bool`.
- `getStatusLabelAttribute()`: Waiting for the pastor / Approved / Not approved.

### Task 15: UPDATE `app/Models/AbcEnrolment.php`
- `pastorReference(): HasOne`.
- `public static function pastorOnFile(string $church): ?array`, returning `['name' => string, 'email' => ?string]`:
  - only for strings in `CHURCHES`;
  - strip " (Town)", normalise (lowercase, drop a leading "the ", collapse spaces);
  - the first `Church::active()` whose normalised name starts with it;
  - the pastor is `churches.pastor_name` (its email is a user at that church with the same normalised name), else the first `pastors()` user ordered senior_pastor, pastor, assistant_pastor;
  - null when there is no pastor.
- `public static function pastorDirectory(): array`, keyed by `CHURCHES` string: `['pastor_name' => …, 'pastor_on_file' => bool]`, only churches with a pastor. **Never** emails.
- `public static function samePastor(string $a, string $b): bool`: normalise by dropping a leading "pastor", "ps", "rev" or "reverend", punctuation and case.
- **GOTCHA**: `churches` names have trailing spaces (ids 11 and 12), so trim.

### Task 16: CREATE `app/Notifications/AbcPastorReferenceRequest.php`
- `ShouldQueue`, scalars only: `token`, `applicantName`.
- `toMail`:
  ```php
  ->subject("Pastor's reference for {$applicantName}: Apostolic Bible College")
  ->greeting('Dear Pastor,')
  ->line("An applicant for Apostolic Bible College, {$applicantName}, has listed you as their pastor.")
  ->line('Please take the time to fill out this short questionnaire and let us know if you approve for the applicant to be a student at ABC.')
  ->action('Fill out the questionnaire', url("/apostolic-bible-college/pastor-reference/{$token}"))
  ->line("It takes about two minutes. The link is just for you, so please don't forward it.")
  ->salutation("God bless,\nApostolic Bible College, UPCI New Zealand")
  ```
- Docblock: the token rides in the queued job; the DB only stores its hash.

### Task 17: CREATE `app/Notifications/AbcPastorReferenceReceived.php`
- `ShouldQueue`, scalars: `enrolmentId`, `applicantName`, `pastorName`, `approves` (bool).
- Subject: "Pastor's reference received: {applicant}".
- Lines: "{pastor} has returned the pastor's reference for {applicant}." and "Approves of them becoming a student: Yes/No."
- Action: "View in admin" → `/admin/abc-enrolments/{id}`.
- No free-text answers in the email, mirroring `NewAbcEnrolment`'s PII rule.

### Task 18: UPDATE `AbcEnrolmentController` and CREATE `AbcPastorReferenceController`; UPDATE routes
- `AbcEnrolmentController::pastors()` returns `{success, data: AbcEnrolment::pastorDirectory()}`.
- `store()` changes:
  - Rule: `'pastor_email' => [Rule::requiredIf(fn () => ! self::pastorEmailOnFile($request)), 'nullable', 'string', 'email', 'max:255']`.
  - Messages:
    - `'pastor_email.required' => "Enter your pastor's email address, so the college can ask them for a reference"`
    - `'pastor_email.email' => "Enter your pastor's email address in the correct format, like name@example.com"`
  - Exclude `pastor_email` from the enrolment data.
  - In `DB::transaction`: create the enrolment, then the reference. The email and source are records when on file and the name matches; otherwise the applicant's. Set `emailed_at = now()`.
  - After the transaction: `rescue(…AbcPastorReferenceRequest…, report: true)`, then the existing college notification.
- `AbcPastorReferenceController`:
  - `show(string $token)`: 404 JSON `{success:false,message:'This link is not valid…'}`. Otherwise `{applicant_name, church, pastor_name, level, submitted, submitted_at}`.
  - `store(Request, string $token)`: 404 when unknown, 409 when already submitted.
    - Validation:
      - yes/no `required|in:yes,no` for `faithful_attendance`, `faithful_holiness`, `faithful_tithes`, `is_serving`, `approves`;
      - `serving_details`: `exclude_unless:is_serving,yes|required|string|max:2000`;
      - `approves_level`: `exclude_unless:approves,yes|required|in:yes,no`;
      - `comments`: `nullable|string|max:5000`;
      - `signed_name`: `required|string|max:255`.
    - Messages in GOV.UK style.
    - Atomic: `AbcPastorReference::whereKey($id)->whereNull('submitted_at')->update([...answers, 'submitted_at' => now()])`. If 0 rows, return 409.
    - Then `rescue` the college notification when configured, and return 200 `{success:true}`.
- Routes (inside the api group, next to the enrolment POST):
  ```php
  Route::get('/abc-enrolments/pastors', [AbcEnrolmentController::class, 'pastors']);
  Route::get('/abc-pastor-references/{token}', [AbcPastorReferenceController::class, 'show'])->middleware('throttle:30,1,abc-pastor-reference');
  Route::post('/abc-pastor-references/{token}', [AbcPastorReferenceController::class, 'store'])->middleware('throttle:10,1,abc-pastor-reference-submit');
  ```

### Task 19: CREATE `resources/js/views/abc-form-document.css` and UPDATE `AbcNewStudentEnrolment.vue`
- Move the D3 shared rules verbatim into the CSS file and reference it with `<style scoped src="./abc-form-document.css"></style>`:
  - tokens, masthead, intro, opener, questions, inputs, options, follow-ups, errors;
  - bar, signature, privacy, general error, submit, btn, colophon, receipt;
  - their phone rules.
- Keep the enrolment-only rules (progression ladder, running head, error-summary wrap, honeypot, fees, clauses) in the view's own scoped block.
- Add a `select` rule matching the inputs (52px, a grey-600 border, a chevron as an inline SVG background, and `padding-right: 44px`), plus focus and error states.
- Template 4.5:
  ```html
  <label class="l" for="church_name">…<Req /></label>
  <select id="church_name" v-model="form.church_name" class="w-l" :aria-invalid="invalid('church_name')" :aria-describedby="describedBy('church_name')">
    <option value="" disabled>Select your church</option>
    <option v-for … :value="church.value">{{ church.name }} ({{ church.town }})</option>
    <option value="other">Other (not listed)</option>
  </select>
  ```
  The 4.5a follow-up stays as it is.
- 4.6:
  - a hint `<p id="pastor_name-hint" class="help">` when auto-filled: "Filled in from your church's details. Change it if someone else is your pastor.";
  - a `<p class="vh" role="status" aria-live="polite">{{ pastorStatus }}</p>` present from mount.
- 4.6a `pastor_email` (`type=email`, `autocomplete=off`) is shown when `needsPastorEmail`:
  - the church is chosen; and
  - it is Other, or there is no `pastor_on_file`, or `!samePastor(form.pastor_name, suggested.pastor_name)`; or
  - there is a server error on `pastor_email`.

  The hint says "We'll email your pastor a short reference questionnaire."
- Script:
  - `pastors = ref({})`, loaded on mount from `/api/abc-enrolments/pastors`. Failure is silent: the server then requires an email when needed.
  - `watch(() => form.church_name, …)`: fill `pastor_name` only if it is empty or still equals the last auto-fill, then set `pastorStatus`.
  - `requiredFields` section 4 gains `pastor_email` when `needsPastorEmail`.
  - `samePastor` in JS mirrors the PHP normaliser.
- Keep the `CHURCHES` const (the contract test reads it).
- **GOTCHA**: `buildPayload` sends only visible required fields, so `pastor_email` must be in `requiredFields` when shown.

### Task 20: CREATE `resources/js/views/AbcPastorReference.vue` and the route
- The route `/apostolic-bible-college/pastor-reference/:token`, name `AbcPastorReference`, is placed after the new-student route and before `/:slug(.*)`.
- Page (D3, uses `<style scoped src="./abc-form-document.css">`):
  - Masthead: "Apostolic Bible College · UPCI New Zealand", "Form ABC-2 · Pastor's reference", h1 "Pastor's Reference", sub "— Apostolic Bible College".
  - States:
    - loading (`role=status`);
    - not found: "This link isn't valid. It may have been replaced by a newer email." Plus the college email;
    - already submitted: a receipt;
    - form;
    - submitted: a receipt.
  - Intro (Brandon's words): "Dear Pastor, An applicant for Apostolic Bible College, **{name}** ({church}), has listed you as their pastor. Please take the time to fill out this short questionnaire and let us know if you approve for the applicant to be a student at ABC."
  - Section 1 "Faithfulness". Legend "Is this applicant faithful in the following?" with:
    - 1.1 Attendance to services;
    - 1.2 Holiness & modesty;
    - 1.3 Tithes and offerings.

    Each is a `YesNo` in its own fieldset.
  - Section 2 "Serving": 2.1 "Is this applicant currently serving in some area of your church?" (YesNo), then 2.1a "How are they involved?" (textarea, shown when yes).
  - Section 3 "Approval":
    - 3.1 "Do you approve of this applicant becoming a student?" (YesNo);
    - 3.1a "Are you happy for them to be enrolled at their requested level of study, Certificate Level (Year 1)?" (YesNo, when yes; every new student starts there);
    - 3.2 "Anything else you would like the college to know?" (optional textarea).
  - Signature block: `signed_name` ("Your name", prefilled with `pastor_name`) and the date shown (today, display only). Echo line.
  - Button "Send to the college →". Submit posts JSON. The 422, 409, 404 and 429 responses map like the enrolment form's.
  - `setPageMeta("Pastor's Reference - Apostolic Bible College", …)`.
- Reuse the `Req`, `FieldError` and `YesNo` helpers by copying the three small render functions; they are 25 lines and local to the ABC views.

### Task 21: UPDATE the admin (infolist, table, view page)
- **Infolist**: Section "Pastor's reference", after "Spiritual information". Fields:
  - `pastorReference.status_label` badge, with the placeholder "Not requested (enrolled before pastor references began)";
  - pastor name, email (copyable), source label;
  - emailed at and received at (`dateTime`);
  - then, visible when submitted: the yes/no badges, `serving_details` and `comments` via `TextLines`, `signed_name`.
- **Table**: `TextColumn::make('pastorReference.status_label')->label("Pastor's reference")->badge()->placeholder('Not requested')`. Add `->modifyQueryUsing(fn ($q) => $q->with('pastorReference'))`.
- **ViewAbcEnrolment**: `Action::make('emailPastor')`:
  - label "Email the pastor again" when a reference exists, else "Ask the pastor for a reference";
  - hidden when submitted;
  - `->authorize(fn () => (bool) auth()->user()?->isNational())`;
  - schema: `pastor_name` (required) and `pastor_email` (required, email), defaulted from the reference;
  - action: `updateOrCreate`, a new token, source `admin` if the email changed (else keep), `emailed_at = now()`; notify (rescue); Filament notification "Questionnaire emailed to …".

### Task 22: Tests: CREATE `AbcPastorReferenceTest` and `AbcPastorReferenceAdminTest`; UPDATE `AbcEnrolmentApiTest` and `AbcEnrolmentNewStudentPageTest`
- **AbcEnrolmentApiTest**: `abcEnrolmentPayload()` gains `'pastor_email' => 'pastor@example.test'`. "no notification is sent while no recipient is configured" now asserts `Notification::assertSentOnDemandTimes(NewAbcEnrolment::class, 0)`, with a comment: the pastor's questionnaire still goes out; the college copy needs a recipient.
- **AbcPastorReferenceTest**:
  - The directory maps listed churches to pastors (fixtures: Grace Fellowship row with a senior_pastor user; Church Triumphant with `pastor_name` and a matching user; Walk by Faith row with no pastor) and never contains "@".
  - An enrolment with an on-file pastor needs no `pastor_email`: a reference with source `church_records`, and the request is sent on demand to the user's email.
  - A changed pastor name requires `pastor_email` (422 with the exact message).
  - Other church requires `pastor_email`. The applicant's email is used with source `applicant`.
  - An applicant-sent `pastor_email` doesn't override records when the names match.
  - The request mail has a link `/apostolic-bible-college/pastor-reference/{64 hex}`, its hash matches the row, and the raw token is not in the DB.
  - GET by token returns the context. Unknown token: 404. A malformed token: 404 without a query.
  - POST:
    - valid answers store booleans and `submitted_at`;
    - the college gets `AbcPastorReferenceReceived` when configured;
    - a second POST returns 409 and the answers are unchanged;
    - the conditional follow-ups are required or dropped;
    - the messages are plain words.
  - The submit endpoint is rate limited. A deleted enrolment cascades its reference.
- **AbcPastorReferenceAdminTest**:
  - The view shows the section in both the waiting and received states, the answers escaped.
  - The table shows the status column.
  - `emailPastor` rotates the token, sends to the new address and sets source `admin`; it is hidden after submit.
  - A regional user can't reach it (403/404 on the page).
- **AbcEnrolmentNewStudentPageTest** (additions):
  - the source has `<select id="church_name"`, `/api/abc-enrolments/pastors` and `pastor_email`;
  - routes.js has the pastor-reference route before `/:slug(.*)`;
  - `GET /apostolic-bible-college/pastor-reference/abc` serves the SPA (200).
- **VALIDATE**: `sudo -u www-data php artisan test --compact --filter='Abc'`

---

## Testing Strategy

### Edge Cases Checklist
- [ ] Event running across today (1–22 Oct) stays; one that ended yesterday NZ time goes, even when UTC says it's still yesterday
- [ ] 31 Dec event included; 1 Jan next year excluded
- [ ] General upload with existing general items appends after max `sort_order`; department items don't affect it
- [ ] Church with trailing space in its name (`Walk by Faith Tongan Ministry `) still matches
- [ ] Pastor name typed as "Pastor Chester Kebasen" counts as the same pastor
- [ ] Church chosen, then changed: the auto-fill follows; a name the applicant typed is never overwritten
- [ ] API down for `/pastors`: the form still submits; the server asks for the email via 422 and the field appears
- [ ] Token: unknown, malformed, already used, replaced by resend (old link 404)
- [ ] Double submit race: the second request returns 409
- [ ] Mail or queue failure never fails the enrolment or the reference submit (`rescue`)
- [ ] No pastor email ever appears in a public response

## Validation Commands

**Level 1, static analysis.** `php -l` on every changed PHP file. `./vendor/bin/pint --test <changed files>`, if the repo uses Pint (check `composer.json`). `npx vite build` compiles the SFCs.

**Level 2, unit and feature.** `sudo -u www-data php artisan test --compact tests/Feature/CalendarWindowTest.php tests/Feature/GeneralGalleryUploadTest.php --filter=…`, then `--filter=Abc`.

**Level 3, full suite and build.** `sudo -u www-data php artisan test --compact`, then `npm run build`. Every test passes except those already failing before this work: record the pre-existing baseline first.

**Level 4, database.**
- `sqlite3 upci ".schema abc_pastor_references"`
- `sqlite3 upci "pragma integrity_check"`
- `/usr/bin/find storage bootstrap/cache -user root | wc -l` prints 0

**Level 5, browser.** Use Playwright 1.64 (scratchpad), against a test server on a DB copy (`MAIL_MAILER=log`).
- **Calendars.** `/departments/mens` shows only 14 Nov. `/events` starts at OCTOBER 2026.
- **Enrolment.**
  - The church select appears, and choosing Grace Fellowship fills "Wayne Goodare".
  - Choosing Other shows 4.5a and 4.6a.
  - At 390px there's no horizontal scroll.
  - Submitting creates the reference. Run `queue:work --once` on the copy, and the log shows the pastor email.
- **Questionnaire.** Open the logged link, fill it in, submit, and the receipt shows. Reload and it says already received. At 390 and 1440, screenshot it beside the enrolment form to confirm the design matches.
- **Admin.** The Gallery list shows Upload photos. The enrolment view shows the reference section.

**Level 6, manual (owner).**
- Upload a batch to the general gallery.
- Submit a test enrolment.
- Find the queued pastor email in the log once a worker runs, or in the `jobs` table.

## Acceptance Criteria
- [ ] The department, region and `/events` lists show events from today (NZ) to 31 Dec, soonest first
- [ ] A national admin can bulk-upload photos to the general gallery from the Gallery list; a regional admin can't
- [ ] The church is a dropdown; picking a listed church with a known pastor fills the pastor's name
- [ ] Every new enrolment creates a pastor reference and queues the pastor email; the pastor's email address is never public
- [ ] The pastor can answer once via the link; the answers show in the admin; the college gets an email when configured
- [ ] Full suite green (or the pre-existing baseline); the build succeeds; nothing deleted

## Risks and Mitigations
| Risk | Likelihood | Impact | Mitigation |
|---|---|---|---|
| The live site serves the working tree, so PHP edits are live before the migration | MED | HIGH | Run the migration (Task 0, owner OK) **before** editing the enrolment controller |
| An applicant enters their own email as the "pastor" | MED | MED | The source is stored and shown in the admin ("Given by the applicant"); records win whenever the name matches |
| Church name matching drifts if a church is renamed in the CMS | LOW | LOW | Prefix match after normalising; a missed match just means no auto-fill and the applicant is asked for the email |
| Moving the CSS changes the enrolment form's look | LOW | MED | Rules move verbatim; screenshot before and after at 1440 and 390 and compare |
| No queue worker, so mail sits in `jobs` | HIGH | LOW (owner accepted) | Reported. `queue:work` plus a mailer later sends the backlog |

## Notes
- **Design.** The questionnaire page extends the approved D3 direction (`.claude/design/abc-enrolment/direction-approved.md`) as "Form ABC-2", rather than introducing a new visual design. This is iteration within an approved direction, so there is no new three-direction gate. The select is a field-type change the owner asked for by name.
- **Brandon's draft.** Used nearly verbatim. Two changes: the typo "some are of your church" becomes "some area", and "their requested level of study" names Certificate Level (Year 1), since every new student starts there.
- **Token.** A random token, stored as sha256. This was chosen over a signed URL because the link opens an SPA route, survives APP_KEY rotation and supports single use; see the research notes.
- **APP_URL.** It is `https://upci.b8.co.nz` (verified 2026-10-17), so queued mail links resolve correctly.
