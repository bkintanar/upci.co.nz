<?php

use App\Models\User;
use Livewire\Livewire;
use App\Enums\UserRole;
use App\Enums\AccessLevel;
use App\Models\AbcEnrolment;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use App\Filament\Resources\AbcEnrolments\Pages\ViewAbcEnrolment;
use App\Filament\Resources\AbcEnrolments\Pages\ListAbcEnrolments;

/*
 * The ABC Enrolments inbox holds applicants' dates of birth, addresses,
 * phone numbers and learning difficulties, so it is national-only and
 * read/delete only, like Contact Messages.
 */
beforeEach(function () {
    Filament::setCurrentPanel('admin');
    Storage::fake('local');
});

function abcEnrolmentAdminUser(string $level): User
{
    return User::create([
        'name' => 'T',
        'email' => $level.'-abc-enrolment@x',
        'password' => 'x',
        'role' => UserRole::ADMINISTRATOR,
        'access_level' => AccessLevel::from($level),
    ]);
}

function abcEnrolmentRecord(array $overrides = []): AbcEnrolment
{
    return AbcEnrolment::create(array_merge([
        'full_name' => 'Mere Tūhoe',
        'date_of_birth' => '1995-04-12',
        'gender' => 'female',
        'citizenship' => 'New Zealand',
        'address' => '12 Example Street, Hamilton',
        'home_phone' => '07 123 4567',
        'mobile_phone' => '021 123 4567',
        'email' => 'mere@example.test',
        'resides_in_nz' => true,
        'reason_for_attending' => 'To be equipped for ministry.',
        'attended_upci_bible_school' => false,
        'has_learning_difficulties' => false,
        'wants_ministerial_licence' => true,
        'read_articles_of_faith' => false,
        'baptised_in_jesus_name' => true,
        'received_holy_ghost' => true,
        'church_name' => 'Grace Fellowship (Hamilton)',
        'pastor_name' => 'Pastor Example',
        'attendance_duration' => '3 years',
        'ministry_involvement' => 'Youth and music.',
        'agreement_name' => 'Mere Tūhoe',
        'agreement_date' => now()->toDateString(),
    ], $overrides));
}

test('only national users may read or delete enrolments, and nobody creates or edits them', function () {
    $enrolment = abcEnrolmentRecord();
    $national = abcEnrolmentAdminUser('national');
    $regional = abcEnrolmentAdminUser('regional');
    $local = abcEnrolmentAdminUser('local');

    expect($national->can('viewAny', AbcEnrolment::class))->toBeTrue()
        ->and($national->can('view', $enrolment))->toBeTrue()
        ->and($national->can('delete', $enrolment))->toBeTrue()
        ->and($national->can('create', AbcEnrolment::class))->toBeFalse()
        ->and($national->can('update', $enrolment))->toBeFalse()
        ->and($regional->can('viewAny', AbcEnrolment::class))->toBeFalse()
        ->and($regional->can('view', $enrolment))->toBeFalse()
        ->and($local->can('viewAny', AbcEnrolment::class))->toBeFalse()
        ->and($local->can('view', $enrolment))->toBeFalse();
});

test('a national user can open the inbox and an enrolment', function () {
    $enrolment = abcEnrolmentRecord();

    $this->actingAs(abcEnrolmentAdminUser('national'));

    $this->get('/admin/abc-enrolments')->assertOk()->assertSee('Mere Tūhoe');
    $this->get("/admin/abc-enrolments/{$enrolment->id}")
        ->assertOk()
        ->assertSee('Grace Fellowship (Hamilton)')
        ->assertSee('Read and understood the Articles of Faith');
});

test('regional and local users are refused the inbox and its records', function (string $level) {
    $enrolment = abcEnrolmentRecord();

    $this->actingAs(abcEnrolmentAdminUser($level));

    $this->get('/admin/abc-enrolments')->assertForbidden();
    $this->get("/admin/abc-enrolments/{$enrolment->id}")->assertForbidden();
})->with(['regional', 'local']);

test('there is no create or edit page', function () {
    $enrolment = abcEnrolmentRecord();

    $this->actingAs(abcEnrolmentAdminUser('national'));

    // "create" is not a route, so it is read as a record key that does not exist.
    $this->get('/admin/abc-enrolments/create')->assertNotFound();

    // An unknown /admin/... path falls through to the SPA catch-all rather
    // than 404ing, so assert on the route table instead of the response.
    expect(Route::has('filament.admin.resources.abc-enrolments.edit'))->toBeFalse()
        ->and(Route::has('filament.admin.resources.abc-enrolments.create'))->toBeFalse()
        ->and(Route::has('filament.admin.resources.abc-enrolments.view'))->toBeTrue();
});

test('No answers render as No rather than disappearing', function () {
    $enrolment = abcEnrolmentRecord(['read_articles_of_faith' => false]);

    $this->actingAs(abcEnrolmentAdminUser('national'));

    Livewire::test(ViewAbcEnrolment::class, ['record' => $enrolment->getRouteKey()])
        // Bracketed by the next label, so "No" cannot be satisfied by some
        // later text such as "None uploaded".
        ->assertSeeInOrder(['Read and understood the Articles of Faith', 'No', 'Baptised in the name of Jesus Christ', 'Yes']);
});

test('applicant input is escaped, never rendered as HTML', function () {
    $enrolment = abcEnrolmentRecord(['full_name' => '<script>alert(1)</script>']);

    $this->actingAs(abcEnrolmentAdminUser('national'));

    $this->get("/admin/abc-enrolments/{$enrolment->id}")
        ->assertOk()
        ->assertDontSee('<script>alert(1)</script>', false)
        ->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false);

    $this->get('/admin/abc-enrolments')
        ->assertDontSee('<script>alert(1)</script>', false);
});

test('a national user can download an uploaded transcript', function () {
    Storage::disk('local')->put('abc-enrolments/transcripts/abc123.pdf', '%PDF-1.4 test');
    $enrolment = abcEnrolmentRecord([
        'attended_upci_bible_school' => true,
        'transcript_path' => 'abc-enrolments/transcripts/abc123.pdf',
        'transcript_original_name' => 'my-transcript.pdf',
    ]);

    $this->actingAs(abcEnrolmentAdminUser('national'));

    Livewire::test(ViewAbcEnrolment::class, ['record' => $enrolment->getRouteKey()])
        ->assertActionVisible('downloadTranscript')
        ->callAction('downloadTranscript')
        ->assertFileDownloaded('my-transcript.pdf');
});

test('the download action is hidden when there is no transcript', function () {
    $enrolment = abcEnrolmentRecord();

    $this->actingAs(abcEnrolmentAdminUser('national'));

    Livewire::test(ViewAbcEnrolment::class, ['record' => $enrolment->getRouteKey()])
        ->assertActionHidden('downloadTranscript');
});

test('deleting an enrolment removes its transcript file', function () {
    Storage::disk('local')->put('abc-enrolments/transcripts/abc123.pdf', '%PDF-1.4 test');
    $enrolment = abcEnrolmentRecord(['transcript_path' => 'abc-enrolments/transcripts/abc123.pdf']);

    $enrolment->delete();

    Storage::disk('local')->assertMissing('abc-enrolments/transcripts/abc123.pdf');
});

test('the list shows newest first and filters by transcript', function () {
    $older = abcEnrolmentRecord(['full_name' => 'Older Applicant']);
    $older->forceFill(['created_at' => now()->subDay()])->save();
    $withTranscript = abcEnrolmentRecord([
        'full_name' => 'With Transcript',
        'transcript_path' => 'abc-enrolments/transcripts/x.pdf',
    ]);

    $this->actingAs(abcEnrolmentAdminUser('national'));

    Livewire::test(ListAbcEnrolments::class)
        ->assertCanSeeTableRecords([$withTranscript, $older], inOrder: true)
        ->filterTable('transcript_path', true)
        ->assertCanSeeTableRecords([$withTranscript])
        ->assertCanNotSeeTableRecords([$older]);
});
