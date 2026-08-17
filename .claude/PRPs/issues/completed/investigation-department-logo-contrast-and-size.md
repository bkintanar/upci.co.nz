# Investigation: Department logos are small and low-contrast on the hero

**Issue**: free-form report — "the logos per department is so small, and does not look good with the
background. fix it. there are light logos for dark background and dark logos for light background."
**Type**: BUG
**Investigated**: 2026-08-17

### Assessment

| Metric | Value | Reasoning |
|---|---|---|
| **Severity** | **MEDIUM** | Brand presentation is wrong on all six department heroes — the mark is the one thing a department page must get right — but nothing is functionally broken and the pages are readable |
| **Complexity** | **MEDIUM** | One view is at fault, but serving two backgrounds needs a second logo column, a Filament field, an API field, a migration, and a one-off asset transform on 18 supplied SVGs |
| **Confidence** | **HIGH** | Measured the rendered box in a browser, read the SVG source, and rendered every variant side by side on the real hero gradient — the "obvious" fix was disproved by that evidence |

---

## Problem Statement

Every department hero renders the department's **dark-ink** logo on a **dark** coloured gradient,
because `departments` stores a single `logo_path` that is used on both dark and light backgrounds.
The reporter is also right that a full light/dark asset set exists — but the supplied `-WHITE`
variants are **not** drop-in replacements: each contains an opaque black backing rectangle, so
swapping them in would paint a black box onto the coloured hero.

Separately, the logo *looks* small because `-01` is the **stacked** lockup: at the current fixed
128px height the emblem and the wordmark each get roughly half, so neither reads.

---

## Analysis

### Evidence Chain

**WHY** does the logo look bad on the department hero?

↓ **BECAUSE** a dark-ink mark is being drawn on a dark gradient.
Evidence: `resources/js/views/Department.vue:29-34` — the hero image, inside a section whose
background is the department hue ending in `brand-ink`:
```vue
<img
    v-if="departmentLogo"
    :src="departmentLogo"
    :alt="`${department.name} logo`"
    class="h-24 md:h-32 w-auto mx-auto mb-8 drop-shadow-lg"
/>
```

↓ **BECAUSE** there is only one logo per department, and it is the dark-background-unsuitable one.
Evidence: all six rows point at the `-01` (non-WHITE) file:
```
mens       department-logos/UPCINZ-MEN-01.svg
ladies     department-logos/UPCINZ-LADIES-01.svg
missions   department-logos/UPCINZ-MISSIONS-01.svg
youth      department-logos/UPCINZ-YOUTH-01.svg
childrens  department-logos/UPCINZ-CHILDREN-01.svg
prayer     department-logos/UPCINZ-PRAYER-01.svg
```

↓ **BECAUSE** only the dark variant was ever published to the public disk.
Evidence: `storage/app/public/department-logos/` contains **6 files** — one per department, all
`-01.svg`. Meanwhile `resources/images/logos/` contains the **full supplied library**: three
lockups (`01`, `02`, `03`) per department in `.svg`, `.ai` and `.png`, **each with a `-WHITE`
counterpart**. The reporter is correct that both exist; the seeding took one.

↓ **ROOT CAUSE (1)**: the schema models one logo where the design system supplies two.
`departments.logo_path` is a single column consumed by three render sites with two different
background colours.

### 🔴 The obvious fix does not work — and this is the most important finding

Swapping `logo_path` to the `-WHITE.svg` file would put a **black rectangle** on the hero.
Every `-WHITE` variant carries an opaque, unfilled (therefore black) full-canvas backing rect:

```xml
<!-- resources/images/logos/youth/UPCINZ-YOUTH-01-WHITE.svg -->
<rect x="0.5" y="0.5" width="3999" height="4000"/>

<!-- resources/images/logos/youth/UPCINZ-YOUTH-03-WHITE.svg  (03 is 2:1) -->
<rect x="0.5" y="0.5" width="4000" height="2000"/>
```

The dark variants have no such element — their only `<rect>` is a small internal shape:
```xml
<!-- resources/images/logos/youth/UPCINZ-YOUTH-01.svg -->
<rect id="XMLID_2_" x="1628.5" y="1946.5" class="st1" width="1000" height="1000"/>
```

**Verified visually**, not inferred: rendering all variants on the actual youth hero gradient
shows the `-WHITE` files as solid black tiles with white artwork inside. The supplied light
logos are drawn *for black*, not *for transparency*.

### ROOT CAUSE (2) — the size complaint is a lockup problem, not a pixel problem

The rendered box is **128×128 CSS px** on all six pages (measured; `h-24 md:h-32`, natural size
150×150, viewBox `0 0 4000 4000`). That is not small. What is small is the artwork *within* it,
because `-01` is the **stacked** lockup — circular emblem above a two-line wordmark:

| Lockup | Shape | At a fixed 128px height |
|---|---|---|
| `-01` | stacked: emblem over "UPCI NZ / YOUTH MINISTRY" | emblem ≈ half the height, wordmark nearly illegible ← **current** |
| `-02` | emblem only, square | emblem gets the full 128px |
| `-03` | horizontal: emblem + wordmark side by side (2:1) | emblem gets the full height **and** the wordmark is large |

So the fix for "so small" is at least as much *which lockup* as *what height*.

### Affected Files

| File | Lines | Action | Description |
|---|---|---|---|
| `database/migrations/…_add_logo_light_path_to_departments.php` | NEW | CREATE | Second logo column for dark backgrounds |
| `app/Models/Department.php` | `$fillable` | UPDATE | Add `logo_light_path` |
| `app/Filament/Resources/Departments/Schemas/DepartmentForm.php` | — | UPDATE | `FileUpload` — **must** `->disk('public')` |
| `app/Http/Controllers/Api/DepartmentController.php` | 24, 84 | UPDATE | Publish `logo_light_path` on both endpoints |
| `resources/js/views/Department.vue` | 29-34, 162-165 | UPDATE | Prefer the light logo on the dark hero; resize |
| `database/migrations/…_seed_department_light_logos.php` | NEW | CREATE | Publish the background-stripped `-WHITE` files and point the column at them |
| `storage/app/public/department-logos/` | — | CREATE | 6 new background-stripped light SVGs |

**Deliberately NOT changed:** `GetInvolved.vue:73` and `DepartmentListBlock.vue:21` both render
the logo on `bg-white` cards. Dark ink on white is **correct there** and must keep using
`logo_path`.

### Integration Points

- `Department.vue:162-165` — `departmentLogo` computed, falls back to `settings.header_logo_url`
- `DepartmentController.php:24` (index) and `:84` (show) — both must publish the new field
- `Department.vue` hero section uses `heroClasses` from `utils/theme.js` (the brand `dept` hues) —
  every hero is dark, so the light logo applies unconditionally there

### ⚠️ Asset naming is inconsistent — a derivation rule will break

Do **not** implement this as `logo_path.replace('.svg', '-WHITE.svg')`. `missions` does not follow
the pattern:

| Department | Dark (lockup 01) | Light (lockup 01) |
|---|---|---|
| mens | `UPCINZ-MEN-01.svg` | `UPCINZ-MEN-01-WHITE.svg` |
| ladies | `UPCINZ-LADIES-01.svg` | `UPCINZ-LADIES-01-WHITE.svg` |
| youth | `UPCINZ-YOUTH-01.svg` | `UPCINZ-YOUTH-01-WHITE.svg` |
| childrens | `UPCINZ-CHILDREN-01.svg` | `UPCINZ-CHILDREN-01-WHITE.svg` |
| prayer | `UPCINZ-PRAYER-01.svg` | `UPCINZ-PRAYER-01-WHITE.svg` |
| **missions** | `UPCINZ-MISSION**S**-01.svg` (plural) | `UPCINZ-MISSION-01-WHITE.svg` (**singular**) |

`UPCINZ-MISSIONS-01-WHITE.svg` **does not exist**. A string-derivation rule 404s on missions,
and the hero would silently fall back to the site logo. Store the path explicitly.

### Git History

- **Introduced**: `2026_08_17_100003_add_logo_path_to_departments_table.php` added the single
  column; the seeding picked `-01` per department
- **Compounded by**: `dfd5c43` (this session) — respreading the department hues made every hero a
  saturated dark gradient, which sharpened an existing contrast problem
- **Implication**: **original gap, recently made more visible.** The single-column schema could
  never have served both backgrounds; the hue change is what made it obvious

---

## Implementation Plan

### Step 1: Strip the black backing rect from the six light SVGs

**Action**: CREATE six files in `storage/app/public/department-logos/`

For each department, take the lockup-01 `-WHITE.svg` from `resources/images/logos/<dept>/` and
remove the single full-canvas `<rect>` — the one whose width/height match the viewBox
(`3999`/`4000` × `4000`, or `4000` × `2000` for the 2:1 lockups). Save as
`UPCINZ-<DEPT>-01-WHITE-TRANSPARENT.svg`.

Source files (note missions):
```
mens       resources/images/logos/mens/UPCINZ-MEN-01-WHITE.svg
ladies     resources/images/logos/ladies/UPCINZ-LADIES-01-WHITE.svg
missions   resources/images/logos/missions/UPCINZ-MISSION-01-WHITE.svg   ← singular
youth      resources/images/logos/youth/UPCINZ-YOUTH-01-WHITE.svg
childrens  resources/images/logos/childrens/UPCINZ-CHILDREN-01-WHITE.svg
prayer     resources/images/logos/prayer/UPCINZ-PRAYER-01-WHITE.svg
```

**Why**: the supplied light logos are drawn for a black plate. Stripping the plate is what turns
them into usable overlays, and it preserves the red `#B43F38` accent that a CSS
`filter: brightness(0) invert(1)` would destroy.

**GOTCHA**: remove **only** the full-canvas rect. Verify afterwards that the artwork still renders
(open each file) — a wrong deletion silently removes part of the mark.

**VALIDATE**: each output file renders white-on-transparent over a coloured background.

---

### Step 2: Add the column

**File**: `database/migrations/2026_08_18_130000_add_logo_light_path_to_departments.php` · CREATE

```php
Schema::table('departments', function (Blueprint $table) {
    $table->string('logo_light_path')->nullable()->after('logo_path');
});
```

Mirror the comment style of `2026_08_17_100003_add_logo_path_to_departments_table.php`. Nullable
forever: a department without a light variant must fall back, not break.

**Why a second column rather than a naming convention**: see the missions row above.

---

### Step 3: Model, Filament, API

- `app/Models/Department.php` — add `'logo_light_path'` to `$fillable`
- `DepartmentForm.php` — add the upload beside the existing logo field:
  ```php
  FileUpload::make('logo_light_path')
      ->label('Logo (for dark backgrounds)')
      ->image()
      ->disk('public')          // FILESYSTEM_DISK is `local`; without this it 404s
      ->directory('department-logos')
      ->helperText('A light/white version of the mark, used on the department hero. Leave empty to fall back to the main logo.')
  ```
  **MIRROR**: `EventForm.php` — the `image_path` upload added in `2427213`, same disk gotcha
- `DepartmentController.php:24` and `:84` — add `'logo_light_path' => $d->logo_light_path,`

---

### Step 4: Use it on the hero, and fix the size

**File**: `resources/js/views/Department.vue`

**Current** (`:162-165`):
```js
const departmentLogo = computed(() => {
    if (department.value?.logo_path) return imageUrl(department.value.logo_path)
    if (settings.value?.header_logo_url) return settings.value.header_logo_url
```

**Required change** — prefer the light variant, because this hero is always dark:
```js
// The hero is always a dark department hue ending in brand-ink, so the light
// mark is the correct one here. logo_path stays the fallback: a department
// with no light variant still shows its mark rather than nothing.
// The two card render sites (GetInvolved, DepartmentListBlock) sit on white
// and deliberately keep using logo_path.
const departmentLogo = computed(() => {
    if (department.value?.logo_light_path) return imageUrl(department.value.logo_light_path)
    if (department.value?.logo_path) return imageUrl(department.value.logo_path)
    if (settings.value?.header_logo_url) return settings.value.header_logo_url
```

**Current** (`:33`):
```
class="h-24 md:h-32 w-auto mx-auto mb-8 drop-shadow-lg"
```
**Required change** — larger, and `drop-shadow-lg` removed (it is there to rescue contrast the
right asset now provides):
```
class="h-32 md:h-44 w-auto mx-auto mb-8"
```

⛔ **This height, and the lockup choice below, need the client's pick before implementing** — see
Open Decision.

---

### Step 5: Seed the six paths

**File**: `database/migrations/2026_08_18_140000_seed_department_light_logos.php` · CREATE

Map slug → stripped filename **explicitly** (no string derivation), guarded so it only fills a
column that is still null:

```php
$map = [
    'mens' => 'department-logos/UPCINZ-MEN-01-WHITE-TRANSPARENT.svg',
    // …
];
foreach ($map as $slug => $path) {
    Department::where('slug', $slug)->whereNull('logo_light_path')->update(['logo_light_path' => $path]);
}
```

**MIRROR**: the guard pattern in `2026_08_18_100000_author_the_search_led_homepage.php` — a CMS
edit by a person must beat a migration replay.

---

## Patterns to Follow

**FileUpload with an explicit disk (the gotcha this codebase has hit twice):**
```php
// SOURCE: app/Filament/Resources/Events/Schemas/EventForm.php (commit 2427213)
// ->disk('public') is not optional here. FILESYSTEM_DISK is `local` in this
// project's .env, so a FileUpload that does not name its disk writes into
// private storage and the image 404s on the public site.
FileUpload::make('image_path')
    ->image()
    ->disk('public')
    ->directory('events')
```

**Fallback chain that degrades rather than breaks:**
```js
// SOURCE: resources/js/views/Department.vue:162-165
if (department.value?.logo_path) return imageUrl(department.value.logo_path)
if (settings.value?.header_logo_url) return settings.value.header_logo_url
```

**Guarded content migration:**
```php
// SOURCE: database/migrations/2026_08_18_100000_author_the_search_led_homepage.php
// Content has an editor, and the editor wins — only write what is still unset.
```

---

## ⛔ Open Decision — which lockup, and how big

This is a visual choice and the client has rejected five design changes that landed without
review. **Do not pick this unilaterally.** A comparison of all three lockups on the real hero
gradient has been rendered at `/tmp/claude-0/logo-compare.png`.

| Option | Effect at the same height |
|---|---|
| **`-01` stacked** (current lockup, light variant) | Emblem ≈ half the height, wordmark small. Safest — no layout change, just correct colour |
| **`-03` horizontal** (recommended) | Emblem gets the full height **and** the wordmark is large and legible. Biggest visible gain, but it is a wide 2:1 mark above an `h1` that repeats the department name |
| **`-02` emblem only** | Cleanest and largest emblem; the `h1` underneath already supplies the name, so the wordmark is arguably redundant |

**Recommendation: `-02` or `-03`.** The hero's `h1` already reads "Youth Ministry" directly under
the logo, so the `-01` wordmark repeats it at an illegible size — which is exactly why it reads as
"small".

---

## Edge Cases & Risks

| Risk / Edge case | Mitigation |
|---|---|
| **Stripping the wrong `<rect>`** silently deletes artwork | Remove only the full-canvas rect; open all six outputs and compare against the `-WHITE` original before seeding |
| **`missions` naming** — `MISSIONS-01.svg` vs `MISSION-01-WHITE.svg` | Explicit slug→path map, never string derivation. `UPCINZ-MISSIONS-01-WHITE.svg` does not exist |
| **The two white-card sites regress** if they pick up the light logo | They must keep reading `logo_path`. Verify `/departments` and any `department_list` block after the change |
| `FileUpload` without `->disk('public')` | Explicit in Step 3; this has already shipped as a defect once |
| A department with no light variant | Fallback chain keeps the dark logo — visibly imperfect but never blank |
| SVG `<rect>` with no `fill` attribute | Defaults to **black** in SVG, which is why these read as black plates. Do not assume "no fill" means transparent |
| Larger logo pushes the `h1` below the fold on mobile | Check 390px after resizing; the hero has `py-20 lg:py-28` |

---

## Validation

### Automated

```bash
./vendor/bin/pint --test --dirty
npm run build
php artisan test               # expect 117 passed / 16 pre-existing failures
php artisan migrate:rollback --step=2 && php artisan migrate   # reversible
```

### Browser — the only way this bug is visible

```bash
cd /tmp/claude-0 && node logo.mjs     # measures the rendered logo box on all six departments
```

- [ ] All six heroes show a **light** mark with no black rectangle
- [ ] The red `#B43F38` accent survives (proves a filter hack was not used)
- [ ] `/departments` cards and any `department_list` block still show the **dark** mark on white
- [ ] No horizontal overflow at 390px; the `h1` is still visible without scrolling
- [ ] A department with `logo_light_path = null` falls back to its dark logo, not to nothing

---

## Scope Boundaries

**IN SCOPE**
- A second, CMS-editable light logo per department
- Background-stripped light SVGs on the public disk
- The department hero using the light variant, at a larger size

**OUT OF SCOPE — do not touch**
- `GetInvolved.vue` and `DepartmentListBlock.vue` logo sources — dark on white is correct
- The stock colour tokens and legacy chrome in those files — owned by
  `brand-consistency-follow-ups.plan.md`
- Region logos (`Region.vue:28`, `Regions.vue:37`, `RegionListBlock.vue:21`) — `regions.logo_path`
  is **null for all three** and has no supplied asset set; a separate problem
- The site header/footer logos — already deliberately split into nav and footer lockups
- The `.ai` sources and the `.png` alternates (`UPCINZ-YM-*`, `UPCINZ-CM-*` …) — the SVGs are what
  the site should serve
- Converting the hero to a light background — the dark hue treatment is client-approved

---

## Metadata

- **Investigated by**: Claude
- **Timestamp**: 2026-08-17
- **Artifact**: `.claude/PRPs/issues/investigation-department-logo-contrast-and-size.md`
- **Evidence image**: `/tmp/claude-0/logo-compare.png` (all variants on the real hero gradient)
- **GitHub**: not posted — free-form report, no issue number
