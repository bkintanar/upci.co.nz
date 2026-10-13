<?php

namespace Database\Seeders;

use App\Models\Page;
use Illuminate\Database\Seeder;

/**
 * The homepage for a fresh install: the upca-style layout authored for the live
 * site by 2026_10_14_000002_author_the_upca_style_homepage.
 *
 * firstOrCreate, not create: running this against a site that already has a
 * homepage must never overwrite what an editor has built in the CMS.
 */
class HomePageSeeder extends Seeder
{
    public function run(): void
    {
        Page::firstOrCreate(
            ['slug' => 'home'],
            [
                'title' => 'Home - UPCI New Zealand',
                'meta_description' => 'Welcome to UPCI New Zealand. Building strong Christian communities across New Zealand through faith, fellowship, and service.',
                'is_published' => true,
                'sort_order' => 0,
                'content' => [
                    [
                        'type' => 'hero',
                        'data' => [
                            'layout' => 'banner',
                            'eyebrow' => "This year's theme",
                            'heading' => 'Pentecost Everyday',
                            'subheading' => 'A Sustainable Annual Harvest — Going Beyond 2026',
                            'verse' => '“…and the Lord added to the church daily such as should be saved.” Acts 2:47',
                            'background_image' => 'page-images/01KPYX2XHW5DRFENE0NJ918RC6.jpg',
                            'image_alt' => null,
                            'style' => 'gradient-blue',
                        ],
                    ],
                    [
                        'type' => 'cta',
                        'data' => [
                            'style' => 'band',
                            'eyebrow' => 'Next',
                            'highlight' => '24–25',
                            'highlight_caption' => 'October',
                            'heading' => 'Annual General Conference',
                            'text' => 'Wellington · Saturday and Sunday',
                            'button_text' => 'See the calendar',
                            'button_url' => '/events',
                        ],
                    ],
                    [
                        'type' => 'feature',
                        'data' => [
                            'heading' => 'About the UPCI NZ',
                            'content' => 'The United Pentecostal Church International of New Zealand is a fellowship of '
                                .'apostolic congregations across the country, organised into the Northern, Central and '
                                .'Southern regions.',
                            'tagline' => 'Wherever you are, you are welcome.',
                            'button_text' => 'Read more',
                            'button_url' => '/about',
                            'image' => null,
                            'image_alt' => null,
                            'fallback_caption' => 'Ten congregations, from Whangarei in the north to Rolleston in the south.',
                            'image_side' => 'right',
                            'background' => 'paper',
                        ],
                    ],
                    [
                        'type' => 'church_finder',
                        'data' => [
                            'heading' => 'Find a church near you',
                            'placeholder' => 'Suburb, town or postcode',
                            'button_text' => 'Search',
                            'layout' => 'panel',
                            'description' => 'Congregations across the Northern, Central and Southern regions.',
                            'show_regions' => true,
                            'show_directory' => true,
                            'directory_heading' => 'Our congregations',
                            'help_heading' => 'Not sure where to start?',
                            'help_text' => "Tell us where you live and we'll point you to the nearest congregation.",
                            'help_link_text' => 'Connect with us',
                            'help_link_url' => '/connect-with-us',
                        ],
                    ],
                    [
                        'type' => 'events_feed',
                        'data' => [
                            'heading' => 'Coming up',
                            'scope' => 'national',
                            'limit' => 8,
                            'layout' => 'cards',
                            'more_text' => 'More events',
                            'more_url' => '/events',
                            'empty_message' => 'No events are scheduled at the moment.',
                        ],
                    ],
                    [
                        'type' => 'department_list',
                        'data' => [
                            'heading' => 'Our ministries',
                            'layout' => 'tiles',
                            'lede' => 'Six departments, one fellowship — each with its own page and its own place on the calendar.',
                            'empty_message' => 'Ministry information is being updated.',
                        ],
                    ],
                    [
                        'type' => 'cta',
                        'data' => [
                            'heading' => 'Connect with us',
                            'text' => "Questions, prayer requests, or looking for a church home — we'd love to hear from you.",
                            'button_text' => 'Get in touch',
                            'button_url' => '/connect-with-us',
                            'style' => 'panel',
                        ],
                    ],
                ],
            ]
        );
    }
}
