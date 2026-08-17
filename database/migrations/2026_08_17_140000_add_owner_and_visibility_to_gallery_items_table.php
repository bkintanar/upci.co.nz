<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

/**
 * One gallery, three owners (requirement 2): departments, regions, and a
 * general gallery owned by nobody.
 *
 * Uses nullableMorphs, not morphs. `morphs()` is NOT NULL and aborts when the
 * table already has rows, which this one does.
 *
 * `is_published` is new: the table had no visibility column at all, so every
 * uploaded item was live the moment it was saved.
 *
 * The legacy free-text `department` column is deliberately KEPT. The single
 * existing row reads "Apostolic Bible College", which is not one of the six
 * `departments` rows — ABC is its own page, not a department. Rather than
 * invent a department to point it at, that row becomes a general-gallery item
 * (null owner) and keeps its original string, so nothing is lost if ABC later
 * gets a model of its own. Dropping the column is a separate decision once
 * every row has a real owner.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('gallery_items', function (Blueprint $table) {
            $table->nullableMorphs('galleryable');
            $table->boolean('is_published')->default(true)->after('image_path');
        });

        // Existing rows stay visible. Defaulting them to draft would empty the
        // live gallery on deploy.
        DB::table('gallery_items')->update(['is_published' => true]);
    }

    public function down(): void
    {
        Schema::table('gallery_items', function (Blueprint $table) {
            // nullableMorphs() creates a composite index; drop it before the
            // columns it covers. See the department_id migration for what
            // happens on SQLite when that order is wrong.
            $table->dropIndex(['galleryable_type', 'galleryable_id']);
            $table->dropColumn(['galleryable_type', 'galleryable_id', 'is_published']);
        });
    }
};
