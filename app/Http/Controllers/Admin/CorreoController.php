<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ConfiguracionCorreo;
use App\Models\PlantillaCorreo;
use App\Services\Correos;
use App\Support\Sitio;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Panel → Correos (igual que en el restaurante): servidor SMTP, correo de
 * prueba, plantillas y bitácora de envíos.
 */
class CorreoController extends Controller
{
    public const ESTADOS = [
        'enviado' => ['success', 'Enviado'], 'fallido' => ['danger', 'Falló'],
        'desactivado' => ['warning', 'Servidor apagado'], 'sin_plantilla' => ['secondary', 'Sin plantilla'],
    ];

    public function __construct(private readonly Correos $correos) {}

    public function index(Request $request): View
    {
        $estado = array_key_exists($request->query('estado'), self::ESTADOS) ? $request->query('estado') : null;

        return view('correos.index', [
            'cfg' => ConfiguracionCorreo::actual(),
            'plantillas' => PlantillaCorreo::query()->orderByDesc('del_sistema')->orderBy('nombre')->get(),
            'bitacora' => DB::table('bitacora_correos')->when($estado, fn ($q) => $q->where('estado', $estado))->orderByDesc('id')->paginate(25)->withQueryString(),
            'conteos' => DB::table('bitacora_correos')->select('estado', DB::raw('count(*) as total'))->groupBy('estado')->pluck('total', 'estado'),
            'estado' => $estado,
            'pestana' => in_array($request->query('ver'), ['servidor', 'plantillas', 'bitacora'], true) ? $request->query('ver') : ($estado ? 'bitacora' : 'servidor'),
        ]);
    }

    public function guardar(Request $request): RedirectResponse
    {
        $datos = $request->validate([
            'host' => ['nullable', 'required_if:is_active,1', 'string', 'max:150'],
            'puerto' => ['nullable', 'required_if:is_active,1', 'integer', 'between:1,65535'],
            'usuario' => ['nullable', 'string', 'max:200'],
            'clave' => ['nullable', 'string', 'max:500'],
            'cifrado' => ['nullable', Rule::in(['tls', 'ssl'])],
            'remitente_correo' => ['nullable', 'required_if:is_active,1', 'email', 'max:150'],
            'remitente_nombre' => ['nullable', 'string', 'max:120'],
            'responder_a' => ['nullable', 'email', 'max:150'],
            'avisos_a' => ['nullable', 'string', 'max:500'],
        ], [
            'host.required_if' => 'Para activar el servidor indica el host (p. ej. smtp.gmail.com).',
            'puerto.required_if' => 'Indica el puerto (587 con TLS o 465 con SSL).',
            'remitente_correo.required_if' => 'Indica el correo que aparece como remitente.',
        ]);
        $invalidos = array_filter(array_map('trim', explode(',', (string) ($datos['avisos_a'] ?? ''))), fn ($c) => $c !== '' && ! filter_var($c, FILTER_VALIDATE_EMAIL));
        if ($invalidos) {
            return back()->withInput()->withErrors(['avisos_a' => 'Correo no válido en «Avisos a»: '.implode(', ', $invalidos)]);
        }

        $cfg = ConfiguracionCorreo::actual();
        if (($datos['clave'] ?? '') === '') {
            unset($datos['clave']); // vacío = conserva la actual
        }
        $cfg->update($datos + ['is_active' => $request->boolean('is_active'), 'cifrado' => $datos['cifrado'] ?? null]);

        return redirect()->route('admin.correos.index', ['ver' => 'servidor'])->with('status', 'Servidor de correo guardado.');
    }

    public function probar(Request $request): RedirectResponse
    {
        $request->validate(['destinatario' => ['required', 'email']]);
        $html = view('correos.prueba')->render();
        $ok = $this->correos->mandar(['asunto' => 'Correo de prueba · '.Sitio::nombre(), 'html' => $html], $request->input('destinatario'), '_prueba', $request->user()->id);
        if ($ok) {
            ConfiguracionCorreo::actual()->update(['probado_en' => now()]);
        }

        return redirect()->route('admin.correos.index', ['ver' => 'servidor'])->with($ok ? 'status' : 'aviso', $ok
            ? "Correo de prueba enviado a {$request->input('destinatario')}. Revisa la bandeja (y el correo no deseado)."
            : 'No se pudo enviar. El detalle está en la Bitácora.');
    }

    // ── Plantillas ──────────────────────────────────────────────────────────

    public function create(): View
    {
        return view('correos.plantilla', ['plantilla' => new PlantillaCorreo(['is_active' => true, 'variables' => []])]);
    }

    public function store(Request $request): RedirectResponse
    {
        $p = new PlantillaCorreo($this->validar($request));
        $p->save();

        return redirect()->route('admin.correos.plantillas.edit', $p)->with('status', 'Plantilla creada.');
    }

    public function edit(PlantillaCorreo $plantilla): View
    {
        return view('correos.plantilla', ['plantilla' => $plantilla]);
    }

    public function update(Request $request, PlantillaCorreo $plantilla): RedirectResponse
    {
        $plantilla->update($this->validar($request, $plantilla));

        return back()->with('status', 'Plantilla guardada.');
    }

    public function destroy(PlantillaCorreo $plantilla): RedirectResponse
    {
        abort_if($plantilla->del_sistema, 422, 'Esta plantilla la usa el sistema: se puede editar o desactivar, pero no borrar.');
        $plantilla->delete();

        return redirect()->route('admin.correos.index', ['ver' => 'plantillas'])->with('status', 'Plantilla borrada.');
    }

    /** Vista previa con datos de ejemplo, dentro del marco del sistema. */
    public function vistaPrevia(PlantillaCorreo $plantilla): Response
    {
        $render = $plantilla->render($this->ejemplo($plantilla));

        return response(view('correos.marco', ['cuerpo' => $render['html']])->render())
            ->header('Content-Security-Policy', "script-src 'none'");
    }

    public function probarPlantilla(Request $request, PlantillaCorreo $plantilla): RedirectResponse
    {
        $request->validate(['destinatario' => ['required', 'email']]);
        $render = $plantilla->render($this->ejemplo($plantilla));
        $ok = $this->correos->mandar(['asunto' => '[PRUEBA] '.$render['asunto'], 'html' => $render['html']], $request->input('destinatario'), $plantilla->codigo, $request->user()->id);

        return back()->with($ok ? 'status' : 'aviso', $ok ? "Prueba enviada a {$request->input('destinatario')}." : 'No se pudo enviar. El detalle está en la Bitácora.');
    }

    private function validar(Request $request, ?PlantillaCorreo $plantilla = null): array
    {
        $datos = $request->validate([
            'codigo' => [Rule::requiredIf(! $plantilla?->del_sistema), 'nullable', 'string', 'max:60', 'regex:/^[a-z0-9_]+$/', Rule::unique('plantillas_correo', 'codigo')->ignore($plantilla)],
            'nombre' => ['required', 'string', 'max:120'],
            'descripcion' => ['nullable', 'string', 'max:255'],
            'asunto' => ['required', 'string', 'max:200'],
            'contenido' => ['required', 'string', 'max:100000'],
        ], ['codigo.regex' => 'El código solo lleva minúsculas, números y guion bajo (p. ej. «promocion_mensual»).']);
        // Las variables se detectan del texto.
        preg_match_all('/\{\{\s*(\w+)\s*\}\}/', $datos['asunto'].' '.$datos['contenido'], $m);

        return [
            ...collect($datos)->except($plantilla?->del_sistema ? ['codigo'] : [])->all(),
            'variables' => array_values(array_unique($m[1])),
            'is_active' => $request->boolean('is_active'),
        ];
    }

    /** @return array<string, string> */
    private function ejemplo(PlantillaCorreo $p): array
    {
        $valores = [
            'nombre' => 'María López', 'enlace' => url('/'), 'minutos' => '60', 'correo' => 'maria@ejemplo.com', 'empresa' => 'Café Central', 'telefono' => '5555 5555', 'sistema_interes' => 'Sistema para Restaurantes',
            'mensaje' => 'Me interesa una demostración del sistema.', 'fecha' => now()->format('d/m/Y H:i'),
            'error' => 'mysqldump falló: Access denied for user (ejemplo).', 'dias' => '7',
        ] + $this->correos->base();

        return collect($p->variables ?? [])->mapWithKeys(fn ($v) => [$v => $valores[$v] ?? "[{$v}]"])->all() + $this->correos->base();
    }
}
