<?php

use App\Models\Page;
use Illuminate\Database\Migrations\Migration;

/**
 * "In the choose your registration path, disable the 3 buttons except the
 * first one" (owner request, 2026-10-17).
 *
 * On the Apostolic Bible College's Register page, the "Choose your
 * registration path" cards keep "First time student" open and turn off
 * Returning students, Foundation level course and Work exemption
 * application. The cards stay on the page, greyed, with buttons that cannot
 * be clicked. Each can be turned back on in Admin › Pages ("Turn this link
 * off" on the card).
 *
 * Cards are found by title, never by position, and only in that block. A
 * card already edited to another title is left alone. down() turns the same
 * three back on.
 */
return new class extends Migration
{
    private const SLUG = 'apostolic-bible-college/enrollment';

    private const HEADING = 'Choose your registration path';

    private const TURNED_OFF = ['Returning students', 'Foundation level course *NEW', 'Work exemption application'];

    public function up(): void
    {
        $this->set(true);
    }

    public function down(): void
    {
        $this->set(false);
    }

    private function set(bool $disabled): void
    {
        $page = Page::where('slug', self::SLUG)->first();

        if (! $page) {
            return;
        }

        $blocks = is_string($page->content) ? (json_decode($page->content, true) ?: []) : ($page->content ?? []);
        $changed = false;

        foreach ($blocks as &$block) {
            if (($block['type'] ?? null) !== 'cards' || ($block['data']['heading'] ?? null) !== self::HEADING) {
                continue;
            }

            foreach ($block['data']['items'] as &$card) {
                if (in_array($card['data']['title'] ?? null, self::TURNED_OFF, true)) {
                    $card['data']['disabled'] = $disabled;
                    $changed = true;
                }
            }
            unset($card);
        }
        unset($block);

        if ($changed) {
            $page->update(['content' => $blocks]);
        }
    }
};
