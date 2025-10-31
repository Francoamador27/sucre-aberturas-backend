<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CategoriaGasto extends Model
{
    use HasFactory;

    protected $table = 'categorias_gastos';

    protected $fillable = [
        'nombre_categoria',
        'detalle_categoria'
    ];

    // Relación con gastos
    public function gastos()
    {
        return $this->hasMany(Gasto::class, 'idcat', 'id');
    }
}