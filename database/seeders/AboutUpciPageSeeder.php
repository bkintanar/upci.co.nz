<?php

namespace Database\Seeders;

use App\Models\Page;
use Illuminate\Database\Seeder;

class AboutUpciPageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create About UPCI page
        Page::create([
            'title' => 'About the UPCI - United Pentecostal Church International',
            'slug' => 'about-upci',
            'meta_description' => 'Learn about the United Pentecostal Church International and our mission worldwide.',
            'is_published' => true,
            'sort_order' => 1,
            'content' => [
                // Hero/Intro Section
                [
                    'type' => 'hero',
                    'data' => [
                        'heading' => 'About the UPCI',
                        'subheading' => 'Learn about the United Pentecostal Church International and our mission worldwide.',
                        'style' => 'gradient-slate',
                    ],
                ],

                // Our History Section (Two Column)
                [
                    'type' => 'two_column',
                    'data' => [
                        'left_content' => "## Our History\n\nThe United Pentecostal Church International was formed in 1945 by the merger of the Pentecostal Church, Incorporated, and the Pentecostal Assemblies of Jesus Christ. Since then, UPCI has grown to become one of the largest Oneness Pentecostal organizations in the world.\n\nWith headquarters in Hazelwood, Missouri, UPCI serves millions of members across the globe, including our vibrant New Zealand district.",
                        'right_content' => "### Key Statistics\n\n- Over 40,000 churches worldwide\n- More than 6 million members globally\n- Active in over 200 countries\n- Strong presence in New Zealand",
                    ],
                ],

                // Our Mission Text
                [
                    'type' => 'text',
                    'data' => [
                        'heading' => 'Our Mission',
                        'content' => 'The UPCI is committed to fulfilling the Great Commission by spreading the gospel of Jesus Christ, establishing churches, training ministers, and providing fellowship and support for our members worldwide.',
                    ],
                ],

                // Mission Cards
                [
                    'type' => 'cards',
                    'data' => [
                        'heading' => '',
                        'items' => [
                            [
                                'type' => 'card',
                                'data' => [
                                    'title' => 'Evangelism',
                                    'description' => 'Spreading the gospel message to all nations and peoples.',
                                ],
                            ],
                            [
                                'type' => 'card',
                                'data' => [
                                    'title' => 'Discipleship',
                                    'description' => 'Training and equipping believers for ministry and service.',
                                ],
                            ],
                            [
                                'type' => 'card',
                                'data' => [
                                    'title' => 'Fellowship',
                                    'description' => 'Building strong Christian communities and relationships.',
                                ],
                            ],
                        ],
                    ],
                ],

                // Call to Action
                [
                    'type' => 'cta',
                    'data' => [
                        'heading' => 'Learn More',
                        'text' => 'Discover the foundational beliefs of Oneness Pentecostalism',
                        'button_text' => 'Learn About Oneness Pentecostalism',
                        'button_url' => '/about/oneness-pentecostalism',
                        'style' => 'blue',
                    ],
                ],
            ],
        ]);
    }
}
