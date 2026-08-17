<?php

use App\Models\User;
use App\Models\Church;
use App\Models\Region;
use App\Enums\UserRole;
use App\Enums\AccessLevel;
use App\Models\Attendance;
use App\Models\Department;
use App\Models\SiteSetting;
use App\Models\ContactMessage;
use App\Models\DepartmentAnnouncement;
use App\Filament\Resources\Attendances\AttendanceResource;

/**
 * Regression cover for security defects that were live in production.
 *
 * Each of these was fixed by hand and verified manually. These tests exist so
 * that reintroducing any of them fails loudly rather than silently, which is
 * exactly what happened the first time — none of them were caught by a policy
 * review, because several never reach a policy at all.
 */
beforeEach(function () {
    $this->region = Region::firstOrCreate(['slug' => 'northern'], ['name' => 'Northern Region', 'sort_order' => 1]);
    $this->otherRegion = Region::firstOrCreate(['slug' => 'southern'], ['name' => 'Southern Region', 'sort_order' => 3]);
    // index() chains ->withCoordinates(), so a church without lat/lng is
    // invisible to the endpoint and any assertion over it would vacuously pass
    $this->church = Church::create([
        'name' => 'Regression Church',
        'region_id' => $this->region->id,
        'is_active' => true,
        'latitude' => -36.8485,
        'longitude' => 174.7633,
    ]);
});

function regressionUser(string $level, array $extra = []): User
{
    return User::create(array_merge([
        'name' => 'T',
        'email' => $level.'-regression@x',
        'password' => 'x',
        'role' => UserRole::ADMINISTRATOR,
        'access_level' => AccessLevel::from($level),
    ], $extra));
}

/*
|--------------------------------------------------------------------------
| Unauthenticated church writes
|--------------------------------------------------------------------------
| apiResource() registered all seven verbs with no auth middleware and no
| policy check, and api/* is CSRF-exempt, so DELETE /api/churches/{id} was an
| unauthenticated hard delete of production rows.
*/

test('church write verbs are not routable', function () {
    $id = $this->church->id;

    $this->postJson('/api/churches', ['name' => 'Injected'])->assertStatus(405);
    $this->putJson("/api/churches/{$id}", ['name' => 'Renamed'])->assertStatus(405);
    $this->patchJson("/api/churches/{$id}", ['name' => 'Renamed'])->assertStatus(405);
    $this->deleteJson("/api/churches/{$id}")->assertStatus(405);

    expect(Church::find($id))->not->toBeNull()
        ->and(Church::find($id)->name)->toBe('Regression Church');
});

test('church read endpoints still work', function () {
    $this->getJson('/api/churches')->assertOk()->assertJsonPath('success', true);
});

/*
|--------------------------------------------------------------------------
| Public API must not publish personal data
|--------------------------------------------------------------------------
| formatLeadershipForApi() returned each person's email and internal user id.
| Those emails are the login identifiers for the admin panel.
*/

test('the public church endpoint publishes no emails or user ids for leadership', function () {
    User::create([
        'name' => 'Pastor Person',
        'email' => 'pastor-regression@example.com',
        'password' => 'x',
        'role' => UserRole::SENIOR_PASTOR,
        'access_level' => AccessLevel::LOCAL,
        'church_id' => $this->church->id,
    ]);

    $payload = $this->getJson('/api/churches')->json('data');

    foreach ($payload as $church) {
        foreach (['pastors', 'elders', 'deacons', 'other_leadership'] as $group) {
            foreach ($church['leadership'][$group] ?? [] as $person) {
                expect($person)->not->toHaveKey('email')
                    ->and($person)->not->toHaveKey('id')
                    ->and($person)->toHaveKey('name');
            }
        }
    }
});

/*
|--------------------------------------------------------------------------
| Missing policy => Filament allows
|--------------------------------------------------------------------------
| DepartmentAnnouncement had no policy. Filament falls back to allow, so a
| local user could author content rendered through v-html on the public site.
*/

test('department announcements are national-only', function () {
    $department = Department::create(['name' => 'Regression Dept', 'slug' => 'regression-dept']);
    $announcement = DepartmentAnnouncement::create([
        'department_id' => $department->id,
        'title' => 'Test',
        'content' => 'Body',
    ]);

    $local = regressionUser('local', ['church_id' => $this->church->id]);
    $national = regressionUser('national');

    foreach (['viewAny', 'create'] as $ability) {
        expect($local->can($ability, DepartmentAnnouncement::class))->toBeFalse()
            ->and($national->can($ability, DepartmentAnnouncement::class))->toBeTrue();
    }

    expect($local->can('update', $announcement))->toBeFalse()
        ->and($local->can('delete', $announcement))->toBeFalse()
        ->and($national->can('update', $announcement))->toBeTrue();
});

test('every model reachable in the admin has a policy', function () {
    // Filament defaults an unpolicied model to ALLOW, so an absent policy is a
    // silent grant rather than a loud failure. This is the guard for that.
    $models = [
        App\Models\Church::class,
        App\Models\User::class,
        App\Models\Event::class,
        App\Models\Department::class,
        App\Models\DepartmentAnnouncement::class,
        App\Models\Page::class,
        App\Models\MenuItem::class,
        App\Models\GalleryItem::class,
        App\Models\AGSUpdate::class,
        App\Models\Attendance::class,
        App\Models\ContactMessage::class,
    ];

    foreach ($models as $model) {
        expect(Illuminate\Support\Facades\Gate::getPolicyFor($model) !== null)
            ->toBeTrue("{$model} has no policy — Filament defaults to allow");
    }
});

/*
|--------------------------------------------------------------------------
| Privilege escalation
|--------------------------------------------------------------------------
| UserPolicy::update() returns true unconditionally for self-edit, and the
| user form exposed access_level. disabled() alone is not a fix — Filament
| re-hydrates disabled fields, so dehydrated() must be present too.
*/

test('privilege fields on the user form are gated on national access', function () {
    $source = file_get_contents(app_path('Filament/Resources/Users/Schemas/UserForm.php'));

    foreach (['access_level', 'role', 'church_id', 'region_id'] as $field) {
        $offset = strpos($source, "Select::make('{$field}')");
        expect($offset !== false)->toBeTrue("field {$field} missing from the form");

        // inspect only this field's own chain, up to the next Select
        $next = strpos($source, 'Select::make(', $offset + 10);
        $chain = substr($source, $offset, ($next ?: strlen($source)) - $offset);

        // disabled() alone is not a fix — Filament re-hydrates disabled fields,
        // so dehydrated() must be present on the same chain.
        expect(str_contains($chain, '->disabled('))->toBeTrue("{$field} is not disabled for non-national users");
        expect(str_contains($chain, '->dehydrated('))->toBeTrue("{$field} lacks dehydrated()");
    }
});

/*
|--------------------------------------------------------------------------
| Dashboard widgets
|--------------------------------------------------------------------------
| Three widgets queried Attendance directly, bypassing both the policy and
| ScopesToAccessLevel, on the dashboard every panel user lands on.
*/

test('attendance widgets query through the scoped resource', function () {
    $widgets = [
        'MainStatsWidget.php',
        'CategoryBreakdownWidget.php',
        'AttendanceTrendChart.php',
    ];

    foreach ($widgets as $widget) {
        $source = file_get_contents(app_path("Filament/Widgets/{$widget}"));

        // strip comments so the explanatory notes don't trip the assertion
        // strip line comments then block comments; note the `s` flag must NOT
        // apply to the line-comment pattern or `.` swallows the rest of the file
        $code = preg_replace('#//[^\n]*#', '', $source);
        $code = preg_replace('#/\*.*?\*/#s', '', $code);

        // a bare `Attendance::` query bypasses both the policy and the scope
        expect(preg_match('/(?<!Resource::getEloquentQuery\(\))\bAttendance::/', $code))
            ->toBe(0, "{$widget} queries Attendance directly, bypassing access-level scoping");
        expect(str_contains($code, 'AttendanceResource::getEloquentQuery()'))
            ->toBeTrue("{$widget} does not use the scoped query");
    }
});

test('attendance scoping actually limits what a local user sees', function () {
    $otherChurch = Church::create(['name' => 'Other Church', 'region_id' => $this->otherRegion->id, 'is_active' => true]);

    $local = regressionUser('local', ['church_id' => $this->church->id]);
    $national = regressionUser('national');

    $counts = ['mens' => 1, 'ladies' => 1, 'youth' => 0, 'children' => 0, 'visitors' => 0];
    Attendance::create(['church_id' => $this->church->id, 'user_id' => $local->id, 'date' => now()->toDateString()] + $counts);
    Attendance::create(['church_id' => $otherChurch->id, 'user_id' => $national->id, 'date' => now()->toDateString()] + $counts);

    $this->actingAs($local);
    expect(AttendanceResource::getEloquentQuery()->count())->toBe(1);

    $this->actingAs($national);
    expect(AttendanceResource::getEloquentQuery()->count())->toBe(2);
});

/*
|--------------------------------------------------------------------------
| Contact messages
|--------------------------------------------------------------------------
| Inbound messages arrive from an unauthenticated endpoint. They must be
| readable by national users, invisible to everyone else, and never editable.
*/

test('contact messages are national-only and never authored in the admin', function () {
    $message = ContactMessage::create([
        'first_name' => 'A',
        'last_name' => 'Visitor',
        'email' => 'visitor@example.com',
        'message' => 'Hello',
    ]);

    $local = regressionUser('local', ['church_id' => $this->church->id]);
    $national = regressionUser('national');

    expect($national->can('viewAny', ContactMessage::class))->toBeTrue()
        ->and($national->can('view', $message))->toBeTrue()
        ->and($national->can('delete', $message))->toBeTrue()
        // inbound mail is not something an admin should be able to write
        ->and($national->can('create', ContactMessage::class))->toBeFalse()
        ->and($national->can('update', $message))->toBeFalse()
        ->and($local->can('viewAny', ContactMessage::class))->toBeFalse()
        ->and($local->can('view', $message))->toBeFalse();
});

/*
|--------------------------------------------------------------------------
| Rate limiting
|--------------------------------------------------------------------------
*/

test('the public contact endpoint is rate limited', function () {
    $payload = [
        'first_name' => 'A',
        'last_name' => 'B',
        'email' => 'a@b.com',
        'message' => 'Hello there',
    ];

    $sawLimit = false;
    for ($i = 0; $i < 8; $i++) {
        if ($this->postJson('/api/contact', $payload)->getStatusCode() === 429) {
            $sawLimit = true;
            break;
        }
    }

    expect($sawLimit)->toBeTrue('POST /api/contact accepted 8 requests without rate limiting');
});

/*
|--------------------------------------------------------------------------
| Uploads must reach the public disk
|--------------------------------------------------------------------------
| FILESYSTEM_DISK is `local`, and Filament v4 picks default visibility by
| literal disk-name match, so an upload field without disk('public') writes
| somewhere the web server cannot serve.
*/

test('every file upload targets the public disk and rejects svg', function () {
    $forms = [
        'Resources/GalleryItems/Schemas/GalleryItemForm.php',
        'Resources/Departments/Schemas/DepartmentForm.php',
        'Resources/Pages/Schemas/PageForm.php',
    ];

    foreach ($forms as $form) {
        $source = file_get_contents(app_path("Filament/{$form}"));

        foreach (explode('FileUpload::make(', $source) as $i => $chunk) {
            if ($i === 0) {
                continue; // text before the first FileUpload
            }
            $chain = substr($chunk, 0, strpos($chunk.'FileUpload::make(', 'FileUpload::make('));

            expect(str_contains($chain, "->disk('public')"))
                ->toBeTrue("a FileUpload in {$form} does not target the public disk");
        }
    }
});

/*
|--------------------------------------------------------------------------
| Site settings page
|--------------------------------------------------------------------------
| A custom Filament Page has no model and never consults a policy —
| CanAuthorizeAccess::canAccess() hard-returns true and discoverPages()
| auto-registers it. The page must gate itself, and the assertion has to be
| on the URL: a policy check would pass while the page stayed wide open.
*/

// Two separate tests on purpose: issuing the 403 and the 200 from one test
// leaves Filament/Livewire state behind that breaks the second request.
test('a local user cannot reach the site settings page', function () {
    $local = regressionUser('local', ['church_id' => $this->church->id]);

    $this->actingAs($local)->get('/admin/manage-site-settings')->assertForbidden();
});

test('a national user can reach the site settings page', function () {
    $national = regressionUser('national');

    $this->actingAs($national)->get('/admin/manage-site-settings')->assertOk();
});

test('site settings expose two independently editable logos', function () {
    $settings = SiteSetting::current();

    expect($settings->getFillable())->toContain('header_logo_path')
        ->and($settings->getFillable())->toContain('footer_logo_path');

    // one row, always
    SiteSetting::current();
    expect(SiteSetting::count())->toBe(1);

    $settings->update([
        'header_logo_path' => 'site/header.png',
        'footer_logo_path' => 'site/footer.png',
    ]);

    expect(SiteSetting::current()->header_logo_path)->toBe('site/header.png')
        ->and(SiteSetting::current()->footer_logo_path)->toBe('site/footer.png');
});

/*
|--------------------------------------------------------------------------
| Church locator coverage
|--------------------------------------------------------------------------
| index() used to chain ->withCoordinates(), which silently hid every church
| without lat/lng — half of them, including both of Central Region's, so that
| region showed nothing at all. They belong in the list and the region filter;
| only the map needs to skip them.
*/

test('churches without coordinates are still listed and filterable', function () {
    $unmapped = Church::create([
        'name' => 'Unmapped Church',
        'region_id' => $this->region->id,
        'is_active' => true,
    ]);

    $payload = $this->getJson('/api/churches')->assertOk()->json('data');
    $names = collect($payload)->pluck('name');

    expect($names)->toContain('Unmapped Church')
        ->and($names)->toContain('Regression Church');

    // and it must be honest about not being plottable
    $row = collect($payload)->firstWhere('name', 'Unmapped Church');
    expect($row['has_coordinates'])->toBeFalse();

    // the region filter must count it too
    $filtered = $this->getJson('/api/churches?organizational_region='.$this->region->slug)
        ->assertOk()->json('data');

    expect(collect($filtered)->pluck('id'))->toContain($unmapped->id);
});
