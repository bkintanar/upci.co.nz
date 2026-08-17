<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

/**
 * Event artwork.
 *
 * The client confirmed on 2026-08-17 that promotional flyers exist for most
 * events. There was nowhere to put them: `events` had no image column, so the
 * calendar could only ever be typographic no matter what artwork existed.
 *
 * Named `image_path` rather than `flyer` or `artwork` because that is the name
 * the rest of this schema already uses for the same idea — `departments.hero_image`
 * aside, `regions.logo_path`, `departments.logo_path` and `gallery_items.image_path`
 * all end in `_path` and hold a disk-relative string. A new noun here would make
 * the column look like a different kind of thing than it is.
 *
 * Nullable on purpose and forever. The calendar's chosen direction (E2, the wide
 * agenda) uses artwork as a row thumbnail where it exists and simply omits the
 * column where it does not — so an event with no flyer is a normal event, not a
 * broken one. Any layout that requires this column to be populated is the wrong
 * layout, for the same reason the homepage was built not to depend on
 * photography.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->string('image_path')->nullable()->after('description');
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn('image_path');
        });
    }
};
