<?php

namespace Database\Seeders;

use App\Models\Page;
use Illuminate\Database\Seeder;

class LeadershipPageSeeder extends Seeder
{
    public function run(): void
    {
        Page::updateOrCreate(
            ['slug' => 'about/leadership'],
            [
                'title' => 'Leadership - UPCI New Zealand',
                'meta_description' => 'Meet the dedicated leaders who guide and serve the UPCI New Zealand district.',
                'is_published' => true,
                'sort_order' => 5,
                'content' => [
                    // Hero Section
                    [
                        'type' => 'hero',
                        'data' => [
                            'heading' => 'Leadership',
                            'subheading' => 'Meet the dedicated leaders who guide and serve the UPCI New Zealand district.',
                            'style' => 'gradient-slate',
                            'background_image' => null,
                            'button1_text' => null,
                            'button1_url' => null,
                            'button2_text' => null,
                            'button2_url' => null,
                        ],
                    ],

                    // District Leadership Cards
                    [
                        'type' => 'cards',
                        'data' => [
                            'heading' => 'District Leadership',
                            'items' => [
                                [
                                    'type' => 'card',
                                    'data' => [
                                        'title' => 'District Superintendent',
                                        'description' => 'Leading the UPCI New Zealand district with vision and dedication. Oversees all district operations and provides spiritual leadership.',
                                        'icon_svg' => '<svg class="w-12 h-12 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>',
                                        'link_url' => null,
                                        'link_text' => null,
                                    ],
                                ],
                                [
                                    'type' => 'card',
                                    'data' => [
                                        'title' => 'District Secretary',
                                        'description' => 'Managing administrative affairs and maintaining records. Handles correspondence, minutes, and official documentation.',
                                        'icon_svg' => '<svg class="w-12 h-12 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>',
                                        'link_url' => null,
                                        'link_text' => null,
                                    ],
                                ],
                                [
                                    'type' => 'card',
                                    'data' => [
                                        'title' => 'District Treasurer',
                                        'description' => 'Overseeing financial matters and stewardship. Manages district finances and ensures proper accounting.',
                                        'icon_svg' => '<svg class="w-12 h-12 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path></svg>',
                                        'link_url' => null,
                                        'link_text' => null,
                                    ],
                                ],
                            ],
                        ],
                    ],

                    // Ministry Leaders Text Block
                    [
                        'type' => 'text',
                        'data' => [
                            'heading' => 'Ministry Leaders',
                            'content' => '',
                        ],
                    ],

                    // Ministry Leaders - Blue Cards (Left Column)
                    [
                        'type' => 'cards',
                        'data' => [
                            'heading' => null,
                            'items' => [
                                [
                                    'type' => 'card',
                                    'data' => [
                                        'title' => 'Youth Ministry',
                                        'description' => 'Leading the next generation in faith and service. Organizes youth events, camps, and discipleship programs.',
                                        'icon_svg' => 'blue-ministry',
                                        'link_url' => null,
                                        'link_text' => null,
                                    ],
                                ],
                                [
                                    'type' => 'card',
                                    'data' => [
                                        'title' => "Women's Ministry",
                                        'description' => "Empowering women in their walk with God. Coordinates women's conferences, Bible studies, and outreach.",
                                        'icon_svg' => 'blue-ministry',
                                        'link_url' => null,
                                        'link_text' => null,
                                    ],
                                ],
                                [
                                    'type' => 'card',
                                    'data' => [
                                        'title' => "Men's Ministry",
                                        'description' => 'Building strong men of God and character. Facilitates men\'s retreats, accountability groups, and service projects.',
                                        'icon_svg' => 'blue-ministry',
                                        'link_url' => null,
                                        'link_text' => null,
                                    ],
                                ],
                                [
                                    'type' => 'card',
                                    'data' => [
                                        'title' => "Children's Ministry",
                                        'description' => "Nurturing young hearts and minds in the faith. Provides Sunday school, VBS, and children's events.",
                                        'icon_svg' => 'green-ministry',
                                        'link_url' => null,
                                        'link_text' => null,
                                    ],
                                ],
                                [
                                    'type' => 'card',
                                    'data' => [
                                        'title' => 'Music Ministry',
                                        'description' => 'Leading worship and praise in our services. Coordinates choirs, bands, and special music programs.',
                                        'icon_svg' => 'green-ministry',
                                        'link_url' => null,
                                        'link_text' => null,
                                    ],
                                ],
                                [
                                    'type' => 'card',
                                    'data' => [
                                        'title' => 'Evangelism Ministry',
                                        'description' => 'Spreading the gospel throughout New Zealand. Organizes outreach events, street ministry, and missions.',
                                        'icon_svg' => 'green-ministry',
                                        'link_url' => null,
                                        'link_text' => null,
                                    ],
                                ],
                            ],
                        ],
                    ],

                    // Leadership Principles
                    [
                        'type' => 'two_column',
                        'data' => [
                            'left_content' => "## Leadership Principles\n\n### Servant Leadership\n\nOur leaders follow the example of Jesus Christ, who came not to be served but to serve. They lead with humility, compassion, and a heart for the people.",
                            'right_content' => "## &nbsp;\n\n### Spiritual Authority\n\nLeadership in UPCI is based on spiritual maturity, biblical knowledge, and a proven track record of faithfulness in ministry and service.",
                        ],
                    ],

                    // CTA
                    [
                        'type' => 'cta',
                        'data' => [
                            'heading' => 'Learn More About Our Leadership',
                            'text' => 'Discover the history and vision of our General Superintendent.',
                            'button_text' => 'Learn About Our General Superintendent',
                            'button_url' => '/about/general-superintendent',
                            'style' => 'blue',
                        ],
                    ],
                ],
            ]
        );

        $this->command->info('Leadership page created successfully!');
    }
}
