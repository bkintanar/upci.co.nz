<?php

use App\Models\Page;
use Illuminate\Database\Migrations\Migration;

/**
 * Announcements on the homepage (owner request, 2026-10-17: "add an
 * announcement section to the main landing page, where we can upload a photo
 * from the CMS and it will show there").
 *
 * The Announcements block the Apostolic Bible College page already has,
 * placed right after the "Next" band and before "About the UPCI NZ", so news
 * is near the top. It shows the newest three. It starts empty, and an empty
 * Announcements block renders nothing, so the homepage looks the same until
 * the first announcement is added in Admin › Pages › Home. It can be moved
 * like any block.
 *
 * Guarded: the homepage must exist, have no announcements block yet, and
 * have the "Next" band (a cta in the 'band' style) to sit after. The outgoing
 * version is archived as an unpublished page rather than lost.
 */
return new class extends Migration
{
    private const ARCHIVE = 'home-before-announcements';

    public function up(): void
    {
        $page = Page::where('slug', 'home')->first();

        if (! $page) {
            return;
        }

        $current = $this->blocks($page);

        if (in_array('announcements', array_column($current, 'type'), true)) {
            return;
        }

        $band = collect($current)->search(
            fn ($block) => ($block['type'] ?? null) === 'cta' && ($block['data']['style'] ?? null) === 'band'
        );

        if ($band === false) {
            return;
        }

        Page::updateOrCreate(
            ['slug' => self::ARCHIVE],
            [
                'title' => 'Home — before announcements',
                'content' => $current,
                'is_published' => false,
            ]
        );

        array_splice($current, $band + 1, 0, [[
            'type' => 'announcements',
            'data' => [
                'heading' => 'Announcements',
                'empty_message' => null,
                'limit' => 3,
                'items' => [],
            ],
        ]]);

        $page->update(['content' => $current]);
    }

    public function down(): void
    {
        $page = Page::where('slug', 'home')->first();

        if (! $page || ! Page::where('slug', self::ARCHIVE)->exists()) {
            return;
        }

        // Only take out the block this migration added, and only while it is
        // still empty: announcements written since are kept, and so is every
        // other edit to the homepage. The archive row is kept too.
        $current = $this->blocks($page);
        $index = collect($current)->search(fn ($block) => ($block['type'] ?? null) === 'announcements');

        if ($index === false || ! empty($current[$index]['data']['items'])) {
            return;
        }

        array_splice($current, $index, 1);
        $page->update(['content' => $current]);
    }

    private function blocks(Page $page): array
    {
        $content = $page->content;

        return is_string($content) ? (json_decode($content, true) ?: []) : ($content ?? []);
    }
};
