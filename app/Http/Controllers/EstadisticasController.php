<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\Gasto;
use Illuminate\Http\Request;
use Carbon\Carbon;

class EstadisticasController extends Controller
{
    public function index(Request $request)
    {
        $fechaDesde = $request->input('fecha_desde');
        $fechaHasta = $request->input('fecha_hasta');

        // Query para eventos pagados
        $queryPagados = Event::where('chec', 1);
        
        // Query para eventos no pagados
        $queryNoPagados = Event::where('chec', 0);
        
        // Query para gastos
        $queryGastos = Gasto::query();

        // Aplicar filtros de fecha si existen
        if ($fechaDesde) {
            $queryPagados->whereDate('start', '>=', $fechaDesde);
            $queryNoPagados->whereDate('start', '>=', $fechaDesde);
            $queryGastos->whereDate('fecha', '>=', $fechaDesde);
        }

        if ($fechaHasta) {
            $queryPagados->whereDate('start', '<=', $fechaHasta);
            $queryNoPagados->whereDate('start', '<=', $fechaHasta);
            $queryGastos->whereDate('fecha', '<=', $fechaHasta);
        }

        // Obtener totales
        $totalPagados = $queryPagados->sum('monto');
        $totalNoPagados = $queryNoPagados->sum('monto');
        $totalGastos = $queryGastos->sum('importe');

        // Contar cantidad de eventos
        $cantidadPagados = $queryPagados->count();
        $cantidadNoPagados = $queryNoPagados->count();
        $cantidadGastos = $queryGastos->count();

        // Calcular balance (ingresos - gastos)
        $balance = $totalPagados - $totalGastos;
        $ingresosEsperados = $totalPagados + $totalNoPagados;

        return response()->json([
            'success' => true,
            'data' => [
                'ingresos_cobrados' => [
                    'total' => (float) $totalPagados,
                    'cantidad' => $cantidadPagados
                ],
                'ingresos_pendientes' => [
                    'total' => (float) $totalNoPagados,
                    'cantidad' => $cantidadNoPagados
                ],
                'gastos' => [
                    'total' => (float) $totalGastos,
                    'cantidad' => $cantidadGastos
                ],
                'resumen' => [
                    'balance' => (float) $balance,
                    'ingresos_totales_esperados' => (float) $ingresosEsperados,
                    'porcentaje_cobrado' => $ingresosEsperados > 0 
                        ? round(($totalPagados / $ingresosEsperados) * 100, 2) 
                        : 0
                ]
            ],
            'filtros' => [
                'fecha_desde' => $fechaDesde,
                'fecha_hasta' => $fechaHasta
            ]
        ]);
    }
}