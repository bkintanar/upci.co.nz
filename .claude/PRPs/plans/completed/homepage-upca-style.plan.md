# Feature: An upca.org.au-style homepage for UPCI NZ

## Summary

The homepage at `/` is the D2 "search-led" CMS page (approved 2026-08-17). It has no photography, a plain grid of
congregation cards, a centred paragraph, a list of events, and department logos at 80px inside white cards. The
owner finds it bare and wants it to feel like https://www.upca.org.au/, the Australian sister body, while keeping the
UPCI NZ brand.

This plan rebuilds it around upca's information architecture:

1. a department-logo strip above the navbar, on every page;
2. a full-width theme banner (the existing "Pentecost Everyday – Going Beyond 2026" image);
3. a band promoting the next event;
4. About, as text beside a large photo;
5. Find a church, mid-page;
6. the congregations, compact;
7. upcoming events as photo cards in a scroll row;
8. the ministries as colour tiles;
9. Connect;
10. a richer footer.

How the work is done:
- **CMS-driven.** The existing blocks gain optional `layout` settings, and there is one new block (`feature`).
- **Today's look by default.** A missing setting means today's rendering, so no other page changes.
- **Content switch.** A guarded content migration switches the homepage and archives the current one, unpublished.
- **Design gate first.** UI work goes through the huashu-design three-direction gate, and the chosen direction is
  turned into an exact class map before any Vue is written.

## User Story

As a **visitor to the UPCI New Zealand website**
I want **a homepage that looks like a living national church, with our theme, our people, our events and our ministries**
So that **it feels like the same family as UPCI Australia and invites me in, instead of reading as a bare directory**

## Problem Statement

Measured 2026-10-14 from screenshots of both sites at 1440 and 390, the live DB, and the code.

1. **No imagery.** The homepage renders zero photographs. upca.org.au leads with a banner, a worship photo and photo
   event cards.
2. **Departments are buried.** Their logos are 80px (`h-20`) square marks inside white cards
   (`DepartmentListBlock.vue:19-27`). upca shows every department's logo in a strip at the top of every page.
3. **Flat, repeated sections.** The page is white cards throughout. "Who we are" has no image and no link. Events are
   a list (`EventsFeedBlock.vue:12-28`) that ignores the `image_path` the admin already supports (`EventForm.php`).
4. **Broken copy.** The live "Connect with us" `cta` stores its sentence under `content`, but the renderer reads
   `data.text` (`CmsPage.vue:154`; `PageForm.php` declares `text`). It has never rendered. Only `home` has this
   (checked: `select … where cta has content` returns `home` alone).
5. **Thin footer.** There is no ministers' log-in and no postal address (`site_settings` has no column for one), and
   `contact_email` / `footer_blurb` are empty.

## Solution Statement

| upca element | Mechanism | Change |
|---|---|---|
| Department-logo strip, every page | `DepartmentStrip.vue` above `<Navbar />` in `App.vue`. It reads `/api/departments` and uses `logo_light_path`; all 6 departments have a white 4000×2000 SVG (`department-logos/UPCINZ-*-03-WHITE-TRANSPARENT.svg`). | NEW component |
| Theme banner | `hero.layout = 'banner'`: the image is shown whole at its own ratio with no overlay. The h1 is real text: `sr-only` at md and up, and visible under the banner on phones. Plus `hero.image_alt`. | `hero` UPDATE |
| "Register for…" band | `cta.style = 'band'`: a slim band with the text left and the button right | `cta` UPDATE |
| About + photo | new `feature` block: text, button, image, alt, side, ground. With no image it shows a designed fallback, never stock. | NEW block |
| Locator panel | `church_finder.layout = 'panel'` + `description` + region shortcuts from `/api/regions` | `church_finder` UPDATE |
| Congregations without a card wall | `church_directory.compact = true`: name and town rows per region (all ten still named) | `church_directory` UPDATE |
| Event photo cards | `events_feed.layout = 'cards'` + `more_text` / `more_url`. A scroll-snap row (no autoplay); image, or a date-led tile. | `events_feed` UPDATE |
| Departments with presence | `department_list.layout = 'tiles'`: hue panels (reusing `departmentHeroClasses()`) with white logos | `department_list` UPDATE |
| Footer duties | ministers' log-in (`/admin`), contact email, new `postal_address` setting | Footer, `site_settings` UPDATE |

**Default rule (keeps every other page identical):** each renderer treats a missing key as today's value:
- `hero.layout` → `overlay`
- `events_feed.layout` → `list`
- `church_finder.layout` → `band`
- `department_list.layout` → `cards`
- `church_directory.compact` → `false`
- `cta.style` → its current values

16 pages carry `hero` blocks and 11 carry `cta` blocks. None are rewritten.

## Metadata

| Field | Value |
|---|---|
| Type | ENHANCEMENT (homepage redesign + site chrome) |
| Complexity | HIGH in scope; LOW in technical risk per task. The patterns all exist, and every unknown below was verified on 2026-10-14. |
| Branch | `feature/homepage-upca-style`, from `main` @ `11c82aa` (the ABC enrolment and site-audit work is merged; Filament is upgraded) |
| Stack (installed) | laravel/framework **12.70.0**, filament/filament **4.15.1**, livewire/livewire **3.8.10**, vue 3.5.22, vue-router 4.5.1, tailwindcss 3.4, pestphp/pest 3.8.2. **No new packages.** |
| Baseline | `php artisan test`: **279 passed, 25 skipped, 0 failed**; `npm run build` passes |
| Estimated Tasks | 17 |

## Owner decisions (2026-10-14)

| Question | Decision |
|---|---|
| What opens the homepage | **The Pentecost Everyday banner** (`page-images/01KPYX2XHW5DRFENE0NJ918RC6.jpg`, 2128×788, text baked in), with a band underneath promoting the next big event. Changeable in the admin. |
| Photography | **The owner will upload photos.** Photo slots (About photo, event images) are filled in the admin. Until filled they show **designed fallbacks, never stock photos**. |
| Department-logo strip | **Yes, above the navbar, on every page** |
| Find a church | **Mid-page, like upca**: Hero → About → Find a church → Events → Departments |
| Branching | Merge the enrolment-form branch to `main` (done), and branch from `main` (done) |
| Filament | Upgrade to the latest 4.x before this work (done: 4.15.1, commit `11c82aa`) |

## Standing constraints (memory and design records)

- **huashu-design gate:** three real HTML directions, then STOP for the owner's pick (Task 3).
- **upca.org.au is the basis, not a template** (`.claude/design/upci-redesign/spec.md` §0, `direction-approved.md`).
  - Take its IA, the logo strip and departments-as-first-class.
  - Don't copy its orange/black palette, Poppins, Wix widths or card styling.
  - Success test: "two organisations in the same family".
- **Brand:**
  - green `oklch(0.47 0.09 143)` primary, clay `oklch(0.62 0.12 55)` accent;
  - ink and paper neutrals; Figtree (300–800 loaded);
  - `brand-error` only for errors;
  - department hues come from the existing `departmentHeroClasses()` (`resources/js/utils/theme.js:31-48`; literal `from-brand-dept-*-700 via-brand-dept-*-900 to-brand-ink`).
- **Forbidden** (`brand-spec.md` §4):
  - purple or blue tech gradients, emoji icons;
  - rounded cards with a left colour border;
  - SVG-drawn people;
  - invented statistics;
  - stock photography.
- **Delete nothing; back up first.** The D2 homepage is archived unpublished.
- **Quality floor:** body text ≥ 14px (the site uses 17px); contrast ≥ 4.5:1; 1440×900 and 390px; no horizontal scroll;
  visible focus.

---

## UX Design

### Before State
```
╔══════════════════════════════════════════════════════════════════════════════╗
║  /  (CmsPage, slug 'home', 6 blocks)                                         ║
║  [Navbar slate-800, sticky]                                                  ║
║  ┌─ green-100 band: Find a church near you [ search ][Search] ────────────┐  ║
║  Our congregations — Northern (4) / Central (2) / Southern (4): 10 cards     ║
║  Who we are (centred paragraph)                                              ║
║  What's coming up [row][row][row][row]   (no images)                         ║
║  Our ministries   [80px logo card] ×6                                        ║
║  ███ Connect with us [Get in touch]   (its sentence never renders)           ║
║  [Footer slate-900: logo · blurb · facebook | links | "New Zealand"]          ║
║  DATA: /api/pages/home → /api/churches, /api/events, /api/departments        ║
╚══════════════════════════════════════════════════════════════════════════════╝
```

### After State
```
╔══════════════════════════════════════════════════════════════════════════════╗
║  [Dept strip: ink ground, 6 white logos → /departments/{slug}]   every page  ║
║  [Navbar, sticky top:0 — the strip scrolls away above it]                    ║
║  ┌──────────── PENTECOST EVERYDAY banner, whole, 2128×788 ─────────────┐    ║
║  │ h1 "Pentecost Everyday": sr-only ≥md, visible text under it <md     │    ║
║  └─────────────────────────────────────────────────────────────────────┘    ║
║  ▓ band: Annual General Conference · 24–25 October · Wellington [See the     ║
║          calendar]                                                           ║
║  About the UPCI NZ — text · [Read more → /about]      [photo | fallback]      ║
║  Find a church near you [search][Search]   Northern · Central · Southern →   ║
║  Our congregations — compact rows, all ten named                             ║
║  Coming up                               [More events →]                     ║
║  ⟨[img|date tile][…][…][…]⟩  scroll-snap row, keyboard-scrollable, no autoplay║
║  Our ministries  [hue tile + white logo] ×6                                  ║
║  ███ Connect with us — sentence now renders [Get in touch]                   ║
║  [Footer: logo·blurb·socials | links | Ministers' log-in · email · address]  ║
║  DATA: + /api/regions (finder), image_path on /api/events, postal_address    ║
║        on /api/site-settings                                                 ║
╚══════════════════════════════════════════════════════════════════════════════╝
```

### Interaction Changes
| Location | Before | After | User Impact |
|---|---|---|---|
| Every page, top | Navbar only | Department strip above a sticky navbar | Any department is one click away |
| `/` first screen | Search band | Theme banner + next-event band | The theme and what's next are visible first |
| `/` about | Paragraph | Text + photo + "Read more" (`/about`, published, id 22) | A route into About |
| `/` events | Text list | Photo/date cards + "More events" (`/events`) | Event flyers uploaded in the admin appear |
| `/` ministries | 80px logos | Hue tiles, large white logos | Departments read as first-class |
| Footer | No log-in or address | Ministers' log-in, email, address | Matches upca's footer duties |

---

## Mandatory Reading

| Priority | File | Lines | Why |
|---|---|---|---|
| P0 | `resources/js/views/CmsPage.vue` | 60-240 (block loop, hero 69-112, cta 151-159), 320-345 (`isHome`, `hasHero`), 400-530 (`getImageUrl`, `getHeroClasses`, `getCtaClasses`, `sectionBackground`) | Where the hero banner, cta band and `feature` registration go |
| P0 | `app/Filament/Resources/Pages/Schemas/PageForm.php` | 79-127 hero, 238-268 cta, 403-482 finder/directory/events/departments | Every new key is declared here. Filament rebuilds block state from the schema on save. |
| P0 | `database/migrations/2026_08_18_100000_author_the_search_led_homepage.php` | all | **The guarded content-migration pattern to mirror** |
| P0 | `resources/js/components/blocks/{EventsFeedBlock,DepartmentListBlock,ChurchFinderBlock,ChurchDirectoryBlock}.vue` | all | Blocks gaining layouts; `useBlockData` + `BlockState` pattern |
| P0 | `resources/js/utils/theme.js` | 1-60 | `departmentHeroClasses(color_theme)`, literal hue gradients to reuse for tiles and date tiles |
| P1 | `resources/js/App.vue`, `components/Navbar.vue` (root `<nav class="… sticky top-0 z-50">`), `components/Footer.vue`, `composables/useSiteSettings.js` | all | Strip mount point; footer columns and settings |
| P1 | `app/Filament/Pages/ManageSiteSettings.php`, `app/Http/Controllers/Api/SiteSettingController.php`, `app/Models/SiteSetting.php` | all | `postal_address` end to end |
| P1 | `tests/Feature/CmsBlockSchemaTest.php` | 157-193 | Required presentation keys; add `feature` |
| P1 | `tests/Feature/AbcEnrolmentPageLinkTest.php`, `tests/Feature/ChurchFormBooleanStateTest.php` | all | Migration-test and Livewire-test patterns |
| P2 | `.claude/design/upci-redesign/{brand-spec,spec,homepage-spec,direction-approved}.md` | all | Constraints and the earlier upca analysis |
| P2 | `database/seeders/HomePageSeeder.php` | all | Manual fresh-install seeder (not called by `DatabaseSeeder`) |

**External documentation**
| Source | Why |
|---|---|
| [WCAG 2.2 Understanding 1.4.5 Images of Text](https://www.w3.org/WAI/WCAG22/Understanding/images-of-text.html) | The banner's words must also exist as real text: the h1/subheading |
| [W3C images: textual](https://www.w3.org/WAI/tutorials/images/textual/), [functional (logo links)](https://www.w3.org/WAI/tutorials/images/functional/) | Banner alt; strip-logo alt is the department name |
| [W3C carousels: animations](https://www.w3.org/WAI/tutorials/carousels/animations/), [functionality](https://www.w3.org/WAI/tutorials/carousels/functionality/) | No autoplay; keyboard operable |
| [MDN scroll-snap-type](https://developer.mozilla.org/en-US/docs/Web/CSS/scroll-snap-type) | `proximity`, not `mandatory` (mandatory can trap) |
| [web.dev Optimize LCP](https://web.dev/articles/optimize-lcp) | `fetchpriority="high"` on the banner; never lazy-load it |
| [MDN img](https://developer.mozilla.org/en-US/docs/Web/HTML/Reference/Elements/img) | width/height prevent CLS; `loading`, `decoding` |
| [Tailwind v3 content configuration](https://v3.tailwindcss.com/docs/content-configuration) | Classes must be complete literal strings |
| [Tailwind v3 aspect-ratio](https://v3.tailwindcss.com/docs/aspect-ratio), [scroll-snap](https://v3.tailwindcss.com/docs/scroll-snap-type) | `aspect-[4/3]`, `snap-x snap-proximity`, `snap-start` |
| [Filament 4 Builder](https://filamentphp.com/docs/4.x/forms/builder), [FileUpload](https://filamentphp.com/docs/4.x/forms/file-upload) | Block schema; `->disk('public')->visibility('public')`, `->imageEditor()` |
| [MDN position: sticky](https://developer.mozilla.org/en-US/docs/Web/CSS/position) | Strip in normal flow above the sticky nav |

---

## Verified facts this plan relies on (2026-10-14)

| Fact | Evidence |
|---|---|
| Live `home` block types are exactly `church_finder, church_directory, text, events_feed, department_list, cta` | live DB |
| The "Who we are" text is: `The United Pentecostal Church International of New Zealand is a fellowship of apostolic congregations across the country, organised into the Northern, Central and Southern regions.\n\nWherever you are, you are welcome.` | live DB |
| The banner `01KPYX2XHW5DRFENE0NJ918RC6.jpg` (2128×788) exists on the public disk and is not used by any page | `storage/app/public/page-images`; content `LIKE` search |
| All 6 departments have `logo_light_path` (white, viewBox 4000×2000) and a `color_theme` in {green, pink, purple, yellow, indigo, blue} | live DB; `/api/departments` returns both |
| `/api/regions` returns `slug, name, logo_url, presbyter_name, churches_count`, published only, `sort_order` | `RegionController.php:31-50` |
| `/api/events` returns `image_path` (bare disk path), `start_date`/`end_date` as `Y-m-d`, `location`, `url`, `department{slug,name,color_theme}`. It has no limit param. 0 of 49 events have an image; every `url` is empty. | `EventController.php:18-90`; live DB |
| The Annual General Conference is event id 46, 2026-10-24 → 2026-10-25, Wellington | live DB |
| `/about` is published (pages id 22) and `/events` is an SPA route | live DB; `routes.js` |
| Church directory cards are **not links** today (name, city, pastor_name, "Not on the map yet") | `ChurchDirectoryBlock.vue:24-40` |
| `pages.slug` is unique | `2025_10_24_023322_create_pages_table.php:17` |
| `HomePageSeeder` is not called by `DatabaseSeeder` and is used by no test | grep |
| No `overflow` wrapper exists around `<Navbar>`, so sticky still works with the strip above it | `App.vue`, `app.css`, `app.blade.php` |
| Filament 4.15.1 has `->imageEditor()`, `->imageEditorAspectRatioOptions()`, `->visibility()`, `->automaticallyResizeImagesMode()`, `Select::boolean()` | `vendor/filament/forms/src/Components/*` |
| Filament 4.15 form-test helpers find fields by **plain name** (`assertFormFieldIsDisabled('region_id')`) | fixed in `11c82aa` |
| `renderMarkdown` (DOMPurify-sanitised) is `resources/js/utils/markdown.js:76` | file |
| `ManageSiteSettings` form uses `statePath('data')` and has `save()`; access is national-only | file |

**Environment note (not caused by this work):** `.env` has `APP_URL=http://upci.loc`, a hostname only this server
resolves. Filament's FileUpload previews build their URLs from it, so on staging the **admin image previews** point at
`upci.loc`. The public API is unaffected (it uses the request host). When the owner uploads homepage photos, the files
save correctly, but the previews in the admin won't display. **The owner should set
`APP_URL=https://upci.b8.co.nz`** (server config; not part of this code change). It is listed as an owner action in
Task 16.

---

## Patterns to Mirror

**GUARDED CONTENT MIGRATION** (`2026_08_18_100000_author_the_search_led_homepage.php`):
```php
$page = Page::where('slug', 'home')->first();
if (! $page) { return; }
$current = is_string($page->content) ? json_decode($page->content, true) : $page->content;
$current = is_array($current) ? $current : [];
$types = array_map(fn ($b) => $b['type'] ?? '', $current);
if ($types !== [/* exact expected sequence */]) { return; }   // edited in the CMS → the editor wins
Page::updateOrCreate(['slug' => 'home-before-d2'], ['title' => '…', 'content' => $current, 'is_published' => false]);
$page->update(['content' => [ /* new blocks */ ]]);
```

**BLOCK DECLARATION** (`PageForm.php:471-482`):
```php
Builder\Block::make('department_list')->label('Department List')->schema([
    TextInput::make('heading')->label('Heading')->maxLength(255),
    Toggle::make('show_logos')->label('Show logos')->default(true),
    TextInput::make('limit')->label('Maximum shown')->numeric(),
    TextInput::make('empty_message')->label('If there is nothing to show')->default('No departments are published yet.')->maxLength(255),
])->icon('heroicon-o-rectangle-stack'),
```

**PUBLIC-DISK UPLOAD** (`ManageSiteSettings.php:64-71`):
```php
FileUpload::make('header_logo_path')->label('Header logo')->image()
    ->disk('public')->visibility('public')->directory('site')->maxSize(2048),
```

**DATA BLOCK** (`EventsFeedBlock.vue:48-75`):
```js
const { items, loading, error, load } = useBlockData(url)
const visible = computed(() => { const limit = parseInt(props.data.limit, 10); return Number.isFinite(limit) && limit > 0 ? items.value.slice(0, limit) : items.value })
const dayOf = (d) => (d ? d.split('-')[2] : '')   // never through Date: UTC shift in NZ
```

**LITERAL CLASS MAPS** (`utils/theme.js:31-48`):
```js
const DEPARTMENT_THEMES = { blue: 'bg-gradient-to-br from-brand-dept-blue-700 via-brand-dept-blue-900 to-brand-ink', /* … */ }
export function departmentHeroClasses(theme) { return DEPARTMENT_THEMES[theme] || DEPARTMENT_THEMES.blue }
```

**MIGRATION TEST** (`AbcEnrolmentPageLinkTest.php`):
```php
function abcCardMigration(): object { return require database_path('migrations/2026_10_02_000002_….php'); }
```

**FILAMENT LIVEWIRE TEST** (`ChurchFormBooleanStateTest.php`):
```php
beforeEach(fn () => Filament::setCurrentPanel('admin'));
$this->actingAs($national);
Livewire::test(EditChurch::class, ['record' => $church->getRouteKey()])->set('data.name', 'X')->call('save')->assertHasNoFormErrors();
```

---

## Files to Change

| File | Action | Justification |
|---|---|---|
| `.claude/design/homepage-upca/` (spec, assets, 3 demos, shots, `direction-approved.md` with the class contract) | CREATE | Gate (Tasks 2–4) |
| `database/migrations/2026_10_14_000001_add_postal_address_to_site_settings.php` | CREATE | New column |
| `app/Models/SiteSetting.php`, `app/Filament/Pages/ManageSiteSettings.php`, `app/Http/Controllers/Api/SiteSettingController.php` | UPDATE | `postal_address` |
| `app/Filament/Resources/Pages/Schemas/PageForm.php` | UPDATE | New keys + `feature` block |
| `tests/Feature/CmsBlockSchemaTest.php` | UPDATE | `feature` presentation keys |
| `resources/js/components/DepartmentStrip.vue` | CREATE | Strip |
| `resources/js/App.vue` | UPDATE | Mount the strip |
| `resources/js/views/CmsPage.vue` | UPDATE | Hero banner, cta band, `feature` registration |
| `resources/js/components/blocks/FeatureBlock.vue` | CREATE | About + photo |
| `resources/js/components/blocks/{ChurchFinderBlock,ChurchDirectoryBlock,EventsFeedBlock,DepartmentListBlock}.vue` | UPDATE | Layouts |
| `resources/js/components/Footer.vue` | UPDATE | Log-in, email, address, ground |
| `resources/js/components/Navbar.vue` | UPDATE **only if** the contract says so | Ground colour only |
| `database/migrations/2026_10_14_000002_author_the_upca_style_homepage.php` | CREATE | Guarded content switch |
| `database/seeders/HomePageSeeder.php` | UPDATE | Fresh installs get the new homepage |
| `tests/Feature/HomepageUpcaStyleTest.php` | CREATE | Migration, schema, settings, source contracts |

## NOT Building (Scope Limits)

- "Missed a national event" (past events, video). There's no video data; possible later.
- A slider or autoplay carousel. Banned by the W3C carousel guidance and not needed.
- Server-side image variants or srcset. Uploads are served at their own size.
- Preloading the banner in the Blade shell, because the path is CMS-driven. A future LCP win.
- Stock photos, or invented numbers (the finder description names regions, not counts).
- Changing any other page's look. Missing keys mean today's rendering.
- Changing `APP_URL`. It's server config, so it's an owner action.
- Filament 5 / Livewire 4. A separate upgrade.
- Deleting the D2 homepage. It's archived as `home-before-upca-style`.

---

## Step-by-Step Tasks

### Task 0: Backup (owner rule)
- **ACTION**: Back up to `/var/www/personal/upci.co.nz-backups/pre-homepage-upca-<ts>/`:
  - `git bundle create repo.bundle --all`;
  - a tarball without vendor, node_modules and .git;
  - `sqlite3 upci ".backup '<dir>/upci.sqlite'"`;
  - `git diff > uncommitted.patch`;
  - then `git tag pre-homepage-upca-<ts>`.
- **VALIDATE**: `git bundle verify` passes, and `PRAGMA integrity_check` returns `ok`.

### Task 1: Backend: `postal_address` (no design dependency)
- **MIGRATION** `2026_10_14_000001_add_postal_address_to_site_settings.php`:
  ```php
  return new class extends Migration {
      public function up(): void { Schema::table('site_settings', fn (Blueprint $t) => $t->text('postal_address')->nullable()->after('contact_email')); }
      public function down(): void { Schema::table('site_settings', fn (Blueprint $t) => $t->dropColumn('postal_address')); }
  };
  ```
- **MODEL** `SiteSetting`: add `'postal_address'` to `$fillable`.
- **ADMIN** `ManageSiteSettings`, section "Contact & footer":
  `Textarea::make('postal_address')->label('Postal address')->rows(3)->maxLength(500)->helperText('Shown in the site footer. Leave empty to hide.')`
  (import `Filament\Forms\Components\Textarea`). The section keeps `->columns(2)`; give the textarea `->columnSpanFull()`.
- **API** `SiteSettingController::index`: add `'postal_address' => $settings->postal_address`.
- **VALIDATE**: `php -l`; Task 13's settings tests.

### Task 2: Gather the gate's real assets
- **ACTION**: Copy into `.claude/design/homepage-upca/assets/`:
  - the banner;
  - the NZ landmarks collage (`page-images/01KPZ4BSJ8J7JPMHHCTA9RS9SC.jpg`, pale, real);
  - the Men's camp group photo (`department-images/01KPZ4VS5HD6SKYDYM0JD4PVE3.png`);
  - the six `*-03-WHITE-TRANSPARENT.svg` logos;
  - `site/upci-nz-logo-nav.png` and `site/upci-nz-logo-footer.png`.
- **ACTION**: Take reference screenshots of upca.org.au and the current `/` at 1440×900 and 390×844 (Playwright,
  headless shell 1243, as for the ABC work).
- **VALIDATE**: The files exist; sizes are 2128×788, 2128×788, 1915×709.

### Task 3: huashu-design three-direction gate — STOP for the owner's pick
- **ACTION**: Write `.claude/design/homepage-upca/spec.md` (≥500 words): owner decisions, section order, real content
  (from "Verified facts"), brand tokens, forbidden list, sizes, and the two fallback states (no About photo; event
  without image).
- **ACTION**: Run the skill's three logics in parallel, all **inside the upca context**: seconds roulette, benchmark,
  best designer. Each is a single HTML file using the real assets, at 1440 and 390.
- **ACTION**: Each direction must make an explicit, visible choice for **every slot**:
  - **chrome:** strip, navbar ground, footer;
  - **opening:** banner framing, band;
  - **about:** feature (with photo and fallback);
  - **church finding:** finder panel, compact directory;
  - **events and ministries:** event card (photo and date tile), ministry tile.
- **ACTION**: Publish the three as one artifact (like the ABC directions page) and **stop** for the owner's choice.
- **VALIDATE**: Three demos and screenshots exist.

### Task 4: Turn the chosen direction into a class contract (removes all visual ambiguity)
- **ACTION**: Record the owner's words verbatim in `.claude/design/homepage-upca/direction-approved.md`.
- **ACTION**: Add a **Class contract** table to that file. For every slot below it lists the exact **literal Tailwind
  class strings**, transcribed from the chosen demo's CSS using only `brand-*` tokens and standard utilities:

  | Slot | Class keys |
  |---|---|
  | `strip` | wrapper, logo img height |
  | `navbar` | ground; may be "unchanged" |
  | `banner` | wrapper, caption block for mobile |
  | `band` | ground, heading, button |
  | `feature` | section, grid, image box, fallback panel, heading, body, button |
  | `finder.panel` | section, grid, form, region link |
  | `directory.compact` | grid, row |
  | `events.cards` | header, scroller, card, image, date tile, title, meta, more-link |
  | `tiles` | grid, tile, logo, name |
  | `footer` | ground, log-in button, address |

- **RULE**: No class may be built by string interpolation. Department hues come only from `departmentHeroClasses()`.
- **VALIDATE**: Every slot has an entry, and `grep -E '\$\{' direction-approved.md` finds no interpolated class.

### Task 5: Declare every new block key in `PageForm.php`
Defaults reproduce today's look; existing blocks without the key render as today.
- **`hero`** (add to the schema):
  ```php
  Select::make('layout')->label('Layout')->options(['overlay' => 'Text over a darkened image', 'banner' => 'Banner image shown whole'])->default('overlay')->required(),
  TextInput::make('image_alt')->label('Image description (alt text)')->maxLength(255)
      ->helperText('For a banner with words in it, type those words.'),
  ```
- **`cta`**: add `'band' => 'Slim band'` to the existing `style` options.
- **NEW `feature`** block, placed after `two_column`:
  ```php
  Builder\Block::make('feature')->label('Text beside a photo')->icon('heroicon-o-photo')->schema([
      TextInput::make('heading')->label('Heading')->required()->maxLength(255)->columnSpanFull(),
      MarkdownEditor::make('content')->label('Text')->columnSpanFull(),
      TextInput::make('button_text')->label('Button text')->maxLength(50),
      TextInput::make('button_url')->label('Button link')->maxLength(255),
      FileUpload::make('image')->label('Photo')->image()->imageEditor()
          ->disk('public')->visibility('public')->directory('page-images')->maxSize(5120)
          ->helperText('Optional. Until a photo is added, a designed panel is shown.'),
      TextInput::make('image_alt')->label('Photo description (alt text)')->maxLength(255),
      Select::make('image_side')->label('Photo side')->options(['right' => 'Right', 'left' => 'Left'])->default('right')->required(),
      Select::make('background')->label('Background')->options(['white' => 'White', 'slate' => 'Light grey', 'paper' => 'Paper'])->default('white')->required(),
  ])->columns(2),
  ```
- **`church_finder`**:
  - `Select layout` (`band` → "Band", `panel` → "Panel with region links"), `->default('band')->required()`;
  - `Textarea description` (`->rows(2)->maxLength(255)`);
  - `Toggle show_regions` (`->default(true)`).
- **`church_directory`**: `Toggle::make('compact')->label('Compact list')->default(false)`.
- **`events_feed`**:
  - `Select layout` (`list` → "List", `cards` → "Cards in a scrolling row"), `->default('list')->required()`;
  - `TextInput more_text` (`->default('More events')->maxLength(50)`);
  - `TextInput more_url` (`->default('/events')->maxLength(255)`).
- **`department_list`**: `Select layout` (`cards` → "Cards", `tiles` → "Colour tiles"), `->default('cards')->required()`.
- **GOTCHA**: `required()` on `layout` only enforces a value when someone saves the block; existing stored blocks
  without it still render through the default rule.
- **VALIDATE**:
  - `php -l`;
  - a national admin gets 200 on `/admin/pages/6/edit` (HTTP test in Task 13);
  - the source-declaration test in Task 13.

### Task 6: `CmsBlockSchemaTest` — add the new block's presentation keys
- **IMPLEMENT**: in the `match` at `:177`, add `'feature' => ['image_side', 'background'],`. Nothing else: missing
  `layout` keys are by design (the default rule).
- **VALIDATE**: `php artisan test --filter=CmsBlockSchema` passes.

### Task 7: `DepartmentStrip.vue` + mount
- **IMPLEMENT** (classes from the contract's `strip` row):
  ```vue
  <template>
      <!-- Fixed height so the navbar never shifts while the list loads or if it fails. -->
      <nav aria-label="Departments" :class="STRIP.wrapper">
          <ul v-if="departments.length" class="flex items-center gap-8 overflow-x-auto h-full px-4 sm:px-6 lg:px-8 max-w-[1600px] mx-auto">
              <li v-for="d in departments" :key="d.slug" class="shrink-0">
                  <router-link :to="`/departments/${d.slug}`" class="block rounded focus-visible:outline focus-visible:outline-2 focus-visible:outline-white">
                      <img v-if="logoOf(d)" :src="logoOf(d)" :alt="d.name" width="200" height="100" decoding="async" :class="STRIP.logo">
                      <span v-else class="text-white text-sm font-semibold">{{ d.name }}</span>
                  </router-link>
              </li>
          </ul>
      </nav>
  </template>
  <script>
  import { defineComponent, computed } from 'vue'
  import { useBlockData } from '../composables/useBlockData'
  const STRIP = { wrapper: '/* from contract */', logo: '/* from contract */' }
  export default defineComponent({
      name: 'DepartmentStrip',
      setup() {
          const { items } = useBlockData('/api/departments')
          const departments = computed(() => items.value || [])
          const url = (p) => (!p ? '' : p.startsWith('http') ? p : `/storage/${p}`)
          // White lockup first (the strip is dark), then the standard mark.
          const logoOf = (d) => url(d.logo_light_path) || url(d.logo_path)
          return { departments, logoOf, STRIP }
      },
  })
  </script>
  ```
- **MOUNT** `App.vue`: `<DepartmentStrip />` immediately before `<Navbar />` (inside `#app`, not inside `<main>`).
  Register the component.
- **GOTCHA**:
  - Don't wrap the strip and nav in one sticky element.
  - `/api/departments` returns published departments in `sort_order`.
  - This is one request per app load, because `App.vue` mounts once.
- **VALIDATE**: `npm run build`; browser (Task 15): the strip is visible, scrolls away, and the navbar pins at top 0;
  each link's accessible name is the department name; at 390 the strip scrolls inside itself.

### Task 8: Hero `banner` layout (`CmsPage.vue`)
- **IMPLEMENT**: wrap the existing hero `<section>` in `v-if="block.data.layout !== 'banner'"` (unchanged markup for every
  other page), and add:
  ```vue
  <section v-else-if="block.type === 'hero'" :class="BANNER.wrapper">
      <img v-if="block.data.background_image" :src="getImageUrl(block.data.background_image)"
           :alt="block.data.image_alt || ''" width="2128" height="788"
           fetchpriority="high" loading="eager" class="block w-full h-auto">
      <!-- Real text: the banner's words are illegible on phones and invisible to
           screen readers. Shown under the banner below md; screen-reader-only above. -->
      <div :class="BANNER.caption">
          <h1 class="…contract…">{{ block.data.heading }}</h1>
          <p v-if="block.data.subheading" class="…contract…">{{ block.data.subheading }}</p>
      </div>
  </section>
  ```
  `BANNER.caption` contains `md:sr-only`.
- **GOTCHA**:
  - The hero branch is inside a `v-if/v-else-if` chain on `block.type`. Restructure it as
    `v-if="block.type === 'hero' && block.data.layout !== 'banner'"` followed by
    `v-else-if="block.type === 'hero'"`, so the chain stays valid.
  - `hasHero` (`:341`) still suppresses `PageHeader`.
  - `church_finder` is no longer first, so it renders an h2. There is exactly one h1.
- **VALIDATE**:
  - `npm run build`;
  - a browser check: the h1 is visible at 390 and has `sr-only` at 1440;
  - no CLS above 0.1;
  - other pages with a hero (e.g. `/about`, `/apostolic-bible-college`) are pixel-identical before and after (Task 15
    regression shots).

### Task 9: `cta` `band` style (`CmsPage.vue`)
- **IMPLEMENT**:
  - `getCtaClasses` gains `band: '<contract band.ground>'`;
  - the template branches on `block.data.style === 'band'`: a single `max-w-7xl` row, `flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4`, with the heading (+ optional `text`) left and the `<a :href>` button right;
  - other styles are unchanged.
- **VALIDATE**: `npm run build`; a browser check.

### Task 10: `FeatureBlock.vue` + registration
- **IMPLEMENT**:
  - **Props**: `{ data }`.
  - **Grid**: `lg:grid-cols-2`. `image_side === 'left'` puts the media first from `lg` (`lg:order-first` on the media
    cell, as a literal class chosen by a ternary). On phones the media comes first.
  - **Text**: `v-html="renderMarkdown(data.content || '')"` (import from `../../utils/markdown`).
  - **Button**: when `button_text && button_url`, `<a :href>`.
  - **With an image**: `<img :src="url(data.image)" :alt="data.image_alt || ''" loading="lazy" decoding="async" class="w-full h-full object-cover">` inside the contract's aspect box.
  - **Without an image**: the contract's fallback panel, `aria-hidden="true"`, no text such as "placeholder" or "coming soon".
  - **Ground**: `{ white: 'bg-white', slate: 'bg-slate-50', paper: 'bg-brand-paper' }[data.background] || 'bg-white'`, as a literal map.
  - **Register** in `CmsPage.vue`: import, add to `components`, and
    `<FeatureBlock v-else-if="block.type === 'feature'" :data="block.data" />` beside the other data blocks
    (`:224-230`).
- **VALIDATE**: `npm run build`; a browser check with and without `image`.

### Task 11: Data-block layouts
- **`ChurchFinderBlock.vue`**, when `data.layout === 'panel'`:
  - Left: the heading (h1/h2 logic unchanged), `description`, and the same `<form>` (keep the
    `id="church-finder-query"` label/input and the `submit` router push).
  - Right, when `data.show_regions !== false`: `useBlockData('/api/regions')`, `router-link :to="/regions/${r.slug}"`,
    showing `r.name` (and `r.churches_count` congregations, from the API, not invented).
  - The `/api/regions` call is made only in panel mode:
    `useBlockData(() => (props.data.layout === 'panel' && props.data.show_regions !== false ? '/api/regions' : null))`.
    Verified: `useBlockData` accepts a getter, watches it, and on a falsy URL sets `items = []` without fetching
    (`composables/useBlockData.js:32-47`).
  - `'band'`/missing → today's markup.
- **`ChurchDirectoryBlock.vue`**, when `data.compact === true`:
  - per region, `grid sm:grid-cols-2 lg:grid-cols-3` of rows showing `church.name` and `church.city`;
  - **no links** (same as today's cards); keep "Not on the map yet" as a small muted note.
  - Otherwise today's markup.
- **`EventsFeedBlock.vue`**, when `data.layout === 'cards'`:
  - **Header**: the heading, plus `<router-link :to="data.more_url || '/events'">{{ data.more_text || 'More events' }}</router-link>`.
  - **Scroller**: `<div role="region" :aria-label="data.heading || 'Upcoming events'" tabindex="0" class="flex gap-6 overflow-x-auto snap-x snap-proximity pb-4 …">`.
  - **Card**: `<article class="snap-start shrink-0 w-[85%] sm:w-72 …">`.
  - **Card top**:
    - with an image: `<img v-if="event.image_path" :src="url(event.image_path)" alt="" loading="lazy" decoding="async" class="aspect-[4/3] w-full object-cover">`;
    - without one: a date tile using `departmentHeroClasses(event.department?.color_theme)` when a department exists,
      else the contract's brand ground. It shows `dayOf(start_date)` and `monthOf(start_date)` in large type.
  - **Card body**: `event.name`, the date range (string-split; show `end_date` only when it differs), and `location`.
  - **Link**: only when `event.url`; external, `target="_blank" rel="noopener"`.
  - No `setInterval` and no autoplay.
  - `'list'`/missing → today's markup.
- **`DepartmentListBlock.vue`**, when `data.layout === 'tiles'`:
  - a grid of `router-link :to="/departments/${slug}"` tiles;
  - ground `departmentHeroClasses(department.color_theme)`;
  - `<img :src="url(department.logo_light_path || department.logo_path)" :alt="department.name" loading="lazy">` (large,
    per the contract), plus the name.
  - `'cards'`/missing → today's markup.
- **VALIDATE**:
  - `npm run build`;
  - `php artisan test --filter='ChurchSearchFilter|ApiVisibility|EventScope'` stays green;
  - browser keyboard: Tab focuses the event scroller, and arrow keys scroll it.

### Task 12: Footer (and Navbar only per the contract)
- **`Footer.vue`**:
  - Contact column: the Ministers' log-in as a plain `<a href="/admin">` (a server path; `isPlainAnchor` rule), styled
    as the contract's `footer.login`.
  - `contact_email` as selectable text plus a `mailto` link (when set).
  - `postal_address` in `<address class="not-italic whitespace-pre-line">` (when set; from `settings.value?.postal_address`).
  - Keep "Find your local church".
  - Ground per the contract.
- **`Navbar.vue`**: only if the contract's `navbar` row is not "unchanged" — swap the ground class only. No menu logic.
- **GOTCHA**: The log-in is not added to the header menu rows (`NavigationTest` forbids duplicate header destinations;
  `/admin` is already menu item 32).
- **VALIDATE**: `php artisan test --filter=Navigation`; `npm run build`.

### Task 13: Guarded content migration `2026_10_14_000002_author_the_upca_style_homepage.php`
- **IMPLEMENT**, mirroring the D2 migration:
  ```php
  private const EXPECTED = ['church_finder', 'church_directory', 'text', 'events_feed', 'department_list', 'cta'];
  private const WRITTEN  = ['hero', 'cta', 'feature', 'church_finder', 'church_directory', 'events_feed', 'department_list', 'cta'];
  private const ARCHIVE  = 'home-before-upca-style';

  public function up(): void {
      $page = Page::where('slug', 'home')->first();
      if (! $page) { return; }
      $current = $this->blocks($page);
      if ($this->types($current) !== self::EXPECTED) { return; }          // edited in the CMS → leave it
      $find = fn (string $type) => collect($current)->firstWhere('type', $type)['data'] ?? [];
      Page::updateOrCreate(['slug' => self::ARCHIVE], ['title' => 'Home — before the upca-style rebuild', 'content' => $current, 'is_published' => false]);
      $connect = $find('cta');
      $page->update(['content' => [
          ['type' => 'hero', 'data' => [
              'layout' => 'banner',
              'heading' => 'Pentecost Everyday',
              'subheading' => 'A Sustainable Annual Harvest — Going Beyond 2026',
              'background_image' => 'page-images/01KPYX2XHW5DRFENE0NJ918RC6.jpg',
              'image_alt' => 'Pentecost Everyday. A Sustainable Annual Harvest, Going Beyond 2026. …and the Lord added to the church daily such as should be saved. Acts 2:47',
              'style' => 'gradient-blue',
          ]],
          ['type' => 'cta', 'data' => [
              'style' => 'band',
              'heading' => 'Annual General Conference · 24–25 October · Wellington',
              'button_text' => 'See the calendar',
              'button_url' => '/events',
          ]],
          ['type' => 'feature', 'data' => [
              'heading' => 'About the UPCI NZ',
              'content' => $find('text')['content'] ?? '',                 // the live "Who we are" copy, verbatim
              'button_text' => 'Read more', 'button_url' => '/about',
              'image' => null, 'image_alt' => null,
              'image_side' => 'right', 'background' => 'white',
          ]],
          ['type' => 'church_finder', 'data' => array_merge($find('church_finder'), [
              'layout' => 'panel',
              'description' => 'Congregations across the Northern, Central and Southern regions.',
              'show_regions' => true,
          ])],
          ['type' => 'church_directory', 'data' => array_merge($find('church_directory'), ['compact' => true])],
          ['type' => 'events_feed', 'data' => array_merge($find('events_feed'), [
              'layout' => 'cards', 'heading' => 'Coming up', 'limit' => 8,
              'more_text' => 'More events', 'more_url' => '/events',
          ])],
          ['type' => 'department_list', 'data' => array_merge($find('department_list'), ['layout' => 'tiles'])],
          // The old block stored its sentence under 'content'; the renderer reads 'text'.
          ['type' => 'cta', 'data' => array_merge(
              collect($connect)->except('content')->all(),
              ['text' => $connect['content'] ?? ($connect['text'] ?? null), 'style' => $connect['style'] ?? 'blue'],
          )],
      ]]);
  }

  public function down(): void {
      $page = Page::where('slug', 'home')->first();
      $archive = Page::where('slug', self::ARCHIVE)->first();
      if (! $page || ! $archive || $this->types($this->blocks($page)) !== self::WRITTEN) { return; }
      $page->update(['content' => $this->blocks($archive)]);              // the archive row stays (delete nothing)
  }
  // blocks(): decode string|array → array; types(): array_map(fn ($b) => $b['type'] ?? '', …)
  ```
- **GOTCHA**:
  - `$find('cta')` returns the **only** `cta` in the D2 sequence ("Connect with us").
  - Keep `upcoming_only` and `scope: national` from the old `events_feed` (`array_merge` preserves them).
  - The band's date is content: after 25 Oct someone updates it in the admin (Task 16 report).
- **VALIDATE**: Task 14 tests.

### Task 14: Seeder + tests
- **`HomePageSeeder`**:
  - Switch `Page::create([...])` to `Page::firstOrCreate(['slug' => 'home'], [...])`, so it never overwrites an
    existing homepage.
  - Set its content to the same eight blocks, with literal values. The "Who we are" copy goes into the `feature`
    block's `content`, and the Connect sentence goes under `text`.
- **`tests/Feature/HomepageUpcaStyleTest.php`** (helper prefix `upcaHome…` — Pest helpers are global):
  1. **Migration on a D2 homepage**: create `home` with the exact live D2 blocks (copy them from the live DB into the
     test), then `up()`.
     - The types equal `WRITTEN`.
     - `feature.content` equals the Who-we-are copy.
     - The last cta has `text` (the Connect sentence) and no `content`.
     - `church_directory.compact` is true; `events_feed.scope` is `national` and `upcoming_only` is preserved.
     - The archive exists, unpublished, holding the original blocks.
  2. **Editor wins**: a `home` with any other sequence is left byte-identical, and no archive is created.
  3. **No homepage**: no-op, no exception.
  4. **Idempotent**: running `up()` twice gives the same content, and one archive row.
  5. **Rollback**: `down()` restores the original blocks and the archive remains. `down()` on an edited homepage does nothing.
  6. **Form declarations**: regex `PageForm.php` per block (as `CmsBlockSchemaTest::declaredCardFields()` does) to check:
     - `hero`: `layout`, `image_alt`;
     - `feature`: `heading, content, button_text, button_url, image, image_alt, image_side, background`;
     - `church_finder`: `layout, description, show_regions`;
     - `church_directory`: `compact`;
     - `events_feed`: `layout, more_text, more_url`;
     - `department_list`: `layout`;
     - `cta` options include `'band'`.
  7. **Admin page renders**: a national user gets 200 on `GET /admin/pages/{home}/edit` for a page whose content is the
     new homepage. This proves the form hydrates every key.
  8. **Settings**:
     - `/api/site-settings` returns `postal_address`;
     - `Livewire::test(ManageSiteSettings::class)->set('data.postal_address', "PO Box 1\nWellington")->call('save')` as a national user persists it;
     - a local user gets 403 on `/admin/manage-site-settings`.
  9. **Seeder**: `$this->seed(HomePageSeeder::class)` on an empty pages table gives the `WRITTEN` sequence, and running
     it again doesn't change an existing homepage.
  10. **Vue source contracts** (`file_get_contents`):
      - `App.vue` has `<DepartmentStrip` before `<Navbar`;
      - `DepartmentStrip.vue` uses `logo_light_path` and `:alt="d.name"`;
      - `EventsFeedBlock.vue` cards use `snap-x`, `role="region"`, `tabindex="0"`, and **no** `setInterval`;
      - the hero banner `<img` has `fetchpriority="high"` and no `loading="lazy"`;
      - none of the changed Vue files contains a `class` built with `${` (`/class="[^"]*\$\{/` and `:class="` + template literal).
- **VALIDATE**: `php artisan test --compact --filter='HomepageUpcaStyle|CmsBlockSchema'`.

### Task 15: Full validation, regression shots, live switch (with the owner's OK)
- **ACTION**: Run Level 1-3 (below).
- **ACTION**: **Regression shots before the content switch.** With the new build but the old content, take full-page
  screenshots of `/`, `/about`, `/apostolic-bible-college`, `/departments/youth` and
  `/apostolic-bible-college/enrollment/new-student` at 1440 and 390. Compare them with shots taken before the build
  (Task 2). Only the strip (and footer/navbar per the contract) may differ.
- **ACTION**: With the owner's OK, run `php artisan migrate` on live (Task 0 backup in place).
- **ACTION**: Browser check on `https://upci.b8.co.nz/` at 1440×900 and 390×844:
  - **sections**: present in order;
  - **layout**: no horizontal scroll; no console errors; the strip scrolls away and the navbar stays pinned;
  - **banner**: alt text present;
  - **h1**: visible at 390 and sr-only at 1440;
  - **keyboard**: the strip links and the event scroller can be reached and operated;
  - **fallbacks**: the About fallback and the date tiles render;
  - **ABC running head**: still pins under the navbar.
- **ACTION**: Put the screenshots side by side with upca's.
- **VALIDATE**: as listed.

### Task 16: Report, commit, archive
- **ACTION**: Write `.claude/PRPs/reports/homepage-upca-style-report.md`, including:
  - owner actions:
    - set `APP_URL=https://upci.b8.co.nz` so admin image previews work;
    - upload an About photo in Pages → Home → "Text beside a photo";
    - add event flyers (the AGC first);
    - fill in the contact email and postal address in Site Settings;
    - update the band after 25 Oct;
  - the staleness date.
- **ACTION**: Commit in focused commits, each passing the full suite on its own (as with the enrolment work):
  1. settings backend;
  2. block schema;
  3. strip and chrome;
  4. block layouts;
  5. content migration and seeder;
  6. design records, plan and report.
- **ACTION**: Archive the plan to `completed/`.

---

## Testing Strategy

| Test file | Cases | Validates |
|---|---|---|
| `tests/Feature/HomepageUpcaStyleTest.php` | 10 groups (Task 14) | Content switch, rollback, editor-wins, schema declarations, admin hydration, settings, seeder, Vue contracts |
| `tests/Feature/CmsBlockSchemaTest.php` (+`feature`) | Presentation keys site-wide | Explicit presentation |
| Existing `NavigationTest`, `ChurchSearchFilterTest`, `ApiVisibilityTest`, `EventScopeTest`, `ExampleTest`, `ChurchFormBooleanStateTest` | Unchanged | No regressions |

### Edge cases
- [ ] Homepage edited in the CMS before migrating: no-op, no archive.
- [ ] Department without `logo_light_path`: uses `logo_path`, then its name as text.
- [ ] `/api/departments` fails: the strip area keeps its height and stays empty; the navbar doesn't move.
- [ ] Event without an image, end date or URL: date tile; single date; no link.
- [ ] No upcoming events: `empty_message`, and "More events" still shows.
- [ ] Feature without an image: designed fallback; no "placeholder" wording.
- [ ] The admin replaces the banner with a different ratio: `h-auto` keeps it correct, and CLS is limited to that load.
- [ ] 390px: the strip and event row scroll inside themselves; the page never scrolls sideways.
- [ ] `prefers-reduced-motion`: no smooth-scroll animation added.
- [ ] Other pages with `hero`/`cta`/`events_feed`/`department_list`/`church_finder`: identical (regression shots).

---

## Validation Commands

### Level 1: STATIC_ANALYSIS
```bash
{ git diff --name-only; git ls-files --others --exclude-standard; } | grep '\.php$' | sort -u | xargs -n1 php -l
vendor/bin/pint --test $( { git diff --name-only; git ls-files --others --exclude-standard; } | grep '\.php$' | sort -u )
```
(Run pint on changed files only. `tests/Unit/ExampleTest.php` has a missing final newline from before this work; leave it.)
### Level 2: TARGETED
```bash
php artisan test --compact --filter='HomepageUpcaStyle|CmsBlockSchema|Navigation|ChurchSearchFilter|ApiVisibility|EventScope'
```
### Level 3: FULL + BUILD
```bash
php artisan test --compact && npm run build      # expect 0 failed (baseline 279 passed / 25 skipped)
```
### Level 4: DATABASE (after the owner's OK and the live migrate)
```bash
sqlite3 upci "select json_group_array(json_extract(value,'$.type')) from pages, json_each(pages.content) where slug='home'"
#   ["hero","cta","feature","church_finder","church_directory","events_feed","department_list","cta"]
sqlite3 upci "select is_published from pages where slug='home-before-upca-style'"     # 0
sqlite3 upci "pragma table_info(site_settings)" | grep postal_address
```
### Level 5: BROWSER (Playwright 1.64 in the scratchpad + headless shell 1243)
Run the Task 15 checklist on `https://upci.b8.co.nz/` at 1440×900 and 390×844, plus the regression pages.
### Level 6: MANUAL (owner)
In the admin:
1. Upload the About photo; it replaces the fallback.
2. Add a flyer to the AGC event; its card shows the flyer.
3. Fill in the email and address; the footer shows them.
4. Replace the banner; it keeps its own ratio.

---

## Acceptance Criteria
- [ ] The homepage order is: strip, navbar, banner, band, about, finder, congregations, events, ministries, connect, footer.
- [ ] The strip shows on every route, is keyboard reachable with department-name alt text, and the navbar stays pinned.
- [ ] The banner is shown whole; the h1 is real text (visible on phones, sr-only on desktop); `fetchpriority="high"`; no lazy loading.
- [ ] The About and event slots work with and without images; no stock photos; no "placeholder" text.
- [ ] "Connect with us" shows its sentence.
- [ ] The footer has the ministers' log-in, plus email and address when set.
- [ ] Other pages are identical (regression shots).
- [ ] Full suite green; build passes; no horizontal scroll at 390; no console errors.
- [ ] `direction-approved.md` holds the owner's words and the class contract, and the build matches it.
- [ ] Nothing deleted; D2 is archived unpublished.

## Risks and Mitigations

| Risk | Likelihood | Impact | Mitigation |
|---|---|---|---|
| Visual interpretation drifts from the chosen demo | LOW | MED | The Task 4 class contract is transcribed from the demo before coding; Task 15 compares screenshots |
| Looks like a clone of upca | LOW | MED | Three directions within the upca IA in the NZ brand; "same family" test |
| No photos uploaded for weeks | HIGH | LOW | Designed fallbacks; the banner, strip and hue tiles carry colour and imagery from day one |
| The banner's words are illegible on phones | Certain | LOW | Real h1/subheading under the banner below md; alt text carries the words |
| The band goes stale after 25 Oct | Certain | LOW | Admin-editable; the report names the date and the owner action |
| New keys dropped by an admin save | LOW | HIGH | All keys declared (test 6); the admin-hydration test (7) |
| Other pages change by accident | LOW | MED | The default rule; no backfill; regression shots (Task 15) |
| Sticky navbar broken | LOW | MED | The strip is in normal flow; no overflow ancestor (verified); browser check |
| Admin previews don't show uploaded photos | Certain until `APP_URL` is fixed | LOW | Owner action documented; uploads themselves work |

## Confidence: 9.8/10
Every fact the code depends on was checked against the code, the live data or the installed packages on 2026-10-14:
- block sequence and copy;
- asset paths and sizes;
- API shapes;
- the Filament 4.15.1 method names;
- test-helper behaviour after the upgrade;
- slug uniqueness;
- the seeder's usage;
- sticky constraints.

The visual layer is pinned by a class contract before any Vue is written. Every new option defaults to today's
rendering, and regression shots prove other pages unchanged. The remaining 0.2 is subjective: whether the owner likes
the result once real photos are in.

## Notes
- **Supersedes D2** (2026-08-17, chosen when no photography existed). D2's principle survives: all ten congregations are
  named on the homepage, through the compact directory.
- **Inventory:**
  - Usable real images: the banner; the pale NZ landmarks collage; the Men's camp group photo; the dark congregation montage.
  - The only gallery item's image is an unrelated blog graphic; that's a separate fix.
  - No events have images yet.
- **Future**:
  - preload the banner in the Blade shell for LCP;
  - image variants;
  - "Missed a national event" once video links exist;
  - Filament 5 / Livewire 4 as its own upgrade (the address finder needs rework for Livewire 4).
