<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegistroRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function register(RegistroRequest $request)
    {
        // Validar registro
        $data = $request->validated();
        // crear el usuario
        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => bcrypt($data['password']),
        ]);
        return [
            'token' => $user->createToken('token')->plainTextToken,
            'user' => $user
        ];
    }
    public function login(LoginRequest $request)
    {
        $data = $request->validated();

        if (!auth()->attempt($data)) {
            return response()->json([
                'errors' => 'Credenciales invalidas'
            ], 422);
        }

        $user = Auth::user();

        return response()->json([
            'token' => $user->createToken('token')->plainTextToken,
            'user' => $user
        ]);
    }

    public function logout(Request $request)
    {

        $user = $request->user();
        $user->currentAccessToken()->delete();
        return [
            'user' => null,
            'message' => 'Sesión cerrada con éxito'
        ];
    }
}
