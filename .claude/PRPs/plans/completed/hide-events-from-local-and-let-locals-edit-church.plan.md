# Feature: Hide Events from Local Users (with 404) + Let Local Users Edit Their Own Church

## Summary

Three small, orthogonal policy changes. (1) `EventPolicy::viewAny` and `view` return `false` for local users — this automatically hides the Events sidebar item for them. (2) A small route middleware on `EventResource` converts the would-be 403 into a 404 when a local user hits `/admin/events/*` directly in the URL bar, so the resource appears not to exist. (3) `ChurchPolicy::update` is extended with a local-user branch (`$church->id === $user->church_id`) so local users can finally edit their assigned church record — which is what the user's intent requires ("local churches to edit their own details"), but which the current policy explicitly forbids.

## User Story

As a **UPCI NZ local pastor (`access_level = LOCAL`)**
I want to **not see the Events admin page at all — not in the sidebar, not by guessing the URL**
And I want to **be able to edit my own church's contact details, address, and services from `/admin/churches/{my-church-id}/edit`**
So that **my admin panel only shows me things I'm authorised to manage, and I can keep my church's own information accurate without having to ask a national administrator**.

## Problem Statement

Two concrete gaps, both easily reproducible today:

1. **Events are visible to local users, against the reviewed access intent.**
   - `EventPolicy::viewAny` returns `(bool) $user->access_level` (`app/Policies/EventPolicy.php:17`), which is `true` for any access-level including LOCAL.
   - Result: a local user sees "Events" in the left nav and can open `/admin/events` → they see every event across the whole organisation (national, all regions).
   - The previous access-level plan (`user-access-levels-local-regional-national.plan.md`) treated Events as an "aggregated national view readable by everyone", but the product-level decision has now changed: local users should only see their own church's world.

2. **Local users can't edit their own church record.**
   - `ChurchPolicy::update` (`app/Policies/ChurchPolicy.php:25-36`) returns `true` only for `isNational()` or for `isRegional()` matching region_id. There is no `isLocal()` branch.
   - Result: a local pastor going to `/admin/churches/{theirChurchId}/edit` either sees a read-only form (Filament hides the Save button) or gets a 403 when they submit. They can view their church (trait scope allows it, `CanAccessChurch` allows view) but not update it.

Testable signals:
- **Before**: log in as `andrew.kintanar@churchtriumphant.co.nz` (role=senior_pastor, access_level=local, church_id=2). Nav shows Events; `/admin/events` loads with all events. `/admin/churches/2/edit` shows the form but the Save button is missing.
- **After**: nav does **not** show Events; `GET /admin/events` returns **404**; `GET /admin/events/3` (hypothetical event) returns **404**; `/admin/churches/2/edit` shows the form **with a working Save button** and saves successfully. `/admin/churches/5/edit` (different church) still returns 403.

## Solution Statement

Three localised edits, no new architecture:

1. **Tighten `EventPolicy`** — `viewAny` and `view` return `$user->isNational() || $user->isRegional()`. Filament's sidebar calls `canAccess()` → `canViewAny()` → the policy; when it's false the nav item is automatically hidden (`vendor/filament/filament/src/Resources/Resource/Concerns/HasNavigation.php:50`). Create/update/delete are already `isNational()`-only and stay as-is.

2. **Add a route middleware on `EventResource`** that short-circuits to `abort(404)` when a local user hits any Events route directly. This gives URL-typed access a 404 instead of Filament's default 403. Nav rendering is unaffected because `getRouteMiddleware()` only runs on route dispatch, not on navigation visibility checks (confirmed from `vendor/filament/filament/src/Resources/Resource/Concerns/HasRoutes.php:75`).

3. **Extend `ChurchPolicy::update`** with a final branch: `return $user->isLocal() && $church->id === $user->church_id`. Mirrors the existing national/regional shape. No form, table, or resource changes needed — the form is already rendered and the Save button simply appears when the policy allows update.

## Metadata

| Field            | Value                                                  |
| ---------------- | ------------------------------------------------------ |
| Type             | ENHANCEMENT (+ small BUG_FIX for the Church edit case) |
| Complexity       | LOW                                                    |
| Systems Affected | EventPolicy, ChurchPolicy, EventResource, 1 middleware |
| Dependencies     | Laravel 11, Filament v4 (already present)              |
| Estimated Tasks  | 4                                                      |

---

## UX Design

### Before State
```
╔═══════════════════════════════════════════════════════════════════════════════╗
║                              BEFORE STATE (local user)                         ║
╠═══════════════════════════════════════════════════════════════════════════════╣
║                                                                               ║
║   Local pastor logs in → /admin/dashboard                                     ║
║        │                                                                      ║
║        ▼                                                                      ║
║   ┌────────────────────────────────────────────────────────────┐             ║
║   │ Sidebar nav:                                                │             ║
║   │   • Dashboard                                               │             ║
║   │   • Churches (1 row, their own)                             │             ║
║   │   • Users (users of their church)                           │             ║
║   │   • Attendances (their church only)                         │             ║
║   │   • Events   ◄── VISIBLE BUT SHOULDN'T BE                   │             ║
║   │   • Departments (read-only)                                 │             ║
║   └────────────────────────────────────────────────────────────┘             ║
║        │                                                                      ║
║        ├──► clicks Events → /admin/events (200 OK, all events visible)       ║
║        │                                                                      ║
║        └──► clicks Churches > [their church] > Edit                          ║
║              → /admin/churches/2/edit                                         ║
║              → form renders but Save button is HIDDEN (policy denies update) ║
║              → local user has to ask national admin to change anything       ║
║                                                                               ║
║   PAIN_POINTS:                                                                ║
║    - Events visible when the product rule says they shouldn't be             ║
║    - No 404 mechanism — nav gate alone wouldn't stop a forged URL anyway     ║
║    - Local pastor can SEE their church details but can't EDIT them           ║
║                                                                               ║
╚═══════════════════════════════════════════════════════════════════════════════╝
```

### After State
```
╔═══════════════════════════════════════════════════════════════════════════════╗
║                               AFTER STATE (local user)                         ║
╠═══════════════════════════════════════════════════════════════════════════════╣
║                                                                               ║
║   Local pastor logs in → /admin/dashboard                                     ║
║        │                                                                      ║
║        ▼                                                                      ║
║   ┌────────────────────────────────────────────────────────────┐             ║
║   │ Sidebar nav:                                                │             ║
║   │   • Dashboard                                               │             ║
║   │   • Churches (1 row, their own) — editable!                 │             ║
║   │   • Users                                                   │             ║
║   │   • Attendances                                             │             ║
║   │   • [Events hidden — EventPolicy::viewAny = false]          │             ║
║   │   • Departments (read-only)                                 │             ║
║   └────────────────────────────────────────────────────────────┘             ║
║        │                                                                      ║
║        ├──► manually types /admin/events in URL                               ║
║        │     → Filament route middleware runs BEFORE canAccess                ║
║        │     → middleware detects isLocal() → abort(404)                      ║
║        │     → browser shows 404 Not Found (Laravel's standard page)          ║
║        │     → no leak of resource existence                                  ║
║        │                                                                      ║
║        └──► Churches → [their church] → Edit                                  ║
║              → ChurchPolicy::update returns true (new local branch)           ║
║              → Save button visible                                            ║
║              → submits, updates succeed                                       ║
║              → Filament flash: "Saved."                                       ║
║                                                                               ║
║   SECURITY_BOUNDARY:                                                          ║
║    - Nav hidden by policy (no UI surface)                                     ║
║    - Routes 404 by middleware (no existence signal)                           ║
║    - DB scope unchanged (trait still default-denies if somehow reached)       ║
║                                                                               ║
║   DATA_FLOW (URL 404 path):                                                   ║
║     GET /admin/events                                                         ║
║       → Filament admin auth middleware (user session)                         ║
║       → EventResource::getRouteMiddleware() chain runs                        ║
║       → NationalOrRegionalOnly::handle($req, $next)                           ║
║       → user->isLocal() === true → abort(404)                                 ║
║       → Laravel renders 404.blade.php                                         ║
║                                                                               ║
║   DATA_FLOW (church edit happy path):                                         ║
║     POST /admin/churches/2/edit                                               ║
║       → Filament EditChurch livewire page                                     ║
║       → Policy::update($user, $church) — new local branch returns true        ║
║       → form state validated and saved                                        ║
║       → church row updated                                                    ║
║                                                                               ║
╚═══════════════════════════════════════════════════════════════════════════════╝
```

### Interaction Changes
| Location | Before | After | User Impact |
|----------|--------|-------|-------------|
| Sidebar (local user) | Events item visible | Events item hidden | Cleaner nav, matches authorisation |
| `GET /admin/events` (local) | 200 OK, full list | 404 Not Found | Resource appears not to exist |
| `GET /admin/events/{id}` (local) | 200 OK, event detail | 404 Not Found | Same |
| `GET /admin/events/create` (local) | 403 Forbidden | 404 Not Found | Consistent 404 semantics |
| `GET /admin/churches/{own-id}/edit` (local) | Form renders, Save hidden | Form renders, Save visible and works | Local pastor can self-serve |
| `POST /admin/churches/{own-id}` (local) | 403 | 200 with success flash | Submit succeeds |
| `GET /admin/churches/{other-id}/edit` (local) | 403 | 403 | Still denied |
| Sidebar (national/regional user) | Events visible | Events visible | Unchanged |

---

## Mandatory Reading

| Priority | File | Lines | Why Read This |
|----------|------|-------|---------------|
| P0 | `app/Policies/EventPolicy.php` | 1-45 | File being tightened; see existing `(bool) $user->access_level` idiom |
| P0 | `app/Policies/ChurchPolicy.php` | 1-47 | File being extended with the local branch in `update()` |
| P0 | `app/Filament/Resources/Events/EventResource.php` | 1-56 | File being extended with `getRouteMiddleware()` |
| P1 | `vendor/filament/filament/src/Resources/Resource/Concerns/HasRoutes.php` | 70-80, 120-127 | Confirms `getRouteMiddleware(Panel $panel)` signature and how it's applied only at route-dispatch time |
| P1 | `vendor/filament/filament/src/Resources/Resource/Concerns/HasNavigation.php` | 42-55 | Confirms nav visibility uses `canAccess()` which bubbles up to the policy — so policy change is enough to hide the nav |
| P1 | `vendor/filament/filament/src/Resources/Resource/Concerns/HasAuthorization.php` | 17-20 | Confirms `canAccess()` → `canViewAny()` → policy `viewAny` |
| P2 | `app/Models/User.php` | 96-110 | `isLocal() / isRegional() / isNational()` helpers used throughout the policies |
| P2 | `tests/Feature/AccessLevelScopingTest.php` | 1-50 | Test file pattern to extend for the new EventPolicy + ChurchPolicy cases |
| P2 | `tests/Feature/PanelAccessGateTest.php` | 1-54 | Pest test pattern using `Filament::getPanel('admin')` — mirror this for any resource-level test |

**External Documentation:**

| Source | Section | Why Needed |
|--------|---------|------------|
| [Filament v4 Resources — Authorization](https://filamentphp.com/docs/4.x/resources/overview#authorization) | "Authorizing actions" | Confirms `viewAny` gates the sidebar; `update` gates the edit button |
| [Filament v4 Resources — Route middleware](https://filamentphp.com/docs/4.x/resources/overview#resource-middleware) | `$routeMiddleware` property / `getRouteMiddleware()` override | Confirms v4 API for per-resource middleware |
| [Laravel 11 — abort() helper](https://laravel.com/docs/11.x/errors#the-abort-helper) | `abort(404)` | Throws `NotFoundHttpException`; rendered as 404 by Laravel's default exception handler |

---

## Patterns to Mirror

**POLICY_METHOD (EventPolicy, existing idiom):**
```php
// SOURCE: app/Policies/EventPolicy.php:15-18
public function viewAny(User $user): bool
{
    return (bool) $user->access_level;
}
```

**POLICY_METHOD (ChurchPolicy update, existing idiom):**
```php
// SOURCE: app/Policies/ChurchPolicy.php:25-36
public function update(User $user, Church $church): bool
{
    if ($user->isNational()) {
        return true;
    }

    if ($user->isRegional()) {
        return $church->region_id === $user->region_id;
    }

    return false;
}
```

**RESOURCE_BODY (no existing `getRouteMiddleware` in this project — new pattern, from Filament v4 docs):**
```php
// TARGET PATTERN: add to EventResource
use Filament\Panel;

public static function getRouteMiddleware(Panel $panel): string|array
{
    return [
        \App\Http\Middleware\NationalOrRegionalOnly::class,
    ];
}
```

**MIDDLEWARE_SHAPE (no custom middleware exists in this app yet — standard Laravel 11 pattern):**
```php
// TARGET PATTERN: app/Http/Middleware/NationalOrRegionalOnly.php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class NationalOrRegionalOnly
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->isLocal()) {
            abort(404);
        }

        return $next($request);
    }
}
```

**TEST_STRUCTURE (Pest, existing pattern):**
```php
// SOURCE: tests/Feature/AccessLevelScopingTest.php:24-32
test('national user sees every church', function () {
    $user = User::create([
        'name' => 'N', 'email' => 'n@x', 'password' => 'x',
        'role' => UserRole::ADMINISTRATOR, 'access_level' => AccessLevel::NATIONAL,
    ]);
    $this->actingAs($user);

    expect(ChurchResource::getEloquentQuery()->count())->toBe(4);
});
```

---

## Files to Change

### Create

| File | Purpose |
|------|---------|
| `app/Http/Middleware/NationalOrRegionalOnly.php` | 404s any request where the authenticated user has `access_level = LOCAL`. Reusable for any resource that should be hidden-with-404 for local users (Events now, possibly Departments later). |
| `tests/Feature/EventAccessPolicyTest.php` | Assert: local user → `EventPolicy::viewAny` = false; national/regional = true; local user hitting `/admin/events` → 404 (via Filament test helper). |
| `tests/Feature/ChurchPolicyLocalEditTest.php` | Assert: local user can update their own church; cannot update another church; regional/national unchanged. |

### Update

| File | Change |
|------|--------|
| `app/Policies/EventPolicy.php` | `viewAny` and `view` return `$user->isNational() || $user->isRegional()`. Update docblock: Events are now national+regional aggregated; local users do not see them. |
| `app/Policies/ChurchPolicy.php` | Add final branch to `update()`: `if ($user->isLocal()) { return $church->id === $user->church_id; }` |
| `app/Filament/Resources/Events/EventResource.php` | Add `use Filament\Panel;` import + `public static function getRouteMiddleware(Panel $panel): string|array { return [NationalOrRegionalOnly::class]; }` |

### NOT touching

- `ScopesToAccessLevel` trait, other policies, other resources.
- `bootstrap/app.php` — no need to register the middleware globally; resource-scoped registration is sufficient and keeps the blast radius small.
- `ChurchForm.php` — form stays the same; local users get every field. (See "Open questions" for field-level tightening as a possible follow-up.)
- `Departments`, `Attendances`, `Users`, CMS resources — no scope change requested.

---

## NOT Building (Scope Limits)

- **Field-level edit restrictions for local church-editing.** A local pastor can technically edit `region_id`, `is_active`, and any other field on their church. If that's undesirable, add `disabled()` / `visible()` predicates on those fields in `ChurchForm.php` — out of scope here, flag as follow-up.
- **Hiding Departments for local users.** The user only asked about Events. Departments is left visible (read-only via its existing policy), same behaviour as today.
- **Changing the 404 page.** Laravel's default `404.blade.php` renders; no custom styling.
- **Rate-limiting the `/admin/events` probes.** Attackers could enumerate which resources exist by timing 403 vs 404 — not an issue here since we're returning 404 consistently, and the Filament admin panel is behind auth anyway.
- **Audit logging of blocked-URL attempts.** Out of scope. Add if operational need emerges.
- **Renaming the middleware later if scope expands.** `NationalOrRegionalOnly` reads cleanly today even though only Events consumes it; if it's later reused for Departments that semantic still holds.

---

## Step-by-Step Tasks

Execute in order. Each task is atomic and independently verifiable.

### Task 1: UPDATE `app/Policies/EventPolicy.php`

- **ACTION**: Tighten `viewAny` and `view` to exclude local users.
- **IMPLEMENT**:
  ```php
  public function viewAny(User $user): bool
  {
      return $user->isNational() || $user->isRegional();
  }

  public function view(User $user, Event $event): bool
  {
      return $user->isNational() || $user->isRegional();
  }
  ```
- **MIRROR**: `app/Policies/ChurchPolicy.php:25-36` — `isNational()` / `isRegional()` branching idiom.
- **DOCBLOCK UPDATE**: Change the top-of-file docblock (`app/Policies/EventPolicy.php:8-12`) from "all authenticated admin users can view the full list" to "only national and regional users can see events; local users are blocked at both nav and route levels."
- **GOTCHA**: Leave `create/update/delete` untouched — they already return `$user->isNational()`, which is correct.
- **VALIDATE**:
  ```bash
  php -l app/Policies/EventPolicy.php
  ```
  **EXPECT**: No syntax errors.

### Task 2: CREATE `app/Http/Middleware/NationalOrRegionalOnly.php`

- **ACTION**: Small middleware that 404s requests from local users.
- **IMPLEMENT**:
  ```php
  <?php

  namespace App\Http\Middleware;

  use Closure;
  use Illuminate\Http\Request;
  use Symfony\Component\HttpFoundation\Response;

  class NationalOrRegionalOnly
  {
      public function handle(Request $request, Closure $next): Response
      {
          $user = $request->user();

          if ($user && $user->isLocal()) {
              abort(404);
          }

          return $next($request);
      }
  }
  ```
- **MIRROR**: No existing custom middleware in this project. Pattern from Laravel 11 docs (`abort()` helper + auto-discovered middleware — no kernel registration needed because we invoke it by FQCN from the resource).
- **GOTCHA**: Do NOT check `$user->access_level === null` here. An unauthenticated request should pass through; the Filament auth middleware in front of us already rejects anonymous users. A user who is authenticated but has NULL access_level is already blocked by the login gate from the prior plan (`User::canAccessPanel`) — they won't reach this middleware.
- **VALIDATE**:
  ```bash
  php -l app/Http/Middleware/NationalOrRegionalOnly.php
  ```
  **EXPECT**: No syntax errors.

### Task 3: UPDATE `app/Filament/Resources/Events/EventResource.php`

- **ACTION**: Register the middleware on this resource only.
- **IMPLEMENT**: Add import and method:
  ```php
  use App\Http\Middleware\NationalOrRegionalOnly;
  use Filament\Panel;

  // ... inside class, alongside other static methods:

  public static function getRouteMiddleware(Panel $panel): string|array
  {
      return [NationalOrRegionalOnly::class];
  }
  ```
- **MIRROR**: No existing use of `getRouteMiddleware` in this project — new hook. Signature confirmed against `vendor/filament/filament/src/Resources/Resource/Concerns/HasRoutes.php:124`.
- **GOTCHA**: `getRouteMiddleware` is on the **resource**, not on individual pages. The middleware runs for every page in this resource (list / view / create / edit). That's exactly what we want — local users get 404 on every Event URL.
- **GOTCHA 2**: This does NOT affect navigation visibility. Nav visibility goes through `canAccess()` which bubbles up to the policy (`EventPolicy::viewAny` from Task 1). Keep both changes — they cover different code paths.
- **VALIDATE**:
  ```bash
  php -l app/Filament/Resources/Events/EventResource.php
  sudo -u www-data php artisan route:list --path=admin/events 2>&1 | head -20
  ```
  **EXPECT**: No syntax errors; `route:list` shows the Events routes (they still register — the middleware is invoked at dispatch, not registration time).

### Task 4: UPDATE `app/Policies/ChurchPolicy.php`

- **ACTION**: Add local-user branch to `update()`.
- **IMPLEMENT**: Replace the current `update()` with:
  ```php
  public function update(User $user, Church $church): bool
  {
      if ($user->isNational()) {
          return true;
      }

      if ($user->isRegional()) {
          return $church->region_id === $user->region_id;
      }

      if ($user->isLocal()) {
          return $church->id === $user->church_id;
      }

      return false;
  }
  ```
- **MIRROR**: Shape matches the existing method — just adds a third branch following the same `if ... return` pattern.
- **GOTCHA**: Don't change `create` or `delete`. Local users should not be able to create new churches or delete churches — the policy correctly enforces `isNational()` for both. Leave those alone.
- **GOTCHA 2**: A local user with `church_id = null` would fall through to `false` — correct, they can't edit anything.
- **VALIDATE**:
  ```bash
  php -l app/Policies/ChurchPolicy.php
  ```
  **EXPECT**: No syntax errors.

---

## Testing Strategy

### Unit / Feature Tests

| Test File | Test Cases | Validates |
|-----------|------------|-----------|
| `tests/Feature/EventAccessPolicyTest.php` | local → viewAny false; national → viewAny true; regional → viewAny true; local → view false; national → create true; local → create false | Policy changes in Task 1 |
| `tests/Feature/EventAccessPolicyTest.php` (same file) | actingAs local → GET /admin/events returns 404; actingAs national → 200 | Middleware behaviour in Tasks 2+3 |
| `tests/Feature/ChurchPolicyLocalEditTest.php` | local with matching church_id → update true; local with different church_id → update false; regional → update true in region, false outside; national → update always true; create/delete still deny local | Policy change in Task 4 |

### Edge Cases Checklist

- [ ] Local user without `church_id` (null) → can't update any church — falls through to `return false`.
- [ ] Local user with `church_id = 2` typing `/admin/events` → 404 (not 403, not blank list).
- [ ] Local user typing `/admin/events/create` → 404.
- [ ] National user typing `/admin/events` → 200 OK, sees events.
- [ ] Regional user typing `/admin/events` → 200 OK, sees events (unchanged).
- [ ] Local user's nav no longer shows Events but still shows Churches, Users, Attendances.
- [ ] Local user opens their own church's edit page → Save button is visible and saves.
- [ ] Local user tampering the URL to edit another church → 403 (policy denies; middleware doesn't apply to churches).
- [ ] No regressions in existing `AccessLevelScopingTest` (9 tests).
- [ ] No regressions in existing `PanelAccessGateTest` (4 tests).

---

## Validation Commands

### Level 1: Static analysis
```bash
cd /var/www/personal/upci.co.nz
vendor/bin/pint --test app/Policies/EventPolicy.php app/Policies/ChurchPolicy.php app/Http/Middleware/NationalOrRegionalOnly.php app/Filament/Resources/Events/EventResource.php tests/Feature/EventAccessPolicyTest.php tests/Feature/ChurchPolicyLocalEditTest.php
```
**EXPECT**: No style violations. If any, run without `--test` to auto-fix.

### Level 2: Unit / policy-level feature tests
```bash
sudo -u www-data php artisan test --filter "EventAccessPolicy|ChurchPolicyLocalEdit"
```
**EXPECT**: All new tests pass.

### Level 3: No regressions in existing suites
```bash
sudo -u www-data php artisan test --filter "AccessLevelScoping|PanelAccessGate"
```
**EXPECT**: 9 + 4 = 13 tests still pass.

### Level 4: Route middleware actually runs (smoke test)
```bash
# As local user — requires a session, simplest via Pest test helper (already covered in Level 2 via actingAs + get('/admin/events')).
# Manual alternative: log in as a local pastor in a browser, type /admin/events, expect 404 page.
```

### Level 5: Manual browser walkthrough
- Log in as `andrew.kintanar@churchtriumphant.co.nz` (set password via `php artisan users:reset-password andrew.kintanar@churchtriumphant.co.nz` + reset URL from `storage/logs/laravel.log`).
- **EXPECT**:
  - Sidebar has no "Events" item.
  - Type `/admin/events` in URL → 404 page.
  - Open Churches → click their row → Edit → form has Save button → change the phone number → Save → flash says saved → reload shows new phone.
  - Log out.
- Log in as `admin@upci.co.nz` (national).
  - Sidebar still shows Events.
  - `/admin/events` loads normally.

---

## Acceptance Criteria

- [ ] Local user's sidebar does not include Events.
- [ ] `GET /admin/events`, `/admin/events/create`, `/admin/events/{id}`, `/admin/events/{id}/edit` all return 404 for local users.
- [ ] National and regional users see Events in the sidebar and can access all Events routes as before.
- [ ] Local user can successfully update their own church via `/admin/churches/{own-id}/edit` — form saves, data persists.
- [ ] Local user still cannot update another church (403).
- [ ] Local user still cannot create or delete a church (403 / hidden button).
- [ ] Existing `AccessLevelScopingTest` + `PanelAccessGateTest` still pass (no regressions).

---

## Completion Checklist

- [ ] Task 1 — `EventPolicy` tightened
- [ ] Task 2 — `NationalOrRegionalOnly` middleware created
- [ ] Task 3 — `EventResource::getRouteMiddleware()` added
- [ ] Task 4 — `ChurchPolicy::update` gets local branch
- [ ] `EventAccessPolicyTest` written and passes
- [ ] `ChurchPolicyLocalEditTest` written and passes
- [ ] Existing tests still green
- [ ] Manual browser walkthrough confirms all three behaviours

---

## Risks and Mitigations

| Risk | Likelihood | Impact | Mitigation |
|------|------------|--------|------------|
| Middleware accidentally applies to ALL resources if registered globally | LOW | HIGH | Register via `EventResource::getRouteMiddleware()` only — never add to `AdminPanelProvider` middleware chain. |
| `canAccess()` / `canViewAny()` misbehaves and leaks Events into nav | LOW | LOW | Nav call path verified via `vendor/filament/filament/src/Resources/Resource/Concerns/HasNavigation.php:50` — `canAccess` → `canViewAny` → policy. If it leaks, the middleware catches it at route time regardless. Belt + suspenders. |
| Local user field-edits break their own church (e.g. accidentally sets `is_active = false`) | MED | LOW | Out of scope for this plan — documented as open question. Locals are trusted admin users; if field-level tightening is needed, follow up with `->disabled(fn () => auth()->user()->isLocal())` predicates on specific form fields. |
| Opening `/admin/events` as national user suddenly 404s | LOW | MED | Middleware only aborts when `$user->isLocal()` is strictly true — national (`isNational()`) and regional users never match this predicate. Covered by test `national user reaches /admin/events`. |
| Test environment boots wrong access_level on factory-created user | LOW | LOW | Tests explicitly set `access_level` on `User::create()` — no implicit defaults. |
| Filament v4 version drift renames `getRouteMiddleware` | LOW | LOW | Composer lock pins versions; upgrade happens intentionally. If it changes, this plan surfaces the rename immediately via Task 3's validate step. |

---

## Notes

- **Why 404 and not 403?** 404 hides the resource's existence from someone probing URLs. 403 leaks "there is a resource here, you just can't have it." For local pastors, the product intent is that Events simply aren't part of their admin panel — a 404 matches that semantic. (For the Churches edit case, 403 is still correct when they try to edit someone else's church — the resource clearly exists, they see it in the list, the denial is meaningful.)
- **Why not override `canAccess()` on EventResource to `abort(404)` directly?** Because `canAccess()` is also called by the navigation renderer (`HasNavigation.php:50`). Aborting there would crash the entire sidebar for local users instead of just hiding the Events item. Route middleware is the clean separation — nav uses the policy, routes use the middleware, neither interferes with the other.
- **Why `NationalOrRegionalOnly` and not `DenyLocal`?** Same semantic either way, but "NationalOrRegionalOnly" reads as "this resource is for these roles" and aligns with how the existing `NationalOnlyPolicy` (`app/Policies/NationalOnlyPolicy.php`) is named.
- **Follow-up candidate.** The user specifically mentioned Events. If the same hide-for-local treatment is wanted for Departments later, reuse this middleware. Two-line change: `use App\Http\Middleware\NationalOrRegionalOnly;` + `public static function getRouteMiddleware($panel) { return [NationalOrRegionalOnly::class]; }` in `DepartmentResource`, plus the matching `DepartmentPolicy::viewAny` tightening.
- **Confidence: 9/10.** Small surface area, no architectural risk, patterns mirrored from existing code, tests verify each path. The one unknown is whether the manual browser step surfaces a subtle Filament v4 interaction I haven't anticipated — hence not 10/10.
