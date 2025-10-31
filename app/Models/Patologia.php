<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Patologia extends Model
{
    use HasFactory;

    // opcional si querés forzar el nombre de la tabla (Laravel ya lo infiere)
    protected $table = 'patologias';

    protected $fillable = [
        'paciente_id',
        'alergico',
        'medicamentos',
        'recomendaciones',
    ];

    // Relación con Paciente (ajustá el modelo si lo llamás distinto)
    public function paciente()
    {
        return $this->belongsTo(Patient::class, 'paciente_id', 'idpa');
    }
}
