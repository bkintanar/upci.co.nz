<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

/**
 * Requirement 9: separate the national calendar from regional events.
 *
 * The 49 existing rows are the 2026 national calendar, so they all backfill to
 * scope='national' with a null region. Region currently appears only as free
 * text inside event NAMES ("PM - Central Region, Waikato"), which nothing can
 * filter on — deliberately NOT parsed here. Guessing a region from a name
 * would write wrong data that later looks authoritative; assigning those rows
 * is T55, a data task for the client.
 *
 * `scope` is a plain string with a default rather than a DB-level enum:
 * SQLite has no enum type, and Laravel emulates it with a CHECK constraint
 * that later ALTERs cannot modify. The EventScope enum enforces the values in
 * PHP instead.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->string('scope', 16)->default('national')->after('department_id');
            $table->foreignId('region_id')
                ->nullable()
                ->after('scope')
                ->constrained('regions')
                ->nullOnDelete();
            $table->index('region_id');
            $table->index('scope');
        });

        // Explicit rather than relying on the column default, so the intent is
        // recorded for rows that already existed.
        DB::table('events')->update(['scope' => 'national', 'region_id' => null]);
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            // Drop the standalone indexes BEFORE the column they reference.
            // See 2026_04_19_000003_add_department_id_to_events_table.php —
            // leaving them makes SQLite abort with "no such column".
            $table->dropIndex(['scope']);
            $table->dropIndex(['region_id']);
            $table->dropConstrainedForeignId('region_id');
            $table->dropColumn('scope');
        });
    }
};
