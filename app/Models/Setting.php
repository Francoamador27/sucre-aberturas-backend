<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $fillable = [
        'company_name',
        'logo',
        'contact_email',
        'sender_name',
        'whatsapp',
        'phone',
        'address',
        'google_map_iframe',
        'instagram',
        'facebook',
        'business_hours',
    ];

    /**
     * Devuelve siempre la fila única de configuración.
     */
    public static function singleton(): self
    {
        return static::firstOrCreate(['id' => 1]);
    }
    public function getLogoUrlAttribute(): ?string
    {
        if (!$this->logo)
            return null;

        // Si ya es absoluta, devolver tal cual
        if (preg_match('~^https?://~i', $this->logo)) {
            return $this->logo;
        }

        // Si guarda rutas bajo /storage/... esto lo hace absoluto
        return url($this->logo); // o Storage::url($this->logo) si guardás en disk "public"
    }
}
