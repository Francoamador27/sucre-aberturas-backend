<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Document extends Model
{
    protected $table = 'document'; // si tu tabla es singular
    protected $fillable = [
        'title',
        'descripcion',
        'nomfi',
        'fere',
        'idpa',
    ];

    /**
     * Relación con el paciente (1 paciente -> muchos documentos)
     */
    public function patient()
    {
        return $this->belongsTo(Patient::class, 'idpa', 'idpa');
        //              ^Modelo relacionado  ^FK en Document ^PK en Patient
    }
}
