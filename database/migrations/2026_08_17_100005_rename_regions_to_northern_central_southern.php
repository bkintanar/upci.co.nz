<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Migrations\Migration;

/**
 * Renames the three regions to Northern / Central / Southern, slugs included.
 *
 * Safe to rename the slugs: nothing public references them. menu_items.url,
 * pages.content, resources/js and app/Http were all checked and none mention
 * north/central/south as a region slug; /regions/:slug does not exist yet.
 *
 * `regions` carries UNIQUE on both name and slug, so each row is updated in a
 * single statement rather than swapping through an intermediate value.
 *
 * Note the names are administrative labels, not geography — Hamilton appears
 * in both Northern and Central, and Wellington is Southern.
 */
return new class extends Migration
{
    private const RENAMES = [
        'north' => ['northern', 'Northern Region'],
        'south' => ['southern', 'Southern Region'],
    ];

    public function up(): void
    {
        foreach (self::RENAMES as $oldSlug => [$newSlug, $newName]) {
            DB::table('regions')->where('slug', $oldSlug)->update([
                'slug' => $newSlug,
                'name' => $newName,
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        foreach (self::RENAMES as $oldSlug => [$newSlug, $newName]) {
            $oldName = $oldSlug === 'north' ? 'North Region' : 'South Region';

            DB::table('regions')->where('slug', $newSlug)->update([
                'slug' => $oldSlug,
                'name' => $oldName,
                'updated_at' => now(),
            ]);
        }
    }
};
