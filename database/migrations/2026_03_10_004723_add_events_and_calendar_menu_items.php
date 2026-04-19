<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $exists = DB::table('menu_items')
            ->where('location', 'header')
            ->where('label', 'Events')
            ->whereNull('parent_id')
            ->exists();

        if (! $exists) {
            DB::table('menu_items')->insert([
                'label' => 'Events',
                'url' => '/events',
                'location' => 'header',
                'sort_order' => 5,
                'is_active' => true,
                'open_in_new_tab' => false,
                'parent_id' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $existsCal = DB::table('menu_items')
            ->where('location', 'header')
            ->where('label', 'Calendar')
            ->whereNull('parent_id')
            ->exists();

        if (! $existsCal) {
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

    public function down(): void
    {
        DB::table('menu_items')
            ->where('location', 'header')
            ->whereIn('label', ['Events', 'Calendar'])
            ->whereNull('parent_id')
            ->delete();
    }
};
