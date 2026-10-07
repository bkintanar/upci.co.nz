<?php

use App\Models\Page;
use Database\Seeders\ApostolicBibleCollegePageSeeder;

/*
 * The "First time student" card on the ABC registration page moves from the
 * Google Form to the native enrolment form. The live "Work exemption
 * application" card carries the same forms.gle URL and must not move with it.
 */

const ABC_FIRST_TIME_GOOGLE = 'https://forms.gle/iqVTh9YQH3nhZdw48';
const ABC_FIRST_TIME_NATIVE = '/apostolic-bible-college/enrollment/new-student';

function abcRegistrationPage(): Page
{
    // The live page's shape, including the work-exemption card that shares
    // the first-time form's URL.
    $card = fn (string $title, string $url) => ['type' => 'card', 'data' => ['title' => $title, 'link_url' => $url, 'link_text' => 'Register']];

    return Page::create([
        'slug' => 'apostolic-bible-college/enrollment',
        'title' => 'Register - Apostolic Bible College',
        'is_published' => true,
        'content' => [
            ['type' => 'hero', 'data' => ['heading' => 'Registration']],
            ['type' => 'text', 'data' => ['content' => 'Use the card that matches your situation. Each link opens the correct Google Form in a new tab.']],
            ['type' => 'cards', 'data' => ['style' => 'registration', 'items' => [
                $card('First time student', ABC_FIRST_TIME_GOOGLE),
                $card('Returning students', 'https://forms.gle/9nEDxXdJgGbtwr5m9'),
                $card('Foundation level course *NEW', 'https://forms.gle/DoBQxwMmbQfgduWq9'),
                $card('Work exemption application', ABC_FIRST_TIME_GOOGLE),
            ]]],
        ],
    ]);
}

function abcCardLinks(Page $page): array
{
    return collect($page->fresh()->content[2]['data']['items'])
        ->mapWithKeys(fn ($item) => [$item['data']['title'] => $item['data']['link_url']])
        ->all();
}

function abcCardMigration(): object
{
    return require database_path('migrations/2026_10_02_000002_point_abc_first_time_card_at_native_form.php');
}

test('the first-time card moves to the native form and nothing else does', function () {
    $page = abcRegistrationPage();

    abcCardMigration()->up();

    expect(abcCardLinks($page))->toBe([
        'First time student' => ABC_FIRST_TIME_NATIVE,
        'Returning students' => 'https://forms.gle/9nEDxXdJgGbtwr5m9',
        'Foundation level course *NEW' => 'https://forms.gle/DoBQxwMmbQfgduWq9',
        'Work exemption application' => ABC_FIRST_TIME_GOOGLE,
    ])->and($page->fresh()->content[1]['data']['content'])
        ->toContain('First-time students now register here on our site')
        ->not->toContain('Each link opens the correct Google Form');
});

test('rolling back restores the Google Form link and the original sentence', function () {
    $page = abcRegistrationPage();
    $original = $page->fresh()->content;

    abcCardMigration()->up();
    abcCardMigration()->down();

    expect($page->fresh()->content)->toBe($original);
});

test('the migration does nothing when the page is missing', function () {
    // Earlier migrations create a few pages of their own, so compare
    // before and after rather than expecting an empty table.
    $before = Page::orderBy('id')->get(['id', 'slug', 'content'])->toArray();

    abcCardMigration()->up();

    expect(Page::where('slug', 'apostolic-bible-college/enrollment')->exists())->toBeFalse()
        ->and(Page::orderBy('id')->get(['id', 'slug', 'content'])->toArray())->toBe($before);
});

test('a fresh install seeds the first-time card with the native form', function () {
    $this->seed(ApostolicBibleCollegePageSeeder::class);

    $page = Page::where('slug', 'apostolic-bible-college/enrollment')->sole();
    $cards = collect($page->content)->firstWhere('type', 'cards')['data']['items'];
    $firstTime = collect($cards)->firstWhere('data.title', 'First time student');

    expect($firstTime['data']['link_url'])->toBe(ABC_FIRST_TIME_NATIVE);
});
