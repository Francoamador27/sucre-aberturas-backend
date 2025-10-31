<?php

namespace App\Services\EstrategiasPago;

use App\Models\Pedido;

class PagoInterno implements EstrategiaPagoInterface
{
    public function procesar(Pedido $pedido, $carritos): ?string
    {
        // Omitimos MercadoPago. Podemos marcarlo como pagado si querés.
        $pedido->update(['estado' => 'pagado']);
        return '/admin-dash';
    }
}