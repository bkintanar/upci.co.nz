# brand-spec.md — UPCI New Zealand

Gate file for §1.a Core Asset Protocol. Every value below was **extracted from a real asset**, not invented.

## 1. Assets held (real, on disk)

| Asset | Source | Local path |
|---|---|---|
| Primary logo | `resources/images/upci-nz-logo.png` (2500×1409, the file Vite bundles today) | `assets/logo.png` (600×338) |
| Leader portraits ×5 | `storage/app/public/page-images/*` — real UPCI NZ executive board & department heads, already live on `/about/leadership` | `assets/photo1–5.jpg` |
| Wide photo ×1 | ABC landing hero, live on `/apostolic-bible-college` | `assets/photo6.jpg` |

**Not held / must not be faked:** church exterior photography, congregation/worship photography, regional imagery. Where a direction needs these it must use an honest placeholder labelled "photo to be supplied", never a CSS silhouette or stock "inspiration" shot.

## 2. Colour — derived via the three-step protocol (sample → converge → argue)

### Sampled
- **From our own logo** (Pillow histogram over 593,037 opaque px): near-white `#F0F0F0` 68%, black `#000000` 9.8%, and a **fern green `#4D7B37`** at 5.2% (25,902 px at exactly that value — it is a flat brand fill, not a photographic gradient).
- **From the AU sister body** (`upca.org.au`, raw HTML hex frequency): `#2B5672` deep slate-blue ×22, `#181818` near-black ×56, `#F7F7F7`/`#E2E2E2` greys, warm amber family `#FDEAD2`/`#FCD29D`/`#FE9361`. (`#116DFF` ×19 is Wix's editor default — **excluded**, it is chrome not brand.)
- **From cultural context:** NZ. Fern green is the national visual shorthand; it is also literally already in our mark.

### Converged (2 chromatic + 1 neutral ramp)
```
--ink        oklch(0.18 0.01 150)   /* near-black, from logo #000 lifted off pure */
--green-900  oklch(0.34 0.07 143)
--green-700  oklch(0.47 0.09 143)   /* THE brand colour. Resolves to #3a6838, NOT #4D7B37 —
                                        the convergence deliberately darkens the raw logo
                                        sample so it reads as ink. Verified against the
                                        compiled CSS. */
--green-100  oklch(0.93 0.03 143)
--clay-600   oklch(0.62 0.12  55)   /* warm accent, borrowed from AU's amber, chroma pulled to ink density */
--paper      oklch(0.98 0.005 95)   /* warm white, not #FFF */
--grey-600 / --grey-400 / --grey-200  /* L 0.55 / 0.72 / 0.90 */
```
Hue separation green 143° vs clay 55° = 88° apart (≥60° ✓). Chroma capped at 0.12 for brand fills per the print-density table — no screen-fluorescent fills.

### Argued (the required one-liner)
> **The primary is the green already sitting inside the UPCI NZ mark (`#4D7B37`), converged to `oklch(0.47 0.09 143)` so it reads as ink rather than screen-green. The warm clay accent is borrowed from the Australian sister body's amber family so the two national sites feel related without NZ copying AU's blue — AU's `#2B5672` is deliberately *not* adopted, because our own logo is green and a denomination should look like its own mark.**

This matters: **the live site currently uses Tailwind's default `slate-800` + `blue-600`, which appears nowhere in the UPCI NZ logo.** The present palette is arbitrary. That is the single biggest reason the UI reads as generic.

## 3. Typography

- **AU sister uses Poppins** (confirmed: `poppins`, `poppins-semibold`, `poppins-extralight`, `poppins-v2` in their stylesheet) — geometric, warm, and it is also the font the Bauhaus Geometric style specifies. Adopting it creates a real family resemblance between the two national sites.
- Current NZ site loads **Figtree** (`app.blade.php`, bunny.net) but Tailwind is never configured to use it — `tailwind.config.js` has an empty `theme.extend`, so everything actually renders in the default `ui-sans-serif` stack. **The webfont is loaded and then not used.** Another concrete source of the "unfinished" feel.
- Directions may pair a display face against Poppins body, but must justify the pairing.

## 4. Forbidden

- No purple/blue "tech" gradients, no emoji icons, no rounded-card + left-colour-border, no SVG-drawn people.
- No invented statistics, no fabricated church names, no stock "community" photography standing in for real congregations.
- Do not adopt `#116DFF` (Wix chrome) or `#2B5672` (AU's blue) as the NZ primary.

## 5. Temperature

Warm, plain-spoken, institutional-but-not-corporate. This is a national church body: it must look **trustworthy and organised** first, contemporary second. Not a startup landing page.
