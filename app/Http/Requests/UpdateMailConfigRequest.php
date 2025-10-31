<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateMailConfigRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Ajustá el gate/middleware a gusto (ej: Sanctum)
        return true;
    }

    public function rules(): array
    {
        return [
            'host'         => ['required','string','max:255'],
            'port'         => ['required','integer','min:1','max:65535'],
            'username'     => ['required','string','max:255'],
            'password'     => ['nullable','string','max:255'], // si viene vacío => no cambia
            'encryption'   => ['required','in:none,ssl,tls'],

            'from_email'   => ['nullable','email','max:255'],
            'from_name'    => ['nullable','string','max:255'],
            'admin_email'  => ['nullable','email','max:255'],
        ];
    }
}
