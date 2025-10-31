<?php

namespace App\Services\EstrategiasPago;

use App\Models\Pedido;

interface EstrategiaPagoInterface
{
    public function procesar(Pedido $pedido, $carritos): ?string;
}
