# Feature: User Access Levels — Local, Regional, National

## Summary

Introduce a first-class `access_level` dimension on users (Local / Regional / National), promote `organizational_region` to a proper `regions` lookup table, replace the current scattered role-based scoping in Filament resources with a shared trait + Laravel policies, and apply consistent coverage across every admin resource. Local users see one church, Regional users see one region (all churches in it), National users see everything. Writes are enforced via policies (not just list queries). Public frontend and API contracts are preserved.

## User Story

As a **UPCI NZ admin user**
I want **the admin panel to show and allow edits only on data within my authority (my church, my region, or nationwide)**
So that **local pastors can manage their own church without accidentally touching another congregation's data, regional presbyters can oversee their region, and national executives retain full visibility**.

## Problem Statement

Right now, scoping is partial and inconsistent:
- Three resources (Churches / Users / Attendances) scope *reads* via hand-rolled `getEloquentQuery()` branches keyed on role (`hasFullAccess` / `isPastor` / `isRegionalPresbyter`), using `users.assigned_region` (a single varchar) and `churches.organizational_region` (a single varchar).
- Four more resources (Events, Departments, MenuItems, Pages) allow only "national" (hasFullAccess) writes but permit anyone to list — some inconsistency; Events is meant to be a single nationwide aggregated view anyway.
- Two resources (AGSUpdates, GalleryItems) already gate `canViewAny` to national only.
- No Laravel policies exist — writes past `getEloquentQuery()` are implicitly trusted.
- The role enum conflates "what you do" (pastor, elder, deacon) with "what you can see" (regional_presbyter, executive_board, administrator), blocking clean extension (e.g., an elder with regional access, or later, a department head).

Testable success signals:
- Log in as a user with `access_level=local, church_id=5` → Churches list shows 1 row (id=5); Users list shows only users with `church_id=5`; Attendances list shows only `church_id=5`; Events list shows all events (unscoped read); Events/Departments/CMS resources are either hidden from nav or strictly read-only.
- Log in as `access_level=regional, region_id=2` → Churches list shows only churches in region 2; same propagation through Users and Attendances.
- Log in as `access_level=national` → every resource fully accessible.

## Solution Statement

Add an orthogonal `access_level` enum on `users` (decouples from `role`), promote `organizational_region` to a `regions` table with an FK from `churches` and `users`, implement a `ScopesToAccessLevel` trait to centralise the list-query scoping logic, and use Laravel policies to enforce create/update/delete. Design the trait to take per-resource closures ("how do I reach church_id?", "how do I reach region_id?") so adding department-level permissions later is a single new closure.

## Metadata

| Field            | Value                                                                                     |
| ---------------- | ----------------------------------------------------------------------------------------- |
| Type             | ENHANCEMENT (+ minor REFACTOR of existing partial scoping)                                |
| Complexity       | MEDIUM-HIGH (schema + trait + 10 resources + policies + backfill)                         |
| Systems Affected | Laravel DB schema, Eloquent models, Filament resources/forms/tables/infolists, public API |
| Dependencies     | Laravel 11, Filament v4, PHP 8.4 (all already present)                                    |
| Estimated Tasks  | 14                                                                                        |

---

## UX Design

### Before State
```
╔═══════════════════════════════════════════════════════════════════════════════╗
║                              BEFORE STATE                                      ║
╠═══════════════════════════════════════════════════════════════════════════════╣
║                                                                               ║
║   User logs into /admin                                                       ║
║        │                                                                      ║
║        ▼                                                                      ║
║   ┌────────────────────────────────────────────────────────────┐             ║
║   │ Filament dashboard                                          │             ║
║   │ Nav: Churches, Users, Attendances, Events, Departments,    │             ║
║   │      CMS Pages, Menu Items, Gallery, AGS Updates, Messages │             ║
║   └────────────────────────────────────────────────────────────┘             ║
║        │                                                                      ║
║        ▼ clicks "Churches"                                                    ║
║   ┌────────────────────────────────────────────────────────────┐             ║
║   │ Each resource hand-rolls getEloquentQuery():                │             ║
║   │  - hasFullAccess → all rows                                 │             ║
║   │  - isPastor → where church_id = user.church_id             │             ║
║   │  - isRegionalPresbyter → where organizational_region =     │             ║
║   │                           user.assigned_region              │             ║
║   │  - otherwise → all rows (bug: falls through)                │             ║
║   │ No write-side enforcement.                                  │             ║
║   └────────────────────────────────────────────────────────────┘             ║
║                                                                               ║
║   PAIN_POINTS:                                                                ║
║    - Scoping only in 3 resources; rest are inconsistent                       ║
║    - "Fall through" branch lets unknown-role users see everything             ║
║    - Writes aren't enforced past the list query                               ║
║    - role column mixes function (pastor) with permission (regional)           ║
║    - assigned_region is a loose varchar, no referential integrity             ║
║    - No path to add department-level permissions without more drift           ║
║                                                                               ║
╚═══════════════════════════════════════════════════════════════════════════════╝
```

### After State
```
╔═══════════════════════════════════════════════════════════════════════════════╗
║                               AFTER STATE                                      ║
╠═══════════════════════════════════════════════════════════════════════════════╣
║                                                                               ║
║   User logs into /admin                                                       ║
║        │                                                                      ║
║        ▼                                                                      ║
║   ┌────────────────────────────────────────────────────────────┐             ║
║   │ Filament dashboard                                          │             ║
║   │ Nav (scoped by access_level via Policy::viewAny):           │             ║
║   │  local:    Churches, Users, Attendances, Events (view-only) │             ║
║   │  regional: Churches, Users, Attendances, Events (view-only) │             ║
║   │            + Departments (view-only)                        │             ║
║   │  national: Everything above + full CRUD on all CMS content  │             ║
║   └────────────────────────────────────────────────────────────┘             ║
║        │                                                                      ║
║        ▼ clicks "Churches"                                                    ║
║   ┌────────────────────────────────────────────────────────────┐             ║
║   │ ScopesToAccessLevel trait centralises the getEloquentQuery  │             ║
║   │ logic:                                                      │             ║
║   │   match($user->access_level) {                              │             ║
║   │     national → unscoped;                                    │             ║
║   │     regional → $query through regionalPath($user->region_id);│            ║
║   │     local    → $query through localPath($user->church_id);  │             ║
║   │     null     → empty query (safe default-deny)              │             ║
║   │   }                                                         │             ║
║   └────────────────────────────────────────────────────────────┘             ║
║        │                                                                      ║
║        ▼ tries to edit a row they shouldn't                                   ║
║   ┌────────────────────────────────────────────────────────────┐             ║
║   │ {Resource}Policy::update($user, $record) returns false     │             ║
║   │ → 403 Forbidden (Filament honours policies automatically)   │             ║
║   └────────────────────────────────────────────────────────────┘             ║
║                                                                               ║
║   EVENTS SPECIAL CASE: viewAny+view always allowed (national aggregated      ║
║   view per Q5); create/update/delete → national only.                         ║
║                                                                               ║
║   VALUE_ADDS:                                                                 ║
║    - Uniform read+write scoping across every resource                         ║
║    - Safe default-deny (unknown/null access_level sees nothing)               ║
║    - Orthogonal role vs access_level — roles can stay descriptive             ║
║    - Regions as first-class rows — referential integrity + better forms       ║
║    - Single extension point for future department-level permissions           ║
║                                                                               ║
║   DATA_FLOW (list-page render, regional user):                                ║
║     Request → Filament Page → {Resource}::getEloquentQuery()                  ║
║       → ScopesToAccessLevel::scopeForUser(query, user)                        ║
║       → apply regionalPath closure: whereHas('church', ..region_id=X)         ║
║       → render only rows in that region.                                      ║
║                                                                               ║
║   DATA_FLOW (row edit, local user):                                           ║
║     Filament → Policy::update($user, $record)                                 ║
║       → $record->church_id === $user->church_id ? allow : 403                 ║
║                                                                               ║
╚═══════════════════════════════════════════════════════════════════════════════╝
```

### Interaction Changes
| Location | Before | After | User Impact |
|----------|--------|-------|-------------|
| `/admin` nav (local user) | All 10 resources visible | Churches, Users, Attendances + Events (read-only); rest hidden | No accidental edits of CMS or other-church data |
| `/admin/churches` (local) | 1 row (via partial scope) | 1 row (via trait) | Same result, now safer |
| `/admin/churches/{id}/edit` (local, wrong church) | Could submit POST anyway | 403 from `ChurchPolicy::update()` | Enforced writes |
| `/admin/users` (regional) | Users whose `church.organizational_region = assigned_region` | Users whose `church.region_id = region_id` | Behaviourally identical, stricter data |
| `/admin/events` (any role) | Blocked for non-national writes, inconsistent listing | Unrestricted view (aggregated national calendar); only national creates/edits | Matches the "one aggregated events view" goal |
| Churches admin form — "Region" field | Hardcoded Select with 3 strings | Select relationship on `regions` table | Option list comes from DB, consistent with other lookups |
| Public `/find-church` (Vue) | Unchanged | Unchanged | Fully public per decision Q7 |
| `GET /api/churches?organizational_region=X` | Filters by varchar match | Filters by `region.name` match | Same wire shape — contract preserved |

---

## Mandatory Reading

| Priority | File | Lines | Why Read This |
|----------|------|-------|---------------|
| P0 | `app/Filament/Resources/Churches/ChurchResource.php` | 28-46 | The exact scoping pattern to replace via trait |
| P0 | `app/Filament/Resources/Attendances/AttendanceResource.php` | 27-47 | Same pattern; second example to confirm shape |
| P0 | `app/Filament/Resources/Users/UserResource.php` | 28-48 | Third example with `whereHas('church', ...)` pattern |
| P0 | `app/Enums/UserRole.php` | 1-92 | Contains the deprecated helpers `hasFullAccess`, `isPastor`, `isRegionalPresbyter` that will be deleted or redirected |
| P0 | `app/Models/User.php` | 1-84 | Model to extend (add cast, region relation, helpers) |
| P0 | `app/Models/Church.php` | 1-215 | Model to extend (region relation, drop organizational_region usage) |
| P1 | `app/Http/Controllers/Api/ChurchController.php` | 1-300 | Public API that must continue to expose `organizational_region` string in responses + filter params (contract preservation) |
| P1 | `app/Filament/Resources/Churches/Schemas/ChurchForm.php` | 180-205 | `Select::make('organizational_region')` to migrate to `region_id` relationship |
| P1 | `app/Filament/Resources/Users/Schemas/UserForm.php` | 68-85 | `Select::make('assigned_region')` to migrate to `region_id` relationship with access-level gating |
| P1 | `app/Filament/Resources/Churches/Tables/ChurchesTable.php` | 25-40 | `TextColumn::make('organizational_region')` to migrate to `region.name` |
| P1 | `app/Filament/Resources/Churches/Schemas/ChurchInfolist.php` | 45-70 | `TextEntry::make('organizational_region')` to migrate |
| P1 | `app/Filament/Resources/Users/Schemas/UserInfolist.php` | 55-70 | `TextEntry::make('assigned_region')` to migrate |
| P2 | `app/Filament/Resources/AGSUpdates/AGSUpdateResource.php` | 30-40 | Existing `canViewAny` pattern for national-only — baseline behaviour being formalised |
| P2 | `app/Filament/Resources/GalleryItems/GalleryItemResource.php` | 30-40 | Same |
| P2 | `app/Filament/Resources/Events/EventResource.php` | 28-40 | Existing `getEloquentQuery` returning empty for non-national — will be relaxed to unrestricted list, policies gate writes |
| P2 | `app/Filament/Resources/Departments/DepartmentResource.php` | 30-45 | Same pattern |
| P2 | `app/Filament/Resources/MenuItems/MenuItemResource.php` | 30-45 | Same — becomes NationalOnly |
| P2 | `app/Filament/Resources/Pages/PageResource.php` | 30-45 | Same |
| P2 | `app/Filament/Resources/Churches/Pages/ListChurches.php` | 1-30 | `$canCreate = hasFullAccess` pattern to either replace with policy or leave as-is |
| P2 | `app/Filament/Resources/Users/Pages/ListUsers.php` | 1-30 | Same |
| P3 | `bootstrap/app.php` | whole file | Where to register global middleware/policies in Laravel 11 style |
| P3 | `database/migrations/2025_10_11_110157_create_user_roles_table.php` | whole | Unused pivot — not touched here, but worth knowing it exists |

**External Documentation:**
| Source | Section | Why Needed |
|--------|---------|------------|
| [Filament v4 Resources — getEloquentQuery](https://filamentphp.com/docs/4.x/resources/overview#customizing-the-eloquent-query) | "Customizing the Eloquent query" | How Filament consumes the scoped query on list pages and for loading records on edit/view pages |
| [Filament v4 Resources — Authorization](https://filamentphp.com/docs/4.x/resources/overview#authorization) | "Authorization" | How Filament auto-discovers Laravel policies for `viewAny/view/create/update/delete` |
| [Laravel 11 Policies](https://laravel.com/docs/11.x/authorization#creating-policies) | "Creating Policies" | Standard `php artisan make:policy` pattern, auto-registration in Laravel 11 (no AuthServiceProvider needed since 11.x) |
| [Laravel 11 Eloquent — Enum Casting](https://laravel.com/docs/11.x/eloquent-mutators#enum-casting) | "Enum Casting" | How `protected function casts(): array` maps `access_level` → PHP enum |
| [Laravel Migrations — Schema::table](https://laravel.com/docs/11.x/migrations#modifying-columns) | "Dropping Columns" | SQLite requires specific syntax for dropping a column; Laravel 11 handles it but foreign keys must be dropped first |

---

## Patterns to Mirror

**EXISTING_SCOPING_PATTERN (to centralise in trait):**
```php
// SOURCE: app/Filament/Resources/Churches/ChurchResource.php:28-46
public static function getEloquentQuery(): Builder
{
    $query = parent::getEloquentQuery();
    $user = auth()->user();
    if (! $user) {
        return $query;
    }
    if (UserRole::hasFullAccess($user)) {
        return $query;
    }
    if (UserRole::isPastor($user)) {
        return $query->where('id', $user->church_id ?? -1);
    }
    if (UserRole::isRegionalPresbyter($user)) {
        return $query->where('organizational_region', $user->assigned_region);
    }
    return $query;
}
```

**EXISTING_NATIONAL_ONLY_PATTERN (basis for NationalOnlyPolicy):**
```php
// SOURCE: app/Filament/Resources/AGSUpdates/AGSUpdateResource.php:30-40
public static function canViewAny(): bool
{
    $user = auth()->user();
    if (! $user) return false;
    return UserRole::hasFullAccess($user);
}
```

**EXISTING_CREATE_GATING (in ListChurches.php):**
```php
// SOURCE: app/Filament/Resources/Churches/Pages/ListChurches.php:14-24
protected function getHeaderActions(): array
{
    $user = auth()->user();
    $canCreate = $user && UserRole::hasFullAccess($user);
    return $canCreate ? [CreateAction::make()] : [];
}
```

**MODEL_RELATION_PATTERN:**
```php
// SOURCE: app/Models/User.php:80-83
public function church(): BelongsTo
{
    return $this->belongsTo(Church::class);
}
```

**ENUM_STYLE:**
```php
// SOURCE: app/Enums/UserRole.php:5-32  — new AccessLevel enum mirrors this shape
enum UserRole: string
{
    case MEMBER = 'member';
    // ...
    public function getLabel(): string { return match ($this) { /* ... */ }; }
    public static function getOptions(): array { return collect(self::cases())->mapWithKeys(...); }
}
```

**MIGRATION_STYLE:**
```php
// SOURCE: database/migrations/2026_03_10_000004_add_assigned_region_to_users_table.php
return new class extends Migration {
    public function up(): void {
        Schema::table('users', function (Blueprint $table) {
            $table->string('assigned_region')->nullable()->after('role');
        });
    }
    public function down(): void {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('assigned_region');
        });
    }
};
```

**CHURCH_FORM_SELECT (pattern for Region relationship):**
```php
// SOURCE: app/Filament/Resources/Churches/Schemas/ChurchForm.php:183-202
// BEFORE (hardcoded):
Select::make('organizational_region')
    ->label('Region')
    ->options([
        'North Region' => 'North Region',
        'Central Region' => 'Central Region',
        'South Region' => 'South Region',
    ])
    ->placeholder('Select region')
    ->searchable(),
// AFTER (to mirror):
Select::make('region_id')
    ->label('Region')
    ->relationship('region', 'name')
    ->preload()
    ->searchable(),
```

**API_CONTRACT_PRESERVATION (ChurchController):**
```php
// SOURCE: app/Http/Controllers/Api/ChurchController.php:210-213
// BEFORE:
Church::active()->whereNotNull('organizational_region')->distinct()->pluck('organizational_region')
// AFTER (preserve output shape — still a flat array of 3 strings):
\App\Models\Region::orderBy('sort_order')->pluck('name')
```

---

## Files to Change

### Create (new files)

| File | Purpose |
|------|---------|
| `database/migrations/2026_04_20_100001_create_regions_table.php` | `regions` lookup table + seed 3 rows |
| `database/migrations/2026_04_20_100002_add_region_id_to_churches_table.php` | FK + backfill + drop `organizational_region` |
| `database/migrations/2026_04_20_100003_add_access_level_and_region_id_to_users_table.php` | Add columns + backfill from existing `role`/`assigned_region` + drop `assigned_region` |
| `app/Enums/AccessLevel.php` | LOCAL / REGIONAL / NATIONAL enum with helpers |
| `app/Models/Region.php` | Eloquent model with `churches()` + `users()` hasMany |
| `app/Filament/Concerns/ScopesToAccessLevel.php` | Shared trait for resource `getEloquentQuery()` using closures |
| `app/Policies/ChurchPolicy.php` | viewAny/view/create/update/delete using access level |
| `app/Policies/UserPolicy.php` | Same (with self-view always allowed) |
| `app/Policies/AttendancePolicy.php` | Same |
| `app/Policies/EventPolicy.php` | viewAny/view = true for all; writes = national-only |
| `app/Policies/DepartmentPolicy.php` | viewAny/view = true for all; writes = national-only |
| `app/Policies/NationalOnlyPolicy.php` | Abstract base: all methods return `$user->isNational()`. Used by Pages, MenuItems, GalleryItems, AGSUpdates, ContactMessages |
| `app/Policies/PagePolicy.php` | extends NationalOnlyPolicy |
| `app/Policies/MenuItemPolicy.php` | extends NationalOnlyPolicy |
| `app/Policies/GalleryItemPolicy.php` | extends NationalOnlyPolicy |
| `app/Policies/AGSUpdatePolicy.php` | extends NationalOnlyPolicy |
| `app/Policies/ContactMessagePolicy.php` | extends NationalOnlyPolicy |
| `tests/Feature/AccessLevelScopingTest.php` | Fixture users per access level; assert list contents + policy denials |

### Update

| File | Change |
|------|--------|
| `app/Models/User.php` | Replace `assigned_region` in fillable → `access_level` + `region_id`; cast `access_level` to AccessLevel; add `region()` belongsTo + `isLocal()` / `isRegional()` / `isNational()` / `canAccessChurch(Church)` / `canAccessRegion(Region)` |
| `app/Models/Church.php` | Drop `organizational_region` from fillable; add `region()` belongsTo; remove or rewrite `scopeByRegion` if needed |
| `app/Enums/UserRole.php` | Remove (or reroute to AccessLevel) `hasFullAccess`, `isPastor`, `isRegionalPresbyter` helpers — callers migrate to `$user->isLocal/Regional/National()` |
| `app/Filament/Resources/Churches/ChurchResource.php` | Replace `getEloquentQuery()` with `use ScopesToAccessLevel;` + trait-expected methods |
| `app/Filament/Resources/Users/UserResource.php` | Same — via the trait with `whereHas('church', ...)` closure |
| `app/Filament/Resources/Attendances/AttendanceResource.php` | Same |
| `app/Filament/Resources/Events/EventResource.php` | Remove the existing `getEloquentQuery` override (events are now an aggregated national view — all users see all events); EventPolicy gates writes |
| `app/Filament/Resources/Departments/DepartmentResource.php` | Same as Events |
| `app/Filament/Resources/Pages/PageResource.php` | Remove `getEloquentQuery` override; PagePolicy handles everything |
| `app/Filament/Resources/MenuItems/MenuItemResource.php` | Same |
| `app/Filament/Resources/GalleryItems/GalleryItemResource.php` | Remove `canViewAny` override; GalleryItemPolicy handles it |
| `app/Filament/Resources/AGSUpdates/AGSUpdateResource.php` | Same |
| `app/Filament/Resources/Churches/Schemas/ChurchForm.php:183-202` | `Select::make('organizational_region')` → `Select::make('region_id')->relationship('region','name')` |
| `app/Filament/Resources/Churches/Tables/ChurchesTable.php:31` | `TextColumn::make('organizational_region')` → `TextColumn::make('region.name')->label('Region')` |
| `app/Filament/Resources/Churches/Schemas/ChurchInfolist.php:48-66` | `TextEntry::make('organizational_region')` → `TextEntry::make('region.name')`, guard on `$record->region` |
| `app/Filament/Resources/Users/Schemas/UserForm.php:68-85` | Replace `assigned_region` Select → `region_id` relationship Select; add `Select::make('access_level')` with 3 options; show region only when `access_level === 'regional'` |
| `app/Filament/Resources/Users/Schemas/UserInfolist.php:55-70` | `TextEntry::make('assigned_region')` → `TextEntry::make('region.name')` + `TextEntry::make('access_level')` |
| `app/Filament/Resources/Churches/Pages/ListChurches.php:14-24` | Replace `UserRole::hasFullAccess` with policy check (or delete — policy takes over) |
| `app/Filament/Resources/Users/Pages/ListUsers.php:14-24` | Same |
| `app/Http/Controllers/Api/ChurchController.php:25-27,64,101,142,210-213,277` | Rewire filter, validation, and response formatter so public API still accepts/returns `organizational_region` string via `region.name` |

---

## NOT Building (Scope Limits)

Explicit exclusions to prevent scope creep:

- **Department-level permissions.** Per user note, this is a future extension. The trait is designed to accept a department closure later without refactoring callers. Not built now.
- **New dashboard widgets.** Per Q6=b, don't add widgets. Policies naturally hide nav items the user can't access; that's the scope-aware behaviour. AccountWidget + FilamentInfoWidget stay unchanged (neither displays scoped data).
- **Public-facing frontend changes.** Per Q7=fully public, `resources/js/views/*.vue` and `/api/*` public endpoints remain open to everyone. API response shape preserved (still returns `organizational_region: "North Region"` strings).
- **Renaming `organizational_region` in the public API.** Keep the JSON key identical for Vue-frontend contract. Internally map from the new `region.name`.
- **A `user_regions` pivot.** Per Q2=no, each regional user oversees exactly one region. Keep a single `users.region_id` FK.
- **Touching or deleting the unused `user_roles` pivot table** (from 2025-10-11 migration). Neither used nor harmful; leave for separate cleanup.
- **Self-service role/access changes.** Only national users can grant access to others; enforced by `UserPolicy::update`.
- **Removing the `UserRole` enum or its roles.** Role stays as the "what you do" label (pastor, elder, deacon). Only the scope-detection helpers get removed/deprecated.
- **Automated policy registration magic.** Laravel 11 auto-registers policies by class-name convention (`{Model}Policy` in `App\Policies`); no explicit registration needed unless a model lives in an unusual namespace. Don't add a custom policy mapper.
- **Changing any Vue / CMS content.** Nothing under `resources/js/`, `resources/views/`, or `public/build/` is touched. No frontend rebuild needed.

---

## Step-by-Step Tasks

Execute in order. Every task ends with an executable verification step.

### Task 1: CREATE `database/migrations/2026_04_20_100001_create_regions_table.php`

- **ACTION**: Create `regions` table; seed North / Central / South in the same migration.
- **IMPLEMENT**:
  ```php
  Schema::create('regions', function (Blueprint $table) {
      $table->id();
      $table->string('name')->unique();
      $table->string('slug')->unique();
      $table->integer('sort_order')->default(0);
      $table->timestamps();
  });
  DB::table('regions')->insert([
      ['name' => 'North Region',   'slug' => 'north',   'sort_order' => 1, 'created_at' => now(), 'updated_at' => now()],
      ['name' => 'Central Region', 'slug' => 'central', 'sort_order' => 2, 'created_at' => now(), 'updated_at' => now()],
      ['name' => 'South Region',   'slug' => 'south',   'sort_order' => 3, 'created_at' => now(), 'updated_at' => now()],
  ]);
  ```
- **MIRROR**: `database/migrations/2025_10_24_053731_create_menu_items_table.php` for Schema::create shape; `database/migrations/2026_04_19_000004_seed_departments_and_menu_items.php` for seeding-in-migration pattern.
- **GOTCHA**: SQLite. Don't use `->comment()` — not portable.
- **VALIDATE**: `sudo -u www-data php artisan migrate --force` then `sqlite3 /var/www/personal/upci.co.nz/upci "SELECT * FROM regions;"` should show 3 rows.

### Task 2: CREATE `app/Models/Region.php`

- **ACTION**: Eloquent model.
- **IMPLEMENT**:
  ```php
  class Region extends Model {
      protected $fillable = ['name', 'slug', 'sort_order'];
      public function churches(): HasMany { return $this->hasMany(Church::class); }
      public function users(): HasMany { return $this->hasMany(User::class); }
  }
  ```
- **MIRROR**: `app/Models/Department.php` (existing lookup-style model).
- **VALIDATE**: `sudo -u www-data HOME=/tmp php -r "/* bootstrap laravel */ echo App\Models\Region::count();"` should print `3`.

### Task 3: CREATE `database/migrations/2026_04_20_100002_add_region_id_to_churches_table.php`

- **ACTION**: Add FK, backfill, drop old column.
- **IMPLEMENT**:
  ```php
  public function up(): void {
      Schema::table('churches', fn (Blueprint $t) =>
          $t->foreignId('region_id')->nullable()->after('organizational_region')->constrained('regions')->nullOnDelete()
      );
      // Backfill by name match
      foreach (DB::table('regions')->get() as $region) {
          DB::table('churches')
              ->where('organizational_region', $region->name)
              ->update(['region_id' => $region->id]);
      }
      Schema::table('churches', fn (Blueprint $t) => $t->dropColumn('organizational_region'));
  }
  public function down(): void {
      Schema::table('churches', fn (Blueprint $t) => $t->string('organizational_region')->nullable());
      foreach (DB::table('regions')->get() as $region) {
          DB::table('churches')
              ->where('region_id', $region->id)
              ->update(['organizational_region' => $region->name]);
      }
      Schema::table('churches', function (Blueprint $t) {
          $t->dropForeign(['region_id']);
          $t->dropColumn('region_id');
      });
  }
  ```
- **GOTCHA**: In SQLite, `dropForeign` is a no-op and `dropColumn` rebuilds the table. `doctrine/dbal` may be required for older Laravel; Laravel 11 handles SQLite column drops natively.
- **VALIDATE**: After migrate, `sqlite3 ... "SELECT name, region_id FROM churches LIMIT 5;"` shows the backfill. `"PRAGMA table_info(churches);"` shows no `organizational_region` column.

### Task 4: CREATE `app/Enums/AccessLevel.php`

- **ACTION**: The new access enum.
- **IMPLEMENT**:
  ```php
  namespace App\Enums;
  enum AccessLevel: string {
      case LOCAL    = 'local';
      case REGIONAL = 'regional';
      case NATIONAL = 'national';

      public function getLabel(): string { return match ($this) {
          self::LOCAL    => 'Local (single church)',
          self::REGIONAL => 'Regional (one region)',
          self::NATIONAL => 'National (all regions)',
      }; }

      public static function getOptions(): array {
          return collect(self::cases())->mapWithKeys(fn ($l) => [$l->value => $l->getLabel()])->toArray();
      }
  }
  ```
- **MIRROR**: `app/Enums/UserRole.php:5-32`.
- **VALIDATE**: `php -r "require 'vendor/autoload.php'; var_dump(App\Enums\AccessLevel::cases());"` prints 3 cases.

### Task 5: CREATE `database/migrations/2026_04_20_100003_add_access_level_and_region_id_to_users_table.php`

- **ACTION**: Add `access_level` + `region_id`, backfill, drop `assigned_region`.
- **IMPLEMENT**:
  ```php
  public function up(): void {
      Schema::table('users', function (Blueprint $t) {
          $t->string('access_level')->nullable()->after('role');
          $t->foreignId('region_id')->nullable()->after('access_level')->constrained('regions')->nullOnDelete();
      });
      // Backfill
      // national <- EXECUTIVE_BOARD or ADMINISTRATOR
      DB::table('users')->whereIn('role', ['executive_board', 'administrator'])->update(['access_level' => 'national']);
      // regional <- REGIONAL_PRESBYTER, map region by name
      foreach (DB::table('users')->where('role', 'regional_presbyter')->get() as $u) {
          $region = DB::table('regions')->where('name', $u->assigned_region)->first();
          DB::table('users')->where('id', $u->id)->update([
              'access_level' => 'regional',
              'region_id' => $region?->id,
          ]);
      }
      // local <- anyone with a church_id but no access_level yet
      DB::table('users')->whereNull('access_level')->whereNotNull('church_id')->update(['access_level' => 'local']);
      // anyone without both: leave access_level NULL — they'll see nothing (safe default-deny)

      Schema::table('users', fn (Blueprint $t) => $t->dropColumn('assigned_region'));
  }
  public function down(): void {
      Schema::table('users', fn (Blueprint $t) => $t->string('assigned_region')->nullable()->after('role'));
      // Reverse-backfill
      foreach (DB::table('users')->whereNotNull('region_id')->get() as $u) {
          $name = DB::table('regions')->where('id', $u->region_id)->value('name');
          DB::table('users')->where('id', $u->id)->update(['assigned_region' => $name]);
      }
      Schema::table('users', function (Blueprint $t) {
          $t->dropForeign(['region_id']);
          $t->dropColumn(['region_id', 'access_level']);
      });
  }
  ```
- **GOTCHA**: Do the backfill BEFORE dropping `assigned_region` — otherwise you lose the source data needed for region-id lookup.
- **VALIDATE**: `sqlite3 ... "SELECT id, name, role, access_level, region_id FROM users;"` — existing users should have coherent `access_level` values.

### Task 6: UPDATE `app/Models/User.php`

- **ACTION**: Add cast, relation, helpers. Drop `assigned_region` from fillable.
- **IMPLEMENT**:
  ```php
  use App\Enums\AccessLevel;
  // ...
  protected $fillable = ['name', 'email', 'password', 'church_id', 'role', 'access_level', 'region_id'];
  protected function casts(): array {
      return [
          'email_verified_at' => 'datetime',
          'password' => 'hashed',
          'role' => UserRole::class,
          'access_level' => AccessLevel::class,
      ];
  }
  public function region(): BelongsTo { return $this->belongsTo(Region::class); }
  public function isLocal(): bool    { return $this->access_level === AccessLevel::LOCAL; }
  public function isRegional(): bool { return $this->access_level === AccessLevel::REGIONAL; }
  public function isNational(): bool { return $this->access_level === AccessLevel::NATIONAL; }
  public function canAccessChurch(?Church $c): bool {
      if (! $c) return false;
      return match (true) {
          $this->isNational() => true,
          $this->isRegional() => $c->region_id === $this->region_id,
          $this->isLocal()    => $c->id === $this->church_id,
          default             => false,
      };
  }
  ```
- **MIRROR**: `app/Models/User.php:24-55` (existing casts pattern); `app/Models/User.php:80-83` (belongsTo pattern).
- **VALIDATE**: Inline bootstrap + `App\Models\User::find(1)->isNational()` returns a boolean without error.

### Task 7: UPDATE `app/Models/Church.php`

- **ACTION**: Add region relation; drop `organizational_region` from fillable; remove/replace `scopeByRegion` if it referenced that column.
- **IMPLEMENT**:
  ```php
  protected $fillable = [/* remove 'organizational_region' */ /* keep the rest */];
  public function region(): BelongsTo { return $this->belongsTo(Region::class); }
  ```
- **GOTCHA**: `app/Models/Church.php:125-128` has `scopeByRegion($query, $region)` using the `region` column (not `organizational_region`) — leave it alone; it scopes on the NZ-geographic `region` column, not the org one.
- **VALIDATE**: `App\Models\Church::first()->region?->name` returns a string or null.

### Task 8: CREATE `app/Filament/Concerns/ScopesToAccessLevel.php`

- **ACTION**: Reusable trait. Each resource declares two closures.
- **IMPLEMENT**:
  ```php
  namespace App\Filament\Concerns;
  use App\Enums\AccessLevel;
  use Closure;
  use Illuminate\Database\Eloquent\Builder;
  trait ScopesToAccessLevel {
      public static function getEloquentQuery(): Builder {
          $query = parent::getEloquentQuery();
          $user  = auth()->user();
          if (! $user || ! $user->access_level) {
              return $query->whereRaw('1=0'); // safe default-deny
          }
          return match ($user->access_level) {
              AccessLevel::NATIONAL => $query,
              AccessLevel::REGIONAL => (static::regionalScope())($query, $user->region_id ?? -1),
              AccessLevel::LOCAL    => (static::localScope())($query, $user->church_id ?? -1),
          };
      }
      /** Closure: (Builder $q, int $churchId) => Builder */
      abstract protected static function localScope(): Closure;
      /** Closure: (Builder $q, int $regionId) => Builder */
      abstract protected static function regionalScope(): Closure;
  }
  ```
- **GOTCHA**: PHP traits can't declare `abstract` methods in all versions. For PHP 8.4 they're legal; ensure it works. If not, switch to static methods the resource is expected to define, and call them defensively with `method_exists`.
- **VALIDATE**: `php -l app/Filament/Concerns/ScopesToAccessLevel.php` passes.

### Task 9: UPDATE `ChurchResource`, `UserResource`, `AttendanceResource` to use the trait

- **ACTION**: Replace `getEloquentQuery()` body with `use ScopesToAccessLevel;` + define the two closures.
- **IMPLEMENT** (example for `ChurchResource`):
  ```php
  use App\Filament\Concerns\ScopesToAccessLevel;
  use Closure;

  class ChurchResource extends Resource {
      use ScopesToAccessLevel;
      // ... existing props ...
      protected static function localScope(): Closure {
          return fn (Builder $q, int $churchId) => $q->where('id', $churchId);
      }
      protected static function regionalScope(): Closure {
          return fn (Builder $q, int $regionId) => $q->where('region_id', $regionId);
      }
  }
  ```
  For `UserResource`:
  ```php
  protected static function localScope(): Closure {
      return fn (Builder $q, int $churchId) => $q->where('church_id', $churchId);
  }
  protected static function regionalScope(): Closure {
      return fn (Builder $q, int $regionId) => $q->whereHas('church', fn ($c) => $c->where('region_id', $regionId));
  }
  ```
  For `AttendanceResource`:
  ```php
  protected static function localScope(): Closure {
      return fn (Builder $q, int $churchId) => $q->where('church_id', $churchId);
  }
  protected static function regionalScope(): Closure {
      return fn (Builder $q, int $regionId) => $q->whereHas('church', fn ($c) => $c->where('region_id', $regionId));
  }
  ```
- **VALIDATE**: `sudo -u www-data php artisan about` shows no errors. Opening `/admin/churches` while logged in as each access-level user returns the expected row count (manual check via Task 14).

### Task 10: CREATE policies

- **ACTION**: One policy per scoped resource + one `NationalOnlyPolicy` base for CMS resources.
- **IMPLEMENT** — `ChurchPolicy`:
  ```php
  namespace App\Policies;
  use App\Models\{Church, User};
  class ChurchPolicy {
      public function viewAny(User $user): bool { return (bool) $user->access_level; }
      public function view(User $user, Church $record): bool { return $user->canAccessChurch($record); }
      public function create(User $user): bool { return $user->isNational(); }
      public function update(User $user, Church $record): bool {
          return $user->isNational() || ($user->isRegional() && $record->region_id === $user->region_id);
      }
      public function delete(User $user, Church $record): bool { return $user->isNational(); }
  }
  ```
  `UserPolicy`:
  ```php
  public function view(User $actor, User $target): bool {
      if ($actor->id === $target->id) return true; // always self
      return $actor->canAccessChurch($target->church);
  }
  public function update(User $actor, User $target): bool {
      if ($actor->id === $target->id) return true;
      return $actor->isNational() || ($actor->isRegional() && $target->church?->region_id === $actor->region_id);
  }
  // create: national only — avoids local pastors inviting users into other churches
  public function create(User $u): bool { return $u->isNational(); }
  public function delete(User $u, User $t): bool { return $u->isNational() && $u->id !== $t->id; }
  ```
  `AttendancePolicy`:
  ```php
  public function view(User $u, Attendance $a): bool { return $u->canAccessChurch($a->church); }
  public function update(User $u, Attendance $a): bool {
      return $u->isNational() || ($u->isRegional() && $a->church?->region_id === $u->region_id) || ($u->isLocal() && $a->church_id === $u->church_id);
  }
  // local users CAN create attendance for their own church
  public function create(User $u): bool { return (bool) $u->access_level; }
  ```
  `EventPolicy` + `DepartmentPolicy`:
  ```php
  public function viewAny(User $u): bool { return (bool) $u->access_level; }
  public function view(User $u, $record): bool { return (bool) $u->access_level; }
  public function create(User $u): bool { return $u->isNational(); }
  public function update(User $u, $record): bool { return $u->isNational(); }
  public function delete(User $u, $record): bool { return $u->isNational(); }
  ```
  `NationalOnlyPolicy` (abstract):
  ```php
  abstract class NationalOnlyPolicy {
      public function viewAny(User $u): bool { return $u->isNational(); }
      public function view(User $u, $r): bool { return $u->isNational(); }
      public function create(User $u): bool { return $u->isNational(); }
      public function update(User $u, $r): bool { return $u->isNational(); }
      public function delete(User $u, $r): bool { return $u->isNational(); }
  }
  ```
  Then `PagePolicy`, `MenuItemPolicy`, `GalleryItemPolicy`, `AGSUpdatePolicy`, `ContactMessagePolicy` each extend `NationalOnlyPolicy`.
- **MIRROR**: No existing policies in this codebase yet — this establishes the pattern.
- **GOTCHA**: Laravel 11 auto-registers policies by convention: `App\Models\Church` → `App\Policies\ChurchPolicy`. No `AuthServiceProvider` entry needed. Confirm by visiting the resource with a non-privileged user.
- **VALIDATE**: `php artisan route:list` runs without error; navigate to `/admin/pages` as local user → should 403 or hide from nav.

### Task 11: UPDATE Filament resources to remove obsolete overrides

- **ACTION**: Remove hand-rolled access logic now that policies + trait take over.
- **EVENTS**: Delete the `getEloquentQuery()` override from `EventResource` (line ~28-40). Events become an aggregated national view — reads unrestricted, writes gated by `EventPolicy`.
- **DEPARTMENTS**: Same as Events.
- **PAGES / MENU ITEMS**: Remove the `getEloquentQuery()` override; `NationalOnlyPolicy::viewAny` hides them entirely for non-national users.
- **AGS UPDATES / GALLERY ITEMS**: Remove the `canViewAny()` override. Policy takes over.
- **LIST PAGES**: In `ListChurches.php:14-24` and `ListUsers.php:14-24`, either:
  - Delete the custom `getHeaderActions()` — Filament will use the policy's `create()` automatically, OR
  - Leave the custom method but replace `UserRole::hasFullAccess($user)` with `$user->isNational()` (clearer).
- **VALIDATE**: `php artisan about` clean; `/admin/pages` as local user → not in nav.

### Task 12: UPDATE Filament forms/tables/infolists for the new columns

- **ChurchForm.php:183-202** → swap to `Select::make('region_id')->relationship('region', 'name')->preload()->searchable()`.
- **ChurchesTable.php:31** → `TextColumn::make('region.name')->label('Region')->sortable()`.
- **ChurchInfolist.php:48-66** → `TextEntry::make('region.name')->label('Region')->visible(fn ($record) => $record->region !== null)`; adjust the container `visible()` predicate accordingly.
- **UserForm.php:68-85**:
  - Add `Select::make('access_level')->options(App\Enums\AccessLevel::getOptions())->required()`.
  - Replace `Select::make('assigned_region')` with `Select::make('region_id')->relationship('region', 'name')->visible(fn ($get) => $get('access_level') === 'regional')->requiredIf('access_level', 'regional')`.
  - Keep `church_id` field but show/require only when `access_level === 'local'`.
- **UserInfolist.php:55-70** → `TextEntry::make('access_level')`, `TextEntry::make('region.name')`, and surface `church.name` already exists.
- **VALIDATE**: Open each form in admin; fields render; saving a user with `access_level=regional, region_id=X` succeeds and persists.

### Task 13: UPDATE public `ChurchController` API to preserve the `organizational_region` wire shape

- **ACTION**: Wire-compat layer. The public API must continue to accept and return `organizational_region` string.
- **IMPLEMENT** (sketch; apply at each referenced line):
  - `index()` filter (line 25-27):
    ```php
    if ($request->filled('organizational_region')) {
        $query->whereHas('region', fn ($r) => $r->where('name', $request->organizational_region));
    }
    ```
  - `store()` line 64 + validation `in:` rule (line 101): resolve the posted name to region_id before Eloquent create; the `in:North Region,...` validation rule stays as-is.
  - `update()` similarly (line 142).
  - `organizationalRegions()` endpoint (line 210-213): `return Region::orderBy('sort_order')->pluck('name');`.
  - `formatChurchForApi` (line 277): `'organizational_region' => $church->region?->name`.
- **GOTCHA**: Eager-load the region to avoid N+1: `Church::active()->withCoordinates()->with('region')->…`.
- **VALIDATE**:
  ```bash
  curl -s --resolve upci.b8.co.nz:80:127.0.0.1 "http://upci.b8.co.nz/api/churches-organizational-regions" | python3 -c "import json, sys; print(json.load(sys.stdin))"
  # expect: ["North Region", "Central Region", "South Region"]
  curl -s --resolve upci.b8.co.nz:80:127.0.0.1 "http://upci.b8.co.nz/api/churches?organizational_region=North%20Region" | head
  # expect: normal JSON response filtered to North churches
  ```

### Task 14: WRITE `tests/Feature/AccessLevelScopingTest.php`

- **ACTION**: Feature tests that prove scoping works.
- **IMPLEMENT** (outline):
  ```php
  test('local user sees only their church', function () {
      [$region1, $region2] = Region::factory()->count(2)->create();
      $church1 = Church::factory()->create(['region_id' => $region1->id]);
      $church2 = Church::factory()->create(['region_id' => $region1->id]);
      $user = User::factory()->create(['access_level' => 'local', 'church_id' => $church1->id]);
      $this->actingAs($user);
      // expect: visiting /admin/churches lists church1 only
      // (use Filament's testing helper or direct query via ChurchResource::getEloquentQuery())
  });
  test('regional user sees all churches in their region', /* ... */);
  test('national user sees everything', /* ... */);
  test('policy blocks cross-church edits', /* ... */);
  test('events list is unrestricted for all access levels', /* ... */);
  test('CMS pages are national-only', /* ... */);
  ```
- **MIRROR**: No existing feature tests in `tests/Feature/` for this codebase — establish the pattern. Use pest (the Laravel default in Laravel 11) or PHPUnit — check `phpunit.xml` to confirm.
- **VALIDATE**: `sudo -u www-data php artisan test --filter AccessLevelScopingTest`.

---

## Testing Strategy

### Unit / Feature Tests to Write

| Test File | Test Cases | Validates |
|-----------|------------|-----------|
| `tests/Feature/AccessLevelScopingTest.php` | local sees 1 church; regional sees all-in-region; national sees all; events unrestricted; CMS national-only | `ScopesToAccessLevel` trait + resource wiring |
| `tests/Feature/AccessLevelPolicyTest.php` | cross-church edit denied; self-view always allowed; national-only mutations | Policy correctness |
| `tests/Unit/AccessLevelEnumTest.php` | `getOptions()` shape; label strings | Enum helpers |

### Edge Cases Checklist

- [ ] User with `access_level = NULL` (bad state) sees nothing, never errors
- [ ] User with `access_level = regional` but `region_id = NULL` sees nothing
- [ ] User with `access_level = local` but `church_id = NULL` sees nothing
- [ ] Self-view: a user can always see their own record regardless of access_level
- [ ] Updating one's own access_level is denied (only national can change another user's permissions)
- [ ] Delete on Church: cascades / nullOnDelete for dependent `users.church_id` and `events.church_id` (events don't have one today — fine)
- [ ] Region delete: `nullOnDelete` on `churches.region_id` and `users.region_id` — rows survive, scope becomes empty for affected regional users
- [ ] Public API `/api/churches?organizational_region=X` still works exactly as before
- [ ] `/api/churches-organizational-regions` still returns three strings
- [ ] Creating a regional user without `region_id` → form validation error (not DB error)

---

## Validation Commands

### Level 1: Static analysis

```bash
cd /var/www/personal/upci.co.nz
vendor/bin/pint --test    # php-cs-fixer style check (pint.json in root)
```
**EXPECT**: No style violations.

### Level 2: Migrations apply cleanly

```bash
sudo -u www-data php artisan migrate --force
```
**EXPECT**: all 3 new migrations report `DONE` without warnings.

### Level 3: Feature + unit tests

```bash
sudo -u www-data php artisan test --filter "AccessLevel|Policy"
```
**EXPECT**: all green.

### Level 4: Public API smoke

```bash
curl -s --resolve upci.b8.co.nz:80:127.0.0.1 "http://upci.b8.co.nz/api/churches-organizational-regions"
curl -s --resolve upci.b8.co.nz:80:127.0.0.1 "http://upci.b8.co.nz/api/churches?organizational_region=North%20Region" | head -c 500
```
**EXPECT**: Same shape as before migration (3 region strings / JSON churches array).

### Level 5: Admin browser check (operator-run)

For each seed user (local / regional / national):
- Log in at `/admin`, confirm nav shows the expected resources only.
- Open Churches, Users, Attendances lists — row counts match expectations.
- Attempt an edit on a row outside scope via direct URL — confirm 403.
- Try to create a new Page / MenuItem / GalleryItem — should be denied for non-national users.

### Level 6: Data integrity spot-check

```bash
sudo -u www-data HOME=/tmp php -r "
require 'vendor/autoload.php';
\$app = require 'bootstrap/app.php';
\$app->make(Illuminate\\Contracts\\Console\\Kernel::class)->bootstrap();
echo 'users with NULL access_level: ', App\\Models\\User::whereNull('access_level')->count(), PHP_EOL;
echo 'churches with NULL region_id: ', App\\Models\\Church::whereNull('region_id')->count(), PHP_EOL;
echo 'regions: ', App\\Models\\Region::count(), PHP_EOL;
"
```
**EXPECT**: `regions: 3`; any NULLs are flagged so the operator can decide.

---

## Acceptance Criteria

- [ ] `regions` table populated with 3 rows (North / Central / South)
- [ ] `churches.region_id` column exists; `organizational_region` column removed; backfill preserved all existing region assignments
- [ ] `users.access_level` and `users.region_id` columns exist; `assigned_region` removed; existing users have sensible backfilled values (national / regional / local)
- [ ] `ScopesToAccessLevel` trait applied to Churches, Users, Attendances resources
- [ ] All 10+ policies registered and enforced (Filament nav + edit pages respond to policy)
- [ ] Events/Departments list is unrestricted (aggregated view); create/update/delete restricted to national
- [ ] Pages, MenuItems, GalleryItems, AGSUpdates, ContactMessages are national-only
- [ ] Public API endpoints return unchanged JSON shapes for the Vue frontend
- [ ] All three fixture-level users pass the manual browser check
- [ ] Feature tests pass (Level 3)
- [ ] No regressions in `sudo -u www-data php artisan test` (full suite)

---

## Completion Checklist

- [ ] Task 1 — regions table + seed
- [ ] Task 2 — Region model
- [ ] Task 3 — churches.region_id migration (backfill + drop old col)
- [ ] Task 4 — AccessLevel enum
- [ ] Task 5 — users access_level + region_id migration
- [ ] Task 6 — User model updated
- [ ] Task 7 — Church model updated
- [ ] Task 8 — ScopesToAccessLevel trait
- [ ] Task 9 — three scoped resources use the trait
- [ ] Task 10 — all 11 policies in place
- [ ] Task 11 — stale overrides removed from resources
- [ ] Task 12 — forms/tables/infolists updated for new columns
- [ ] Task 13 — public API contract preserved
- [ ] Task 14 — feature tests pass
- [ ] Browser smoke test as each persona passes

---

## Risks and Mitigations

| Risk | Likelihood | Impact | Mitigation |
|------|------------|--------|------------|
| Backfill mis-maps a user's access_level (e.g., existing elder with no explicit role) | MED | MED | Conservative default: anyone without role=executive_board/administrator/regional_presbyter and with a `church_id` → local; users without `church_id` → NULL (default-deny). Operator reviews Level 6 output and fixes any NULLs manually before releasing. |
| Policy breaks a currently-working admin flow (e.g., ListUsers loses its create button for national) | MED | MED | Remove the custom `$canCreate` gating in ListChurches/ListUsers only after confirming the policy's `create()` method returns true for national — test by logging in as administrator. |
| Public Vue frontend breaks because `/api/churches?organizational_region=...` returns different shape | LOW | HIGH | API wire-compat layer (Task 13) keeps the key `organizational_region` in both query params and responses. Verified via curl in Level 4. |
| Cascading delete on Region wipes church data | LOW | HIGH | Use `nullOnDelete` on both `churches.region_id` and `users.region_id` FKs. Deleting a Region leaves rows intact, scope just becomes empty. |
| SQLite gotchas with multiple schema changes in one migration | LOW | LOW | Split the three DDL migrations (regions, churches FK, users FK) — each is atomic. Tested sequentially in Task 1/3/5. |
| Trait `abstract` methods unsupported on older PHP | LOW | LOW | PHP 8.4 is in use (confirmed); abstract-in-trait supported since PHP 8.0. |
| `organizational_region` validation `in:North Region,Central Region,South Region` breaks if a region is renamed in `regions` table | LOW | LOW | Keep the hardcoded `in:` rule for now — changing region names is a separate operator decision with its own follow-up. |
| User submits form with access_level=regional but no region_id | LOW | LOW | Form-level validation in Task 12 (`requiredIf`). |
| Existing admin sessions break after schema change | LOW | LOW | Laravel reads access_level / region_id fresh per request — next login picks them up. |

---

## Notes

- **Extension point for department permissions (as user noted).** When it comes time to add department-level access, the path is: add `users.department_ids` (pivot), extend `AccessLevel` with `DEPARTMENTAL` or add a secondary `department_scope` column, and in the trait add a third closure: `departmentalScope()`. No existing scoped resource needs to change beyond declaring the new closure — that's the point of the closure-based trait.
- **Why Events/Departments have unrestricted reads.** Per user's stated intent (Q5 note): Events are an aggregated national view. Local pastors should be able to see everything happening across the country; they just can't *edit* it. Same for Departments as an organisational reference.
- **Why `user_roles` pivot table stays untouched.** It was introduced in a 2025-10-11 migration but never populated or referenced. This plan doesn't need it (we're using a single `access_level` enum per Q1=a). Cleanup is a separate task and out of scope.
- **Why policies AND the trait (not one or the other).** The trait scopes list queries (makes `getEloquentQuery()` DRY); policies enforce writes (`create/update/delete`) and navigation (`viewAny`). Filament v4 consults both: the query limits what shows, the policies gate what the user can do with what shows.
- **Why auto-registration of policies works in Laravel 11.** Laravel 11 removed the explicit `AuthServiceProvider::$policies` map; it infers by naming convention (`App\Models\Church` → `App\Policies\ChurchPolicy`). See [docs link above]. Just placing files in `app/Policies/` is enough.
- **Confidence: 9/10.** The only reason not 10/10 is the SQLite `dropColumn` edge case on old versions — not expected to bite us on the current setup, but worth the on-the-fly check in Task 3. Every other decision maps cleanly to existing patterns in the codebase.
