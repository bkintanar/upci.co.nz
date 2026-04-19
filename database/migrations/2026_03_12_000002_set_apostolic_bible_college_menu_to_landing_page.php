<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Link Apostolic Bible College parent to the ABC landing page.
     */
    public function up(): void
    {
        DB::table('menu_items')
            ->where('location', 'header')
            ->where('label', 'Apostolic Bible College')
            ->whereNull('parent_id')
            ->update(['url' => '/apostolic-bible-college']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('menu_items')
            ->where('location', 'header')
            ->where('label', 'Apostolic Bible College')
            ->whereNull('parent_id')
            ->update(['url' => '#']);
    }
};
