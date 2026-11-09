<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configurePasswordResetMail();
    }

    /**
     * Email atur ulang kata sandi dalam Bahasa Indonesia.
     */
    private function configurePasswordResetMail(): void
    {
        ResetPassword::toMailUsing(function (User $notifiable, string $token): MailMessage {
            $url = route('password.reset', [
                'token' => $token,
                'email' => $notifiable->getEmailForPasswordReset(),
            ]);

            return (new MailMessage)
                ->subject('Atur ulang kata sandi Kasku')
                ->markdown('mail.reset-password', [
                    'name' => $notifiable->name,
                    'url' => $url,
                    'expireMinutes' => config('auth.passwords.'.config('auth.defaults.passwords').'.expire'),
                ]);
        });
    }
}
