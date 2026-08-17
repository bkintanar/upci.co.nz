<?php

namespace App\Console\Commands;

use App\Models\Department;
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
 * Only fills a department whose logo_path is empty, so re-running never
 * clobbers a logo someone has since uploaded. Pass --force to overwrite.
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

        $this->newLine();
        $this->info(sprintf(
            '%s %d logo(s). Skipped %d already set, %d missing from the pack.',
            $dryRun ? 'Would install' : 'Installed',
            $installed,
            $skipped,
            $missing
        ));

        return self::SUCCESS;
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
