<?php

use App\Models\Page;

/*
 * "In the choose your registration path, disable the 3 buttons except the
 * first one" (owner, 2026-10-17). A card's link can be turned off in the CMS:
 * the card stays, greyed, and its button is not a link.
 */

function registrationPathsMigration(): object
{
    return require database_path('migrations/2026_10_17_000008_turn_off_three_abc_registration_paths.php');
}

/** The Register page's registration cards as they were on the live site. */
function registrationPathsPage(): Page
{
    $card = fn (string $title, string $url) => ['type' => 'card', 'data' => ['title' => $title, 'description' => 'x', 'link_url' => $url, 'link_text' => 'Register HERE']];

    return Page::updateOrCreate(['slug' => 'apostolic-bible-college/enrollment'], [
        'title' => 'Register - Apostolic Bible College', 'is_published' => true,
        'content' => [
            ['type' => 'hero', 'data' => ['heading' => 'Registration']],
            ['type' => 'cards', 'data' => ['heading' => 'Choose your registration path', 'style' => 'registration', 'columns' => 2, 'background' => 'slate', 'items' => [
                $card('First time student', '/apostolic-bible-college/enrollment/new-student'),
                $card('Returning students', 'https://forms.gle/9nEDxXdJgGbtwr5m9'),
                $card('Foundation level course *NEW', 'https://forms.gle/DoBQxwMmbQfgduWq9'),
                $card('Work exemption application', 'https://forms.gle/iqVTh9YQH3nhZdw48'),
            ]]],
        ],
    ]);
}

function registrationPathsDisabled(Page $page): array
{
    return array_map(fn ($card) => $card['data']['disabled'] ?? false, $page->fresh()->content[1]['data']['items']);
}

test('every path but the first is turned off, and can be turned back on', function () {
    $page = registrationPathsPage();

    registrationPathsMigration()->up();
    expect(registrationPathsDisabled($page))->toBe([false, true, true, true]);

    registrationPathsMigration()->down();
    expect(registrationPathsDisabled($page))->toBe([false, false, false, false]);
});

test('a card renamed in the CMS is left alone', function () {
    $page = registrationPathsPage();
    $content = $page->content;
    $content[1]['data']['items'][1]['data']['title'] = 'Returning students (Term 1)';
    $page->update(['content' => $content]);

    registrationPathsMigration()->up();

    expect(registrationPathsDisabled($page))->toBe([false, false, true, true]);
});

test('the card form lets an editor turn a link off, with an optional note', function () {
    $form = file_get_contents(app_path('Filament/Resources/Pages/Schemas/PageForm.php'));
    $card = substr($form, strpos($form, "Builder\\Block::make('card')"), 6000);

    expect($card)->toContain("Toggle::make('disabled')")->toContain("TextInput::make('disabled_note')");
});

test('a turned-off card shows its button greyed, and not as a link', function () {
    $cmsPage = file_get_contents(resource_path('js/views/CmsPage.vue'));

    expect($cmsPage)
        ->toContain('<template v-if="card.data.disabled && card.data.variant !== \'person\'">')
        ->toContain('aria-disabled="true"')
        ->toContain('<span class="sr-only"> (not available)</span>')
        ->toContain('<a v-else-if="card.data.link_url && card.data.variant !== \'person\'"')
        ->toContain('.cms-registration-card.is-disabled {');
});
