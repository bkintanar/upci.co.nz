<?php

use App\Models\Page;
use Illuminate\Database\Migrations\Migration;

/**
 * "Change the Wickette to Wickett. This is the General Superintendent's
 * name" (owner request, 2026-11-09).
 *
 * The Leadership page (about/leadership) spelled Rev. Troy Wickett's name
 * "Wickette" on his card and at the start of his bio. Every page is checked,
 * not only that one, in case the name was copied elsewhere; only text inside
 * the blocks changes, and only that word. Running it again changes nothing.
 *
 * down() does nothing: putting a misspelling back is not an undo anyone
 * wants. The content before this change is in
 * ../upci.co.nz-backups/pre-wickett-spelling-20261109-124848/upci.sqlite.
 */
return new class extends Migration
{
    private const WRONG = 'Wickette';

    private const RIGHT = 'Wickett';

    public function up(): void
    {
        Page::where('content', 'like', '%'.self::WRONG.'%')->each(function (Page $page): void {
            $blocks = $page->content ?? [];

            array_walk_recursive($blocks, function (&$value): void {
                if (is_string($value)) {
                    $value = str_replace(self::WRONG, self::RIGHT, $value);
                }
            });

            if ($blocks !== $page->content) {
                $page->update(['content' => $blocks]);
            }
        });
    }

    public function down(): void
    {
        //
    }
};
