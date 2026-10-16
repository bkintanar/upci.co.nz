<?php

use App\Models\AbcEnrolment;

/*
 * Regression: the route-level limits on the public write endpoints shared a
 * counter with the group's 60/min baseline, because an unnamed throttle keys
 * on domain|ip. The reads the SPA makes on every page load (menus, site
 * settings) used up the 5/min contact allowance, so a visitor who had viewed
 * two pages had their message refused with a 429.
 */

function throttleIsolationEnrolment(): array
{
    $answers = array_fill_keys(AbcEnrolment::YES_NO_FIELDS, 'no');

    return [
        ...$answers,
        'resides_in_nz' => 'yes',
        'full_name' => 'A', 'date_of_birth' => '1990-01-01', 'gender' => 'male', 'citizenship' => 'NZ',
        'address' => 'Somewhere', 'home_phone' => '1', 'mobile_phone' => '2', 'email' => 'a@b.test',
        'reason_for_attending' => 'x', 'church_name' => AbcEnrolment::CHURCHES[0], 'pastor_name' => 'P',
        // No church in the test database has a pastor on file, so the
        // pastor's reference needs an address (AbcPastorReferenceTest).
        'pastor_email' => 'pastor@b.test',
        'attendance_duration' => '1 year', 'ministry_involvement' => 'x', 'agreement_name' => 'A',
        'agreement_date' => now()->toDateString(),
    ];
}

function readTheSiteLikeTheSpa(int $times = 10): void
{
    foreach (range(1, $times) as $i) {
        test()->getJson('/api/menu/header')->assertOk();
    }
}

test('browsing does not use up the contact form limit', function () {
    readTheSiteLikeTheSpa();

    $this->postJson('/api/contact', ['first_name' => 'A', 'last_name' => 'B', 'email' => 'a@b.test', 'message' => 'Hello'])
        ->assertCreated();
});

test('browsing does not use up the enrolment limit', function () {
    readTheSiteLikeTheSpa();

    $this->postJson('/api/abc-enrolments', throttleIsolationEnrolment())->assertCreated();

    expect(AbcEnrolment::count())->toBe(1);
});

test('browsing does not use up the address search limit', function () {
    readTheSiteLikeTheSpa(25);

    // 422 (missing query) proves the request got past the limiter.
    $this->postJson('/api/address-search', [])->assertStatus(422);
});

test('each write endpoint still has its own limit', function () {
    $statuses = collect(range(1, 6))->map(fn () => $this->postJson('/api/contact', [
        'first_name' => 'A', 'last_name' => 'B', 'email' => 'a@b.test', 'message' => 'Hello',
    ])->getStatusCode());

    expect($statuses->last())->toBe(429)
        // A separate counter: the enrolment form is unaffected by the contact limit.
        ->and($this->postJson('/api/abc-enrolments', throttleIsolationEnrolment())->getStatusCode())->toBe(201);
});
