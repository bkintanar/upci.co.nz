<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Apostolic Bible College is a dropdown-only parent; its URL should be # not /abc.
     */
    public function up(): void
    {
        DB::table('menu_items')
            ->where('location', 'header')
            ->where('label', 'Apostolic Bible College')
            ->whereNull('parent_id')
            ->update(['url' => '#']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Optional: restore /abc if you want to reverse
        DB::table('menu_items')
            ->where('location', 'header')
            ->where('label', 'Apostolic Bible College')
            ->whereNull('parent_id')
            ->update(['url' => '/abc']);
    }
};
