<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCartDiscountRequest;
use Illuminate\Http\Request;
use App\Models\CartDiscount;

class CartDiscountController extends Controller
{
    public function index()
    {
        $reglas = CartDiscount::orderByDesc('created_at')->get();

        return response()->json([
            'message' => 'Listado de reglas de descuento',
            'data' => $reglas
        ]);
    }

    public function store(StoreCartDiscountRequest $request)
    {
        $discount = CartDiscount::create($request->validated());

        return response()->json([
            'message' => 'Regla de descuento creada correctamente',
            'data' => $discount
        ], 201);
    }

    public function show($id)
    {
        $discount = CartDiscount::find($id);

        if (!$discount) {
            return response()->json([
                'message' => 'Regla de descuento no encontrada'
            ], 404);
        }

        return response()->json([
            'data' => $discount
        ]);
    }

    public function update(Request $request, $id)
    {
        $discount = CartDiscount::find($id);

        if (!$discount) {
            return response()->json([
                'message' => 'Regla de descuento no encontrada'
            ], 404);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:percentage,fixed',
            'condition_type' => 'required|in:amount,quantity',
            'discount_value' => 'required|numeric|min:0',
            'min_value' => 'nullable|numeric|min:0',
            'max_discount' => 'nullable|numeric|min:0',
            'usage_limit' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        $discount->update($validated);

        return response()->json([
            'message' => 'Regla de descuento actualizada correctamente',
            'data' => $discount
        ]);
    }

    public function destroy($id)
    {
        $discount = CartDiscount::find($id);

        if (!$discount) {
            return response()->json([
                'message' => 'Regla de descuento no encontrada'
            ], 404);
        }

        $discount->delete();

        return response()->json([
            'message' => 'Regla de descuento eliminada correctamente'
        ]);
    }
}
