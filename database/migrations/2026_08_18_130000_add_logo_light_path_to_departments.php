<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

/**
 * A second department logo, for dark backgrounds.
 *
 * `departments.logo_path` is consumed by three render sites across two
 * different background colours: the department hero (a dark brand-hue gradient
 * ending in brand-ink) and two card lists that sit on white. One column cannot
 * serve both, so the hero was drawing a dark-ink mark on a dark gradient.
 *
 * The supplied asset library already contains the pair — `resources/images/logos/`
 * holds three lockups per department, each with a `-WHITE` counterpart — so this
 * is a schema gap rather than a missing asset.
 *
 * Stored as an explicit path rather than derived from `logo_path` by string
 * substitution, because the supplied filenames are not consistent: missions is
 * `UPCINZ-MISSIONS-02.svg` (plural) against `UPCINZ-MISSION-02-WHITE.svg`
 * (singular), and `UPCINZ-MISSIONS-02-WHITE.svg` does not exist. A derivation
 * rule would 404 on exactly one of six departments and fall back silently.
 *
 * Nullable, and permanently so: a department without a light variant falls back
 * to its dark logo, which is imperfect on the hero but never blank.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('departments', function (Blueprint $table) {
            $table->string('logo_light_path')->nullable()->after('logo_path');
        });
    }

    public function down(): void
    {
        Schema::table('departments', function (Blueprint $table) {
            $table->dropColumn('logo_light_path');
        });
    }
};
