# Direction approved — 2026-10-14

## What was shown

Three homepage directions, built from `spec.md` and published together at
https://claude.ai/artifact/YW3tKw9RMAWKhhejR3rsDc.

| | Direction | Logic | Files |
|---|---|---|---|
| D1 | Geometric illustration | roulette #5 Pure-CSS Art | `design-demos/D1-pure-css.html`, `shots/D1-*` |
| **D2** | **The federation (after the Church of Scotland)** | **benchmark** | `design-demos/D2-benchmark.html`, `shots/D2-*` |
| D3 | Livery and the long thin line (after Studio Dumbar) | best designer | `design-demos/D3-designer.html`, `shots/D3-*` |

Recommendation given: D2, optionally with D3's colour-block strip.

## What the owner chose (verbatim)

> "let's do D2 as recommended. just remove the top most navigation. the one with all the logos in it."

> "i also like the menu from D1. where i can clearly see it."

So the build is:

- **D2** for every section from the banner down to the footer.
- **No department-logo strip.** This reverses the 2026-10-14 planning decision "strip above the navbar on every
  page". D2's ribbon bars under the strip go with it. The six-colour ribbon stays where D2 uses it on its own: under
  the ministries heading and on top of the footer.
- **D1's navbar.** A green-900 bar with clearly readable white links; underline on hover; Minister's Log-in as an
  outlined pill; the full menu from 1280px.

## Additions D2 needs beyond the plan

Each is an optional admin field. Missing means today's rendering, so other pages are unaffected.

| Block | New keys | Why |
|---|---|---|
| `hero` (banner) | `eyebrow`, `verse` | D2's text under the banner: "This year's theme", plus the Acts 2:47 line |
| `cta` | `eyebrow`, `highlight`, `highlight_caption`; style `panel` | Band: "Next" + "24–25" / "October". Connect: D2's split panel. A new style rather than changing 'blue', which 11 other pages use. |
| `feature` | `tagline`, `fallback_caption` | The emphasised "Wherever you are, you are welcome." line; the caption on the no-photo panel |
| `church_finder` | `show_directory`, `directory_heading`, `help_heading`, `help_text`, `help_link_text`, `help_link_url` | D2 puts the finder and the congregations side by side in one section, with a help box |
| `department_list` | `lede` | "Six departments, one fellowship — …" |

As a result the homepage has **7 blocks** (the separate `church_directory` block folds into `church_finder`):
`hero, cta, feature, church_finder, events_feed, department_list, cta`.

## Class contract

All classes are literal Tailwind strings using `brand-*` tokens. Department hues come only from `utils/theme.js`
(`departmentHeroClasses`, plus the new `departmentSwatchClasses`). Translucent white on dark grounds uses `white/NN`
(the oklch brand tokens have no `<alpha-value>`). `brand-muted` is new: ink at about 75% on paper, `oklch(0.40 0.008 150)`,
which passes 4.5:1.

| Slot | Classes (from the demo CSS) |
|---|---|
| **navbar** (D1) | `nav`: `bg-brand-green-900 text-white sticky top-0 z-50`; row `h-20` (stays 80px for the ABC running head); logo `h-14 xl:h-[62px]`; desktop links `hidden xl:flex items-center gap-[clamp(14px,1.45vw,22px)] text-[14.5px] font-semibold`; link `py-2 whitespace-nowrap hover:underline decoration-2 underline-offset-[7px]`; caret = 8×5 triangle; log-in pill `px-4 py-2 border-[1.5px] border-white rounded-full hover:bg-white hover:text-brand-green-900`; menu button `xl:hidden inline-flex items-center gap-2.5 min-h-11 px-4 rounded-full border-[1.5px] border-white font-bold`; mobile panel `bg-brand-green-900 border-t border-white/15` |
| **button** (D2) | `inline-flex items-center justify-center gap-2.5 min-h-12 px-[22px] rounded-sm text-sm font-bold tracking-[0.07em] uppercase whitespace-nowrap transition-colors`; green `bg-brand-green-700 text-white hover:bg-brand-green-900`; clay `bg-brand-clay-600 text-brand-ink hover:bg-brand-paper`; line `border-2 border-white/70 text-white hover:bg-white/10` |
| **section head** (D2) | `pb-3.5 border-b-[3px] border-brand-grey-200 mb-6`; h2 `text-[27px] md:text-[34px] leading-tight font-bold tracking-[-0.015em] text-brand-ink`; more link `inline-flex items-center gap-2 text-[15px] font-bold text-brand-green-700 hover:underline underline-offset-4` |
| **banner** | `img block w-full h-auto`; caption `max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-6 pb-7 border-b border-brand-grey-200 md:sr-only`; eyebrow `text-xs font-bold tracking-[0.12em] uppercase text-brand-green-700`; h1 `mt-1.5 text-[34px] leading-none font-extrabold tracking-[-0.02em]`; sub `mt-2 text-lg font-semibold`; verse `mt-2.5 text-[15px] italic text-brand-muted` |
| **band** | `bg-brand-ink text-white border-t-[3px] border-brand-clay-600`; inner grid `grid grid-cols-[auto_1fr] md:grid-cols-[auto_auto_1fr_auto] gap-x-[18px] md:gap-x-7 gap-y-1 items-center py-5`; label `col-span-2 md:col-span-1 text-xs font-bold tracking-[0.14em] uppercase text-brand-clay-600`; date block `flex flex-col md:flex-row md:items-baseline md:gap-2.5 leading-none pr-[18px] md:pr-7 border-r border-white/20`; date `text-[40px] md:text-[44px] font-extrabold tracking-[-0.03em] text-brand-clay-600`; caption `mt-1.5 md:mt-0 text-[13px] font-bold tracking-[0.12em] uppercase`; title `text-xl md:text-2xl font-bold leading-tight`; place `text-[15px] text-white/80 mt-0.5`; button clay, `col-span-2 md:col-span-1 mt-3.5 md:mt-0 w-full md:w-auto` |
| **feature** | grid `grid gap-8 lg:grid-cols-[5fr_7fr] lg:gap-16 lg:items-start`; heading = section head; body `space-y-4 text-[17px]`; tagline `text-[22px] leading-snug font-bold text-brand-green-900`; button green `mt-7`; figure `relative aspect-square md:aspect-[16/10] border border-brand-grey-200 overflow-hidden bg-brand-green-100`; photo `absolute inset-0 w-full h-full object-cover`; fallback: collage `aspect-[2128/788]`, legend `hidden md:grid grid-cols-[13.2fr_20.2fr_22.1fr_21.6fr_22.9fr] border-b border-brand-green-900/20` (cells `px-2 py-2 text-[13px] font-bold text-brand-green-900 border-l`), mobile legend inline "Pictured · …", body `flex-1 flex flex-col justify-end gap-2 px-6 pb-6 pt-5`, caption `text-[21px] md:text-[26px] leading-tight font-bold text-brand-green-900` |
| **finder panel** | section grid `grid gap-14 lg:grid-cols-2 lg:gap-16 xl:grid-cols-[7fr_5fr] xl:gap-20`; lede `text-[17px] text-brand-muted`; search `flex mt-5 max-w-[620px]` with input `flex-1 min-w-0 h-14 px-4 border-2 border-r-0 border-brand-ink rounded-l-sm bg-brand-paper text-[17px] font-medium` and button `h-14 px-[22px] rounded-r-sm bg-brand-green-700 text-white text-sm font-bold tracking-[0.07em] uppercase hover:bg-brand-green-900`; mini head `mt-9 text-xs font-bold tracking-[0.12em] uppercase text-brand-muted`; region link `flex items-center gap-3.5 py-4 px-1 border-b border-brand-grey-200`, name `text-xl font-bold text-brand-green-700`, count chip `inline-grid place-items-center min-w-[30px] h-[30px] mr-2 bg-brand-green-100 text-brand-green-900 font-extrabold rounded-sm`; help box `mt-9 max-w-[620px] px-6 py-[22px] bg-brand-green-100 rounded-sm` |
| **spine** (compact directory) | `relative pl-[30px]` with a 2px `bg-brand-grey-400` rule; poles `text-xs font-bold tracking-[0.14em] uppercase text-brand-muted` + 7px triangles; region head `flex items-baseline justify-between py-2 text-[13px] font-extrabold tracking-[0.12em] uppercase text-brand-green-900 border-b border-brand-grey-200`, marker 14px square `bg-brand-green-700` with paper ring; church row `sm:flex sm:items-baseline sm:gap-4 py-2 border-b border-dotted border-brand-grey-400`, dot 8px `rounded-full bg-brand-paper border-2 border-brand-green-700`; name `text-base font-semibold`; town `text-[15px] text-brand-muted sm:ml-auto sm:text-right` |
| **events cards** | key `flex flex-wrap gap-x-[18px] gap-y-1.5 -mt-3 mb-6 text-[13px] text-brand-muted`, swatch 12px `rounded-sm`; scroller `overflow-x-auto snap-x snap-proximity pb-[18px]` with `role="region" tabindex="0"`; row `flex gap-5 md:gap-6 w-max`; card `w-[272px] md:w-[304px] snap-start shrink-0`; tile `relative block aspect-[2/1] overflow-hidden rounded-sm text-white`, national `bg-brand-ink shadow-[inset_0_3px_0_theme(colors.brand.clay.600)]`, department = `departmentHeroClasses()`; label `absolute left-4 top-3.5 text-xs font-bold tracking-[0.12em] uppercase` (national in clay); logo `absolute right-2 top-1 w-[84px] h-[42px]` (national: footer logo `right-3.5 top-3 h-[30px] w-auto`); date `absolute left-4 bottom-3 flex items-baseline gap-2 leading-none` with `b` `text-[44px] font-extrabold tracking-[-0.035em]` and month `text-sm font-bold tracking-[0.12em] uppercase`; "On now" flag `absolute left-4 bottom-16 px-2 py-1 bg-brand-paper text-brand-ink text-xs font-bold rounded-sm`; flyer `absolute inset-0 w-full h-full object-cover` + chip `absolute left-0 bottom-0 px-2.5 py-1.5 bg-brand-ink text-xs font-bold tracking-[0.1em] uppercase`; title `mt-3.5 text-lg leading-snug font-bold` (link `text-brand-green-700 hover:underline`); meta `mt-1.5 text-[15px] text-brand-muted` with location `font-semibold text-brand-ink` |
| **ministries tiles** | ribbon `grid grid-cols-6 h-1 my-3.5` with `departmentSwatchClasses()`; lede `text-[17px] text-brand-muted mb-2`; grid `grid lg:grid-cols-2 lg:gap-x-12`; row `grid grid-cols-[132px_1fr] md:grid-cols-[240px_1fr] gap-4 md:gap-6 items-center py-4 md:py-5 border-b border-dotted border-brand-grey-400`; tile `grid place-items-center aspect-[2/1] rounded-sm overflow-hidden` + `departmentHeroClasses()`; title `text-[19px] md:text-[22px] font-bold` link `text-brand-green-700`; next `mt-1.5 text-[15px] text-brand-muted`, tag `block text-xs font-bold tracking-[0.12em] uppercase` (dept text colour via `departmentTextClasses()`) |
| **connect** (cta `panel`) | `bg-brand-green-100`; inner `grid gap-[18px] py-12 md:grid-cols-[auto_1fr_auto] md:items-center md:gap-10 md:py-14`; h2 `text-[30px] md:text-4xl leading-tight font-extrabold tracking-[-0.02em] text-brand-green-900 md:pr-10 md:border-r-[3px] md:border-brand-green-900/25`; text `text-[19px] max-w-[46ch]`; button green |
| **footer** | `bg-gradient-to-br from-brand-green-900 via-brand-green-900 to-brand-ink text-white`, ribbon on top; grid `grid gap-10 pt-14 pb-10 md:grid-cols-[4fr_5fr_3fr] md:gap-14 md:pt-[72px]`; logo `h-16 w-auto`; blurb `mt-[18px] text-base text-white/85 max-w-[38ch]`; column head `text-xl font-medium pb-3 border-b-2 border-white/55 mb-4`; links `grid grid-cols-2 gap-x-6 gap-y-1`, link `relative inline-block py-1.5 pl-4 text-[15px] text-white/90 hover:underline` with clay chevron; log-in = line button `mb-[22px]`; dl `grid gap-3.5`, dt `text-xs font-bold tracking-[0.12em] uppercase text-white/70`, dd `mt-0.5 text-base whitespace-pre-line`; bar `flex flex-wrap items-center justify-between gap-4 pt-5 pb-7 border-t border-white/20 text-sm text-white/80`; social `inline-grid place-items-center w-9 h-9 bg-white text-brand-green-900 rounded-sm hover:bg-brand-clay-600 hover:text-brand-ink` |
