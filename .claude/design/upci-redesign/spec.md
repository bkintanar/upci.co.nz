# Shared Design Spec — UPCI New Zealand homepage redesign

This is the **single common input** for three independent design directions. Each direction reads only this file, works alone, and does not look at the others.

---

## 0. THE MOST IMPORTANT CONSTRAINT — read twice

`https://www.upca.org.au/` (United Pentecostal Church of Australia) is the **basis, NOT a template. Do not replicate it.**

What you *take* from UPCA:
- **Information architecture** — a national church body's site is organised around: who we are → find a church near you → what's happening (calendar) → our departments/ministries → get in touch. That ordering is proven and we adopt it.
- **The standard/bar** — theirs is a coherent, current, professionally-organised site. Ours currently is not. Match the *level*, not the appearance.
- **The insight that departments are first-class citizens** — UPCA gives 10 national departments equal billing with their own logos.

What you must **NOT** take:
- ❌ Their blue `#2B5672`. Our logo is **green**. Using their blue would make NZ look like a regional copy of AU.
- ❌ Their layout, section order, hero composition, or card styling copied shape-for-shape.
- ❌ Their Wix-default look (flat, shadowless, `#116DFF` links).

If someone put your design next to upca.org.au, the correct reaction is *"these are clearly two organisations in the same family"* — **not** *"this is the same website with the colours swapped."* A direction that reads as a reskin of UPCA has failed.

---

## 1. What this is

The public website of **UPCI New Zealand** — the national body of the United Pentecostal Church International in New Zealand. Established denomination, 10 affiliated churches nationally, 3 organisational regions, 6 national departments, a Bible college, and a full 2026 events calendar.

It is a **real, live, in-production site** being redesigned — not a concept. Current stack: Laravel + Vue 3 SPA + Tailwind, content managed through a Filament admin CMS. Everything you design must be plausibly buildable in Tailwind by a developer, and every piece of text content must be CMS-editable (nothing hard-coded).

## 2. Audience and use case

Three distinct visitors, in priority order:
1. **Someone looking for a church** — often new to the area, possibly new to the faith, on a phone. Needs "find a church near me" fast. This is the highest-traffic real task.
2. **An existing member** — checking what's on (calendar), department news, ABC enrolment.
3. **A minister / leader** — needs the calendar, regional information, official notices.

Mobile matters heavily. Assume ≥60% phone traffic for visitor type 1.

## 3. Real content you must use (no Lorem, no invention)

**Nav (current, live):** About the UPCI NZ · Departments · Find a Church · Apostolic Bible College · Calendar of Events · Connect with Us

**6 national departments** (real, with their existing colour themes): National Men's Department (green) · Ladies Ministries (pink) · Home Missions Department (purple) · Youth Ministry (yellow) · Children's Ministry (indigo) · Prayer Ministry (blue).
⚠️ Those six per-department colours currently clash with each other and with the site chrome — **resolving that into one coherent system is part of the brief.** You may re-derive them as a harmonised family (e.g. one hue ramp, or muted tints of the brand green) rather than six saturated Tailwind defaults.

**3 regions:** North Region · Central Region · South Region (4 / 2 / 4 churches respectively). Region names are being revised to Northern/Central/Southern — either is acceptable in your design.

**Real leadership** (photos supplied in `assets/photo1–5.jpg`): Rev. Troy Wickette (General Superintendent) · Rev. Wayne Goodare (Assistant General Superintendent) · Rev. Andrew Kintanar (General Secretary & Treasurer) · Rev. Brian Aubrey (Northern Region Presbyter) · Rev. Jules Matika (Central Region Presbyter) · Rev. Peter Lloyd (Southern Region Presbyter).
⚠️ They currently have **name + role only, no biography.** Do not invent bios. If your design wants a bio, use a short honest placeholder.

**Real 2026 events** (from the live calendar, 49 total): General Conference (15 Jan) · National 7 Day Prayer & Fasting (26 Jan) · ABC Teachers Training Seminar (6 Feb) · Annual Ministers Meeting (21 Feb) · Apostolic Men's Conference (10 Apr) · JBQ Mini-tourney (4 Jul).

**Real churches:** Apostolic Life Church · Church Triumphant Wellington · Southside Pentecostal Fellowship · Apostolics of Christchurch · Grace Fellowship · Daystar Fellowship · Storehouse Chapel · Pentecostals of Rangiora · Lighthouse of Jesus Christ Rolleston · New Zealand Family in Christ Church.

**Do not invent:** member counts, "200+ churches" style statistics, testimonials, service times, or any church not on the list above. The current live site has fabricated stats ("6M+ Members Globally") — do not carry that forward.

## 4. Brand — bound by `brand-spec.md`, read it

Short version: primary is the **fern green `oklch(0.47 0.09 143)` ≈ `#4D7B37` sampled from our own logo**; near-black ink; warm off-white paper; a clay/amber accent. **Poppins** is the sanctioned type (it is also what AU uses, giving family resemblance through *type* rather than through colour). You may pair a display face against it if you justify the pairing.

Do not invent colours outside that system. Do not use Tailwind's default `slate-800`/`blue-600` — that arbitrary pairing is precisely the problem being fixed.

## 5. Output format — identical across all three directions

- **One single self-contained `.html` file**, plain HTML + CSS (no React, no build step). Google Fonts via `<link>` is allowed.
- **Homepage only**, full scroll length — hero through footer.
- Reference images by **relative path**: `../assets/logo.png`, `../assets/photo1.jpg` … `../assets/photo6.jpg`. (These stay in one folder and are screenshotted in place.)
- Design for a **1440×900** viewport. Also make it not break at 390px wide — mobile is the majority case.
- Save to `.claude/design/upci-redesign/design-demos/<direction-name>.html`.

## 6. Non-negotiable quality floor

- Body text ≥14px, labels ≥12px, body contrast ≥4.5:1. Whitespace must be **composition** — a clear focal point in the first screen — not absence of content.
- No AI-slop: no purple gradients, no emoji icons, no rounded-card-with-left-colour-border, no SVG-drawn faces, no decorative icon on every heading, no invented data.
- **Every element earns its place.** A national church site should feel calm and organised, not busy.
- Photos supplied are portraits of real people — crop them respectfully (no extreme/jokey crops).

## 7. The question your design must answer

**What is the visual motif that belongs to *this* organisation and no other?** Not "a church site" in general — UPCI *New Zealand*. Candidate seeds (pick one, or find better): the fern green already in the mark; the three-region geography of the country; the idea of a national body as a *network of local places* rather than a headquarters. Whatever you choose, state it in one line in an HTML comment at the top of your file: `<!-- FORM COMES FROM: ... -->`.

If you cannot write that line, you are applying a template rather than designing.
