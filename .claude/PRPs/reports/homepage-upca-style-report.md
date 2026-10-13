# Implementation Report

**Plan**: `.claude/PRPs/plans/homepage-upca-style.plan.md` (archived to `.claude/PRPs/plans/completed/`)
**Branch**: `feature/homepage-upca-style`
**Date**: 2026-10-14
**Status**: COMPLETE. The live database was migrated on 2026-10-14 with the owner's OK, after a backup to `/var/www/personal/upci.co.nz-backups/pre-homepage-migrate-20261014-123255/` (git tag `pre-homepage-migrate-20261014-123255`).

---

## Summary

The homepage is rebuilt to direction **D2, "The federation"**, chosen by the owner, with **D1's navbar** and **no
department-logo strip**. The decision and its class contract are in `.claude/design/homepage-upca/direction-approved.md`.

The page runs top to bottom as:

1. The Pentecost Everyday banner, shown whole.
2. A "Next" band for the AGC (24–25 October, Wellington).
3. About, with a photo slot. Until a photo is uploaded it shows a designed panel: a collage of NZ places with a caption.
4. Find a church, with live region counts and a help box, next to all ten congregations listed north to south.
5. Event cards in a sideways scroll row: date-led tiles in each organiser's colour, with an "On now · day X of Y" flag.
6. The six ministries as colour tiles, each with its next date from the calendar.
7. A Connect panel.

The navbar (green bar, pill log-in) and the footer (department-colour ribbon, chevron links, contact column with the
ministers' log-in and the new postal address) change on every page.

Everything is CMS content. Each new key is optional, and a missing key renders as before, so the other 16 hero
pages and 11 cta pages are unaffected.

## Assessment vs Reality

| Metric | Predicted | Actual | Reasoning |
|---|---|---|---|
| Complexity | HIGH | HIGH | As planned, plus the D2 additions below |
| Confidence | 9.8 | Held | The guarded migration ran cleanly on a copy of the live DB; no surprises in the data |

## Deviations from Plan

The owner chose D2 at the design gate, and that changed the plan in these ways:

- **No department-logo strip (Task 7 dropped).** The owner said: "just remove the top most navigation. the one with
  all the logos in it." A test now pins that there is no strip.
- **Navbar from D1.** The owner said: "i also like the menu from D1. where i can clearly see it."
  - The bar is green-900, with the full menu from 1280px (it was 1400px).
  - Links underline on hover. Minister's Log-in is an outlined pill.
  - Dropdowns now also open on keyboard focus.
  - The bar stays 80px tall, so the ABC running head is unaffected; this was checked in the browser.
- **7 blocks, not 8.** The `church_directory` block folds into `church_finder` (`show_directory`), which puts the
  finder and the congregations side by side as D2 has them. The `church_directory` compact mode (`compact`) is still
  built and works for other pages.
- **Extra optional keys D2 needed:**
  - hero: `eyebrow`, `verse`;
  - cta: `eyebrow`, `highlight`, `highlight_caption`, and a new `panel` style;
  - feature: `tagline`, `fallback_caption`;
  - church_finder: `show_directory`, `directory_heading`, `help_*`;
  - department_list: `lede`.
- **Banner image is decorative** (`image_alt` null). Its words appear as real text under it (the eyebrow, h1,
  subheading and verse), so an alt text would make screen readers read them twice.
- **The "Who we are" text is split.** Its last paragraph becomes the About tagline. If the text has only one
  paragraph, it stays whole (a test covers this).
- **Events API** now includes `department.logo_light_path`, for the tiles' white marks. This is an additive field.
- **Connect panel** switches to three columns at `lg` (1024px) rather than `md`, because at 768px the sentence column
  was too narrow.
- **Section spacing:** a homepage section that follows another on the paper ground drops its top padding (`flushTop`
  in `CmsPage`), as D2 does, so the gaps don't double.

## Tasks Completed

| Area | Files |
|---|---|
| Postal address setting | migration `2026_10_14_000001`, `SiteSetting`, `SiteSettingController`, `ManageSiteSettings` |
| Admin form keys | `PageForm.php` (hero, cta, feature, church_finder, church_directory, events_feed, department_list) |
| Content switch | migration `2026_10_14_000002` (guarded, archives to `home-before-upca-style`, editor wins), `HomePageSeeder` |
| Events API | `EventController` (`logo_light_path`) |
| Tokens and helpers | `tailwind.config.js` (`brand.muted`); `utils/theme.js` (`departmentSwatchClasses`, `departmentTextClasses`, `DEPARTMENT_RIBBON`, `ctaButton`); new `utils/eventDates.js` and `utils/churches.js` |
| New components | `blocks/FeatureBlock.vue`; `home/SectionHead.vue`, `home/ChurchSpine.vue`, `home/DepartmentRibbon.vue`, `home/ArrowIcon.vue`; collage at `resources/images/home/` |
| Block layouts | `ChurchFinderBlock` (panel), `ChurchDirectoryBlock` (compact), `EventsFeedBlock` (cards), `DepartmentListBlock` (tiles) |
| Page renderer | `CmsPage.vue`: banner hero, band and panel cta, feature, `flushTop` |
| Site chrome | `Navbar.vue` (D1), `Footer.vue` (D2) |

## Validation Results

| Check | Result | Details |
|---|---|---|
| Tests | ✅ | 320 passed, 25 skipped, 0 failed. Before the homepage work it was 279 passed. |
| Pint | ✅ | Clean on every changed PHP file |
| Build | ✅ | `npm run build`. All arbitrary classes are confirmed present in the emitted CSS. |
| Migration on live data | ✅ | Run on a copy of `./upci`: it wrote the 7-block sequence and archived the old homepage unpublished |
| Browser, 1440 / 1280 / 390 | ✅ | No sideways page scroll; one h1; no console errors; the full menu fits on one line at 1280 |
| Photo states | ✅ | The About photo replaces the collage panel; an event flyer replaces its tile and gets a date chip |
| Regressions | ✅ | ABC running head pinned under the 80px bar; `/about` and `/departments/youth` render with the new chrome and no errors |

Screenshots are in `.claude/design/homepage-upca/shots/after/`.

## Tests Written

`tests/Feature/HomepageUpcaStyleTest.php`:

- **Migration:** the switch, the D2 keys, the tagline split, a one-paragraph text, the Connect content→text fix and
  panel style, the archive, editor-wins, no homepage, idempotency, rollback, and rollback after an edit.
- **Form:** every key declared, the band and panel styles, the feature photo on the public disk, and the admin edit
  page opening.
- **Settings:** the postal address in the API, saving it, and a local admin being refused.
- **Seeder:** a fresh install, and never overwriting an existing homepage.
- **Events API:** `logo_light_path`.
- **Vue source contracts:**
  - no strip; the D1 navbar classes and 80px height;
  - the event row has `role="region"` and `tabindex="0"`, snaps, and has no timers;
  - the banner image loads eagerly at high priority, and the banner has an h1;
  - no `${` in class attributes across 10 files;
  - the footer has the postal address.

`CmsBlockSchemaTest` requires `image_side` and `background` on feature blocks.

## Owner actions

1. ~~Approve migrating the live DB.~~ Done 2026-10-14. The backup and tag are noted under Status; the outgoing
   homepage is kept unpublished as `home-before-upca-style`. To undo only the content switch: `php artisan migrate:rollback --step=1`.
   It restores the old homepage only if nobody has edited the new one, and leaves the postal-address column alone.
2. **About photo:** upload it in the admin under Pages → Home → About block → Photo, and add a description.
3. **Event flyers:** add an image to an event and its card shows the flyer.
4. **Contact email and postal address:** set them in Site Settings → Contact & footer. Each is hidden until set.
5. **The "Next" band:** after 25 October, change it in Pages → Home to whatever is next.
6. **`APP_URL`:** set `APP_URL=https://upci.b8.co.nz` in `.env` on staging so admin upload previews work.

## Next Steps

- [ ] Owner reviews staging after the live migration.
- [ ] Commit in focused commits when asked.
