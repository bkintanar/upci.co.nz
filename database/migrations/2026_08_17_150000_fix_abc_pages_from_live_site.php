<?php

use App\Models\Page;
use Illuminate\Database\Migrations\Migration;

/**
 * Requirement 4: reconcile the ABC section against the live site at
 * https://www.upci.co.nz/apostolic-bible-college.
 *
 * Two things were wrong.
 *
 * 1. The registration page carried FIVE cards where the live site offers four.
 *    "Foundation level" and "Foundation level course *NEW" are the same option
 *    pointing at the same form (forms.gle/DoBQxwMmbQfgduWq9), so a student saw
 *    the same destination twice under two names. Keeps the live site's wording
 *    and its ordering: first time, returning, foundation, work exemption.
 *
 * 2. The live section has four sub-pages — About, Principal's Corner, Register
 *    and Connect. Connect did not exist here at all.
 *
 * All four forms.gle URLs were verified against the live site and already
 * matched; they are deliberately left untouched.
 *
 * Guards on each row existing, so this no-ops under RefreshDatabase rather
 * than failing every test.
 */
return new class extends Migration
{
    private const REGISTER_SLUG = 'apostolic-bible-college/enrollment';

    private const CONNECT_SLUG = 'apostolic-bible-college/connect';

    public function up(): void
    {
        $this->dedupeRegistrationCards();
        $this->createConnectPage();
    }

    public function down(): void
    {
        // The duplicate card is not restored: it was a defect, and putting a
        // second link to the same form back would be reintroducing it.
        Page::where('slug', self::CONNECT_SLUG)->delete();
    }

    private function dedupeRegistrationCards(): void
    {
        $page = Page::where('slug', self::REGISTER_SLUG)->first();

        if (! $page) {
            return;
        }

        $blocks = is_string($page->content) ? json_decode($page->content, true) : $page->content;

        if (! is_array($blocks)) {
            return;
        }

        foreach ($blocks as $i => $block) {
            if (($block['type'] ?? null) !== 'cards') {
                continue;
            }

            $items = collect($block['data']['items'] ?? [])
                // Drop the older, less specific of the two foundation cards.
                ->reject(fn ($item) => ($item['data']['title'] ?? '') === 'Foundation level')
                ->values();

            // Match the live site's order.
            $order = [
                'First time student' => 0,
                'Returning students' => 1,
                'Foundation level course *NEW' => 2,
                'Work exemption application' => 3,
            ];

            $blocks[$i]['data']['items'] = $items
                ->sortBy(fn ($item) => $order[$item['data']['title'] ?? ''] ?? 99)
                ->values()
                ->all();
        }

        $page->update(['content' => $blocks]);
    }

    private function createConnectPage(): void
    {
        if (Page::where('slug', self::CONNECT_SLUG)->exists()) {
            return;
        }

        Page::create([
            'slug' => self::CONNECT_SLUG,
            'title' => 'Connect - Apostolic Bible College',
            'is_published' => true,
            'content' => [
                [
                    'type' => 'hero',
                    'data' => [
                        'heading' => 'Connect With Us',
                        'subheading' => 'Questions about Apostolic Bible College? Get in touch.',
                        'style' => 'gradient-indigo',
                        'background_image' => null,
                    ],
                ],
                [
                    'type' => 'text',
                    'data' => [
                        'heading' => 'Contact Us',
                        // The live page renders its body through an embedded
                        // widget that is not in the served HTML, so the exact
                        // wording could not be carried across. Points at this
                        // site's own contact form rather than inventing copy
                        // or duplicating a second form.
                        'content' => "Send us a message and someone from the college will get back to you.\n\n[Contact form](/contact)",
                    ],
                ],
                [
                    'type' => 'text',
                    'data' => [
                        'heading' => 'Prayer Request',
                        'content' => "Need prayer? Let us know and our team will stand with you.\n\n[Send a prayer request](/contact)",
                    ],
                ],
            ],
        ]);
    }
};
