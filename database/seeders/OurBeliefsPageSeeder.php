<?php

namespace Database\Seeders;

use App\Models\Page;
use Illuminate\Database\Seeder;

class OurBeliefsPageSeeder extends Seeder
{
    public function run(): void
    {
        Page::updateOrCreate(
            ['slug' => 'about/beliefs'],
            [
                'title' => 'Our Beliefs - UPCI New Zealand',
                'meta_description' => 'The fundamental doctrines that guide our faith and practice as Oneness Pentecostals.',
                'is_published' => true,
                'sort_order' => 4,
                'content' => [
                    // Hero Section
                    [
                        'type' => 'hero',
                        'data' => [
                            'heading' => 'Our Beliefs',
                            'subheading' => 'The fundamental doctrines that guide our faith and practice as Oneness Pentecostals.',
                            'style' => 'gradient-slate',
                            'background_image' => null,
                            'button1_text' => null,
                            'button1_url' => null,
                            'button2_text' => null,
                            'button2_url' => null,
                        ],
                    ],

                    // Two Column: Core Doctrines and Essential Practices
                    [
                        'type' => 'two_column',
                        'data' => [
                            'left_content' => "## Core Doctrines\n\n### The Oneness of God\n\nWe believe in one God who has revealed Himself as Father, Son, and Holy Spirit. These are not three separate persons but three manifestations of the one true God.\n\n> \"Hear, O Israel: The LORD our God, the LORD is one.\"\n> *— Deuteronomy 6:4*\n\n### The Deity of Jesus Christ\n\nWe believe that Jesus Christ is God manifested in flesh. He is both fully God and fully man, the Savior of the world who died for our sins and rose again.\n\n> \"And without controversy great is the mystery of godliness: God was manifest in the flesh...\"\n> *— 1 Timothy 3:16*\n\n### Salvation Through Jesus Christ\n\nWe believe that salvation comes through faith in Jesus Christ, repentance from sin, baptism in Jesus' name, and receiving the Holy Ghost.\n\n> \"Repent, and be baptized every one of you in the name of Jesus Christ for the remission of sins, and ye shall receive the gift of the Holy Ghost.\"\n> *— Acts 2:38*",
                            'right_content' => "## Essential Practices\n\n### Water Baptism\n\nWe baptize by immersion in the name of Jesus Christ for the remission of sins, following the apostolic pattern established in the New Testament.\n\n> \"And he commanded them to be baptized in the name of the Lord.\"\n> *— Acts 10:48*\n\n### Holy Ghost Baptism\n\nWe believe in the necessity of receiving the Holy Ghost with the evidence of speaking in tongues, as experienced by the early church on the Day of Pentecost.\n\n> \"And they were all filled with the Holy Ghost, and began to speak with other tongues...\"\n> *— Acts 2:4*\n\n### Holy Living\n\nWe believe in living a holy life separated from the world, following biblical standards of dress, behavior, and lifestyle that honor God.\n\n> \"But as he which hath called you is holy, so be ye holy in all manner of conversation.\"\n> *— 1 Peter 1:15*",
                        ],
                    ],

                    // Additional Beliefs
                    [
                        'type' => 'cards',
                        'data' => [
                            'heading' => 'Additional Beliefs',
                            'items' => [
                                [
                                    'type' => 'card',
                                    'data' => [
                                        'title' => 'Divine Healing',
                                        'description' => 'We believe in divine healing through faith and prayer.',
                                        'icon_svg' => '<svg class="w-4 h-4 text-green-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path></svg>',
                                        'link_url' => null,
                                        'link_text' => null,
                                    ],
                                ],
                                [
                                    'type' => 'card',
                                    'data' => [
                                        'title' => 'Second Coming',
                                        'description' => 'We believe in the imminent return of Jesus Christ.',
                                        'icon_svg' => '<svg class="w-4 h-4 text-green-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path></svg>',
                                        'link_url' => null,
                                        'link_text' => null,
                                    ],
                                ],
                                [
                                    'type' => 'card',
                                    'data' => [
                                        'title' => 'Resurrection',
                                        'description' => 'We believe in the resurrection of the dead and eternal life.',
                                        'icon_svg' => '<svg class="w-4 h-4 text-green-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path></svg>',
                                        'link_url' => null,
                                        'link_text' => null,
                                    ],
                                ],
                                [
                                    'type' => 'card',
                                    'data' => [
                                        'title' => 'Tithing',
                                        'description' => 'We believe in the biblical principle of tithing and offerings.',
                                        'icon_svg' => '<svg class="w-4 h-4 text-green-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path></svg>',
                                        'link_url' => null,
                                        'link_text' => null,
                                    ],
                                ],
                                [
                                    'type' => 'card',
                                    'data' => [
                                        'title' => 'Evangelism',
                                        'description' => 'We believe in the Great Commission to spread the gospel.',
                                        'icon_svg' => '<svg class="w-4 h-4 text-green-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path></svg>',
                                        'link_url' => null,
                                        'link_text' => null,
                                    ],
                                ],
                                [
                                    'type' => 'card',
                                    'data' => [
                                        'title' => 'Church Government',
                                        'description' => 'We believe in apostolic church government and authority.',
                                        'icon_svg' => '<svg class="w-4 h-4 text-green-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path></svg>',
                                        'link_url' => null,
                                        'link_text' => null,
                                    ],
                                ],
                            ],
                        ],
                    ],

                    // CTA
                    [
                        'type' => 'cta',
                        'data' => [
                            'heading' => 'Learn More About Our Leadership',
                            'text' => 'Meet the dedicated leaders serving our churches across New Zealand.',
                            'button_text' => 'Meet Our Leadership',
                            'button_url' => '/about/leadership',
                            'style' => 'blue',
                        ],
                    ],
                ],
            ]
        );

        $this->command->info('Our Beliefs page created successfully!');
    }
}
