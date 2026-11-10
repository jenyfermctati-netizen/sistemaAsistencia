<?php

namespace App\Notifications;

use App\Models\Empresa;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class InvitacionAdministradorEmpresa extends Notification
{
    use Queueable;

    public function __construct(
        private readonly Empresa $empresa,
        private readonly string $token
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $url = route('password.reset', [
            'token' => $this->token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ]);

        return (new MailMessage)
            ->subject('Bienvenido a CONNEXT: activa tu cuenta administrativa')
            ->greeting('Hola '.$notifiable->name)
            ->line('Has sido invitado como administrador de '.$this->empresa->razon_social.'.')
            ->line('Define una contraseña segura para activar tu acceso. El enlace es personal, vence y solo puede utilizarse una vez.')
            ->action('Definir mi contraseña', $url)
            ->line('Si no esperabas esta invitación, ignora este mensaje.');
    }
}
