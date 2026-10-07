<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

/**
 * Tells the college a new first-time enrolment has arrived.
 *
 * Carries scalars rather than the model, so the queued job neither fails if
 * the enrolment is deleted before it runs nor serialises the applicant's
 * details into the jobs table. The email deliberately holds only the name
 * and a link: date of birth, address and phone stay behind the admin login.
 */
class NewAbcEnrolment extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public int $enrolmentId,
        public string $fullName,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("New ABC enrolment: {$this->fullName}")
            ->line("{$this->fullName} has submitted a first-time student enrolment for the Apostolic Bible College.")
            ->action('View in admin', url("/admin/abc-enrolments/{$this->enrolmentId}"));
    }
}
