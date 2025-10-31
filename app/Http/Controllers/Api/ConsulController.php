<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Consul;
use Illuminate\Http\Request;

class ConsulController extends Controller
{
    public function index($idpa)
    {
        return response()->json([
            'success' => true,
            'data' => Consul::where('idpa', $idpa)
                ->orderBy('fere', 'desc')
                ->orderBy('idconslt', 'desc')
                ->get(),
        ]);
    }

    public function store(Request $request, $idpa)
    {
        $data = $request->validate([
            'mtcl'      => ['nullable','string'],
            'numdiente' => ['nullable','string','max:50'],
        ]);

        $row = Consul::create([
            'mtcl'      => $data['mtcl']      ?? null,
            'idpa'      => (int) $idpa,
            'state'     => 1,
            'fere'      => now(),
            'numdiente' => $data['numdiente'] ?? null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Nota agregada al historial clínico.',
            'data'    => $row,
        ], 201);
    }
    public function destroy($idconslt)
{
    $row = Consul::find($idconslt);

    if (!$row) {
        return response()->json([
            'success' => false,
            'message' => 'Nota no encontrada.',
        ], 404);
    }

    $row->delete();

    return response()->json([
        'success' => true,
        'message' => 'Nota eliminada correctamente.',
    ], 200);
}
}
