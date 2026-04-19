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

        // Apostolic Bible College parent with sub-items (links to landing page)
        $abcParent = MenuItem::create([
            'label' => 'Apostolic Bible College',
            'url' => '/apostolic-bible-college',
            'location' => 'header',
            'sort_order' => 2,
            'is_active' => true,
            'open_in_new_tab' => false,
        ]);

        MenuItem::create([
            'label' => 'About',
            'description' => 'About ABC – follow us on social media',
            'url' => 'https://www.facebook.com',
            'location' => 'header',
            'sort_order' => 1,
            'is_active' => true,
            'open_in_new_tab' => true,
            'parent_id' => $abcParent->id,
        ]);

        MenuItem::create([
            'label' => "Principal's Corner",
            'description' => 'Message from the Principal',
            'url' => '/apostolic-bible-college/principals-corner',
            'location' => 'header',
            'sort_order' => 2,
            'is_active' => true,
            'open_in_new_tab' => false,
            'parent_id' => $abcParent->id,
        ]);

        MenuItem::create([
            'label' => 'Enrollment Registration',
            'description' => 'Enroll at Apostolic Bible College',
            'url' => '/apostolic-bible-college/enrollment',
            'location' => 'header',
            'sort_order' => 3,
            'is_active' => true,
            'open_in_new_tab' => false,
            'parent_id' => $abcParent->id,
        ]);

        // Departments (formerly Get Involved)
        MenuItem::create([
            'label' => 'Departments',
            'url' => '/departments',
            'location' => 'header',
            'sort_order' => 3,
            'is_active' => true,
            'open_in_new_tab' => false,
        ]);

        MenuItem::create([
            'label' => 'Find a Church',
            'url' => '/find-church',
            'location' => 'header',
            'sort_order' => 4,
            'is_active' => true,
            'open_in_new_tab' => false,
        ]);
    }
}
