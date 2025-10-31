<?php

namespace App\Services;

use App\Helpers\CartDiscountHelper;
use App\Helpers\CuponHelper;
use MercadoPago\MercadoPagoConfig;
use MercadoPago\Client\Preference\PreferenceClient;
use MercadoPago\Exceptions\MPApiException;
use Illuminate\Support\Facades\Log;

class MercadoPagoService
{
    protected $client;

    public function __construct()
    {
        MercadoPagoConfig::setAccessToken(config('services.mercadopago.access_token'));
        // Opcional para pruebas en local (desactivado si estás en servidor)
        // MercadoPagoConfig::setRuntimeEnviroment(MercadoPagoConfig::LOCAL);

        $this->client = new PreferenceClient();
    }

    public function crearPreferencia($pedido, $carritos,$costoEnvio = 0): ?string
    {
        $items = [];

        // Paso 1: Calcular cantidad total y monto total original
        $totalCantidad = 0;
        $totalOriginal = 0;

        foreach ($carritos as $carrito) {
            $producto = \App\Models\Producto::find($carrito->producto_id);
            $cantidad = (int) $carrito->cantidad;
            $subtotal = $producto->precio * $cantidad;

            $totalCantidad += $cantidad;
            $totalOriginal += $subtotal;
        }

        // Paso 2: Buscar la mejor regla de descuento activa por cantidad
        $descuentoCantidad = CartDiscountHelper::calcularDescuentoPorCantidad($totalOriginal, $totalCantidad);

        // Inicializar variables por defecto
        $cupon = null;
        $descuentoCupon = 0;
        $totalConPrimerDescuento = $totalOriginal - $descuentoCantidad;
        $codigoCupon = $pedido->codigo_cupon ?? null;
        if ($codigoCupon) {
            [$cupon, $descuentoCupon] = CuponHelper::validarCuponAplicable($codigoCupon, $totalConPrimerDescuento);
        }
        $totalFinal = $totalOriginal - $descuentoCantidad - $descuentoCupon;

        foreach ($carritos as $carrito) {
            $producto = \App\Models\Producto::find($carrito->producto_id);
            $cantidad = (int) $carrito->cantidad;
            $subtotalItem = $producto->precio * $cantidad;

            // Proporción del item respecto al total original
            $proporcion = $subtotalItem / $totalOriginal;

            // Precio final ajustado por la proporción del total con ambos descuentos
            $precioFinalItem = round(($proporcion * $totalFinal) / $cantidad, 2);

            $items[] = [
                "id" => $producto->id,
                "title" => $producto->nombre,
                "description" => $producto->descripcion ?? '',
                "currency_id" => "ARS",
                "quantity" => $cantidad,
                "unit_price" => $precioFinalItem,
            ];
        }

        Log::info('Método de envío del pedido', [
            'pedido_id' => $pedido->id,
            'metodo_envio' => $pedido->metodo_envio,
            'costo_envio' => $pedido->costo_envio,
        ]);
        // Agregar ítem de envío si corresponde
        if ($costoEnvio > 0) {
            $items[] = [
                "title" => "Envío por E-pick",
                "description" => "Costo de envío a domicilio",
                "currency_id" => "ARS",
                "quantity" => 1,
                "unit_price" => (float) $costoEnvio,
            ];
        }

        $usuario = $pedido->usuario;

        $payer = [
            "name" => $usuario->name,
            "email" => $usuario->email,
        ];

        $request = [
            "items" => $items,
            "payer" => $payer,
            "payment_methods" => [
                "excluded_payment_methods" => [],
                "installments" => 12,
                "default_installments" => 1
            ],
            "back_urls" => [
                "success" => config('app.frontend_url') . "/pagos/success?pedido_id={$pedido->id}",
                "failure" => config('app.frontend_url') . "/pagos/failure?pedido_id={$pedido->id}",
                "pending" => config('app.frontend_url') . "/pagos/pending?pedido_id={$pedido->id}"
            ],
            "external_reference" => $pedido->id,
            "notification_url" => "https://api.proyectoswebsite.com/api/mercadopago/webhook",
            "statement_descriptor" => "TU_NEGOCIO",
            "auto_return" => "approved",
            "expires" => false
        ];

        try {
            $preference = $this->client->create($request);
            return $preference->init_point;
        } catch (MPApiException $e) {
            Log::error('MercadoPago MPApiException: ' . json_encode($e->getApiResponse()->getContent()));
            return null;
        } catch (\Exception $e) {
            Log::error('MercadoPago Exception: ' . $e->getMessage());
            return null;
        }
    }



}
