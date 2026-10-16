<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

/**
 * Tells the college a pastor has sent back their reference.
 *
 * Scalars only, like NewAbcEnrolment, and the same rule: the email says who
 * and whether the pastor approves, and links to the admin. The pastor's
 * written answers stay behind the admin login.
 */
class AbcPastorReferenceReceived extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public int $enrolmentId,
        public string $applicantName,
        public string $pastorName,
        public bool $approves,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Pastor's reference received: {$this->applicantName}")
            ->line("{$this->pastorName} has sent back the pastor's reference for {$this->applicantName}.")
            ->line('Approves of them becoming a student: '.($this->approves ? 'Yes' : 'No').'.')
            ->action('View in admin', url("/admin/abc-enrolments/{$this->enrolmentId}"));
    }
}
