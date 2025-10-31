<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Pedido;

class PagoController extends Controller
{
    public function success(Request $request)
    {
        $pedido = Pedido::find($request->query('pedido_id'));
        if ($pedido) {
            $pedido->estado = 'pagado';
            $pedido->save();
        }

        return redirect('/gracias'); // Redirigí a tu página de agradecimiento
    }

    public function failure(Request $request)
    {
        return redirect('/pago-fallido');
    }

    public function pending(Request $request)
    {
        return redirect('/pago-pendiente');
    }
}
