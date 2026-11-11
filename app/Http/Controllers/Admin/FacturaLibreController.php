<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Cotizacion;
use App\Models\DocumentoTributario;
use App\Models\DocumentoTributarioItem;
use App\Models\Producto;
use App\Models\Sistema;
use App\Services\ConsultaNitService;
use App\Services\QrService;
use App\Support\Sitio;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class FacturaLibreController extends Controller
{
    /**
     * Listado del módulo "Emitir factura".
     */
    public function index(): View
    {
        $facturas = DocumentoTributario::query()
            ->with('items')
            ->whereNull('pago_id')
            ->orderByDesc('id')
            ->paginate(20);

        return view('admin.facturas.index', compact('facturas'));
    }

    /**
     * Muestra el detalle de la factura y los ítems/conceptos consumidos.
     */
    public function show(DocumentoTributario $documento): View
    {
        $documento->loadMissing('items');
        return view('admin.facturas.show', ['doc' => $documento]);
    }

    /**
     * Formulario moderno para emitir factura:
     * - Puede cargar desde una cotización existente (?cotizacion=ID)
     * - Tiene catálogo de sistemas y productos para seleccionar con un clic
     * - Búsqueda automática de NIT por API SAT / INFILE
     */
    public function create(Request $request): View
    {
        $sistemas = Sistema::where('visible', true)->orderBy('nombre')->get();
        $productos = Producto::where('activo', true)->orderBy('nombre')->get();

        $cotizacionOrigen = null;
        if ($request->filled('cotizacion')) {
            $cotizacionOrigen = Cotizacion::with('items')->find($request->query('cotizacion'));
        }

        return view('admin.facturas.create', compact('sistemas', 'productos', 'cotizacionOrigen'));
    }

    /**
     * Endpoint AJAX para consultar NIT en tiempo real.
     */
    public function consultarNit(Request $request): JsonResponse
    {
        $nit = (string) $request->query('nit');
        $resultado = ConsultaNitService::consultar($nit);
        return response()->json($resultado);
    }

    /**
     * Guarda la factura y sus ítems detallados.
     */
    public function store(Request $request): RedirectResponse
    {
        $datos = $request->validate([
            'nit_receptor' => 'required|string|max:50',
            'nombre_receptor' => 'required|string|max:200',
            'correo_receptor' => 'nullable|email|max:150',
            'direccion_receptor' => 'nullable|string|max:255',
            'moneda' => 'required|in:USD,GTQ',
            'condicion_pago' => 'required|string|max:30',
            'metodo_pago' => 'required|string|max:50',
            'notas' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.tipo' => 'required|in:B,S',
            'items.*.descripcion' => 'required|string',
            'items.*.cantidad' => 'required|numeric|min:0.01',
            'items.*.precio_unitario' => 'required|numeric|min:0',
        ]);

        return DB::transaction(function () use ($datos) {
            $subtotal = 0;
            $itemsPreparados = [];

            foreach ($datos['items'] as $index => $item) {
                $cant = (float) $item['cantidad'];
                $pu = (float) $item['precio_unitario'];
                $lineaTotal = round($cant * $pu, 2);
                $subtotal += $lineaTotal;

                $itemsPreparados[] = [
                    'numero_linea' => $index + 1,
                    'tipo' => $item['tipo'],
                    'descripcion' => trim($item['descripcion']),
                    'cantidad' => $cant,
                    'unidad_medida' => 'UND',
                    'precio_unitario' => $pu,
                    'descuento' => 0.00,
                    'subtotal' => $lineaTotal,
                ];
            }

            $doc = DocumentoTributario::create([
                'tipo' => 'FACT',
                'numero_interno' => DocumentoTributario::generarNumeroInterno(),
                'moneda' => $datos['moneda'],
                'condicion_pago' => $datos['condicion_pago'],
                'metodo_pago' => $datos['metodo_pago'],
                'subtotal' => $subtotal,
                'impuestos' => 0.00,
                'total' => $subtotal,
                'nit_receptor' => trim(strtoupper($datos['nit_receptor'])),
                'nombre_receptor' => trim($datos['nombre_receptor']),
                'correo_receptor' => $datos['correo_receptor'] ? trim($datos['correo_receptor']) : null,
                'direccion_receptor' => $datos['direccion_receptor'] ? trim($datos['direccion_receptor']) : 'Ciudad',
                'estado' => 'emitido',
                'fecha_emision' => now(),
                'notas' => $datos['notas'] ?? null,
            ]);

            $doc->asegurarToken();

            foreach ($itemsPreparados as $ip) {
                $doc->items()->create($ip);
            }

            return redirect()->route('admin.facturas.show', $doc)
                ->with('venta_exitosa', [
                    'id' => $doc->id,
                    'numero' => $doc->numero_interno,
                    'total' => $doc->total,
                    'moneda' => $doc->moneda,
                ]);
        });
    }

    /**
     * Muestra la vista imprimible de la factura en pantalla (A4 o Ticket).
     */
    public function imprimir(Request $request, DocumentoTributario $documento): View
    {
        $documento->loadMissing('items');
        $documento->asegurarToken();

        $formato = in_array($request->query('formato'), ['ticket', 'factura'], true)
            ? $request->query('formato')
            : 'factura';

        $urlVerificar = route('comprobantes.documento.verificar', ['token' => $documento->token_publico]);
        $qrUri = QrService::generarDataUri($urlVerificar, 180);
        $sitio = Sitio::config();

        return view('facturas.documento_imprimir', [
            'doc' => $documento,
            'formato' => $formato,
            'qrUri' => $qrUri,
            'sitio' => $sitio,
        ]);
    }

    /**
     * Descarga la factura en PDF.
     */
    public function descargarPdf(Request $request, DocumentoTributario $documento): Response
    {
        $documento->loadMissing('items');
        $documento->asegurarToken();

        $formato = in_array($request->query('formato'), ['ticket', 'factura'], true)
            ? $request->query('formato')
            : 'factura';

        $urlVerificar = route('comprobantes.documento.verificar', ['token' => $documento->token_publico]);
        $qrUri = QrService::generarDataUri($urlVerificar, 180);
        $sitio = Sitio::config();

        $papel = $formato === 'ticket' ? [0, 0, 226.77, 650] : 'letter';

        $pdf = Pdf::loadView('facturas.documento_imprimir', [
            'doc' => $documento,
            'formato' => $formato,
            'qrUri' => $qrUri,
            'sitio' => $sitio,
        ])
        ->setPaper($papel, 'portrait')
        ->setOption([
            'isHtml5ParserEnabled' => true,
            'isRemoteEnabled' => true,
            'defaultFont' => 'DejaVu Sans',
        ]);

        return $pdf->download(($formato === 'ticket' ? 'Ticket-' : 'Factura-') . $documento->numero_interno . '.pdf');
    }

    /**
     * Validación pública del código QR para facturas manuales.
     */
    public function verificar(string $token): View
    {
        $doc = DocumentoTributario::where('token_publico', $token)->with('items')->firstOrFail();
        $sitio = Sitio::config();

        return view('facturas.verificar_documento', compact('doc', 'sitio'));
    }
}
