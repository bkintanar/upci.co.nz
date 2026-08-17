<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

/**
 * Regions become editable content, not just a taxonomy.
 *
 * Requirement 11 asks each region to carry its own landing page: a logo, an
 * intro message and the presbyter's name. Until now `regions` held only
 * name/slug/sort_order, so the three region pages had nowhere to read from.
 *
 * `is_published` defaults to TRUE rather than false. The three live regions
 * already drive the church locator's filter, and defaulting them to draft
 * would blank that filter the moment this migration ran — the columns are
 * additive, so the existing behaviour has to survive them.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('regions', function (Blueprint $table) {
            $table->string('logo_path')->nullable()->after('slug');
            $table->text('intro')->nullable()->after('logo_path');
            $table->string('presbyter_name')->nullable()->after('intro');
            $table->boolean('is_published')->default(true)->after('presbyter_name');
        });
    }

    public function down(): void
    {
        Schema::table('regions', function (Blueprint $table) {
            $table->dropColumn(['logo_path', 'intro', 'presbyter_name', 'is_published']);
        });
    }
};
