# Brand assets: favicon and share card (2026-10-17)

The owner: "update the favicon, and the opengraph image. Use the globe part of the logo."

- `globe.png`: the globe emblem, cut from `storage/app/public/site/upci-nz-logo-footer.png` (320×320, transparent). Its lines
  are white, so it always sits on the brand green (`#20411e`, brand-green-900).
- The favicons in `public/` are built from it:
  - `favicon.ico` (16, 32, 48), `favicon-16x16.png`, `favicon-32x32.png`, and `favicon.svg` (an embedded 128px PNG; not
    linked): the globe on a green disc.
  - `apple-touch-icon.png` (180), `icon-192.png`, `icon-512.png`: the globe on a full green square, since phones round the
    corners themselves.
- `og-image.html` and `og-globe.png` are the source of `public/og-image.jpg`, the 1200×630 share card. To regenerate,
  screenshot the `.card` element at 1200×630 in Chrome (Figtree loads from fonts.bunny.net) and save it as JPEG,
  quality 88.
- The tags are in `resources/views/app.blade.php`.

Originals (Laravel's default icons) are backed up in `../upci.co.nz-backups/pre-favicon-20261017-214702/`.
