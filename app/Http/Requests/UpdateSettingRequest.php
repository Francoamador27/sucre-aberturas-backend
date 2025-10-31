<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Ajustá esto según tu lógica de permisos
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'company_name'      => 'nullable|string|max:200',
            'logo'              => 'nullable|file|mimes:png,jpg,jpeg,webp,svg|max:2048',
            'contact_email'     => 'nullable|email',
            'sender_name'       => 'nullable|string|max:150',
            'whatsapp'          => 'nullable|string|max:50',
            'phone'             => 'nullable|string|max:50',
            'address'           => 'nullable|string|max:255',
            'google_map_iframe' => 'nullable|string',
            'instagram'         => 'nullable|url',
            'facebook'          => 'nullable|url',
            'business_hours'    => 'nullable|string',
        ];
    }
}
