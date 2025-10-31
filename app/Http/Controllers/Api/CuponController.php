<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Coupon;
use App\Http\Requests\StoreCuponRequest;
use Illuminate\Http\Request;
use App\Helpers\CuponHelper;

class CuponController extends Controller
{
    public function index()
    {
        return response()->json([
            'data' => Coupon::orderByDesc('created_at')->get(),
            'message' => 'Listado de cupones'
        ]);
    }
public static function validar(Request $request)
{
    $codigo = $request->input('code');
    $total = (float) $request->input('total');

    [$cupon, $descuento] = CuponHelper::validarCuponAplicable($codigo, $total);

    if (!$cupon) {
        return response()->json([
            'valid' => false,
            'message' => 'Cupón inválido, vencido o superó su límite de uso.',
        ], 404);
    }

    return response()->json([
        'valid' => true,
        'data' => $cupon,
        'discount' => round($descuento, 2),
        'message' => 'Cupón válido',
    ]);
}

    public function store(StoreCuponRequest $request)
    {
        $cupon = Coupon::create($request->validated());

        return response()->json([
            'data' => $cupon,
            'message' => 'Cupón creado correctamente'
        ], 201);
    }

    public function destroy($id)
    {
        $cupon = Coupon::find($id);

        if (!$cupon) {
            return response()->json(['message' => 'Cupón no encontrado'], 404);
        }

        $cupon->delete();

        return response()->json(['message' => 'Cupón eliminado correctamente']);
    }
}
