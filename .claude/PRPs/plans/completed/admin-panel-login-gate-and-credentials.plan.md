# Feature: Admin Panel Login Gate + Credentials for Local/Regional Users

## Summary

Close the login-side gap in the access-level system: stop users with `access_level = NULL` from reaching the admin panel (today they can log in but see only blank lists because of the default-deny trait), enable Filament's built-in password-reset flow so local and regional users can set and recover their own credentials, and assign sensible `access_level` values to the 15 existing users so each sees only what they should. The scoping trait and policies are already in place — this plan is strictly about **who can get through the login door and with what credentials**.

## User Story

As a **UPCI NZ local pastor or regional presbyter**
I want **to log in to `/admin` with my own password and see only the data I'm responsible for**
So that **I can manage my church (or region) without help from the national administrator, and without seeing or touching data that belongs to other congregations**.

## Problem Statement

Today any user with a valid password can reach `/admin` regardless of their `access_level`. The downstream scoping trait (`ScopesToAccessLevel`) default-denies when `access_level = NULL`, so those users end up staring at blank resource lists — a broken-feeling UX that is indistinguishable from a bug.

Separately, there is no credential path for anyone except `admin@upci.co.nz`:
- The seeder creates only the admin (`DatabaseSeeder.php:18-21`).
- The other 14 users were inserted later (via Filament, presumably) and share the factory default password `'password'` (`UserFactory.php:30`) — a security issue.
- Laravel's `/forgot-password` route is redirected to `/admin/*` (`routes/auth.php:7-12`), but Filament's panel config does NOT call `->passwordReset()` (`AdminPanelProvider.php:31-64`), so the forgot-password page 404s.

Concrete symptoms an operator can reproduce right now:
- Log in as any user with `access_level = NULL` (e.g. `jane@example.com`, role=member) → reach `/admin/dashboard` → all resource tiles show 0 rows, every list is empty.
- Click "forgot password" on `/admin/login` → no such link exists; the route `/forgot-password` returns 404.
- Database shows: 9 users with `access_level = NULL`, 6 local, 1 national, 0 regional.

Testable success signals:
- User with `access_level = NULL` hitting `/admin/login` with correct password → redirected back with "You are not authorized to access the admin panel."
- User with `access_level = LOCAL, church_id = 5` logs in → sees Churches (1 row), Users (only users of church 5), Attendances (only church 5) — **nothing else in nav**.
- User clicks "Forgot password" on the login page → receives an email with a reset link that works.
- National admin in `/admin/users` can edit a user's `access_level` and `region_id` directly in the form.

## Solution Statement

Three minimal pieces:

1. **Implement `FilamentUser::canAccessPanel()`** on `App\Models\User` — returns `true` only when `access_level` is non-null. This is the single gate that blocks NULL-access users at the login boundary (before any resource loads).
2. **Enable password reset in the panel config** via `->passwordReset()` on `AdminPanelProvider`. Filament handles the routes, views, token storage (using Laravel's existing `password_reset_tokens` table), and emails. No custom controllers required.
3. **One-off data hygiene migration** that assigns `access_level` to existing users based on role (pastors → local if they have a `church_id`; otherwise NULL = blocked) + a console command `users:reset-password {email}` so the national admin can trigger a password-reset email for any user from the CLI (safer than sharing the factory default password by hand).

No changes to the scoping trait, policies, or any resource. The existing enforcement layer already does the right thing once access_level is set correctly and the gate is closed.

## Metadata

| Field            | Value                                                  |
| ---------------- | ------------------------------------------------------ |
| Type             | ENHANCEMENT (+ security fix)                           |
| Complexity       | LOW-MEDIUM                                             |
| Systems Affected | User model, AdminPanelProvider, data migration, seeder |
| Dependencies     | Laravel 11, Filament v4 (both already present)         |
| Estimated Tasks  | 7                                                      |

---

## UX Design

### Before State
```
╔═══════════════════════════════════════════════════════════════════════════════╗
║                              BEFORE STATE                                      ║
╠═══════════════════════════════════════════════════════════════════════════════╣
║                                                                               ║
║   User visits /admin/login                                                    ║
║        │                                                                      ║
║        ▼                                                                      ║
║   ┌────────────────────────────────────────────────────────────┐             ║
║   │ Filament login form (no "forgot password" link)             │             ║
║   └────────────────────────────────────────────────────────────┘             ║
║        │  (any valid email + password, incl. factory default 'password')      ║
║        ▼                                                                      ║
║   ┌────────────────────────────────────────────────────────────┐             ║
║   │ /admin/dashboard — ALL users reach here regardless of role  │             ║
║   │                                                              │             ║
║   │   access_level = NULL   → empty lists everywhere            │             ║
║   │   access_level = LOCAL  → properly scoped lists             │             ║
║   │   access_level = NATIONAL → everything                       │             ║
║   └────────────────────────────────────────────────────────────┘             ║
║                                                                               ║
║   PAIN_POINTS:                                                                ║
║    - NULL-access users log in successfully but can't do anything             ║
║    - No way to know why (feels like a bug, not a permission)                 ║
║    - Every user still has factory default password 'password'                ║
║    - No password reset flow exists                                           ║
║    - National admin must share passwords manually if at all                  ║
║    - /forgot-password 404s (route is redirected but no handler)              ║
║                                                                               ║
║   DATA_FLOW (login):                                                         ║
║     POST /admin/login → auth()->attempt() → session cookie set              ║
║       → redirect to /admin → Filament renders dashboard → blank              ║
║                                                                               ║
╚═══════════════════════════════════════════════════════════════════════════════╝
```

### After State
```
╔═══════════════════════════════════════════════════════════════════════════════╗
║                               AFTER STATE                                      ║
╠═══════════════════════════════════════════════════════════════════════════════╣
║                                                                               ║
║   User visits /admin/login                                                    ║
║        │                                                                      ║
║        ▼                                                                      ║
║   ┌────────────────────────────────────────────────────────────┐             ║
║   │ Filament login form with "Forgot password?" link            │             ║
║   └────────────────────────────────────────────────────────────┘             ║
║        │                                                                      ║
║        ├──► clicks "Forgot password" ──► /admin/password-reset/request       ║
║        │    → email with token sent via Laravel's mail                        ║
║        │    → user clicks link, sets new password                             ║
║        │                                                                      ║
║        ▼  submits credentials                                                 ║
║   ┌────────────────────────────────────────────────────────────┐             ║
║   │ Filament calls User::canAccessPanel($panel)                 │             ║
║   │   access_level is NULL → reject with toast                  │             ║
║   │   access_level is LOCAL/REGIONAL/NATIONAL → allow           │             ║
║   └────────────────────────────────────────────────────────────┘             ║
║        │                                                                      ║
║        ▼                                                                      ║
║   ┌────────────────────────────────────────────────────────────┐             ║
║   │ /admin/dashboard — shown only to authorised users          │             ║
║   │                                                              │             ║
║   │   LOCAL  → nav shows resources where their church has data │             ║
║   │   REGIONAL → nav shows resources spanning their region     │             ║
║   │   NATIONAL → nav shows all resources                        │             ║
║   │   (unchanged — policies + trait already in place)           │             ║
║   └────────────────────────────────────────────────────────────┘             ║
║                                                                               ║
║   CLI TOOL (national admin):                                                  ║
║     php artisan users:reset-password pastor@example.com                       ║
║       → generates reset link, sends email                                     ║
║                                                                               ║
║   VALUE_ADDS:                                                                 ║
║    - NULL-access users blocked at the door, with a clear message             ║
║    - Local/regional users get a self-service password path                   ║
║    - National admin has a one-command way to (re)issue credentials           ║
║    - Factory-default passwords become unusable (everyone resets)             ║
║                                                                               ║
║   DATA_FLOW (login, local user):                                             ║
║     POST /admin/login → auth()->attempt()                                    ║
║       → User::canAccessPanel($panel) returns true                           ║
║       → redirect /admin → policies/trait scope every list                    ║
║                                                                               ║
║   DATA_FLOW (login, NULL-access user):                                       ║
║     POST /admin/login → auth()->attempt() (credentials valid)               ║
║       → User::canAccessPanel($panel) returns false                           ║
║       → Filament throws 403 with message                                     ║
║       → auth()->logout() → redirect /admin/login with error                  ║
║                                                                               ║
╚═══════════════════════════════════════════════════════════════════════════════╝
```

### Interaction Changes

| Location | Before | After | User Impact |
|----------|--------|-------|-------------|
| `/admin/login` | No forgot-password link | "Forgot your password?" link visible | Self-service password reset |
| `/admin/login` success with `access_level=NULL` | Reaches empty dashboard | 403 with "You don't have access to the admin panel" | Clear signal, not a ghost login |
| `/admin/password-reset/request` | 404 | Renders Filament's request form | Functional flow |
| `/admin/password-reset/reset/{token}` | 404 | Renders reset form, updates password | Functional flow |
| CLI | No command | `php artisan users:reset-password email@x.com` | Admin has fallback when mail queue is broken |
| `/admin/users/create` (national) | `access_level` field visible | Unchanged | No change — field was already in `UserForm.php` per previous plan |

---

## Mandatory Reading

| Priority | File | Lines | Why Read This |
|----------|------|-------|---------------|
| P0 | `app/Models/User.php` | 1-128 | Class to add `FilamentUser` interface + `canAccessPanel` to |
| P0 | `app/Providers/Filament/AdminPanelProvider.php` | 29-64 | Panel config to add `->passwordReset()` to |
| P0 | `app/Filament/Concerns/ScopesToAccessLevel.php` | 1-42 | Confirm the trait's default-deny on NULL — explains why the gate is enough |
| P1 | `app/Policies/ChurchPolicy.php` | 1-47 | Example of existing policy using `access_level` — no changes, just context |
| P1 | `database/factories/UserFactory.php` | 1-40 | Source of the shared default password; need to know what the current password hash is for the hygiene step |
| P1 | `config/auth.php` | 38-95 | Confirms `passwords.users` provider config used by reset flow |
| P2 | `routes/auth.php` | 1-20 | Current redirect chain — confirm it no longer collides with Filament's reset routes |
| P2 | `.env` | whole | Confirm `MAIL_MAILER`/`MAIL_FROM_ADDRESS` — password reset needs working mail |
| P2 | `database/migrations/0001_01_01_000000_create_users_table.php` | whole | Contains `password_reset_tokens` table definition — no migration needed |

**External Documentation:**

| Source | Section | Why Needed |
|--------|---------|------------|
| [Filament v4 Panel — Authentication](https://filamentphp.com/docs/4.x/users/overview#authorizing-access-to-the-panel) | `canAccessPanel` | Exact interface to implement — single-method `canAccessPanel(Panel $panel): bool` |
| [Filament v4 Panel — Password reset](https://filamentphp.com/docs/4.x/users/resetting-passwords) | `->passwordReset()` | Enables /password-reset/request and /password-reset/reset/{token} routes; reuses Laravel's password broker |
| [Laravel 11 — Password Reset](https://laravel.com/docs/11.x/passwords#resetting-passwords) | "Password Broker" | Under-the-hood details for the `users:reset-password` artisan command — use `Password::broker()->sendResetLink(['email' => $email])` |
| [Laravel 11 — Writing Commands](https://laravel.com/docs/11.x/artisan#writing-commands) | Generating, arguments | Standard `make:command` pattern for `app/Console/Commands/ResetUserPasswordCommand.php` |

---

## Patterns to Mirror

**FILAMENT_USER_INTERFACE (new pattern — none in this codebase yet):**
```php
// TARGET PATTERN: from Filament v4 docs
// file: app/Models/User.php
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;

class User extends Authenticatable implements FilamentUser
{
    // ... existing ...

    public function canAccessPanel(Panel $panel): bool
    {
        return (bool) $this->access_level;
    }
}
```

**PANEL_CONFIG_EXTENSION:**
```php
// SOURCE: app/Providers/Filament/AdminPanelProvider.php:31-36
// BEFORE:
return $panel
    ->default()
    ->id('admin')
    ->path('admin')
    ->login()
    ->colors([...])

// AFTER — add one line:
return $panel
    ->default()
    ->id('admin')
    ->path('admin')
    ->login()
    ->passwordReset()   // <-- new
    ->colors([...])
```

**ACCESS_LEVEL_HELPERS (already in place, for reference):**
```php
// SOURCE: app/Models/User.php:96-109
public function isLocal(): bool    { return $this->access_level === AccessLevel::LOCAL; }
public function isRegional(): bool { return $this->access_level === AccessLevel::REGIONAL; }
public function isNational(): bool { return $this->access_level === AccessLevel::NATIONAL; }
```

**MIGRATION_STYLE (for the hygiene data migration):**
```php
// SOURCE: previous access-level migration — database/migrations/2026_04_20_100003_add_access_level_and_region_id_to_users_table.php
// MIRROR this inline-data-update pattern:
DB::table('users')->whereNull('access_level')->whereNotNull('church_id')->update(['access_level' => 'local']);
```

**ARTISAN_COMMAND_SHAPE (bare — no existing custom commands in this repo):**
```php
// NEW PATTERN — from Laravel 11 docs
namespace App\Console\Commands;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Password;

class ResetUserPasswordCommand extends Command
{
    protected $signature = 'users:reset-password {email}';
    protected $description = 'Send a password-reset email to the given user';

    public function handle(): int
    {
        $email = $this->argument('email');
        $status = Password::broker()->sendResetLink(['email' => $email]);
        $this->line(__($status));
        return $status === Password::RESET_LINK_SENT ? self::SUCCESS : self::FAILURE;
    }
}
```

---

## Files to Change

### Create

| File | Purpose |
|------|---------|
| `database/migrations/2026_04_24_100001_assign_access_level_to_existing_users.php` | One-off: pastors with `church_id` → local; national admin email → national; everyone else left NULL (blocked at login). Documents the mapping so a re-seed produces identical state. |
| `app/Console/Commands/ResetUserPasswordCommand.php` | CLI: `php artisan users:reset-password <email>` sends a reset link. |

### Update

| File | Change |
|------|--------|
| `app/Models/User.php` | `implements FilamentUser` + `use Filament\Panel` + add `canAccessPanel(Panel $panel): bool` returning `(bool) $this->access_level`. |
| `app/Providers/Filament/AdminPanelProvider.php` | Insert `->passwordReset()` between `->login()` and `->colors([...])`. |

### NOT touching

- `ScopesToAccessLevel` trait, all 11 policies, any resource file — the scoping is correct; problem is upstream of them.
- `routes/auth.php` — Filament registers its own reset routes under `/admin/password-reset/*`, which don't collide with the `/forgot-password` → `/admin/password-reset/request` redirect. Leave the redirect as-is; it now lands on a real page.
- `UserFactory.php` — the factory stays for tests. Only the hygiene migration rewrites existing rows.

---

## NOT Building (Scope Limits)

- **A public-facing Vue login page.** Out of scope (was option 3 in the triage). Nothing in `resources/js/` changes.
- **API authentication (Sanctum).** `/api/*` stays public — the Vue frontend depends on it being open.
- **Email domain whitelisting or 2FA.** Can be added later via `canAccessPanel()` extension; not needed today.
- **A registration flow.** National admin creates users via Filament; the CLI/email reset gets them a password. Self-service signup would let anyone claim an account.
- **Cleaning up the 9 NULL-access users.** The migration *leaves them NULL intentionally*: a Member or Usher role doesn't need admin access. They remain in the DB for attendance records or future use but can't log in to `/admin`. Deleting them is a separate decision.
- **Rotating the factory-default password hash for existing users.** Once `->passwordReset()` is live, the CLI command is the mechanism — one by one, as users are onboarded. A bulk "invalidate all passwords" step is a one-line SQL that the operator can run at their discretion; not this plan's job.
- **SSO / OAuth.** Out of scope.

---

## Step-by-Step Tasks

Execute in order. Every task has an executable verification step.

### Task 1: UPDATE `app/Models/User.php` — implement `FilamentUser`

- **ACTION**: Add `use Filament\Models\Contracts\FilamentUser;` and `use Filament\Panel;` at the top, change `class User extends Authenticatable` to `class User extends Authenticatable implements FilamentUser`, and add the `canAccessPanel` method.
- **IMPLEMENT**:
  ```php
  public function canAccessPanel(Panel $panel): bool
  {
      return (bool) $this->access_level;
  }
  ```
- **MIRROR**: None in codebase — new interface. Pattern from Filament docs.
- **GOTCHA**: `access_level` is already cast to `AccessLevel` enum (`app/Models/User.php:56`). Casting `null` stays `null`, any enum value is truthy — `(bool)` works for both. Don't replace with `$this->access_level !== null` — it's equivalent but less idiomatic; match the `viewAny` style in `ChurchPolicy.php:12`.
- **VALIDATE**:
  ```bash
  cd /var/www/personal/upci.co.nz
  sudo -u www-data php -r "
  require 'vendor/autoload.php';
  \$app = require 'bootstrap/app.php';
  \$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
  \$u = App\Models\User::where('email','admin@upci.co.nz')->first();
  echo 'admin canAccess: '; var_dump(\$u->canAccessPanel(new Filament\Panel()));
  \$m = App\Models\User::where('role','member')->first();
  echo 'member canAccess: '; var_dump(\$m->canAccessPanel(new Filament\Panel()));
  "
  ```
  **EXPECT**: admin → `bool(true)`, member → `bool(false)`.

### Task 2: UPDATE `app/Providers/Filament/AdminPanelProvider.php` — enable password reset

- **ACTION**: Add `->passwordReset()` to the fluent chain.
- **IMPLEMENT**: Insert one line between `->login()` (line 35) and `->colors(...)` (line 36):
  ```php
  ->login()
  ->passwordReset()
  ->colors([
  ```
- **GOTCHA**: Filament reads from `config('auth.passwords.users')` automatically. Confirm `config/auth.php:63-68` has the default `users` broker pointing at the `password_reset_tokens` table — already there.
- **VALIDATE**:
  ```bash
  sudo -u www-data php artisan route:list | grep password-reset
  ```
  **EXPECT**: Two new routes — `/admin/password-reset/request` and `/admin/password-reset/reset/{token}`.

### Task 3: CONFIRM mail config — `.env`

- **ACTION**: Read `.env` and confirm `MAIL_MAILER`, `MAIL_FROM_ADDRESS` are set. If not set, document that Task 2 is a no-op until they are.
- **VALIDATE**:
  ```bash
  grep -E "^MAIL_(MAILER|FROM_ADDRESS|FROM_NAME)" /var/www/personal/upci.co.nz/.env
  ```
  **EXPECT**: At minimum `MAIL_MAILER=log` for local dev (reset link appears in `storage/logs/laravel.log`) OR real SMTP creds. If neither, note that password reset will silently fail and ask the operator to configure before rolling out.
- **NOTE**: If `MAIL_MAILER=log`, that's fine for now — the national admin running `php artisan users:reset-password email@x.com` (Task 6) can pull the reset link out of `storage/logs/laravel.log` and hand it over manually. This is deliberately lo-fi.

### Task 4: CREATE `database/migrations/2026_04_24_100001_assign_access_level_to_existing_users.php`

- **ACTION**: Map existing users to sensible `access_level` values. Idempotent: only touches rows still NULL.
- **IMPLEMENT**:
  ```php
  public function up(): void {
      // Senior pastors + pastors who already have a church_id → local
      DB::table('users')
          ->whereNull('access_level')
          ->whereNotNull('church_id')
          ->whereIn('role', ['pastor', 'senior_pastor', 'assistant_pastor'])
          ->update(['access_level' => 'local']);

      // Explicitly leave NULL for: members, ushers, deacons, elders without context.
      // They retain accounts (for attendance FKs) but cannot log in to /admin.
      // National admins must still be assigned manually via Filament.
  }

  public function down(): void {
      // Reverse only the rows we set above — don't wipe pre-existing values.
      DB::table('users')
          ->whereIn('role', ['pastor', 'senior_pastor', 'assistant_pastor'])
          ->whereNotNull('church_id')
          ->where('access_level', 'local')
          ->update(['access_level' => null]);
  }
  ```
- **GOTCHA**: Do NOT touch rows with an existing non-null `access_level` — several users are already `local` from the prior plan's backfill; re-setting them is a no-op but `->whereNull('access_level')` makes the intent explicit.
- **VALIDATE**:
  ```bash
  sudo -u www-data php artisan migrate --force
  sqlite3 /var/www/personal/upci.co.nz/upci "SELECT access_level, COUNT(*) FROM users GROUP BY access_level;"
  ```
  **EXPECT**: `local` count increases (or stays at 6 if all pastors already had it), NULL count shrinks to just members/ushers/deacons, national stays at 1.

### Task 5: CREATE `app/Console/Commands/ResetUserPasswordCommand.php`

- **ACTION**: Thin wrapper around Laravel's password broker.
- **IMPLEMENT**:
  ```php
  <?php
  namespace App\Console\Commands;

  use Illuminate\Console\Command;
  use Illuminate\Support\Facades\Password;

  class ResetUserPasswordCommand extends Command
  {
      protected $signature = 'users:reset-password {email}';
      protected $description = 'Send a password-reset email to the given user';

      public function handle(): int
      {
          $email  = $this->argument('email');
          $status = Password::broker()->sendResetLink(['email' => $email]);

          if ($status === Password::RESET_LINK_SENT) {
              $this->info("Reset link sent to {$email}.");
              return self::SUCCESS;
          }

          $this->error(__($status));
          return self::FAILURE;
      }
  }
  ```
- **MIRROR**: None — no custom commands in this repo yet. Pattern from Laravel 11 docs.
- **GOTCHA**: Laravel 11 auto-discovers commands in `app/Console/Commands/` — no kernel registration needed.
- **VALIDATE**:
  ```bash
  sudo -u www-data php artisan list | grep users:reset-password
  sudo -u www-data php artisan users:reset-password admin@upci.co.nz
  # If MAIL_MAILER=log, check storage/logs/laravel.log for the reset URL
  tail -20 /var/www/personal/upci.co.nz/storage/logs/laravel.log | grep -i password
  ```
  **EXPECT**: Command appears in artisan list; running it prints "Reset link sent to admin@upci.co.nz."; the log contains the reset URL.

### Task 6: MANUAL — Verify login gate in browser

- **ACTION**: End-to-end smoke test.
- **STEPS**:
  1. In the DB, ensure a user exists with `access_level = NULL` (several do by default: `jane@example.com`, `deacon@church.com`).
  2. Reset their password:
     ```bash
     sudo -u www-data php artisan tinker --execute="App\Models\User::where('email','jane@example.com')->update(['password' => bcrypt('testpass123')]);"
     ```
  3. Visit `/admin/login`, submit `jane@example.com` / `testpass123`.
  4. **EXPECT**: Filament rejects with a "Not authorised" message, user is not logged in (no session cookie set for /admin).
  5. Repeat with `admin@upci.co.nz` — **EXPECT**: reaches dashboard.
  6. Repeat with a LOCAL user (e.g. `andrew.kintanar@churchtriumphant.co.nz` after a password reset) — **EXPECT**: reaches dashboard, Churches list shows 1 row (their church), Users list shows only their church's users.
- **NO AUTOMATED ASSERTION**: this is a UI flow; document the result as "passed" or "failed with {description}".

### Task 7: UPDATE session summary / memory

- **ACTION**: After all prior tasks pass, append a short note to `MEMORY.md` via the auto-memory system so future sessions know the login gate is in place and the CLI exists.
- **IMPLEMENT**: Write one project memory file summarising: `canAccessPanel` gates on `access_level`; `users:reset-password` is the admin-side credential path; `->passwordReset()` is enabled in the panel.
- **VALIDATE**: `MEMORY.md` includes a one-line entry pointing to the new memory file.

---

## Testing Strategy

### Manual verification (Task 6)

The critical path is visible only in a browser; it's the one step that can't be automated without Filament's test helpers (which this codebase doesn't use yet — establishing that framework is out of scope).

### Optional: feature test

If the operator wants it, add `tests/Feature/PanelAccessGateTest.php`:
```php
test('user with null access_level cannot access panel', function () {
    $u = User::factory()->create(['access_level' => null]);
    $this->actingAs($u)->get('/admin')->assertStatus(403);
});
test('user with access_level can access panel', function () {
    $u = User::factory()->create(['access_level' => 'local', 'church_id' => Church::factory()->create()->id]);
    $this->actingAs($u)->get('/admin')->assertStatus(200);
});
```
Not in the required task list because the existing repo has no feature tests; adding the test infrastructure is its own follow-up.

### Edge Cases Checklist

- [ ] User with `access_level = NULL` → blocked at login gate (Task 6)
- [ ] User with `access_level = LOCAL` but `church_id = NULL` → allowed into panel, but trait default-denies all lists (no crash). Should this user be blocked too? See "Open questions" below.
- [ ] User with `access_level = REGIONAL` but `region_id = NULL` → same as above
- [ ] `php artisan users:reset-password nonexistent@x.com` → prints Laravel's "We can't find a user with that email address." message; exits 1
- [ ] Reset token expires after 60 minutes (Laravel default) — document, don't change
- [ ] Logging out works normally (no change to logout flow)
- [ ] Admin still lands in panel after the gate is added

---

## Validation Commands

### Level 1: Static analysis
```bash
cd /var/www/personal/upci.co.nz
vendor/bin/pint --test
```
**EXPECT**: No style violations.

### Level 2: Migrations apply
```bash
sudo -u www-data php artisan migrate --force
```
**EXPECT**: One new migration (`assign_access_level_to_existing_users`) reports DONE.

### Level 3: Routes include password-reset
```bash
sudo -u www-data php artisan route:list | grep password-reset
```
**EXPECT**: Two routes under `/admin/password-reset/`.

### Level 4: Artisan command present
```bash
sudo -u www-data php artisan list | grep users:reset-password
```
**EXPECT**: One entry.

### Level 5: Login gate manual (Task 6)
Browser walkthrough per the steps in Task 6.

### Level 6: Data hygiene spot-check
```bash
sqlite3 /var/www/personal/upci.co.nz/upci "SELECT id, email, role, access_level, church_id FROM users ORDER BY access_level;"
```
**EXPECT**:
- All `senior_pastor` + `pastor` users with a `church_id` → `access_level = local`.
- Members / ushers / deacons → `access_level = NULL` (blocked intentionally).
- Admin → `national`.

---

## Acceptance Criteria

- [ ] `User` implements `FilamentUser::canAccessPanel()`, returning true iff `access_level` is non-null.
- [ ] `AdminPanelProvider` calls `->passwordReset()`.
- [ ] `/admin/password-reset/request` and `/admin/password-reset/reset/{token}` return 200 for anonymous GET.
- [ ] NULL-access user with valid credentials → rejected at login, no session started.
- [ ] LOCAL user with valid credentials → reaches dashboard, sees only their church's data in Churches/Users/Attendances.
- [ ] NATIONAL user → unchanged: sees everything.
- [ ] `php artisan users:reset-password <email>` produces a reset link (email or log) for existing users, an error for nonexistent.
- [ ] No regressions in `php artisan test` (existing suite stays green — there is no existing suite of relevance, so this is a no-op check).

---

## Completion Checklist

- [ ] Task 1 — `User` implements `FilamentUser`
- [ ] Task 2 — `->passwordReset()` added to panel
- [ ] Task 3 — Mail config verified (or operator flagged)
- [ ] Task 4 — access-level hygiene migration run
- [ ] Task 5 — `users:reset-password` command created
- [ ] Task 6 — Browser smoke test with NULL / local / national users
- [ ] Task 7 — MEMORY.md updated

---

## Risks and Mitigations

| Risk | Likelihood | Impact | Mitigation |
|------|------------|--------|------------|
| Mail not configured → reset emails silently fail | HIGH (local dev) | MED | Task 3 verifies; if `MAIL_MAILER=log`, reset URL lands in `storage/logs/laravel.log` and the admin forwards it manually. Not pretty, but functional. |
| `canAccessPanel` reject message is invisible (toast disappears) | LOW | LOW | Filament shows a flash message by default. If it's insufficient, customise by throwing a `Filament\Auth\Exceptions\UserNotAuthorized` with a message. Not in this plan. |
| Local users with `church_id = NULL` (data error) see empty panel and don't know why | MED | LOW | They reach the panel because `access_level` is set; the trait default-denies because `church_id` is `-1`. This is by design — document that "empty panel despite being allowed in" means the user's `church_id` is missing and national admin should fix the record. |
| Existing pastors with `access_level=local, church_id=set` forget their password and never ask for a reset | LOW | MED | CLI command exists; national admin can send the link proactively. |
| Migration re-run would re-assign an already-demoted user | LOW | LOW | Migration uses `whereNull('access_level')` — if a national admin later sets someone to `NULL` deliberately (to block them), re-running the migration would undo that for pastors. Mitigation: this migration runs once; down() is scoped to "only what up() set"; if re-run is ever needed, the operator reviews. |
| A user with a valid reset token but `access_level=NULL` resets password and still can't log in | MED | LOW | Expected behaviour — resetting your password doesn't grant access. The login gate is the authorisation boundary. The reset flow succeeds, but login still rejects. Document this in user-facing email copy later. |

---

## Open Questions (for the operator, not blockers)

- **Do you want LOCAL users with `church_id=NULL` to also be rejected at login?** Today they'd enter the panel and see empty lists — same UX issue as before, just shifted to a rarer case. Fix: tighten `canAccessPanel` to `return $this->access_level !== null && match($this->access_level) { LOCAL => $this->church_id !== null, REGIONAL => $this->region_id !== null, NATIONAL => true };`. Not added to the plan by default because it adds branching for a data-integrity case; your call.
- **Should members/ushers/deacons be deleted, or kept for attendance records?** Not addressed here — their accounts coexist with the access gate silently.
- **Who sends the first password-reset to each pastor?** Operationally this plan hands the national admin a CLI. If you want a "Send welcome email" bulk action in the Filament User resource instead, that's ~30 min of extra work on `UserResource::Tables::ChurchesTable` — not included.

---

## Notes

- The reason the three changes are small and self-contained: the prior access-level plan (`user-access-levels-local-regional-national.plan.md`) did the hard work of establishing `ScopesToAccessLevel`, policies, and the `access_level` column. This plan is strictly the login-side bookend it was missing.
- Filament's `->passwordReset()` reuses Laravel's `password_reset_tokens` table (already present in `0001_01_01_000000_create_users_table.php`). No migration for tokens needed.
- The artisan command exists so that even if mail is misconfigured the national admin can always manually rescue a user by handing them the URL from the log. It's the "break glass" fallback.
- **Confidence: 9/10.** Low complexity, small blast radius, every step independently verifiable. The one unknown is real mail deliverability — handled by Task 3's gate check.
