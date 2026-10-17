<?php

use App\Models\Page;
use App\Models\User;
use App\Enums\UserRole;
use App\Enums\AccessLevel;
use Filament\Facades\Filament;

/*
 * "https://upci.b8.co.nz/find-church make this editable in the CMS" (owner,
 * 2026-10-17). /find-church is a CMS page: the church finder is a block
 * (church_locator) with its hero words as fields, and the closing call to
 * action is an ordinary cta block.
 */

function findChurchBlockSource(): string
{
    return file_get_contents(resource_path('js/components/blocks/ChurchLocatorBlock.vue'));
}

test('the find-church page is a published CMS page with the finder and its call to action', function () {
    $content = $this->getJson('/api/pages/find-church')->assertOk()->json('data.content');

    expect(array_column($content, 'type'))->toBe(['church_locator', 'cta'])
        ->and($content[0]['data'])->toMatchArray([
            'eyebrow' => 'Find Your Local Church',
            'heading' => 'Discover UPCI Churches',
            'heading_highlight' => 'Across New Zealand',
            'search_placeholder' => 'Search by city, region, church name, or address...',
        ])
        ->and($content[1]['data'])->toMatchArray([
            'style' => 'blue',
            'heading' => "Can't Find a Church Near You?",
            'button_url' => '/connect-with-us',
        ]);
});

test('the router sends /find-church to the CMS page renderer, before the catch-all', function () {
    $routes = file_get_contents(resource_path('js/router/routes.js'));
    $route = substr($routes, strpos($routes, "path: '/find-church'"), 400);

    expect($route)->toContain("import('../views/CmsPage.vue')")
        ->and(strpos($routes, "path: '/find-church'"))->toBeLessThan(strpos($routes, "path: '/:slug(.*)'"));
});

test('the finder block owns the page heading, so no second title band is drawn', function () {
    $cmsPage = file_get_contents(resource_path('js/views/CmsPage.vue'));

    expect($cmsPage)->toContain("b.type === 'hero' || b.type === 'church_locator'")
        ->toContain('<ChurchLocatorBlock v-else-if="block.type === \'church_locator\'" :data="block.data" />')
        ->and(findChurchBlockSource())->toContain('<h1');
});

test('the finder still takes the homepage search over and keeps its filters in the address', function () {
    expect(findChurchBlockSource())
        ->toContain('route.query.search')
        ->toContain('route.query.region')
        ->toContain('router.replace({ path: route.path, query })')
        ->not->toContain("'/find-church'")
        // The CMS page owns the title now.
        ->not->toContain('setPageMeta(');
});

test('every field the finder block reads is declared in the page form', function (string $key) {
    $form = file_get_contents(app_path('Filament/Resources/Pages/Schemas/PageForm.php'));
    $block = substr($form, strpos($form, "Builder\\Block::make('church_locator')"), 4000);

    expect(findChurchBlockSource())->toContain("data.{$key}")
        ->and($block)->toContain("::make('{$key}')");
})->with(['eyebrow', 'heading', 'heading_highlight', 'intro', 'show_stats', 'extra_stat_value', 'extra_stat_label', 'search_placeholder']);

test('a national admin can open the page in the editor', function () {
    Filament::setCurrentPanel('admin');
    $admin = User::create([
        'name' => 'N', 'email' => 'find-church-editor@x', 'password' => 'x',
        'role' => UserRole::ADMINISTRATOR, 'access_level' => AccessLevel::NATIONAL,
    ]);

    $this->actingAs($admin)
        ->get('/admin/pages/'.Page::where('slug', 'find-church')->value('id').'/edit')
        ->assertOk()
        ->assertSee('Church finder (map and list)');
});

test('the SPA serves /find-church', function () {
    $this->get('/find-church?search=Hamilton')->assertOk();
});
