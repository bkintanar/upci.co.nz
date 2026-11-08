<?php

use App\Models\User;
use App\Models\Church;
use App\Models\Region;
use Livewire\Livewire;
use App\Enums\UserRole;
use App\Enums\AccessLevel;
use App\Models\AbcEnrolment;
use Filament\Facades\Filament;
use App\Models\AbcPastorReference;
use Illuminate\Support\Facades\Storage;
use App\Filament\Widgets\MainStatsWidget;
use App\Filament\Pages\ManageSiteSettings;
use Illuminate\Support\Facades\Notification;
use App\Filament\Resources\Users\UserResource;
use App\Filament\Widgets\AttendanceTrendChart;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Notifications\AbcPastorReferenceRequest;
use App\Filament\Widgets\CategoryBreakdownWidget;
use App\Filament\Resources\Churches\ChurchResource;
use App\Filament\Resources\Attendances\AttendanceResource;
use App\Filament\Resources\AbcEnrolments\AbcEnrolmentResource;
use App\Filament\Resources\AbcEnrolments\Pages\ViewAbcEnrolment;

/*
 * "I now need a login with role of bible school admin or something, that can
 * see the list of enrollees on the bible school and data" (owner,
 * 2026-11-02). The Bible School access level opens ABC Enrolments (the list,
 * each enrolment with its pastor's reference, transcripts, and asking the
 * pastor for a reference) and nothing else in the admin.
 */
beforeEach(function () {
    Filament::setCurrentPanel('admin');
    Storage::fake('local');
    Notification::fake();
});

function bibleSchoolLogin(array $overrides = []): User
{
    return User::create(array_merge([
        'name' => 'Bible School', 'email' => 'bible-school-'.uniqid().'@x', 'password' => 'x',
        'role' => UserRole::MEMBER, 'access_level' => AccessLevel::BIBLE_SCHOOL,
    ], $overrides));
}

function bibleSchoolOtherLogin(AccessLevel $level, array $overrides = []): User
{
    return User::create(array_merge([
        'name' => 'Other', 'email' => $level->value.'-'.uniqid().'@x', 'password' => 'x',
        'role' => UserRole::ADMINISTRATOR, 'access_level' => $level,
    ], $overrides));
}

function bibleSchoolEnrolment(?array $reference = null, array $overrides = []): AbcEnrolment
{
    $enrolment = AbcEnrolment::create(array_merge([
        'full_name' => 'Mere Tūhoe', 'date_of_birth' => '1995-04-12', 'gender' => 'female',
        'citizenship' => 'New Zealand', 'address' => '12 Example Street', 'home_phone' => '07 123 4567',
        'mobile_phone' => '021 123 4567', 'email' => 'mere@example.test', 'resides_in_nz' => true,
        'reason_for_attending' => 'To be equipped.', 'attended_upci_bible_school' => false,
        'has_learning_difficulties' => false, 'wants_ministerial_licence' => true,
        'read_articles_of_faith' => true, 'baptised_in_jesus_name' => true, 'received_holy_ghost' => true,
        'church_name' => 'Grace Fellowship (Hamilton)', 'pastor_name' => 'Wayne Example',
        'attendance_duration' => '3 years', 'ministry_involvement' => 'Music.',
        'agreement_name' => 'Mere Tūhoe', 'agreement_date' => now()->toDateString(),
    ], $overrides));

    if ($reference !== null) {
        $row = new AbcPastorReference(array_merge([
            'pastor_name' => 'Wayne Example', 'pastor_email' => 'wayne@grace.test',
            'pastor_email_source' => 'applicant', 'emailed_at' => now(),
        ], $reference));
        $row->issueToken();
        $enrolment->pastorReference()->save($row);
    }

    return $enrolment;
}

test('a Bible School login gets into the admin panel, and the level can be chosen', function () {
    expect(bibleSchoolLogin()->canAccessPanel(Filament::getPanel('admin')))->toBeTrue()
        ->and(AccessLevel::BIBLE_SCHOOL->getLabel())->toBe('Bible School (ABC enrolments only)')
        ->and(AccessLevel::getOptions())->toHaveKey('bible_school');
});

test('it can read enrolments and ask for a pastor\'s reference, but not create, edit or delete them', function () {
    $enrolment = bibleSchoolEnrolment();
    $user = bibleSchoolLogin();

    expect($user->can('viewAny', AbcEnrolment::class))->toBeTrue()
        ->and($user->can('view', $enrolment))->toBeTrue()
        ->and($user->can('emailPastor', $enrolment))->toBeTrue()
        ->and($user->can('create', AbcEnrolment::class))->toBeFalse()
        ->and($user->can('update', $enrolment))->toBeFalse()
        ->and($user->can('delete', $enrolment))->toBeFalse()
        ->and($user->can('deleteAny', AbcEnrolment::class))->toBeFalse();

    // The email ability is new: national keeps it, regional and local never had it.
    expect(bibleSchoolOtherLogin(AccessLevel::NATIONAL)->can('emailPastor', $enrolment))->toBeTrue()
        ->and(bibleSchoolOtherLogin(AccessLevel::REGIONAL)->can('emailPastor', $enrolment))->toBeFalse()
        ->and(bibleSchoolOtherLogin(AccessLevel::LOCAL)->can('emailPastor', $enrolment))->toBeFalse();
});

test('ABC Enrolments is the only resource it can open', function () {
    $this->actingAs(bibleSchoolLogin());

    // Every registered resource, so one added later without a policy fails here.
    foreach (Filament::getPanel('admin')->getResources() as $resource) {
        expect($resource::canViewAny())->toBe($resource === AbcEnrolmentResource::class, $resource);
    }

    expect(ManageSiteSettings::canAccess())->toBeFalse();
});

test('every other admin list, and the site settings, refuse it by URL', function () {
    $this->actingAs(bibleSchoolLogin());
    $checked = 0;

    foreach (Filament::getPanel('admin')->getResources() as $resource) {
        if ($resource === AbcEnrolmentResource::class || ! $resource::hasPage('index')) {
            continue;
        }

        $this->get($resource::getUrl('index'))->assertForbidden();
        $checked++;
    }

    $this->get(ManageSiteSettings::getUrl())->assertForbidden();

    expect($checked)->toBeGreaterThan(5);
});

test('the dashboard opens, without the attendance widgets', function () {
    $this->actingAs(bibleSchoolLogin());

    $this->get('/admin')->assertOk();

    // The widgets load lazily, so the page loading proves nothing about them.
    expect(MainStatsWidget::canView())->toBeFalse()
        ->and(AttendanceTrendChart::canView())->toBeFalse()
        ->and(CategoryBreakdownWidget::canView())->toBeFalse();

    $this->actingAs(bibleSchoolOtherLogin(AccessLevel::NATIONAL));

    expect(MainStatsWidget::canView())->toBeTrue()
        ->and(AttendanceTrendChart::canView())->toBeTrue()
        ->and(CategoryBreakdownWidget::canView())->toBeTrue();
});

test('it sees the list and the whole enrolment, the pastor\'s answers included', function () {
    $enrolment = bibleSchoolEnrolment([
        'faithful_attendance' => true, 'faithful_holiness' => true, 'faithful_tithes' => false,
        'is_serving' => true, 'serving_details' => 'Leads the youth band.',
        'approves' => true, 'approves_level' => true, 'comments' => 'Faithful.',
        'signed_name' => 'Wayne Example', 'submitted_at' => now(),
    ]);

    $this->actingAs(bibleSchoolLogin());

    $this->get('/admin/abc-enrolments')->assertOk()->assertSee('Mere Tūhoe');
    $this->get("/admin/abc-enrolments/{$enrolment->id}")
        ->assertOk()
        ->assertSee('Grace Fellowship (Hamilton)')
        ->assertSee("Pastor's reference");

    Livewire::test(ViewAbcEnrolment::class, ['record' => $enrolment->getRouteKey()])
        ->assertSeeInOrder(['Approved', 'Faithful in attendance to services', 'Leads the youth band.', 'Faithful.'])
        ->assertActionHidden('delete');
});

test('it can download a transcript', function () {
    Storage::disk('local')->put('abc-enrolments/transcripts/bs123.pdf', '%PDF-1.4 test');
    $enrolment = bibleSchoolEnrolment(overrides: [
        'attended_upci_bible_school' => true,
        'transcript_path' => 'abc-enrolments/transcripts/bs123.pdf',
        'transcript_original_name' => 'transcript.pdf',
    ]);

    $this->actingAs(bibleSchoolLogin());

    Livewire::test(ViewAbcEnrolment::class, ['record' => $enrolment->getRouteKey()])
        ->assertActionVisible('downloadTranscript')
        ->callAction('downloadTranscript')
        ->assertFileDownloaded('transcript.pdf');
});

test('it can email the pastor for a reference', function () {
    $enrolment = bibleSchoolEnrolment([]);

    $this->actingAs(bibleSchoolLogin());

    Livewire::test(ViewAbcEnrolment::class, ['record' => $enrolment->getRouteKey()])
        ->assertActionVisible('emailPastor')
        ->callAction('emailPastor', data: ['pastor_name' => 'Wayne Example', 'pastor_email' => 'wayne@grace.test'])
        ->assertHasNoActionErrors()
        ->assertNotified('Questionnaire emailed to wayne@grace.test');

    Notification::assertSentOnDemand(
        AbcPastorReferenceRequest::class,
        fn ($notification, $channels, $notifiable) => $notifiable->routes['mail'] === 'wayne@grace.test',
    );
});

test('church-scoped lists come back empty for it, not broken', function () {
    $region = Region::firstOrCreate(['slug' => 'northern'], ['name' => 'Northern Region', 'sort_order' => 1]);
    Church::create(['name' => 'North Church', 'region_id' => $region->id, 'is_active' => true]);

    $this->actingAs(bibleSchoolLogin());

    expect(ChurchResource::getEloquentQuery()->count())->toBe(0)
        ->and(UserResource::getEloquentQuery()->count())->toBe(0)
        ->and(AttendanceResource::getEloquentQuery()->count())->toBe(0);
});

test('a regional presbyter cannot view or edit a Bible School login in their region', function () {
    $region = Region::firstOrCreate(['slug' => 'northern'], ['name' => 'Northern Region', 'sort_order' => 1]);
    $church = Church::create(['name' => 'North Church', 'region_id' => $region->id, 'is_active' => true]);

    $regional = bibleSchoolOtherLogin(AccessLevel::REGIONAL, ['role' => UserRole::REGIONAL_PRESBYTER, 'region_id' => $region->id]);
    $local = bibleSchoolOtherLogin(AccessLevel::LOCAL, ['role' => UserRole::PASTOR, 'church_id' => $church->id]);
    $bibleSchool = bibleSchoolLogin(['church_id' => $church->id]);

    // Control: the same presbyter does manage a local login of that church.
    expect($regional->can('view', $local))->toBeTrue()
        ->and($regional->can('update', $local))->toBeTrue();

    // Editing it would mean setting its password, and so reading applicants' details.
    expect($regional->can('view', $bibleSchool))->toBeFalse()
        ->and($regional->can('update', $bibleSchool))->toBeFalse();
});

test('a national administrator can give a login Bible School access', function () {
    $user = bibleSchoolOtherLogin(AccessLevel::LOCAL, ['role' => UserRole::MEMBER]);
    $this->actingAs(bibleSchoolOtherLogin(AccessLevel::NATIONAL));

    Livewire::test(EditUser::class, ['record' => $user->getRouteKey()])
        ->fillForm(['access_level' => AccessLevel::BIBLE_SCHOOL->value])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($user->fresh()->access_level)->toBe(AccessLevel::BIBLE_SCHOOL);

    $this->get("/admin/users/{$user->id}")
        ->assertOk()
        ->assertSee('Bible School (ABC enrolments only)');
});
