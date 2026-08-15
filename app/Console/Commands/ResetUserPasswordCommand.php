<?php

namespace App\Console\Commands;

use Exception;
use Filament\Facades\Filament;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Password;
use Filament\Models\Contracts\FilamentUser;
use Illuminate\Contracts\Auth\CanResetPassword;
use Filament\Auth\Notifications\ResetPassword as FilamentResetPasswordNotification;

class ResetUserPasswordCommand extends Command
{
    protected $signature = 'users:reset-password {email}';

    protected $description = 'Send a Filament-aware password-reset email to the given user';

    public function handle(): int
    {
        $email = $this->argument('email');

        $status = Password::broker(Filament::getAuthPasswordBroker())->sendResetLink(
            ['email' => $email],
            function (CanResetPassword $user, string $token): void {
                if (
                    ($user instanceof FilamentUser) &&
                    (! $user->canAccessPanel(Filament::getCurrentOrDefaultPanel()))
                ) {
                    throw new Exception("User [{$user->getEmailForPasswordReset()}] cannot access the admin panel; refusing to send reset link.");
                }

                $notification = app(FilamentResetPasswordNotification::class, ['token' => $token]);
                $notification->url = Filament::getResetPasswordUrl($token, $user);

                $user->notify($notification);
            },
        );

        if ($status === Password::RESET_LINK_SENT) {
            $this->info("Reset link sent to {$email}.");

            return self::SUCCESS;
        }

        $this->error(__($status));

        return self::FAILURE;
    }
}
