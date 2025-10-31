<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Carrito extends Model
{
    use HasFactory;

    protected $fillable = [
        'pedido_id',
        'producto_id',
        'cantidad',
        'imagenes',
    ];

    protected $casts = [
        'imagenes' => 'array', // para que Laravel lo devuelva como array
    ];

    public function pedido()
    {
        return $this->belongsTo(Pedido::class);
    }
    public function producto()
    {
        return $this->belongsTo(Producto::class);
    }
}
