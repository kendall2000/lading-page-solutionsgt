<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ConfiguracionDTE;
use App\Models\DocumentoTributario;
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
        $pestana = in_array($request->query('ver'), ['comprobantes', 'dte', 'configuracion'], true)
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

        $cfgDte = ConfiguracionDTE::actual();

        return view('admin.facturacion.index', [
            'pestana' => $pestana,
            'pagos' => $pagos,
            'dtes' => $dtes,
            'cfgDte' => $cfgDte,
            'buscar' => $buscar,
            'conteos' => [
                'total_pagos' => Pago::count(),
                'facturados' => Pago::whereNotNull('numero_comprobante')->count(),
                'dtes_certificados' => DocumentoTributario::where('estado', 'certificado')->count(),
            ],
        ]);
    }

    /**
     * Guarda la configuración de certificación INFILE (DTE / SAT).
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
            'afiliacion_iva' => 'required|in:GEN,PEQ,EXE',
            'alias_ws' => 'nullable|string|max:100',
            'token_signer' => 'nullable|string|max:255',
            'llave_ws' => 'nullable|string|max:255',
            'url_firma' => 'required|url|max:255',
            'url_certificacion' => 'required|url|max:255',
            'url_anulacion' => 'required|url|max:255',
        ]);

        $datos['activo'] = $request->boolean('activo');

        $cfg = ConfiguracionDTE::actual();
        $cfg->update($datos);

        return back()->with('exito', 'Configuración de facturación electrónica (INFILE) actualizada correctamente.');
    }
}
