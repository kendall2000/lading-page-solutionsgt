<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Cliente;
use App\Models\Contrato;
use App\Models\Cuenta;
use App\Models\Sistema;
use App\Services\Cuentas;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/** Cuentas de clientes del portal «Mi cuenta»: invitar, editar, ligar a una empresa y asignar sistemas contratados. */
class CuentaClienteController extends Controller
{
    public const FILTROS = ['activas' => 'Activas', 'sin_confirmar' => 'Sin confirmar', 'invitadas' => 'Invitadas', 'desactivadas' => 'Desactivadas'];

    public function __construct(private readonly Cuentas $cuentas) {}

    public function index(Request $request): View
    {
        $buscar = trim((string) $request->query('buscar'));
        $filtro = array_key_exists((string) $request->query('estado'), self::FILTROS) ? $request->query('estado') : null;

        return view('admin.cuentas.index', [
            'cuentas' => Cuenta::query()->with('cliente')->withCount(['conversaciones', 'solicitudes'])
                ->when($buscar !== '', fn ($q) => $q->where(fn ($w) => $w->where('nombre', 'like', "%{$buscar}%")
                    ->orWhere('correo', 'like', "%{$buscar}%")->orWhere('empresa', 'like', "%{$buscar}%")))
                ->when($filtro === 'activas', fn ($q) => $q->where('activa', true)->whereNotNull('correo_verificado_en'))
                ->when($filtro === 'sin_confirmar', fn ($q) => $q->where('activa', true)->whereNull('correo_verificado_en')->whereNull('invitada_en'))
                ->when($filtro === 'invitadas', fn ($q) => $q->where('activa', true)->whereNull('correo_verificado_en')->whereNotNull('invitada_en'))
                ->when($filtro === 'desactivadas', fn ($q) => $q->where('activa', false))
                ->latest()->paginate(20)->withQueryString(),
            'buscar' => $buscar,
            'filtro' => $filtro,
            'totales' => [
                'todas' => Cuenta::query()->count(),
                'activas' => Cuenta::query()->where('activa', true)->whereNotNull('correo_verificado_en')->count(),
            ],
        ]);
    }

    public function create(Request $request): View
    {
        return view('admin.cuentas.form', [
            'cuenta' => new Cuenta(['activa' => true, 'cliente_id' => $request->integer('cliente') ?: null]),
            'clientes' => $this->clientes(),
        ]);
    }

    /** Crear = invitar: la persona recibe un correo para crear su contraseña. */
    public function store(Request $request): RedirectResponse
    {
        $cuenta = Cuenta::query()->create($this->validar($request));
        $enviado = $request->boolean('invitar', true) ? $this->cuentas->invitar($cuenta) : null;

        return redirect()->route('admin.cuentas.edit', $cuenta)->with('status', match ($enviado) {
            true => 'Cuenta creada. Le enviamos la invitación a '.$cuenta->correo.'.',
            false => 'Cuenta creada, pero la invitación no salió (revisa Panel → Correos). Puedes reenviarla.',
            null => 'Cuenta creada sin invitación. Puede entrar con un enlace por correo.',
        });
    }

    public function edit(Cuenta $cuenta): View
    {
        return view('admin.cuentas.form', [
            'cuenta' => $cuenta->load('cliente'),
            'clientes' => $this->clientes(),
            'contratos' => $cuenta->contratos()->with('cliente')->get(),
            'sistemas' => Sistema::query()->orderBy('nombre')->pluck('nombre', 'id'),
            'conversaciones' => $cuenta->conversaciones()->limit(5)->get(),
            'solicitudes' => $cuenta->solicitudes()->with('sistema')->limit(5)->get(),
        ]);
    }

    public function update(Request $request, Cuenta $cuenta): RedirectResponse
    {
        $datos = $this->validar($request, $cuenta);
        $cambioCorreo = $datos['correo'] !== $cuenta->correo;
        $cuenta->update($datos);
        // Otro correo: hay que confirmarlo de nuevo (y los enlaces pendientes dejan de servir).
        if ($cambioCorreo) {
            $cuenta->forceFill(['correo_verificado_en' => null, 'token_hash' => null, 'token_tipo' => null, 'token_vence' => null])->save();
            $this->cuentas->enviarConfirmacion($cuenta);
        }

        return back()->with('status', $cambioCorreo ? 'Cuenta guardada. Le enviamos un correo para confirmar la dirección nueva.' : 'Cuenta guardada.');
    }

    public function destroy(Cuenta $cuenta): RedirectResponse
    {
        // Sus chats y solicitudes se quedan en el panel (solo pierden el vínculo con la cuenta).
        $cuenta->delete();

        return redirect()->route('admin.cuentas.index')->with('status', 'Cuenta eliminada.');
    }

    public function invitar(Cuenta $cuenta): RedirectResponse
    {
        abort_unless($cuenta->activa, 422, 'La cuenta está desactivada.');

        return back()->with('status', $this->cuentas->invitar($cuenta)
            ? 'Invitación enviada a '.$cuenta->correo.'.'
            : 'La invitación no salió: revisa Panel → Correos.');
    }

    public function agregarContrato(Request $request, Cuenta $cuenta): RedirectResponse
    {
        $datos = $request->validate([
            'sistema_id' => ['required', 'exists:sistemas,id'],
            'url_acceso' => ['nullable', 'url:https,http', 'max:255'],
            'plan' => ['nullable', 'string', 'max:120'],
            'desde' => ['nullable', 'date'],
            'hasta' => ['nullable', 'date', 'after_or_equal:desde'],
            'notas' => ['nullable', 'string', 'max:2000'],
        ], [], ['sistema_id' => 'sistema', 'url_acceso' => 'dirección de acceso', 'hasta' => 'vence']);
        // «Para toda la empresa»: lo ven todas las cuentas ligadas a esa empresa.
        $empresa = $request->boolean('para_empresa') && $cuenta->cliente_id;
        Contrato::query()->create($datos + ['cuenta_id' => $empresa ? null : $cuenta->id, 'cliente_id' => $empresa ? $cuenta->cliente_id : null]);

        return back()->with('status', 'Sistema agregado a la cuenta.');
    }

    public function actualizarContrato(Request $request, Contrato $contrato): RedirectResponse
    {
        $contrato->update($request->validate([
            'url_acceso' => ['nullable', 'url:https,http', 'max:255'],
            'plan' => ['nullable', 'string', 'max:120'],
            'desde' => ['nullable', 'date'],
            'hasta' => ['nullable', 'date', 'after_or_equal:desde'],
            'notas' => ['nullable', 'string', 'max:2000'],
        ], [], ['url_acceso' => 'dirección de acceso', 'hasta' => 'vence']));

        return back()->with('status', 'Sistema contratado actualizado.');
    }

    public function quitarContrato(Contrato $contrato): RedirectResponse
    {
        $contrato->delete();

        return back()->with('status', 'Sistema quitado.');
    }

    private function clientes()
    {
        return Cliente::query()->orderBy('empresa')->pluck('empresa', 'id');
    }

    private function validar(Request $request, ?Cuenta $cuenta = null): array
    {
        $request->merge(['correo' => Str::lower(trim((string) $request->input('correo')))]);
        $datos = $request->validate([
            'nombre' => ['required', 'string', 'max:120'],
            'correo' => ['required', 'email', 'max:150', Rule::unique('cuentas', 'correo')->ignore($cuenta)],
            'telefono' => ['nullable', 'string', 'max:30'],
            'empresa' => ['nullable', 'string', 'max:150'],
            'cliente_id' => ['nullable', 'exists:clientes,id'],
        ], ['correo.unique' => 'Ya hay una cuenta con ese correo.'], ['cliente_id' => 'empresa']);

        return $datos + ['activa' => $request->boolean('activa')];
    }
}
