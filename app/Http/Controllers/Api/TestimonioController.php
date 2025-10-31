<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Testimonio;
use Illuminate\Support\Str;

class TestimonioController extends Controller
{
    public function store(Request $request)
    {
        // Validar los datos
        $request->validate([
            'nombre' => 'required|string|max:255',
            'texto' => 'required|string',
            'imagen' => 'required|image|mimes:jpg,jpeg,png,webp|max:10240',
        ]);

        // Preparar el directorio
        $directorio = public_path('storage/uploads/testimonios/');
        if (!file_exists($directorio)) {
            mkdir($directorio, 0755, true);
        }

        // Guardar imagen
        $file = $request->file('imagen');
        $nombreImagen = Str::uuid() . '.' . $file->getClientOriginalExtension();
        $file->move($directorio, $nombreImagen);
        $rutaImagen = '/testimonios/' . $nombreImagen;

        // Crear el testimonio en la base de datos
        $testimonio = Testimonio::create([
            'nombre' => $request->nombre,
            'texto' => $request->texto,
            'imagen' => $rutaImagen,
        ]);

        return response()->json([
            'message' => 'Testimonio creado correctamente',
            'data' => $testimonio,
        ]);
    }
    public function index()
    {
        $testimonios = Testimonio::latest()->get();

        return response()->json([
            'data' => $testimonios
        ]);
    }
    public function destroy($id)
    {
        $testimonio = Testimonio::findOrFail($id);
        $rutaImagen = public_path('storage/uploads/testimonios/') . basename($testimonio->imagen);

        // Eliminar la imagen del servidor
        if (file_exists($rutaImagen)) {
            unlink($rutaImagen);
        }

        // Eliminar el testimonio de la base de datos
        $testimonio->delete();

        return response()->json([
            'message' => 'Testimonio eliminado correctamente'
        ]);
    }
}
