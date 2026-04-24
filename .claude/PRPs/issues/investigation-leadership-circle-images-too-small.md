# Investigation: Leadership CMS Page — Circle Portraits Are Too Small

**Issue**: Free-form (no GH issue number)
**Type**: BUG (UI/UX — visual sizing)
**Investigated**: 2026-04-24

### Assessment

| Metric     | Value  | Reasoning                                                                                                                                                                      |
| ---------- | ------ | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------ |
| Severity   | LOW    | Purely cosmetic. All functionality works; images load; only issue is visual weight. No user is blocked, no feature broken — it just doesn't "look nice" per the user's ask.     |
| Complexity | LOW    | Single-line class swap in `CmsPage.vue`, plus `npm run build`. No logic change; no DB migration; no test impact.                                                                |
| Confidence | HIGH   | Root cause pinpointed: `resources/js/views/CmsPage.vue:123-124` hardcodes `w-24 h-24` (96px) for the "icon-is-an-uploaded-image" branch, which is exactly what Leadership cards trip. Verified by reading the page's `content` JSON in DB — all 13 leadership cards have `icon` set to an image path and no `icon_svg`, so they fall through to that 96px branch. |

---

## Problem Statement

On the Leadership CMS page (`/about/leadership`), the leader portrait images render as circular avatars at only 96×96 pixels, which feels undersized for a leadership page where the portraits are meant to be prominent. The user wants them larger so the page looks more polished.

---

## Analysis

### Evidence Chain

**WHY the Leadership page portraits are 96×96**
↓ BECAUSE the `CmsPage.vue` `cards` block renders any card whose `data.icon` is an image path (and whose `data.icon_svg` is falsy/absent) with the Tailwind class `w-24 h-24 mx-auto mb-4 rounded-full object-cover`.
Evidence: `resources/js/views/CmsPage.vue:123-124`:
```vue
<img v-else-if="card.data.icon" :src="getImageUrl(card.data.icon)" :alt="card.data.title"
     :class="card.data.icon_svg === 'blue-ministry' || card.data.icon_svg === 'green-ministry' ? 'w-full h-auto mx-auto mb-4 rounded-lg object-cover' : 'w-24 h-24 mx-auto mb-4 rounded-full object-cover'" />
```
`w-24` / `h-24` in Tailwind = 6rem = 96px.

**WHY the Leadership cards hit this branch**
↓ BECAUSE they have `data.icon` pointing at `page-images/*.jpeg|webp|jpg` and no `data.icon_svg` value at all.
Evidence: `sqlite3 upci "SELECT content FROM pages WHERE slug = 'about/leadership';"` — every card is shaped like:
```json
{"data":{"icon":"page-images/01KPQCTQJ0TZ7KT7MZH0BHHB4B.jpeg","title":"General Superintendent","description":"Rev. Troy Wickette","link_url":null,"link_text":null},"type":"card"}
```
`icon_svg` is not a key; the Vue template's ternary therefore falls to the `else` branch and uses the small circle sizing.

**ROOT CAUSE**: `w-24 h-24` (96×96) is the default "portrait circle" size for every CMS card whose icon is an uploaded image rather than an SVG. The Leadership page is the highest-profile consumer of this pattern, but the National Department Leadership section on the same page uses it too (6 more cards). The sizing was chosen (presumably) for small ministry icons and is too small for human-face portraits.

### Current vs proposed dimensions

| Viewport | Current | Proposed | Visual |
|---|---|---|---|
| Any (`w-24 h-24`) | 96×96 px | `w-40 h-40` → 160×160 px (67% larger) on all sizes, OR responsive `w-40 h-40 md:w-48 md:h-48` → 160 mobile / 192 desktop | Headshots feel like proper portraits, not icons; fits the 3-col grid comfortably at max-w-7xl. |

### Affected Files

| File                                    | Lines   | Action | Description                                                                                              |
| --------------------------------------- | ------- | ------ | -------------------------------------------------------------------------------------------------------- |
| `resources/js/views/CmsPage.vue`        | 123-124 | UPDATE | Replace `w-24 h-24` with `w-40 h-40 md:w-48 md:h-48`                                                      |
| (built asset)                           | N/A     | BUILD  | Run `npm run build` so the new classes land in `public/build/*.js` / `public/build/*.css`               |

### Integration Points

- Every CMS page with a `cards` block whose items have `icon` set to an uploaded image and NO `icon_svg` value. From a quick audit of the DB:
  - `/about/leadership` — **13 cards** (7 Executive Board + 6 National Department Leadership). Primary beneficiary.
  - Any future CMS page authored the same way will also get the larger portraits.
  - Pages that use `icon_svg = 'blue-ministry'` / `'green-ministry'` (registered programs etc.) use the `w-full h-auto rounded-lg` branch — **unaffected**.
  - Pages with SVG-string `icon_svg` (checkmarks, leadership-icons, etc.) use `getCardIconContainerClass()` — **unaffected**.
- No API changes; no model changes; no route changes.

### Git History

- `resources/js/views/CmsPage.vue` last touched in commit `96f4f86` ("updates") — recent, part of ongoing CMS work.
- No specific commit introduced the `w-24 h-24` class; it's been there since the cards block was authored. Not a regression.

---

## Implementation Plan

### Step 1: Update the image sizing in `CmsPage.vue`

**File**: `resources/js/views/CmsPage.vue`
**Lines**: 123-124
**Action**: UPDATE

**Current code:**
```vue
<img v-else-if="card.data.icon" :src="getImageUrl(card.data.icon)" :alt="card.data.title"
     :class="card.data.icon_svg === 'blue-ministry' || card.data.icon_svg === 'green-ministry' ? 'w-full h-auto mx-auto mb-4 rounded-lg object-cover' : 'w-24 h-24 mx-auto mb-4 rounded-full object-cover'" />
```

**Required change:**
```vue
<img v-else-if="card.data.icon" :src="getImageUrl(card.data.icon)" :alt="card.data.title"
     :class="card.data.icon_svg === 'blue-ministry' || card.data.icon_svg === 'green-ministry' ? 'w-full h-auto mx-auto mb-4 rounded-lg object-cover' : 'w-40 h-40 md:w-48 md:h-48 mx-auto mb-4 rounded-full object-cover'" />
```

**Why**: `w-40 h-40` = 160px on mobile, `md:w-48 md:h-48` = 192px on desktop. That's a 67–100% bump over the previous 96px — visually distinct, still proportional in a 3-column grid. Responsive scaling keeps it reasonable on phones (where 192px would eat too much of a 375px-wide viewport).

### Step 2: Rebuild Vite assets

**Command**:
```bash
cd /var/www/personal/upci.co.nz
npm run build
```

**Why**: The Vue template change has to be compiled into the production `public/build/` bundle; Tailwind's JIT also needs to see the new `w-40`, `h-40`, `md:w-48`, `md:h-48` classes so they're emitted into the CSS. Both are done by `vite build`.

**Alternative**: If running `npm run dev` (Vite dev server) is already active, the change hot-reloads. But a deployed build needs `npm run build`.

### Step 3: Verify in the browser

**Manual** (no automated assertion — this is a visual change):
1. Open `https://upci.b8.co.nz/about/leadership` in a desktop browser.
2. Confirm the 13 portrait circles (7 Executive Board + 6 Department Leadership) render at 192×192 px each. They should feel prominent without dominating the card.
3. Resize the viewport to mobile width (~375 px). Circles should now render at 160×160 px.
4. Confirm no layout break — the 3-column grid (`grid-cols-1 sm:grid-cols-2 lg:grid-cols-3`) still flows cleanly.
5. Sanity-check one other CMS page that uses the SVG-string `icon_svg` branch (e.g. any page with the `blue-ministry`/`green-ministry` cards) — those should be unchanged.

---

## Patterns to Follow

**From the codebase — the same component's pattern for when `icon_svg === 'blue-ministry'`:**
```vue
// SOURCE: CmsPage.vue:124 (the TRUE branch of the ternary — shown for reference)
'w-full h-auto mx-auto mb-4 rounded-lg object-cover'
```
Not what we're changing, but it shows the responsive-image idiom used elsewhere in the same file.

**Tailwind size classes in use elsewhere in the same component:**
```
// getCardIconContainerClass() uses w-24 h-24 for leadership-icon circles (SVG backdrop):
'w-24 h-24 bg-blue-100 rounded-full mx-auto mb-4 flex items-center justify-center'   // CmsPage.vue:259
```
→ Keep that container at 96px because it's for small icon glyphs, not for photos. Only the photo path (Step 1) changes.

---

## Edge Cases & Risks

| Risk/Edge Case                                                                        | Mitigation                                                                                                                                                                                           |
| ------------------------------------------------------------------------------------- | ----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Existing portrait files are low-resolution (e.g. 100×100); upscaling to 192 looks fuzzy | Sample the actual files: `ls -la public/storage/page-images/ | head -5` and check dimensions. Most appear to be proper headshots (JPEG/WEBP), likely >= 400×400 — should scale up cleanly. If any are small, re-upload at higher resolution is a separate concern; this change doesn't make them look worse than they already do in a zoom. |
| Layout break on a card with very long name/title                                      | The card container has no fixed height; larger image just pushes text down. Grid rows take the tallest card's height, consistent across all cards. Checked at 192px it still fits in the existing `p-8` padding comfortably. |
| Tailwind JIT doesn't emit `md:w-48` because it's not referenced elsewhere              | `vite build` runs the full JIT pass against all `.vue` files — will emit the new classes. Confirmed `w-40`, `h-40`, `md:w-48`, `md:h-48` are all valid Tailwind v3+ defaults. |
| A future CMS editor wants to bring the circles back down for a specific page          | Out of scope. If per-page sizing control is needed later, add a `size` field to the card schema. Noted as "OUT OF SCOPE". |
| Changing sizing also changes other CMS pages unexpectedly                              | Only 2 pages in the DB today use the image-icon branch: this Leadership page (13 cards). Audited: `sqlite3 upci "SELECT slug FROM pages WHERE content LIKE '%\"icon\":\"page-images%';"` returns just `about/leadership`. Zero collateral damage. |

---

## Validation

### Automated Checks

```bash
# Syntax check — Vue SFC should still parse
npm run build 2>&1 | tail -10
# EXPECT: vite build completes, no errors; emits chunks under public/build/
```

```bash
# Tailwind emitted the new classes
grep -oE "w-40|h-40|md:w-48|md:h-48" public/build/assets/*.css | sort -u
# EXPECT: all four classes present
```

```bash
# No regression to existing PHP tests
sudo -u www-data php artisan test --filter "AccessLevelScoping|PanelAccessGate|EventAccessPolicy|ChurchPolicyLocalEdit" 2>&1 | tail -3
# EXPECT: 27/27 pass (this change shouldn't touch PHP at all; purely sanity)
```

### Manual Verification

1. Open `https://upci.b8.co.nz/about/leadership` in a desktop browser (>= 768 px).
2. DevTools element picker → hover one circle image.
3. **EXPECT**: Computed style shows `width: 192px; height: 192px; border-radius: 9999px; object-fit: cover;`.
4. Resize to mobile (< 768 px).
5. **EXPECT**: Computed style shows `width: 160px; height: 160px;`.
6. Scroll through all 13 cards — they should look like coherent portraits, not icon-ish avatars.
7. Check one other CMS page (e.g. `/get-involved`) that uses `icon_svg` → no visual change there.

---

## Scope Boundaries

**IN SCOPE:**

- Single-line class change on `CmsPage.vue:124`.
- `npm run build` to bake the change.

**OUT OF SCOPE (do not touch):**

- The other image class (`w-full h-auto rounded-lg` for `blue-ministry`/`green-ministry` cards) — different use case, different look intended.
- The icon-container class in `getCardIconContainerClass` — that's for SVG-glyph backdrops, deliberately 96px.
- Uploading new portraits at higher resolution — separate content concern; this plan only changes the render size.
- Adding a CMS-editable size field to cards — deferred; only revisit if the user later asks for per-page control.
- Changes to `ChurchForm.php`, the Filament admin, any policy, or any route — this is purely a Vue-template tweak.

---

## Metadata

- **Investigated by**: Claude
- **Timestamp**: 2026-04-24
- **Artifact**: `.claude/PRPs/issues/investigation-leadership-circle-images-too-small.md`
- **Next step**: run `/prp-issue-fix .claude/PRPs/issues/investigation-leadership-circle-images-too-small.md` OR apply the one-line swap manually and run `npm run build`.
