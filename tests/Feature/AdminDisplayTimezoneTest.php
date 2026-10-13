<?php

use App\Models\User;
use Livewire\Livewire;
use App\Enums\UserRole;
use App\Enums\AccessLevel;
use App\Models\AbcEnrolment;
use App\Models\ContactMessage;
use Filament\Facades\Filament;
use Filament\Forms\Components\TimePicker;
use App\Filament\Resources\Shared\TextLines;
use Filament\Forms\Components\DateTimePicker;
use Filament\Support\Facades\FilamentTimezone;
use App\Filament\Resources\AbcEnrolments\Pages\ViewAbcEnrolment;
use App\Filament\Resources\AbcEnrolments\Pages\ListAbcEnrolments;
use App\Filament\Resources\ContactMessages\Pages\ViewContactMessage;
use App\Filament\Resources\ContactMessages\Pages\ListContactMessages;

/*
 * The admin shows date-times in New Zealand time (config app.display_timezone)
 * while everything stays stored in UTC. Found in the 2026-10-14 end-to-end
 * check: an enrolment sent at 12:38pm on 14 October showed as
 * "13 Oct 2026, 11:38pm". The same check found applicants' line breaks
 * collapsing in the admin; they now show one line per row (TextLines).
 */
beforeEach(function () {
    Filament::setCurrentPanel('admin');

    $this->actingAs(User::create([
        'name' => 'T',
        'email' => 'national-display-tz@x',
        'password' => 'x',
        'role' => UserRole::ADMINISTRATOR,
        'access_level' => AccessLevel::NATIONAL,
    ]));
});

function displayTzEnrolment(array $overrides = []): AbcEnrolment
{
    $enrolment = AbcEnrolment::create(array_merge([
        'full_name' => 'Test Applicant',
        'date_of_birth' => '1999-04-12',
        'gender' => 'female',
        'citizenship' => 'New Zealand',
        'address' => "1 Test Street\nWellington 6011",
        'home_phone' => '04 000 0000',
        'mobile_phone' => '021 000 0000',
        'email' => 'applicant@example.test',
        'resides_in_nz' => true,
        'reason_for_attending' => 'To serve.',
        'attended_upci_bible_school' => false,
        'has_learning_difficulties' => false,
        'wants_ministerial_licence' => true,
        'read_articles_of_faith' => true,
        'baptised_in_jesus_name' => true,
        'received_holy_ghost' => true,
        'church_name' => 'Grace Fellowship (Hamilton)',
        'pastor_name' => 'Pastor Example',
        'attendance_duration' => '3 years',
        'ministry_involvement' => "Youth.\nMusic.",
        'agreement_name' => 'Test Applicant',
        'agreement_date' => '2026-10-14',
    ], $overrides));

    // 23:38 UTC on 13 October is 12:38pm on 14 October in New Zealand
    // (NZDT, UTC+13, from the last Sunday in September).
    $enrolment->forceFill(['created_at' => '2026-10-13 23:38:00'])->save();

    return $enrolment;
}

test('the enrolment inbox shows when an enrolment arrived in New Zealand time', function () {
    displayTzEnrolment();

    Livewire::test(ListAbcEnrolments::class)
        ->assertSee('14 Oct 2026, 12:38pm')
        ->assertDontSee('13 Oct 2026, 11:38pm');
});

test('an enrolment record shows its received time in New Zealand time', function () {
    $enrolment = displayTzEnrolment();

    Livewire::test(ViewAbcEnrolment::class, ['record' => $enrolment->getRouteKey()])
        ->assertSee('14 Oct 2026, 12:38pm')
        ->assertDontSee('13 Oct 2026, 11:38pm');
});

test('date-only answers are never shifted by the display timezone', function () {
    $enrolment = displayTzEnrolment();

    Livewire::test(ViewAbcEnrolment::class, ['record' => $enrolment->getRouteKey()])
        ->assertSee('12 Apr 1999')
        ->assertSee('14 Oct 2026')
        ->assertDontSee('13 Apr 1999');
});

test('it is stored in UTC all the same', function () {
    $enrolment = displayTzEnrolment();

    expect($enrolment->fresh()->getRawOriginal('created_at'))->toBe('2026-10-13 23:38:00')
        ->and(config('app.timezone'))->toBe('UTC');
});

test('form pickers are not converted, so typed times keep their meaning', function () {
    // FilamentTimezone::set() would have fixed the display too, but it also
    // makes these pickers convert what editors type, shifting existing
    // announcement times and church service times by 13 hours.
    expect(FilamentTimezone::get())->toBe('UTC')
        ->and(DateTimePicker::make('published_at')->getTimezone())->toBe('UTC')
        ->and(TimePicker::make('time')->getTimezone())->toBe('UTC');
});

/** The text of each line row (li) in the rendered admin page. */
function displayTzLineRows(string $html): array
{
    $dom = new DOMDocument;
    @$dom->loadHTML('<?xml encoding="utf-8"?>'.$html);

    $rows = [];
    foreach ((new DOMXPath($dom))->query('//ul[contains(@class, "fi-in-text-has-line-breaks")]/li') as $li) {
        $rows[] = trim($li->textContent, " \n\t");
    }

    return $rows;
}

test('an enrolment shows each line of an answer on its own row, still escaped', function () {
    // Browsers send textarea line breaks as \r\n.
    $enrolment = displayTzEnrolment(['address' => "1 Test Street\r\n<b>Wellington</b> 6011"]);

    $component = Livewire::test(ViewAbcEnrolment::class, ['record' => $enrolment->getRouteKey()])
        ->assertDontSeeHtml('<b>Wellington</b>')
        ->assertSeeHtml('&lt;b&gt;Wellington&lt;/b&gt; 6011');

    expect(displayTzLineRows($component->html()))
        ->toContain('1 Test Street', '<b>Wellington</b> 6011', 'Youth.', 'Music.');
});

test('the contact inbox shows New Zealand time and keeps a message\'s paragraphs', function () {
    $message = ContactMessage::create([
        'first_name' => 'Ana',
        'last_name' => 'Test',
        'email' => 'ana@example.test',
        'message' => "Kia ora,\r\n\r\nCould someone call me?",
    ]);
    $message->forceFill(['created_at' => '2026-10-13 23:38:00'])->save();

    Livewire::test(ListContactMessages::class)->assertSee('14 Oct 2026, 12:38pm');

    $component = Livewire::test(ViewContactMessage::class, ['record' => $message->getRouteKey()])
        ->assertSee('14 Oct 2026, 12:38pm');

    // The blank line survives as its own row (a non-breaking space), so the
    // paragraph break stays visible.
    expect(displayTzLineRows($component->html()))->toBe(['Kia ora,', "\u{00A0}", 'Could someone call me?']);
});

test('text is split on any line ending, with blank lines kept as spacers', function () {
    expect(TextLines::split("a\r\nb\nc\rd"))->toBe(['a', 'b', 'c', 'd'])
        ->and(TextLines::split("one\n\n\ntwo"))->toBe(['one', "\u{00A0}", "\u{00A0}", 'two'])
        ->and(TextLines::split("\n  padded  \n"))->toBe(['padded'])
        ->and(TextLines::split(null))->toBe([])
        ->and(TextLines::split(''))->toBe([]);
});
