<?php

use App\Models\Page;
use Illuminate\Database\Migrations\Migration;

/**
 * Point the registration page's "First time student" card at the site's own
 * enrolment form instead of the Google Form it replaces, and correct the
 * intro sentence that promised every link opens a Google Form.
 *
 * Matches the card by title AND url, never by position: the live "Work
 * exemption application" card carries the same forms.gle URL, and changing
 * which form that card opens is the owner's call, not this migration's.
 *
 * Guards on the row and on the exact old values, so it no-ops under
 * RefreshDatabase and on a page someone has since edited by hand.
 */
return new class extends Migration
{
    private const REGISTER_SLUG = 'apostolic-bible-college/enrollment';

    private const CARD_TITLE = 'First time student';

    private const GOOGLE_FORM_URL = 'https://forms.gle/iqVTh9YQH3nhZdw48';

    private const NATIVE_FORM_URL = '/apostolic-bible-college/enrollment/new-student';

    private const OLD_SENTENCE = 'Each link opens the correct Google Form in a new tab.';

    private const NEW_SENTENCE = 'First-time students now register here on our site; the other options open the official Google Forms in a new tab.';

    public function up(): void
    {
        $this->swap(self::GOOGLE_FORM_URL, self::NATIVE_FORM_URL, self::OLD_SENTENCE, self::NEW_SENTENCE);
    }

    public function down(): void
    {
        $this->swap(self::NATIVE_FORM_URL, self::GOOGLE_FORM_URL, self::NEW_SENTENCE, self::OLD_SENTENCE);
    }

    private function swap(string $fromUrl, string $toUrl, string $fromSentence, string $toSentence): void
    {
        $page = Page::where('slug', self::REGISTER_SLUG)->first();

        if (! $page) {
            return;
        }

        $blocks = is_string($page->content) ? json_decode($page->content, true) : $page->content;

        if (! is_array($blocks)) {
            return;
        }

        foreach ($blocks as $i => $block) {
            if (($block['type'] ?? null) === 'text' && is_string($block['data']['content'] ?? null)) {
                $blocks[$i]['data']['content'] = str_replace($fromSentence, $toSentence, $block['data']['content']);
            }

            if (($block['type'] ?? null) !== 'cards') {
                continue;
            }

            foreach ($block['data']['items'] ?? [] as $j => $item) {
                if (($item['data']['title'] ?? null) === self::CARD_TITLE
                    && ($item['data']['link_url'] ?? null) === $fromUrl) {
                    $blocks[$i]['data']['items'][$j]['data']['link_url'] = $toUrl;
                }
            }
        }

        $page->update(['content' => $blocks]);
    }
};
