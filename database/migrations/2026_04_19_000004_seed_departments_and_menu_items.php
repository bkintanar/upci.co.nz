<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $seedData = [
            [
                'name' => "Men's Department",
                'slug' => 'mens',
                'color_theme' => 'green',
                'description' => "# Men's Department\n\nPlaceholder — edit in admin.",
                'scripture_quote' => null,
                'sort_order' => 10,
            ],
            [
                'name' => "Ladies' Department",
                'slug' => 'ladies',
                'color_theme' => 'pink',
                'description' => "# Ladies' Department\n\nPlaceholder — edit in admin.",
                'scripture_quote' => null,
                'sort_order' => 20,
            ],
            [
                'name' => 'Missions Department',
                'slug' => 'missions',
                'color_theme' => 'blue',
                'description' => "# Missions Department\n\nPlaceholder — edit in admin.",
                'scripture_quote' => null,
                'sort_order' => 30,
            ],
            [
                'name' => 'Youth Ministry',
                'slug' => 'youth',
                'color_theme' => 'indigo',
                'description' => "# Youth Ministry\n\nPlaceholder — edit in admin.",
                'scripture_quote' => null,
                'sort_order' => 40,
            ],
            [
                'name' => "Children's Ministry",
                'slug' => 'childrens',
                'color_theme' => 'yellow',
                'description' => "# Children's Ministry\n\nPlaceholder — edit in admin.",
                'scripture_quote' => null,
                'sort_order' => 50,
            ],
            [
                'name' => 'Prayer Ministry',
                'slug' => 'prayer',
                'color_theme' => 'purple',
                'description' => "# Prayer Ministry\n\nPlaceholder — edit in admin.",
                'scripture_quote' => null,
                'sort_order' => 60,
            ],
        ];

        foreach ($seedData as $d) {
            DB::table('departments')->updateOrInsert(
                ['slug' => $d['slug']],
                $d + [
                    'is_published' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            );
        }

        $dep = DB::table('menu_items')
            ->where('location', 'header')
            ->where('label', 'Departments')
            ->whereNull('parent_id')
            ->first();

        if (! $dep) {
            return;
        }

        $children = [
            ['Mens Department', '/departments/mens', 10, 'Fellowship & discipleship for men'],
            ['Ladies Department', '/departments/ladies', 20, 'Fellowship & discipleship for women'],
            ['Missions Department', '/departments/missions', 30, 'UPCI NZ missions'],
            ['Youth Ministry', '/departments/youth', 40, 'Teens & young adults'],
            ["Children's Ministry", '/departments/childrens', 50, 'Kids of all ages'],
            ['Prayer Ministry', '/departments/prayer', 60, 'Prayer chain & intercession'],
        ];

        foreach ($children as [$label, $url, $sort, $desc]) {
            if (DB::table('menu_items')
                ->where('parent_id', $dep->id)
                ->where('label', $label)
                ->exists()) {
                continue;
            }

            DB::table('menu_items')->insert([
                'label' => $label,
                'description' => $desc,
                'url' => $url,
                'location' => 'header',
                'sort_order' => $sort,
                'is_active' => true,
                'open_in_new_tab' => false,
                'parent_id' => $dep->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
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
            DB::table('menu_items')
                ->where('parent_id', $dep->id)
                ->whereIn('url', [
                    '/departments/mens',
                    '/departments/ladies',
                    '/departments/missions',
                    '/departments/youth',
                    '/departments/childrens',
                    '/departments/prayer',
                ])
                ->delete();
        }

        DB::table('departments')
            ->whereIn('slug', ['mens', 'ladies', 'missions', 'youth', 'childrens', 'prayer'])
            ->delete();
    }
};
