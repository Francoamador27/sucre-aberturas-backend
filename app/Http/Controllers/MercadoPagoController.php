<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Pedido;
use MercadoPago\MercadoPagoConfig;
use MercadoPago\Client\Payment\PaymentClient;
use MercadoPago\Exceptions\MPApiException;

class MercadoPagoController extends Controller
{
    public function webhook(Request $request)
    {
        // Configurar el access token
        MercadoPagoConfig::setAccessToken(config('services.mercadopago.access_token'));

        // Log básico para revisar la entrada
        file_put_contents(
            storage_path('logs/mercadopago-webhook.log'),
            now() . ' - ' . json_encode($request->all()) . "\n",
            FILE_APPEND
        );

        if ($request->input('type') === 'payment') {
            $paymentId = $request->input('data.id');

            try {
                $client = new PaymentClient();
                $payment = $client->get($paymentId); // ← Nueva forma correcta

                if ($payment && $payment->external_reference) {
                    $pedidoId = $payment->external_reference;
                    $estado = $payment->status;

                    $pedido = Pedido::find($pedidoId);
                    if ($pedido) {
                        $pedido->estado_pago = $estado;
                        $pedido->payment_id = $payment->id;
                        $pedido->payment_type = $payment->payment_type_id;
                        $pedido->payment_method = $payment->payment_method_id;
                        $pedido->paid_at = $estado === 'approved' ? now() : null;
                        $pedido->save();
                    }
                }
            } catch (MPApiException $e) {
                file_put_contents(
                    storage_path('logs/mercadopago-error.log'),
                    now() . ' - ' . $e->getMessage() . "\n",
                    FILE_APPEND
                );
            }
        }

        return response()->json(['status' => 'ok'], 200);
    }
}
