<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    protected $table = 'events';
    protected $primaryKey = 'id';
    public $timestamps = false;

    /** Estados */
    public const STATE_SCHEDULED = 1; // Confirmado / creado
    public const STATE_COMPLETED = 2; // Completado
    public const STATE_CANCELLED = 3; // Cancelado
    public const STATE_NO_SHOW   = 4; // No asistió

    protected $fillable = [
        'title','idpa','idodc','color','start','end','state','monto','chec',
    ];

    /** Valores por defecto (por si la tabla no los tiene como DEFAULT) */
    protected $attributes = [
        'state' => self::STATE_SCHEDULED,
        'color' => '#0ea5e9',
        'monto' => 0,
        'chec'  => 0, // 0 = no pagado, 1 = pagado
    ];

    /** Casts para tipos */
    protected $casts = [
        'start' => 'datetime:Y-m-d H:i:s',
        'end'   => 'datetime:Y-m-d H:i:s',
        'state' => 'integer',
        'monto' => 'decimal:2',
        'chec'  => 'boolean',
    ];

    /** Relaciones */
    public function patient()
    {
        return $this->belongsTo(Patient::class, 'idpa', 'idpa');
    }

    public function doctor()
    {
        return $this->belongsTo(Doctor::class, 'idodc', 'idodc');
    }

    /** Scopes útiles */
    public function scopeScheduled($q)
    {
        return $q->where('state', self::STATE_SCHEDULED);
    }

    public function scopeCompleted($q)
    {
        return $q->where('state', self::STATE_COMPLETED);
    }

    public function scopeCancelled($q)
    {
        return $q->where('state', self::STATE_CANCELLED);
    }

    public function scopeNoShow($q)
    {
        return $q->where('state', self::STATE_NO_SHOW);
    }
}
