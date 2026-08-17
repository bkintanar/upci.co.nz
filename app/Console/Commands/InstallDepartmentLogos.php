<?php

namespace App\Console\Commands;

use App\Models\Department;
use App\Models\SiteSetting;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

/**
 * Copies the shipped logo pack onto the public disk and points each
 * department at its own mark.
 *
 * The pack lives in resources/images/logos/<slug>/, which is not web-served —
 * only Vite-bundled imports reach the browser from there, and these need to be
 * replaceable through the admin. So the default variant is copied onto the
 * public disk, where an admin can overwrite it via the FileUpload field.
 *
 * A command rather than a migration, for the same reason as
 * uploads:move-to-public: migrations run under RefreshDatabase on every test
 * and on deploys where the source files may not be present.
 *
 * Installs TWO marks per department. `logo_path` is the dark-ink mark for the
 * white cards in listings; `logo_light_path` is a background-stripped white mark
 * for the department hero, which is a dark brand-hue gradient. One column could
 * not serve both, which is why the hero used to draw dark ink on a dark ground.
 *
 * Only fills a column that is empty, so re-running never clobbers a logo
 * someone has since uploaded. The two are checked independently — a department
 * can have its dark mark set and still need the light one. Pass --force to
 * overwrite both.
 */
class InstallDepartmentLogos extends Command
{
    protected $signature = 'logos:install
                            {--dry-run : Show what would change without writing}
                            {--force : Overwrite a logo_path that is already set}';

    protected $description = 'Install the shipped department logos onto the public disk';

    private const SOURCE_ROOT = 'resources/images/logos';

    private const TARGET_DIR = 'department-logos';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $force = (bool) $this->option('force');
        $disk = Storage::disk('public');

        $departments = Department::query()->orderBy('sort_order')->get();

        if ($departments->isEmpty()) {
            $this->warn('No departments found.');

            return self::SUCCESS;
        }

        $installed = $skipped = $missing = 0;

        foreach ($departments as $department) {
            $source = $this->defaultLogoFor($department->slug);

            if ($source === null) {
                $this->warn("  no logo in the pack for '{$department->slug}'");
                $missing++;

                continue;
            }

            if (filled($department->logo_path) && ! $force) {
                $this->line("  already set, skipping: {$department->slug} ({$department->logo_path})");
                $skipped++;

                continue;
            }

            $target = self::TARGET_DIR.'/'.basename($source);

            if ($dryRun) {
                $this->line("  would install: {$department->slug} -> {$target}");
                $installed++;

                continue;
            }

            $disk->put($target, file_get_contents($source));
            $department->update(['logo_path' => $target]);

            $this->info("  installed: {$department->slug} -> {$target}");
            $installed++;
        }

        $light = $this->installLightLogos($disk, $departments, $dryRun, $force);
        $this->installSiteLogos($disk, $dryRun, $force);

        $this->newLine();
        // Counted separately. The two are independent: a department can have its
        // dark mark already set and still be missing the light one, which is
        // exactly the state this command was extended to fix — so folding them
        // into one total would report "installed 0" on a run that installed six.
        $this->info(sprintf(
            '%s %d dark and %d light department logo(s). Skipped %d dark already set, %d missing from the pack.',
            $dryRun ? 'Would install' : 'Installed',
            $installed,
            $light,
            $skipped,
            $missing
        ));

        return self::SUCCESS;
    }

    /**
     * The light mark, for the department hero.
     *
     * The hero is a dark brand-hue gradient ending in brand-ink, so the default
     * dark-ink logo installed above disappears into it. The pack ships a
     * `-WHITE` counterpart for every lockup, but they are NOT usable as-is:
     * each carries a full-canvas `<rect>` with no `fill` attribute, and an
     * unfilled rect renders BLACK in SVG. They are drawn to sit on a black
     * plate. Dropped straight onto the hero they show as a black rectangle,
     * which is worse than the dark-on-dark problem they fix.
     *
     * So the plate is stripped on the way through. Stripping beats a CSS
     * `filter: brightness(0) invert(1)` on the dark mark, because the filter
     * would also flatten the red #B43F38 accent that is part of the mark.
     *
     * The `-03` lockup (horizontal: emblem plus wordmark) is preferred. It
     * carries the department's name itself, so the hero's `<h1>` is visually
     * redundant and is hidden with `sr-only` — kept in the DOM for the document
     * outline and screen readers, since the redundancy is visual only. A 2:1
     * mark also uses the width of a hero, where the square `-02` emblem and the
     * stacked `-01` both cap out on height and read small.
     */
    private function installLightLogos($disk, $departments, bool $dryRun, bool $force): int
    {
        $installed = 0;

        foreach ($departments as $department) {
            $source = $this->lightLogoFor($department->slug);

            if ($source === null) {
                $this->warn("  no light logo in the pack for '{$department->slug}'");

                continue;
            }

            if (filled($department->logo_light_path) && ! $force) {
                $this->line("  already set, skipping: {$department->slug} light ({$department->logo_light_path})");

                continue;
            }

            // Normalise the plural, so the installed files read consistently
            // even though the pack ships MISSION- for the white variants.
            $name = str_replace('MISSION-', 'MISSIONS-', basename($source, '.svg'));
            $target = self::TARGET_DIR.'/'.$name.'-TRANSPARENT.svg';

            if ($dryRun) {
                $this->line("  would install: {$department->slug} light -> {$target}");
                $installed++;

                continue;
            }

            $svg = $this->stripBackgroundPlate(file_get_contents($source));

            if ($svg === null) {
                $this->warn('  could not find a background plate in '.basename($source).', skipping rather than installing a black tile');

                continue;
            }

            $disk->put($target, $svg);
            $department->update(['logo_light_path' => $target]);

            $this->info("  installed: {$department->slug} light -> {$target}");
            $installed++;
        }

        return $installed;
    }

    /**
     * The emblem-only white variant. Globbed rather than derived for the same
     * reason as defaultLogoFor: the pack ships both MISSION- and MISSIONS-
     * prefixes, and `UPCINZ-MISSIONS-02-WHITE.svg` does not exist at all — so a
     * string substitution on the dark path would return a missing file for
     * exactly one of the six departments.
     */
    private function lightLogoFor(string $slug): ?string
    {
        $dir = base_path(self::SOURCE_ROOT.'/'.$slug);

        if (! is_dir($dir)) {
            return null;
        }

        foreach (['*-03-WHITE.svg', '*-02-WHITE.svg', '*-01-WHITE.svg', '*-WHITE.svg'] as $pattern) {
            $matches = glob($dir.'/'.$pattern) ?: [];

            if ($matches !== []) {
                sort($matches);

                return $matches[0];
            }
        }

        return null;
    }

    /**
     * Removes the opaque backing rectangle from a `-WHITE` logo.
     *
     * Matched on GEOMETRY, never on id: the dark variants reuse the very same
     * `id="XMLID_2_"` for a small internal shape, so an id match would silently
     * mangle artwork. A plate is a rect that covers the whole viewBox from at or
     * before the origin.
     *
     * Returns null when no plate is found, so the caller can skip rather than
     * install something that would render as a black tile.
     */
    private function stripBackgroundPlate(string $svg): ?string
    {
        if (! preg_match('/viewBox="[\d.\-]+\s+[\d.\-]+\s+([\d.\-]+)\s+([\d.\-]+)"/', $svg, $box)) {
            return null;
        }

        [$viewWidth, $viewHeight] = [(float) $box[1], (float) $box[2]];
        $removed = 0;

        $stripped = preg_replace_callback('/<rect\b[^>]*\/?>/', function (array $match) use ($viewWidth, $viewHeight, &$removed) {
            $attr = function (string $name) use ($match): ?float {
                return preg_match('/'.$name.'="([\d.\-]+)"/', $match[0], $found) ? (float) $found[1] : null;
            };

            $width = $attr('width');
            $height = $attr('height');
            $x = $attr('x') ?? 0.0;
            $y = $attr('y') ?? 0.0;

            // Sized with a 1% tolerance, not an exact match. The pack is not
            // consistent about this: most plates are 4000x2000 or 4001x4001, but
            // the ladies and childrens 03 lockups ship a 4000x1999 plate against
            // a 2000-high viewBox — one unit short. An exact `>=` test misses
            // those two, and the caller would then skip them rather than install
            // anything. 1% is far tighter than any real artwork rect: the closest
            // is a 1000x1000 shape at x=1628 in the dark variants.
            if ($width !== null && $height !== null
                && $width >= $viewWidth * 0.99 && $height >= $viewHeight * 0.99
                && $x <= $viewWidth * 0.01 && $y <= $viewHeight * 0.01) {
                $removed++;

                return '';
            }

            return $match[0];
        }, $svg);

        return $removed === 1 ? $stripped : null;
    }

    /**
     * The header and footer take different lockups: the navbar suits the
     * stacked mark, the footer has room for the horizontal one.
     */
    private function installSiteLogos($disk, bool $dryRun, bool $force): void
    {
        $settings = SiteSetting::current();

        $pairs = [
            'header_logo_path' => 'upci-nz-logo-nav.png',
            'footer_logo_path' => 'upci-nz-logo-footer.png',
        ];

        foreach ($pairs as $column => $file) {
            $source = base_path('resources/images/'.$file);

            if (! is_file($source)) {
                $this->warn("  site logo source missing: {$file}");

                continue;
            }

            if (filled($settings->{$column}) && ! $force) {
                $this->line("  already set, skipping: {$column}");

                continue;
            }

            $target = 'site/'.$file;

            if ($dryRun) {
                $this->line("  would install: {$column} -> {$target}");

                continue;
            }

            $disk->put($target, file_get_contents($source));
            $settings->update([$column => $target]);
            $this->info("  installed: {$column} -> {$target}");
        }
    }

    /**
     * The default is the "-01" variant in full colour. Matched by glob rather
     * than by name because the pack is not internally consistent — the
     * missions folder ships both MISSION- and MISSIONS- prefixes.
     */
    private function defaultLogoFor(string $slug): ?string
    {
        $dir = base_path(self::SOURCE_ROOT.'/'.$slug);

        if (! is_dir($dir)) {
            return null;
        }

        foreach (['*-01.svg', '*-01.png', '*.svg', '*.png'] as $pattern) {
            $matches = array_values(array_filter(
                glob($dir.'/'.$pattern) ?: [],
                fn (string $path) => ! str_contains(strtoupper(basename($path)), 'WHITE')
            ));

            if ($matches !== []) {
                sort($matches);

                return $matches[0];
            }
        }

        return null;
    }
}
