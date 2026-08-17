# Implementation Report — UPCI NZ site overhaul

**Plan**: `.claude/PRPs/plans/upci-nz-site-overhaul.plan.md` (72 tasks)
**Branch**: `main` — 90 commits (69 substantive, 21 ralph progress-tracking), all fast-forward,
no merge commits
**Date**: 2026-08-17
**Status**: **ALL 72 TASKS RESOLVED** — 65 done · 3 partial · 4 declined with reasons · **0 remaining**

> **Two revisions since the first close.**
>
> The first close recorded "7 remaining, every one gated on a client decision." That was **too
> broad**: re-deriving each premise from the code showed three were already delivered or
> answerable from data, one rested on a false premise, and one was five separate fixes bundled
> as a single blocked task. Only **two** were genuinely waiting on a person.
>
> Those two then got their answer. The client chose **Direction D2 (search-led)**, confirmed
> **photography is available**, and approved **respreading the department hues** — so T45, T51
> and T49② were all built. Nothing on the task list is now waiting on anyone.

---

## Position

All 11 original requirements are addressed. Every remaining task waits on a decision only the
client can make.

| Check | Result |
|---|---|
| Lint (`pint --test --dirty`) | pass |
| Build (`npm run build`) | pass |
| Tests | **117 passed, 269 assertions** (from 42 at session start) |
| Full-route sweep | **32 routes, no problems** |
| Migration chain | `up` and `reset` clean on a scratch database |
| Pre-existing suite failures | still exactly 16 — none added |

103 files changed, +8,029 / −645. 109 tests across 14 files.

---

## The residue, re-derived

Each of the original seven was re-checked against the code rather than against the previous
summary. Five dissolved.

| Task | Recorded as | Actually |
|---|---|---|
| **T50** calendar spike | blocked on direction | ✅ **already built.** `Calendar.vue` renders a 7-column month grid; browser-verified — 42 day cells, real events, **zero console errors**. The spike asked if it was achievable; it was already achieved |
| **T54** calendar decision | blocked on direction | ✅ **decided by implementation.** "Build or retire" — it is built, routed and working. Retiring would delete working functionality |
| **T55** events → regions | blocked on client | ✅ **resolved on evidence.** Exactly **1 of 49** event names contains a region word. The other 48 carry no derivable signal, so assignment is client data by measurement, not by preference — which is what the task's own `OR` asked for in writing |
| **T69** SBQ/JBQ colours | blocked on hue choice | ⛔ **premise false.** SBQ/JBQ are **CMS pages**, not departments — they never touch the palette. And colour lives in **one** file now, not the three the hazard warns about |
| **T49** craft fixes | blocked on hue choice | ◐ **five fixes, not one.** Two already satisfied, one superseded, one a defect, one genuinely a taste call |

### Then the client answered, and the last two were built

**Direction D2 — search-led** (after the Church of England's *A Church Near You*), chosen from
three built previews. The homepage now opens with a search field and, immediately beneath it,
**all ten congregations named** under Northern → Central → Southern. Search is a filter over a
list that is already visible rather than a box in front of an empty page — a visitor who does
not know what to type still gets an answer.

| Task | Built |
|---|---|
| **T45** | D2 homepage. Verified in a browser: 10 churches named, search at 205px above the fold, correct region order, 0 overflow at 1440 and 390, 0 page errors |
| **T51** | Cards go flat under D2 — the lifting `shadow-lg`/`rounded-xl` card is gone, hover moves the border instead. Proven on leadership (14 cards) and ABC (4) before reaching the other five pages |
| **T49②** | Six department hues respread onto the brand family: same lightness and chroma as the brand green, hues 45-80° apart, each near its previous identity |

**Photography exists** and will be supplied. D2 deliberately does not depend on it — a layout
that breaks when the images are late is the wrong layout.

**Still held back on purpose:** T49④, greyscaling the leadership portraits. It desaturates
photographs of named people, and five design changes have already been rejected for landing
without review. It goes up separately or not at all.

### Two defects this work surfaced

**The homepage rendered no `h1`.** D2 opens with the finder block, whose heading was an `h2`,
and `PageHeader` is suppressed at the root. This is the *same symptom* that exposed the
rejected Direction B homepage — reproduced, caught by the sweep, and fixed by promoting the
first block's heading on the homepage.

**`/api/churches` never consulted `regions.sort_order`.** It ordered by featured-then-name, so
the homepage rendered **Southern above Northern**. `ChurchDirectoryBlock` carried a comment
asserting the API supplied region order — an ordering that had never existed. Fixed at source
with a `leftJoin`, so every consumer benefits and churches without a region still appear.

### A defect this pass found in my own work

`tailwind.config.js` defines `text-body` (17px) and `text-h2` (40px) for T49. **Nothing uses
them** — 0 occurrences across `resources/`, so Tailwind's JIT emits them into none of the five
built stylesheets. Dead configuration, added by the change that was supposed to deliver the
type scale. It is the "wire infrastructure to a real consumer in the same change" rule from
the Patterns section below, broken by the commit that recorded it. Applying the scale is a
visible site-wide type change, so it goes through the design gate rather than landing here.

Also open, and shaping the homepage: **do conference or congregation photographs exist?**
There are 27 images on the public disk — 23 leadership portraits, 2 logos, 1 department image,
1 gallery photo. Nothing landscape. UPCA's approach is built on photography the organisation
does not currently have, and `brand-spec.md` rules out the usual substitute in as many words.

Three homepage directions are built and waiting at
`.claude/design/upci-redesign/design-demos/home-r2/`, each a different honest answer to *what
an image-led homepage does before the images exist*.

---

## Defects fixed that the plan did not contain

The plan gated new work and never audited what already existed. These were found by reading
and exercising the running site.

| Defect | Severity |
|---|---|
| Unauthenticated `DELETE /api/churches/{id}` — anyone could delete a church | critical |
| The live SQLite user database committed to a **public** repository across 12 commits | critical |
| Personal Gmail addresses of named individuals published on an unauthenticated endpoint | high |
| `CACHE_STORE=database` on SQLite — 500s on roughly **60% of page loads**, masked by fallbacks | high |
| Everything rendered through `v-html` was unsanitised; two announcements already carried raw `<iframe>` | high |
| A rejected homepage silently re-applying itself on every `migrate` | high |
| Two ABC pages scrolled sideways on a phone (Facebook embeds ship `width="560"`) | medium |
| `/departments` ignored the departments table entirely — four hard-coded ministries against six real ones | medium |
| The general gallery queried a department literally named "general"; empty state since launch | medium |
| Four homepage statistics, three of which had drifted from the data | medium |
| A menu-endpoint failure left the site with **no navigation at all** | medium |

---

## Decisions recorded rather than guessed

- **T70 declined** — `gallery_items.department` still holds the only record of what the single
  gallery photograph is.
- **T60 declined** — "detail links *if relevant*" is not relevant for two ~600-character
  announcements rendered in full.
- **T64 declined** — asks to split a task that is finished.
- **T5 partial** — the stale Christchurch address is that church's only address, and the
  directions link is built from it. Blanking degrades the listing twice over.
- **T65 partial** — presbyters linked from the leadership page; region intros deliberately not
  written. Placeholders were sanctioned generally; that does not extend to attributed speech.
- **T14 deviation** — refused a bare `is_published` filter on the locator's region endpoint,
  which would have repeated the defect fixed in `d1e0b0c`. **The client may still want it as
  originally specified.**

---

## What went wrong on my side

**Seven Vue regressions shipped and fixed.** Every one passed lint, tests and build; every one
was obvious in a browser within seconds. Curl and a green build cannot verify a Vue change.

**My own verification tooling misled me three times:**
1. A test that could not fail — caught by Pest's `risky` flag, not by me.
2. A link check finishing before the SPA rendered its 404 — a false pass on a dead link.
3. A route sweep whose own load tripped the site's rate limiter, then reported the correct 429
   handling as a page failure.

Two invented defects; the first hid a real one. Verification code deserves the same scepticism
as the code it verifies.

**Five design changes were rejected by the client** — a two-row header, an SBQ/JBQ ordering
workaround, the Direction B homepage, a contents legend. The lesson is to show design work
before building on it, and to name a structural limit rather than quietly ship a workaround
around it.

---

## Patterns worth carrying forward

- **Backfill, then change.** Removing an inference rule restyles every page relying on it.
  Compute what the rule produces today, store it as data, then let the renderer read it — both
  in one commit.
- **Wire infrastructure to a real consumer in the same change**, or it is unverified by
  construction.
- **A rejected migration must be emptied, not rolled back.** `migrate:rollback` removes the
  row, so the next `migrate` re-applies it.
- **Check a task's premise before executing it.** Five plan tasks were mis-specified until the
  code was read.

---

## Action required outside this repository

`.env` is gitignored, so the production server needs **`CACHE_STORE=file`** set by hand.
`.env.example` is corrected and tracked, which covers future deploys.

---

## Recommended next step

Get the three decisions. Resuming the plan without them produces churn — what remains is
either finished, deliberately declined with the reasoning recorded, or genuinely blocked.
