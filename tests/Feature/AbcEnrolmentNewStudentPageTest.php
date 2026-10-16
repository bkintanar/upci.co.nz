<?php

use App\Models\AbcEnrolment;

/*
 * The enrolment page is a Vue view, so these read its source. The API accepts
 * only the exact strings in AbcEnrolment::CHURCHES; a church renamed in one
 * place and not the other would make that choice fail validation every time.
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

test('the church is a dropdown that fills in the pastor from the college\'s list', function () {
    // Owner request, 2026-10-17: "the church names should be a drop down, and
    // then it should auto complete the name of the pastor".
    expect(abcEnrolmentViewSource())
        ->toContain('<select id="church_name" v-model="form.church_name"')
        ->toContain('<option value="other">Other (not listed)</option>')
        ->toContain("fetch('/api/abc-enrolments/pastors'")
        ->toContain('role="status" aria-live="polite">{{ pastorStatus }}')
        ->not->toContain('type="radio" name="church_name"');
});

test('the pastor\'s email is asked for only when the college cannot reach the pastor itself', function () {
    expect(abcEnrolmentViewSource())
        ->toContain('<div v-if="needsPastorEmail" class="follow">')
        ->toContain('<input id="pastor_email" v-model="form.pastor_email" type="email"')
        ->toContain("...(needsPastorEmail.value ? ['pastor_email'] : [])");
});

test('the pastor\'s questionnaire has a route before the CMS catch-all, and the SPA serves it', function () {
    $routes = file_get_contents(resource_path('js/router/routes.js'));

    expect(strpos($routes, "'/apostolic-bible-college/pastor-reference/:token'"))
        ->toBeGreaterThan(0)
        ->toBeLessThan(strpos($routes, "path: '/:slug(.*)'"));

    $this->get('/apostolic-bible-college/pastor-reference/'.str_repeat('a', 64))->assertOk();
});

test('both college forms share one stylesheet, scoped to each', function () {
    $reference = file_get_contents(resource_path('js/views/AbcPastorReference.vue'));

    expect(abcEnrolmentViewSource())->toContain('<style scoped src="./abc-form-document.css"></style>')
        ->and($reference)->toContain('<style scoped src="./abc-form-document.css"></style>')
        ->and($reference)->toContain("fetch(url, {\n                    method: 'POST'");
});
