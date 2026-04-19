# Feature: Departments CMS

## Summary

Add six Department sections (Mens, Ladies, Missions, Youth, Children's, Prayer Ministry) to the public site. Each Department has an editable **Description**, a list of **Announcements**, and a **Calendar** of events — all managed from the Filament back-office by content editors. A new `departments` table stores each department with a rich description; a new `department_announcements` table stores per-department announcements (markdown); the existing `events` table gains an optional `department_id` so calendar entries can be filtered per department. A new dynamic Vue route `/departments/:slug` renders each department page by calling a new `/api/departments/{slug}` endpoint. Header menu items under the existing "Departments" parent are added via migration.

## User Story

As a **content editor / department leader**
I want to **manage each department's description, announcements, and calendar entries from the admin panel**
So that **the public department pages stay current without requiring a developer**.

## Problem Statement

The header already has a "Departments" menu item (seeded in 2026_03_10_000002, url `#` since 2026_03_10_005020) but no per-department page exists. Visitors clicking the menu land on `GetInvolved.vue`, a static catch-all. Department leaders cannot publish announcements, post events, or update their description. The site needs six dedicated department pages with three structured sections each, all editable by non-developers.

## Solution Statement

Create three persistence layers mirroring existing feature patterns:

1. **Department** (new model/table/Filament resource) — one row per department, carries `name`, `slug`, `description` (markdown), `hero_image`, `color_theme`, `scripture_quote`, `is_published`, `sort_order`. Mirrors the Event feature shape.
2. **DepartmentAnnouncement** (new model/table/Filament resource, nested as a RelationManager on Department) — per-department announcements with `title`, `content` (markdown), `published_at`, `is_published`, `sort_order`. Mirrors the AGSUpdate feature shape.
3. **Event.department_id** (additive column on existing `events` table) — optional FK so the same Events resource can be used to create department calendar entries. The existing `/api/events` endpoint gets an optional `?department=<slug>` filter.

Expose a `GET /api/departments/{slug}` endpoint that returns `{ department, description, announcements[], events[] }` in one response. Render via a new `resources/js/views/Department.vue` bound to `/departments/:slug`. Seed the six departments plus header menu children via a migration that mirrors the Events/AGS Updates menu migrations.

## Metadata

| Field            | Value                                                                                                |
| ---------------- | ---------------------------------------------------------------------------------------------------- |
| Type             | NEW_CAPABILITY                                                                                       |
| Complexity       | MEDIUM                                                                                               |
| Systems Affected | DB schema, Eloquent models, Filament admin, Laravel API, Vue SPA, navigation menu                    |
| Dependencies     | filament/filament ^4.0@beta, laravel/framework ^12.0, vue ^3.5.22, vue-router ^4.5.1, marked ^16.4.1 |
| Estimated Tasks  | 24                                                                                                   |

---

## UX Design

### Before State

```
╔═══════════════════════════════════════════════════════════════════════════════╗
║                              BEFORE STATE                                     ║
╠═══════════════════════════════════════════════════════════════════════════════╣
║                                                                               ║
║   Navbar hover "Departments" ─► dropdown shows only "Gallery" + "Social"      ║
║                                 (no links to individual departments)          ║
║                                                                               ║
║   User clicks "Departments" label ─► nothing happens (url = '#')              ║
║                                                                               ║
║   Static /departments route ─► GetInvolved.vue (hard-coded 4 departments,     ║
║                                  no announcements, no calendar)               ║
║                                                                               ║
║   Filament admin: no Department resource, no way to edit any department       ║
║                                                                               ║
║   PAIN_POINT: Six departments promised in menu copy, zero way to publish      ║
║               their content. Department leaders cannot post announcements     ║
║               or events. Pages are hard-coded in Vue.                         ║
║                                                                               ║
║   DATA_FLOW: none — no department data exists in DB                           ║
║                                                                               ║
╚═══════════════════════════════════════════════════════════════════════════════╝
```

### After State

```
╔═══════════════════════════════════════════════════════════════════════════════╗
║                               AFTER STATE                                     ║
╠═══════════════════════════════════════════════════════════════════════════════╣
║                                                                               ║
║   Filament admin                                                              ║
║   ├── Departments (CRUD)                                                      ║
║   │     └── Description (MarkdownEditor), hero image, color, scripture        ║
║   │     └── Announcements tab (RelationManager) — title, markdown, date       ║
║   │     └── Events tab (RelationManager filtered by department_id)            ║
║   └── Events (existing, now accepts department_id select)                     ║
║                                                                               ║
║   Navbar "Departments" dropdown ─► Mens, Ladies, Missions, Youth,             ║
║                                    Children's, Prayer Ministry, Gallery       ║
║                                                                               ║
║   User clicks "Mens Department" ─► /departments/mens                          ║
║        ├── fetch /api/departments/mens                                        ║
║        ├── Hero with name + scripture quote                                   ║
║        ├── Description section (markdown → HTML)                              ║
║        ├── Announcements list (recent first)                                  ║
║        └── Upcoming calendar grid                                             ║
║                                                                               ║
║   VALUE_ADD: Department leaders publish directly; visitors see current        ║
║              announcements and events per department without dev work.        ║
║                                                                               ║
║   DATA_FLOW: Filament edit → DB → GET /api/departments/{slug}                 ║
║              → Department.vue renders → visitor sees fresh content            ║
║                                                                               ║
╚═══════════════════════════════════════════════════════════════════════════════╝
```

### Interaction Changes

| Location                                       | Before                                | After                                                 | User Impact                                      |
| ---------------------------------------------- | ------------------------------------- | ----------------------------------------------------- | ------------------------------------------------ |
| Navbar "Departments" dropdown                  | Gallery + Social only                 | 6 departments + Gallery + Social                      | Visitors can jump straight to a department page  |
| `/departments/mens` (and 5 siblings)           | 404 / SPA fallback to CmsPage 404     | Department page with description, announcements, cal  | Visitors see per-department content              |
| Filament admin sidebar                         | Events, AGS Updates, Gallery, CMS     | + Departments (with Announcements relation)           | Editors can CRUD departments + announcements     |
| Filament EventForm                             | No department field                   | Select "Department" (nullable) — filters calendar     | Events can be flagged as belonging to a dept     |
| `GET /api/events?department=mens`              | ignored                               | returns only events where `department.slug='mens'`    | Calendar can be scoped per department            |

---

## Mandatory Reading

**CRITICAL: Implementation agent MUST read these files before starting any task:**

| Priority | File                                                                                        | Lines | Why Read This                                                   |
| -------- | ------------------------------------------------------------------------------------------- | ----- | --------------------------------------------------------------- |
| P0       | `app/Filament/Resources/Events/EventResource.php`                                           | 1-70  | Resource skeleton to mirror for DepartmentResource              |
| P0       | `app/Filament/Resources/Events/Schemas/EventForm.php`                                       | 1-37  | Form skeleton (Section, Grid, TextInput, Toggle) — mirror       |
| P0       | `app/Filament/Resources/Events/Tables/EventsTable.php`                                      | 1-37  | Table + published badge pattern — mirror                        |
| P0       | `app/Filament/Resources/Events/Pages/ListEvents.php`                                        | 1-19  | List page skeleton — mirror for every List page                 |
| P0       | `app/Filament/Resources/Pages/Schemas/PageForm.php`                                         | 33-70 | Slug auto-gen via `Str::slug` + `afterStateUpdated` — mirror    |
| P0       | `app/Filament/Resources/Pages/Schemas/PageForm.php`                                         | 86-106| `FileUpload::make` + `->image()->directory()->maxSize()`        |
| P0       | `app/Filament/Resources/AGSUpdates/Schemas/AGSUpdateForm.php`                               | 1-28  | Content-only form pattern with `Textarea` — mirror announcements|
| P0       | `app/Http/Controllers/Api/EventController.php`                                              | 1-69  | JSON shape `{success, data[]}`, `?from`/`?to` filter pattern    |
| P0       | `app/Http/Controllers/Api/PageController.php`                                               | 1-64  | Show-by-slug pattern with 404 envelope                          |
| P0       | `app/Http/Controllers/Api/AGSUpdateController.php`                                          | 1-25  | `->published()->orderByDesc('published_at')->get()->map(...)`   |
| P0       | `app/Models/Event.php`                                                                      | 1-34  | `$fillable`, `casts()`, `scopePublished()` — mirror             |
| P0       | `app/Models/MenuItem.php`                                                                   | 1-74  | Self-referential parent/children structure                      |
| P0       | `database/migrations/2026_03_10_000005_create_events_table.php`                             | 1-30  | Column definitions to mirror                                    |
| P0       | `database/migrations/2026_03_10_004836_add_ags_updates_menu_item.php`                       | 1-30  | Menu-insertion migration pattern (idempotent `exists()` guard)  |
| P0       | `database/migrations/2026_03_10_005020_add_departments_gallery_and_social_menu_children.php`| 1-62  | Exact "Departments" parent lookup — reuse this query            |
| P0       | `resources/js/router/routes.js`                                                             | 1-89  | Where to register `/departments/:slug` (before catch-all)       |
| P0       | `resources/js/views/AgsUpdates.vue`                                                         | 1-120 | Markdown render with `marked.parse` + `prose` classes — mirror  |
| P0       | `resources/js/views/Events.vue`                                                             | 1-110 | Event card grid + date formatting — mirror for calendar grid    |
| P0       | `resources/js/views/Home.vue`                                                               | 1-50  | Hero section pattern (gradient, h1, CTA buttons) — mirror       |
| P0       | `routes/web.php`                                                                            | 1-62  | Where to add `/api/departments` routes (inside api group)       |
| P1       | `app/Enums/UserRole.php`                                                                    | all   | `isPastor`, `isRegionalPresbyter`, `hasFullAccess` predicates   |
| P1       | `resources/js/components/Navbar.vue`                                                        | 1-180 | How dropdown children render (description subtitle) — no change |

**External Documentation:**

| Source                                                                                                                                              | Section                   | Why Needed                                                       |
| --------------------------------------------------------------------------------------------------------------------------------------------------- | ------------------------- | ---------------------------------------------------------------- |
| [Filament v4 Relation Managers](https://filamentphp.com/docs/4.x/resources/relation-managers)                                                       | "Creating a relation manager" | DepartmentAnnouncements attached to Department via RelationManager |
| [Filament v4 MarkdownEditor](https://filamentphp.com/docs/4.x/forms/fields/markdown-editor)                                                         | "Available toolbar buttons" | Mirror `PageForm` markdown toolbar list                          |
| [Filament v4 Select::relationship](https://filamentphp.com/docs/4.x/forms/fields/select#populating-options-from-a-relationship)                     | "relationship" method     | Event.department_id as `Select::make()->relationship()`          |
| [Laravel 12 Migrations — `foreignId`](https://laravel.com/docs/12.x/migrations#foreign-key-constraints)                                             | foreignId().constrained() | Department FK on events + announcements                          |
| [Vue Router 4 Dynamic Matching](https://router.vuejs.org/guide/essentials/dynamic-matching.html)                                                    | "Reacting to params"      | `watch(() => route.params.slug)` — mirror `CmsPage.vue`          |
| [marked](https://marked.js.org/using_advanced)                                                                                                      | "Options"                 | Already used with `{breaks: true, gfm: true}` in CmsPage.vue     |

- GOTCHA: Filament v4 uses `Section::make(...)->schema([...])` (not `->columns()`). Copy exactly from `EventForm.php`.
- GOTCHA: `routes/web.php` API routes live inside `Route::prefix('api')->group(...)` — **do not** create `routes/api.php`.
- GOTCHA: Vue router catch-all `/:slug(.*)` is last; new `/departments/:slug` must be added **before** it.
- GOTCHA: Event's existing `is_published` default is `true`; keep that so department calendar entries surface by default.

---

## Patterns to Mirror

**RESOURCE_SHELL:**

```php
// SOURCE: app/Filament/Resources/Events/EventResource.php:20-70
// COPY THIS PATTERN for DepartmentResource:
class EventResource extends Resource
{
    protected static ?string $model = Event::class;
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function shouldRegisterNavigation(): bool
    {
        $user = auth()->user();
        if (! $user) { return true; }
        if (UserRole::isPastor($user) || UserRole::isRegionalPresbyter($user)) { return false; }
        return true;
    }

    public static function form(Schema $schema): Schema     { return EventForm::configure($schema); }
    public static function infolist(Schema $schema): Schema { return EventInfolist::configure($schema); }
    public static function table(Table $table): Table       { return EventsTable::configure($table); }

    public static function getPages(): array
    {
        return [
            'index'  => ListEvents::route('/'),
            'create' => CreateEvent::route('/create'),
            'view'   => ViewEvent::route('/{record}'),
            'edit'   => EditEvent::route('/{record}/edit'),
        ];
    }
}
```

**SLUG_AUTO_GENERATION:**

```php
// SOURCE: app/Filament/Resources/Pages/Schemas/PageForm.php:33-45
// COPY THIS PATTERN for Department slug:
TextInput::make('name') // or 'title' for Pages
    ->required()
    ->maxLength(255)
    ->live(onBlur: true)
    ->afterStateUpdated(function (Get $get, $set, ?string $old, ?string $state) {
        if (($get('slug') ?? '') !== Str::slug($old)) {
            return; // preserve manual edits
        }
        $set('slug', Str::slug($state));
    }),
TextInput::make('slug')
    ->required()
    ->maxLength(255)
    ->unique(ignoreRecord: true)
    ->helperText('Used in the URL (e.g., /departments/[slug]).'),
```

**PUBLISHED_BADGE (Table column):**

```php
// SOURCE: app/Filament/Resources/Events/Tables/EventsTable.php:20-24
// COPY THIS PATTERN verbatim:
TextColumn::make('is_published')
    ->badge()
    ->formatStateUsing(fn ($s) => $s ? 'Published' : 'Draft')
    ->color(fn ($s) => $s ? 'success' : 'gray'),
```

**FILE_UPLOAD:**

```php
// SOURCE: app/Filament/Resources/Pages/Schemas/PageForm.php:90-94
// COPY THIS PATTERN for hero_image:
FileUpload::make('hero_image')
    ->label('Hero Image')
    ->image()
    ->directory('department-images')
    ->maxSize(5120),
```

**MARKDOWN_EDITOR (Description):**

```php
// SOURCE: app/Filament/Resources/Pages/Schemas/PageForm.php — 'text' block content field
// COPY THIS PATTERN for description:
MarkdownEditor::make('description')
    ->toolbarButtons(['bold','italic','link','heading','bulletList','orderedList','blockquote'])
    ->columnSpanFull(),
```

**MODEL_WITH_PUBLISHED_SCOPE:**

```php
// SOURCE: app/Models/Event.php:1-34
// COPY THIS PATTERN for Department and DepartmentAnnouncement:
class Event extends Model
{
    protected $fillable = [
        'name', 'slug', 'description', 'start_date', 'end_date',
        'location', 'url', 'is_published', 'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date'   => 'date',
            'is_published' => 'boolean',
        ];
    }

    public function scopePublished($query)
    {
        return $query->where('is_published', true);
    }
}
```

**API_CONTROLLER_WITH_SHOW_BY_SLUG:**

```php
// SOURCE: app/Http/Controllers/Api/PageController.php (show) + EventController.php (index)
// COPY THIS PATTERN:
public function show(string $slug): JsonResponse
{
    $department = Department::published()->where('slug', $slug)->first();
    if (! $department) {
        return response()->json(['success' => false, 'message' => 'Department not found'], 404);
    }
    return response()->json([
        'success' => true,
        'data' => [
            'id'               => $department->id,
            'name'             => $department->name,
            'slug'             => $department->slug,
            'description'      => $department->description,
            'hero_image'       => $department->hero_image,
            'color_theme'      => $department->color_theme,
            'scripture_quote'  => $department->scripture_quote,
            'announcements'    => $department->announcements()
                ->published()->orderByDesc('published_at')->get()
                ->map(fn ($a) => [
                    'id' => $a->id, 'title' => $a->title,
                    'content' => $a->content,
                    'published_at' => $a->published_at?->toIso8601String(),
                ]),
            'events'           => $department->events()
                ->published()->orderBy('start_date')->get()
                ->map(fn ($e) => [
                    'id' => $e->id, 'name' => $e->name, 'slug' => $e->slug,
                    'description' => $e->description,
                    'start_date' => $e->start_date->format('Y-m-d'),
                    'end_date' => $e->end_date?->format('Y-m-d'),
                    'location' => $e->location, 'url' => $e->url,
                ]),
        ],
    ]);
}
```

**MENU_MIGRATION_IDEMPOTENT_INSERT:**

```php
// SOURCE: database/migrations/2026_03_10_005020_add_departments_gallery_and_social_menu_children.php:8-46
// COPY THIS PATTERN for each department child:
$dep = DB::table('menu_items')
    ->where('location', 'header')->where('label', 'Departments')
    ->whereNull('parent_id')->first();

if ($dep && ! DB::table('menu_items')
        ->where('parent_id', $dep->id)->where('label', 'Mens Department')->exists()) {
    DB::table('menu_items')->insert([
        'label'           => 'Mens Department',
        'description'     => 'Resources and events for men',
        'url'             => '/departments/mens',
        'location'        => 'header',
        'sort_order'      => 10,
        'is_active'       => true,
        'open_in_new_tab' => false,
        'parent_id'       => $dep->id,
        'created_at'      => now(),
        'updated_at'      => now(),
    ]);
}
```

**VUE_VIEW_FETCH_WITH_PARAM_WATCH:**

```js
// SOURCE: resources/js/views/CmsPage.vue (fetch + route.path watcher)
//       + resources/js/views/AgsUpdates.vue (marked.parse for content)
// COPY THIS PATTERN in Department.vue:
const route = useRoute()
const data = ref(null), loading = ref(true), error = ref(null)

const fetchDepartment = async (slug) => {
    loading.value = true; error.value = null
    try {
        const res = await fetch(`/api/departments/${slug}`)
        const body = await res.json()
        if (body.success && body.data) { data.value = body.data }
        else { error.value = body.message || 'Department not found' }
    } catch (e) { error.value = e.message }
    finally { loading.value = false }
}

const renderMarkdown = (s) => s ? marked.parse(s, { breaks: true, gfm: true }) : ''
const formatDate = (iso) => new Date(iso).toLocaleDateString('en-NZ',
    { day: 'numeric', month: 'long', year: 'numeric' })

onMounted(() => fetchDepartment(route.params.slug))
watch(() => route.params.slug, (slug) => slug && fetchDepartment(slug))
```

---

## Files to Change

### CREATE

| File                                                                                           | Justification                                                 |
| ---------------------------------------------------------------------------------------------- | ------------------------------------------------------------- |
| `database/migrations/{ts}_create_departments_table.php`                                        | New `departments` table                                       |
| `database/migrations/{ts}_create_department_announcements_table.php`                           | New `department_announcements` table                          |
| `database/migrations/{ts}_add_department_id_to_events_table.php`                               | Additive FK on existing `events`                              |
| `database/migrations/{ts}_seed_departments_and_menu_items.php`                                 | Seed 6 departments + add 6 child menu items                   |
| `app/Models/Department.php`                                                                    | Eloquent model with `published` scope + relations             |
| `app/Models/DepartmentAnnouncement.php`                                                        | Eloquent model with `published` scope + belongsTo Department  |
| `app/Filament/Resources/Departments/DepartmentResource.php`                                    | Filament resource shell                                       |
| `app/Filament/Resources/Departments/Schemas/DepartmentForm.php`                                | Form (name/slug/description/hero/theme/scripture)             |
| `app/Filament/Resources/Departments/Schemas/DepartmentInfolist.php`                            | Empty stub (parity with Events)                               |
| `app/Filament/Resources/Departments/Tables/DepartmentsTable.php`                               | Table with published badge + sort                             |
| `app/Filament/Resources/Departments/Pages/ListDepartments.php`                                 | List page                                                     |
| `app/Filament/Resources/Departments/Pages/CreateDepartment.php`                                | Create page                                                   |
| `app/Filament/Resources/Departments/Pages/EditDepartment.php`                                  | Edit page                                                     |
| `app/Filament/Resources/Departments/Pages/ViewDepartment.php`                                  | View page                                                     |
| `app/Filament/Resources/Departments/RelationManagers/AnnouncementsRelationManager.php`         | Announcements CRUD inside Department edit                     |
| `app/Filament/Resources/Departments/RelationManagers/EventsRelationManager.php`                | Calendar events CRUD inside Department edit                   |
| `app/Http/Controllers/Api/DepartmentController.php`                                            | `index` + `show($slug)` endpoints                             |
| `resources/js/views/Department.vue`                                                            | Public page renderer                                          |

### UPDATE

| File                                                                                           | Justification                                                 |
| ---------------------------------------------------------------------------------------------- | ------------------------------------------------------------- |
| `app/Models/Event.php`                                                                         | Add `department_id` to `$fillable` + `department()` belongsTo |
| `app/Filament/Resources/Events/Schemas/EventForm.php`                                          | Add `Select::make('department_id')->relationship('department','name')->searchable()->nullable()` |
| `app/Filament/Resources/Events/Tables/EventsTable.php`                                         | Add optional `department.name` column + filter                |
| `app/Http/Controllers/Api/EventController.php`                                                 | Accept `?department=<slug>` filter in `index`                 |
| `routes/web.php`                                                                               | Register `/api/departments` + `/api/departments/{slug}`       |
| `resources/js/router/routes.js`                                                                | Register `/departments/:slug` **before** the `/:slug(.*)` catch-all |

---

## NOT Building (Scope Limits)

- **No new CMS block types.** Description is a single MarkdownEditor field, not a block-based Builder. Rationale: matches the AGSUpdate pattern and keeps the migration/seed simple. Future blocks can be added later.
- **No department-scoped users / per-department auth.** Any admin with access to Filament can edit any department. Rationale: existing `UserRole` gating (pastor/regional presbyter hidden) is preserved; fine-grained permissions are a separate PRP.
- **No events calendar view.** The existing `/calendar` route stays untouched. The Department page embeds a simple upcoming-events list, not a full month grid.
- **No replacement of `/departments` (GetInvolved.vue).** The landing `/departments` page continues to render `GetInvolved.vue`. Only `/departments/:slug` is new. Rationale: avoids regressions in a page linked from the menu and the Gallery section.
- **No change to the Gallery or Social child menu items.** They remain siblings of the new department children.
- **No frontend for `GET /api/departments` (index).** The index endpoint exists for completeness but is not consumed by any Vue view. Rationale: Navbar uses the menu API, not departments API.
- **No i18n / Maori translation fields.** The schema stays English-only; adding multilingual support is out of scope.

---

## Step-by-Step Tasks

Execute in order. Each task is atomic and independently verifiable.

### Task 1: CREATE `database/migrations/{ts}_create_departments_table.php`

- **ACTION**: CREATE migration
- **IMPLEMENT**:
  ```php
  Schema::create('departments', function (Blueprint $table) {
      $table->id();
      $table->string('name');
      $table->string('slug')->unique();
      $table->text('description')->nullable();      // markdown
      $table->string('hero_image')->nullable();
      $table->string('color_theme', 32)->default('blue'); // tailwind palette name
      $table->text('scripture_quote')->nullable();
      $table->boolean('is_published')->default(true);
      $table->integer('sort_order')->default(0);
      $table->timestamps();
  });
  ```
- **MIRROR**: `database/migrations/2026_03_10_000005_create_events_table.php:9-23`
- **GOTCHA**: Timestamp in filename must be LATER than `2026_03_12_000002_*` (pick e.g. `2026_04_19_000001`).
- **VALIDATE**: `php artisan migrate --pretend`

### Task 2: CREATE `database/migrations/{ts}_create_department_announcements_table.php`

- **ACTION**: CREATE migration
- **IMPLEMENT**:
  ```php
  Schema::create('department_announcements', function (Blueprint $table) {
      $table->id();
      $table->foreignId('department_id')->constrained('departments')->cascadeOnDelete();
      $table->string('title');
      $table->longText('content')->nullable();       // markdown
      $table->timestamp('published_at')->nullable();
      $table->boolean('is_published')->default(true);
      $table->integer('sort_order')->default(0);
      $table->timestamps();
      $table->index(['department_id','is_published']);
  });
  ```
- **MIRROR**: `database/migrations/2026_03_10_004735_create_a_g_s_updates_table.php:1-29` + `foreignId()->constrained()` idiom.
- **GOTCHA**: `is_published` default `true` (unlike AGS Updates which defaults `false`) so seeded placeholder announcements surface immediately.
- **VALIDATE**: `php artisan migrate --pretend`

### Task 3: CREATE `database/migrations/{ts}_add_department_id_to_events_table.php`

- **ACTION**: CREATE migration, additive column only
- **IMPLEMENT**:
  ```php
  public function up(): void {
      Schema::table('events', function (Blueprint $table) {
          $table->foreignId('department_id')->nullable()
              ->after('url')
              ->constrained('departments')->nullOnDelete();
          $table->index('department_id');
      });
  }
  public function down(): void {
      Schema::table('events', function (Blueprint $table) {
          $table->dropConstrainedForeignId('department_id');
      });
  }
  ```
- **GOTCHA**: `nullable()` is REQUIRED — existing rows have no department. `nullOnDelete()` so deleting a department doesn't wipe events.
- **VALIDATE**: `php artisan migrate --pretend`

### Task 4: CREATE `app/Models/Department.php`

- **ACTION**: CREATE Eloquent model
- **IMPLEMENT**:
  ```php
  class Department extends Model {
      protected $fillable = [
          'name','slug','description','hero_image','color_theme',
          'scripture_quote','is_published','sort_order',
      ];
      protected function casts(): array {
          return ['is_published' => 'boolean'];
      }
      public function scopePublished($q) { return $q->where('is_published', true); }
      public function announcements() {
          return $this->hasMany(DepartmentAnnouncement::class)->orderBy('sort_order');
      }
      public function events() {
          return $this->hasMany(Event::class);
      }
  }
  ```
- **MIRROR**: `app/Models/Event.php:1-34`
- **VALIDATE**: `php artisan tinker --execute="App\Models\Department::query()->toSql();"`

### Task 5: CREATE `app/Models/DepartmentAnnouncement.php`

- **ACTION**: CREATE Eloquent model
- **IMPLEMENT**:
  ```php
  class DepartmentAnnouncement extends Model {
      protected $fillable = [
          'department_id','title','content',
          'published_at','is_published','sort_order',
      ];
      protected function casts(): array {
          return ['published_at' => 'datetime', 'is_published' => 'boolean'];
      }
      public function scopePublished($q) { return $q->where('is_published', true); }
      public function department() {
          return $this->belongsTo(Department::class);
      }
  }
  ```
- **MIRROR**: `app/Models/AGSUpdate.php:1-31`
- **VALIDATE**: `php artisan tinker --execute="App\Models\DepartmentAnnouncement::query()->toSql();"`

### Task 6: UPDATE `app/Models/Event.php`

- **ACTION**: Add `department_id` to `$fillable`; add `department()` belongsTo
- **IMPLEMENT**:
  ```php
  protected $fillable = [
      'name','slug','description','start_date','end_date',
      'location','url','is_published','sort_order','department_id',
  ];

  public function department() {
      return $this->belongsTo(\App\Models\Department::class);
  }
  ```
- **GOTCHA**: Do NOT change existing `$casts` — dates stay as-is.
- **VALIDATE**: `php artisan tinker --execute="App\Models\Event::first()?->department;"`

### Task 7: RUN migrations

- **ACTION**: Apply the three new migrations to the local DB
- **COMMAND**: `php artisan migrate`
- **VALIDATE**: `php artisan tinker --execute="Schema::hasColumn('events','department_id') ? 'ok' : 'fail';"`

### Task 8: CREATE `app/Filament/Resources/Departments/DepartmentResource.php`

- **ACTION**: CREATE resource skeleton
- **IMPLEMENT**: copy `EventResource` layout. `$navigationLabel = 'Departments'`. Wire `form`, `table`, `infolist` to the schema classes created in Tasks 9–11. Register pages (Task 12–15) and RelationManagers (Task 16–17) via `getRelations()`:
  ```php
  public static function getRelations(): array {
      return [
          AnnouncementsRelationManager::class,
          EventsRelationManager::class,
      ];
  }
  ```
- **MIRROR**: `app/Filament/Resources/Events/EventResource.php:1-70`
- **VALIDATE**: `php artisan filament:optimize && php artisan route:list | grep departments`

### Task 9: CREATE `app/Filament/Resources/Departments/Schemas/DepartmentForm.php`

- **ACTION**: CREATE form schema
- **IMPLEMENT**:
  ```php
  return $schema->components([
      Section::make('Department')->schema([
          TextInput::make('name')->required()->maxLength(255)
              ->live(onBlur: true)
              ->afterStateUpdated(function (Get $get, $set, ?string $old, ?string $state) {
                  if (($get('slug') ?? '') !== Str::slug($old)) { return; }
                  $set('slug', Str::slug($state));
              }),
          TextInput::make('slug')->required()->maxLength(255)
              ->unique(ignoreRecord: true)
              ->helperText('Used in the URL (e.g., /departments/[slug]).'),
          MarkdownEditor::make('description')
              ->toolbarButtons(['bold','italic','link','heading','bulletList','orderedList','blockquote'])
              ->columnSpanFull(),
          FileUpload::make('hero_image')->image()
              ->directory('department-images')->maxSize(5120),
          Grid::make(2)->schema([
              Select::make('color_theme')->options([
                  'blue'=>'Blue','green'=>'Green','pink'=>'Pink',
                  'yellow'=>'Yellow','purple'=>'Purple','indigo'=>'Indigo',
              ])->default('blue')->required(),
              TextInput::make('sort_order')->numeric()->default(0),
          ]),
          Textarea::make('scripture_quote')->rows(3)
              ->helperText('Short scripture displayed beside the hero.'),
          Toggle::make('is_published')->default(true),
      ]),
  ]);
  ```
- **MIRROR**: `PageForm.php:33-45` (slug), `PageForm.php:90-94` (FileUpload), `EventForm.php:10-33` (Section/Grid pattern).
- **VALIDATE**: Open `/admin/departments/create` in browser — form renders without error.

### Task 10: CREATE `app/Filament/Resources/Departments/Schemas/DepartmentInfolist.php`

- **ACTION**: CREATE empty stub (mirror EventInfolist)
- **IMPLEMENT**: `$schema->components([])`
- **MIRROR**: `app/Filament/Resources/Events/Schemas/EventInfolist.php:1-16`
- **VALIDATE**: no errors loading `/admin/departments/{id}`.

### Task 11: CREATE `app/Filament/Resources/Departments/Tables/DepartmentsTable.php`

- **ACTION**: CREATE table definition
- **IMPLEMENT**:
  ```php
  return $table->columns([
      TextColumn::make('sort_order')->label('#')->sortable(),
      TextColumn::make('name')->searchable()->sortable(),
      TextColumn::make('slug')->copyable()->badge()->color('gray'),
      TextColumn::make('announcements_count')->counts('announcements')->label('Announcements'),
      TextColumn::make('events_count')->counts('events')->label('Events'),
      TextColumn::make('is_published')
          ->badge()
          ->formatStateUsing(fn ($s) => $s ? 'Published' : 'Draft')
          ->color(fn ($s) => $s ? 'success' : 'gray'),
      TextColumn::make('updated_at')->dateTime()->sortable()->since(),
  ])
  ->defaultSort('sort_order')
  ->filters([
      SelectFilter::make('is_published')->label('Status')
          ->options(['1'=>'Published','0'=>'Draft']),
  ])
  ->recordActions([ViewAction::make(), EditAction::make()])
  ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
  ```
- **MIRROR**: `EventsTable.php:1-37` + `PagesTable.php` filter/default-sort.
- **VALIDATE**: `/admin/departments` lists seeded rows after Task 18.

### Task 12: CREATE `app/Filament/Resources/Departments/Pages/ListDepartments.php`

- **ACTION**: CREATE list page; header action = CreateAction
- **MIRROR**: `app/Filament/Resources/Events/Pages/ListEvents.php:1-19`
- **VALIDATE**: `/admin/departments` reachable.

### Task 13: CREATE `app/Filament/Resources/Departments/Pages/CreateDepartment.php`

- **ACTION**: CREATE page (no overrides)
- **MIRROR**: `app/Filament/Resources/Events/Pages/CreateEvent.php`
- **VALIDATE**: `/admin/departments/create` renders form.

### Task 14: CREATE `app/Filament/Resources/Departments/Pages/EditDepartment.php`

- **ACTION**: CREATE edit page; header actions = [ViewAction, DeleteAction]
- **MIRROR**: `app/Filament/Resources/Events/Pages/EditEvent.php`
- **VALIDATE**: Editing a department shows RelationManager tabs.

### Task 15: CREATE `app/Filament/Resources/Departments/Pages/ViewDepartment.php`

- **ACTION**: CREATE view page; header actions = [EditAction]
- **MIRROR**: `app/Filament/Resources/Events/Pages/ViewEvent.php`
- **VALIDATE**: `/admin/departments/{id}` renders.

### Task 16: CREATE `app/Filament/Resources/Departments/RelationManagers/AnnouncementsRelationManager.php`

- **ACTION**: CREATE RelationManager
- **IMPLEMENT**:
  - `protected static string $relationship = 'announcements';`
  - `form`: Section with `TextInput::make('title')->required()`, `MarkdownEditor::make('content')->toolbarButtons([...])`, `DateTimePicker::make('published_at')`, `Toggle::make('is_published')->default(true)`, `TextInput::make('sort_order')->numeric()->default(0)`.
  - `table`: columns `title`, `published_at` (dateTime), `is_published` (published badge pattern). Default sort `published_at` desc. `headerActions([CreateAction::make()])`. `recordActions([EditAction::make(), DeleteAction::make()])`.
- **MIRROR**: `AGSUpdateForm.php:1-28` (form fields) + `AGSUpdatesTable.php:1-35` (table columns).
- **GOTCHA**: Filament v4 RelationManager extends `Filament\Resources\RelationManagers\RelationManager`. Import correct namespace.
- **VALIDATE**: On `/admin/departments/1/edit`, "Announcements" tab shows a CRUD table.

### Task 17: CREATE `app/Filament/Resources/Departments/RelationManagers/EventsRelationManager.php`

- **ACTION**: CREATE RelationManager that reuses EventForm fields
- **IMPLEMENT**:
  - `protected static string $relationship = 'events';`
  - `form`: inline (don't re-import EventForm — keep it simple). Fields: `name`, `slug`, `description` (Textarea), `start_date`, `end_date`, `location`, `url`, `is_published`, `sort_order`. Mirror `EventForm.php:9-33` (omit `department_id` — FK is set by parent).
  - `table`: columns `name`, `start_date`, `end_date`, `is_published` (published badge). Default sort `start_date`.
- **MIRROR**: `EventForm.php` + `EventsTable.php`.
- **VALIDATE**: Adding an event through this tab persists with the correct `department_id`.

### Task 18: CREATE `database/migrations/{ts}_seed_departments_and_menu_items.php`

- **ACTION**: CREATE migration that seeds the 6 departments AND inserts 6 header menu children under the existing "Departments" parent.
- **IMPLEMENT**:
  ```php
  public function up(): void {
      $seedData = [
          ['name'=>"Men's Department", 'slug'=>'mens', 'color_theme'=>'green',
           'description'=>"# Men's Department\n\nPlaceholder — edit in admin.",
           'scripture_quote'=>null, 'sort_order'=>10],
          ['name'=>"Ladies' Department", 'slug'=>'ladies', 'color_theme'=>'pink',
           'description'=>"# Ladies' Department\n\nPlaceholder — edit in admin.",
           'scripture_quote'=>null, 'sort_order'=>20],
          ['name'=>'Missions Department', 'slug'=>'missions', 'color_theme'=>'blue',
           'description'=>"# Missions Department\n\nPlaceholder — edit in admin.",
           'scripture_quote'=>null, 'sort_order'=>30],
          ['name'=>'Youth Ministry', 'slug'=>'youth', 'color_theme'=>'indigo',
           'description'=>"# Youth Ministry\n\nPlaceholder — edit in admin.",
           'scripture_quote'=>null, 'sort_order'=>40],
          ['name'=>"Children's Ministry", 'slug'=>'childrens', 'color_theme'=>'yellow',
           'description'=>"# Children's Ministry\n\nPlaceholder — edit in admin.",
           'scripture_quote'=>null, 'sort_order'=>50],
          ['name'=>'Prayer Ministry', 'slug'=>'prayer', 'color_theme'=>'purple',
           'description'=>"# Prayer Ministry\n\nPlaceholder — edit in admin.",
           'scripture_quote'=>null, 'sort_order'=>60],
      ];
      foreach ($seedData as $d) {
          DB::table('departments')->updateOrInsert(
              ['slug'=>$d['slug']],
              $d + ['is_published'=>true,'created_at'=>now(),'updated_at'=>now()]
          );
      }

      $dep = DB::table('menu_items')
          ->where('location','header')->where('label','Departments')
          ->whereNull('parent_id')->first();
      if (! $dep) { return; }

      $children = [
          ['Mens Department',    '/departments/mens',     10, 'Fellowship & discipleship for men'],
          ["Ladies Department",  '/departments/ladies',   20, 'Fellowship & discipleship for women'],
          ['Missions Department','/departments/missions', 30, 'UPCI NZ missions'],
          ['Youth Ministry',     '/departments/youth',    40, 'Teens & young adults'],
          ["Children's Ministry",'/departments/childrens',50, 'Kids of all ages'],
          ['Prayer Ministry',    '/departments/prayer',   60, 'Prayer chain & intercession'],
      ];
      foreach ($children as [$label,$url,$sort,$desc]) {
          if (DB::table('menu_items')
                ->where('parent_id',$dep->id)->where('label',$label)->exists()) { continue; }
          DB::table('menu_items')->insert([
              'label'=>$label,'description'=>$desc,'url'=>$url,
              'location'=>'header','sort_order'=>$sort,
              'is_active'=>true,'open_in_new_tab'=>false,
              'parent_id'=>$dep->id,
              'created_at'=>now(),'updated_at'=>now(),
          ]);
      }
  }

  public function down(): void {
      $dep = DB::table('menu_items')
          ->where('location','header')->where('label','Departments')
          ->whereNull('parent_id')->first();
      if ($dep) {
          DB::table('menu_items')->where('parent_id',$dep->id)
              ->whereIn('url', [
                  '/departments/mens','/departments/ladies','/departments/missions',
                  '/departments/youth','/departments/childrens','/departments/prayer',
              ])->delete();
      }
      DB::table('departments')->whereIn('slug', [
          'mens','ladies','missions','youth','childrens','prayer',
      ])->delete();
  }
  ```
- **MIRROR**: `database/migrations/2026_03_10_005020_add_departments_gallery_and_social_menu_children.php:1-62`
- **GOTCHA**: `updateOrInsert` prevents duplicates if migrate is re-run. Children use `exists()` guard like the AGS menu migration.
- **VALIDATE**: After migrate, `/api/menu/header` includes the 6 department children.

### Task 19: CREATE `app/Http/Controllers/Api/DepartmentController.php`

- **ACTION**: CREATE controller with `index` + `show`
- **IMPLEMENT**:
  ```php
  public function index(): JsonResponse {
      $departments = Department::published()->orderBy('sort_order')->get()
          ->map(fn (Department $d) => [
              'id'=>$d->id,'name'=>$d->name,'slug'=>$d->slug,
              'hero_image'=>$d->hero_image,'color_theme'=>$d->color_theme,
          ]);
      return response()->json(['success'=>true,'data'=>$departments]);
  }

  public function show(string $slug): JsonResponse {
      $d = Department::published()->where('slug',$slug)->first();
      if (! $d) {
          return response()->json(['success'=>false,'message'=>'Department not found'], 404);
      }
      return response()->json(['success'=>true,'data'=>[
          'id'=>$d->id,'name'=>$d->name,'slug'=>$d->slug,
          'description'=>$d->description,'hero_image'=>$d->hero_image,
          'color_theme'=>$d->color_theme,'scripture_quote'=>$d->scripture_quote,
          'announcements'=>$d->announcements()->published()
              ->orderByDesc('published_at')->get()
              ->map(fn ($a)=>[
                  'id'=>$a->id,'title'=>$a->title,'content'=>$a->content,
                  'published_at'=>$a->published_at?->toIso8601String(),
              ]),
          'events'=>$d->events()->published()->orderBy('start_date')->get()
              ->map(fn ($e)=>[
                  'id'=>$e->id,'name'=>$e->name,'slug'=>$e->slug,
                  'description'=>$e->description,
                  'start_date'=>$e->start_date->format('Y-m-d'),
                  'end_date'=>$e->end_date?->format('Y-m-d'),
                  'location'=>$e->location,'url'=>$e->url,
              ]),
      ]]);
  }
  ```
- **MIRROR**: `app/Http/Controllers/Api/PageController.php:1-64` (show) + `EventController.php:14-42` (index mapping).
- **VALIDATE**: `curl -s http://localhost:8000/api/departments/mens | jq .success` → `true`.

### Task 20: UPDATE `routes/web.php`

- **ACTION**: Inside the `Route::prefix('api')` group, add:
  ```php
  Route::get('/departments', [DepartmentController::class, 'index']);
  Route::get('/departments/{slug}', [DepartmentController::class, 'show']);
  ```
  plus the `use App\Http\Controllers\Api\DepartmentController;` import at the top.
- **MIRROR**: existing `/pages` + `/events` registrations (`routes/web.php:23-32`).
- **VALIDATE**: `php artisan route:list | grep api/departments` shows both routes.

### Task 21: UPDATE `app/Http/Controllers/Api/EventController.php`

- **ACTION**: Add `?department=<slug>` filter to `index`
- **IMPLEMENT**: after the existing `$query = Event::published()->orderBy('start_date');`, add:
  ```php
  if ($request->filled('department')) {
      $query->whereHas('department', fn ($q) => $q->where('slug', $request->department));
  }
  ```
  Also add `'department' => $event->department?->slug,` to the `->map(...)` output (eager-load with `->with('department')`).
- **VALIDATE**: `curl -s '/api/events?department=mens'` returns only events whose department slug matches.

### Task 22: UPDATE `app/Filament/Resources/Events/Schemas/EventForm.php`

- **ACTION**: Add department select above the published toggle.
- **IMPLEMENT**: inside the existing Section, add:
  ```php
  Select::make('department_id')
      ->label('Department')
      ->relationship('department', 'name')
      ->searchable()->preload()->nullable(),
  ```
- **MIRROR**: Filament v4 Select::relationship — see docs reference above.
- **VALIDATE**: Filament Event form now shows Department dropdown.

### Task 23: CREATE `resources/js/views/Department.vue`

- **ACTION**: CREATE Vue view bound to `/departments/:slug`
- **IMPLEMENT**:
  - `<script setup>` (or Options API to match existing style — pick the Options API matching Events.vue/AgsUpdates.vue for consistency).
  - Imports: `defineComponent`, `ref`, `onMounted`, `watch`, `useRoute` from vue-router, `marked`.
  - `data()`: `{ department: null, loading: true, error: null }`.
  - Fetch `/api/departments/${slug}` on mount AND on `watch(() => route.params.slug)`.
  - Helpers: `renderMarkdown(s)` using `marked.parse(s, {breaks:true,gfm:true})`; `formatDate(iso)` using `toLocaleDateString('en-NZ',{day:'numeric',month:'long',year:'numeric'})`; `heroClasses(theme)` returning a Tailwind gradient class per theme.
  - Template: Hero (copy Home.vue hero pattern), optional scripture quote block, Description section (`v-html="renderMarkdown(department.description)"` inside `class="prose prose-slate max-w-none"`), Announcements list (copy AgsUpdates.vue `<article>` pattern), Events grid (copy Events.vue 3-col card pattern).
  - Empty states: "No announcements yet." / "No upcoming events." when arrays are empty.
- **MIRROR**: `resources/js/views/AgsUpdates.vue:1-120`, `resources/js/views/Events.vue:1-110`, `resources/js/views/Home.vue:4-48`.
- **VALIDATE**: `npm run build` succeeds. Visit `/departments/mens` — page renders with seeded placeholder description.

### Task 24: UPDATE `resources/js/router/routes.js`

- **ACTION**: Add `/departments/:slug` route **before** the catch-all `/:slug(.*)`.
- **IMPLEMENT**:
  ```js
  { path: '/departments/:slug', name: 'Department',
    component: () => import('../views/Department.vue') },
  ```
  Insert directly after the existing `/departments` route (line with `name: 'Departments'`).
- **GOTCHA**: Order matters — if added after `/:slug(.*)`, the catch-all will match first.
- **VALIDATE**: `npm run build` succeeds. Navigating from navbar "Mens Department" loads `Department.vue`, not the catch-all `CmsPage.vue`.

---

## Testing Strategy

### Manual Test Matrix

| Test                                                                       | Expected Result                                               |
| -------------------------------------------------------------------------- | ------------------------------------------------------------- |
| Visit `/departments/mens` (fresh DB post-migrate)                          | Page renders with seeded placeholder description              |
| Visit `/departments/does-not-exist`                                        | Shows "Department not found" error message, not a blank page  |
| In Filament, create announcement via Department RelationManager            | Announcement appears on `/departments/{slug}` after refresh   |
| In Filament, create Event with `department_id=mens`                        | Event appears on `/departments/mens` upcoming calendar        |
| Event without department_id                                                | Still appears on `/events` (unchanged behavior)               |
| `curl /api/events?department=mens`                                         | Returns only events linked to `mens`                          |
| `curl /api/departments`                                                    | Returns list of 6 published departments                       |
| Unpublish a department via Filament                                        | `/api/departments/{slug}` returns 404                         |
| Hover "Departments" in navbar                                              | Dropdown includes the 6 departments + Gallery + Social        |
| Delete Department in Filament with existing events                         | Events survive (department_id set to null)                    |

### Edge Cases Checklist

- [ ] Department with no announcements renders empty-state message
- [ ] Department with no events renders empty-state message
- [ ] Announcement with null `published_at` still renders (show "Undated")
- [ ] Slug with capital letters in URL: `/departments/MENS` — test case-sensitivity of lookup
- [ ] Unpublished announcement hidden from frontend even though FK exists
- [ ] Hero image missing → graceful fallback (gradient only, no broken `<img>`)
- [ ] Markdown with `<script>` — `marked` sanitization default behavior must be verified
- [ ] Concurrent create of two announcements by two admins — no unique constraint blocks them

---

## Validation Commands

### Level 1: STATIC_ANALYSIS

```bash
./vendor/bin/pint --test
php -l app/Models/Department.php app/Models/DepartmentAnnouncement.php \
    app/Http/Controllers/Api/DepartmentController.php \
    app/Filament/Resources/Departments/DepartmentResource.php
npm run build
```

**EXPECT**: Exit 0, no parse errors, no pint diffs, vite build succeeds.

### Level 2: MIGRATE + ROUTE LIST

```bash
php artisan migrate
php artisan route:list | grep -E 'api/(departments|events)'
```

**EXPECT**: Migrations "Running" → "DONE"; route list includes `api/departments`, `api/departments/{slug}`.

### Level 3: TEST SUITE

```bash
php artisan test
```

**EXPECT**: 0 failures. (No new tests required in scope, but suite must still pass.)

### Level 4: END-TO-END (manual curl)

```bash
curl -s http://localhost:8000/api/departments | jq '.data | length'        # expect 6
curl -s http://localhost:8000/api/departments/mens | jq '.data.slug'       # expect "mens"
curl -s http://localhost:8000/api/departments/bogus | jq '.success'        # expect false
curl -s http://localhost:8000/api/menu/header | jq '.data[] | select(.label=="Departments") | .children | length' # expect 8 (6 new + Gallery + Social)
```

### Level 5: BROWSER VALIDATION

1. `php artisan serve` + `npm run dev`
2. Navigate to `/` → hover "Departments" → verify 6 new entries appear
3. Click "Mens Department" → page renders hero, description, announcements, events
4. Navigate between departments via navbar — `watch` triggers refetch (no hard reload)
5. Open `/admin` → Departments → edit one → add announcement → reload `/departments/{slug}` → see it

---

## Acceptance Criteria

- [ ] 6 departments seeded with published=true on fresh migrate
- [ ] 6 new menu items appear as children of the "Departments" header menu
- [ ] Filament admin has a "Departments" resource with CRUD + Announcements + Events relation tabs
- [ ] Each department page (`/departments/{slug}`) fetches and renders description, announcements, events
- [ ] Events can be optionally tagged with `department_id` in the existing Events form
- [ ] `GET /api/events?department=<slug>` returns filtered results
- [ ] Deleting a department cascades announcements (FK cascade) but nulls the `department_id` on events (no data loss)
- [ ] `npm run build` and `php artisan test` both pass
- [ ] No regression on `/events`, `/ags-updates`, `/cms/welcome`, or existing navbar entries

---

## Completion Checklist

- [ ] All 24 tasks executed in dependency order
- [ ] `php artisan migrate` runs cleanly on a fresh clone
- [ ] `php artisan route:list` shows the two new `/api/departments*` routes
- [ ] Vue bundle builds without warnings about missing imports
- [ ] Navbar dropdown shows 6 new entries
- [ ] Each of the 6 department slugs resolves to a page with hero + description
- [ ] Admin can add an announcement and see it on the public page
- [ ] Admin can add an event with a department and see it on the department page and `/events`
- [ ] Filament Events table still works (no regression from FK addition)

---

## Risks and Mitigations

| Risk                                                                 | Likelihood | Impact | Mitigation                                                                                                      |
| -------------------------------------------------------------------- | ---------- | ------ | --------------------------------------------------------------------------------------------------------------- |
| `/departments/:slug` shadowed by `/:slug(.*)` catch-all              | HIGH       | HIGH   | Task 24 inserts the new route **before** the catch-all; verify order in `routes.js`.                            |
| Filament `Select::relationship` eager loads all departments          | LOW        | LOW    | 6 rows only — `preload()` is safe. If it grows, switch to `->searchable()` without `preload`.                   |
| `$casts` property vs `casts()` method mismatch                       | LOW        | LOW    | Use `casts(): array` (the newer Laravel 12 style, matching Event + AGSUpdate).                                  |
| Migrations run out of order due to timestamp collision with existing | MED        | MED    | Pick timestamps strictly greater than `2026_03_12_000002_*` (use `2026_04_19_*`).                               |
| Markdown XSS via announcement content                                | LOW        | MED    | `marked` v16 sanitizes by default; authors are authenticated Filament admins anyway.                            |
| Vue hot-reload caches stale `routes.js`                              | LOW        | LOW    | `npm run build` before manual test, or full reload of dev server.                                               |
| RelationManager "Events" duplicates the main Events form logic       | MED        | LOW    | Accepted — keeping duplication simple per "three similar lines better than premature abstraction".              |
| Navbar only renders one level of children (no grandchildren)         | N/A        | N/A    | Our menu structure is 2-level (Departments → Mens/Ladies/…) which the existing Navbar already supports.         |
| Existing `/departments` page confusion (GetInvolved.vue vs new pages)| MED        | LOW    | `/departments` (landing) stays as GetInvolved.vue. Only `/departments/:slug` is new. Document in release notes. |

---

## Notes

- **Why a dedicated `Department` model instead of reusing `Page` content blocks?** Announcements and events are structured (dates, published_at) and need per-item CRUD. Shoehorning them into `pages.content` JSON would block filtering and make the admin UX worse for non-technical editors.
- **Why not convert the existing `/departments` landing page too?** Out of scope. Current `GetInvolved.vue` is hand-tuned with gradient cards and gallery integration; replacing it is a separate job. The new sub-pages are additive.
- **Why put announcements as a RelationManager inside Department edit rather than a top-level Filament resource?** Keeps the editor workflow in one place — department leaders edit their department and immediately manage its announcements and events. Top-level resources exist too if full-access admins want a cross-department view (future work).
- **Department color_theme** drives Tailwind gradient classes in `heroClasses()`. Stored as a string (`'blue'`, `'green'`, …) so it's editable without code changes; the Vue view maps each to a concrete gradient class.
- **Calendar format** is a simple chronological grid, not a month view. If the existing `/calendar` route adds a month grid later, the department page can embed it — not in scope now.
- **Why no Pest tests?** The existing features (Events, AGSUpdates, Pages) ship with no feature tests either. Adding tests just for this feature would be inconsistent — tests for the whole API surface are a separate PRP if the team wants them.
