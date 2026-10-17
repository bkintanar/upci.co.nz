# Feature: Find a Church and Connect with Us as CMS pages, a contact form block, and ABC announcements

## Summary

Three owner requests (2026-10-17), planned together for review before any build:

1. **"https://upci.b8.co.nz/find-church make this editable in the CMS."** `/find-church` becomes an ordinary CMS page made of two blocks:
   - a new `church_locator` block: today's locator, moved out of `ChurchLocator.vue`, with its hero words now fields;
   - a standard `cta` block for "Can't Find a Church Near You?".

   It looks and works as it does now.
2. **"The connect with us ABC page put it also in CMS"** (owner chose "Both, via a form block"). A new `contact_form` block renders today's contact form, moved out of `ConnectWithUs.vue`:
   - `/connect-with-us` becomes a CMS page built from it;
   - the ABC Connect page shows the form itself instead of a link;
   - each message records which page it was sent from, so the college can tell its messages apart in the inbox.
3. **"Also add an announcement block to the ABC page."** A new `announcements` block holds its own items, edited on the page in Admin › Pages: title, date, body, optional photo with required description, and a show toggle. It renders in the approved department "Quiet frame" announcement design, shared rather than copied. It is added, empty and therefore invisible, to the ABC landing page, ready to fill in.

Work happens on `main` (owner, 2026-10-17: "no need to branch out").

## User Story

As a **national admin**, I want to edit the Find a Church page, the Connect with Us page and ABC announcements in Admin › Pages, so that wording and news change without a developer.

As a **visitor to the ABC Connect page**, I want to send the college a message right there, so that I'm not sent to a general page that doesn't mention the college.

## Problem Statement

- **`/find-church`** is `resources/js/views/ChurchLocator.vue`, 1133 lines. Its hero, intro, stats label, search placeholder and closing call to action are all hard-coded. There is no `pages` row, so nothing appears in Admin › Pages.
- **`/connect-with-us`** is `resources/js/views/ConnectWithUs.vue`, with its heading, intro and messages hard-coded and no `pages` row.
- **The ABC Connect page** (CMS id 14) has two text blocks whose markdown links ("Contact form", "Send a prayer request") go to `/connect-with-us`. The links render as plain-looking text. Messages sent there are indistinguishable in the inbox: `contact_messages` has no source column.
- **The ABC landing page** (CMS id 13) has nowhere for announcements.
  - Department announcements need a `department_id` (NOT NULL), and there is no ABC department.
  - Nothing renders announcements outside `Department.vue`.

## Solution Statement

- **Block architecture as it stands.** `pages.content` is a list of `{type, data}`. `PageForm.php` declares each block, and `CmsPage.vue` renders it through a `v-if` chain, delegating data blocks to `components/blocks/*Block.vue`, which fetch via `useBlockData`. Three new blocks plug into exactly that: `church_locator`, `contact_form` and `announcements`.
- **Moves.** `ChurchLocator.vue` and `ConnectWithUs.vue` move with `git mv` (moved, not deleted) to `components/blocks/ChurchLocatorBlock.vue` and `ContactFormBlock.vue`. Their page-only parts (page title, outer page shell, own call to action) come out. `routes.js` points `/find-church` and `/connect-with-us` at `CmsPage.vue`.
- **Page rows.** A content migration creates the `find-church` and `connect-with-us` rows with today's words.
- **Announcements API.** `PageController::show` gains one per-block step for announcements: drop hidden items, newest first, add photo sizes (shared with `DepartmentController`), and send the alt text only with a photo.
- **Announcements view.** The department announcement list markup moves into `components/AnnouncementList.vue`, used by both `Department.vue` and the new `AnnouncementsBlock.vue`. One design, two places.

## Metadata

| Field | Value |
|---|---|
| Type | ENHANCEMENT (1, 2) + NEW_CAPABILITY (3) |
| Complexity | HIGH: two large Vue moves, three new blocks, API transformation, one schema migration, four content migrations, test updates |
| Systems Affected | Page builder (PageForm), CmsPage renderer, router, PageController, ContactController + contact inbox, DepartmentController, Department.vue |
| Dependencies | Laravel 12.70, Filament 4.15.1, Vue 3.5, vue-router 4, Leaflet 1.9, Pest 3. No new packages |
| Estimated Tasks | 24 |

---

## UX Design

### Before State
```
╔══════════════════════════════════════════════════════════════════════════════╗
║ /find-church       ChurchLocator.vue: hero, search, filters, map, list,        ║
║                    modal, CTA. Every word is in code. Admin › Pages: no row    ║
║ /connect-with-us   ConnectWithUs.vue: h1 + intro + form card. Words in code    ║
║ /apostolic-bible-college/connect (CMS)                                         ║
║                    purple hero · "Contact Us" text ─[Contact form]→            ║
║                    /connect-with-us · "Prayer Request" text ─link→ same        ║
║                    message saved ─► inbox row; nothing says it came from ABC   ║
║ /apostolic-bible-college (CMS)                                                 ║
║                    hero · about · 3 features · two-column · cards              ║
║                    no announcements anywhere                                   ║
╚══════════════════════════════════════════════════════════════════════════════╝
```

### After State
```
╔══════════════════════════════════════════════════════════════════════════════╗
║ /find-church   CmsPage ─► pages['find-church']                                 ║
║    [church_locator] eyebrow · heading + highlight · intro · stats ·            ║
║                     search placeholder (all fields), then the same search,     ║
║                     filters, map, list, modal; ?search/?region/?service_day    ║
║    [cta blue]       "Can't Find a Church Near You?" (fields)                   ║
║    Admin › Pages › "Find a Church": edit words, add or reorder blocks          ║
║ /connect-with-us   CmsPage ─► pages['connect-with-us']                         ║
║    PageHeader h1 "Connect with Us" + lede (meta description)                   ║
║    [contact_form]  card "Get in touch" · form · thank-you (fields)             ║
║ /apostolic-bible-college/connect                                               ║
║    hero · [contact_form "Contact Us"] form in place · "Prayer Request" text    ║
║    POST /api/contact {…, page:"/apostolic-bible-college/connect"}             ║
║        ─► contact_messages.source_page ─► inbox column "Sent from"             ║
║ /apostolic-bible-college                                                       ║
║    hero · about · [announcements] (hidden until the first item) · features …   ║
║    Admin › Pages › ABC › Announcements block › Add announcement:               ║
║        title · date · body · photo (+ required description) · show toggle      ║
║    /api/pages/… ─► hidden dropped, newest first, photo sizes added             ║
║        ─► AnnouncementList (same component as department pages; PhotoViewer)   ║
╚══════════════════════════════════════════════════════════════════════════════╝
```

### Interaction Changes
| Location | Before | After | User Impact |
|---|---|---|---|
| Admin › Pages | no Find a Church / Connect rows | two new pages, plus three new block types in the builder | edit them like any page |
| `/find-church` | hard-coded | the same look and behaviour, from CMS fields | none for visitors |
| `/connect-with-us` | centred h1 + intro | the site's standard PageHeader (h1 + lede), then the same form card | heading styled like other CMS pages |
| ABC Connect | link to the general form | the form on the page | one step fewer; messages labelled |
| Admin › Contact Messages | From, Email, Message | plus "Sent from" | the college finds its messages |
| ABC landing | no news | Announcements section once items exist | college news on its own page |

---

## Mandatory Reading

| Priority | File | Lines | Why |
|---|---|---|---|
| P0 | `app/Filament/Resources/Pages/Schemas/PageForm.php` | 73–79, 312–457 (cta, cards with nested Builder), 490–535 (church_finder), 651–688 (gallery) | Block declaration patterns to mirror; nested Builder for items |
| P0 | `resources/js/views/CmsPage.vue` | 56–310 template chain, 380–420 imports/components/hasHero/isHome, 447–481 fetchPage, 520–545 onPaper/flushTop | Where the blocks plug in; the h1 rule |
| P0 | `resources/js/views/ChurchLocator.vue` | 1–48 hero, 50–375 locator, 377–388 CTA, 392–991 script (438–444 query seed + setPageMeta, 809–822 URL mirror, 917–949 mount/unmount) | The move and what comes out |
| P0 | `resources/js/views/ConnectWithUs.vue` | all (164) | The move |
| P0 | `resources/js/views/Department.vue` | 66–133 announcements, 159 PhotoViewer, 200–270 shape maps and helpers | The shared list extraction |
| P1 | `database/migrations/2026_10_14_000002_author_the_upca_style_homepage.php` | all | Guarded content migration with archive row; `down()` restores and deletes nothing |
| P1 | `database/migrations/2026_10_02_000002_point_abc_first_time_card_at_native_form.php` | all | Locate blocks by content, never by position |
| P1 | `app/Http/Controllers/Api/PageController.php` | 40–64 | Where enrichment goes |
| P1 | `app/Http/Controllers/Api/DepartmentController.php` | 41–129 (imageSize) | Photo-size helper to share |
| P1 | `app/Http/Controllers/Api/ContactController.php`, `app/Models/ContactMessage.php`, `app/Filament/Resources/ContactMessages/**` | all | Source-page column |
| P1 | `app/Filament/Resources/Departments/RelationManagers/AnnouncementsRelationManager.php` | 30–70 | Announcement fields: PhotoUpload, alt required with photo |
| P2 | `tests/Feature/HomepageUpcaStyleTest.php` | migration require/up/down helpers, block-key dataset, no-fragment-class test | Test patterns |
| P2 | `tests/Feature/DepartmentPhotosFrontendTest.php`, `tests/Feature/MapPopupEscapingTest.php` | all | Source-reading tests whose paths move |
| P2 | `tests/Feature/CmsBlockSchemaTest.php` | all | Block schema checks |

**External documentation**
| Source | Section | Why |
|---|---|---|
| [Filament 4 Builder](https://filamentphp.com/docs/4.x/forms/builder) | blocks, labels, icons, `maxItems` | block declarations |
| [Filament 4 Testing schemas](https://filamentphp.com/docs/4.x/testing/testing-schemas#testing-builders) | `Builder::fake()` | predictable keys; **call the returned restore closure** |
| [Filament 4 FileUpload](https://filamentphp.com/docs/4.x/forms/file-upload#configuring-the-storage-disk-and-directory) | disk/visibility | `.env` has `FILESYSTEM_DISK=local`; always set the public disk |
| [Filament 4 DatePicker](https://filamentphp.com/docs/4.x/forms/date-time-picker#timezones-for-date-only-fields) | date-only | `->format('Y-m-d')`; parse as a local date in Vue |
| [Vue Router: reacting to params](https://router.vuejs.org/guide/essentials/dynamic-matching.html#Reacting-to-Params-Changes) | component reuse | CmsPage already watches `route.path` |
| [Leaflet Map.remove / invalidateSize](https://leafletjs.com/reference.html#map-remove) | lifecycle | map in a plain variable; `remove()` on unmount |
| [WCAG ARIA22](https://www.w3.org/WAI/WCAG22/Techniques/aria/ARIA22) | status messages | contact thank-you |

---

## Patterns to Mirror

**A data block in the builder** (`PageForm.php:651-686`, gallery):
```php
Builder\Block::make('gallery')
    ->label('Gallery')
    ->schema([
        TextInput::make('heading')->label('Heading')->maxLength(255),
        // …
        TextInput::make('empty_message')->label('If there is nothing to show')
            ->default('No photos have been added yet.')->maxLength(255),
    ])
    ->icon('heroicon-o-photo'),
```

**Items inside a block** (`PageForm.php:357-457`, cards): a nested `Builder::make('items')->blocks([Builder\Block::make('card')->schema([...])])`. The stored shape is `data.items = [{type:'card', data:{…}}]`. Mirror it for announcements as `items = [{type:'announcement', data:{…}}]`.

**Rendering a data block** (`CmsPage.vue:295-302`):
```vue
<GalleryBlock v-else-if="block.type === 'gallery'" :data="block.data" />
```

**Photo with required description** (`AnnouncementsRelationManager.php`):
```php
PhotoUpload::make('image_path', 'announcements')->label('Photo')->imageEditor()
    ->helperText('Optional. A flyer or a photo from the event. It shows whole on the department page, never cropped.')->columnSpanFull(),
TextInput::make('image_alt')->label('Photo description')->maxLength(255)
    ->requiredWith('image_path')
    ->validationMessages(['required_with' => 'Describe the photo for people who cannot see it.'])
```

**Guarded content migration with an archive** (`2026_10_14_000002_author_the_upca_style_homepage.php`): find the page by slug and return if it is missing. Compare the block-type sequence to `EXPECTED` and return if it differs ("the editor wins"). Archive with `Page::updateOrCreate(['slug' => ARCHIVE], [..., 'is_published' => false])`. `down()` restores only if still `WRITTEN`, and deletes nothing.

**Announcement payload** (`DepartmentController.php`):
```php
'image_path' => $a->image_path,
'image_alt' => $a->image_path ? $a->image_alt : null,
...$this->imageSize($a->image_path),
```

**Tests.** Pest, run as www-data.
- Source-reading tests use `file_get_contents(resource_path(...))`.
- Migration tests `require database_path('migrations/…')` and call `->up()`/`->down()`.
- Filament uses `Livewire::test(EditPage::class, ['record' => …])->fillForm([...])->call('save')`.
- Literal class strings only: a test forbids `${` in `class=""`.

---

## Files to Change

| File | Action | Justification |
|---|---|---|
| `database/migrations/2026_10_18_000001_add_source_page_to_contact_messages.php` | CREATE | nullable `source_page` string |
| `database/migrations/2026_10_18_000002_author_find_church_and_connect_pages.php` | CREATE | `find-church` and `connect-with-us` rows (`firstOrCreate`; never overwrite) |
| `database/migrations/2026_10_18_000003_put_the_contact_form_on_the_abc_connect_page.php` | CREATE | guarded swap of the "Contact Us" text block for `contact_form`; archive row |
| `database/migrations/2026_10_18_000004_add_announcements_to_the_abc_page.php` | CREATE | guarded insert of an empty `announcements` block after the about text; archive row |
| `app/Models/ContactMessage.php` | UPDATE | fillable `source_page` |
| `app/Http/Controllers/Api/ContactController.php` | UPDATE | `page` input → `source_page` |
| `app/Filament/Resources/ContactMessages/Tables/ContactMessagesTable.php`, `Schemas/ContactMessageInfolist.php` | UPDATE | "Sent from"; empty-state text |
| `app/Http/Controllers/Api/Concerns/MeasuresImages.php` | CREATE | `imageSize()` moved from DepartmentController, shared |
| `app/Http/Controllers/Api/DepartmentController.php` | UPDATE | use the trait (method moved, not removed) |
| `app/Http/Controllers/Api/PageController.php` | UPDATE | announcements enrichment |
| `app/Filament/Resources/Pages/Schemas/PageForm.php` | UPDATE | `church_locator`, `contact_form`, `announcements` blocks |
| `resources/js/views/ChurchLocator.vue` → `resources/js/components/blocks/ChurchLocatorBlock.vue` | MOVE + UPDATE | `git mv`; hero fields from `data`; CTA and `setPageMeta` out; URL mirror uses `route.path` |
| `resources/js/views/ConnectWithUs.vue` → `resources/js/components/blocks/ContactFormBlock.vue` | MOVE + UPDATE | `git mv`; page shell and `setPageMeta` out; fields from `data`; sends `page` |
| `resources/js/components/AnnouncementList.vue` | CREATE | list markup and class maps moved from Department.vue |
| `resources/js/components/blocks/AnnouncementsBlock.vue` | CREATE | section + heading + AnnouncementList |
| `resources/js/views/Department.vue` | UPDATE | uses AnnouncementList |
| `resources/js/views/CmsPage.vue` | UPDATE | register 3 blocks; `hasHero` counts `church_locator` |
| `resources/js/router/routes.js` | UPDATE | `/find-church`, `/connect-with-us` → CmsPage.vue (entries and names kept) |
| `tests/Feature/MapPopupEscapingTest.php`, `tests/Feature/DepartmentPhotosFrontendTest.php` | UPDATE | read the moved files |
| `tests/Feature/CmsFindChurchPageTest.php`, `tests/Feature/ContactFormBlockTest.php`, `tests/Feature/AnnouncementsBlockTest.php` | CREATE | the new behaviour |

## NOT Building (Scope Limits)

- **Restyling.** Find a Church keeps its current (pre-redesign) look. Connect takes the standard PageHeader. A redesign of these pages would go through the huashu three-direction gate as its own request.
- **The ABC Connect hero colour** (`gradient-indigo`, purple). It's a style setting the owner can change in the CMS ("Gradient Blue" is the brand green). Report it rather than change content unasked.
- **Text-block link colour** (CmsPage scoped CSS `#2563eb`, blue). This is a separate, site-wide styling fix; flag it.
- **An announcements data model or admin list.** The items live in the block. A shared announcements model would need its own owner (page or department) migration and admin; not needed for one page.
- **Email for contact messages.** None exists today, and the email service is the owner's later task.
- **A Prayer Request form.** That text block still links to `/connect-with-us`, which now shows the form.

---

## Step-by-Step Tasks

### Task 0: Back up, and get the owner's OK for live migrations
- **Live changes:**
  - 1 schema migration (a nullable column).
  - 3 content migrations, each guarded with an archive row: create two pages, change the ABC Connect page, add a block to the ABC page.
- **Backup:**
  - `git bundle`
  - a site tarball (excluding node_modules and vendor)
  - `sqlite3 upci ".backup"` plus an integrity check
  - a git tag `pre-cms-pages-<ts>`

  All of it goes to `../upci.co.nz-backups/pre-cms-pages-<ts>/`.
- **Order matters, because the working tree is the live site:**
  - **Phase A** (before any code, harmless to the current code): `migrate --path` for files `…000001` (column) and `…000002` (new page rows; unused until the router changes).
  - **Phase B** (after the build, when CmsPage can render the new blocks): `…000003` and `…000004`.
- **VALIDATE:**
  - `sqlite3 upci ".schema contact_messages"`
  - `select slug, is_published, json_array_length(content) from pages where slug in ('find-church','connect-with-us')`
  - `/usr/bin/find storage bootstrap/cache -user root | wc -l` prints 0

### Task 1: CREATE the migration `…000001_add_source_page_to_contact_messages`
- `$table->string('source_page')->nullable()->after('message')`. Docblock: the ABC Connect page now has its own form (owner, 2026-10-17).
- `down()` drops the column.

### Task 2: UPDATE ContactMessage, ContactController and the inbox
- Rule: `'page' => ['nullable', 'string', 'max:255', 'regex:/^\/[\w\-\/]*$/']`, a site path only. Stored as `source_page`. Unknown extra fields are still ignored.
- Table: `TextColumn::make('source_page')->label('Sent from')->placeholder('—')`.
- Infolist: an entry with the same label. Escaped, never `->html()`.
- Empty state: "Submissions from the contact forms (Connect with Us, the ABC Connect page) appear here."
- **VALIDATE**: ContactFormBlockTest (Task 21).

### Task 3: CREATE the `MeasuresImages` trait; UPDATE DepartmentController
- Move `imageSize()` verbatim into `App\Http\Controllers\Api\Concerns\MeasuresImages` (protected). DepartmentController uses it.
- **VALIDATE**: `AnnouncementPhotoTest`, `DepartmentPhotosFrontendTest` pass.

### Task 4: UPDATE PageController, adding the announcements enrichment
```php
// SOURCE PATTERN: DepartmentController announcements payload
private function forPublic(array $content): array   // each block in, each block out
    'announcements' items:
    - drop where data.is_published === false;
    - sort by data.published_at desc (stable);
    - map to [title, content, image_path, image_alt (only with image), image_width, image_height, published_at].
```
- Other blocks pass through untouched.
- Docblock: the first per-block step; it exists because a photo's shape decides the layout before it loads, as on department pages.
- **VALIDATE**: AnnouncementsBlockTest API cases.

### Task 5: UPDATE PageForm with the three blocks
- **`church_locator`** (icon `heroicon-o-map`, label "Church finder (map and list)"):
  - `eyebrow` ("Find Your Local Church")
  - `heading` (required, "Discover UPCI Churches")
  - `heading_highlight` ("Across New Zealand")
  - `intro` (Textarea)
  - `show_stats` (Toggle, default true)
  - `extra_stat_value` ("24/7") and `extra_stat_label` ("Directory"), both optional
  - `search_placeholder` ("Search by city, region, church name, or address...")
  - `->maxItems(1)` (one map per page)
  - Helper text: churches themselves are edited under Churches.
- **`contact_form`** (icon `heroicon-o-envelope`):
  - `heading` ("Get in touch")
  - `intro` (Textarea, optional)
  - `button_text` ("Send")
  - `success_message` ("Thank you. Your message has been sent.")
  - `->maxItems(1)`
- **`announcements`** (icon `heroicon-o-megaphone`):
  - `heading` ("Announcements")
  - `empty_message` (default blank; helper "Leave blank to hide the section until there is an announcement")
  - nested `Builder::make('items')->blocks([Builder\Block::make('announcement')->schema([...])])` with:
    - `TextInput title` (required, 255)
    - `DatePicker published_at` (required, `->format('Y-m-d')`, default today)
    - `MarkdownEditor content` (same toolbar as the department one)
    - `PhotoUpload::make('image_path', 'announcements')`
    - `TextInput image_alt` (`->requiredWith('image_path')` + the department message)
    - `Toggle is_published` ("Show on the page", default true)
  - `->collapsible()`
- **GOTCHA**: field names equal data keys; anything undeclared is dropped on the next save. Single-file PhotoUpload stores a string path (verify in a test).

### Task 6: MOVE `ChurchLocator.vue` → `components/blocks/ChurchLocatorBlock.vue` (`git mv`), then UPDATE
- Props: `data`.
- Hero words come from `data.*`, with today's text as fallbacks so an empty field never blanks the hero. Stats show if `data.show_stats !== false`. The extra stat shows only if both fields are set.
- Remove from the block:
  - the CTA section (it becomes the page's `cta` block);
  - the `setPageMeta` call (CmsPage owns page meta).
- URL mirror: `router.replace({ path: route.path, query })`, not the hard-coded `'/find-church'`.
- Keep the query seeding (`search`, `region`, `service_day`) unchanged. It is the homepage `church_finder` contract.
- Leaflet: map in a module/plain variable, created after the data loads, `remove()` on unmount. This is already done and stays.
- Keep `escapeHtml` on every popup interpolation.

### Task 7: MOVE `ConnectWithUs.vue` → `components/blocks/ContactFormBlock.vue` (`git mv`), then UPDATE
- Remove the outer page h1 and intro (the page's PageHeader or hero supplies them) and the `setPageMeta` call.
- The card heading is `data.heading || 'Get in touch'`, followed by `data.intro`, the button `data.button_text || 'Send'`, and the success text `data.success_message || 'Thank you. Your message has been sent.'`.
- Success:
  - a `role="status"` container, always rendered, which the message fills;
  - focus moves to it.
- The POST body adds `page: route.path`.
- Field ids stay `first_name`, `last_name`, `email` and `message` (ErrorSummary anchors). Note: two forms on one page would clash; `contact_form` is `maxItems(1)`.
- Section wrapper: `py-16 bg-slate-50`, `max-w-3xl`, the existing card classes.

### Task 8: CREATE `components/AnnouncementList.vue`; UPDATE Department.vue
- Move the `<ol>` item markup and the `shape`/`TITLE_COLUMNS`/`FIGURE_PLACES`/`BODY_PLACES`/`PHOTO_FINISH`/`MARKDOWN` maps, the PhotoViewer mount and `openAnnouncementPhoto` from Department.vue verbatim.
- Props: `items` (the department payload shape), `label` (the viewer label prefix).
- Department.vue renders `<AnnouncementList :items="department.announcements" />` inside its existing section and heading, and keeps its empty state.
- **VALIDATE**: screenshot of `/departments/mens` before and after, identical within the announcements section.

### Task 9: CREATE `components/blocks/AnnouncementsBlock.vue`
- Section: the department announcements section's wrapper and D3 heading classes, copied as literal strings, with `data.heading`.
- Body: `<AnnouncementList :items="data.items" label="Apostolic Bible College" />`.
- When there are no items, render nothing if `empty_message` is blank, otherwise the message.

### Task 10: UPDATE CmsPage.vue
- Import and register `ChurchLocatorBlock`, `ContactFormBlock` and `AnnouncementsBlock`, with three `v-else-if` lines next to the other data blocks.
- `hasHero`: `b.type === 'hero' || b.type === 'church_locator'`. The locator's hero has the h1.

### Task 11: UPDATE routes.js
- `/find-church` and `/connect-with-us` use `component: () => import('../views/CmsPage.vue')`. Keep the paths and names, and a comment on why: the CMS rows `find-church` and `connect-with-us`.

### Task 12: CREATE the migration `…000002_author_find_church_and_connect_pages`
- `Page::firstOrCreate(['slug' => 'find-church'], [...])`:
  - title "Find a Church - UPCI New Zealand"
  - meta "Find your nearest UPCI church in New Zealand, by region or by name."
  - published
  - content:
    - `church_locator` with today's words;
    - `cta`: `style: 'blue'`, heading "Can't Find a Church Near You?", text "We're always looking to expand our ministry. Contact us to learn about starting a new UPCI church in your area.", button "Contact Us About Starting a Church" linking to `/connect-with-us`.
- `connect-with-us`:
  - title "Connect with Us - UPCI New Zealand"
  - meta "Have a question or would like to get in touch? Send us a message and we'll get back to you."
  - content: `contact_form` with heading "Get in touch".
- `down()`: leaves the rows. The routes no longer reach them once the code is reverted, and nothing is deleted.

### Task 13: CREATE the migration `…000003_put_the_contact_form_on_the_abc_connect_page`
- Guard: the page exists, has types `['hero','text','text']`, and block 1 has heading "Contact Us" with content containing `](/connect-with-us)`. Otherwise do nothing.
- Archive to `apostolic-bible-college/connect-before-contact-form` (unpublished).
- Replace block 1 with `contact_form` {heading "Contact Us", intro "Send us a message and someone from the college will get back to you."}.
- `down()` restores from the archive if the types are still `['hero','contact_form','text']`.

### Task 14: CREATE the migration `…000004_add_announcements_to_the_abc_page`
- Guard: the page exists, and a `text` block headed "About Apostolic Bible College" exists. No `announcements` block yet.
- Archive to `apostolic-bible-college-before-announcements`.
- Insert `{type:'announcements', data:{heading:'Announcements', empty_message:null, items:[]}}` right after that text block.
- `down()` restores only if the block is still empty.

### Tasks 15–20: Validate in the browser
On test servers with DB copies (`php -S` as www-data, from `public/`):
- **`/find-church` before and after.**
  - Diff the screenshots at 1440 and 390, masking the navbar.
  - `/find-church?search=Hamilton` filters the list.
  - Changing the region or day updates the URL.
  - The modal opens, the map renders, and nothing scrolls sideways.
- **The homepage** church search lands on `/find-church?search=…`.
- **`/connect-with-us`.** Submit, and the row has `source_page` `/connect-with-us`.
- **ABC Connect.** Submit, and the row has `source_page` `/apostolic-bible-college/connect`.
- **ABC page.**
  - With no items, it is unchanged (diff).
  - With three test items (portrait flyer, landscape photo, text only) added through the admin form on the copy, the section renders like the department page. The viewer opens.
  - A hidden item doesn't show. Nothing scrolls sideways at 390.
- **Admin.**
  - Edit Find a Church: change the heading, save, and it shows.
  - The block picker lists the three new blocks.
  - The inbox shows "Sent from".

### Task 21: Tests
- **`CmsFindChurchPageTest`:**
  - The page row exists with `[church_locator, cta]`.
  - `routes.js` maps `/find-church` to CmsPage before the catch-all.
  - `CmsPage` `hasHero` includes `church_locator`.
  - The block source reads `route.query.search` and `region`, mirrors with `route.path`, and has no `setPageMeta` and no `'/find-church'` literal.
  - PageForm declares every `church_locator` key the block reads.
  - `GET /api/pages/find-church` returns 200 with the blocks.
- **`ContactFormBlockTest`:**
  - The `/api/contact` `page` input is stored; malformed values are refused (422); it is optional.
  - The inbox column and infolist show it.
  - Migration 000003: swaps, archives, no-op when edited, `down()` restores, deletes nothing.
  - Block source sends `page: route.path` and has `role="status"`.
  - The `connect-with-us` page row exists.
- **`AnnouncementsBlockTest`:**
  - PageForm declares the block and item keys.
  - The API drops hidden items, orders newest first, adds sizes, sends alt only with a photo, and leaves other blocks untouched.
  - Admin save with `Builder::fake()` (restored in `afterEach`) stores a string path on the public disk, and the alt text is required with a photo.
  - Migration 000004: inserts after the about text, no-op on re-run or edit, `down()`.
  - `AnnouncementList` is used by both Department.vue and the block.
- **Updates:** `MapPopupEscapingTest` path → `ChurchLocatorBlock.vue`. `DepartmentPhotosFrontendTest` reads `AnnouncementList.vue` for the moved strings; its assertions are unchanged.
- **VALIDATE**: `sudo -u www-data php artisan test --compact`, all green.

### Task 22: Build and run Phase B migrations
- `npm run build`, then `migrate --path` for 000003 and 000004 (live, after the backup).
- **VALIDATE**:
  - the live pages: `/find-church`, `/connect-with-us`, `/apostolic-bible-college/connect`, `/apostolic-bible-college` (curl the API and screenshot);
  - `/usr/bin/find storage bootstrap/cache -user root` prints 0.

---

## Testing Strategy

### Edge Cases Checklist
- [ ] `/find-church` deep link with `?search=` / `?region=` / `?service_day=`
- [ ] A Find a Church page unpublished in the admin shows the CMS 404 (a known consequence; say so in the report)
- [ ] Locator block fields left blank use today's words, never an empty hero
- [ ] A contact `page` value that is not a site path is refused
- [ ] Two contact forms on one page cannot happen (`maxItems(1)`)
- [ ] An announcement with a photo but no description is refused in the admin
- [ ] A missing photo file gives null sizes, which lays out as landscape without breaking (department behaviour)
- [ ] An announcement dated in the future still shows. There is no scheduling; the date is a label. Note it.
- [ ] Migrations no-op on pages edited since (the editor wins) and never delete

## Validation Commands
1. `php -l` on changed PHP; `./vendor/bin/pint --test <changed PHP>`.
2. `sudo -u www-data php artisan test --compact tests/Feature/CmsFindChurchPageTest.php tests/Feature/ContactFormBlockTest.php tests/Feature/AnnouncementsBlockTest.php tests/Feature/MapPopupEscapingTest.php tests/Feature/DepartmentPhotosFrontendTest.php tests/Feature/HomepageUpcaStyleTest.php tests/Feature/CmsBlockSchemaTest.php`
3. Full suite: `sudo -u www-data php artisan test --compact` (baseline 426 passed, 25 skipped), then `npm run build`.
4. Database checks: Task 0 and Task 22.
5. Browser checks: Tasks 15–20 (Playwright 1.64 alpha at `/root/.npm/_npx/9833c18b2d85bc59/node_modules/playwright-core`, `chromium_headless_shell-1243`).

## Acceptance Criteria
- [ ] Find a Church and Connect with Us appear in Admin › Pages, and their words change from there
- [ ] `/find-church` looks and works as before, including links from the homepage search
- [ ] The ABC Connect page has the form, and its messages read "Sent from /apostolic-bible-college/connect" in the inbox
- [ ] The ABC page has an Announcements block that shows announcements in the department design once added
- [ ] Nothing deleted; content migrations archive and are guarded; full suite green; build OK

## Risks and Mitigations
| Risk | Likelihood | Impact | Mitigation |
|---|---|---|---|
| The live tree changes behaviour mid-build (a route to CmsPage before the rows exist; a new block type before the renderer) | MED | HIGH | Phase A migrations first (rows, column), then code and build, then Phase B content migrations |
| Moving Department.vue markup alters the approved design | LOW | MED | Verbatim move; screenshot diff of `/departments/mens`; the existing source tests are repointed, not weakened |
| `ChurchLocator`'s 1100 lines break in the move (Leaflet lifecycle, query mirror) | MED | MED | Minimal edits; screenshot diff; deep-link and filter checks in the browser |
| An admin unpublishes Find a Church and the page 404s | LOW | MED | Report it; the page lists like any other |
| `Builder::fake()` leaks into other tests | MED | LOW | Restore in `afterEach` |

## Notes
- **Design gate.** No new visual design:
  - Find a Church keeps its look;
  - Connect uses the site's standard PageHeader and the existing card;
  - announcements reuse the approved D3 department design.

  So no three-direction gate. If the owner wants these pages to match the redesigned homepage, that is a separate design request.
- **Why a block-held list rather than a model.** There is no ABC department, and `department_announcements.department_id` is NOT NULL. A block keeps the announcements with the page they appear on, needs no schema change or new admin screen, and mirrors how `cards` holds items. The API step gives them the same shape the department page uses, so the same component renders both.
- **Why the locator keeps its own hero.** The standard hero block cannot produce the badge, the highlighted second line or the live church and region counts. They are tied to the locator's data, so they belong to its block.
- **Live data at plan time:**
  - `apostolic-bible-college` is `[hero, text, feature, feature, feature, two_column, cards]`;
  - `apostolic-bible-college/connect` is `[hero(gradient-indigo), text "Contact Us", text "Prayer Request"]`;
  - no `find-church` or `connect-with-us` rows exist;
  - `contact_messages` has no source column.
