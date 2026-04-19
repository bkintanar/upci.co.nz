<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('menu_items')
            ->where('location', 'header')
            ->where('label', 'Get Involved')
            ->whereNull('parent_id')
            ->update(['label' => 'Departments', 'url' => '/departments']);

        DB::table('menu_items')
            ->where('location', 'header')
            ->where('label', 'Join Us')
            ->whereNull('parent_id')
            ->delete();

        // Add Apostolic Bible College parent and children if not present
        $abcParent = DB::table('menu_items')
            ->where('location', 'header')
            ->where('label', 'Apostolic Bible College')
            ->whereNull('parent_id')
            ->first();

        if (! $abcParent) {
            $abcParentId = DB::table('menu_items')->insertGetId([
                'label' => 'Apostolic Bible College',
                'url' => '#',
                'location' => 'header',
                'sort_order' => 2,
                'is_active' => true,
                'open_in_new_tab' => false,
                'parent_id' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('menu_items')->insert([
                [
                    'label' => 'About',
                    'description' => 'About ABC – follow us on social media',
                    'url' => 'https://www.facebook.com',
                    'location' => 'header',
                    'sort_order' => 1,
                    'is_active' => true,
                    'open_in_new_tab' => true,
                    'parent_id' => $abcParentId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'label' => "Principal's Corner",
                    'description' => 'Message from the Principal',
                    'url' => '/apostolic-bible-college/principals-corner',
                    'location' => 'header',
                    'sort_order' => 2,
                    'is_active' => true,
                    'open_in_new_tab' => false,
                    'parent_id' => $abcParentId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'label' => 'Enrollment Registration',
                    'description' => 'Enroll at Apostolic Bible College',
                    'url' => '/apostolic-bible-college/enrollment',
                    'location' => 'header',
                    'sort_order' => 3,
                    'is_active' => true,
                    'open_in_new_tab' => false,
                    'parent_id' => $abcParentId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('menu_items')
            ->where('location', 'header')
            ->where('label', 'Departments')
            ->whereNull('parent_id')
            ->update(['label' => 'Get Involved', 'url' => '/get-involved']);

        $abcParent = DB::table('menu_items')
            ->where('location', 'header')
            ->where('label', 'Apostolic Bible College')
            ->whereNull('parent_id')
            ->first();

        if ($abcParent) {
            DB::table('menu_items')->where('parent_id', $abcParent->id)->delete();
            DB::table('menu_items')->where('id', $abcParent->id)->delete();
        }
    }
};
