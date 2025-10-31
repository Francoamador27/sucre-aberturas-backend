<?php

namespace App\Mail;

use App\Models\Pedido;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PedidoEstadoCancelado extends Mailable
{
    use Queueable, SerializesModels;

    public $pedido;

    public function __construct(Pedido $pedido)
    {
        $this->pedido = $pedido;
    }

    public function envelope()
    {
        return new Envelope(
            subject: 'Confirmación: tu pedido ha sido cancelado',
        );
    }

    public function content()
    {
        return new Content(
            markdown: 'emails.pedido_cancelado',
            with: [
                'pedido' => $this->pedido,
            ],
        );
    }

    public function attachments()
    {
        return [];
    }
}
