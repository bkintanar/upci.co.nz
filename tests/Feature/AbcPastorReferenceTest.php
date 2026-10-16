<?php

use App\Models\User;
use App\Models\Church;
use App\Enums\UserRole;
use App\Models\AbcEnrolment;
use App\Models\AbcPastorReference;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Notification;
use App\Notifications\AbcPastorReferenceRequest;
use App\Notifications\AbcPastorReferenceReceived;
use Illuminate\Routing\Middleware\ThrottleRequests;

/*
 * The pastor's reference (owner request, 2026-10-17, Brandon Borders' draft):
 * - the enrolment form's church dropdown fills in the pastor;
 * - each enrolment emails that pastor a link to a short questionnaire;
 * - the pastor's answers are stored once, and the college is told.
 */
beforeEach(function () {
    Notification::fake();
    Storage::fake('local');
    config(['abc.enrolment_notify_to' => null]);
    $this->withoutMiddleware(ThrottleRequests::class);

    // Names as the CMS has them, which are not the form's list names.
    $grace = Church::create(['name' => 'Grace Fellowship - A UPCINZ Church', 'city' => 'Hamilton', 'is_active' => true]);
    $triumphant = Church::create(['name' => 'Church Triumphant Wellington', 'city' => 'Wellington', 'is_active' => true]);
    $triumphant->forceFill(['pastor_name' => 'Andrew Example'])->save();
    Church::create(['name' => 'Walk by Faith Tongan Ministry ', 'city' => 'Papakura', 'is_active' => true]);

    $pastor = fn (string $name, string $email, UserRole $role, Church $church) => User::create([
        'name' => $name, 'email' => $email, 'password' => 'x', 'role' => $role, 'church_id' => $church->id,
    ]);
    $pastor('Wayne Example', 'wayne@grace.test', UserRole::SENIOR_PASTOR, $grace);
    $pastor('Assistant Example', 'assistant@grace.test', UserRole::ASSISTANT_PASTOR, $grace);
    // Church Triumphant's pastor is recorded on the church, and their user
    // account is not a pastor role (a board member, as on the live site).
    $pastor('Andrew Example', 'andrew@triumphant.test', UserRole::EXECUTIVE_BOARD, $triumphant);
});

function pastorReferenceEnrolment(array $overrides = []): array
{
    return array_merge([
        'full_name' => 'Mere Tūhoe', 'date_of_birth' => '1995-04-12', 'gender' => 'female',
        'citizenship' => 'New Zealand', 'address' => '12 Example Street', 'home_phone' => '07 123 4567',
        'mobile_phone' => '021 123 4567', 'email' => 'mere@example.test', 'resides_in_nz' => 'yes',
        'reason_for_attending' => 'To be equipped.', 'attended_upci_bible_school' => 'no',
        'has_learning_difficulties' => 'no', 'wants_ministerial_licence' => 'yes',
        'read_articles_of_faith' => 'yes', 'baptised_in_jesus_name' => 'yes', 'received_holy_ghost' => 'yes',
        'church_name' => 'Grace Fellowship (Hamilton)', 'pastor_name' => 'Wayne Example',
        'attendance_duration' => '3 years', 'ministry_involvement' => 'Music.',
        'agreement_name' => 'Mere Tūhoe', 'agreement_date' => now()->toDateString(),
    ], $overrides);
}

function pastorReferenceAnswers(array $overrides = []): array
{
    return array_merge([
        'faithful_attendance' => 'yes', 'faithful_holiness' => 'yes', 'faithful_tithes' => 'no',
        'is_serving' => 'yes', 'serving_details' => 'Leads the youth band.',
        'approves' => 'yes', 'approves_level' => 'yes',
        'comments' => 'A faithful young woman.', 'signed_name' => 'Wayne Example',
    ], $overrides);
}

/** Enrols, and returns the token from the email sent to the pastor. */
function pastorReferenceToken(array $overrides = []): string
{
    test()->postJson('/api/abc-enrolments', pastorReferenceEnrolment($overrides))->assertCreated();

    $token = null;
    Notification::assertSentOnDemand(AbcPastorReferenceRequest::class, function ($notification) use (&$token) {
        $token = $notification->token;

        return true;
    });

    return $token;
}

test('the dropdown learns each listed church\'s pastor, and never an email address', function () {
    $response = $this->getJson('/api/abc-enrolments/pastors')->assertOk();

    expect($response->json('data'))->toBe([
        'Church Triumphant Wellington (Wellington)' => ['pastor_name' => 'Andrew Example', 'pastor_on_file' => true],
        'Grace Fellowship (Hamilton)' => ['pastor_name' => 'Wayne Example', 'pastor_on_file' => true],
    ])->and($response->getContent())->not->toContain('@');
});

test('an applicant who names the pastor on file is not asked for an email', function () {
    $this->postJson('/api/abc-enrolments', pastorReferenceEnrolment(['pastor_name' => 'Pastor Wayne Example']))->assertCreated();

    $reference = AbcEnrolment::sole()->pastorReference;

    expect($reference->pastor_email)->toBe('wayne@grace.test')
        ->and($reference->pastor_email_source)->toBe('church_records')
        ->and($reference->pastor_name)->toBe('Pastor Wayne Example')
        ->and($reference->emailed_at)->not->toBeNull();

    Notification::assertSentOnDemand(
        AbcPastorReferenceRequest::class,
        fn ($notification, $channels, $notifiable) => $notifiable->routes['mail'] === 'wayne@grace.test'
            && $notification->applicantName === 'Mere Tūhoe',
    );
});

test('a pastor recorded on the church is reached at their own account\'s address', function () {
    $this->postJson('/api/abc-enrolments', pastorReferenceEnrolment([
        'church_name' => 'Church Triumphant Wellington (Wellington)', 'pastor_name' => 'Andrew Example',
    ]))->assertCreated();

    expect(AbcEnrolment::sole()->pastorReference->pastor_email)->toBe('andrew@triumphant.test');
});

test('naming someone else, choosing Other, or a church with no pastor on file needs the pastor\'s email', function (array $answers) {
    $this->postJson('/api/abc-enrolments', pastorReferenceEnrolment($answers))
        ->assertUnprocessable()
        ->assertJsonPath('errors.pastor_email.0', "Enter your pastor's email address, so the college can ask them for a reference");
})->with([
    'someone else' => [['pastor_name' => 'Ps. Somebody Else']],
    'Other' => [['church_name' => 'other', 'church_name_other' => 'Lighthouse Rolleston']],
    'no pastor on file' => [['church_name' => 'Walk by Faith (Auckland)']],
    'not in the CMS at all' => [['church_name' => 'House of Mercy (Tokoroa)']],
]);

test('an email the applicant gives is used, and labelled as theirs', function () {
    $this->postJson('/api/abc-enrolments', pastorReferenceEnrolment([
        'church_name' => 'other', 'church_name_other' => 'Lighthouse Rolleston',
        'pastor_name' => 'Anna Example', 'pastor_email' => 'anna@lighthouse.test',
    ]))->assertCreated();

    $reference = AbcEnrolment::sole()->pastorReference;

    expect($reference->pastor_email)->toBe('anna@lighthouse.test')
        ->and($reference->pastor_email_source)->toBe('applicant')
        ->and($reference->email_source_label)->toBe('Given by the applicant');
});

test('the church\'s records win over an address the applicant sends for the same pastor', function () {
    $this->postJson('/api/abc-enrolments', pastorReferenceEnrolment(['pastor_email' => 'me@applicant.test']))->assertCreated();

    expect(AbcEnrolment::sole()->pastorReference->pastor_email)->toBe('wayne@grace.test');
});

test('a malformed pastor email is refused in plain words', function () {
    $this->postJson('/api/abc-enrolments', pastorReferenceEnrolment(['church_name' => 'other', 'church_name_other' => 'X', 'pastor_email' => 'not-an-email']))
        ->assertJsonPath('errors.pastor_email.0', "Enter your pastor's email address in the correct format, like name@example.com");
});

test('the email links to the questionnaire, and only the token\'s hash is stored', function () {
    $token = pastorReferenceToken();

    $mail = (new AbcPastorReferenceRequest($token, 'Mere Tūhoe'))->toMail(new Illuminate\Notifications\AnonymousNotifiable);
    $reference = AbcPastorReference::sole();

    expect($token)->toMatch('/^[0-9a-f]{64}$/')
        ->and($mail->actionUrl)->toEndWith("/apostolic-bible-college/pastor-reference/{$token}")
        ->and($mail->greeting)->toBe('Dear Pastor,')
        ->and(implode(' ', $mail->introLines))->toContain('An applicant for Apostolic Bible College, Mere Tūhoe, has listed you as their pastor.')
        ->not->toContain('1995')
        ->and($reference->getRawOriginal('token_hash'))->toBe(hash('sha256', $token))
        ->and(json_encode(AbcPastorReference::query()->toBase()->get()))->not->toContain($token);
});

test('the questionnaire page learns only who it is about', function () {
    $token = pastorReferenceToken();

    $this->getJson("/api/abc-pastor-references/{$token}")
        ->assertOk()
        ->assertExactJson(['success' => true, 'data' => [
            'applicant_name' => 'Mere Tūhoe',
            'church' => 'Grace Fellowship (Hamilton)',
            'pastor_name' => 'Wayne Example',
            'level' => 'Certificate Level (Year 1)',
            'submitted' => false,
            'submitted_at' => null,
        ]]);
});

test('an unknown or malformed link is a plain 404', function (string $token) {
    pastorReferenceToken();

    $this->getJson("/api/abc-pastor-references/{$token}")->assertNotFound()->assertJsonPath('success', false);
    $this->postJson("/api/abc-pastor-references/{$token}", pastorReferenceAnswers())->assertNotFound();
})->with([
    'unknown' => [str_repeat('a', 64)],
    'malformed' => ['not-a-token'],
]);

test('the pastor\'s answers are stored as booleans, once', function () {
    $token = pastorReferenceToken();

    $this->postJson("/api/abc-pastor-references/{$token}", pastorReferenceAnswers())->assertOk();

    $reference = AbcPastorReference::sole();
    expect($reference->faithful_attendance)->toBeTrue()
        ->and($reference->faithful_tithes)->toBeFalse()
        ->and($reference->serving_details)->toBe('Leads the youth band.')
        ->and($reference->approves_level)->toBeTrue()
        ->and($reference->signed_name)->toBe('Wayne Example')
        ->and($reference->submitted_at)->not->toBeNull()
        ->and($reference->status_label)->toBe('Approved');

    $this->postJson("/api/abc-pastor-references/{$token}", pastorReferenceAnswers(['approves' => 'no']))
        ->assertStatus(409);
    $this->getJson("/api/abc-pastor-references/{$token}")->assertJsonPath('data.submitted', true);

    expect(AbcPastorReference::sole()->approves)->toBeTrue();
});

test('the college is told when a pastor answers, without the written answers', function () {
    config(['abc.enrolment_notify_to' => 'college@example.test']);
    $token = pastorReferenceToken();

    $this->postJson("/api/abc-pastor-references/{$token}", pastorReferenceAnswers(['approves' => 'no']))->assertOk();

    Notification::assertSentOnDemand(
        AbcPastorReferenceReceived::class,
        fn ($notification, $channels, $notifiable) => $notifiable->routes['mail'] === 'college@example.test'
            && $notification->approves === false
            && $notification->enrolmentId === AbcEnrolment::sole()->id,
    );

    $mail = (new AbcPastorReferenceReceived(7, 'Mere Tūhoe', 'Wayne Example', false))->toMail(new Illuminate\Notifications\AnonymousNotifiable);
    expect(implode(' ', $mail->introLines))->toContain('Wayne Example has sent back')->toContain('Approves of them becoming a student: No.')
        ->not->toContain('youth band')
        ->and($mail->actionUrl)->toEndWith('/admin/abc-enrolments/7');
});

test('every question needs an answer, and the follow-ups only when they apply', function () {
    $token = pastorReferenceToken();

    $errors = $this->postJson("/api/abc-pastor-references/{$token}", [])->assertUnprocessable()->json('errors');
    expect(array_keys($errors))->toEqualCanonicalizing([
        'faithful_attendance', 'faithful_holiness', 'faithful_tithes', 'is_serving', 'approves', 'signed_name',
    ])->and($errors['faithful_holiness'][0])->toBe('Select whether the applicant is faithful in holiness and modesty');

    $this->postJson("/api/abc-pastor-references/{$token}", pastorReferenceAnswers(['serving_details' => '', 'approves_level' => null]))
        ->assertJsonValidationErrors(['serving_details', 'approves_level']);

    // Switched to No: the follow-up answers left behind are dropped.
    $this->postJson("/api/abc-pastor-references/{$token}", pastorReferenceAnswers(['is_serving' => 'no', 'approves' => 'no']))->assertOk();

    $reference = AbcPastorReference::sole();
    expect($reference->serving_details)->toBeNull()
        ->and($reference->approves_level)->toBeNull()
        ->and($reference->status_label)->toBe('Not approved');
});

test('a mail outage never fails an enrolment or a pastor\'s answers', function () {
    Notification::shouldReceive('route')->andThrow(new RuntimeException('mail is down'));

    $this->postJson('/api/abc-enrolments', pastorReferenceEnrolment())->assertCreated();

    expect(AbcPastorReference::count())->toBe(1);
});

test('deleting an enrolment deletes its pastor\'s reference', function () {
    pastorReferenceToken();

    AbcEnrolment::sole()->delete();

    expect(AbcPastorReference::count())->toBe(0);
});

test('sending the questionnaire is rate limited', function () {
    $token = pastorReferenceToken();
    $this->app->offsetUnset(ThrottleRequests::class);

    $statuses = collect(range(1, 11))->map(
        fn () => $this->postJson("/api/abc-pastor-references/{$token}", [])->getStatusCode()
    );

    expect($statuses->last())->toBe(429);
});
