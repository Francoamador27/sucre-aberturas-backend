<?php

namespace App\Http\Controllers;

use App\Models\Odontograma;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
class OdontogramaController extends Controller
{
    /**
     * GET /api/odontograma/{idpa}
     * Devuelve el último odontograma del paciente.
     */
public function show($idpa)
{
    $row = Odontograma::where('idpa', $idpa)->first();

    if (!$row) {
        return response()->json([
            'success' => true,
            'data' => ['registros' => (object) []],
        ], 200);
    }

    return response()->json([
        'success' => true,
        'data' => [
            'id' => $row->id,
            'idpa' => $row->idpa,
            'registros' => json_decode($row->datos, true) ?? (object) [],
            'fecha_modificacion' => $row->fecha_modificacion,
            'fecha_creacion' => $row->fecha_creacion,
        ],
    ], 200);
}


    /**
     * POST /api/odontograma/{idpa}
     * Upsert por idpa (crea si no existe, actualiza si existe).
     * Body:
     * {
     *   "datos": { ...geometry... },     // o string JSON
     *   "fecha_modificacion": "ISO8601"  // opcional, informativo
     * }
     */
    public function store(Request $request, $idpa)
    {
        $request->validate([
            'datos' => ['required'],                 // objeto o string JSON
            'fecha_modificacion' => ['nullable', 'string'],
        ]);

        $rawDatos = $request->input('datos');

        // Normalizo a string JSON válido para guardar en 'datos'
        if (is_string($rawDatos)) {
            $decoded = json_decode($rawDatos, true);
            $toStore = (json_last_error() === JSON_ERROR_NONE && is_array($decoded))
                ? json_encode($decoded)
                : $rawDatos; // si ya venía como string JSON válido, lo guardo tal cual
        } else {
            $toStore = json_encode($rawDatos);
        }

        // Upsert por idpa
        $row = Odontograma::firstOrNew(['idpa' => $idpa]);
        $row->datos = $toStore ?? '{}';

        // Si te llega la fecha desde el front, la usamos; si no, Eloquent setea UPDATED_AT
        if ($request->filled('fecha_modificacion')) {
            $row->{Odontograma::UPDATED_AT} = Carbon::parse($request->input('fecha_modificacion'));
        }

        $row->save();

        return response()->json([
            'success' => true,
            'message' => 'Odontograma guardado correctamente.',
            'data' => [
                'id' => $row->id,
                'idpa' => $row->idpa,
            ],
        ], 200);
    }
}
