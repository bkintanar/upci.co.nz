/** @type {import('tailwindcss').Config} */

// Brand tokens from .claude/design/upci-redesign/brand-spec.md, which derives
// the primary from the UPCI NZ mark itself: a Pillow histogram over the logo's
// 593,037 opaque pixels found a flat fern green #4D7B37 fill. It is converged
// to oklch(0.47 0.09 143) so it reads as ink rather than screen-green.
//
// That convergence is a real colour change, not a restatement. The spec
// annotates the oklch value as "≈ #4D7B37"; it actually resolves to #3a6838 —
// darker and less saturated than the raw logo sample. The darkening is the
// point ("reads as ink"), so the oklch value is authoritative and the spec's
// approximation note is the inaccurate part. Verified by converting
// OKLCH → sRGB independently and getting the same #3a6838 the build emits.
//
// Left in oklch rather than hard-coded hex: it is what the spec authored, and
// Tailwind converts it at build time anyway, so there is no rendering
// difference — only a loss of intent if it were flattened here.
//
// The site previously rendered in Tailwind's default slate-800 + blue-600,
// neither of which appears anywhere in the logo. That arbitrary palette is the
// single biggest reason the UI reads as generic.
//
// The clay accent is borrowed from the Australian sister body's amber family so
// the two national sites feel related. AU's blue #2B5672 is deliberately NOT
// adopted — a denomination should look like its own mark. Hue separation is
// green 143 vs clay 55, i.e. 88 degrees apart. Chroma is capped at 0.12 so
// brand fills stay at print density rather than going screen-fluorescent.
const brand = {
  ink: 'oklch(0.18 0.01 150)',
  paper: 'oklch(0.98 0.005 95)',
  green: {
    100: 'oklch(0.93 0.03 143)',
    700: 'oklch(0.47 0.09 143)',
    900: 'oklch(0.34 0.07 143)',
  },
  clay: {
    600: 'oklch(0.62 0.12 55)',
  },
  grey: {
    200: 'oklch(0.90 0.005 150)',
    400: 'oklch(0.72 0.008 150)',
    600: 'oklch(0.55 0.01 150)',
  },
  // Scoped to error states only (D13). The two-hue rule stands everywhere else;
  // this exists because green and clay cannot carry "something is wrong".
  error: 'oklch(0.52 0.17 27)',
}

export default {
  content: [
    "./resources/**/*.blade.php",
    "./resources/**/*.js",
    "./resources/**/*.vue",
  ],
  theme: {
    extend: {
      colors: { brand },
      fontFamily: {
        // Figtree is already loaded in app.blade.php from bunny.net and was
        // never wired up, so everything rendered in the default ui-sans-serif
        // stack — the webfont was fetched and then unused. body already carries
        // `font-sans`, so pointing it here corrects the whole site at once.
        //
        // Chosen over Poppins (the AU sister body's face) deliberately: Poppins
        // is geometric and round, which reads friendlier than a national church
        // body should. Direction B's register is plain and institutional, and
        // Figtree costs no extra network request.
        sans: ['Figtree', 'ui-sans-serif', 'system-ui', 'sans-serif'],
      },
      fontSize: {
        // Direction B's type scale (T49): h2 at 40px and body at 17px. Bare
        // numbers rather than rem multiples of a 16px root so the sizes match
        // the spec exactly.
        'body': ['17px', { lineHeight: '1.6' }],
        'h2': ['40px', { lineHeight: '1.15', letterSpacing: '-0.01em' }],
      },
    },
  },
  plugins: [],
}
