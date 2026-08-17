<?php

use App\Models\Page;
use Illuminate\Database\Migrations\Migration;

/**
 * `two_column` gains a ratio, and its right column stops being forced into a
 * grey panel (§11.4).
 *
 * The block hard-coded `lg:grid-cols-2` and wrapped the right column in
 * `bg-gray-100 p-8 rounded-lg` unconditionally. So it could not express an
 * uneven split — Direction B's 2/3 + 1/3 among them — and an author writing
 * 2,700 characters of prose into the right column got all of it inside a grey
 * box whether that suited the content or not.
 *
 * Backfilled to the current behaviour: an even split with the panel on. Every
 * existing block therefore looks exactly as it does now, and the options only
 * matter once someone changes them. Same ordering as the presentation
 * heuristics — store the current values first, then let the renderer read them.
 */
return new class extends Migration
{
    public function up(): void
    {
        $this->apply(fn (array $data) => array_merge($data, [
            'ratio' => $data['ratio'] ?? '1-1',
            'right_panel' => $data['right_panel'] ?? true,
        ]));
    }

    public function down(): void
    {
        $this->apply(function (array $data) {
            unset($data['ratio'], $data['right_panel']);

            return $data;
        });
    }

    private function apply(callable $fn): void
    {
        foreach (Page::all() as $page) {
            $blocks = is_string($page->content) ? json_decode($page->content, true) : $page->content;

            if (! is_array($blocks)) {
                continue;
            }

            $changed = false;

            foreach ($blocks as $i => $block) {
                if (($block['type'] ?? null) !== 'two_column') {
                    continue;
                }

                $blocks[$i]['data'] = $fn($block['data'] ?? []);
                $changed = true;
            }

            if ($changed) {
                $page->update(['content' => $blocks]);
            }
        }
    }
};
