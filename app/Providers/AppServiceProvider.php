<?php

namespace App\Providers;

use App\Models\PersonalAccessToken;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\ServiceProvider;
use Laravel\Sanctum\Sanctum;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Sanctum::usePersonalAccessTokenModel(PersonalAccessToken::class);

        VerifyEmail::toMailUsing(function ($notifiable, $url) {
            $frontendUrl = config('app.frontend_url') . '/verify-email?url=' . urlencode($url);

            return (new MailMessage)
                ->subject('Verifica tu email')
                ->line('Haz clic en el boton para verificar tu email.')
                ->action('Verificar Email', $frontendUrl)
                ->line('Si no creaste una cuenta, ignora este mensaje.');
        });
    }
}
