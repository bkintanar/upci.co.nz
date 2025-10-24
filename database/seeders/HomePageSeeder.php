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
                        'style' => 'gradient-blue',
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
                                    'link_url' => '/about/beliefs',
                                    'link_text' => 'Learn More',
                                ],
                            ],
                            [
                                'type' => 'card',
                                'data' => [
                                    'title' => 'Community',
                                    'description' => 'Join our vibrant community of believers across New Zealand, united in faith and purpose.',
                                    'link_url' => '/find-church',
                                    'link_text' => 'Find a Church',
                                ],
                            ],
                            [
                                'type' => 'card',
                                'data' => [
                                    'title' => 'Ministry',
                                    'description' => "Get involved in various ministries including children, youth, men's, and women's programs.",
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
