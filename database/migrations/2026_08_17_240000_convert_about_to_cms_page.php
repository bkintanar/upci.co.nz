<?php

use App\Models\Page;
use Illuminate\Database\Migrations\Migration;

/**
 * `/about` becomes a CMS page (T66, G12).
 *
 * It was live, routed, and 100% hard-coded — 85 lines of markup that nobody
 * without a deploy could change, sitting in front of five sub-pages that are
 * all already CMS-managed. The hub was the only part of the About section its
 * own editors could not edit.
 *
 * Content is carried across verbatim rather than rewritten. The conversion is
 * about who can change it, not about changing it.
 *
 * The General Superintendent block is included as it stands. Its MENU link was
 * removed in T44 and its route deliberately kept, so whether the hub should
 * still feature it is a judgement for the client — and once this is CMS
 * content, removing it is one click rather than another deploy.
 */
return new class extends Migration
{
    private const SLUG = 'about';

    public function up(): void
    {
        if (Page::where('slug', self::SLUG)->exists()) {
            return;
        }

        Page::create([
            'slug' => self::SLUG,
            'title' => 'About UPCI New Zealand',
            'meta_description' => 'Discover more about the United Pentecostal Church International and our presence in New Zealand.',
            'is_published' => true,
            'content' => [
                [
                    'type' => 'hero',
                    'data' => [
                        'heading' => 'About UPCI New Zealand',
                        'subheading' => 'Discover more about the United Pentecostal Church International and our presence in New Zealand.',
                        'style' => 'gradient-indigo',
                        'background_image' => null,
                    ],
                ],
                [
                    'type' => 'cards',
                    'data' => [
                        'heading' => null,
                        'background' => 'slate',
                        'style' => 'default',
                        'columns' => 2,
                        'items' => [
                            [
                                'type' => 'card',
                                'data' => [
                                    'icon' => null, 'icon_svg' => null, 'variant' => null, 'bio' => null,
                                    'title' => 'About the UPCI',
                                    'description' => 'Learn about the history, mission, and global impact of the United Pentecostal Church International.',
                                    'link_url' => '/about/upci',
                                    'link_text' => 'Learn more',
                                ],
                            ],
                            [
                                'type' => 'card',
                                'data' => [
                                    'icon' => null, 'icon_svg' => null, 'variant' => null, 'bio' => null,
                                    'title' => 'Oneness Pentecostalism',
                                    'description' => 'Understand the foundational beliefs that unite us as Oneness Pentecostals.',
                                    'link_url' => '/about/oneness-pentecostalism',
                                    'link_text' => 'Learn more',
                                ],
                            ],
                            [
                                'type' => 'card',
                                'data' => [
                                    'icon' => null, 'icon_svg' => null, 'variant' => null, 'bio' => null,
                                    'title' => 'Our Beliefs',
                                    'description' => 'Explore the fundamental doctrines that guide our faith and practice.',
                                    'link_url' => '/about/beliefs',
                                    'link_text' => 'Learn more',
                                ],
                            ],
                            [
                                'type' => 'card',
                                'data' => [
                                    'icon' => null, 'icon_svg' => null, 'variant' => null, 'bio' => null,
                                    'title' => 'Leadership',
                                    'description' => 'Meet the dedicated leaders who guide UPCI New Zealand.',
                                    'link_url' => '/about/leadership',
                                    'link_text' => 'Learn more',
                                ],
                            ],
                        ],
                    ],
                ],
                [
                    'type' => 'text',
                    'data' => [
                        'heading' => 'General Superintendent',
                        'background' => 'white',
                        'style' => 'default',
                        'content' => 'Meet the spiritual leader who guides the worldwide UPCI organization and learn about '
                            ."their role in providing leadership to our global family.\n\n"
                            .'[Learn about our General Superintendent](/about/general-superintendent)',
                    ],
                ],
                [
                    'type' => 'cta',
                    'data' => [
                        'heading' => 'Get involved',
                        'text' => 'Find a department, a region, or a church near you.',
                        'button_text' => 'Get involved',
                        'button_url' => '/departments',
                        'style' => 'blue',
                    ],
                ],
            ],
        ]);
    }

    public function down(): void
    {
        Page::where('slug', self::SLUG)->delete();
    }
};
