# Design spec: department page photos (announcements, gallery and photo viewer)

All three directions get this file and `brand-spec.md` as their only input. Each direction works alone and must not
look at the others.

## What it is

The department pages of **UPCI New Zealand**, the national body of the United Pentecostal Church International in New
Zealand. There are six ministries: Men's, Ladies, Home Missions, Youth, Children's, Prayer. Each one has a page at
`/departments/<slug>` with:

- a hero in the department's own hue, with its white logo;
- its description;
- **Announcements**;
- a **Calendar**;
- a **Gallery**.

The site was just redesigned:

- **Navbar** (keep it exactly as given): green-900 bar, white links, "Minister's Log-in" as an outlined pill.
- **Homepage** in "direction D2" (after the Church of Scotland):
  - sentence-case section headings on a 3px rule;
  - square uppercase tracked buttons;
  - a six-colour department ribbon;
  - dotted row separators;
  - a deep green-to-ink footer.

This spec covers only **three things on the department page**. You restyle the Announcements section and the Gallery
section and design the photo viewer. Everything else on the page (hero, description, calendar, navbar, footer)
appears as context in its current form and stays as it is.

Before shots of the current Men's page: `shots/before/department-mens-1440.png` and `department-mens-390.png`. Today
an announcement is a white rounded card with a date, a title and a markdown body. Authors paste Facebook links and
embeds into the body because there was nowhere to put a photo; the Men's Rally card shows a large empty box where a
Facebook embed failed to load. The gallery exists but is invisible: no department has any photos yet, and an empty
grid hides itself.

## What is being added (already decided by the owner, already built in the backend)

1. **One optional photo per announcement**, with a required short description (alt text). Authors upload flyers,
   which are usually **portrait, A4-ish (1240×1754)**, and event photos, which are usually landscape. **The
   announcement photo must never be cropped.** A flyer cut off at the edges loses its date and words. Clicking the
   photo opens it large in the viewer.
2. **A usable gallery.** Admins can now upload up to 30 photos at once. A gallery item has an optional caption
   (title). Photos are mixed orientations. **Gallery thumbnails may be cropped to a uniform grid**, but each must be a
   real `<button>` with a visible focus state, so you can tab to it and press Enter. The section appears only when
   the department has at least one photo.
3. **The photo viewer**: a dialog in the browser's top layer (native `<dialog>` with `showModal()`). It has:
   - a dark ground;
   - the large photo, whole and uncropped;
   - its caption, if any;
   - a counter, "Photo 3 of 14";
   - previous/next buttons, hidden when there is only one photo, and wrapping at the ends;
   - a close button;
   - keyboard support (arrows, Esc) and swipe on touch.

   It does **not** autoplay, and it moves nothing under `prefers-reduced-motion`. The same viewer is used for the
   gallery and for a single announcement photo (then without prev/next).

## Audience and job

- **Visitors**: church members and newcomers, mostly on **phones (390 px)**, then laptops (1440 px).
- **Admins**: national leaders posting a flyer or a batch of event photos after a camp or rally.
- **Job**:
  - "What is this ministry doing next, and what did it just do?"
  - The announcement shows the flyer.
  - The gallery shows the people and the places.
  - The viewer lets you actually see a photo.
- **Tone**: warm, plain-spoken, institutional but not corporate. It is a national church, not a startup or a
  magazine trying to be cool. Photos of real people at real gatherings carry the warmth. The design should let them.

## Content to use (real, from the live site; use verbatim)

The **Men's department** (`/departments/mens`, "National Men's Department", hue **green**).

**Announcements, newest first. Show all three cards:**

1. **"MM – Men's Online Virtual Training"**, posted 14 Oct 2026, with a **portrait flyer**
   (`assets/flyer-mens-online-training-STANDIN.jpg`, 1240×1754).
   - Description: "Flyer: Men's Online Virtual Training, Saturday 14 November, nationwide online".
   - Body: "Our next online training session for men across the country. Ask your pastor for the link."
   - The flyer is a STAND-IN built from the real calendar event; no real flyer exists on the server. Note that in an
     HTML comment.
2. **"Men's Camp 2026"**, posted 2 Oct 2026, with a **landscape photo** (`assets/mens-camp-group.png`, 1915×709).
   - Description: "About sixty men from UPCI NZ congregations on the terraced steps at camp".
   - Body: "Thank you to every man who came. More photos are in the gallery below."
3. **"Apostolic Men's Rally - Queenstown"**, posted 24 Apr 2026, with **no photo**. This is the real live text, with
   its emoji removed (emoji are forbidden by the brand):
   > UPCI NZ MEN'S RALLY · Queenstown, NZ
   > Men of God, it's time to be renewed and strengthened! Join us for powerful preaching, worship, and fellowship.
   > "Be watchful, stand firm in the faith… be strong." – 1 Corinthians 16:13
   > Invite someone and don't miss it!

**Gallery (14 photos; files in `assets/gallery/`, all real).** These are crops of the real Men's Camp photo, the
five panels of the real NZ landmarks collage, and the real theme banner.

| # | File | Size | Caption |
|---|---|---|---|
| 1 | g01-camp-group.jpg | 1915×709 | Men's Camp — the whole group |
| 2 | g02-camp-left.jpg | 804×709 | Men's Camp |
| 3 | g03-camp-right.jpg | 805×709 | Men's Camp |
| 4 | g04-camp-centre-portrait.jpg | 510×709 | (none) |
| 5 | g05-camp-square.jpg | 531×532 | (none) |
| 6 | g06-camp-front-rows.jpg | 766×461 | Men's Camp — front rows |
| 7 | g07-auckland.jpg | 280×788 | Auckland |
| 8 | g08-wellington.jpg | 429×788 | Wellington |
| 9 | g09-christchurch.jpg | 470×788 | Christchurch |
| 10 | g10-hamilton.jpg | 459×788 | Hamilton |
| 11 | g11-tauranga.jpg | 487×788 | Tauranga |
| 12 | g12-pentecost-everyday-banner.jpg | 2128×788 | Pentecost Everyday — this year's theme |
| 13 | g13-camp-portrait-right.jpg | 425×568 | (none) |
| 14 | g14-camp-square-left.jpg | 709×709 | Men's Camp |

**Context strip, to keep the page truthful.**

Hero: the department hue gradient `from dept-green-700 via dept-green-900 to ink`. Use `assets/mens-camp-group.png`
behind it at low opacity, the white logo `assets/mens-logo-white.svg` (2:1), and the verse "… Be strong and of a good
courage; be not afraid, neither be thou dismayed: for the Lord thy God is with thee whithersoever thou goest. —
Joshua 1:9".

Then one short description paragraph: "The Apostolic Men's Department of UPCINZ is a ministry dedicated to building
strong, Spirit-led men who are grounded in Apostolic doctrine and committed to living a life that honors God."

Order: navbar → hero → description → **Announcements** → Calendar → **Gallery** → footer. You may place the gallery
before the calendar if you argue for it in a comment.

The Calendar has two items: "MM – Men's Online Virtual Training", 22 August 2026, Nationwide (online); and the same
on 14 November 2026. Keep the calendar plain, since it is not in scope.

The navbar and footer use `assets/upci-nz-logo-nav.png` (287×320) and `assets/upci-nz-logo-footer.png` (804×320).

## Output format (identical for all three)

- **ONE self-contained HTML file** in `design-demos/` (name it `D{n}-<short-name>.html`). Plain HTML and CSS, plus
  vanilla JS for the viewer and the demo toggles only.
  - Figtree from `https://fonts.bunny.net/css?family=figtree:400,500,600,700,800` (as the site loads it).
  - No other CDN.
  - Images by relative path `../assets/...`.
- **Demo toggles**: a small fixed control (bottom-right).
  - Gallery size **1 / 5 / 14**: the first 1, the first 5, or all 14 items.
  - **Open viewer**: opens on photo 3 of the current set.
  - URL hash `#viewer` opens the viewer at load; `#g1` and `#g5` set the gallery size.
- The viewer must **really work** in the demo: click any thumbnail or the announcement photo; arrows and Esc; prev and
  next wrap; the counter updates; focus returns to the thumbnail you opened it from.
- It must render at **1440×900** and at **390 px wide with no horizontal page scroll**, including with the viewer
  open.
- Screenshots, with Playwright 1.64 at
  `/tmp/claude-0/-var-www-personal-upci-co-nz/dc2cc4fc-631f-4dba-b7f8-c00b68adb144/scratchpad/node_modules` and
  `executablePath: '/root/.cache/ms-playwright/chromium_headless_shell-1243/chrome-headless-shell-linux64/chrome-headless-shell'`:
  - `shots/D{n}-page-1440.png` (full page)
  - `shots/D{n}-page-390.png` (full page)
  - `shots/D{n}-viewer-1440.png` (viewport, viewer open on photo 3)
  - `shots/D{n}-viewer-390.png`
  - `shots/D{n}-gallery-5-1440.png` (gallery section only, 5 photos)

  Wait for fonts and images to load before each shot.

## Constraints (hard)

- **Colour**: brand tokens only (`brand-spec.md` §2). The department hue for Men's is
  `dept-green-700 oklch(0.47 0.09 143)` and `dept-green-900 oklch(0.34 0.07 143)`; it is the same as the brand
  green. The viewer's dark ground is **ink** `oklch(0.18 0.01 150)`, possibly at about 96% opacity. It is not pure
  `#000` and not navy.
- **Type**: Figtree only. Body text at least 16 px, small text at least 12 px, contrast at least 4.5:1, including
  white text on the viewer ground.
- **Tailwind 3-expressible.** It will be rebuilt with literal Tailwind classes. Brand oklch tokens have no `/alpha`
  modifier, so translucency on dark grounds must be `white/NN`, or an arbitrary `[color:oklch(... / a)]`. No CSS
  that Tailwind can't express cleanly, except the small viewer `<style>` block (dialog sizing, `::backdrop`,
  reduced motion).
- **Forbidden**:
  - emoji;
  - purple or blue tech gradients;
  - rounded cards with a left colour border;
  - SVG-drawn people;
  - invented statistics, photos or people;
  - "placeholder" wording visible to visitors;
  - autoplaying slideshows;
  - cropping the announcement photo;
  - an icon on every heading.
- **Accessibility**:
  - one `<h1>` (the department name in the hero);
  - section headings are `<h2>`;
  - thumbnails and the announcement photo are `<button>`s whose accessible name comes from the image alt or an
    `aria-label`, for example "Open photo 4 of 14";
  - the viewer has a visible close button, a focus trap, a `role="status" aria-live="polite"` counter announcement,
    and returns focus on close;
  - every control is at least 44×44 px on 390;
  - visible focus everywhere, using clay or white on dark.
- **The viewer panel must not fill the whole viewport edge to edge.** Clicking the backdrop around the photo closes
  it (the site's Modal closes on a click that lands on the `<dialog>` itself). Size the panel to the photo,
  for example at most about 92vw × 88vh.

## Visual-motif hypothesis (from the content)

- **The notice board.** Announcements are what a church pins up: a flyer, a photo, a typed notice. Each has a
  different shape, and the design should honour the object, letting the flyer be a flyer.
- **The group photo.** The gallery's real subject is "who was there": wide group shots and the places they
  travelled from. The long thin country appears again in the five portrait city panels.
- **The ministry hue.** The page belongs to one department. Its hue is the page's accent; the green-to-ink gradient
  is how this site renders a department.
- **Ink for looking.** The viewer is the one dark room in a paper house: everything recedes except the photo.

A good direction lets one of these shape the form, rather than decorating a generic template.

## Success

- A visitor on a phone sees the flyer whole and readable, and can open it to full size in one tap.
- Within two seconds the gallery says "these are our people and our places".
- The viewer feels calm and obvious: you know where you are (3 of 14), how to move, and how to leave.
- It looks like the same site as the redesigned homepage, not a bolted-on photo plugin.
