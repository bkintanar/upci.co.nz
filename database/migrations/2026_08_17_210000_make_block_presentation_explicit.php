<?php

use App\Models\Page;
use Illuminate\Database\Migrations\Migration;

/**
 * Presentation stops being inferred from content (§11.2).
 *
 * Five rules in CmsPage.vue decided how a block looked by reading what was in
 * it or where it sat:
 *
 *   - a text block's background came from its ARRAY POSITION (index === 1)
 *   - a cards section's background came from its ORDINAL among cards blocks
 *   - 48px gradient "stat" styling triggered on the string "- **"
 *   - column count came from the NUMBER of cards
 *   - "registration" styling applied when every card happened to link offsite
 *
 * So an editor could not control layout, and reordering blocks silently
 * restyled the page. Writing a fourth card, or an ordinary bold bullet, changed
 * the design.
 *
 * This backfills each block with the value its heuristic produces TODAY, so
 * every existing page keeps exactly the appearance it has now. Only once the
 * values are stored can the renderer stop guessing — which is the point of the
 * task, and why the backfill and the renderer change land together.
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach (Page::all() as $page) {
            $blocks = is_string($page->content) ? json_decode($page->content, true) : $page->content;

            if (! is_array($blocks)) {
                continue;
            }

            $cardsSeen = 0;
            $changed = false;

            foreach ($blocks as $i => $block) {
                $type = $block['type'] ?? null;

                if ($type === 'text') {
                    // getTextBlockClasses(index): index === 1 was white.
                    $blocks[$i]['data']['background'] ??= ($i === 1 ? 'white' : 'slate');
                    // hasStats(content): the literal string "- **".
                    $blocks[$i]['data']['style'] ??= str_contains((string) ($block['data']['content'] ?? ''), '- **')
                        ? 'stats'
                        : 'default';
                    $changed = true;
                }

                if ($type === 'cards') {
                    $items = $block['data']['items'] ?? [];
                    $count = count($items);

                    // getCardsGridClasses(): >=5 -> 3, >=4 -> 2, else 3.
                    $blocks[$i]['data']['columns'] ??= match (true) {
                        $count >= 5 => 3,
                        $count >= 4 => 2,
                        default => 3,
                    };

                    // isRegistrationBlock(): 3+ cards, every one linking offsite.
                    $isRegistration = $count >= 3 && collect($items)->every(
                        fn ($item) => filled($item['data']['link_url'] ?? null)
                            && str_starts_with((string) $item['data']['link_url'], 'http')
                    );
                    $blocks[$i]['data']['style'] ??= $isRegistration ? 'registration' : 'default';

                    // getCardsSectionClasses(): alternated on the cards ordinal.
                    $blocks[$i]['data']['background'] ??= ($cardsSeen % 2 === 0 ? 'slate' : 'white');

                    $cardsSeen++;
                    $changed = true;
                }
            }

            if ($changed) {
                $page->update(['content' => $blocks]);
            }
        }
    }

    /**
     * Strips the explicit values. The renderer falls back to its defaults
     * rather than to the old heuristics, so this is a data rollback only —
     * reverting the appearance needs the code reverted too.
     */
    public function down(): void
    {
        foreach (Page::all() as $page) {
            $blocks = is_string($page->content) ? json_decode($page->content, true) : $page->content;

            if (! is_array($blocks)) {
                continue;
            }

            foreach ($blocks as $i => $block) {
                if (in_array($block['type'] ?? null, ['text', 'cards'], true)) {
                    unset(
                        $blocks[$i]['data']['background'],
                        $blocks[$i]['data']['style'],
                        $blocks[$i]['data']['columns'],
                    );
                }
            }

            $page->update(['content' => $blocks]);
        }
    }
};
