# Feature: "Bible School" access level (ABC enrolments only)

## Summary

Add a fourth access level, **Bible School (ABC enrolments only)**, that a national administrator assigns to a login in Admin › Users. A Bible School login gets into `/admin` and sees one thing: **ABC Enrolments**, meaning the list, each enrolment's full record (including the pastor's reference), transcript downloads, and the "Ask the pastor for a reference / Email the pastor again" button. It cannot delete enrolments. Every other resource, page and dashboard widget is hidden and refused by URL.

Today, simply adding an enum case would be unsafe. Five policy checks grant on "any access level". `ScopesToAccessLevel` and the three attendance widgets would throw `UnhandledMatchError`, and a Bible School user would crash on the dashboard the moment they logged in. This plan closes each of those gaps.

## User Story

As the **UPCI NZ national administrator**
I want to **give a Bible School staff member a login whose only access is the ABC enrolments**
So that **they can review applicants, their pastors' endorsements and transcripts without seeing or touching churches, users, attendance, events or site content**.

## Problem Statement

The owner asked (2026-11-02): "i now need a login with role of bible school admin or something. that can see the list of enrollees on the bible school and data. give me a role that i can assign to a login that will have access to the information from the backend."

Today ABC Enrolments is national-only (`AbcEnrolmentPolicy extends NationalOnlyPolicy`). The only way to let someone read enrolments is to make them national, which hands them the whole admin.

Testable success signals:
- A user with `access_level = bible_school` can `GET /admin`, `/admin/abc-enrolments` and `/admin/abc-enrolments/{id}` (200), and sees the Pastor's reference answers.
- That user gets **403** on every other resource index (`/admin/churches`, `/admin/users`, `/admin/attendances`, `/admin/events`, …) and on `/admin/manage-site-settings`.
- In the panel, only `AbcEnrolmentResource::canViewAny()` is true for that user.
- The three attendance widgets report `canView() === false` for that user, so the dashboard does not crash.
- National, regional and local behaviour is unchanged (the full existing suite stays green).

## Solution Statement

- **Enum.** Add `AccessLevel::BIBLE_SCHOOL = 'bible_school'`. `users.access_level` is a nullable `string` (`2026_04_20_100003_…:13`), so **no migration** is needed.
- **Allow list.** `AbcEnrolmentPolicy` overrides `viewAny`/`view` to allow national **or** Bible School, and gains an `emailPastor` ability. `delete*` stays national-only (inherited). `create`/`update` stay `false`.
- **Close the leaks.** Add `User::hasChurchScope()` (national, regional or local). Replace the five `(bool) $user->access_level` grants in AttendancePolicy, ChurchPolicy and UserPolicy with it.
- **Belt and braces.**
  - `ScopesToAccessLevel` gets an explicit `BIBLE_SCHOOL => 1=0` arm.
  - The three attendance widgets get `canView()` tied to `AttendanceResource::canViewAny()`.
  - `UserPolicy::outranks()` stops regional presbyters from editing a Bible School login. That is the same class of bug its own comment describes for national admins.
- **Admin UI.** The user form's helper text mentions the new level. The `emailPastor` action authorises through the policy instead of a hard-coded `isNational()`.

## Metadata

| Field            | Value |
| ---------------- | ----- |
| Type             | ENHANCEMENT |
| Complexity       | MEDIUM (small diff, ~10 files, but security-sensitive: every gate must be right) |
| Systems Affected | `AccessLevel` enum, `User` model, 4 policies, `ScopesToAccessLevel` trait, 3 dashboard widgets, ABC enrolment view page, user form, tests |
| Dependencies     | Laravel 12.70, Filament 4.15.1, Pest 3.8, PHP 8.4 (all present; nothing new) |
| Estimated Tasks  | 11 (incl. backup + validation) |

---

## UX Design

### Before State
```
╔══════════════════════════════════════════════════════════════════════════════╗
║                                BEFORE STATE                                  ║
╠══════════════════════════════════════════════════════════════════════════════╣
║  Admin › Users › Access Level select                                         ║
║   ┌──────────────────────────────┐                                           ║
║   │ Local (single church)        │   No way to grant enrolments alone.       ║
║   │ Regional (one region)        │   Only "National" reaches ABC Enrolments, ║
║   │ National (all regions)  ◄────┼── and National = the whole admin          ║
║   └──────────────────────────────┘   (churches, users, pages, settings…)     ║
║                                                                              ║
║  /admin/abc-enrolments  ── AbcEnrolmentPolicy::viewAny ── isNational()       ║
║        local / regional ──► 403                                              ║
║                                                                              ║
║  HYPOTHETICAL (enum case added, nothing else):                               ║
║   login ──► /admin Dashboard ──► MainStatsWidget ──► AttendanceResource::    ║
║            getEloquentQuery() ──► ScopesToAccessLevel match ── no arm ──►    ║
║            UnhandledMatchError (500)                                         ║
║   Sidebar shows Churches / Users / Attendances  (viewAny = (bool) level)     ║
║   and each list 500s; ABC Enrolments still hidden (national-only)            ║
║                                                                              ║
║  USER_FLOW: owner must make Bible School staff national, or nothing.         ║
║  PAIN_POINT: no least-privilege option for applicant PII.                    ║
╚══════════════════════════════════════════════════════════════════════════════╝
```

### After State
```
╔══════════════════════════════════════════════════════════════════════════════╗
║                                 AFTER STATE                                  ║
╠══════════════════════════════════════════════════════════════════════════════╣
║  Admin › Users › Access Level select (national admin only)                   ║
║   ┌──────────────────────────────────────┐                                   ║
║   │ Local (single church)                │                                   ║
║   │ Regional (one region)                │                                   ║
║   │ National (all regions)               │                                   ║
║   │ Bible School (ABC enrolments only) ◄─┼── NEW (no church/region needed)   ║
║   └──────────────────────────────────────┘                                   ║
║                                                                              ║
║  Bible School login ──► /admin  Dashboard (welcome + sign out only;          ║
║                          attendance widgets canView = false)                 ║
║        │                                                                     ║
║        └─ Sidebar:  Content ▸ ABC Enrolments  [badge: count]   (only item)   ║
║                 │                                                            ║
║                 ▼                                                            ║
║          /admin/abc-enrolments  (list, reference status column, filters)     ║
║                 │  View                                                      ║
║                 ▼                                                            ║
║          /admin/abc-enrolments/{id}                                          ║
║            Personal · Bible school · Learning · Spiritual ·                  ║
║            Pastor's reference (answers) · Agreement                          ║
║            [Download transcript]  [Ask the pastor for a reference /          ║
║                                    Email the pastor again]                   ║
║            (no Delete button; no bulk delete)                                ║
║                                                                              ║
║  Any other admin URL ──► 403      /admin/manage-site-settings ──► 403        ║
║                                                                              ║
║  DATA_FLOW: users.access_level='bible_school' ──► AccessLevel::BIBLE_SCHOOL  ║
║   ──► canAccessPanel (non-null) ✓ ──► policies: AbcEnrolment viewAny/view/   ║
║   emailPastor ✓; every other policy ✗ ──► Filament hides nav + 403s URLs     ║
╚══════════════════════════════════════════════════════════════════════════════╝
```

### Interaction Changes
| Location | Before | After | User Impact |
|----------|--------|-------|-------------|
| Admin › Users › Access Level | 3 options | 4th option "Bible School (ABC enrolments only)" | Owner can grant enrolment-only access |
| `/admin` for a Bible School login | n/a (would 500) | Dashboard with Account + Filament info widgets only | Clean landing, one sidebar item |
| `/admin/abc-enrolments` | national only | national + Bible School | Bible School staff see applicants |
| `/admin/abc-enrolments/{id}` | national only | national + Bible School; Delete hidden for Bible School | Read, download transcript, chase endorsement |
| "Email the pastor" button | `isNational()` | policy `emailPastor`: national or Bible School | Bible School staff can (re)send the reference questionnaire |
| Regional presbyter editing users | could (by policy) edit a non-national/non-regional user in region | cannot view/edit a Bible School login | Closes a privilege-escalation path |

---

## Mandatory Reading

| Priority | File | Lines | Why Read This |
|----------|------|-------|---------------|
| P0 | `app/Enums/AccessLevel.php` | all (35) | Enum to extend; `getLabel`/`getColor` are exhaustive `match` with no default |
| P0 | `app/Models/User.php` | 98-136 | `isLocal/isRegional/isNational`, `canAccessPanel`, `canAccessChurch` (has `default => false`) |
| P0 | `app/Policies/NationalOnlyPolicy.php` | all | Base class `AbcEnrolmentPolicy` inherits; method signatures (`Model $record`) |
| P0 | `app/Policies/AbcEnrolmentPolicy.php` | all | Policy to widen; keep its docblock voice |
| P0 | `app/Policies/AttendancePolicy.php` | 10-12, 20-23, 49-52 | Three `(bool) $user->access_level` grants to replace |
| P0 | `app/Policies/ChurchPolicy.php` | 10-12 | `(bool) $user->access_level` grant to replace |
| P0 | `app/Policies/UserPolicy.php` | 9-12, 28-45, 57-65 | `viewAny` grant; regional `update` path; `outranks()` |
| P0 | `app/Filament/Concerns/ScopesToAccessLevel.php` | 21-35 | `match` without default arm |
| P0 | `app/Filament/Resources/AbcEnrolments/Pages/ViewAbcEnrolment.php` | 22-60 | Header actions; `emailPastor` `->authorize(...)` at 54-56; transcript comment at 25-27 |
| P1 | `app/Filament/Widgets/MainStatsWidget.php`, `AttendanceTrendChart.php`, `CategoryBreakdownWidget.php` | 1-30 | Each calls `AttendanceResource::getEloquentQuery()`, has no `canView()` |
| P1 | `app/Filament/Resources/Users/Schemas/UserForm.php` | 50-117 | Access level select + church/region `requiredIf`/`visible` |
| P1 | `tests/Feature/AbcEnrolmentAdminTest.php` | 1-160 | Helper + policy + HTTP + Livewire test style |
| P1 | `tests/Feature/AbcPastorReferenceAdminTest.php` | 1-160 | `pastorReferenceEnrolmentRecord`, `callAction('emailPastor', data: …)`, `Notification::assertSentOnDemand` |
| P2 | `tests/Feature/PanelAccessGateTest.php`, `tests/Feature/DashboardTest.php` | all / 40-60 | Panel-gate + dashboard test style |
| P2 | `vendor/filament/actions/src/Concerns/CanBeAuthorized.php` | 33-85 | `authorize('ability')` prepends the record to the Gate arguments |
| P2 | `vendor/filament/filament/src/Panel/Concerns/HasRoutes.php` | 170-200 | No `home` route here → `Filament::getUrl()` = `/admin` (Dashboard) after login |

**External Documentation:** see "References" at the end (filled from research).

---

## Patterns to Mirror

**ENUM (exhaustive match, add an arm in each):**
```php
// SOURCE: app/Enums/AccessLevel.php:5-35
enum AccessLevel: string
{
    case LOCAL = 'local';
    case REGIONAL = 'regional';
    case NATIONAL = 'national';

    public function getLabel(): string
    {
        return match ($this) {
            self::LOCAL => 'Local (single church)',
            self::REGIONAL => 'Regional (one region)',
            self::NATIONAL => 'National (all regions)',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::LOCAL => 'gray',
            self::REGIONAL => 'info',
            self::NATIONAL => 'success',
        };
    }
    // getOptions(): collect(self::cases())->mapWithKeys(fn (self $l) => [$l->value => $l->getLabel()])->toArray();
}
```

**USER LEVEL HELPERS:**
```php
// SOURCE: app/Models/User.php:98-116
public function isLocal(): bool
{
    return $this->access_level === AccessLevel::LOCAL;
}

public function isRegional(): bool
{
    return $this->access_level === AccessLevel::REGIONAL;
}

public function isNational(): bool
{
    return $this->access_level === AccessLevel::NATIONAL;
}

public function canAccessPanel(Panel $panel): bool
{
    return (bool) $this->access_level;
}
```

**POLICY WITH DOCBLOCK + OVERRIDES OF THE NATIONAL-ONLY BASE:**
```php
// SOURCE: app/Policies/AbcEnrolmentPolicy.php:8-28
/**
 * National-only, and read/delete only. An enrolment is an application someone
 * else submitted, holding their date of birth, address, phone numbers and any
 * learning difficulties, so nobody authors or rewrites one in the admin. The
 * resource has no create or edit page either; this makes that explicit at the
 * authorization layer, the same as ContactMessagePolicy.
 */
class AbcEnrolmentPolicy extends NationalOnlyPolicy
{
    public function create(User $user): bool { return false; }
    public function update(User $user, Model $record): bool { return false; }
}
```

**MIXED-LEVEL POLICY (positive checks, never "any level"):**
```php
// SOURCE: app/Policies/EventPolicy.php:16-19
public function viewAny(User $user): bool
{
    return $user->isNational() || $user->isRegional();
}
```

**THE LEAKS TO REPLACE (the anti-pattern):**
```php
// SOURCE: app/Policies/AttendancePolicy.php:10-12 (also :20-23 create, :49-52 deleteAny)
// SOURCE: app/Policies/ChurchPolicy.php:10-12, app/Policies/UserPolicy.php:9-12
public function viewAny(User $user): bool
{
    return (bool) $user->access_level;
}
```

**CUSTOM PAGE GATE (static, used by nav too):**
```php
// SOURCE: app/Filament/Pages/ManageSiteSettings.php:45-53
public static function canAccess(): bool
{
    return auth()->user()?->isNational() ?? false;
}
```

**ACTION AUTHORISATION (current, to be replaced by an ability string):**
```php
// SOURCE: app/Filament/Resources/AbcEnrolments/Pages/ViewAbcEnrolment.php:54-56
// The resource is national-only already; said again here because
// this action sends email in the college's name.
->authorize(fn (): bool => (bool) auth()->user()?->isNational())
```

**TEST STRUCTURE (Pest `test()`, file-level helpers with unique names, `User::create` + enum):**
```php
// SOURCE: tests/Feature/AbcEnrolmentAdminTest.php:19-34, 64-98
beforeEach(function () {
    Filament::setCurrentPanel('admin');
    Storage::fake('local');
});

function abcEnrolmentAdminUser(string $level): User
{
    return User::create([
        'name' => 'T',
        'email' => $level.'-abc-enrolment@x',
        'password' => 'x',
        'role' => UserRole::ADMINISTRATOR,
        'access_level' => AccessLevel::from($level),
    ]);
}

test('regional and local users are refused the inbox and its records', function (string $level) {
    $enrolment = abcEnrolmentRecord();

    $this->actingAs(abcEnrolmentAdminUser($level));

    $this->get('/admin/abc-enrolments')->assertForbidden();
    $this->get("/admin/abc-enrolments/{$enrolment->id}")->assertForbidden();
})->with(['regional', 'local']);
```

**LIVEWIRE ACTION TEST + ON-DEMAND NOTIFICATION:**
```php
// SOURCE: tests/Feature/AbcPastorReferenceAdminTest.php:112-131
Livewire::test(ViewAbcEnrolment::class, ['record' => $enrolment->getRouteKey()])
    ->callAction('emailPastor', data: ['pastor_name' => 'Wayne Example', 'pastor_email' => 'wayne@new.test'])
    ->assertHasNoActionErrors()
    ->assertNotified('Questionnaire emailed to wayne@new.test');

Notification::assertSentOnDemand(
    AbcPastorReferenceRequest::class,
    fn ($notification, $channels, $notifiable) => $notifiable->routes['mail'] === 'wayne@new.test'
        && hash('sha256', $notification->token) === $reference->getRawOriginal('token_hash'),
);
```

**SUBMITTED-REFERENCE FIXTURE (copy the attributes, not the function name):**
```php
// SOURCE: tests/Feature/AbcPastorReferenceAdminTest.php:36-60, 75-89
// Enrolment created with AbcEnrolment::create([...required fields...]); then
$row = new AbcPastorReference(array_merge([
    'pastor_name' => 'Wayne Example', 'pastor_email' => 'wayne@grace.test',
    'pastor_email_source' => 'applicant', 'emailed_at' => now(),
], $reference));
$row->issueToken();
$enrolment->pastorReference()->save($row);
// submitted answers: 'faithful_attendance' => true, 'faithful_holiness' => true, 'faithful_tithes' => false,
// 'is_serving' => true, 'serving_details' => '…', 'approves' => true, 'approves_level' => true,
// 'comments' => 'Faithful.', 'signed_name' => 'Wayne Example', 'submitted_at' => now()
```

**CODE STYLE:** `pint.json` uses the `laravel` preset with imports **sorted by length**, `not_operator_with_successor_space` (`! $x`), single quotes, and one blank line between methods. Run Pint on changed files only (Task 10).

---

## Files to Change

| File | Action | Justification |
|------|--------|---------------|
| `app/Enums/AccessLevel.php` | UPDATE | New case + label/colour arms (both `match` blocks are exhaustive) |
| `app/Models/User.php` | UPDATE | `isBibleSchool()` + `hasChurchScope()` helpers |
| `app/Policies/AbcEnrolmentPolicy.php` | UPDATE | Allow `viewAny`/`view`/`emailPastor` for Bible School; docblock |
| `app/Policies/AttendancePolicy.php` | UPDATE | `viewAny`/`create`/`deleteAny`: `hasChurchScope()` |
| `app/Policies/ChurchPolicy.php` | UPDATE | `viewAny`: `hasChurchScope()` |
| `app/Policies/UserPolicy.php` | UPDATE | `viewAny`: `hasChurchScope()`; `outranks()` excludes Bible School targets |
| `app/Filament/Concerns/ScopesToAccessLevel.php` | UPDATE | Explicit `BIBLE_SCHOOL => 1=0` arm |
| `app/Filament/Widgets/MainStatsWidget.php` | UPDATE | `canView()` = `AttendanceResource::canViewAny()` |
| `app/Filament/Widgets/AttendanceTrendChart.php` | UPDATE | same |
| `app/Filament/Widgets/CategoryBreakdownWidget.php` | UPDATE | same |
| `app/Filament/Resources/AbcEnrolments/Pages/ViewAbcEnrolment.php` | UPDATE | `->authorize('emailPastor')`; fix two comments that say "national-only" |
| `app/Filament/Resources/Users/Schemas/UserForm.php` | UPDATE | Helper text names the new level |
| `tests/Feature/BibleSchoolAccessTest.php` | CREATE | Full coverage of the new level + regressions |

No migration. No Vue/frontend change. No route change. No `.env`/server change.

---

## NOT Building (Scope Limits)

- **A `UserRole` "Bible School Admin".** Role is the person's church role ("what you do"). Access level is admin reach ("what you can see"), per the access-level plan in `completed/user-access-levels-local-regional-national.plan.md`. The owner said "role … or something"; the access level is the right lever. The role select stays as-is (pick e.g. Member).
- **Deleting enrolments for Bible School.** Deleting applicant PII stays national-only (inherited `delete`/`deleteAny`). One-line change later if the owner wants it.
- **Contact messages from the ABC "Connect with us" page.** Those live in Contact Messages (national-only). Not requested; would need a per-source filter. Offer later.
- **CMS editing of ABC pages.** Not requested.
- **A custom Bible School dashboard / auto-redirect to enrolments.** The dashboard stays reachable (it is where Filament sends every login: no `home` route in this panel, so `Filament::getUrl()` = `/admin`). Hiding it would 403 the user right after login. The sidebar has a single item, which is enough.
- **Creating the actual login on the live DB.** Live data change. The owner does it in Admin › Users (or asks me to, explicitly).
- **Email delivery.** "Email the pastor" works in code, but the site's mail is still `MAIL_MAILER=log` with no queue worker (`AbcPastorReferenceRequest` is `ShouldQueue`). That's the existing owner item, unchanged here.
- **Rewriting existing tests.** `AbcEnrolmentAdminTest`/`AbcPastorReferenceAdminTest` keep passing unchanged (they test regional/local refusals, still true). Nothing is deleted.

---

## Step-by-Step Tasks

Execute in order. The working tree is served live by php-fpm, so **every save is live immediately**. Each task below is backward-compatible on its own, because no user has `bible_school` until the owner assigns it.

### Task 0: BACKUP (house rule)

- **ACTION**: Snapshot the current uncommitted work before touching anything. No DB change is planned, so a sqlite backup is optional.
- **IMPLEMENT**:
  ```bash
  cd /var/www/personal/upci.co.nz
  B=/var/www/personal/upci.co.nz-backups/pre-bible-school-access-$(date +%Y%m%d-%H%M%S); mkdir -p "$B"
  /usr/bin/git diff --binary > "$B/uncommitted.patch"
  /usr/bin/git ls-files --others --exclude-standard -z | tar --null -T - -czf "$B/untracked.tar.gz"
  /usr/bin/git rev-parse HEAD > "$B/HEAD"
  printf 'Before the Bible School access level (2026-11-02). Restore: git checkout <HEAD> && git apply uncommitted.patch && tar xzf untracked.tar.gz\n' > "$B/README"
  ```
- **GOTCHA**: Use `/usr/bin/git`. Bare `git` output is condensed by a hook, so a patch made with it is a summary, not a patch.
- **VALIDATE**: `ls -la "$B"`, `/usr/bin/git apply --check --reverse "$B/uncommitted.patch"` (exit 0), and a non-empty `untracked.tar.gz`.
- **BASELINE**: `sudo -u www-data php artisan test --compact 2>&1 | tail -3`. Record the pass/skip counts.

### Task 1: UPDATE `app/Enums/AccessLevel.php`

- **ACTION**: Add the case and its arms.
- **IMPLEMENT**:
  - `case BIBLE_SCHOOL = 'bible_school';` after `NATIONAL`. Order sets the select order, so it lands last.
  - `getLabel()`: `self::BIBLE_SCHOOL => 'Bible School (ABC enrolments only)',`
  - `getColor()`: `self::BIBLE_SCHOOL => 'warning',` (distinct from gray/info/success).
- **GOTCHA**: Both `match` blocks lack `default`. Missing an arm throws `UnhandledMatchError` in UserInfolist/UserForm for that user. Don't add a `default`; keep them exhaustive.
- **VALIDATE**: `sudo -u www-data php -r 'require "vendor/autoload.php"; var_dump(App\Enums\AccessLevel::getOptions());'` shows 4 entries.

### Task 2: UPDATE `app/Models/User.php`

- **ACTION**: Two helpers next to `isNational()` (after line 111).
- **IMPLEMENT**:
  ```php
  public function isBibleSchool(): bool
  {
      return $this->access_level === AccessLevel::BIBLE_SCHOOL;
  }

  /**
   * National, regional and local logins look after churches: their people,
   * attendance and events. A Bible School login does not; it reads ABC
   * enrolments and nothing else. Policies that used to grant on "has any
   * access level" ask this instead, so a new level starts with nothing.
   */
  public function hasChurchScope(): bool
  {
      return $this->isNational() || $this->isRegional() || $this->isLocal();
  }
  ```
- **DO NOT** change `canAccessPanel()`. `(bool) $this->access_level` already admits the new case, and `users:reset-password` relies on it.
- **VALIDATE**: `php -l app/Models/User.php`

### Task 3: UPDATE `app/Policies/AbcEnrolmentPolicy.php`

- **ACTION**: Widen read access and add the email ability. Keep delete national-only.
- **IMPLEMENT**:
  ```php
  /**
   * Read/delete only. An enrolment is an application someone else submitted,
   * holding their date of birth, address, phone numbers and any learning
   * difficulties, so nobody authors or rewrites one in the admin. The resource
   * has no create or edit page either; this makes that explicit at the
   * authorization layer, the same as ContactMessagePolicy.
   *
   * National users and Bible School logins may read enrolments and ask a
   * pastor for a reference. Deleting one stays national-only (inherited).
   */
  class AbcEnrolmentPolicy extends NationalOnlyPolicy
  {
      public function viewAny(User $user): bool
      {
          return $user->isNational() || $user->isBibleSchool();
      }

      public function view(User $user, Model $record): bool
      {
          return $this->viewAny($user);
      }

      /**
       * ViewAbcEnrolment's "Ask the pastor for a reference" / "Email the
       * pastor again". It sends email in the college's name.
       */
      public function emailPastor(User $user, Model $record): bool
      {
          return $this->view($user, $record);
      }

      public function create(User $user): bool { return false; }   // keep existing formatting
      public function update(User $user, Model $record): bool { return false; }
  }
  ```
- **GOTCHA**: The signatures must match `NationalOnlyPolicy` (`Model $record`, not `AbcEnrolment`), or PHP raises a fatal incompatible-signature error.
- **VALIDATE**: `php -l app/Policies/AbcEnrolmentPolicy.php`

### Task 4: UPDATE `AttendancePolicy`, `ChurchPolicy`, `UserPolicy` (close the "any level" grants)

- **ACTION**: Replace each `return (bool) $user->access_level;` with `return $user->hasChurchScope();`:
  - `app/Policies/AttendancePolicy.php`: `viewAny` (:10-12), `create` (:20-23), `deleteAny` (:49-52)
  - `app/Policies/ChurchPolicy.php`: `viewAny` (:10-12)
  - `app/Policies/UserPolicy.php`: `viewAny` (:9-12)
- **ALSO** in `UserPolicy::outranks()` (:62-65), and update its docblock to say regional outranks local and no-access users only:
  ```php
  return ! $target->isNational() && ! $target->isRegional() && ! $target->isBibleSchool();
  ```
  Why: a regional presbyter could otherwise `update` (and so set the password of) a Bible School login whose church is in their region. That would hand them enrolment PII. It is the exact bug class the comment at :35-37 describes.
- **GOTCHA**: For null/local/regional/national the result is identical to before. Null → false, as now. Don't touch `view`/`update` self-access (`$actor->id === $target->id`).
- **VALIDATE**: `/usr/bin/grep -rn "(bool) \$user->access_level" app/Policies` returns nothing.

### Task 5: UPDATE `app/Filament/Concerns/ScopesToAccessLevel.php`

- **ACTION**: Add an explicit arm to the `match` at :30-34.
- **IMPLEMENT**: `AccessLevel::BIBLE_SCHOOL => $query->whereRaw('1=0'),`, with a one-line comment: "A Bible School login looks after no church; its policies refuse these resources, and this keeps any query that slips through empty."
- **GOTCHA**: Keep the `match` exhaustive (no `default`), so a future fifth level fails loudly instead of silently.
- **VALIDATE**: `php -l app/Filament/Concerns/ScopesToAccessLevel.php`

### Task 6: UPDATE the three attendance widgets

- **ACTION**: In `MainStatsWidget.php`, `AttendanceTrendChart.php` and `CategoryBreakdownWidget.php`, add:
  ```php
  /**
   * Attendance figures, so only for logins that may see attendance. A Bible
   * School login lands on the dashboard too, and must not get these.
   */
  public static function canView(): bool
  {
      return AttendanceResource::canViewAny();
  }
  ```
  `AttendanceResource` is already imported in all three.
- **GOTCHA**: `Widget::canView()` is `public static` in Filament 4 (`vendor/filament/widgets/src/Widget.php:34`). Match the signature exactly. Widgets render lazily, so an HTTP `GET /admin` test can pass even when a widget would crash. Assert `canView()` directly (Task 9).
- **VALIDATE**: `php -l` each file.

### Task 7: UPDATE `app/Filament/Resources/AbcEnrolments/Pages/ViewAbcEnrolment.php`

- **ACTION**:
  - Replace lines 54-56 with:
    ```php
    // Sends email in the college's name: AbcEnrolmentPolicy::emailPastor
    // (national or Bible School).
    ->authorize('emailPastor')
    ```
  - Reword the comment at 25-27: "…reaching this page already requires the enrolment view permission (national or Bible School)."
- **GOTCHA**: `authorize('emailPastor')` prepends the record (`CanBeAuthorized::parseAuthorizationArguments`), so the policy receives `(User, AbcEnrolment)`. Leave `->visible(...)` (hidden once the reference is submitted) as is.
- **VALIDATE**: `sudo -u www-data php artisan test --compact tests/Feature/AbcPastorReferenceAdminTest.php`. The national send tests must still pass.

### Task 8: UPDATE `app/Filament/Resources/Users/Schemas/UserForm.php`

- **ACTION**: Change the helper text at :60 to `'Local: one church. Regional: one region. National: full access. Bible School: ABC enrolments only.'`
- **GOTCHA**: The church select stays visible and optional for Bible School (`requiredIf` only for local), and region stays hidden. No other change needed.
- **VALIDATE**: `php -l app/Filament/Resources/Users/Schemas/UserForm.php`

### Task 9: CREATE `tests/Feature/BibleSchoolAccessTest.php`

- **ACTION**: Pest feature tests. Use **unique** helper names, because Pest test files share the global function namespace and `abcEnrolmentRecord` already exists.
- **SETUP**: `beforeEach`: `Filament::setCurrentPanel('admin'); Storage::fake('local'); Notification::fake();`. Helpers:
  - `bibleSchoolLogin(array $overrides = []): User`: `User::create([...'role' => UserRole::MEMBER, 'access_level' => AccessLevel::BIBLE_SCHOOL])`.
  - `bibleSchoolEnrolment(?array $reference = null): AbcEnrolment`: copy the fields from `pastorReferenceEnrolmentRecord`.
- **TESTS**:
  1. `a Bible School login gets into the admin panel`: `canAccessPanel(Filament::getPanel('admin'))` is true; the label is "Bible School (ABC enrolments only)"; `getOptions()` has `bible_school`.
  2. `it can read enrolments and ask for a pastor's reference, but not create, edit or delete them`:
     - `can('viewAny')`, `can('view')` and `can('emailPastor', $e)` are true;
     - `create`, `update`, `delete` and `deleteAny` are false.
  3. `ABC Enrolments is the only resource it can open`: `actingAs`, then for each `Filament::getPanel('admin')->getResources()`, expect `$r::canViewAny()` to be `$r === AbcEnrolmentResource::class`. This automatically covers resources added later.
  4. `every other admin list refuses it by URL`: for each resource except ABC with an `index` page, `get($r::getUrl('index'))->assertForbidden()`; also `get(ManageSiteSettings::getUrl())->assertForbidden()`.
  5. `the dashboard opens, without the attendance widgets`: `get('/admin')->assertOk()`; the three widgets' `canView()` are false; as a national user they are true.
  6. `it sees the list and the whole enrolment, the pastor's answers included`:
     - an enrolment with a submitted reference (answers as in the fixture above);
     - `get('/admin/abc-enrolments')->assertOk()->assertSee('Mere Tūhoe')`;
     - `Livewire::test(ViewAbcEnrolment::class, …)->assertSeeInOrder(['Approved', 'Faithful in attendance to services', 'Faithful.'])`;
     - `assertActionHidden('delete')`.
  7. `it can download a transcript`: mirror `AbcEnrolmentAdminTest` around :140-160 (fake `local` disk file + `transcript_path`) → `assertActionVisible('downloadTranscript')`.
  8. `it can email the pastor`: `callAction('emailPastor', data: [...])->assertHasNoActionErrors()`; `Notification::assertSentOnDemand(AbcPastorReferenceRequest::class, …)`.
  9. `church-scoped lists come back empty, not broken`: `actingAs`; `ChurchResource::getEloquentQuery()->count()`, plus the same for UserResource and AttendanceResource, are `0` (no `UnhandledMatchError`).
  10. `a regional presbyter cannot view or edit a Bible School login`:
      - region + church in that region;
      - a regional user (`region_id`), and a Bible School user with `church_id` in that region;
      - `$regional->can('update', $bs)` and `can('view', $bs)` are false;
      - control: the same regional user CAN update a local user of that church (proves the test setup is real).
  11. `a national administrator can give a login Bible School access`:
      - `Livewire::test(EditUser::class, ['record' => $u->getRouteKey()])->fillForm(['access_level' => 'bible_school'])->call('save')->assertHasNoFormErrors()`;
      - `$u->fresh()->access_level` is `AccessLevel::BIBLE_SCHOOL`.
- **GOTCHA**:
  - Login responses: `actingAs` bypasses login. Use `assertForbidden()` (Filament returns 403 for policy denial on resource pages).
  - `EventResource` adds `NationalOrRegionalOnly` middleware, which blocks only local, so the Bible School request reaches the policy and gets 403, not 404. If a 404 shows up, read the middleware before changing the test.
  - Make the `delete` action name match Filament's (`DeleteAction::make()` → `'delete'`).
- **VALIDATE**: `sudo -u www-data php artisan test --compact tests/Feature/BibleSchoolAccessTest.php`. All pass.

### Task 10: LINT + FULL SUITE + OWNERSHIP

- **IMPLEMENT**:
  ```bash
  cd /var/www/personal/upci.co.nz
  vendor/bin/pint app/Enums/AccessLevel.php app/Models/User.php app/Policies/AbcEnrolmentPolicy.php app/Policies/AttendancePolicy.php app/Policies/ChurchPolicy.php app/Policies/UserPolicy.php app/Filament/Concerns/ScopesToAccessLevel.php app/Filament/Widgets/MainStatsWidget.php app/Filament/Widgets/AttendanceTrendChart.php app/Filament/Widgets/CategoryBreakdownWidget.php app/Filament/Resources/AbcEnrolments/Pages/ViewAbcEnrolment.php app/Filament/Resources/Users/Schemas/UserForm.php tests/Feature/BibleSchoolAccessTest.php
  sudo -u www-data php artisan test --compact
  chown -R www-data:www-data storage bootstrap/cache
  ```
- **GOTCHA**:
  - Pint only the listed files. Other uncommitted files belong to earlier, unrelated work.
  - Run tests as www-data, because root-run tests leave root-owned storage that breaks uploads.
- **VALIDATE**: Pint reports fixed/clean. The full suite has the baseline count + the new tests, with 0 failures.

---

## Testing Strategy

| Test File | Test Cases | Validates |
|-----------|-----------|-----------|
| `tests/Feature/BibleSchoolAccessTest.php` (new) | 11 tests above | New level, every gate, widgets, regional-escalation fix, assignability |
| `tests/Feature/AbcEnrolmentAdminTest.php` (unchanged) | national read/delete, regional/local 403 | No regression on enrolment gates |
| `tests/Feature/AbcPastorReferenceAdminTest.php` (unchanged) | national email flow | `authorize('emailPastor')` keeps national working |
| `tests/Feature/PanelAccessGateTest.php`, `DashboardTest.php`, `AccessLevelScopingTest.php`, `SecurityRegressionTest.php`, `*OwnDataOnlyTest.php` (unchanged) | existing levels | `hasChurchScope()` swap is behaviour-identical for null/local/regional/national |

### Edge Cases Checklist
- [ ] Bible School user with a stale `church_id`/`region_id` (switched from local/regional): still sees only enrolments, because policies ignore scope columns for this level.
- [ ] Bible School user on `/admin/events` (route middleware `NationalOrRegionalOnly` blocks only local): 403 from the policy.
- [ ] Bible School user editing their own record via URL: `UserPolicy::viewAny` is false, so resource pages are refused before record-level `view`/`update` self-access matters.
- [ ] Dashboard widgets render lazily: covered by direct `canView()` assertions.
- [ ] Regional presbyter vs Bible School login in their region: refused (Task 4).
- [ ] Bulk delete on the enrolments table: hidden (`deleteAny` false).
- [ ] Reference already submitted: `emailPastor` hidden for everyone (existing `visible`).
- [ ] Null access level: still refused at the panel gate (unchanged).

---

## Validation Commands

### Level 1: STATIC_ANALYSIS
```bash
cd /var/www/personal/upci.co.nz
for f in app/Enums/AccessLevel.php app/Models/User.php app/Policies/*.php app/Filament/Concerns/ScopesToAccessLevel.php app/Filament/Widgets/*.php app/Filament/Resources/AbcEnrolments/Pages/ViewAbcEnrolment.php app/Filament/Resources/Users/Schemas/UserForm.php; do php -l "$f" >/dev/null || echo "LINT FAIL $f"; done
vendor/bin/pint --test <the 13 files from Task 10>
/usr/bin/grep -rn "(bool) \$user->access_level" app/Policies   # expect no output
```
**EXPECT**: no "LINT FAIL", Pint clean, grep empty.

### Level 2: UNIT/FEATURE (new)
```bash
sudo -u www-data php artisan test --compact tests/Feature/BibleSchoolAccessTest.php
```

### Level 3: FULL_SUITE
```bash
sudo -u www-data php artisan test --compact
```
**EXPECT**: baseline + 11 passing, 0 failures. No frontend build is needed; nothing in `resources/js` changes.

### Level 4: DATABASE_VALIDATION
None. No schema change. `users.access_level` is `string NULL` (`database/migrations/2026_04_20_100003_add_access_level_and_region_id_to_users_table.php:13`). Optional read-only check: `sqlite3 upci "PRAGMA table_info(users);" | /usr/bin/grep access_level`.

### Level 5: BROWSER_VALIDATION (after the owner creates the login)
1. As the national admin, open https://upci.co.nz/admin/users/create and check that the Access Level select shows "Bible School (ABC enrolments only)".
2. Sign in as the Bible School login and check:
   - the sidebar shows only "ABC Enrolments";
   - the dashboard shows no attendance stats;
   - an enrolment opens with the "Pastor's reference" section;
   - there is no Delete button;
   - `/admin/churches` gives 403.

### Level 6: MANUAL_VALIDATION
- `sudo -u www-data php artisan route:list --name=abc-enrolments`: unchanged (index + view only).
- `tail -n 50 storage/logs/laravel.log` after the browser pass: no `UnhandledMatchError`.

---

## Acceptance Criteria

- [ ] "Bible School (ABC enrolments only)" is assignable in Admin › Users by a national admin.
- [ ] A Bible School login sees and opens ABC Enrolments (list + full record, pastor's reference included), can download transcripts and email the pastor, and cannot delete.
- [ ] Every other resource and custom page is hidden and returns 403; the dashboard loads without attendance widgets.
- [ ] Regional presbyters cannot view/edit a Bible School login.
- [ ] National/regional/local behaviour unchanged; full suite green; Pint clean on changed files.
- [ ] Nothing deleted; backup taken; no commit unless the owner asks.

## Completion Checklist

- [ ] Task 0 backup + baseline recorded
- [ ] Tasks 1-8 done in order, each validated
- [ ] Task 9 tests pass
- [ ] Task 10 Pint + full suite + chown
- [ ] Owner told how to assign the level (Admin › Users › Access Level), and that pastor emails won't actually send until email is set up

---

## Risks and Mitigations

| Risk | Likelihood | Impact | Mitigation |
|------|-----------|--------|------------|
| A gate missed: Bible School sees or crashes on another screen | MED | HIGH (PII/ops) | Test 3 iterates **every** registered resource; test 4 hits every index URL; widgets asserted directly |
| `hasChurchScope()` swap changes existing behaviour | LOW | HIGH | Identical truth table for null/local/regional/national; existing scoping/security suites must stay green |
| Edits go live mid-implementation (tree served by FPM) | MED | LOW | Tasks are individually safe: no user holds `bible_school` until the owner assigns it; enum arms (Task 1) land before anything can render the label |
| OPcache serving stale classes after edits | LOW | LOW | Earlier same-session edits took effect without an FPM reload; if not, `systemctl reload php8.4-fpm` (server action: ask first) |
| Regional escalation via `UserPolicy::update` | LOW (list scope already hides them) | HIGH | `outranks()` excludes Bible School targets + test 10 |
| Owner expected Bible School to also delete or see contact messages | LOW | LOW | Called out in NOT Building; one-line follow-ups |

---

## Notes

- **Owner Q&A, 2026-11-02.**
  - The owner asked what "Email pastor" is. It is the endorsement: the pastor's reference questionnaire.
  - I recommended Bible School logins get it, because chasing endorsements is Bible School work.
  - The plan includes it. If the owner prefers view-only, change `AbcEnrolmentPolicy::emailPastor` to `return $user->isNational();`. That one line is the whole change, and the test 8 expectation flips.
- **How the owner makes the login (after implementation).** Admin › Users › New user:
  - Name, email, password;
  - Access Level: **Bible School (ABC enrolments only)**;
  - Role: any (e.g. Member);
  - Church: leave empty.

  Password-reset emails won't arrive while mail is log-only, so set the password in the form.
- **Why not a role.** `UserRole` is the person's church role and drives nothing in authorisation since the access-level refactor. Access level is the authorisation axis.
- **Future levels.** Because of `hasChurchScope()`, any further level (e.g. a department-only login) starts with nothing, and the exhaustive `match` blocks force a deliberate decision.

## References

- [Filament 4.x: Resources › Authorization](https://filamentphp.com/docs/4.x/resources/overview#authorization)
  - KEY_INSIGHT: `viewAny()` hides a resource from navigation. In source, `Resource::canAccess()` returns `canViewAny()`, and resource pages `abort_unless(canAccess(), 403)` on mount **and hydrate**, so a denied resource gives 403 on every page and on later Livewire requests. Bulk delete checks `deleteAny()`.
  - GOTCHA: **A model with no policy, or a policy missing the method, is allowed by default.** All 12 resource models here have policies (`app/Policies/*Policy.php`), and test 3 iterates every registered resource, so a future resource without a policy fails the suite instead of leaking.
  - GOTCHA: Never set `$shouldSkipAuthorization` / `skipAuthorization()` on these resources.
  - APPLIES_TO: Tasks 3-4, test 3-4.
- [Filament 4.x: Custom pages › Authorization](https://filamentphp.com/docs/4.x/navigation/custom-pages#authorization)
  - KEY_INSIGHT: Static `canAccess()` both hides the nav item and 403s the URL. It defaults to `true` for every panel user, which is why the Dashboard stays reachable. `ManageSiteSettings` already overrides it to national-only.
  - APPLIES_TO: test 4, NOT Building (dashboard).
- [Filament 4.x: Widgets](https://filamentphp.com/docs/4.x/widgets/overview)
  - KEY_INSIGHT: Static `canView()` runs before `mount()`, including for lazy widgets and later Livewire updates (403 when denied). Dashboards filter denied widgets out.
  - GOTCHA: Keep sensitive work out of `boot()`/`placeholder()`. These widgets do their queries in `getStats()`/`getData()`, so they are fine.
  - APPLIES_TO: Task 6, test 5.
- [Filament 4.x: Actions › Authorization using a policy](https://filamentphp.com/docs/4.x/actions/overview#authorization-using-a-policy)
  - KEY_INSIGHT: `->authorize('ability')` checks the policy, and a failed check **hides** the action by default. In source (`CanBeAuthorized.php:33-85`, vendor 4.15.1), the record is prepended to the arguments.
  - APPLIES_TO: Task 7.
- [Filament 4.x: Global search](https://filamentphp.com/docs/4.x/resources/global-search)
  - KEY_INSIGHT: `canGloballySearch()` requires `canAccess()`, and the search query uses `getEloquentQuery()`. No resource here is globally searchable (no `recordTitleAttribute`), so it is n/a, but Task 5's arm keeps it safe if that changes.
- [Filament 4.x: Users › Authorizing access to the panel](https://filamentphp.com/docs/4.x/users/overview#authorizing-access-to-the-panel)
  - KEY_INSIGHT: `canAccessPanel()` is the door. It is unchanged, since `(bool) access_level` already admits the new case.
- Filament post-login redirect (source-derived; not in the v4 docs)
  - KEY_INSIGHT: `LoginResponse` returns `redirect()->intended(Filament::getUrl())`. `Panel::getUrl()` uses the `{panel}.home` route if it exists, otherwise the panel path.
  - Verified on this install with `route:list`: there is **no** `filament.admin.home` route, and `admin` = `filament.admin.pages.dashboard`. So every login lands on the Dashboard, and denying the Dashboard to Bible School would 403 them on arrival. Hence widgets are gated with `canView()`, not the page.
- [Laravel 12.x: Authorization › Policy discovery](https://laravel.com/docs/12.x/authorization#policy-discovery) and [customising the HTTP response](https://laravel.com/docs/12.x/authorization#customizing-the-http-response-status)
  - KEY_INSIGHT: Policies are auto-discovered by `{Model}Policy`. A denial gives 403. There is no `Gate::before` in this app (audit grep of `app/`, `bootstrap/`, `config/`).
- [PHP: match](https://www.php.net/manual/en/control-structures.match.php)
  - KEY_INSIGHT: A `match` must be exhaustive, or it throws `UnhandledMatchError`. That is why Task 1 adds arms to both enum methods and Task 5 adds the trait arm, with no `default`, so future levels also fail loudly.
