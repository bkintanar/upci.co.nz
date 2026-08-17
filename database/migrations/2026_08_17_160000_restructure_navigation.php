<?php

use App\Models\Page;
use App\Models\MenuItem;
use App\Models\SiteSetting;
use Illuminate\Database\Migrations\Migration;

/**
 * Requirements 7, 8 and 10: one pass over the whole navigation.
 *
 * Doing these separately would renumber sort_order three times and leave the
 * menu inconsistent between migrations, so every change lands together.
 *
 * ORDER MATTERS. Footer rows are seeded BEFORE anything else: the footer
 * rebuild reads location='footer', and there are currently zero such rows, so
 * wiring it up first would render an empty footer.
 *
 * Changes:
 *  - Gallery moves from a child of About (pointing at /departments#gallery, an
 *    anchor on another page) to a top-level item pointing at the real /gallery.
 *  - Regions added, pointing at the new /regions.
 *  - General Superintendent link removed. The Vue component is deliberately
 *    KEPT — the route still resolves for anyone holding a direct link.
 *  - "Youth & Children's" becomes a real section holding Youth Ministry,
 *    Children's Ministry, SBQ and JBQ. The two ministries are MOVED, not
 *    copied, so they do not appear twice.
 *  - Top-level sort_order renumbered 10..90. They currently collide (Find a
 *    Church and Apostolic Bible College are both 3), which makes ordering
 *    depend on insertion order rather than intent.
 *  - social_links seeded with the organisation's actual Facebook presence, so
 *    the footer can render from settings instead of hard-coded markup.
 *
 * NOTE: this takes the header from six top-level items to nine, which will
 * worsen the pre-existing navbar crowding. T46's two-row header is the fix;
 * that is a layout task, not a reason to withhold the items requirement 7 asks
 * for.
 */
return new class extends Migration
{
    public function up(): void
    {
        $this->seedFooterRows();
        $this->restructureHeader();
        $this->seedSocialLinks();
        $this->createQuizzingPages();
    }

    public function down(): void
    {
        MenuItem::where('location', 'footer')->delete();
        MenuItem::whereIn('label', ['Regions', 'SBQ', 'JBQ', "Youth & Children's"])->delete();
        Page::whereIn('slug', ['youth-and-childrens/sbq', 'youth-and-childrens/jbq'])->delete();
    }

    /**
     * Seeded first — see the class comment.
     */
    private function seedFooterRows(): void
    {
        if (MenuItem::where('location', 'footer')->exists()) {
            return;
        }

        $rows = [
            ['About UPCI', '/about/upci', 10],
            ['Our Beliefs', '/about/beliefs', 20],
            ['Leadership', '/about/leadership', 30],
            ['Departments', '/departments', 40],
            ['Regions', '/regions', 50],
            ['Gallery', '/gallery', 60],
            ['Find a Church', '/find-church', 70],
            ['Calendar of Events', '/events', 80],
            ['Connect with Us', '/connect-with-us', 90],
        ];

        foreach ($rows as [$label, $url, $order]) {
            MenuItem::create([
                'label' => $label,
                'url' => $url,
                'location' => 'footer',
                'sort_order' => $order,
                'is_active' => true,
            ]);
        }
    }

    private function restructureHeader(): void
    {
        // Requirement 7: remove the General Superintendent link. The route and
        // component stay so an existing direct link still resolves.
        MenuItem::where('location', 'header')
            ->where('url', '/about/general-superintendent')
            ->delete();

        // A Facebook link sitting inside the About dropdown labelled "Social"
        // belongs in the footer's social row, which now renders from settings.
        MenuItem::where('location', 'header')->where('label', 'Social')->delete();

        // Gallery was a child of About pointing at an anchor on another page.
        MenuItem::where('location', 'header')
            ->where('label', 'Gallery')
            ->delete();

        $topLevel = [
            'About the UPCI NZ' => 10,
            'Departments' => 20,
            'Find a Church' => 40,
            'Apostolic Bible College' => 50,
            'Calendar of Events' => 70,
            'Connect with Us' => 90,
        ];

        foreach ($topLevel as $label => $order) {
            MenuItem::where('location', 'header')
                ->whereNull('parent_id')
                ->where('label', $label)
                ->update(['sort_order' => $order]);
        }

        $youthAndChildrens = MenuItem::firstOrCreate(
            ['location' => 'header', 'label' => "Youth & Children's", 'parent_id' => null],
            ['url' => '#', 'sort_order' => 30, 'is_active' => true]
        );

        // MOVED, not copied — leaving them under Departments as well would list
        // the same ministry twice in one menu bar.
        MenuItem::where('location', 'header')
            ->whereIn('url', ['/departments/youth', '/departments/childrens'])
            ->update(['parent_id' => $youthAndChildrens->id]);

        MenuItem::where('parent_id', $youthAndChildrens->id)
            ->where('url', '/departments/youth')
            ->update(['sort_order' => 10]);
        MenuItem::where('parent_id', $youthAndChildrens->id)
            ->where('url', '/departments/childrens')
            ->update(['sort_order' => 20]);

        foreach ([['SBQ', '/youth-and-childrens/sbq', 30], ['JBQ', '/youth-and-childrens/jbq', 40]] as [$label, $url, $order]) {
            MenuItem::firstOrCreate(
                ['location' => 'header', 'label' => $label, 'parent_id' => $youthAndChildrens->id],
                ['url' => $url, 'sort_order' => $order, 'is_active' => true]
            );
        }

        MenuItem::firstOrCreate(
            ['location' => 'header', 'label' => 'Regions', 'parent_id' => null],
            ['url' => '/regions', 'sort_order' => 60, 'is_active' => true]
        );

        MenuItem::firstOrCreate(
            ['location' => 'header', 'label' => 'Gallery', 'parent_id' => null],
            ['url' => '/gallery', 'sort_order' => 80, 'is_active' => true]
        );
    }

    /**
     * Requirement 8 asks for the Twitter/X link to be removed at the settings
     * level rather than hidden in markup. The footer's three social icons were
     * hard-coded and all pointed at "#" — two Twitter bird variants and a
     * Pinterest, none of them live. Seeding the real presence lets the footer
     * render from data, so there is no Twitter entry to remove later.
     */
    private function seedSocialLinks(): void
    {
        $settings = SiteSetting::current();

        if (filled($settings->social_links)) {
            return;
        }

        $settings->update([
            'social_links' => [
                ['platform' => 'facebook', 'url' => 'https://www.facebook.com/NZMinistriesUPCI'],
            ],
        ]);
    }

    /**
     * Requirement 10. Neither page existed anywhere — SBQ and JBQ appear only
     * inside event NAMES in the 2026 calendar seeder. These are deliberate
     * placeholders: the structure and the menu position are real, the copy is
     * marked as needing the client's words rather than invented.
     */
    private function createQuizzingPages(): void
    {
        $pages = [
            'youth-and-childrens/sbq' => ['SBQ - Senior Bible Quizzing', 'Senior Bible Quizzing', 'youth'],
            'youth-and-childrens/jbq' => ['JBQ - Junior Bible Quizzing', 'Junior Bible Quizzing', 'children'],
        ];

        foreach ($pages as $slug => [$title, $heading, $audience]) {
            if (Page::where('slug', $slug)->exists()) {
                continue;
            }

            Page::create([
                'slug' => $slug,
                'title' => $title,
                'is_published' => true,
                'content' => [
                    [
                        'type' => 'hero',
                        'data' => [
                            'heading' => $heading,
                            'subheading' => "Bible quizzing for our {$audience} across New Zealand.",
                            'style' => 'gradient-indigo',
                            'background_image' => null,
                        ],
                    ],
                    [
                        'type' => 'text',
                        'data' => [
                            'heading' => 'About '.$heading,
                            'content' => 'This page is ready for content. Add the programme description, age groups, '
                                ."study material and how to take part.\n\n"
                                .'Quizzing dates appear on the [calendar of events](/events).',
                        ],
                    ],
                ],
            ]);
        }
    }
};
