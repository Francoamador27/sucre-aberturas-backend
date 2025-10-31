<?php

// app/Mail/ContactoCliente.php
namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ContactoCliente extends Mailable
{
    use Queueable, SerializesModels;

    public array $datos;

    public function __construct(array $datos)
    {
        $this->datos = $datos;
    }

    public function build()
    {
        return $this->subject('Recibimos tu consulta')
            ->markdown('emails.contacto_cliente')  // 👈 clave
            ->with(['datos' => $this->datos]);
    }
}
