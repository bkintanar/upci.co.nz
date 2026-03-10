<?php

namespace Database\Seeders;

use App\Models\MenuItem;
use Illuminate\Database\Seeder;

class MenuItemSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create "About the UPCI NZ" parent menu
        $aboutParent = MenuItem::create([
            'label' => 'About the UPCI NZ',
            'url' => '#',
            'location' => 'header',
            'sort_order' => 1,
            'is_active' => true,
            'open_in_new_tab' => false,
        ]);

        // Create "About the UPCI NZ" sub-menu items
        MenuItem::create([
            'label' => 'About the UPCI',
            'description' => 'Our history and mission',
            'url' => '/about/upci',
            'location' => 'header',
            'sort_order' => 1,
            'is_active' => true,
            'open_in_new_tab' => false,
            'parent_id' => $aboutParent->id,
        ]);

        MenuItem::create([
            'label' => 'Oneness Pentecostalism',
            'description' => 'Our foundational beliefs',
            'url' => '/about/oneness-pentecostalism',
            'location' => 'header',
            'sort_order' => 2,
            'is_active' => true,
            'open_in_new_tab' => false,
            'parent_id' => $aboutParent->id,
        ]);

        MenuItem::create([
            'label' => 'Our Beliefs',
            'description' => 'Core doctrines and practices',
            'url' => '/about/beliefs',
            'location' => 'header',
            'sort_order' => 3,
            'is_active' => true,
            'open_in_new_tab' => false,
            'parent_id' => $aboutParent->id,
        ]);

        MenuItem::create([
            'label' => 'Leadership',
            'description' => 'Meet our leaders',
            'url' => '/about/leadership',
            'location' => 'header',
            'sort_order' => 4,
            'is_active' => true,
            'open_in_new_tab' => false,
            'parent_id' => $aboutParent->id,
        ]);

        MenuItem::create([
            'label' => 'General Superintendent',
            'description' => 'Global leadership',
            'url' => '/about/general-superintendent',
            'location' => 'header',
            'sort_order' => 5,
            'is_active' => true,
            'open_in_new_tab' => false,
            'parent_id' => $aboutParent->id,
        ]);

        // Create top-level menu items
        MenuItem::create([
            'label' => 'Get Involved',
            'url' => '/get-involved',
            'location' => 'header',
            'sort_order' => 2,
            'is_active' => true,
            'open_in_new_tab' => false,
        ]);

        MenuItem::create([
            'label' => 'Find a Church',
            'url' => '/find-church',
            'location' => 'header',
            'sort_order' => 3,
            'is_active' => true,
            'open_in_new_tab' => false,
        ]);

        // Create the "Join Us" button (special styling in frontend)
        MenuItem::create([
            'label' => 'Join Us',
            'url' => '/get-involved',
            'location' => 'header',
            'sort_order' => 4,
            'is_active' => true,
            'open_in_new_tab' => false,
        ]);
    }
}
