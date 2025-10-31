<?php

namespace App\Mail;

use App\Models\Event;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class EventCancelled extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public Event $event;

    public function __construct(Event $event)
    {
        $this->event = $event->load('patient', 'doctor');
    }

    public function build()
    {
        return $this->subject('Cancelación de turno')
            ->markdown('emails.events.cancelled');
    }
}
