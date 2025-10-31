<?php

namespace App\Http\Controllers\Api;

use setasign\Fpdi\Fpdi;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Carrito;

class PdfController extends Controller
{
    public function generar(Request $request)
    {
        $request->validate([
            'carrito_id' => 'required|integer|exists:carritos,id',
        ]);

        $carrito = Carrito::findOrFail($request->carrito_id);

        $imagenes = is_array($carrito->imagenes)
            ? $carrito->imagenes
            : json_decode($carrito->imagenes, true);

        $pdf = new Fpdi();

        // Ruta al archivo PDF template
        $templatePath = storage_path('app/pdf/template.pdf');
        $pageCount = $pdf->setSourceFile($templatePath);
        $templateId = $pdf->importPage(1);
        $cantidad = $carrito->cantidad;
        $pdf->AddPage();
        $pdf->useTemplate($templateId);

        // Encabezado
        $pdf->SetFont('Helvetica', '', 14);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->SetXY(30, 0);
        $pdf->Cell(0, 10, 'Pedido ID: ' . $carrito->pedido_id . '  |  Cantidad: ' . $cantidad, 0, 1);

        // Configuración de grilla
        $columnas = 2;
        $filas = 3;
        $anchoImagen = 64;
        $altoImagen = 64;
        $espaciadoX = 33;
        $espaciadoY = 26.5;

        $margenIzquierdo = 30;
        $margenSuperior = 29;
        $imagenesPorPagina = $columnas * $filas;

        $imagenes = is_array($carrito->imagenes)
            ? $carrito->imagenes
            : json_decode($carrito->imagenes, true);

        // Verificamos cuántas hay
        $esUnaSolaImagen = count($imagenes) === 1;
        $total = $esUnaSolaImagen ? $cantidad : count($imagenes);

        for ($i = 0; $i < $total; $i++) {
            $imagen = $esUnaSolaImagen ? $imagenes[0] : $imagenes[$i];
            $ruta = public_path('storage/uploads/' . $imagen);
            if (!file_exists($ruta))
                continue;

            // Crear nueva página si hace falta
            if ($i > 0 && $i % $imagenesPorPagina === 0) {
                $pdf->AddPage();
                $pdf->useTemplate($templateId);
            }

            $indexEnPagina = $i % $imagenesPorPagina;
            $col = $indexEnPagina % $columnas;
            $fila = floor($indexEnPagina / $columnas);

            $x = $margenIzquierdo + ($col * ($anchoImagen + $espaciadoX));
            $y = $margenSuperior + ($fila * ($altoImagen + $espaciadoY));

            $pdf->Image($ruta, $x, $y, $anchoImagen, $altoImagen);
        }


        return response($pdf->Output('S'))
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'attachment; filename="carrito-' . $carrito->id . '.pdf"');
    }
}
