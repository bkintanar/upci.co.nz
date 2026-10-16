<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\AbcPastorReference;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Notification;
use App\Notifications\AbcPastorReferenceReceived;

/**
 * The pastor's reference questionnaire (Form ABC-2), reached from the link
 * emailed to an applicant's pastor (owner request, 2026-10-17, from Brandon
 * Borders' draft).
 *
 * There is no login: the link's token is the only key. It is matched by its
 * sha256, a reference is answered once, and the page learns only what it
 * needs to show (the applicant's name and church), never the enrolment.
 *
 * Yes/No answers arrive as the strings "yes"/"no", like the enrolment form's,
 * and are stored as booleans. Follow-ups use exclude_unless, so an answer
 * left behind after its question was switched to No is dropped.
 */
class AbcPastorReferenceController extends Controller
{
    private const NOT_FOUND = 'This link is not valid. It may have been replaced by a newer email from the college.';

    public function show(string $token): JsonResponse
    {
        $reference = AbcPastorReference::findByToken($token);

        if (! $reference) {
            return response()->json(['success' => false, 'message' => self::NOT_FOUND], 404);
        }

        $enrolment = $reference->enrolment;

        return response()->json([
            'success' => true,
            'data' => [
                'applicant_name' => $enrolment->full_name,
                'church' => $enrolment->church_name === 'other' ? $enrolment->church_name_other : $enrolment->church_name,
                'pastor_name' => $reference->pastor_name,
                'level' => AbcPastorReference::LEVEL,
                'submitted' => $reference->isSubmitted(),
                'submitted_at' => $reference->submitted_at?->toIso8601String(),
            ],
        ]);
    }

    public function store(Request $request, string $token): JsonResponse
    {
        $reference = AbcPastorReference::findByToken($token);

        if (! $reference) {
            return response()->json(['success' => false, 'message' => self::NOT_FOUND], 404);
        }

        if ($reference->isSubmitted()) {
            return $this->alreadySubmitted();
        }

        $yesNo = ['required', 'in:yes,no'];

        $validated = $request->validate([
            'faithful_attendance' => $yesNo,
            'faithful_holiness' => $yesNo,
            'faithful_tithes' => $yesNo,
            'is_serving' => $yesNo,
            'serving_details' => ['exclude_unless:is_serving,yes', 'required', 'string', 'max:2000'],
            'approves' => $yesNo,
            'approves_level' => ['exclude_unless:approves,yes', 'required', 'in:yes,no'],
            'comments' => ['nullable', 'string', 'max:5000'],
            'signed_name' => ['required', 'string', 'max:255'],
        ], [
            // In the pastor's words, like the enrolment form's messages.
            'faithful_attendance.*' => 'Select whether the applicant is faithful in attendance to services',
            'faithful_holiness.*' => 'Select whether the applicant is faithful in holiness and modesty',
            'faithful_tithes.*' => 'Select whether the applicant is faithful in tithes and offerings',
            'is_serving.*' => 'Select whether the applicant is serving in some area of your church',
            'serving_details.required' => 'Tell us how the applicant is involved',
            'approves.*' => 'Select whether you approve of the applicant becoming a student',
            'approves_level.*' => 'Select whether you are happy for them to be enrolled at '.AbcPastorReference::LEVEL,
            'signed_name.required' => 'Enter your name to sign the reference',
        ], [
            'serving_details' => 'how they are involved',
            'comments' => 'anything else',
            'signed_name' => 'your name',
        ]);

        $answers = $validated;
        foreach (AbcPastorReference::YES_NO_FIELDS as $field) {
            if (array_key_exists($field, $answers)) {
                $answers[$field] = $answers[$field] === 'yes';
            }
        }

        // Answered once. The update only matches while the reference is
        // still open, so two sends racing each other store one set of
        // answers and the other is told it has already been received.
        $stored = AbcPastorReference::whereKey($reference->getKey())
            ->whereNull('submitted_at')
            ->update([...$answers, 'submitted_at' => now(), 'updated_at' => now()]);

        if ($stored === 0) {
            return $this->alreadySubmitted();
        }

        if ($to = config('abc.enrolment_notify_to')) {
            // A mail or queue outage must never lose the pastor's answers;
            // they are already saved and shown in the admin.
            rescue(
                fn () => Notification::route('mail', $to)->notify(new AbcPastorReferenceReceived(
                    $reference->abc_enrolment_id,
                    $reference->enrolment->full_name,
                    $validated['signed_name'],
                    $answers['approves'],
                )),
                report: true,
            );
        }

        return response()->json(['success' => true]);
    }

    private function alreadySubmitted(): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'This reference has already been sent to the college. Thank you.',
        ], 409);
    }
}
