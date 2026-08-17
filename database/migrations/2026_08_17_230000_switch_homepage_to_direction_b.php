<?php

use App\Models\Page;
use Illuminate\Database\Migrations\Migration;

/**
 * The homepage moves to Direction B's block sequence (T32), at the user's
 * direction after reviewing a preview.
 *
 * B treats the front page as a TASK rather than a brochure: the first thing on
 * it is "find a church", and what follows is the network itself. Almost every
 * section is now data-bound, so the page keeps itself current — publishing an
 * event or adding a church updates the homepage with nobody editing it.
 *
 * The statistics block replaces four hand-typed figures, three of which had
 * drifted from the data: it claimed 10 established churches against 9, 3
 * daughter works against none, 2 preaching points against 1, and 12 potential
 * home groups against none. Counting at request time means they cannot be
 * wrong again.
 *
 * The previous content is copied to an unpublished `home-previous` page before
 * anything is overwritten, and down() restores from it. Replacing a site's
 * front page is not something to do without a way back.
 */
return new class extends Migration
{
    public function up(): void
    {
        $home = Page::where('slug', 'home')->first();

        if (! $home) {
            return;
        }

        $existing = is_string($home->content) ? json_decode($home->content, true) : $home->content;
        $existing = is_array($existing) ? $existing : [];

        // Safety net first, so a failure below cannot lose the old front page.
        Page::updateOrCreate(
            ['slug' => 'home-previous'],
            [
                'title' => 'Previous homepage (archived)',
                'is_published' => false,
                'content' => $existing,
            ]
        );

        // Carried over rather than rewritten: this is the client's own wording
        // about who they are, and B changes the sequence, not the voice.
        $whoWeAre = collect($existing)->first(fn ($block) => ($block['type'] ?? null) === 'cards');

        if ($whoWeAre) {
            $whoWeAre['data']['heading'] = 'Who we are';
        }

        $home->update(['content' => array_values(array_filter([
            ['type' => 'church_finder', 'data' => [
                'heading' => 'Find a UPCI church in New Zealand',
                'placeholder' => 'Enter your town or suburb',
                'button_text' => 'Find a church',
            ]],
            ['type' => 'church_directory', 'data' => [
                'heading' => 'A network of local churches',
                'group_by_region' => true,
                'empty_message' => 'Church listings are being updated.',
            ]],
            ['type' => 'statistics', 'data' => [
                'heading' => 'The church in New Zealand',
                'lede' => 'Counted from our current listings.',
                'empty_message' => 'Figures are being compiled.',
            ]],
            $whoWeAre,
            ['type' => 'department_list', 'data' => [
                'heading' => 'Our national departments',
                'show_logos' => true,
                'empty_message' => 'Departments are being updated.',
            ]],
            ['type' => 'events_feed', 'data' => [
                'heading' => "What's on",
                'scope' => 'national',
                'limit' => 5,
                'upcoming_only' => true,
                'empty_message' => 'The next calendar is being confirmed.',
            ]],
            ['type' => 'region_list', 'data' => [
                'heading' => 'Our regions',
                'show_logos' => true,
                'empty_message' => 'Regions are being updated.',
            ]],
            ['type' => 'cta', 'data' => [
                'heading' => 'Get in touch',
                'text' => 'Questions about the church, or want to speak with someone? We would be glad to hear from you.',
                'button_text' => 'Contact us',
                'button_url' => '/connect-with-us',
                'style' => 'blue',
            ]],
        ]))]);

        // The preview served its purpose once the real page carries the sequence.
        Page::where('slug', 'home-b-preview')->delete();
    }

    public function down(): void
    {
        $previous = Page::where('slug', 'home-previous')->first();
        $home = Page::where('slug', 'home')->first();

        if (! $previous || ! $home) {
            return;
        }

        $home->update(['content' => $previous->content]);
        $previous->delete();
    }
};
