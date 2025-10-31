<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Carrito;

class Pedido extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'estado',
        'total',
        'estado_pago',
        'payment_id',
        'payment_type',
        'payment_method',
        'metodo_envio',
        'costo_envio',
        'paid_at',
        'cantidad',
        'codigo_cupon',
        'monto_descuento_cupon',
        'descuento_aplicado',
        'total_sin_descuento',
    ];

    // Relación: un pedido pertenece a un usuario
    public function usuario()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // Relación: un pedido tiene muchos carritos
    public function carritos()
    {
        return $this->hasMany(Carrito::class);
    }
}
