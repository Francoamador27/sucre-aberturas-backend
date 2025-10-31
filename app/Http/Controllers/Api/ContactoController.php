<?php

namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller; // <-- esta línea es clave
use App\Http\Requests\ContactoRequest;
use App\Mail\ContactoCliente;
use App\Mail\ContactoRecibido;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class ContactoController extends Controller
{
public function enviar(ContactoRequest $request)
{
    // obtenés solo lo que quieras, excluyendo turnstile_token
    $data = $request->safe()->except(['turnstile_token']);

    $adminTo = config('mail.to_admin');

    try {
        Mail::to($adminTo)->send(new ContactoRecibido($data));

        if (!empty($data['email'])) {
            Mail::to($data['email'])->send(new ContactoCliente($data));
        }

        return response()->json([
            'success' => true,
            'message' => 'Mensaje enviado correctamente. ¡Te contactamos en breve!',
        ]);
    } catch (\Throwable $e) {
        Log::error('Error enviando correo de contacto', ['error' => $e->getMessage()]);
        return response()->json([
            'message' => 'Tu mensaje fue recibido, pero hubo un problema enviando el correo.',
        ], 202);
    }
}
}
