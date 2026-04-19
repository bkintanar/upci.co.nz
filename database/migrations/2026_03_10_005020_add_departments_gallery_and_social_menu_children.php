<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $dep = DB::table('menu_items')
            ->where('location', 'header')
            ->where('label', 'Departments')
            ->whereNull('parent_id')
            ->first();

        if ($dep) {
            DB::table('menu_items')->where('id', $dep->id)->update(['url' => '#']);

            if (! DB::table('menu_items')->where('parent_id', $dep->id)->where('label', 'Gallery')->exists()) {
                DB::table('menu_items')->insert([
                    'label' => 'Gallery',
                    'url' => '/departments#gallery',
                    'location' => 'header',
                    'sort_order' => 1,
                    'is_active' => true,
                    'open_in_new_tab' => false,
                    'parent_id' => $dep->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
            if (! DB::table('menu_items')->where('parent_id', $dep->id)->where('label', 'Social')->exists()) {
                DB::table('menu_items')->insert([
                    'label' => 'Social',
                    'description' => 'Follow us on social media',
                    'url' => 'https://www.facebook.com',
                    'location' => 'header',
                    'sort_order' => 2,
                    'is_active' => true,
                    'open_in_new_tab' => true,
                    'parent_id' => $dep->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        $dep = DB::table('menu_items')
            ->where('location', 'header')
            ->where('label', 'Departments')
            ->whereNull('parent_id')
            ->first();
        if ($dep) {
            DB::table('menu_items')->where('id', $dep->id)->update(['url' => '/departments']);
            DB::table('menu_items')->where('parent_id', $dep->id)->delete();
        }
    }
};
