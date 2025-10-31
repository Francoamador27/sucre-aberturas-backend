<?php

namespace App\Http\Controllers;

use App\Models\Gasto;
use App\Models\CategoriaGasto;
use Illuminate\Http\Request;

class GastoController extends Controller
{
    // Mostrar todos los gastos
public function index(Request $request)
{
    $query = Gasto::with('categoria');

    // Filtrar por categoría
    if ($request->has('categoria') && $request->categoria != '') {
        $query->where('idcat', $request->categoria);
    }

    // Filtrar por rango de fechas
    if ($request->has('fecha_desde') && $request->fecha_desde != '') {
        $query->whereDate('fecha', '>=', $request->fecha_desde);
    }

    if ($request->has('fecha_hasta') && $request->fecha_hasta != '') {
        $query->whereDate('fecha', '<=', $request->fecha_hasta);
    }

    // Ordenar
    $ordenarPor = $request->get('ordenar_por', 'fecha');
    $direccion = $request->get('direccion', 'desc');
    $query->orderBy($ordenarPor, $direccion);

    // Paginación (opcional)
    if ($request->has('per_page')) {
        $gastos = $query->paginate($request->per_page);
        return response()->json($gastos);
    }

    // Sin paginación
    $gastos = $query->get();
    return response()->json($gastos);
}
    // Mostrar un gasto específico
    public function show($id)
    {
        $gasto = Gasto::with('categoria')->findOrFail($id);
        return response()->json($gasto);
    }

    // Crear un nuevo gasto
    public function store(Request $request)
    {
        $validated = $request->validate([
            'idcat' => 'required|exists:categorias_gastos,id',
            'fecha' => 'required|date',
            'descripcion' => 'required|string|max:255',
            'importe' => 'required|numeric|min:0'
        ]);

        $gasto = Gasto::create($validated);
        
        return response()->json([
            'message' => 'Gasto creado exitosamente',
            'gasto' => $gasto->load('categoria')
        ], 201);
    }

    // Actualizar un gasto existente
    public function update(Request $request, $id)
    {
        $gasto = Gasto::findOrFail($id);

        $validated = $request->validate([
            'idcat' => 'sometimes|exists:categorias_gastos,id',
            'fecha' => 'sometimes|date',
            'descripcion' => 'sometimes|string|max:255',
            'importe' => 'sometimes|numeric|min:0'
        ]);

        $gasto->update($validated);

        return response()->json([
            'message' => 'Gasto actualizado exitosamente',
            'gasto' => $gasto->load('categoria')
        ]);
    }

    // Eliminar un gasto
    public function destroy($id)
    {
        $gasto = Gasto::findOrFail($id);
        $gasto->delete();

        return response()->json([
            'message' => 'Gasto eliminado exitosamente'
        ]);
    }

    // Obtener todas las categorías (útil para formularios)
    public function getCategorias()
    {
        $categorias = CategoriaGasto::all();
        return response()->json($categorias);
    }

    // Obtener gastos por categoría
    public function getByCategoria($idcat)
    {
        $gastos = Gasto::where('idcat', $idcat)
            ->with('categoria')
            ->orderBy('fecha', 'desc')
            ->get();
        
        return response()->json($gastos);
    }

    // Obtener gastos por rango de fechas
    public function getByFechas(Request $request)
    {
        $validated = $request->validate([
            'fecha_inicio' => 'required|date',
            'fecha_fin' => 'required|date|after_or_equal:fecha_inicio'
        ]);

        $gastos = Gasto::whereBetween('fecha', [$validated['fecha_inicio'], $validated['fecha_fin']])
            ->with('categoria')
            ->orderBy('fecha', 'desc')
            ->get();

        return response()->json($gastos);
    }
}