<?php

namespace App\Http\Controllers;

use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use App\Models\Ejemplo;

class EjemploController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'imagen' => 'required|image|mimes:jpg,jpeg,png,webp|max:2048', // 2MB
        ]);

        // Ruta física a la carpeta deseada
        $directorio = public_path('storage/uploads/ejemplos/');
        if (!file_exists($directorio)) {
            mkdir($directorio, 0755, true);
        }

        // Guardar imagen
        $file = $request->file('imagen');
        $nombreImagen = Str::uuid() . '.' . $file->getClientOriginalExtension();
        $file->move($directorio, $nombreImagen);

        // Ruta accesible desde el navegador
        $rutaImagen = '/ejemplos/' . $nombreImagen;

        // Guardar en la base de datos
        $ejemplo = Ejemplo::create([
            'imagen' => $rutaImagen,
        ]);

        return response()->json([
            'message' => 'Imagen subida correctamente',
            'data' => $ejemplo,
        ], 201);
    }
    public function index()
    {
        $ejemplos = Ejemplo::latest()->get();

        return response()->json([
            'data' => $ejemplos
        ]);
    }
    public function destroy($id)
    {
        $ejemplo = Ejemplo::find($id);

        if (!$ejemplo) {
            return response()->json(['error' => 'Ejemplo no encontrado'], 404);
        }

        // Ruta física al archivo
        $rutaCompleta = public_path($ejemplo->imagen);

        // Borrar archivo si existe
        if (File::exists($rutaCompleta)) {
            File::delete($rutaCompleta);
        }

        // Borrar de la base de datos
        $ejemplo->delete();

        return response()->json(['message' => 'Ejemplo eliminado correctamente']);
    }
}
