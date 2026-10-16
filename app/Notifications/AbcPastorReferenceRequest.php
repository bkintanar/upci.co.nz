<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

/**
 * Asks an applicant's pastor for a reference: a link to the questionnaire
 * (Form ABC-2). The wording is Brandon Borders' draft (2026-10-17).
 *
 * Carries scalars rather than the model, like NewAbcEnrolment. The token
 * rides in the queued job because the email needs it; the database keeps
 * only its hash. Only the applicant's name goes to the pastor; their other
 * details stay behind the admin login.
 */
class AbcPastorReferenceRequest extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $token,
        public string $applicantName,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Pastor's reference for {$this->applicantName}: Apostolic Bible College")
            ->greeting('Dear Pastor,')
            ->line("An applicant for Apostolic Bible College, {$this->applicantName}, has listed you as their pastor.")
            ->line('Please take the time to fill out this short questionnaire and let us know if you approve for the applicant to be a student at ABC.')
            ->action('Fill out the questionnaire', url("/apostolic-bible-college/pastor-reference/{$this->token}"))
            ->line("It takes about two minutes. The link is just for you, so please don't forward it.")
            ->salutation("God bless,\nApostolic Bible College, UPCI New Zealand");
    }
}
