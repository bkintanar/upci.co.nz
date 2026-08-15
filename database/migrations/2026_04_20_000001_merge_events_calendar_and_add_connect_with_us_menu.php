<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('menu_items')
            ->where('location', 'header')
            ->where('label', 'Events')
            ->whereNull('parent_id')
            ->update([
                'label' => 'Calendar of Events',
                'url' => '/events',
                'updated_at' => now(),
            ]);

        DB::table('menu_items')
            ->where('location', 'header')
            ->where('label', 'Calendar')
            ->whereNull('parent_id')
            ->delete();

        $exists = DB::table('menu_items')
            ->where('location', 'header')
            ->where('label', 'Connect with Us')
            ->whereNull('parent_id')
            ->exists();

        if (! $exists) {
            DB::table('menu_items')->insert([
                'label' => 'Connect with Us',
                'url' => '/connect-with-us',
                'location' => 'header',
                'sort_order' => 7,
                'is_active' => true,
                'open_in_new_tab' => false,
                'parent_id' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        DB::table('menu_items')
            ->where('location', 'header')
            ->where('label', 'Connect with Us')
            ->whereNull('parent_id')
            ->delete();

        DB::table('menu_items')
            ->where('location', 'header')
            ->where('label', 'Calendar of Events')
            ->whereNull('parent_id')
            ->update([
                'label' => 'Events',
                'url' => '/events',
                'updated_at' => now(),
            ]);

        $calExists = DB::table('menu_items')
            ->where('location', 'header')
            ->where('label', 'Calendar')
            ->whereNull('parent_id')
            ->exists();

        if (! $calExists) {
            DB::table('menu_items')->insert([
                'label' => 'Calendar',
                'url' => '/calendar',
                'location' => 'header',
                'sort_order' => 6,
                'is_active' => true,
                'open_in_new_tab' => false,
                'parent_id' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
};
