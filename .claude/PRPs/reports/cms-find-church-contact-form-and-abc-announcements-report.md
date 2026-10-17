# Implementation Report

**Plan**: `.claude/PRPs/plans/completed/cms-find-church-contact-form-and-abc-announcements.plan.md`
**Branch**: `main` (owner, 2026-10-17: "no need to branch out"). Uncommitted until the owner asks.
**Date**: 2026-10-17
**Status**: COMPLETE

---

## Summary

1. **Find a Church is a CMS page.** `pages.slug 'find-church'` holds:
   - a `church_locator` block: the finder moved from `views/ChurchLocator.vue`, with its hero words as fields;
   - a standard `cta` block.

   It looks and works as before: the homepage search hands over, filters stay in the address, and the details window and maps work.
2. **Contact form block.**
   - `views/ConnectWithUs.vue` moved to `ContactFormBlock.vue`.
   - `/connect-with-us` is a CMS page built from it.
   - Every message stores `source_page`, shown as "Sent from" in Contact Messages.
3. **Announcements block.**
   - Items are edited inside the block (title, date, text, photo with required description, show toggle).
   - `PageController` drops hidden items, sorts newest first and adds photo sizes.
   - They render with `AnnouncementList.vue`, the department announcement list moved out of `Department.vue` and now shared.
   - Added, empty and so invisible, after "About Apostolic Bible College" on the ABC page.

## Assessment vs Reality

| Metric | Predicted | Actual | Reasoning |
|---|---|---|---|
| Complexity | HIGH | HIGH | As planned |
| Confidence | 8/10 | Matched | Two snags, both caught: `defaultItems()` is not a Builder method, and my own comment tripped a source test. The owner edited the ABC Connect page in the CMS while this was being built; the migration's guard left the edit alone, as designed. |

## Tasks Completed

| # | Task | File(s) | Status |
|---|---|---|---|
| 0 | Backup (`../upci.co.nz-backups/pre-cms-pages-20261017-132400`, tag `pre-cms-pages-20261017-132400`); owner OK; step 1 then step 2 live | | ✅ |
| 1 | `source_page` column | `2026_10_17_000002_add_source_page_to_contact_messages.php` | ✅ |
| 2 | Store and show the source | `ContactMessage.php`, `ContactController.php`, `ContactMessagesTable.php`, `ContactMessageInfolist.php` | ✅ |
| 3 | Shared photo sizes | `Api/Concerns/MeasuresImages.php`, `DepartmentController.php` (method moved) | ✅ |
| 4 | Announcements step in the page API | `PageController.php` | ✅ |
| 5 | Three blocks in the page builder | `PageForm.php` | ✅ |
| 6 | Finder block (git mv) | `components/blocks/ChurchLocatorBlock.vue` | ✅ |
| 7 | Contact form block (git mv) | `components/blocks/ContactFormBlock.vue` | ✅ |
| 8 | Shared announcement list | `components/AnnouncementList.vue`, `utils/markdownClasses.js`, `views/Department.vue` | ✅ |
| 9 | Announcements block | `components/blocks/AnnouncementsBlock.vue` | ✅ |
| 10 | Renderer | `views/CmsPage.vue` | ✅ |
| 11 | Routes | `router/routes.js` | ✅ |
| 12 | Page rows | `2026_10_17_000003_author_the_find_church_and_connect_pages.php` | ✅ |
| 13 | ABC Connect form | `2026_10_17_000004_put_the_contact_form_on_the_abc_connect_page.php` | ✅ (no-op live: see below) |
| 14 | ABC announcements block | `2026_10_17_000005_add_announcements_to_the_abc_page.php` | ✅ |
| 15–20 | Browser checks | QA servers on DB copies | ✅ |
| 21 | Tests | 3 new files, 2 repointed | ✅ |
| 22 | Build + step 2 live | | ✅ |

## Validation Results

| Check | Result | Details |
|---|---|---|
| Static (php -l, Pint) | ✅ | Pint passes on all 18 changed or new PHP files |
| Tests | ✅ | 469 passed, 25 skipped, 0 failed (baseline 426 passed) |
| Build | ✅ | `npm run build` |
| Database | ✅ | Integrity ok; `contact_messages.source_page`; rows `find-church` and `connect-with-us`; ABC page `[hero, text, announcements, feature×3, two_column, cards]`; archive `apostolic-bible-college-before-announcements` |
| Browser (copies) | ✅ | See below |
| Live | ✅ | All four pages 200, one h1 each, no console errors |

**Browser checks, on copies**
- **Screenshot diffs:**
  - `/departments/mens` is pixel-identical at 1440 and 390.
  - `/apostolic-bible-college` is identical except the hero's animated arrow.
  - `/find-church` is identical except the closing button's label colour (see Deviations).
- **Find a Church:**
  - The homepage search for "Hamilton" lands on `/find-church?search=Hamilton` with 2 of 2 churches and the map.
  - The Northern region filter writes `?region=northern` (4 of 4), and a deep link restores it.
  - The details window and its map open.
- **Contact forms:** both send. The copy's inbox records `/connect-with-us` and `/apostolic-bible-college/connect`. The thank-you is announced and focused.
- **Announcements:**
  - Four test items (flyer, square photo, text only, hidden draft) render newest first, with the draft absent.
  - The layouts match the department page, and the viewer opens.
  - No sideways scroll at 390.

## Deviations from Plan

- **ABC Connect, live.** The owner edited the page in the admin at 13:33 NZDT, 37 seconds before step 2 ran:
  - added a contact form block ("Get in touch") where "Prayer Request" was;
  - uploaded a new hero photo.

  Migration 000004's guard saw a hand-edited page and changed nothing ("the editor wins"), so no archive row was made. The page has the form. It still has the "Contact Us" text block with its "Contact form" link, and "Prayer Request" is gone; both are left to the owner.
- **Find a Church closing button.** The label is ink rather than green. That is the standard `cta` block's button, shared with every CMS page, so it was not changed.
- **`MARKDOWN` classes** moved to `utils/markdownClasses.js`. The department description uses them too, so they could not move into the list component.
- **No `defaultItems(0)`** on the announcements item list: Builder has no such method, and items start empty anyway.
- **Migrations' `down()`** undoes only what each one added: the replaced section, or the empty block. Later edits elsewhere on the page are kept.

## Issues Encountered

- **The admin page editor was broken briefly.** `->defaultItems(0)` was called on a Builder, and the working tree is live. It was caught by `HomepageUpcaStyleTest` and fixed within minutes, before the owner's 13:31 edits, which saved normally. The public site was unaffected.
- **A source test matched a comment.** My own comment quoted `'/find-church'`; it was reworded.

## Tests Written

| Test File | Test Cases |
|---|---|
| `CmsFindChurchPageTest.php` | page row and blocks; route to CmsPage before the catch-all; the block owns the h1; query contract and `route.path`; every block field declared (dataset of 8); editor opens; SPA serves the path |
| `ContactFormBlockTest.php` | `source_page` stored or null; non-path values refused (4); inbox shows "Sent from"; `/connect-with-us` page and route; block sends its page, `role=status`, no h1 or setPageMeta; migration 000004 swap, archive, no-op when edited, rerun, minimal `down()` |
| `AnnouncementsBlockTest.php` | API order, hidden items dropped, other blocks untouched; photo size and private alt; fields declared (dataset of 9); admin save stores a public string path (`Builder::fake()`); a photo without a description is refused; migration 000005 insert, rerun no-op, no-op without "About", `down()` only while empty; renders nothing when empty |
| Updated | `MapPopupEscapingTest` → `ChurchLocatorBlock.vue`. `DepartmentPhotosFrontendTest` → `AnnouncementList.vue`, plus a shared-list check |

## Owner follow-ups

- **ABC Connect:** remove the "Contact Us" text block, or its "Contact form" link, now that the form is on the page. Put back "Prayer Request" if wanted.
- **Adding announcements:** Admin › Pages › Apostolic Bible College › Announcements › "Add an announcement".
- **Off-brand styling:** the hero style "Gradient Indigo" (purple) is still set on ABC Connect, and text-block links are blue site-wide. Both were flagged in the plan; neither has changed.
