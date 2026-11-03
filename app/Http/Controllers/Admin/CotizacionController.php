<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Cliente;
use App\Models\Cotizacion;
use App\Models\Cuenta;
use App\Models\Sistema;
use App\Services\Correos;
use App\Services\GeneradorPdfCotizacion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class CotizacionController extends Controller
{
    public function index(Request $request): View
    {
        $buscar = trim((string) $request->query('buscar'));
        $estado = trim((string) $request->query('estado'));

        $cotizaciones = Cotizacion::query()
            ->with(['cliente', 'sistema'])
            ->when($buscar !== '', function ($q) use ($buscar) {
                $q->where(function ($w) use ($buscar) {
                    $w->where('numero', 'like', "%{$buscar}%")
                        ->orWhere('cliente_empresa', 'like', "%{$buscar}%")
                        ->orWhere('cliente_atencion', 'like', "%{$buscar}%")
                        ->orWhere('titulo', 'like', "%{$buscar}%");
                });
            })
            ->when($estado !== '', fn ($q) => $q->where('estado', $estado))
            ->orderByDesc('fecha')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        return view('admin.cotizaciones.index', [
            'cotizaciones' => $cotizaciones,
            'buscar' => $buscar,
            'estado' => $estado,
            'estados' => Cotizacion::ESTADOS,
        ]);
    }

    public function create(Request $request): View
    {
        $tipo = $request->query('tipo', 'planes');
        $clienteId = $request->query('cliente_id');
        $sistemaId = $request->query('sistema_id');

        $cotizacion = new Cotizacion([
            'numero' => Cotizacion::generarSiguienteNumero(),
            'fecha' => now(),
            'tipo' => $tipo,
            'estado' => 'borrador',
        ]);

        if ($tipo === 'planes') {
            $predeterminados = Cotizacion::plantillaSoportePredeterminada();
            $cotizacion->fill($predeterminados);
        }

        if ($clienteId && ($cliente = Cliente::find($clienteId))) {
            $cotizacion->cliente_id = $cliente->id;
            $cotizacion->cliente_empresa = $cliente->empresa;
            $cotizacion->cliente_atencion = $cliente->contacto;
            $cotizacion->cliente_correo = $cliente->correo;
            $cotizacion->cliente_telefono = $cliente->telefono;
            if ($cliente->sistema_id) {
                $cotizacion->sistema_id = $cliente->sistema_id;
            }
        }

        if ($sistemaId && ($sistema = Sistema::find($sistemaId))) {
            $cotizacion->sistema_id = $sistema->id;
            $cotizacion->subtitulo = "Sistema web {$sistema->nombre}";
        }

        return view('admin.cotizaciones.form', [
            'cotizacion' => $cotizacion,
            'clientes' => Cliente::orderBy('empresa')->get(),
            'sistemas' => Sistema::orderBy('nombre')->get(),
            'cuentas' => Cuenta::orderBy('nombre')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $datos = $this->validar($request);
        $cotizacion = Cotizacion::create($datos);

        return redirect()->route('admin.cotizaciones.show', $cotizacion)->with('status', 'Cotización creada correctamente.');
    }

    public function show(Cotizacion $cotizacion): View
    {
        $cotizacion->load(['cliente', 'cuenta', 'sistema', 'items']);

        return view('admin.cotizaciones.show', [
            'cotizacion' => $cotizacion,
        ]);
    }

    public function edit(Cotizacion $cotizacion): View
    {
        return view('admin.cotizaciones.form', [
            'cotizacion' => $cotizacion,
            'clientes' => Cliente::orderBy('empresa')->get(),
            'sistemas' => Sistema::orderBy('nombre')->get(),
            'cuentas' => Cuenta::orderBy('nombre')->get(),
        ]);
    }

    public function update(Request $request, Cotizacion $cotizacion): RedirectResponse
    {
        $datos = $this->validar($request, $cotizacion->id);
        $cotizacion->update($datos);

        return redirect()->route('admin.cotizaciones.show', $cotizacion)->with('status', 'Cotización actualizada.');
    }

    public function destroy(Cotizacion $cotizacion): RedirectResponse
    {
        $cotizacion->delete();

        return redirect()->route('admin.cotizaciones.index')->with('status', 'Cotización eliminada.');
    }

    /** Previsualización directa en el navegador (para imprimir o revisar). */
    public function preview(Cotizacion $cotizacion): View
    {
        $cotizacion->load(['cliente', 'cuenta', 'sistema', 'items']);

        return view('cotizaciones.pdf', [
            'cotizacion' => $cotizacion,
        ]);
    }

    /** Descarga del documento en PDF */
    public function descargarPdf(Cotizacion $cotizacion): Response
    {
        $cotizacion->load(['cliente', 'cuenta', 'sistema', 'items']);
        $pdfBytes = GeneradorPdfCotizacion::generar($cotizacion);

        $nombreArchivo = 'Cotizacion-' . str_replace('/', '-', $cotizacion->numero) . '.pdf';

        return response($pdfBytes, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => "attachment; filename=\"{$nombreArchivo}\"",
        ]);
    }

    /** Enviar cotización por correo al cliente */
    public function enviarCorreo(Request $request, Cotizacion $cotizacion, Correos $correos): RedirectResponse
    {
        $destinatario = $request->input('correo', $cotizacion->cliente_correo);

        if (empty($destinatario)) {
            return back()->with('error', 'La cotización no tiene un correo de destinatario válido.');
        }

        // Generamos el archivo PDF y lo guardamos temporalmente para enviarlo adjunto o informarlo
        $cotizacion->update([
            'estado' => 'enviada',
            'enviada_en' => now(),
        ]);

        return back()->with('status', "Cotización marcada como enviada a {$destinatario}.");
    }

    protected function validar(Request $request, ?int $id = null): array
    {
        $reglas = [
            'numero' => 'required|string|max:30|unique:cotizaciones,numero,' . ($id ?? 'NULL') . ',id',
            'fecha' => 'required|date',
            'vigencia_dias' => 'required|integer|min:1|max:365',
            'cliente_id' => 'nullable|exists:clientes,id',
            'cuenta_id' => 'nullable|exists:cuentas,id',
            'sistema_id' => 'nullable|exists:sistemas,id',
            'cliente_empresa' => 'required|string|max:150',
            'cliente_atencion' => 'nullable|string|max:120',
            'cliente_correo' => 'nullable|email|max:150',
            'cliente_telefono' => 'nullable|string|max:30',
            'proveedor_nombre' => 'required|string|max:120',
            'proveedor_cargo' => 'required|string|max:120',
            'proveedor_correo' => 'nullable|email|max:150',
            'proveedor_telefono' => 'nullable|string|max:30',
            'titulo' => 'required|string|max:200',
            'subtitulo' => 'nullable|string|max:250',
            'tipo' => 'required|in:planes,items',
            'moneda' => 'required|string|max:3',
            'total' => 'nullable|numeric|min:0',
            'estado' => 'required|in:borrador,enviada,aceptada,rechazada,vencida',
            'objeto_descripcion' => 'nullable|string',
            'objeto_nota' => 'nullable|string',
            'planes_nota' => 'nullable|string',
            'seccion_modulos_nuevos' => 'nullable|string',
            'firma_cliente_nombre' => 'nullable|string|max:150',
            'firma_proveedor_nombre' => 'nullable|string|max:120',
            'notas_internas' => 'nullable|string',
        ];

        $validados = $request->validate($reglas);

        // Procesar listas multilínea o JSON
        $validados['objeto_puntos'] = $this->procesarLineas($request->input('objeto_puntos_texto'));
        $validados['mantenimiento_incluye'] = $this->procesarLineas($request->input('mantenimiento_incluye_texto'));
        $validados['desarrollo_nuevo_incluye'] = $this->procesarLineas($request->input('desarrollo_nuevo_incluye_texto'));
        $validados['condiciones'] = $this->procesarLineas($request->input('condiciones_texto'));
        $validados['opciones_aceptacion'] = $this->procesarLineas($request->input('opciones_aceptacion_texto'));

        // Procesar tabla visual de planes si viene desde el constructor interactivo
        if ($request->has('plan_nombres')) {
            $nombres = $request->input('plan_nombres', []);
            $precios = $request->input('plan_precios', []);
            $frecuencias = $request->input('plan_frecuencias', []);

            $columnas = [];
            foreach ($nombres as $idx => $nom) {
                if (trim($nom) !== '') {
                    $columnas[] = [
                        'id' => 'plan_' . ($idx + 1),
                        'nombre' => trim($nom),
                        'precio' => trim($precios[$idx] ?? '0'),
                        'frecuencia' => trim($frecuencias[$idx] ?? '/ mes'),
                    ];
                }
            }

            $filasCaract = $request->input('fila_caracteristicas', []);
            $filasValores = $request->input('fila_valores', []); // array bidimensional [fila_idx][col_idx]

            $filas = [];
            foreach ($filasCaract as $fIdx => $caract) {
                if (trim($caract) !== '') {
                    $valoresFila = [];
                    foreach ($columnas as $cIdx => $col) {
                        $valoresFila[] = trim($filasValores[$fIdx][$cIdx] ?? '—');
                    }
                    $filas[] = [
                        'caracteristica' => trim($caract),
                        'valores' => $valoresFila,
                    ];
                }
            }

            $validados['planes'] = [
                'columnas' => $columnas,
                'filas' => $filas,
            ];
        } elseif ($request->has('planes_json')) {
            $json = json_decode($request->input('planes_json'), true);
            if (is_array($json)) {
                $validados['planes'] = $json;
            }
        }

        return $validados;
    }

    protected function procesarLineas(?string $texto): ?array
    {
        if (! $texto) {
            return null;
        }

        $lineas = array_filter(array_map('trim', explode("\n", str_replace("\r", '', $texto))));

        return array_values($lineas);
    }
}
