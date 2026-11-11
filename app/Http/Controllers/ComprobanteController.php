<?php

namespace App\Http\Controllers;

use App\Models\Pago;
use App\Services\QrService;
use App\Support\Sitio;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class ComprobanteController extends Controller
{
    /**
     * Muestra la vista de impresión en pantalla (A4 o Ticket).
     */
    public function imprimir(Request $request, Pago $pago): View
    {
        $pago->loadMissing(['cuenta', 'producto']);
        $pago->asegurarComprobante();

        $formato = in_array($request->query('formato'), ['ticket', 'factura'], true)
            ? $request->query('formato')
            : 'factura';

        $urlVerificar = route('comprobantes.verificar', ['token' => $pago->token_publico]);
        $qrUri = QrService::generarDataUri($urlVerificar, 180);
        $sitio = Sitio::config();

        return view('facturas.imprimir', compact('pago', 'formato', 'qrUri', 'sitio'));
    }

    /**
     * Descarga el documento compilado en PDF.
     */
    public function descargarPdf(Request $request, Pago $pago): Response
    {
        $pago->loadMissing(['cuenta', 'producto']);
        $pago->asegurarComprobante();

        $formato = in_array($request->query('formato'), ['ticket', 'factura'], true)
            ? $request->query('formato')
            : 'factura';

        $urlVerificar = route('comprobantes.verificar', ['token' => $pago->token_publico]);
        $qrUri = QrService::generarDataUri($urlVerificar, 180);
        $sitio = Sitio::config();

        $papel = $formato === 'ticket' ? [0, 0, 226.77, 600] : 'letter'; // 80mm aprox = 226.77 pt
        $orientacion = 'portrait';

        $pdf = Pdf::loadView('facturas.imprimir', [
            'pago' => $pago,
            'formato' => $formato,
            'qrUri' => $qrUri,
            'sitio' => $sitio,
        ])
        ->setPaper($papel, $orientacion)
        ->setOption([
            'isHtml5ParserEnabled' => true,
            'isRemoteEnabled' => true,
            'defaultFont' => 'DejaVu Sans',
        ]);

        $nombreArchivo = ($formato === 'ticket' ? 'Ticket-' : 'Factura-') . $pago->numero_comprobante . '.pdf';
        return $pdf->download($nombreArchivo);
    }

    /**
     * Vista pública que se abre al escanear el código QR.
     */
    public function verificar(string $token): View
    {
        $pago = Pago::where('token_publico', $token)->firstOrFail();
        $pago->loadMissing(['cuenta', 'producto']);
        $sitio = Sitio::config();

        return view('facturas.verificar', compact('pago', 'sitio'));
    }
}
