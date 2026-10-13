<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

/**
 * A postal address for the site footer. The footer's contact column could
 * only say "New Zealand"; the sister site's footer carries its headquarters
 * address, and that is what someone looking for a postal contact expects.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $table->text('postal_address')->nullable()->after('contact_email');
        });
    }

    public function down(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $table->dropColumn('postal_address');
        });
    }
};
