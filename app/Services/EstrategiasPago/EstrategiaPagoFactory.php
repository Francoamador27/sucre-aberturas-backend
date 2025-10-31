<?php

namespace App\Services\EstrategiasPago;

class EstrategiaPagoFactory
{
    public static function obtenerEstrategia(string $rol, float $costoEnvio): EstrategiaPagoInterface
    {
        return match ($rol) {
            'admin' => new PagoInterno(),
            default => new PagoMercadoPago($costoEnvio),
        };
    }
}
