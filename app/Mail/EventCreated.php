<?php

namespace App\Mail;

use App\Models\Event;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
// Si querés cola: implements ShouldQueue
use Illuminate\Contracts\Queue\ShouldQueue;

class EventCreated extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public Event $event;

    public function __construct(Event $event)
    {
        // Cargamos relaciones para usar en el email
        $this->event = $event->load('patient', 'doctor');
    }

    public function build()
    {
        return $this->subject('Confirmación de turno')
            ->markdown('emails.events.created');
    }
}
