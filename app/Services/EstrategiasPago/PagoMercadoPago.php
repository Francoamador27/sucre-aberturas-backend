<?php

namespace App\Services\EstrategiasPago;

use App\Models\Pedido;
use App\Services\MercadoPagoService;

class PagoMercadoPago implements EstrategiaPagoInterface
{
    protected float $costoEnvio;

    public function __construct(float $costoEnvio)
    {
        $this->costoEnvio = $costoEnvio;
    }

    public function procesar(Pedido $pedido, $carritos): ?string
    {
        $mp = new MercadoPagoService();
        return $mp->crearPreferencia($pedido, $carritos, $this->costoEnvio);
    }
}

