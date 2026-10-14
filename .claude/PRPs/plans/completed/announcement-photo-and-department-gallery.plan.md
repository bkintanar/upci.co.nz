# Feature: Announcement photos and a usable department gallery

## Summary

Department announcements can't hold a photo. Their only fields are a title and a markdown body. The three live
announcements show the workaround: a pasted Facebook embed, a bare Facebook photo link, and plain text. So the owner
says announcements are "only a link". This plan adds one optional photo per announcement, with a required short
description when a photo is attached. The photo shows on the department page and opens full size.

Each department already has a working photo gallery: admin tab, API and public page. It is invisible because no
department has a photo, and the grid hides itself when empty. Adding photos is also slow: one form per photo. This
plan makes the existing gallery usable rather than building a second one:

- an **Upload photos** action that takes up to 30 photos at once, each becoming its own gallery item;
- a **photo viewer** that opens a photo large, with next/previous, keyboard and swipe;
- a department gallery section designed through the design gate.

Everything stays on the existing data model: `gallery_items` with its morph owner, and the shared relation manager.

## User Story

As a national admin who runs a ministry's page
I want to attach a photo to an announcement, and drop a batch of event photos into the department's gallery
So that visitors see the flyer or the event itself on our site, not a Facebook link, and can browse the photos
full size.

## Problem Statement

1. There is no image field anywhere in announcements:
   - no column in `department_announcements`;
   - not in `DepartmentAnnouncement::$fillable`;
   - no `FileUpload` in `AnnouncementsRelationManager`;
   - no key in `DepartmentController::show`;
   - no `<img>` in `Department.vue:67-83`.
   Authors paste Facebook links into `content`. Testable: after this plan, an announcement saved with a photo returns
   `image_path` and `image_alt` from `GET /api/departments/{slug}`, and the department page renders the photo.
2. The department gallery accepts one photo per create form (`GalleryItemsRelationManager`), so loading 20 event
   photos takes 20 modal round-trips. Testable: one "Upload photos" action with N files creates N
   department-owned `gallery_items` rows.
3. Gallery photos can't be enlarged. `GalleryGrid.vue` renders fixed `h-48` crops with no click target. Testable:
   clicking a thumbnail opens a dialog showing that photo, with "Photo X of N", and arrows/Esc work.

## Solution Statement

**Announcement photo.**
- A migration adds two nullable columns: `image_path`, the disk-relative path, named like `events.image_path` and
  `gallery_items.image_path`; and `image_alt`.
- The relation manager form gets a photo field from a new shared `PhotoUpload` factory, plus a "Photo description"
  field that is `requiredWith` the photo. The table gets a thumbnail column.
- `DepartmentController::show` adds both keys to each announcement, as bare paths like every other image field in
  that payload.
- `Department.vue` renders the photo per the design gate. It never crops a flyer: portrait or landscape, it is shown
  whole. Clicking it opens the photo viewer.

**Gallery.**
- `PhotoUpload` centralises the upload rules that are currently copy-pasted:
  - the public disk with public visibility;
  - jpeg/png/webp only, never SVG;
  - 10 MB;
  - in-browser resize to at most 2000 px.

  The shared single-photo form and the standalone gallery form both adopt it.
- The shared `GalleryItemsRelationManager` (mounted on both Department and Region) gains an **Upload photos** header
  action. It is a multiple `PhotoUpload` with up to 30 files, an optional caption applied to every photo, and a
  Published toggle. In one transaction it creates one `GalleryItem` per file through
  `$this->getOwnerRecord()->galleryItems()`. Its `sort_order` continues after the current maximum, in upload order.
- The action is authorised exactly like the built-in CreateAction: hidden on read-only pages, and only when the
  gallery policy allows create.

**Viewer.**
- A new `PhotoViewer.vue` wraps the existing `Modal.vue`. Modal already provides the top-layer dialog, Esc,
  backdrop click, focus trap and focus return. The viewer adds next/previous (buttons, arrow keys, swipe), a polite
  live region "Photo 3 of 12: caption", scroll lock, and no motion under `prefers-reduced-motion`.
- In `GalleryGrid.vue` each thumbnail becomes a `<button>` that opens the viewer. Because `GalleryGrid` is shared,
  the department page, region pages, `/gallery` and CMS gallery blocks all gain the viewer at once.

## Metadata

| Field | Value |
|---|---|
| Type | ENHANCEMENT, plus a NEW_CAPABILITY (announcement photo) |
| Complexity | MEDIUM: one additive migration, admin form and action, one API key pair, a new viewer component, a design gate |
| Systems affected | DB (`department_announcements`), Filament (announcement and gallery relation managers, standalone gallery form), API (`DepartmentController::show`), Vue (`Department.vue`, `GalleryGrid.vue`, new `PhotoViewer.vue`) |
| Dependencies | None new. Filament 4.15.1, Livewire 3.8.10, Vue 3.5.22, Tailwind 3.4, Pest 3.8.2 |
| Estimated tasks | 18 (Task 0 to Task 16, plus Task 2a) |

## Owner decisions (2026-10-15)

| Question | Answer |
|---|---|
| What should the department gallery get? (It already exists but is empty, so invisible.) | **Improve the existing one**: upload many photos at once, click a photo to see it large with next/previous, and a proper gallery section on each department page. It stays hidden until it has photos. |
| Photos per announcement | **One photo**, with a short description for screen readers |
| Who can add them | **National admins only** (the current rules) |

## Standing constraints (memory and design records)

- **Delete nothing.**
  - No file, test, route, method or DB row deletions.
  - Replacing or removing a photo leaves the old file on disk, as the gallery and events already do.
  - Test artefacts that land in real storage are moved to `/var/www/personal/upci.co.nz-backups/qa-artifacts-<date>/`,
    never deleted.
- **Back up first** before the live migration, to `/var/www/personal/upci.co.nz-backups/<label>-<timestamp>/`:
  - a git bundle;
  - a tarball without vendor or node_modules;
  - `sqlite3 upci ".backup ..."`;
  - the uncommitted patch;
  - a git tag.
- **The live DB migration needs the owner's OK.**
- **Every UI change goes through the huashu-design three-direction gate** (Task 8).
  - upca.org.au is a reference, not a template.
  - The brand green is `#4D7B37`, built as the `brand-green-700` oklch token.
- **`FILESYSTEM_DISK=local`**, so every `FileUpload` must say `->disk('public')->visibility('public')`. `PhotoUpload`
  does this once.
- **Admin date-times display in NZ time**: the `app.display_timezone` hook in `AdminPanelProvider::boot`, commit
  `9022d6c`. Don't use `FilamentTimezone::set()`. The announcement `published_at` picker stays UTC; that is
  deliberate.
- **Public-form text in the admin is never `->html()`.** Announcement content is national-only for the same reason
  (`DepartmentAnnouncementPolicy` docblock).
- Commit only when asked. Don't push unless asked.

## UX Design

### Before State
```
ADMIN  Departments › Edit "Men's"
  ├─ Announcements tab   [New] → Title · Content (markdown) · Published at · Order · Published
  │                          └─ no photo field; authors paste a Facebook link/iframe into Content
  ├─ Events tab
  └─ Gallery tab         [New gallery item] → Title · Description · Image (ONE) · Published · Order
                             └─ 20 event photos = 20 round-trips through this modal

PUBLIC  /departments/mens
  Hero → Description → Announcements: date · title · markdown (link/iframe) → Calendar
       → <GalleryGrid department="mens"> → renders NOTHING (0 department photos; empty grid hides)
  Gallery photos (when they exist): fixed h-48 crops, not clickable, cannot be enlarged

DATA  department_announcements(title, content, published_at, is_published, sort_order)  — no image
      gallery_items(image_path, galleryable_type='department'|'region'|NULL, …)  — 1 row, owner NULL
```

### After State
```
ADMIN  Departments › Edit "Men's"
  ├─ Announcements tab   [New] → Title · Content · PHOTO (optional, jpeg/png/webp ≤10 MB, resized ≤2000px)
  │                               · PHOTO DESCRIPTION (required when a photo is attached) · Published at · …
  │                      table: [thumb] Title · Published at · Status
  └─ Gallery tab         [UPLOAD PHOTOS] → up to 30 photos (grid, drag to reorder) · Caption (optional,
                         applied to all) · Published  →  N gallery items, appended in upload order
                         [New gallery item] (one photo with its own title/description, unchanged)

PUBLIC  /departments/mens   (visual treatment per the Task 8 design gate)
  Announcements: date · title · PHOTO (whole, never cropped; click → viewer) · markdown
  Gallery section (only when it has photos): thumbnails are buttons
     click → PhotoViewer dialog: large photo · caption · "Photo 3 of 12" · ‹ › · Esc · swipe
             focus returns to the thumbnail on close
  Same viewer on /regions/{slug}, /gallery and CMS gallery blocks (shared GalleryGrid)

DATA  department_announcements + image_path, image_alt (nullable)
      gallery_items unchanged; many rows per upload
```

### Interaction Changes
| Location | Before | After | User impact |
|---|---|---|---|
| Admin › Department › Announcements | Text and links only | Optional photo with a description; thumbnail in the table | Post a flyer or photo directly |
| Admin › Department/Region › Gallery | One photo per form | "Upload photos": up to 30 at once | A whole event's photos in one go |
| Admin › Gallery item (standalone) | 5 MB, no resize | Same rules as everywhere (10 MB, resized ≤ 2000 px) | Phone photos fit |
| `/departments/{slug}` announcements | Date, title, text | Plus a whole, clickable photo | Visitors see the flyer, not a link |
| `/departments/{slug}` gallery | Hidden (empty) | A designed section once it has photos | The gallery appears as soon as it's used |
| Every `GalleryGrid` | Static crops | Click to open the viewer with next/previous | Photos can be seen properly |

## Mandatory Reading

| Priority | File | Lines | Why |
|---|---|---|---|
| P0 | `app/Filament/Resources/Shared/GalleryItemsRelationManager.php` | all | Gets the bulk action. It is shared by Department and Region. |
| P0 | `app/Filament/Resources/Departments/RelationManagers/AnnouncementsRelationManager.php` | all | Gets the photo fields and the thumbnail |
| P0 | `app/Http/Controllers/Api/DepartmentController.php` | 40-93 | The announcement map to extend |
| P0 | `resources/js/components/GalleryGrid.vue` | all | Shared grid. Thumbnails become buttons. |
| P0 | `resources/js/components/Modal.vue` | all | `PhotoViewer` wraps it. It has the focus trap, focus return and backdrop click. |
| P0 | `resources/js/views/Department.vue` | 60-201 | Announcement card and gallery mount |
| P1 | `database/migrations/2026_08_18_120000_add_artwork_to_events.php` | all | Migration to mirror: why `_path`, nullable forever, `after()`, dropColumn `down()` |
| P1 | `app/Filament/Resources/Events/Schemas/EventForm.php` | 27-40 | Optional image on a record, with a disk comment and helper text |
| P1 | `app/Filament/Resources/GalleryItems/Schemas/GalleryItemForm.php` | all | The standalone form; adopts `PhotoUpload` |
| P1 | `app/Policies/GalleryItemPolicy.php` | all | `create` is national or regional; `manages()` covers national, or regional on its own region |
| P1 | `vendor/filament/filament/src/Resources/RelationManagers/RelationManager.php` | 215-235, 349-365 | `getOwnerRecord()` and `isReadOnly()`. Only built-in actions are auto-authorised. |
| P1 | `vendor/filament/filament/src/Resources/Concerns/InteractsWithRelationshipTable.php` | 350-360 | `canCreate()` (protected), which the custom action reuses |
| P2 | `tests/Feature/AnnouncementTest.php`, `tests/Feature/GalleryOwnershipTest.php`, `tests/Feature/AdminTablesTest.php` | all | Test helpers and Livewire relation-manager setup |
| P2 | `resources/js/utils/markdown.js` | 20-90 | The sanitiser that lets Facebook iframes through. Unchanged, but explains the live content. |
| P2 | `vendor/filament/forms/docs/09-file-upload.md` | "Uploading multiple files", "Reordering files", "Number of files validation", "Cropping and resizing images without the editor", "File type validation" | The version-matched docs |

**External documentation**

| Source | Section | Why |
|---|---|---|
| [Filament 4.x File upload](https://filamentphp.com/docs/4.x/forms/file-upload) (also `vendor/filament/forms/docs/09-file-upload.md`) | Uploading multiple files · Number of files validation · Reordering files · Cropping and resizing images without the editor · File type validation (danger aside) | `multiple`, `maxFiles`, `appendFiles`, `panelLayout('grid')`, `automaticallyResizeImages*`. Note that `image()` alone allows SVG. |
| [Livewire 3 uploads](https://livewire.laravel.com/docs/3.x/uploads) | Global validation · Global middleware · Configuring automatic file cleanup | 12 MB temporary limit, `throttle:60,1`, temporary-file cleanup |
| [WAI-ARIA APG: modal dialog](https://www.w3.org/WAI/ARIA/apg/patterns/dialog-modal/) | Keyboard interaction · WAI-ARIA roles | Esc, Tab wrap, focus return, labelling |
| [MDN `<dialog>`](https://developer.mozilla.org/en-US/docs/Web/HTML/Reference/Elements/dialog) and [`closedBy`](https://developer.mozilla.org/docs/Web/API/HTMLDialogElement/closedBy) | Accessibility · Modal dialogs | `showModal` makes the page inert. There's no native backdrop close in Safari, so keep Modal's `event.target === dialog` fallback. |

## Verified facts this plan relies on (2026-10-15)

| Fact | Evidence |
|---|---|
| The department gallery exists end to end | `Department::galleryItems()` morphMany (`app/Models/Department.php:50-53`); `DepartmentResource::getRelations()` includes the shared `GalleryItemsRelationManager` (`:47-51`); `GET /api/gallery?department=` (`routes/web.php:65`); `Department.vue:115` |
| It is invisible today | Live `gallery_items` has 1 row, with a NULL owner. `GalleryGrid.shouldRender` hides an empty, error-free grid. |
| Announcements have no image anywhere | Live `.schema department_announcements`; `$fillable` (`DepartmentAnnouncement.php:10-17`); relation manager form; `DepartmentController.php:51-60`; `Department.vue:67-83` |
| Live announcements: 3 | id 2 childrens "Junior Bible Quizzing 2026" (has a Facebook iframe); id 3 mens "Apostolic Men's Rally - Queenstown" (text); id 8 ladies "General Ladies Director Praise Report \| Whangarei" (a bare Facebook photo URL). All published. |
| Announcements and department galleries are national-only | `DepartmentAnnouncementPolicy extends NationalOnlyPolicy`. `GalleryItemPolicy::manages()`: national, or regional on its own region only. `DepartmentPolicy::update` is national, so a regional user can't open the department edit page. |
| Region uses the same relation manager | `RegionResource::getRelations()` (`:59-64`), so the bulk action also appears for a regional user on their own region. The owner comes from the record, and the policy allows regional create. |
| Custom table actions aren't auto-authorised in relation managers | `RelationManager.php:349-363` covers only Create, Edit, Delete, Attach and similar. A custom `Action` needs `->authorize(fn (): bool => ! $this->isReadOnly() && $this->canCreate())`. `canCreate()` is protected in `InteractsWithRelationshipTable.php:354`; it can be called from the class. |
| `authorize()` accepts a closure | `vendor/filament/actions/src/Concerns/CanBeAuthorized.php:33-49`. A non-string value is stored as is and evaluated. |
| The FileUpload API in 4.15.1 | `multiple`, `maxFiles`, `reorderable` and `maxParallelUploads` are in `BaseFileUpload`. `appendFiles`, `panelLayout`, `automaticallyResizeImagesMode/ToWidth/ToHeight`, `automaticallyUpscaleImagesWhenResizing` and `imageEditor` are in `FileUpload`. The getters exist: `getAcceptedFileTypes`, `getDiskName`, `getVisibility`, `getDirectory`, `getMaxSize`, `getMaxFiles`, `getAutomaticallyResizeImagesMode/Width/Height`. `shouldOrientImagesFromExif` defaults to true (`FileUpload.php:57`). The old `imageResize*` names are deprecated. |
| `image()` alone accepts SVG | `acceptedFileTypes(['image/*'])`. Every photo field must list `['image/jpeg','image/png','image/webp']`. |
| The Livewire defaults | Temporary rule `max:12288` (12 MB), `max_upload_time` 5 min, upload middleware `throttle:60,1`. FilePond uploads each file in its own request (`file-upload.js:289`). |
| Server limits | PHP-FPM 8.4: `upload_max_filesize=20M`, `post_max_size=25M`, `max_file_uploads=20`. nginx `upci.b8.co.nz`: `client_max_body_size 25M`. The CLI `php` limits are 2M and 8M, so local test servers must raise them (Task 14). |
| The upload test technique works (probed 2026-10-15, scratchpad, in-memory DB) | `Livewire::test(GalleryItemsRelationManager::class, ['ownerRecord' => $dept, 'pageClass' => EditDepartment::class])->callTableAction('create', data: ['title' => 'Camp', 'image_path' => UploadedFile::fake()->image('camp.jpg', 1200, 800)])` stores `gallery/<hash>.jpg` on the faked public disk, owned by `['department', id]`. An `.svg` via `UploadedFile::fake()->createWithContent()` gives `assertHasTableActionErrors(['image_path'])`. |
| Tests run in memory | `phpunit.xml`: `DB_DATABASE=:memory:`. Migrations seed the six departments, so test department ids start at 7. |
| The whole planned admin design works (plan-review probe, 2026-10-15, scratchpad, in-memory DB; 7/7 passed) | Probe subclasses of both relation managers carried the exact Task 4 and Task 6 code. **A**: the action's `$data['photos']` is stored paths (`gallery/<hash>.jpg`), not temporary files; 3 rows get owner `department`, caption "Rally", `sort_order` 6, 7, 8 after an existing 5; `assertNotified('3 photos added')` passes. **B**: 31 files give error key `photos` ("must not have more than 30 items") and 0 rows. **C**: one SVG in a batch gives key `photos` ("must be a file of type: image/jpeg, image/png, image/webp") and 0 rows. **D**: on `ViewDepartment`, `assertTableActionHidden('uploadPhotos')` passes. **E**: a regional user on their own region's `EditRegion` can upload; the row is owned by `['region', id]`. **F**: a regional user gets 403 on `/admin/departments/{id}/edit`. **G**: a photo without a description gives key `image_alt` with the custom message from `->validationMessages(['required_with' => …])` (re-run confirmed); with one it saves `announcements/<hash>.jpg`; a description with no photo also saves (Task 5 handles this). |
| The default `requiredWith` message is unfit for admins | It reads "The image alt field is required when image path is present." Task 4 sets labels and a custom message. |
| **Staging runs straight from the working tree** | nginx root is `/var/www/personal/upci.co.nz/public` and PHP-FPM serves the checkout. So code is live on staging as soon as it is saved, and `npm run build` publishes the frontend immediately. That is why Task 2a applies the additive migration to live before any code that writes the new columns. |
| `Modal.vue`'s panel CSS has to be overridden in the same file | `.modal-panel` (white, `max-width: 42rem`, `overflow-y: auto`) and `.modal-panel--wide` live in `Modal.vue`'s global `<style>`. A `.modal-panel--photo` in another component's `<style>` would depend on chunk load order. |
| `LARAVEL_STORAGE_PATH` does not redirect disks when serving a copy | Seen 2026-10-14. Browser-test uploads land in the real `storage/app/public`. Record and move them (Task 14). |
| Branch state | Work sits on `feature/homepage-upca-style`, unpushed: `55fa913`, `7ed37b4`, `33ee381`, `2a0ccd1`, `9022d6c`. `main` is at `e6212d9`, without the Filament 4.15 upgrade (`11c82aa`) or the homepage. |

## Patterns to Mirror

**MIGRATION: optional image column** (`database/migrations/2026_08_18_120000_add_artwork_to_events.php`)
```php
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->string('image_path')->nullable()->after('description');
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn('image_path');
        });
    }
};
```

**UPLOAD FIELD: the comments explain the disk and the SVG risk** (`GalleryItemsRelationManager.php`, form)
```php
FileUpload::make('image_path')
    ->image()
    // See GalleryItemForm — FILESYSTEM_DISK is `local`,
    // and ->image() alone accepts SVG, which can carry
    // script once the file is web-served.
    ->disk('public')
    ->visibility('public')
    ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
    ->maxSize(5120)
    ->directory('gallery')
    ->required(),
```

**SHARED ADMIN HELPER: static, documented** (`app/Filament/Resources/Shared/TextLines.php`)
```php
class TextLines
{
    /**
     * @return list<string>
     */
    public static function split(?string $text): array
    { ... }
}
```

**TABLE THUMBNAIL** (`GalleryItemsRelationManager.php`, table)
```php
ImageColumn::make('image_path')
    ->label('Image')
    ->disk('public')
    ->height(48),
```

**API: a bare disk-relative path** (`DepartmentController.php:51-60`, extended in Task 5)
```php
->map(fn ($a) => [
    'id' => $a->id,
    'title' => $a->title,
    'content' => $a->content,
    'published_at' => $a->published_at?->toIso8601String(),
]);
```

**VUE: image path helper** (`Department.vue:150-154`)
```js
const imageUrl = (path) => {
    if (!path) return ''
    if (path.startsWith('http')) return path
    return `/storage/${path}`
}
```

**DIALOG** (`Modal.vue`): `v-model`, `label`, `panelClass`; it teleports a `<dialog>` to the body.
```vue
<Modal v-model="personOpen" :panel-class="person && person.bio ? 'modal-panel--wide' : ''" :label="person ? person.title : 'Leadership detail'">
```

**NOTIFICATION** (`app/Filament/Pages/ManageSiteSettings.php:129`)
```php
Notification::make()->success()->title('Site settings saved')->send();
```

**TESTS: relation manager with an upload** (probe-verified; setup as in `AdminTablesTest.php:30-38`)
```php
beforeEach(function () {
    Storage::fake('public');
    $this->actingAs(User::create([
        'name' => 'N', 'email' => 'photos-national@x', 'password' => 'x',
        'role' => UserRole::ADMINISTRATOR, 'access_level' => AccessLevel::NATIONAL,
    ]));
    Filament::setCurrentPanel('admin');
});

Livewire::test(GalleryItemsRelationManager::class, ['ownerRecord' => $dept, 'pageClass' => EditDepartment::class])
    ->callTableAction('create', data: ['title' => 'Camp', 'image_path' => UploadedFile::fake()->image('camp.jpg', 1200, 800)])
    ->assertHasNoTableActionErrors();
```

**TESTS: API** (`AnnouncementTest.php`)
```php
function announce(array $attrs = []): DepartmentAnnouncement { /* department_id, title, content, published_at, is_published, sort_order */ }
collect($this->getJson('/api/departments/mens')->json('data.announcements'))
```

**TESTS: Vue source contracts** (`HomepageUpcaStyleTest.php`, the `upcaHomeSource()` helper and the
"no class names from fragments" dataset). There's no JS test runner, so the markup's promises are pinned by reading
the source.

## Files to Change

| File | Action | Justification |
|---|---|---|
| `database/migrations/2026_10_15_000001_add_photo_to_department_announcements.php` | CREATE | `image_path`, `image_alt` nullable |
| `app/Models/DepartmentAnnouncement.php` | UPDATE | fillable |
| `app/Filament/Resources/Shared/PhotoUpload.php` | CREATE | One definition of a safe, resized photo upload |
| `app/Filament/Resources/Departments/RelationManagers/AnnouncementsRelationManager.php` | UPDATE | Photo, description, thumbnail column |
| `app/Http/Controllers/Api/DepartmentController.php` | UPDATE | `image_path`, `image_alt` per announcement |
| `app/Filament/Resources/Shared/GalleryItemsRelationManager.php` | UPDATE | "Upload photos" action; single form uses `PhotoUpload` |
| `app/Filament/Resources/GalleryItems/Schemas/GalleryItemForm.php` | UPDATE | Uses `PhotoUpload` (same rules everywhere) |
| `.claude/design/department-photos/` | CREATE | Design gate: spec, brand spec, three demos, shots, `direction-approved.md` |
| `resources/js/components/PhotoViewer.vue` | CREATE | The viewer, wrapping Modal |
| `resources/js/components/Modal.vue` | UPDATE | Style only: add `.modal-panel--photo` beside `.modal-panel--wide`, so the cascade order is guaranteed. No behaviour change. |
| `resources/js/components/GalleryGrid.vue` | UPDATE | Thumbnail buttons that open the viewer, per the contract |
| `resources/js/views/Department.vue` | UPDATE | Announcement photo and gallery section, per the contract |
| `tests/Feature/AnnouncementPhotoTest.php` | CREATE | Migration, API, admin form |
| `tests/Feature/GalleryBulkUploadTest.php` | CREATE | Action, authorisation, ordering, limits |
| `tests/Feature/PhotoUploadRulesTest.php` | CREATE | `PhotoUpload` contract, applied in all four places |
| `tests/Feature/DepartmentPhotosFrontendTest.php` | CREATE | Vue source contracts |
| `.claude/PRPs/reports/announcement-photo-and-department-gallery-report.md` | CREATE | Report |

## NOT Building (Scope Limits)

- **Albums.** The owner chose to improve the gallery, not to add albums.
- **Department-scoped admins.** The owner chose national-only. Policies are unchanged.
- **More than one photo per announcement** (owner choice). A gallery covers the "many photos" case.
- **Server-side thumbnails or srcset.** No intervention/image dependency. In-browser resize to ≤ 2000 px keeps files
  small. Revisit if galleries grow large.
- **Deleting files** when a photo is replaced, removed or its row deleted. This matches galleries and events today,
  and the owner's delete-nothing rule. Note it in the report as a future housekeeping job.
- **Rewriting the three live announcements' Facebook links.** That is content. The report suggests the owner
  re-posts their photos.
- **Restyling the rest of the department page** (hero, description, calendar). The design gate covers the
  announcements and gallery sections and the viewer only.
- **Auto-adding announcement photos to the gallery.** Not asked for. Keep the two separate.

## Step-by-Step Tasks

### Task 0: Branch and baseline
- **ACTION**: ask the owner whether to merge `feature/homepage-upca-style` into `main` first (recommended), then
  `git checkout main && git merge --ff-only feature/homepage-upca-style` (fast-forward; `main` is a strict ancestor)
  and `git checkout -b feature/department-photos`. If the owner declines, branch from `feature/homepage-upca-style`.
- **VALIDATE**: `php artisan test` gives 328 passed, 25 skipped, 0 failed (the baseline after `9022d6c`).

### Task 1: Migration: announcement photo columns
- **CREATE** `database/migrations/2026_10_15_000001_add_photo_to_department_announcements.php`. Mirror the events
  artwork migration, with a docblock explaining:
  - why: authors pasted Facebook links because there was nowhere to put a photo;
  - why the names: `image_path` to match the schema, `image_alt` for the description;
  - that both are nullable forever, since an announcement without a photo is normal.
- **IMPLEMENT**: `$table->string('image_path')->nullable()->after('content'); $table->string('image_alt')->nullable()->after('image_path');`.
  `down()` drops both columns. That is the migration's own columns, consistent with every migration here.
- **GOTCHA**: don't touch `content` (`longText` in the migration, `text` live in SQLite; leave it).
- **VALIDATE**: `php artisan test --filter=AnnouncementTest` passes (4 existing tests).

### Task 2: Model
- **UPDATE** `DepartmentAnnouncement::$fillable`: add `'image_path', 'image_alt'`.
- **VALIDATE**: `php -l app/Models/DepartmentAnnouncement.php`.

### Task 2a: Apply the additive migration to live first (only with the owner's OK)
- **WHY**: staging runs straight from the working tree. Once Task 4's form is on disk, any announcement save on
  staging fails with "no such column: image_path" until live has the columns. The columns are nullable and additive,
  so applying them ahead of the code is safe. The current code never reads or writes them.
- **ACTION**: after Tasks 1 and 2 pass their tests, ask the owner. On a yes:
  - back up to `/var/www/personal/upci.co.nz-backups/pre-department-photos-<ts>/`: `repo.bundle` (`git bundle create … --all`), `site.tar.gz` (no vendor or node_modules), `upci.sqlite` via `sqlite3 upci ".backup …"` plus `PRAGMA integrity_check`, `uncommitted.patch` (`git diff HEAD`), and `git tag pre-department-photos-<ts>`;
  - `php artisan migrate --force`.
- **If the owner says not yet**: write Tasks 3 to 7 but don't leave Task 4 or Task 6 code in the working tree between
  sessions. Alternatively, tell the owner that announcement saves on staging fail until the migration runs.
- **VALIDATE**:
  - `sqlite3 -readonly upci ".schema department_announcements"` shows `image_path` and `image_alt`;
  - `php artisan migrate:status | tail -1` shows Ran;
  - `curl -s https://upci.b8.co.nz/api/departments/mens` still returns 200 with its announcements.

### Task 3: `PhotoUpload` shared factory
- **CREATE** `app/Filament/Resources/Shared/PhotoUpload.php`:
  ```php
  namespace App\Filament\Resources\Shared;

  use Filament\Forms\Components\FileUpload;

  /**
   * One definition of a photo upload, used by announcements and galleries.
   *
   * - public disk with public visibility: FILESYSTEM_DISK is `local`, and a FileUpload that does not
   *   name its disk writes into private storage, where the image 404s on the public site. Filament v4
   *   also picks the default visibility by a literal match on the disk name, so it is set explicitly
   *   (the reason GalleryItemForm gave).
   * - jpeg/png/webp only: ->image() alone accepts SVG, which can carry script once web-served.
   * - 10 MB and an in-browser resize to at most 2000px: phone photos are 3-12 MB; shrinking them before
   *   upload keeps each request under Livewire's 12 MB temporary limit and staging's 25 MB body limit,
   *   and keeps pages light without a server-side thumbnail step. 'contain' never crops (a flyer keeps
   *   its edges) and nothing small is upscaled. EXIF orientation is applied by default.
   */
  class PhotoUpload
  {
      public const TYPES = ['image/jpeg', 'image/png', 'image/webp'];

      public const MAX_KB = 10240;

      public const MAX_EDGE = '2000';

      public static function make(string $name, string $directory): FileUpload
      {
          return FileUpload::make($name)
              ->image()
              ->disk('public')
              ->visibility('public')
              ->acceptedFileTypes(self::TYPES)
              ->maxSize(self::MAX_KB)
              ->directory($directory)
              ->automaticallyResizeImagesMode('contain')
              ->automaticallyResizeImagesToWidth(self::MAX_EDGE)
              ->automaticallyResizeImagesToHeight(self::MAX_EDGE)
              ->automaticallyUpscaleImagesWhenResizing(false);
      }
  }
  ```
- **GOTCHA**:
  - FilePond checks `maxSize` against the original file on add, which is why the limit is 10 MB, not 5 MB.
  - The server-side rule then checks the resized file.
- **TEST** (`tests/Feature/PhotoUploadRulesTest.php`):
  - `PhotoUpload::make('x', 'dir')` getters return: disk `public`, visibility `public`, the three types (no
    `image/svg+xml`), `10240`, `contain`, `2000`/`2000`, directory `dir`.
  - Source-contract dataset: each of `AnnouncementsRelationManager.php`, `GalleryItemsRelationManager.php` and
    `GalleryItemForm.php` contains `PhotoUpload::make(`.
  - No photo field in those files still calls `FileUpload::make('image_path')` directly.
- **VALIDATE**: `php artisan test --filter=PhotoUploadRules`.

### Task 4: Announcements relation manager
- **UPDATE** the form. After `MarkdownEditor::make('content')`:
  ```php
  PhotoUpload::make('image_path', 'announcements')
      ->label('Photo')
      ->imageEditor()
      ->helperText('Optional. A flyer or a photo from the event. It shows whole on the department page, never cropped.')
      ->columnSpanFull(),
  TextInput::make('image_alt')
      ->label('Photo description')
      ->maxLength(255)
      ->requiredWith('image_path')
      // The default reads "The image alt field is required when image path is present."
      ->validationMessages(['required_with' => 'Describe the photo for people who cannot see it.'])
      ->helperText('Say what the photo shows, for people who cannot see it — e.g. "Flyer: Junior Bible Quizzing, 14 March, Hamilton".')
      ->columnSpanFull(),
  ```
- **UPDATE** the table: prepend `ImageColumn::make('image_path')->label('Photo')->disk('public')->height(40)`.
- **IMPORTS**: `Filament\Tables\Columns\ImageColumn`, `App\Filament\Resources\Shared\PhotoUpload`.
- **GOTCHA**:
  - Filament 4 closure utilities are `Filament\Schemas\Components\Utilities\Get` and `Set`, not
    `Filament\Forms\Get`. This bit `PageForm` before.
  - `requiredWith()` avoids closures entirely, and exists in `CanBeValidated.php:402`.
- **TEST** (`AnnouncementPhotoTest.php`), with
  `Livewire::test(AnnouncementsRelationManager::class, ['ownerRecord' => $dept, 'pageClass' => EditDepartment::class])`:
  - create with `image_path` = `UploadedFile::fake()->image('flyer.jpg', 800, 1131)` and `image_alt` creates a row
    with `announcements/<hash>.jpg` on the faked public disk;
  - a photo without a description gives `assertHasTableActionErrors(['image_alt' => 'Describe the photo for people who cannot see it.'])`;
  - an SVG is refused with `['image_path']`;
  - an announcement without a photo saves as before;
  - the table renders the `image_path` column (`assertTableColumnExists('image_path')`).
- **VALIDATE**: `php artisan test --filter=AnnouncementPhoto`.

### Task 5: API
- **UPDATE** `DepartmentController::show`: add
  `'image_path' => $a->image_path, 'image_alt' => $a->image_path ? $a->image_alt : null` to the announcement map.
  Bare paths, like `hero_image` and `logo_path` in the same payload. The description is only sent with a photo: the
  probe showed a description typed without a photo still saves (`requiredWith` only works one way), and a stray
  description shouldn't reach the page.
- **TEST**:
  - `data.announcements.0.image_path` and `image_alt` are present and correct;
  - both keys are present and null on an announcement without a photo (the contract is stable for the Vue page);
  - a stored `image_alt` without an `image_path` comes back as `image_alt: null`;
  - the existing `AnnouncementTest` still passes.
- **VALIDATE**: `php artisan test --filter='AnnouncementPhoto|AnnouncementTest'`.

### Task 6: Gallery relation manager: bulk "Upload photos" and the shared upload
- **UPDATE** the form: replace the inline `FileUpload::make('image_path')…` with
  `PhotoUpload::make('image_path', 'gallery')->required()`. Keep the existing comment's point in `PhotoUpload`'s
  docblock.
- **ADD** a header action, before `CreateAction::make()`:
  ```php
  Action::make('uploadPhotos')
      ->label('Upload photos')
      ->icon(Heroicon::OutlinedPhoto)
      ->modalHeading('Upload photos')
      ->modalDescription('Add up to 30 photos at once. Each becomes its own gallery item, in the order shown; you can give them a shared caption and edit any of them afterwards.')
      ->modalSubmitActionLabel('Upload')
      // Custom actions are not authorised automatically in a relation manager (only Create/Edit/Delete
      // and friends are — RelationManager.php:349-363). Mirror CreateAction: never on a read-only (View)
      // page, and only when the gallery policy allows create.
      ->authorize(fn (): bool => ! $this->isReadOnly() && $this->canCreate())
      ->schema([
          PhotoUpload::make('photos', 'gallery')
              ->label('Photos')
              ->multiple()
              ->minFiles(1)
              ->maxFiles(30)   // Livewire throttles uploads at 60 a minute
              ->appendFiles()
              ->reorderable()
              ->panelLayout('grid')
              ->required(),
          TextInput::make('caption')
              ->label('Caption for these photos (optional)')
              ->maxLength(255)
              ->helperText('Used as each photo\'s title — e.g. "Men\'s Rally, Queenstown 2026". Screen readers read it, so say what the photos show.'),
          Toggle::make('is_published')->label('Published')->default(true),
      ])
      ->action(function (array $data): void {
          $owner = $this->getOwnerRecord();

          $count = DB::transaction(function () use ($owner, $data): int {
              $next = (int) $owner->galleryItems()->max('sort_order');

              foreach (array_values($data['photos']) as $i => $path) {
                  $owner->galleryItems()->create([
                      'title' => filled($data['caption'] ?? null) ? $data['caption'] : null,
                      'image_path' => $path,
                      'is_published' => (bool) ($data['is_published'] ?? true),
                      'sort_order' => $next + $i + 1,
                  ]);
              }

              return count($data['photos']);
          });

          Notification::make()->success()
              ->title($count === 1 ? '1 photo added' : "{$count} photos added")
              ->send();
      }),
  ```
- **IMPORTS**:
  - `Filament\Actions\Action`
  - `Filament\Support\Icons\Heroicon` (confirmed in 4.15.1: `Heroicon::OutlinedPhoto`)
  - `Filament\Notifications\Notification`
  - `Illuminate\Support\Facades\DB`
  - `App\Filament\Resources\Shared\PhotoUpload`
- **GOTCHA**:
  - **Legacy column default.** `gallery_items.department` is a legacy NOT NULL string with default `'general'`.
    Leave it to the default, as the existing CreateAction does.
  - **Paths, not files** (probe-verified, A). `$data['photos']` holds stored paths such as `gallery/<hash>.jpg` by
    the time `action()` runs.
  - **Orphans from a failed transaction.** Files are stored before the transaction. If an insert ever failed, the
    rows roll back but the files stay on disk. That's acceptable under delete-nothing, and validation failures (B, C)
    happen before any file is stored.
  - **Region gallery.** Region uses this relation manager too, so a regional user sees the action on their own
    region. That is correct: the owner is fixed by the record, and `GalleryItemPolicy::create` allows regional.
- **TEST** (`GalleryBulkUploadTest.php`):
  1. A national admin uploads 3 fakes to a department, with the caption "Rally" and Published on. This creates 3
     rows, all owned by `['department', id]`, all titled "Rally", with files under `gallery/` on the faked public
     disk. `assertNotified('3 photos added')` passes.
  2. `sort_order` continues after an existing item at `5` (so 6, 7, 8) in upload order.
  3. No caption gives null titles.
  4. Published off gives rows that `GET /api/gallery?department=` does not return.
  5. 31 files fail with `assertHasTableActionErrors(['photos'])` and create no rows.
  6. An SVG among the files fails and creates no rows (the transaction).
  7. On the department View page (`pageClass => ViewDepartment::class`) the action is hidden
     (`assertTableActionHidden('uploadPhotos')`).
  8. A regional user on their own region's relation manager (`EditRegion`) can upload, and the rows are owned by
     `['region', their region]`.
  9. The single "New gallery item" create still works through `PhotoUpload` (the probe shape).
  - Existing `GalleryOwnershipTest` and `AdminTablesTest` must still pass.
- **VALIDATE**: `php artisan test --filter='GalleryBulkUpload|GalleryOwnership|AdminTables'`.

### Task 7: Standalone gallery form adopts `PhotoUpload`
- **UPDATE** `GalleryItemForm.php`: its `FileUpload::make('image_path')…` becomes
  `PhotoUpload::make('image_path', 'gallery')->required()`, keeping any label and helper. The `MorphToSelect` owner
  picker is unchanged.
- **VALIDATE**: `php artisan test --filter='GalleryOwnership|PhotoUploadRules'`.

### Task 8: huashu-design three-direction gate (STOP for the owner's pick)
- **ACTION**: run the huashu-design skill with a shared spec in
  `.claude/design/department-photos/spec.md` (plus `brand-spec.md`, copied from `.claude/design/homepage-upca/`).
- **Scope of the spec**:
  - the department page's Announcements section, with a card with no photo, a landscape event photo and a portrait
    A4 flyer;
  - the Gallery section: 1, 5 and 14 photos, mixed orientations, with and without captions;
  - the PhotoViewer: open state at 1440 and 390, the "Photo 3 of 12" counter, caption, prev/next and close.
- **Real content**:
  - the three live announcement titles;
  - real photos from `storage/app/public/department-images/`;
  - `.claude/design/homepage-upca/assets/` (`mens-camp-group.png`, `banner-pentecost-everyday.jpg`,
    `nz-landmarks-collage.jpg`);
  - a portrait crop of one as the flyer stand-in.
- **Hard constraints**:
  - brand tokens only, Figtree;
  - never crop a flyer;
  - thumbnails may crop but must be buttons with visible focus;
  - the viewer is a dark top-layer dialog, has no autoplay, and moves nothing under `prefers-reduced-motion`;
  - 390 px has no horizontal scroll;
  - Tailwind 3-expressible: literal classes, brand oklch tokens have no `/alpha`, so use `white/NN` or
    `[color:oklch(... / a)]`.
- **Output**:
  - three self-contained HTML demos in `design-demos/` and screenshots;
  - publish them together as an artifact;
  - STOP and ask the owner to choose;
  - record their words, and a class contract per slot, in `direction-approved.md`, as the homepage did.

### Task 9: `PhotoViewer.vue`
- **CREATE** `resources/js/components/PhotoViewer.vue`:
  - **Props**: `modelValue` (open), `photos` (`[{ src, alt, caption }]`), `index` (v-model:index).
  - **Emits**: `update:modelValue`, `update:index`.
  - **Wraps** `<Modal :model-value :label="'Photo viewer'" panel-class="modal-panel--photo" @update:model-value>`.
    Modal supplies the top layer, Esc, backdrop click (`event.target === dialog`, Safari-safe), Tab trap and focus
    return to the opener.
  - **Inside**:
    - the `<img>` with no `loading="lazy"` (it is the visible photo), `decoding="async"` and `alt`;
    - the caption;
    - prev/next `<button>`s, with `aria-label` "Previous photo" and "Next photo", hidden when there's one photo,
      wrapping at the ends;
    - a visually hidden `<p role="status" aria-live="polite">Photo {i+1} of {n}{caption ? ': ' + caption : ''}</p>`.
  - **Keys**: `@keydown` on the panel root. `ArrowLeft`/`ArrowRight` move; Modal handles Esc and Tab.
  - **Swipe**: pointer events with a 50 px horizontal threshold; `touch-action: pan-y` on the image.
  - **Scroll lock**: while open, set `document.documentElement.style.overflow = 'hidden'` and restore on close and
    on unmount. `overscroll-behavior: contain` on the panel.
  - **Motion**: transitions only inside `@media (prefers-reduced-motion: no-preference)`.
  - **Styling**: the dark panel's look comes from the Task 8 contract. The `.modal-panel--photo` rules go in
    **`Modal.vue`'s own `<style>`, beside `.modal-panel--wide`**. They must override `.modal-panel`'s white
    background, `max-width: 42rem`, `max-height` and `overflow-y: auto`, and a rule in another component would
    depend on chunk load order.
  - **Panel size**: keep the panel sized to the photo (for example at most about 92vw by 88vh). Don't make it fill
    the viewport. Modal closes on a click that lands on the `<dialog>` itself, so a full-viewport panel would leave
    no backdrop and "click outside to close" would silently stop working.
- **GOTCHA**:
  - Modal focuses the first focusable element in the panel on open, which is its close button. That's acceptable
    (APG); keep the close button first in the DOM.
  - Don't add a second focus trap; Modal's handles Tab.
  - Don't re-implement the dialog.
  - No `setInterval`/`setTimeout` slideshow.
- **VALIDATE**: `npm run build`.

### Task 10: `GalleryGrid.vue` opens the viewer
- **UPDATE**:
  - each `<figure>` thumbnail becomes `<button type="button" @click="open(i)" :aria-label="…">`, wrapping the
    `<img loading="lazy" decoding="async" :alt>`;
  - the aria-label is `item.title ? \`Open photo: ${item.title}\` : \`Open photo ${i + 1} of ${items.length}\``;
  - mount one `<PhotoViewer v-model="viewerOpen" v-model:index="viewerIndex" :photos>`, with `photos` mapped from
    `items` (`src: item.image_url, alt: item.title || '', caption: item.title || item.description`);
  - section and grid classes per the Task 8 contract, with literal strings only;
  - `shouldRender` and the props are unchanged, so Region, `/gallery` and the CMS block keep working.
- **GOTCHA**: `GalleryGrid` is used by `Region.vue` (preloaded), `Gallery.vue` (keyed tabs) and `GalleryBlock.vue`.
  Don't change its props contract.
- **VALIDATE**: `npm run build`; `php artisan test --filter='GalleryOwnership|ApiVisibility|RegionAccess'`.

### Task 11: `Department.vue`: announcement photo and gallery section
- **UPDATE**:
  - **Announcement card** per the contract:
    `<button type="button" v-if="a.image_path" @click="openAnnouncementPhoto(a)"><img :src="imageUrl(a.image_path)" :alt="a.image_alt || ''" loading="lazy" decoding="async" class="… object-contain …"></button>`.
    Whole image, never `object-cover`. The `<img>` alt gives the button its accessible name, so it reads as the
    description. Add a visible or sr-only "Enlarge" cue per the contract. It opens a `PhotoViewer` with that single
    photo, so prev/next are hidden.
  - **Gallery** keeps `<GalleryGrid :department="department.slug" … />`, with the heading and section classes from
    the contract.
  - Everything else on the page is unchanged.
- **VALIDATE**: `npm run build`.

### Task 12: Frontend contract tests
- **CREATE** `tests/Feature/DepartmentPhotosFrontendTest.php`, reading the source like `upcaHomeSource()`:
  - `Department.vue` binds `a.image_alt` to the announcement `<img>` and uses `loading="lazy"`. Within the
    announcements section only, there is no `object-cover`. Extract the substring from the `Announcements</h2>`
    heading (or the contract's section id) to the next `</section>`, because the hero legitimately uses `bg-cover`;
  - `GalleryGrid.vue` thumbnails are `<button type="button"`, it imports `PhotoViewer`, and it has
    `decoding="async"`;
  - `PhotoViewer.vue` has:
    - `role="status"` and `aria-live="polite"`;
    - `ArrowLeft` and `ArrowRight`;
    - `prefers-reduced-motion`;
    - no `setInterval`;
    - no `loading="lazy"` on the main image;
    - `Modal` imported (no second dialog implementation);
  - `Modal.vue` defines `.modal-panel--photo`;
  - the "no `${` in class attributes" dataset for the three files.
- **VALIDATE**: `php artisan test --filter=DepartmentPhotosFrontend`.

### Task 13: Full validation
- **VALIDATE**:
  - `php artisan test` passes: the baseline 328 plus the new tests, 0 failed;
  - `vendor/bin/pint --test` on the changed PHP;
  - `npm run build`;
  - every new arbitrary class is present in the built CSS (the Python check used for the homepage).

### Task 14: Browser validation on a DB copy
- **SETUP**:
  - `sqlite3 upci ".backup '<scratch>/upci-photos.sqlite'"`;
  - `DB_DATABASE=<copy> php artisan migrate --force`;
  - create a national test admin in the copy only;
  - serve from `public/` with raised CLI limits:
    `cd public && DB_DATABASE=<copy> APP_URL=http://127.0.0.1:8127 CACHE_STORE=file SESSION_DRIVER=file MAIL_MAILER=log php -d upload_max_filesize=20M -d post_max_size=25M -S 127.0.0.1:8127 ../vendor/laravel/framework/src/Illuminate/Foundation/resources/server.php`;
  - record a marker file before starting.
- **Playwright** (scratchpad, headless shell 1243):
  - add an announcement with a portrait flyer and a description;
  - bulk-upload about 8 photos to Men's;
  - check `/departments/mens` at 1440 and 390: photo whole, gallery visible, no sideways scroll;
  - open the viewer: counter text, ArrowRight/ArrowLeft, Esc, focus back on the same thumbnail, Tab stays inside;
  - check that a regional test admin on their region sees "Upload photos", and can't open a department edit page;
  - check `/regions/{slug}` and `/gallery` still render.
- **CLEANUP**: stop the servers by PID. `find storage/app/public -newer <marker> -type f` lists the test uploads;
  **move** them to `/var/www/personal/upci.co.nz-backups/qa-artifacts-<date>/` with a README. Never delete them.

### Task 15: Live verification
- **ACTION**: the migration already ran in Task 2a. Run `npm run build` (final), then check:
  - `GET https://upci.b8.co.nz/api/departments/mens` returns `image_path: null` and `image_alt: null` on each existing
    announcement;
  - `/departments/mens` renders unchanged for the photo-less announcements;
  - the gallery stays hidden while it's empty;
  - a Playwright render of the staging URL at 1440 and 390 shows no failed requests.

### Task 16: Report, commit, archive
- **ACTION**:
  - write `.claude/PRPs/reports/announcement-photo-and-department-gallery-report.md`:
    - deviations, validation, and the backup path;
    - owner actions: re-post the three Facebook-linked announcements with photos; load event photos with
      "Upload photos"; the APP_URL fix for admin previews; file housekeeping as a future job;
  - focused commits when the owner asks: backend, admin, frontend, and the design/plan archive;
  - move the plan to `plans/completed/`.

## Testing Strategy

| File | Covers |
|---|---|
| `tests/Feature/PhotoUploadRulesTest.php` | The `PhotoUpload` getters (disk, visibility, types without SVG, 10 MB, contain, 2000/2000, directory). It's used in all three places, and no raw photo `FileUpload` remains. |
| `tests/Feature/AnnouncementPhotoTest.php` | Columns exist and are nullable. API keys present and null when there's no photo. Create with a photo stores it under `announcements/`. Photo without a description fails `image_alt`. SVG fails. A no-photo announcement still saves. Thumbnail column. |
| `tests/Feature/GalleryBulkUploadTest.php` | N files make N owned rows. `sort_order` continues. Caption applied or null. Unpublished rows are hidden from the API. 31 files and an SVG are refused, with no partial rows. Hidden on the View page. Regional user on own region works. Single create unchanged. |
| `tests/Feature/DepartmentPhotosFrontendTest.php` | Vue source contracts (alt binding, never cropping the announcement photo, buttons, live region, arrows, reduced motion, no autoplay, Modal reuse, literal classes) |
| Existing | `AnnouncementTest`, `GalleryOwnershipTest`, `ApiVisibilityTest`, `RegionAccessTest`, `AdminTablesTest`, `SecurityRegressionTest`, `AdminDisplayTimezoneTest`, `HomepageUpcaStyleTest` stay green |

### Edge cases
- [ ] Announcement without a photo renders exactly as today, and its API keys are null.
- [ ] A portrait A4 flyer shows whole on the card and in the viewer.
- [ ] The photo is removed in admin: `image_path` becomes null and the card has no photo. The file stays on disk (by
  design).
- [ ] A photo with an empty description is refused with the plain message. A description with no photo saves but
  the API returns `image_alt: null`.
- [ ] Mid-implementation on staging: no announcement save fails, because Task 2a ran first. Partial UI from an
  interim `npm run build` is acceptable on staging.
- [ ] The viewer closes on a click outside the photo (the backdrop), on its close button, and on Esc.
- [ ] Bulk upload: 0 files gives a required error; 31 files gives a max error; an SVG in the batch fails without
  partial rows.
- [ ] Bulk upload with Published off: rows exist but are hidden publicly.
- [ ] A one-photo gallery hides the viewer's prev/next. Navigation wraps at the ends.
- [ ] Keyboard: Enter or Space on a thumbnail opens the viewer; arrows move; Esc closes; focus returns to that
  thumbnail; Tab stays inside.
- [ ] Touch: swipe left and right; vertical scroll inside the dialog isn't hijacked.
- [ ] `prefers-reduced-motion`: no transitions.
- [ ] 390 px: no horizontal page scroll; the viewer's controls are reachable and at least 44 px.
- [ ] Phone photos of 8 to 10 MB upload, resized under 2000 px. An original over 10 MB gets FilePond's size message.
- [ ] A regional user can't reach a department's gallery or announcements.
- [ ] The region page, `/gallery` and CMS gallery blocks still render, now with the viewer.

## Validation Commands

### Level 1: STATIC_ANALYSIS
```bash
vendor/bin/pint --test $(git diff --name-only -- '*.php') $(git ls-files --others --exclude-standard -- '*.php')
```
### Level 2: TARGETED
```bash
php artisan test --filter='PhotoUploadRules|AnnouncementPhoto|GalleryBulkUpload|DepartmentPhotosFrontend|AnnouncementTest|GalleryOwnership|ApiVisibility|RegionAccess|AdminTables'
```
### Level 3: FULL + BUILD
```bash
php artisan test && npm run build
```
**EXPECT**: 0 failed; the build succeeds; new arbitrary classes are present in `public/build/assets/app-*.css`.

### Level 4: DATABASE (after Task 2a, with the owner's OK)
```bash
sqlite3 -readonly upci ".schema department_announcements"   # image_path, image_alt present
php artisan migrate:status | tail -2                           # 2026_10_15_000001 Ran
```
### Level 5: BROWSER
See Task 14: Playwright 1.64 in the scratchpad, `executablePath` set to chrome-headless-shell 1243.

### Level 6: MANUAL (owner)
1. In the admin, open Departments › Men's › Announcements, then New. Attach a flyer and describe it, then save. The
   department page shows the flyer whole; clicking it opens it large.
2. Open the Gallery tab, then Upload photos. Drop in a batch and add a caption. The department page now shows a
   gallery; click a photo and use the arrows.

## Acceptance Criteria
- [ ] Owner decisions honoured: improve the gallery, one photo per announcement, national-only access.
- [ ] Announcement photo and description are saved, served by the API, and rendered whole and clickable.
- [ ] "Upload photos" creates one owned gallery item per file, in order, authorised like Create.
- [ ] One `PhotoUpload` definition everywhere a photo is uploaded.
- [ ] Viewer: keyboard, swipe, focus return, live counter, reduced motion; no autoplay.
- [ ] Design gate passed and its class contract followed.
- [ ] Level 1 to 3 pass; no regressions; browser checks pass at 1440 and 390.
- [ ] Delete-nothing honoured, including test artefacts; backup taken before the live migration.

## Risks and Mitigations

| Risk | Likelihood | Impact | Mitigation |
|---|---|---|---|
| The bulk action receives temporary files, not stored paths | RESOLVED | — | The plan-review probe (A) showed `$data['photos']` is stored paths. |
| Admin announcement saves break on staging before the live migration | HIGH if the order is wrong | HIGH | Task 2a applies the additive migration first, with the owner's OK. If declined, don't leave Task 4 code on disk. |
| `.modal-panel--photo` loses to `.modal-panel` | MED | MED | Define it in `Modal.vue` beside `--wide`; a contract test checks this. |
| Small photos keep their EXIF (including GPS) | MED | LOW | The resize re-encodes, which strips EXIF, only for images over 2000 px; most phone photos are larger. Server-side EXIF stripping is out of scope; noted in the report. |
| FilePond rejects big phone photos before resizing | MED | MED | `maxSize` 10 MB on the original, and the resize shrinks what is sent. Test an 8 MB JPEG in Task 14. |
| A regional user gets bulk upload where they shouldn't | LOW | MED | `authorize()` reuses `canCreate()` and `isReadOnly()`. The policy restricts regional users to their own region. Tests 7 and 8. |
| Viewer accessibility regressions (focus, Safari backdrop) | MED | MED | Reuse `Modal.vue`, which already handles focus return and an `event.target` backdrop. Contract tests and the browser keyboard run. |
| Orphan files pile up | MED | LOW | Out of scope by the delete-nothing rule; noted in the report as housekeeping. |
| Admin previews look broken on staging | KNOWN | LOW | The `APP_URL` mismatch (memory). It's an owner action in the report; uploads still work. |
| Browser-test uploads land in real storage | KNOWN | LOW | A marker file plus `find -newer`, then move them to backups (Task 14). |

## Confidence: 9.5/10

- Every backend piece has a direct precedent: the events artwork migration, the shared relation manager, and policy
  reuse. The exact Task 4 and Task 6 admin code was probe-run against Filament 4.15.1 (7/7: paths, ordering, limits,
  SVG, read-only hiding, regional scope, `requiredWith`). The gallery already exists, so the data work is small.
- The plan review fixed the ordering hazard (staging runs the working tree, so the live migration comes first),
  the Modal CSS cascade, the backdrop-close trap, the admin message wording, and a stray-description leak.
- The remaining uncertainty is in the UI: the design gate's outcome and the viewer's touch and Safari behaviour.
  Both are contained by reusing `Modal.vue` and by browser validation at 1440 and 390.

## Notes

- Why one shared `PhotoUpload`: four nearly identical `FileUpload` blocks exist today, with sizes of 4096, 5120 and
  5120. They have already drifted. Giving the announcement photo, the single gallery item and the bulk upload
  different rules would make the same phone photo succeed in one place and fail in another.
- The viewer lives in the shared `GalleryGrid`, so regions and the general gallery gain it at no extra cost. This
  follows "Requirement 2", which says not to build the gallery three times.
- `published_at` on announcements stays a UTC picker. The admin's NZ display hook (`9022d6c`) is display-only by
  design, so this feature doesn't change scheduling behaviour.
