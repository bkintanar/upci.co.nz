<?php

use App\Models\AbcEnrolment;

/*
 * The enrolment page is a Vue view, so these read its source. The API accepts
 * only the exact strings in AbcEnrolment::CHURCHES; a church renamed in one
 * place and not the other would make that radio fail validation every time.
 */

function abcEnrolmentViewSource(): string
{
    return file_get_contents(resource_path('js/views/AbcNewStudentEnrolment.vue'));
}

test('the page offers exactly the churches the API accepts, in the same order', function () {
    preg_match('/const CHURCHES = \[(.*?)\]/s', abcEnrolmentViewSource(), $match);
    preg_match_all("/'((?:[^'\\\\]|\\\\.)*)'/", $match[1] ?? '', $names);

    expect($names[1])->toBe(AbcEnrolment::CHURCHES);
});

test('the page offers exactly the learning difficulties the API accepts', function () {
    preg_match('/const learningDifficulties = \{(.*?)\}/s', abcEnrolmentViewSource(), $match);
    preg_match_all('/(\w+): \'([^\']+)\'/', $match[1] ?? '', $pairs);

    expect(array_combine($pairs[1], $pairs[2]))->toBe(AbcEnrolment::LEARNING_DIFFICULTIES);
});

test('the page posts to the enrolment endpoint without forcing a content type', function () {
    $source = abcEnrolmentViewSource();

    // A hand-set Content-Type would drop the multipart boundary and break the upload.
    expect($source)->toContain("fetch('/api/abc-enrolments'")
        ->toContain('new FormData()')
        ->not->toContain("'Content-Type'");
});

test('the route to the page sits before the CMS catch-all', function () {
    $routes = file_get_contents(resource_path('js/router/routes.js'));

    expect(strpos($routes, "'/apostolic-bible-college/enrollment/new-student'"))
        ->toBeLessThan(strpos($routes, "path: '/:slug(.*)'"));
});

test('the SPA serves the page URL', function () {
    $this->get('/apostolic-bible-college/enrollment/new-student')->assertOk();
});

test('the page carries the fee schedule from the Google Form, not a placeholder', function () {
    expect(abcEnrolmentViewSource())
        ->toContain('Total Fees for Full Time Students (per year)</th><td>$350</td>')
        ->toContain('Total Fees for Part Time Students (per year)</th><td>$250</td>')
        ->not->toContain('to be supplied by the college');
});
