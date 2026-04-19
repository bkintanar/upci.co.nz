<?php

namespace Database\Seeders;

use App\Models\Page;
use Illuminate\Database\Seeder;

class ApostolicBibleCollegePageSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedLandingPage();
        $this->seedPrincipalsCorner();
        $this->seedEnrollment();
    }

    private function seedLandingPage(): void
    {
        Page::updateOrCreate(
            ['slug' => 'apostolic-bible-college'],
            [
                'title' => 'Apostolic Bible College - UPCI New Zealand',
                'meta_description' => 'Apostolic Bible College of New Zealand – online ministry training since the 1970s. Join the legacy.',
                'is_published' => true,
                'sort_order' => 19,
                'content' => [
                    [
                        'type' => 'hero',
                        'data' => [
                            'heading' => 'Apostolic Bible College',
                            'subheading' => 'New Zealand — Training and equipping for ministry since the 1970s',
                            'style' => 'gradient-blue',
                            'background_image' => null,
                            'button1_text' => "Principal's Corner",
                            'button1_url' => '/apostolic-bible-college/principals-corner',
                            'button2_text' => 'Register',
                            'button2_url' => '/apostolic-bible-college/enrollment',
                        ],
                    ],
                    [
                        'type' => 'text',
                        'data' => [
                            'heading' => 'About Apostolic Bible College',
                            'content' => "Apostolic Bible College of New Zealand has enjoyed a long history in New Zealand with roots stretching back to the 1970's. It has adapted over the decades and is now a **completely online school**.\n\nABC utilizes a leading learning management system for students to attend classes and submit work, with opportunities for students and faculty to interact. Base-level classes cover fundamental doctrine; as you progress, you'll experience deeper subject matter that will challenge and equip you for ministry.",
                        ],
                    ],
                    [
                        'type' => 'two_column',
                        'data' => [
                            'left_content' => "## Join the Legacy!\n\nBe part of a movement that has trained generations of ministers across New Zealand and the world.",
                            'right_content' => "### #itsstillharvesttime2023\n\n*Follow the conversation and share your journey.*",
                        ],
                    ],
                    [
                        'type' => 'cards',
                        'data' => [
                            'heading' => 'Explore',
                            'items' => [
                                [
                                    'type' => 'card',
                                    'data' => [
                                        'title' => "Principal's Corner",
                                        'description' => 'A message from Rev. Brandon Borders, Principal of Apostolic Bible College.',
                                        'icon_svg' => 'blue-ministry',
                                        'link_url' => '/apostolic-bible-college/principals-corner',
                                        'link_text' => 'Read more',
                                    ],
                                ],
                                [
                                    'type' => 'card',
                                    'data' => [
                                        'title' => 'Register',
                                        'description' => 'First-time, returning, or foundation level — find the right form and get started.',
                                        'icon_svg' => 'green-ministry',
                                        'link_url' => '/apostolic-bible-college/enrollment',
                                        'link_text' => 'Go to registration',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ]
        );

        $this->command->info('Apostolic Bible College landing page created.');
    }

    private function seedPrincipalsCorner(): void
    {
        Page::updateOrCreate(
            ['slug' => 'apostolic-bible-college/principals-corner'],
            [
                'title' => "Principal's Corner - Apostolic Bible College",
                'meta_description' => 'A message from Rev. Brandon Borders, Principal of Apostolic Bible College, UPCI New Zealand.',
                'is_published' => true,
                'sort_order' => 20,
                'content' => [
                    [
                        'type' => 'hero',
                        'data' => [
                            'heading' => "Principal's Corner",
                            'subheading' => 'Rev. Brandon Borders',
                            'style' => 'gradient-indigo',
                            'background_image' => null,
                            'button1_text' => 'Register',
                            'button1_url' => '/apostolic-bible-college/enrollment',
                            'button2_text' => null,
                            'button2_url' => null,
                        ],
                    ],
                    [
                        'type' => 'two_column',
                        'data' => [
                            'left_content' => "## Background\n\nBrandon Borders attended Bible college at **Gateway College of Evangelism** in St. Louis, Missouri. He went on to intern and serve in ministry at churches in Odessa, Texas and Norman, Oklahoma.\n\nHe and his family arrived in New Zealand as missionaries in **2017** and served as pastor of Grace Fellowship in Hamilton before planting **Apostolic Life Church** in Tauranga.",
                            'right_content' => "## Apostolic Bible College Principal\n\n*Rev. Brandon Borders leads ABC with a heart for training the next generation of ministers.*",
                        ],
                    ],
                    [
                        'type' => 'text',
                        'data' => [
                            'heading' => "Principal's Message",
                            'content' => '#itsstillharvesttime2023',
                        ],
                    ],
                    [
                        'type' => 'cta',
                        'data' => [
                            'heading' => 'Ready to study with us?',
                            'text' => 'Register for Apostolic Bible College — first-time, returning, or foundation level.',
                            'button_text' => 'Go to registration',
                            'button_url' => '/apostolic-bible-college/enrollment',
                            'style' => 'purple',
                        ],
                    ],
                ],
            ]
        );

        $this->command->info("Principal's Corner page created.");
    }

    private function seedEnrollment(): void
    {
        Page::updateOrCreate(
            ['slug' => 'apostolic-bible-college/enrollment'],
            [
                'title' => 'Register - Apostolic Bible College',
                'meta_description' => 'Register for Apostolic Bible College. First-time students, returning students, and foundation level.',
                'is_published' => true,
                'sort_order' => 21,
                'content' => [
                    [
                        'type' => 'hero',
                        'data' => [
                            'heading' => 'Registration',
                            'subheading' => 'Choose your path below — all links open the official registration forms.',
                            'style' => 'gradient-indigo',
                            'background_image' => null,
                            'button1_text' => null,
                            'button1_url' => null,
                            'button2_text' => null,
                            'button2_url' => null,
                        ],
                    ],
                    [
                        'type' => 'text',
                        'data' => [
                            'heading' => null,
                            'content' => "Whether you're joining ABC for the first time, returning for a new term, or registering for foundation-level courses, use the card that matches your situation. Each link opens the correct Google Form in a new tab.",
                        ],
                    ],
                    [
                        'type' => 'cards',
                        'data' => [
                            'heading' => 'Choose your registration path',
                            'items' => [
                                [
                                    'type' => 'card',
                                    'data' => [
                                        'title' => 'First time student',
                                        'description' => 'New to Apostolic Bible College? Complete the first-time student registration form.',
                                        'icon_svg' => 'blue-ministry',
                                        'link_url' => 'https://forms.gle/iqVTh9YQH3nhZdw48',
                                        'link_text' => 'First Time Student Register HERE',
                                    ],
                                ],
                                [
                                    'type' => 'card',
                                    'data' => [
                                        'title' => 'Returning students',
                                        'description' => 'Already a student? Register for the new term here.',
                                        'icon_svg' => 'green-ministry',
                                        'link_url' => 'https://forms.gle/9nEDxXdJgGbtwr5m9',
                                        'link_text' => 'Returning Students Register HERE',
                                    ],
                                ],
                                [
                                    'type' => 'card',
                                    'data' => [
                                        'title' => 'Work exemption application',
                                        'description' => 'Apply for work exemption (opens the form in a new tab).',
                                        'icon_svg' => 'blue-ministry',
                                        'link_url' => 'https://forms.gle/iqVTh9YQH3nhZdw48',
                                        'link_text' => 'Work Exemption Application',
                                    ],
                                ],
                                [
                                    'type' => 'card',
                                    'data' => [
                                        'title' => 'Foundation level',
                                        'description' => 'Register for foundation-level courses.',
                                        'icon_svg' => 'green-ministry',
                                        'link_url' => 'https://forms.gle/DoBQxwMmbQfgduWq9',
                                        'link_text' => 'Foundation Level Register HERE',
                                    ],
                                ],
                                [
                                    'type' => 'card',
                                    'data' => [
                                        'title' => 'Foundation level course *NEW',
                                        'description' => 'New foundation-level course — register here.',
                                        'icon_svg' => 'blue-ministry',
                                        'link_url' => 'https://forms.gle/DoBQxwMmbQfgduWq9',
                                        'link_text' => 'Register HERE',
                                    ],
                                ],
                            ],
                        ],
                    ],
                    [
                        'type' => 'cta',
                        'data' => [
                            'heading' => 'Questions before you register?',
                            'text' => 'Read a message from our Principal or explore what ABC offers.',
                            'button_text' => "Principal's Corner",
                            'button_url' => '/apostolic-bible-college/principals-corner',
                            'style' => 'indigo',
                        ],
                    ],
                ],
            ]
        );

        $this->command->info('Enrollment page created.');
    }
}
