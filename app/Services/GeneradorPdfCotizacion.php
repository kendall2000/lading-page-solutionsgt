<?php

namespace App\Services;

use App\Models\Cotizacion;
use Barryvdh\DomPDF\Facade\Pdf;

class GeneradorPdfCotizacion
{
    /**
     * Genera el archivo PDF a partir de una cotización usando DomPDF nativo en PHP.
     * Devuelve los bytes del PDF.
     */
    public static function generar(Cotizacion $cotizacion): string
    {
        $pdf = Pdf::loadView('cotizaciones.pdf', ['cotizacion' => $cotizacion])
            ->setPaper('letter', 'portrait')
            ->setOption([
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => true,
                'defaultFont' => 'DejaVu Sans',
            ]);

        return $pdf->output();
    }
}
