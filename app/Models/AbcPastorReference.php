<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * The pastor's reference for one ABC enrolment: who was asked, how to reach
 * them, and their answers once they send the questionnaire (Form ABC-2, from
 * Brandon Borders' draft, 2026-10-17).
 *
 * The emailed link carries a random token; only its sha256 is stored here,
 * so a copy of the database holds no working link. See the migration.
 */
class AbcPastorReference extends Model
{
    /** The yes/no answers, stored as booleans. */
    public const YES_NO_FIELDS = [
        'faithful_attendance',
        'faithful_holiness',
        'faithful_tithes',
        'is_serving',
        'approves',
        'approves_level',
    ];

    /** Where the pastor's email address came from, as the admin shows it. */
    public const SOURCES = [
        'church_records' => "From the church's records in the CMS",
        'applicant' => 'Given by the applicant',
        'admin' => 'Entered by an administrator',
    ];

    /**
     * The level every new student starts at (the enrolment form says so), so
     * it is the "requested level of study" the pastor is asked about.
     */
    public const LEVEL = 'Certificate Level (Year 1)';

    protected $fillable = [
        'abc_enrolment_id',
        'pastor_name',
        'pastor_email',
        'pastor_email_source',
        'emailed_at',
        'faithful_attendance',
        'faithful_holiness',
        'faithful_tithes',
        'is_serving',
        'serving_details',
        'approves',
        'approves_level',
        'comments',
        'signed_name',
        'submitted_at',
    ];

    protected $hidden = ['token_hash'];

    protected function casts(): array
    {
        return [
            'emailed_at' => 'datetime',
            'submitted_at' => 'datetime',
            ...array_fill_keys(self::YES_NO_FIELDS, 'boolean'),
        ];
    }

    public function enrolment(): BelongsTo
    {
        return $this->belongsTo(AbcEnrolment::class, 'abc_enrolment_id');
    }

    /**
     * A new token for the emailed link. Sets the hash on this model (the
     * caller saves it) and returns the token itself, which goes into the
     * email and nowhere else. A new token makes any earlier link stop working.
     */
    public function issueToken(): string
    {
        $token = bin2hex(random_bytes(32));
        $this->token_hash = hash('sha256', $token);

        return $token;
    }

    /** The reference a link's token belongs to, if any. */
    public static function findByToken(string $token): ?self
    {
        // Anything that is not one of our tokens is turned away before it
        // reaches the database.
        if (! preg_match('/^[0-9a-f]{64}$/', $token)) {
            return null;
        }

        return static::where('token_hash', hash('sha256', $token))->first();
    }

    public function isSubmitted(): bool
    {
        return $this->submitted_at !== null;
    }

    public function getStatusLabelAttribute(): string
    {
        if (! $this->isSubmitted()) {
            return 'Waiting for the pastor';
        }

        return $this->approves ? 'Approved' : 'Not approved';
    }

    public function getEmailSourceLabelAttribute(): ?string
    {
        return self::SOURCES[$this->pastor_email_source] ?? $this->pastor_email_source;
    }
}
