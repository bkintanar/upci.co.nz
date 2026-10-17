<?php

use App\Models\Page;
use App\Models\User;
use Livewire\Livewire;
use App\Enums\UserRole;
use App\Enums\AccessLevel;
use Filament\Facades\Filament;
use Illuminate\Http\UploadedFile;
use Filament\Forms\Components\Builder;
use Illuminate\Support\Facades\Storage;
use App\Filament\Resources\Pages\Pages\EditPage;

/*
 * "Also add an announcement block to the ABC page" (owner, 2026-10-17). The
 * announcements live in the block, are shown like a department's, and the
 * page API gets them ready: hidden ones out, newest first, photo sizes in.
 */
beforeEach(function () {
    Storage::fake('public');
});

function announcementsBlockPage(array $items, array $more = []): Page
{
    return Page::create([
        'title' => 'Announcements test', 'slug' => 'announcements-test-'.uniqid(), 'is_published' => true,
        'content' => [
            ['type' => 'text', 'data' => ['heading' => 'Before', 'content' => 'Untouched.', 'background' => 'white', 'style' => 'default']],
            ['type' => 'announcements', 'data' => ['heading' => 'Announcements', 'empty_message' => null, 'items' => array_map(
                fn ($item) => ['type' => 'announcement', 'data' => $item], $items
            )]],
            ...$more,
        ],
    ]);
}

function announcementsBlockMigration(): object
{
    return require database_path('migrations/2026_10_17_000005_add_announcements_to_the_abc_page.php');
}

function announcementsBlockAbcPage(): Page
{
    return Page::updateOrCreate(['slug' => 'apostolic-bible-college'], [
        'title' => 'Apostolic Bible College - UPCI New Zealand',
        'is_published' => true,
        'content' => [
            ['type' => 'hero', 'data' => ['heading' => 'Apostolic Bible College', 'style' => 'gradient-blue']],
            ['type' => 'text', 'data' => ['heading' => 'About Apostolic Bible College', 'content' => 'About.', 'background' => 'white', 'style' => 'default']],
            ['type' => 'feature', 'data' => ['heading' => 'Foundational Level']],
        ],
    ]);
}

test('the page API shows announcements newest first, without hidden ones', function () {
    $page = announcementsBlockPage([
        ['title' => 'Older', 'published_at' => '2026-09-01', 'content' => 'First term.', 'is_published' => true],
        ['title' => 'Hidden', 'published_at' => '2026-10-20', 'is_published' => false],
        ['title' => 'Newer', 'published_at' => '2026-10-10', 'content' => 'Second term.', 'is_published' => true],
    ]);

    $content = $this->getJson("/api/pages/{$page->slug}")->assertOk()->json('data.content');

    expect(array_column($content[1]['data']['items'], 'title'))->toBe(['Newer', 'Older'])
        ->and($content[1]['data']['items'][0])->toMatchArray([
            'id' => 1, 'title' => 'Newer', 'content' => 'Second term.', 'published_at' => '2026-10-10',
            'image_path' => null, 'image_alt' => null, 'image_width' => null, 'image_height' => null,
        ])
        // Every other block passes through untouched.
        ->and($content[0])->toBe($page->content[0]);
});

test('a photo comes with its size and description, and a description alone stays private', function () {
    Storage::disk('public')->putFileAs('announcements', UploadedFile::fake()->image('flyer.jpg', 800, 1131), 'flyer.jpg');
    $page = announcementsBlockPage([
        ['title' => 'Flyer', 'published_at' => '2026-10-10', 'image_path' => 'announcements/flyer.jpg', 'image_alt' => 'Open day flyer, 1 November'],
        ['title' => 'Words', 'published_at' => '2026-10-01', 'image_path' => null, 'image_alt' => 'Typed without a photo'],
    ]);

    $items = $this->getJson("/api/pages/{$page->slug}")->json('data.content.1.data.items');

    expect($items[0])->toMatchArray(['image_path' => 'announcements/flyer.jpg', 'image_alt' => 'Open day flyer, 1 November', 'image_width' => 800, 'image_height' => 1131])
        ->and($items[1]['image_alt'])->toBeNull();
});

test('every announcement field is declared in the page form', function (string $key) {
    $form = file_get_contents(app_path('Filament/Resources/Pages/Schemas/PageForm.php'));
    $block = substr($form, strpos($form, "Builder\\Block::make('announcements')"), 6000);

    expect($block)->toContain("::make('{$key}'");
})->with(['heading', 'empty_message', 'limit', 'items', 'title', 'published_at', 'is_published', 'content', 'image_path', 'image_alt']);

test('an admin adds an announcement with a photo, which is stored as a public path', function () {
    $restore = Builder::fake();

    try {
        Filament::setCurrentPanel('admin');
        $this->actingAs(User::create([
            'name' => 'N', 'email' => 'announcements-editor@x', 'password' => 'x',
            'role' => UserRole::ADMINISTRATOR, 'access_level' => AccessLevel::NATIONAL,
        ]));
        $page = announcementsBlockPage([]);

        Livewire::test(EditPage::class, ['record' => $page->getRouteKey()])
            ->fillForm(['content' => [
                ['type' => 'text', 'data' => ['heading' => 'Before', 'content' => 'Untouched.', 'background' => 'white', 'style' => 'default']],
                ['type' => 'announcements', 'data' => ['heading' => 'Announcements', 'items' => [
                    ['type' => 'announcement', 'data' => [
                        'title' => 'Open day', 'published_at' => '2026-10-17', 'is_published' => true, 'content' => 'Come and see.',
                        'image_path' => [UploadedFile::fake()->image('flyer.jpg', 800, 1131)], 'image_alt' => 'Open day flyer',
                    ]],
                ]]],
            ]])
            ->call('save')
            ->assertHasNoFormErrors();

        $item = $page->fresh()->content[1]['data']['items'][0]['data'] ?? [];

        expect($item['title'] ?? null)->toBe('Open day')
            ->and($item['published_at'] ?? null)->toBe('2026-10-17')
            ->and($item['image_path'] ?? null)->toBeString()->toStartWith('announcements/');
        Storage::disk('public')->assertExists($item['image_path']);
    } finally {
        $restore();
    }
});

test('a photo without a description is refused in plain words', function () {
    $restore = Builder::fake();

    try {
        Filament::setCurrentPanel('admin');
        $this->actingAs(User::create([
            'name' => 'N', 'email' => 'announcements-alt@x', 'password' => 'x',
            'role' => UserRole::ADMINISTRATOR, 'access_level' => AccessLevel::NATIONAL,
        ]));
        $page = announcementsBlockPage([]);

        Livewire::test(EditPage::class, ['record' => $page->getRouteKey()])
            ->fillForm(['content' => [
                ['type' => 'announcements', 'data' => ['heading' => 'Announcements', 'items' => [
                    ['type' => 'announcement', 'data' => [
                        'title' => 'Open day', 'published_at' => '2026-10-17', 'is_published' => true,
                        'image_path' => [UploadedFile::fake()->image('flyer.jpg')], 'image_alt' => '',
                    ]],
                ]]],
            ]])
            ->call('save')
            ->assertHasFormErrors(['content.0.data.items.0.data.image_alt' => 'Describe the photo for people who cannot see it.']);
    } finally {
        $restore();
    }
});

test('the ABC page gets an empty announcements block after its About section', function () {
    $page = announcementsBlockAbcPage();

    announcementsBlockMigration()->up();
    announcementsBlockMigration()->up();

    $content = $page->fresh()->content;
    expect(array_column($content, 'type'))->toBe(['hero', 'text', 'announcements', 'feature'])
        ->and($content[2]['data'])->toBe(['heading' => 'Announcements', 'empty_message' => null, 'items' => []])
        ->and(Page::where('slug', 'apostolic-bible-college-before-announcements')->value('is_published'))->toBeFalse();
});

test('the ABC page is left alone without its About section', function () {
    $page = announcementsBlockAbcPage();
    $content = $page->content;
    $content[1]['data']['heading'] = 'Welcome';
    $page->update(['content' => $content]);

    announcementsBlockMigration()->up();

    expect(array_column($page->fresh()->content, 'type'))->toBe(['hero', 'text', 'feature']);
});

test('undoing takes out the block only while it is still empty', function () {
    $page = announcementsBlockAbcPage();
    announcementsBlockMigration()->up();

    $content = $page->fresh()->content;
    $content[2]['data']['items'] = [['type' => 'announcement', 'data' => ['title' => 'Kept', 'published_at' => '2026-10-17']]];
    $page->update(['content' => $content]);
    announcementsBlockMigration()->down();
    expect(array_column($page->fresh()->content, 'type'))->toContain('announcements');

    $content[2]['data']['items'] = [];
    $page->update(['content' => $content]);
    announcementsBlockMigration()->down();
    expect(array_column($page->fresh()->content, 'type'))->toBe(['hero', 'text', 'feature']);
});

test('the block renders nothing until there is an announcement or a message', function () {
    $source = file_get_contents(resource_path('js/components/blocks/AnnouncementsBlock.vue'));

    expect($source)->toContain('<section v-if="items.length || data.empty_message"')
        ->toContain('<AnnouncementList v-if="items.length" :items="items" />');
});

// ── The homepage (owner request, 2026-10-17) ───────────────────────────────

function announcementsHomeMigration(): object
{
    return require database_path('migrations/2026_10_17_000007_add_announcements_to_the_homepage.php');
}

/** The homepage's block sequence as it was on the live site on 2026-10-17. */
function announcementsHomePage(): Page
{
    return Page::updateOrCreate(['slug' => 'home'], [
        'title' => 'Home - UPCI New Zealand', 'is_published' => true,
        'content' => [
            ['type' => 'hero', 'data' => ['layout' => 'banner', 'heading' => 'Pentecost Everyday']],
            ['type' => 'cta', 'data' => ['style' => 'band', 'heading' => 'Annual General Conference']],
            ['type' => 'feature', 'data' => ['heading' => 'About the UPCI NZ', 'background' => 'paper']],
            ['type' => 'church_finder', 'data' => ['layout' => 'panel']],
            ['type' => 'events_feed', 'data' => ['layout' => 'cards']],
            ['type' => 'department_list', 'data' => ['layout' => 'tiles']],
            ['type' => 'cta', 'data' => ['style' => 'panel', 'heading' => 'Connect with us']],
        ],
    ]);
}

test('the homepage gets an Announcements block showing the newest three, after the Next band', function () {
    $page = announcementsHomePage();

    announcementsHomeMigration()->up();
    announcementsHomeMigration()->up();

    $content = $page->fresh()->content;
    expect(array_column($content, 'type'))->toBe(['hero', 'cta', 'announcements', 'feature', 'church_finder', 'events_feed', 'department_list', 'cta'])
        ->and($content[2]['data'])->toBe(['heading' => 'Announcements', 'empty_message' => null, 'limit' => 3, 'items' => []])
        ->and(Page::where('slug', 'home-before-announcements')->value('is_published'))->toBeFalse();
});

test('a homepage without the Next band is left alone', function () {
    $page = announcementsHomePage();
    $content = $page->content;
    array_splice($content, 1, 1);
    $page->update(['content' => $content]);

    announcementsHomeMigration()->up();

    expect(array_column($page->fresh()->content, 'type'))->not->toContain('announcements');
});

test('undoing takes the homepage block out only while it is still empty', function () {
    $page = announcementsHomePage();
    announcementsHomeMigration()->up();

    $content = $page->fresh()->content;
    $content[2]['data']['items'] = [['type' => 'announcement', 'data' => ['title' => 'Kept', 'published_at' => '2026-10-17']]];
    $page->update(['content' => $content]);
    announcementsHomeMigration()->down();
    expect(array_column($page->fresh()->content, 'type'))->toContain('announcements');

    $content[2]['data']['items'] = [];
    $page->update(['content' => $content]);
    announcementsHomeMigration()->down();
    expect(array_column($page->fresh()->content, 'type'))->toBe(['hero', 'cta', 'feature', 'church_finder', 'events_feed', 'department_list', 'cta']);
});

test('a limit shows only the newest few', function () {
    $page = announcementsBlockPage(array_map(
        fn ($day) => ['title' => "Day {$day}", 'published_at' => sprintf('2026-10-%02d', $day)],
        [3, 9, 1, 7, 5],
    ));
    $content = $page->content;
    $content[1]['data']['limit'] = 3;
    $page->update(['content' => $content]);

    $items = $this->getJson("/api/pages/{$page->slug}")->json('data.content.1.data.items');

    expect(array_column($items, 'title'))->toBe(['Day 9', 'Day 7', 'Day 5']);
});

test('an empty Announcements block takes no space, and the block below keeps its padding', function () {
    $cmsPage = file_get_contents(resource_path('js/views/CmsPage.vue'));

    expect($cmsPage)
        ->toContain("(block.type === 'announcements' && ((block.data?.items || []).length > 0 || Boolean(block.data?.empty_message)))")
        ->toContain('<AnnouncementsBlock v-else-if="block.type === \'announcements\'" :data="block.data" :flush-top="flushTop(index)" />')
        ->and(file_get_contents(resource_path('js/components/blocks/AnnouncementsBlock.vue')))
        ->toContain("props.flushTop ? 'pb-14 md:pb-20 xl:pb-24' : 'py-14 md:py-20 xl:py-24'");
});
