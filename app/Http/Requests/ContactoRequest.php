<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ContactoRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'nombre' => 'required|string|max:100',
            'email' => 'required|email',
            'telefono' => 'required|string|max:20',
            'mensaje' => 'required|string|max:1000',
            'turnstile_token' => 'sometimes|nullable|string', // <-- opcional
        ];
    }
}

