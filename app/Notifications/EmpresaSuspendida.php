<?php

namespace App\Notifications;

use App\Models\Empresa;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class EmpresaSuspendida extends Notification
{
    use Queueable;

    public function __construct(private readonly Empresa $empresa) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Acceso de empresa suspendido en CONNEXT')
            ->greeting('Hola '.$notifiable->name)
            ->line('El acceso operativo de '.$this->empresa->razon_social.' ha sido suspendido.')
            ->line('Los registros se conservan. Contacta al administrador de la plataforma para regularizar el acceso.');
    }
}
