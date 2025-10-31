<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
class UserController extends Controller
{
    public function index(Request $request)
    {
        $query = User::query();

        // 🔍 Búsqueda inteligente (nombre, email, dni)
        if ($request->filled('busqueda')) {
            $busqueda = $request->busqueda;
            $query->where(function ($q) use ($busqueda) {
                $q->where('name', 'like', "%$busqueda%")
                    ->orWhere('email', 'like', "%$busqueda%")
                    ->orWhere('dni', 'like', "%$busqueda%");
            });
        }

        // ↕ Ordenamiento por fecha de creación
        $direccion = $request->get('direccion', 'desc');
        if (!in_array($direccion, ['asc', 'desc'])) {
            $direccion = 'desc';
        }

        $query->orderBy('created_at', $direccion);

        // 📄 Paginación (por defecto 10)
        $perPage = $request->get('per_page', 10);

        return response()->json($query->paginate($perPage));
    }
    public function cambiarPassword(Request $request)
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'password_actual' => ['required'],
            'password_nueva' => ['required', 'min:8'],
            'confirmar_password' => ['required', 'same:password_nueva'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Errores de validación',
                'errors' => $validator->errors(),
            ], 422);
        }

        if (!Hash::check($request->password_actual, $user->password)) {
            return response()->json([
                'message' => 'La contraseña actual no es correcta',
            ], 401);
        }

        $user->password = Hash::make($request->password_nueva);
        $user->save();

        return response()->json([
            'message' => 'Contraseña actualizada correctamente',
        ]);
    }
    public function actualizarPerfil(Request $request)
    {
        $user = Auth::user();

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => "required|email|unique:users,email,{$user->id}",
            'telefono' => 'nullable|string|max:20',
            'dni' => 'nullable|string|max:20',
            'codigo_postal' => 'nullable|string|max:20',
            'direccion' => 'nullable|string|max:255',
            'localidad' => 'nullable|string|max:255',
            'provincia' => 'nullable|string|max:255',
        ]);

        $user->update($data);

        return response()->json([
            'message' => 'Datos actualizados correctamente.',
            'user' => $user,
        ]);
    }
    public function show(User $user)
    {
        return response()->json([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'telefono' => $user->telefono,
            'dni' => $user->dni,
            'codigo_postal' => $user->codigo_postal,
            'direccion' => $user->direccion,
            'localidad' => $user->localidad,
            'provincia' => $user->provincia,
            'rol' => $user->rol,
            'admin' => $user->admin,
            'fecha_creacion' => $user->created_at->format('Y-m-d H:i'),
            'ultima_actualizacion' => $user->updated_at->format('Y-m-d H:i'),
        ]);
    }

    public function update(Request $request, User $user)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'telefono' => 'nullable|string|max:20',
            'dni' => 'nullable|string|max:20',
            'codigo_postal' => 'nullable|string|max:10',
            'direccion' => 'nullable|string|max:255',
            'localidad' => 'nullable|string|max:255',
            'provincia' => 'nullable|string|max:255',
            'rol' => 'required|string',
            'admin' => 'nullable', // validamos manualmente
        ]);

        // ⚠️ fuerza que admin sea booleano
        $data['admin'] = filter_var($request->input('admin'), FILTER_VALIDATE_BOOLEAN);

        $user->update($data);

        return response()->json([
            'message' => 'Usuario actualizado correctamente',
            'user' => $user,
            'request' => $request
        ]);
    }



    public function destroy(User $user)
    {
        $user->delete();

        return response()->json(['message' => 'Usuario eliminado']);
    }
}
