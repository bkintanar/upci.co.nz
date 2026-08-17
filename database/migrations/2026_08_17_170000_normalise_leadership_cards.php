<?php

use App\Models\Page;
use Illuminate\Database\Migrations\Migration;

/**
 * T41's open question: which card field holds the person's name.
 *
 * On the leadership page the mapping was inverted against every other card on
 * the site — `title` held the ROLE ("General Superintendent") and
 * `description` held the NAME ("Rev. Troy Wickette"). Everywhere else `title`
 * is the name of the thing. For a person card, the person IS the thing.
 *
 * Swapped rather than special-cased in the template. The `cards` block is
 * shared across seven pages, so teaching the renderer that one page means the
 * opposite of the others would be a heuristic — exactly what T27 is removing.
 * A one-time data fix makes the semantics uniform instead.
 *
 * Also stamps `variant: 'person'` on these blocks. That is an explicit author
 * option, not an inference: it tells the renderer to use the portrait
 * treatment and open a detail modal (requirement 3) without guessing from
 * heading text or item count.
 *
 * `bio` is initialised to null. Requirement 3 asks the modal to show a
 * biography; none exists in the CMS yet, so the field is created for authoring
 * and the modal omits the section until it is filled.
 */
return new class extends Migration
{
    private const SLUG = 'about/leadership';

    public function up(): void
    {
        $this->transform(function (array $card) {
            // Guard against a re-run swapping the pair back.
            if (($card['data']['variant'] ?? null) === 'person') {
                return $card;
            }

            $role = $card['data']['title'] ?? null;
            $name = $card['data']['description'] ?? null;

            $card['data']['title'] = trim((string) $name) ?: $role;
            $card['data']['description'] = trim((string) $role);
            $card['data']['variant'] = 'person';
            $card['data']['bio'] = $card['data']['bio'] ?? null;

            return $card;
        });
    }

    public function down(): void
    {
        $this->transform(function (array $card) {
            if (($card['data']['variant'] ?? null) !== 'person') {
                return $card;
            }

            $name = $card['data']['title'] ?? null;
            $role = $card['data']['description'] ?? null;

            $card['data']['title'] = $role;
            $card['data']['description'] = $name;
            unset($card['data']['variant'], $card['data']['bio']);

            return $card;
        });
    }

    private function transform(callable $fn): void
    {
        $page = Page::where('slug', self::SLUG)->first();

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

            $blocks[$i]['data']['items'] = array_map(
                fn (array $card) => $fn($card),
                $block['data']['items'] ?? []
            );
        }

        $page->update(['content' => $blocks]);
    }
};
