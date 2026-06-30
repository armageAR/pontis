<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\URL;

class VerifyEmailChangeNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private User $user)
    {
    }

    public function via($notifiable): array
    {
        return ['mail'];
    }

    protected function confirmationUrl(): string
    {
        $apiUrl = URL::temporarySignedRoute(
            'emailchange.confirm',
            Carbon::now()->addMinutes(Config::get('auth.verification.expire', 60)),
            [
                'id'   => $this->user->getKey(),
                'hash' => sha1($this->user->pending_email),
            ]
        );

        $frontendUrl = rtrim(config('app.frontend_url'), '/');

        return $frontendUrl . '/confirmar-email?confirm_url=' . urlencode($apiUrl);
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Confirmá tu nuevo email — Pontis')
            ->greeting('¡Hola ' . $this->user->name . '!')
            ->line('Recibimos una solicitud para cambiar el email de tu cuenta de Pontis a esta dirección.')
            ->line('Para aplicar el cambio, confirmá que esta casilla te pertenece.')
            ->action('Confirmar nuevo email', $this->confirmationUrl())
            ->line('Este enlace expira en 60 minutos.')
            ->line('Si no solicitaste este cambio, ignorá este mensaje: tu email actual seguirá vigente.')
            ->salutation('— Equipo Pontis');
    }
}
