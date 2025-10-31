<?php


namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ContactoRecibido extends Mailable
{
    use Queueable, SerializesModels;

    public $datos;

    public function __construct(array $datos)
    {
        $this->datos = $datos;
    }

    public function build()
    {
        return $this->subject('Nuevo contacto desde el sitio web')
            ->markdown('emails.contacto', ['datos' => $this->datos]);
        // opcional: ->replyTo($this->datos['email'] ?? null, $this->datos['nombre'] ?? null);
    }
}

