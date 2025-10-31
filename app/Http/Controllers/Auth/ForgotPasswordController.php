<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;

class ForgotPasswordController extends Controller
{
    public function sendResetLink(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        // Este método no revela si el usuario existe
        Password::sendResetLink($request->only('email'));

        return response()->json([
            'message' => 'Si tu email está registrado, te llegará una notificación con las instrucciones.',
        ]);
    }
}
