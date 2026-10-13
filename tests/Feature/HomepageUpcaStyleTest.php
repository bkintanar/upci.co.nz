<?php

use App\Models\Page;
use App\Models\User;
use App\Models\Event;
use Livewire\Livewire;
use App\Enums\UserRole;
use App\Enums\EventScope;
use App\Enums\AccessLevel;
use App\Models\Department;
use App\Models\SiteSetting;
use Filament\Facades\Filament;
use Database\Seeders\HomePageSeeder;
use App\Filament\Pages\ManageSiteSettings;

/*
 * The upca-style homepage (plan: .claude/PRPs/plans/homepage-upca-style.plan.md).
 *
 * The content switch is a guarded migration: it only rewrites a homepage that
 * still has the exact D2 block sequence, archives the outgoing version
 * unpublished, and rolls back only what it wrote.
 */

const UPCA_HOME_WRITTEN = ['hero', 'cta', 'feature', 'church_finder', 'events_feed', 'department_list', 'cta'];
const UPCA_HOME_ABOUT = 'The United Pentecostal Church International of New Zealand is a fellowship of apostolic congregations across the country, organised into the Northern, Central and Southern regions.';
const UPCA_HOME_WHO_WE_ARE = UPCA_HOME_ABOUT."\n\nWherever you are, you are welcome.";
const UPCA_HOME_CONNECT = "Questions, prayer requests, or looking for a church home — we'd love to hear from you.";

/** The live D2 homepage as of 2026-10-14, copied from the live database. */
function upcaHomeD2Blocks(): array
{
    return [
        ['type' => 'church_finder', 'data' => ['heading' => 'Find a church near you', 'placeholder' => 'Suburb, town or postcode', 'button_text' => 'Search']],
        ['type' => 'church_directory', 'data' => ['heading' => 'Our congregations', 'group_by_region' => true, 'empty_message' => 'Our church list is being updated. Please check back shortly.']],
        ['type' => 'text', 'data' => ['heading' => 'Who we are', 'content' => UPCA_HOME_WHO_WE_ARE]],
        ['type' => 'events_feed', 'data' => ['heading' => "What's coming up", 'scope' => 'national', 'limit' => 4, 'empty_message' => 'No events are scheduled at the moment.']],
        ['type' => 'department_list', 'data' => ['heading' => 'Our ministries', 'empty_message' => 'Ministry information is being updated.']],
        ['type' => 'cta', 'data' => ['heading' => 'Connect with us', 'content' => UPCA_HOME_CONNECT, 'button_text' => 'Get in touch', 'button_url' => '/connect-with-us']],
    ];
}

function upcaHomePage(array $blocks): Page
{
    Page::where('slug', 'home')->delete(); // test database only

    return Page::create(['slug' => 'home', 'title' => 'Home - UPCI New Zealand', 'is_published' => true, 'content' => $blocks]);
}

function upcaHomeMigration(): object
{
    return require database_path('migrations/2026_10_14_000002_author_the_upca_style_homepage.php');
}

function upcaHomeTypes(Page $page): array
{
    return array_map(fn ($b) => $b['type'], $page->fresh()->content);
}

function upcaHomeBlock(Page $page, string $type, int $nth = 0): array
{
    return array_values(array_filter($page->fresh()->content, fn ($b) => $b['type'] === $type))[$nth]['data'];
}

function upcaHomeUser(string $level): User
{
    return User::create([
        'name' => 'T',
        'email' => $level.'-upca-home@x',
        'password' => 'x',
        'role' => UserRole::ADMINISTRATOR,
        'access_level' => AccessLevel::from($level),
    ]);
}

// ── Migration ──────────────────────────────────────────────────────────────

test('the D2 homepage becomes the upca-style homepage', function () {
    $page = upcaHomePage(upcaHomeD2Blocks());

    upcaHomeMigration()->up();

    expect(upcaHomeTypes($page))->toBe(UPCA_HOME_WRITTEN);

    $hero = upcaHomeBlock($page, 'hero');
    expect($hero['layout'])->toBe('banner')
        ->and($hero['background_image'])->toBe('page-images/01KPYX2XHW5DRFENE0NJ918RC6.jpg')
        ->and($hero['heading'])->toBe('Pentecost Everyday')
        ->and($hero['verse'])->toContain('Acts 2:47');

    $band = upcaHomeBlock($page, 'cta', 0);
    expect($band['style'])->toBe('band')
        ->and($band['highlight'])->toBe('24–25')
        ->and($band['highlight_caption'])->toBe('October')
        ->and($band['heading'])->toBe('Annual General Conference');

    $finder = upcaHomeBlock($page, 'church_finder');
    expect($finder['layout'])->toBe('panel')
        ->and($finder['heading'])->toBe('Find a church near you')
        ->and($finder['show_directory'])->toBeTrue()
        ->and($finder['directory_heading'])->toBe('Our congregations')
        ->and($finder['help_link_url'])->toBe('/connect-with-us');

    expect(upcaHomeBlock($page, 'feature')['button_url'])->toBe('/about')
        ->and(upcaHomeBlock($page, 'feature')['image'])->toBeNull()
        ->and(upcaHomeBlock($page, 'events_feed')['layout'])->toBe('cards')
        ->and(upcaHomeBlock($page, 'events_feed')['scope'])->toBe('national')
        ->and(upcaHomeBlock($page, 'events_feed')['empty_message'])->toBe('No events are scheduled at the moment.')
        ->and(upcaHomeBlock($page, 'department_list')['layout'])->toBe('tiles')
        ->and(upcaHomeBlock($page, 'department_list')['lede'])->toStartWith('Six departments');
});

test('the closing welcome becomes the About tagline', function () {
    $page = upcaHomePage(upcaHomeD2Blocks());

    upcaHomeMigration()->up();

    $feature = upcaHomeBlock($page, 'feature');

    expect($feature['content'])->toBe(UPCA_HOME_ABOUT)
        ->and($feature['tagline'])->toBe('Wherever you are, you are welcome.');
});

test('a one-paragraph Who we are keeps its text and gets no tagline', function () {
    $blocks = upcaHomeD2Blocks();
    $blocks[2]['data']['content'] = 'Just the one paragraph.';
    $page = upcaHomePage($blocks);

    upcaHomeMigration()->up();

    expect(upcaHomeBlock($page, 'feature')['content'])->toBe('Just the one paragraph.')
        ->and(upcaHomeBlock($page, 'feature')['tagline'])->toBeNull();
});

test('the Connect sentence moves from content to text, where it renders', function () {
    $page = upcaHomePage(upcaHomeD2Blocks());

    upcaHomeMigration()->up();

    $connect = upcaHomeBlock($page, 'cta', 1);

    expect($connect['heading'])->toBe('Connect with us')
        ->and($connect['text'])->toBe(UPCA_HOME_CONNECT)
        ->and($connect['style'])->toBe('panel')
        ->and($connect)->not->toHaveKey('content');
});

test('the outgoing homepage is archived unpublished', function () {
    upcaHomePage(upcaHomeD2Blocks());

    upcaHomeMigration()->up();

    $archive = Page::where('slug', 'home-before-upca-style')->sole();

    expect($archive->is_published)->toBeFalse()
        ->and($archive->content)->toBe(upcaHomeD2Blocks());
});

test('a homepage edited in the CMS is left alone', function () {
    $edited = [['type' => 'text', 'data' => ['heading' => 'Our own page', 'content' => 'Edited', 'background' => 'white', 'style' => 'default']]];
    $page = upcaHomePage($edited);

    upcaHomeMigration()->up();

    expect($page->fresh()->content)->toBe($edited)
        ->and(Page::where('slug', 'home-before-upca-style')->exists())->toBeFalse();
});

test('it does nothing without a homepage', function () {
    Page::where('slug', 'home')->delete();

    upcaHomeMigration()->up();

    expect(Page::where('slug', 'home')->exists())->toBeFalse();
});

test('running it twice changes nothing more', function () {
    $page = upcaHomePage(upcaHomeD2Blocks());

    upcaHomeMigration()->up();
    $once = $page->fresh()->content;
    upcaHomeMigration()->up();

    expect($page->fresh()->content)->toBe($once)
        ->and(Page::where('slug', 'home-before-upca-style')->count())->toBe(1);
});

test('rolling back restores the D2 homepage and keeps the archive', function () {
    $page = upcaHomePage(upcaHomeD2Blocks());

    upcaHomeMigration()->up();
    upcaHomeMigration()->down();

    expect($page->fresh()->content)->toBe(upcaHomeD2Blocks())
        ->and(Page::where('slug', 'home-before-upca-style')->exists())->toBeTrue();
});

test('rolling back leaves a homepage edited after the switch alone', function () {
    $page = upcaHomePage(upcaHomeD2Blocks());
    upcaHomeMigration()->up();

    $edited = [['type' => 'text', 'data' => ['heading' => 'Edited later', 'content' => 'x', 'background' => 'white', 'style' => 'default']]];
    $page->update(['content' => $edited]);

    upcaHomeMigration()->down();

    expect($page->fresh()->content)->toBe($edited);
});

// ── Admin form ─────────────────────────────────────────────────────────────

/** The source of one Builder block in PageForm, up to the next block. */
function upcaHomeBlockSource(string $block): string
{
    $source = file_get_contents(app_path('Filament/Resources/Pages/Schemas/PageForm.php'));
    $start = strpos($source, "Builder\\Block::make('{$block}')");
    expect($start)->not->toBeFalse("block {$block} is declared");
    $next = strpos($source, 'Builder\\Block::make(', $start + 1);

    return substr($source, $start, $next === false ? null : $next - $start);
}

test('every new block key is declared in the page form', function (string $block, array $keys) {
    $source = upcaHomeBlockSource($block);

    foreach ($keys as $key) {
        expect($source)->toContain("::make('{$key}')");
    }
})->with([
    'hero' => ['hero', ['layout', 'image_alt', 'eyebrow', 'verse']],
    'cta' => ['cta', ['eyebrow', 'highlight', 'highlight_caption']],
    'feature' => ['feature', ['heading', 'content', 'tagline', 'button_text', 'button_url', 'image', 'image_alt', 'fallback_caption', 'image_side', 'background']],
    'church_finder' => ['church_finder', ['layout', 'description', 'show_regions', 'show_directory', 'directory_heading', 'help_heading', 'help_text', 'help_link_text', 'help_link_url']],
    'church_directory' => ['church_directory', ['compact']],
    'events_feed' => ['events_feed', ['layout', 'more_text', 'more_url']],
    'department_list' => ['department_list', ['layout', 'lede']],
]);

test('the call-to-action block offers the band and panel styles', function () {
    expect(upcaHomeBlockSource('cta'))
        ->toContain("'band' => 'Slim band'")
        ->toContain("'panel' => 'Split panel'");
});

test('the feature photo uploads to the public disk', function () {
    expect(upcaHomeBlockSource('feature'))->toContain("->disk('public')")->toContain("->visibility('public')");
});

test('a national admin can open the new homepage in the editor', function () {
    Filament::setCurrentPanel('admin');
    $page = upcaHomePage(upcaHomeD2Blocks());
    upcaHomeMigration()->up();

    $this->actingAs(upcaHomeUser('national'))
        ->get("/admin/pages/{$page->id}/edit")
        ->assertOk()
        ->assertSee('Pentecost Everyday');
});

// ── Site settings: postal address ──────────────────────────────────────────

test('the site settings API publishes the postal address', function () {
    SiteSetting::current()->update(['postal_address' => "PO Box 1\nWellington"]);

    $this->getJson('/api/site-settings')
        ->assertOk()
        ->assertJsonPath('data.postal_address', "PO Box 1\nWellington");
});

test('a national admin can save the postal address', function () {
    Filament::setCurrentPanel('admin');
    $this->actingAs(upcaHomeUser('national'));

    Livewire::test(ManageSiteSettings::class)
        ->set('data.postal_address', "PO Box 1\nWellington")
        ->call('save')
        ->assertHasNoErrors();

    expect(SiteSetting::current()->fresh()->postal_address)->toBe("PO Box 1\nWellington");
});

test('a local admin cannot reach site settings', function () {
    $this->actingAs(upcaHomeUser('local'))
        ->get('/admin/manage-site-settings')
        ->assertForbidden();
});

// ── Seeder ─────────────────────────────────────────────────────────────────

test('a fresh install seeds the upca-style homepage', function () {
    Page::where('slug', 'home')->delete();

    $this->seed(HomePageSeeder::class);

    $page = Page::where('slug', 'home')->sole();

    expect(upcaHomeTypes($page))->toBe(UPCA_HOME_WRITTEN)
        ->and(upcaHomeBlock($page, 'feature')['content'])->toBe(UPCA_HOME_ABOUT)
        ->and(upcaHomeBlock($page, 'feature')['tagline'])->toBe('Wherever you are, you are welcome.')
        ->and(upcaHomeBlock($page, 'cta', 1)['text'])->toBe(UPCA_HOME_CONNECT);
});

test('the seeder never overwrites an existing homepage', function () {
    $existing = [['type' => 'text', 'data' => ['heading' => 'Mine', 'content' => 'x', 'background' => 'white', 'style' => 'default']]];
    $page = upcaHomePage($existing);

    $this->seed(HomePageSeeder::class);

    expect($page->fresh()->content)->toBe($existing);
});

// ── Events API: the department's white mark ────────────────────────────────

test('each event carries its department\'s white logo for the homepage tiles', function () {
    $department = Department::create([
        'name' => 'Prayer Ministry', 'slug' => 'prayer-upca-home', 'color_theme' => 'blue',
        'logo_light_path' => 'department-logos/PRAYER-WHITE.svg', 'is_published' => true,
    ]);
    Event::create([
        'name' => '22 Day Prayer', 'slug' => '22-day-prayer-upca-home', 'start_date' => '2026-10-01',
        'end_date' => '2026-10-22', 'is_published' => true, 'scope' => EventScope::NATIONAL,
        'department_id' => $department->id,
    ]);

    $event = collect($this->getJson('/api/events?scope=national')->json('data'))->firstWhere('slug', '22-day-prayer-upca-home');

    expect($event['department'])->toMatchArray([
        'slug' => 'prayer-upca-home',
        'color_theme' => 'blue',
        'logo_light_path' => 'department-logos/PRAYER-WHITE.svg',
    ]);
});

// ── Vue source contracts ───────────────────────────────────────────────────
//
// No JS test runner exists, so the promises the design makes in markup are
// pinned here by reading the source.

function upcaHomeSource(string $path): string
{
    return file_get_contents(resource_path("js/{$path}"));
}

test('there is no department-logo strip above the navbar', function () {
    // The owner removed it when choosing D2: "just remove the top most
    // navigation. the one with all the logos in it."
    expect(file_exists(resource_path('js/components/DepartmentStrip.vue')))->toBeFalse()
        ->and(upcaHomeSource('App.vue'))->not->toContain('Strip');
});

test('the navbar is D1\'s green bar and keeps its 80px height', function () {
    $navbar = upcaHomeSource('components/Navbar.vue');

    // The ABC enrolment page pins its running head under an 80px bar.
    expect($navbar)->toContain('bg-brand-green-900 text-white sticky top-0 z-50')
        ->toContain('h-20')
        ->toContain('hidden xl:flex')
        ->toContain('rounded-full')
        ->not->toContain('min-[1400px]')
        ->not->toContain('bg-slate-800');
});

test('the event row is a keyboard-scrollable region with no autoplay', function () {
    $events = upcaHomeSource('components/blocks/EventsFeedBlock.vue');

    expect($events)->toContain('snap-x')
        ->toContain('role="region"')
        ->toContain('tabindex="0"')
        ->not->toContain('setInterval')
        ->not->toContain('setTimeout');
});

test('the banner image is loaded eagerly at high priority', function () {
    $cms = upcaHomeSource('views/CmsPage.vue');
    $start = strpos($cms, "block.data.layout === 'banner'");
    $banner = substr($cms, $start, strpos($cms, '</section>', $start) - $start);

    expect($banner)->toContain('fetchpriority="high"')
        ->not->toContain('loading="lazy"')
        ->toContain('<h1');
});

test('the homepage components build no class names from fragments', function (string $path) {
    // Tailwind reads source as text; a class assembled from a template
    // literal is never emitted and silently renders unstyled.
    preg_match_all('/:?class="([^"]*)"/', upcaHomeSource($path), $matches);

    foreach ($matches[1] as $classes) {
        expect($classes)->not->toContain('${');
    }
})->with([
    'components/Navbar.vue',
    'components/Footer.vue',
    'components/blocks/FeatureBlock.vue',
    'components/blocks/ChurchFinderBlock.vue',
    'components/blocks/EventsFeedBlock.vue',
    'components/blocks/DepartmentListBlock.vue',
    'components/home/ChurchSpine.vue',
    'components/home/SectionHead.vue',
    'components/home/DepartmentRibbon.vue',
    'views/CmsPage.vue',
]);

test('the footer shows the postal address from site settings', function () {
    expect(upcaHomeSource('components/Footer.vue'))->toContain('postal_address')->toContain('whitespace-pre-line');
});
