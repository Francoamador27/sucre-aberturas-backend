<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
// use Illuminate\Database\Eloquent\SoftDeletes; // ← descomenta si tu tabla tiene deleted_at

class Patient extends Model
{
    // use SoftDeletes; // ← solo si existe la columna deleted_at

    protected $table = 'patients';
    protected $primaryKey = 'idpa';
    public $timestamps = false;

    // Si tu PK es autoincremental entero:
    public $incrementing = true;
    protected $keyType = 'int';

    protected $fillable = [
        'user_id',
        'nompa',
        'apepa',
        'direc',
        'sex',
        'grup',
        'phon',
        'cump',
        'state',
        // 'fere', // inclúyelo si lo seteás desde la app; si lo llena la DB, podés omitirlo
    ];

    protected $casts = [
        'cump'  => 'date:Y-m-d',   // te devuelve YYYY-MM-DD automáticamente
        'state' => 'integer',
        // 'fere'  => 'datetime:Y-m-d H:i:s', // si existe y querés castear
    ];

    /* ----------------------- Relaciones ----------------------- */

    public function user()
    {
        // FK = user_id, PK en users = id (ajusta si usás otra)
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function documents()
    {
        return $this->hasMany(Document::class, 'idpa', 'idpa');
    }
    // Búsqueda rápida por varios campos + user
    public function scopeSearch($query, ?string $term)
    {
        if (! $term) return $query;

        $t = trim($term);
        return $query->where(function ($w) use ($t) {
            $w->where('nompa', 'like', "%{$t}%")
              ->orWhere('apepa', 'like', "%{$t}%")
              ->orWhere('phon', 'like', "%{$t}%")
              ->orWhereHas('user', function ($wu) use ($t) {
                  $wu->where('email', 'like', "%{$t}%")
                     ->orWhere('dni', 'like', "%{$t}%")
                     ->orWhere('name', 'like', "%{$t}%");
              });
        });
    }

    public function scopeActive($query)
    {
        return $query->where('state', 1);
    }

    /* ----------------------- Accessors opcionales ----------------------- */

    // $patient->full_name
    public function getFullNameAttribute(): string
    {
        return trim(($this->nompa ?? '') . ' ' . ($this->apepa ?? ''));
    }
        public function patologias()
    {
        return $this->hasMany(Patologia::class, 'paciente_id', 'idpa');
    }
}
