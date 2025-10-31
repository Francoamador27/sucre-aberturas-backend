<?php

namespace App\Http\Controllers;

use App\Models\Patologia;
use App\Models\Patient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PatologiaController extends Controller
{
    /**
     * Obtener todas las patologías de un paciente específico
     */
    public function index($idpa)
    {
        try {
            $paciente = Patient::where('idpa', $idpa)->firstOrFail();
            $patologias = Patologia::where('paciente_id', $idpa)->get();
            
            return response()->json([
                'success' => true,
                'paciente' => $paciente,
                'patologias' => $patologias
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Paciente no encontrado'
            ], 404);
        }
    }

    /**
     * Guardar una nueva patología
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'idpa' => 'required|integer|exists:patients,idpa',
            'alergico' => 'nullable|string',
            'medicamentos' => 'nullable|string',
            'recomendaciones' => 'nullable|string',
        ], [
            'idpa.required' => 'El ID del paciente es requerido',
            'idpa.exists' => 'El paciente no existe',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Verificar si el paciente ya tiene patologías registradas
            $patologiaExistente = Patologia::where('paciente_id', $request->idpa)->first();

            if ($patologiaExistente) {
                // Actualizar la patología existente
                $patologiaExistente->update([
                    'alergico' => $request->alergico,
                    'medicamentos' => $request->medicamentos,
                    'recomendaciones' => $request->recomendaciones,
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Patología actualizada exitosamente',
                    'data' => $patologiaExistente
                ], 200);
            } else {
                // Crear nueva patología
                $patologia = Patologia::create([
                    'paciente_id' => $request->idpa,
                    'alergico' => $request->alergico,
                    'medicamentos' => $request->medicamentos,
                    'recomendaciones' => $request->recomendaciones,
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Patología guardada exitosamente',
                    'data' => $patologia
                ], 201);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al guardar la patología',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mostrar una patología específica
     */
    public function show($id)
    {
        try {
            $patologia = Patologia::with('paciente')->findOrFail($id);
            
            return response()->json([
                'success' => true,
                'data' => $patologia
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Patología no encontrada'
            ], 404);
        }
    }

    /**
     * Actualizar una patología existente
     */
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'alergico' => 'nullable|string',
            'medicamentos' => 'nullable|string',
            'recomendaciones' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $patologia = Patologia::findOrFail($id);
            
            $patologia->update([
                'alergico' => $request->alergico,
                'medicamentos' => $request->medicamentos,
                'recomendaciones' => $request->recomendaciones,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Patología actualizada exitosamente',
                'data' => $patologia
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar la patología',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Eliminar una patología
     */
    public function destroy($id)
    {
        try {
            $patologia = Patologia::findOrFail($id);
            $patologia->delete();

            return response()->json([
                'success' => true,
                'message' => 'Patología eliminada exitosamente'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar la patología'
            ], 500);
        }
    }

    /**
     * Obtener la patología de un paciente (método auxiliar para tu frontend)
     */
    public function getByPatient($idpa)
    {
        try {
            $patologia = Patologia::where('paciente_id', $idpa)->first();
            
            if (!$patologia) {
                return response()->json([
                    'success' => true,
                    'message' => 'El paciente no tiene patologías registradas',
                    'data' => null
                ], 200);
            }

            return response()->json([
                'success' => true,
                'data' => $patologia
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener la patología'
            ], 500);
        }
    }
}