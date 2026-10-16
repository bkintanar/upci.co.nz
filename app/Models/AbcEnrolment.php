<?php

namespace App\Models;

use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * A first-time student's enrolment in the Apostolic Bible College, submitted
 * through /apostolic-bible-college/enrollment/new-student. Replaces the
 * Google Form "Apostolic Bible School Enrolment Form for New Students".
 */
class AbcEnrolment extends Model
{
    /**
     * The church list from the Google Form, in its order, rather than the
     * churches table: the college's list includes fellowships the locator
     * does not (El Shaddai, Walk by Faith, House of Mercy). The form's
     * "Storehouse Chapel (Hamilton" typo is fixed here.
     */
    public const CHURCHES = [
        'Apostolic Life Church (Tauranga)',
        'Apostolics of Christchurch (Christchurch)',
        'Church Triumphant Wellington (Wellington)',
        'Daystar Fellowship (Whangarei)',
        'El Shaddai- Hindi Speaking Fellowship (Auckland)',
        'Grace Fellowship (Hamilton)',
        'New Zealand Family in Christ (Auckland)',
        'Pentecostals of Rangiora (Rangiora)',
        'Solid Foundation Church (Hastings)',
        'Southside Pentecostal Fellowship (Auckland)',
        'Storehouse Chapel (Hamilton)',
        'Walk by Faith (Auckland)',
        'House of Mercy (Tokoroa)',
    ];

    public const LEARNING_DIFFICULTIES = [
        'hearing' => 'Hearing',
        'vision' => 'Vision',
        'writing' => 'Writing',
        'english' => 'Using English in learning',
        'other' => 'Other',
    ];

    /** The Google Form's Yes/No questions, stored as booleans. */
    public const YES_NO_FIELDS = [
        'resides_in_nz',
        'attended_upci_bible_school',
        'has_learning_difficulties',
        'wants_ministerial_licence',
        'read_articles_of_faith',
        'baptised_in_jesus_name',
        'received_holy_ghost',
    ];

    protected $fillable = [
        'full_name',
        'date_of_birth',
        'gender',
        'citizenship',
        'address',
        'home_phone',
        'mobile_phone',
        'email',
        'resides_in_nz',
        'residence_country',
        'reason_for_attending',
        'attended_upci_bible_school',
        'transcript_path',
        'transcript_original_name',
        'has_learning_difficulties',
        'learning_difficulty',
        'learning_difficulty_other',
        'wants_ministerial_licence',
        'read_articles_of_faith',
        'baptised_in_jesus_name',
        'received_holy_ghost',
        'church_name',
        'church_name_other',
        'pastor_name',
        'attendance_duration',
        'ministry_involvement',
        'agreement_name',
        'agreement_date',
    ];

    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
            'agreement_date' => 'date',
            ...array_fill_keys(self::YES_NO_FIELDS, 'boolean'),
        ];
    }

    protected static function booted(): void
    {
        // A transcript is personal data that belongs to this submission and
        // nothing else; when an admin deletes the submission, the file goes
        // with it rather than lingering on the private disk.
        static::deleted(function (AbcEnrolment $enrolment) {
            if ($enrolment->transcript_path) {
                Storage::disk('local')->delete($enrolment->transcript_path);
            }
        });
    }

    /** The pastor's reference requested for this enrolment (Form ABC-2). */
    public function pastorReference(): HasOne
    {
        return $this->hasOne(AbcPastorReference::class);
    }

    /**
     * The pastor the college has on file for a church on the list: their
     * name, and their email when the CMS has one. Null for Other, for a
     * listed church the CMS does not have (El Shaddai, Solid Foundation and
     * House of Mercy, as of 2026-10-17), and for a church with no pastor.
     *
     * @return array{name: string, email: ?string}|null
     */
    public static function pastorOnFile(string $church): ?array
    {
        return self::findPastor($church, self::churchRecords());
    }

    /**
     * The pastor's email on file for this church, but only when the name the
     * applicant gave is that pastor's. Anyone else they name has to be
     * reached at an address they give, and the admin shows it as theirs.
     */
    public static function pastorEmailOnFile(?string $church, ?string $pastorName): ?string
    {
        $onFile = $church ? self::pastorOnFile($church) : null;

        return $onFile && filled($onFile['email']) && self::samePastor($onFile['name'], $pastorName)
            ? $onFile['email']
            : null;
    }

    /**
     * For the enrolment form: each listed church that has a pastor on file,
     * with the pastor's name and whether the college can email them. Never
     * the email address itself: this is public.
     *
     * @return array<string, array{pastor_name: string, pastor_on_file: bool}>
     */
    public static function pastorDirectory(): array
    {
        $records = self::churchRecords();

        return collect(self::CHURCHES)
            ->mapWithKeys(fn (string $church) => [$church => self::findPastor($church, $records)])
            ->filter()
            ->map(fn (array $pastor) => ['pastor_name' => $pastor['name'], 'pastor_on_file' => filled($pastor['email'])])
            ->all();
    }

    /**
     * Whether two names are the same pastor, as people write them: "Pastor
     * Chester Kebasen", "Ps. chester kebasen" and "Chester Kebasen" all are.
     */
    public static function samePastor(?string $a, ?string $b): bool
    {
        $key = fn (?string $name): string => preg_replace(
            '/^(?:(?:pastor|ps|rev|reverend)\s+)+/',
            '',
            trim(preg_replace('/\s+/', ' ', preg_replace('/[^\pL\pN\s]/u', '', mb_strtolower((string) $name)))),
        );

        return $key($a) !== '' && $key($a) === $key($b);
    }

    /** @return Collection<int, Church> */
    private static function churchRecords(): Collection
    {
        return Church::active()->orderBy('id')->get();
    }

    /**
     * The list's names ("Grace Fellowship (Hamilton)") are not the CMS's
     * ("Grace Fellowship - A UPCINZ Church"), and no column links the two,
     * so a listed church is the first active CMS church whose name starts
     * with the listed name, ignoring case, spacing, a leading "The" and the
     * town in brackets. A church renamed beyond that simply loses its
     * auto-filled pastor, and the applicant is asked for the email instead.
     *
     * The pastor is the church's own pastor_name, with the email of the user
     * at that church who has the same name; otherwise its most senior
     * pastor-role user.
     *
     * @param  Collection<int, Church>  $records
     * @return array{name: string, email: ?string}|null
     */
    private static function findPastor(string $church, Collection $records): ?array
    {
        if (! in_array($church, self::CHURCHES, true)) {
            return null;
        }

        $key = fn (string $name): string => preg_replace('/^the /', '', trim(preg_replace('/\s+/', ' ', mb_strtolower($name))));
        $listed = $key(preg_replace('/\s*\([^)]*\)$/', '', $church));
        $record = $records->first(fn (Church $record) => str_starts_with($key($record->name), $listed));

        if (! $record) {
            return null;
        }

        if (filled($record->pastor_name)) {
            $name = trim($record->pastor_name);
            $user = $record->users()->get()->first(fn (User $user) => self::samePastor($user->name, $name));

            return ['name' => $name, 'email' => $user?->email];
        }

        $pastor = $record->pastors()->get()->sortByDesc(fn (User $user) => $user->role->getLevel())->first();

        return $pastor ? ['name' => trim($pastor->name), 'email' => $pastor->email] : null;
    }

    /** The church as the applicant gave it: a listed name, or their own text. */
    public function getChurchDisplayAttribute(): string
    {
        return $this->church_name === 'other'
            ? 'Other: '.$this->church_name_other
            : $this->church_name;
    }

    public function getLearningDifficultyDisplayAttribute(): ?string
    {
        if (! $this->learning_difficulty) {
            return null;
        }

        return $this->learning_difficulty === 'other'
            ? 'Other: '.$this->learning_difficulty_other
            : (self::LEARNING_DIFFICULTIES[$this->learning_difficulty] ?? $this->learning_difficulty);
    }
}
