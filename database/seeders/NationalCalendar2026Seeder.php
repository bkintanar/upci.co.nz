<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\Event;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * One-time seed of the 2026 UPCI NZ National Calendar (47 events).
 *
 * Idempotent: every row is keyed on a deterministic slug (name + start date),
 * re-running the seeder is a no-op.
 *
 * Source: "01 NATIONAL CALENDAR OF EVENTS 2026.pdf" (revised 1/30/26).
 *
 * Run with:
 *   php artisan db:seed --class=NationalCalendar2026Seeder
 */
class NationalCalendar2026Seeder extends Seeder
{
    public function run(): void
    {
        $deptIds = Department::pluck('id', 'slug'); // ['missions' => 3, 'mens' => 1, ...]

        $theme = 'Theme: Pentecost EveryDay – A Sustainable Annual Harvest';

        // [name, start_date, end_date|null, location, department_slug|null, description|null]
        $events = [
            // January
            ['Mission Sunday Promotion',                           '2026-01-04', null,         'Nationwide',                                 'missions',  $theme],
            ['National 7 Day Prayer & Fasting',                    '2026-01-26', '2026-01-31', 'Nationwide',                                 'prayer',    null],
            ['ABC – Enrollment Closed',                            '2026-01-31', null,         'Nationwide',                                 null,        'Apostolic Bible College enrolment deadline.'],

            // February
            ['Mission Sunday Promotion',                           '2026-02-01', null,         'Nationwide',                                 'missions',  $theme],
            ['ABC – Teachers Training Seminar',                    '2026-02-06', '2026-02-07', 'Hamilton',                                   null,        'Apostolic Bible College.'],
            ['PM – Central Region, Waikato (Pastors & Teams)',     '2026-02-14', null,         'Storehouse Chapel — 4pm',                    'prayer',    'Pastors, Prayer Coordinator, prayer teams.'],
            ['CM – Promotion',                                     '2026-02-15', null,         'Nationwide',                                 'childrens', null],
            ['Annual Ministers Meeting (AMM) — Ministers Seminar', '2026-02-21', null,         'Tauranga',                                   null,        null],
            ['PM – PAC Regional Prayer & Fasting',                 '2026-02-28', '2026-03-02', 'Nationwide',                                 'prayer',    null],

            // March
            ['Mission Sunday Promotion',                           '2026-03-01', null,         'Nationwide',                                 'missions',  $theme],
            ['ABC – Classes Commence',                             '2026-03-03', null,         'Virtual / Nationwide',                       null,        'Apostolic Bible College.'],
            ['LM – General Director Visitation',                   '2026-03-28', null,         'South Island (AOC / POR)',                   'ladies',    null],

            // April
            ['Mission Program',                                    '2026-04-03', '2026-04-05', 'Rangiora',                                   'missions',  null],
            ['Mission Sunday Promotion',                           '2026-04-05', null,         'Nationwide',                                 'missions',  $theme],
            ["PM – 3 Day Prayer & Fasting (General Men's Conf.)",  '2026-04-07', '2026-04-09', 'Nationwide',                                 'prayer',    "Aligned with the General Men's Conference."],
            ["MM – Apostolic Men's Conference",                    '2026-04-10', '2026-04-12', 'Queenstown',                                 'mens',      null],
            ['LM – General Director Visitation',                   '2026-04-25', null,         'Whangarei',                                  'ladies',    null],
            ['CM – Promotion',                                     '2026-04-26', null,         'Nationwide',                                 'childrens', null],

            // May
            ['Mission Sunday Promotion',                           '2026-05-03', null,         'Nationwide',                                 'missions',  $theme],
            ['LM – General Director Visitation',                   '2026-05-09', null,         'ALC Bay of Plenty / SFC Hawkes Bay',         'ladies',    null],
            ['YM – Nth Island Youth Rally',                        '2026-05-16', null,         'Hamilton',                                   'youth',     null],

            // June
            ['PM – Prayer Breakfast',                              '2026-06-06', null,         'Christchurch',                               'prayer',    null],
            ['Mission Sunday Promotion',                           '2026-06-07', null,         'Nationwide',                                 'missions',  $theme],
            ["Minister's Training Development (MTD)",              '2026-06-13', null,         'Virtual Session',                            null,        null],
            ['AYC',                                                '2026-06-27', '2026-07-06', 'Auckland / Dunedin / Christchurch',          'youth',     'Apostolic Youth Corp.'],

            // July
            ['CM – JBQ Mini-tourney',                              '2026-07-04', null,         'Auckland / Whangarei',                       'childrens', null],
            ['Mission Sunday Promotion',                           '2026-07-05', null,         'Nationwide',                                 'missions',  $theme],
            ['LM – General Director Visitation',                   '2026-07-11', null,         'Waikato (Grace / Storehouse)',               'ladies',    null],
            ['YM – South Island Youth Rally',                      '2026-07-11', null,         'Christchurch',                               'youth',     null],
            ['CM – JBQ Mini-tourney',                              '2026-07-18', null,         'Hamilton / Tauranga',                        'childrens', null],
            ['CM – Promotion',                                     '2026-07-26', null,         'Nationwide',                                 'childrens', null],

            // August
            ['Mission Sunday Promotion',                           '2026-08-02', null,         'Nationwide',                                 'missions',  $theme],
            ['LM – General Director Visitation',                   '2026-08-08', null,         'Auckland (NZ Family / El Shaddai / SSPF / Walk by Faith)', 'ladies', null],
            ['CM – JBQ Mini-tourney',                              '2026-08-15', null,         'Wellington / Hawkes Bay',                    'childrens', null],
            ["MM – Men's Online Virtual Training",                 '2026-08-22', null,         'Nationwide (online)',                        'mens',      null],

            // September
            ['Mission Sunday Promotion',                           '2026-09-06', null,         'Nationwide',                                 'missions',  $theme],
            ['LM – General Director Visitation',                   '2026-09-19', null,         'Wellington (CTW)',                           'ladies',    null],
            ['CM – JBQ Mini-tourney',                              '2026-09-26', null,         'South Island (AOC / POR)',                   'childrens', null],

            // October
            ['PM – 22 Day Prayer & Fasting (General Conference)',  '2026-10-01', '2026-10-22', 'Nationwide',                                 'prayer',    null],
            ['Mission Sunday Promotion',                           '2026-10-04', null,         'Nationwide',                                 'missions',  $theme],
            ['Executive Board Meeting',                            '2026-10-22', null,         'Wellington',                                 null,        'Pre-AGC executive session.'],
            ['Annual General Meeting (AGM) & Ministers Seminar',   '2026-10-23', null,         'Wellington',                                 null,        'Day before the Annual General Conference.'],
            ['Annual General Conference',                          '2026-10-24', '2026-10-25', 'Wellington',                                 null,        null],

            // November
            ['Mission Sunday Promotion',                           '2026-11-01', null,         'Nationwide',                                 'missions',  $theme],
            ["MM – Men's Online Virtual Training",                 '2026-11-14', null,         'Nationwide (online)',                        'mens',      null],
            ['CM – Promotion',                                     '2026-11-22', null,         'Nationwide',                                 'childrens', null],

            // December
            ['Mission Sunday Promotion',                           '2026-12-06', null,         'Nationwide',                                 'missions',  $theme],
        ];

        foreach ($events as $i => [$name, $start, $end, $location, $deptSlug, $description]) {
            $slug = Str::slug($name.' '.$start);

            Event::firstOrCreate(
                ['slug' => $slug],
                [
                    'name'          => $name,
                    'description'   => $description,
                    'start_date'    => $start,
                    'end_date'      => $end,
                    'location'      => $location,
                    'url'           => null,
                    'is_published'  => true,
                    'sort_order'    => 100 + $i,
                    'department_id' => $deptSlug ? ($deptIds[$deptSlug] ?? null) : null,
                ]
            );
        }
    }
}
