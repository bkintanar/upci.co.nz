<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

/**
 * A photo on a department announcement (owner request, 2026-10-15).
 *
 * Announcements were a title and a markdown body, so authors who had a flyer or
 * an event photo pasted a Facebook link or embed into the body instead: all
 * three live announcements did. There was nowhere to put the image itself.
 *
 * `image_path` follows the schema's own name for a disk-relative image
 * (`events.image_path`, `gallery_items.image_path`). `image_alt` holds the
 * description a screen reader reads, which the admin requires whenever a photo
 * is attached.
 *
 * Nullable on purpose and forever: an announcement without a photo is a normal
 * announcement, and the department page simply shows no image for it.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('department_announcements', function (Blueprint $table) {
            $table->string('image_path')->nullable()->after('content');
            $table->string('image_alt')->nullable()->after('image_path');
        });
    }

    public function down(): void
    {
        Schema::table('department_announcements', function (Blueprint $table) {
            $table->dropColumn(['image_path', 'image_alt']);
        });
    }
};
