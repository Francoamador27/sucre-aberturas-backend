<?php
namespace App\Http\Controllers;

use App\Http\Resources\PedidoCollection;
use App\Models\Coupon;
use App\Models\Producto;
use App\Models\User;
use App\Models\Pedido;
use App\Services\EstrategiasPago\EstrategiaPagoFactory;
use App\Services\MercadoPagoService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use App\Models\Carrito;
use App\Http\Controllers\EmailController;
use App\Helpers\CuponHelper;
use App\Helpers\CartDiscountHelper;
use Illuminate\Support\Facades\Auth;

class PedidoController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $query = Pedido::with(['usuario', 'carritos']);

        // 🔍 Filtrar por estado
        if ($request->filled('estado')) {
            $query->where('estado', $request->estado);
        }

        // 🔍 Buscar por nombre del cliente
        if ($request->filled('busqueda')) {
            $query->whereHas('usuario', function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->busqueda . '%');
            });
        }

        // ↕ Ordenar por fecha (asc o desc)
        $direccion = $request->get('direccion', 'desc');
        if (!in_array($direccion, ['asc', 'desc'])) {
            $direccion = 'desc'; // fallback
        }
        $query->orderBy('created_at', $direccion);

        // 📄 Paginación
        $pedidos = $query->paginate($request->get('per_page', 10));

        return new PedidoCollection($pedidos);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $datos = $request->input('datos');
        $carritosData = $request->input('carrtios');
        $codigoCupon = $request->input('cupon');
        $admin = $request->input('user');

        // 🔐 Verificar si hay un token válido y obtener el usuario logueado
        $usuarioLogueado = $this->getAuthenticatedUser($request);

        // \Log::info('Usuario logueado:', [
        //     'id' => $usuarioLogueado->id ?? null,
        //     'admin' => $usuarioLogueado->admin ?? null,
        //     'email' => $usuarioLogueado->email ?? null
        // ]);

        $rol = ($usuarioLogueado && $usuarioLogueado->admin == 1) ? 'admin' : 'client';

        // 👤 Determinar qué usuario usar para el pedido
        if ($usuarioLogueado) {
            // Si hay usuario logueado, usar ese usuario y actualizar sus datos si es necesario
            $usuario = $usuarioLogueado;

            // Actualizar datos del usuario logueado si vienen en la request
            $usuario->update([
                'name' => $datos['name'] ?? $usuario->name,
                'telefono' => $datos['telefono'] ?? $usuario->telefono,
                'dni' => $datos['dni'] ?? $usuario->dni,
                'codigo_postal' => $datos['codigo_postal'] ?? $usuario->codigo_postal,
                'direccion' => $datos['direccion'] ?? $usuario->direccion,
                'localidad' => $datos['localidad'] ?? $usuario->localidad,
                'provincia' => $datos['provincia'] ?? $usuario->provincia,
            ]);

            //  \Log::info('Usando usuario logueado actualizado:', ['id' => $usuario->id]);
        } else {
            // Si no hay usuario logueado, buscar o crear usuario (comportamiento original)
            $usuario = User::firstOrCreate(
                ['email' => $datos['email']],
                [
                    'name' => $datos['name'],
                    'password' => Hash::make('password_random_' . time()),
                    'telefono' => $datos['telefono'] ?? null,
                    'dni' => $datos['dni'] ?? null,
                    'codigo_postal' => $datos['codigo_postal'] ?? null,
                    'direccion' => $datos['direccion'] ?? null,
                    'localidad' => $datos['localidad'] ?? null,
                    'provincia' => $datos['provincia'] ?? null,
                    'rol' => 'client',
                ]
            );

            //  \Log::info('Usuario creado/encontrado (sin login):', ['id' => $usuario->id]);
        }

        $pedido = Pedido::create([
            'user_id' => $usuario->id,
            'estado' => 'recibido',
            'total' => 0,
        ]);

        // \Log::info('Pedido creado:', ['pedido_id' => $pedido->id]);

        $totalPedido = 0;
        $totalCantidad = 0;

        foreach ($carritosData as $item) {
            $carrito = Carrito::find($item['id']);
            $producto = Producto::find($carrito->producto_id);

            if (!$producto) {
                \Log::warning("Producto no encontrado para carrito ID {$carrito->id}");
                continue;
            }

            $cantidad = (int) $carrito->cantidad;
            $subtotal = $producto->precio * $cantidad;
            $totalCantidad += $cantidad;
            $totalPedido += $subtotal;

            $carrito->pedido_id = $pedido->id;
            $carrito->save();
        }

        $montoDescuento = CartDiscountHelper::calcularDescuentoPorCantidad($totalPedido, $totalCantidad);

        // Validar cupón usando helper centralizado
        $cupon = null;
        $descuentoCupon = 0;
        $totalConDescuento = $totalPedido - $montoDescuento;

        if ($codigoCupon) {
            [$cupon, $descuentoCupon] = CuponHelper::validarCuponAplicable($codigoCupon, $totalConDescuento);
            if ($cupon) {
                $cupon->increment('usage_count');
            }
        }

        $totalFinal = $totalPedido - $montoDescuento - $descuentoCupon;

        $metodoEnvio = $datos['metodo_envio'] ?? 'cordoba';
        $costoEnvio = 0;
 
        //LOGUEAR CODIGO POSTAL RECIBIDO
        \Log::info('Código postal recibido:', ['codigo_postal' => $datos['codigo_postal'] ?? 'N/A']);

        
        if ($metodoEnvio === 'epick' && !empty($datos['codigo_postal'])) {
            $codigo = (int) preg_replace('/\D/', '', $datos['codigo_postal']);
            if ($codigo < 5000 || $codigo > 5019) {
                $costoEnvio = $this->calcularCostoEnvioEPick($datos['codigo_postal'], $totalConDescuento);
            }
        }

        $totalFinal += $costoEnvio;

        // Guardar totales
        $pedido->update([
            'total' => $totalFinal,
            'descuento_aplicado' => $montoDescuento,
            'total_sin_descuento' => $totalPedido,
            'metodo_envio' => $metodoEnvio,
            'costo_envio' => $costoEnvio,
            'codigo_cupon' => $cupon?->code,
            'monto_descuento_cupon' => $descuentoCupon,
        ]);

        $pedido->refresh();

        try {
            $emailController = new EmailController();
            $emailController->enviarPedidoRecibido($pedido->id);
        } catch (\Exception $e) {
            \Log::error('Error al enviar el correo de confirmación: ' . $e->getMessage());
        }

        $estrategiaPago = EstrategiaPagoFactory::obtenerEstrategia($rol,$costoEnvio);
        $initPoint = $estrategiaPago->procesar($pedido, $pedido->carritos);

        return response()->json([
            'message' => 'Pedido y carritos procesados correctamente',
            'pedido_id' => $pedido->id,
            'user_id' => $usuario->id,
            'total' => $pedido->total,
            'init_point' => $initPoint,
            'usuario_logueado' => $usuarioLogueado ? true : false, // 🔍 Para debugging
        ]);
    }

    /**
     * 🔐 Método para obtener el usuario autenticado de forma opcional (Sanctum)
     */

    private function calcularCostoEnvioEPick(string $codigoPostal, float $valorTotal): ?int
    {
        try {
            $client = new \GuzzleHttp\Client();
            $response = $client->post('https://dev-ar.e-pick.com.ar/api/orders/calculator/www', [
                'headers' => [
                    'Accept' => 'application/json',
                    'Origin' => 'https://dev-ar.e-pick.com.ar',
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'package' => [
                        'long' => 10,
                        'width' => 10,
                        'height' => 20,
                        'weight' => "0.5",
                        'value' => $valorTotal,
                    ],
                    'sender' => [
                        'postal_code' => '5000',
                    ],
                    'addressee' => [
                        'postal_code' => $codigoPostal,
                    ],
                ]
            ]);

            $data = json_decode($response->getBody(), true);

            if ($data['isValid'] && isset($data['price'])) {
                return (int) $data['price'];
            }
        } catch (\Exception $e) {
            \Log::error("Error al calcular costo de envío E-Pick: " . $e->getMessage());
        }

        return 0;
    }

    private function getAuthenticatedUser(Request $request)
    {
        try {
            // Verificar si hay un token en el header Authorization
            $token = $request->bearerToken();

            if (!$token) {
                \Log::info('No se encontró token en la request');
                return null;
            }

            \Log::info('Token encontrado:', ['token' => substr($token, 0, 20) . '...']);

            // Intentar autenticar con Sanctum usando el guard 'sanctum'
            $user = Auth::guard('sanctum')->user();

            if ($user) {
                \Log::info('Usuario autenticado exitosamente con Sanctum:', ['user_id' => $user->id]);
                return $user;
            }

            \Log::info('No se pudo autenticar al usuario con el token de Sanctum');
            return null;

        } catch (\Exception $e) {
            \Log::error('Error general al autenticar con Sanctum: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Pedido  $pedido
     * @return \Illuminate\Http\Response
     */
    public function show(Pedido $pedido)
    {
        $pedido->load(['usuario', 'carritos']);
        return response()->json($pedido);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Pedido  $pedido
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Pedido $pedido)
    {
        $request->validate([
            'estado' => 'required|in:recibido,impreso,enviado,completado,cancelado',
        ]);

        $pedido->estado = $request->estado;
        $pedido->save();

        app(EmailController::class)->enviarCorreoPorEstado($pedido);

        return response()->json([
            'message' => 'Estado del pedido actualizado correctamente.',
            'pedido' => $pedido,
        ]);
    }

    public function misPedidos(Request $request)
    {
        $pedidos = Pedido::with(['carritos'])
            ->where('user_id', auth()->id())
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return new PedidoCollection($pedidos);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Pedido  $pedido
     * @return \Illuminate\Http\Response
     */
    public function destroy(Pedido $pedido)
    {
        // Obtener los carritos relacionados a este pedido
        $carritos = Carrito::where('pedido_id', $pedido->id)->get();

        foreach ($carritos as $carrito) {
            CarritoController::eliminarFisicamente($carrito);
        }

        $pedido->delete();

        return response()->json(['message' => 'Pedido y carritos eliminados correctamente']);
    }
}