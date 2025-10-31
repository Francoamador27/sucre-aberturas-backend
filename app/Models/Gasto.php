<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Gasto extends Model
{
    use HasFactory;

    protected $table = 'gastos';

    protected $fillable = [
        'idcat',
        'fecha',
        'descripcion',
        'importe'
    ];

    protected $casts = [
        'fecha' => 'date',
        'importe' => 'decimal:2'
    ];

    // Relación con la tabla categorias_gastos
    public function categoria()
    {
        return $this->belongsTo(CategoriaGasto::class, 'idcat', 'id');
    }
}