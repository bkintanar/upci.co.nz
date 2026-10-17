<?php

use App\Models\Page;
use Illuminate\Database\Migrations\Migration;

/**
 * The contact form on the Apostolic Bible College's Connect page (owner
 * choice, 2026-10-17: "Both, via a form block").
 *
 * Its "Contact Us" section was a text block ending in a link to the general
 * /connect-with-us page. That section becomes the contact form itself, with
 * the same heading and sentence, and messages sent from it read "Sent from
 * /apostolic-bible-college/connect" in the admin. The hero and the "Prayer
 * Request" section are untouched.
 *
 * Guarded like the homepage migrations: content has an editor, and the editor
 * wins. If the page is not exactly hero, text, text with that "Contact Us"
 * section, it has been edited in the CMS and is left alone. The outgoing
 * version is archived as an unpublished page rather than lost.
 */
return new class extends Migration
{
    private const SLUG = 'apostolic-bible-college/connect';

    private const EXPECTED = ['hero', 'text', 'text'];

    private const WRITTEN = ['hero', 'contact_form', 'text'];

    private const ARCHIVE = 'apostolic-bible-college/connect-before-contact-form';

    public function up(): void
    {
        $page = Page::where('slug', self::SLUG)->first();

        if (! $page) {
            return;
        }

        $current = $this->blocks($page);
        $contact = $current[1]['data'] ?? [];

        if ($this->types($current) !== self::EXPECTED
            || ($contact['heading'] ?? null) !== 'Contact Us'
            || ! str_contains($contact['content'] ?? '', '](/connect-with-us)')) {
            return;
        }

        Page::updateOrCreate(
            ['slug' => self::ARCHIVE],
            [
                'title' => 'ABC Connect — before the contact form',
                'content' => $current,
                'is_published' => false,
            ]
        );

        // The section's own sentence, without the link that pointed away.
        $intro = trim(preg_replace('/\[[^\]]*\]\(\/connect-with-us\)/', '', $contact['content']));

        $current[1] = [
            'type' => 'contact_form',
            'data' => [
                'heading' => 'Contact Us',
                'intro' => $intro !== '' ? $intro : null,
                'button_text' => 'Send',
                'success_message' => 'Thank you. Your message has been sent. Someone from the college will get back to you.',
            ],
        ];

        $page->update(['content' => $current]);
    }

    public function down(): void
    {
        $page = Page::where('slug', self::SLUG)->first();
        $archive = Page::where('slug', self::ARCHIVE)->first();

        // Only undo what this migration wrote; an edited page stays.
        $current = $page ? $this->blocks($page) : [];

        if (! $archive || $this->types($current) !== self::WRITTEN) {
            return;
        }

        // Only the section this migration replaced goes back, so edits made
        // since to the hero or "Prayer Request" survive. The archive row is
        // kept: delete nothing.
        $current[1] = $this->blocks($archive)[1];
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
