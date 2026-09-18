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
            // Enlazar directamente a la ruta de verificacion del backend: esta
            // marca el email como verificado y redirige a
            // /verify-email?status=verified (ver VerificationController::verify).
            return (new MailMessage)
                ->subject('Verifica tu email')
                ->line('Haz clic en el boton para verificar tu email.')
                ->action('Verificar Email', $url)
                ->line('Si no creaste una cuenta, ignora este mensaje.');
        });
    }
}
