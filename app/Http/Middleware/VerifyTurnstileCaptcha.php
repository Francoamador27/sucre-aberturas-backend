<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class VerifyTurnstileCaptcha
{
    public function handle(Request $request, Closure $next)
    {
        // Solo activar en producción
        if (app()->environment('production')) {
            $token = $request->input('turnstile_token');

            if (!$token) {
                return response()->json(['message' => 'Falta el token de verificación.'], 422);
            }

            $response = Http::asForm()->post('https://challenges.cloudflare.com/turnstile/v0/siteverify', [
                'secret' => env('TURNSTILE_SECRET_KEY'),
                'response' => $token,
                'remoteip' => $request->ip(),
            ]);

            if (!$response->json('success')) {
                return response()->json(['message' => 'Verificación fallida.'], 403);
            }
        }

        return $next($request);
    }
}
