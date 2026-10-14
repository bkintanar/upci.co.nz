# Direction approved: 2026-10-15

## What was shown

Three directions, built from `spec.md` and `brand-spec.md`. They were published together, with working demos, at
https://claude.ai/artifact/ARooFknu8isgX9vCyrjvha.

| | Direction | Logic | Files |
|---|---|---|---|
| D1 | Editorial | Random style draw (#1, Editorial Brutalism, after Bloomberg Businessweek) | `design-demos/D1-editorial.html`, `shots/D1-*` |
| D2 | Newsroom | Benchmark (Apple Newsroom, verified; Newsroom has no lightbox) | `design-demos/D2-newsroom.html`, `shots/D2-*` |
| **D3** | **Quiet frame** | **Best designer (Kenya Hara)** | `design-demos/D3-hara.html`, `shots/D3-*` |

I recommended D2, optionally with D3's flyer row.

## What the owner chose (verbatim)

> "let's do the quiet frame. this affects the whole site right?"

So the build is **D3** throughout, with no mixing.

Answer given on scope:
- `GalleryGrid` is shared, so the contact sheet and the viewer apply to every gallery: the 6 department pages, the
  3 region pages and `/gallery`. No CMS page uses a gallery block yet.
- The announcement layout exists only on department pages.
- The homepage, navbar, footer and other pages are unchanged.

## Changes to the demo for production

1. **Solid viewer panel.** In every demo, page text showed faintly through the 96% ink backdrop around the caption
   on a phone. The panel itself is solid ink, on a 96% ink backdrop.
2. **Modal reuse.** The viewer wraps `Modal.vue`, per the plan. Modal supplies the top layer, Esc, the backdrop
   click, the Tab trap and focus return. Modal gains two optional props, both defaulting to today's behaviour:
   - `showClose`: `PhotoViewer` uses its own square "Close" button;
   - `dialogClass`: for the ink backdrop.

   `.modal-panel--photo` and `.modal-dialog--photo` live in Modal's own `<style>`.
3. **CSS fitting, not the demo's JS `fit()`.** The image gets `max-width` and `max-height` (92vw, and 88vh minus the
   two control lines) with `width/height: auto`. That shows it whole, never upscales it, and needs no script.
4. **Orientation from the API.** The announcement layout depends on the photo's shape, so
   `GET /api/departments/{slug}` sends `image_width` and `image_height`, read from the file with `getimagesize`. Height
   greater than width means portrait. Unknown dimensions use the landscape layout, which still shows the photo whole.
   The `<img>` gets `width` and `height` attributes, so nothing shifts while it loads.
5. **Description and Calendar heading** take the page's left axis and the section-head style, as in the demo. The
   calendar cards are unchanged.
6. **Lazy loading.** Gallery thumbnails and announcement photos use `loading="lazy"` and `decoding="async"`. The
   demo left these off only for its screenshots.

7. **Wide photos are capped at 36rem tall** (`w-auto max-w-full max-h-[36rem]`), still whole. The demo's camp photo
   was very wide, but a near-square 4:3 phone photo laid across ten columns filled a whole screen in the browser check.

## Class contract (Tailwind 3, literal strings, `brand-*` tokens)

The lift shadow is `shadow-[0_1px_2px_oklch(0.18_0.01_150/0.14),0_18px_34px_-22px_oklch(0.18_0.01_150/0.6)]`.

| Slot | Classes |
|---|---|
| **Section** (photo sections on the department page) | `bg-brand-paper pt-16 lg:pt-[88px]` (gallery also `pb-[88px]`); inner `max-w-7xl mx-auto px-4 sm:px-6 lg:px-8` |
| **Section head** | `flex items-baseline justify-between gap-4 pb-3 border-b-[3px] border-brand-grey-200`; h2 `text-[27px] md:text-[34px] leading-tight font-bold tracking-[-0.015em] text-brand-ink`; meta (count) `text-base text-brand-muted tabular-nums whitespace-nowrap` |
| **Description** | `pt-14 lg:pt-20`; inner `lg:grid lg:grid-cols-12 lg:gap-x-8`; prose `text-[19px] leading-[1.65] max-w-2xl lg:col-start-3 lg:col-span-8 lg:text-[22px] lg:leading-[1.6]` |
| **Announcement list** | `<ol>`; item `border-t-2 border-dotted border-brand-grey-400 first:border-t-0 pt-8 pb-9 lg:grid lg:grid-cols-12 lg:gap-x-8 lg:grid-rows-[auto_auto_1fr] lg:pt-11 lg:pb-12` |
| **Date** | `mb-2 text-xs font-bold uppercase tracking-[0.12em] text-brand-green-700 lg:col-start-1 lg:col-span-2 lg:row-start-1 lg:self-baseline lg:mb-0` |
| **Title** | `text-[22px] leading-tight font-bold tracking-[-0.01em] text-brand-ink lg:row-start-1 lg:self-baseline lg:text-[28px] lg:leading-[1.2]`; columns: portrait and landscape `lg:col-start-3 lg:col-span-8`; text `lg:col-start-3 lg:col-span-7` |
| **Figure, portrait** | `mt-5 max-w-[26rem] lg:max-w-none lg:col-start-3 lg:col-span-4 lg:row-start-2 lg:row-span-2 lg:mt-6` |
| **Figure, landscape** | `mt-5 lg:col-start-3 lg:col-span-10 lg:row-start-2 lg:mt-6` |
| **Body** | `mt-4 text-[17px] leading-[1.65] max-w-[38rem] text-brand-ink space-y-2.5 lg:text-lg`; portrait `lg:col-start-7 lg:col-span-5 lg:row-start-2 lg:mt-6`; landscape `lg:col-start-3 lg:col-span-7 lg:row-start-3 lg:mt-5`; text `lg:col-start-3 lg:col-span-7 lg:row-start-2` |
| **Photo button** | `group block w-full text-left`; the flyer (portrait) is a sheet, `w-full h-auto` with the lift shadow; the photo (landscape) is a print, `w-full h-auto bg-white p-2 ring-1 ring-brand-grey-200` plus the lift shadow; hint `mt-3.5 inline-flex items-center gap-2 text-sm font-semibold text-brand-muted group-hover:text-brand-green-700 group-hover:underline underline-offset-[3px]` ("View full size", with the 4-corner icon) |
| **Contact sheet** | `mt-6 bg-white p-3 sm:p-6 ring-1 ring-brand-grey-200` plus the lift shadow; note above it `mt-4 text-base text-brand-muted` ("Select a photo to see it whole.") |
| **Frames** | `grid grid-cols-2 gap-x-3 gap-y-5 sm:grid-cols-3 sm:gap-x-4 sm:gap-y-6 md:grid-cols-4 lg:grid-cols-5 lg:gap-x-6 lg:gap-y-7` |
| **Frame** | `group block w-full text-left`; image box `block aspect-square overflow-hidden bg-brand-grey-200`; img `w-full h-full object-cover group-hover:opacity-90`; label `mt-2 flex items-baseline gap-2 min-h-6 text-sm leading-snug`; number `shrink-0 min-w-5 text-xs font-bold tracking-[0.04em] text-brand-muted tabular-nums`; caption `font-medium text-brand-ink group-hover:underline underline-offset-[3px]` |
| **Single photo** | `inline-block`; img `w-auto max-w-full max-h-[34rem] h-auto`; the same label |
| **Viewer** | dialog `modal-dialog--photo` (backdrop ink at 0.96); panel `modal-panel--photo`: solid ink, white text, `p-3`, `width: min-content` (so a long caption wraps under the photo), `min-width: min(360px, 92vw)`, `max-width: 92vw`, no radius or shadow, visible overflow |
| **Viewer top** | `flex items-center justify-between gap-4 mb-3`; label `text-xs font-bold uppercase tracking-[0.12em] text-white/80 truncate min-w-0` |
| **Viewer button** | `inline-flex items-center justify-center gap-2 min-w-11 h-11 px-3.5 border border-white/40 rounded-[2px] text-sm font-bold uppercase tracking-[0.07em] text-white hover:bg-white/10 hover:border-white/70 shrink-0`; square variant `w-11 px-0` |
| **Viewer image** | `block mx-auto w-auto h-auto max-w-[calc(92vw-1.5rem)] max-h-[calc(88vh-8.5rem)] max-sm:max-h-[calc(88vh-11rem)] bg-white/5` |
| **Viewer bottom** | `flex flex-wrap items-center gap-x-6 gap-y-3 mt-3 min-h-11`; caption `flex-[1_1_14rem] min-w-0 text-base leading-snug text-white`; nav `flex items-center gap-2 ml-auto max-sm:w-full max-sm:justify-between`; counter `min-w-[8.5rem] text-center text-[15px] font-medium text-white/90 tabular-nums` (`role="status" aria-live="polite"`) |
| **Focus** | `focus-visible:outline focus-visible:outline-[3px] focus-visible:outline-offset-[3px] focus-visible:outline-brand-clay-600`; white on the viewer |
| **Motion** | Viewer fade only (opacity, 0.18s) under `prefers-reduced-motion: no-preference`; otherwise none. No autoplay. |
