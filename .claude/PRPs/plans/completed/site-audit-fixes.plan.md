# Fix: Site audit findings (admin, public SPA, API, routing, tests)

## Summary

A multi-agent read-only audit of the whole site produced **49 confirmed findings**. After
merging duplicates (several agents found the same defect from different angles) that comes to
**34 tasks**. There are **2 critical** defects: the Gallery admin 500s on every create, view and
edit page because of a Filament v3 leftover, and a regional user can take over a national
administrator's account. There are **3 high** ones: church leadership can be hijacked across
churches, there is a stored XSS on `/find-church`, and church coordinates are never saved. The
rest are medium and low correctness, data-exposure and UX defects. The plan also clears the 16
baseline test failures, so the suite ends at **0 failures**.

## OWNER CONSTRAINTS (amendment, 2026-09-30). These override anything below.

1. **Delete nothing.** No file, test, route, route name, method, seeder, DB row or directory gets
   removed. Changing lines inside a function is allowed when the change *is* the fix (for example
   replacing the unsafe `afterStateUpdated` closure, dropping a v4-invalid `->nullable()` call, or
   dropping `->sortable()` from a column that can't be sorted). Dead code that isn't causing a bug
   stays where it is.
2. **Stale tests are kept, not deleted.** Each stale starter-kit test file keeps its tests and
   gets a `beforeEach(fn () => $this->markTestSkipped('<reason>'))` saying what replaced it. New
   coverage goes in new files (`FilamentAuthTest.php`) or next to the kept tests (`DashboardTest.php`).
3. **Owner decisions (resolved 2026-09-30):** Task 26 means *schedule*: a future `published_at` is a go-live
   time (`AGSUpdateSchedulingTest`). For Task 27, production is **behind Cloudflare**, so the app trusts
   loopback plus Cloudflare's ranges, with a `TRUSTED_PROXIES` override (`TrustedProxyTest`). For Task 20,
   menu item 32 was set to `/admin` in the live DB. That exposed another bug: the SPA sent `/admin` through
   `<router-link>`, so `resources/js/utils/menuUrl.js` `isPlainAnchor()` now makes server paths use plain anchors.
4. A full backup exists: `/var/www/personal/upci.co.nz-backups/pre-audit-20260930-063604/` and git tag `pre-audit-20260930-063604`.

Per-task overrides:
- **Task 3.4**: keep `Church::setAssignedLeadershipAttribute()`. It becomes unused, which is harmless.
- **Task 4**: keep `onAddressSelected()` / `handleAddressSelected()`. Only fix the lat/lng write path.
- **Task 14**: keep the unreachable `tabs.length === 1` fallback. Only add the empty message.
- **Task 18**: keep the `_t` / `_cb` cache-busters. Only add the debounce and the stale-response guard.
- **Task 29 (replaces the original)**: keep every route and `VerifyEmailController.php`.
  `routes/auth.php` already uses redirects, so (a) point them at the real v4 panel URLs
  (`register` → the panel login, since the panel has no registration; `forgot-password` and
  `reset-password/{token}` → the panel password-reset request URL); (b) change
  `VerifyEmailController`'s `route('dashboard')` to the Filament panel URL (`/admin`); (c) move
  `require __DIR__.'/auth.php';` above the `/cms/{slug}` and `/{any}` routes; (d) narrow the catch-all
  with `->where('any', '^(?!api(/|$)).*')`. Leave the empty `admin` group in place.
- **Tasks 30 and 31 (replace the originals)**: keep all 7 files and mark each one skipped as in
  constraint 2. Still create `tests/Feature/FilamentAuthTest.php`.
- **Task 32**: keep the two existing `/dashboard` tests, marked skipped with a reason, and add the
  new `/admin` tests alongside them in the same file.
- **Task 33**: update `ChurchSeeder`. Don't delete it.
- **Appendix note**: leave the empty `database/database.sqlite` alone.

---

## User Story

As a **UPCI NZ administrator (national, regional or local)**
I want **the admin panel to enforce the access tiers it advertises and the public site to show correct, safe data**
So that **no one can escalate their privileges, and visitors see the content editors actually published**

## Problem Statement

Every item below is a verified defect with a file:line and evidence. See the finding ids in each task.

1. **Admin 500 (critical):** `MorphToSelect::nullable()` doesn't exist in Filament v4, so
   `/admin/gallery-items/create`, `/{id}` and `/{id}/edit` all throw.
2. **Account takeover (critical):** `UserPolicy::update` lets a regional user edit any user
   whose church is in their region, national administrators included. The password and email
   fields aren't gated, so the regional user can set a national admin's password.
3. **Cross-church hijack (high):** the "Assign Leadership" select lists every leader in the
   country and rewrites `church_id` in `afterStateUpdated`, before save and with no authorisation.
   A local pastor can pull any user into their church or detach users from it.
4. **Stored XSS (high):** church name, address, city and region go unescaped into Leaflet
   `bindPopup()` HTML on the public locator. A local admin can therefore run script in a national
   admin's same-origin session.
5. **Silent data loss (high):** the church `latitude`/`longitude` fields are `->hidden()`. In v4
   that means they're neither rendered nor dehydrated, so coordinates are never saved.
6. **Medium and low:** scope checks where null matches null, privileged fields editable by
   local users, orphaned attendance rows, closure parameters Filament never injects (`$s`, and
   `$record` in column `visible()`), a MorphTo sort that throws SQL errors, inactive menu items
   leaking into the public menu, auth routes shadowed by the SPA catch-all, 16 stale starter-kit
   tests, and a group of SPA defects (a NaN crash, multi-select sending one value, blank tabs,
   dead links, a UTC date bug).

## Solution Statement

Fix each defect where it lives and add a regression test for each one. Mirror patterns the
codebase already uses. Privileged fields get the `UserForm` gate
(`->disabled(fn () => ! auth()->user()?->isNational())->dehydrated(fn () => (bool) auth()->user()?->isNational())`).
The scheduling scope mirrors `DepartmentAnnouncement::scopePublished`. Security tests go in
`SecurityRegressionTest.php`, `GalleryOwnershipTest.php` and `AccessLevelScopingTest.php`. Stale
starter-kit tests are kept and marked skipped (see OWNER CONSTRAINTS). They test Volt components and routes that no longer
exist, and Filament owns auth now.

## Metadata

| Field | Value |
|---|---|
| Type | BUG FIX (security + correctness) |
| Complexity | **MEDIUM-HIGH**. Most tasks are small; the authorisation tasks need care and tests |
| Systems Affected | Filament resources (Users, Churches, GalleryItems, Attendances, Events, Departments, AGSUpdates), policies, `User` model, `routes/web.php`, `routes/auth.php`, `bootstrap/app.php`, Api controllers, 9 Vue files, seeders, `tests/Feature/*` |
| Dependencies | Laravel 12, Filament ^4.0@beta, Livewire 3, Pest, Vue 3, Leaflet |
| Estimated Tasks | 34 (2 critical, 3 high, 13 medium, 16 low) |
| Test DB | `phpunit.xml` uses sqlite `:memory:`, so running the suite is safe |

---

## Mandatory Reading

| File | Why |
|---|---|
| `app/Filament/Resources/Users/Schemas/UserForm.php` | The national-only field gate to copy for every privileged field |
| `app/Models/DepartmentAnnouncement.php:40-44` | The `scopePublished` pattern to mirror for AGS scheduling |
| `app/Filament/Concerns/*` / `UserResource::localScope/regionalScope` | How resources scope queries by access level |
| `tests/Feature/SecurityRegressionTest.php` | The test style and helpers (`regressionUser()`) for security regressions |
| `tests/Feature/GalleryOwnershipTest.php` | Existing gallery policy tests to extend |
| `vendor/filament/forms/src/Components/MorphToSelect.php` | Confirms which methods exist in v4 (no `nullable()`) |
| `vendor/filament/support/src/Concerns/EvaluatesClosures.php` | Why `$s` gets `null`: parameters are injected by **name** |
| memory: `project_login_gate.md` | `canAccessPanel()` rejects NULL `access_level` **on purpose**, so don't "fix" that |
| memory: `project_filament_file_upload_disk.md` | Every FileUpload needs `->disk('public')`. Don't regress it while touching `GalleryItemForm` |

## Patterns to Mirror

```php
// Privileged-field gate (UserForm.php)
->disabled(fn () => ! auth()->user()?->isNational())
->dehydrated(fn () => (bool) auth()->user()?->isNational())
```

```php
// Scheduling-aware published scope (DepartmentAnnouncement.php:40-44)
return $query->where('is_published', true)
    ->where(fn ($q) => $q->whereNull('published_at')->orWhere('published_at', '<=', now()));
```

```php
// Filament closure injection: the parameter NAME is the contract
->formatStateUsing(fn ($state) => ...)   // correct
->formatStateUsing(fn ($s) => ...)       // $s is always null
```

## Files to Change

| File | Action | Tasks |
|---|---|---|
| `app/Filament/Resources/GalleryItems/Schemas/GalleryItemForm.php` | UPDATE | 1 |
| `app/Filament/Resources/GalleryItems/GalleryItemResource.php` | UPDATE | 1 |
| `app/Filament/Resources/GalleryItems/Tables/GalleryItemsTable.php` | UPDATE | 10 |
| `app/Policies/UserPolicy.php` | UPDATE | 2 |
| `app/Filament/Resources/Users/UserResource.php` | UPDATE | 2 |
| `app/Filament/Resources/Churches/Schemas/ChurchForm.php` | UPDATE | 3, 4, 6 |
| `app/Filament/Resources/Churches/Pages/CreateChurch.php`, `EditChurch.php` | UPDATE | 3 |
| `app/Models/Church.php` | NO CHANGE (the mutator is kept) | 3 |
| `packages/filament-address-finder/resources/js/address-finder.js` (+ `src/AddressFinder.php`) | UPDATE | 4 |
| `app/Models/User.php`, `app/Policies/ChurchPolicy.php`, `app/Policies/AttendancePolicy.php` | UPDATE | 5 |
| `app/Filament/Resources/Attendances/Pages/CreateAttendance.php`, `Schemas/AttendanceForm.php` | UPDATE | 7 |
| `EventsTable.php`, `DepartmentsTable.php`, `Departments/RelationManagers/EventsRelationManager.php`, `AGSUpdatesTable.php` | UPDATE | 8 |
| `app/Filament/Resources/Churches/Tables/ChurchesTable.php` | UPDATE | 9 |
| `resources/js/views/ChurchLocator.vue` | UPDATE | 11, 12, 13, 16, 17, 18 |
| `app/Http/Controllers/Api/ChurchController.php` | UPDATE | 13 |
| `resources/js/views/Gallery.vue` | UPDATE | 14 |
| `resources/js/components/blocks/ChurchDirectoryBlock.vue` | UPDATE | 15 |
| `resources/js/components/Navbar.vue`, `Footer.vue` (+ new `MenuLink.vue`) | UPDATE/CREATE | 19 |
| `menu_items` row id 32 (data, via admin UI) | DATA | 20 |
| `resources/js/views/GetInvolved.vue` | UPDATE | 21 |
| `resources/js/views/Calendar.vue` | UPDATE | 22 |
| `resources/js/components/blocks/EventsFeedBlock.vue` | UPDATE | 23 |
| `app/Http/Controllers/Api/MenuItemController.php` | UPDATE | 24 |
| `app/Http/Controllers/Api/GalleryController.php`, `EventController.php` | UPDATE | 25 |
| `app/Models/AGSUpdate.php` | UPDATE (after owner decision) | 26 |
| `bootstrap/app.php` | UPDATE | 27, 28 |
| `resources/js/composables/useBlockData.js` | UPDATE | 28 |
| `routes/web.php`, `routes/auth.php` | UPDATE | 29 |
| `app/Http/Controllers/Auth/VerifyEmailController.php` | UPDATE (redirect target only) | 29 |
| `tests/Feature/Auth/*` (5 files) | UPDATE (mark skipped) + new `tests/Feature/FilamentAuthTest.php` | 30 |
| `tests/Feature/Settings/*` (2 files) | UPDATE (mark skipped) | 31 |
| `tests/Feature/DashboardTest.php` | REWRITE | 32 |
| `database/seeders/ChurchSeeder.php` | UPDATE | 33 |
| `database/seeders/DatabaseSeeder.php` (+ `UserFactory`) | UPDATE | 34 |

## NOT Building (Scope Limits)

- **No change to `canAccessPanel()`.** Rejecting NULL `access_level` is deliberate (memory: login gate).
- **No sync of the legacy church `address` column.** It's a known quirk, not part of this audit.
- **No Filament `->registration()` or `->emailVerification()`.** The panel doesn't offer them, and the dead routes get deleted instead.
- **No `->profile()` page** unless the owner asks for one (see Task 31).
- **No visual or design changes.** Vue tasks fix behaviour only. The one markup change (the CTA
  in Task 17) keeps its existing classes, so the huashu-design gate isn't triggered.
- **No DB writes from code for Task 20.** It's a content fix made through the admin UI.

---

## Step-by-Step Tasks

Tasks are grouped by area. Areas are ordered by their most severe finding, and tasks within an
area are ordered by severity.

### Phase 1: Filament admin (authorisation, v4 leftovers, data integrity)

**Task 1 [CRITICAL]: Gallery admin 500 + latent regional owner bypass**
- **Findings**: `morphtoselect-nullable-crash`, `gallery-regional-owner-bypass-latent`
- **Files**: `app/Filament/Resources/GalleryItems/Schemas/GalleryItemForm.php:48`, `app/Policies/GalleryItemPolicy.php:38`, `app/Filament/Resources/GalleryItems/GalleryItemResource.php`
- **Change** (both in one commit, because removing `nullable()` makes the bypass reachable):
  ```php
  // GalleryItemForm.php:42-50 — remove ->nullable(); MorphToSelect is optional unless ->required()
  MorphToSelect::make('galleryable')
      ->types([...])            // unchanged
      ->searchable()
      ->preload()
      // Owner is national-only on the standalone resource. Regional users manage their
      // region's gallery through RegionResource's relation manager, where the owner is fixed.
      ->disabled(fn () => ! auth()->user()?->isNational())
      ->dehydrated(fn () => (bool) auth()->user()?->isNational()),
  ```
  ```php
  // GalleryItemResource.php — standalone create is national-only
  public static function canCreate(): bool
  {
      return (bool) auth()->user()?->isNational();
  }
  ```
  Leave `GalleryItemPolicy::create` as it is (regional create is still legitimate inside the
  relation manager). Update its comment so it says the standalone resource enforces national-only
  through `canCreate()`.
- **GOTCHA**: Keep `->disk('public')` on the FileUpload (memory). Check that the region relation
  manager's create still works for a regional user.
- **VALIDATE**:
  - `php -l app/Filament/Resources/GalleryItems/Schemas/GalleryItemForm.php`
  - New tests in `tests/Feature/GalleryOwnershipTest.php`:
    - national: `get('/admin/gallery-items/create')->assertOk()`, and the same for `/{id}` and `/{id}/edit`
    - regional: `get('/admin/gallery-items/create')->assertForbidden()`
    - regional editing their own region's item: `Livewire::test(EditGalleryItem::class, ['record' => $item->id])->set('data.galleryable_type', Department::class)->set('data.galleryable_id', $dept->id)->call('save')` leaves the owner unchanged
  - `php artisan test --filter=GalleryOwnership`

**Task 2 [CRITICAL]: Regional to national account takeover; regional users can't edit themselves**
- **Findings**: `regional-reset-national-password`, `regional-can-takeover-national-user`, `regional-user-cannot-edit-self`
- **Files**: `app/Policies/UserPolicy.php:14-21, 27-42`, `app/Filament/Resources/Users/UserResource.php:38`
- **Change**: a non-national actor may only act on targets of **strictly lower** rank.
  ```php
  public function view(User $actor, User $target): bool
  {
      if ($actor->id === $target->id || $actor->isNational()) {
          return true;
      }
      return $this->outranks($actor, $target) && $actor->canAccessChurch($target->church);
  }

  public function update(User $actor, User $target): bool
  {
      if ($actor->id === $target->id || $actor->isNational()) {
          return true;
      }
      if ($actor->isRegional()) {
          return $actor->region_id !== null
              && $this->outranks($actor, $target)
              && $target->church?->region_id === $actor->region_id;
      }
      return false;
  }

  // Regional outranks local and no-access users; nobody but national outranks regional/national.
  private function outranks(User $actor, User $target): bool
  {
      return ! $target->isNational() && ! $target->isRegional();
  }
  ```
  Apply the same `outranks()` check to `delete()` if it has the same shape.
  ```php
  // UserResource::regionalScope — always include the actor themselves
  return fn (Builder $q, int $regionId) => $q->where(fn (Builder $w) => $w
      ->whereKey(auth()->id())
      ->orWhereHas('church', fn (Builder $c) => $c->where('region_id', $regionId)));
  ```
- **GOTCHA**: Don't add `orWhere('region_id', $regionId)` to the scope. That would list other
  regional peers, which `update` now refuses anyway, and it widens what `view` exposes.
- **VALIDATE**: new tests in `tests/Feature/SecurityRegressionTest.php`:
  - regional actor, national target whose church is in the region: `can('update')` is false, and `Livewire::test(EditUser::class, ['record' => $national->id])` is forbidden
  - regional actor, regional peer whose church is in the region: `can('update')` is false
  - regional actor, local user in the region: `can('update')` is true
  - regional actor with `church_id = null`: `get("/admin/users/{$self->id}/edit")->assertOk()`
  - `php artisan test --filter=SecurityRegression`

**Task 3 [HIGH]: "Assign Leadership" cross-church hijack; leadership lost on create**
- **Findings**: `church-leadership-cross-church-hijack`, `leadership-select-reassigns-any-user`, `church-create-leadership-discarded`
- **Files**: `app/Filament/Resources/Churches/Schemas/ChurchForm.php:69-117`, `Pages/CreateChurch.php`, `Pages/EditChurch.php`, `app/Models/Church.php` (`setAssignedLeadershipAttribute`)
- **Change**:
  1. In the select, **delete the whole `afterStateUpdated` closure** (lines 95-117). Add `->dehydrated(false)` and `->disabled(fn () => auth()->user()?->isLocal())`.
  2. Scope `options()` to users the actor may update, plus the church's current leaders (so they still display):
     ```php
     ->options(fn (?Church $record) => User::query()
         ->whereIn('role', [...])                         // unchanged role list
         ->get()
         ->filter(fn (User $u) => Gate::allows('update', $u) || $u->church_id === $record?->id)
         ->mapWithKeys(fn (User $u) => [$u->id => "{$u->name} ({$u->role->label()})"]))
     ```
     (Keep the existing label format.)
  3. Add a shared concern `app/Filament/Resources/Churches/Concerns/SyncsLeadership.php`:
     ```php
     protected function syncLeadership(Church $church, array $selected): void
     {
         $current = User::where('church_id', $church->id)->whereIn('role', [...])->pluck('id')->all();
         $toAdd = array_diff($selected, $current);
         $toRemove = array_diff($current, $selected);
         foreach (User::whereIn('id', [...$toAdd, ...$toRemove])->get() as $user) {
             Gate::authorize('update', $user);   // 403 before any write
         }
         DB::transaction(function () use ($church, $toAdd, $toRemove) {
             User::whereIn('id', $toAdd)->update(['church_id' => $church->id]);
             User::whereIn('id', $toRemove)->update(['church_id' => null]);
         });
     }
     ```
     Call it from `CreateChurch::afterCreate()` and `EditChurch::afterSave()` with
     `$this->form->getRawState()['assigned_leadership'] ?? []`. Skip the call when the field is
     disabled for the actor.
  4. Delete `Church::setAssignedLeadershipAttribute()`, since `dehydrated(false)` means the key never reaches the model.
- **VALIDATE**: new tests in `tests/Feature/ChurchPolicyLocalEditTest.php` (or `SecurityRegressionTest.php`):
  - local user editing their own church: the leadership field is disabled, and another church's pastor's name is **not** in the rendered options
  - regional user: after `->set('data.assigned_leadership', [..., $otherRegionPastor->id])` and **without** calling `save`, the other pastor's `church_id` is unchanged; after `save`, the result is a 403 or a validation error, and it's still unchanged
  - national `CreateChurch` with a selected leader: after `create`, the leader's `church_id` equals the new church
  - `php artisan test --filter="ChurchPolicyLocalEdit|SecurityRegression"`

**Task 4 [HIGH]: Church coordinates are never saved**
- **Finding**: `church-coordinates-never-saved`
- **Files**: `app/Filament/Resources/Churches/Schemas/ChurchForm.php:297-305`, `packages/filament-address-finder/resources/js/address-finder.js:~527-600`, `packages/filament-address-finder/src/AddressFinder.php:29-33` (+ `handleAddressSelected`)
- **Change**:
  ```php
  use Filament\Forms\Components\Hidden;
  Hidden::make('latitude'),
  Hidden::make('longitude'),
  ```
  In `address-finder.js` `updateFormFields`, the lat/lng inputs are `wire:model` bound, so after
  setting `.value` also dispatch `input` **and** `change` (the other fields already do this at
  lines ~537-597). Better still, write through Livewire:
  `this.$wire?.set('data.latitude', lat); this.$wire?.set('data.longitude', lng);`.
  Delete the dead `onAddressSelected()` / `handleAddressSelected()` path, which calls a
  non-existent `$this->getSet()`. Alternatively, implement it with `$this->makeSetUtility()` if
  it's wanted.
- **GOTCHA**: The selectors at ~527 match `input[name*="latitude"]`. Filament v4 hidden inputs
  have `id="data.latitude"` and are wired with `wire:model`, not `name`. Check that the selector
  matches the rendered DOM and add `input[id$=".latitude"]` if it doesn't. The package JS has to be
  rebuilt or re-published (`php artisan filament:assets`).
- **VALIDATE**:
  - Pest: `Livewire::test(EditChurch::class, ['record' => $c->id])->set('data.latitude', -36.85)->set('data.longitude', 174.76)->call('save')`, then `expect($c->fresh()->latitude)->toEqual(-36.85)`
  - Manual: pick an address in the admin, save, and confirm the church appears on `/find-church`

**Task 5 [MEDIUM]: A null region_id or church_id matches another null**
- **Finding**: `null-region-regional-matches-everything-null`
- **Files**: `app/Models/User.php:130-131`, `app/Policies/ChurchPolicy.php:32,36`, `app/Policies/AttendancePolicy.php:32,36`, `app/Policies/UserPolicy.php:39` (already covered by Task 2)
- **Change**: guard each comparison against a null actor scope:
  ```php
  $this->isRegional() => $this->region_id !== null && $church->region_id === $this->region_id,
  $this->isLocal()    => $this->church_id !== null && $church->id === $this->church_id,
  ```
  Do the same in `ChurchPolicy` (`$user->region_id !== null && ...`, `$user->church_id !== null && ...`)
  and `AttendancePolicy`.
- **VALIDATE**: tests in `tests/Feature/AccessLevelScopingTest.php`: a regional user with `region_id = null` can't update a church with `region_id = null` or a churchless user; a local user with `church_id = null` can't view an attendance row with `church_id = null`. Run `php artisan test --filter=AccessLevelScoping`.
- **Follow-up (read-only)**: `php artisan tinker --execute="App\Models\User::where('access_level','regional')->whereNull('region_id')->pluck('email')"`. Report any rows to the owner.

**Task 6 [MEDIUM]: Local and regional users can move a church's region**
- **Findings**: `local-user-can-move-church-region`, `church-region-editable-by-local-regional`
- **File**: `app/Filament/Resources/Churches/Schemas/ChurchForm.php:183-188`
- **Change**: add the national-only gate to `Select::make('region_id')`:
  ```php
  ->disabled(fn () => ! auth()->user()?->isNational())
  ->dehydrated(fn () => (bool) auth()->user()?->isNational())
  ```
  Decision taken: regional users can't move churches either, which matches RegionPolicy's "the taxonomy stays with national".
- **VALIDATE**: in `ChurchPolicyLocalEditTest.php`, a local user and a regional user each `->set('data.region_id', $other->id)->call('save')` and the church's `region_id` is unchanged; a national user can change it. Run `php artisan test --filter=ChurchPolicyLocalEdit`.

**Task 7 [MEDIUM]: Attendance takes church_id from its creator**
- **Findings**: `attendance-null-church-for-regional-national`, `attendance-church-from-creator`
- **Files**: `app/Filament/Resources/Attendances/Pages/CreateAttendance.php:16`, `app/Filament/Resources/Attendances/Schemas/AttendanceForm.php`
- **Change**:
  ```php
  // AttendanceForm — for non-local users
  Select::make('church_id')
      ->label('Church')
      ->options(fn () => Church::query()->get()
          ->filter(fn (Church $c) => auth()->user()->canAccessChurch($c))
          ->pluck('name', 'id'))
      ->searchable()
      ->required()
      ->visible(fn () => ! auth()->user()?->isLocal()),
  ```
  ```php
  // CreateAttendance::mutateFormDataBeforeCreate
  $user = Auth::user();
  if ($user->isLocal()) {
      $data['church_id'] = $user->church_id;
  }
  $church = Church::find($data['church_id'] ?? null);
  abort_unless($church && $user->canAccessChurch($church), 403);
  ```
  Keep the existing "No church assigned" TextEntry for local users without a church. The
  `abort_unless` refuses the create in that case.
- **VALIDATE**: in `SecurityRegressionTest.php`: a regional user creates attendance for a church in their region and it's saved with that `church_id`; a church outside the region gives a 403; a local user's `church_id` is forced even when `data.church_id` is tampered with. Run `php artisan test --filter=SecurityRegression`.

**Task 8 [MEDIUM]: The is_published badge always reads "Draft"**
- **Finding**: `published-badge-always-draft`
- **Files**: `app/Filament/Resources/Events/Tables/EventsTable.php:38-39`, `app/Filament/Resources/Departments/Tables/DepartmentsTable.php:26-27`, `app/Filament/Resources/Departments/RelationManagers/EventsRelationManager.php:58-59`, `app/Filament/Resources/AGSUpdates/Tables/AGSUpdatesTable.php:20`
- **Change**: rename `$s` to `$state` in all 8 closures (`formatStateUsing` and `color`).
- **VALIDATE**: `grep -rn 'fn (\$s)' app/Filament` returns nothing. Add a Pest test: `Livewire::test(ListEvents::class)` with a published event does `->assertSee('Published')`.

**Task 9 [MEDIUM]: ChurchesTable columns never visible**
- **Finding**: `churches-table-columns-never-visible`
- **File**: `app/Filament/Resources/Churches/Tables/ChurchesTable.php:58,60,64,70,77,92`
- **Change**: remove every `->visible(fn ($record) => ...)` and use `->placeholder('—')->toggleable()`
  instead. Rename the `state` column to `suburb` (the `state` column was dropped in `2025_10_11_235130`).
- **VALIDATE**: `grep -n "visible(fn (\$record)" app/Filament/Resources/Churches/Tables/ChurchesTable.php` returns nothing. Add a Pest test: `Livewire::test(ListChurches::class)->assertTableColumnVisible('city')->assertSee('Auckland')`.

**Task 10 [MEDIUM]: Sorting the gallery by "Belongs to" throws a SQL error**
- **Finding**: `gallery-sort-morphto-sql-error`
- **File**: `app/Filament/Resources/GalleryItems/Tables/GalleryItemsTable.php:24`
- **Change**: remove `->sortable()` from `TextColumn::make('galleryable.name')`.
- **VALIDATE**: in `GalleryOwnershipTest.php`, `Livewire::test(ListGalleryItems::class)->assertTableColumnExists('galleryable.name')` and the column is not sortable (or `->call('sortTable', 'galleryable.name')` no longer throws).

### Phase 2: Public Vue SPA (`resources/js`)

Validate each Vue task with `npm run build`, then load the owning route in the browser and check
there are 0 console errors.

**Task 11 [HIGH]: Stored XSS in the locator map popups**
- **Finding**: `locator-popup-stored-xss`
- **File**: `resources/js/views/ChurchLocator.vue:710-718` (`initializeModalMap`), `:811-824` (`updateMarkers`)
- **Change**: add an escape helper and use it for **every** interpolated field, including `church.id` in `data-church-id`:
  ```js
  const escapeHtml = (v) => String(v ?? '').replace(/[&<>"']/g, (c) => (
    { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]))
  // `<h3 ...>${escapeHtml(church.name)}</h3><p ...>${escapeHtml(church.address)}</p>
  //  <p ...>${escapeHtml(church.city)}, ${escapeHtml(church.region)}</p>`
  ```
  If you'd rather, build the popup as DOM nodes with `textContent` and pass the element to `bindPopup`.
- **VALIDATE**: `grep -n 'bindPopup' resources/js/views/ChurchLocator.vue` shows no raw `${church.` inside a template. Manual: set a local test church's name to `<img src=x onerror=alert(1)>` **in a dev DB only**, open its popup, and confirm the text renders literally with no alert.

**Task 12 [MEDIUM]: The modal map crashes for unmappable churches**
- **Finding**: `locator-modal-map-nan-crash`
- **File**: `resources/js/views/ChurchLocator.vue:681-702` (and the template map column)
- **Change**: at the top of `initializeModalMap(church)`, remove any existing `modalMap` and then
  `if (!isMappable(church)) return`. In the template, wrap the map div in `v-if="isMappable(selectedChurch)"`
  and add a `v-else` panel ("No map location on file"), using the existing brand classes.
- **VALIDATE**: open church id 1 (NULL latitude) on `/find-church`. The console shows no `Invalid LatLng`, and the fallback panel renders.

**Task 13 [MEDIUM]: Multi-select service days filters by the last day only**
- **Finding**: `locator-service-day-multi-select`
- **Files**: `resources/js/views/ChurchLocator.vue:535,753` (+ the `route.query.service_day` reader), `app/Http/Controllers/Api/ChurchController.php:42-43,105`
- **Change**:
  ```js
  selectedServiceDays.value.forEach(day => params.append('service_day[]', day))
  ```
  ```php
  $days = array_filter((array) $request->input('service_day', []));
  if ($days) {
      $query->where(function ($q) use ($days) {
          foreach ($days as $day) {
              $q->orWhereRaw('LOWER(service_times) LIKE ?', ['%"'.strtolower($day).'"%']);
          }
      });
  }
  ```
  (Accept both a scalar and an array, so old shared links still work. Line 105 echoes the value, so
  echo `$days`.) Keep the URL mirror as an array and normalise `route.query.service_day` to an
  array when reading it.
- **VALIDATE**: extend `tests/Feature/ChurchSearchFilterTest.php` so that `?service_day[]=sunday&service_day[]=wednesday` returns a Sunday-only church **and** a Wednesday-only church, and `?service_day=sunday` still works. Run `php artisan test --filter=ChurchSearchFilter`.

**Task 14 [MEDIUM]: Empty gallery tabs render nothing**
- **Finding**: `gallery-tabs-blank`
- **File**: `resources/js/views/Gallery.vue:34` (and `:43`)
- **Change**: pass `empty-message="No photos in this gallery yet."` to `GalleryGrid`. Delete the
  unreachable `tabs.length === 1` fallback at line 43, since there are always at least "All" and "General".
- **VALIDATE**: `/gallery`, then click a region tab. The empty message shows.

**Task 15 [LOW]: The homepage directory never shows the pastor**
- **Finding**: `directory-block-pastor-field`
- **File**: `resources/js/components/blocks/ChurchDirectoryBlock.vue:33`
- **Change**: `church.pastor` becomes `church.pastor_name`, in both `v-if` and the interpolation.
- **VALIDATE**: on the homepage, church 2 shows "Andrew Kintanar".

**Task 16 [LOW]: `tel:null` links and empty Pastor/Phone rows**
- **Finding**: `locator-tel-null`
- **File**: `resources/js/views/ChurchLocator.vue:311-322, 346-347`
- **Change**: add `v-if="selectedChurch.phone"` to the phone row and the Call Church button, and `v-if="selectedChurch.pastor"` to the pastor row.
- **VALIDATE**: `grep -n 'tel:' ChurchLocator.vue` shows each link guarded. Open church 5 and confirm there's no Call button.

**Task 17 [LOW]: The "Contact Us About Starting a Church" button is dead**
- **Finding**: `locator-dead-cta`
- **File**: `resources/js/views/ChurchLocator.vue:374-376`
- **Change**: change `<button ...>` to `<router-link to="/connect-with-us" ...>`, keeping the same classes.
- **VALIDATE**: clicking it navigates to `/connect-with-us`.

**Task 18 [LOW]: Locator search race, no debounce**
- **Finding**: `locator-search-race`
- **File**: `resources/js/views/ChurchLocator.vue:~520-560, 727`
- **Change**: split the watcher: `searchQuery` debounced to 300ms, while region and days fetch
  immediately. In `fetchChurches`, keep an `AbortController` (abort the previous one) or a
  `requestSeq` counter, and ignore stale responses. Remove the `_t` and `_cb` cache-busters.
- **VALIDATE**: type "wellington" quickly. DevTools Network shows 1 request (or earlier ones cancelled), and the results match the final term.

**Task 19 [LOW]: External URLs in grandchildren and footer links become broken in-app routes**
- **Finding**: `nav-grandchild-footer-external-url`
- **Files**: `resources/js/components/Navbar.vue:55-62, 133-141`, `resources/js/components/Footer.vue:46`
- **Change**: create `resources/js/components/MenuLink.vue`. It renders `<a :href :target="open_in_new_tab ? '_blank' : '_self'" rel="noopener">`
  when `url` starts with `http` or `url === '#'`, and `<router-link>` otherwise, with a slot for
  the content and pass-through classes. Use it for grandchildren (desktop and mobile) and footer links.
- **VALIDATE**: `npm run build`. Temporarily set a footer link to `https://facebook.com` in a dev DB and confirm it leaves the site.

**Task 20 [LOW]: The "Minister's Log-in" menu item points at `#` (DATA)**
- **Finding**: `nav-ministers-login-dead`
- **Change**: **the owner should do this through the admin UI** (Menu Items, id 32): set the URL to `/admin`. No code change and no migration.
- **VALIDATE**: `curl -s localhost/api/menu/header | jq '..|objects|select(.label?|test("Log-in"))|.url'` returns `"/admin"`.

**Task 21 [LOW]: Placeholder social links on /departments**
- **Finding**: `getinvolved-placeholder-social`
- **File**: `resources/js/views/GetInvolved.vue:14,18`
- **Change**: read `useSiteSettings().settings.social_links` as `Footer.vue` does, and render only the links that are set (`v-if`).
- **VALIDATE**: on `/departments` the links point at the configured profiles, or aren't rendered when unset.

**Task 22 [LOW]: The calendar has no loading or error state**
- **Finding**: `calendar-no-error-state`
- **File**: `resources/js/views/Calendar.vue:175-189`
- **Change**: add `loading` and `error` refs plus a retry (mirror `BlockState` usage), and a `requestSeq`
  guard so stale months are dropped. Handle `success === false` as an error. Optionally build
  `from`/`to` with `new Date(year, month, 1)` formatted locally (it's a latent portability issue only).
- **VALIDATE**: throttle to offline in DevTools. The error and retry show instead of "No events this month".

**Task 23 [LOW]: The events feed uses the UTC "today"**
- **Finding**: `events-feed-utc-today`
- **File**: `resources/js/components/blocks/EventsFeedBlock.vue:55`
- **Change**:
  ```js
  const d = new Date(), pad = (n) => String(n).padStart(2, '0')
  params.append('from', `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`)
  ```
- **VALIDATE**: `grep -n toISOString EventsFeedBlock.vue` returns nothing.

### Phase 3: Public JSON API (data exposure and contract)

**Task 24 [MEDIUM]: Inactive child menu items leak**
- **Finding**: `menu-inactive-children-leak`
- **File**: `app/Http/Controllers/Api/MenuItemController.php:16-19, 68`
- **Change**: in `formatMenuItem`, filter at every depth:
  ```php
  'children' => $item->children->where('is_active', true)->map(fn ($c) => $this->formatMenuItem($c))->values(),
  ```
  Also constrain the eager load so no inactive rows are queried:
  `->with(['children' => fn ($q) => $q->active()->with(['children' => fn ($q) => $q->active()])])`.
- **VALIDATE**: new `tests/Feature/MenuApiTest.php`: an active parent with an inactive child means `getJson('/api/menu/header')` doesn't contain the child's label. Do the same for a grandchild. Run `php artisan test --filter=MenuApi`.

**Task 25 [LOW]: Draft departments and regions exposed through filters**
- **Finding**: `draft-owner-content-via-filters`
- **Files**: `app/Http/Controllers/Api/GalleryController.php:25-26, 38, 43`, `app/Http/Controllers/Api/EventController.php:36-38, 54, 75-79`
- **Change**: `Rule::exists('departments', 'slug')->where('is_published', true)` (and the same for regions); add `->published()` to the owner lookups.
  **Decision:** in `/api/events`, **exclude** events whose owning department or region is
  unpublished (`whereDoesntHave`/`whereHas(... published())`), rather than nulling the block. That
  matches `DepartmentController::show`, which 404s drafts.
- **VALIDATE**: new tests: `getJson('/api/gallery?department=<draft-slug>')` returns a 422 identical to the one for a non-existent slug, and `/api/events` doesn't contain the draft department's name. Run `php artisan test --filter="Gallery|Event"`.

**Task 26 [LOW]: AGSUpdate ignores published_at (NEEDS OWNER DECISION)**
- **Findings**: `ags-updates-ignore-published-at`, `ags-published-ignores-published-at`
- **File**: `app/Models/AGSUpdate.php:27-30`
- **Decision gate**: confirm that `published_at` means "go live at". If it does:
  ```php
  public function scopePublished($query)
  {
      return $query->where('is_published', true)
          ->where(fn ($q) => $q->whereNull('published_at')->orWhere('published_at', '<=', now()));
  }
  ```
  and add a helper text to `AGSUpdateForm.php:22` ("Leave empty to publish immediately; a future date schedules it"). If it's only a display date, change nothing and record that here.
- **VALIDATE**: in `AnnouncementTest.php` (or a new `AGSUpdateTest.php`), `is_published=true, published_at=now()->addYear()` gives `AGSUpdate::published()->count() === 0`, and `getJson('/api/ags-updates')` excludes it.

**Task 27 [LOW]: `trustProxies(at: '*')` lets clients bypass the IP throttles**
- **Finding**: `trust-all-proxies-bypasses-throttle`
- **File**: `bootstrap/app.php:14`
- **Change**:
  ```php
  $middleware->trustProxies(at: array_filter(explode(',', (string) env('TRUSTED_PROXIES', '127.0.0.1'))));
  ```
  Add `TRUSTED_PROXIES=` to `.env.example` with a comment. **Ask the owner for the production
  proxy IP or CIDR (for example the Cloudflare ranges) before deploying.** A wrong value breaks
  `$request->ip()` and HTTPS detection.
- **VALIDATE**: a Pest test where `withHeader('X-Forwarded-For', '1.2.3.4')` from an untrusted REMOTE_ADDR doesn't change `request()->ip()`. Six contact POSTs with rotating XFF headers get a 429 on the 6th.

**Task 28 [LOW]: API errors return HTML (302, 429, 404) instead of JSON**
- **Finding**: `api-validation-redirects-not-422` (also covers the second half of `unknown-api-paths-return-spa-html-200`)
- **Files**: `bootstrap/app.php` (`withExceptions`), `resources/js/composables/useBlockData.js:53-54`
- **Change**:
  ```php
  ->withExceptions(function (Exceptions $exceptions) {
      $exceptions->shouldRenderJsonWhen(fn (Request $r) => $r->is('api/*') || $r->expectsJson());
  })
  ```
  In `useBlockData.js` (and the other view `fetch` calls), send `headers: { Accept: 'application/json' }`.
- **VALIDATE**: `getJson` isn't enough here because it already sends Accept. Use `$this->get('/api/gallery?department=nope')->assertStatus(422)->assertJsonStructure(['message', 'errors'])`.

### Phase 4: Routing, auth leftovers and the test suite

**Task 29 [MEDIUM]: Auth routes shadowed by the SPA catch-all; v3 redirect targets; dead verify-email**
- **Findings**: `auth-routes-shadowed-by-spa-catchall` (x2), `auth-redirect-targets-v3-paths`, `verify-email-controller-missing-dashboard-route`, `verify-email-controller-dead-dashboard-route`, `unknown-api-paths-return-spa-html-200`
- **Files**: `routes/web.php:80-94`, `routes/auth.php`, `app/Http/Controllers/Auth/VerifyEmailController.php`
- **Change**:
  1. Rewrite `routes/auth.php` to contain only:
     ```php
     Route::middleware('guest')->group(function () {
         // Closures: the panel isn't resolved when route files load
         Route::get('login', fn () => redirect(filament()->getLoginUrl()))->name('login');
         Route::get('forgot-password', fn () => redirect(filament()->getRequestPasswordResetUrl()))->name('password.request');
     });
     Route::post('logout', App\Livewire\Actions\Logout::class)->name('logout');
     ```
     Delete `register`, `reset-password/{token}`, `verify-email`, `verify-email/{id}/{hash}`, `confirm-password`.
  2. Delete `app/Http/Controllers/Auth/VerifyEmailController.php`. Its `route('dashboard')` doesn't
     exist, and `User` doesn't implement `MustVerifyEmail`.
  3. In `routes/web.php`, move `require __DIR__.'/auth.php';` **above** the `/cms/{slug}` and
     `/{any}` routes. Delete the empty `Route::prefix('admin')->middleware(['auth'])` group (lines 90-92).
  4. Narrow the catch-all so it doesn't swallow the API: `->where('any', '^(?!api(/|$)).*')`. Unknown
     `/api/*` paths then 404, and Task 28 turns those into JSON.
- **GOTCHA**: Keep the route named `login`, because Laravel's guest redirect uses `route('login')`.
  Before deleting `password.reset`, check that nothing calls it:
  `grep -rn "password.reset\|verification\.\|password.confirm" app resources routes`. Filament's
  `ResetPassword` notification builds its own URL.
- **VALIDATE**:
  - `php artisan route:list --path=login` shows `GET login`, and `php artisan route:list --path=verify-email` returns no routes
  - `php artisan route:list | grep -c 'admin/register\|password/reset'` returns `0`
  - the tests in Task 30 (`GET /login` redirects to `/admin/login`; `GET /api/nope` is a 404 JSON response)

**Task 30 [MEDIUM]: Stale starter-kit auth tests. DELETE all 5 and replace them with one Filament auth test**
- **Findings**: `stale-starter-kit-tests` (x2), `stale-auth-volt-tests`
- **Delete**: `tests/Feature/Auth/AuthenticationTest.php`, `EmailVerificationTest.php`,
  `PasswordConfirmationTest.php`, `PasswordResetTest.php`, `RegistrationTest.php`
- **Justification (the same for all 5)**: they `use Livewire\Volt\Volt`, which isn't installed.
  They test Volt components (`auth.login`, `auth.register`, `auth.forgot-password`,
  `auth.reset-password`, `auth.confirm-password`) that don't exist. The tests in them that pass are
  false positives served by the SPA catch-all. Registration, email verification and password
  confirmation aren't features of this app (Task 29 deletes them), so there's nothing to rewrite
  those tests against. Login and password reset now belong to Filament, and the new file below
  covers them.
- **Create** `tests/Feature/FilamentAuthTest.php`:
  - `get('/admin/login')->assertOk()`
  - `Livewire::test(\Filament\Auth\Pages\Login::class)->fillForm(['email' => $u->email, 'password' => 'password'])->call('authenticate')`, then `assertAuthenticatedAs($u)`; a wrong password gives `assertHasFormErrors(['email'])` (check the page class path in `vendor/filament/filament/src/Auth/Pages/`)
  - `get('/admin/password-reset/request')->assertOk()`; the request flow with `Notification::fake()` sends `\Filament\Auth\Notifications\ResetPassword`
  - `get('/login')->assertRedirect('/admin/login')`. **This is the test that would have caught the shadowing bug.**
  - `get('/forgot-password')->assertRedirect('/admin/password-reset/request')`
  - `get('/register')->assertOk()` (it now falls to the SPA, which is intended), or leave it out
  - `actingAs($u)->post('/logout')` then `assertGuest()`
  - `get('/api/does-not-exist')->assertNotFound()->assertJson([...])` (Task 28/29)
- **VALIDATE**: `php artisan test --filter=FilamentAuth`

**Task 31 [MEDIUM]: Stale starter-kit settings tests. DELETE**
- **Finding**: `stale-settings-volt-tests`
- **Delete**: `tests/Feature/Settings/PasswordUpdateTest.php`, `tests/Feature/Settings/ProfileUpdateTest.php` (and the empty directory)
- **Justification**: they test Volt `settings.profile`, `settings.password` and
  `settings.delete-user-form` pages that were removed. No `settings/*` routes exist. The one test
  that passes ("profile page is displayed") is a false positive from the SPA catch-all. The app has
  no self-service settings feature to rewrite them against. Password changes go through
  `UserResource` (with Task 2's self-edit fix) and `users:reset-password`. If the owner later
  enables Filament `->profile()`, write new tests against `Filament\Auth\Pages\EditProfile` at
  that point.
- **VALIDATE**: `ls tests/Feature/Settings` fails, and `php artisan test` shows no Volt errors.

**Task 32 [LOW]: DashboardTest. REWRITE against /admin**
- **Finding**: `stale-dashboard-test`
- **File**: `tests/Feature/DashboardTest.php`
- **Justification for rewriting rather than deleting**: the intent (guests are bounced from the
  dashboard, and users who are allowed in can reach it) is still valid and cheap to cover. Only the
  URL is stale: `/dashboard` doesn't exist, and the admin dashboard is `/admin`. Some of this is
  already covered by `PanelAccessGateTest.php` and the site-settings tests, so check first and add
  only the missing cases.
- **Change**:
  ```php
  test('guests are redirected to the admin login', function () {
      $this->get('/admin')->assertRedirect('/admin/login');
  });
  test('a user with an access level can reach the admin dashboard', function () {
      $this->actingAs(User::factory()->create(['access_level' => AccessLevel::LOCAL]))->get('/admin')->assertOk();
  });
  test('a user with NULL access_level is forbidden', function () {
      $this->actingAs(User::factory()->create(['access_level' => null]))->get('/admin')->assertForbidden();
  });
  ```
  (Drop any case that duplicates `PanelAccessGateTest`.)
- **VALIDATE**: `php artisan test --filter=Dashboard`

### Phase 5: Seeders (fresh or dev installs only)

**Task 33 [LOW]: ChurchSeeder crashes on the current schema**
- **Finding**: `church-seeder-crashes`
- **File**: `database/seeders/ChurchSeeder.php:20` (every row)
- **Decision**: nothing calls it (`DatabaseSeeder` doesn't). **Update it** if the owner wants demo
  locator data. Otherwise **delete** it. Recommended: update, because it's the only locator
  fixture. Replace `state` with `suburb`, convert `service_times` to
  `['service_type' => ..., 'time' => ..., 'days' => [...]]`, and map region names to `region_id`.
- **VALIDATE**: a Pest test with `$this->seed(ChurchSeeder::class)` that doesn't throw and gives `Church::count() > 0` (in-memory DB). Run `php artisan test --filter=Seeder`.

**Task 34 [LOW]: The DatabaseSeeder admin can't log in**
- **Finding**: `database-seeder-admin-cannot-login`
- **Files**: `database/seeders/DatabaseSeeder.php:18`, `database/factories/UserFactory.php`
- **Change**: add a `national()` state to `UserFactory`
  (`fn () => ['access_level' => AccessLevel::NATIONAL, 'role' => UserRole::ADMINISTRATOR]`) and
  seed with `User::factory()->national()->create([...])`.
- **VALIDATE**: a Pest test that runs `$this->seed()` and then `User::where('email', 'admin@upci.co.nz')->first()->canAccessPanel(filament()->getDefaultPanel())` is true.

---

## Testing Strategy

### Automated
- Every Phase 1 and Phase 3 task adds at least one Pest regression test. Security ones go in
  `SecurityRegressionTest.php` so reintroducing them "fails loudly", as that file's docblock says.
- Filament page tests: use `Livewire::test(PageClass::class, ['record' => $id])` with `actingAs`.
  Mirror `GalleryOwnershipTest.php`.
- Tasks 1, 2 and 3 **must** each include a negative (forbidden) case, not only the happy path.

### Manual / browser
- `/admin/gallery-items/create` as a national user; edit a church and pick an address (coordinates save); `/find-church` (the popup escapes HTML, an unmappable church shows the fallback, multi-day filtering works, no `tel:null`); `/gallery` region tabs; the homepage directory pastor; `/login` redirects to `/admin/login`.

### Edge cases
- A regional user with `church_id = null` (Task 2 self-edit) and with `region_id = null` (Task 5).
- A local user with `church_id = null` creating attendance (Task 7 must give a 403, not an orphaned row).
- An old shared locator link with a scalar `?service_day=sunday` (Task 13 must still work).
- Existing national users attached to churches (users 8 and 16) must remain editable by national users only.

## Validation Commands

```bash
# Level 1: syntax on every touched PHP file
git diff --name-only | grep '\.php$' | xargs -n1 php -l

# Level 2: routes
php artisan route:list --path=login          # GET login -> closure redirect
php artisan route:list --path=verify-email   # no routes
php artisan route:list | grep -c 'admin/register\|admin/password/reset'   # 0

# Level 3: no v3 / closure-injection leftovers
grep -rn 'fn (\$s)' app/Filament                        # empty
grep -rn 'MorphToSelect' -A6 app/Filament | grep -n nullable   # empty
grep -rn "visible(fn (\$record)" app/Filament/Resources/Churches/Tables   # empty

# Level 4: frontend
npm run build

# Level 5: FULL SUITE, which must end with 0 failures
php artisan test
```

## Acceptance Criteria

- [ ] `php artisan test` ends with **0 failed** (baseline was 16 failed / 121 passed; the pass count changes because stale files are deleted and new tests added)
- [ ] `/admin/gallery-items/create`, `/{id}` and `/{id}/edit` return 200 for national users and 403 (create) for regional users
- [ ] A regional user can't update a national or regional user; a regional user can edit their own profile
- [ ] "Assign Leadership" performs no writes before save, authorises every user it changes, and is disabled for local users
- [ ] Church latitude and longitude persist from the admin form
- [ ] Locator popups render church fields as text, never as HTML
- [ ] `GET /login` redirects to `/admin/login`; unknown `/api/*` paths return a JSON 404
- [ ] Task 26 (AGS scheduling) is either implemented or recorded as "display date only" by the owner
- [ ] Task 27's proxy list has been confirmed by the owner before deploy

## Risks and Mitigations

| Risk | Likelihood | Impact | Mitigation |
|---|---|---|---|
| Tightening `UserPolicy` locks regional users out of workflows they rely on (for example editing a regional peer) | MED | MED | This is intended. Confirm with the owner and note it in the PR |
| `trustProxies` misconfigured in production breaks `ip()` and HTTPS detection | MED | HIGH | Owner supplies the proxy CIDR; deploy with `TRUSTED_PROXIES` set; smoke-test the login redirect scheme |
| Address-finder JS still doesn't reach the hidden inputs | MED | HIGH | Use `$wire.set`, not DOM selectors; the Pest test covers the server side and a manual pick covers the JS |
| Catch-all regex change breaks an SPA route that starts with `api` | LOW | MED | The regex excludes only `api` and `api/…` exactly; `/apostolic-bible-college` still matches |
| Deleting the auth routes removes a route name something still calls | LOW | MED | Run the grep in the Task 29 GOTCHA before deleting |

## Notes

**Order of work.** Do Tasks 1 and 2 first and ship them on their own. They're the only
critical items and each is a few lines. Land Task 29 before Tasks 30-32, because the new
`FilamentAuthTest` asserts the fixed routing.

**Duplicate merging.** 49 findings became 34 tasks. The merges: morphto crash + gallery bypass;
two takeover reports + self-edit 404; two leadership-hijack reports + create-discard; two
church-region reports; two attendance reports; two AGS reports; two shadowing reports + v3
redirect targets + two verify-email reports + the unknown-API catch-all; three stale-test reports,
which are split per directory for the delete/rewrite decision.

---

## Appendix: Refuted findings

None. Every finding from the audit was confirmed on verification (0 refuted). These parts were
**downgraded or partly refuted** inside confirmed findings. Don't re-investigate them:

- `calendar-no-error-state`: the from/to month arithmetic (`'2026-00-01'`, `'2026-13-01'`) is **not** a live bug. The range is a superset of the viewed month and compares correctly on sqlite. It's latent only for MySQL DATE columns.
- `stale-dashboard-test`: guests getting 200 on `/dashboard` is **not** an auth hole. It's the static SPA shell with no data.
- `auth-routes-shadowed-by-spa-catchall`: the claim that "guests redirected by `auth` middleware land on the SPA" is overstated. Filament's middleware redirects to the panel login itself, and nothing in the app links to `/login`.
- `church-seeder-crashes`: "a fresh install gets no locator data" is overstated. `DatabaseSeeder` never calls `ChurchSeeder`.
- `nav-grandchild-footer-external-url`: latent. No current menu row has an external URL at those levels.
- `nav-ministers-login-dead`: a content issue, not a code bug.
- `ags-*-published-at`: only a defect if `published_at` is meant as a go-live date. That needs an owner decision (Task 26).
- `trust-all-proxies-bypasses-throttle`: exploitability depends on whether the edge proxy overwrites X-Forwarded-For, which can't be verified from the repo.
- Audit side-effect (not a finding): a verifier created an empty, gitignored `database/database.sqlite` (0 bytes, unused, since `.env` uses `upci`). The owner can delete it by hand.
