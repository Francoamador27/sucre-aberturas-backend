<?php

namespace App\Http\Controllers;

use App\Models\CategoriaGasto;
use Illuminate\Http\Request;

class CategoriaGastoController extends Controller
{
    // Mostrar todas las categorías
    public function index()
    {
        $categorias = CategoriaGasto::all();
        return response()->json($categorias);
    }

    // Mostrar una categoría específica
    public function show($id)
    {
        $categoria = CategoriaGasto::findOrFail($id);
        return response()->json($categoria);
    }

    // Crear una nueva categoría
    public function store(Request $request)
    {
        $validated = $request->validate([
            'nombre_categoria' => 'required|string|max:255|unique:categorias_gastos,nombre_categoria',
            'detalle_categoria' => 'nullable|string|max:500'
        ]);

        $categoria = CategoriaGasto::create($validated);
        
        return response()->json([
            'message' => 'Categoría creada exitosamente',
            'categoria' => $categoria
        ], 201);
    }

    // Actualizar una categoría existente
    public function update(Request $request, $id)
    {
        $categoria = CategoriaGasto::findOrFail($id);

        $validated = $request->validate([
            'nombre_categoria' => 'sometimes|string|max:255|unique:categorias_gastos,nombre_categoria,' . $id,
            'detalle_categoria' => 'nullable|string|max:500'
        ]);

        $categoria->update($validated);

        return response()->json([
            'message' => 'Categoría actualizada exitosamente',
            'categoria' => $categoria
        ]);
    }

    // Eliminar una categoría
    public function destroy($id)
    {
        $categoria = CategoriaGasto::findOrFail($id);
        
        // Verificar si tiene gastos asociados
        if ($categoria->gastos()->count() > 0) {
            return response()->json([
                'message' => 'No se puede eliminar la categoría porque tiene gastos asociados'
            ], 400);
        }

        $categoria->delete();

        return response()->json([
            'message' => 'Categoría eliminada exitosamente'
        ]);
    }

    // Obtener una categoría con sus gastos
    public function getWithGastos($id)
    {
        $categoria = CategoriaGasto::with('gastos')->findOrFail($id);
        return response()->json($categoria);
    }

    // Obtener estadísticas de una categoría
    public function getEstadisticas($id)
    {
        $categoria = CategoriaGasto::findOrFail($id);
        
        $estadisticas = [
            'categoria' => $categoria,
            'total_gastos' => $categoria->gastos()->count(),
            'importe_total' => $categoria->gastos()->sum('importe'),
            'importe_promedio' => $categoria->gastos()->avg('importe')
        ];

        return response()->json($estadisticas);
    }
}