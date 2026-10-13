# Design spec: UPCI NZ homepage, upca.org.au-style

The shared input for all three directions. Each direction reads ONLY this file and `brand-spec.md`, and must not look
at the others.

## What it is

The homepage (`/`) of **UPCI New Zealand**, the national body of the United Pentecostal Church International in New
Zealand. The owner finds today's homepage bare and wants it to feel like **https://www.upca.org.au/** (UPCI
Australia, the sister body), while keeping the NZ brand. Reference screenshots: `shots/before/upca-1440.png`,
`upca-390.png`. Today's page: `shots/before/home-1440.png`, `home-390.png`.

**upca is the basis, NOT a template.** Take:
- its information architecture: the department-logo strip, the banner and band, about with a photo, the locator,
  event photo cards, and a rich footer;
- the idea that departments are first-class.

Do NOT copy:
- its orange/black palette;
- Poppins;
- Wix widths;
- the way it styles its cards.

Success test: someone who knows both sites says "two organisations in the same family", not "the same site with the
colours swapped".

## Audience and job

- **Visitors:** church members across NZ, newcomers looking for a church, ministers. Phones first (390px), laptops
  second (1440px).
- **Job:** state the year's theme; show what's next; say who we are; help someone find a church; show upcoming
  events; give the six ministries real presence.
- **Tone:** warm, plain-spoken, institutional but not corporate (brand-spec §5). A national church, not a startup.

## Section order (fixed by the owner; every direction uses it)

1. **Department-logo strip**, ABOVE the site navbar, on every page.
   - The six white logos are in `assets/logos/*-03-WHITE-TRANSPARENT.svg` (2:1).
   - The order is Men's, Ladies, Missions, Youth, Children's, Prayer; each links to `/departments/<slug>`.
   - It scrolls away; the navbar below it stays sticky.
2. **Site navbar.** Show it as the real site has it: the logo `assets/upci-nz-logo-nav.png`, with "About the UPCI NZ ▾,
   Departments ▾, Regions, Find a Church, Apostolic Bible College, Calendar of Events, Gallery, Connect with Us,
   Minister's Log-in". Its ground colour is yours to propose; today it is slate-800.
3. **Theme banner** `assets/banner-pentecost-everyday.jpg` (2128×788). Its words are baked in: "PENTECOST EVERYDAY / A
   Sustainable Annual Harvest / Going Beyond 2026 / …and the Lord added to the church daily such as should be saved. ~
   Acts 2:47".
   - Show it **whole** (never cropped, never darkened).
   - On phones its small text is illegible, so below the banner put the real text heading "Pentecost Everyday" plus
     "A Sustainable Annual Harvest — Going Beyond 2026". From 768px up this text may be visually hidden, but it must
     exist.
4. **Next-event band:** "Annual General Conference · 24–25 October · Wellington", with the button "See the calendar".
5. **About:** heading "About the UPCI NZ", then this text verbatim: "The United Pentecostal Church International of
   New Zealand is a fellowship of apostolic congregations across the country, organised into the Northern, Central and
   Southern regions. / Wherever you are, you are welcome." Then the button "Read more", a large photo beside it, and:
   - **Photo state A (today, nothing uploaded):** a DESIGNED fallback panel. No stock photo, no grey box, and no words
     like "placeholder" or "photo coming soon". `assets/nz-landmarks-collage.jpg` (a pale collage of real NZ places:
     Sky Tower, the Beehive, Christchurch Cathedral, a Hamilton bridge, Tauranga) may be used, or pure CSS shapes.
   - **Photo state B (after upload):** show `assets/mens-camp-group.png` as the stand-in uploaded photo, cropped to
     your frame.
6. **Find a church:** heading "Find a church near you", the description "Congregations across the Northern, Central
   and Southern regions.", a search box (placeholder "Suburb, town or postcode") with a "Search" button, and links to
   **Northern Region (4)**, **Central Region (2)**, **Southern Region (4)**. These counts come live from the API, so
   they're real.
7. **Our congregations (compact)**, all ten named, grouped by region, name + town only, no cards:
   - **Northern:** Southside Pentecostal Fellowship (SSPF), Auckland; Daystar Fellowship, Whangarei; Grace
     Fellowship - A UPCINZ Church, Hamilton; New Zealand Family in Christ Church - UPCI NZ, Auckland.
   - **Central:** Apostolic Life Church (ALC), Tauranga; Storehouse Chapel, Hamilton.
   - **Southern:** Church Triumphant Wellington, Wellington; Apostolics of Christchurch (AOC), Christchurch; Lighthouse
     of Jesus Christ - Rolleston, Rolleston; The Pentecostals of Rangiora (POR), Rangiora.
8. **Coming up:** event cards in a horizontal scroll row (scroll-snap, NO autoplay), with a "More events" link. Each
   card is an image (when uploaded) or a **date-led tile** (state A, for all of them today). Real events:
   - 1 Oct–22 Oct: PM – 22 Day Prayer & Fasting (General Conference), Nationwide (dept hue: blue)
   - 22 Oct: Executive Board Meeting, Wellington
   - 23 Oct: Annual General Meeting (AGM) & Ministers Seminar, Wellington
   - 24–25 Oct: Annual General Conference, Wellington
   - 1 Nov: Mission Sunday Promotion, Nationwide (purple)
   - 14 Nov: MM – Men's Online Virtual Training, Nationwide (online) (green)
   - 22 Nov: CM – Promotion, Nationwide (indigo)
   - 6 Dec: Mission Sunday Promotion, Nationwide (purple)

   For state B, give ONE card (the Annual General Conference) the banner image as its stand-in flyer.
9. **Our ministries:** six tiles, each with its department's hue ground and its white logo, linking to its page.
   Hues are fixed by the brand (see Constraints).
10. **Connect with us:** "Questions, prayer requests, or looking for a church home — we'd love to hear from you.", with
    the button "Get in touch".
11. **Footer:**
    - logo `assets/upci-nz-logo-footer.png` and the blurb "Building strong Christian communities across New Zealand
      through faith, fellowship, and service.";
    - a Facebook icon;
    - Quick links: About UPCI, Our Beliefs, Leadership, Regions, Departments, Gallery, Find a Church, Calendar of
      Events, Connect with Us;
    - Contact: a **"Ministers' log-in" button**, an email line (show "contact email to be set" faintly, or omit it),
      and a postal address block (show the example "PO Box 00000, Wellington", marked as an example in an HTML
      comment);
    - "© 2026 UPCI New Zealand".

## Output format (identical for all three)

- ONE self-contained HTML file in `design-demos/`, plain HTML + CSS. Vanilla JS is allowed only for the demo toggle
  below. Google Fonts `<link>` for Figtree. No other CDN.
- Images are referenced by relative path `../assets/...`.
- **Demo toggle**: a small fixed control switching between "Today (no photos uploaded)" and "With photos", which swaps
  the About fallback for the photo and gives the AGC card its image. Hash `#photos` turns state B on at load.
- It must render at **1440×900** and at **390px wide with no horizontal page scroll**. The strip and the event row
  scroll inside themselves.

## Constraints (hard)

- **Colour:** brand tokens only (brand-spec §2).
  - ink `oklch(0.18 0.01 150)`; paper `oklch(0.98 0.005 95)`; green-100 `oklch(0.93 0.03 143)`; green-700
    `oklch(0.47 0.09 143)` (THE brand colour); green-900 `oklch(0.34 0.07 143)`; clay-600 `oklch(0.62 0.12 55)`
    (accent); grey-200/400/600 `oklch(0.90/0.72/0.55 0.005 150)`.
  - **Department hues** (fixed; L/C match the brand): blue `oklch(0.47 0.09 220)`, green `oklch(0.47 0.09 143)`, pink
    `oklch(0.47 0.09 10)`, yellow `oklch(0.47 0.09 70)`, purple `oklch(0.47 0.09 325)`, indigo `oklch(0.47 0.09 275)`.
    Each has a deeper `oklch(0.34 0.07 <hue>)`. The site renders department grounds as a gradient from the 700, via the
    900, to ink; you may use that or a flat 700.
  - Department → hue: men's green, ladies pink, missions purple, youth yellow, children's indigo, prayer blue.
  - No other hues.
- **Type:** Figtree (300–800 available). Body ≥ 16px; small text ≥ 12px; contrast ≥ 4.5:1.
- **Forbidden:**
  - purple or blue "tech" gradients; emoji;
  - rounded cards with a left colour border;
  - SVG-drawn people; invented statistics;
  - stock photos; "placeholder" wording visible to visitors;
  - an icon on every heading.
- **Accessibility:** a real `<h1>` (the banner text); the strip's logo `alt` = department name; visible focus; the
  event row is `role="region"` with `aria-label` and is keyboard scrollable.
- **Implementation reality:** this will be rebuilt in **Tailwind 3**, using classes keyed to the brand tokens above.
  Prefer things Tailwind expresses cleanly (spacing, flex/grid, aspect ratios, borders, gradients from-via-to,
  opacity). Any CSS-shape illustration must be reproducible with a handful of absolutely positioned divs.

## Visual-motif hypothesis (from the content)

- **"Going Beyond 2026", a harvest:** growth, a field, a horizon.
- **The long thin country:** three regions, north to south. The congregations are naturally a north-to-south list.
- **The fern green** in the logo.
- **Six ministries:** six hues, one family.

A good direction lets one of these shape the page, rather than decorating a generic template.

## Success

- The first screen says "UPCI NZ, Pentecost Everyday, the AGC is next".
- The page reads as alive and full even before a single photo is uploaded.
- Departments are impossible to miss.
- At 390px nothing is cramped and nothing scrolls sideways.
