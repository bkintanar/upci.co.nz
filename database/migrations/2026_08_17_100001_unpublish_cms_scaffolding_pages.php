<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Migrations\Migration;

/**
 * `welcome` and `about-cms` are the sample pages the CMS shipped with. Both
 * were still published, so the SPA catch-all served them to the public at
 * /welcome and /about-cms.
 *
 * A data migration rather than an admin click: the SQLite file is no longer
 * tracked in git, so migrations are now the only mechanism by which a content
 * change reaches another checkout. Follows the DB::table() idiom already used
 * by the five menu_items migrations in this directory.
 */
return new class extends Migration
{
    private const SLUGS = ['welcome', 'about-cms'];

    public function up(): void
    {
        DB::table('pages')
            ->whereIn('slug', self::SLUGS)
            ->update(['is_published' => false, 'updated_at' => now()]);
    }

    public function down(): void
    {
        DB::table('pages')
            ->whereIn('slug', self::SLUGS)
            ->update(['is_published' => true, 'updated_at' => now()]);
    }
};
