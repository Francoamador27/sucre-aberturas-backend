<?php

namespace App\Http\Controllers;

use App\Mail\PedidoRecibido;
use App\Models\Pedido;
use Illuminate\Support\Facades\Mail;
use App\Mail\PedidoEstadoRecibido;
use App\Mail\PedidoEstadoImpreso;
use App\Mail\PedidoEstadoEnviado;
use App\Mail\PedidoEstadoCompletado;
use App\Mail\PedidoEstadoCancelado;

class EmailController extends Controller
{
    public function enviarPedidoRecibido($id)
    {
        // Cargar pedido con relaciones necesarias
        $pedido = Pedido::with(['usuario', 'carritos'])->findOrFail($id);

        // Enviar el correo
        Mail::to($pedido->usuario->email)->send(new PedidoRecibido($pedido));

        return response()->json(['message' => 'Correo de confirmación enviado correctamente.']);
    }
    public function probarEnvio()
    {
        // Test para confirmar que entra al controlador

        // Esta parte no se ejecuta porque está después del return
        Mail::raw('Este es un correo de prueba enviado desde Laravel con SMTP de Hostinger.', function ($message) {
            $message->to('francohugoamador25@gmail.com')
                ->subject('Correo de prueba Laravel + Hostinger');
        });

        return response()->json(['message' => 'Correo de prueba enviado.']);
    }

    public function enviarCorreoPorEstado(Pedido $pedido)
    {
        $usuario = $pedido->usuario;

        switch ($pedido->estado) {
            case 'recibido':
                Mail::to($usuario->email)->send(new PedidoEstadoRecibido($pedido));
                break;
            case 'impreso':
                Mail::to($usuario->email)->send(new PedidoEstadoImpreso($pedido));
                break;
            case 'enviado':
                Mail::to($usuario->email)->send(new PedidoEstadoEnviado($pedido));
                break;
            case 'completado':
                Mail::to($usuario->email)->send(new PedidoEstadoCompletado($pedido));
                break;
            case 'cancelado':
                Mail::to($usuario->email)->send(new PedidoEstadoCancelado($pedido));
                break;
        }
    }


}
