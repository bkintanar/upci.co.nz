# brand-spec.md: UPCI New Zealand (department photos)

This is the gate file for the core asset protocol. It is carried over from
`.claude/design/homepage-upca/brand-spec.md` and updated to the site as it is
shipped today. Every value was extracted from a real asset or from the live
`tailwind.config.js`, not invented.

## 1. Assets held (real, on disk, in `assets/`)

| Asset | Source | File |
|---|---|---|
| Nav logo (white, stacked) | live site settings | `upci-nz-logo-nav.png` 287×320 |
| Footer logo (white, horizontal) | live site settings | `upci-nz-logo-footer.png` 804×320 |
| Men's Ministry mark (white, 2:1) | `storage/app/public/department-logos/UPCINZ-MEN-03-WHITE-TRANSPARENT.svg` | `mens-logo-white.svg` |
| Men's Camp group photo | the Men's department hero, live | `mens-camp-group.png` 1915×709 |
| 14 gallery photos | crops of the camp photo, the 5 panels of the real NZ landmarks collage, and the real theme banner | `gallery/g01…g14.jpg` |
| Portrait flyer, STAND-IN | composed from the real calendar event "MM – Men's Online Virtual Training, Sat 14 Nov, Nationwide (online)"; no real flyer exists on the server | `flyer-mens-online-training-STANDIN.jpg` 1240×1754 |

**Not held, and must not be faked:** any other congregation photography,
portraits standing in for event photos, or stock "community" images.

## 2. Colour (the shipped tokens, from `tailwind.config.js`)

```
ink        oklch(0.18 0.01 150)
muted      oklch(0.40 0.008 150)   /* ink at about 75% on paper, for secondary text; passes 4.5:1 */
paper      oklch(0.98 0.005 95)    /* the warm page ground, not #FFF */
green-100  oklch(0.93 0.03 143)
green-700  oklch(0.47 0.09 143)    /* THE brand colour; from the logo's #4D7B37, darkened so it reads as ink */
green-900  oklch(0.34 0.07 143)
clay-600   oklch(0.62 0.12 55)     /* warm accent, used sparingly: rules, focus, the "Next" label */
grey-200 / grey-400 / grey-600     /* L 0.90 / 0.72 / 0.55 */
dept hues (700 / 900, same L and C as the brand): green 143, pink 10, purple 325, yellow 70, indigo 275, blue 220
```

- A department's surface is a gradient: `from dept-<hue>-700 via dept-<hue>-900 to ink`.
- The Men's hue is green 143, the same angle as the brand.
- Translucency on dark grounds uses `white/NN`, because the oklch tokens have no `<alpha-value>`.

## 3. Typography

- **Figtree**, 400 to 800, loaded from bunny.net and configured as the Tailwind sans. It is used everywhere.
- The homepage's D2 voice:
  - section `h2`: 27 px on mobile, 34 px from md up, bold, tracking -0.015em, on a 3 px grey-200 rule;
  - eyebrows: 12 px bold uppercase, tracking 0.12em;
  - buttons: 14 px bold uppercase, tracking 0.07em, square corners (2 px).

## 4. The shipped chrome you must keep (context, not in scope)

**Navbar (D1)**
- `bg-green-900`, white 14.5 px semibold links, `h-20`;
- the nav logo at 56 to 62 px;
- "Minister's Log-in" as an outlined pill;
- below 1280 px, a "Menu" pill button.

**Footer (D2)**
- a six-colour department ribbon (4 px) on top;
- a green-900 to ink gradient;
- three columns: logo and blurb; "Quick links" with clay chevrons; "Contact" with an outlined "MINISTERS' LOG-IN"
  button;
- "© 2026 UPCI New Zealand" and a square Facebook icon.

## 5. Forbidden

- Emoji.
- Purple or blue tech gradients.
- Rounded cards with a left colour border.
- SVG-drawn people.
- Invented statistics or people.
- Stock photos.
- Cropping a flyer.
- Pure `#000` or navy for the viewer ground.

## 6. Temperature

Warm, plain-spoken, institutional but not corporate. Trustworthy and organised first, contemporary second. The
photos of real people carry the warmth; the frame should stay quiet enough to let them.
