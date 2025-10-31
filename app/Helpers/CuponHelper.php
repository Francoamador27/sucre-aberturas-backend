<?php

namespace App\Helpers;

use App\Models\Coupon;

class CuponHelper
{
    public static function validarCuponAplicable(?string $codigo, float $total): array
    {
        if (!$codigo) return [null, 0];

        $cupon = Coupon::where('code', $codigo)
            ->where('is_active', true)
            ->where(function ($q) use ($total) {
                $q->whereNull('min_purchase')
                    ->orWhere('min_purchase', '<=', $total);
            })
            ->where(function ($q) {
                $q->whereNull('end_date')
                    ->orWhere('end_date', '>=', now());
            })
            ->where(function ($q) {
                $q->whereNull('usage_limit')
                    ->orWhereColumn('usage_limit', '>', 'usage_count');
            })
            ->first();

        if (!$cupon) return [null, 0];

        $descuento = 0;

        if ($cupon->type === 'percentage') {
            $descuento = $total * ($cupon->discount_value / 100);
        } elseif ($cupon->type === 'fixed') {
            $descuento = $cupon->discount_value;
        }

        if ($cupon->max_discount && $descuento > $cupon->max_discount) {
            $descuento = $cupon->max_discount;
        }

        return [$cupon, round($descuento, 2)];
    }
}
