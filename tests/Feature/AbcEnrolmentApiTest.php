<?php

use App\Models\AbcEnrolment;
use Illuminate\Http\UploadedFile;
use App\Notifications\NewAbcEnrolment;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Notification;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Routing\Middleware\ThrottleRequests;

/*
 * POST /api/abc-enrolments: the native replacement for the Google Form
 * "Apostolic Bible School Enrolment Form for New Students".
 */

function abcEnrolmentPayload(array $overrides = []): array
{
    return array_merge([
        'full_name' => 'Mere Tūhoe',
        'date_of_birth' => '1995-04-12',
        'gender' => 'female',
        'citizenship' => 'New Zealand',
        'address' => "12 Example Street\nHamilton 3204",
        'home_phone' => '07 123 4567',
        'mobile_phone' => '021 123 4567',
        'email' => 'mere@example.test',
        'resides_in_nz' => 'yes',
        'reason_for_attending' => 'To be equipped for ministry.',
        'attended_upci_bible_school' => 'no',
        'has_learning_difficulties' => 'no',
        'wants_ministerial_licence' => 'yes',
        'read_articles_of_faith' => 'yes',
        'baptised_in_jesus_name' => 'yes',
        'received_holy_ghost' => 'yes',
        'church_name' => 'Grace Fellowship (Hamilton)',
        'pastor_name' => 'Pastor Example',
        // No church in the test database has a pastor on file, so the
        // pastor's reference needs an address (AbcPastorReferenceTest).
        'pastor_email' => 'pastor@example.test',
        'attendance_duration' => '3 years',
        'ministry_involvement' => 'Youth and music.',
        'agreement_name' => 'Mere Tūhoe',
        'agreement_date' => now()->toDateString(),
    ], $overrides);
}

beforeEach(function () {
    Notification::fake();
    Storage::fake('local');
    Storage::fake('public');
    config(['abc.enrolment_notify_to' => null]);

    // Several tests post more than the endpoint's 5-per-minute limit; the
    // limit itself has its own test below, which puts the middleware back.
    $this->withoutMiddleware(ThrottleRequests::class);
});

test('a complete enrolment is stored with its answers as booleans', function () {
    $this->postJson('/api/abc-enrolments', abcEnrolmentPayload())
        ->assertCreated()
        ->assertJson(['success' => true]);

    $enrolment = AbcEnrolment::sole();

    expect($enrolment->full_name)->toBe('Mere Tūhoe')
        ->and($enrolment->date_of_birth->toDateString())->toBe('1995-04-12')
        ->and($enrolment->resides_in_nz)->toBeTrue()
        ->and($enrolment->attended_upci_bible_school)->toBeFalse()
        ->and($enrolment->received_holy_ghost)->toBeTrue()
        ->and($enrolment->church_display)->toBe('Grace Fellowship (Hamilton)');
});

test('every question the Google Form required is required', function () {
    $required = [
        'full_name', 'date_of_birth', 'gender', 'citizenship', 'address', 'home_phone',
        'mobile_phone', 'email', 'resides_in_nz', 'reason_for_attending',
        'attended_upci_bible_school', 'has_learning_difficulties', 'wants_ministerial_licence',
        'read_articles_of_faith', 'baptised_in_jesus_name', 'received_holy_ghost',
        'church_name', 'pastor_name', 'attendance_duration', 'ministry_involvement',
        'agreement_name', 'agreement_date',
    ];

    $this->postJson('/api/abc-enrolments', [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors($required);

    expect(AbcEnrolment::count())->toBe(0);
});

test('yes/no answers must be yes or no', function () {
    $this->postJson('/api/abc-enrolments', abcEnrolmentPayload(['received_holy_ghost' => 'maybe']))
        ->assertJsonValidationErrors('received_holy_ghost');
});

test('living outside New Zealand requires the country', function () {
    $this->postJson('/api/abc-enrolments', abcEnrolmentPayload(['resides_in_nz' => 'no']))
        ->assertJsonValidationErrors('residence_country');

    $this->postJson('/api/abc-enrolments', abcEnrolmentPayload(['resides_in_nz' => 'no', 'residence_country' => 'Australia']))
        ->assertCreated();

    expect(AbcEnrolment::sole()->residence_country)->toBe('Australia');
});

test('a country left behind after switching back to New Zealand is dropped', function () {
    $this->postJson('/api/abc-enrolments', abcEnrolmentPayload(['resides_in_nz' => 'yes', 'residence_country' => 'Australia']))
        ->assertCreated();

    expect(AbcEnrolment::sole()->residence_country)->toBeNull();
});

test('learning difficulties need a type, and Other needs a description', function () {
    $this->postJson('/api/abc-enrolments', abcEnrolmentPayload(['has_learning_difficulties' => 'yes']))
        ->assertJsonValidationErrors('learning_difficulty');

    $this->postJson('/api/abc-enrolments', abcEnrolmentPayload(['has_learning_difficulties' => 'yes', 'learning_difficulty' => 'other']))
        ->assertJsonValidationErrors('learning_difficulty_other');

    $this->postJson('/api/abc-enrolments', abcEnrolmentPayload(['has_learning_difficulties' => 'yes', 'learning_difficulty' => 'telepathy']))
        ->assertJsonValidationErrors('learning_difficulty');

    $this->postJson('/api/abc-enrolments', abcEnrolmentPayload([
        'has_learning_difficulties' => 'yes',
        'learning_difficulty' => 'other',
        'learning_difficulty_other' => 'Dyslexia',
    ]))->assertCreated();

    expect(AbcEnrolment::sole()->learning_difficulty_display)->toBe('Other: Dyslexia');
});

test('a learning difficulty left behind after answering No is dropped', function () {
    $this->postJson('/api/abc-enrolments', abcEnrolmentPayload([
        'has_learning_difficulties' => 'no',
        'learning_difficulty' => 'vision',
        'learning_difficulty_other' => 'stray',
    ]))->assertCreated();

    $enrolment = AbcEnrolment::sole();

    expect($enrolment->learning_difficulty)->toBeNull()
        ->and($enrolment->learning_difficulty_other)->toBeNull();
});

test('each church on the Google Form list is accepted', function (string $church) {
    $this->postJson('/api/abc-enrolments', abcEnrolmentPayload(['church_name' => $church]))
        ->assertCreated();
})->with(AbcEnrolment::CHURCHES);

test('a church not on the list is refused, and Other needs a name', function () {
    $this->postJson('/api/abc-enrolments', abcEnrolmentPayload(['church_name' => 'Made Up Church']))
        ->assertJsonValidationErrors('church_name');

    $this->postJson('/api/abc-enrolments', abcEnrolmentPayload(['church_name' => 'other']))
        ->assertJsonValidationErrors('church_name_other');

    $this->postJson('/api/abc-enrolments', abcEnrolmentPayload(['church_name' => 'other', 'church_name_other' => 'Lighthouse Rolleston']))
        ->assertCreated();

    expect(AbcEnrolment::sole()->church_display)->toBe('Other: Lighthouse Rolleston');
});

test('a transcript is stored on the private disk, never the public one', function () {
    $this->post('/api/abc-enrolments', abcEnrolmentPayload([
        'attended_upci_bible_school' => 'yes',
        'transcript' => UploadedFile::fake()->create('transcript.pdf', 200, 'application/pdf'),
    ]), ['Accept' => 'application/json'])->assertCreated();

    $enrolment = AbcEnrolment::sole();

    expect($enrolment->transcript_path)->toStartWith('abc-enrolments/transcripts/')
        ->and($enrolment->transcript_original_name)->toBe('transcript.pdf');

    Storage::disk('local')->assertExists($enrolment->transcript_path);
    expect(Storage::disk('public')->allFiles())->toBe([]);
});

test('a transcript is optional for someone who attended before', function () {
    $this->postJson('/api/abc-enrolments', abcEnrolmentPayload(['attended_upci_bible_school' => 'yes']))
        ->assertCreated();

    expect(AbcEnrolment::sole()->transcript_path)->toBeNull();
});

test('a transcript must be a PDF or image of at most 10 MB', function () {
    $this->post('/api/abc-enrolments', abcEnrolmentPayload([
        'attended_upci_bible_school' => 'yes',
        'transcript' => UploadedFile::fake()->create('virus.exe', 10, 'application/x-msdownload'),
    ]), ['Accept' => 'application/json'])->assertJsonValidationErrors('transcript');

    $this->post('/api/abc-enrolments', abcEnrolmentPayload([
        'attended_upci_bible_school' => 'yes',
        'transcript' => UploadedFile::fake()->create('big.pdf', 10241, 'application/pdf'),
    ]), ['Accept' => 'application/json'])->assertJsonValidationErrors('transcript');

    expect(AbcEnrolment::count())->toBe(0);
});

test('a transcript sent by someone who never attended is not kept', function () {
    $this->post('/api/abc-enrolments', abcEnrolmentPayload([
        'attended_upci_bible_school' => 'no',
        'transcript' => UploadedFile::fake()->create('transcript.pdf', 200, 'application/pdf'),
    ]), ['Accept' => 'application/json'])->assertCreated();

    expect(AbcEnrolment::sole()->transcript_path)->toBeNull()
        ->and(Storage::disk('local')->allFiles())->toBe([]);
});

test('date of birth must be in the past', function () {
    $this->postJson('/api/abc-enrolments', abcEnrolmentPayload(['date_of_birth' => now()->toDateString()]))
        ->assertJsonValidationErrors('date_of_birth');

    $this->postJson('/api/abc-enrolments', abcEnrolmentPayload(['date_of_birth' => '12/04/1995']))
        ->assertJsonValidationErrors('date_of_birth');
});

test('the agreement date must be today, allowing a day either side for New Zealand time', function () {
    $this->postJson('/api/abc-enrolments', abcEnrolmentPayload(['agreement_date' => now()->subDays(3)->toDateString()]))
        ->assertJsonValidationErrors('agreement_date');

    $this->postJson('/api/abc-enrolments', abcEnrolmentPayload(['agreement_date' => now()->addDays(3)->toDateString()]))
        ->assertJsonValidationErrors('agreement_date');

    $this->postJson('/api/abc-enrolments', abcEnrolmentPayload(['agreement_date' => now()->addDay()->toDateString()]))
        ->assertCreated();
});

test('a filled honeypot looks like success but stores and sends nothing', function () {
    config(['abc.enrolment_notify_to' => 'college@example.test']);

    $this->postJson('/api/abc-enrolments', abcEnrolmentPayload(['website' => 'http://spam.example']))
        ->assertCreated()
        ->assertJson(['success' => true]);

    expect(AbcEnrolment::count())->toBe(0);
    Notification::assertNothingSent();
});

test('no notification is sent while no recipient is configured', function () {
    $this->postJson('/api/abc-enrolments', abcEnrolmentPayload())->assertCreated();

    // The college's copy needs a recipient. The pastor's questionnaire is
    // sent regardless (AbcPastorReferenceTest), so only the college's email
    // is checked here.
    Notification::assertSentOnDemandTimes(NewAbcEnrolment::class, 0);
});

test('the configured recipient is notified once', function () {
    config(['abc.enrolment_notify_to' => 'college@example.test']);

    $this->postJson('/api/abc-enrolments', abcEnrolmentPayload())->assertCreated();

    Notification::assertSentOnDemandTimes(NewAbcEnrolment::class, 1);
    Notification::assertSentOnDemand(
        NewAbcEnrolment::class,
        fn ($notification, $channels, $notifiable) => $notifiable->routes['mail'] === 'college@example.test'
            && $notification->enrolmentId === AbcEnrolment::sole()->id,
    );
});

test('the notification email carries a link, not the applicant\'s personal details', function () {
    $mail = (new NewAbcEnrolment(42, 'Mere Tūhoe'))->toMail(new AnonymousNotifiable);
    $text = implode(' ', [$mail->subject, ...$mail->introLines, ...$mail->outroLines, $mail->actionUrl]);

    expect($text)->toContain('Mere Tūhoe')
        ->toContain('/admin/abc-enrolments/42')
        ->not->toContain('1995')
        ->not->toContain('021');
});

test('the endpoint is rate limited', function () {
    $this->app->offsetUnset(ThrottleRequests::class);

    $statuses = collect(range(1, 6))->map(
        fn () => $this->postJson('/api/abc-enrolments', abcEnrolmentPayload())->getStatusCode()
    );

    expect($statuses->last())->toBe(429);
});

test('errors tell the applicant what to do in plain words', function () {
    $errors = $this->postJson('/api/abc-enrolments', [])->assertUnprocessable()->json('errors');

    expect($errors['resides_in_nz'][0])->toBe('Select whether you are currently residing in New Zealand')
        ->and($errors['church_name'][0])->toBe('Select the church you currently attend')
        ->and($errors['full_name'][0])->toBe('Enter your full name')
        ->and(collect($errors)->flatten()->implode(' '))->not->toContain('The whether');

    $this->postJson('/api/abc-enrolments', abcEnrolmentPayload(['received_holy_ghost' => 'maybe']))
        ->assertJsonPath('errors.received_holy_ghost.0', 'Select whether you have received the Holy Ghost');
});
