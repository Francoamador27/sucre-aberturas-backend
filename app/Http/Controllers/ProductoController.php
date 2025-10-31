<?php

namespace App\Http\Controllers;

use App\Http\Resources\ProductoCollection;
use App\Models\Producto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class ProductoController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \App\Http\Resources\ProductoCollection
     */
    public function index(Request $request)
    {
        $query = Producto::query();

        // Filtro por disponibilidad
        if ($request->filled('disponible')) {
            $query->where('disponible', $request->disponible);
        }

        // Filtro por categoría
        if ($request->filled('categoria_id')) {
            $query->where('categoria_id', $request->categoria_id);
        }

        // Búsqueda inteligente: en nombre o descripción
        if ($request->filled('busqueda')) {
            $texto = $request->busqueda;
            $query->where(function ($q) use ($texto) {
                $q->where('nombre', 'like', '%' . $texto . '%')
                    ->orWhere('descripcion', 'like', '%' . $texto . '%');
            });
        }

        // Ordenamiento dinámico
        $ordenables = ['nombre', 'precio', 'created_at', 'id'];
        $ordenPor = $request->get('ordenar_por', 'id');
        $direccion = $request->get('direccion', 'desc');

        if (!in_array($ordenPor, $ordenables))
            $ordenPor = 'id';
        if (!in_array($direccion, ['asc', 'desc']))
            $direccion = 'desc';

        $query->orderBy($ordenPor, $direccion);

        // Paginación
        $perPage = $request->get('per_page', 10);

        return new ProductoCollection($query->paginate($perPage));
    }


    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string|max:255',
            'descripcion' => 'nullable|string',
            'precio' => 'required|numeric|min:0',
            'disponible' => 'required|boolean',
            'categoria_id' => 'required|exists:categorias,id',
            'imagenes' => 'required|array|min:1',
            'imagenes.*' => 'file|image|max:10240', // 10MB c/u
        ]);

        $imagenesGuardadas = [];
        $directorio = public_path('storage/uploads/combos');

        // Crear el directorio si no existe
        if (!file_exists($directorio)) {
            mkdir($directorio, 0755, true);
        }

        // Guardar cada imagen en public/storage/uploads/combos
        foreach ($request->file('imagenes') as $idx => $imagen) {
            Log::info("Imagen {$idx}: " . $imagen->getClientOriginalName());

            $nombre = uniqid() . '.' . $imagen->getClientOriginalExtension();
            $imagen->move($directorio, $nombre);

            // Guardar ruta relativa para usar en el frontend
            $imagenesGuardadas[] = 'storage/uploads/combos/' . $nombre;
        }

        // Crear el producto
        $producto = Producto::create([
            'nombre' => $request->nombre,
            'descripcion' => $request->descripcion,
            'precio' => $request->precio,
            'disponible' => $request->disponible,
            'categoria_id' => $request->categoria_id,
            'imagen' => json_encode($imagenesGuardadas),
        ]);

        return response()->json($producto, 201);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Producto  $producto
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $producto = Producto::find($id);

        if (!$producto) {
            return response()->json(['message' => 'Producto no encontrado.'], 404);
        }

        return response()->json($producto);
    }


    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Producto  $producto
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Producto $producto)
    {
        $request->validate([
            'nombre' => 'required|string|max:255',
            'descripcion' => 'nullable|string',
            'precio' => 'required|numeric|min:0',
            'disponible' => 'required|boolean',
        ]);

        $producto->update([
            'nombre' => $request->nombre,
            'descripcion' => $request->descripcion,
            'precio' => $request->precio,
            'disponible' => $request->disponible,
        ]);

        return response()->json([
            'message' => 'Producto actualizado correctamente.',
            'producto' => $producto
        ]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Producto  $producto
     * @return \Illuminate\Http\Response
     */
public function destroy(Producto $producto)
{
    return DB::transaction(function () use ($producto) {

        // Asegurá que $producto->imagen sea un array (por si viene como string)
        $imagenes = is_array($producto->imagen)
            ? $producto->imagen
            : (json_decode($producto->imagen, true) ?: []);

        foreach ($imagenes as $rutaRelativa) {
            // La ruta la guardaste tipo: 'storage/uploads/combos/archivo.jpg'
            $rutaAbsoluta = public_path($rutaRelativa);

            if (is_string($rutaAbsoluta) && file_exists($rutaAbsoluta)) {
                @unlink($rutaAbsoluta);
                Log::info("Imagen eliminada: {$rutaAbsoluta}");
            } else {
                Log::warning("No se encontró imagen para borrar: {$rutaAbsoluta}");
            }
        }

        // Si tenés relaciones (carritos, etc.), desvinculá acá antes del delete

        $producto->delete(); // o forceDelete() si usás SoftDeletes y querés borrado real

        return response()->json(['message' => 'Producto eliminado'], 200);
    });
}
}
