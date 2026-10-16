<?php

namespace App\Http\Controllers\Api;

use Illuminate\Support\Str;
use App\Models\AbcEnrolment;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Http\JsonResponse;
use App\Models\AbcPastorReference;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Notifications\NewAbcEnrolment;
use Illuminate\Support\Facades\Notification;
use App\Notifications\AbcPastorReferenceRequest;

/**
 * First-time student enrolment for the Apostolic Bible College, replacing
 * the Google Form at forms.gle/iqVTh9YQH3nhZdw48.
 *
 * Yes/No answers arrive as the strings "yes"/"no" (the form posts
 * multipart for the transcript, so there are no JSON booleans) and are
 * stored as booleans. Conditional answers use exclude_unless, so an answer
 * left behind after its parent question was switched back is dropped
 * rather than stored or validated.
 *
 * Each enrolment also asks the applicant's pastor for a reference (Form
 * ABC-2, owner request 2026-10-17): see AbcPastorReferenceController.
 */
class AbcEnrolmentController extends Controller
{
    private const REQUIRED_MESSAGES = [
        'full_name.required' => 'Enter your full name',
        'date_of_birth.required' => 'Enter your date of birth',
        'gender.required' => 'Select your gender',
        'citizenship.required' => 'Enter your citizenship',
        'address.required' => 'Enter your address',
        'home_phone.required' => 'Enter your home phone number',
        'mobile_phone.required' => 'Enter your mobile phone number',
        'email.required' => 'Enter your email address',
        'resides_in_nz.required' => 'Select whether you are currently residing in New Zealand',
        'residence_country.required' => 'Enter the country you are residing in',
        'reason_for_attending.required' => 'Tell us why you want to attend the Apostolic Bible College',
        'attended_upci_bible_school.required' => 'Select whether you have attended a UPCI Bible school before',
        'has_learning_difficulties.required' => 'Select whether you have any learning difficulties',
        'learning_difficulty.required' => 'Select which learning difficulty you have',
        'learning_difficulty_other.required' => 'Describe your learning difficulty',
        'wants_ministerial_licence.required' => 'Select whether you desire to become a licensed minister',
        'read_articles_of_faith.required' => 'Select whether you have read and understood the Articles of Faith',
        'baptised_in_jesus_name.required' => 'Select whether you have been baptised in the name of Jesus Christ',
        'received_holy_ghost.required' => 'Select whether you have received the Holy Ghost',
        'church_name.required' => 'Select the church you currently attend',
        'church_name_other.required' => 'Enter the name of your church',
        'pastor_name.required' => "Enter your church pastor's name",
        'pastor_email.required' => "Enter your pastor's email address, so the college can ask them for a reference",
        'attendance_duration.required' => 'Enter how long you have attended',
        'ministry_involvement.required' => 'Describe any area of ministry you are involved in',
        'agreement_name.required' => 'Print your full name to agree',
        'agreement_date.required' => "Select today's date",
    ];

    /**
     * For the form's church dropdown: each listed church's pastor, so
     * choosing the church fills in the pastor's name. Names and an "on file"
     * flag only; pastors' email addresses are never published.
     */
    public function pastors(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => AbcEnrolment::pastorDirectory(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        // Honeypot: a real visitor never sees or fills this field. Answer
        // exactly like a success so a bot learns nothing, but store and send
        // nothing.
        if (filled($request->input('website'))) {
            return response()->json(['success' => true, 'id' => null], 201);
        }

        $yesNo = ['required', 'in:yes,no'];

        $validated = $request->validate([
            // Section 1. Personal information
            'full_name' => ['required', 'string', 'max:255'],
            'date_of_birth' => ['required', 'date_format:Y-m-d', 'before:today', 'after:1900-01-01'],
            'gender' => ['required', 'in:male,female'],
            'citizenship' => ['required', 'string', 'max:255'],
            'address' => ['required', 'string', 'max:1000'],
            'home_phone' => ['required', 'string', 'max:50'],
            'mobile_phone' => ['required', 'string', 'max:50'],
            'email' => ['required', 'email', 'max:255'],
            'resides_in_nz' => $yesNo,
            'residence_country' => ['exclude_unless:resides_in_nz,no', 'required', 'string', 'max:255'],
            'reason_for_attending' => ['required', 'string', 'max:5000'],

            // Section 2. Previous Bible school attendance
            'attended_upci_bible_school' => $yesNo,
            'transcript' => ['exclude_unless:attended_upci_bible_school,yes', 'nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],

            // Section 3. Learning challenges
            'has_learning_difficulties' => $yesNo,
            'learning_difficulty' => ['exclude_unless:has_learning_difficulties,yes', 'required', Rule::in(array_keys(AbcEnrolment::LEARNING_DIFFICULTIES))],
            'learning_difficulty_other' => ['exclude_unless:learning_difficulty,other', 'required', 'string', 'max:255'],

            // Section 4. Spiritual information
            'wants_ministerial_licence' => $yesNo,
            'read_articles_of_faith' => $yesNo,
            'baptised_in_jesus_name' => $yesNo,
            'received_holy_ghost' => $yesNo,
            'church_name' => ['required', 'string', Rule::in([...AbcEnrolment::CHURCHES, 'other'])],
            'church_name_other' => ['exclude_unless:church_name,other', 'required', 'string', 'max:255'],
            'pastor_name' => ['required', 'string', 'max:255'],
            // Asked only when the college cannot reach the pastor named above
            // from its own records: Other, a church with no pastor email on
            // file, or a pastor other than the one on file.
            'pastor_email' => [
                Rule::requiredIf(fn () => AbcEnrolment::pastorEmailOnFile(
                    $request->string('church_name')->toString(),
                    $request->string('pastor_name')->toString(),
                ) === null),
                'nullable', 'string', 'email', 'max:255',
            ],
            'attendance_duration' => ['required', 'string', 'max:255'],
            'ministry_involvement' => ['required', 'string', 'max:5000'],

            // Agreement. The app runs in UTC and applicants are in New
            // Zealand, so "today" can differ by a day either way.
            'agreement_name' => ['required', 'string', 'max:255'],
            'agreement_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:yesterday', 'before_or_equal:tomorrow'],
        ], [
            // Messages say what to do, in the applicant's words (GOV.UK
            // style); Laravel's ":attribute field is required" template reads
            // badly for questions like "whether you live in New Zealand".
            ...self::REQUIRED_MESSAGES,
            ...collect(AbcEnrolment::YES_NO_FIELDS)
                ->mapWithKeys(fn ($field) => ["{$field}.in" => self::REQUIRED_MESSAGES["{$field}.required"]])
                ->all(),
            'gender.in' => 'Select your gender',
            'email.email' => 'Enter an email address in the correct format, like name@example.com',
            'pastor_email.email' => "Enter your pastor's email address in the correct format, like name@example.com",
            'date_of_birth.date_format' => 'Enter your date of birth as a real date',
            'date_of_birth.before' => 'Your date of birth must be in the past',
            'date_of_birth.after' => 'Your date of birth must be a real date',
            'learning_difficulty.in' => 'Select which learning difficulty you have',
            'church_name.in' => 'Select a church from the list, or choose Other',
            'agreement_date.date_format' => "Select today's date",
            'agreement_date.after_or_equal' => "Select today's date",
            'agreement_date.before_or_equal' => "Select today's date",
            'transcript.file' => 'The transcript must be a file',
            'transcript.mimes' => 'The transcript must be a PDF, JPG or PNG',
            'transcript.max' => 'The transcript must be 10 MB or smaller',
        ], [
            // Used only by the remaining defaults (mostly max lengths).
            'full_name' => 'full name',
            'home_phone' => 'home phone',
            'mobile_phone' => 'mobile phone',
            'residence_country' => 'country of residence',
            'reason_for_attending' => 'reason for attending',
            'learning_difficulty_other' => 'learning difficulty description',
            'church_name_other' => 'church name',
            'pastor_name' => 'pastor',
            'pastor_email' => "pastor's email address",
            'attendance_duration' => 'how long you have attended',
            'ministry_involvement' => 'ministry involvement',
            'agreement_name' => 'printed full name',
        ]);

        // pastor_email belongs to the pastor's reference, not the enrolment.
        $data = collect($validated)->except(['transcript', 'pastor_email'])->all();

        foreach (AbcEnrolment::YES_NO_FIELDS as $field) {
            $data[$field] = $validated[$field] === 'yes';
        }

        // Only when the transcript survived validation: it is excluded
        // unless the applicant said they attended before.
        if (isset($validated['transcript'])) {
            $file = $validated['transcript'];

            // The private disk on purpose (storage/app/private): a transcript
            // must never be reachable at a public URL.
            $data['transcript_path'] = $file->store('abc-enrolments/transcripts', 'local');
            $data['transcript_original_name'] = Str::limit($file->getClientOriginalName(), 250, '');
        }

        // The college's own record of the pastor wins whenever the applicant
        // named that pastor; otherwise the address the applicant gave, which
        // the admin labels as theirs.
        $onFile = AbcEnrolment::pastorEmailOnFile($validated['church_name'], $validated['pastor_name']);

        [$record, $token] = DB::transaction(function () use ($data, $validated, $onFile): array {
            $record = AbcEnrolment::create($data);

            $reference = new AbcPastorReference([
                'pastor_name' => $validated['pastor_name'],
                'pastor_email' => $onFile ?? $validated['pastor_email'],
                'pastor_email_source' => $onFile ? 'church_records' : 'applicant',
                'emailed_at' => now(),
            ]);
            $token = $reference->issueToken();
            $record->pastorReference()->save($reference);

            return [$record, $token];
        });

        // The pastor's questionnaire. As with the college's email below, a
        // mail or queue outage must never turn a saved enrolment into a
        // failed one; the admin can send it again from the enrolment.
        rescue(
            fn () => Notification::route('mail', $record->pastorReference->pastor_email)
                ->notify(new AbcPastorReferenceRequest($token, $record->full_name)),
            report: true,
        );

        if ($to = config('abc.enrolment_notify_to')) {
            // A mail or queue outage must never turn a saved enrolment into
            // a failed submission.
            rescue(
                fn () => Notification::route('mail', $to)
                    ->notify(new NewAbcEnrolment($record->id, $record->full_name)),
                report: true,
            );
        }

        return response()->json([
            'success' => true,
            'id' => $record->id,
        ], 201);
    }
}
