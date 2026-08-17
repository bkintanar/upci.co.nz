# Implementation Report — UPCI NZ site overhaul

**Plan**: `.claude/PRPs/plans/upci-nz-site-overhaul.plan.md` (72 tasks)
**Branch**: `main` — 65 commits, all fast-forward, no merge commits
**Date**: 2026-08-17
**Status**: **PAUSED ON DECISIONS** — 60 done · 2 partial · 3 declined with reasons · 7 remaining

---

## Position

All 11 original requirements are addressed. Every remaining task waits on a decision only the
client can make.

| Check | Result |
|---|---|
| Lint (`pint --test --dirty`) | pass |
| Build (`npm run build`) | pass |
| Tests | **106 passed, 254 assertions** (from 42 at session start) |
| Full-route sweep | **32 routes, no problems** |
| Migration chain | `up` and `reset` clean on a scratch database |
| Pre-existing suite failures | still exactly 16 — none added |

103 files changed, +8,029 / −645. 109 tests across 14 files.

---

## The seven remaining tasks, and what each waits on

| Waiting on | Tasks |
|---|---|
| **Homepage direction** (D1 / D2 / D3) | T45 hero · T50 calendar spike · T51 cards spike · T54 calendar decision |
| **Which hue each department owns** | T49 craft fixes · T69 SBQ/JBQ colours |
| **Whether the 49 events belong to regions** | T55 |

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
