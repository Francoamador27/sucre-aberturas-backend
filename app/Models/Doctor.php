<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Doctor extends Model
{
    use HasFactory;

    protected $table = 'doctor';
    protected $primaryKey = 'idodc';
    public $incrementing = true;
    public $timestamps = false;

    // Carga siempre el usuario del doctor
    protected $with = ['user'];

    // Si querés que estos campos calculados aparezcan en JSON:
    protected $appends = ['name', 'email', 'phone', 'specialty'];

    protected $fillable = [
        'ceddoc','nodoc','apdoc','nomesp','direcd','sexd','phd','nacd','user_id',
        'corr','username',/* 'password', */'rol','state','fere','color'
    ];

    protected $hidden = ['password'];

    protected $casts = [
        'nacd'  => 'date',
        'fere'  => 'datetime',
        'rol'   => 'integer',
        'state' => 'integer',
    ];

    /* Relaciones */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    // ✔ Si en la tabla events la FK también es 'idodc'
    public function events()
    {
        return $this->hasMany(Event::class, 'idodc', 'idodc');
    }

    /* Accessors (combinan doctor + user) */
    public function getNameAttribute(): ?string
    {
        return $this->user->name
            ?? trim(($this->nodoc ?? '').' '.($this->apdoc ?? ''))
            ?: null;
    }

    public function getEmailAttribute(): ?string
    {
        return $this->user->email ?? $this->corr ?? null;
    }

    public function getPhoneAttribute(): ?string
    {
        return $this->user->telefono ?? $this->phd ?? null;
    }

    public function getSpecialtyAttribute(): ?string
    {
        return $this->nomesp ?? null;
    }
}
