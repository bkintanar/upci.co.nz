<?php

use App\Models\Page;
use Illuminate\Database\Migrations\Migration;

/**
 * The upca-style homepage (owner request, 2026-10-14).
 *
 * The search-led D2 homepage was chosen on 2026-08-17 because no photography
 * existed then. The owner now wants the homepage to feel like the Australian
 * sister body's (www.upca.org.au): a theme banner, the next event, an About
 * section with a photo, the church finder mid-page, events as cards, and the
 * ministries given real presence. Plan:
 * .claude/PRPs/plans/homepage-upca-style.plan.md; design choice:
 * .claude/design/homepage-upca/direction-approved.md.
 *
 * D2's principle survives: all ten congregations are still named on the
 * homepage, now beside the church finder (church_finder.show_directory), so
 * the separate church_directory block folds into it.
 *
 * Guarded like the D2 migration that this mirrors: content has an editor, and
 * the editor wins. If the homepage no longer has the exact D2 block sequence,
 * someone has edited it in the CMS and it is left alone. The outgoing version
 * is archived as an unpublished page rather than lost.
 */
return new class extends Migration
{
    private const EXPECTED = ['church_finder', 'church_directory', 'text', 'events_feed', 'department_list', 'cta'];

    private const WRITTEN = ['hero', 'cta', 'feature', 'church_finder', 'events_feed', 'department_list', 'cta'];

    private const ARCHIVE = 'home-before-upca-style';

    public function up(): void
    {
        $page = Page::where('slug', 'home')->first();

        if (! $page) {
            return;
        }

        $current = $this->blocks($page);

        if ($this->types($current) !== self::EXPECTED) {
            return;
        }

        $find = fn (string $type): array => collect($current)->firstWhere('type', $type)['data'] ?? [];

        Page::updateOrCreate(
            ['slug' => self::ARCHIVE],
            [
                'title' => 'Home — before the upca-style rebuild',
                'content' => $current,
                'is_published' => false,
            ]
        );

        // "Who we are" ends on a one-line welcome; the feature block gives that
        // line its own emphasis, so it moves from the body to the tagline.
        $paragraphs = preg_split('/\n\s*\n/', trim($find('text')['content'] ?? ''));
        $tagline = count($paragraphs) > 1 ? array_pop($paragraphs) : null;

        $directory = $find('church_directory');

        // The one cta in the D2 sequence is "Connect with us". It stored its
        // sentence under 'content', but the renderer and the form use 'text',
        // so the sentence never appeared.
        $connect = $find('cta');

        $page->update(['content' => [
            [
                'type' => 'hero',
                'data' => [
                    'layout' => 'banner',
                    'eyebrow' => "This year's theme",
                    'heading' => 'Pentecost Everyday',
                    'subheading' => 'A Sustainable Annual Harvest — Going Beyond 2026',
                    'verse' => '“…and the Lord added to the church daily such as should be saved.” Acts 2:47',
                    'background_image' => 'page-images/01KPYX2XHW5DRFENE0NJ918RC6.jpg',
                    // The banner's words are repeated as real text under it
                    // (eyebrow, heading, subheading, verse), so the image itself
                    // is decorative and a description would be read twice.
                    'image_alt' => null,
                    'style' => 'gradient-blue',
                ],
            ],
            [
                // Content, not code: after 25 October it is updated in the
                // admin to whatever is next.
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
                    'content' => implode("\n\n", $paragraphs),
                    'tagline' => $tagline,
                    'button_text' => 'Read more',
                    'button_url' => '/about',
                    'image' => null,
                    'image_alt' => null,
                    // Names the ends of the country rather than counting: the
                    // congregations listed beside the finder carry the detail.
                    'fallback_caption' => 'Ten congregations, from Whangarei in the north to Rolleston in the south.',
                    'image_side' => 'right',
                    'background' => 'paper',
                ],
            ],
            [
                'type' => 'church_finder',
                'data' => array_merge($find('church_finder'), [
                    'layout' => 'panel',
                    // Names the regions rather than counting churches: a count
                    // written into content goes stale the day one is added.
                    'description' => 'Congregations across the Northern, Central and Southern regions.',
                    'show_regions' => true,
                    'show_directory' => true,
                    'directory_heading' => $directory['heading'] ?? 'Our congregations',
                    'help_heading' => 'Not sure where to start?',
                    'help_text' => "Tell us where you live and we'll point you to the nearest congregation.",
                    'help_link_text' => 'Connect with us',
                    'help_link_url' => '/connect-with-us',
                ]),
            ],
            [
                'type' => 'events_feed',
                'data' => array_merge($find('events_feed'), [
                    'layout' => 'cards',
                    'heading' => 'Coming up',
                    'limit' => 8,
                    'more_text' => 'More events',
                    'more_url' => '/events',
                ]),
            ],
            [
                'type' => 'department_list',
                'data' => array_merge($find('department_list'), [
                    'layout' => 'tiles',
                    'lede' => 'Six departments, one fellowship — each with its own page and its own place on the calendar.',
                ]),
            ],
            [
                'type' => 'cta',
                'data' => array_merge(
                    collect($connect)->except('content')->all(),
                    [
                        'text' => $connect['content'] ?? ($connect['text'] ?? null),
                        'style' => 'panel',
                    ],
                ),
            ],
        ]]);
    }

    public function down(): void
    {
        $page = Page::where('slug', 'home')->first();
        $archive = Page::where('slug', self::ARCHIVE)->first();

        // Only undo what this migration wrote; an edited homepage stays.
        if (! $page || ! $archive || $this->types($this->blocks($page)) !== self::WRITTEN) {
            return;
        }

        // The archive row is kept: delete nothing.
        $page->update(['content' => $this->blocks($archive)]);
    }

    private function blocks(Page $page): array
    {
        $content = is_string($page->content) ? json_decode($page->content, true) : $page->content;

        return is_array($content) ? $content : [];
    }

    private function types(array $blocks): array
    {
        return array_map(fn ($block) => $block['type'] ?? '', $blocks);
    }
};
