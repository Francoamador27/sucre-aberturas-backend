<?php 
namespace App\Helpers;

use App\Models\CartDiscount;

class CartDiscountHelper
{
    /**
     * Calcula el monto de descuento según la cantidad total de productos.
     */
    public static function calcularDescuentoPorCantidad(float $totalOriginal, int $totalCantidad): float
    {
        $regla = CartDiscount::where('is_active', true)
            ->where('type', 'percentage')
            ->where('condition_type', 'quantity')
            ->where('min_value', '<=', $totalCantidad)
            ->orderByDesc('min_value')
            ->first();

        if (!$regla) return 0;

        $porcentaje = $regla->discount_value / 100;
        $montoDescontado = $totalOriginal * $porcentaje;

        if (!is_null($regla->max_discount)) {
            $montoDescontado = min($montoDescontado, $regla->max_discount);
        }

        return round($montoDescontado, 2);
    }
}
