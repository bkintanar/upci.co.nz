<?php

namespace Database\Seeders;

use App\Models\Page;
use Illuminate\Database\Seeder;

class OnenessPentecostalismPageSeeder extends Seeder
{
    public function run(): void
    {
        Page::updateOrCreate(
            ['slug' => 'about/oneness-pentecostalism'],
            [
                'title' => 'Oneness Pentecostalism - Understanding Our Faith',
                'meta_description' => 'Understanding the foundational beliefs that unite us as Oneness Pentecostals.',
                'is_published' => true,
                'sort_order' => 3,
                'content' => [
                    // Hero Section
                    [
                        'type' => 'hero',
                        'data' => [
                            'heading' => 'Oneness Pentecostalism',
                            'subheading' => 'Understanding the foundational beliefs that unite us as Oneness Pentecostals.',
                            'style' => 'gradient-slate',
                            'background_image' => null,
                            'button1_text' => null,
                            'button1_url' => null,
                            'button2_text' => null,
                            'button2_url' => null,
                        ],
                    ],

                    // What is Oneness Pentecostalism
                    [
                        'type' => 'text',
                        'data' => [
                            'heading' => 'What is Oneness Pentecostalism?',
                            'content' => "Oneness Pentecostalism is a movement within Pentecostal Christianity that emphasizes the oneness of God, rejecting the traditional Trinitarian doctrine in favor of a modalistic view of the Godhead. We believe that God is one person who has manifested Himself in different modes or roles throughout history.\n\nThis belief is rooted in our understanding of Scripture and the early church's teachings about the nature of God.",
                        ],
                    ],

                    // Two Column: Key Beliefs and Biblical Foundation
                    [
                        'type' => 'two_column',
                        'data' => [
                            'left_content' => "## Key Beliefs\n\n### One God\nWe believe in one God who has revealed Himself as Father, Son, and Holy Spirit in different manifestations.\n\n### Jesus Name Baptism\nWe baptize in the name of Jesus Christ, following the apostolic pattern found in the New Testament.\n\n### Holy Ghost Baptism\nWe believe in the necessity of receiving the Holy Ghost with the evidence of speaking in tongues.",
                            'right_content' => "## Biblical Foundation\n\n> \"Hear, O Israel: The LORD our God, the LORD is one.\"\n> *— Deuteronomy 6:4*\n\n> \"For there are three that bear record in heaven, the Father, the Word, and the Holy Ghost: and these three are one.\"\n> *— 1 John 5:7 (KJV)*\n\n> \"And Jesus came and spake unto them, saying, All power is given unto me in heaven and in earth. Go ye therefore, and teach all nations, baptizing them in the name of the Father, and of the Son, and of the Holy Ghost.\"\n> *— Matthew 28:18-19*",
                        ],
                    ],

                    // The Oneness Message
                    [
                        'type' => 'text',
                        'data' => [
                            'heading' => 'The Oneness Message',
                            'content' => "The Oneness message is not just a theological position—it's a call to return to the apostolic faith and the simplicity of the gospel as preached by the early church. We believe this message brings clarity to understanding who God is and how we should worship Him.\n\nThis understanding shapes every aspect of our worship, ministry, and daily walk with God.",
                        ],
                    ],

                    // CTA
                    [
                        'type' => 'cta',
                        'data' => [
                            'heading' => 'Learn More About Our Beliefs',
                            'text' => 'Explore our complete statement of faith and doctrinal positions.',
                            'button_text' => 'Read Our Complete Beliefs',
                            'button_url' => '/about/beliefs',
                            'style' => 'blue',
                        ],
                    ],
                ],
            ]
        );

        $this->command->info('Oneness Pentecostalism page created successfully!');
    }
}
