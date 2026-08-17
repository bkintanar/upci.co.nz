<?php

use App\Models\Page;
use App\Models\Region;
use Illuminate\Database\Migrations\Migration;

/**
 * Regions learn who their presbyter is (T65, part).
 *
 * The three names are already published — they sit inside the leadership page's
 * cards as "Northern Region Presbyter" and so on — but only as prose in a CMS
 * block. `regions.presbyter_name` sat null, so the region pages and the
 * /api/regions payload could not say who leads a region, and the fact lived in
 * exactly one place that nothing could query.
 *
 * Read from the leadership cards rather than typed in here, so this cannot
 * disagree with the page it came from. If a card is reworded, this migration
 * finds nothing and changes nothing rather than writing a stale name.
 *
 * `intro` is deliberately NOT seeded. That field is a message FROM the region,
 * and inventing one would put words in a presbyter's mouth on a public page.
 * The region template already omits the message section when it is empty, so
 * an unfilled intro costs nothing; invented copy would cost credibility.
 * Region logos are likewise left null — the page falls back to a lettermark.
 */
return new class extends Migration
{
    public function up(): void
    {
        $page = Page::where('slug', 'about/leadership')->first();

        if (! $page) {
            return;
        }

        $blocks = is_string($page->content) ? json_decode($page->content, true) : $page->content;

        if (! is_array($blocks)) {
            return;
        }

        foreach ($blocks as $block) {
            if (($block['type'] ?? null) !== 'cards') {
                continue;
            }

            foreach ($block['data']['items'] ?? [] as $item) {
                $name = trim((string) ($item['data']['title'] ?? ''));
                $role = strtolower(trim((string) ($item['data']['description'] ?? '')));

                if ($name === '' || ! str_contains($role, 'presbyter')) {
                    continue;
                }

                // "Northern Region Presbyter" -> northern
                foreach (Region::all() as $region) {
                    $firstWord = strtolower(explode(' ', $region->name)[0]);

                    if (str_contains($role, $firstWord)) {
                        $region->update(['presbyter_name' => $name]);
                    }
                }
            }
        }
    }

    public function down(): void
    {
        Region::query()->update(['presbyter_name' => null]);
    }
};
