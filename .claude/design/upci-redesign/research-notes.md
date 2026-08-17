# Verified external research — pinned to this project's exact versions

Versions confirmed on disk: `filament/filament v4.0.0`, Laravel 12.23.1, PHP 8.4.23, Vue 3.5.22, Leaflet 1.9.4, Tailwind 3.4, Pest 3.8, Node 22.

---

## 1. ⚠️ CORRECTION — `Filament\Pages\SettingsPage` is NOT in core v4

This overturns the obvious approach for the "main logo editable via CMS / SiteSettings" requirement.

`SettingsPage` ships in the **separate** `filament/spatie-laravel-settings-plugin` package, which pulls in `spatie/laravel-settings`. It is **not** part of `filament/filament`. Evidence: `filamentphp.com/api/4.x/Filament/Pages/SettingsPage.html` 404s; only a 3.x plugin API page exists.

This project has **no** spatie/laravel-settings (confirmed: `composer.lock` contains `spatie/invade`, `laravel-package-tools`, `laravel-ray`, `once`, `pest-plugin-*`, `ray`, `shiki-php` — no settings package) and **no** settings table (confirmed via `sqlite3 upci .tables`).

**Therefore:** either add the plugin dependency, or build a plain custom page. Recommended — plain custom page, no new dependency:

```php
// app/Filament/Pages/ManageSiteSettings.php  (auto-discovered: AdminPanelProvider already
// calls discoverPages(in: app_path('Filament/Pages')) — note that directory does NOT exist yet)
class ManageSiteSettings extends \Filament\Pages\Page implements HasForms
{
    use \Filament\Forms\Concerns\InteractsWithForms;

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill(SiteSetting::first()?->toArray() ?? []);
    }

    public function form(Schema $schema): Schema   // ← Schema, NOT Form
    {
        return $schema->components([ /* ... */ ])->statePath('data');
    }

    public function save(): void
    {
        SiteSetting::updateOrCreate(['id' => 1], $this->form->getState());
    }
}
```
Blade: `<form wire:submit="save">{{ $this->form }}</form>`.
Gate it with `PagePolicy`-style national-only access — mirror `app/Policies/NationalOnlyPolicy.php`.

Docs: https://filamentphp.com/docs/4.x/navigation/custom-pages

## 2. Filament v4 vs v3 — the traps

Upgrade guide: https://filamentphp.com/docs/4.x/upgrade-guide

- `Filament\Forms\Form` → **`Filament\Schemas\Schema`**; `->schema([...])` → **`->components([...])`**. Any v3-era blog using `function form(Form $form): Form` will fatal-error. (This repo is already correct — see `ChurchForm.php`.)
- Layout components (`Section`, `Grid`, `Tabs`, `Fieldset`, `Wizard`) live in `Filament\Schemas\Components\*`.
- 🔴 **`Grid`/`Section`/`Fieldset` no longer span all columns by default in v4** — needs explicit `->columnSpanFull()`. Silently breaks v3-era layouts.
- Infolist entries → `Filament\Schemas\Components\Infolists`.
- Actions consolidated into one `Filament\Actions\Action` across tables/forms/infolists/pages.
- `table()` is still typed `Filament\Tables\Table` — **tables did NOT move** to the Schema namespace. Only forms/infolists did.
- `getPages()` unchanged and still required.

## 3. FileUpload — confirms and sharpens the P0 bug

Docs: https://filamentphp.com/docs/4.x/forms/file-upload

🔴 **Default visibility is decided by a literal string match on the disk name.** Only a disk literally named `public` defaults to public visibility; in v4 every other disk name defaults to `private` (a behaviour change from v3).

This is exactly why `DepartmentForm.php:45` (`hero_image`) and `GalleryItemForm.php:21` (`image_path`) put their files in `storage/app/private/` — with `FILESYSTEM_DISK=local` and no `->disk('public')`, v4 writes private. Fix:

```php
FileUpload::make('image_path')
    ->image()
    ->disk('public')          // required
    ->visibility('public')    // explicit is safer
    ->directory('gallery')
```
Plus a data migration to move the two orphaned files already sitting in `storage/app/private/{department-images,gallery}/`.

## 4. RelationManager over Repeater for galleries

Docs: https://filamentphp.com/docs/4.x/resources/managing-relationships#creating-a-relation-manager

Filament's own docs say Repeaters are "only suitable if your related model only has a few fields". For gallery items (image + title + description + order) a **RelationManager is the idiomatic v4 choice**. This repo already has the pattern working — `Departments/RelationManagers/AnnouncementsRelationManager.php` — so a `GalleryItemsRelationManager` mirrors existing code exactly.

Scaffold: `php artisan make:filament-relation-manager DepartmentResource galleryItems title`

## 5. SelectFilter by relationship (for the church-locator region filter)

Docs: https://filamentphp.com/docs/4.x/tables/filters/select#relationship-select-filters
```php
SelectFilter::make('organizationalRegion')
    ->relationship('organizationalRegion', 'name')
    ->searchable()->preload()
```
Records with a null relationship are **excluded by default** — pass `hasEmptyOption: true` + `->emptyRelationshipOptionLabel('None')` if unassigned churches must show.

## 6. Polymorphic gallery (Laravel 12)

Docs: https://laravel.com/docs/12.x/eloquent-relationships#one-to-many-polymorphic-relations

For one reusable `gallery_items` table serving Department + Region + general: `$table->morphs('galleryable')` → `galleryable_id` / `galleryable_type`; `MorphTo` on GalleryItem, `MorphMany` on Department and Region.

🔴 **Call `Relation::enforceMorphMap([...])` in `AppServiceProvider::boot()`** (not plain `morphMap`) with short aliases `'department' => Department::class`, `'region' => Region::class`. `enforceMorphMap` hard-fails with `ClassMorphViolationException` on anything unmapped, keeps FQCNs out of the DB, and survives namespace refactors.

Migration is cheap here: `gallery_items` currently holds **exactly one row**.

## 7. Leaflet — locking the map to New Zealand

Reference: https://leafletjs.com/reference.html (`#map-maxbounds`, `#map-maxboundsviscosity`)

```js
const NZ = L.latLngBounds([-47.35, 166.3], [-34.1, 178.6]); // mainland, excl. Chathams
map = L.map(el, {
  center: [-40.9006, 174.8860],
  zoom: 6,
  minZoom: 5,
  maxBounds: NZ,
  maxBoundsViscosity: 1.0,   // 0 = free drag, 1.0 = hard wall
  worldCopyJump: false,
});
```

- **Chathams are deliberately excluded**: at ~176.5°W they cross the antimeridian, which a single `LatLngBounds` rectangle cannot express without projection tricks. Markers there can still render unconstrained; only the *bounds* exclude them.
- 🔴 **Set `maxBounds`/`minZoom` at construction, before any `fitBounds()`** — otherwise the initial `fitBounds` fights the bounce-back and produces jittery positioning.
- 🔴 Directly relevant to `ChurchLocator.vue`: `updateMarkers()` currently calls `map.fitBounds(group.getBounds().pad(0.1))` on **every** filter change. With `maxBounds` added this will fight the bounds and over-zoom on single-result filters. Cap it: `fitBounds(bounds, { maxZoom: 12, padding: [40,40] })`.

## 8. Accessible modal — no new dependency

MDN: https://developer.mozilla.org/en-US/docs/Web/API/HTMLDialogElement/showModal

Native `<dialog>` + `.showModal()` gives top-layer rendering, `::backdrop`, **automatic background inertness**, native Escape-to-close, and correct implicit modal semantics — no manual `aria-modal`/inert wiring. Baseline widely-available since ~March 2022 (Chrome 37+, Edge 79+, Firefox 98+, Safari 15.4+); no polyfill needed.

🔴 `<dialog>` does **not** provide a focus trap — add a small keydown handler cycling focusable elements. Give it `aria-labelledby` pointing at an internal heading.

Pattern for this stack: `<Teleport to="body">` wrapping `<dialog ref>`, call `showModal()`/`close()` from a watcher on an `open` prop; capture `document.activeElement` before opening and restore it in the native `close` handler. **Skip body-scroll-lock hacks** — `showModal()` already handles it and old CSS tricks conflict with dialog layering.

Note: `ChurchLocator.vue` already has a hand-rolled modal with `role="dialog" aria-modal="true"`. Converting it to native `<dialog>` and extracting it as a shared component serves both the leadership-modal requirement and the existing locator.

## 9. Tailwind — portrait leadership images + the dynamic-class trap

`aspect-[3/4] object-cover object-top` is standard and JIT-detected **only when written verbatim** in a scanned file.

🔴 Directly relevant: this project builds class strings in JS helpers (`resources/js/utils/eventStatus.js`). Tailwind's scanner cannot see `` `aspect-[${ratio}]` ``. `eventStatus.js` already does the right thing — it returns **complete literal strings** and says so in a comment ("Keep strings literal so Tailwind's JIT scanner picks them up"). **Follow that existing pattern** — a lookup map of full class strings — rather than adding regex `safelist` entries, which are a v3 JIT feature not guaranteed under Tailwind v4's Oxide engine.
