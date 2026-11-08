<?php

use App\Models\Page;

/*
 * "Change the Wickette to Wickett. This is the General Superintendent's
 * name" (owner, 2026-11-09).
 */

function wickettSpellingMigration(): object
{
    return require database_path('migrations/2026_11_09_000001_correct_wickett_spelling.php');
}

/** The Leadership page's General Superintendent card as it was on the live site. */
function wickettLeadershipPage(): Page
{
    return Page::updateOrCreate(['slug' => 'about/leadership'], [
        'title' => 'UPCINZ Leadership', 'is_published' => true,
        'content' => [
            ['type' => 'hero', 'data' => ['heading' => 'Leadership']],
            ['type' => 'cards', 'data' => ['style' => 'default', 'items' => [
                ['type' => 'card', 'data' => [
                    'icon' => 'page-images/gs.png',
                    'title' => 'Rev. Troy Wickette',
                    'description' => 'General Superintendent / Regional Director of Pacific Region',
                    'variant' => 'person',
                    'bio' => 'Rev. Troy Wickette received his missionary appointment to Fiji and the Solomon Islands.',
                ]],
                ['type' => 'card', 'data' => ['title' => 'Rev. Wayne Goodare', 'description' => 'Assistant General Superintendent']],
            ]]],
        ],
    ]);
}

test('the General Superintendent\'s name is spelled Wickett on his card and in his bio', function () {
    $page = wickettLeadershipPage();

    wickettSpellingMigration()->up();

    $card = $page->fresh()->content[1]['data']['items'][0]['data'];

    expect($card['title'])->toBe('Rev. Troy Wickett')
        ->and($card['bio'])->toBe('Rev. Troy Wickett received his missionary appointment to Fiji and the Solomon Islands.')
        ->and(json_encode($page->fresh()->content))->not->toContain('Wickette');
});

test('nothing else on the page changes, and running it again changes nothing', function () {
    $page = wickettLeadershipPage();

    wickettSpellingMigration()->up();
    $once = $page->fresh()->content;
    wickettSpellingMigration()->up();

    expect($page->fresh()->content)->toBe($once)
        ->and($once[0])->toBe(['type' => 'hero', 'data' => ['heading' => 'Leadership']])
        ->and($once[1]['data']['items'][0]['data']['description'])->toBe('General Superintendent / Regional Director of Pacific Region')
        ->and($once[1]['data']['items'][1]['data']['title'])->toBe('Rev. Wayne Goodare');
});

test('a page without the misspelling is left alone', function () {
    $page = Page::create(['slug' => 'about-us', 'title' => 'About', 'is_published' => true, 'content' => [['type' => 'text', 'data' => ['body' => 'Welcome']]]]);
    $before = $page->updated_at;

    $this->travel(1)->minutes();
    wickettSpellingMigration()->up();

    expect($page->fresh()->updated_at->equalTo($before))->toBeTrue();
});
