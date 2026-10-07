# Feature: ABC new-student enrolment form (native replacement for the Google Form)

## Summary

The ABC registration page (`/apostolic-bible-college/enrollment`, CMS page id 12) sends first-time
students to a Google Form, "Apostolic Bible School Enrolment Form for New Students"
(`https://forms.gle/iqVTh9YQH3nhZdw48` → `docs.google.com/forms/d/e/1FAIpQLSeGzF99SflRX4uJRjJsyU727hPZIqGb3plrenUm3PDMbCUwMQ`).
This plan replaces that form with a native one:

- a Vue view at `/apostolic-bible-college/enrollment/new-student` with all 26 Google Form fields;
- an unauthenticated, throttled, honeypot-guarded `POST /api/abc-enrolments` (multipart, because of the optional transcript upload);
- an `abc_enrolments` table, with transcripts stored on the **private** `local` disk;
- a read-only, national-only Filament inbox "ABC Enrolments", with a transcript download action;
- a queued email notification, sent only once a recipient is configured (a placeholder for now).

The "First time student" card on page 12 is then repointed at the new route, using a data migration plus a seeder change.
Everything follows the existing `/api/contact` → `ContactMessage` → `ContactMessageResource` pattern.

## User Story

As a **prospective first-time ABC student**
I want to **complete my enrolment on the UPCI NZ site itself**
So that **I don't get bounced to a Google Form, and the college receives my application in its own admin, where it is kept private**

## Problem Statement

1. Clicking "First Time Student Register HERE" on `/apostolic-bible-college/enrollment` leaves the site for Google Forms (`CmsPage.vue:202` opens `http*` links with `target="_blank"`).
2. Submissions go to a Google account rather than the site's admin, where they can't be access-controlled with the site's national/regional/local model.
3. The Google Form asks students to "attach the transcript of your previous study" but has no upload field.

Testable: once this is done, the card's `link_url` is the internal path. `POST /api/abc-enrolments` with a valid
payload returns 201 and creates one row. A national admin sees the row and can download the transcript. Local
and regional admins get 403.

## Solution Statement

Mirror the contact-form stack one-for-one, then add three things it doesn't have:

| Layer | Contact form (mirror) | Enrolment (new) |
|---|---|---|
| Vue | `resources/js/views/ConnectWithUs.vue` | `resources/js/views/AbcNewStudentEnrolment.vue` (multi-section form, `FormData`) |
| Route | `routes.js` `/connect-with-us` | `routes.js` `/apostolic-bible-college/enrollment/new-student`, placed **before** `/cms/:slug(.*)` |
| API | `routes/web.php` `POST /api/contact` + `throttle:5,1` | `POST /api/abc-enrolments` + `throttle:5,1` |
| Controller | `Api/ContactController@store` | `Api/AbcEnrolmentController@store` (+ honeypot, file store, notify) |
| Model/table | `ContactMessage` / `contact_messages` | `AbcEnrolment` / `abc_enrolments` |
| Policy | `ContactMessagePolicy extends NationalOnlyPolicy` (create/update false) | `AbcEnrolmentPolicy`, identical shape |
| Admin | `Filament/Resources/ContactMessages/*` | `Filament/Resources/AbcEnrolments/*` (+ "Download transcript" action) |
| **New** | — | `App\Notifications\NewAbcEnrolment` (queued, on-demand), sent only when `config('abc.enrolment_notify_to')` is set |
| **New** | — | Data migration that repoints the card, plus the seeder change |

## Metadata

| Field | Value |
|---|---|
| Type | NEW_CAPABILITY |
| Complexity | MEDIUM. It's a well-trodden pattern, but the form is long (26 fields, 3 conditionals, a file upload) and it's the first queued notification in the app |
| Systems Affected | Vue SPA (router, new view), public API (`routes/web.php`), DB (new table), Filament admin (new resource), notifications/queue, CMS content (page 12) |
| Dependencies | laravel/framework 12.23.1, filament/filament 4.0.0, livewire/livewire 3.6.4, pestphp/pest 3.8.2 (+ plugin-laravel 3.2.0), vue 3.5.22, vue-router 4.5.1, tailwindcss 3.4. **No new packages** |
| Estimated Tasks | 15 |

## Owner decisions (recorded 2026-10-02)

| Question | Decision |
|---|---|
| Church dropdown source | **The Google Form's 13 names + Other** (hard-coded list, not the `churches` table). Fix the source typo `Storehouse Chapel (Hamilton` → `Storehouse Chapel (Hamilton)` |
| Email on submit | **Placeholder for now.** Build the notification, read the recipient from `ABC_ENROLMENT_NOTIFY_EMAIL`, leave it empty, and send nothing while it's empty |
| Who sees enrolments | **National users only** (`NationalOnlyPolicy`) |
| Transcript | **Optional file upload**, shown only when "attended UPCI Bible school before" = Yes. Stored privately and downloadable by national admins |
| Owner rules (memory) | **Delete nothing** (this feature only adds, plus one link change). **Back up first** (Task 0). **UI work goes through the huashu-design three-direction gate** (Task 9) |

---

## UX Design

### Before State
```
╔═══════════════════════════════════════════════════════════════════════════════╗
║                              BEFORE STATE                                     ║
╠═══════════════════════════════════════════════════════════════════════════════╣
║  /apostolic-bible-college  ──"Register"──►  /apostolic-bible-college/enrollment║
║                                              (CmsPage.vue, page id 12)        ║
║                                              ┌──────────────────────────────┐ ║
║                                              │ cards (style=registration)   │ ║
║                                              │ [First time student]  ───────┼─╫──► NEW TAB
║                                              │ [Returning students]  ───────┼─╫──► forms.gle/9nED…
║                                              │ [Foundation level *NEW] ─────┼─╫──► forms.gle/DoBQ…
║                                              │ [Work exemption]  ───────────┼─╫──► forms.gle/iqVT… (!)
║                                              └──────────────────────────────┘ ║
║                                                                               ║
║   forms.gle/iqVTh9YQH3nhZdw48 ──► Google Form (5 pages, 26 fields)            ║
║                                   └──► responses in a Google account          ║
║                                                                               ║
║   USER_FLOW: click card → leave site → Google Form → submit → Google thanks   ║
║   PAIN_POINT: off-site, unbranded, no transcript upload, data outside admin   ║
║   DATA_FLOW: browser → Google Forms → Google Sheet (outside the site)         ║
╚═══════════════════════════════════════════════════════════════════════════════╝
```

### After State
```
╔═══════════════════════════════════════════════════════════════════════════════╗
║                               AFTER STATE                                     ║
╠═══════════════════════════════════════════════════════════════════════════════╣
║  /apostolic-bible-college/enrollment                                          ║
║   [First time student] ──(same tab)──► /apostolic-bible-college/enrollment/   ║
║                                         new-student  (AbcNewStudentEnrolment) ║
║   (other 3 cards unchanged)             ┌──────────────────────────────────┐  ║
║                                         │ Intro + Progression of Study     │  ║
║                                         │ 1 Personal information           │  ║
║                                         │ 2 Previous Bible school (+file)  │  ║
║                                         │ 3 Learning challenges            │  ║
║                                         │ 4 Spiritual information          │  ║
║                                         │ 5 Fees + Agreement               │  ║
║                                         │ [Submit enrolment]               │  ║
║                                         └───────────────┬──────────────────┘  ║
║                                                         │ FormData (multipart)║
║                                                         ▼                     ║
║                     POST /api/abc-enrolments  (throttle 60/min group + 5/min) ║
║                       ├─ honeypot filled? → 201 fake, nothing stored          ║
║                       ├─ 422 {errors} → ErrorSummary + inline errors          ║
║                       └─ 201 → row in abc_enrolments                          ║
║                              ├─ transcript → storage/app/private/abc-…        ║
║                              └─ notify (only if ABC_ENROLMENT_NOTIFY_EMAIL)   ║
║                                                                               ║
║   /admin/abc-enrolments (national only) → list → view → Download transcript   ║
║                                                                               ║
║   USER_FLOW: click card → same-site form → submit → in-page "Thank you" panel ║
║   VALUE_ADD: on-brand, transcript upload, data in the site's own admin       ║
║   DATA_FLOW: browser → API → DB + private disk → Filament inbox (+ email)     ║
╚═══════════════════════════════════════════════════════════════════════════════╝
```

### Interaction Changes
| Location | Before | After | User Impact |
|---|---|---|---|
| `/apostolic-bible-college/enrollment`, "First time student" card | Opens Google Form in a new tab | Opens `/apostolic-bible-college/enrollment/new-student` in the same tab | Stays on site |
| Same page, intro text block | "Each link opens the correct Google Form in a new tab." | "First-time students register here on our site; the other options open the official Google Forms in a new tab." | Accurate copy |
| `/apostolic-bible-college/enrollment/new-student` | Didn't exist (would fall to the CMS catch-all → not found) | Native enrolment form | New page |
| `/admin` navigation, Content group | — | "ABC Enrolments", with a count badge (national users only) | College staff read applications in the admin |

---

## Google Form field inventory (source of truth; extracted from `FB_PUBLIC_LOAD_DATA_`)

Required = `R`. All "Yes/No" fields are radios and are sent as the strings `yes`/`no`.

| # | Section | Label (verbatim) | Type | Req | Column | Validation |
|---|---|---|---|---|---|---|
| 1 | 1 Personal | Full name | text | R | `full_name` string(255) | `required,string,max:255` |
| 2 | 1 | Date of birth | date | R | `date_of_birth` date | `required,date_format:Y-m-d,before:today,after:1900-01-01` |
| 3 | 1 | Gender | radio Male/Female | R | `gender` string(10) | `required,in:male,female` |
| 4 | 1 | Citizenship | text | R | `citizenship` string(255) | `required,string,max:255` |
| 5 | 1 | Address | paragraph | R | `address` text | `required,string,max:1000` |
| 6 | 1 | Home phone | text | R | `home_phone` string(50) | `required,string,max:50` |
| 7 | 1 | Mobile phone | text | R | `mobile_phone` string(50) | `required,string,max:50` |
| 8 | 1 | Email | text | R | `email` string(255) | `required,email,max:255` |
| 9 | 1 | Are you currently residing in New Zealand? | radio | R | `resides_in_nz` bool | `required,in:yes,no` |
| 10 | 1 | If residing in a country other than New Zealand, please specify where | text | conditional | `residence_country` string(255) null | `exclude_unless:resides_in_nz,no` then `required,string,max:255` |
| 11 | 1 | Why do you want to attend the Apostolic Bible College of NZ? | paragraph | R | `reason_for_attending` text | `required,string,max:5000` |
| 12 | 2 Previous | Have you ever attended a UPCI Bible school before? | radio | R | `attended_upci_bible_school` bool | `required,in:yes,no` |
| 13 | 2 | (new) Transcript of previous study | file | optional | `transcript_path`, `transcript_original_name` string null | `exclude_unless:attended_upci_bible_school,yes` then `nullable,file,mimes:pdf,jpg,jpeg,png,max:10240` |
| 14 | 3 Learning | Do you have any learning difficulties? | radio | R | `has_learning_difficulties` bool | `required,in:yes,no` |
| 15 | 3 | If yes, please indicate which from the list below: | radio Hearing/Vision/Writing/Using English in learning/Other | conditional | `learning_difficulty` string(50) null | `exclude_unless:has_learning_difficulties,yes` then `required,in:hearing,vision,writing,english,other` |
| 16 | 3 | (Other text) | text | conditional | `learning_difficulty_other` string(255) null | `exclude_unless:learning_difficulty,other` then `required,string,max:255` |
| 17 | 4 Spiritual | Do you desire to become a licensed minister under UPCI-NZ? | radio | R | `wants_ministerial_licence` bool | `required,in:yes,no` |
| 18 | 4 | Have you read and understood the Articles of Faith? | radio | R | `read_articles_of_faith` bool | `required,in:yes,no` |
| 19 | 4 | Have you been baptised in the name of Jesus Christ? | radio | R | `baptised_in_jesus_name` bool | `required,in:yes,no` |
| 20 | 4 | Have you received the Holy Ghost by the evidence of tongues? | radio | R | `received_holy_ghost` bool | `required,in:yes,no` |
| 21 | 4 | What is the name of the church you currently attend to? | radio list (13) + Other | R | `church_name` string(255) | `required,string,Rule::in([...AbcEnrolment::CHURCHES, 'other'])` |
| 22 | 4 | (Other church text) | text | conditional | `church_name_other` string(255) null | `exclude_unless:church_name,other` then `required,string,max:255` |
| 23 | 4 | Who is your church pastor? | text | R | `pastor_name` string(255) | `required,string,max:255` |
| 24 | 4 | How long have you attended? | text | R | `attendance_duration` string(255) | `required,string,max:255` |
| 25 | 4 | Describe any area of ministry you are currently involved in. | paragraph | R | `ministry_involvement` text | `required,string,max:5000` |
| 26 | 5 Agreement | If you agree, please print your full name | text | R | `agreement_name` string(255) | `required,string,max:255` |
| 27 | 5 | If you agree, please select today's date | date | R | `agreement_date` date | `required,date_format:Y-m-d,after_or_equal:yesterday,before_or_equal:tomorrow` (app timezone is UTC, the users are in NZ, so allow ±1 day) |
| — | — | Honeypot (`website`) | hidden text | — | not stored | if filled, return a fake 201 and store/notify nothing |

**Church list (`AbcEnrolment::CHURCHES`, in this order, from the Google Form):**
`Apostolic Life Church (Tauranga)`, `Apostolics of Christchurch (Christchurch)`, `Church Triumphant Wellington (Wellington)`,
`Daystar Fellowship (Whangarei)`, `El Shaddai- Hindi Speaking Fellowship (Auckland)`, `Grace Fellowship (Hamilton)`,
`New Zealand Family in Christ (Auckland)`, `Pentecostals of Rangiora (Rangiora)`, `Solid Foundation Church (Hastings)`,
`Southside Pentecostal Fellowship (Auckland)`, `Storehouse Chapel (Hamilton)`, `Walk by Faith (Auckland)`, `House of Mercy (Tokoroa)`, then Other.

**Static copy to port verbatim.** Use the text in the Google Form; it's quoted below.
- **Intro:** "Praise the Lord! We warmly welcome you to the Apostolic Bible School (ABC) of UPCI-NZ. We are blessed to see your interest in enriching and equipping yourself for the furtherance of God's kingdom. This form is for students that are enroling to UPCI-NZ Apostolic Bible School for the first time. Kindly fill them completely to avoid any delays in your application. If you have been a student of Apostolic Bible School (ABC) before, kindly use the 'Enrolment Form for Returning Students'." Link "Enrolment Form for Returning Students" to `https://forms.gle/9nEDxXdJgGbtwr5m9` (new tab).
- **Progression of Study:** the image shows Certificate Level (Year 1) → Intermediate Diploma (Year 1) → Intermediate Diploma (Year 2) → Full Diploma (Year 1) → Full Diploma (Year 2). Render it as an accessible HTML ordered list or steps, not an image. Text under it: "All new students who enrol with UPCI-NZ ABC begin their studies at the Certificate Level, and progress to higher levels accordingly after completing the required units. If you are a new student to ABC but have completed a level of study with other UPCI Bible School, kindly indicate it at 'Section 2'."
- **Section 2 help:** "If yes, please attach the transcript of your previous study. If you have queries, don't hesitate to reach out to us at apostolicbiblecollege@upci.co.nz". The Google Form lists two addresses; use the later one, `apostolicbiblecollege@upci.co.nz` (see Open questions).
- **Section 5:** "Students are encouraged to settle their fees as soon as possible. Should you wish to discuss an alternative payment plan, please contact the Bible college administrator. All students will receive an invoice with relevant fee and payment information before course begins." Then the **Schedule of fees** image (see Task 1).
- **Agreement:** "I hereby certify/agree that:" followed by four bullets, verbatim:
  1. the information provided on this application is complete and accurate to the best of my knowledge and the Apostolic Bible College of NZ is authorized to make whatever inquiries are necessary to certify the accuracy of my records;
  2. the registration fee of $50.00 is non-refundable;
  3. the course material provided to me is the property of the Apostolic Bible College of NZ until such time that I pay the total fees for the calendar year, and that if I withdraw from the programme prior to the full payment of fees, that the course material must be returned to the Bible School;
  4. I will adhere to the student requirements as outlined in the Student Handbook.
- **New privacy collection notice** (NZ Privacy Act 2020, IPP 3; needs owner review): "The information you provide is collected by UPCI New Zealand's Apostolic Bible College to process your enrolment. It is seen only by national UPCI-NZ administrators, and you can ask to see or correct it by emailing apostolicbiblecollege@upci.co.nz."

---

## Mandatory Reading

| Priority | File | Lines | Why |
|---|---|---|---|
| P0 | `resources/js/views/ConnectWithUs.vue` | all | The Vue form pattern to MIRROR: page shell, input classes, error handling, success state |
| P0 | `app/Http/Controllers/Api/ContactController.php` | all | Controller and response shape to mirror |
| P0 | `app/Filament/Resources/ContactMessages/**` | all | Read-only inbox to mirror (Resource, Pages, Tables, Schemas) |
| P0 | `app/Policies/ContactMessagePolicy.php`, `app/Policies/NationalOnlyPolicy.php` | all | Policy to mirror |
| P1 | `resources/js/components/layout/ErrorSummary.vue` | all | Expects `{field: [msg]}`; links to `#field`, **so input ids must equal field names** |
| P1 | `resources/js/router/routes.js` | 53-67, 113-128 | Where the route goes: next to the ABC routes, before the `/cms` and catch-all routes |
| P1 | `routes/web.php` | 15-72 | The `/api` group (throttle 60/min); the contact route with `throttle:5,1` at ~70 |
| P1 | `database/migrations/2026_08_17_150000_fix_abc_pages_from_live_site.php` | all | **Precedent for editing page JSON in a migration** (guards against a missing row under RefreshDatabase) |
| P1 | `database/seeders/ApostolicBibleCollegePageSeeder.php` | 143-245 | The `seedEnrollment()` card list to update |
| P1 | `resources/js/views/CmsPage.vue` | 166-210, 466 | Card link rendering: `http` → new tab, otherwise same tab |
| P2 | `tests/Feature/SecurityRegressionTest.php` | 37-46, 243-290 | `regressionUser()` helper; policy and throttle test style |
| P2 | `tests/Feature/AttendanceEditScopeTest.php` | all | Filament Livewire test setup (`Filament::setCurrentPanel('admin')`) |
| P2 | `tests/Feature/CmsBlockSchemaTest.php` | all | Don't add CMS block fields; this test checks that every field the Vue reads is declared in PageForm |
| P2 | `tailwind.config.js` | 28-47 | Brand tokens (`brand-green-*`, `brand-ink`, `brand-error`, which is for errors only) |

**External documentation:**
| Source | Section | Why |
|---|---|---|
| [Laravel 12 Validation](https://laravel.com/docs/12.x/validation#rule-exclude-unless) | `exclude_unless`, `required_if`, `in`, `date_format`, `before`, `after_or_equal`, `file`/`mimes`/`max` | Conditional fields; an excluded key is **absent** from the validated data |
| [Laravel 12 Notifications: on-demand](https://laravel.com/docs/12.x/notifications#on-demand-notifications) | `Notification::route('mail', …)` | Recipient with no user model |
| [Laravel 12 Notifications: queueing](https://laravel.com/docs/12.x/notifications#queueing-notifications) | `ShouldQueue`, `afterCommit` | **Nothing is sent without a worker** (`QUEUE_CONNECTION=database`) |
| [Laravel 12 Notifications: testing](https://laravel.com/docs/12.x/notifications#testing) | `Notification::fake()`, `assertSentOnDemand`, `assertNothingSent` | Tests |
| [Laravel 12 File storage](https://laravel.com/docs/12.x/filesystem#file-uploads) | `store()` on a disk, `Storage::download` | Private transcript storage and download |
| [Filament 4.x: Viewing records](https://filamentphp.com/docs/4.x/resources/viewing-records) | infolist on a ViewRecord page | Read-only view |
| [Filament 4.x: Actions overview](https://filamentphp.com/docs/4.x/actions/overview) | `Filament\Actions\Action` | The download header action (v4 namespace) |
| [Filament 4.x: Ternary filter](https://filamentphp.com/docs/4.x/tables/filters/ternary) | `TernaryFilter` | "Has transcript" filter (optional) |
| [NZ Privacy principles](https://www.privacy.org.nz/privacy-principles/) | IPP 1-4, 5, 9 | Collection notice; security; retention |
| [GOV.UK Error summary](https://design-system.service.gov.uk/components/error-summary/) | Focus and linking | The existing ErrorSummary already follows it; for radio groups, link to the first option id |
| [MDN `<input type="date">`](https://developer.mozilla.org/en-US/docs/Web/HTML/Reference/Elements/input/date) | value format | Always `yyyy-mm-dd`, which matches `date_format:Y-m-d` |
| [W3C WAI: Grouping controls](https://www.w3.org/WAI/tutorials/forms/grouping/) | fieldset/legend | Every radio group is a `<fieldset><legend>` |

---

## Patterns to Mirror

**CONTROLLER (validation + response):**
```php
// SOURCE: app/Http/Controllers/Api/ContactController.php:12-26
$validated = $request->validate([
    'first_name' => ['required', 'string', 'max:255'],
    'email' => ['required', 'email', 'max:255'],
    'message' => ['required', 'string', 'max:5000'],
]);
$record = ContactMessage::create($validated);
return response()->json(['success' => true, 'id' => $record->id], 201);
```

**ROUTE (inside the `/api` group, own throttle):**
```php
// SOURCE: routes/web.php (~line 70)
Route::post('/contact', [ContactController::class, 'store'])
    ->middleware('throttle:5,1');
```

**MODEL:**
```php
// SOURCE: app/Models/ContactMessage.php + casts() method style from app/Models/Event.php
protected $fillable = ['first_name', 'last_name', 'email', 'message'];
protected function casts(): array { return ['start_date' => 'date', 'is_published' => 'boolean']; }
```

**MIGRATION:**
```php
// SOURCE: database/migrations/2026_04_20_000002_create_contact_messages_table.php
return new class extends Migration {
    public function up(): void { Schema::create('contact_messages', function (Blueprint $table) { $table->id(); /* … */ $table->timestamps(); }); }
    public function down(): void { Schema::dropIfExists('contact_messages'); }
};
```

**POLICY:**
```php
// SOURCE: app/Policies/ContactMessagePolicy.php
class ContactMessagePolicy extends NationalOnlyPolicy
{
    public function create(User $user): bool { return false; }
    public function update(User $user, Model $record): bool { return false; }
}
```

**FILAMENT RESOURCE / TABLE / INFOLIST / VIEW PAGE:** copy `app/Filament/Resources/ContactMessages/` file for file:
- `ContactMessageResource.php`: `$navigationGroup = 'Content'`, `getNavigationBadge()`, and only the `index` and `view` pages.
- `Tables/ContactMessagesTable.php`: `TextColumn::make('created_at')->label('Received')->dateTime('j M Y, g:ia')->sortable()`, `->defaultSort('created_at','desc')`, `recordActions([ViewAction::make()])`, a `DeleteBulkAction` in the toolbar, and the empty-state heading/description.
- `Schemas/ContactMessageInfolist.php`: `Section::make(...)->schema([Grid::make(3)->schema([...TextEntry...])])->columnSpanFull()`. Its comment says **never `->html()`**, because the content is attacker-controlled.
- `Pages/ViewContactMessage.php`: `getHeaderActions(): [DeleteAction::make()]`.

**VUE FORM:**
```js
// SOURCE: resources/js/views/ConnectWithUs.vue:127-160 (submit), template 1-105
// shell: <div class="py-16 bg-slate-50"><div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
// card:  bg-white rounded-xl shadow-md border border-slate-200 p-6 sm:p-8
// label: block text-sm font-medium text-slate-700 mb-1
// input: 'w-full px-4 py-2 rounded-lg focus:outline-none focus:ring-2 focus:border-transparent',
//        errors.x ? 'border-2 border-brand-error focus:ring-brand-error' : 'border border-slate-300 focus:ring-brand-green-700'
// error: <p v-if="errors.x" class="mt-1 text-sm text-brand-error font-medium">{{ errors.x[0] }}</p>
// submit button: inline-flex … bg-brand-green-700 text-white … hover:bg-brand-green-900 … disabled:opacity-60
// flow: response.ok → submitted=true; 422 → errors = body.errors; else generalError; catch → network error
```
The huashu-design gate (Task 9) may change the visuals. The **behaviour and accessibility contract** above doesn't change.

**PAGE-JSON DATA MIGRATION:**
```php
// SOURCE: database/migrations/2026_08_17_150000_fix_abc_pages_from_live_site.php:47-60
$page = Page::where('slug', self::REGISTER_SLUG)->first();
if (! $page) { return; }                                  // no-op under RefreshDatabase
$blocks = is_string($page->content) ? json_decode($page->content, true) : $page->content;
if (! is_array($blocks)) { return; }
```

**TESTS:**
```php
// SOURCE: tests/Feature/SecurityRegressionTest.php:37-46 (helper) and ~243-262 (policy)
function regressionUser(string $level, array $extra = []): User { return User::create(array_merge([
    'name' => 'T', 'email' => $level.'-regression@x', 'password' => 'x',
    'role' => UserRole::ADMINISTRATOR, 'access_level' => AccessLevel::from($level)], $extra)); }
expect($national->can('viewAny', ContactMessage::class))->toBeTrue();
// Filament: beforeEach(fn () => Filament::setCurrentPanel('admin')); $this->actingAs($u); Livewire::test(Page::class, ['record' => $id])
```
**Helper names must be unique across test files** (Pest loads them globally). Use a prefix such as `abcEnrolment…()`.

---

## Files to Change

| File | Action | Justification |
|---|---|---|
| `resources/images/abc/schedule-of-fees.png` (or owner-supplied text) | CREATE | Fees image from the Google Form (Task 1) |
| `config/abc.php` | CREATE | `enrolment_notify_to` ← `env('ABC_ENROLMENT_NOTIFY_EMAIL')` (placeholder) |
| `.env.example` | UPDATE | Document `ABC_ENROLMENT_NOTIFY_EMAIL=` |
| `database/migrations/2026_10_02_000001_create_abc_enrolments_table.php` | CREATE | Table |
| `app/Models/AbcEnrolment.php` | CREATE | Model, `CHURCHES` and `LEARNING_DIFFICULTIES` constants, casts |
| `app/Policies/AbcEnrolmentPolicy.php` | CREATE | National-only, no create or update |
| `app/Notifications/NewAbcEnrolment.php` | CREATE | Queued mail with minimal PII and a link to the admin |
| `app/Http/Controllers/Api/AbcEnrolmentController.php` | CREATE | Validate, honeypot, store file, persist, notify |
| `routes/web.php` | UPDATE | `POST /api/abc-enrolments` + `throttle:5,1` |
| `app/Filament/Resources/AbcEnrolments/AbcEnrolmentResource.php` | CREATE | Inbox resource |
| `app/Filament/Resources/AbcEnrolments/Pages/ListAbcEnrolments.php` | CREATE | List page |
| `app/Filament/Resources/AbcEnrolments/Pages/ViewAbcEnrolment.php` | CREATE | View page + Download transcript + Delete |
| `app/Filament/Resources/AbcEnrolments/Tables/AbcEnrolmentsTable.php` | CREATE | Columns, filters |
| `app/Filament/Resources/AbcEnrolments/Schemas/AbcEnrolmentInfolist.php` | CREATE | 5 sections matching the form |
| `resources/js/views/AbcNewStudentEnrolment.vue` | CREATE | The form |
| `resources/js/router/routes.js` | UPDATE | Add the route after `Enrollment` |
| `database/migrations/2026_10_02_000002_point_abc_first_time_card_at_native_form.php` | CREATE | Repoint page 12's card and fix the intro sentence |
| `database/seeders/ApostolicBibleCollegePageSeeder.php` | UPDATE | Same link and copy change for fresh installs |
| `tests/Feature/AbcEnrolmentApiTest.php` | CREATE | API tests |
| `tests/Feature/AbcEnrolmentAdminTest.php` | CREATE | Policy, Filament and download tests |
| `tests/Feature/AbcEnrolmentPageLinkTest.php` | CREATE | Data migration and seeder tests |

## NOT Building (Scope Limits)

- **The returning-student, foundation-level and work-exemption forms.** They stay as Google Forms. Each is its own port later, using this one as the template.
- **Repointing the "Work exemption application" card.** Live, it points at the *same* first-time form (`iqVTh9…`). That looks like a content error, but the 2026-08-17 migration states the URLs "were verified against the live site", so leave it for the owner (Open questions).
- **Using the `churches` table for the dropdown.** Owner decision: the Google list plus Other.
- **Status or "mark reviewed" workflow, CSV export, or editing submissions.** It's a read-and-delete inbox, the same as Contact Messages. It can be added later.
- **Captcha or Turnstile.** Throttle plus honeypot matches the current contact form. Revisit if spam appears.
- **A real notification recipient or production mail setup.** The placeholder only (owner decision).
- **Automatic retention or pruning.** Flagged for the owner (Privacy Act IPP 9), not built.
- **Multi-page wizard UX.** One page with five clearly titled sections. huashu-design may propose steps; if it does, they must stay client-side only, with the same single POST.
- **Deleting anything.** Owner rule. The Google links for the other cards stay. Nothing is removed.

---

## Step-by-Step Tasks

### Task 0: Back up before changing anything (owner rule)
- **ACTION**: Take the same backup set as on 2026-09-30, into `/var/www/personal/upci.co.nz-backups/pre-abc-enrolment-<timestamp>/`: a git bundle (`--all`), a tarball of the tree without vendor, node_modules or .git, `sqlite3 upci ".backup '<dir>/upci.sqlite'"`, and `git diff > uncommitted.patch`. Then `git tag pre-abc-enrolment-<timestamp>`.
- **GOTCHA**: Branch `feature/site-audit-fixes` currently has the **uncommitted** audit fixes. Ask the user whether to commit them first, or to build on top of them on a new branch `feature/abc-enrolment-form`. Don't mix the two in one commit.
- **VALIDATE**: `git bundle verify <dir>/repo.bundle` and `sqlite3 <dir>/upci.sqlite "PRAGMA integrity_check"` → `ok`.

### Task 1: Obtain the "Schedule of fees" image
- **ACTION**: The image is on page 5 of the Google Form, so it isn't in the first page's HTML. (The "Progression of Study" image was extracted and is rendered as HTML; see the inventory.) Use the Chrome tools to open the form, step through pages 1-4 with obviously fake data **without pressing Submit**, and capture the fee image (`img` src under the "Schedule of fees" item → download). Alternatively, ask the owner for the current fee schedule.
- **IMPLEMENT**: If it's a simple table, transcribe it to HTML in the Vue view (accessible and editable). Otherwise save it as `resources/images/abc/schedule-of-fees.png` with descriptive `alt` text.
- **GOTCHA**: Never press the Google Form's Submit. If you can't capture the image, ship with the paragraph text only and list it as an owner follow-up.
- **VALIDATE**: The file exists, or the transcribed table has been reviewed against the image.

### Task 2: CREATE `config/abc.php` + UPDATE `.env.example`
- **IMPLEMENT**:
  ```php
  <?php
  return [
      // Where new ABC enrolment notifications go. Empty = no email is sent;
      // submissions still land in the admin inbox. Placeholder until the
      // college confirms the address.
      'enrolment_notify_to' => env('ABC_ENROLMENT_NOTIFY_EMAIL'),
  ];
  ```
  In `.env.example`, add `ABC_ENROLMENT_NOTIFY_EMAIL=` with a one-line comment.
- **VALIDATE**: `php artisan tinker --execute="var_dump(config('abc.enrolment_notify_to'));"` prints `NULL`.

### Task 3: CREATE migration `create_abc_enrolments_table`
- **IMPLEMENT**: Mirror the contact migration. Columns from the inventory:
  - `full_name`, `date_of_birth` (date), `gender` (string 10), `citizenship`, `address` (text), `home_phone` (50), `mobile_phone` (50), `email`
  - `resides_in_nz` (boolean), `residence_country` (nullable), `reason_for_attending` (text)
  - `attended_upci_bible_school` (boolean), `transcript_path` (nullable), `transcript_original_name` (nullable)
  - `has_learning_difficulties` (boolean), `learning_difficulty` (nullable 50), `learning_difficulty_other` (nullable)
  - `wants_ministerial_licence`, `read_articles_of_faith`, `baptised_in_jesus_name`, `received_holy_ghost` (all boolean)
  - `church_name`, `church_name_other` (nullable), `pastor_name`, `attendance_duration`, `ministry_involvement` (text)
  - `agreement_name`, `agreement_date` (date), `timestamps()`
- **GOTCHA**: Don't run `migrate` against the live `./upci` until Task 14. Tests use `:memory:`.
- **VALIDATE**: `php -l` on the file; `php artisan migrate:status` shows it Pending.

### Task 4: CREATE `app/Models/AbcEnrolment.php`
- **IMPLEMENT**: `$fillable` holds every column above. `casts()` sets `date_of_birth` and `agreement_date` → `'date'`, and the 7 yes/no columns → `'boolean'`. Add:
  - `public const CHURCHES = [ …13 names verbatim, with the Storehouse typo fixed… ];`
  - `public const LEARNING_DIFFICULTIES = ['hearing' => 'Hearing', 'vision' => 'Vision', 'writing' => 'Writing', 'english' => 'Using English in learning', 'other' => 'Other'];`
  - Accessors for the admin:
    - `getChurchDisplayAttribute()` returns `church_name_other` when `church_name === 'other'`.
    - `getLearningDifficultyDisplayAttribute()` returns the matching label, or the Other text.
- **MIRROR**: `ContactMessage.php` (fillable) and `Event.php` (casts method).
- **VALIDATE**: `php -l app/Models/AbcEnrolment.php`

### Task 5: CREATE `app/Policies/AbcEnrolmentPolicy.php`
- **IMPLEMENT**: An exact copy of `ContactMessagePolicy` (extends `NationalOnlyPolicy`; `create` and `update` return false), with the docblock adjusted. Auto-discovered by name, so no registration is needed.
- **VALIDATE**: `php -l`; covered by the Task 13 tests.

### Task 6: CREATE `app/Notifications/NewAbcEnrolment.php`
- **IMPLEMENT**: `class NewAbcEnrolment extends Notification implements ShouldQueue { use Queueable; }`. The constructor takes `public int $enrolmentId, public string $fullName`. Pass scalars, not the model, so a deleted record doesn't break the job and so no PII is serialised into `jobs`.
  - `via()` returns `['mail']`.
  - `toMail()` builds a `MailMessage`:
    - subject "New ABC enrolment: {name}";
    - one line saying a new first-time student enrolment was submitted;
    - `->action('View in admin', url("/admin/abc-enrolments/{$this->enrolmentId}"))`.
  - **No DOB, address or phone in the email.**
  - It's the first notification in the app, so create the `app/Notifications` directory.
- **GOTCHA**: The queue is `database`, so with no worker running, nothing is sent. Locally `MAIL_MAILER=log`. In production it needs a running `queue:work` and real `MAIL_*` settings, which matches the existing password-reset requirement in the project memory.
- **VALIDATE**: `php -l`; covered by Task 12.

### Task 7: CREATE `app/Http/Controllers/Api/AbcEnrolmentController.php`
- **IMPLEMENT**:
  ```php
  public function store(Request $request): JsonResponse
  {
      // Honeypot: a real visitor never sees or fills this field. Answer exactly
      // like a success so a bot learns nothing, but store and send nothing.
      if (filled($request->input('website'))) {
          return response()->json(['success' => true, 'id' => null], 201);
      }

      $validated = $request->validate([
          'full_name' => ['required', 'string', 'max:255'],
          'date_of_birth' => ['required', 'date_format:Y-m-d', 'before:today', 'after:1900-01-01'],
          'gender' => ['required', 'in:male,female'],
          // … every row of the inventory, in order …
          'residence_country' => ['exclude_unless:resides_in_nz,no', 'required', 'string', 'max:255'],
          'transcript' => ['exclude_unless:attended_upci_bible_school,yes', 'nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
          'learning_difficulty' => ['exclude_unless:has_learning_difficulties,yes', 'required', Rule::in(array_keys(AbcEnrolment::LEARNING_DIFFICULTIES))],
          'learning_difficulty_other' => ['exclude_unless:learning_difficulty,other', 'required', 'string', 'max:255'],
          'church_name' => ['required', 'string', Rule::in([...AbcEnrolment::CHURCHES, 'other'])],
          'church_name_other' => ['exclude_unless:church_name,other', 'required', 'string', 'max:255'],
          'agreement_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:yesterday', 'before_or_equal:tomorrow'],
      ], [], [ /* friendly attribute names, e.g. 'resides_in_nz' => 'residing in New Zealand' */ ]);

      $data = collect($validated)->except('transcript')->all();
      foreach (['resides_in_nz', 'attended_upci_bible_school', 'has_learning_difficulties',
                'wants_ministerial_licence', 'read_articles_of_faith', 'baptised_in_jesus_name',
                'received_holy_ghost'] as $flag) {
          $data[$flag] = $validated[$flag] === 'yes';
      }

      if ($file = $request->file('transcript')) {
          if (array_key_exists('transcript', $validated)) {
              // Private disk on purpose: storage/app/private, never public.
              $data['transcript_path'] = $file->store('abc-enrolments/transcripts', 'local');
              $data['transcript_original_name'] = Str::limit($file->getClientOriginalName(), 250, '');
          }
      }

      $record = AbcEnrolment::create($data);

      if ($to = config('abc.enrolment_notify_to')) {
          // A mail outage must never turn a saved enrolment into a 500.
          rescue(fn () => Notification::route('mail', $to)
              ->notify(new NewAbcEnrolment($record->id, $record->full_name)), report: true);
      }

      return response()->json(['success' => true, 'id' => $record->id], 201);
  }
  ```
- **MIRROR**: `ContactController@store`: array-style rules, the `{success, id}` 201 shape, and the native 422.
- **GOTCHA**:
  - Keys removed by `exclude_*` are **absent** from `$validated`. The model columns are nullable, so `create()` is fine.
  - Put `exclude_*` **first** in each rule list.
  - Radios are the strings `yes`/`no`, which avoids the `required_if` JSON-boolean strictness trap.
  - `nullable` must come after `exclude_unless` for the transcript.
  - Don't log the request body.
- **VALIDATE**: `php -l`; covered by Task 12.

### Task 8: UPDATE `routes/web.php`
- **IMPLEMENT**: Inside the `Route::prefix('api')->middleware('throttle:60,1')` group, next to `/contact`:
  ```php
  // ABC new-student enrolment. Unauthenticated write of personal data, with an
  // optional file upload, so it gets the same tight limit as the contact form.
  Route::post('/abc-enrolments', [AbcEnrolmentController::class, 'store'])
      ->middleware('throttle:5,1');
  ```
  Add the `use` import in the alphabetical block at the top.
- **GOTCHA**: `api/*` is CSRF-exempt (`bootstrap/app.php`), and the routes must stay above the SPA catch-all, as everything in that group already does.
- **VALIDATE**: `php artisan route:list --path=api/abc-enrolments` shows `POST`.

### Task 9: huashu-design gate for the form page (owner rule: every UI task)
- **ACTION**: Run the `huashu-design` skill:
  - Show three directions for `AbcNewStudentEnrolment.vue`, for example "single long form with sticky section index", "five numbered section cards", and "client-side stepper (5 steps, one POST)".
  - Base it on upca.org.au, but **don't replicate it**. Brand green is `#4D7B37`.
  - **Wait for the user to choose** before Task 10.
- **CONSTRAINTS TO PASS TO THE SKILL**:
  - Must reuse `ErrorSummary` and the input/error classes from `ConnectWithUs.vue`.
  - `brand-error` is for errors only.
  - Every radio group is a `<fieldset>`/`<legend>`.
  - Mobile at 375px, with no horizontal scroll.
- **VALIDATE**: The user's chosen direction is recorded in this plan's Notes.

### Task 10: CREATE `resources/js/views/AbcNewStudentEnrolment.vue`
- **IMPLEMENT**: The chosen direction, built to this contract:
  - `defineComponent` + `setup()`. Call `usePageMeta().setPageMeta('New Student Enrolment - Apostolic Bible College', '…')` in `onMounted`, as in ConnectWithUs.
  - A `reactive` form with every inventory key. The 7 radios default to `''` (no preselection). `agreement_date` defaults to today **in local time**: `const d = new Date(), pad = n => String(n).padStart(2,'0')` → `${d.getFullYear()}-${pad(d.getMonth()+1)}-${pad(d.getDate())}`. Never use `toISOString()`; that was the UTC bug fixed in the audit.
  - A `transcript` ref holding the `File` from `<input type="file" accept=".pdf,.jpg,.jpeg,.png">`. Check the size on the client (10 MB) with a friendly message; the server stays authoritative.
  - **Conditionals**, both with `v-if` and in what is sent:
    - `residence_country` when `resides_in_nz === 'no'`;
    - the transcript input and the section 2 help text when `attended_upci_bible_school === 'yes'`;
    - the difficulty radios when `has_learning_difficulties === 'yes'`;
    - `learning_difficulty_other` when `learning_difficulty === 'other'`;
    - `church_name_other` when `church_name === 'other'`.
  - **Submit**: build a `FormData` and append every non-empty field, plus `transcript` when it's set and visible. Then `fetch('/api/abc-enrolments', { method: 'POST', headers: { Accept: 'application/json' }, body: fd })`.
    - **Do not set `Content-Type`**; the browser adds the multipart boundary.
    - Handle `response.ok` → `submitted`, `422` → `errors = body.errors`, `429` → "Too many attempts. Please wait a minute and try again.", anything else → generalError, and catch → the network error message.
  - **Accessibility**:
    - Each radio group is a `<fieldset :aria-describedby="errors.x ? 'x-error' : null"><legend>`.
    - The **first radio's id equals the field name**, so `ErrorSummary`'s `#field` link focuses it.
    - Text inputs use `id` = field name.
    - Error `<p :id="`${field}-error`">`.
    - Required markers in the label text. Native `required` on always-required inputs only, never on conditional ones.
    - After a 422, `await nextTick()` and focus the summary. Check whether `ErrorSummary` already does this through its `root` ref and watch. If it does, don't duplicate it.
  - **Honeypot**: `<div aria-hidden="true" class="absolute -left-[9999px]" tabindex="-1"><label for="website">Website</label><input id="website" v-model="form.website" type="text" autocomplete="off" tabindex="-1"></div>`. Send it, and never show its errors.
  - **Copy**: everything in "Static copy to port verbatim". Section titles are "1. Personal information", "2. Previous Bible school attendance", "3. Learning challenges", "4. Spiritual information", "5. Financial information & agreement". Put the privacy notice above the submit button.
  - **Success state**: replace the form with a panel: "Thank you — your enrolment has been received. The Apostolic Bible College will be in touch by email." Include a link back to `/apostolic-bible-college`, and `window.scrollTo({ top: 0 })`.
  - Ids for radio options: `${field}`, then `${field}-2` and so on, so the first one equals the field name.
- **MIRROR**: `ConnectWithUs.vue` (all behaviour and classes).
- **GOTCHA**:
  - Error keys for the file come back as `transcript`.
  - Laravel's `in:` message lists raw values, so pass friendly attribute names from the controller.
  - **Don't add `Accept: application/json` alone without the body being FormData.** Both are required.
- **VALIDATE**: `npm run build` exits 0.

### Task 11: UPDATE `resources/js/router/routes.js`
- **IMPLEMENT**: Directly after the `Enrollment` route (line ~58-62):
  ```js
  {
      path: '/apostolic-bible-college/enrollment/new-student',
      name: 'AbcNewStudentEnrolment',
      component: () => import('../views/AbcNewStudentEnrolment.vue')
  },
  ```
- **GOTCHA**: It must sit before `/cms/:slug(.*)` and the `/:slug(.*)` catch-all, or CmsPage will try to load the slug `apostolic-bible-college/enrollment/new-student` and show not-found. No Laravel route is needed, because `routes/web.php`'s `{any}` catch-all serves the SPA.
- **VALIDATE**: `npm run build`. Then `curl -s -o /dev/null -w '%{http_code}' localhost:<port>/apostolic-bible-college/enrollment/new-student` → 200, and the browser check in Level 5.

### Task 12: CREATE `tests/Feature/AbcEnrolmentApiTest.php`
- **IMPLEMENT**: A helper `abcEnrolmentPayload(array $overrides = []): array` returning a fully valid payload (all `yes`/`no` strings, a church from `AbcEnrolment::CHURCHES`, `agreement_date` = `now()->toDateString()`). Then the tests:
  1. A valid post → `assertCreated()->assertJson(['success'=>true])`. The row exists, the booleans are cast, and `resides_in_nz` is true.
  2. Missing required fields → 422 with every key from the inventory's `R` rows in `errors`.
  3. `resides_in_nz=no` without `residence_country` → 422 on `residence_country`. `resides_in_nz=yes` **with** a stray `residence_country` → 201, and the column is `null` (excluded).
  4. `has_learning_difficulties=yes` without `learning_difficulty` → 422. With `learning_difficulty=other` and no other text → 422 on `learning_difficulty_other`. `has_learning_difficulties=no` with a stray value → stored as null.
  5. `church_name=other` without `church_name_other` → 422. `church_name='Made Up Church'` → 422. Each name in `AbcEnrolment::CHURCHES` → 201 (a dataset).
  6. Transcript:
     - `Storage::fake('local')` + `UploadedFile::fake()->create('t.pdf', 200, 'application/pdf')` with `attended=yes` → stored at `abc-enrolments/transcripts/...` on **local**, with the original name saved.
     - `Storage::fake('public')` stays empty, which proves the file is never public.
     - A `.exe` → 422.
     - 11 MB → 422.
     - A transcript with `attended=no` → 201 and nothing stored.
  7. `date_of_birth` today or in the future → 422. `agreement_date` 3 days ago → 422.
  8. Honeypot: `website=http://spam` → 201, `AbcEnrolment::count() === 0`, and `Notification::assertNothingSent()`.
  9. Notifications:
     - `config(['abc.enrolment_notify_to' => null])` → `Notification::assertNothingSent()`.
     - `config([... => 'college@example.test'])` → `Notification::assertSentOnDemand(NewAbcEnrolment::class, fn ($n, $ch, $notifiable) => $notifiable->routes['mail'] === 'college@example.test')`.
     - The rendered mail body contains no DOB or phone: `(new NewAbcEnrolment(1, 'X'))->toMail(new AnonymousNotifiable)->render()`, or check the lines.
  10. Throttle: the 6th post in a minute → 429 (mirror `SecurityRegressionTest`'s contact throttle test).
- **VALIDATE**: `php artisan test --compact --filter=AbcEnrolmentApi`

### Task 13: CREATE Filament inbox + `tests/Feature/AbcEnrolmentAdminTest.php`
- **IMPLEMENT** (copy `ContactMessages/*` and rename):
  - **Resource**:
    - `$model = AbcEnrolment::class`, icon `Heroicon::OutlinedAcademicCap`. **Check that the case exists:** `grep -n "OutlinedAcademicCap" vendor/filament/support/src/Icons/Heroicon.php`.
    - Label `'ABC Enrolments'`, group `'Content'`, sort `4`.
    - Badge = count; `index` and `view` pages only.
  - **Table**:
    - `created_at` "Received"; `full_name` (searchable); `email` (searchable, copyable); `mobile_phone`; `church_display` "Church" (computed; not sortable).
    - `IconColumn::make('transcript_path')->label('Transcript')->boolean()`. **Verify that `IconColumn::boolean()` treats a non-empty string as true**; otherwise use `->state(fn ($r) => filled($r->transcript_path))`.
    - Filters: `TernaryFilter::make('has_transcript')->attribute('transcript_path')->nullable()`, and `SelectFilter::make('church_name')->options(array_combine(AbcEnrolment::CHURCHES, AbcEnrolment::CHURCHES) + ['other' => 'Other'])`.
    - Default sort `created_at desc`, `ViewAction`, `DeleteBulkAction`.
    - Empty state: "No enrolments yet" / "Submissions from /apostolic-bible-college/enrollment/new-student appear here."
  - **Infolist**: five `Section`s mirroring the form, each with `Grid::make(2|3)`.
    - Plain `TextEntry` only, with the comment "**never ->html()**" copied from `ContactMessageInfolist`.
    - Yes/no fields: `TextEntry::make('resides_in_nz')->formatStateUsing(fn ($state) => $state ? 'Yes' : 'No')->badge()`.
    - Long text uses `->columnSpanFull()`, plus `->extraAttributes(['class' => 'whitespace-pre-line'])` so line breaks show without HTML.
    - Show `church_display` and `learning_difficulty_display`, and `transcript_original_name` (or "None").
  - **View page**: `getHeaderActions()` returns:
    ```php
    Action::make('downloadTranscript')
        ->label('Download transcript')
        ->icon(Heroicon::OutlinedArrowDownTray)
        ->visible(fn (AbcEnrolment $record) => filled($record->transcript_path)
            && Storage::disk('local')->exists($record->transcript_path))
        ->action(fn (AbcEnrolment $record) => Storage::disk('local')->download(
            $record->transcript_path, $record->transcript_original_name ?: basename($record->transcript_path))),
    DeleteAction::make(),
    ```
    Use `Filament\Actions\Action` (v4 namespace). Reaching the page already requires `view`, which is national-only.
  - **Delete keeps the file?** Owner rule: delete nothing *we* didn't create. A national admin deleting a submission through the UI is a user action, so also remove its transcript file in `AbcEnrolment::booted()` → `static::deleted(fn ($e) => $e->transcript_path && Storage::disk('local')->delete($e->transcript_path))`. That stops orphaned PII lingering on disk (Privacy Act IPP 9). **Note this in the PR description.**
- **TESTS** (`AbcEnrolmentAdminTest.php`, helper `abcEnrolmentAdminUser($level)`):
  - Policy: national can `viewAny`/`view`/`delete`, and **cannot** `create`/`update`. Local and regional can't `viewAny`.
  - HTTP:
    - national `get('/admin/abc-enrolments')->assertOk()`;
    - regional and local → `assertForbidden()`;
    - national `get("/admin/abc-enrolments/{$id}")->assertOk()->assertSee($e->full_name)`;
    - `/admin/abc-enrolments/create` → 404.
  - XSS: an enrolment with `full_name = '<script>alert(1)</script>'` → the view page `assertDontSee('<script>alert(1)</script>', false)` and `assertSee('&lt;script&gt;', false)`.
  - Download:
    - `Storage::fake('local')`, put a file, then `Livewire::test(ViewAbcEnrolment::class, ['record' => $e->getRouteKey()])->callAction('downloadTranscript')->assertFileDownloaded($e->transcript_original_name)`. **Verify that `assertFileDownloaded` exists in Livewire 3.6** (`grep -rn "function assertFileDownloaded" vendor/livewire`).
    - Without a transcript, the action is hidden: `->assertActionHidden('downloadTranscript')`.
  - Deleting a record removes its transcript from the fake disk.
- **VALIDATE**: `php artisan test --compact --filter=AbcEnrolmentAdmin`

### Task 14: Repoint the card. CREATE data migration + UPDATE seeder + `tests/Feature/AbcEnrolmentPageLinkTest.php`
- **IMPLEMENT** the migration `2026_10_02_000002_point_abc_first_time_card_at_native_form.php`, mirroring `2026_08_17_150000`:
  - Load the page by slug `apostolic-bible-college/enrollment`. Return if it's missing.
  - Decode `content`.
  - In the `cards` block whose `data.style === 'registration'`, find the item whose `data.title === 'First time student'` **and** whose `data.link_url === 'https://forms.gle/iqVTh9YQH3nhZdw48'`. Set `link_url` to `'/apostolic-bible-college/enrollment/new-student'`. Leave `link_text` alone.
  - In the `text` block, replace the exact sentence `Each link opens the correct Google Form in a new tab.` with `First-time students now register here on our site; the other options open the official Google Forms in a new tab.` **Only if the old sentence is present.**
  - Match by title + URL, **not by index**. The work-exemption card has the same URL and must stay untouched.
  - `down()` restores the old URL and sentence, again matching by title + new URL.
- **IMPLEMENT** the seeder: in `ApostolicBibleCollegePageSeeder::seedEnrollment()`, change the first card's `link_url` (line ~184) and the text-block sentence the same way. Leave the work-exemption card's `iqVTh…` (line ~204) as it is.
- **TESTS**:
  - Create a page row with the live JSON shape: four cards, with the work-exemption card also on `iqVTh…`. Run the migration's `up()` via `(require database_path('migrations/2026_10_02_000002_….php'))->up()`.
  - Assert the first-time card is now internal, **the work-exemption card is still on `iqVTh…`**, the other two are unchanged, and the sentence is replaced.
  - Run `down()` and assert it's restored.
  - With no page row, `up()` doesn't throw.
  - `$this->seed(ApostolicBibleCollegePageSeeder::class)`, then the enrollment page's first card is internal.
- **APPLY TO LIVE DB** (after the user says OK, and Task 0's backup exists): `php artisan migrate`. The new table and the content change are both additive or reversible.
- **VALIDATE**: `php artisan test --compact --filter=AbcEnrolmentPageLink`; then `sqlite3 upci "select content from pages where id=12"` shows the new path on card 0 and `iqVTh` on the work-exemption card.

---

## Testing Strategy

### Tests to write
| Test File | Cases | Validates |
|---|---|---|
| `tests/Feature/AbcEnrolmentApiTest.php` | Happy path, required fields, 3 conditionals × (missing / stray), church allow-list (dataset), transcript (private disk, mime, size, dropped when "no"), dates, honeypot, notification on/off + no PII, throttle | Controller + validation + storage + notification |
| `tests/Feature/AbcEnrolmentAdminTest.php` | Policy matrix, HTTP 200/403/404, XSS escaping, download action + hidden state, delete removes file | Filament inbox + policy |
| `tests/Feature/AbcEnrolmentPageLinkTest.php` | Migration up/down/no-op, work-exemption untouched, seeder | Content change |

### Edge cases checklist
- [ ] All seven yes/no radios left unselected → 422 on each
- [ ] "Other" chosen but blank (church, learning difficulty)
- [ ] Conditional answered, then the parent switched back. The client doesn't send it, and the server excludes it anyway
- [ ] Transcript selected, then "attended before" switched to No. Not sent, and the server drops it
- [ ] Transcript is a 10.1 MB PDF, a `.docx`, or an `.exe` renamed to `.pdf` (`mimes` checks content, so this is rejected)
- [ ] Unicode names and macrons (Māori names). Stored and displayed escaped
- [ ] Date of birth today or in the future; agreement date ±1 day (NZ vs UTC)
- [ ] 6th submission in a minute → 429 is shown as a friendly message
- [ ] Honeypot filled → user-visible success, nothing stored
- [ ] `ABC_ENROLMENT_NOTIFY_EMAIL` unset → no job queued; set → exactly one on-demand notification
- [ ] Regional/local admins can't see the menu item or the URLs

---

## Validation Commands

### Level 1: STATIC_ANALYSIS
```bash
{ git diff --name-only; git ls-files --others --exclude-standard; } | grep '\.php$' | sort -u | xargs -n1 php -l
vendor/bin/pint --test $( { git diff --name-only; git ls-files --others --exclude-standard; } | grep '\.php$' | sort -u )
```
**EXPECT**: no syntax errors; pint PASS.

### Level 2: UNIT/FEATURE TESTS
```bash
php artisan test --compact --filter='AbcEnrolment'
```
**EXPECT**: all pass.

### Level 3: FULL_SUITE + BUILD
```bash
php artisan test --compact && npm run build
```
**EXPECT**: 0 failed (the baseline before this feature is 216 passed / 25 skipped), and the build succeeds.

### Level 4: DATABASE_VALIDATION (after `php artisan migrate` on live, with the user's OK)
```bash
sqlite3 upci ".schema abc_enrolments"
sqlite3 upci "select json_extract(content,'$[2].data.items[0].data.link_url'), json_extract(content,'$[2].data.items[3].data.link_url') from pages where slug='apostolic-bible-college/enrollment'"
# expect: /apostolic-bible-college/enrollment/new-student | https://forms.gle/iqVTh9YQH3nhZdw48
php artisan route:list --path=api/abc-enrolments
```

### Level 5: BROWSER_VALIDATION (Chrome tools)
- `/apostolic-bible-college/enrollment`: click "First Time Student Register HERE". It opens the form in the **same tab**.
- Submit empty. The ErrorSummary gets focus, every link jumps to its field, and radio links focus the first option.
- Toggle each conditional: the field appears and disappears, and its error clears.
- Fill everything and attach a small PDF, then submit. The success panel shows.
- Check at 375px width: no horizontal scroll.
- `/admin/abc-enrolments` as a national user: the row is listed; the view shows every section; Download transcript returns the PDF.
- `/admin/abc-enrolments` as a regional user: 403, and no nav item.

### Level 6: MANUAL_VALIDATION
1. With `ABC_ENROLMENT_NOTIFY_EMAIL=you@example.test` and `MAIL_MAILER=log`, run `php artisan queue:work --once` after a submission. `storage/logs/laravel.log` shows the mail with the name and admin link, and no DOB or phone.
2. Unset it again: no row added to `jobs`.
3. `ls storage/app/private/abc-enrolments/transcripts` contains the upload. `curl /storage/abc-enrolments/...` returns 404, because it isn't public.

---

## Acceptance Criteria
- [ ] The "First time student" card opens `/apostolic-bible-college/enrollment/new-student` in the same tab. The other three cards are unchanged.
- [ ] The form has all 26 Google Form fields, with the same labels, options and required flags, plus the optional transcript upload.
- [ ] A valid submission stores one `abc_enrolments` row. The transcript is on the private `local` disk only.
- [ ] Only national admins can list, view, download and delete. Nobody can create or edit.
- [ ] A notification is sent only when `ABC_ENROLMENT_NOTIFY_EMAIL` is set, and it carries no DOB, address or phone.
- [ ] Levels 1-3 pass; full suite 0 failed; build OK.
- [ ] The UI follows the huashu-design direction the user chose, is accessible (fieldsets, focusable error summary), and works at 375px.
- [ ] Nothing deleted (`git status` shows no `D`).

## Completion Checklist
- [ ] Task 0 backup verified
- [ ] Tasks 1-14 done in order, each validated
- [ ] Levels 1-3 pass
- [ ] Level 4 after the user's OK to migrate live
- [ ] Level 5 browser pass
- [ ] Report written to `.claude/PRPs/reports/`

---

## Risks and Mitigations

| Risk | Likelihood | Impact | Mitigation |
|---|---|---|---|
| Spam or bot submissions (unauthenticated, CSRF-exempt) | MED | MED | `throttle:5,1` + honeypot (tested). Cloudflare is in front. Add Turnstile later if needed |
| PII exposure (DOB, address, phones, learning difficulties) | LOW | HIGH | National-only policy (tested); escaped output only (XSS test); private disk (tested); no PII in email or logs |
| No email ever arrives (no worker, or no recipient) | HIGH (by design for now) | LOW | It's a placeholder. The admin badge count shows new rows. Document the `queue:work` + `MAIL_*` + `ABC_ENROLMENT_NOTIFY_EMAIL` needed to go live |
| The data migration hits the wrong card (two cards share the URL) | MED | MED | Match title **and** URL, never index. A test covers the live shape, including the work-exemption card |
| Upload limits: PHP `upload_max_filesize=20M` (FPM 8.4/8.5); nginx `client_max_body_size` unknown | LOW | MED | App max is 10 MB. Check nginx allows ≥ 11 MB, or a large file returns an HTML 413 that the Vue form shows as the general error |
| UTC app timezone vs NZ users on `agreement_date` | MED | LOW | ±1 day window (tested) |
| Fee schedule image can't be captured | MED | LOW | Ship the text paragraph; owner supplies the schedule (Task 1) |
| Field order or wording drift from the Google Form | LOW | LOW | The inventory table is the source of truth; Level 5 compares side by side |

---

## Open questions for the owner (do not block implementation)
1. **Work exemption card**: live, it points at the *first-time* Google Form (`forms.gle/iqVTh…`), the same as this form. Is that intended? This plan leaves it alone.
2. **Contact email**: the Google Form mentions both `info.nzabc@gmail.com` and `apostolicbiblecollege@upci.co.nz`. The plan uses the latter in the copy.
3. **Notification recipient**: set `ABC_ENROLMENT_NOTIFY_EMAIL` when decided. Production also needs a queue worker and real mail settings.
4. **Retention**: how long should enrolments and transcripts be kept? (Privacy Act IPP 9.) A prune job can be added.
5. **Privacy notice wording**: review the draft collection notice above.
6. **Fee schedule**: confirm the current fees if the Google Form image is out of date.

## Notes
- The field inventory was extracted on 2026-10-02 from the form's `FB_PUBLIC_LOAD_DATA_` and checked by resolving `forms.gle/iqVTh9YQH3nhZdw48` to the Google Form URL from the user's brief.
- The Google Form's "Other" choices (learning difficulty and church) show up as an empty-label option with the "other" flag. They're modelled as the value `other` plus a free-text field.
- The "Progression of Study" image was downloaded to the session scratchpad. It's rendered as HTML instead, for accessibility and editability.
- huashu-design direction chosen: **D3, "the application as a document"** (user: "let's do D3."). See `.claude/design/abc-enrolment/direction-approved.md`.
