# direction-approved.md

Gate file for the huashu-design three-direction protocol. Written 2026-08-17.

## What was shown

Three complete homepage designs, each built independently from the same `spec.md` + `brand-spec.md`, by three different design logics so they would diverge structurally rather than being one layout in three colourways.

| # | File | Logic | Screenshot |
|---|---|---|---|
| A | `design-demos/A-bauhaus-geometric.html` | Style roulette (`date +%S` = 49 → `49 % 20 + 1` = #10, "Bauhaus Geometric") | `shots/A-bauhaus-geometric-{hero,full,mobile}.png` |
| B | `design-demos/B-benchmark-transfer.html` | Real-world benchmark transfer — **GOV.UK**, verified via WebSearch as Design Museum London "Designs of the Year" 2013 overall winner | `shots/B-benchmark-transfer-{hero,full,mobile}.png` |
| C | `design-demos/C-designer-led.html` | Best-fit studio — **Johnson Banks** (Christian Aid, Cancer Research UK, Comic Relief) | `shots/C-designer-led-{hero,full,mobile}.png` |

Full-page images were also delivered to the user as downscaled JPEGs (`*-full.jpg`) because `SendUserFile` rejects PNGs over ~1.5 MB with a 400.

## User's choice — verbatim

> **"B — GOV.UK style (Recommended)"**

Selected from a three-option prompt on 2026-08-17. No mixing or modification was requested.

## What that commits us to

**B's design language**, as recorded in its own header comment:

> `FORM COMES FROM: the national body as a plain directory of local places — a task-first finder for "a church near you," organised like a civil register: flat lists, dated rows, top-colour-bar tags for departments, and no decoration standing between the visitor and the answer.`

> `BENCHMARK: GOV.UK (https://www.gov.uk/) — winner, Design Museum London "Designs of the Year" 2013 (overall winner, beating 98 contenders); design system maintained by the UK Government Digital Service.`

Concretely, the decisions now locked in:

1. **Task-first hero, no hero photography.** The homepage opens with a church-finder input, not an image. This directly serves the highest-traffic real visitor need (someone on a phone looking for a local church).
2. **Departments resolved as flat colour-bar tags** within one harmonised two-hue family — transferring GOV.UK's real per-organisation colour-tag convention. This replaces the six clashing saturated Tailwind hues (green/pink/purple/yellow/indigo/blue) currently in the `departments` table.
3. **Undecorated lists over cards.** Heading + one-line description, no icons, no card chrome, no left-border accents.
4. **Two-thirds / one-third content-plus-related-links grid** for prose sections.
5. **Dated rows for events**, sitemap-style multi-column footer.
6. **Palette and type per `brand-spec.md`** — the fern green `#4D7B37` sampled from the UPCI NZ logo (explicitly *not* UPCA's blue `#2B5672`), ink, warm paper, clay accent; Poppins throughout.

## Standing constraint carried forward

From `spec.md` §0, reaffirmed by the user mid-session: **"use it as basis, do not replicate it."** upca.org.au supplies information architecture and a quality bar. It supplies neither colour nor layout. Note the chosen direction's benchmark is GOV.UK, not UPCA — so this is doubly safe.

## Open items that block completion of this direction

- **Rev. Peter Lloyd has no portrait.** Five portraits exist for six named leaders. All three directions declined to fake one. Needed before the leadership section is complete.
- **Church Triumphant Wellington is filed under South Region** in the live DB. Flagged, not changed — may be a deliberate lower-North-plus-South grouping or a bad `region_id`. Will be publicly visible once region pages ship.
- **Region naming** — DB has "North/Central/South Region" (slugs `north`/`central`/`south`); the requirements doc says Northern/Central/Southern. Unresolved.
