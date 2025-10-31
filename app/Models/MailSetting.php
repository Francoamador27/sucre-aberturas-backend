<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class MailSetting extends Model
{
    protected $fillable = [
        'host', 'port', 'username', 'password', 'encryption',
        'from_email', 'from_name', 'admin_email',
    ];

    // Nunca exponer password en JSON
    protected $hidden = ['password'];

    // Accessor/Mutator para encriptar/desencriptar si quisieras leerla (no la enviaremos)
    public function setPasswordAttribute($value): void
    {
        if ($value === null || $value === '') {
            // no modificar
            return;
        }
        $this->attributes['password'] = Crypt::encryptString($value);
    }

    public function getPasswordDecryptedAttribute(): ?string
    {
        if (!$this->password) return null;
        try {
            return Crypt::decryptString($this->password);
        } catch (\Throwable $e) {
            return null;
        }
    }
}
