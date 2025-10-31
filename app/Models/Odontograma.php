<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Odontograma extends Model
{
    protected $table = 'odontograma';
    protected $primaryKey = 'id';

    // Mapea los timestamps a tus columnas
    public $timestamps = true;
    const CREATED_AT = 'fecha_creacion';
    const UPDATED_AT = 'fecha_modificacion';

    // Columnas que realmente existen
    protected $fillable = ['idpa', 'datos'];

    // Si querés que Eloquent te lo entregue como array
    protected $casts = [
        'datos' => 'array',
    ];
}