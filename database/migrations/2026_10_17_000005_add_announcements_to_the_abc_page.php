<?php

use App\Models\Page;
use Illuminate\Database\Migrations\Migration;

/**
 * An Announcements block on the Apostolic Bible College page (owner request,
 * 2026-10-17: "add an announcement block to the ABC page").
 *
 * Placed right after "About Apostolic Bible College", so news sits near the
 * top. It starts empty, and an empty Announcements block with no message
 * renders nothing, so the page looks the same until the first announcement
 * is added in Admin › Pages. It can be moved like any block.
 *
 * Guarded: the page must exist, have that "About" text block, and no
 * announcements block yet. The outgoing version is archived as an
 * unpublished page rather than lost.
 */
return new class extends Migration
{
    private const SLUG = 'apostolic-bible-college';

    private const ARCHIVE = 'apostolic-bible-college-before-announcements';

    private const ABOUT = 'About Apostolic Bible College';

    public function up(): void
    {
        $page = Page::where('slug', self::SLUG)->first();

        if (! $page) {
            return;
        }

        $current = $this->blocks($page);

        if (in_array('announcements', $this->types($current), true)) {
            return;
        }

        $about = collect($current)->search(
            fn ($block) => ($block['type'] ?? null) === 'text' && ($block['data']['heading'] ?? null) === self::ABOUT
        );

        if ($about === false) {
            return;
        }

        Page::updateOrCreate(
            ['slug' => self::ARCHIVE],
            [
                'title' => 'Apostolic Bible College — before announcements',
                'content' => $current,
                'is_published' => false,
            ]
        );

        array_splice($current, $about + 1, 0, [[
            'type' => 'announcements',
            'data' => [
                'heading' => 'Announcements',
                'empty_message' => null,
                'items' => [],
            ],
        ]]);

        $page->update(['content' => $current]);
    }

    public function down(): void
    {
        $page = Page::where('slug', self::SLUG)->first();
        $archive = Page::where('slug', self::ARCHIVE)->first();

        if (! $page || ! $archive) {
            return;
        }

        // Only take out the block this migration added, and only while it is
        // still empty: announcements written since are kept, and so is every
        // other edit to the page. The archive row is kept too.
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

    private function types(array $blocks): array
    {
        return array_map(fn ($block) => $block['type'] ?? null, $blocks);
    }
};
