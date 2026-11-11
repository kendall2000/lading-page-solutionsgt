<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ConfiguracionDTE;
use App\Models\Cotizacion;
use App\Models\DocumentoTributario;
use App\Models\DocumentoTributarioItem;
use App\Models\Producto;
use App\Models\Sistema;
use App\Services\ConsultaNitService;
use App\Services\DTEService;
use App\Services\QrService;
use App\Support\Imagenes;
use App\Support\Sitio;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use RuntimeException;

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
        $documento->loadMissing(['items', 'logs', 'origen', 'complementarios']);

        return view('admin.facturas.show', [
            'doc' => $documento,
            'cfgDte' => ConfiguracionDTE::actual(),
            'saldo' => $documento->esFactura() ? $documento->saldoAcreditable() : 0.0,
        ]);
    }

    /**
     * Reintentar o certificar la factura ante INFILE / SAT.
     */
    public function certificarSat(DocumentoTributario $documento, DTEService $dteService): RedirectResponse
    {
        try {
            $resultado = $dteService->certificar($documento);
        } catch (RuntimeException $e) {
            return back()->with('error_sat', $e->getMessage());
        }

        $nombre = $documento->nombreTipo();

        if ($resultado['exito']) {
            return back()->with('exito', "{$nombre} certificada exitosamente ante la SAT (UUID: {$resultado['uuid']}).");
        }

        return back()->with('error_sat', "INFILE / SAT rechazó el documento: {$resultado['error']}");
    }

    /**
     * Anula ante la SAT un documento certificado.
     */
    public function anular(Request $request, DocumentoTributario $documento, DTEService $dteService): RedirectResponse
    {
        $datos = $request->validate([
            'motivo' => 'required|string|min:5|max:255',
        ], [], ['motivo' => 'motivo de anulación']);

        try {
            $dteService->anular($documento, $datos['motivo']);
        } catch (RuntimeException $e) {
            return back()->with('error_sat', 'No se pudo anular: ' . $e->getMessage());
        }

        return back()->with('exito', "{$documento->nombreTipo()} {$documento->numero_interno} anulada ante la SAT.");
    }

    /**
     * Emite una nota de crédito, débito o abono sobre una factura certificada.
     */
    public function emitirNota(Request $request, DocumentoTributario $documento, DTEService $dteService): RedirectResponse
    {
        $datos = $request->validate([
            'tipo' => ['required', Rule::in(array_keys(DocumentoTributario::NOTAS))],
            'monto' => 'required|numeric|min:0.01|max:9999999999',
            'motivo' => 'required|string|min:5|max:255',
            'descripcion' => 'nullable|string|max:500',
        ], [], ['motivo' => 'motivo del ajuste', 'descripcion' => 'descripción']);

        try {
            $resultado = $dteService->emitirNota(
                $documento,
                $datos['tipo'],
                (float) $datos['monto'],
                $datos['motivo'],
                $datos['descripcion'] ?? null
            );
        } catch (RuntimeException $e) {
            return back()->withInput()->with('error_sat', $e->getMessage());
        }

        $nota = $resultado['nota'];

        if ($resultado['exito']) {
            return redirect()->route('admin.facturas.show', $nota)
                ->with('exito', "{$nota->nombreTipo()} {$nota->numero_interno} certificada ante la SAT (UUID: {$resultado['uuid']}).");
        }

        return redirect()->route('admin.facturas.show', $nota)
            ->with('error_sat', "La nota quedó guardada, pero INFILE / SAT la rechazó: {$resultado['error']}");
    }

    /**
     * Muestra o descarga el XML guardado: generado, firmado, certificado o de anulación.
     */
    public function xml(Request $request, DocumentoTributario $documento, string $cual): Response
    {
        $contenido = match ($cual) {
            'generado' => $documento->xml_generado,
            'firmado' => $documento->xml_firmado,
            'certificado' => $documento->xml_certificado,
            'anulacion' => $documento->xml_anulacion,
            default => null,
        };

        abort_if(empty($contenido), 404);

        // INFILE puede devolver el XML certificado en base64
        if ($cual === 'certificado' && !str_starts_with(ltrim($contenido), '<')) {
            $decodificado = base64_decode($contenido, true);
            if ($decodificado !== false && str_starts_with(ltrim($decodificado), '<')) {
                $contenido = $decodificado;
            }
        }

        $archivo = "{$documento->tipo}-{$documento->numero_interno}-{$cual}.xml";
        $disposicion = $request->boolean('descargar') ? 'attachment' : 'inline';

        return response($contenido, 200, [
            'Content-Type' => 'application/xml; charset=UTF-8',
            'Content-Disposition' => "{$disposicion}; filename=\"{$archivo}\"",
            'X-Content-Type-Options' => 'nosniff',
        ]);
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
        return view('facturas.documento_imprimir', $this->datosDocumento($request, $documento));
    }

    /**
     * Descarga la factura en PDF.
     */
    public function descargarPdf(Request $request, DocumentoTributario $documento): Response
    {
        $datos = $this->datosDocumento($request, $documento);
        $formato = $datos['formato'];

        // Ticket de 80 mm (226.77 pt) con alto según las líneas, para que no se corte
        $papel = $formato === 'ticket'
            ? [0, 0, 226.77, 430 + 34 * max(1, $documento->items->count()) + ($documento->uuid_sat ? 70 : 0) + ($documento->notas ? 30 : 0)]
            : 'letter';

        $pdf = Pdf::loadView('facturas.documento_pdf', $datos)
        ->setPaper($papel, 'portrait')
        ->setOption([
            'isHtml5ParserEnabled' => true,
            'isRemoteEnabled' => false,
            'defaultFont' => 'DejaVu Sans',
        ]);

        $nombre = $formato === 'ticket' ? 'Ticket' : str_replace(' ', '-', ucfirst($documento->nombreTipo()));

        return $pdf->download($nombre . '-' . $documento->numero_interno . '.pdf');
    }

    /**
     * Datos de la plantilla facturas/partes/documento (pantalla y PDF).
     * Documento certificado: el QR lleva al verificador oficial de la SAT (como sistema-inventario);
     * sin certificar: a la verificación pública propia.
     */
    private function datosDocumento(Request $request, DocumentoTributario $documento): array
    {
        $documento->loadMissing(['items', 'origen']);
        $documento->asegurarToken();
        $cfgDte = ConfiguracionDTE::actual();
        $sitio = Sitio::config();

        $formato = in_array($request->query('formato'), ['ticket', 'factura'], true)
            ? $request->query('formato')
            : 'factura';

        $urlQr = $documento->uuid_sat
            ? 'https://felpub.c.sat.gob.gt/verificador-web/publico/vistas/verificacionDte.jsf?' . http_build_query([
                'tipo' => 'autorizacion',
                'numero' => $documento->uuid_sat,
                'emisor' => $cfgDte->nit_emisor,
                'receptor' => $documento->nit_receptor ?: 'CF',
                'monto' => number_format((float) $documento->total, 2, '.', ''),
            ])
            : route('comprobantes.documento.verificar', ['token' => $documento->token_publico]);

        return [
            'doc' => $documento,
            'formato' => $formato,
            'qrUri' => QrService::generarDataUri($urlQr, 180),
            'sitio' => $sitio,
            'logoUri' => $this->logoDataUri($sitio),
            'cfgDte' => $cfgDte,
        ];
    }

    /**
     * Logo del sitio incrustado como data URI: DomPDF no siempre puede descargar
     * imágenes remotas (Contabo o el dominio local con certificado propio).
     */
    private function logoDataUri(mixed $sitio): ?string
    {
        $ruta = $sitio->logo ?? null;
        if (blank($ruta)) {
            return null;
        }

        $contenido = rescue(function () use ($ruta) {
            if (str_starts_with($ruta, 'http')) {
                return Http::timeout(5)->get($ruta)->throw()->body();
            }
            if (str_starts_with($ruta, 'assets/')) {
                return is_file(public_path($ruta)) ? file_get_contents(public_path($ruta)) : null;
            }

            return Storage::disk(Imagenes::DISCO)->get($ruta);
        }, null, false);

        if (! $contenido) {
            return null;
        }

        $tipo = (new \finfo(FILEINFO_MIME_TYPE))->buffer($contenido) ?: 'image/png';
        // DomPDF no dibuja WEBP de forma fiable
        if (! in_array($tipo, ['image/png', 'image/jpeg', 'image/gif', 'image/svg+xml'], true)) {
            return null;
        }

        return 'data:' . $tipo . ';base64,' . base64_encode($contenido);
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
