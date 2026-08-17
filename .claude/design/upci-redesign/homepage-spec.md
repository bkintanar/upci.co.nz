# Homepage spec — round 2 (UPCA as basis)

Written 2026-08-17, after the user reviewed and **rejected** the Direction B homepage.

## What happened

Direction B (GOV.UK benchmark transfer) was approved as the site's overall direction and its
homepage was built and switched live. The user rejected it on sight and asked for the approach
of the Australian sister body, `upca.org.au`. The homepage was rolled back to its original
content; the rest of the B work (tokens, breadcrumb, page header, contents, data-bound blocks)
stands and is not in question.

**Standing instruction from earlier in the project:** "use it as basis, do not replicate it."

## What UPCA actually does (verified by fetching the site, not assumed)

Top to bottom:

1. **Hero** — one large thematic photograph carrying the current national event's branding,
   with a single prominent `REGISTER` button to ticketing. The hero is an *invitation to one
   thing*, not a menu.
2. **About** — three or four lines, then `READ MORE`. Deliberately thin.
3. **Church locator** — searchable, filtered by state and by church name.
4. **Missed national events** — a carousel of video/photo tiles from past conferences,
   2023–2026, each labelled with its location.
5. Footer with a `Get in touch` prompt.

Character: **image-led**, dark/neutral ground with white text, moderate density, spacious.
Social links (Spotify, Instagram, Facebook, YouTube) are threaded through the page.

## Why B failed here

B's founding move is *no hero photography* and *the front page is a task*. UPCA's founding
move is *one photograph and one invitation*. They are close to opposites. B was chosen for the
site's overall language and remains right for interior pages — breadcrumbs, title bands,
readable documents — but it is the wrong grammar for this front page.

## 🔴 The binding constraint: there is no photography

An inventory of `storage/app/public` returns **27 images**: 23 leadership portraits (portrait
or square), 2 logo files, 1 department image, 1 gallery photo. There is **no landscape
congregation, conference or building photography at all**.

UPCA's approach is built on large photography. UPCINZ does not have it, and `brand-spec.md`
§4 forbids the obvious substitute in as many words: *no stock "community" photography standing
in for real congregations*.

So the three directions below are not three skins. **Each is a different honest answer to
"what does an image-led homepage do when there are no images yet?"** — and each degrades
gracefully into the full-photography version once the client supplies photographs.

This is the single most useful thing the round can establish, and it is a question only the
client can close: **are there conference and congregation photographs available?**

## Audience and purpose

- **Primary visitor:** someone in New Zealand looking for a church near them. UPCA answers
  this with a locator high on the page; so should we.
- **Secondary:** an existing member checking what is on nationally.
- **Tertiary:** someone deciding whether this movement is for them — the "who we are" question.

## Content that genuinely exists

- 10 active churches across 3 regions (4 Northern / 2 Central / 4 Southern), 5 without
  coordinates
- 49 national calendar events for 2026, all currently national scope
- 6 departments, each with a real logo from the 2026 pack
- 13 leadership portraits, real and distinct
- Apostolic Bible College, with a principal's message and four live registration forms
- 1 gallery photograph
- Verified counts: 9 established churches, 1 preaching point, 3 regions

## Non-negotiables

- The fern green `#3a6838` from the logo is the primary. AU's blue is deliberately not adopted.
- Figtree, already loaded and now wired.
- No invented statistics — the four on the current homepage are wrong and must not return.
- No stock congregation photography.
- Honest placeholders beat bad substitutes.
- Output: full-width responsive homepage, reviewed at 1440×900.

## Visual motif (form from content)

The content's own distinguishing structure is **ten named congregations across three regions in
one long thin country**. That is the thing no other church site has, and it is what the
directions should grow from — not a stock hero.
