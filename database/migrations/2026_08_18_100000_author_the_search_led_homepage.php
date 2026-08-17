<?php

use App\Models\Page;
use Illuminate\Database\Migrations\Migration;

/**
 * The search-led homepage — Direction D2 (T45).
 *
 * Chosen by the client on 2026-08-17 from three built previews; the decision and
 * its reasoning are recorded in
 * .claude/design/upci-redesign/direction-approved.md.
 *
 * D2 answers the question most visitors actually arrive with — "where do I
 * worship?" — instead of asking them to admire a masthead first. Search sits at
 * the top, and immediately underneath it the ten congregations are NAMED, under
 * their three region headings. The search field is a filter over a list that is
 * already visible, not a box in front of an empty page. That distinction is the
 * whole direction: a visitor who does not know what to type still gets an
 * answer, because the answer is already on the screen.
 *
 * It was also the only one of the three whose central component already existed
 * and was already tested (`ChurchDirectoryBlock` groups by the organisational
 * region axis), and the only one that needs no hero photography. The client has
 * since confirmed photography is available, but D2 deliberately does not depend
 * on it — any layout that breaks when the images are late is the wrong layout.
 *
 * ── Why this migration is guarded, and the previous one was not ──────────────
 *
 * The Direction B homepage shipped as an unguarded migration, was rejected, and
 * was rolled back with `migrate:rollback`. That removes the migration's ROW,
 * which returns the file to the "not yet run" state — so the next `migrate` for
 * any unrelated work silently re-applied the rejected homepage, and it survived
 * several iterations that way before a sweep noticed the homepage had no h1.
 *
 * So this one refuses to overwrite anything it did not itself write. If the
 * homepage has been edited in the CMS since — by a person, which is the entire
 * point of it being CMS content — this migration leaves it alone rather than
 * stamping over their work. A migration that authors CONTENT is different in
 * kind from one that changes SCHEMA: content has an editor, and the editor wins.
 */
return new class extends Migration
{
    public function up(): void
    {
        $page = Page::where('slug', 'home')->first();

        if (! $page) {
            return;
        }

        $current = is_string($page->content) ? json_decode($page->content, true) : $page->content;
        $current = is_array($current) ? $current : [];

        // Guard: only author a homepage that still looks like the seeded one.
        // If someone has edited it in the CMS, their version stands.
        $types = array_map(fn ($b) => $b['type'] ?? '', $current);

        if ($types !== ['hero', 'text', 'cards', 'text', 'cta']) {
            return;
        }

        // Keep the outgoing version readable rather than only recoverable from
        // git. `home-previous` already holds the pre-B homepage, so this takes
        // its own slug instead of overwriting that history.
        Page::updateOrCreate(
            ['slug' => 'home-before-d2'],
            [
                'title' => 'Home — before the search-led rebuild',
                'content' => $current,
                'is_published' => false,
            ]
        );

        $page->update(['content' => [
            [
                'type' => 'church_finder',
                'data' => [
                    'heading' => 'Find a church near you',
                    'placeholder' => 'Suburb, town or postcode',
                    'button_text' => 'Search',
                ],
            ],
            [
                'type' => 'church_directory',
                'data' => [
                    'heading' => 'Our congregations',
                    'group_by_region' => true,
                    // Deliberately no limit: there are ten churches, and naming
                    // all of them IS the direction. A "view all" link here would
                    // reintroduce the extra click D2 exists to remove.
                    'empty_message' => 'Our church list is being updated. Please check back shortly.',
                ],
            ],
            [
                'type' => 'text',
                'data' => [
                    'heading' => 'Who we are',
                    'content' => 'The United Pentecostal Church International of New Zealand is a fellowship of '
                        .'apostolic congregations across the country, organised into the Northern, Central and '
                        ."Southern regions.\n\nWherever you are, you are welcome.",
                ],
            ],
            [
                'type' => 'events_feed',
                'data' => [
                    'heading' => "What's coming up",
                    'scope' => 'national',
                    'limit' => 4,
                    'empty_message' => 'No events are scheduled at the moment.',
                ],
            ],
            [
                'type' => 'department_list',
                'data' => [
                    'heading' => 'Our ministries',
                    'empty_message' => 'Ministry information is being updated.',
                ],
            ],
            [
                'type' => 'cta',
                'data' => [
                    'heading' => 'Connect with us',
                    'content' => "Questions, prayer requests, or looking for a church home — we'd love to hear from you.",
                    'button_text' => 'Get in touch',
                    'button_url' => '/connect-with-us',
                ],
            ],
        ]]);
    }

    public function down(): void
    {
        $previous = Page::where('slug', 'home-before-d2')->first();
        $page = Page::where('slug', 'home')->first();

        if ($previous && $page) {
            $page->update(['content' => $previous->content]);
        }

        // NOTE: rolling this back restores the content but, as the class comment
        // explains, does NOT withdraw the instruction. If D2 is ever rejected,
        // empty this migration's up() — do not rely on a rollback.
    }
};
