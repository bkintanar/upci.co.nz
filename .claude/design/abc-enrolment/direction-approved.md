# Direction approved — 2026-10-02

## What was shown

Three directions for the ABC new-student enrolment page, built as real HTML from one shared spec
(`spec.md`) and published together for review at https://claude.ai/artifact/NBhmADN75fKYzTbX5g3XxK.

| | Direction | Logic | Files |
|---|---|---|---|
| D1 | Swiss index: one long page with a sticky section index | style roulette (#19 Swiss Monochrome, brand colours) | `design-demos/D1-swiss-index.html`, `shots/D1-*.png` |
| D2 | One step at a time, then Check your answers | benchmark (GOV.UK Design System) | `design-demos/D2-stepper-check-answers.html`, `shots/D2-*.png` |
| **D3** | **The application as a document (Form ABC-1, signature block)** | **best designer (Pentagram / Michael Bierut)** | `design-demos/D3-application-document.html`, `shots/D3-*.png` |

Recommendation given: D3, with D2's Check your answers screen added before submit.

## What the user chose

> "let's do D3."

D3 as designed. D2's check screen was offered and **not** taken up, so it is not added.

## Carried forward from D3 into the Vue build

- Masthead with Form ABC-1 reference line; Progression of Study as a staircase, "You start here" on Certificate Level.
- Five sections with large numeral openers, hanging clause numbers (1.1 …), conditional sub-clauses (1.9a …).
- Sticky running head "Section n of 5" with five boxes that fill when a section's required answers are done.
- Agreement terms as numbered clauses; signature block (printed name on a signature line, date, live "Signed by … on …").
- Receipt-style success state ("Form ABC-1 · Received").
- Not carried: the demo-only Demo switch, the demo's own site header/footer (the SPA supplies those), and the demo's guessed field names (the API's names are used).

## Resolved after approval

- Schedule of fees (2026-10-08): the user approved copying it from the Google Form ("2. copy."). It was captured
  from page 6 of 7 by a headless browser that filled pages 1–5 with obviously fake answers and stopped at the
  Submit page without submitting. The source image is `schedule-of-fees-source.png`. On the page it is a real
  table: Full Time $350 per year, Part Time $250 per year.
