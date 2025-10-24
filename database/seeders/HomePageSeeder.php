<?php

namespace Database\Seeders;

use App\Models\Page;
use Illuminate\Database\Seeder;

class HomePageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create a CMS version of the home page
        Page::create([
            'title' => 'Home - UPCI New Zealand',
            'slug' => 'home',
            'meta_description' => 'Welcome to UPCI New Zealand. Building strong Christian communities across New Zealand through faith, fellowship, and service.',
            'is_published' => true,
            'sort_order' => 0,
            'content' => [
                // Hero Section
                [
                    'type' => 'hero',
                    'data' => [
                        'heading' => 'Welcome to UPCI New Zealand',
                        'subheading' => 'Building strong Christian communities across New Zealand through faith, fellowship, and service.',
                        'style' => 'gradient-slate',
                        'button1_text' => 'Learn More',
                        'button1_url' => '/about/upci',
                        'button2_text' => 'Get Involved',
                        'button2_url' => '/get-involved',
                    ],
                ],

                // About Section with heading
                [
                    'type' => 'text',
                    'data' => [
                        'heading' => 'Our Mission',
                        'content' => 'The United Pentecostal Church International is a Oneness Pentecostal organization with churches, ministers, and members across the globe. Our New Zealand district is committed to spreading the gospel and building strong Christian communities.',
                    ],
                ],

                // Three Cards - Beliefs, Community, Ministry
                [
                    'type' => 'cards',
                    'data' => [
                        'heading' => '',
                        'items' => [
                            [
                                'type' => 'card',
                                'data' => [
                                    'title' => 'Our Beliefs',
                                    'description' => "We believe in the Oneness of God and the necessity of baptism in Jesus' name and receiving the Holy Ghost.",
                                    'icon_svg' => '<svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path></svg>',
                                    'link_url' => '/about/beliefs',
                                    'link_text' => 'Learn More',
                                ],
                            ],
                            [
                                'type' => 'card',
                                'data' => [
                                    'title' => 'Community',
                                    'description' => 'Join our vibrant community of believers across New Zealand, united in faith and purpose.',
                                    'icon_svg' => '<svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>',
                                    'link_url' => '/find-church',
                                    'link_text' => 'Find a Church',
                                ],
                            ],
                            [
                                'type' => 'card',
                                'data' => [
                                    'title' => 'Ministry',
                                    'description' => "Get involved in various ministries including children, youth, men's, and women's programs.",
                                    'icon_svg' => '<svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>',
                                    'link_url' => '/get-involved',
                                    'link_text' => 'Get Involved',
                                ],
                            ],
                        ],
                    ],
                ],

                // Stats Section
                [
                    'type' => 'text',
                    'data' => [
                        'heading' => 'Our Impact',
                        'content' => "Making a difference in communities across New Zealand\n\n- **200+** Countries Worldwide\n- **6M+** Members Globally\n- **40K+** Churches Total\n- **NZ** Growing in New Zealand",
                    ],
                ],

                // Call to Action
                [
                    'type' => 'cta',
                    'data' => [
                        'heading' => 'Ready to Get Involved?',
                        'text' => 'Discover how you can be part of our mission and connect with your local UPCI church.',
                        'button_text' => 'Explore Ministries',
                        'button_url' => '/get-involved',
                        'style' => 'blue',
                    ],
                ],
            ],
        ]);
    }
}
