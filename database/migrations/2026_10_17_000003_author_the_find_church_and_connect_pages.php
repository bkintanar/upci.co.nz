<?php

use App\Models\Page;
use Illuminate\Database\Migrations\Migration;

/**
 * /find-church and /connect-with-us as CMS pages (owner requests, 2026-10-17:
 * "make this editable in the CMS", and the contact form "also in CMS").
 *
 * Both pages were Vue views with every word in code. Their words move into
 * blocks here, unchanged: the church finder (church_locator, with its own
 * hero) and its closing call to action, and the contact form
 * (contact_form). The router sends both paths to CmsPage, which reads these
 * rows.
 *
 * firstOrCreate: a row an editor has already made is never overwritten.
 * down() leaves the rows alone. Once the router no longer points at the CMS
 * they are simply unused, and nothing is deleted.
 */
return new class extends Migration
{
    public function up(): void
    {
        Page::firstOrCreate(['slug' => 'find-church'], [
            'title' => 'Find a Church - UPCI New Zealand',
            'meta_description' => 'Find your nearest UPCI church in New Zealand, by region or by name.',
            'is_published' => true,
            'sort_order' => 0,
            'content' => [
                [
                    'type' => 'church_locator',
                    'data' => [
                        'eyebrow' => 'Find Your Local Church',
                        'heading' => 'Discover UPCI Churches',
                        'heading_highlight' => 'Across New Zealand',
                        'intro' => 'Connect with your local UPCI community. Find churches, service times, and contact information across New Zealand.',
                        'show_stats' => true,
                        'extra_stat_value' => '24/7',
                        'extra_stat_label' => 'Directory',
                        'search_placeholder' => 'Search by city, region, church name, or address...',
                    ],
                ],
                [
                    'type' => 'cta',
                    'data' => [
                        // 'blue' is the brand green (bg-brand-green-700), as
                        // the page's own closing section was.
                        'style' => 'blue',
                        'heading' => "Can't Find a Church Near You?",
                        'text' => "We're always looking to expand our ministry. Contact us to learn about starting a new UPCI church in your area.",
                        'button_text' => 'Contact Us About Starting a Church',
                        'button_url' => '/connect-with-us',
                        'eyebrow' => null,
                        'highlight' => null,
                        'highlight_caption' => null,
                    ],
                ],
            ],
        ]);

        Page::firstOrCreate(['slug' => 'connect-with-us'], [
            'title' => 'Connect with Us - UPCI New Zealand',
            // Shown under the page heading as well as used for search results.
            'meta_description' => "Have a question or would like to get in touch? Send us a message and we'll get back to you.",
            'is_published' => true,
            'sort_order' => 0,
            'content' => [
                [
                    'type' => 'contact_form',
                    'data' => [
                        'heading' => 'Get in touch',
                        'intro' => null,
                        'button_text' => 'Send',
                        'success_message' => 'Thank you. Your message has been sent.',
                    ],
                ],
            ],
        ]);
    }

    public function down(): void
    {
        // Deliberately nothing: delete nothing.
    }
};
