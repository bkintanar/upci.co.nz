# Feature: Make the `/about/general-superintendent` Page CMS-Editable

## Summary

The `/about/general-superintendent` route is the only `/about/*` page in the site that bypasses the CMS — it's hardcoded in `resources/js/views/about/GeneralSuperintendent.vue` (143 lines) while every sibling (`about/upci`, `about/beliefs`, `about/leadership`, `about/oneness-pentecostalism`) is stored in the `pages` table and rendered via `CmsPage.vue`. This plan ports the existing hardcoded content into a seeded CMS `Page` record (slug `about/general-superintendent`), deletes the explicit Vue route so Vue Router falls through to the existing CMS catch-all, and deletes the now-orphaned Vue component. Result: the page is editable by any National admin through the Filament `Pages` resource, with zero visual change for end users.

## User Story

As a **UPCI NZ National administrator**
I want to **edit the content on `/about/general-superintendent` (heading, role description, selection process, stats, prayer CTA) via the existing Filament `Pages` resource in the admin panel**
So that **updates — e.g. a new General Superintendent's name, updated stats, refreshed prayer text — can happen without a developer edit-and-deploy cycle**.

## Problem Statement

Concrete symptoms an operator can reproduce right now:
- In `/admin/pages`, the list shows 10 CMS pages (home, welcome, about-cms, about/upci, about/beliefs, about/oneness-pentecostalism, about/leadership, apostolic-bible-college, apostolic-bible-college/principals-corner, apostolic-bible-college/enrollment). **No entry for `about/general-superintendent`** — there's nothing to click, nothing to edit.
- Navigating to `https://upci.b8.co.nz/about/general-superintendent` in a browser still renders, because `resources/js/router/routes.js` has a hard-coded entry mapping that path to `../views/about/GeneralSuperintendent.vue`.
- That component's content (heading, role list, stats, prayer block) is baked into the template. Changing anything requires editing Vue, running `npm run build`, and re-deploying.

Testable signals:
- **Before**: `sqlite3 upci "SELECT slug FROM pages WHERE slug='about/general-superintendent';"` returns nothing. A national admin in `/admin/pages` sees no row.
- **After**: the same SQL returns one row. Admin sees a "General Superintendent - UPCI New Zealand" row in the Pages list, clicks Edit, updates the hero heading, saves — the change shows on `/about/general-superintendent` on next page load. The frontend route now goes through `CmsPage.vue` and the hardcoded component no longer exists (or is unreferenced).

## Solution Statement

Three small, ordered edits:

1. **Seeder** — create `database/seeders/GeneralSuperintendentPageSeeder.php` mirroring `LeadershipPageSeeder.php`'s `Page::updateOrCreate(['slug'=>...], [...])` pattern. Port the existing Vue content into the same `content` JSON block shape already used across the codebase (`hero`, `two_column`, `text`, `cta`). Every block type we need already exists in `CmsPage.vue`'s renderer — zero new block types.

2. **Router** — remove the explicit `/about/general-superintendent` route from `resources/js/router/routes.js`. Vue Router's existing catch-all `/:slug(.*)` route already dispatches to `CmsPage.vue`, which does `fetch('/api/pages/' + slug)` — the new DB row is served automatically. Sibling `/about/*` routes that explicitly dispatch to `CmsPage.vue` continue to do so; their shape is harmless.

3. **Cleanup** — delete `resources/js/views/about/GeneralSuperintendent.vue` (now unreferenced). The file is 143 lines of purely template code; nothing else imports it.

A Vite rebuild (`npm run build`) emits the updated route table into the production bundle.

## Metadata

| Field            | Value                                                                               |
| ---------------- | ----------------------------------------------------------------------------------- |
| Type             | ENHANCEMENT (+ small REFACTOR removing hardcoded page)                              |
| Complexity       | LOW                                                                                 |
| Systems Affected | Laravel seeders, `pages` table (one row), Vue router, one deleted component         |
| Dependencies     | Laravel 11, Filament v4, Vue 3 / Vite (all already present)                         |
| Estimated Tasks  | 5                                                                                   |

---

## UX Design

### Before State
```
╔═══════════════════════════════════════════════════════════════════════════════╗
║                              BEFORE STATE                                      ║
╠═══════════════════════════════════════════════════════════════════════════════╣
║                                                                               ║
║   Public visitor                                                              ║
║     Browser → /about/general-superintendent                                   ║
║     → Vue Router matches explicit route                                       ║
║     → loads views/about/GeneralSuperintendent.vue (143 lines, hardcoded)     ║
║     → renders static content baked into the template                          ║
║                                                                               ║
║   National admin                                                              ║
║     /admin → Pages resource                                                   ║
║     → 10 rows listed; no "General Superintendent" entry                       ║
║     → no way to edit the page content                                         ║
║     → any text change requires: edit .vue → npm run build → deploy            ║
║                                                                               ║
║   PAIN_POINTS:                                                                ║
║    - Only sibling /about/* page not manageable via CMS                        ║
║    - Stats ("200+ countries", "40,000+ churches", "6M+ members") rot over    ║
║      time; no editor path                                                     ║
║    - Leadership changes (new GS name) require developer involvement           ║
║                                                                               ║
╚═══════════════════════════════════════════════════════════════════════════════╝
```

### After State
```
╔═══════════════════════════════════════════════════════════════════════════════╗
║                               AFTER STATE                                      ║
╠═══════════════════════════════════════════════════════════════════════════════╣
║                                                                               ║
║   Public visitor                                                              ║
║     Browser → /about/general-superintendent                                   ║
║     → Vue Router — explicit route gone, falls through to catch-all            ║
║        /:slug(.*) → CmsPage.vue                                               ║
║     → CmsPage.vue: fetch(`/api/pages/about/general-superintendent`)          ║
║     → renders content blocks (hero, two_column, text, cta) from DB           ║
║                                                                               ║
║   National admin                                                              ║
║     /admin → Pages resource                                                   ║
║     → 11 rows listed — new "General Superintendent - UPCI New Zealand" row  ║
║     → click Edit → Filament Builder shows hero + two_column + text + cta    ║
║     → tweak heading / list items / stats / prayer → Save                     ║
║     → refresh public page → change is live                                    ║
║                                                                               ║
║   VALUE_ADDS:                                                                 ║
║    - Consistent with every other /about/* page                                ║
║    - No developer needed for leadership / stats / copy updates                ║
║    - Zero net new code paths — reuses existing CmsPage.vue renderer           ║
║    - Seeder is idempotent (Page::updateOrCreate) — re-running is safe         ║
║                                                                               ║
║   DATA_FLOW:                                                                  ║
║     1. Admin edits in /admin/pages/{id}/edit                                 ║
║        → Filament PageResource writes to `pages.content` (JSON)              ║
║     2. Visitor hits /about/general-superintendent                             ║
║        → Vue Router catch-all → CmsPage.vue                                  ║
║        → GET /api/pages/about/general-superintendent (web.php + PageController)║
║        → returns {data: {content: [...]}}                                    ║
║        → CmsPage.vue renders block-by-block                                  ║
║                                                                               ║
╚═══════════════════════════════════════════════════════════════════════════════╝
```

### Interaction Changes

| Location | Before | After | User Impact |
|----------|--------|-------|-------------|
| `/admin/pages` (national user) | 10 rows; no GS entry | 11 rows; GS row editable | Full editorial control |
| `/about/general-superintendent` (public) | Hardcoded Vue | CMS Vue (same visual) | None for visitor |
| `resources/js/views/about/GeneralSuperintendent.vue` | Exists (143 lines) | Deleted | Less dead code to maintain |
| `resources/js/router/routes.js` | Explicit route | Entry removed; catch-all handles it | Matches how sibling pages now work |
| `sqlite3 upci "SELECT slug FROM pages WHERE slug LIKE 'about/%'"` | 4 rows | 5 rows | DB reflects CMS coverage |

---

## Mandatory Reading

| Priority | File | Lines | Why Read This |
|----------|------|-------|---------------|
| P0 | `database/seeders/LeadershipPageSeeder.php` | 1-60 | Exact pattern to mirror — `Page::updateOrCreate(['slug' => 'about/leadership'], [...])` with `content` array of block objects |
| P0 | `database/seeders/AboutUpciPageSeeder.php` | entire | Second reference for the idiom — sibling page with similar block mix |
| P0 | `resources/js/views/about/GeneralSuperintendent.vue` | 1-143 | Source of truth for content being ported — all template prose is here |
| P0 | `resources/js/views/CmsPage.vue` | 22-145 | Block-type renderer — confirms `hero`, `two_column`, `text`, `cta` blocks all exist and render as expected |
| P1 | `resources/js/router/routes.js` | (around the `/about/*` entries) | The router file to edit — entries for sibling pages show the correct end-state shape |
| P1 | `app/Models/Page.php` | entire | Page model — casts `content` to array, fields include `title`, `slug`, `meta_description`, `is_published`, `sort_order`, `content` |
| P1 | `app/Filament/Resources/Pages/PageResource.php` | entire | Where the row will appear in Filament; confirms the admin already lists `pages` rows |
| P2 | `database/seeders/DatabaseSeeder.php` | 14-26 | Central seeder registry — new seeder should be invoked here if we want it to run via `db:seed` |
| P2 | `app/Http/Controllers/Api/PageController.php` (or equivalent) | (endpoint handler) | Confirms `/api/pages/{slug}` resolves the row; no code change needed — the new row is transparent |

**External Documentation:**

| Source | Section | Why Needed |
|--------|---------|------------|
| [Vue Router 4 — Dynamic Matching](https://router.vuejs.org/guide/essentials/dynamic-matching.html) | path params & catch-all | Confirms `/:slug(.*)` catch-all picks up a removed explicit route without any other change |
| [Laravel 11 — Seeders](https://laravel.com/docs/11.x/seeding#writing-seeders) | `php artisan db:seed --class=...` | Single-class seeder invocation (avoids touching `DatabaseSeeder.php` unless we want automatic runs) |
| [Laravel 11 — updateOrCreate](https://laravel.com/docs/11.x/eloquent#upserts) | idempotency | Re-running the seeder won't duplicate the row |

---

## Patterns to Mirror

**SEEDER_SHAPE (primary pattern):**
```php
// SOURCE: database/seeders/LeadershipPageSeeder.php:10-33
public function run(): void
{
    Page::updateOrCreate(
        ['slug' => 'about/leadership'],
        [
            'title' => 'Leadership - UPCI New Zealand',
            'meta_description' => 'Meet the dedicated leaders ...',
            'is_published' => true,
            'sort_order' => 5,
            'content' => [
                [
                    'type' => 'hero',
                    'data' => [
                        'heading' => 'Leadership',
                        'subheading' => 'Meet the dedicated leaders ...',
                        'style' => 'gradient-slate',
                        'background_image' => null,
                        'button1_text' => null,
                        'button1_url' => null,
                        'button2_text' => null,
                        'button2_url' => null,
                    ],
                ],
                // ... more blocks
            ],
        ],
    );
}
```

**HERO_BLOCK (from existing seeders, all `/about/*` pages):**
```php
[
    'type' => 'hero',
    'data' => [
        'heading' => 'General Superintendent',
        'subheading' => 'Meet the spiritual leader who guides the worldwide UPCI organization.',
        'style' => 'gradient-slate',
        'background_image' => null,
        'button1_text' => null,
        'button1_url' => null,
        'button2_text' => null,
        'button2_url' => null,
    ],
],
```

**TWO_COLUMN_BLOCK (renders via `CmsPage.vue:91-98`; supports markdown):**
```php
[
    'type' => 'two_column',
    'data' => [
        'left_content' => "## General Superintendent\n\nThe General Superintendent serves as the chief executive officer of the UPCI, providing spiritual leadership and administrative oversight ...",
        'right_content' => "## Role and Responsibilities\n\n- **Spiritual Leadership** — Provides vision and direction for the worldwide UPCI organization.\n- **Administrative Oversight** — Oversees the operations of the General Board and headquarters staff.\n- **International Representation** — Represents UPCI at international conferences and events.\n- **Policy Development** — Works with the General Board to develop organizational policies.",
    ],
],
```

**TEXT_BLOCK with stats pattern (from `CmsPage.vue:229-232` — `hasStats` detects `- **`):**
```php
[
    'type' => 'text',
    'data' => [
        'heading' => 'Global Impact',
        'content' => "- **200+** Countries\n  UPCI presence worldwide\n- **40,000+** Churches\n  Under UPCI leadership\n- **6M+** Members\n  Worldwide UPCI family",
    ],
],
```

**CTA_BLOCK (from existing seeders):**
```php
[
    'type' => 'cta',
    'data' => [
        'heading' => 'Pray for Our Leadership',
        'text' => "We ask for your prayers for our General Superintendent and all UPCI leaders as they guide our organization in fulfilling the Great Commission.\n\n\"And he gave some, apostles; and some, prophets; and some, evangelists; and some, pastors and teachers; For the perfecting of the saints, for the work of the ministry, for the edifying of the body of Christ.\" - Ephesians 4:11-12",
        'button_text' => 'Return to Leadership',
        'button_url' => '/about/leadership',
        'style' => 'blue',
    ],
],
```

**VUE_ROUTER entry shape (for sibling `/about/*` — what to DELETE, not add):**
```js
// SOURCE: resources/js/router/routes.js — the entry to REMOVE:
{
    path: '/about/general-superintendent',
    name: 'GeneralSuperintendent',
    component: () => import('../views/about/GeneralSuperintendent.vue')
},
```

**CATCH_ALL that picks it up once the explicit route is gone (already present):**
```js
// SOURCE: resources/js/router/routes.js (end of the array)
{
    path: '/:slug(.*)',
    name: 'CmsPageBySlug',
    component: () => import('../views/CmsPage.vue')
}
```

---

## Files to Change

### Create

| File                                                            | Purpose                                                                                           |
| --------------------------------------------------------------- | ------------------------------------------------------------------------------------------------- |
| `database/seeders/GeneralSuperintendentPageSeeder.php`          | `Page::updateOrCreate(['slug' => 'about/general-superintendent'], [...])` — idempotent seeder   |

### Update

| File                                 | Change                                                                                                       |
| ------------------------------------ | ------------------------------------------------------------------------------------------------------------ |
| `resources/js/router/routes.js`      | Delete the `/about/general-superintendent` → `GeneralSuperintendent.vue` entry (4 lines). Catch-all takes over. |

### Delete

| File                                                            | Why                                                                                 |
| --------------------------------------------------------------- | ----------------------------------------------------------------------------------- |
| `resources/js/views/about/GeneralSuperintendent.vue`            | Orphaned once the router entry is removed. Content has been ported to the seeder.  |

### Run

- `sudo -u www-data php artisan db:seed --class=GeneralSuperintendentPageSeeder --force` — populates the DB row.
- `npm run build` — rebuilds the frontend bundle (router change + one-fewer-view).

### NOT touching

- `app/Models/Page.php`, `app/Filament/Resources/Pages/PageResource.php` — the row is just another `pages` entry; zero schema change.
- `app/Http/Controllers/Api/PageController.php` (or wherever `/api/pages/{slug}` lives) — already resolves any slug.
- `database/seeders/DatabaseSeeder.php` — do NOT auto-invoke; keep the new seeder one-shot-only (operator runs it explicitly). Matches how `LeadershipPageSeeder` is currently invoked (manually, not from the central seeder — confirm by re-reading `DatabaseSeeder.php`; add to the `->call([...])` array only if the convention there already lists it).

---

## NOT Building (Scope Limits)

- **Preserving the exact pixel-level layout of the old hardcoded page.** The CMS block renderer is semantically equivalent but visually may differ slightly (e.g. the hardcoded "Global Impact" used three side-by-side blue cards; the CMS `text` block with stats renders via the `hasStats` markdown path — a different visual, but in the same design vocabulary as the rest of the site). If you want a pixel-perfect port, that's a post-MVP concern; noted as an open question.
- **A new CMS block type for "stats with colored circle icons".** Out of scope. The existing markdown-driven stats render is the consistent pattern across Leadership, About UPCI, etc. — the GS page should match them.
- **Making the CTA button URL smart.** The old page had no CTA button; the new one adds one going back to `/about/leadership`. Adjust in Filament later if undesired.
- **Localisation / i18n.** Not present anywhere else in the CMS; not adding here.
- **SEO-specific tweaks** (og:image, structured data for a single person). Out of scope; CMS stores `meta_description` which is enough for now.
- **Deleting the seeder after it runs.** Keep it — it's a reproducible way to restore the page content, and `updateOrCreate` makes it safe to re-run.
- **Auditing the other `/about/*` explicit routes.** They ALL explicitly dispatch to `CmsPage.vue` and that's fine — no reason to also remove them in this plan. Out of scope.

---

## Step-by-Step Tasks

Execute in order. Each task ends with an executable verification step.

### Task 1: CREATE `database/seeders/GeneralSuperintendentPageSeeder.php`

- **ACTION**: Create the seeder mirroring `LeadershipPageSeeder.php`.
- **IMPLEMENT**:
  ```php
  <?php

  namespace Database\Seeders;

  use App\Models\Page;
  use Illuminate\Database\Seeder;

  class GeneralSuperintendentPageSeeder extends Seeder
  {
      public function run(): void
      {
          Page::updateOrCreate(
              ['slug' => 'about/general-superintendent'],
              [
                  'title' => 'General Superintendent - UPCI New Zealand',
                  'meta_description' => 'Meet the General Superintendent who provides spiritual leadership and administrative oversight for the worldwide UPCI organization.',
                  'is_published' => true,
                  'sort_order' => 6,
                  'content' => [
                      [
                          'type' => 'hero',
                          'data' => [
                              'heading' => 'General Superintendent',
                              'subheading' => 'Meet the spiritual leader who guides the worldwide UPCI organization.',
                              'style' => 'gradient-slate',
                              'background_image' => null,
                              'button1_text' => null,
                              'button1_url' => null,
                              'button2_text' => null,
                              'button2_url' => null,
                          ],
                      ],
                      [
                          'type' => 'two_column',
                          'data' => [
                              'left_content' => "## General Superintendent\n\nThe General Superintendent serves as the chief executive officer of the UPCI, providing spiritual leadership and administrative oversight to the entire organization.",
                              'right_content' => "## Role and Responsibilities\n\n- **Spiritual Leadership** — Provides vision and direction for the worldwide UPCI organization.\n- **Administrative Oversight** — Oversees the operations of the General Board and headquarters staff.\n- **International Representation** — Represents UPCI at international conferences and events.\n- **Policy Development** — Works with the General Board to develop organizational policies.",
                          ],
                      ],
                      [
                          'type' => 'text',
                          'data' => [
                              'heading' => 'Selection Process',
                              'content' => "The General Superintendent is elected by the General Conference, which meets every two years. This position requires extensive ministerial experience, proven leadership abilities, and a deep understanding of UPCI doctrine and practice.\n\nThe General Superintendent serves a four-year term and may be re-elected for additional terms. This position represents the highest level of leadership within the UPCI organization.",
                          ],
                      ],
                      [
                          'type' => 'text',
                          'data' => [
                              'heading' => 'Global Impact',
                              'content' => "- **200+** Countries with UPCI presence worldwide\n- **40,000+** Churches under UPCI leadership\n- **6M+** Members in the worldwide UPCI family",
                          ],
                      ],
                      [
                          'type' => 'cta',
                          'data' => [
                              'heading' => 'Pray for Our Leadership',
                              'text' => "We ask for your prayers for our General Superintendent and all UPCI leaders as they guide our organization in fulfilling the Great Commission.\n\n\"And he gave some, apostles; and some, prophets; and some, evangelists; and some, pastors and teachers; For the perfecting of the saints, for the work of the ministry, for the edifying of the body of Christ.\" — Ephesians 4:11-12",
                              'button_text' => 'Return to Leadership',
                              'button_url' => '/about/leadership',
                              'style' => 'blue',
                          ],
                      ],
                  ],
              ],
          );
      }
  }
  ```
- **MIRROR**: `database/seeders/LeadershipPageSeeder.php:1-60` exactly — imports, namespace, class, method, `updateOrCreate` call.
- **GOTCHA**: `Page::content` is cast to array on the model (confirmed by reading `app/Models/Page.php`). Pass a plain PHP array — Laravel encodes to JSON on write. Do NOT `json_encode` manually.
- **GOTCHA 2**: Keep `sort_order: 6` (one more than Leadership's `5`) so the admin Pages list orders this near its siblings.
- **VALIDATE**:
  ```bash
  php -l database/seeders/GeneralSuperintendentPageSeeder.php
  # EXPECT: No syntax errors
  ```

### Task 2: RUN the seeder

- **ACTION**: Populate the DB row.
- **IMPLEMENT**:
  ```bash
  cd /var/www/personal/upci.co.nz
  sudo -u www-data php artisan db:seed --class=GeneralSuperintendentPageSeeder --force
  ```
- **VALIDATE**:
  ```bash
  sqlite3 upci "SELECT id, slug, title, is_published FROM pages WHERE slug='about/general-superintendent';"
  # EXPECT: one row with title "General Superintendent - UPCI New Zealand", is_published=1
  ```

### Task 3: UPDATE `resources/js/router/routes.js` — remove the explicit route

- **ACTION**: Delete the 4-line entry.
- **IMPLEMENT**: Find and delete the block matching:
  ```js
  {
      path: '/about/general-superintendent',
      name: 'GeneralSuperintendent',
      component: () => import('../views/about/GeneralSuperintendent.vue')
  },
  ```
- **GOTCHA**: Watch for trailing commas. The routes array uses `}, { ... },` with trailing commas — the standard JS pattern. After deleting, ensure the preceding route still ends with `},` and the next entry starts cleanly.
- **GOTCHA 2**: Do NOT delete any other `/about/*` route. They currently all dispatch to `CmsPage.vue` explicitly — harmless; let them be (also noted in "NOT Building").
- **VALIDATE**:
  ```bash
  # Quick grep — expect NO match after the edit
  grep -c "about/general-superintendent" resources/js/router/routes.js
  # EXPECT: 0
  ```

### Task 4: DELETE `resources/js/views/about/GeneralSuperintendent.vue`

- **ACTION**: Remove the hardcoded component; it's now orphaned.
- **IMPLEMENT**:
  ```bash
  rm resources/js/views/about/GeneralSuperintendent.vue
  ```
- **GOTCHA**: Confirm no other file imports it before deleting:
  ```bash
  grep -rn "GeneralSuperintendent.vue\|about/GeneralSuperintendent" resources/js/ 2>/dev/null
  # EXPECT: no matches after Task 3 (router entry was the only referrer)
  ```
- **VALIDATE**:
  ```bash
  test ! -f resources/js/views/about/GeneralSuperintendent.vue && echo "deleted"
  # EXPECT: "deleted"
  ```

### Task 5: REBUILD the Vite bundle

- **ACTION**: Emit the updated router (no GS entry) and drop the deleted view from the bundle.
- **IMPLEMENT**:
  ```bash
  cd /var/www/personal/upci.co.nz
  npm run build 2>&1 | tail -20
  ```
- **VALIDATE**:
  ```bash
  # Confirm the deleted view doesn't appear in the built bundle
  grep -rl "GeneralSuperintendent" public/build/ 2>/dev/null
  # EXPECT: no matches (the compiled bundle no longer includes it)
  ```
- **GOTCHA**: Build may leave old chunks in `public/build/` from prior builds. If the grep still matches and it's in a stale chunk filename, safe to ignore — Vite manifest points at the new chunk.

---

## Testing Strategy

### No automated tests needed

This is a content port + two small file edits. The existing test suite (`AccessLevelScopingTest` / `PanelAccessGateTest` / `EventAccessPolicyTest` / `ChurchPolicyLocalEditTest`) doesn't cover Vue views or seeders, and there's no infrastructure yet for visual regression. The manual check is the authoritative validator.

### Edge Cases Checklist

- [ ] Re-running the seeder is a no-op (updateOrCreate)
- [ ] Public request to `/about/general-superintendent` renders the content
- [ ] Admin in `/admin/pages` sees the new row, can edit, can save
- [ ] Admin changing the hero heading updates the public page on reload
- [ ] Removing the seeder's content blocks one by one (via Filament) still renders without layout crash — `CmsPage.vue` guards each block with `v-if`/`v-else-if`
- [ ] Toggling `is_published=false` via the Filament form — page should stop showing (behavior per existing pages controller)
- [ ] A non-national user (e.g. local pastor) should NOT see the Pages resource at all — policy (`PagePolicy extends NationalOnlyPolicy`) already enforces this; this plan changes nothing about that

---

## Validation Commands

### Level 1: Static analysis
```bash
cd /var/www/personal/upci.co.nz
vendor/bin/pint --test database/seeders/GeneralSuperintendentPageSeeder.php
```
**EXPECT**: No style violations.

### Level 2: Seeder runs cleanly
```bash
sudo -u www-data php artisan db:seed --class=GeneralSuperintendentPageSeeder --force
sqlite3 upci "SELECT id, slug, is_published, json_array_length(content) as blocks FROM pages WHERE slug='about/general-superintendent';"
```
**EXPECT**: one row, `blocks = 5`.

### Level 3: No regressions in existing tests
```bash
sudo -u www-data php artisan test --filter "AccessLevelScoping|PanelAccessGate|EventAccessPolicy|ChurchPolicyLocalEdit"
```
**EXPECT**: 27/27 pass.

### Level 4: API endpoint serves the row
```bash
curl -sk -H "X-Forwarded-Proto: https" --resolve upci.b8.co.nz:80:127.0.0.1 "http://upci.b8.co.nz/api/pages/about%2Fgeneral-superintendent" | python3 -c "import json, sys; d=json.load(sys.stdin); print('success:', d.get('success')); print('blocks:', len(d.get('data',{}).get('content',[])))"
```
**EXPECT**: `success: True`, `blocks: 5`.

### Level 5: Vite build succeeds
```bash
npm run build 2>&1 | tail -3
```
**EXPECT**: "built in NN.NNs" with no errors.

### Level 6: Browser manual check
1. Visit `https://upci.b8.co.nz/about/general-superintendent` in an incognito tab.
2. **EXPECT**: page renders — hero heading "General Superintendent", then a two-column role + responsibilities section, a Selection Process text block, a Global Impact stats block, and a blue CTA with the Ephesians quote.
3. Log in as `admin@upci.co.nz` at `/admin/login`, navigate to `/admin/pages`.
4. **EXPECT**: a row "General Superintendent - UPCI New Zealand" exists; click Edit.
5. Change the hero `heading` to "General Superintendent (UPCI)", Save.
6. Reload the public page — heading should now read the new text.
7. Restore the original heading via Filament — public page reverts.

---

## Acceptance Criteria

- [ ] `pages` table has one row with `slug = 'about/general-superintendent'`.
- [ ] `GET /api/pages/about/general-superintendent` returns 200 with `data.content` containing 5 blocks.
- [ ] `/about/general-superintendent` in a browser renders the same semantic content as before (hero + two-col + text + stats + CTA).
- [ ] `/admin/pages` lists the new row (for national admin).
- [ ] Editing the hero heading via Filament and saving updates the public page.
- [ ] `resources/js/views/about/GeneralSuperintendent.vue` no longer exists.
- [ ] `grep -n "about/general-superintendent" resources/js/router/routes.js` returns nothing.
- [ ] `npm run build` emits the new bundle cleanly.
- [ ] Existing test suite (27 relevant tests) still passes.

---

## Completion Checklist

- [ ] Task 1 — seeder file created
- [ ] Task 2 — seeder run; DB row present
- [ ] Task 3 — router entry removed
- [ ] Task 4 — hardcoded Vue component deleted
- [ ] Task 5 — `npm run build` succeeds
- [ ] Level 1-5 validation passes
- [ ] Manual browser check (Level 6) confirms editability end-to-end

---

## Risks and Mitigations

| Risk | Likelihood | Impact | Mitigation |
|------|------------|--------|------------|
| Content-shape port loses some text or formatting | LOW | LOW | Port is text-only — copy-paste from the .vue file into seeder strings. Markdown equivalents preserve bold/list formatting via `renderMarkdown` in `CmsPage.vue`. |
| Seeder's `sort_order: 6` collides with another page | LOW | LOW | Filament Pages list sorts on `sort_order`; duplicates just order ambiguously. Non-blocking. Rebase to 7 if it's taken. |
| Browser router cache serves the old route | LOW | LOW | `npm run build` emits new chunk hashes; served bundle is fresh on next request. Users with stale SPA state may need one refresh. |
| `updateOrCreate` wipes manual admin edits if the seeder is re-run later | MED | MED | Document clearly: the seeder is a **first-time install** tool. After an admin has edited via Filament, re-running the seeder overwrites their changes. Add a comment at the top of the seeder warning future operators. |
| Removing the explicit route breaks a deep link from a stale external site | LOW | LOW | The catch-all `/:slug(.*)` route at the bottom of `routes.js` catches exactly this URL, so the deep link still works. |
| Markdown stats block renders differently from the original three-card layout | MED | LOW | Flagged in "NOT Building". If undesired, post-MVP task is to extend `CmsPage.vue` with a `stats` block type or restyle the existing stats render. Current rendering is consistent with other CMS pages. |
| Vite build fails on a missing import somewhere that referenced the deleted view | LOW | HIGH | Task 4 GOTCHA runs a grep to confirm no other file imports the deleted view. Only `routes.js` does — and Task 3 removes it before Task 4 deletes the file. |

---

## Notes

- **Why a seeder, not a manual create-in-Filament.** The seeder is deterministic, version-controllable, and reproduces the page on any new dev env / staging / fresh prod. "Create via UI" leaves no audit trail and can't be replayed. Once in prod, admins will edit live — the seeder exists for provisioning.
- **Why remove the explicit router entry rather than edit it to point at CmsPage.vue.** Editing it to `component: () => import('../views/CmsPage.vue')` would work identically at runtime. Removing it is cleaner: the existing catch-all already handles every CMS slug, and keeping an explicit entry is redundant. That said, sibling routes `/about/upci`, `/about/beliefs`, etc. also have explicit CmsPage.vue entries — a separate housekeeping task could remove those too. Not in scope here.
- **Why delete the .vue file.** 143 lines of dead code on `main`. Easy to delete cleanly.
- **Confidence: 9/10.** Low complexity, every block type already rendered in production for sibling pages, idempotent seeder, manual verification path is simple. The one unknown is whether the stats block renders acceptably compared to the original three-card layout — acknowledged in risks and flagged as a follow-up if needed.
