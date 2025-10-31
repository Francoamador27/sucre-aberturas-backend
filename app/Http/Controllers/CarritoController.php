<?php

namespace App\Http\Controllers;

use App\Models\Carrito;
use App\Models\Producto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class CarritoController extends Controller
{
    // Listar todos los carritos
    public function index(Request $request)
    {
        $query = Carrito::query();

        // Si viene ?sin_pedido=1, filtrá los carritos sin pedido
        if ($request->has('sin_pedido') && $request->sin_pedido == 1) {
            $query->whereNull('pedido_id');
        }

        return response()->json($query->get());
    }

    // Crear un nuevo carrito
    public function store(Request $request)
    {
        $request->validate([
            'producto_id' => 'required|exists:productos,id',
            'cantidad' => 'required|integer|min:1',
            'session_token' => 'nullable|string',
            'imagenes' => 'required|array|min:1',
            'imagenes.*' => 'file|image|max:10240',
        ]);

        // 📌 Log cantidad y nombres de las imágenes recibidas
        Log ::info('Cantidad de imágenes recibidas: ' . count($request->file('imagenes')));
        foreach ($request->file('imagenes') as $key => $img) {
            Log::info("Imagen $key: " . $img->getClientOriginalName());
        }

        $producto = Producto::findOrFail($request->producto_id);
        $imagenesGuardadas = [];

        $directorio = public_path('storage/uploads');

        if (!file_exists($directorio)) {
            mkdir($directorio, 0755, true); // crea recursivamente las carpetas
        }

        $imagenesGuardadas = [];

        foreach ($request->file('imagenes') as $imagen) {
            $nombre = uniqid() . '.' . $imagen->getClientOriginalExtension();
            $imagen->move($directorio, $nombre);
            $imagenesGuardadas[] = $nombre;
        }



        $carrito = Carrito::create([
            'producto_id' => $producto->id,
            'cantidad' => $request->cantidad,
            'imagenes' => $imagenesGuardadas,
            'session_token' => $request->session_token,
            'pedido_id' => null,
        ]);

        return response()->json([
            'message' => 'Carrito creado correctamente',
            'carrito' => [
                'id' => $carrito->id,
                'producto_id' => $carrito->producto_id,
                'cantidad' => $carrito->cantidad,
                'precio_unidad' => $producto->precio,
                'precio_total' => $producto->precio * $carrito->cantidad,
                'imagenes' => $carrito->imagenes,
                'session_token' => $carrito->session_token,
                'pedido_id' => $carrito->pedido_id,
            ],
        ]);
    }

    // Mostrar un carrito específico
    public function show($id)
    {
        $carrito = Carrito::find($id);

        if (!$carrito) {
            return response()->json(['message' => 'Carrito no encontrado'], 404);
        }

        return response()->json($carrito);
    }

    // Actualizar un carrito
    public function update(Request $request, $id)
    {
        $carrito = Carrito::find($id);

        if (!$carrito) {
            return response()->json(['message' => 'Carrito no encontrado'], 404);
        }

        $request->validate([
            'cantidad' => 'sometimes|required|integer|min:1',
        ]);

        if ($request->has('cantidad')) {
            $carrito->cantidad = $request->cantidad;
        }

        $carrito->save();

        return response()->json(['message' => 'Carrito actualizado correctamente', 'carrito' => $carrito]);
    }

    // Eliminar un carrito
    public function destroy($id)
    {
        $carrito = Carrito::find($id);

        if (!$carrito) {
            return response()->json(['message' => 'Carrito no encontrado'], 404);
        }

        self::eliminarFisicamente($carrito);

        return response()->json(['message' => 'Carrito eliminado correctamente']);
    }

    public static function eliminarFisicamente(Carrito $carrito)
    {
        if (is_array($carrito->imagenes)) {
            foreach ($carrito->imagenes as $imagen) {
                $ruta = public_path('storage/uploads/' . $imagen);
                if (file_exists($ruta)) {
                    unlink($ruta);
                }
            }
        }

        $carrito->delete();
    }


}
