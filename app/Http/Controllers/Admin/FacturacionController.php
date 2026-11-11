<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ConfiguracionDTE;
use App\Models\DocumentoTributario;
use App\Models\LogDTE;
use App\Models\Pago;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FacturacionController extends Controller
{
    /**
     * Listado general de facturas, comprobantes y estado de certificación.
     */
    public function index(Request $request): View
    {
        $pestana = in_array($request->query('ver'), ['comprobantes', 'dte', 'logs', 'configuracion'], true)
            ? $request->query('ver')
            : 'comprobantes';

        $buscar = trim((string) $request->query('q'));
        $filtroTexto = fn ($q) => $q->when($buscar !== '', fn ($q) => $q->where(fn ($q) =>
            $q->where('correo', 'like', "%{$buscar}%")
              ->orWhere('descripcion', 'like', "%{$buscar}%")
              ->orWhere('numero_comprobante', 'like', "%{$buscar}%")
              ->orWhere('paypal_id', 'like', "%{$buscar}%")
        ));

        $pagos = Pago::query()
            ->with(['cuenta', 'producto'])
            ->tap($filtroTexto)
            ->orderByDesc('pagado_en')
            ->orderByDesc('id')
            ->paginate(25, ['*'], 'pag_c')
            ->withQueryString();

        $dtes = DocumentoTributario::query()
            ->with(['pago', 'cuenta'])
            ->orderByDesc('id')
            ->paginate(25, ['*'], 'pag_d')
            ->withQueryString();

        $logs = LogDTE::query()
            ->with('documento')
            ->orderByDesc('id')
            ->paginate(25, ['*'], 'pag_l')
            ->withQueryString();

        $cfgDte = ConfiguracionDTE::actual();

        return view('admin.facturacion.index', [
            'pestana' => $pestana,
            'pagos' => $pagos,
            'dtes' => $dtes,
            'logs' => $logs,
            'cfgDte' => $cfgDte,
            'buscar' => $buscar,
            'conteos' => [
                'total_pagos' => Pago::count(),
                'facturados' => Pago::whereNotNull('numero_comprobante')->count(),
                'dtes_certificados' => DocumentoTributario::where('estado', 'certificado')->count(),
                'total_logs' => LogDTE::count(),
                'logs_error' => LogDTE::where('exito', false)->count(),
            ],
        ]);
    }

    /**
     * Guarda la configuración de certificación INFILE (DTE / SAT) con frases y establecimientos.
     */
    public function guardarConfiguracion(Request $request): RedirectResponse
    {
        $datos = $request->validate([
            'activo' => 'nullable|boolean',
            'ambiente' => 'required|in:pruebas,produccion',
            'nit_emisor' => 'required|string|max:25',
            'nombre_emisor' => 'required|string|max:200',
            'nombre_comercial' => 'required|string|max:200',
            'correo_emisor' => 'nullable|email|max:150',
            'direccion' => 'required|string|max:255',
            'codigo_postal' => 'required|string|max:15',
            'municipio' => 'required|string|max:100',
            'departamento' => 'required|string|max:100',
            'codigo_establecimiento' => 'required|string|max:10',
            'afiliacion_iva' => 'required|in:GEN,PEQ,EXE',
            'alias_ws' => 'nullable|string|max:100',
            'token_signer' => 'nullable|string|max:255',
            'llave_ws' => 'nullable|string|max:255',
            'url_firma' => 'required|url|max:255',
            'url_certificacion' => 'required|url|max:255',
            'url_anulacion' => 'required|url|max:255',
            'permite_anulacion_directa' => 'nullable|boolean',
            'habilitar_notas_credito' => 'nullable|boolean',
            'habilitar_notas_debito' => 'nullable|boolean',
            'habilitar_notas_abono' => 'nullable|boolean',
            'frases' => 'nullable|array',
            'frases.*.tipo_frase' => 'required_with:frases|string|max:10',
            'frases.*.codigo_escenario' => 'required_with:frases|string|max:10',
            'frases.*.descripcion' => 'nullable|string|max:255',
        ]);

        $datos['activo'] = $request->boolean('activo');
        $datos['permite_anulacion_directa'] = $request->boolean('permite_anulacion_directa');
        $datos['habilitar_notas_credito'] = $request->boolean('habilitar_notas_credito');
        $datos['habilitar_notas_debito'] = $request->boolean('habilitar_notas_debito');
        $datos['habilitar_notas_abono'] = $request->boolean('habilitar_notas_abono');

        // Procesar frases configuradas
        $frasesProcesadas = [];
        if (!empty($request->input('frases')) && is_array($request->input('frases'))) {
            foreach ($request->input('frases') as $f) {
                if (!empty($f['tipo_frase']) && !empty($f['codigo_escenario'])) {
                    $frasesProcesadas[] = [
                        'tipo_frase' => trim($f['tipo_frase']),
                        'codigo_escenario' => trim($f['codigo_escenario']),
                        'descripcion' => trim($f['descripcion'] ?? ''),
                    ];
                }
            }
        }
        $datos['frases_config'] = $frasesProcesadas;

        $cfg = ConfiguracionDTE::actual();
        $cfg->update($datos);

        return back()->with('exito', 'Configuración de facturación electrónica (INFILE), frases SAT y establecimientos guardada exitosamente.');
    }
}
