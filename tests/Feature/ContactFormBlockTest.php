<?php

use App\Models\Page;
use App\Models\User;
use Livewire\Livewire;
use App\Enums\UserRole;
use App\Enums\AccessLevel;
use App\Models\ContactMessage;
use Filament\Facades\Filament;
use Illuminate\Routing\Middleware\ThrottleRequests;
use App\Filament\Resources\ContactMessages\Pages\ListContactMessages;

/*
 * The contact form as a CMS block (owner choice, 2026-10-17: "Both, via a form
 * block"). It is /connect-with-us, and it sits on the Apostolic Bible
 * College's Connect page; each message records the page it came from.
 */
beforeEach(function () {
    $this->withoutMiddleware(ThrottleRequests::class);
});

function contactFormMessage(array $overrides = []): array
{
    return array_merge([
        'first_name' => 'Mere', 'last_name' => 'Tūhoe', 'email' => 'mere@example.test', 'message' => 'Kia ora',
    ], $overrides);
}

function contactFormMigration(): object
{
    return require database_path('migrations/2026_10_17_000004_put_the_contact_form_on_the_abc_connect_page.php');
}

/** The ABC Connect page as it was on the live site on 2026-10-17. */
function contactFormAbcConnectPage(): Page
{
    return Page::updateOrCreate(['slug' => 'apostolic-bible-college/connect'], [
        'title' => 'Connect - Apostolic Bible College',
        'is_published' => true,
        'content' => [
            ['type' => 'hero', 'data' => ['heading' => 'Connect With Us', 'subheading' => 'Questions about Apostolic Bible College? Get in touch.', 'style' => 'gradient-indigo', 'background_image' => null]],
            ['type' => 'text', 'data' => ['heading' => 'Contact Us', 'content' => "Send us a message and someone from the college will get back to you.\n\n[Contact form](/connect-with-us)", 'background' => 'white', 'style' => 'default']],
            ['type' => 'text', 'data' => ['heading' => 'Prayer Request', 'content' => "Need prayer? Let us know and our team will stand with you.\n\n[Send a prayer request](/connect-with-us)", 'background' => 'slate', 'style' => 'default']],
        ],
    ]);
}

test('a message records the page it was sent from', function () {
    $this->postJson('/api/contact', contactFormMessage(['page' => '/apostolic-bible-college/connect']))->assertCreated();
    $this->postJson('/api/contact', contactFormMessage())->assertCreated();

    expect(ContactMessage::orderBy('id')->pluck('source_page')->all())->toBe(['/apostolic-bible-college/connect', null]);
});

test('the page must be a path on this site', function (string $page) {
    $this->postJson('/api/contact', contactFormMessage(['page' => $page]))
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['page' => 'The page must be a path on this site.']);

    expect(ContactMessage::count())->toBe(0);
})->with(['https://evil.example/x', 'javascript:alert(1)', 'free text', '/<script>']);

test('the inbox shows where each message came from', function () {
    Filament::setCurrentPanel('admin');
    $this->actingAs(User::create([
        'name' => 'N', 'email' => 'contact-inbox@x', 'password' => 'x',
        'role' => UserRole::ADMINISTRATOR, 'access_level' => AccessLevel::NATIONAL,
    ]));
    ContactMessage::create(contactFormMessage(['source_page' => '/apostolic-bible-college/connect']));

    Livewire::test(ListContactMessages::class)
        ->assertSee('Sent from')
        ->assertSee('/apostolic-bible-college/connect');
});

test('/connect-with-us is a CMS page built from the contact form block', function () {
    $content = $this->getJson('/api/pages/connect-with-us')->assertOk()->json('data.content');

    expect(array_column($content, 'type'))->toBe(['contact_form'])
        ->and($content[0]['data']['heading'])->toBe('Get in touch');

    $routes = file_get_contents(resource_path('js/router/routes.js'));
    expect(substr($routes, strpos($routes, "path: '/connect-with-us'"), 300))->toContain("import('../views/CmsPage.vue')");
});

test('the form block sends its page, says thank you aloud, and leaves the page title to the CMS', function () {
    $source = file_get_contents(resource_path('js/components/blocks/ContactFormBlock.vue'));

    expect($source)->toContain('body: JSON.stringify({ ...form, page: route.path })')
        ->toContain('role="status"')
        ->toContain("data.success_message || 'Thank you. Your message has been sent.'")
        ->not->toContain('setPageMeta(')
        ->not->toContain('<h1');
});

test('the ABC Connect page gets the form in place of its link', function () {
    $page = contactFormAbcConnectPage();

    contactFormMigration()->up();

    $content = $page->fresh()->content;
    expect(array_column($content, 'type'))->toBe(['hero', 'contact_form', 'text'])
        ->and($content[1]['data']['heading'])->toBe('Contact Us')
        ->and($content[1]['data']['intro'])->toBe('Send us a message and someone from the college will get back to you.')
        ->and($content[0])->toBe(contactFormAbcConnectPage()->getOriginal('content')[0] ?? $content[0]);

    $archive = Page::where('slug', 'apostolic-bible-college/connect-before-contact-form')->first();
    expect($archive->is_published)->toBeFalse()
        ->and(array_column($archive->content, 'type'))->toBe(['hero', 'text', 'text']);
});

test('the ABC Connect page is left alone once edited, and a rerun changes nothing', function () {
    $page = contactFormAbcConnectPage();
    $content = $page->content;
    $content[1]['data']['content'] = 'Email the college instead.';
    $page->update(['content' => $content]);

    contactFormMigration()->up();
    expect($page->fresh()->content)->toBe($content);

    contactFormAbcConnectPage();
    contactFormMigration()->up();
    $once = $page->fresh()->content;
    contactFormMigration()->up();
    expect($page->fresh()->content)->toBe($once);
});

test('undoing it brings the link section back and keeps later edits elsewhere', function () {
    $page = contactFormAbcConnectPage();
    contactFormMigration()->up();

    $content = $page->fresh()->content;
    $content[0]['data']['style'] = 'gradient-blue';
    $page->update(['content' => $content]);

    contactFormMigration()->down();

    $after = $page->fresh()->content;
    expect(array_column($after, 'type'))->toBe(['hero', 'text', 'text'])
        ->and($after[0]['data']['style'])->toBe('gradient-blue')
        ->and($after[1]['data']['content'])->toContain('[Contact form](/connect-with-us)')
        ->and(Page::where('slug', 'apostolic-bible-college/connect-before-contact-form')->exists())->toBeTrue();
});
