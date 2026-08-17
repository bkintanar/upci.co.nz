<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

/**
 * Departments get their own logo, separate from the existing `hero_image`.
 *
 * These are different things: hero_image is the wide banner behind the
 * department page header, logo_path is the mark used in listings, cards and
 * nav. Requirement 1b asks for the logo specifically, with a fallback to the
 * site logo when a department has none.
 *
 * Nullable, so the fallback is expressible and the column adds cleanly to a
 * populated table on SQLite.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('departments', function (Blueprint $table) {
            $table->string('logo_path')->nullable()->after('hero_image');
        });
    }

    public function down(): void
    {
        Schema::table('departments', function (Blueprint $table) {
            $table->dropColumn('logo_path');
        });
    }
};
