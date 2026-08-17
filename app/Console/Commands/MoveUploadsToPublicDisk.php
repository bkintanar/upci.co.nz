<?php

namespace App\Console\Commands;

use App\Models\Department;
use App\Models\GalleryItem;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

/**
 * Relocates uploads that landed on the private disk back when the Filament
 * FileUpload fields omitted ->disk('public').
 *
 * Deliberately a command and not a migration: migrations run under
 * RefreshDatabase on every test run and on fresh deploys where the source
 * files do not exist.
 *
 * Driven by what the database actually references, so it never touches the
 * unreferenced orphans also sitting on the private disk, and it never
 * overwrites a file that already exists on the public disk.
 */
class MoveUploadsToPublicDisk extends Command
{
    protected $signature = 'uploads:move-to-public {--dry-run : List what would move without writing}';

    protected $description = 'Move DB-referenced uploads from the private disk to the public disk';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');

        $referenced = collect()
            ->merge(Department::query()->whereNotNull('hero_image')->where('hero_image', '!=', '')->pluck('hero_image'))
            ->merge(GalleryItem::query()->whereNotNull('image_path')->where('image_path', '!=', '')->pluck('image_path'))
            ->unique()
            ->values();

        if ($referenced->isEmpty()) {
            $this->info('Nothing referenced. Nothing to do.');

            return self::SUCCESS;
        }

        $private = Storage::disk('local');
        $public = Storage::disk('public');
        $moved = $skipped = $missing = 0;

        foreach ($referenced as $path) {
            if ($public->exists($path)) {
                $this->line("  already public, skipping: {$path}");
                $skipped++;

                continue;
            }

            if (! $private->exists($path)) {
                $this->warn("  referenced but on neither disk: {$path}");
                $missing++;

                continue;
            }

            if ($dryRun) {
                $this->line("  would move: {$path}");
                $moved++;

                continue;
            }

            $public->put($path, $private->get($path));
            $private->delete($path);
            $this->info("  moved: {$path}");
            $moved++;
        }

        $this->newLine();
        $this->info(sprintf(
            '%s %d file(s). Skipped %d already-public, %d missing.',
            $dryRun ? 'Would move' : 'Moved',
            $moved,
            $skipped,
            $missing
        ));

        return self::SUCCESS;
    }
}
