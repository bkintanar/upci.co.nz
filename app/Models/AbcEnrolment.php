<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

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
