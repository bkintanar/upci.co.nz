<?php

use App\Models\Department;
use Illuminate\Database\Migrations\Migration;

/**
 * Point each department at its light mark.
 *
 * The files are the supplied `-02-WHITE` lockups with their opaque backing
 * rectangle removed. That rect matters: every `-WHITE` asset in
 * `resources/images/logos/` carries a full-canvas `<rect>` with no `fill`
 * attribute, and an unfilled rect renders BLACK in SVG — so the supplied light
 * logos are drawn to sit on a black plate, not as transparent overlays. Dropped
 * in unmodified they would paint a black rectangle onto the coloured hero,
 * which is worse than the dark-on-dark problem they were meant to fix.
 *
 * Stripping the plate was chosen over a CSS `filter: brightness(0) invert(1)`
 * on the dark mark, because the filter would also flatten the red #B43F38
 * accent that is part of the mark.
 *
 * The `-02` lockup (emblem only) was chosen over `-01` (stacked emblem plus
 * wordmark). The hero renders an `<h1>` with the department's name directly
 * beneath the logo, so `-01`'s wordmark repeats that name at roughly half the
 * available height — the actual cause of the reported "logo is so small".
 *
 * Paths are written out per department rather than derived from `logo_path`,
 * because the supplied filenames disagree with themselves: missions ships
 * `UPCINZ-MISSIONS-02.svg` (plural) and `UPCINZ-MISSION-02-WHITE.svg`
 * (singular). Note the destination below is normalised back to the plural form
 * to match its siblings.
 *
 * Guarded to `whereNull`, so re-running never overwrites a logo somebody has
 * since uploaded through the CMS. Content has an editor, and the editor wins.
 */
return new class extends Migration
{
    public function up(): void
    {
        $logos = [
            'mens' => 'department-logos/UPCINZ-MEN-02-WHITE-TRANSPARENT.svg',
            'ladies' => 'department-logos/UPCINZ-LADIES-02-WHITE-TRANSPARENT.svg',
            'missions' => 'department-logos/UPCINZ-MISSIONS-02-WHITE-TRANSPARENT.svg',
            'youth' => 'department-logos/UPCINZ-YOUTH-02-WHITE-TRANSPARENT.svg',
            'childrens' => 'department-logos/UPCINZ-CHILDREN-02-WHITE-TRANSPARENT.svg',
            'prayer' => 'department-logos/UPCINZ-PRAYER-02-WHITE-TRANSPARENT.svg',
        ];

        foreach ($logos as $slug => $path) {
            Department::where('slug', $slug)
                ->whereNull('logo_light_path')
                ->update(['logo_light_path' => $path]);
        }
    }

    public function down(): void
    {
        Department::query()->update(['logo_light_path' => null]);
    }
};
