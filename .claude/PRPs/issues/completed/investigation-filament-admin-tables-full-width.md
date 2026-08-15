# Investigation: Filament Admin Panel — Tables Constrained by Left/Right Padding

**Issue**: Free-form (no GH issue number)
**Type**: ENHANCEMENT (UI polish)
**Investigated**: 2026-04-24

### Assessment

| Metric     | Value  | Reasoning                                                                                                                                                                                     |
| ---------- | ------ | --------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Priority   | MEDIUM | Visual / usability — not blocking. Data density is the main win: tables with many columns (Churches, Users, Attendances) feel cramped at 1280px on wide monitors. Admins use these daily.  |
| Complexity | LOW    | Single-line addition to `AdminPanelProvider::panel()`. No migrations, no tests, no breaking changes to any resource. Panel-wide config.                                                    |
| Confidence | HIGH   | Root cause pinpointed in Filament internals: `vendor/filament/filament/resources/views/components/layout/index.blade.php:12` defaults to `Width::SevenExtraLarge` (= `max-w-7xl` = 1280px) when the panel doesn't override it. This app's `AdminPanelProvider` does not call `->maxContentWidth(...)`, so it falls through to that default. |

---

## Problem Statement

Every page in the Filament admin panel (`/admin/*`) is wrapped in a container that caps content at ~1280 px (`max-w-7xl`), leaving large empty bands of left/right padding on wider monitors. For list/table pages — Churches, Users, Attendances, Events, etc. — this wastes viewport width and makes horizontally-dense tables feel cramped. The user wants tables (and by extension every admin page) to stretch the full viewport.

---

## Analysis

### Root Cause / Change Rationale

**Filament v4's layout picks `Width::SevenExtraLarge` as its default max content width**, and the current `AdminPanelProvider` doesn't override it.

### Evidence Chain

**WHY admin pages are capped at ~1280 px**
↓ BECAUSE the layout template does:
```blade
// vendor/filament/filament/resources/views/components/layout/index.blade.php:12
$maxContentWidth ??= (filament()->getMaxContentWidth() ?? Width::SevenExtraLarge);
```
If `getMaxContentWidth()` returns `null`, the default `Width::SevenExtraLarge` = the string `'7xl'` = Tailwind `max-w-7xl` = 80rem = 1280 px is used to set the inner container's max width.

**WHY `getMaxContentWidth()` returns `null` for this panel**
↓ BECAUSE `AdminPanelProvider::panel()` never calls `->maxContentWidth(...)`.
Evidence: `app/Providers/Filament/AdminPanelProvider.php:29-65` — the fluent chain on the `Panel` goes `->default() → ->id('admin') → ->path('admin') → ->login() → ->passwordReset() → ->colors([...]) → ->font('Inter') → ->brandName(...) → ->discoverResources(...) → ->pages([...]) → ->discoverWidgets(...) → ->widgets([...]) → ->middleware([...]) → ->authMiddleware([...])`. No `maxContentWidth` call anywhere.
`HasMaxContentWidth` trait confirms the stored value defaults to `null`:
```php
// vendor/filament/filament/src/Panel/Concerns/HasMaxContentWidth.php:9
protected Width | string | null $maxContentWidth = null;
```

**WHY Filament exposes `Width::Full` as an opt-in**
↓ BECAUSE the `Filament\Support\Enums\Width` enum ships `Full`, `Max`/`MaxContent`, and many sized variants — `Width::Full` maps to `max-w-full` = no horizontal constraint. Switching the default to `Full` is Filament's documented way to opt into edge-to-edge layout.
Evidence: `vendor/filament/support/src/Enums/Width.php:22` — `case Full = 'full';`.

**ROOT CAUSE**: `AdminPanelProvider` needs one line: `->maxContentWidth(Width::Full)` inside `panel(Panel $panel)`. That's it.

### Affected Files

| File                                              | Lines   | Action | Description                                                                                     |
| ------------------------------------------------- | ------- | ------ | ----------------------------------------------------------------------------------------------- |
| `app/Providers/Filament/AdminPanelProvider.php`   | 3-21    | UPDATE | Add `use Filament\Support\Enums\Width;` to the imports                                          |
| `app/Providers/Filament/AdminPanelProvider.php`   | 31-64   | UPDATE | Insert `->maxContentWidth(Width::Full)` into the fluent chain (suggested position: after `->brandName(...)`, before `->discoverResources(...)`) |

### Integration Points

- Every Filament page — List / View / Edit / Create for every resource, Dashboard, and any custom Pages. One config line flips them all at once.
- The sidebar stays `20rem` (confirmed at `vendor/filament/filament/src/Panel/Concerns/HasSidebar.php:11`); content area takes the rest of the viewport after sidebar + default Filament gutters.
- No Tailwind build needed — these classes ship in Filament's prebuilt CSS (`public/css/filament/filament/app.css`).

### Git History

- `app/Providers/Filament/AdminPanelProvider.php` was touched in `2beaf66` ("10 March 2026") and earlier WIP. No commit set `maxContentWidth`, so this is an original omission — not a regression.

### What the user will actually see

- **Before**: on a 1920×1080 or 2560×1440 monitor, tables and forms sit in a center-aligned 1280 px column with ~200–600 px of empty background on each side.
- **After**: the content area fills the space between the 320 px sidebar and the right viewport edge. Filament's inner page padding (Tailwind `px-4 sm:px-6 lg:px-8` from the page wrapper) still provides breathing room — tables won't hug the literal viewport edge.

---

## Implementation Plan

### Step 1: Import the `Width` enum in `AdminPanelProvider`

**File**: `app/Providers/Filament/AdminPanelProvider.php`
**Lines**: 3-20 (imports block)
**Action**: UPDATE

**Current imports:**
```php
namespace App\Providers\Filament;

use Filament\Panel;
use Filament\PanelProvider;
use Filament\Pages\Dashboard;
use Filament\Support\Colors\Color;
use Filament\Widgets\AccountWidget;
use Filament\Widgets\FilamentInfoWidget;
use Filament\Http\Middleware\Authenticate;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Filament\Http\Middleware\AuthenticateSession;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
```

**Required change** — add one `use` line; let Pint re-order imports:
```php
use Filament\Support\Enums\Width;
```

### Step 2: Add `->maxContentWidth(Width::Full)` to the panel chain

**File**: `app/Providers/Filament/AdminPanelProvider.php`
**Lines**: 31-40 (fluent-chain on `$panel`)
**Action**: UPDATE

**Current chain (after login gate plan was applied):**
```php
return $panel
    ->default()
    ->id('admin')
    ->path('admin')
    ->login()
    ->passwordReset()
    ->colors([
        'primary' => Color::Blue,
    ])
    ->font('Inter')
    ->brandName('UPCI New Zealand')
    ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
```

**Required change** — insert `->maxContentWidth(Width::Full)` between `->brandName(...)` and `->discoverResources(...)`:
```php
return $panel
    ->default()
    ->id('admin')
    ->path('admin')
    ->login()
    ->passwordReset()
    ->colors([
        'primary' => Color::Blue,
    ])
    ->font('Inter')
    ->brandName('UPCI New Zealand')
    ->maxContentWidth(Width::Full)
    ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
```

**Why**: `Width::Full` maps to the string `'full'` → Tailwind `max-w-full` → no horizontal cap. Filament's layout (`components/layout/index.blade.php:12`) reads `getMaxContentWidth()` and injects the matching Tailwind class on the inner container. Everything downstream just works.

### Step 3: Clear caches

**Commands:**
```bash
cd /var/www/personal/upci.co.nz
sudo -u www-data php artisan config:clear
sudo -u www-data php artisan view:clear
```

**Why**: `PanelProvider` is a service provider; its config can be cached via `php artisan config:cache`. Blade view cache also holds the compiled layout. Clear both so the new chain value takes effect immediately on the next request.

### Step 4 (optional polish): Revert simple pages to default

If the login page or any Filament "simple" page (narrow, centered card) ends up looking bad when the full-width setting spills into it, opt those pages back in to a smaller max via `->simplePageMaxContentWidth(Width::Large)`. This is guarded by the separate `$simplePageMaxContentWidth` field (line 11 of the trait) — defaults to `null`, i.e. uses the narrow Filament default which is unaffected by `maxContentWidth(Full)` since simple pages have their own sizing. So **this step is likely unnecessary**; included only as a fallback if you see the login page go full-width and dislike it.

---

## Patterns to Follow

**From Filament v4 docs & vendor internals — exact API:**
```php
// SOURCE: vendor/filament/filament/src/Panel/Concerns/HasMaxContentWidth.php:13-18
public function maxContentWidth(Width | string | null $maxContentWidth): static
{
    $this->maxContentWidth = $maxContentWidth;

    return $this;
}
```

**Width enum cases (choose one):**
```php
// SOURCE: vendor/filament/support/src/Enums/Width.php
case Full = 'full';          // max-w-full — what we want
case SevenExtraLarge = '7xl'; // Filament's silent default
case MaxContent = 'max';      // max-w-max — fits content
// ... many intermediate sizes available if Full feels too wide
```

**Existing import-block pattern in the same file** (for placing the new `use Filament\Support\Enums\Width;`):
```php
// SOURCE: app/Providers/Filament/AdminPanelProvider.php:5-8 — Filament imports are grouped together
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Pages\Dashboard;
use Filament\Support\Colors\Color;
```
Pint will sort the new import alongside these.

---

## Edge Cases & Risks

| Risk/Edge Case                                                        | Mitigation                                                                                                                                                                                                        |
| --------------------------------------------------------------------- | ---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Forms with narrow single-column layouts look stretched / sparse       | Filament forms use their own inner grid constraints. Review one form (e.g. Churches edit) visually. If specific forms look sparse, set `->maxContentWidth(Width::Full)` panel-wide but override per page via `Page::getMaxContentWidth()` returning a narrower value. Not needed in this plan. |
| Very long URL/text columns in a full-width table wrap awkwardly       | Filament table columns have their own widths. Wider viewport just gives each column more space — usually an improvement. If a specific column (e.g. church address) still wraps, that's a column-level `->wrap()` decision, out of scope. |
| Existing max-w-7xl assumption in custom Vue / Blade components        | Confirmed no custom Filament layouts in this repo — `AdminPanelProvider` uses the default layout. Safe.                                                                                                          |
| Dashboard widgets (AccountWidget, FilamentInfoWidget) look wrong full-width | Filament widgets use the widget grid, not raw width. They'll stretch nicely. Default Filament widget card styling has internal padding.                                                                         |
| Simple/login page goes full-width (ugly)                              | `simplePageMaxContentWidth` is a separate field — simple pages keep their narrow default. Verify on login. If it does go full-width, add `->simplePageMaxContentWidth(Width::Large)` (Step 4).                |
| The user later decides 1280px was better for some pages                | One-line revert: remove `->maxContentWidth(Width::Full)`. No data migration, no rollback complexity.                                                                                                             |

---

## Validation

### Automated Checks

```bash
# Syntax
php -l app/Providers/Filament/AdminPanelProvider.php

# Lint
vendor/bin/pint --test app/Providers/Filament/AdminPanelProvider.php

# No regression on existing tests (no tests touch the provider directly; sanity)
sudo -u www-data php artisan test --filter "AccessLevelScoping|PanelAccessGate|EventAccessPolicy|ChurchPolicyLocalEdit" 2>&1 | tail -3
# EXPECT: 27/27 pass

# Programmatic confirmation the setting is picked up
sudo -u www-data HOME=/tmp php -r "
require 'vendor/autoload.php';
\$app = require 'bootstrap/app.php';
\$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
\$panel = Filament\Facades\Filament::getPanel('admin');
\$w = \$panel->getMaxContentWidth();
echo 'max content width: ' . (is_object(\$w) ? \$w->value : var_export(\$w, true)) . PHP_EOL;
"
# EXPECT: max content width: full
```

### Manual Verification

1. Clear caches (Step 3).
2. Log in as `admin@upci.co.nz` and open `/admin/churches` on a wide monitor (>= 1600 px).
3. **EXPECT**: the table stretches from the sidebar's right edge to within a small gutter of the right viewport edge. No 200+ px empty band on the right.
4. Open `/admin/users` and `/admin/attendances` — same.
5. Open a create/edit page (e.g. `/admin/churches/2/edit`) — form fields stretch but still respect their internal grid.
6. Briefly log out → hit `/admin/login`. Login card should still look normal (narrow card centered on page). If it went full-width ugly, apply Step 4.

---

## Scope Boundaries

**IN SCOPE:**

- Single import + single fluent-chain insertion in `AdminPanelProvider.php`
- Cache clears after the edit
- Optional tightening for simple pages only if login looks bad

**OUT OF SCOPE (do not touch):**

- Individual resource-level max-width overrides (e.g. if a specific resource should stay narrow, that's per-page work)
- Column widths / `->wrap()` / table density settings — different concern
- Custom admin themes, custom CSS, Tailwind tweaks — not needed; Filament ships the classes already
- Vue frontend (`resources/js/`) — this is backend-admin only
- Any policy, migration, or model change
- Adding a per-user "compact / full width" toggle — over-engineering for a config decision

---

## Metadata

- **Investigated by**: Claude
- **Timestamp**: 2026-04-24
- **Artifact**: `.claude/PRPs/issues/investigation-filament-admin-tables-full-width.md`
- **Next step**: run `/prp-issue-fix .claude/PRPs/issues/investigation-filament-admin-tables-full-width.md` OR apply the two one-line edits manually and clear caches.
