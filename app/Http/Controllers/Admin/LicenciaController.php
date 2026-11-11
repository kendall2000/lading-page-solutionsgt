<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ConfiguracionLicencias;
use App\Models\Contrato;
use App\Models\Instalacion;
use App\Models\PagoLicencia;
use App\Services\Licencias;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use RuntimeException;

/**
 * Licencias Nexus: instalaciones de Nexus ERP de cada cliente, su vencimiento, pagos a mano, prórrogas,
 * suspensión y la conexión con cada instalación (token, clave pública y aviso «consulta ya»).
 */
class LicenciaController extends Controller
{
    public const FILTROS = ['todas' => 'Todas', 'por_vencer' => 'Por vencer', 'mora' => 'En mora', 'sin_comunicacion' => 'Sin comunicación'];

    public function __construct(private Licencias $licencias) {}

    public function index(Request $request): View
    {
        $filtro = array_key_exists($request->query('filtro'), self::FILTROS) ? $request->query('filtro') : 'todas';
        $todas = Instalacion::query()->with('contrato')->orderBy('nombre')->get()
            ->map(fn (Instalacion $i) => ['i' => $i, 'estado' => $i->estado()]);
        $conteo = [
            'todas' => $todas->count(),
            'por_vencer' => $todas->where('estado.etapa', 'POR_VENCER')->count(),
            'mora' => $todas->whereIn('estado.etapa', ['GRACIA', 'RESTRINGIDO', 'SOLO_LECTURA'])->count(),
            'sin_comunicacion' => $todas->filter(fn ($f) => $f['i']->sinComunicacion())->count(),
        ];

        return view('admin.licencias.index', [
            'filas' => match ($filtro) {
                'por_vencer' => $todas->where('estado.etapa', 'POR_VENCER'),
                'mora' => $todas->whereIn('estado.etapa', ['GRACIA', 'RESTRINGIDO', 'SOLO_LECTURA']),
                'sin_comunicacion' => $todas->filter(fn ($f) => $f['i']->sinComunicacion()),
                default => $todas,
            },
            'filtro' => $filtro,
            'conteo' => $conteo,
            'cfgLic' => ConfiguracionLicencias::actual(),
        ]);
    }

    public function create(): View
    {
        $cfg = ConfiguracionLicencias::actual();

        return view('admin.licencias.form', [
            'instalacion' => new Instalacion(['tipo' => 'renta', 'moneda' => 'GTQ', 'meses_periodo' => 1, 'modo' => 'NORMAL']),
            'contratos' => $this->contratos(), 'cfgLic' => $cfg,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $instalacion = Instalacion::query()->create($this->validar($request));
        $token = $this->licencias->nuevoToken($instalacion);

        return redirect()->route('admin.licencias.show', $instalacion)
            ->with('status', 'Instalación creada. Copia ya su configuración: el token no se vuelve a mostrar.')
            ->with('token_licencia', $token);
    }

    public function show(Instalacion $licencia): View
    {
        $licencia->load(['contrato.sistema', 'contrato.cuenta', 'contrato.cliente', 'pagos.usuario']);

        return view('admin.licencias.show', [
            'i' => $licencia,
            'estado' => $licencia->estado(),
            'cfgLic' => ConfiguracionLicencias::actual(),
            'token' => session('token_licencia'),
        ]);
    }

    public function edit(Instalacion $licencia): View
    {
        return view('admin.licencias.form', ['instalacion' => $licencia, 'contratos' => $this->contratos(), 'cfgLic' => ConfiguracionLicencias::actual()]);
    }

    public function update(Request $request, Instalacion $licencia): RedirectResponse
    {
        $licencia->update($this->validar($request, $licencia));
        $avisada = $this->licencias->avisar($licencia->refresh());

        return redirect()->route('admin.licencias.show', $licencia)->with('status', 'Instalación guardada.'.($avisada ? ' La instalación ya aplicó los cambios.' : ''));
    }

    public function destroy(Instalacion $licencia): RedirectResponse
    {
        $licencia->delete();

        return redirect()->route('admin.licencias.index')->with('status', 'Instalación eliminada. Si sigue encendida, conserva lo último que supo.');
    }

    // ── Acciones ────────────────────────────────────────────────────────────

    public function pago(Request $request, Instalacion $licencia): RedirectResponse
    {
        $datos = $request->validate([
            'pagado_el' => ['required', 'date', 'before_or_equal:today'],
            'monto' => ['required', 'numeric', 'min:0', 'max:99999999'],
            'moneda' => ['required', Rule::in(['GTQ', 'USD'])],
            'metodo' => ['required', Rule::in(array_keys(PagoLicencia::METODOS))],
            'referencia' => ['nullable', 'string', 'max:120'],
            'meses' => ['required', 'integer', 'min:1', 'max:36'],
            'notas' => ['nullable', 'string', 'max:500'],
        ]);
        if ($licencia->tipo === 'venta') {
            return back()->withErrors(['pago' => 'Una instalación vendida no tiene vencimiento: no lleva pagos de licencia.']);
        }
        $pago = $this->licencias->registrarPago($licencia, $datos, $request->user()->id);

        return back()->with('status', 'Pago registrado: pagado hasta el '.$pago->vence_despues->format('d/m/Y').'.'.$this->resultadoAviso($licencia));
    }

    public function anularPago(PagoLicencia $pago): RedirectResponse
    {
        try {
            $this->licencias->anularPago($pago);
        } catch (RuntimeException $e) {
            return back()->withErrors(['pago' => $e->getMessage()]);
        }

        return back()->with('status', 'Pago anulado: el vencimiento volvió al anterior.'.$this->resultadoAviso($pago->instalacion));
    }

    public function prorroga(Request $request, Instalacion $licencia): RedirectResponse
    {
        $dias = (int) $request->validate(['dias' => ['required', 'integer', 'min:1', 'max:90']])['dias'];
        $this->licencias->prorrogar($licencia, $dias);

        return back()->with('status', "Prórroga de {$dias} días: hasta el ".$licencia->prorroga_hasta->format('d/m/Y').'.'.$this->resultadoAviso($licencia));
    }

    public function quitarProrroga(Instalacion $licencia): RedirectResponse
    {
        $licencia->update(['prorroga_hasta' => null]);
        $this->licencias->avisar($licencia);

        return back()->with('status', 'Prórroga quitada.'.$this->resultadoAviso($licencia));
    }

    public function modo(Request $request, Instalacion $licencia): RedirectResponse
    {
        $modo = $request->validate(['modo' => ['required', Rule::in(array_keys(Instalacion::MODOS))]])['modo'];
        $this->licencias->cambiarModo($licencia, $modo);

        return back()->with('status', 'Modo: '.Instalacion::MODOS[$modo][0].'.'.$this->resultadoAviso($licencia));
    }

    public function avisar(Instalacion $licencia): RedirectResponse
    {
        return $this->licencias->avisar($licencia)
            ? back()->with('status', 'La instalación consultó y aplicó su licencia.')
            : back()->withErrors(['aviso' => 'No se pudo: '.$licencia->aviso_resultado]);
    }

    public function token(Instalacion $licencia): RedirectResponse
    {
        $token = $this->licencias->nuevoToken($licencia);

        return back()->with('status', 'Token nuevo generado: el anterior ya no sirve. Ponlo en el .env de la instalación.')->with('token_licencia', $token);
    }

    public function configuracion(Request $request): RedirectResponse
    {
        ConfiguracionLicencias::actual()->update($request->validate([
            'contacto' => ['nullable', 'string', 'max:255'],
            'enlace_pago' => ['nullable', 'string', 'max:500', 'starts_with:https://'],
            'dias_aviso' => ['required', 'integer', 'min:0', 'max:365'],
            'dias_gracia' => ['required', 'integer', 'min:0', 'max:365'],
            'dias_restringido' => ['required', 'integer', 'min:0', 'max:365'],
        ], ['enlace_pago.starts_with' => 'El enlace de pago debe empezar con https://']));

        return back()->with('status', 'Configuración guardada. Las instalaciones la reciben en su próxima consulta.');
    }

    private function resultadoAviso(Instalacion $i): string
    {
        $i->refresh();

        return $i->aviso_resultado === 'Aplicada'
            ? ' La instalación ya lo aplicó.'
            : ' No se pudo avisar a la instalación ('.$i->aviso_resultado.'): lo tomará en su próxima consulta (máximo 6 horas).';
    }

    private function contratos()
    {
        return Contrato::query()->with(['sistema', 'cuenta', 'cliente'])->latest('id')->get()
            ->mapWithKeys(fn (Contrato $c) => [$c->id => collect([$c->cliente?->empresa ?? $c->cuenta?->nombre ?? $c->cuenta?->correo, $c->sistema?->nombre, $c->plan])->filter()->implode(' · ')
                .' — '.($c->hasta ? 'hasta '.$c->hasta->format('d/m/Y') : 'sin vencimiento')]);
    }

    private function validar(Request $request, ?Instalacion $actual = null): array
    {
        $datos = $request->validate([
            'nombre' => ['required', 'string', 'max:150'],
            'identificador' => ['required', 'string', 'regex:/^[a-z0-9][a-z0-9-]{1,39}$/', Rule::unique('instalaciones', 'identificador')->ignore($actual?->id)],
            'url' => ['required', 'url:http,https', 'max:255'],
            'tipo' => ['required', Rule::in(array_keys(Instalacion::TIPOS))],
            'contrato_id' => ['nullable', 'exists:contratos,id'],
            'correo_cobro' => ['nullable', 'email', 'max:150'],
            'telefono' => ['nullable', 'string', 'max:30'],
            'monto' => ['nullable', 'numeric', 'min:0', 'max:99999999'],
            'moneda' => ['required', Rule::in(['GTQ', 'USD'])],
            'meses_periodo' => ['required', 'integer', 'min:1', 'max:36'],
            'vence_el' => ['nullable', 'date'],
            'dias_aviso' => ['nullable', 'integer', 'min:0', 'max:365'],
            'dias_gracia' => ['nullable', 'integer', 'min:0', 'max:365'],
            'dias_restringido' => ['nullable', 'integer', 'min:0', 'max:365'],
            'contacto' => ['nullable', 'string', 'max:255'],
            'enlace_pago' => ['nullable', 'string', 'max:500', 'starts_with:https://'],
            'notas' => ['nullable', 'string', 'max:3000'],
        ], [
            'identificador.regex' => 'El identificador va en minúsculas, sin espacios: letras, números y guiones (p. ej. «acme»). Debe ser el mismo NEXUS_LICENCIA_ID de la instalación.',
            'enlace_pago.starts_with' => 'El enlace de pago debe empezar con https://',
        ]);

        return array_merge($datos, ['bloquear_facturacion' => $request->boolean('bloquear_facturacion')]);
    }
}
