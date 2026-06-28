<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\URL;

class VerifyEmailNotification extends VerifyEmail implements ShouldQueue
{
    use Queueable;

    protected function verificationUrl($notifiable): string
    {
        $apiUrl = URL::temporarySignedRoute(
            'verification.verify',
            Carbon::now()->addMinutes(Config::get('auth.verification.expire', 60)),
            [
                'id' => $notifiable->getKey(),
                'hash' => sha1($notifiable->getEmailForVerification()),
            ]
        );

        $frontendUrl = rtrim(config('app.frontend_url'), '/');

        return $frontendUrl . '/verify-email?verify_url=' . urlencode($apiUrl);
    }

    public function toMail($notifiable): MailMessage
    {
        $url = $this->verificationUrl($notifiable);

        return (new MailMessage)
            ->subject('Verificá tu email — Pontis')
            ->greeting('¡Hola ' . $notifiable->name . '!')
            ->line('Gracias por registrarte en Pontis. Para continuar con el proceso de aprobación de tu cuenta, necesitamos verificar tu dirección de email.')
            ->action('Verificar email', $url)
            ->line('Este enlace expira en 60 minutos.')
            ->line('Si no creaste una cuenta en Pontis, ignorá este mensaje.')
            ->salutation('— Equipo Pontis');
    }
}
