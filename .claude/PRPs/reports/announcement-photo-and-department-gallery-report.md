# Implementation Report

**Plan**: `.claude/PRPs/plans/announcement-photo-and-department-gallery.plan.md` (archived to `plans/completed/`)
**Branch**: `feature/department-photos` (from `main` at `9022d6c`)
**Date**: 2026-10-15
**Status**: COMPLETE. Uncommitted, waiting for the owner's word to commit.

---

## Summary

### Announcement photos
- Department announcements can carry one photo with a required description.
- On the department page it shows whole, never cropped, and opens large in a viewer:
  - a tall flyer stands beside its words;
  - a wide photo lies across the line.

### The existing department gallery is now usable
- **Upload photos**: up to 30 at once, each becoming its own gallery item, stored in file-name order.
- **Contact sheet**: numbered frames, styled as the owner's chosen design direction **D3, "Quiet frame"** (after
  Kenya Hara).
- **Photo viewer**:
  - a solid ink panel;
  - "Photo 4 of 14";
  - previous and next, by button, arrow keys or swipe;
  - Esc closes, and focus returns to the frame you opened;
  - no autoplay.

The gallery component is shared, so the contact sheet and viewer also apply to the region pages and `/gallery`.

### Upload rules
Every photo upload in the admin now follows one definition (`PhotoUpload`):
- public disk;
- jpeg, png or webp, never SVG;
- 10 MB;
- shrunk in the browser to at most 2000 px.

## Owner decisions

| Decision | Answer |
|---|---|
| Gallery scope | Improve the existing one |
| Photos per announcement | One |
| Who can add them | National admins only (unchanged) |
| Merge the homepage branch first | Yes. `main` was fast-forwarded to `9022d6c` (not pushed). |
| Add the columns to the live DB first | Yes. Backup taken (below). |
| Design direction | "let's do the quiet frame." Recorded in `.claude/design/department-photos/direction-approved.md`. |

## Assessment vs Reality

| Metric | Predicted | Actual | Reasoning |
|---|---|---|---|
| Complexity | MEDIUM | MEDIUM | The backend went exactly as probed; the real-browser check found two upload-order problems and two layout issues, all fixed |
| Confidence | 9.5/10 | Held | Every admin behaviour matched the plan-review probe. The surprises were client-side: FilePond's ordering, and real photo shapes. |

## Deviations from Plan

1. **Batch order is file-name order, not "the order shown".**
   - A real-browser upload stored photos in the order they finished. Filament stores multiple uploads in finish
     order, and FilePond resizes each photo first, so a later photo can finish first.
   - One-at-a-time uploads didn't fix it, because the queue is fed as resizes finish.
   - Fix: `storeFileNamesIn('photo_names')`, then sort by the original name with numbers compared as numbers
     (IMG_99 before IMG_0101; PHP's `strnatcasecmp` gets leading zeros wrong). For phone and camera photos that is
     the order they were taken.
   - Drag-to-reorder was removed, since the sort would undo it. Stored files keep random names.
2. **The API also sends `image_width` and `image_height`** for announcement photos, read from the file with
   `getimagesize`. The approved design lays a photo out by its shape, and the sizes also stop the page jumping while
   it loads. There was no schema change.
3. **Wide announcement photos are capped at 36rem tall.** Still whole, but a near-square phone photo across ten
   columns otherwise filled a screen.
4. **Markdown text wraps long links** (`[overflow-wrap:anywhere]`). The Men's description ends with a long Facebook
   URL that made a phone page 449 px wide once the text was larger.
5. **Modal changes.** `Modal.vue` gained two optional props, `showClose` and `dialogClass` (defaults keep every
   existing modal as it was), and holds the viewer's CSS. The viewer panel uses `width: min-content` rather than
   `max-content`, so a long caption wraps under a narrow photo.
6. **Context bits from the approved demo.** The description and the Calendar heading moved to the page's left axis
   and section-head style; the calendar cards are unchanged. `/gallery` moved from a white to a paper section,
   because the contact sheet is white.
7. **Task 2a ran before Task 3** as planned: the live migration came first, so staging never broke.

## Tasks Completed

| # | Task | Files | Status |
|---|---|---|---|
| 0 | Branch from updated `main`; baseline 328 passed | — | ✅ |
| 1–2 | Migration `image_path`, `image_alt`; fillable | `2026_10_15_000001_add_photo_to_department_announcements.php`, `DepartmentAnnouncement.php` | ✅ |
| 2a | Backup, then live migrate | `/var/www/personal/upci.co.nz-backups/pre-department-photos-20261015-085335/` + tag | ✅ |
| 3 | Shared upload definition | `app/Filament/Resources/Shared/PhotoUpload.php` | ✅ |
| 4 | Announcement photo, description (plain error), thumbnail | `AnnouncementsRelationManager.php` | ✅ |
| 5 | API photo fields (+ size) | `DepartmentController.php` | ✅ |
| 6 | "Upload photos" action (+ file-name order) | `Shared/GalleryItemsRelationManager.php` | ✅ |
| 7 | Standalone gallery form on `PhotoUpload` | `GalleryItemForm.php` | ✅ |
| 8 | Design gate: three directions, owner chose D3 | `.claude/design/department-photos/` | ✅ |
| 9 | Viewer | `PhotoViewer.vue`, `Modal.vue` | ✅ |
| 10 | Contact sheet with viewer | `GalleryGrid.vue`, `Gallery.vue` | ✅ |
| 11 | Department page | `Department.vue` | ✅ |
| 12 | Frontend contract tests | `DepartmentPhotosFrontendTest.php` | ✅ |
| 13 | Full validation | — | ✅ |
| 14 | Real-browser check on a DB copy | — | ✅ |
| 15 | Live verification (staging) | — | ✅ |
| 16 | Report, archive | this file | ✅ (commit pending) |

## Validation Results

| Check | Result | Details |
|---|---|---|
| Tests | ✅ | 367 passed, 25 skipped, 0 failed (was 328 at this branch's start; 278 before the previous feature) |
| Pint | ✅ | 11 files clean |
| Build | ✅ | All new arbitrary classes are in the CSS; the viewer CSS is in Modal's chunk |
| Live DB | ✅ | Columns present; the API returns `image_path`, `image_alt`, `image_width`, `image_height` (null for the existing text announcements) |
| Admin, real browser (DB copy) | ✅ | Flyer announcement created through FilePond; "8 photos added"; order correct after the fix; the table shows the Photo column |
| Public pages (DB copy), 1440 and 390 | ✅ | No sideways scroll; one h1; flyer and wide photo whole at their own ratios; all 11 images load on scroll |
| Viewer (DB copy) | ✅ | Counter and wrap-around (4 → 5, then back past 1 to 8); scroll locked; Tab stays inside; Esc returns focus to the frame; a single announcement photo has no counter or arrows; a backdrop click closes it |
| Staging | ✅ | `/departments/mens`, `/gallery`, `/regions/northern`: no errors, no sideways scroll |

## Tests Written

- `PhotoUploadRulesTest`: the upload definition's disk, visibility, types (no SVG), 10 MB, resize; used by all three
  photo fields.
- `AnnouncementPhotoTest` (11):
  - columns;
  - creating with a photo;
  - the plain-words error when the description is missing;
  - SVG refused;
  - no-photo saves;
  - the table column;
  - the API keys, nulls and stray description;
  - pixel size, and null when the file is missing.
- `GalleryBulkUploadTest` (13):
  - one item per photo;
  - append after existing photos;
  - no caption gives no title;
  - unpublished stays hidden;
  - more than 30 refused;
  - one SVG refuses the batch;
  - at least one photo required;
  - hidden on the View page;
  - regional user on own region;
  - regional user refused the department;
  - single create unchanged;
  - file-name order;
  - random stored names.
- `DepartmentPhotosFrontendTest` (9): Vue source contracts.
  - The announcement photo is whole, described and opens the viewer.
  - The layout follows the photo's shape.
  - Gallery frames are numbered buttons.
  - The viewer reuses Modal, with a live counter and arrow keys, and has no slideshow or lazy loading.
  - The viewer styles live in Modal after `.modal-panel`.
  - No class names are built from fragments.

## Issues Encountered (and outcome)

- **Upload order.** Two fixes before it held (see Deviation 1).
- **Phone overflow** from a long link. Fixed (Deviation 4).
- **Test files land in real storage** (the test server shares it). This is known and was planned for: 27 test
  uploads were moved, not deleted, to `/var/www/personal/upci.co.nz-backups/qa-artifacts-20261015-department-photos/`
  with a README. No live record references them.
- **Two files that are not mine.** `storage/app/public/page-images/*.png`, created during the same window, were made
  by neither the test nor any record. They were left untouched; they may be someone's unsaved admin uploads.
- **Pre-existing, not caused here.** Switching relation-manager tabs in the department admin logs "Could not find
  Livewire component in DOM tree" in the browser console. It reproduces on the last commit (`9022d6c`) through a
  temporary worktree; nothing visibly breaks. It's a Filament matter, out of scope.

## Owner actions

1. **Re-post the three Facebook-linked announcements with photos.** The Men's Rally's embedded Facebook video
   doesn't always load (an empty box in screenshots).
2. **Load event photos** with Departments › (department) › Gallery › Upload photos. Until then each department page
   shows its Gallery section with "No photos yet." The owner asked for this after the build (the plan had empty
   galleries hidden).
3. **Replace the one general-gallery photo.** It is a technical blog graphic ("Building a Production Homelab…")
   mislabelled "Apostolic Bible College Teacher's Seminar with Rev. Kent Smith". Now that `/gallery` shows it as a
   whole print, it is more visible.
4. **`APP_URL`.** Set it to `https://upci.b8.co.nz` so admin upload previews work on staging.
5. **Future housekeeping.** Replaced or removed photos stay on disk, by design under delete-nothing.

## Next Steps

- [x] Committed: `14ee380` (backend), `644baf1` (frontend, including "No photos yet."), and the archive commit.
- [ ] Push `main` and this branch when asked.
