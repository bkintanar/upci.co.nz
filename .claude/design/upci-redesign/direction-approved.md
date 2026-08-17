# Direction approved — 2026-08-17

## What was shown

Three homepage directions, built as real HTML and screenshotted, at
`.claude/design/upci-redesign/design-demos/home-r2/`:

| | Direction | After | Needs photography |
|---|---|---|---|
| D1 | Dark Editorial | style roulette | no |
| **D2** | **Search-led** | **A Church Near You (Church of England)** | **no** |
| D3 | Vessel | Kenya Hara | yes, or very tight copy |

## What the client chose

**D2 — Search-led.** Selected from the three previews.

> "Find a church near you" above the fold, with the ten congregations named underneath
> beneath three region headings. Search demoted to a filter over a list that is already
> visible — not a search box in front of an empty page.

**Why this one holds up:** it answers the single question most visitors arrive with, it needs
no hero photography, and it reuses the church locator and the organisational region axis that
are already built, tested and on the data model. It is the lowest-risk of the three and the
only one whose central component already exists.

## Two further answers given at the same time

**Photography — "Yes, I can supply them."** Conference/congregation photography exists and
will be provided. It does **not** gate D2, which was chosen partly because it needs none. Treat
incoming images as enhancement, never as a dependency: any layout that breaks without them is
the wrong layout. The 27 images currently on the server are 23 leadership portraits, 2 logos,
1 department image and 1 gallery photo — nothing landscape.

**Department hues — "Respread to harmonise with brand green."** The six current hues are
default Tailwind values that predate the brand palette and read as unrelated to it. Each
department keeps its own identity; the family is pulled to a consistent lightness and chroma
around the brand green.

## What this unblocks

| Task | Was | Now |
|---|---|---|
| T45 | hero, written against rejected Direction B | rewrite against D2 — whose motif this already is |
| T51 | cards spike, testing B's anti-card stance | re-test under D2's stance |
| T49② | department hues | decided — respread |

## What is still deliberately not decided

**T49④ — greyscale on the leadership row.** Not asked, not assumed. It desaturates
photographs of named people, and five design changes have already been rejected for landing
without review. It goes up separately or not at all.

## Standing constraint

`upca.org.au` is the **basis, not to be replicated**. Brand green is `#4D7B37`, taken from the
logo — not the current site's blue.
