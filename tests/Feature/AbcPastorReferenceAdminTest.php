<?php

use App\Models\User;
use App\Models\Church;
use Livewire\Livewire;
use App\Enums\UserRole;
use App\Enums\AccessLevel;
use App\Models\AbcEnrolment;
use Filament\Facades\Filament;
use App\Models\AbcPastorReference;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Notification;
use App\Notifications\AbcPastorReferenceRequest;
use App\Filament\Resources\AbcEnrolments\Pages\ViewAbcEnrolment;
use App\Filament\Resources\AbcEnrolments\Pages\ListAbcEnrolments;

/*
 * The pastor's reference in the ABC Enrolments admin: its status and
 * answers on the enrolment, a column in the list, and "Email the pastor"
 * to send the questionnaire again or to a corrected address.
 */
beforeEach(function () {
    Filament::setCurrentPanel('admin');
    Storage::fake('local');
    Notification::fake();
});

function pastorReferenceAdmin(string $level = 'national'): User
{
    return User::create([
        'name' => 'R', 'email' => $level.'-pastor-ref-'.uniqid().'@x', 'password' => 'x',
        'role' => UserRole::ADMINISTRATOR, 'access_level' => AccessLevel::from($level),
    ]);
}

function pastorReferenceEnrolmentRecord(?array $reference = null): AbcEnrolment
{
    $enrolment = AbcEnrolment::create([
        'full_name' => 'Mere Tūhoe', 'date_of_birth' => '1995-04-12', 'gender' => 'female',
        'citizenship' => 'New Zealand', 'address' => '12 Example Street', 'home_phone' => '07 123 4567',
        'mobile_phone' => '021 123 4567', 'email' => 'mere@example.test', 'resides_in_nz' => true,
        'reason_for_attending' => 'To be equipped.', 'attended_upci_bible_school' => false,
        'has_learning_difficulties' => false, 'wants_ministerial_licence' => true,
        'read_articles_of_faith' => true, 'baptised_in_jesus_name' => true, 'received_holy_ghost' => true,
        'church_name' => 'Grace Fellowship (Hamilton)', 'pastor_name' => 'Wayne Example',
        'attendance_duration' => '3 years', 'ministry_involvement' => 'Music.',
        'agreement_name' => 'Mere Tūhoe', 'agreement_date' => now()->toDateString(),
    ]);

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

test('an enrolment waiting on its pastor says so, and where the address came from', function () {
    $this->actingAs(pastorReferenceAdmin());
    $enrolment = pastorReferenceEnrolmentRecord([]);

    Livewire::test(ViewAbcEnrolment::class, ['record' => $enrolment->getRouteKey()])
        ->assertSee("Pastor's reference")
        ->assertSee('Waiting for the pastor')
        ->assertSee('wayne@grace.test')
        ->assertSee('Given by the applicant')
        ->assertDontSee('Faithful in attendance to services')
        ->assertActionVisible('emailPastor');
});

test('the pastor\'s answers show on the enrolment, escaped', function () {
    $this->actingAs(pastorReferenceAdmin());
    $enrolment = pastorReferenceEnrolmentRecord([
        'faithful_attendance' => true, 'faithful_holiness' => true, 'faithful_tithes' => false,
        'is_serving' => true, 'serving_details' => "Leads the youth band.\n<script>alert(1)</script>",
        'approves' => true, 'approves_level' => true, 'comments' => 'Faithful.',
        'signed_name' => 'Wayne Example', 'submitted_at' => now(),
    ]);

    Livewire::test(ViewAbcEnrolment::class, ['record' => $enrolment->getRouteKey()])
        ->assertSeeInOrder(['Approved', 'Faithful in attendance to services', 'Faithful in tithes and offerings', 'Happy for them to start at Certificate Level (Year 1)', 'Leads the youth band.', 'Faithful.'])
        ->assertSee('&lt;script&gt;', escape: false)
        ->assertDontSee('<script>alert(1)</script>', escape: false)
        ->assertActionHidden('emailPastor');
});

test('an enrolment from before pastor references began says it was not requested', function () {
    $this->actingAs(pastorReferenceAdmin());
    $enrolment = pastorReferenceEnrolmentRecord();

    Livewire::test(ViewAbcEnrolment::class, ['record' => $enrolment->getRouteKey()])
        ->assertSee('Not requested: enrolled before pastor references began');
});

test('the list shows each enrolment\'s reference status', function () {
    $this->actingAs(pastorReferenceAdmin());
    $waiting = pastorReferenceEnrolmentRecord([]);
    $approved = pastorReferenceEnrolmentRecord(['approves' => true, 'submitted_at' => now()]);
    $old = pastorReferenceEnrolmentRecord();

    Livewire::test(ListAbcEnrolments::class)
        ->assertCanSeeTableRecords([$waiting, $approved, $old])
        ->assertSee('Waiting for the pastor')
        ->assertSee('Approved')
        ->assertSee('Not requested');
});

test('emailing the pastor again sends a new link to the corrected address', function () {
    $this->actingAs(pastorReferenceAdmin());
    $enrolment = pastorReferenceEnrolmentRecord([]);
    $oldHash = $enrolment->pastorReference->getRawOriginal('token_hash');

    Livewire::test(ViewAbcEnrolment::class, ['record' => $enrolment->getRouteKey()])
        ->callAction('emailPastor', data: ['pastor_name' => 'Wayne Example', 'pastor_email' => 'wayne@new.test'])
        ->assertHasNoActionErrors()
        ->assertNotified('Questionnaire emailed to wayne@new.test');

    $reference = $enrolment->pastorReference()->first();
    expect($reference->pastor_email)->toBe('wayne@new.test')
        ->and($reference->pastor_email_source)->toBe('admin')
        ->and($reference->getRawOriginal('token_hash'))->not->toBe($oldHash);

    Notification::assertSentOnDemand(
        AbcPastorReferenceRequest::class,
        fn ($notification, $channels, $notifiable) => $notifiable->routes['mail'] === 'wayne@new.test'
            && hash('sha256', $notification->token) === $reference->getRawOriginal('token_hash'),
    );

    // The old link no longer opens the questionnaire.
    expect(AbcPastorReference::where('token_hash', $oldHash)->exists())->toBeFalse();
});

test('an older enrolment can be sent its first questionnaire, to the pastor on file', function () {
    $church = Church::create(['name' => 'Grace Fellowship - A UPCINZ Church', 'city' => 'Hamilton', 'is_active' => true]);
    User::create(['name' => 'Wayne Example', 'email' => 'wayne@grace.test', 'password' => 'x', 'role' => UserRole::SENIOR_PASTOR, 'church_id' => $church->id]);
    $this->actingAs(pastorReferenceAdmin());
    $enrolment = pastorReferenceEnrolmentRecord();

    Livewire::test(ViewAbcEnrolment::class, ['record' => $enrolment->getRouteKey()])
        ->assertActionHasLabel('emailPastor', 'Ask the pastor for a reference')
        ->mountAction('emailPastor')
        ->assertActionDataSet(['pastor_name' => 'Wayne Example', 'pastor_email' => 'wayne@grace.test'])
        ->callMountedAction()
        ->assertHasNoActionErrors();

    expect($enrolment->pastorReference()->first()->pastor_email_source)->toBe('church_records');
});

test('regional and local users cannot reach an enrolment or its reference', function (string $level) {
    $enrolment = pastorReferenceEnrolmentRecord([]);

    $this->actingAs(pastorReferenceAdmin($level))
        ->get("/admin/abc-enrolments/{$enrolment->id}")
        ->assertForbidden();
})->with(['regional', 'local']);
